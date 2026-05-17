<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bulk_upload_batches')) {
            Schema::create('bulk_upload_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_id')->constrained()->cascadeOnDelete();
                $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
                $table->integer('total_files')->default(0);
                $table->integer('processed_files')->default(0);
                $table->integer('failed_files')->default(0);
                $table->string('status')->default('pending'); // pending, processing, completed, failed
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_upload_batches');
    }
};
