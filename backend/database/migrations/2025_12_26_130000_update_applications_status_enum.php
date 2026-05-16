<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Originally: update applications status enum to include new values.
 * Now: guarded — only runs on MySQL (enum change) or PostgreSQL (type change).
 * On fresh SQLite installs, applications.status is already a string, so no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('applications')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Normalize legacy status values
            DB::statement("UPDATE applications SET status = 'reviewing' WHERE status = 'reviewed'");
            DB::statement("UPDATE applications SET status = 'hired' WHERE status = 'accepted'");
            DB::statement("UPDATE applications SET status = 'submitted' WHERE status = 'pending'");

            DB::statement(
                "ALTER TABLE applications MODIFY status ENUM(" .
                "'submitted'," .
                "'reviewing'," .
                "'shortlisted'," .
                "'interviewed'," .
                "'offered'," .
                "'hired'," .
                "'rejected'" .
                ") NOT NULL DEFAULT 'submitted'"
            );
        } elseif ($driver === 'pgsql') {
            DB::statement("UPDATE applications SET status = 'reviewing' WHERE status = 'reviewed'");
            DB::statement("UPDATE applications SET status = 'hired' WHERE status = 'accepted'");
            DB::statement("UPDATE applications SET status = 'submitted' WHERE status = 'pending'");
            DB::statement("ALTER TABLE applications ALTER COLUMN status TYPE varchar(20)");
            DB::statement("ALTER TABLE applications ALTER COLUMN status SET DEFAULT 'submitted'");
        }
        // SQLite: status is already varchar from base migration, no action needed
    }

    public function down(): void
    {
        // No rollback needed — status is now always varchar
    }
};
