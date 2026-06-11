<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Services\TaxiKnowledgeContentFormatterService;
use Tests\TestCase;

class TaxiKnowledgeContentFormatterServiceTest extends TestCase
{
    private TaxiKnowledgeContentFormatterService $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new TaxiKnowledgeContentFormatterService();
    }

    public function test_strips_ai_preamble_and_structures_articles(): void
    {
        $input = 'De onderstaande tekst is netjes opgemaakt. Gebaseerd op het aangeleverde document.'
            .'Artikel 1 – Begripsomschrijving In deze voorwaarden wordt verstaan onder taxivervoer.'
            .'Artikel 2 – Annulering Een rit kan kosteloos worden geannuleerd tot 24 uur voor vertrek.';

        $result = $this->formatter->format($input, shorten: false);

        $this->assertSame('full', $result['mode']);
        $this->assertStringNotContainsString('onderstaande tekst is netjes opgemaakt', $result['html']);
        $this->assertStringContainsString('<h3>Artikel 1', $result['html']);
        $this->assertStringContainsString('<h3>Artikel 2', $result['html']);
        $this->assertStringContainsString('kosteloos', $result['html']);
    }

    public function test_formats_faq_bullet_list_as_html_lists(): void
    {
        $input = "Veelgestelde vragen over de website\n"
            ."Overzicht van veelgebruikte onderdelen en links op de website:\n"
            ."- de pagina \"Contact\" – Snelle Links: Home\n"
            ."- de pagina \"Contact\" – Snelle Links: Vacatures\n"
            ."- de pagina \"Diensten\" – Luchthavenvervoer\n"
            ."\nGebruik dit overzicht om klanten te verwijzen naar diensten.";

        $result = $this->formatter->format(
            $input,
            shorten: false,
            title: 'Veelgestelde vragen over de website',
        );

        $this->assertStringContainsString('<h3>Veelgestelde vragen over de website</h3>', $result['html']);
        $this->assertStringContainsString('<ul>', $result['html']);
        $this->assertStringContainsString('<li>de pagina &quot;Contact&quot; – Snelle Links: Home</li>', $result['html']);
        $this->assertStringContainsString('veelgebruikte onderdelen', $result['html']);
    }

    public function test_short_faq_keeps_intro_and_top_bullets(): void
    {
        $input = "Veelgestelde vragen over de website\n"
            ."Overzicht van veelgebruikte onderdelen en links op de website:\n"
            ."- de pagina \"Contact\" – Snelle Links: Home\n"
            ."- de pagina \"Contact\" – Snelle Links: Vacatures\n"
            ."- de pagina \"Diensten\" – Luchthavenvervoer\n"
            ."- de pagina \"Privacy\" – privacyverklaring\n"
            ."- de pagina \"Voorwaarden\" – algemene voorwaarden\n"
            ."\nGebruik dit overzicht om klanten te verwijzen naar diensten, contact, privacy, voorwaarden of hulp op de website.";

        $short = $this->formatter->format($input, shorten: true, title: 'Veelgestelde vragen over de website');
        $full = $this->formatter->format($input, shorten: false, title: 'Veelgestelde vragen over de website');

        $this->assertStringContainsString('<ul>', $short['html']);
        $this->assertStringContainsString('<ul>', $full['html']);
        $this->assertLessThanOrEqual(3, substr_count($short['html'], '<li>'));
        $this->assertGreaterThan(substr_count($short['html'], '<li>'), substr_count($full['html'], '<li>'));
        $this->assertStringNotContainsString('Gebruik dit overzicht om klanten', $short['html']);
        $this->assertStringContainsString('Gebruik dit overzicht om klanten', $full['html']);
    }

    public function test_shorten_is_idempotent_for_already_short_content(): void
    {
        $shortHtml = '<p>Annuleren kan kosteloos tot 24 uur voor vertrek.</p><ul><li>Contact via de website</li></ul>';
        $first = $this->formatter->format($shortHtml, shorten: true, title: 'Annuleren', category: 'voorwaarden');
        $second = $this->formatter->format($first['html'], shorten: true, title: 'Annuleren', category: 'voorwaarden');

        $this->assertSame(mb_strlen(strip_tags($first['html'])), mb_strlen(strip_tags($second['html'])));
        $this->assertStringContainsString('kosteloos', $second['html']);
    }

    public function test_short_mode_prefers_relevant_sentences(): void
    {
        $input = 'Artikel 1 – Algemeen Dit document geldt voor alle ritten.'
            .'Artikel 9 – Annulering Een rit annuleren kan kosteloos tot 24 uur voor vertrek.'
            .'Artikel 10 – Overig Overige bepalingen zijn van toepassing.';

        $result = $this->formatter->format($input, shorten: true, title: 'Annuleren', category: 'voorwaarden');

        $this->assertSame('short', $result['mode']);
        $this->assertStringContainsString('kosteloos', $result['html']);
        $this->assertLessThanOrEqual(700, mb_strlen(strip_tags($result['html'])));
    }

    public function test_normalize_display_html_wraps_paragraphs_in_list_items(): void
    {
        $html = '<ul><p>Eerste punt</p><p>Tweede punt</p></ul>';

        $result = $this->formatter->normalizeDisplayHtml($html);

        $this->assertStringContainsString('<li>Eerste punt</li>', $result);
        $this->assertStringContainsString('<li>Tweede punt</li>', $result);
    }

    public function test_normalize_display_html_splits_plain_text_inside_ul(): void
    {
        $html = '<ul>Zorgvuldig en rustig vervoer Persoonlijke service Betrouwbaar vervoer</ul>';

        $result = $this->formatter->normalizeDisplayHtml($html);

        $this->assertStringContainsString('<li>Zorgvuldig en rustig vervoer</li>', $result);
        $this->assertStringContainsString('<li>Persoonlijke service</li>', $result);
    }

    public function test_normalize_display_html_groups_short_paragraphs_after_intro(): void
    {
        $html = '<h3>Stipt luchthavenvervoer zonder stress</h3>'
            .'<p>Een vlucht halen begint met goed geregeld vervoer.</p>'
            .'<p>Altijd ruim op tijd</p>'
            .'<p>Vaste prijs vooraf</p>'
            .'<p>Help met bagage</p>';

        $result = $this->formatter->normalizeDisplayHtml($html);

        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>Altijd ruim op tijd</li>', $result);
        $this->assertStringContainsString('<li>Vaste prijs vooraf</li>', $result);
    }
}
