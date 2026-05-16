<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Originally: add sector and profile_data to candidates.
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
            if (!Schema::hasColumn('candidates', 'sector')) {
                $table->string('sector', 20)->nullable();
            }
            if (!Schema::hasColumn('candidates', 'profile_data')) {
                $table->json('profile_data')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('candidates')) {
            return;
        }
        Schema::table('candidates', function (Blueprint $table) {
            if (Schema::hasColumn('candidates', 'profile_data')) {
                $table->dropColumn('profile_data');
            }
            if (Schema::hasColumn('candidates', 'sector')) {
                $table->dropColumn('sector');
            }
        });
    }
};
