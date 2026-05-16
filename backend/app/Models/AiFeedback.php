<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recruiter feedback on AI match results.
 *
 * Captures agree/disagree/note/flag signals from recruiters reviewing
 * the AI-generated shortlist. Designed for future model evaluation.
 */
class AiFeedback extends Model
{
    use HasFactory;

    protected $table = 'ai_feedbacks';

    protected $fillable = [
        'application_id',
        'job_id',
        'recruiter_id',
        'feedback_type',
        'feedback_note',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }
}
