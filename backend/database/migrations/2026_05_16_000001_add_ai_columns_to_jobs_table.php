<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1: Add structured AI matching columns to jobs table.
 * Guarded for idempotency on fresh installs.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('jobs') || Schema::hasColumn('jobs', 'required_skills')) {
            return;
        }

        Schema::table('jobs', function (Blueprint $table) {
            $isPgsql = Schema::getConnection()->getDriverName() === 'pgsql';

            if ($isPgsql) {
                $table->jsonb('required_skills')->nullable();
                $table->jsonb('preferred_skills')->nullable();
            } else {
                $table->json('required_skills')->nullable();
                $table->json('preferred_skills')->nullable();
            }

            $table->string('seniority', 50)->nullable();
            $table->unsignedSmallInteger('min_experience_years')->nullable();
            $table->unsignedSmallInteger('max_experience_years')->nullable();

            if ($isPgsql) {
                $table->jsonb('scoring_config')->nullable();
            } else {
                $table->json('scoring_config')->nullable();
            }

            $table->text('ai_recruiter_notes')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('jobs') || !Schema::hasColumn('jobs', 'required_skills')) {
            return;
        }
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn([
                'required_skills', 'preferred_skills', 'seniority',
                'min_experience_years', 'max_experience_years',
                'scoring_config', 'ai_recruiter_notes',
            ]);
        });
    }
};
