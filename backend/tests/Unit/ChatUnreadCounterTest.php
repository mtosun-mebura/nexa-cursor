<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Services\ChatUnreadCounter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatUnreadCounterTest extends TestCase
{
    #[Test]
    public function unread_count_query_uses_existing_chat_columns(): void
    {
        $this->assertTrue(Schema::hasTable('chats'));
        $this->assertFalse(Schema::hasColumn('chats', 'candidate_id'));
        $this->assertTrue(Schema::hasColumn('chats', 'user_id'));
        $this->assertTrue(Schema::hasColumn('chats', 'is_active'));

        $company = Company::query()->create(['name' => 'Chat unread', 'is_active' => true]);
        $owner = User::factory()->create(['company_id' => $company->id]);
        $other = User::factory()->create(['company_id' => $company->id]);

        $activeId = $this->insertChat($owner->id, $company->id, true);
        $inactiveId = $this->insertChat($owner->id, $company->id, false);
        $otherId = $this->insertChat($other->id, $company->id, true);

        $this->insertMessage($activeId, 'App\\Models\\Candidate', null);
        $this->insertMessage($activeId, User::class, null);
        $this->insertMessage($activeId, 'App\\Models\\Candidate', now());
        $this->insertMessage($inactiveId, 'App\\Models\\Candidate', null);
        $this->insertMessage($otherId, 'App\\Models\\Candidate', null);

        $counter = app(ChatUnreadCounter::class);
        $sql = $counter->unreadQuery((int) $owner->id, User::class)->toSql();

        $this->assertStringNotContainsString('candidate_id', strtolower($sql));
        $this->assertStringContainsString('user_id', strtolower($sql));
        $this->assertStringContainsString('is_active', strtolower($sql));
        $this->assertSame(1, $counter->countForUser((int) $owner->id, User::class));
        $this->assertSame(1, $counter->countForUser((int) $other->id, User::class));
    }

    private function insertChat(int $userId, int $companyId, bool $active): int
    {
        return (int) DB::table('chats')->insertGetId([
            'user_id' => $userId,
            'company_id' => $companyId,
            'is_active' => $active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertMessage(int $chatId, string $senderType, mixed $readAt): void
    {
        $row = [
            'chat_id' => $chatId,
            'message' => 'Hallo',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $columns = Schema::getColumnListing('chat_messages');
        if (in_array('sender_type', $columns, true)) {
            $row['sender_type'] = $senderType;
        }
        if (in_array('sender_id', $columns, true)) {
            $row['sender_id'] = 1;
        }
        if (in_array('read_at', $columns, true)) {
            $row['read_at'] = $readAt;
        }

        DB::table('chat_messages')->insert($row);
    }
}
