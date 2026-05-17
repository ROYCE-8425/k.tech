<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bulk_upload_items')) {
            Schema::create('bulk_upload_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('batch_id')->constrained('bulk_upload_batches')->cascadeOnDelete();
                $table->foreignId('job_id')->constrained()->cascadeOnDelete();
                $table->string('original_filename');
                $table->string('stored_path');
                $table->string('mime_type')->nullable();
                $table->string('status')->default('pending'); // pending, parsing, parsed, matching, completed, failed
                $table->text('error_message')->nullable();
                $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
                $table->json('parsed_cv_data')->nullable();
                $table->json('ai_match_result')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_upload_items');
    }
};
