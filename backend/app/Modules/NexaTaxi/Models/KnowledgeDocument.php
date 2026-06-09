<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeDocument extends Model
{
    protected $table = 'knowledge_documents';

    public const UPDATED_AT = null;

    protected $fillable = [
        'title',
        'content',
        'category',
        'embedding',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function categoryLabels(): array
    {
        return [
            'diensten' => 'Diensten',
            'voorwaarden' => 'Voorwaarden',
            'contact' => 'Contact',
            'privacy' => 'Privacy',
            'website' => 'Website',
            'algemeen' => 'Algemeen',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id');
    }
}
