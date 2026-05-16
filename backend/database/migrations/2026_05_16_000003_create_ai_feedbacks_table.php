<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1: Create ai_feedbacks table for recruiter feedback on AI match results.
 *
 * Captures recruiter agree/disagree/note signals on AI-generated shortlist results.
 * Designed to support future model improvement and evaluation workflows.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->foreignId('recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('feedback_type', 50); // e.g. 'agree', 'disagree', 'note', 'flag'
            $table->text('feedback_note')->nullable();
            $table->timestamps();

            $table->index(['application_id']);
            $table->index(['job_id']);
            $table->index(['recruiter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feedbacks');
    }
};
