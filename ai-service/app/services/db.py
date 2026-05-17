"""
Async PostgreSQL connection pool for ai-service.

Provides connection pooling via asyncpg and runtime schema bootstrap
for the knowledge_documents table (idempotent, safe for existing DBs).

Gracefully degrades when asyncpg is unavailable or DB is unreachable.
"""
from __future__ import annotations

import asyncio
import logging
import os
import time
from typing import Any, Optional

logger = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# asyncpg availability guard
# ---------------------------------------------------------------------------

_ASYNCPG_AVAILABLE = False
try:
    import asyncpg  # noqa: F401
    _ASYNCPG_AVAILABLE = True
except ImportError:
    logger.warning(
        "asyncpg is not installed — DB-backed retrieval will be unavailable. "
        "Install with: pip install asyncpg"
    )


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
_POOL_CREATE_TIMEOUT_S: float = 10.0  # total timeout for pool creation incl. DNS

# Cached status — set once during bootstrap, avoids repeated checks
_pgvector_available: Optional[bool] = None
_table_exists: Optional[bool] = None
_bootstrap_error: Optional[str] = None


def _get_database_url() -> str:
    return os.getenv(
        "DATABASE_URL",
        "postgresql://cvmatcher:cvmatcher@postgres:5432/recruitment_app",
    )


def is_db_configured() -> bool:
    """Check if a DATABASE_URL is explicitly configured (not just the default)."""
    return bool(os.getenv("DATABASE_URL", ""))


async def get_pool():
    """Return (and lazily create) the asyncpg connection pool.

    Raises:
        RuntimeError: if asyncpg is not installed
        Exception: if connection to PostgreSQL fails
    """
    global _pool

    if not _ASYNCPG_AVAILABLE:
        raise RuntimeError(
            "asyncpg is not installed — DB-backed retrieval unavailable"
        )

    if _pool is not None:
        # Quick liveness check — if the pool was closed externally, reset
        try:
            _pool.get_size()  # raises if pool is closed/terminated
            return _pool
        except Exception:
            _pool = None

    import asyncpg

    url = _get_database_url()
    logger.info("Creating asyncpg pool → %s", _mask_url(url))

    try:
        _pool = await asyncio.wait_for(
            asyncpg.create_pool(
                url,
                min_size=_POOL_MIN_SIZE,
                max_size=_POOL_MAX_SIZE,
                timeout=_CONNECT_TIMEOUT_S,
                command_timeout=15.0,
            ),
            timeout=_POOL_CREATE_TIMEOUT_S,
        )
        logger.info("asyncpg pool created (size=%d)", _pool.get_size())
    except asyncio.TimeoutError:
        raise ConnectionError(
            f"Database connection timed out after {_POOL_CREATE_TIMEOUT_S}s — "
            f"check DATABASE_URL and ensure PostgreSQL is reachable"
        )
    except Exception as exc:
        raise ConnectionError(
            f"Database connection failed: {exc} — "
            f"check DATABASE_URL ({_mask_url(url)})"
        ) from exc

    return _pool


async def close_pool() -> None:
    """Gracefully close the connection pool."""
    global _pool
    if _pool is not None:
        await _pool.close()
        _pool = None
        logger.info("asyncpg pool closed")


async def check_pgvector_available(*, use_cache: bool = True) -> bool:
    """Check if pgvector extension is available in the connected DB.

    Caches the result after first successful check to avoid repeated queries.
    """
    global _pgvector_available

    if use_cache and _pgvector_available is not None:
        return _pgvector_available

    try:
        pool = await get_pool()
        row = await pool.fetchval(
            "SELECT EXISTS(SELECT 1 FROM pg_extension WHERE extname = 'vector')"
        )
        _pgvector_available = bool(row)
        return _pgvector_available
    except Exception:
        return False


async def check_table_exists(*, use_cache: bool = True) -> bool:
    """Check if knowledge_documents table exists."""
    global _table_exists

    if use_cache and _table_exists is not None:
        return _table_exists

    try:
        pool = await get_pool()
        row = await pool.fetchval("""
            SELECT EXISTS(
                SELECT 1 FROM information_schema.tables
                WHERE table_name = 'knowledge_documents'
            )
        """)
        _table_exists = bool(row)
        return _table_exists
    except Exception:
        return False


