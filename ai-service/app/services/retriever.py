"""
Knowledge document retrieval using pgvector.

Provides embedding generation, similarity search, corpus seeding,
and graceful fallback with 4 explicit retrieval modes:

  1. pgvector           — full vector similarity search
  2. fallback_db_no_embedding — DB available but embeddings missing
  3. fallback_db_unavailable  — DB connection failed
  4. fallback_static    — no DB, using in-memory corpus

Phase 18: operational hardening — clearer status, backfill reporting,
runtime mode tracking.
"""
from __future__ import annotations

import json
import logging
import os
import time
from dataclasses import dataclass, field
from typing import Any

logger = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# Retrieval result with explicit metadata
# ---------------------------------------------------------------------------


@dataclass
class RetrievalResult:
    """Structured retrieval output with provenance metadata."""

    evidence: list[dict[str, Any]]
    retrieval_method: str  # pgvector | fallback_db_no_embedding | fallback_db_unavailable | fallback_static
    doc_count: int = 0
    embedding_available: bool = False
    latency_ms: float = 0.0
    notes: list[str] = field(default_factory=list)

    def to_legacy(self) -> tuple[list[dict[str, Any]], str]:
        """Backward-compatible tuple output for existing callers."""
        return self.evidence, self.retrieval_method


# ---------------------------------------------------------------------------
# Static fallback corpus — used when DB or embeddings are unavailable
# ---------------------------------------------------------------------------

_STATIC_CORPUS: list[dict[str, Any]] = [
    {
        "source": "korean-hiring-guide",
        "excerpt": "Korean employers value clear role-fit evidence and practical project outcomes.",
        "score": 0.6,
        "retrieval_method": "fallback_static",
    },
    {
        "source": "jd-best-practices",
        "excerpt": "Candidates with 60-70% core skills and high learning velocity can still be top performers.",
        "score": 0.5,
        "retrieval_method": "fallback_static",
    },
    {
        "source": "tech-evaluation-note",
        "excerpt": "For AI hiring products, explainability and citation-backed ranking improve trust.",
        "score": 0.4,
        "retrieval_method": "fallback_static",
    },
]

# ---------------------------------------------------------------------------
# Seed documents for initial corpus
# ---------------------------------------------------------------------------

_SEED_DOCUMENTS: list[dict[str, Any]] = [
    {
        "source": "korean-hiring-guide",
        "title": "Korean Hiring Culture Guide",
        "content": (
            "Korean employers value clear role-fit evidence and practical project outcomes. "
            "In Korean corporate culture, demonstrating specific achievements and quantifiable "
            "results is highly valued during the hiring process. Companies often prioritize "
            "candidates who show cultural adaptability and team collaboration skills alongside "
            "technical competence."
        ),
        "metadata": {"tags": ["culture", "hiring", "fit", "korea"], "language": "en"},
    },
    {
        "source": "jd-best-practices",
        "title": "Job Description Evaluation Best Practices",
        "content": (
            "Candidates with 60-70% core skills and high learning velocity can still be top "
            "performers. When evaluating job descriptions, focus on distinguishing must-have "
            "requirements from nice-to-have preferences. A well-structured JD clearly separates "
            "required qualifications from preferred skills."
        ),
        "metadata": {"tags": ["skills", "matching", "jd"], "language": "en"},
    },
    {
        "source": "tech-evaluation-note",
        "title": "Technical Evaluation Standards for AI Products",
        "content": (
            "For AI hiring products, explainability and citation-backed ranking improve trust. "
            "Technical assessments should evaluate problem-solving approach, code quality, and "
            "system design thinking. Automated scoring should always be transparent about "
            "confidence levels and methodology."
        ),
        "metadata": {"tags": ["ai", "explainability", "evaluation"], "language": "en"},
    },
    {
        "source": "seniority-mapping-guide",
        "title": "Seniority Level Mapping Guide",
        "content": (
            "Seniority levels across companies vary significantly. A 'Senior' at a startup may "
            "correspond to 'Mid' at a large enterprise. When matching candidates to roles, "
            "consider years of experience, scope of responsibility, and technical depth rather "
            "than title alone. Typical mapping: Intern (0-1yr), Junior (1-3yr), Mid (3-5yr), "
            "Senior (5-8yr), Lead/Staff (8+yr)."
        ),
        "metadata": {"tags": ["seniority", "experience", "mapping"], "language": "en"},
    },
    {
        "source": "skills-assessment-framework",
        "title": "Technical Skills Assessment Framework",
        "content": (
            "When assessing technical skills for software engineering roles, categorize skills "
            "into: core programming languages, frameworks and libraries, infrastructure and "
            "DevOps, databases, and domain-specific tools. Required skills should map to daily "
            "job functions, while preferred skills indicate growth potential."
        ),
        "metadata": {"tags": ["skills", "assessment", "technical"], "language": "en"},
    },
    {
        "source": "experience-evaluation-criteria",
        "title": "Experience Evaluation Criteria",
        "content": (
            "Experience quality matters more than quantity. Evaluate: relevance of past projects "
            "to the target role, complexity of problems solved, scale of systems worked on, and "
            "evidence of continuous learning. A candidate with 3 years of highly relevant "
            "experience may outperform one with 7 years in a loosely related field."
        ),
        "metadata": {"tags": ["experience", "evaluation", "quality"], "language": "en"},
    },
]


