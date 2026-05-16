"""
Health and diagnostic endpoint for AI service (Phase 15).

Reports service status without exposing secrets.
Stays at AI-service level only — no Laravel UI integration.
"""
from __future__ import annotations

import logging
import os
from typing import Any

from fastapi import APIRouter

logger = logging.getLogger(__name__)

health_router = APIRouter()


@health_router.get("/health")
async def health_check() -> dict[str, Any]:
    """Lightweight health/status endpoint.

    Reports:
      - overall status (ok / degraded / error)
      - provider: configured provider, model, key presence
      - retrieval: mode capability, corpus stats, embedding readiness
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
        components["database"] = {"connected": False, "error": str(exc)}
        status = "degraded"

    # --- Retrieval ---
    try:
        from app.services.retriever import KnowledgeRetriever
        retriever = KnowledgeRetriever()
        components["retrieval"] = await retriever.get_status()
    except Exception as exc:
        components["retrieval"] = {"error": str(exc)}
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
    }

    return {
        "status": status,
        "pipeline_version": "v2.0",
        "components": components,
    }
