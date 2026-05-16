<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Originally: add cv_data and cv_proof_files to applications.
 * Now: guarded no-op — columns merged into create_applications_table.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('applications')) {
            return;
        }
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'cv_data')) {
                $table->longText('cv_data')->nullable();
            }
            if (!Schema::hasColumn('applications', 'cv_proof_files')) {
                $table->longText('cv_proof_files')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('applications') || !Schema::hasColumn('applications', 'cv_data')) {
            return;
        }
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['cv_data', 'cv_proof_files']);
        });
    }
};
