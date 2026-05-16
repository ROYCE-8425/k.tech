-- Phase 3: Knowledge documents table for pgvector-backed retrieval grounding.
-- This runs on fresh Postgres volume creation only.
-- The ai-service also bootstraps this table at runtime for existing databases.

CREATE TABLE IF NOT EXISTS knowledge_documents (
    id            SERIAL PRIMARY KEY,
    source        VARCHAR(255) NOT NULL,
    title         VARCHAR(500) NOT NULL,
    content       TEXT NOT NULL,
    metadata      JSONB DEFAULT '{}',
    embedding     vector(1536),
    created_at    TIMESTAMPTZ DEFAULT NOW(),
    updated_at    TIMESTAMPTZ DEFAULT NOW()
);
