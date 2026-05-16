<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add ai_match_result column for persisting sanitized match audit data.
 * Guarded: no-op on fresh installs where column already exists in base migration.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('applications') || Schema::hasColumn('applications', 'ai_match_result')) {
            return;
        }
        Schema::table('applications', function (Blueprint $table) {
            $table->jsonb('ai_match_result')->nullable()->after('cv_manual_breakdown');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('applications') || !Schema::hasColumn('applications', 'ai_match_result')) {
            return;
        }
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('ai_match_result');
        });
    }
};
