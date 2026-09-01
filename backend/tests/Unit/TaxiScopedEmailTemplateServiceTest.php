<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Modules\NexaTaxi\Services\TaxiAppLoginCodeEmailTemplateService;
use App\Modules\NexaTaxi\Services\TaxiAppUserWelcomeEmailTemplateService;
use App\Modules\NexaTaxi\Services\TaxiCustomerLoginCodeEmailTemplateService;
use App\Modules\NexaTaxi\Services\TaxiCustomerLoginCodeService;
use App\Modules\NexaTaxi\Services\TaxiCustomerAcceptEmailTemplateService;
use App\Services\CompanyEmailLogoService;
use App\Services\EmailTemplateService;
use App\Services\EnvService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiScopedEmailTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ensure_global_login_code_template_is_idempotent(): void
    {
        EmailTemplate::query()->create([
            'type' => TaxiCustomerLoginCodeEmailTemplateService::TYPE,
            'company_id' => null,
            'name' => 'Eenmalige inlogcode (Nexa Taxi)',
            'subject' => 'Oud onderwerp',
            'html_content' => '<p>oud</p>',
            'is_active' => true,
        ]);

        $service = app(TaxiCustomerLoginCodeEmailTemplateService::class);
        $service->ensureGlobalTemplateExists();
        $service->ensureGlobalTemplateExists();

        $this->assertSame(1, EmailTemplate::query()
            ->where('type', TaxiCustomerLoginCodeEmailTemplateService::TYPE)
            ->whereNull('company_id')
            ->count());
    }

    #[Test]
    public function ensure_tenant_login_code_template_is_idempotent(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV']);

        $service = app(TaxiCustomerLoginCodeEmailTemplateService::class);
        $service->ensureTenantTemplateExists($company->id);
        $service->ensureTenantTemplateExists($company->id);

        $this->assertSame(1, EmailTemplate::query()
            ->where('type', TaxiCustomerLoginCodeEmailTemplateService::TYPE)
            ->where('company_id', $company->id)
            ->count());
    }

    #[Test]
    public function ensure_global_ride_accepted_template_is_idempotent(): void
    {
        EmailTemplate::query()->create([
            'type' => TaxiCustomerAcceptEmailTemplateService::TYPE,
            'company_id' => null,
            'name' => 'Rit geaccepteerd (Nexa Taxi)',
            'subject' => 'Oud onderwerp',
            'html_content' => '<p>oud</p>',
            'is_active' => true,
        ]);

        $service = app(TaxiCustomerAcceptEmailTemplateService::class);
        $service->ensureGlobalTemplateExists();
        $service->ensureGlobalTemplateExists();

        $this->assertSame(1, EmailTemplate::query()
            ->where('type', TaxiCustomerAcceptEmailTemplateService::TYPE)
            ->whereNull('company_id')
            ->count());
    }

    #[Test]
    public function issuing_login_code_does_not_create_email_templates(): void
    {
        Mail::fake();

        $company = Company::query()->create(['name' => 'Taxi BV', 'is_active' => true]);
        $user = \App\Models\User::factory()->create([
            'company_id' => $company->id,
            'email' => 'klant@example.test',
        ]);

        $this->mock(EnvService::class, function ($mock): void {
            $mock->shouldReceive('isMailDeliverableToInbox')->andReturn(true);
            $mock->shouldReceive('applyMailConfigToRuntime');
            $mock->shouldReceive('resolveMailFromHeaders')->andReturn([
                'from_address' => 'noreply@example.test',
                'from_name' => 'Test',
                'smtp_username' => '',
            ]);
        });

        $loginService = new TaxiCustomerLoginCodeService(
            app(TaxiCustomerLoginCodeEmailTemplateService::class),
            app(EmailTemplateService::class),
            app(CompanyEmailLogoService::class),
            app(EnvService::class),
        );

        $loginService->issueAndSend($user, $company->id, 'https://example.test/login');
        $loginService->issueAndSend($user, $company->id, 'https://example.test/login');

        $this->assertSame(0, EmailTemplate::query()
            ->where('type', TaxiCustomerLoginCodeEmailTemplateService::TYPE)
            ->where('company_id', $company->id)
            ->count());
        $this->assertSame(0, EmailTemplate::query()
            ->where('type', TaxiCustomerLoginCodeEmailTemplateService::TYPE)
            ->whereNull('company_id')
            ->count());
    }

    #[Test]
    public function app_login_code_prefers_tenant_template_over_global(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Tosun']);

        EmailTemplate::query()->create([
            'type' => TaxiAppLoginCodeEmailTemplateService::TYPE,
            'company_id' => null,
            'name' => 'Globaal',
            'subject' => 'Globaal',
            'html_content' => '<p>STANDAARD</p>',
            'is_active' => true,
        ]);
        $tenant = EmailTemplate::query()->create([
            'type' => TaxiAppLoginCodeEmailTemplateService::TYPE,
            'company_id' => $company->id,
            'name' => 'Tenant',
            'subject' => 'Tenant',
            'html_content' => '<p>TENANT-EIGEN</p>',
            'is_active' => true,
        ]);

        $active = app(TaxiAppLoginCodeEmailTemplateService::class)
            ->resolveActiveTemplate($company->id);

        $this->assertNotNull($active);
        $this->assertSame($tenant->id, $active->id);
        $this->assertSame('<p>TENANT-EIGEN</p>', $active->html_content);
    }

    #[Test]
    public function app_login_code_upgrade_turns_heading_into_button_on_global_and_tenant(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Tosun']);
        $heading = '<h2 style="text-align:center;"><a href="{{ LOGIN_URL }}">{{ APP_NAME }}</a></h2>';

        EmailTemplate::query()->create([
            'type' => TaxiAppLoginCodeEmailTemplateService::TYPE,
            'company_id' => null,
            'name' => 'Globaal',
            'subject' => 'Globaal',
            'html_content' => '<p>Open de app:</p>'.$heading.'<p>Powered by NEXA SaaS.</p>',
            'is_active' => true,
        ]);
        EmailTemplate::query()->create([
            'type' => TaxiAppLoginCodeEmailTemplateService::TYPE,
            'company_id' => $company->id,
            'name' => 'Tenant',
            'subject' => 'Tenant',
            'html_content' => '<p>Open de app:</p>'.$heading.'<p>Powered by NEXA SaaS.</p>',
            'is_active' => true,
        ]);

        app(TaxiAppLoginCodeEmailTemplateService::class)->ensureGlobalTemplateExists();

        $global = EmailTemplate::query()
            ->where('type', TaxiAppLoginCodeEmailTemplateService::TYPE)
            ->whereNull('company_id')
            ->first();
        $tenant = EmailTemplate::query()
            ->where('type', TaxiAppLoginCodeEmailTemplateService::TYPE)
            ->where('company_id', $company->id)
            ->first();

        $this->assertNotNull($global);
        $this->assertNotNull($tenant);
        foreach ([$global, $tenant] as $template) {
            $this->assertStringContainsString('background-color:#ea580c', $template->html_content);
            $this->assertStringContainsString('Open {{ APP_NAME }}', $template->html_content);
            $this->assertStringContainsString('Powered by NEXA SaaS.', $template->html_content);
            $this->assertStringNotContainsString('<h2', $template->html_content);
        }
    }

    #[Test]
    public function ensure_global_app_login_code_does_not_overwrite_custom_html(): void
    {
        EmailTemplate::query()->create([
            'type' => TaxiAppLoginCodeEmailTemplateService::TYPE,
            'company_id' => null,
            'name' => 'Aangepast',
            'subject' => 'Aangepast onderwerp',
            'html_content' => '<p>eigen globale tekst</p>',
            'is_active' => true,
        ]);

        app(TaxiAppLoginCodeEmailTemplateService::class)->ensureGlobalTemplateExists();

        $global = EmailTemplate::query()
            ->where('type', TaxiAppLoginCodeEmailTemplateService::TYPE)
            ->whereNull('company_id')
            ->first();

        $this->assertNotNull($global);
        $this->assertSame('<p>eigen globale tekst</p>', $global->html_content);
        $this->assertSame('Aangepast onderwerp', $global->subject);
    }

    #[Test]
    public function welcome_login_block_upgrade_rounds_corners_and_darkens_label(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Tosun']);
        $oldBlock = '<table role="presentation" width="100%" style="width:100%;border-collapse:collapse;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:0 0 20px;"><tr><td style="padding:16px 18px;"><p style="margin:0 0 8px;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;">Inloggen</p><p style="margin:0;font-size:14px;"><strong>E-mailadres:</strong> {{ USER_EMAIL }}</p></td></tr></table>';

        EmailTemplate::query()->create([
            'type' => TaxiAppUserWelcomeEmailTemplateService::TYPE_CHAUFFEUR,
            'company_id' => null,
            'name' => 'Globaal',
            'subject' => 'Globaal',
            'html_content' => '<p>Hallo</p>'.$oldBlock.'<p>footer</p>',
            'is_active' => true,
        ]);
        EmailTemplate::query()->create([
            'type' => TaxiAppUserWelcomeEmailTemplateService::TYPE_CHAUFFEUR,
            'company_id' => $company->id,
            'name' => 'Tenant',
            'subject' => 'Tenant',
            'html_content' => '<p>Hallo</p>'.$oldBlock.'<p>Powered by NEXA SaaS.</p>',
            'is_active' => true,
        ]);

        app(TaxiAppUserWelcomeEmailTemplateService::class)->ensureAllGlobalTemplatesExist();

        $global = EmailTemplate::query()
            ->where('type', TaxiAppUserWelcomeEmailTemplateService::TYPE_CHAUFFEUR)
            ->whereNull('company_id')
            ->first();
        $tenant = EmailTemplate::query()
            ->where('type', TaxiAppUserWelcomeEmailTemplateService::TYPE_CHAUFFEUR)
            ->where('company_id', $company->id)
            ->first();

        foreach ([$global, $tenant] as $template) {
            $this->assertNotNull($template);
            $this->assertStringContainsString('border-radius:12px', $template->html_content);
            $this->assertStringContainsString('border-collapse:separate', $template->html_content);
            $this->assertStringContainsString('background-color:#e8eef5', $template->html_content);
            $this->assertStringContainsString('color:#0f172a;font-weight:700', $template->html_content);
            $this->assertStringNotContainsString('#f8fafc', $template->html_content);
            $this->assertStringNotContainsString('#64748b', $template->html_content);
        }
        $this->assertStringContainsString('Powered by NEXA SaaS.', $tenant->html_content);
    }
}
