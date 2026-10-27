<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnBConfigOtherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        Http::fake([
            'www.php.net/*' => Http::response([
                '8.5.1' => ['date' => '2026-01-15'],
                '8.4.10' => ['date' => '2026-01-10'],
            ]),
            'hub.docker.com/*' => Http::response(['name' => '8.5-cli'], 200),
            'repo.packagist.org/*' => Http::response(['packages' => []]),
        ]);

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->default('offered');
            $table->string('source', 32)->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->dateTime('pickup_at');
            $table->dateTime('pickup_proposal_at')->nullable();
            $table->string('pickup_proposal_status', 32)->nullable();
            $table->text('pickup_proposal_customer_remark')->nullable();
            $table->timestamp('pickup_proposal_sent_at')->nullable();
            $table->timestamp('pickup_proposal_responded_at')->nullable();
            $table->string('pickup_proposal_whatsapp_wamid', 191)->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->text('customer_note')->nullable();
            $table->json('booking_payload')->nullable();
            $table->timestamps();
        });
    }

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    private function visibleHtml(string $html): string
    {
        $html = (string) preg_replace('/<!--.*?-->/s', '', $html);

        return (string) preg_replace('/<script\b[^>]*>.*?<\/script>/s', '', $html);
    }

    private function tenantSession(array $extra = []): array
    {
        $tenant = Company::query()->create(['name' => 'Locale Config Taxi', 'is_active' => true]);

        return ['selected_tenant' => $tenant->id] + $extra;
    }

    #[Test]
    public function general_settings_page_is_english_in_en_locale(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'en'])
            ->get(route('admin.settings.general.index'))
            ->assertOk()
            ->getContent();

        foreach (['General configuration', 'Current logo', 'Current favicon', 'Logo size (px)', 'Admin footer text', 'Show AI assistant', 'Form success message', 'Click or drag & drop', 'Only SVG, PNG, JPG and GIF files are allowed.', 'Delete avatar', 'Checkmark'] as $english) {
            $this->assertStringContainsString($english, html_entity_decode($html), $english);
        }
        foreach (['Algemene configuraties', 'Huidige logo', 'Huidige favicon', 'Logo grootte (px)', 'Admin footertekst', 'AI-assistent tonen', 'Formulier succesbericht', 'Klik of Sleep', 'Alleen SVG, PNG, JPG en GIF bestanden zijn toegestaan.', 'Avatar verwijderen', 'Vinkje'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($html), $dutch);
        }
    }

    #[Test]
    public function general_settings_page_keeps_dutch_in_nl_locale(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession(['locale' => 'nl'])
            ->get(route('admin.settings.general.index'))
            ->assertOk()
            ->getContent();

        foreach (['Algemene configuraties', 'Huidige logo', 'Logo grootte (px)', 'Admin footertekst', 'Formulier succesbericht', 'Klik of Sleep &amp; Drop', 'Alleen SVG, PNG, JPG en GIF bestanden zijn toegestaan.', 'Vinkje', 'Logo, favicon &amp; avatar'] as $dutch) {
            $this->assertStringContainsString($dutch, $html, $dutch);
        }
    }

    #[Test]
    public function bank_account_page_and_validation_follow_the_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession(['locale' => 'en']);

        $html = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.settings.bank-account'))
            ->assertOk()
            ->getContent();

        foreach (['Bank account', 'Account number for payout of Nexa Suite rides', 'Current account', 'Not filled in yet', 'Required for payout.', 'Save', 'Back'] as $english) {
            $this->assertStringContainsString($english, $html, $english);
        }
        foreach (['Bankrekening', 'Huidige rekening', 'Nog niet ingevuld', 'Verplicht voor uitbetaling', 'Opslaan'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($html), $dutch);
        }

        $this->actingAs($admin)
            ->withSession($session)
            ->post(route('admin.settings.bank-account.update'), ['iban' => ''])
            ->assertSessionHasErrors(['iban' => 'Enter your IBAN for payout of rides.']);

        $nl = $this->actingAs($admin)
            ->withSession(['locale' => 'nl', 'selected_tenant' => $session['selected_tenant']])
            ->get(route('admin.settings.bank-account'))
            ->assertOk()
            ->getContent();
        foreach (['Bankrekening', 'Huidige rekening', 'Nog niet ingevuld', 'Verplicht voor uitbetaling', 'Opslaan'] as $dutch) {
            $this->assertStringContainsString($dutch, $nl, $dutch);
        }

        $this->actingAs($admin)
            ->withSession(['locale' => 'nl', 'selected_tenant' => $session['selected_tenant']])
            ->post(route('admin.settings.bank-account.update'), ['iban' => ''])
            ->assertSessionHasErrors(['iban' => 'Vul je IBAN in voor uitbetaling van ritten.']);
    }

    #[Test]
    public function upgrade_page_is_english_in_en_locale_and_dutch_in_nl_locale(): void
    {
        $admin = $this->superAdmin();

        $html = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.settings.upgrade.index'))
            ->assertOk()
            ->getContent();

        foreach (['Platform-wide', 'Installed stack', 'Update Laravel', 'Update PHP in Docker', 'Update PostgreSQL in Docker', 'Docker containers', 'Command on container', 'Restart containers', 'Rebuild images', 'Update other packages', 'Upgrade to latest versions', 'Upgrade history', 'No upgrades performed yet.', 'Fetching status…', 'Could not load Laravel status.', 'Select at least one container to restart.', 'Minor update'] as $english) {
            $this->assertStringContainsString($english, $html, $english);
        }
        foreach (['Platform-breed', 'Geïnstalleerde stack', 'Laravel bijwerken', 'PHP in Docker bijwerken', 'Docker-containers', 'Commando op container', 'Containers herstarten', 'Images opnieuw bouwen', 'Overige packages bijwerken', 'Upgrade naar nieuwste versies', 'Upgradegeschiedenis', 'Status ophalen…'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($html), $dutch);
        }

        $nl = $this->actingAs($admin)
            ->withSession(['locale' => 'nl'])
            ->get(route('admin.settings.upgrade.index'))
            ->assertOk()
            ->getContent();
        foreach (['Platform-breed', 'Geïnstalleerde stack', 'Laravel bijwerken', 'PHP in Docker bijwerken', 'Docker-containers', 'Commando op container', 'Containers herstarten', 'Images opnieuw bouwen', 'Upgradegeschiedenis', 'Status ophalen…', 'Nog geen upgrades uitgevoerd.'] as $dutch) {
            $this->assertStringContainsString($dutch, $nl, $dutch);
        }
    }

    #[Test]
    public function upgrade_messages_follow_the_locale(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.settings.upgrade.vps-ip.update'), ['coolify_vps_public_ip' => ''])
            ->assertSessionHasErrors(['coolify_vps_public_ip' => 'Enter the public VPS IP.']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->deleteJson(route('admin.settings.upgrade.history.destroy'), ['ids' => [999]])
            ->assertStatus(422)
            ->assertJsonPath('message', 'No rows deleted. Running upgrades are kept.');

        $this->actingAs($admin)
            ->withSession(['locale' => 'nl'])
            ->deleteJson(route('admin.settings.upgrade.history.destroy'), ['ids' => [999]])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Geen regels verwijderd. Lopende upgrades blijven staan.');
    }

    #[Test]
    public function whatsapp_proposal_mock_page_is_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession(['locale' => 'en']);

        $html = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.whatsapp-pickup-proposal-mock.index'))
            ->assertOk()
            ->getContent();

        foreach (['WhatsApp pickup proposal test', 'How this test works', 'Create test data', 'Proposals', 'Live · newest first', 'No proposals yet.', 'WhatsApp message', 'Delete selected proposals', 'Environment:'] as $english) {
            $this->assertStringContainsString($english, $html, $english);
        }
        foreach (['WhatsApp ophaalvoorstel test', 'Hoe deze test werkt', 'Testdata aanmaken', 'Voorstellen', 'nieuwste bovenaan', 'Nog geen voorstellen', 'WhatsApp-bericht', 'Omgeving:'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($html), $dutch);
        }

        $nl = $this->actingAs($admin)
            ->withSession(['locale' => 'nl', 'selected_tenant' => $session['selected_tenant']])
            ->get(route('admin.whatsapp-pickup-proposal-mock.index'))
            ->assertOk()
            ->getContent();
        foreach (['WhatsApp ophaalvoorstel test', 'Hoe deze test werkt', 'Testdata aanmaken', 'Voorstellen', 'Live · nieuwste bovenaan', 'Nog geen voorstellen.', 'WhatsApp-bericht', 'Omgeving:'] as $dutch) {
            $this->assertStringContainsString($dutch, $nl, $dutch);
        }
    }

    #[Test]
    public function whatsapp_proposal_mock_flash_and_errors_follow_the_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession(['locale' => 'en']);

        $this->actingAs($admin)
            ->withSession($session)
            ->post(route('admin.whatsapp-pickup-proposal-mock.clear'))
            ->assertSessionHas('success', 'No test data to clear.');

        $this->actingAs($admin)
            ->withSession(['locale' => 'nl'])
            ->post(route('admin.whatsapp-pickup-proposal-mock.clear'))
            ->assertSessionHas('success', 'Geen testdata om te wissen.');

        $staff = User::factory()->create();
        $this->actingAs($staff)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.whatsapp-pickup-proposal-mock.index'))
            ->assertRedirect(route('admin.login'));
    }
}
