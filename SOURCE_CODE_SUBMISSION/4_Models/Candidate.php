<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\EncryptsAttributes;

class Candidate extends Model
{
    use HasFactory, EncryptsAttributes;
    
    /**
     * Fields to encrypt using IT Solo Leveling AES-256-GCM
     * Data đã được mã hóa bởi encrypt_existing_data.php
     */
    protected $encryptable = [
        'name',
        'email', 
        'phone',
        'summary',
        'about_me',
        'profile_data',
    ];

    protected $fillable = [
        'user_id',
        'name', 
        'email', 
        'phone', 
        'file_path_cv', 
        'summary', 
        'about_me',
        'work_experiences',
        'skills_json',
        'skills',
        'experience',
        'education',
        'sector',
        'profile_data',
        'certifications',
        'portfolio_url',
        'linkedin_url',
        'github_url',
        'proof_files',
    ];

    protected $casts = [
        'skills_json' => 'array',
        'proof_files' => 'array',
        'work_experiences' => 'array',
        // profile_data is encrypted, handled by EncryptsAttributes trait
    ];
    
    /**
     * Get profile_data attribute - ensure array after decryption
     * Note: EncryptsAttributes trait handles decryption automatically via getAttribute()
     * This accessor runs AFTER trait decryption, so $value should already be decrypted
     */
    public function getProfileDataAttribute($value)
    {
        // Value comes from EncryptsAttributes trait which already decrypts
        // If it's already an array, return it
        if (is_array($value)) {
            return $value;
        }
        
        // If it's a JSON string (decrypted from encrypted JSON), parse it
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        
        // Return empty array as default
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

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
