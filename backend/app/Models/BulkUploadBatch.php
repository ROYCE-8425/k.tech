<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkUploadBatch extends Model
{
    protected $fillable = [
        'job_id',
        'recruiter_id',
        'total_files',
        'processed_files',
        'failed_files',
        'status',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BulkUploadItem::class, 'batch_id');
    }
}
