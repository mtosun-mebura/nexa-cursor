<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Api\AppBootstrapController;
use App\Modules\NexaTaxi\Services\TaxiAppCapabilitiesService;
use App\Modules\NexaTaxi\Services\TaxiAppFirstLoginService;
use App\Modules\NexaTaxi\Services\TaxiContractPortalAccessService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Services\MarketplaceCompanyRegistrationService;
use App\Services\ModuleDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaxiAppLoginSuperAdminFlagTest extends TestCase
{
    use RefreshDatabase;

    private function login(User $user, string $password): array
    {
        $request = Request::create('/api/taxi/v1/app/login', 'POST', [
            'email' => $user->email,
            'password' => $password,
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = app(AppBootstrapController::class)->login(
            $request,
            app(TaxiAppCapabilitiesService::class),
            app(TaxiDriverEligibilityService::class),
            app(TaxiContractPortalAccessService::class),
            app(ModuleDatabaseService::class),
            app(TaxiAppFirstLoginService::class),
        );

        $this->assertSame(200, $response->getStatusCode());

        return $response->getData(true);
    }

    private function marketplaceDriver(): User
    {
        Role::firstOrCreate(['name' => 'marketplace', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);
        $company = Company::query()->create([
            'name' => 'Market Taxi',
            'is_active' => true,
            'package_key' => MarketplaceCompanyRegistrationService::PACKAGE_KEY,
        ]);
        $company->modules()->attach($taxi->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'password' => 'Geheim123!',
            'email_verified_at' => now(),
        ]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('marketplace');

        return $user;
    }

    #[Test]
    public function login_payload_flags_regular_driver_as_not_super_admin(): void
    {
        $user = $this->marketplaceDriver();

        $data = $this->login($user, 'Geheim123!');

        $this->assertFalse($data['user']['is_super_admin']);
    }

    #[Test]
    public function login_payload_flags_super_admin(): void
    {
        $user = $this->marketplaceDriver();
        $user->assignRole('super-admin');

        $data = $this->login($user, 'Geheim123!');

        $this->assertTrue($data['user']['is_super_admin']);
    }
}
