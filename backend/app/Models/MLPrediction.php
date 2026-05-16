<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ML Prediction History
 * 
 * Lưu lịch sử các predictions để monitoring và analysis
 */
class MLPrediction extends Model
{
    protected $table = 'ml_predictions';

    protected $fillable = [
        'candidate_id',
        'job_id',
        'ml_model_id',
        'input_features',
        'group_scores',
        'weighted_score',
        'ml_score',
        'final_score',
        'classification',
        'confidence',
        'processing_time_ms',
        'feature_contributions',
        'human_score',
        'human_feedback',
    ];

    protected $casts = [
        'input_features' => 'array',
        'group_scores' => 'array',
        'weighted_score' => 'float',
        'ml_score' => 'float',
        'final_score' => 'float',
        'confidence' => 'float',
        'processing_time_ms' => 'integer',
        'feature_contributions' => 'array',
        'human_score' => 'float',
    ];

    // ========================================================================
    // RELATIONSHIPS
    // ========================================================================

    /**
     * Candidate được đánh giá
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Job mà candidate apply
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * ML Model đã sử dụng
     */
    public function mlModel(): BelongsTo
    {
        return $this->belongsTo(MLModel::class);
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Predictions có human feedback
     */
    public function scopeWithFeedback($query)
    {
        return $query->whereNotNull('human_score');
    }

    /**
     * Lọc theo classification
     */
    public function scopeOfClassification($query, string $classification)
    {
        return $query->where('classification', $classification);
    }

    /**
     * Lọc theo job
     */
    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Lọc theo khoảng thời gian
     */
    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // ========================================================================
    // ACCESSORS
    // ========================================================================

    /**
     * Sai số giữa ML và Human
     */
    public function getScoreErrorAttribute(): ?float
    {
        if ($this->human_score === null) {
            return null;
        }
        return abs($this->final_score - $this->human_score);
    }

    /**
     * Lấy score của từng group
     */
    public function getGroupAScoreAttribute(): ?float
    {
        return $this->group_scores['group_a']['score'] ?? null;
    }

    public function getGroupBScoreAttribute(): ?float
    {
        return $this->group_scores['group_b']['score'] ?? null;
    }

    public function getGroupCScoreAttribute(): ?float
    {
        return $this->group_scores['group_c']['score'] ?? null;
    }

    // ========================================================================
    // METHODS
    // ========================================================================

    /**
     * Thêm human feedback
     */
    public function addFeedback(float $humanScore, ?string $feedback = null): self
    {
        $this->update([
            'human_score' => $humanScore,
            'human_feedback' => $feedback,
        ]);

        return $this;
    }

    /**
     * Kiểm tra prediction có chính xác không
     * (sai số <= threshold)
     */
    public function isAccurate(float $threshold = 5.0): bool
    {
        if ($this->human_score === null) {
            return true; // Chưa có feedback => assume accurate
        }
        return $this->score_error <= $threshold;
    }

    /**
     * Tạo training data từ prediction có feedback
     */
    public function toTrainingData(): array
    {
        if ($this->human_score === null) {
            return [];
        }

        return [
            'candidate_id' => $this->candidate_id,
            'job_id' => $this->job_id,
            'features' => $this->input_features,
            'group_scores' => $this->group_scores,
            'score' => $this->human_score,
            'source' => 'prediction_feedback',
        ];
    }

    // ========================================================================
    // STATIC METHODS
    // ========================================================================

    /**
     * Tính model accuracy từ predictions có feedback
     */
    public static function calculateModelAccuracy(int $mlModelId, float $threshold = 5.0): array
    {
        $predictions = static::where('ml_model_id', $mlModelId)
            ->whereNotNull('human_score')
            ->get();

        if ($predictions->isEmpty()) {
            return [
                'count' => 0,
                'accuracy' => null,
                'mae' => null,
            ];
        }

        $accurateCount = 0;
        $totalError = 0;

        foreach ($predictions as $prediction) {
            if ($prediction->isAccurate($threshold)) {
                $accurateCount++;
            }
            $totalError += $prediction->score_error;
        }

        return [
            'count' => $predictions->count(),
            'accuracy' => ($accurateCount / $predictions->count()) * 100,
            'mae' => $totalError / $predictions->count(),
        ];
    }

    /**
     * Thống kê theo classification
     */
    public static function getClassificationStats($query = null): array
    {
        $baseQuery = $query ?? static::query();
        
        return $baseQuery
            ->selectRaw('classification, COUNT(*) as count, AVG(final_score) as avg_score')
            ->groupBy('classification')
            ->get()
            ->keyBy('classification')
            ->toArray();
    }
}
