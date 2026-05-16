<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorCode extends Model
{
    protected $fillable = [
        'user_id',
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
     * Generate a new OTP code for a user
     */
    public static function generateFor(User $user): self
    {
        // Delete any existing codes for this user
        self::where('user_id', $user->id)->delete();

        // Generate a random 6-digit code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return self::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(10), // Code valid for 10 minutes
        ]);
    }

    /**
     * Verify a code for a user
     */
    public static function verify(User $user, string $code): bool
    {
        $record = self::where('user_id', $user->id)
            ->where('code', $code)
            ->first();

        if (!$record) {
            return false;
        }

        if ($record->isExpired()) {
            $record->delete();
            return false;
        }

        // Code is valid - delete it so it can't be reused
        $record->delete();

        return true;
    }
}
