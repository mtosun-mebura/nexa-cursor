<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    protected $table = 'knowledge_chunks';

    public const UPDATED_AT = null;

    protected $fillable = [
        'document_id',
        'chunk_text',
        'embedding',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }
}
