<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnUpdate()->cascadeOnDelete();
            // Status: use string for SQLite/PG compatibility (was enum, updated in 2025_12_26_130000)
            $table->string('status', 20)->default('submitted')->index();
            $table->float('match_score')->nullable();
            // Manual CV scoring fields (was in 2025_12_26_000002)
            $table->float('cv_manual_score')->nullable();
            $table->string('cv_manual_grade')->nullable();
            $table->timestamp('cv_manual_scored_at')->nullable();
            $table->foreignId('cv_manual_scored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('cv_manual_inputs')->nullable();
            $table->longText('cv_manual_breakdown')->nullable();
            // AI match result (was in 2026_05_14_000001)
            $table->json('ai_match_result')->nullable();
            $table->string('cv_file_path')->nullable();
            $table->json('cv_vector')->nullable();
            // cv_data + cv_proof_files (was in 2025_12_23_000002)
            $table->longText('cv_data')->nullable();
            $table->longText('cv_proof_files')->nullable();
            $table->longText('cover_letter')->nullable();
            // notes + interview fields (was in 2025_12_13_213828)
            $table->text('notes')->nullable();
            $table->timestamp('interviewed_at')->nullable();
            $table->foreignId('interviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();

            $table->unique(['job_id', 'candidate_id']);
            $table->index(['job_id', 'candidate_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
