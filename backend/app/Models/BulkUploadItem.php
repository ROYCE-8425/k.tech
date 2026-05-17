<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkUploadItem extends Model
{
    protected $fillable = [
        'batch_id',
        'job_id',
        'original_filename',
        'stored_path',
        'mime_type',
        'status',
        'error_message',
        'candidate_id',
        'application_id',
        'parsed_cv_data',
        'ai_match_result',
    ];

    protected $casts = [
        'parsed_cv_data' => 'array',
        'ai_match_result' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BulkUploadBatch::class, 'batch_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
