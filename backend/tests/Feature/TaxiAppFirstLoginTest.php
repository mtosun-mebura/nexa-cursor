<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CustomerLoginCode;
use App\Models\TenantCustomerEmail;
use App\Models\User;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Services\TaxiAppFirstLoginService;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Services\UserRoleAssignmentService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxiAppFirstLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'contractant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'contractouder', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        RateLimiter::clear('taxi-app-code-ip:127.0.0.1');
        Mail::fake();

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        app(TaxiContractvervoerSchemaService::class)->ensureTablesExist('module_taxi');

        $hasLoginCodeRoute = false;
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === 'api/taxi/v1/driver/login-code/request') {
                $hasLoginCodeRoute = true;
                break;
            }
        }
        if (! $hasLoginCodeRoute) {
            Route::middleware('api')
                ->prefix('api/taxi')
                ->group(app_path('Modules/NexaTaxi/Routes/api-public.php'));
        }
    }

    #[Test]
    public function unknown_email_gets_an_error_on_driver_code_request(): void
    {
        $this->postJson('/api/taxi/v1/driver/login-code/request', [
            'email' => 'onbekend@example.com',
        ])
            ->assertStatus(422)
            ->assertJson(['message' => 'Dit e-mailadres is niet bekend.']);
    }

    #[Test]
    public function chauffeur_cannot_request_a_contract_code(): void
    {
        $driver = $this->makePendingAppUser('chauffeur', 'chauffeur@example.com');

        $this->postJson('/api/taxi/v1/contract/login-code/request', [
            'email' => $driver->email,
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Dit e-mailadres heeft geen toegang tot het contractportaal.']);
    }

    #[Test]
    public function activated_account_must_use_password(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Login', 'is_active' => true]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'klaar@example.com',
            'password' => 'Geheim123!',
            'must_change_password' => false,
            'password_must_be_set' => false,
            'email_verified_at' => now(),
        ]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['chauffeur']);

        $this->postJson('/api/taxi/v1/driver/login-code/request', [
            'email' => $user->email,
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Dit account is al geactiveerd. Log in met je wachtwoord.']);
    }

    #[Test]
    public function pending_driver_login_requires_first_login_code(): void
    {
        $user = $this->makePendingAppUser('chauffeur', 'wacht@example.com');

        $this->postJson('/api/taxi/v1/driver/login', [
            'email' => $user->email,
            'password' => 'ietsfout',
        ])
            ->assertStatus(403)
            ->assertJson([
                'error' => 'first_login_required',
            ]);
    }

    #[Test]
    public function driver_can_request_code_set_password_and_login(): void
    {
        $user = $this->makePendingAppUser('chauffeur', 'nieuw.chauffeur@example.com');

        $this->postJson('/api/taxi/v1/driver/login-code/request', [
            'email' => $user->email,
        ])->assertOk();

        $this->assertDatabaseHas('customer_login_codes', [
            'user_id' => $user->id,
            'purpose' => CustomerLoginCode::PURPOSE_DRIVER,
        ]);

        CustomerLoginCode::query()->where('user_id', $user->id)->update([
            'consumed_at' => now(),
        ]);
        CustomerLoginCode::query()->create([
            'user_id' => $user->id,
            'purpose' => CustomerLoginCode::PURPOSE_DRIVER,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->postJson('/api/taxi/v1/driver/login-code/verify', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'NieuwWacht1',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);

        $user->refresh();
        $this->assertFalse((bool) $user->must_change_password);
        $this->assertFalse((bool) $user->password_must_be_set);
        $this->assertTrue(Hash::check('NieuwWacht1', $user->password));

        $this->postJson('/api/taxi/v1/driver/login', [
            'email' => $user->email,
            'password' => 'NieuwWacht1',
        ])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    #[Test]
    public function expired_code_is_rejected(): void
    {
        $user = $this->makePendingAppUser('chauffeur', 'verlopen@example.com');
        CustomerLoginCode::query()->create([
            'user_id' => $user->id,
            'purpose' => CustomerLoginCode::PURPOSE_DRIVER,
            'code_hash' => Hash::make('654321'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson('/api/taxi/v1/driver/login-code/verify', [
            'email' => $user->email,
            'code' => '654321',
            'password' => 'NieuwWacht1',
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Deze code is onjuist of niet meer geldig. Vraag een nieuwe code aan.']);
    }

    #[Test]
    public function contractant_can_request_code_and_activate(): void
    {
        $user = $this->makePendingAppUser('contractant', 'contractant@example.com');
        $this->ensureContractCustomer((int) $user->company_id);

        CustomerLoginCode::query()->create([
            'user_id' => $user->id,
            'purpose' => CustomerLoginCode::PURPOSE_CONTRACT,
            'code_hash' => Hash::make('111222'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->postJson('/api/taxi/v1/contract/login-code/verify', [
            'email' => $user->email,
            'code' => '111222',
            'password' => 'NieuwWacht1',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    #[Test]
    public function creating_a_chauffeur_sends_welcome_without_password(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Welkom', 'is_active' => true]);
        User::factory()->create(['company_id' => $company->id]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->post(route('admin.users.store'), [
                'first_name' => 'Nieuwe',
                'last_name' => 'Chauffeur',
                'email' => 'nieuwe.chauffeur@example.com',
                'company_id' => $company->id,
                'roles' => ['chauffeur'],
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'nieuwe.chauffeur@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue((bool) $created->must_change_password);
        $this->assertTrue((bool) $created->password_must_be_set);
        $this->assertNull($created->email_verified_at);
        $this->assertFalse(Hash::check('Password1', $created->password));
        $this->assertDatabaseHas('tenant_customer_emails', [
            'recipient_email' => 'nieuwe.chauffeur@example.com',
            'type' => TenantCustomerEmail::TYPE_WELCOME,
        ]);
    }

    #[Test]
    public function creating_a_contractouder_sends_welcome_without_password(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Contract', 'is_active' => true]);
        User::factory()->create(['company_id' => $company->id]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->post(route('admin.users.store'), [
                'first_name' => 'Nieuwe',
                'last_name' => 'Ouder',
                'email' => 'nieuwe.ouder@example.com',
                'company_id' => $company->id,
                'roles' => ['contractouder'],
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'nieuwe.ouder@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue((bool) $created->password_must_be_set);
        $this->assertDatabaseHas('tenant_customer_emails', [
            'recipient_email' => 'nieuwe.ouder@example.com',
            'type' => TenantCustomerEmail::TYPE_WELCOME,
        ]);
    }

    private function makePendingAppUser(string $role, string $email): User
    {
        $company = Company::query()->create(['name' => 'Taxi App '.$role, 'is_active' => true]);
        $firstLogin = app(TaxiAppFirstLoginService::class);
        $user = User::factory()->create(array_merge([
            'company_id' => $company->id,
            'email' => $email,
            'password' => $firstLogin->unusablePasswordHash(),
            'is_active' => true,
        ], $firstLogin->provisionFlags()));
        app(UserRoleAssignmentService::class)->syncWebRoles($user, [$role]);

        return $user->fresh();
    }

    private function ensureContractCustomer(int $companyId): void
    {
        $conn = 'module_taxi';
        app(TaxiContractvervoerSchemaService::class)->ensureTablesExist($conn);
        TransportCustomer::on($conn)->create([
            'company_id' => $companyId,
            'name' => 'OBS Test',
            'active' => true,
        ]);
    }
}
