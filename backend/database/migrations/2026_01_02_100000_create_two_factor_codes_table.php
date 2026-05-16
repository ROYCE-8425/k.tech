<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('two_factor_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code', 6);
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['user_id', 'code']);
        });

        if (!Schema::hasColumn('users', 'two_factor_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('two_factor_enabled')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_codes');
        if (Schema::hasColumn('users', 'two_factor_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('two_factor_enabled');
            });
        }
    }
};
