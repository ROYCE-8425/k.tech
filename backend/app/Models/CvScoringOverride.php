<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvScoringOverride extends Model
{
    protected $table = 'cv_scoring_overrides';

    protected $fillable = [
        'profile_id',
        'criterion_code',
        'weight',
        'override_config',
    ];

    protected $casts = [
        'weight' => 'float',
        'override_config' => 'array',
    ];

    public function profile()
    {
        return $this->belongsTo(CvScoringProfile::class, 'profile_id');
    }
}
