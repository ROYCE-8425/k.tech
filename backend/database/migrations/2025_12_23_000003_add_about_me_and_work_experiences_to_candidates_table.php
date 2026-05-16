<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Originally: add about_me and work_experiences to candidates.
 * Now: guarded no-op — columns merged into create_candidates_table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('candidates')) {
            return;
        }
        Schema::table('candidates', function (Blueprint $table) {
            if (!Schema::hasColumn('candidates', 'about_me')) {
                $table->text('about_me')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'work_experiences')) {
                $table->json('work_experiences')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('candidates')) {
            return;
        }
        Schema::table('candidates', function (Blueprint $table) {
            if (Schema::hasColumn('candidates', 'work_experiences')) {
                $table->dropColumn('work_experiences');
            }
            if (Schema::hasColumn('candidates', 'about_me')) {
                $table->dropColumn('about_me');
            }
        });
    }
};
