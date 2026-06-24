<?php

namespace Tests\Unit;

use App\Services\TenantSync\TenantSyncReportBuilder;
use PHPUnit\Framework\TestCase;

class TenantSyncReportBuilderTest extends TestCase
{
    public function test_emits_progress_events_for_steps_rows_and_notes(): void
    {
        $events = [];
        $builder = new TenantSyncReportBuilder;
        $builder->onProgress(function (array $event) use (&$events): void {
            $events[] = $event;
        });

        $builder->addStep('Tenant-sync gestart');
        $builder->addRow('Hoofddatabase', 'users', 2, 1, 0);
        $builder->addNote('Testnotitie');
        $builder->setSummary(42, 3, 1, 0);

        $this->assertSame('step', $events[0]['type']);
        $this->assertSame('Tenant-sync gestart', $events[0]['label']);

        $this->assertSame('row', $events[1]['type']);
        $this->assertSame('users', $events[1]['row']['label']);

        $this->assertSame('note', $events[2]['type']);
        $this->assertSame('Testnotitie', $events[2]['note']);

        $this->assertSame('summary', $events[3]['type']);
        $this->assertSame(42, $events[3]['remote_company_id']);
    }
}
