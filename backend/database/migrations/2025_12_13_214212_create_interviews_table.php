<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Originally: create interviews table (timestamped before applications existed).
 * Now: guarded — skips if applications table doesn't exist yet,
 * and deferred to a later migration that runs after applications is created.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('applications')) {
            return; // Will be created by the deferred migration
        }
        if (Schema::hasTable('interviews')) {
            return; // Already exists
        }
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheduled_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->string('type', 20)->default('online');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->text('feedback')->nullable();
            $table->integer('rating')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
