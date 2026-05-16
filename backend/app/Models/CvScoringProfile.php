<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvScoringProfile extends Model
{
    protected $table = 'cv_scoring_profiles';

    protected $fillable = [
        'rubric_id',
        'key',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function rubric()
    {
        return $this->belongsTo(CvRubric::class, 'rubric_id');
    }

    public function overrides()
    {
        return $this->hasMany(CvScoringOverride::class, 'profile_id');
    }
}
