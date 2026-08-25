<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Services\UserRoleAssignmentService;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyPackageEntitlementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function super_admin_sees_package_select_on_company_edit(): void
    {
        $company = Company::query()->create(['name' => 'Pakket Bedrijf', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.companies.edit', $company))
            ->assertOk()
            ->assertSee('name="package_key"', false)
            ->assertSee('Start (start)', false)
            ->assertSee('Pro (pro)', false)
            ->assertSee('name="package_addons['.\App\Support\TenantPackageAddon::EXTRA_CLIENTS.']"', false)
            ->assertSee('name="package_addons['.\App\Support\TenantPackageAddon::GPS_TRACKING.']"', false)
            ->assertSee('name="package_addons['.\App\Support\TenantPackageAddon::FLEET.']"', false);
    }

    #[Test]
    public function creating_a_fourth_chauffeur_on_start_package_is_blocked(): void
    {
        $company = Company::query()->create([
            'name' => 'Start Chauffeurs',
            'is_active' => true,
            'package_key' => 'start',
        ]);
        $roles = app(UserRoleAssignmentService::class);
        for ($i = 0; $i < 3; $i++) {
            $driver = User::factory()->create(['company_id' => $company->id]);
            $roles->syncWebRoles($driver, ['chauffeur']);
        }

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'first_name' => 'Vierde',
                'last_name' => 'Chauffeur',
                'email' => 'vierde.chauffeur@example.com',
                'password' => 'Password1',
                'company_id' => $company->id,
                'roles' => ['chauffeur'],
            ])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('roles');

        $this->assertStringContainsString(
            'maximaal 3 chauffeurs',
            session('errors')->first('roles')
        );
        $this->assertNull(User::query()->where('email', 'vierde.chauffeur@example.com')->first());
    }

    #[Test]
    public function start_package_disables_mollie_payment_options(): void
    {
        $company = Company::query()->create([
            'name' => 'Start Mollie',
            'is_active' => true,
            'package_key' => 'start',
        ]);

        \App\Models\PaymentProvider::create([
            'company_id' => $company->id,
            'name' => 'Mollie',
            'provider_type' => 'mollie',
            'is_active' => true,
            'config' => [
                'api_key' => Crypt::encryptString('test_1234567890abcdef'),
            ],
        ]);

        $dispatch = app(TaxiDispatchSettingsService::class);
        $dispatch->setPaymentBookingEnabled(true, (int) $company->id);
        $dispatch->setPaymentDriverEnabled(true, (int) $company->id);

        $options = $dispatch->paymentOptionsForTenant((int) $company->id);
        $this->assertFalse($options['mollie_package_allowed']);
        $this->assertFalse($options['mollie_configured']);
        $this->assertFalse($options['booking']);
        $this->assertFalse($options['driver']);
    }
}
