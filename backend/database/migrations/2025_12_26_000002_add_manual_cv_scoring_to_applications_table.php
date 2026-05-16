<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Originally: add manual CV scoring columns to applications.
 * Now: guarded no-op — columns merged into create_applications_table.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('applications')) {
            return;
        }
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'cv_manual_score')) {
                $table->float('cv_manual_score')->nullable();
                $table->string('cv_manual_grade')->nullable();
                $table->timestamp('cv_manual_scored_at')->nullable();
                $table->foreignId('cv_manual_scored_by')->nullable()->constrained('users')->nullOnDelete();
                $table->longText('cv_manual_inputs')->nullable();
                $table->longText('cv_manual_breakdown')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('applications') || !Schema::hasColumn('applications', 'cv_manual_score')) {
            return;
        }
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cv_manual_scored_by');
            $table->dropColumn([
                'cv_manual_score', 'cv_manual_grade', 'cv_manual_scored_at',
                'cv_manual_inputs', 'cv_manual_breakdown',
            ]);
        });
    }
};
