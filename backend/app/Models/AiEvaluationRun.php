<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiEvaluationRun extends Model
{
    protected $fillable = [
        'status',
        'started_at',
        'finished_at',
        'total_cases',
        'completed_cases',
        'error_message',
        'results_path',
        'metrics',
    ];

    protected $casts = [
        'metrics' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
