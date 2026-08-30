<?php

namespace Tests\Unit;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use App\Services\EnvService;
use App\Services\TenantWelcomeEmailTemplateService;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailTemplateSampleNoticeTest extends TestCase
{
    private function keepArrayMailer(): void
    {
        config([
            'mail.default' => 'array',
            'mail.from.address' => 'noreply@example.com',
            'mail.from.name' => 'NEXA Test',
        ]);
        $this->mock(EnvService::class, function ($mock) {
            $mock->shouldReceive('applyMailConfigToRuntime');
            $mock->shouldReceive('applyPlatformMailConfigToRuntime');
            $mock->shouldReceive('resolveMailFromHeaders')->andReturn([
                'from_address' => 'noreply@example.com',
                'from_name' => 'NEXA Test',
            ]);
        });
    }

    #[Test]
    public function mark_as_template_sample_prefixes_subject_and_injects_banner(): void
    {
        $service = app(EmailTemplateService::class);
        [$subject, $html, $text] = $service->markAsTemplateSample(
            'Welkom bij NEXA Suite',
            '<html><body><p>Beste Lisa</p></body></html>',
            'Beste Lisa'
        );

        $this->assertSame('[Voorbeeld] Welkom bij NEXA Suite', $subject);
        $this->assertStringContainsString(EmailTemplateService::TEMPLATE_SAMPLE_NOTICE, $html);
        $this->assertStringContainsString('data-nexa-template-sample="1"', $html);
        $this->assertStringContainsString('<p>Beste Lisa</p>', $html);
        $this->assertStringStartsWith(EmailTemplateService::TEMPLATE_SAMPLE_NOTICE, $text);
    }

    #[Test]
    public function admin_test_mail_includes_sample_notice(): void
    {
        $this->keepArrayMailer();
        $template = app(TenantWelcomeEmailTemplateService::class)->ensureExists();

        app(EmailTemplateService::class)->sendTestEmail(
            $template,
            'test@example.com',
            'Tester',
            [],
            asTemplateSample: true
        );

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertNotEmpty($messages);
        $message = $messages->last()->getOriginalMessage();
        $this->assertStringStartsWith('[Voorbeeld] ', $message->getSubject());
        $this->assertStringContainsString(EmailTemplateService::TEMPLATE_SAMPLE_NOTICE, (string) $message->getHtmlBody());
        $this->assertStringContainsString(EmailTemplateService::TEMPLATE_SAMPLE_NOTICE, (string) $message->getTextBody());
    }

    #[Test]
    public function regular_test_mail_send_has_no_sample_notice(): void
    {
        $this->keepArrayMailer();
        $template = EmailTemplate::query()->create([
            'type' => 'custom',
            'company_id' => null,
            'name' => 'Gewone mail',
            'subject' => 'Hallo {{ USER_NAME }}',
            'html_content' => '<html><body><p>Hallo {{ USER_NAME }}</p></body></html>',
            'text_content' => 'Hallo {{ USER_NAME }}',
            'is_active' => true,
        ]);

        app(EmailTemplateService::class)->sendTestEmail(
            $template,
            'echt@example.com',
            'Echte ontvanger'
        );

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertNotEmpty($messages);
        $message = $messages->last()->getOriginalMessage();
        $this->assertSame('Hallo Echte ontvanger', $message->getSubject());
        $this->assertStringNotContainsString(EmailTemplateService::TEMPLATE_SAMPLE_NOTICE, (string) $message->getHtmlBody());
        $this->assertStringNotContainsString('[Voorbeeld]', $message->getSubject());
    }
}
