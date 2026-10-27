<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ErrorLog;
use App\Models\User;
use App\Services\Monitoring\MonitoringErrorFingerprint;
use App\Services\Monitoring\MonitoringSafeAction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMonitoringBulkResolveTest extends TestCase
{
    private const TYPE_ERROR = 'SQLSTATE[23505]: Unique violation: duplicate key value violates unique constraint "pg_type_typname_nsp_index" Key (typname, typnamespace)=(transport_customers, 17893) already exists.';

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('module_taxi');
    }

    #[Test]
    public function selected_known_errors_are_resolved_and_the_rest_stays_open_with_a_diagnosis(): void
    {
        $known = $this->log(self::TYPE_ERROR);
        $missing = $this->log('SQLSTATE[42P01]: Undefined table: relation "cache" does not exist');
        $untouched = $this->log('Een andere fout die niet is geselecteerd');

        $this->actingAs($this->superAdmin())
            ->post(route('admin.monitoring.errors.bulk-resolve'), ['ids' => [$known->id, $missing->id]])
            ->assertRedirect(route('admin.monitoring.index', ['sectie' => 'fouten', 'periode' => 'maand']))
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, '1 opgelost, 1 vereisen handmatige actie, 0 mislukt')
                && str_contains($message, 'AI niet geconfigureerd'));

        $this->assertTrue($known->fresh()->resolved);
        $this->assertSame('opgelost', $known->fresh()->context['monitoring_fix']['status']);

        $open = $missing->fresh();
        $this->assertFalse($open->resolved);
        $this->assertSame('handmatig', $open->context['monitoring_fix']['status']);
        $this->assertSame('missing_table', $open->context['monitoring_fix']['category']);
        $this->assertStringContainsString('cache', (string) $open->context['monitoring_fix']['diagnosis_nl']);

        $this->assertFalse($untouched->fresh()->resolved);
        $this->assertArrayNotHasKey('monitoring_fix', $untouched->fresh()->context ?? []);
    }

    #[Test]
    public function ai_is_not_configured_leaves_unknown_errors_open_and_says_so(): void
    {
        Http::fake();
        $log = $this->log('Iets onbekends ging mis 42');

        $this->actingAs($this->superAdmin())
            ->post(route('admin.monitoring.errors.bulk-resolve'), ['ids' => [$log->id]])
            ->assertSessionHas('warning', fn (string $message): bool => str_contains($message, '0 opgelost, 1 vereisen handmatige actie'));

        Http::assertNothingSent();
        $fix = $log->fresh()->context['monitoring_fix'];
        $this->assertFalse($log->fresh()->resolved);
        $this->assertStringContainsString('AI niet geconfigureerd', $fix['outcome']);
    }

    #[Test]
    public function ai_is_called_once_per_fingerprint_and_never_for_deterministically_fixed_rows(): void
    {
        $this->configureAi(['category' => 'onbekend', 'safe_action' => null, 'diagnosis_nl' => 'Onbekende fout. Kijk in de logs.', 'proposed_fix_nl' => 'Controleer de code.']);

        $a1 = $this->log('Rit 101 kon niet worden verwerkt');
        $a2 = $this->log('Rit 202 kon niet worden verwerkt');
        $b = $this->log('Totaal andere storing');
        $fixed = $this->log(self::TYPE_ERROR);
        $this->assertSame(
            app(MonitoringErrorFingerprint::class)->for($a1),
            app(MonitoringErrorFingerprint::class)->for($a2),
        );

        $this->actingAs($this->superAdmin())
            ->post(route('admin.monitoring.errors.bulk-resolve'), ['all' => 1])
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, '1 opgelost, 3 vereisen handmatige actie, 0 mislukt'));

        Http::assertSentCount(2);
        $this->assertTrue($fixed->fresh()->resolved);
        $this->assertFalse($a1->fresh()->resolved);
        $this->assertSame('Onbekende fout. Kijk in de logs.', $a1->fresh()->context['monitoring_fix']['diagnosis_nl']);
        $this->assertSame('Onbekende fout. Kijk in de logs.', $a2->fresh()->context['monitoring_fix']['diagnosis_nl']);
        $this->assertTrue($a2->fresh()->context['monitoring_fix']['ai']);
        $this->assertNotNull($b->fresh()->context['monitoring_fix']['diagnosis_nl']);

        // Tweede klik: het AI-resultaat komt uit de cache.
        $this->actingAs($this->superAdmin())
            ->post(route('admin.monitoring.errors.bulk-resolve'), ['all' => 1]);
        Http::assertSentCount(2);
    }

    #[Test]
    public function undefined_view_variable_gets_a_fixed_diagnosis_without_an_ai_call(): void
    {
        $this->configureAi(['category' => 'x', 'safe_action' => null, 'diagnosis_nl' => 'AI', 'proposed_fix_nl' => 'AI']);
        $log = $this->log('Undefined variable $emailTemplate (View: /var/www/html/resources/views/admin/email-templates/partials/formulier-preview.blade.php)');

        $this->actingAs($this->superAdmin())
            ->post(route('admin.monitoring.errors.bulk-resolve'), ['ids' => [$log->id]])
            ->assertRedirect();

        Http::assertNothingSent();
        $fix = $log->fresh()->context['monitoring_fix'];
        $this->assertFalse($log->fresh()->resolved);
        $this->assertSame('view_undefined_variable', $fix['category']);
        $this->assertFalse($fix['ai']);
        $this->assertStringContainsString('$emailTemplate', $fix['diagnosis_nl']);
        $this->assertStringContainsString('resources/views/admin/email-templates/partials/formulier-preview.blade.php', $fix['diagnosis_nl']);
        $this->assertStringNotContainsString('/var/www/html', $fix['diagnosis_nl']);
        $this->assertStringContainsString('controller', $fix['proposed_fix_nl']);
    }

    #[Test]
    public function whitelisted_action_runs_but_unverifiable_action_leaves_the_row_open(): void
    {
        $this->configureAi(['category' => 'stale_cache', 'safe_action' => 'cache_clear', 'diagnosis_nl' => 'Verouderde cache.', 'proposed_fix_nl' => 'Cache legen.']);
        $log = $this->log('Verouderde configuratie in cache');

        Artisan::shouldReceive('call')->once()->with('cache:clear')->andReturn(0);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.monitoring.errors.bulk-resolve'), ['ids' => [$log->id]])
            ->assertRedirect();

        $fix = $log->fresh()->context['monitoring_fix'];
        $this->assertFalse($log->fresh()->resolved);
        $this->assertSame('cache_clear', $fix['action']);
        $this->assertSame('handmatig', $fix['status']);
        $this->assertStringContainsString('cache:clear uitgevoerd', $fix['outcome']);
    }

    #[Test]
    public function non_whitelisted_ai_action_is_never_executed(): void
    {
        $this->configureAi(['category' => 'schema', 'safe_action' => 'migrate:fresh --force', 'diagnosis_nl' => 'Schema stuk.', 'proposed_fix_nl' => 'Migreer.']);
        $log = $this->log('Schema kapot');

        Artisan::shouldReceive('call')->never();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.monitoring.errors.bulk-resolve'), ['ids' => [$log->id]])
            ->assertRedirect();

        $fix = $log->fresh()->context['monitoring_fix'];
        $this->assertFalse($log->fresh()->resolved);
        $this->assertNull($fix['action']);
        $this->assertStringContainsString('staat niet op de whitelist', $fix['outcome']);
        $this->assertSame('Migreer.', $fix['proposed_fix_nl']);
        $this->assertNotContains('migrate:fresh --force', MonitoringSafeAction::values());
    }

    #[Test]
    public function financial_errors_and_jobs_are_never_retried_but_safe_failed_jobs_are(): void
    {
        $this->configureAi(['category' => 'job_failed', 'safe_action' => 'retry_failed_job', 'diagnosis_nl' => 'Job mislukt.', 'proposed_fix_nl' => 'Opnieuw proberen.']);

        $financialUuid = (string) Str::uuid();
        $safeUuid = (string) Str::uuid();
        $this->insertFailedJob($financialUuid, 'App\\Jobs\\ProcessPlatformBillingJob');
        $this->insertFailedJob($safeUuid, 'App\\Jobs\\SyncKnvTaxiMembersJob');

        $financial = $this->log('ProcessPlatformBillingJob is mislukt na timeout');
        $safe = $this->log('SyncKnvTaxiMembersJob is mislukt na timeout');

        $this->actingAs($this->superAdmin())
            ->post(route('admin.monitoring.errors.bulk-resolve'), ['ids' => [$financial->id, $safe->id]])
            ->assertRedirect();

        $this->assertDatabaseHas('failed_jobs', ['uuid' => $financialUuid]);
        $this->assertFalse($financial->fresh()->resolved);
        $this->assertStringContainsString('betalingen', $financial->fresh()->context['monitoring_fix']['outcome']);

        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $safeUuid]);
        $this->assertTrue($safe->fresh()->resolved);
        $this->assertSame('retry_failed_job', $safe->fresh()->context['monitoring_fix']['action']);
    }

    #[Test]
    public function non_super_admin_gets_403_and_sees_no_controls(): void
    {
        $company = Company::query()->create(['name' => 'Bulk Tenant', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company-admin');
        $log = $this->log('Zichtbare fout');

        $this->actingAs($user)
            ->get(route('admin.monitoring.index', ['sectie' => 'fouten']))
            ->assertForbidden()
            ->assertDontSee('Geselecteerde fouten oplossen', false);

        $this->actingAs($user)
            ->post(route('admin.monitoring.errors.bulk-resolve'), ['ids' => [$log->id]])
            ->assertForbidden();

        $this->assertFalse($log->fresh()->resolved);
    }

    #[Test]
    public function fouten_table_has_select_all_header_row_checkboxes_and_both_buttons(): void
    {
        $open = $this->log('Open fout');
        $done = $this->log('Opgeloste fout');
        $done->forceFill(['resolved' => true, 'resolved_at' => now()])->save();

        $html = $this->actingAs($this->superAdmin())
            ->get(route('admin.monitoring.index', ['sectie' => 'fouten']))
            ->assertOk()
            ->assertSee('Geselecteerde fouten oplossen', false)
            ->assertSee('Alle onopgeloste fouten oplossen', false)
            ->assertSee('0 geselecteerd', false)
            ->assertSee('AI-diagnose: niet geconfigureerd', false)
            ->assertSee('data-monitoring-bulk-loader', false)
            ->assertSee('Fouten worden opgelost…', false)
            ->assertSee('Alle fouten worden opgelost…', false)
            ->assertSee('Bezig met oplossen van', false)
            ->assertSee('id="monitoring-errors-loading-status"', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/<th class="admin-table__check-col text-center"[^>]*>\s*<label[^>]*>\s*<input type="checkbox" class="kt-checkbox" id="monitoring-errors-select-all" aria-label="Alles selecteren">/', $html);
        $this->assertMatchesRegularExpression('/<th[^>]*check-col[^>]*>.*?<th data-label="Tijd">/s', $html);
        $this->assertSame(2, substr_count($html, 'class="kt-checkbox monitoring-error-check"'));
        $this->assertMatchesRegularExpression('/value="'.$open->id.'"\s+form="monitoring-errors-selected-form"\s+aria-label="Fout selecteren"\s*>/', $html);
        $this->assertMatchesRegularExpression('/value="'.$done->id.'"\s+form="monitoring-errors-selected-form"\s+aria-label="Fout selecteren"\s+disabled/', $html);
    }

    #[Test]
    public function validation_requires_a_selection_and_caps_the_number_of_ids(): void
    {
        $admin = $this->superAdmin();
        $log = $this->log('Fout');

        $this->actingAs($admin)
            ->post(route('admin.monitoring.errors.bulk-resolve'), [])
            ->assertSessionHas('error', 'Selecteer eerst minstens één fout.');

        $this->actingAs($admin)
            ->post(route('admin.monitoring.errors.bulk-resolve'), ['ids' => [999999]])
            ->assertSessionHas('error', 'Een geselecteerde fout bestaat niet meer.');

        $this->actingAs($admin)
            ->post(route('admin.monitoring.errors.bulk-resolve'), ['ids' => range(1, 201)])
            ->assertSessionHas('error', 'Selecteer maximaal 200 fouten tegelijk.');

        $this->assertArrayNotHasKey('monitoring_fix', $log->fresh()->context ?? []);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $answer
     */
    private function configureAi(array $answer): void
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode($answer)]]],
        ])]);
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
}
