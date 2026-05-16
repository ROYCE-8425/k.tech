<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvRubric extends Model
{
    protected $table = 'cv_rubrics';

    protected $fillable = [
        'key',
        'name',
        'total_max',
        'is_active',
    ];

    protected $casts = [
        'total_max' => 'int',
        'is_active' => 'boolean',
    ];
}
