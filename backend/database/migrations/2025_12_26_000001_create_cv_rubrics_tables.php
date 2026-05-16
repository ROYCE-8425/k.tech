<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cv_rubrics', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->unsignedSmallInteger('total_max')->default(100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('cv_rubric_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('cv_rubrics')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name');
            $table->unsignedSmallInteger('max_score');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['rubric_id', 'code']);
        });

        Schema::create('cv_rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('cv_rubric_groups')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->unsignedSmallInteger('max_score');
            $table->string('rule_type', 50);
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->json('rule_config')->nullable();
            } else {
                $table->longText('rule_config')->nullable();
            }
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['group_id', 'code']);
        });

        Schema::create('cv_rubric_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('cv_rubrics')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedSmallInteger('min_score');
            $table->unsignedSmallInteger('max_score')->nullable();
            $table->string('note')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_rubric_grades');
        Schema::dropIfExists('cv_rubric_criteria');
        Schema::dropIfExists('cv_rubric_groups');
        Schema::dropIfExists('cv_rubrics');
    }
};
