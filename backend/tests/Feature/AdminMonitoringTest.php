<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use App\Services\Monitoring\MonitoringErrorLogger;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMonitoringTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function company_admin_cannot_open_monitoring(): void
    {
        $company = Company::query()->create(['name' => 'Monitoring Tenant', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company-admin');

        $this->actingAs($user)
            ->get(route('admin.monitoring.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.monitoring.jobs.restart-stuck'))
            ->assertForbidden();
    }

    #[Test]
    public function super_admin_can_open_monitoring_and_switch_income_period(): void
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $company = Company::query()->create(['name' => 'Monitoring Omzet', 'is_active' => true]);
        Payment::query()->create([
            'company_id' => $company->id,
            'amount' => 125.50,
            'currency' => 'EUR',
            'status' => 'paid',
            'paid_at' => Carbon::now('Europe/Amsterdam')->setTime(12, 0),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.monitoring.index'))
            ->assertOk()
            ->assertSee('Jobs', false)
            ->assertSee('Inkomsten', false);

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.monitoring.index'))
            ->assertOk()
            ->assertSee('Monitoring', false)
            ->assertSee('Jobs', false)
            ->assertSee('Fouten', false)
            ->assertSee('Processen', false)
            ->assertSee('Inkomsten', false)
            ->assertSee('worden niet herstart', false);

        $day = $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.monitoring.index', ['sectie' => 'inkomsten', 'periode' => 'dag']));
        $day->assertOk()
            ->assertSee('00:00', false)
            ->assertSee('23:00', false)
            ->assertSee('125,50', false)
            ->assertSee('Betaalde modulebetalingen', false);
        $this->assertIncomeChartSeries($day->getContent(), 125.50);

        $month = $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.monitoring.index', ['sectie' => 'inkomsten', 'periode' => 'maand']));
        $month->assertOk()
            ->assertSee('125,50', false);
        $this->assertIncomeChartSeries($month->getContent(), 125.50);

        $year = $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.monitoring.index', ['sectie' => 'inkomsten', 'periode' => 'jaar']));
        $year->assertOk()
            ->assertSee('jan ', false)
            ->assertSee('dec ', false)
            ->assertSee('125,50', false);
        $this->assertIncomeChartSeries($year->getContent(), 125.50);
    }

    #[Test]
    public function sidebar_and_alert_are_super_admin_only(): void
    {
        $company = Company::query()->create(['name' => 'Monitoring Sidebar', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');
        app(MonitoringErrorLogger::class)->record('Queue worker testfout', 'queue', ['job' => 'Demo']);

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.monitoring.index', ['sectie' => 'fouten']))
            ->assertOk()
            ->assertSee(route('admin.monitoring.index'), false)
            ->assertSee('onopgeloste monitoringfout', false)
            ->assertSee('Queue worker testfout', false);

        $tenantAdmin = User::factory()->create(['company_id' => $company->id]);
        $tenantAdmin->assignRole('company-admin');

        $this->actingAs($tenantAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.monitoring.index'), false)
            ->assertDontSee('onopgeloste monitoringfout', false);
    }

    #[Test]
    public function super_admin_retries_failed_job_and_skips_payment_capture(): void
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $safeUuid = (string) Str::uuid();
        $paymentUuid = (string) Str::uuid();
        $this->insertFailedJob($safeUuid, 'App\\Jobs\\SyncKnvTaxiMembersJob');
        $this->insertFailedJob($paymentUuid, 'App\\Jobs\\ProcessPlatformBillingJob');

        $this->actingAs($admin)
            ->post(route('admin.monitoring.jobs.retry', $paymentUuid))
            ->assertRedirect(route('admin.monitoring.index', ['sectie' => 'jobs', 'periode' => 'maand']));

        $this->assertDatabaseHas('failed_jobs', ['uuid' => $paymentUuid]);

        $this->actingAs($admin)
            ->post(route('admin.monitoring.jobs.retry', $safeUuid))
            ->assertRedirect();

        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $safeUuid]);
        $this->assertDatabaseHas('jobs', ['queue' => 'default']);
    }

    #[Test]
    public function super_admin_releases_stuck_job_but_not_a_running_or_financial_one(): void
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $stuckId = $this->insertJob('App\\Jobs\\InstallModuleJob', now()->subHours(2)->timestamp);
        $freshId = $this->insertJob('App\\Jobs\\InstallModuleJob', now()->timestamp);
        $financialId = $this->insertJob('App\\Jobs\\ProcessPlatformBillingJob', now()->subHours(2)->timestamp);

        $this->actingAs($admin)
            ->post(route('admin.monitoring.jobs.release', $stuckId))
            ->assertRedirect();

        $this->assertNull(DB::table('jobs')->where('id', $stuckId)->value('reserved_at'));

        $this->actingAs($admin)
            ->post(route('admin.monitoring.jobs.release', $freshId))
            ->assertRedirect();
        $this->assertNotNull(DB::table('jobs')->where('id', $freshId)->value('reserved_at'));

        $this->actingAs($admin)
            ->post(route('admin.monitoring.jobs.release', $financialId))
            ->assertRedirect();
        $this->assertNotNull(DB::table('jobs')->where('id', $financialId)->value('reserved_at'));
    }

    #[Test]
    public function finished_run_is_reported_and_company_admin_cannot_poll(): void
    {
        $company = Company::query()->create(['name' => 'Monitoring Poll', 'is_active' => true]);
        $tenantAdmin = User::factory()->create(['company_id' => $company->id]);
        $tenantAdmin->assignRole('company-admin');

        $this->actingAs($tenantAdmin)
            ->getJson(route('admin.monitoring.status'))
            ->assertForbidden();

        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        Carbon::setTestNow(Carbon::parse('2026-10-20 21:04:12', 'UTC'));

        try {
            $task = new Event(new CacheEventMutex($this->app->make('cache')), 'taxi:cancel-unaccepted-rides');
            $task->name('taxi-cancel-unaccepted-rides');
            $task->exitCode = 0;

            event(new ScheduledTaskStarting($task));

            $running = $this->actingAs($admin)->getJson(route('admin.monitoring.status'));
            $running->assertOk();
            $runningTask = collect($running->json('tasks'))->firstWhere('key', 'taxi-cancel-unaccepted-rides');
            $this->assertNotNull($runningTask);
            $this->assertTrue($runningTask['running']);
            $this->assertSame('Niet-geaccepteerde ritten annuleren', $runningTask['name']);
            $this->assertSame('running', $runningTask['run_status']);
            $this->assertNotEmpty($runningTask['started_at']);
            $this->assertNull($runningTask['finished_at']);
            $this->assertSame('Niet-geaccepteerde ritten annuleren is gestart', $runningTask['running_text']);
            $this->assertNull($runningTask['finished_text']);

            event(new ScheduledTaskFinished($task, 0.4));

            $finished = $this->actingAs($admin)->getJson(route('admin.monitoring.status'));
            $finished->assertOk();
            $finishedTask = collect($finished->json('tasks'))->firstWhere('key', 'taxi-cancel-unaccepted-rides');
            $this->assertNotNull($finishedTask);
            $this->assertFalse($finishedTask['running']);
            $this->assertSame('Gelukt', $finishedTask['status']);
            $this->assertSame('ok', $finishedTask['run_status']);
            $this->assertNotEmpty($finishedTask['started_at']);
            $this->assertNotEmpty($finishedTask['finished_at']);
            $this->assertSame('Afgerond om 20-10-2026 23:04:12', $finishedTask['finished_text']);
            $this->assertSame('20-10-2026 23:04:12', $finishedTask['last_success']);
            $this->assertNull($finished->json('jobs.banner'));
            $this->assertSame([], $finished->json('jobs.reserved'));

            $this->actingAs($admin)
                ->get(route('admin.monitoring.index', ['sectie' => 'processen']))
                ->assertOk()
                ->assertSee('Niet-geaccepteerde ritten annuleren', false)
                ->assertSee('Elke minuut', false)
                ->assertSee('Afgerond om 20-10-2026 23:04:12', false)
                ->assertDontSee('· gelukt', false)
                ->assertSee('width: fit-content', false)
                ->assertSee('class="block">20-10-2026', false)
                ->assertSee('class="block">23:04:12', false);

            $stuckId = $this->insertJob('App\\Jobs\\SyncKnvTaxiMembersJob', now()->subHours(2)->timestamp);
            $stuck = $this->actingAs($admin)->getJson(route('admin.monitoring.status'));
            $stuck->assertOk();
            $this->assertNull($stuck->json('jobs.banner'));
            $this->assertSame([], $stuck->json('jobs.reserved'));
            DB::table('jobs')->where('id', $stuckId)->delete();

            $this->insertJob('App\\Jobs\\SyncKnvTaxiMembersJob', now()->timestamp);
            $withJob = $this->actingAs($admin)->getJson(route('admin.monitoring.status'));
            $withJob->assertOk();
            $this->assertSame('Er draait een job: SyncKnvTaxiMembersJob', $withJob->json('jobs.banner'));
            $reserved = $withJob->json('jobs.reserved');
            $this->assertIsArray($reserved);
            $this->assertCount(1, $reserved);
            $this->assertSame('SyncKnvTaxiMembersJob', $reserved[0]['name']);
            $this->assertNotEmpty($reserved[0]['id']);
            $this->assertNotEmpty($reserved[0]['reserved_at']);

            DB::table('jobs')->delete();
            $after = $this->actingAs($admin)->getJson(route('admin.monitoring.status'));
            $after->assertOk();
            $this->assertNull($after->json('jobs.banner'));
        } finally {
            Carbon::setTestNow();
        }
    }

    private function assertIncomeChartSeries(string $html, float $expectedTotal): void
    {
        $this->assertSame(1, preg_match('/var labels = (\[.*?\]);\s*var data = (\[.*?\]);/s', $html, $matches));
        $labels = json_decode($matches[1], true);
        $data = json_decode($matches[2], true);
        $this->assertIsList($labels);
        $this->assertIsList($data);
        $this->assertNotSame([], $labels);
        $this->assertSame(count($labels), count($data));
        $this->assertNotContains('Geen data', $labels);
        $this->assertEqualsWithDelta($expectedTotal, array_sum($data), 0.001);
        $this->assertGreaterThan(0, max($data));
    }

    private function insertFailedJob(string $uuid, string $displayName): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'uuid' => $uuid,
                'displayName' => $displayName,
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'data' => [],
                'attempts' => 1,
            ]),
            'exception' => 'Test failure',
            'failed_at' => now(),
        ]);
    }

    private function insertJob(string $displayName, int $reservedAt): int
    {
        return (int) DB::table('jobs')->insertGetId([
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => $displayName,
                'data' => [],
            ]),
            'attempts' => 1,
            'reserved_at' => $reservedAt,
            'available_at' => $reservedAt,
            'created_at' => $reservedAt,
        ]);
    }
};
