<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PublicRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PublicRegistrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['klant', 'driver_pending', 'chauffeur', 'super-admin', 'company-admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'api']);
        }
    }

    #[Test]
    public function customer_registration_assigns_only_klant_and_requires_email_verification(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register', [
            'email' => 'Klant@Test.Example',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
            'first_name' => 'Anna',
            'last_name' => 'Klant',
            'account_type' => 'customer',
            // Privilege injection attempts — must be ignored.
            'role' => 'super-admin',
            'roles' => ['super-admin', 'chauffeur'],
            'company_id' => 999,
            'is_active' => true,
            'email_verified_at' => now()->toISOString(),
            'permissions' => ['assign-roles'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('requires_email_verification', true)
            ->assertJsonPath('user.role', 'klant')
            ->assertJsonMissingPath('token');

        $user = User::query()->where('email', 'klant@test.example')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->company_id);
        $this->assertTrue($user->hasRole('klant'));
        $this->assertFalse($user->hasRole('super-admin'));
        $this->assertFalse($user->hasRole('chauffeur'));
        $this->assertFalse($user->hasRole('driver_pending'));
    }

    #[Test]
    public function driver_registration_starts_as_driver_pending_not_chauffeur(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register', [
            'email' => 'driver@example.com',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
            'account_type' => 'driver',
            'role' => 'chauffeur',
            'company_id' => 42,
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.role', PublicRegistrationService::ROLE_DRIVER_PENDING);

        $user = User::query()->where('email', 'driver@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('driver_pending'));
        $this->assertFalse($user->hasRole('chauffeur'));
        $this->assertFalse((bool) $user->is_active);
        $this->assertNull($user->company_id);
    }

    #[Test]
    public function company_self_registration_is_rejected_for_now(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', [
            'email' => 'bedrijf@example.com',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
            'account_type' => 'company',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['account_type']);

        $this->assertNull(User::query()->where('email', 'bedrijf@example.com')->first());
    }

    #[Test]
    public function weak_password_is_rejected(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', [
            'email' => 'weak@example.com',
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function unverified_customer_cannot_login(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', [
            'email' => 'pending@example.com',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ])->assertCreated();

        $this->postJson('/api/auth/login', [
            'email' => 'pending@example.com',
            'password' => 'SecurePass1',
        ])->assertStatus(403)
            ->assertJsonPath('error', 'email_not_verified');
    }

    #[Test]
    public function pending_driver_cannot_login_even_when_verified(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', [
            'email' => 'pending-driver@example.com',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
            'account_type' => 'driver',
        ])->assertCreated();

        $user = User::query()->where('email', 'pending-driver@example.com')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->postJson('/api/auth/login', [
            'email' => 'pending-driver@example.com',
            'password' => 'SecurePass1',
        ])->assertStatus(403)
            ->assertJsonPath('error', 'driver_pending');
    }
}