# ---------------------------------------------------------------------------
# Module-level retrieval mode tracking
# ---------------------------------------------------------------------------

_last_retrieval_mode: str = "not_yet_used"
_last_seed_status: dict[str, Any] = {}


# ---------------------------------------------------------------------------
# Retriever
# ---------------------------------------------------------------------------


class KnowledgeRetriever:
    """Retrieves grounding evidence from knowledge_documents via pgvector.

    Retrieval modes (in priority order):
      1. pgvector — full vector similarity (DB + embeddings available)
      2. fallback_db_no_embedding — DB up but embedding generation failed
      3. fallback_db_unavailable — DB connection failed
      4. fallback_static — no DB at all, use in-memory corpus
    """

    def __init__(self) -> None:
        from app.services.db import VECTOR_DIM

        self._vector_dim = VECTOR_DIM
        self._embedding_model = os.getenv("EMBEDDING_MODEL", "text-embedding-3-small")
        api_key = os.getenv("OPENAI_API_KEY", "")
        self._openai_client = None
        self._embedding_available = False
        if api_key:
            try:
                import openai
                self._openai_client = openai.AsyncOpenAI(api_key=api_key)
                self._embedding_available = True
            except Exception as exc:
                logger.warning("Failed to init OpenAI client for embeddings: %s", exc)

    async def embed_text(self, text: str) -> list[float] | None:
        """Generate embedding vector using OpenAI. Returns None on failure."""
        if not self._openai_client:
            return None
        try:
            response = await self._openai_client.embeddings.create(
                model=self._embedding_model,
                input=text[:8000],
            )
            return response.data[0].embedding
        except Exception as exc:
            logger.warning("Embedding generation failed: %s", exc)
            return None

    # --- Core retrieval with explicit mode tracking ---

    async def _retrieve_pgvector(
        self, query: str, top_k: int, pool,
    ) -> RetrievalResult | None:
        """Attempt full pgvector similarity search. Returns None if not possible."""
        # Check pgvector availability first
        from app.services.db import check_pgvector_available
        if not await check_pgvector_available():
            return None

        embedding = await self.embed_text(query)
        if embedding is None:
            return None  # can't do vector search without embedding

        try:
            rows = await pool.fetch(
                """
                SELECT source, title, content,
                       1 - (embedding <=> $1::vector) AS similarity
                FROM knowledge_documents
                WHERE embedding IS NOT NULL
                ORDER BY embedding <=> $1::vector
                LIMIT $2
                """,
                str(embedding),
                top_k,
            )
        except Exception as exc:
            logger.warning("pgvector query failed: %s", exc)
            return None

        if not rows:
            return None

        evidence = [
            {
                "source": row["source"],
                "excerpt": row["content"][:300],
                "score": round(float(row["similarity"]), 4),
                "retrieval_method": "pgvector",
            }
            for row in rows
        ]
        return RetrievalResult(
            evidence=evidence,
            retrieval_method="pgvector",
            doc_count=len(rows),
            embedding_available=True,
        )

    async def _retrieve_db_text(self, query: str, top_k: int, pool) -> RetrievalResult:
        """Fallback: keyword-based retrieval from DB when embeddings unavailable."""
        # Simple text match — very basic, but better than static corpus
        keywords = [w.lower() for w in query.split() if len(w) > 2][:5]
        if not keywords:
            return RetrievalResult(
                evidence=list(_STATIC_CORPUS),
                retrieval_method="fallback_db_no_embedding",
                doc_count=len(_STATIC_CORPUS),
                notes=["No query keywords, using static corpus"],
            )

        # Check if table has content column (it might have been created without vector)
        try:
            # Build ILIKE conditions for keyword search
            conditions = " OR ".join(
                f"LOWER(content) LIKE '%' || ${i+1} || '%'"
                for i in range(len(keywords))
            )
            query_sql = f"""
                SELECT source, title, content
                FROM knowledge_documents
                WHERE {conditions}
                LIMIT ${ len(keywords) + 1 }
            """
            rows = await pool.fetch(query_sql, *keywords, top_k)
        except Exception as exc:
            logger.warning("DB keyword search failed: %s", exc)
            return RetrievalResult(
                evidence=list(_STATIC_CORPUS),
                retrieval_method="fallback_db_no_embedding",
                doc_count=len(_STATIC_CORPUS),
                notes=[f"DB keyword search error: {exc}"],
            )

        if not rows:
            return RetrievalResult(
                evidence=list(_STATIC_CORPUS),
                retrieval_method="fallback_db_no_embedding",
                doc_count=len(_STATIC_CORPUS),
                notes=["DB keyword search returned no results, using static corpus"],
            )

        evidence = [
            {
                "source": row["source"],
                "excerpt": row["content"][:300],
                "score": 0.3,  # low confidence for text-only match
                "retrieval_method": "fallback_db_no_embedding",
            }
            for row in rows
        ]
        return RetrievalResult(
            evidence=evidence,
            retrieval_method="fallback_db_no_embedding",
            doc_count=len(rows),
            embedding_available=False,
            notes=["Embeddings unavailable, used keyword fallback"],
        )

    async def retrieve(
        self, query: str, top_k: int = 5,
    ) -> tuple[list[dict[str, Any]], str]:
        """Retrieve relevant knowledge documents.

        Returns:
            (evidence_list, retrieval_method)
            retrieval_method is one of:
              - pgvector
              - fallback_db_no_embedding
              - fallback_db_unavailable
              - fallback_static
        """
        start = time.monotonic()
        result = await self.retrieve_full(query, top_k)
        return result.to_legacy()

    async def retrieve_full(
        self, query: str, top_k: int = 5,
    ) -> RetrievalResult:
        """Full retrieval with structured metadata."""
        global _last_retrieval_mode
        start = time.monotonic()

        # Check if forced static mode
        if os.getenv("RETRIEVAL_FALLBACK_ONLY", "").strip().lower() in ("true", "1", "yes"):
            _last_retrieval_mode = "fallback_static"
            return RetrievalResult(
                evidence=list(_STATIC_CORPUS),
                retrieval_method="fallback_static",
                doc_count=len(_STATIC_CORPUS),
                latency_ms=(time.monotonic() - start) * 1000,
                notes=["RETRIEVAL_FALLBACK_ONLY is enabled"],
            )

        try:
            from app.services.db import get_pool
            pool = await get_pool()

            # Try pgvector first
            pgv_result = await self._retrieve_pgvector(query, top_k, pool)
            if pgv_result is not None:
                pgv_result.latency_ms = (time.monotonic() - start) * 1000
                _last_retrieval_mode = "pgvector"
                return pgv_result

            # pgvector failed (no embedding) — try text-based DB search
            logger.info("pgvector retrieval unavailable, trying DB text fallback")
            db_result = await self._retrieve_db_text(query, top_k, pool)
            db_result.latency_ms = (time.monotonic() - start) * 1000
            _last_retrieval_mode = db_result.retrieval_method
            return db_result

        except Exception as exc:
            elapsed = (time.monotonic() - start) * 1000
            logger.warning("DB retrieval failed (%s), using static fallback", exc)
            _last_retrieval_mode = "fallback_static"
            return RetrievalResult(
                evidence=list(_STATIC_CORPUS),
                retrieval_method="fallback_static",
                doc_count=len(_STATIC_CORPUS),
                embedding_available=False,
                latency_ms=elapsed,
                notes=[f"DB unavailable: {exc}"],
            )

    async def seed_and_backfill(self) -> dict[str, Any]:
        """Seed corpus if empty, and backfill NULL embeddings when possible.

        Handles three startup scenarios:
          1. Table is empty → insert seed docs (with embeddings if available).
          2. Docs exist but some have NULL embeddings and embedding generation
             is now available → backfill those embeddings.
          3. All docs already have embeddings → skip (no-op).

        Returns a status dict for startup logging.
        """
        global _last_seed_status
        status: dict[str, Any] = {
            "action": "none",
            "total_docs": 0,
            "embedded_docs": 0,
            "seeded": 0,
            "backfilled": 0,
            "embedding_available": self._embedding_available,
            "error": None,
        }

        try:
            from app.services.db import get_pool

            pool = await get_pool()

            # --- Step 1: Seed if table is empty ---
            count = await pool.fetchval("SELECT COUNT(*) FROM knowledge_documents")
            status["total_docs"] = count

            if count == 0:
                logger.info(
                    "Seeding knowledge_documents with %d initial documents",
                    len(_SEED_DOCUMENTS),
                )
                seeded = 0
                seeded_with_embedding = 0
                for doc in _SEED_DOCUMENTS:
                    embedding = await self.embed_text(doc["content"])
                    try:
                        # Try with embedding column first (pgvector available)
                        await pool.execute(
                            """
                            INSERT INTO knowledge_documents (source, title, content, metadata, embedding)
                            VALUES ($1, $2, $3, $4::jsonb, $5::vector)
                            """,
                            doc["source"],
                            doc["title"],
                            doc["content"],
                            json.dumps(doc["metadata"]),
                            str(embedding) if embedding else None,
                        )
                        seeded += 1
                        if embedding:
                            seeded_with_embedding += 1
                    except Exception as ins_exc:
                        # Vector column might not exist — try without it
                        try:
                            await pool.execute(
                                """
                                INSERT INTO knowledge_documents (source, title, content, metadata)
                                VALUES ($1, $2, $3, $4::jsonb)
                                """,
                                doc["source"],
                                doc["title"],
                                doc["content"],
                                json.dumps(doc["metadata"]),
                            )
                            seeded += 1
                        except Exception as ins2_exc:
                            logger.warning("Failed to seed doc '%s': %s", doc["source"], ins2_exc)

                status["action"] = "seeded"
                status["seeded"] = seeded
                status["total_docs"] = seeded
                status["embedded_docs"] = seeded_with_embedding
                logger.info(
                    "Seed complete: %d docs inserted (%d with embeddings, %d without)",
                    seeded, seeded_with_embedding, seeded - seeded_with_embedding,
                )
                _last_seed_status = status
                return status

            # --- Step 2: Count embedded docs ---
            try:
                embedded_count = await pool.fetchval(
                    "SELECT COUNT(*) FROM knowledge_documents WHERE embedding IS NOT NULL"
                )
            except Exception:
                # embedding column might not exist
                embedded_count = 0
            status["embedded_docs"] = embedded_count
            null_count = count - embedded_count

            if null_count == 0:
                status["action"] = "nothing_to_do"
                logger.info(
                    "knowledge_documents: %d docs, all with embeddings — fully operational",
                    count,
                )
                _last_seed_status = status
                return status

            if not self._embedding_available:
                status["action"] = "backfill_deferred"
                logger.info(
                    "knowledge_documents: %d docs, %d without embeddings "
                    "(OPENAI_API_KEY not set — embeddings unavailable, will retry on next boot)",
                    count, null_count,
                )
                _last_seed_status = status
                return status

            # Embedding client is available — backfill missing embeddings
            logger.info(
                "Backfilling embeddings for %d/%d documents", null_count, count,
            )
            try:
                rows = await pool.fetch(
                    "SELECT id, content FROM knowledge_documents WHERE embedding IS NULL"
                )
            except Exception:
                # embedding column might not exist — nothing to backfill
                status["action"] = "backfill_skipped"
                status["error"] = "embedding column not available"
                _last_seed_status = status
                return status

            backfilled = 0
            failed = 0
            for row in rows:
                embedding = await self.embed_text(row["content"])
                if embedding is not None:
                    try:
                        await pool.execute(
                            """
                            UPDATE knowledge_documents
                            SET embedding = $1::vector, updated_at = NOW()
                            WHERE id = $2
                            """,
                            str(embedding),
                            row["id"],
                        )
                        backfilled += 1
                    except Exception as upd_exc:
                        failed += 1
                        logger.warning("Embedding update failed for doc %d: %s", row["id"], upd_exc)
                else:
                    failed += 1

            status["action"] = "backfilled"
            status["backfilled"] = backfilled
            status["embedded_docs"] = embedded_count + backfilled
            logger.info(
                "Embedding backfill complete: %d/%d updated, %d failed",
                backfilled, null_count, failed,
            )
            if failed > 0:
                status["backfill_failures"] = failed

        except Exception as exc:
            status["action"] = "error"
            status["error"] = str(exc)
            logger.warning(
                "Corpus seed/backfill failed: %s (static fallback remains available)",
                exc,
            )

        _last_seed_status = status
        return status

    async def get_status(self) -> dict[str, Any]:
        """Return retrieval subsystem status (no secrets).

        Reports:
          - embedding_model: configured model name
          - embedding_client_available: whether OpenAI client initialized
          - vector_dim: expected vector dimensions
          - static_corpus_size: fallback corpus doc count
          - db_available: whether DB is reachable
          - db_corpus_count: total docs in DB
          - db_embedded_count: docs with embeddings
          - pgvector_available: whether pgvector extension is active
          - corpus_status: fully_embedded | partially_embedded | seeded_no_embeddings | empty | unavailable
          - active_retrieval_mode: pgvector | fallback_db_no_embedding | fallback_static | not_yet_used
          - last_seed_action: what seed_and_backfill did last time
        """
        status: dict[str, Any] = {
            "embedding_model": self._embedding_model,
            "embedding_client_available": self._embedding_available,
            "vector_dim": self._vector_dim,
            "static_corpus_size": len(_STATIC_CORPUS),
            "db_corpus_count": 0,
            "db_embedded_count": 0,
            "db_available": False,
            "pgvector_available": False,
            "corpus_status": "unavailable",
            "active_retrieval_mode": _last_retrieval_mode,
            "forced_static": os.getenv("RETRIEVAL_FALLBACK_ONLY", "").strip().lower() in ("true", "1", "yes"),
        }

        if _last_seed_status:
            status["last_seed_action"] = _last_seed_status.get("action", "unknown")

        try:
            from app.services.db import get_pool, check_pgvector_available
            pool = await get_pool()
            status["db_available"] = True
            status["pgvector_available"] = await check_pgvector_available()

            try:
                total = await pool.fetchval("SELECT COUNT(*) FROM knowledge_documents")
                status["db_corpus_count"] = total
            except Exception:
                total = 0

            try:
                embedded = await pool.fetchval(
                    "SELECT COUNT(*) FROM knowledge_documents WHERE embedding IS NOT NULL"
                )
                status["db_embedded_count"] = embedded
            except Exception:
                embedded = 0

            # Compute corpus_status
            if total == 0:
                status["corpus_status"] = "empty"
            elif embedded == 0:
                status["corpus_status"] = "seeded_no_embeddings"
            elif embedded < total:
                status["corpus_status"] = "partially_embedded"
            else:
                status["corpus_status"] = "fully_embedded"

        except Exception as exc:
            status["corpus_status"] = "unavailable"
            status["db_error"] = str(exc)

        return status
