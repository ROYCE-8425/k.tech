<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Originally: add notes/interview fields to applications table.
 * Now: no-op — columns merged into create_applications_table.
 * Kept for migration history compatibility on existing databases.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('applications')) {
            return; // Table will be created later with all columns included
        }
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('applications', 'interviewed_at')) {
                $table->timestamp('interviewed_at')->nullable();
            }
            if (!Schema::hasColumn('applications', 'interviewed_by')) {
                $table->foreignId('interviewed_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('applications') || !Schema::hasColumn('applications', 'notes')) {
            return;
        }
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['notes', 'interviewed_at', 'interviewed_by']);
        });
    }
};
