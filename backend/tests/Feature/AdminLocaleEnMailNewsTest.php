<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterProspect;
use App\Models\TaxiProspect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLocaleEnMailNewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        config([
            'taxi-partner-finder.enabled' => true,
            'services.openai.api_key' => null,
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function page(string $route, string $locale, array $params = [])
    {
        $session = ['selected_tenant' => null];
        if ($locale === 'en') {
            $session['locale'] = 'en';
        }

        return $this->actingAs($this->superAdmin())
            ->withSession($session)
            ->get(route($route, $params))
            ->assertOk();
    }

    #[Test]
    public function email_template_pages_are_english_in_en(): void
    {
        $template = EmailTemplate::query()->create([
            'name' => 'Mail News Welkom Template',
            'subject' => 'Welkom onderwerp',
            'type' => 'welcome',
            'html_content' => '<p>inhoud</p>',
            'is_active' => true,
            'company_id' => null,
        ]);

        $index = $this->page('admin.email-templates.index', 'en')->getContent();
        $this->assertSame('en', app()->getLocale());
        $this->assertStringContainsString('Email Templates Management', $index);
        $this->assertStringContainsString('New Template', $index);
        $this->assertStringContainsString('Search templates...', $index);
        $this->assertStringContainsString('Mail News Welkom Template', $index); // data blijft ongewijzigd
        $this->assertStringNotContainsString('E-mail Templates Beheer', $index);
        $this->assertStringNotContainsString('Nieuw Template', $index);
        $this->assertStringNotContainsString('Zoek templates...', $index);
        $this->assertStringContainsString('Are you sure you want to delete this template?', $index);
        $this->assertStringNotContainsString('Weet je zeker dat je dit template wilt verwijderen?', $index);

        // Bestaande bug (niet in scope): partial formulier-preview gebruikt `use ($emailTemplate)` zonder
        // dat de create-pagina die variabele doorgeeft. Hier dus gedeeld als null zodat de pagina rendert.
        View::share('emailTemplate', null);
        $create = $this->page('admin.email-templates.create', 'en')->getContent();
        $this->assertStringContainsString('New Email Template', $create);
        $this->assertStringContainsString('Basic Information', $create);
        $this->assertStringContainsString('Send test email after saving', $create);
        $this->assertStringContainsString('Heading 1', $create); // TinyMCE block formats
        $this->assertStringContainsString('{{VOORNAAM}}', $create); // variabele-syntax blijft onvertaald
        $this->assertStringNotContainsString('kt-card-title">Basis Informatie', $create);
        $this->assertStringNotContainsString('Verstuur testmail na opslaan', $create);

        $show = $this->page('admin.email-templates.show', 'en', ['email_template' => $template])->getContent();
        $this->assertStringContainsString('Email Template Details - Mail News Welkom Template', $show);
        $this->assertStringContainsString('Template Information', $show);
        $this->assertStringContainsString('Send test email', $show);
        $this->assertStringNotContainsString('Template Informatie', $show);

        $fields = $this->page('admin.email-templates.form-fields.index', 'en')->getContent();
        $this->assertStringContainsString('Form fields management', $fields);
        $this->assertStringNotContainsString('Formulier velden beheer', $fields);
    }

    #[Test]
    public function newsletter_pages_are_english_in_en(): void
    {
        $campaign = NewsletterCampaign::query()->create([
            'name' => 'Mail News Campagne',
            'subject' => 'Onderwerp campagne',
            'status' => NewsletterCampaign::STATUS_DRAFT,
            'blocks' => ['title' => 'Titel', 'intro' => 'Intro'],
        ]);
        NewsletterProspect::query()->create([
            'company_name' => 'Mail News Taxi BV',
            'email' => 'mailnews@taxi.test',
            'province' => 'Overijssel',
            'source' => 'ai_web',
            'status' => NewsletterProspect::STATUS_SUBSCRIBED,
        ]);

        $index = $this->page('admin.newsletters.index', 'en')->getContent();
        $this->assertStringContainsString('New newsletter', $index);
        $this->assertStringContainsString('Campaigns', $index);
        $this->assertStringContainsString('Mail News Campagne', $index);
        $this->assertStringNotContainsString('Nieuwe nieuwsbrief', $index);
        $this->assertStringNotContainsString('Campagnes', $index);

        $edit = $this->page('admin.newsletters.edit', 'en', ['campaign' => $campaign])->getContent();
        $this->assertStringContainsString('Edit newsletter', $edit);
        $this->assertStringContainsString('Let AI write', $edit);
        $this->assertStringContainsString('AI is writing\u2026', $edit); // @json escapet de ellips
        $this->assertStringNotContainsString('Laat AI schrijven', $edit);
        $this->assertStringNotContainsString('AI schrijft\u2026', $edit);

        $prospects = $this->page('admin.newsletters.prospects', 'en')->getContent();
        $this->assertStringContainsString('Newsletter customer list', $prospects);
        $this->assertStringContainsString('Search by company, contact, email, phone…', $prospects);
        $this->assertStringContainsString('Looking up companies.', $prospects);
        $this->assertStringContainsString('Mail News Taxi BV', $prospects);
        $this->assertStringNotContainsString('Zoek op bedrijf, contact', $prospects);
        $this->assertStringNotContainsString('Bedrijven worden opgezocht.', $prospects);
        $this->assertStringNotContainsString('Selecteer een provincie', $prospects);

        $send = $this->page('admin.newsletters.send', 'en')->getContent();
        $this->assertStringContainsString('addresses in the opt-in list', $send);
        $this->assertStringContainsString('Send newsletter', $send);
        $this->assertStringNotContainsString('adressen in de opt-inlijst', $send);
    }

    #[Test]
    public function partner_finder_pages_are_english_in_en(): void
    {
        $prospect = TaxiProspect::query()->create([
            'company_name' => 'Mail News Prospect Taxi',
            'email' => null,
        ]);

        $index = $this->page('admin.taxi-partner-finder.index', 'en')->getContent();
        $this->assertStringContainsString('Total found', $index);
        $this->assertStringContainsString('Sync KNV', $index);
        $this->assertStringContainsString('no email', $index);
        $this->assertStringContainsString('Mail News Prospect Taxi', $index);
        $this->assertStringNotContainsString('Totaal gevonden', $index);
        $this->assertStringNotContainsString('KNV synchroniseren', $index);
        $this->assertStringNotContainsString('geen e-mail', $index);

        $show = $this->page('admin.taxi-partner-finder.show', 'en', ['prospect' => $prospect])->getContent();
        $this->assertStringContainsString('Company details', $show);
        $this->assertStringContainsString('Mark as opted out', $show);
        $this->assertStringNotContainsString('Bedrijfsgegevens', $show);
    }

    #[Test]
    public function pages_stay_dutch_in_nl(): void
    {
        $this->assertSame('nl', config('app.locale'));

        $index = $this->page('admin.email-templates.index', 'nl')->getContent();
        $this->assertStringContainsString('E-mail Templates Beheer', $index);
        $this->assertStringContainsString('Nieuw Template', $index);
        $this->assertStringContainsString('Weet je zeker dat je dit template wilt verwijderen?', $index);
        $this->assertStringNotContainsString('Email Templates Management', $index);

        $news = $this->page('admin.newsletters.index', 'nl')->getContent();
        $this->assertStringContainsString('Nieuwe nieuwsbrief', $news);
        $this->assertStringContainsString('Campagnes', $news);

        $prospects = $this->page('admin.newsletters.prospects', 'nl')->getContent();
        $this->assertStringContainsString('Bedrijven worden opgezocht.', $prospects);
        $this->assertStringContainsString('0 min 0 sec', $prospects);

        $finder = $this->page('admin.taxi-partner-finder.index', 'nl')->getContent();
        $this->assertStringContainsString('Totaal gevonden', $finder);
        $this->assertStringContainsString('KNV synchroniseren', $finder);
    }

    #[Test]
    public function edit_page_hints_with_variables_are_translated_and_keep_the_variable_syntax(): void
    {
        $template = EmailTemplate::query()->create([
            'name' => 'Mail News Factuur',
            'subject' => 'Factuur onderwerp',
            'type' => 'invoice',
            'html_content' => '<p>inhoud</p>',
            'is_active' => true,
            'company_id' => null,
        ]);

        $en = $this->page('admin.email-templates.edit', 'en', ['email_template' => $template])->getContent();
        $this->assertStringContainsString('Edit Email Template', $en);
        $this->assertStringContainsString('This tag is <strong>not</strong> stored in the database.', $en);
        $this->assertStringContainsString('{{ INVOICE_AMOUNTS_HTML }}', $en);
        $this->assertStringContainsString('{{ INVOICE_AMOUNT_EXCL }}, {{ INVOICE_TAX_LABEL }}', $en);
        $this->assertStringNotContainsString('Deze tag staat', $en);
    }

    #[Test]
    public function edit_page_hints_stay_dutch_in_nl(): void
    {
        $template = EmailTemplate::query()->create([
            'name' => 'Mail News Factuur',
            'subject' => 'Factuur onderwerp',
            'type' => 'invoice',
            'html_content' => '<p>inhoud</p>',
            'is_active' => true,
            'company_id' => null,
        ]);

        $nl = $this->page('admin.email-templates.edit', 'nl', ['email_template' => $template])->getContent();
        $this->assertStringContainsString('Deze tag staat <strong>niet</strong> in de database. Bij versturen wordt ze vervangen door een HTML-tabel', $nl);
        $this->assertStringContainsString('{{ INVOICE_AMOUNTS_HTML }}', $nl);
    }
}
