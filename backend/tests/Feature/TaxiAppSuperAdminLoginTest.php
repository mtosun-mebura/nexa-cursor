<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxiAppSuperAdminLoginTest extends TestCase
{
    private const DENIED = 'Dit account heeft geen toegang tot de chauffeur- of contract-app.';

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'klant', 'guard_name' => 'web']);
        Mail::fake();
        RateLimiter::clear('taxi-app-code-ip:127.0.0.1');

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $hasLoginRoute = false;
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === 'api/taxi/v1/app/login' && in_array('POST', $route->methods(), true)) {
                $hasLoginRoute = true;
                break;
            }
        }
        if (! $hasLoginRoute) {
            Route::middleware('api')
                ->prefix('api/taxi')
                ->group(app_path('Modules/NexaTaxi/Routes/api-public.php'));
        }
    }

    #[Test]
    public function super_admin_password_login_on_the_driver_contract_app_returns_a_driver_token(): void
    {
        $admin = $this->superAdmin();

        $this->postJson('/api/taxi/v1/app/login', [
            'email' => $admin->email,
            'password' => 'fout',
        ])->assertStatus(401);

        $this->postJson('/api/taxi/v1/app/login', [
            'email' => $admin->email,
            'password' => 'Geheim123!',
        ])
            ->assertOk()
            ->assertJsonPath('user.is_super_admin', true)
            ->assertJsonPath('capabilities.default_screen', 'driver')
            ->assertJsonPath('capabilities.modes.chauffeur', true)
            ->assertJsonPath('capabilities.screens.0.key', 'driver')
            ->assertJsonStructure(['tokens' => ['driver' => ['token']]]);

        $this->assertFalse($admin->fresh()->hasRole('chauffeur'));
    }

    #[Test]
    public function super_admin_can_request_a_login_code_for_the_driver_contract_app(): void
    {
        $admin = $this->superAdmin();

        $this->postJson('/api/taxi/v1/app/login-code/request', [
            'email' => $admin->email,
        ])->assertOk();
    }

    #[Test]
    public function account_without_app_roles_still_gets_the_dutch_denial(): void
    {
        $customer = $this->customer();

        $this->postJson('/api/taxi/v1/app/login', [
            'email' => $customer->email,
            'password' => 'Geheim123!',
        ])
            ->assertStatus(403)
            ->assertJsonPath('message', self::DENIED);

        $this->postJson('/api/taxi/v1/app/login-code/request', [
            'email' => $customer->email,
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Dit e-mailadres heeft geen toegang tot het contractportaal.']);
    }

    #[Test]
    public function super_admin_is_not_rejected_on_driver_contract_or_customer_login(): void
    {
        $admin = $this->superAdmin();

        $this->postJson('/api/taxi/v1/driver/login', [
            'email' => $admin->email,
            'password' => 'Geheim123!',
        ])
            ->assertOk()
            ->assertJsonStructure(['token']);

        $this->postJson('/api/taxi/v1/contract/login', [
            'email' => $admin->email,
            'password' => 'Geheim123!',
        ])
            ->assertOk()
            ->assertJsonStructure(['token']);

        $this->postJson('/api/taxi/v1/customer/login', [
            'email' => $admin->email,
            'password' => 'Geheim123!',
        ])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    #[Test]
    public function customer_is_still_rejected_from_the_driver_app(): void
    {
        $customer = $this->customer();

        $this->postJson('/api/taxi/v1/driver/login', [
            'email' => $customer->email,
            'password' => 'Geheim123!',
        ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Dit account heeft geen chauffeur-toegang.');
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create([
            'email' => 'super@example.com',
            'company_id' => null,
            'password' => 'Geheim123!',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['super-admin']);

        return $user->fresh();
    }

    private function customer(): User
    {
        $company = Company::query()->create([
            'name' => 'Klant Taxi',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'email' => 'klant@example.com',
            'company_id' => $company->id,
            'password' => 'Geheim123!',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['klant']);

        return $user->fresh();
    }
}
