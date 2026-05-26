<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_id',
        'sender_id',
        'sender_type',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Get the chat this message belongs to
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Get the sender (user or candidate)
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if message is from a user
     */
    public function isFromUser(): bool
    {
        return $this->sender_type === User::class;
    }

    /**
     * Check if message is from a candidate
     */
    public function isFromCandidate(): bool
    {
        return $this->sender_type === Candidate::class;
    }
}