async def bootstrap_schema() -> dict[str, Any]:
    """Create pgvector extension and knowledge_documents table if they don't exist.

    Safe to run against existing databases — all operations are idempotent.
    This is the runtime complement to docker/postgres/init/001_*.sql and 002_*.sql.

    Returns a status dict with details about what happened.
    """
    global _pgvector_available, _table_exists, _bootstrap_error

    result: dict[str, Any] = {
        "db_connected": False,
        "pgvector_created": False,
        "table_created": False,
        "error": None,
    }

    try:
        pool = await get_pool()
        result["db_connected"] = True

        async with pool.acquire() as conn:
            # Step 1: Ensure pgvector extension
            try:
                await conn.execute("CREATE EXTENSION IF NOT EXISTS vector")
                _pgvector_available = True
                result["pgvector_created"] = True
                logger.info("pgvector extension verified/created")
            except Exception as ext_exc:
                _pgvector_available = False
                ext_msg = str(ext_exc)
                if "permission denied" in ext_msg.lower():
                    logger.warning(
                        "Cannot create pgvector extension (no superuser). "
                        "Ensure pgvector is pre-installed or run: "
                        "CREATE EXTENSION vector; as superuser."
                    )
                elif "could not open extension" in ext_msg.lower():
                    logger.warning(
                        "pgvector extension not available in this PostgreSQL installation. "
                        "Use pgvector/pgvector Docker image or install pgvector manually."
                    )
                else:
                    logger.warning("pgvector extension creation failed: %s", ext_exc)
                result["error"] = f"pgvector extension: {ext_msg}"

            # Step 2: Create knowledge_documents table
            # Only attempt if pgvector is available (vector column type requires it)
            if _pgvector_available:
                try:
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
                    _table_exists = True
                    result["table_created"] = True
                    logger.info("knowledge_documents schema verified/created")
                except Exception as tbl_exc:
                    logger.warning("Table creation failed: %s", tbl_exc)
                    result["error"] = f"Table creation: {tbl_exc}"
            else:
                # Create table WITHOUT vector column — allows DB text search fallback
                try:
                    await conn.execute("""
                        CREATE TABLE IF NOT EXISTS knowledge_documents (
                            id            SERIAL PRIMARY KEY,
                            source        VARCHAR(255) NOT NULL,
                            title         VARCHAR(500) NOT NULL,
                            content       TEXT NOT NULL,
                            metadata      JSONB DEFAULT '{}'::jsonb,
                            created_at    TIMESTAMPTZ DEFAULT NOW(),
                            updated_at    TIMESTAMPTZ DEFAULT NOW()
                        )
                    """)
                    _table_exists = True
                    result["table_created"] = True
                    logger.info(
                        "knowledge_documents table created WITHOUT vector column "
                        "(pgvector not available — keyword search only)"
                    )
                except Exception as tbl_exc:
                    logger.warning("Fallback table creation failed: %s", tbl_exc)
                    result["error"] = f"Fallback table creation: {tbl_exc}"

    except Exception as exc:
        error_msg = str(exc)
        _bootstrap_error = error_msg
        result["error"] = error_msg
        logger.warning(
            "Schema bootstrap failed: %s (static fallback will be used)", exc,
        )

    return result


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


async def check_health() -> dict[str, Any]:
    """Return DB health status dict (no secrets).

    Reports:
      - connected: whether pool is alive
      - pgvector_available: whether pgvector extension is installed
      - table_exists: whether knowledge_documents table exists
      - pool_size: current pool size
      - database_url_configured: whether DATABASE_URL env is set
      - asyncpg_installed: whether asyncpg package is available
      - bootstrap_error: last bootstrap error if any
    """
    status: dict[str, Any] = {
        "asyncpg_installed": _ASYNCPG_AVAILABLE,
        "database_url_configured": is_db_configured(),
        "connected": False,
        "pgvector_available": False,
        "table_exists": False,
        "pool_size": 0,
    }

    if not _ASYNCPG_AVAILABLE:
        status["error"] = "asyncpg not installed — pip install asyncpg"
        return status

    try:
        pool = await get_pool()
        status["connected"] = True
        status["pool_size"] = pool.get_size()

        # Use cached values if available, otherwise check
        status["pgvector_available"] = await check_pgvector_available()
        status["table_exists"] = await check_table_exists()

    except Exception as exc:
        status["error"] = str(exc)

    if _bootstrap_error:
        status["bootstrap_error"] = _bootstrap_error

    return status


def _mask_url(url: str) -> str:
    """Mask password in database URL for safe logging."""
    import re
    return re.sub(r"://([^:]+):([^@]+)@", r"://\1:***@", url)
