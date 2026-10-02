<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMailSettingsAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function company_admin_sees_only_mailserver_in_configuraties(): void
    {
        $admin = $this->companyAdmin();

        $html = $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Mail Server Instellingen', false)
            ->assertSee('id="mail"', false)
            ->assertDontSee('id="seo"', false)
            ->assertDontSee('id="mollie"', false)
            ->assertDontSee('id="whatsapp"', false)
            ->assertDontSee('id="tenant-sync"', false)
            ->assertDontSee('Upgrade (platform)', false)
            ->getContent();

        $this->assertStringContainsString('Mailserver', $html);
        $this->assertStringNotContainsString('Algemene configuraties', $html);
        $this->assertStringNotContainsString('Systeem configuraties', $html);
    }

    #[Test]
    public function company_admin_can_save_tenant_mail_settings(): void
    {
        $admin = $this->companyAdmin();

        $this->actingAs($admin)
            ->post(route('admin.settings.mail.update'), [
                'MAIL_MAILER' => 'log',
                'MAIL_HOST' => '',
                'MAIL_FROM_ADDRESS' => 'info@taxiroyaal.test',
                'MAIL_FROM_NAME' => 'Taxi Royaal',
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('success');

        // Zonder eigen SMTP-host: alleen From-naam opslaan; From-adres blijft NEXA Suite.
        $this->assertSame(
            'Taxi Royaal',
            GeneralSetting::query()
                ->where('company_id', $admin->company_id)
                ->where('key', 'MAIL_FROM_NAME')
                ->value('value')
        );
        $this->assertNull(
            GeneralSetting::query()
                ->where('company_id', $admin->company_id)
                ->where('key', 'MAIL_FROM_ADDRESS')
                ->first()
        );
    }

    #[Test]
    public function company_admin_can_save_own_smtp_mail_settings(): void
    {
        $admin = $this->companyAdmin();

        $this->actingAs($admin)
            ->post(route('admin.settings.mail.update'), [
                'MAIL_MAILER' => 'smtp',
                'MAIL_HOST' => 'smtp.example.com',
                'MAIL_PORT' => '587',
                'MAIL_ENCRYPTION' => 'tls',
                'MAIL_USERNAME' => 'mail@example.com',
                'MAIL_PASSWORD' => 'secret',
                'MAIL_FROM_ADDRESS' => 'info@taxiroyaal.test',
                'MAIL_FROM_NAME' => 'Taxi Royaal',
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('success');

        $this->assertSame(
            'info@taxiroyaal.test',
            GeneralSetting::query()
                ->where('company_id', $admin->company_id)
                ->where('key', 'MAIL_FROM_ADDRESS')
                ->value('value')
        );
        $this->assertTrue(GeneralSetting::companyHasOwnMailDelivery((int) $admin->company_id));
    }

    #[Test]
    public function company_admin_without_mailserver_permission_cannot_open_settings(): void
    {
        $role = Role::findByName('company-admin', 'web');
        $role->revokePermissionTo(['view-mailserver', 'edit-mailserver']);

        $admin = $this->companyAdmin();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.settings.mail.update'), [
                'MAIL_MAILER' => 'log',
                'MAIL_FROM_ADDRESS' => 'blocked@example.com',
                'MAIL_FROM_NAME' => 'Blocked',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function company_admin_cannot_open_other_settings_routes(): void
    {
        $admin = $this->companyAdmin();

        $this->actingAs($admin)
            ->get(route('admin.settings.general.index'))
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.settings.upgrade.index'))
            ->assertRedirect();
    }

    #[Test]
    public function roles_edit_shows_mailserver_permission_toggles(): void
    {
        $super = User::factory()->create();
        app(UserRoleAssignmentService::class)->syncWebRoles($super, ['super-admin']);
        $role = Role::findByName('company-admin', 'web');

        $this->actingAs($super)
            ->get(route('admin.roles.edit', $role))
            ->assertOk()
            ->assertSee('Mailserver', false)
            ->assertSee('view-mailserver', false)
            ->assertSee('edit-mailserver', false);
    }

    private function companyAdmin(): User
    {
        $company = Company::query()->create(['name' => 'Taxi Mail BV', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        return $user->fresh();
    }
}
