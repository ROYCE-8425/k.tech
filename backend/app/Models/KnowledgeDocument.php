<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Knowledge corpus document for RAG grounding.
 *
 * Schema is shared with ai-service which manages the pgvector embedding column
 * at runtime. This model covers the non-vector columns owned by Laravel.
 */
class KnowledgeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'title',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
