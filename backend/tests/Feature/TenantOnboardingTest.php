<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\TenantOnboardingService;
use App\Services\TenantWelcomeEmailTemplateService;
use App\Services\UserRoleAssignmentService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        app(TenantWelcomeEmailTemplateService::class)->ensureExists();
        Mail::fake();
    }

    #[Test]
    public function wizard_requires_package_and_contact_person(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->post(route('admin.companies.wizard.store-step1'), [
                'name' => 'Taxi Nieuw',
                'email' => 'info@example.com',
                'phone' => '0612345678',
                'street' => 'Kerkstraat',
                'house_number' => '1',
                'postal_code' => '1234AB',
                'city' => 'Amsterdam',
            ])
            ->assertSessionHasErrors(['kvk_number', 'contact_first_name', 'contact_last_name', 'package_key', 'industry']);
    }

    #[Test]
    public function finishing_wizard_creates_company_admin_and_sends_welcome_mail(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $company = Company::query()->create([
            'name' => 'Taxi Onboard BV',
            'email' => 'beheer@example.com',
            'phone' => '0612345678',
            'contact_first_name' => 'Marie',
            'contact_last_name' => 'de Vries',
            'package_key' => 'start',
            'is_active' => true,
            'kvk_number' => '12345678',
            'industry' => 'Taxi',
            'street' => 'Kerkstraat',
            'house_number' => '1',
            'postal_code' => '1234AB',
            'city' => 'Amsterdam',
        ]);

        $this->actingAs($admin)
            ->withSession(['company_wizard.'.$company->id.'.max_reachable' => 10])
            ->post(route('admin.companies.wizard.submit-step', [$company, 10]))
            ->assertRedirect(route('admin.companies.show', $company));

        $user = User::query()->where('email', 'beheer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->password_must_be_set);
        $this->assertNotNull($user->email_verified_at);
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->unsetRelation('roles');
        $this->assertTrue($user->hasRole('company-admin'));
        $this->assertNotNull(EmailTemplate::query()->where('type', 'tenant_welcome')->whereNull('company_id')->first());
    }

    #[Test]
    public function wizard_blocks_future_steps_until_previous_are_completed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $company = Company::query()->create([
            'name' => 'Taxi Stappen BV',
            'email' => 'stappen@example.com',
            'package_key' => 'start',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->withSession(['company_wizard.'.$company->id.'.max_reachable' => 3])
            ->get(route('admin.companies.wizard.step', [$company, 10]))
            ->assertRedirect(route('admin.companies.wizard.step', [$company, 3]));

        $this->actingAs($admin)
            ->withSession(['company_wizard.'.$company->id.'.max_reachable' => 3])
            ->post(route('admin.companies.wizard.submit-step', [$company, 10]))
            ->assertRedirect(route('admin.companies.wizard.step', [$company, 3]));
    }

    #[Test]
    public function existing_company_can_open_all_wizard_steps_without_onboarding_session(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $company = Company::query()->create([
            'name' => 'Taxi Bestaand BV',
            'email' => 'bestaand@example.com',
            'package_key' => 'pro',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.companies.wizard.step', [$company, 9]))
            ->assertOk()
            ->assertSee('Mollie (tenant)', false);
    }

    #[Test]
    public function wizard_mail_step_stores_tenant_mail_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $company = Company::query()->create([
            'name' => 'Taxi Mail BV',
            'email' => 'mailtenant@example.com',
            'package_key' => 'pro',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->withSession(['company_wizard.'.$company->id.'.max_reachable' => 7])
            ->post(route('admin.companies.wizard.submit-step', [$company, 7]), [
                'MAIL_MAILER' => 'smtp',
                'MAIL_HOST' => 'smtp.example.com',
                'MAIL_PORT' => '587',
                'MAIL_ENCRYPTION' => 'tls',
                'MAIL_USERNAME' => 'tenant@example.com',
                'MAIL_FROM_ADDRESS' => 'noreply@example.com',
                'MAIL_FROM_NAME' => 'Taxi Mail',
            ])
            ->assertRedirect(route('admin.companies.wizard.step', [$company, 8]));

        $this->assertSame('smtp', \App\Models\GeneralSetting::get('MAIL_MAILER', null, $company->id));
        $this->assertSame('smtp.example.com', \App\Models\GeneralSetting::get('MAIL_HOST', null, $company->id));
    }

    #[Test]
    public function wizard_switches_selected_tenant_so_super_admin_can_continue(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $other = Company::query()->create([
            'name' => 'Andere Tenant BV',
            'email' => 'andere@example.com',
            'package_key' => 'start',
            'is_active' => true,
        ]);
        $company = Company::query()->create([
            'name' => 'Nieuwe Wizard BV',
            'email' => 'nieuwizard@example.com',
            'package_key' => 'pro',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->withSession([
                'selected_tenant' => $other->id,
                'company_wizard.'.$company->id.'.max_reachable' => 2,
            ])
            ->get(route('admin.companies.wizard.step', [$company, 2]))
            ->assertOk()
            ->assertSee('Vestigingen', false);

        $this->assertSame($company->id, (int) session('selected_tenant'));
    }

    #[Test]
    public function temporary_password_must_be_changed_before_other_admin_actions(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Lock BV',
            'email' => 'lock@example.com',
            'package_key' => 'start',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'email' => 'lock@example.com',
            'company_id' => $company->id,
            'must_change_password' => true,
            'password' => 'Tijdelijk1',
        ]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Wachtwoord wijzigen', false)
            ->assertSee('tijdelijk wachtwoord', false);

        $this->actingAs($user)
            ->from(route('admin.dashboard'))
            ->post(route('admin.subscriptions.upgrade'), ['package_key' => 'pro'])
            ->assertRedirect();

        $this->actingAs($user)
            ->from(route('admin.dashboard'))
            ->post(route('admin.password.force.update'), [
                'password' => 'NieuwWacht1',
                'password_confirmation' => 'NieuwWacht1',
            ])
            ->assertRedirect(route('admin.handleiding.index', ['saved' => 1]));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue($user->welcome_handleiding_pending);
        $this->assertTrue(Hash::check('NieuwWacht1', $user->password));
    }

    #[Test]
    public function cannot_reuse_the_temporary_password(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'password' => 'Tijdelijk1',
        ]);
        $user->assignRole('company-admin');

        $this->actingAs($user)
            ->from(route('admin.dashboard'))
            ->post(route('admin.password.force.update'), [
                'password' => 'Tijdelijk1',
                'password_confirmation' => 'Tijdelijk1',
            ])
            ->assertSessionHasErrors('password');
    }

    #[Test]
    public function start_package_hides_dispatch_handleiding(): void
    {
        $company = Company::query()->create([
            'name' => 'Start Handleiding BV',
            'package_key' => 'start',
            'is_active' => true,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        $this->actingAs($user)
            ->get(route('admin.handleiding.index'))
            ->assertOk()
            ->assertSee('Aan de slag')
            ->assertSee('Ritten en boekingen')
            ->assertDontSee('Dispatch');

        $this->actingAs($user)
            ->get(route('admin.handleiding.show', 'dispatch'))
            ->assertNotFound();
    }

    #[Test]
    public function onboarding_service_sends_admin_login_button(): void
    {
        $template = app(TenantWelcomeEmailTemplateService::class)->ensureExists();
        $this->assertStringContainsString('nexasuite.nl/admin', (string) $template->html_content);
        $this->assertStringNotContainsString('TEMP_PASSWORD', (string) $template->html_content);
        $this->assertStringNotContainsString('Tijdelijk wachtwoord', (string) $template->html_content);
        $this->assertStringContainsString('border-radius:12px', (string) $template->html_content);
        $this->assertStringContainsString('border-collapse:separate', (string) $template->html_content);
        $this->assertStringContainsString('eenmalige code', (string) $template->html_content);
        $this->assertStringContainsString('HANDLEIDING_URL', (string) $template->html_content);

        $company = Company::query()->create([
            'name' => 'Mail Check BV',
            'email' => 'mailcheck@example.com',
            'contact_first_name' => 'Ada',
            'contact_last_name' => 'Koning',
            'package_key' => 'pro',
            'is_active' => true,
        ]);

        $result = app(TenantOnboardingService::class)->provisionCompanyAdmin($company);
        $this->assertTrue($result['created']);
        $this->assertTrue($result['mailed']);
        $this->assertNull($result['password']);
        $this->assertTrue($result['user']->must_change_password);
        $this->assertTrue($result['user']->password_must_be_set);
        $this->assertDatabaseHas('tenant_customer_emails', [
            'company_id' => $company->id,
            'type' => 'tenant_welcome',
            'recipient_email' => 'mailcheck@example.com',
            'status' => 'sent',
        ]);
        $mail = \App\Models\TenantCustomerEmail::query()
            ->where('recipient_email', 'mailcheck@example.com')
            ->where('type', 'tenant_welcome')
            ->first();
        $this->assertNotNull($mail);
        $this->assertStringNotContainsString('Tijdelijk wachtwoord', (string) $mail->body_html);
        $this->assertStringContainsString('eenmalige code', (string) $mail->body_html);
    }

    #[Test]
    public function stored_welcome_template_gets_rounded_login_box_and_drops_password(): void
    {
        EmailTemplate::query()->where('type', 'tenant_welcome')->delete();
        EmailTemplate::query()->create([
            'type' => 'tenant_welcome',
            'company_id' => null,
            'name' => 'Oude welkomstmail',
            'subject' => 'Welkom',
            'description' => 'Bevat inloggegevens, tijdelijk wachtwoord en knoppen naar de admin.',
            'html_content' => '<table role="presentation" width="100%" style="width: 100%; border-collapse: collapse; background-color: #334155; border: 1px solid #94a3b8; border-radius: 8px; margin: 0 0 20px;"><tr><td style="padding: 16px 18px;"><p>Inloggen</p><p><strong>Tijdelijk wachtwoord:</strong> {{ TEMP_PASSWORD }}</p></td></tr></table>',
            'text_content' => "Tijdelijk wachtwoord: {{ TEMP_PASSWORD }}\nLog in met uw e-mailadres als gebruikersnaam en het tijdelijke wachtwoord.",
            'is_active' => true,
        ]);

        $template = app(TenantWelcomeEmailTemplateService::class)->ensureExists();
        $this->assertStringNotContainsString('TEMP_PASSWORD', (string) $template->html_content);
        $this->assertStringNotContainsString('Tijdelijk wachtwoord', (string) $template->html_content);
        $this->assertStringContainsString('border-radius:12px', (string) $template->html_content);
        $this->assertStringContainsString('border-collapse:separate', (string) $template->html_content);
        $this->assertStringContainsString('eenmalige code', (string) $template->html_content);
        $this->assertStringContainsString('eenmalige code', (string) $template->text_content);
        $this->assertStringNotContainsString('tijdelijk wachtwoord', mb_strtolower((string) $template->description));
    }

    #[Test]
    public function company_page_can_create_admin_and_send_welcome_when_wizard_was_not_finished(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $company = Company::query()->create([
            'name' => 'Taxi Tosun Test',
            'email' => 'membur+onboarding@example.com',
            'contact_first_name' => 'Memmo',
            'contact_last_name' => 'Tosuno',
            'package_key' => 'business',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.companies.show', $company))
            ->assertOk()
            ->assertSee('Company-admin ontbreekt', false)
            ->assertSee('Welkomstmail nu versturen', false);

        $this->actingAs($admin)
            ->post(route('admin.companies.send-welcome', $company))
            ->assertRedirect(route('admin.companies.show', $company))
            ->assertSessionHas('success');

        $user = User::query()->where('email', 'membur+onboarding@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame($company->id, (int) $user->company_id);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->password_must_be_set);
        $this->assertDatabaseHas('tenant_customer_emails', [
            'company_id' => $company->id,
            'type' => 'tenant_welcome',
            'recipient_email' => 'membur+onboarding@example.com',
            'status' => 'sent',
        ]);
    }
}
