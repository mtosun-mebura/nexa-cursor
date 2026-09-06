<?php

namespace Tests\Unit;

use App\Models\EmailTemplate;
use App\Services\CompanyEmailLogoService;
use App\Services\TenantWelcomeEmailTemplateService;
use App\Support\NexaBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantWelcomeEmailTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function default_html_contains_nexa_logo_variable_and_readable_buttons(): void
    {
        $template = app(TenantWelcomeEmailTemplateService::class)->ensureExists();

        $this->assertStringContainsString('{{ NEXA_LOGO }}', (string) $template->html_content);
        $this->assertStringNotContainsString('NEXA Suite</p>', (string) $template->html_content);
        $this->assertStringContainsString('<span style="color: #ffffff;">Open de admin</span>', (string) $template->html_content);
        $this->assertContains('NEXA_LOGO', array_keys(TenantWelcomeEmailTemplateService::variableLabels()));
    }

    #[Test]
    public function existing_template_gets_nexa_logo_placeholder(): void
    {
        EmailTemplate::query()->create([
            'type' => TenantWelcomeEmailTemplateService::TYPE,
            'company_id' => null,
            'name' => TenantWelcomeEmailTemplateService::TEMPLATE_NAME,
            'subject' => 'Welkom',
            'html_content' => '<td bgcolor="#0f172a"><p style="color: #94a3b8;">NEXA Suite</p><h1>Welkom bij NEXA</h1></td><a href="#" style="background-color: #2563eb; color: #ffffff;">Open de admin</a>',
            'is_active' => true,
        ]);

        $template = app(TenantWelcomeEmailTemplateService::class)->ensureExists();

        $this->assertStringContainsString('{{ NEXA_LOGO }}', (string) $template->html_content);
        $this->assertStringNotContainsString('NEXA Suite</p>', (string) $template->html_content);
        $this->assertStringContainsString('<span style="color: #ffffff;">Open de admin</span>', (string) $template->html_content);
    }

    #[Test]
    public function preview_injection_replaces_nexa_logo_with_img(): void
    {
        $html = '<div>{{ NEXA_LOGO }}</div>';
        $result = app(CompanyEmailLogoService::class)->injectPreviewLogoIntoHtml($html, null, 'NEXA', true);

        $this->assertStringNotContainsString('{{ NEXA_LOGO }}', $result);
        $this->assertStringNotContainsString(NexaBranding::EMAIL_LOGO_PLACEHOLDER, $result);
        $this->assertStringContainsString('nexa-email-logo', $result);
        $this->assertStringContainsString('alt="NEXA Suite"', $result);
        $this->assertStringContainsString('nexa-logo-dark.png', $result);
        $this->assertStringContainsString('<img', $result);
    }

    #[Test]
    public function preview_variables_use_business_package_and_sample_name(): void
    {
        $vars = app(TenantWelcomeEmailTemplateService::class)->previewVariables();

        $this->assertSame('Lisa Vermeer', $vars['USER_NAME']);
        $this->assertSame('Business', $vars['PACKAGE_NAME']);
        $this->assertStringContainsString('Contractvervoer', $vars['PACKAGE_FEATURES_HTML']);
        $this->assertStringContainsString('<li>', $vars['PACKAGE_FEATURES_HTML']);
        $this->assertStringContainsString('Contractvervoer', $vars['PACKAGE_FEATURES_TEXT']);
    }

    #[Test]
    public function preview_html_replaces_package_features_placeholder(): void
    {
        $template = app(TenantWelcomeEmailTemplateService::class)->ensureExists();
        $vars = app(TenantWelcomeEmailTemplateService::class)->previewVariables();
        $html = app(\App\Services\EmailTemplateService::class)->parseTemplateVariables(
            (string) $template->html_content,
            $vars
        );

        $this->assertStringNotContainsString('PACKAGE_FEATURES_HTML', $html);
        $this->assertStringContainsString('Wat zit er in je pakket', $html);
        $this->assertStringContainsString('Lisa Vermeer', $html);
        $this->assertStringContainsString('Business', $html);
        $this->assertStringContainsString('Contractvervoer', $html);
    }
}
