<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\EncryptsAttributes;

class Interview extends Model
{
    use HasFactory, EncryptsAttributes;
    
    /**
     * Fields to encrypt using IT Solo Leveling AES-256-GCM
     * Data đã được mã hóa bởi encrypt_existing_data.php
     */
    protected $encryptable = [
        'notes',
        'feedback',
        'location',
    ];

    protected $fillable = [
        'application_id',
        'scheduled_by',
        'scheduled_at',
        'duration_minutes',
        'type',
        'location',
        'notes',
        'status',
        'feedback',
        'rating',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
        'rating' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'online' => '💻 Online',
            'onsite' => '🏢 Tại văn phòng',
            'phone' => '📞 Điện thoại',
            default => $this->type,
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'scheduled' => '📅 Đã lên lịch',
            'completed' => '✅ Hoàn thành',
            'cancelled' => '❌ Đã hủy',
            'rescheduled' => '🔄 Đổi lịch',
            default => $this->status,
        };
    }
}
