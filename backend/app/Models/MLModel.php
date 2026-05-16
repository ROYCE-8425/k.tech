<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ML Model Storage
 * 
 * Lưu trữ trained ML models (serialized)
 */
class MLModel extends Model
{
    protected $table = 'ml_models';

    protected $fillable = [
        'model_type',
        'version',
        'model_data',
        'feature_names',
        'hyperparameters',
        'metrics',
        'feature_importance',
        'training_samples',
        'training_started_at',
        'training_completed_at',
        'is_active',
    ];

    protected $casts = [
        'feature_names' => 'array',
        'hyperparameters' => 'array',
        'metrics' => 'array',
        'feature_importance' => 'array',
        'is_active' => 'boolean',
        'training_started_at' => 'datetime',
        'training_completed_at' => 'datetime',
    ];

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Lấy model đang active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Lọc theo loại model
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('model_type', $type);
    }

    /**
     * Lấy version mới nhất
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ========================================================================
    // STATIC METHODS
    // ========================================================================

    /**
     * Lấy active model theo type
     */
    public static function getActive(string $modelType): ?self
    {
        return static::ofType($modelType)->active()->latest()->first();
    }

    /**
     * Tạo version mới
     */
    public static function generateVersion(): string
    {
        return 'v' . date('Y.m.d.His');
    }

    // ========================================================================
    // METHODS
    // ========================================================================

    /**
     * Activate model này (deactivate các models khác cùng type)
     */
    public function activate(): self
    {
        // Deactivate các models khác
        static::ofType($this->model_type)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);
        
        // Activate model này
        $this->update(['is_active' => true]);
        
        return $this;
    }

    /**
     * Lấy model data đã decode
     */
    public function getDecodedModelData(): array
    {
        return json_decode($this->model_data, true) ?? [];
    }

    /**
     * Lấy R² score
     */
    public function getR2Score(): ?float
    {
        return $this->metrics['r2_score'] ?? null;
    }

    /**
     * Lấy MAE
     */
    public function getMae(): ?float
    {
        return $this->metrics['mae'] ?? null;
    }

    /**
     * Lấy training duration
     */
    public function getTrainingDuration(): ?int
    {
        if ($this->training_started_at && $this->training_completed_at) {
            return $this->training_completed_at->diffInSeconds($this->training_started_at);
        }
        return null;
    }
}
