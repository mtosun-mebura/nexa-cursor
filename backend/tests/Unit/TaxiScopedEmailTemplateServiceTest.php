<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\EmailTemplate;
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
}
