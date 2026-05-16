"""
Async PostgreSQL connection pool for ai-service.

Provides connection pooling via asyncpg and runtime schema bootstrap
for the knowledge_documents table (idempotent, safe for existing DBs).
"""
from __future__ import annotations

import logging
import os
import time
from typing import Optional

logger = logging.getLogger(__name__)

_pool = None  # asyncpg.Pool | None — lazy-initialised

# ---------------------------------------------------------------------------
# Centralized constants
# ---------------------------------------------------------------------------

# OpenAI text-embedding-3-small produces 1536-dim vectors.
# All vector columns and queries must use this dimension.
VECTOR_DIM: int = 1536

# Connection pool limits
_POOL_MIN_SIZE: int = 1
_POOL_MAX_SIZE: int = 5
_CONNECT_TIMEOUT_S: float = 5.0


def _get_database_url() -> str:
    return os.getenv(
        "DATABASE_URL",
        "postgresql://cvmatcher:cvmatcher@postgres:5432/cvmatcher",
    )


async def get_pool():
    """Return (and lazily create) the asyncpg connection pool."""
    global _pool
    if _pool is None:
        import asyncpg

        _pool = await asyncpg.create_pool(
            _get_database_url(),
            min_size=_POOL_MIN_SIZE,
            max_size=_POOL_MAX_SIZE,
            timeout=_CONNECT_TIMEOUT_S,
            command_timeout=15.0,
        )
    return _pool


async def close_pool() -> None:
    """Gracefully close the connection pool."""
    global _pool
    if _pool is not None:
        await _pool.close()
        _pool = None


async def check_pgvector_available() -> bool:
    """Check if pgvector extension is available in the connected DB."""
    try:
        pool = await get_pool()
        row = await pool.fetchval(
            "SELECT EXISTS(SELECT 1 FROM pg_extension WHERE extname = 'vector')"
        )
        return bool(row)
    except Exception:
        return False


async def bootstrap_schema() -> None:
    """Create pgvector extension and knowledge_documents table if they don't exist.

    Safe to run against existing databases — all operations are idempotent.
    This is the runtime complement to docker/postgres/init/001_*.sql and 002_*.sql.

    Vector index note:
        No vector index is created at runtime. The seed corpus is intentionally
        small (<100 docs) and Postgres sequential scan is sufficient. If the
        corpus grows past ~1000 documents, call `create_hnsw_index()`.
    """
    try:
        pool = await get_pool()
        async with pool.acquire() as conn:
            # Ensure pgvector extension exists — required before any vector column.
            # On managed Postgres without superuser, this may fail; the table
            # creation will then also fail and the service falls back to static corpus.
            await conn.execute("CREATE EXTENSION IF NOT EXISTS vector")
            logger.info("pgvector extension verified/created")

            await conn.execute(f"""
                CREATE TABLE IF NOT EXISTS knowledge_documents (
                    id            SERIAL PRIMARY KEY,
                    source        VARCHAR(255) NOT NULL,
                    title         VARCHAR(500) NOT NULL,
                    content       TEXT NOT NULL,
                    metadata      JSONB DEFAULT '{{}}'::jsonb,
                    embedding     vector({VECTOR_DIM}),
                    created_at    TIMESTAMPTZ DEFAULT NOW(),
                    updated_at    TIMESTAMPTZ DEFAULT NOW()
                )
            """)
        logger.info("knowledge_documents schema verified/created")
    except Exception as exc:
        logger.warning("Schema bootstrap failed: %s (static fallback will be used)", exc)


async def create_hnsw_index() -> None:
    """Create HNSW vector index on knowledge_documents.embedding.

    Only needed when corpus grows past ~1000 documents.
    Safe to call multiple times (uses IF NOT EXISTS).
    """
    try:
        pool = await get_pool()
        await pool.execute("""
            CREATE INDEX IF NOT EXISTS idx_knowledge_embedding_hnsw
            ON knowledge_documents
            USING hnsw (embedding vector_cosine_ops)
            WITH (m = 16, ef_construction = 64)
        """)
        logger.info("HNSW vector index created/verified")
    except Exception as exc:
        logger.warning("HNSW index creation failed: %s", exc)


async def check_health() -> dict:
    """Return DB health status dict (no secrets)."""
    status = {
        "connected": False,
        "pgvector_available": False,
        "pool_size": 0,
        "database_url_configured": bool(os.getenv("DATABASE_URL", "")),
    }
    try:
        pool = await get_pool()
        status["connected"] = True
        status["pool_size"] = pool.get_size()
        status["pgvector_available"] = await check_pgvector_available()
    except Exception as exc:
        status["error"] = str(exc)
    return status
