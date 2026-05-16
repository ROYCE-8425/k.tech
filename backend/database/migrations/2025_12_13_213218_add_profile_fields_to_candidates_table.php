<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Originally: add profile fields to candidates table.
 * Now: no-op — columns merged into create_candidates_table.
 * Kept for migration history compatibility on existing databases.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('candidates')) {
            return; // Table will be created later with all columns included
        }
        Schema::table('candidates', function (Blueprint $table) {
            if (!Schema::hasColumn('candidates', 'skills')) {
                $table->text('skills')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'experience')) {
                $table->text('experience')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'education')) {
                $table->text('education')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('candidates') || !Schema::hasColumn('candidates', 'skills')) {
            return;
        }
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['skills', 'experience', 'education']);
        });
    }
};
