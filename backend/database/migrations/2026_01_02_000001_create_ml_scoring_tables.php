<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration tạo các bảng cho ML CV Scoring System
 * 
 * Bảng:
 * - ml_training_data: Lưu dữ liệu training (features, scores, labels)
 * - ml_models: Lưu trained models (serialized)
 * - ml_predictions: Lưu lịch sử predictions (audit trail)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bảng 1: ml_training_data
        // Lưu trữ dữ liệu training cho ML models
        Schema::create('ml_training_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained('applications')->onDelete('set null');
            $table->foreignId('candidate_id')->nullable()->constrained('candidates')->onDelete('set null');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->onDelete('set null');
            
            // Features (extracted from form) - Nhóm A
            $table->decimal('experience_years', 4, 1)->default(0)->comment('Số năm kinh nghiệm');
            $table->unsignedTinyInteger('projects_count')->default(0)->comment('Số dự án tiêu biểu');
            $table->unsignedTinyInteger('tech_match_count')->default(0)->comment('Số công nghệ match với job');
            
            // Features - Nhóm B
            $table->unsignedTinyInteger('main_skills_count')->default(0)->comment('Số kỹ năng chính match');
            $table->unsignedTinyInteger('sub_skills_count')->default(0)->comment('Số kỹ năng phụ');
            $table->unsignedTinyInteger('certifications_count')->default(0)->comment('Số chứng chỉ');
            
            // Features - Nhóm C
            $table->decimal('education_score', 4, 1)->default(0)->comment('Điểm học vấn (0-10)');
            $table->decimal('cv_quality_score', 4, 1)->default(0)->comment('Điểm chất lượng CV (0-10)');
            $table->unsignedTinyInteger('soft_skills_count')->default(0)->comment('Số kỹ năng mềm');
            $table->decimal('portfolio_score', 4, 1)->default(0)->comment('Điểm portfolio (0-5)');
            
            // Group scores (calculated)
            $table->decimal('score_group_a', 5, 2)->default(0)->comment('Điểm nhóm A (max 35)');
            $table->decimal('score_group_b', 5, 2)->default(0)->comment('Điểm nhóm B (max 35)');
            $table->decimal('score_group_c', 5, 2)->default(0)->comment('Điểm nhóm C (max 30)');
            
            // Predictions
            $table->decimal('weighted_score', 5, 2)->nullable()->comment('Điểm có trọng số');
            $table->decimal('ml_score', 5, 2)->nullable()->comment('Điểm từ ML model');
            $table->decimal('final_score', 5, 2)->nullable()->comment('Điểm cuối cùng');
            $table->string('classification', 50)->nullable()->comment('Xếp loại');
            
            // Labels (actual outcomes) - Điền sau phỏng vấn
            $table->decimal('actual_score', 5, 2)->nullable()->comment('Điểm thực tế sau phỏng vấn');
            $table->enum('interview_result', ['pass', 'fail', 'pending'])->nullable();
            $table->decimal('performance_rating', 3, 1)->nullable()->comment('Đánh giá hiệu suất (sau 3-6 tháng)');
            
            // Metadata
            $table->timestamp('scored_at')->nullable();
            $table->timestamp('labeled_at')->nullable();
            $table->foreignId('labeled_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index('application_id');
            $table->index('candidate_id');
            $table->index('job_id');
            $table->index('actual_score');
            $table->index('interview_result');
        });

        // Bảng 2: ml_models
        // Lưu trữ trained models (serialized)
        Schema::create('ml_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 50)->comment('random_forest, weight_calculator, scoring_pipeline');
            $table->string('version', 30)->comment('Version string e.g. v2026.01.02.120000');
            
            // Model data (serialized JSON)
            $table->longText('model_data')->comment('JSON serialized model');
            $table->json('feature_names')->nullable()->comment('Danh sách feature names');
            
            // Hyperparameters
            $table->json('hyperparameters')->nullable();
            
            // Metrics
            $table->json('metrics')->nullable()->comment('r2_score, mae, rmse, oob_score, etc.');
            $table->json('feature_importance')->nullable();
            
            // Training info
            $table->unsignedInteger('training_samples')->default(0);
            $table->timestamp('training_started_at')->nullable();
            $table->timestamp('training_completed_at')->nullable();
            
            // Status
            $table->boolean('is_active')->default(false)->comment('Model đang được sử dụng');
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['model_type', 'version']);
            $table->index(['model_type', 'is_active']);
        });

        // Bảng 3: ml_predictions
        // Lưu trữ lịch sử predictions (audit trail)
        Schema::create('ml_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained('applications')->onDelete('set null');
            $table->string('model_version', 30)->nullable();
            
            // Input features (snapshot at prediction time)
            $table->json('features')->comment('All features at prediction time');
            $table->json('group_scores')->comment('Group A, B, C scores');
            $table->json('weights_used')->comment('Weights used for calculation');
            
            // Predictions
            $table->decimal('weighted_score', 5, 2)->nullable();
            $table->decimal('ml_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->string('classification', 50)->nullable();
            
            // Confidence
            $table->decimal('prediction_confidence', 4, 3)->nullable()->comment('0.000 - 1.000');
            $table->json('confidence_interval')->nullable()->comment('Lower/upper bounds');
            
            // Actual outcome (filled later)
            $table->decimal('actual_score', 5, 2)->nullable()->comment('Điểm thực tế');
            
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes
            $table->index('application_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_predictions');
        Schema::dropIfExists('ml_models');
        Schema::dropIfExists('ml_training_data');
    }
};
