<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view-users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit-users', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function show_page_has_send_and_manual_verify_buttons_when_unverified(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->unverified()->create([
            'company_id' => $admin->company_id,
            'phone' => '0612345678',
            'phone_verified_at' => null,
        ]);

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $admin->company_id])
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Verificatiemail versturen', false)
            ->assertSee('Telefoonverificatie versturen', false)
            ->assertSee('E-mailadres handmatig verifiëren', false)
            ->assertSee('Telefoonnummer handmatig verifiëren', false);
    }

    #[Test]
    public function super_admin_can_send_email_verification_to_the_user(): void
    {
        Mail::fake();
        $admin = $this->superAdmin();
        $user = User::factory()->unverified()->create([
            'company_id' => $admin->company_id,
            'email' => 'chauffeur@example.com',
        ]);

        $this->actingAs($admin, 'web')
            ->from(route('admin.users.show', $user))
            ->post(route('admin.users.send-activation-link', $user))
            ->assertRedirect(route('admin.users.show', $user))
            ->assertSessionHas('success');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function super_admin_can_manually_verify_email_and_phone(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->unverified()->create([
            'company_id' => $admin->company_id,
            'phone' => '0612345678',
            'phone_verified_at' => null,
        ]);

        $this->actingAs($admin, 'web')
            ->from(route('admin.users.show', $user))
            ->post(route('admin.users.mark-email-verified', $user))
            ->assertRedirect(route('admin.users.show', $user))
            ->assertSessionHas('success');

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($admin, 'web')
            ->from(route('admin.users.show', $user))
            ->post(route('admin.users.mark-phone-verified', $user))
            ->assertRedirect(route('admin.users.show', $user))
            ->assertSessionHas('success');

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    #[Test]
    public function company_admin_can_send_verification_but_cannot_manually_verify(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Verify', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $role = Role::findByName('company-admin', 'web');
        $role->givePermissionTo(['view-users', 'edit-users']);
        app(UserRoleAssignmentService::class)->syncWebRoles($admin, ['company-admin']);

        $user = User::factory()->unverified()->create([
            'company_id' => $company->id,
            'phone' => '0612345678',
            'phone_verified_at' => null,
        ]);

        Mail::fake();

        $this->actingAs($admin, 'web')
            ->from(route('admin.users.show', $user))
            ->post(route('admin.users.send-activation-link', $user))
            ->assertRedirect(route('admin.users.show', $user))
            ->assertSessionHas('success');

        $this->actingAs($admin, 'web')
            ->from(route('admin.users.show', $user))
            ->post(route('admin.users.send-phone-verification', $user))
            ->assertRedirect(route('admin.users.show', $user))
            ->assertSessionHas('success');

        $this->actingAs($admin, 'web')
            ->post(route('admin.users.mark-email-verified', $user))
            ->assertForbidden();
        $this->actingAs($admin, 'web')
            ->post(route('admin.users.mark-phone-verified', $user))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    #[Test]
    public function creating_a_user_sends_email_verification_and_keeps_email_unverified(): void
    {
        Mail::fake();
        $admin = $this->superAdmin();
        User::factory()->create(['company_id' => $admin->company_id]);

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $admin->company_id])
            ->post(route('admin.users.store'), [
                'first_name' => 'Nieuw',
                'last_name' => 'Account',
                'email' => 'nieuw.account@example.com',
                'password' => 'Password1',
                'company_id' => $admin->company_id,
                'roles' => ['company-admin'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $created = User::query()->where('email', 'nieuw.account@example.com')->first();
        $this->assertNotNull($created);
        $this->assertNull($created->email_verified_at);
        $this->assertStringContainsString('verificatielink', (string) session('success'));
    }

    #[Test]
    public function email_verification_mail_uses_nexa_suite_and_tenant_name(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->unverified()->create([
            'company_id' => $admin->company_id,
            'first_name' => 'Joop',
            'last_name' => 'Joppie',
            'email' => 'joop@example.com',
        ]);
        $user->load('company');

        $html = view('emails.verification', [
            'user' => $user,
            'verificationUrl' => 'https://example.test/verify',
            'suiteBrand' => 'Nexa Suite - '.$user->company->name,
        ])->render();

        $this->assertStringContainsString('Je bent geregistreerd bij Nexa Suite - Verify Co.', $html);
        $this->assertStringContainsString('Als je geen account hebt aangemaakt bij Nexa Suite - Verify Co', $html);
        $this->assertStringContainsString('<p>Met vriendelijke groet,</p>', $html);
        $this->assertStringContainsString('<p>NEXA Suite</p>', $html);
        $this->assertStringNotContainsString('Skillmatching', $html);
        $this->assertStringNotContainsString('Het Nexa', $html);
    }

    #[Test]
    public function creating_a_user_with_phone_also_sends_phone_verification(): void
    {
        Mail::fake();
        $admin = $this->superAdmin();
        User::factory()->create(['company_id' => $admin->company_id]);

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $admin->company_id])
            ->post(route('admin.users.store'), [
                'first_name' => 'Met',
                'last_name' => 'Telefoon',
                'email' => 'met.telefoon@example.com',
                'phone' => '0612345678',
                'password' => 'Password1',
                'company_id' => $admin->company_id,
                'roles' => ['company-admin'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $created = User::query()->where('email', 'met.telefoon@example.com')->first();
        $this->assertNotNull($created);
        $this->assertNull($created->email_verified_at);
        $this->assertNull($created->phone_verified_at);
        $this->assertStringContainsString('telefoonnummer', (string) session('success'));
    }

    #[Test]
    public function bulk_destroy_deletes_selected_users_except_self(): void
    {
        $admin = $this->superAdmin();
        Permission::firstOrCreate(['name' => 'delete-users', 'guard_name' => 'web']);
        $keep = User::factory()->create(['company_id' => $admin->company_id, 'email' => 'keep@example.com']);
        $removeA = User::factory()->create(['company_id' => $admin->company_id, 'email' => 'remove-a@example.com']);
        $removeB = User::factory()->create(['company_id' => $admin->company_id, 'email' => 'remove-b@example.com']);

        $this->actingAs($admin, 'web')
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.bulk-destroy'), [
                'user_ids' => [$removeA->id, $removeB->id, $admin->id],
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('warning');

        $this->assertNull(User::query()->find($removeA->id));
        $this->assertNull(User::query()->find($removeB->id));
        $this->assertNotNull(User::query()->find($admin->id));
        $this->assertNotNull(User::query()->find($keep->id));
    }

    #[Test]
    public function users_index_has_select_all_and_bulk_trash_control(): void
    {
        $admin = $this->superAdmin();
        Permission::firstOrCreate(['name' => 'delete-users', 'guard_name' => 'web']);
        User::factory()->create(['company_id' => $admin->company_id]);

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $admin->company_id])
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('id="users-select-all"', false)
            ->assertSee('id="users-bulk-delete"', false)
            ->assertSee('user-row-checkbox', false);
    }

    #[Test]
    public function phone_verification_link_marks_phone_verified(): void
    {
        $user = User::factory()->unverified()->create([
            'phone' => '0612345678',
            'phone_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute('verify-phone', now()->addDay(), [
            'user' => $user->id,
            'hash' => sha1($user->phone),
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Telefoonnummer succesvol geverifieerd', false);

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    #[Test]
    public function send_phone_verification_without_number_fails(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->unverified()->create([
            'company_id' => $admin->company_id,
            'phone' => null,
            'phone_verified_at' => null,
        ]);

        $this->actingAs($admin, 'web')
            ->from(route('admin.users.show', $user))
            ->post(route('admin.users.send-phone-verification', $user))
            ->assertRedirect(route('admin.users.show', $user))
            ->assertSessionHas('error');
    }

    private function superAdmin(): User
    {
        $company = Company::query()->create(['name' => 'Verify Co', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('super-admin');

        return $admin;
    }
}
