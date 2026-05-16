<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
            $table->index(['user_id']);
        });

        // Add FK from users.company_id → companies.id (deferred from add_role migration)
        if (Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
