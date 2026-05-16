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
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
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
}
