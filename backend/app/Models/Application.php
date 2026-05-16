<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\EncryptsAttributes;

class Application extends Model
{
    use HasFactory, EncryptsAttributes;
    
    /**
     * Fields to encrypt using IT Solo Leveling AES-256-GCM
     * Data đã được mã hóa bởi encrypt_existing_data.php
     */
    protected $encryptable = [
        'cv_data',
        'cv_manual_inputs',
        'cv_manual_breakdown',
        'cover_letter',
        'notes',
    ];

    protected $fillable = [
        'job_id', 
        'candidate_id', 
        'status', 
        'cv_manual_score',
        'cv_manual_grade',
        'cv_manual_scored_at',
        'cv_manual_scored_by',
        'cv_manual_inputs',
        'cv_manual_breakdown',
        'cv_file_path', 
        'cv_data',
        'cv_proof_files',
        'cover_letter', 
        'notes',
        'applied_at',
        'interviewed_at',
        'interviewed_by',
        'ai_match_result',
    ];

    protected $casts = [
        'cv_manual_score' => 'float',
        'cv_manual_scored_at' => 'datetime',
        'applied_at' => 'datetime',
        'interviewed_at' => 'datetime',
        // Encrypted fields: NO cast needed (encryption service handles JSON)
        // 'cv_data' => 'array',
        // 'cv_manual_inputs' => 'array',
        // 'cv_manual_breakdown' => 'array',
        'cv_proof_files' => 'array',
        // Phase 3: sanitized AI match audit data (not encrypted — no PII in subset)
        'ai_match_result' => 'array',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewed_by');
    }

    public function interviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function latestInterview(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Interview::class)->latestOfMany();
    }

    public function aiFeedbacks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AiFeedback::class);
    }

    /**
     * Accessor: cv_data - ensure array after decryption
     */
    public function getCvDataAttribute($value)
    {
        return $this->getEncryptedArrayAttribute($value);
    }
    
    /**
     * Accessor: cv_manual_inputs - ensure array after decryption
     */
    public function getCvManualInputsAttribute($value)
    {
        return $this->getEncryptedArrayAttribute($value);
    }
    
    /**
     * Accessor: cv_manual_breakdown - ensure array after decryption
     */
    public function getCvManualBreakdownAttribute($value)
    {
        return $this->getEncryptedArrayAttribute($value);
    }
    
    /**
     * Helper: Get encrypted array attribute
     */
    protected function getEncryptedArrayAttribute($value)
    {
        // Let trait handle decryption first
        if (is_string($value) && $value !== '' && $this->isEncrypted($value)) {
            // Call parent to trigger trait's getAttribute
            $value = parent::getAttribute(debug_backtrace()[1]['args'][0] ?? null);
        }
        
        // After decryption, ensure it's array
        if (is_array($value)) {
            return $value;
        }
        
        // If it's JSON string, parse it
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        
        return [];
    }
    
    /**
     * Check if value looks encrypted
     */
    protected function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        
        return strlen($value) >= 38 && 
               preg_match('/^[A-Za-z0-9+\/]+=*$/', $value) && 
               base64_decode($value, true) !== false;
    }

    /**
     * Accessor: ai_score maps to cv_manual_score (convert from 100 to 10 scale)
     */
    public function getAiScoreAttribute(): ?float
    {
        if ($this->cv_manual_score === null) {
            return null;
        }
        // cv_manual_score is on 100 scale, convert to 10 scale
        return round($this->cv_manual_score / 10, 1);
    }
}
