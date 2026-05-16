<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ML Training Data Model
 * 
 * Lưu trữ dữ liệu training cho ML models:
 * - Features trích xuất từ CV
 * - Điểm các nhóm A, B, C
 * - Labels (điểm thực tế sau phỏng vấn)
 */
class MLTrainingData extends Model
{
    protected $table = 'ml_training_data';

    protected $fillable = [
        'application_id',
        'candidate_id',
        'job_id',
        
        // Features - Nhóm A
        'experience_years',
        'projects_count',
        'tech_match_count',
        
        // Features - Nhóm B
        'main_skills_count',
        'sub_skills_count',
        'certifications_count',
        
        // Features - Nhóm C
        'education_score',
        'cv_quality_score',
        'soft_skills_count',
        'portfolio_score',
        
        // Group scores
        'score_group_a',
        'score_group_b',
        'score_group_c',
        
        // Predictions
        'weighted_score',
        'ml_score',
        'final_score',
        'classification',
        
        // Labels
        'actual_score',
        'interview_result',
        'performance_rating',
        
        // Metadata
        'scored_at',
        'labeled_at',
        'labeled_by',
    ];

    protected $casts = [
        'experience_years' => 'float',
        'education_score' => 'float',
        'cv_quality_score' => 'float',
        'portfolio_score' => 'float',
        'score_group_a' => 'float',
        'score_group_b' => 'float',
        'score_group_c' => 'float',
        'weighted_score' => 'float',
        'ml_score' => 'float',
        'final_score' => 'float',
        'actual_score' => 'float',
        'performance_rating' => 'float',
        'scored_at' => 'datetime',
        'labeled_at' => 'datetime',
    ];

    // ========================================================================
    // RELATIONSHIPS
    // ========================================================================

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function labeledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'labeled_by');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Chỉ lấy dữ liệu đã được label
     */
    public function scopeLabeled($query)
    {
        return $query->whereNotNull('actual_score');
    }

    /**
     * Chỉ lấy dữ liệu chưa label
     */
    public function scopeUnlabeled($query)
    {
        return $query->whereNull('actual_score');
    }

    /**
     * Lọc theo kết quả phỏng vấn
     */
    public function scopeWithInterviewResult($query, string $result)
    {
        return $query->where('interview_result', $result);
    }

    // ========================================================================
    // ACCESSORS
    // ========================================================================

    /**
     * Lấy tất cả features dưới dạng array
     */
    public function getFeaturesAttribute(): array
    {
        return [
            'experience_years' => $this->experience_years,
            'projects_count' => $this->projects_count,
            'tech_match_count' => $this->tech_match_count,
            'main_skills_count' => $this->main_skills_count,
            'sub_skills_count' => $this->sub_skills_count,
            'certifications_count' => $this->certifications_count,
            'education_score' => $this->education_score,
            'cv_quality_score' => $this->cv_quality_score,
            'soft_skills_count' => $this->soft_skills_count,
            'portfolio_score' => $this->portfolio_score,
        ];
    }

    /**
     * Lấy features dưới dạng array số (cho ML training)
     */
    public function getFeaturesArrayAttribute(): array
    {
        return array_values($this->features);
    }

    /**
     * Lấy score (alias cho actual_score)
     */
    public function getScoreAttribute(): ?float
    {
        return $this->actual_score;
    }

    /**
     * Lấy group scores dưới dạng array
     */
    public function getGroupScoresAttribute(): array
    {
        return [
            'A' => $this->score_group_a,
            'B' => $this->score_group_b,
            'C' => $this->score_group_c,
        ];
    }

    /**
     * Kiểm tra đã được label chưa
     */
    public function getIsLabeledAttribute(): bool
    {
        return $this->actual_score !== null;
    }

    // ========================================================================
    // METHODS
    // ========================================================================

    /**
     * Label sample với điểm thực tế
     */
    public function label(float $actualScore, ?string $interviewResult = null, ?int $labeledBy = null): self
    {
        $this->update([
            'actual_score' => $actualScore,
            'interview_result' => $interviewResult,
            'labeled_at' => now(),
            'labeled_by' => $labeledBy,
        ]);
        
        return $this;
    }

    /**
     * Chuyển thành format cho training
     */
    public function toTrainingFormat(): array
    {
        return [
            'features' => $this->features,
            'group_scores' => $this->group_scores,
            'actual_score' => $this->actual_score,
        ];
    }
}
