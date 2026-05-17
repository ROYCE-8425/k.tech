"""
Smart CV Matcher AI Service — FastAPI application.

Startup lifecycle:
  1. Attempt DB connection (non-blocking — continues on failure)
  2. Bootstrap pgvector extension + knowledge_documents table
  3. Seed knowledge corpus if empty, backfill embeddings if missing
  4. Log clear startup summary: retrieval mode, corpus status

Shutdown: close asyncpg pool.
"""
import asyncio
import logging

from contextlib import asynccontextmanager

from fastapi import FastAPI

from app.api.routes import router as api_router
from app.api.health import health_router

logger = logging.getLogger(__name__)

# Maximum seconds to wait for DB startup tasks before continuing
_DB_STARTUP_TIMEOUT_S: float = 15.0


async def _db_startup() -> dict:
    """Run DB bootstrap + corpus seed with timeout protection.

    Returns a summary dict for logging. Never raises — service starts
    regardless of DB state.
    """
    summary = {
        "db_connected": False,
        "pgvector_available": False,
        "corpus_status": "unavailable",
        "retrieval_mode": "fallback_static",
        "seed_action": "none",
        "error": None,
    }

    try:
        from app.services.db import bootstrap_schema, get_pool, _ASYNCPG_AVAILABLE

        if not _ASYNCPG_AVAILABLE:
            summary["error"] = "asyncpg not installed"
            return summary

        # Connect + bootstrap with timeout
        await get_pool()
        summary["db_connected"] = True

        bootstrap_result = await bootstrap_schema()
        summary["pgvector_available"] = bootstrap_result.get("pgvector_created", False)

        if bootstrap_result.get("error"):
            summary["error"] = bootstrap_result["error"]

        # Seed + backfill
        from app.services.retriever import KnowledgeRetriever
        retriever = KnowledgeRetriever()
        seed_result = await retriever.seed_and_backfill()
        summary["seed_action"] = seed_result.get("action", "unknown")
        summary["corpus_status"] = _compute_corpus_status(seed_result)

        # Determine best retrieval mode
        if summary["pgvector_available"] and seed_result.get("embedded_docs", 0) > 0:
            summary["retrieval_mode"] = "pgvector"
        elif summary["db_connected"]:
            summary["retrieval_mode"] = "fallback_db_no_embedding"
        else:
            summary["retrieval_mode"] = "fallback_static"

    except Exception as exc:
        summary["error"] = str(exc)
        logger.warning("DB startup failed: %s", exc)

    return summary


def _compute_corpus_status(seed_result: dict) -> str:
    """Compute human-readable corpus status from seed_and_backfill result."""
    total = seed_result.get("total_docs", 0)
    embedded = seed_result.get("embedded_docs", 0)

    if total == 0:
        return "empty"
    if embedded == 0:
        return "seeded_no_embeddings"
    if embedded < total:
        return "partially_embedded"
    return "fully_embedded"


def _log_startup_summary(summary: dict) -> None:
    """Log a clear, actionable startup summary."""
    mode = summary.get("retrieval_mode", "unknown")
    corpus = summary.get("corpus_status", "unknown")

    mode_labels = {
        "pgvector": "✅ pgvector (vector similarity)",
        "fallback_db_no_embedding": "⚠️  DB keyword search (no embeddings)",
        "fallback_static": "⚠️  Static corpus (no DB)",
    }
    corpus_labels = {
        "fully_embedded": "✅ fully embedded",
        "partially_embedded": "⚠️  partially embedded (backfill pending)",
        "seeded_no_embeddings": "⚠️  seeded but no embeddings",
        "empty": "⚠️  empty (will auto-seed)",
        "unavailable": "❌ unavailable",
    }

    logger.info("=" * 60)
    logger.info("Smart CV Matcher AI Service — Startup Summary")
    logger.info("=" * 60)
    logger.info("  DB connected:      %s", "✅ yes" if summary["db_connected"] else "❌ no")
    logger.info("  pgvector:          %s", "✅ available" if summary["pgvector_available"] else "❌ not available")
    logger.info("  Retrieval mode:    %s", mode_labels.get(mode, mode))
    logger.info("  Corpus status:     %s", corpus_labels.get(corpus, corpus))
    logger.info("  Seed action:       %s", summary.get("seed_action", "none"))

    if summary.get("error"):
        logger.info("  ⚠️  Error:          %s", summary["error"])

    if mode == "fallback_static":
        logger.info("  💡 To enable DB retrieval: set DATABASE_URL and ensure PostgreSQL is reachable")
    if corpus in ("seeded_no_embeddings", "partially_embedded"):
        logger.info("  💡 To enable embeddings: set OPENAI_API_KEY and restart")

    logger.info("=" * 60)


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Startup/shutdown lifecycle for DB pool and knowledge corpus."""
    # --- Startup ---
    try:
        summary = await asyncio.wait_for(
            _db_startup(),
            timeout=_DB_STARTUP_TIMEOUT_S,
        )
    except asyncio.TimeoutError:
        logger.warning(
            "DB startup timed out after %.0fs — continuing with static fallback",
            _DB_STARTUP_TIMEOUT_S,
        )
        summary = {
            "db_connected": False,
            "pgvector_available": False,
            "corpus_status": "unavailable",
            "retrieval_mode": "fallback_static",
            "seed_action": "timeout",
            "error": f"Startup timed out after {_DB_STARTUP_TIMEOUT_S}s",
        }
    except Exception as exc:
        logger.warning(
            "DB startup bootstrap failed: %s (static fallback will be used)", exc,
        )
        summary = {
            "db_connected": False,
            "pgvector_available": False,
            "corpus_status": "unavailable",
            "retrieval_mode": "fallback_static",
            "seed_action": "error",
            "error": str(exc),
        }

    _log_startup_summary(summary)

    yield

    # --- Shutdown ---
    try:
        from app.services.db import close_pool
        await close_pool()
    except Exception:
        pass


app = FastAPI(
    title="Smart CV Matcher AI Service",
    version="0.5.0",
    lifespan=lifespan,
)

app.include_router(api_router, prefix="/api/v1")
app.include_router(health_router, prefix="/api/v1")
