<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modify columns to LONGTEXT for encrypted storage.
 * Guarded: only runs on MySQL/PostgreSQL where ->change() is supported.
 * On SQLite fresh installs, columns are already longText from base migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // SQLite doesn't support ->change() and base migrations already use longText
        if ($driver === 'sqlite') {
            return;
        }

        if (Schema::hasTable('candidates') && Schema::hasColumn('candidates', 'name')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->longText('name')->change();
                $table->longText('email')->change();
                $table->longText('phone')->nullable()->change();
                $table->longText('summary')->nullable()->change();
                $table->longText('about_me')->nullable()->change();
                $table->longText('profile_data')->nullable()->change();
            });
        }

        if (Schema::hasTable('applications') && Schema::hasColumn('applications', 'cv_data')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->longText('cv_data')->nullable()->change();
                $table->longText('cover_letter')->nullable()->change();
                $table->longText('notes')->nullable()->change();
            });
            // Only change columns that exist
            if (Schema::hasColumn('applications', 'cv_manual_inputs')) {
                Schema::table('applications', function (Blueprint $table) {
                    $table->longText('cv_manual_inputs')->nullable()->change();
                    $table->longText('cv_manual_breakdown')->nullable()->change();
                });
            }
        }

        if (Schema::hasTable('interviews')) {
            Schema::table('interviews', function (Blueprint $table) {
                $table->longText('notes')->nullable()->change();
                $table->longText('feedback')->nullable()->change();
                $table->longText('location')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }

        if (Schema::hasTable('candidates')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->string('name')->change();
                $table->string('email')->change();
                $table->string('phone')->nullable()->change();
                $table->text('summary')->nullable()->change();
                $table->text('about_me')->nullable()->change();
                $table->json('profile_data')->nullable()->change();
            });
        }

        if (Schema::hasTable('applications')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->longText('cv_data')->nullable()->change();
                $table->text('cover_letter')->nullable()->change();
                $table->text('notes')->nullable()->change();
            });
        }

        if (Schema::hasTable('interviews')) {
            Schema::table('interviews', function (Blueprint $table) {
                $table->text('notes')->nullable()->change();
                $table->text('feedback')->nullable()->change();
                $table->string('location')->nullable()->change();
            });
        }
    }
};
