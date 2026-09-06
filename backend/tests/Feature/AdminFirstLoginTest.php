<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CustomerLoginCode;
use App\Models\User;
use App\Services\AdminFirstLoginCodeEmailTemplateService;
use App\Services\AdminFirstLoginService;
use App\Services\UserRoleAssignmentService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
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
            ->assertSee('Eerste keer inloggen', false)
            ->assertSee('Inlogcode aanvragen', false)
            ->assertSee('Terug naar inloggen', false)
            ->assertSee('eenmalige code aan', false)
            ->assertSee('Minimaal 8 tekens, met een hoofdletter, een kleine letter en een cijfer.', false);
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
            ->assertSee('Eerste keer inloggen', false)
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
    public function activated_account_must_use_password(): void
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
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Dit account is al geactiveerd. Log in met uw wachtwoord.']);
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
