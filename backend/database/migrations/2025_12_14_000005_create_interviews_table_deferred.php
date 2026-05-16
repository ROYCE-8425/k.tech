<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deferred create_interviews — runs after applications table exists.
 * The original 2025_12_13_214212 migration is now a guarded no-op on fresh installs.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('interviews')) {
            return; // Already created by the original migration on existing DBs
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
