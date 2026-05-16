<?php

namespace App\Models\Traits;

use App\Services\ITSoloLevelingEncryption;
use Exception;

/**
 * Trait EncryptsAttributes
 * 
 * Tự động mã hóa/giải mã các trường nhạy cảm trong Model
 * Sử dụng ITSoloLevelingEncryption service (AES-256-GCM)
 * 
 * Cách sử dụng:
 * 
 * class Candidate extends Model {
 *     use EncryptsAttributes;
 *     
 *     protected $encryptable = ['name', 'email', 'phone'];
 * }
 */
trait EncryptsAttributes
{
    /**
     * Encryption service instance
     */
    protected static ?ITSoloLevelingEncryption $encryptionService = null;
    
    /**
     * Get encryption service instance
     */
    protected static function getEncryptionService(): ITSoloLevelingEncryption
    {
        if (self::$encryptionService === null) {
            self::$encryptionService = app(ITSoloLevelingEncryption::class);
        }
        
        return self::$encryptionService;
    }
    
    /**
     * Boot trait
     */
    protected static function bootEncryptsAttributes(): void
    {
        // Encrypt trước khi save
        static::saving(function ($model) {
            $model->encryptAttributes();
        });
        
        // Decrypt sau khi retrieve
        static::retrieved(function ($model) {
            $model->decryptAttributes();
        });
    }
    
    /**
     * Get encryptable attributes
     */
    protected function getEncryptableAttributes(): array
    {
        return property_exists($this, 'encryptable') ? $this->encryptable : [];
    }
    
    /**
     * Encrypt attributes trước khi save
     */
    protected function encryptAttributes(): void
    {
        $encryptable = $this->getEncryptableAttributes();
        
        foreach ($encryptable as $attribute) {
            if ($this->isDirty($attribute)) {
                // Get raw value from attributes (not through getAttribute which may decrypt)
                $value = $this->attributes[$attribute] ?? null;
                
                if ($value !== null && $value !== '') {
                    // Check if needs encryption
                    $needsEncryption = false;
                    
                    if (is_string($value)) {
                        $needsEncryption = !$this->isEncrypted($value);
                    } else {
                        // Non-string (array, object) always encrypt
                        $needsEncryption = true;
                    }
                    
                    if ($needsEncryption) {
                        try {
                            $encrypted = self::getEncryptionService()->encrypt($value);
                            $this->attributes[$attribute] = $encrypted;
                        } catch (Exception $e) {
                            \Log::error("Failed to encrypt {$attribute}: " . $e->getMessage());
                            throw $e;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Decrypt attributes sau khi retrieve
     */
    protected function decryptAttributes(): void
    {
        $encryptable = $this->getEncryptableAttributes();
        
        foreach ($encryptable as $attribute) {
            $value = $this->attributes[$attribute] ?? null;
            
            if ($value !== null && $value !== '') {
                try {
                    $decrypted = self::getEncryptionService()->decrypt($value);
                    $this->attributes[$attribute] = $decrypted;
                } catch (Exception $e) {
                    \Log::warning("Failed to decrypt {$attribute}: " . $e->getMessage());
                    // Keep encrypted value, don't throw
                }
            }
        }
    }
    
    /**
     * Override getAttribute to decrypt on access
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        
        // If attribute is encryptable and looks encrypted (base64)
        if (in_array($key, $this->getEncryptableAttributes())) {
            if (is_string($value) && $value !== '' && $this->isEncrypted($value)) {
                try {
                    return self::getEncryptionService()->decrypt($value);
                } catch (Exception $e) {
                    \Log::warning("Failed to decrypt {$key} on get: " . $e->getMessage());
                    return $value;
                }
            }
        }
        
        return $value;
    }
    
    /**
     * Override setAttribute to encrypt on set
     */
    public function setAttribute($key, $value)
    {
        // If attribute is encryptable and not already encrypted
        if (in_array($key, $this->getEncryptableAttributes())) {
            if ($value !== null && $value !== '') {
                // Check if string and not encrypted
                if (is_string($value) && !$this->isEncrypted($value)) {
                    try {
                        $value = self::getEncryptionService()->encrypt($value);
                    } catch (Exception $e) {
                        \Log::error("Failed to encrypt {$key} on set: " . $e->getMessage());
                        throw $e;
                    }
                } elseif (!is_string($value)) {
                    // Non-string (array, object) - encrypt as JSON
                    try {
                        $value = self::getEncryptionService()->encrypt($value);
                    } catch (Exception $e) {
                        \Log::error("Failed to encrypt {$key} on set: " . $e->getMessage());
                        throw $e;
                    }
                }
            }
        }
        
        return parent::setAttribute($key, $value);
    }
    
    /**
     * Check if value is already encrypted (looks like base64)
     */
    protected function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        
        // Check if it looks like base64
        // Our encrypted format: base64(IV + tag + ciphertext)
        // Minimum length: base64(28 bytes) = 38 chars
        return strlen($value) >= 38 && 
               preg_match('/^[A-Za-z0-9+\/]+=*$/', $value) && 
               base64_decode($value, true) !== false;
    }
    
    /**
     * Get original encrypted value (bypass decryption)
     */
    public function getEncryptedAttribute(string $key): ?string
    {
        return $this->attributes[$key] ?? null;
    }
    
    /**
     * Disable encryption temporarily for bulk operations
     */
    public static function withoutEncryption(callable $callback)
    {
        $originalService = self::$encryptionService;
        self::$encryptionService = null;
        
        try {
            return $callback();
        } finally {
            self::$encryptionService = $originalService;
        }
    }
}
