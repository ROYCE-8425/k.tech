import logging
from contextlib import asynccontextmanager

from fastapi import FastAPI

from app.api.routes import router as api_router
from app.api.health import health_router

logger = logging.getLogger(__name__)


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Startup/shutdown lifecycle for DB pool and knowledge corpus."""
    # --- Startup ---
    try:
        from app.services.db import bootstrap_schema, get_pool

        await get_pool()
        await bootstrap_schema()
        logger.info("Database pool and schema bootstrap complete")

        # Seed knowledge corpus if empty, backfill embeddings if missing
        from app.services.retriever import KnowledgeRetriever

        retriever = KnowledgeRetriever()
        await retriever.seed_and_backfill()
    except Exception as exc:
        logger.warning(
            "DB startup bootstrap failed: %s (static fallback will be used)", exc,
        )

    yield

    # --- Shutdown ---
    try:
        from app.services.db import close_pool

        await close_pool()
        logger.info("Database pool closed")
    except Exception:
        pass


app = FastAPI(
    title="Smart CV Matcher AI Service",
    version="0.4.0",
    lifespan=lifespan,
)

app.include_router(api_router, prefix="/api/v1")
app.include_router(health_router, prefix="/api/v1")
