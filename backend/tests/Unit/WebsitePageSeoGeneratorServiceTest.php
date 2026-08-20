<?php

namespace Tests\Unit;

use App\Services\WebsitePageSeoGeneratorService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePageSeoGeneratorServiceTest extends TestCase
{
    #[Test]
    public function it_generates_meta_description_within_ideal_length(): void
    {
        $service = new WebsitePageSeoGeneratorService;

        $result = $service->generate([
            'title' => 'Over ons',
            'page_type' => 'about',
            'site_name' => 'Nexa Taxi',
            'site_description' => 'Betrouwbaar taxivervoer in de regio.',
            'company_name' => 'Demo Taxi BV',
            'include_sections' => true,
        ]);

        $this->assertNotEmpty($result['title']);
        $this->assertNotEmpty($result['meta_description']);
        $this->assertGreaterThanOrEqual(150, mb_strlen($result['meta_description']));
        $this->assertLessThanOrEqual(160, mb_strlen($result['meta_description']));
        $this->assertDoesNotMatchRegularExpression('/…$/', $result['meta_description']);
        $this->assertMatchesRegularExpression('/[.!?]$/u', $result['meta_description']);
        $this->assertArrayHasKey('hero', $result['sections']);
        $this->assertNotEmpty($result['sections']['hero']['title']);
        $this->assertSame('template', $result['source']);
        $this->assertNotEmpty($result['tips']);
        $this->assertSeoTextsHaveNoEmDash($result);
    }

    #[Test]
    public function it_generates_home_copy_with_brand(): void
    {
        $service = new WebsitePageSeoGeneratorService;

        $result = $service->generate([
            'title' => '',
            'page_type' => 'home',
            'site_name' => 'Nexa',
            'company_name' => 'Acme BV',
            'include_sections' => true,
        ]);

        $this->assertStringContainsString('Acme', $result['meta_description']);
        $this->assertStringContainsString('Acme', $result['sections']['hero']['subtitle']);
        $this->assertStringNotContainsString('|', $result['title']);
    }

    #[Test]
    public function it_builds_seo_texts_from_page_content(): void
    {
        $service = new WebsitePageSeoGeneratorService;

        $result = $service->generate([
            'title' => 'Home',
            'page_type' => 'home',
            'site_name' => 'NEXA',
            'company_name' => 'NEXA',
            'include_sections' => true,
            'page_content' => "[Hero]\nTaxi software met chauffeur-app\nBoek ritten online, stuur chauffeurs via de app en houd planning en betaling in één systeem.\n\n[Kenmerken]\nLive dispatch\nContractvervoer planning\nWebsite met online boeking",
        ]);

        $this->assertLessThanOrEqual(160, mb_strlen($result['meta_description']));
        $this->assertGreaterThanOrEqual(150, mb_strlen($result['meta_description']));
        $this->assertDoesNotMatchRegularExpression('/…$/', $result['meta_description']);
        $this->assertStringContainsStringIgnoringCase('taxi', $result['meta_description']);
        $this->assertStringContainsStringIgnoringCase('chauffeur', $result['title'].' '.$result['meta_description']);
        $this->assertStringNotContainsString('Betrouwbare partner voor uw doelgroep', $result['meta_description']);
        $this->assertSame('template', $result['source']);
    }

    #[Test]
    public function it_extracts_text_from_home_sections(): void
    {
        $service = new WebsitePageSeoGeneratorService;

        $text = $service->extractTextFromHomeSections([
            'section_order' => ['hero', 'text_block'],
            'visibility' => ['hero' => true],
            'hero' => [
                'title' => 'Contractvervoer zonder Excel',
                'subtitle' => '<p>Planning, afmeldingen en facturatie in één overzicht.</p>',
                'cta_primary_url' => 'https://example.test/contact',
                'title_color' => '#112233',
            ],
            'text_block' => [
                'content' => '<p>School- en dagbestedingstrajecten met status per rit.</p>',
            ],
            'footer' => ['copyright' => 'niet meenemen'],
        ]);

        $this->assertStringContainsString('Contractvervoer zonder Excel', $text);
        $this->assertStringContainsString('Planning, afmeldingen en facturatie', $text);
        $this->assertStringContainsString('School- en dagbestedingstrajecten', $text);
        $this->assertStringNotContainsString('example.test', $text);
        $this->assertStringNotContainsString('#112233', $text);
        $this->assertStringNotContainsString('niet meenemen', $text);
    }

    #[Test]
    public function it_applies_hero_copy_to_the_first_hero_section(): void
    {
        $service = new WebsitePageSeoGeneratorService;

        $updated = $service->applyHeroToHomeSections([
            'section_order' => ['hero', 'cta'],
            'hero' => [
                'title' => 'Oud',
                'subtitle' => 'Oude ondertitel',
                'cta_primary_text' => 'Ga',
            ],
            'cta' => ['title' => 'Contact'],
        ], [
            'title' => 'Taxi software',
            'subtitle' => 'Boek ritten online.',
            'cta_primary_text' => 'Offerte',
            'cta_secondary_text' => 'Demo',
        ]);

        $this->assertIsArray($updated);
        $this->assertSame('Taxi software', $updated['hero']['title']);
        $this->assertSame('Boek ritten online.', $updated['hero']['subtitle']);
        $this->assertSame('Offerte', $updated['hero']['cta_primary_text']);
        $this->assertSame('Demo', $updated['hero']['cta_secondary_text']);
        $this->assertSame('Contact', $updated['cta']['title']);
        $this->assertNull($service->applyHeroToHomeSections(['section_order' => ['cta']], ['title' => 'X']));
    }

    #[Test]
    public function it_keeps_full_hero_title_instead_of_truncated_seo_title(): void
    {
        $service = new WebsitePageSeoGeneratorService;
        $full = 'Mis je ritten omdat de telefoon overgaat? Laat klanten zelf boeken.';

        $result = $service->generate([
            'title' => 'Mis je ritten omdat de telefoon overgaat? Laat…',
            'page_type' => 'home',
            'site_name' => 'NEXA',
            'include_sections' => true,
            'page_content' => "[Hero]\nMis je ritten omdat de telefoon overgaat? Laat…\nNEXA Suite is het platform voor taxibedrijven.",
            'home_sections' => [
                'section_order' => ['hero'],
                'hero' => [
                    'title' => 'Mis je ritten omdat de telefoon overgaat? Laat…',
                    'subtitle' => 'NEXA Suite is het platform voor taxibedrijven.',
                ],
            ],
        ]);

        $this->assertSame($full, $result['sections']['hero']['title']);
        $this->assertDoesNotMatchRegularExpression('/…$/', $result['sections']['hero']['title']);
        $this->assertLessThanOrEqual(55, mb_strlen($result['title']));
        $this->assertDoesNotMatchRegularExpression('/…/', $result['title']);
        $this->assertDoesNotMatchRegularExpression('/…/', $result['meta_description']);
    }

    #[Test]
    public function it_writes_search_snippets_instead_of_pasting_hero_and_buttons(): void
    {
        $service = new WebsitePageSeoGeneratorService;

        $result = $service->generate([
            'title' => 'NEXA Taxi: Mis je ritten omdat de telefoon overgaat? Laat…',
            'page_type' => 'home',
            'site_name' => 'NEXA Taxi',
            'company_name' => 'NEXA Taxi',
            'include_sections' => true,
            'page_content' => "[Hero]\nNEXA Taxi: Mis je ritten omdat de telefoon overgaat? Laat…\nMis je ritten omdat de telefoon overgaat? Laat klanten zelf boeken.\nStart hier\nMeer lezen\nHerkenbaar?",
        ]);

        $meta = $result['meta_description'];
        $this->assertGreaterThanOrEqual(150, mb_strlen($meta));
        $this->assertLessThanOrEqual(160, mb_strlen($meta));
        $this->assertDoesNotMatchRegularExpression('/…/', $meta);
        $this->assertStringNotContainsStringIgnoringCase('start hier', $meta);
        $this->assertStringNotContainsStringIgnoringCase('meer lezen', $meta);
        $this->assertStringNotContainsStringIgnoringCase('herkenbaar', $meta);
        $this->assertMatchesRegularExpression('/[.!?]$/u', $meta);
        $this->assertTrue(
            str_contains(mb_strtolower($meta), 'boek') || str_contains(mb_strtolower($meta), 'rit'),
            'Meta moet over boeken of ritten gaan, niet over UI-teksten. Got: '.$meta
        );
        $this->assertLessThanOrEqual(55, mb_strlen($result['title']));
        $this->assertDoesNotMatchRegularExpression('/…/', $result['title']);
        $this->assertFalse($this->titleLooksLikeTruncatedHero($result['title']));
        $this->assertSame('template', $result['source']);
    }

    private function titleLooksLikeTruncatedHero(string $title): bool
    {
        return str_contains($title, 'Laat…') || str_ends_with(trim($title), 'Laat');
    }

    #[Test]
    public function it_does_not_overwrite_a_full_hero_title_with_a_truncated_one(): void
    {
        $service = new WebsitePageSeoGeneratorService;
        $full = 'Mis je ritten omdat de telefoon overgaat? Laat klanten zelf boeken.';

        $updated = $service->applyHeroToHomeSections([
            'section_order' => ['hero'],
            'hero' => [
                'title' => $full,
                'subtitle' => 'Oud',
            ],
        ], [
            'title' => 'Mis je ritten omdat de telefoon overgaat? Laat…',
            'subtitle' => 'Nieuw',
        ]);

        $this->assertSame($full, $updated['hero']['title']);
        $this->assertSame('Nieuw', $updated['hero']['subtitle']);
    }

    #[Test]
    public function it_replaces_em_dashes_in_generated_seo_texts(): void
    {
        $service = new WebsitePageSeoGeneratorService;

        $result = $service->generate([
            'title' => 'Website',
            'page_type' => 'custom',
            'site_name' => 'NEXA',
            'company_name' => 'NEXA',
            'include_sections' => true,
            'page_content' => "[Hero]\nWebsite builder — merk + boeking zonder apart CMS\nEigen website met boeking, thema en SEO — zonder apart CMS.",
        ]);

        $this->assertSeoTextsHaveNoEmDash($result);
        $this->assertStringContainsString(':', $result['title'].' '.$result['meta_description']);

        $updated = $service->applyHeroToHomeSections([
            'section_order' => ['hero'],
            'hero' => ['title' => 'Oud', 'subtitle' => 'Oud'],
        ], [
            'title' => 'Website builder — merk + boeking zonder apart CMS',
            'subtitle' => 'Plan een demo — we laten dispatch zien.',
        ]);

        $this->assertSame('Website builder: merk + boeking zonder apart CMS', $updated['hero']['title']);
        $this->assertSame('Plan een demo: we laten dispatch zien.', $updated['hero']['subtitle']);
        $this->assertStringNotContainsString('—', $updated['hero']['title']);
        $this->assertStringNotContainsString('—', $updated['hero']['subtitle']);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function assertSeoTextsHaveNoEmDash(array $result): void
    {
        $blob = $result['title'].' '.$result['meta_description'].' '.implode(' ', $result['tips'] ?? []);
        foreach ($result['sections'] ?? [] as $section) {
            if (! is_array($section)) {
                continue;
            }
            $blob .= ' '.implode(' ', $section);
        }

        $this->assertStringNotContainsString('—', $blob, 'SEO-teksten mogen geen gedachtestreepje (—) bevatten.');
    }
}
