<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'cv_scoring_profile_id',
        'title',
        'description',
        'requirements',
        'salary_min',
        'salary_max',
        'currency',
        'location',
        'status',
        'published_at',
        // Phase 1: AI matching structured inputs
        'required_skills',
        'preferred_skills',
        'seniority',
        'min_experience_years',
        'max_experience_years',
        'scoring_config',
        'ai_recruiter_notes',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        // Phase 1: JSON casts for structured AI fields
        'required_skills' => 'array',
        'preferred_skills' => 'array',
        'scoring_config' => 'array',
        'min_experience_years' => 'integer',
        'max_experience_years' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function cvScoringProfile()
    {
        return $this->belongsTo(\App\Models\CvScoringProfile::class, 'cv_scoring_profile_id');
    }

    public function aiFeedbacks()
    {
        return $this->hasMany(AiFeedback::class);
    }
}

