<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PaymentProvider;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Services\PaymentProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSettingsMollieTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function settings_page_shows_mollie_section_for_selected_tenant(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Mollie BV', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Mollie (tenant)', false)
            ->assertSee('Betalen in chauffeur-app', false)
            ->assertSee('Het geld komt op de Mollie-rekening', false)
            ->assertSee(route('admin.settings.mollie.update'), false);
    }

    #[Test]
    public function super_admin_can_save_tenant_mollie_for_driver_payments(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Mollie Save BV', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $company->id])
            ->post(route('admin.settings.mollie.update'), [
                'mollie_api_key' => 'test_1234567890abcdef',
                'mollie_is_active' => '1',
                'mollie_test_mode' => '1',
                'mollie_driver_payments' => '1',
                'mollie_booking_payments' => '0',
                'mollie_webhook_url' => 'https://example.com/api/taxi/webhooks/mollie',
            ])
            ->assertRedirect();

        $providers = app(PaymentProviderService::class);
        $this->assertTrue($providers->isMollieConfiguredForCompany((int) $company->id));
        $this->assertSame('test_1234567890abcdef', $providers->mollieApiKeyForCompany((int) $company->id));
        $this->assertSame(1, PaymentProvider::query()->where('company_id', $company->id)->where('provider_type', 'mollie')->count());

        $dispatch = app(TaxiDispatchSettingsService::class);
        $options = $dispatch->paymentOptionsForTenant((int) $company->id);
        $this->assertTrue($options['mollie_configured']);
        $this->assertTrue($options['driver']);
        $this->assertFalse($options['booking']);
    }

    #[Test]
    public function mollie_settings_require_a_selected_tenant(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->post(route('admin.settings.mollie.update'), [
                'mollie_api_key' => 'test_1234567890abcdef',
                'mollie_is_active' => '1',
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('settings_tenant_save_notice');
    }

    #[Test]
    public function settings_success_flash_is_rendered_in_the_admin_header(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->withSession(['success' => 'Test email succesvol verzonden naar mehmet@tosun.nl!'])
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('id="admin-header-flash"', false)
            ->assertSee('admin-header-toast', false)
            ->assertSee('Test email succesvol verzonden naar mehmet@tosun.nl!')
            ->assertDontSee('id="test-email-message"', false);
    }

    #[Test]
    public function settings_tenant_save_notice_is_rendered_in_the_admin_header(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $notice = 'Selecteer eerst een tenant (bedrijf) in de zijbalk om per-tenant configuraties te bewerken.';

        $this->actingAs($admin, 'web')
            ->withSession(['settings_tenant_save_notice' => $notice])
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('admin-header-toast', false)
            ->assertSee('kt-alert-warning', false)
            ->assertSee($notice)
            ->assertDontSee('border-orange-700 bg-orange-950', false);
    }
}
