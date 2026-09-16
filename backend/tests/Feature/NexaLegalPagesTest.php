<?php

namespace Tests\Feature;

use App\Listeners\AddLegalLinksToOutgoingMail;
use App\Models\FrontendTheme;
use App\Services\CentralWelcomePageService;
use App\Support\NexaLegalLinks;
use Illuminate\Mail\Events\MessageSending;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class NexaLegalPagesTest extends TestCase
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
        app(CentralWelcomePageService::class)->ensureMarketingPagesExist();
    }

    #[Test]
    public function voorwaarden_page_is_on_the_marketing_site_without_skillmatching_copy(): void
    {
        $this->get('http://localhost:8085/voorwaarden')
            ->assertOk()
            ->assertSee('Algemene voorwaarden', false)
            ->assertSee('NEXA Suite', false)
            ->assertDontSee('NEXA Skillmatching', false)
            ->assertDontSee('vacatures', false);
    }

    #[Test]
    public function voorwaarden_explain_marketplace_fee_only_for_nexasuite_nl_rides(): void
    {
        \App\Models\NexaSuiteMarketplaceSetting::current()->update(['fee_percent' => 11]);

        $this->get('http://localhost:8085/voorwaarden')
            ->assertOk()
            ->assertSee('Ritten via nexasuite.nl', false)
            ->assertSee('11%', false)
            ->assertSee('niet</strong> voor ritten via jouw eigen website', false)
            ->assertSee('maandabonnement', false)
            ->assertSee('dichtstbijzijnde aangesloten taxibedrijf', false)
            ->assertDontSee('per tenant', false)
            ->assertDontSee('jouw tenant', false)
            ->assertDontSee('tenantomgeving', false);
    }

    #[Test]
    public function disclaimer_page_is_available(): void
    {
        $this->get('http://localhost:8085/disclaimer')
            ->assertOk()
            ->assertSee('Disclaimer', false)
            ->assertSee('NEXA Suite', false);
    }

    #[Test]
    public function old_terms_url_redirects_to_voorwaarden(): void
    {
        $this->get('http://localhost:8085/terms')
            ->assertRedirect('/voorwaarden');
    }

    #[Test]
    public function marketing_footer_contains_legal_links_and_not_menu_labels_as_nav_only(): void
    {
        $html = $this->get('http://localhost:8085/taxi')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('href="http://localhost:8085/voorwaarden"', $html);
        $this->assertStringContainsString('href="http://localhost:8085/disclaimer"', $html);
        $this->assertStringContainsString('>Voorwaarden</a>', $html);
        $this->assertStringContainsString('>Disclaimer</a>', $html);
    }

    #[Test]
    public function marketing_pages_do_not_show_the_word_tenant(): void
    {
        foreach (['/', '/taxi', '/prijzen', '/voorwaarden', '/website', '/contractvervoer'] as $path) {
            $this->get('http://localhost:8085'.$path)
                ->assertOk()
                ->assertDontSee('per tenant', false)
                ->assertDontSee('tenant-site', false)
                ->assertDontSee('Multi-tenant', false)
                ->assertDontSee('jouw tenant', false)
                ->assertDontSee('Elke tenant', false);
        }
    }

    #[Test]
    public function outgoing_html_mail_gets_quiet_legal_links(): void
    {
        $email = (new Email)
            ->html('<html><body><p>Hallo</p></body></html>')
            ->text('Hallo');
        $event = new MessageSending($email);

        (new AddLegalLinksToOutgoingMail)->handle($event);

        $html = (string) $email->getHtmlBody();
        $text = (string) $email->getTextBody();
        $this->assertStringContainsString(NexaLegalLinks::MARKER, $html);
        $this->assertStringContainsString('Algemene voorwaarden', $html);
        $this->assertStringContainsString('#9ca3af', $html);
        $this->assertStringContainsString('/voorwaarden', $html);
        $this->assertStringContainsString('/disclaimer', $text);
        $this->assertSame($html, NexaLegalLinks::ensureHtmlFooter($html));
    }
}
