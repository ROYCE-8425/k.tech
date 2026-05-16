<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1: Create knowledge_documents table — Laravel-owned schema definition.
 *
 * This table mirrors the schema created by ai-service/app/services/db.py bootstrap.
 * The ai-service bootstrap is idempotent (CREATE TABLE IF NOT EXISTS) so this
 * migration and the Python bootstrap can coexist safely.
 *
 * The embedding vector column (vector(1536)) is NOT created here because:
 * - pgvector extension is required and may not be available in all environments
 * - SQLite does not support vector types
 * - The ai-service bootstrap handles vector column creation at runtime
 *
 * If you need the embedding column on PostgreSQL, run the ai-service bootstrap
 * or add the column manually:
 *   ALTER TABLE knowledge_documents ADD COLUMN embedding vector(1536);
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $isPgsql = Schema::getConnection()->getDriverName() === 'pgsql';

            $table->id();
            $table->string('source', 255);
            $table->string('title', 500);
            $table->text('content');

            if ($isPgsql) {
                $table->jsonb('metadata')->default('{}');
            } else {
                $table->json('metadata')->nullable();
            }

            // Note: embedding vector(1536) column is managed by ai-service bootstrap
            // and pgvector extension. Not created here for SQLite compatibility.

            $table->timestamps();

            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_documents');
    }
};
