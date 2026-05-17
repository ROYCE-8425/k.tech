"""
Health and diagnostic endpoint for AI service (Phase 15 + Phase 18).

Reports service status without exposing secrets.
Stays at AI-service level only — no Laravel UI integration.

Phase 18: granular retrieval status, corpus health, active mode reporting.
"""
from __future__ import annotations

import logging
import os
from typing import Any

from fastapi import APIRouter

logger = logging.getLogger(__name__)

health_router = APIRouter()


def _compute_retrieval_readiness(
    db_status: dict[str, Any],
    retrieval_status: dict[str, Any],
) -> dict[str, Any]:
    """Compute a clear retrieval readiness summary from component statuses.

    Returns a dict with:
      - mode: the best retrieval mode currently available
      - ready: True if at least one non-static mode works
      - issues: list of actionable issue descriptions
    """
    mode = "fallback_static"
    ready = False
    issues: list[str] = []

    # Check asyncpg
    if not db_status.get("asyncpg_installed"):
        issues.append("asyncpg not installed — run: pip install asyncpg")
        return {"mode": mode, "ready": ready, "issues": issues}

    # Check DB config
    if not db_status.get("database_url_configured"):
        issues.append("DATABASE_URL not set — DB retrieval disabled")
        return {"mode": mode, "ready": ready, "issues": issues}

    # Check DB connection
    if not db_status.get("connected"):
        error = db_status.get("error", "unknown")
        issues.append(f"Database unreachable: {error}")
        return {"mode": mode, "ready": ready, "issues": issues}

    # DB is connected — check pgvector
    if not db_status.get("pgvector_available"):
        mode = "fallback_db_no_embedding"
        ready = True
        issues.append(
            "pgvector extension not available — using keyword search. "
            "Use pgvector/pgvector Docker image or run: CREATE EXTENSION vector;"
        )
    elif not retrieval_status.get("embedding_client_available"):
        mode = "fallback_db_no_embedding"
        ready = True
        issues.append(
            "OpenAI API key not set — embeddings unavailable. "
            "Set OPENAI_API_KEY for vector similarity search"
        )
    else:
        # Check corpus status
        corpus = retrieval_status.get("corpus_status", "unavailable")
        if corpus == "fully_embedded":
            mode = "pgvector"
            ready = True
        elif corpus == "partially_embedded":
            mode = "pgvector"
            ready = True
            issues.append("Some knowledge docs lack embeddings — run backfill on next restart")
        elif corpus == "seeded_no_embeddings":
            mode = "fallback_db_no_embedding"
            ready = True
            issues.append("Knowledge docs exist but have no embeddings — backfill needed")
        elif corpus == "empty":
            mode = "fallback_db_no_embedding"
            ready = True
            issues.append("knowledge_documents table is empty — will auto-seed on next startup")
        else:
            mode = "fallback_db_no_embedding"
            ready = True
            issues.append(f"Corpus status: {corpus}")

    return {"mode": mode, "ready": ready, "issues": issues}


@health_router.get("/health")
async def health_check() -> dict[str, Any]:
    """Lightweight health/status endpoint.

    Reports:
      - overall status (ok / degraded / error)
      - provider: configured LLM provider, model, key presence
      - database: connection, pgvector, table status
      - retrieval: mode capability, corpus stats, embedding readiness, active mode
      - retrieval_readiness: computed best-available mode + actionable issues
      - graph: edge/node counts
      - feedback: reranker enabled status
      - version: pipeline version

    No secrets are exposed.
    """
    status = "ok"
    components: dict[str, Any] = {}

    # --- LLM Provider ---
    try:
        from app.services.llm_providers import create_provider
        provider = create_provider()
        if provider:
            components["provider"] = provider.check_health()
        else:
            components["provider"] = {
                "provider": "none",
                "model": "heuristic_fallback",
                "initialized": False,
            }
            status = "degraded"
    except Exception as exc:
        components["provider"] = {"error": str(exc), "initialized": False}
        status = "degraded"

    # --- Database ---
    try:
        from app.services.db import check_health as db_health
        components["database"] = await db_health()
        if not components["database"].get("connected"):
            status = "degraded"
    except Exception as exc:
        components["database"] = {
            "asyncpg_installed": False,
            "connected": False,
            "error": str(exc),
        }
        status = "degraded"

    # --- Retrieval ---
    try:
        from app.services.retriever import KnowledgeRetriever
        retriever = KnowledgeRetriever()
        components["retrieval"] = await retriever.get_status()
    except Exception as exc:
        components["retrieval"] = {"error": str(exc)}
        status = "degraded"

    # --- Retrieval Readiness (computed summary) ---
    components["retrieval_readiness"] = _compute_retrieval_readiness(
        components.get("database", {}),
        components.get("retrieval", {}),
    )
    if not components["retrieval_readiness"].get("ready"):
        # Only degrade if no DB-backed retrieval at all — static is still functional
        if status == "ok":
            status = "degraded"

    # --- Skill Graph ---
    try:
        from app.services.normalizer import SkillRelationGraph, SkillNormalizer
        graph = SkillRelationGraph()
        normalizer = SkillNormalizer()
        components["graph"] = {
            "edge_count": graph.edge_count,
            "node_count": graph.node_count,
            "synonym_corpus_size": normalizer.corpus_size,
        }
    except Exception as exc:
        components["graph"] = {"error": str(exc)}

    # --- Feedback Reranker ---
    try:
        from app.services.feedback_reranker import FeedbackReranker
        reranker = FeedbackReranker()
        components["feedback_reranker"] = {
            "enabled": reranker.enabled,
        }
    except Exception as exc:
        components["feedback_reranker"] = {"error": str(exc)}

    # --- Environment (safe subset) ---
    components["environment"] = {
        "app_env": os.getenv("APP_ENV", "unknown"),
        "log_level": os.getenv("LOG_LEVEL", "info"),
        "llm_provider_env": os.getenv("LLM_PROVIDER", "(auto-detect)"),
        "embedding_model": os.getenv("EMBEDDING_MODEL", "text-embedding-3-small"),
        "feedback_rerank_enabled": os.getenv("FEEDBACK_RERANK_ENABLED", "true"),
        "retrieval_fallback_only": os.getenv("RETRIEVAL_FALLBACK_ONLY", "false"),
        "database_url_set": bool(os.getenv("DATABASE_URL", "")),
    }

    return {
        "status": status,
        "pipeline_version": "v2.0",
        "components": components,
    }
