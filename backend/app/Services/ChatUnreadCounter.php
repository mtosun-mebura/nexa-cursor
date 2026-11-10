<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ongelezen chatberichten tellen op kolommen die in chats bestaan.
 * Het gedeelde schema heeft user_id, company_id en is_active; geen candidate_id.
 * chat_participants hoort bij chat_rooms (chat_room_id + user_id), niet bij deze chat_id-telling.
 */
class ChatUnreadCounter
{
    public function countForUser(int $userId, string $senderType, ?string $connection = null): int
    {
        return (int) $this->unreadQuery($userId, $senderType, $connection)->count();
    }

    public function unreadQuery(int $userId, string $senderType, ?string $connection = null): Builder
    {
        $connection = $connection ?? (string) config('database.default');
        $schema = Schema::connection($connection);
        $hasCandidate = $schema->hasTable('chats') && $schema->hasColumn('chats', 'candidate_id');

        return DB::connection($connection)
            ->table('chat_messages')
            ->whereExists(function (Builder $query) use ($userId, $hasCandidate, $schema) {
                $query->selectRaw('1')
                    ->from('chats')
                    ->whereColumn('chat_messages.chat_id', 'chats.id')
                    ->where(function (Builder $inner) use ($userId, $hasCandidate) {
                        $inner->where('chats.user_id', $userId);
                        if ($hasCandidate) {
                            $inner->orWhere('chats.candidate_id', $userId);
                        }
                    })
                    ->where('chats.is_active', true);

                if ($schema->hasColumn('chats', 'deleted_at')) {
                    $query->whereNull('chats.deleted_at');
                }
            })
            ->where('chat_messages.sender_type', '!=', $senderType)
            ->whereNull('chat_messages.read_at');
    }
}
