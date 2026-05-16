<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Originally: add profile links and proofs to candidates.
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
            if (!Schema::hasColumn('candidates', 'certifications')) {
                $table->text('certifications')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'portfolio_url')) {
                $table->string('portfolio_url')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'linkedin_url')) {
                $table->string('linkedin_url')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'github_url')) {
                $table->string('github_url')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'proof_files')) {
                $table->json('proof_files')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('candidates') || !Schema::hasColumn('candidates', 'certifications')) {
            return;
        }
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['certifications', 'portfolio_url', 'linkedin_url', 'github_url', 'proof_files']);
        });
    }
};
