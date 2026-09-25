<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Modules\NexaTaxi\Services\TaxiAppLoginCodeEmailTemplateService;
use App\Modules\NexaTaxi\Services\TaxiCustomerAcceptEmailTemplateService;
use App\Modules\NexaTaxi\Services\TaxiCustomerLoginCodeEmailTemplateService;
use App\Services\CompanyEmailLogoService;
use App\Services\NexaContactAanvraagEmailTemplateService;
use App\Support\EmailCardHtml;
use App\Support\NexaBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailBrandingLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function nexa_password_reset_and_contact_mails_use_nexa_logo_card(): void
    {
        $user = (object) ['first_name' => 'Mert', 'last_name' => 'Tosun'];
        $reset = view('emails.password-reset', [
            'user' => $user,
            'resetUrl' => 'https://example.test/reset',
            'nexaLogoHtml' => NexaBranding::EMAIL_LOGO_PLACEHOLDER,
        ])->render();

        $this->assertStringContainsString('#0f172a', $reset);
        $this->assertStringContainsString(NexaBranding::EMAIL_LOGO_PLACEHOLDER, $reset);
        $this->assertStringContainsString('Powered by NEXA Suite.', $reset);
        $this->assertStringContainsString('NEXA Suite-account', $reset);
        $this->assertStringNotContainsString('NEXA Suite</p>', $reset);

        $contact = view('emails.contact', [
            'first_name' => 'Mert',
            'last_name' => 'Tosun',
            'email' => 'mert@example.test',
            'phone' => '0612345678',
            'user_message' => 'Hallo',
            'nexaLogoHtml' => NexaBranding::EMAIL_LOGO_PLACEHOLDER,
        ])->render();

        $this->assertStringContainsString('#0f172a', $contact);
        $this->assertStringContainsString(NexaBranding::EMAIL_LOGO_PLACEHOLDER, $contact);
        $this->assertStringContainsString('Powered by NEXA Suite.', $contact);
        $this->assertStringNotContainsString('NEXA Suite</p>', $contact);
    }

    #[Test]
    public function tenant_booking_mails_use_company_logo_card(): void
    {
        $customer = view('emails.taxi-ride-booking-customer', [
            'customer_name' => 'Jan',
            'ride_id' => 12,
            'pickup_at' => '19-09-2026 10:00',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'quoted_price' => 25.5,
            'summary_text' => 'Samenvatting',
            'company_name' => 'Taxi Tosun',
            'logoHtml' => CompanyEmailLogoService::HTML_PLACEHOLDER,
            'portal_login_url' => 'https://example.test/login',
        ])->render();

        $this->assertStringContainsString('#0f172a', $customer);
        $this->assertStringContainsString(CompanyEmailLogoService::HTML_PLACEHOLDER, $customer);
        $this->assertStringContainsString('Taxi Tosun', $customer);
        $this->assertStringNotContainsString(NexaBranding::EMAIL_LOGO_PLACEHOLDER, $customer);

        $driver = view('emails.taxi-ride-request-driver', [
            'driver_name' => 'Piet',
            'ride_id' => 12,
            'pickup_at' => '19-09-2026 10:00',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'customer_name' => 'Jan',
            'customer_phone' => '0612345678',
            'customer_email' => 'jan@example.test',
            'quoted_price' => 25.5,
            'summary_text' => 'Samenvatting',
            'company_name' => 'Taxi Tosun',
            'logoHtml' => CompanyEmailLogoService::HTML_PLACEHOLDER,
        ])->render();

        $this->assertStringContainsString('#0f172a', $driver);
        $this->assertStringContainsString(CompanyEmailLogoService::HTML_PLACEHOLDER, $driver);
        $this->assertStringContainsString('Taxi Tosun', $driver);
    }

    #[Test]
    public function nexa_contact_aanvraag_template_includes_nexa_logo(): void
    {
        $template = app(NexaContactAanvraagEmailTemplateService::class)->ensureExists();

        $this->assertStringContainsString('{{ NEXA_LOGO }}', (string) $template->html_content);
        $this->assertStringContainsString('#0f172a', (string) $template->html_content);
        $this->assertStringNotContainsString('NEXA Suite</p>', (string) $template->html_content);
        $this->assertStringContainsString('table-layout: auto', (string) $template->html_content);
        $this->assertStringContainsString('@media only screen and (max-width: 600px)', (string) $template->html_content);
        $this->assertStringNotContainsString('table-layout: fixed', (string) $template->html_content);
        $this->assertStringContainsString('Powered by NEXA Suite.', (string) $template->html_content);
        $this->assertStringNotContainsString('NEXA Suite · nexasuite.nl', (string) $template->html_content);
    }

    #[Test]
    public function platform_contact_acknowledgement_omits_nexa_kicker(): void
    {
        $platform = view('emails.contact-acknowledgement', [
            'greetingName' => 'Mert Tosun',
            'companyName' => 'NEXA Suite',
            'logoHtml' => NexaBranding::EMAIL_LOGO_PLACEHOLDER,
        ])->render();
        $this->assertStringContainsString(NexaBranding::EMAIL_LOGO_PLACEHOLDER, $platform);
        $this->assertStringNotContainsString('NEXA Suite</p>', $platform);

        $tenant = view('emails.contact-acknowledgement', [
            'greetingName' => 'Jan',
            'companyName' => 'Taxi Tosun',
            'logoHtml' => CompanyEmailLogoService::HTML_PLACEHOLDER,
        ])->render();
        $this->assertStringContainsString('Taxi Tosun</p>', $tenant);
    }

    #[Test]
    public function tenant_taxi_templates_use_company_logo_in_dark_header(): void
    {
        $login = app(TaxiCustomerLoginCodeEmailTemplateService::class)->defaultHtmlContent();
        $this->assertStringContainsString('{{ COMPANY_LOGO }}', $login);
        $this->assertStringContainsString('#0f172a', $login);
        $this->assertStringContainsString('Powered by NEXA Suite.', $login);

        $appLogin = app(TaxiAppLoginCodeEmailTemplateService::class)->defaultHtmlContent();
        $this->assertStringContainsString('{{ COMPANY_LOGO }}', $appLogin);
        $this->assertStringContainsString('#0f172a', $appLogin);
        $this->assertStringNotContainsString('{{ NEXA_LOGO }}', $appLogin);

        $accepted = app(TaxiCustomerAcceptEmailTemplateService::class)->ensureGlobalTemplateExists();
        $this->assertStringContainsString('{{ COMPANY_LOGO }}', (string) $accepted->html_content);
        $this->assertStringContainsString('#0f172a', (string) $accepted->html_content);
    }

    #[Test]
    public function legacy_tenant_login_code_templates_are_upgraded_to_card_layout(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV']);
        EmailTemplate::query()->create([
            'type' => TaxiCustomerLoginCodeEmailTemplateService::TYPE,
            'company_id' => $company->id,
            'name' => 'Oud',
            'subject' => 'Code',
            'html_content' => '<div style="max-width: 600px;"><div>{{ COMPANY_LOGO }}</div><p>{{ LOGIN_CODE }}</p></div>',
            'is_active' => true,
        ]);

        app(TaxiCustomerLoginCodeEmailTemplateService::class)->ensureGlobalTemplateExists();

        $tenant = EmailTemplate::query()
            ->where('type', TaxiCustomerLoginCodeEmailTemplateService::TYPE)
            ->where('company_id', $company->id)
            ->first();

        $this->assertNotNull($tenant);
        $this->assertStringContainsString('#0f172a', (string) $tenant->html_content);
        $this->assertStringContainsString('{{ COMPANY_LOGO }}', (string) $tenant->html_content);
    }

    #[Test]
    public function nexa_plain_text_mail_is_wrapped_in_card_with_logo_placeholder(): void
    {
        $html = EmailCardHtml::wrap(
            'Onderwerp',
            'Kop',
            EmailCardHtml::bodyFromPlainText("Beste klant,\n\nDit is de inhoud."),
            EmailCardHtml::nexaLogoMarkup(),
            EmailCardHtml::poweredByFooter(),
        );

        $this->assertStringContainsString(NexaBranding::EMAIL_LOGO_PLACEHOLDER, $html);
        $this->assertStringContainsString('#0f172a', $html);
        $this->assertStringContainsString('Beste klant', $html);
        $this->assertStringContainsString('Powered by NEXA Suite.', $html);
        $this->assertStringNotContainsString('NEXA Suite</p>', $html);
    }

    #[Test]
    public function nexa_kicker_is_omitted_but_tenant_kicker_is_kept(): void
    {
        $nexa = EmailCardHtml::wrap(
            'Onderwerp',
            'Kop',
            '<p>Inhoud</p>',
            EmailCardHtml::nexaLogoMarkup(),
            EmailCardHtml::poweredByFooter(),
            'NEXA Suite',
        );
        $this->assertStringNotContainsString('NEXA Suite</p>', $nexa);

        $tenant = EmailCardHtml::wrap(
            'Onderwerp',
            'Kop',
            '<p>Inhoud</p>',
            EmailCardHtml::companyLogoMarkup(),
            EmailCardHtml::poweredByFooter(),
            'Taxi Tosun',
        );
        $this->assertStringContainsString('Taxi Tosun</p>', $tenant);
    }
}
