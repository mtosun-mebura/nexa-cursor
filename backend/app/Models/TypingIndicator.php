<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingIndicator extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'chat_room_id',
        'user_id',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    /**
     * Get the chat room
     */
    public function chatRoom(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class);
    }

    /**
     * Get the user who is typing
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
