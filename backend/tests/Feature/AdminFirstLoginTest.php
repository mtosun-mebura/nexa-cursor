<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CustomerLoginCode;
use App\Models\User;
use App\Services\AdminFirstLoginCodeEmailTemplateService;
use App\Services\AdminFirstLoginService;
use App\Services\MarketplaceCompanyRegistrationService;
use App\Services\UserRoleAssignmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminFirstLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        app(AdminFirstLoginCodeEmailTemplateService::class)->ensureExists();
        Mail::fake();
        RateLimiter::clear('admin-first-login-ip:127.0.0.1');
    }

    #[Test]
    public function login_page_shows_first_login_panel(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Inloggen met e-mailcode', false)
            ->assertSee('Inlogcode aanvragen', false)
            ->assertSee('Terug naar inloggen', false)
            ->assertSee('Taxibedrijf inloggen of registreren', false)
            ->assertSee('(Marketplace)', false)
            ->assertSee('Taxibedrijf registreren', false)
            ->assertSee('eenmalige code aan', false);
    }

    #[Test]
    public function password_login_is_blocked_until_first_login_code_is_used(): void
    {
        $user = $this->makePendingAdmin('wacht@example.com');

        $response = $this->from(route('admin.login'))
            ->post(route('admin.login.post'), [
                'email' => $user->email,
                'password' => 'Geheim123!',
            ]);

        $response->assertRedirect(route('admin.login'))
            ->assertSessionHas('first_login_required', true)
            ->assertSessionHas('first_login_message', AdminFirstLoginService::ACTIVATION_REQUIRED_MESSAGE);

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Inloggen met code', false)
            ->assertSee(AdminFirstLoginService::ACTIVATION_REQUIRED_MESSAGE, false)
            ->assertSee($user->email, false)
            ->assertSee('Inlogcode aanvragen', false);

        $this->assertGuest();
    }

    #[Test]
    public function pending_admin_can_request_code_set_password_and_login(): void
    {
        $user = $this->makePendingAdmin('nieuw.admin@example.com');
        RateLimiter::clear('admin-first-login-email:'.$user->email);

        $this->postJson(route('admin.login.first-code'), [
            'email' => $user->email,
        ])->assertOk();

        $this->assertDatabaseHas('customer_login_codes', [
            'user_id' => $user->id,
            'purpose' => CustomerLoginCode::PURPOSE_ADMIN,
        ]);

        CustomerLoginCode::query()->where('user_id', $user->id)->update([
            'consumed_at' => now(),
        ]);
        CustomerLoginCode::query()->create([
            'user_id' => $user->id,
            'purpose' => CustomerLoginCode::PURPOSE_ADMIN,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->postJson(route('admin.login.first-verify'), [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'NieuwWacht1',
            'password_confirmation' => 'NieuwWacht1',
        ])
            ->assertOk()
            ->assertJsonPath('redirect', route('admin.handleiding.index', ['saved' => 1]));

        $this->assertAuthenticatedAs($user->fresh());
        $user->refresh();
        $this->assertFalse((bool) $user->must_change_password);
        $this->assertFalse((bool) $user->password_must_be_set);
        $this->assertTrue((bool) $user->welcome_handleiding_pending);
        $this->assertTrue(Hash::check('NieuwWacht1', $user->password));
    }

    #[Test]
    public function visiting_login_does_not_rotate_csrf_token(): void
    {
        $this->get(route('admin.login'))->assertOk();
        $token = session()->token();

        $this->get(route('admin.login'))->assertOk();

        $this->assertSame($token, session()->token());
    }

    #[Test]
    public function expired_code_asks_for_a_new_code(): void
    {
        $user = $this->makePendingAdmin('verlopen@example.com');
        RateLimiter::clear('admin-first-login-email:'.$user->email);
        RateLimiter::clear('admin-first-login-verify:'.$user->email.':127.0.0.1');

        CustomerLoginCode::query()->create([
            'user_id' => $user->id,
            'purpose' => CustomerLoginCode::PURPOSE_ADMIN,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinutes(16),
        ]);

        $this->postJson(route('admin.login.first-verify'), [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'NieuwWacht1',
            'password_confirmation' => 'NieuwWacht1',
        ])
            ->assertStatus(410)
            ->assertJsonPath('code', 'code_expired')
            ->assertJsonFragment(['message' => 'Je inlogcode is verlopen. Vraag een nieuwe code aan.']);

        $this->assertGuest();
        $user->refresh();
        $this->assertTrue((bool) $user->password_must_be_set);
    }

    #[Test]
    public function activated_account_can_request_code_login(): void
    {
        $company = Company::query()->create(['name' => 'Klaar BV', 'is_active' => true]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'klaar@example.com',
            'password' => 'Geheim123!',
            'must_change_password' => false,
            'password_must_be_set' => false,
            'email_verified_at' => now(),
        ]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);
        RateLimiter::clear('admin-first-login-email:'.$user->email);

        $this->postJson(route('admin.login.first-code'), [
            'email' => $user->email,
        ])->assertOk();

        $this->assertDatabaseHas('customer_login_codes', [
            'user_id' => $user->id,
            'purpose' => CustomerLoginCode::PURPOSE_ADMIN,
        ]);
    }

    #[Test]
    public function unknown_email_cannot_request_a_first_login_code(): void
    {
        $this->postJson(route('admin.login.first-code'), [
            'email' => 'onbekend@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Dit is een ongeldig e-mailadres.']);
    }

    #[Test]
    public function created_company_admin_can_request_first_login_code_without_password(): void
    {
        Role::firstOrCreate(['name' => 'company-staff', 'guard_name' => 'web']);
        $company = Company::query()->create(['name' => 'Admin Code BV', 'is_active' => true]);
        User::factory()->create(['company_id' => $company->id]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->actingAs($admin, 'web')
            ->post(route('admin.users.store'), [
                'first_name' => 'Nieuwe',
                'last_name' => 'Beheerder',
                'email' => 'nieuwe.beheerder@example.com',
                'company_id' => $company->id,
                'roles' => ['company-admin'],
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'nieuwe.beheerder@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue((bool) $created->password_must_be_set);

        Auth::logout();
        $this->flushSession();
        RateLimiter::clear('admin-first-login-email:'.strtolower($created->email));

        $this->postJson(route('admin.login.first-code'), [
            'email' => $created->email,
        ])->assertOk();

        $this->assertDatabaseHas('customer_login_codes', [
            'user_id' => $created->id,
            'purpose' => CustomerLoginCode::PURPOSE_ADMIN,
        ]);
    }

    #[Test]
    public function marketplace_registration_assigns_marketplace_role(): void
    {
        Role::firstOrCreate(['name' => MarketplaceCompanyRegistrationService::ROLE, 'guard_name' => 'web']);
        RateLimiter::clear('admin-first-login-email:markt@taxi.test');

        $result = app(MarketplaceCompanyRegistrationService::class)->register([
            'company_name' => 'Taxi Markt BV',
            'email' => 'markt@taxi.test',
            'phone' => '0612345678',
            'city' => 'Amsterdam',
        ], '127.0.0.1');

        $user = $result['user']->fresh();
        $this->assertSame(MarketplaceCompanyRegistrationService::PACKAGE_KEY, $result['company']->package_key);

        $registrar = app(PermissionRegistrar::class);
        $previousTeam = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId((int) $user->company_id);
        try {
            $this->assertTrue($user->hasRole(MarketplaceCompanyRegistrationService::ROLE));
            $this->assertFalse($user->hasRole('company-admin'));
            $this->assertFalse($user->hasRole('chauffeur'));
            $this->assertTrue(app(\App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService::class)
                ->rolesIncludeChauffeur($user->webRoleNames()));
            $this->assertTrue($user->isTenantAdmin());
            $this->assertTrue($user->canAccessAdminPanel());
        } finally {
            $registrar->setPermissionsTeamId($previousTeam);
        }
    }

    #[Test]
    public function marketplace_login_code_rejects_unknown_email_with_register_flag(): void
    {
        $this->postJson(route('admin.login.marketplace-code'), [
            'email' => 'onbekend-markt@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'register' => true,
            ])
            ->assertJsonFragment([
                'message' => 'Dit e-mailadres is niet bekend. Registreer eerst als marketplace-taxibedrijf.',
            ]);
    }

    #[Test]
    public function marketplace_login_code_ensures_marketplace_role_for_existing_admin(): void
    {
        Role::firstOrCreate(['name' => MarketplaceCompanyRegistrationService::ROLE, 'guard_name' => 'web']);
        $email = 'bestaand-markt@taxi.test';
        $company = Company::query()->create([
            'name' => 'Bestaand Markt BV',
            'email' => $email,
            'is_active' => true,
            'package_key' => MarketplaceCompanyRegistrationService::PACKAGE_KEY,
        ]);
        $firstLogin = app(AdminFirstLoginService::class);
        $user = User::factory()->create(array_merge([
            'email' => $email,
            'company_id' => $company->id,
            'password' => $firstLogin->unusablePasswordHash(),
            'welcome_handleiding_pending' => false,
            'email_verified_at' => now(),
        ], $firstLogin->provisionFlags()));
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);
        RateLimiter::clear('admin-first-login-email:'.$email);

        $this->postJson(route('admin.login.marketplace-code'), [
            'email' => $email,
        ])->assertOk();

        $user = $user->fresh();
        $registrar = app(PermissionRegistrar::class);
        $previousTeam = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId((int) $user->company_id);
        try {
            $this->assertTrue($user->hasRole(MarketplaceCompanyRegistrationService::ROLE));
            $this->assertFalse($user->hasRole('company-admin'));
            $this->assertTrue(app(\App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService::class)
                ->rolesIncludeChauffeur($user->webRoleNames()));
        } finally {
            $registrar->setPermissionsTeamId($previousTeam);
        }
    }

    private function makePendingAdmin(string $email): User
    {
        $company = Company::query()->create([
            'name' => 'First Login BV',
            'email' => $email,
            'is_active' => true,
            'package_key' => 'start',
        ]);
        $firstLogin = app(AdminFirstLoginService::class);
        $user = User::factory()->create(array_merge([
            'email' => $email,
            'company_id' => $company->id,
            'password' => $firstLogin->unusablePasswordHash(),
            'welcome_handleiding_pending' => false,
            'email_verified_at' => now(),
        ], $firstLogin->provisionFlags()));
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        return $user->fresh();
    }
}
