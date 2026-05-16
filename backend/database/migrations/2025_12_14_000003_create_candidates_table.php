<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            // FK to users (was in 2025_12_13_213218)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('file_path_cv')->nullable();
            $table->text('summary')->nullable();
            // about_me + work_experiences (was in 2025_12_23_000003)
            $table->text('about_me')->nullable();
            $table->json('work_experiences')->nullable();
            // skills, experience, education (was in 2025_12_13_213218)
            $table->text('skills')->nullable();
            $table->text('experience')->nullable();
            $table->text('education')->nullable();
            // sector + profile_data (was in 2025_12_26_120000)
            $table->string('sector', 20)->nullable();
            $table->json('profile_data')->nullable();
            // links + proofs (was in 2025_12_23_000001)
            $table->text('certifications')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->json('proof_files')->nullable();
            $table->json('skills_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
