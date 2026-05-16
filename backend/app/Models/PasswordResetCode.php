<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetCode extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'code',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the code
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the code is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Generate a new reset code for an email
     */
    public static function generateFor(string $email): ?self
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return null;
        }

        // Delete any existing codes for this email
        self::where('email', $email)->delete();

        // Generate a random 6-digit code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return self::create([
            'user_id' => $user->id,
            'email' => $email,
            'code' => $code,
            'expires_at' => now()->addMinutes(15), // Code valid for 15 minutes
        ]);
    }

    /**
     * Verify a code for an email
     */
    public static function verify(string $email, string $code): ?User
    {
        $record = self::where('email', $email)
            ->where('code', $code)
            ->first();

        if (!$record) {
            return null;
        }

        if ($record->isExpired()) {
            $record->delete();
            return null;
        }

        return $record->user;
    }

    /**
     * Mark code as used (delete it)
     */
    public static function markAsUsed(string $email): void
    {
        self::where('email', $email)->delete();
    }
}
