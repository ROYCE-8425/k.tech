<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cv_scoring_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('cv_rubrics')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('key')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('cv_scoring_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('cv_scoring_profiles')->cascadeOnUpdate()->cascadeOnDelete();
            // Criterion code like A1, B2...
            $table->string('criterion_code', 20);
            // Weight multiplier applied after base score (e.g. 1.2 for dev, 0.7 for tester)
            $table->decimal('weight', 6, 3)->default(1.000);
            // Optional override for rule config (merge over base rule_config)
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->json('override_config')->nullable();
            } else {
                $table->longText('override_config')->nullable();
            }
            $table->timestamps();

            $table->unique(['profile_id', 'criterion_code']);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->foreignId('cv_scoring_profile_id')->nullable()->after('company_id')
                ->constrained('cv_scoring_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cv_scoring_profile_id');
        });
        Schema::dropIfExists('cv_scoring_overrides');
        Schema::dropIfExists('cv_scoring_profiles');
    }
};
