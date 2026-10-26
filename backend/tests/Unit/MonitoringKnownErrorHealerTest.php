<?php

namespace Tests\Unit;

use App\Models\ErrorLog;
use App\Services\Monitoring\AdminMonitoringService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonitoringKnownErrorHealerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('module_taxi');
    }

    #[Test]
    public function known_error_recheck_marks_matching_errors_resolved(): void
    {
        $unread = $this->log('SQLSTATE[42703]: Undefined column: column "candidate_id" does not exist in chat_messages where exists (select * from chats)');
        $cancel = $this->log("Scheduled command ['/usr/local/bin/php' 'artisan' taxi:cancel-unaccepted-rides] failed with exit code [1].");
        $type = $this->log('SQLSTATE[23505]: Unique violation: duplicate key value violates unique constraint "pg_type_typname_nsp_index" Key (typname, typnamespace)=(transport_customers, 17893) already exists.');
        $other = $this->log('SQLSTATE[42P01]: Undefined table: relation "cache" does not exist');

        Artisan::shouldReceive('call')
            ->once()
            ->withArgs(fn (string $command): bool => $command === 'taxi:cancel-unaccepted-rides')
            ->andReturn(0);

        app(AdminMonitoringService::class)->recentErrors();

        $this->assertTrue($unread->fresh()->resolved);
        $this->assertNotNull($unread->fresh()->resolved_at);
        $this->assertTrue($cancel->fresh()->resolved);
        $this->assertTrue($type->fresh()->resolved);
        $this->assertFalse($other->fresh()->resolved);
        $this->assertArrayNotHasKey('repair_attempted', $other->fresh()->context ?? []);
        $this->assertTrue(DB::connection('module_taxi')->getSchemaBuilder()->hasTable('transport_customers'));
    }

    #[Test]
    public function known_error_recheck_leaves_failure_unresolved(): void
    {
        $cancel = $this->log("Scheduled command ['/usr/local/bin/php' 'artisan' taxi:cancel-unaccepted-rides] failed with exit code [1].");

        Artisan::shouldReceive('call')
            ->once()
            ->withArgs(fn (string $command): bool => $command === 'taxi:cancel-unaccepted-rides')
            ->andReturn(1);

        $monitoring = app(AdminMonitoringService::class);
        $monitoring->recentErrors();
        $monitoring->recentErrors();

        $fresh = $cancel->fresh();
        $this->assertFalse($fresh->resolved);
        $this->assertTrue(($fresh->context['repair_attempted'] ?? false) === true);
    }

    private function log(string $message): ErrorLog
    {
        return ErrorLog::query()->create([
            'message' => $message,
            'context' => ['exception' => 'Exception'],
            'source' => 'exception',
            'resolved' => false,
            'created_at' => now(),
        ]);
    }
}
