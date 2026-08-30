<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\InfoRequestController;
use App\Models\EmailTemplate;
use App\Models\FrontendTheme;
use App\Services\CentralWelcomePageService;
use App\Services\EmailTemplateService;
use App\Services\NexaContactAanvraagEmailTemplateService;
use Database\Seeders\InfoRequestFormFieldSeeder;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CentralContactPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://localhost:8085',
            'tenancy.central_domains' => ['localhost'],
        ]);

        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        (new InfoRequestFormFieldSeeder)->run();
        app(CentralWelcomePageService::class)->ensureMarketingPagesExist();
    }

    #[Test]
    public function contact_page_is_available_on_localhost(): void
    {
        $this->get('http://localhost:8085/contact')
            ->assertOk()
            ->assertSee('Nieuwe aanvraag', false)
            ->assertSee('name="voornaam"', false)
            ->assertSee('name="pakket"', false)
            ->assertSee('Geen pakket geselecteerd', false)
            ->assertSee('name="template_id"', false)
            ->assertDontSee('Website (laat leeg)', false);
    }

    #[Test]
    public function contact_page_prefills_package_and_message_from_query(): void
    {
        $this->get('http://localhost:8085/contact?pakket=Start')
            ->assertOk()
            ->assertSee('name="pakket"', false)
            ->assertSee('value="Start" selected', false)
            ->assertSee('Ik ben geïnteresseerd in het pakket Start.', false);
    }

    #[Test]
    public function contact_template_is_global_and_sends_to_nexa_inbox(): void
    {
        $template = EmailTemplate::query()
            ->where('type', 'informatieaanvraag')
            ->whereNull('company_id')
            ->first();

        $this->assertNotNull($template);
        $this->assertSame(NexaContactAanvraagEmailTemplateService::TEMPLATE_NAME, $template->name);
        $this->assertSame(NexaContactAanvraagEmailTemplateService::RECIPIENT_EMAIL, $template->recipient_email);
        $this->assertSame('email', $template->recipient_type);
    }

    #[Test]
    public function contact_form_sends_selected_package_in_email(): void
    {
        $template = EmailTemplate::query()
            ->where('type', 'informatieaanvraag')
            ->whereNull('company_id')
            ->first();
        $this->assertNotNull($template);

        $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenantFromHost::class,
            \App\Http\Middleware\TenantMiddleware::class,
        ])->postJson(route('frontend.send-info-request'), array_merge(InfoRequestController::formTimeFields(time() - 5), [
            'template_id' => $template->id,
            'company_website' => '',
            'voornaam' => 'Jan',
            'achternaam' => 'Jansen',
            'email_aanvraag' => 'jan@example.com',
            'telefoonnummer' => '0612345678',
            'pakket' => 'Start',
            'omschrijving' => 'Ik ben geïnteresseerd in het pakket Start. Neem gerust contact met me op over de mogelijkheden en hoe we kunnen starten.',
        ]))->assertOk()->assertJsonPath('success', true);

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertNotEmpty($messages);
        $html = (string) $messages->last()->getOriginalMessage()->getHtmlBody();
        $this->assertStringContainsString('Start', $html);
        $this->assertStringContainsString('Pakket', $html);
        $this->assertStringNotContainsString(EmailTemplateService::TEMPLATE_SAMPLE_NOTICE, $html);
    }

    #[Test]
    public function contact_form_submits_to_info_nexasuite(): void
    {
        Mail::fake();

        $template = EmailTemplate::query()
            ->where('type', 'informatieaanvraag')
            ->whereNull('company_id')
            ->first();
        $this->assertNotNull($template);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenantFromHost::class,
            \App\Http\Middleware\TenantMiddleware::class,
        ])->postJson(route('frontend.send-info-request'), array_merge(InfoRequestController::formTimeFields(time() - 5), [
            'template_id' => $template->id,
            'company_website' => '',
            'voornaam' => 'Jan',
            'achternaam' => 'Jansen',
            'email_aanvraag' => 'jan@example.com',
            'telefoonnummer' => '0612345678',
            'omschrijving' => 'Ik wil NEXA Suite voor mijn taxibedrijf.',
        ]));

        $response->assertOk()->assertJsonPath('success', true);
    }

    #[Test]
    public function contact_email_uses_nexa_saas_as_sender_name(): void
    {
        config(['mail.from.name' => 'NEXA']);

        $template = EmailTemplate::query()
            ->where('type', 'informatieaanvraag')
            ->whereNull('company_id')
            ->first();
        $this->assertNotNull($template);

        app(EmailTemplateService::class)->sendTestEmail(
            $template,
            'ontvanger@example.com',
            'Ontvanger',
            [
                'VOORNAAM' => 'Jan',
                'ACHTERNAAM' => 'Jansen',
                'EMAIL_AANVRAAG' => 'jan@example.com',
            ]
        );

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertNotEmpty($messages);
        $from = $messages->last()->getOriginalMessage()->getFrom();
        $this->assertSame(NexaContactAanvraagEmailTemplateService::FROM_NAME, $from[0]->getName());
    }
}
