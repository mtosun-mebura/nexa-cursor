<?php

namespace Tests\Feature;

use App\Modules\NexaTaxi\Models\TransportAnnouncement;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractPortalAnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables();
    }

    public function test_schema_creates_transport_announcements_table(): void
    {
        $this->assertTrue(Schema::hasTable('transport_announcements'));
        $this->assertTrue(Schema::hasColumn('transport_announcements', 'severity'));
        $this->assertTrue(Schema::hasColumn('transport_announcements', 'is_active'));
    }

    public function test_active_scope_returns_only_visible_announcements(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');

        TransportAnnouncement::query()->create([
            'company_id' => 1,
            'transport_customer_id' => 10,
            'title' => 'Actief',
            'body' => 'Zichtbaar',
            'severity' => 'warning',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        TransportAnnouncement::query()->create([
            'company_id' => 1,
            'transport_customer_id' => 10,
            'title' => 'Uit',
            'body' => null,
            'severity' => 'info',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'is_active' => false,
        ]);

        TransportAnnouncement::query()->create([
            'company_id' => 1,
            'transport_customer_id' => 10,
            'title' => 'Verlopen',
            'body' => null,
            'severity' => 'info',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subHour(),
            'is_active' => true,
        ]);

        TransportAnnouncement::query()->create([
            'company_id' => 1,
            'transport_customer_id' => 99,
            'title' => 'Andere klant',
            'body' => null,
            'severity' => 'critical',
            'starts_at' => now()->subHour(),
            'ends_at' => null,
            'is_active' => true,
        ]);

        $active = TransportAnnouncement::query()
            ->activeForCustomer(10)
            ->pluck('title')
            ->all();

        $this->assertSame(['Actief'], $active);
    }
}
