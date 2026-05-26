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
    public function it_falls_back_to_company_name_when_no_logo(): void
    {
        Storage::fake('public');
        $company = Company::query()->create(['name' => 'Zonder Logo BV']);

        $vars = app(CompanyEmailLogoService::class)->templateVariable($company->id, 'Zonder Logo BV');

        $this->assertStringContainsString('<strong>Zonder Logo BV</strong>', $vars['COMPANY_LOGO']);
    }
}
