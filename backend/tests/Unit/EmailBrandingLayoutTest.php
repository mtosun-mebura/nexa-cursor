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

        $this->assertStringContainsString('background-color:#e5e7eb', $reset);
        $this->assertStringContainsString('color:#0f172a', $reset);
        $this->assertStringContainsString(NexaBranding::EMAIL_LOGO_PLACEHOLDER, $reset);
        $this->assertStringContainsString('https://nexasuite.nl', $reset);
        $this->assertStringContainsString('NEXA Suite-account', $reset);
        $this->assertStringNotContainsString('NEXA Suite</p>', $reset);
        $this->assertStringNotContainsString('bgcolor="#0f172a"', $reset);

        $contact = view('emails.contact', [
            'first_name' => 'Mert',
            'last_name' => 'Tosun',
            'email' => 'mert@example.test',
            'phone' => '0612345678',
            'user_message' => 'Hallo',
            'nexaLogoHtml' => NexaBranding::EMAIL_LOGO_PLACEHOLDER,
        ])->render();

        $this->assertStringContainsString('background-color:#e5e7eb', $contact);
        $this->assertStringContainsString(NexaBranding::EMAIL_LOGO_PLACEHOLDER, $contact);
        $this->assertStringContainsString('https://nexasuite.nl', $contact);
        $this->assertStringNotContainsString('NEXA Suite</p>', $contact);
        $this->assertStringNotContainsString('bgcolor="#0f172a"', $contact);
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

        $this->assertStringContainsString('background-color:#e5e7eb', $customer);
        $this->assertStringContainsString('color:#0f172a', $customer);
        $this->assertStringContainsString(CompanyEmailLogoService::HTML_PLACEHOLDER, $customer);
        $this->assertStringContainsString('Taxi Tosun', $customer); // footer contact line
        $this->assertStringNotContainsString('Taxi Tosun</p>', $customer); // no header kicker under logo
        $this->assertStringNotContainsString('#94a3b8', $customer);
        $this->assertStringNotContainsString('bgcolor="#0f172a"', $customer);
        $this->assertStringNotContainsString(NexaBranding::EMAIL_LOGO_PLACEHOLDER, $customer);

        $cancelled = view('emails.taxi-ride-cancelled-customer', [
            'customer_name' => 'Jan',
            'ride_id' => 311,
            'pickup_at' => '09-11-2026 13:45',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'company_name' => 'Taxi Tosun',
            'logoHtml' => CompanyEmailLogoService::HTML_PLACEHOLDER,
            'intro_text' => 'Uw rit is geannuleerd zoals u heeft aangevraagd.',
            'refund_message' => null,
        ])->render();

        $this->assertStringContainsString(CompanyEmailLogoService::HTML_PLACEHOLDER, $cancelled);
        $this->assertStringNotContainsString('Taxi Tosun</p>', $cancelled);
        $this->assertStringNotContainsString('#94a3b8', $cancelled);

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

        $this->assertStringContainsString('background-color:#e5e7eb', $driver);
        $this->assertStringContainsString(CompanyEmailLogoService::HTML_PLACEHOLDER, $driver);
        // Logo slot filled → company name is not repeated as a header kicker.
        $this->assertStringNotContainsString('Taxi Tosun</p>', $driver);
        $this->assertStringNotContainsString('bgcolor="#0f172a"', $driver);
    }

    #[Test]
    public function nexa_contact_aanvraag_template_includes_nexa_logo(): void
    {
        $template = app(NexaContactAanvraagEmailTemplateService::class)->ensureExists();

        $this->assertStringContainsString('{{ NEXA_LOGO }}', (string) $template->html_content);
        $this->assertStringContainsString('background-color: #e5e7eb', (string) $template->html_content);
        $this->assertStringContainsString('color: #0f172a', (string) $template->html_content);
        $this->assertStringNotContainsString('bgcolor="#0f172a"', (string) $template->html_content);
        $this->assertStringNotContainsString('NEXA Suite</p>', (string) $template->html_content);
        $this->assertStringContainsString('table-layout: auto', (string) $template->html_content);
        $this->assertStringContainsString('@media only screen and (max-width: 600px)', (string) $template->html_content);
        $this->assertStringNotContainsString('table-layout: fixed', (string) $template->html_content);
        $this->assertStringContainsString('https://nexasuite.nl', (string) $template->html_content);
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

        $tenantWithLogo = view('emails.contact-acknowledgement', [
            'greetingName' => 'Jan',
            'companyName' => 'Taxi Tosun',
            'logoHtml' => CompanyEmailLogoService::HTML_PLACEHOLDER,
        ])->render();
        // Logo slot filled → company name kicker omitted (logo is the brand mark).
        $this->assertStringNotContainsString('Taxi Tosun</p>', $tenantWithLogo);

        $tenantWithoutLogo = view('emails.contact-acknowledgement', [
            'greetingName' => 'Jan',
            'companyName' => 'Taxi Tosun',
            'logoHtml' => '',
        ])->render();
        $this->assertStringContainsString('Taxi Tosun</p>', $tenantWithoutLogo);
        $this->assertStringContainsString('font-size:22px', $tenantWithoutLogo);
    }

    #[Test]
    public function tenant_taxi_templates_use_company_logo_in_light_header(): void
    {
        $login = app(TaxiCustomerLoginCodeEmailTemplateService::class)->defaultHtmlContent();
        $this->assertStringContainsString('{{ COMPANY_LOGO }}', $login);
        $this->assertStringContainsString('background-color:#e5e7eb', $login);
        $this->assertStringContainsString('color:#0f172a', $login);
        $this->assertStringNotContainsString('bgcolor="#0f172a"', $login);
        $this->assertStringContainsString('https://nexasuite.nl', $login);

        $appLogin = app(TaxiAppLoginCodeEmailTemplateService::class)->defaultHtmlContent();
        $this->assertStringContainsString('{{ COMPANY_LOGO }}', $appLogin);
        $this->assertStringContainsString('background-color:#e5e7eb', $appLogin);
        $this->assertStringNotContainsString('bgcolor="#0f172a"', $appLogin);
        $this->assertStringNotContainsString('{{ NEXA_LOGO }}', $appLogin);

        $accepted = app(TaxiCustomerAcceptEmailTemplateService::class)->ensureGlobalTemplateExists();
        $this->assertStringContainsString('{{ COMPANY_LOGO }}', (string) $accepted->html_content);
        $this->assertStringContainsString('background-color:#e5e7eb', (string) $accepted->html_content);
        $this->assertStringNotContainsString('bgcolor="#0f172a"', (string) $accepted->html_content);
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
        $this->assertStringContainsString('background-color:#e5e7eb', (string) $tenant->html_content);
        $this->assertStringNotContainsString('bgcolor="#0f172a"', (string) $tenant->html_content);
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
        $this->assertStringContainsString('background-color:#e5e7eb', $html);
        $this->assertStringContainsString('color:#0f172a', $html);
        $this->assertStringNotContainsString('bgcolor="#0f172a"', $html);
        $this->assertStringContainsString('Beste klant', $html);
        $this->assertStringContainsString('https://nexasuite.nl', $html);
        $this->assertStringContainsString('text-decoration:underline', $html);
        $this->assertStringNotContainsString('NEXA Suite</p>', $html);
    }

    #[Test]
    public function questions_footer_uses_tenant_name_and_optional_website_link(): void
    {
        $plain = EmailCardHtml::questionsAndPoweredByFooter(
            EmailCardHtml::companyNameHtml('Taxi Royaal', null)
        );
        $this->assertStringContainsString('Vragen? Neem contact op met Taxi Royaal.', $plain);
        $this->assertStringContainsString('https://nexasuite.nl', $plain);

        $linked = EmailCardHtml::questionsAndPoweredByFooter(
            EmailCardHtml::companyNameHtml('Taxi Royaal', 'royaaltaxi.nl')
        );
        $this->assertStringContainsString('href="https://royaaltaxi.nl"', $linked);
        $this->assertStringContainsString('>Taxi Royaal</a>', $linked);
    }

    #[Test]
    public function company_kicker_only_when_logo_slot_is_empty(): void
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

        $withLogo = EmailCardHtml::wrap(
            'Onderwerp',
            'Kop',
            '<p>Inhoud</p>',
            EmailCardHtml::companyLogoMarkup(),
            EmailCardHtml::poweredByFooter(),
            'Taxi Tosun',
        );
        $this->assertStringNotContainsString('Taxi Tosun</p>', $withLogo);

        $withoutLogo = EmailCardHtml::wrap(
            'Onderwerp',
            'Kop',
            '<p>Inhoud</p>',
            '',
            EmailCardHtml::poweredByFooter(),
            'Taxi Tosun',
        );
        $this->assertStringContainsString('Taxi Tosun</p>', $withoutLogo);
        $this->assertStringContainsString('font-size:22px', $withoutLogo);
    }

    #[Test]
    public function strip_company_name_kickers_keeps_muted_body_copy(): void
    {
        $html = '{{ NEXA_LOGO }}'
            .'<p style="margin:0 0 6px;color:#94a3b8;font-size:13px;letter-spacing:0.04em;">Taxi Tosun</p>'
            .'<p style="margin:28px 0 0;font-size:12px;line-height:1.5;color:#94a3b8;">Geen abonnement afnemen?<br>'
            .'<a href="https://example.test/stop" style="color:#94a3b8;text-decoration:underline;">Proefperiode beëindigen</a></p>';

        $stripped = EmailCardHtml::stripRedundantBrandKickerHtml($html);

        $this->assertStringNotContainsString('Taxi Tosun</p>', $stripped);
        $this->assertStringContainsString('Proefperiode beëindigen', $stripped);
        $this->assertStringContainsString('Geen abonnement afnemen?', $stripped);
    }
}
