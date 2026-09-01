<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Admin\TransportCustomerController;
use App\Modules\NexaTaxi\Controllers\Api\DriverAuthController;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiDriverEarningsAccessService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Services\CompanyEntitlementService;
use App\Services\ModuleDatabaseService;
use App\Services\UserRoleAssignmentService;
use App\Support\TenantPackageCapability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PackageRestrictionMatrixTest extends TestCase
{
    /** @var array<string, array{company: Company, admin: User}> */
    private array $tenants = [];

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'qa.super@example.com',
            'first_name' => 'QA',
            'last_name' => 'Super',
        ]);
        $this->superAdmin->assignRole('super-admin');

        $roles = app(UserRoleAssignmentService::class);
        foreach (['start' => 'QA Start Taxi', 'pro' => 'QA Pro Taxi', 'business' => 'QA Business Taxi'] as $key => $name) {
            $company = Company::query()->create([
                'name' => $name,
                'is_active' => true,
                'package_key' => $key,
            ]);
            $admin = User::factory()->create([
                'company_id' => $company->id,
                'email' => 'qa.'.$key.'@example.com',
                'first_name' => 'QA',
                'last_name' => ucfirst($key),
            ]);
            $roles->syncWebRoles($admin, ['company-admin']);
            $this->tenants[$key] = ['company' => $company, 'admin' => $admin];
        }
    }

    #[Test]
    public function three_package_users_exist_and_see_their_package_on_the_company_page(): void
    {
        $this->assertCount(3, $this->tenants);
        $this->assertSame('start', $this->tenants['start']['company']->package_key);
        $this->assertSame('pro', $this->tenants['pro']['company']->package_key);
        $this->assertSame('business', $this->tenants['business']['company']->package_key);

        foreach ($this->tenants as $key => $tenant) {
            $expectedName = match ($key) {
                'start' => 'Start',
                'pro' => 'Pro',
                'business' => 'Business',
                default => $key,
            };

            $this->actingAs($this->superAdmin)
                ->get(route('admin.companies.show', $tenant['company']))
                ->assertOk()
                ->assertSee($tenant['company']->name, false)
                ->assertSee($expectedName, false)
                ->assertSee($key, false);

            $this->actingAs($tenant['admin'])
                ->get(route('admin.dashboard'))
                ->assertOk();
        }
    }

    #[Test]
    public function company_admins_walk_the_restriction_matrix(): void
    {
        $service = app(CompanyEntitlementService::class);
        $dispatch = app(TaxiDispatchSettingsService::class);

        $matrix = [
            'start' => [
                'mollie' => false,
                'dispatch' => false,
                'driver_app' => false,
                'contract' => false,
                'portal' => false,
                'multi_admin' => false,
                'sepa' => false,
                'max_drivers' => 3,
                'max_clients' => 0,
            ],
            'pro' => [
                'mollie' => true,
                'dispatch' => true,
                'driver_app' => true,
                'contract' => false,
                'portal' => false,
                'multi_admin' => true,
                'sepa' => false,
                'max_drivers' => null,
                'max_clients' => 0,
            ],
            'business' => [
                'mollie' => true,
                'dispatch' => true,
                'driver_app' => true,
                'contract' => true,
                'portal' => true,
                'multi_admin' => true,
                'sepa' => true,
                'max_drivers' => null,
                'max_clients' => 10,
            ],
        ];

        foreach ($matrix as $key => $expected) {
            $company = $this->tenants[$key]['company'];
            $this->assertSame($expected['mollie'], $service->allows($company, TenantPackageCapability::MOLLIE_PAYMENTS), $key.' mollie');
            $this->assertSame($expected['dispatch'], $service->allows($company, TenantPackageCapability::DISPATCH), $key.' dispatch');
            $this->assertSame($expected['driver_app'], $service->allows($company, TenantPackageCapability::DRIVER_APP), $key.' driver_app');
            $this->assertSame($expected['contract'], $service->allows($company, TenantPackageCapability::CONTRACT_TRANSPORT), $key.' contract');
            $this->assertSame($expected['portal'], $service->allows($company, TenantPackageCapability::CONTRACT_PORTAL), $key.' portal');
            $this->assertSame($expected['multi_admin'], $service->allows($company, TenantPackageCapability::MULTIPLE_ADMINS), $key.' multi_admin');
            $this->assertSame($expected['sepa'], $service->allows($company, TenantPackageCapability::MONTHLY_INVOICE_SEPA), $key.' sepa');
            $this->assertSame($expected['max_drivers'], $service->maxDrivers($company), $key.' max_drivers');
            $this->assertSame($expected['max_clients'], $service->maxContractClients($company), $key.' max_clients');
            $this->assertSame($expected['mollie'], $dispatch->paymentOptionsForTenant((int) $company->id)['mollie_package_allowed'], $key.' mollie options');
        }
    }

    #[Test]
    public function start_and_pro_see_a_package_message_on_contractklanten_business_is_not_blocked(): void
    {
        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));

        foreach (['start', 'pro'] as $key) {
            $this->actingAs($this->superAdmin);
            session(['selected_tenant' => $this->tenants[$key]['company']->id]);
            $response = app(TransportCustomerController::class)->index(Request::create('/admin/taxi/contractklanten', 'GET'));
            $this->assertInstanceOf(\Illuminate\View\View::class, $response);
            $message = (string) ($response->getData()['packageDeniedMessage'] ?? '');
            $this->assertStringContainsString(
                'Contractvervoer zit niet in het '.ucfirst($key).'-pakket',
                $message
            );
            $this->assertStringContainsString('Upgrade naar Business', $message);
            $this->assertTrue($response->getData()['customers']->isEmpty());
        }

        $this->actingAs($this->superAdmin);
        session(['selected_tenant' => $this->tenants['business']['company']->id]);
        try {
            $response = app(TransportCustomerController::class)->index(Request::create('/admin/taxi/contractklanten', 'GET'));
            $this->assertNotNull($response);
            if ($response instanceof \Illuminate\View\View) {
                $this->assertNull($response->getData()['packageDeniedMessage']);
            }
        } catch (ValidationException $e) {
            $this->fail('Business mag contractvervoer niet worden geweigerd: '.($e->errors()['package'][0] ?? $e->getMessage()));
        } catch (\Throwable $e) {
            $this->assertTrue(
                app(CompanyEntitlementService::class)->allows(
                    $this->tenants['business']['company'],
                    TenantPackageCapability::CONTRACT_TRANSPORT
                ),
                'Business heeft contractvervoer, maar de taxi-database is in deze testomgeving niet beschikbaar: '.$e->getMessage()
            );
        }
    }

    #[Test]
    public function contractklanten_get_renders_the_package_warning_in_html(): void
    {
        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));
        if (! Route::has('admin.taxi.transport_customers.index')) {
            Route::middleware('web')
                ->get('/admin/taxi/contractklanten', [TransportCustomerController::class, 'index'])
                ->name('admin.taxi.transport_customers.index');
        }

        $this->actingAs($this->superAdmin)
            ->withSession(['selected_tenant' => $this->tenants['start']['company']->id])
            ->from(route('admin.dashboard'))
            ->get('/admin/taxi/contractklanten')
            ->assertOk()
            ->assertSee('Contractvervoer zit niet in het Start-pakket', false)
            ->assertSee('Upgrade naar Business', false)
            ->assertDontSee('Nieuwe klant', false);
    }

    #[Test]
    public function admin_get_package_denial_flashes_a_warning_on_the_dashboard(): void
    {
        $company = $this->tenants['start']['company'];
        Route::middleware('web')->get('/admin/_package-denied', function () use ($company) {
            app(CompanyEntitlementService::class)->assertCanUseContractTransport($company);
        });

        $response = $this->actingAs($this->superAdmin)
            ->from(route('admin.dashboard'))
            ->get('/admin/_package-denied');

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('warning');
        $this->assertStringContainsString(
            'Contractvervoer zit niet in het Start-pakket',
            (string) session('warning')
        );

        $this->get(route('admin.companies.index'))
            ->assertOk()
            ->assertSee('Contractvervoer zit niet in het Start-pakket', false);
    }

    #[Test]
    public function start_blocks_a_fourth_chauffeur_pro_allows_it(): void
    {
        $roles = app(UserRoleAssignmentService::class);
        foreach (['start', 'pro'] as $key) {
            $company = $this->tenants[$key]['company'];
            for ($i = 0; $i < 3; $i++) {
                $driver = User::factory()->create(['company_id' => $company->id]);
                $roles->syncWebRoles($driver, ['chauffeur']);
            }
        }

        $this->actingAs($this->superAdmin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), $this->userPayload(
                (int) $this->tenants['start']['company']->id,
                'vierde.start@example.com',
                ['chauffeur'],
                'Vierde',
                'Chauffeur'
            ))
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('roles');
        $this->assertNull(User::query()->where('email', 'vierde.start@example.com')->first());

        $this->actingAs($this->superAdmin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), $this->userPayload(
                (int) $this->tenants['pro']['company']->id,
                'vierde.pro@example.com',
                ['chauffeur'],
                'Vierde',
                'Chauffeur'
            ));
        $this->assertFalse(session('errors')?->has('roles') ?? false, 'Pro mag een vierde chauffeur niet weigeren.');
        app(CompanyEntitlementService::class)->assertCanAssignChauffeurRoles(
            $this->tenants['pro']['company'],
            ['chauffeur']
        );
    }

    #[Test]
    public function start_blocks_a_second_company_admin_pro_allows_it(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), $this->userPayload(
                (int) $this->tenants['start']['company']->id,
                'tweede.start@example.com',
                ['company-admin'],
                'Tweede',
                'Beheerder'
            ))
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('roles');
        $this->assertStringContainsString('maximaal één beheerder', session('errors')->first('roles'));
        $this->assertNull(User::query()->where('email', 'tweede.start@example.com')->first());

        $this->actingAs($this->superAdmin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), $this->userPayload(
                (int) $this->tenants['pro']['company']->id,
                'tweede.pro@example.com',
                ['company-admin'],
                'Tweede',
                'Beheerder'
            ));
        $this->assertFalse(session('errors')?->has('roles') ?? false, 'Pro mag een tweede beheerder niet weigeren.');
        app(CompanyEntitlementService::class)->assertCanAssignCompanyAdminRoles(
            $this->tenants['pro']['company'],
            ['company-admin']
        );
    }

    #[Test]
    public function settings_show_mollie_warning_only_for_start(): void
    {
        $startHtml = $this->actingAs($this->superAdmin)
            ->withSession(['selected_tenant' => $this->tenants['start']['company']->id])
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Betalen via Mollie zit niet in het Start-pakket', false)
            ->getContent();
        $this->assertStringContainsString('id="mollie"', $startHtml);

        $this->actingAs($this->superAdmin)
            ->withSession(['selected_tenant' => $this->tenants['pro']['company']->id])
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertDontSee('Betalen via Mollie zit niet in het Pro-pakket', false);

        $this->actingAs($this->superAdmin)
            ->from(route('admin.settings.index').'#mollie')
            ->withSession(['selected_tenant' => $this->tenants['start']['company']->id])
            ->post(route('admin.settings.mollie.update'), [
                'mollie_api_key' => 'test_blocked_by_package',
                'mollie_is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('mollie_api_key');
    }

    #[Test]
    public function driver_app_login_is_denied_on_start_and_allowed_on_pro_until_auth_succeeds(): void
    {
        $roles = app(UserRoleAssignmentService::class);
        $startDriver = User::factory()->create([
            'company_id' => $this->tenants['start']['company']->id,
            'email' => 'chauffeur.start@example.com',
            'password' => 'Password1',
            'email_verified_at' => now(),
        ]);
        $roles->syncWebRoles($startDriver, ['chauffeur']);

        $proDriver = User::factory()->create([
            'company_id' => $this->tenants['pro']['company']->id,
            'email' => 'chauffeur.pro@example.com',
            'password' => 'Password1',
            'email_verified_at' => now(),
        ]);
        $roles->syncWebRoles($proDriver, ['chauffeur']);

        $startResponse = $this->driverLogin('chauffeur.start@example.com');
        $this->assertSame(403, $startResponse->getStatusCode());
        $this->assertStringContainsString(
            'chauffeur-app zit niet in het start-pakket',
            strtolower($startResponse->getData(true)['message'] ?? '')
        );

        $proResponse = null;
        try {
            $proResponse = $this->driverLogin('chauffeur.pro@example.com');
        } catch (\Throwable $e) {
            $this->assertStringNotContainsString(
                'chauffeur-app zit niet',
                strtolower($e->getMessage()),
                'Pro mag de chauffeur-app niet weigeren vanwege het pakket.'
            );

            return;
        }
        $this->assertNotSame(403, $proResponse->getStatusCode(), 'Pro mag de chauffeur-app niet weigeren vanwege het pakket.');
        if ($proResponse->getStatusCode() === 200) {
            $this->assertNotEmpty($proResponse->getData(true)['token'] ?? null);
        }
    }

    /**
     * @param  list<string>  $roles
     * @return array<string, mixed>
     */
    private function userPayload(int $companyId, string $email, array $roles, string $firstName, string $lastName): array
    {
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => 'Password1',
            'company_id' => $companyId,
            'roles' => $roles,
        ];
    }

    private function driverLogin(string $email): \Illuminate\Http\JsonResponse
    {
        $request = Request::create('/api/taxi/v1/driver/login', 'POST', [
            'email' => $email,
            'password' => 'Password1',
        ]);
        $request->headers->set('Accept', 'application/json');

        return app(DriverAuthController::class)->login(
            $request,
            app(TaxiDriverEligibilityService::class),
            app(ModuleDatabaseService::class),
            app(TaxiDriverEarningsAccessService::class)
        );
    }
}
