<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\GeneralSetting;
use App\Services\CompanyEmailLogoService;
use App\Services\EmailTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyEmailLogoServiceTest extends TestCase
{
    use RefreshDatabase;

    /** 1×1 PNG */
    private const TINY_PNG = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\nIDATx\x9cc\x00\x01\x00\x00\x05\x00\x01\r\n-\xb4\x00\x00\x00\x00IEND\xaeB`\x82";

    protected function setUp(): void
    {
        parent::setUp();
        GeneralSetting::clearRequestCache();
    }

    #[Test]
    public function it_resolves_logo_bytes_from_general_settings_for_tenant(): void
    {
        Storage::fake('public');
        $company = Company::query()->create(['name' => 'Taxi BV']);
        Storage::disk('public')->put('settings/test-logo.png', self::TINY_PNG);
        GeneralSetting::set('logo', 'settings/test-logo.png', $company->id);

        $payload = app(CompanyEmailLogoService::class)->resolveLogoPayload($company->id);

        $this->assertNotNull($payload);
        $this->assertSame('image/png', $payload['mime']);
        $this->assertSame(self::TINY_PNG, $payload['data']);
    }

    #[Test]
    public function it_parses_global_taxi_template_with_tenant_logo_placeholder(): void
    {
        Storage::fake('public');
        $company = Company::query()->create(['name' => 'Taxi BV']);
        Storage::disk('public')->put('settings/logo.png', self::TINY_PNG);
        GeneralSetting::set('logo', 'settings/logo.png', $company->id);

        $template = EmailTemplate::query()->create([
            'name' => 'Rit geaccepteerd',
            'type' => 'taxi_ride_accepted',
            'company_id' => null,
            'subject' => 'Test',
            'html_content' => '<div>{{ COMPANY_LOGO }}</div><p>{{ COMPANY_NAME }}</p>',
            'is_active' => true,
        ]);

        $vars = array_merge(
            ['COMPANY_NAME' => 'Taxi BV', 'CUSTOMER_NAME' => 'Jan'],
            app(CompanyEmailLogoService::class)->templateVariable($company->id, 'Taxi BV')
        );

        $html = app(EmailTemplateService::class)->parseTemplateVariables($template->html_content, $vars);

        $this->assertStringNotContainsString('{{ COMPANY_LOGO }}', $html);
        $this->assertStringContainsString(CompanyEmailLogoService::HTML_PLACEHOLDER, $html);
        $this->assertStringContainsString('Taxi BV', $html);
    }

    #[Test]
    public function it_embeds_logo_inline_in_html_for_mail_clients(): void
    {
        Storage::fake('public');
        $company = Company::query()->create(['name' => 'Taxi Co']);
        Storage::disk('public')->put('settings/co.png', self::TINY_PNG);
        GeneralSetting::set('logo', 'settings/co.png', $company->id);

        $html = '<header>'.CompanyEmailLogoService::HTML_PLACEHOLDER.'</header>';

        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('embedData')
            ->with(self::TINY_PNG, 'company-logo', 'image/png')
            ->willReturn('cid:test-logo');

        $result = app(CompanyEmailLogoService::class)->embedInHtml($html, $message, $company->id, 'Taxi Co');

        $this->assertStringContainsString('src="cid:test-logo"', $result);
        $this->assertStringNotContainsString(CompanyEmailLogoService::HTML_PLACEHOLDER, $result);
    }

    #[Test]
    public function email_embed_prefers_light_logo_for_white_header(): void
    {
        $darkPng = self::TINY_PNG;
        // Slightly different payload so we can assert which blob was embedded.
        $lightPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        $this->assertNotFalse($lightPng);
        $this->assertNotSame($darkPng, $lightPng);

        $company = Company::query()->create([
            'name' => 'Taxi Light Header',
            'logo_blob' => base64_encode($lightPng),
            'logo_mime_type' => 'image/png',
            'logo_dark_blob' => base64_encode($darkPng),
            'logo_dark_mime_type' => 'image/png',
        ]);

        $html = '<td bgcolor="#ffffff">'.CompanyEmailLogoService::HTML_PLACEHOLDER
            .'<p style="margin:0 0 10px;color:#ffffff;font-size:22px;line-height:1.25;font-weight:700;">Taxi Light Header</p></td>';
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('embedData')
            ->with($lightPng, 'company-logo', 'image/png')
            ->willReturn('cid:light-logo');

        $result = app(CompanyEmailLogoService::class)->embedInHtml($html, $message, $company->id, 'Taxi Light Header');

        $this->assertStringContainsString('src="cid:light-logo"', $result);
        $this->assertStringNotContainsString('Taxi Light Header</p>', $result);
        $this->assertStringNotContainsString(
            'variant=dark',
            (string) app(CompanyEmailLogoService::class)->adminPreviewLogoUrl($company->id)
        );
        $this->assertStringContainsString(
            'variant=dark',
            (string) app(CompanyEmailLogoService::class)->adminPreviewLogoUrl($company->id, true)
        );
    }

    #[Test]
    public function it_strips_legacy_grey_company_kicker_after_logo_embed(): void
    {
        Storage::fake('public');
        $company = Company::query()->create(['name' => 'Taxi Tosun']);
        Storage::disk('public')->put('settings/co.png', self::TINY_PNG);
        GeneralSetting::set('logo', 'settings/co.png', $company->id);

        // Stored/resend HTML still has the old grey kicker under the logo placeholder.
        $html = '<td bgcolor="#0f172a">'
            .CompanyEmailLogoService::HTML_PLACEHOLDER
            .'<p style="margin:0 0 6px;color:#94a3b8;font-size:13px;letter-spacing:0.04em;">Taxi Tosun</p>'
            .'<h1>Bevestiging taxiboeking #311</h1>'
            .'</td>';

        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('embedData')
            ->willReturn('cid:test-logo');

        $result = app(CompanyEmailLogoService::class)->embedInHtml($html, $message, $company->id, 'Taxi Tosun');

        $this->assertStringContainsString('src="cid:test-logo"', $result);
        $this->assertStringNotContainsString('Taxi Tosun</p>', $result);
        $this->assertStringNotContainsString('#94a3b8', $result);
    }

    #[Test]
    public function it_falls_back_to_company_name_when_no_logo(): void
    {
        Storage::fake('public');
        $company = Company::query()->create(['name' => 'Zonder Logo BV']);

        $vars = app(CompanyEmailLogoService::class)->templateVariable($company->id, 'Zonder Logo BV');

        $this->assertStringContainsString('Zonder Logo BV', $vars['COMPANY_LOGO']);
        $this->assertStringContainsString('font-size:22px', $vars['COMPANY_LOGO']);

        $html = '<td bgcolor="#0f172a">'.CompanyEmailLogoService::HTML_PLACEHOLDER.'</td>';
        $message = $this->createMock(Message::class);
        $message->expects($this->never())->method('embedData');

        $result = app(CompanyEmailLogoService::class)->embedInHtml($html, $message, $company->id, 'Zonder Logo BV');

        $this->assertStringContainsString('Zonder Logo BV</p>', $result);
        $this->assertStringContainsString('color:#0f172a', $result);
        $this->assertStringNotContainsString('bgcolor="#0f172a"', $result);
    }

    #[Test]
    public function pwa_logo_urls_include_dark_variant_when_dark_blob_exists(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Logo',
            'logo_blob' => base64_encode(self::TINY_PNG),
            'logo_mime_type' => 'image/png',
            'logo_dark_blob' => base64_encode(self::TINY_PNG),
            'logo_dark_mime_type' => 'image/png',
        ]);

        $urls = app(CompanyEmailLogoService::class)->pwaLogoUrls($company->id);

        $this->assertNotNull($urls['light']);
        $this->assertNotNull($urls['dark']);
        $this->assertStringContainsString('/email-logo/'.$company->id, $urls['light']);
        $this->assertStringContainsString('variant=dark', $urls['dark']);
        $this->assertStringNotContainsString('variant=dark', $urls['light']);
    }

    #[Test]
    public function dark_email_logo_route_serves_dark_blob(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Dark Logo',
            'logo_blob' => base64_encode(self::TINY_PNG),
            'logo_mime_type' => 'image/png',
            'logo_dark_blob' => base64_encode(self::TINY_PNG),
            'logo_dark_mime_type' => 'image/png',
        ]);

        $this->get(route('email.company-logo', ['company' => $company->id, 'variant' => 'dark']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }
}
