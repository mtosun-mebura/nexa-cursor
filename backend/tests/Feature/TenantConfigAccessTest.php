<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyConfigAccessGrant;
use App\Models\Notification;
use App\Models\TenantCustomerEmail;
use App\Models\User;
use App\Services\TenantConfigAccessNotifier;
use App\Support\TenantConfigCapability;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantConfigAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view-companies', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit-companies', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function company_admin_sees_config_steps_locked_and_cannot_post_without_grant(): void
    {
        [$company, $admin] = $this->companyAdminForCompany();

        $this->actingAs($admin)
            ->get(route('admin.companies.wizard.step', [$company, 8]))
            ->assertOk()
            ->assertSee('Deze configuratie is afgeschermd', false)
            ->assertSee('geen toegang', false);

        $this->actingAs($admin)
            ->post(route('admin.companies.wizard.submit-step', [$company, 8]), [
                'skip_config' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.companies.wizard.submit-step', [$company, 7]), [
                'MAIL_MAILER' => 'log',
                'MAIL_FROM_ADDRESS' => 'noreply@example.com',
                'MAIL_FROM_NAME' => 'Test',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function mollie_grant_unlocks_integrations_step_but_keeps_google_locked(): void
    {
        [$company, $admin] = $this->companyAdminForCompany();
        CompanyConfigAccessGrant::query()->create([
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'capability' => TenantConfigCapability::MOLLIE,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.companies.wizard.step', [$company, 9]))
            ->assertOk()
            ->assertSee('Mollie API-sleutel', false)
            ->assertSee('WhatsApp-configuratie is afgeschermd', false);

        $this->actingAs($admin)
            ->get(route('admin.companies.wizard.step', [$company, 8]))
            ->assertOk()
            ->assertSee('Deze configuratie is afgeschermd', false);
    }

    #[Test]
    public function super_admin_can_grant_config_access_to_tenant_user(): void
    {
        Mail::fake();

        $company = Company::query()->create(['name' => 'Grant Co', 'is_active' => true]);
        $tenantUser = User::factory()->create(['company_id' => $company->id]);
        $tenantUser->assignRole('company-admin');
        $super = User::factory()->create([
            'first_name' => 'Alex',
            'last_name' => 'Jansen',
        ]);
        $super->assignRole('super-admin');

        $this->actingAs($super)
            ->put(route('admin.companies.config-access.update', $company), [
                'grants' => [
                    $tenantUser->id => [TenantConfigCapability::GOOGLE_SEO, TenantConfigCapability::MOLLIE],
                ],
            ])
            ->assertRedirect(route('admin.companies.show', $company));

        $this->assertDatabaseHas('company_config_access_grants', [
            'company_id' => $company->id,
            'user_id' => $tenantUser->id,
            'capability' => TenantConfigCapability::GOOGLE_SEO,
        ]);
        $this->assertDatabaseHas('company_config_access_grants', [
            'company_id' => $company->id,
            'user_id' => $tenantUser->id,
            'capability' => TenantConfigCapability::MOLLIE,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $tenantUser->id,
            'company_id' => $company->id,
            'type' => TenantConfigAccessNotifier::NOTIFICATION_TYPE,
        ]);
        $notification = Notification::query()
            ->where('user_id', $tenantUser->id)
            ->where('type', TenantConfigAccessNotifier::NOTIFICATION_TYPE)
            ->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Google SEO', (string) $notification->message);
        $this->assertStringContainsString('Mollie', (string) $notification->message);
        $this->assertSame(
            route('admin.companies.wizard.step', [$company, 8]),
            $notification->action_url
        );
        $data = json_decode((string) $notification->data, true);
        $this->assertSame($super->id, $data['sender_id'] ?? null);

        $mail = TenantCustomerEmail::query()
            ->where('recipient_email', $tenantUser->email)
            ->where('type', TenantCustomerEmail::TYPE_CONFIG_ACCESS)
            ->first();
        $this->assertNotNull($mail);
        $this->assertSame(TenantCustomerEmail::STATUS_SENT, $mail->status);
        $this->assertStringContainsString('Alex Jansen', (string) $mail->body_html);
        $this->assertStringContainsString('heeft je een bericht gestuurd', (string) $mail->body_html);
        $this->assertStringContainsString('Google SEO', (string) $mail->body_html);
        $this->assertStringContainsString('Mollie', (string) $mail->body_html);
        $this->assertStringContainsString('Open de admin', (string) $mail->body_html);
        $this->assertStringContainsString((string) $notification->action_url, (string) $mail->body_html);
        $this->assertStringContainsString('Alex Jansen', (string) $mail->subject);

        $this->actingAs($super)
            ->put(route('admin.companies.config-access.update', $company), [
                'grants' => [
                    $tenantUser->id => [TenantConfigCapability::GOOGLE_SEO, TenantConfigCapability::MOLLIE],
                ],
            ])
            ->assertRedirect(route('admin.companies.show', $company));
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('tenant_customer_emails', 1);

        $this->actingAs($tenantUser)
            ->get(route('admin.companies.wizard.step', [$company, 8]))
            ->assertOk()
            ->assertDontSee('Deze configuratie is afgeschermd', false);
    }

    #[Test]
    public function revoking_config_access_does_not_notify(): void
    {
        Mail::fake();

        $company = Company::query()->create(['name' => 'Revoke Co', 'is_active' => true]);
        $tenantUser = User::factory()->create(['company_id' => $company->id]);
        $tenantUser->assignRole('company-admin');
        CompanyConfigAccessGrant::query()->create([
            'company_id' => $company->id,
            'user_id' => $tenantUser->id,
            'capability' => TenantConfigCapability::MOLLIE,
        ]);
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($super)
            ->put(route('admin.companies.config-access.update', $company), [
                'grants' => [
                    $tenantUser->id => [],
                ],
            ])
            ->assertRedirect(route('admin.companies.show', $company));

        $this->assertDatabaseCount('company_config_access_grants', 0);
        $this->assertDatabaseCount('notifications', 0);
        Mail::assertNothingOutgoing();
    }

    #[Test]
    public function company_admin_cannot_manage_config_access(): void
    {
        [$company, $admin] = $this->companyAdminForCompany();

        $this->actingAs($admin)
            ->put(route('admin.companies.config-access.update', $company), [
                'grants' => [
                    $admin->id => [TenantConfigCapability::MOLLIE],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('company_config_access_grants', 0);
    }

    /**
     * @return array{0: Company, 1: User}
     */
    private function companyAdminForCompany(): array
    {
        $company = Company::query()->create(['name' => 'Tenant Config Co', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('company-admin');
        $admin->givePermissionTo(['view-companies', 'edit-companies']);

        return [$company, $admin];
    }
}
