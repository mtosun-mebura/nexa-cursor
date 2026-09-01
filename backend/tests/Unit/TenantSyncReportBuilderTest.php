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

        $builder->setProgressTotal(3);
        $builder->addStep('Tenant-sync gestart');
        $builder->addRow('Hoofddatabase', 'users', 2, 1, 0);
        $builder->addNote('Testnotitie');
        $builder->setSummary(42, 3, 1, 0);

        $types = array_column($events, 'type');
        $this->assertContains('progress', $types);
        $this->assertContains('step', $types);
        $this->assertContains('row', $types);
        $this->assertContains('note', $types);
        $this->assertContains('summary', $types);

        $step = collect($events)->firstWhere('type', 'step');
        $this->assertSame('Tenant-sync gestart', $step['label'] ?? null);

        $row = collect($events)->firstWhere('type', 'row');
        $this->assertSame('users', $row['row']['label'] ?? null);

        $note = collect($events)->firstWhere('type', 'note');
        $this->assertSame('Testnotitie', $note['note'] ?? null);

        $summary = collect($events)->firstWhere('type', 'summary');
        $this->assertSame(42, $summary['remote_company_id'] ?? null);

        $finalProgress = collect($events)->reverse()->firstWhere('type', 'progress');
        $this->assertSame(100, $finalProgress['percent'] ?? null);
    }
}
