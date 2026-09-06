<?php

namespace Tests\Unit;

use App\Models\WebsitePage;
use PHPUnit\Framework\TestCase;

class WebsitePageAdminCollapsedTest extends TestCase
{
    public function test_default_admin_collapsed_includes_section_order_footer_and_copyright(): void
    {
        $order = ['component:taxi.boekingsmodule', 'hero', 'cta'];
        $collapsed = WebsitePage::defaultAdminCollapsedKeys($order);

        $this->assertSame(
            ['component:taxi.boekingsmodule', 'hero', 'cta', 'footer', 'copyright'],
            $collapsed
        );
    }

    public function test_default_home_sections_for_theme_includes_admin_collapsed(): void
    {
        $sections = WebsitePage::defaultHomeSectionsForTheme('modern');

        $this->assertNotEmpty($sections['admin_collapsed'] ?? []);
        $this->assertContains('hero', $sections['admin_collapsed']);
        $this->assertContains('footer', $sections['admin_collapsed']);
        $this->assertContains('copyright', $sections['admin_collapsed']);
    }

    public function test_default_footer_is_module_neutral(): void
    {
        $footer = WebsitePage::defaultHomeSections()['footer'];
        $labels = array_column($footer['quick_links'], 'label');

        $this->assertSame(WebsitePage::DEFAULT_FOOTER_TAGLINE, $footer['tagline']);
        $this->assertNotContains('Vacatures', $labels);
        $this->assertNotContains('/jobs', array_column($footer['quick_links'], 'url'));
        $this->assertContains('Home', $labels);
        $this->assertContains('Over Ons', $labels);
        $this->assertContains('Contact', $labels);
    }

    public function test_prepare_footer_hides_vacatures_and_legacy_tagline_outside_skillmatching(): void
    {
        $prepared = WebsitePage::prepareFooterForPublicDisplay([
            'footer' => [
                'tagline' => '<p>'.WebsitePage::LEGACY_SKILLMATCHING_FOOTER_TAGLINE.'</p>',
                'quick_links' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Vacatures', 'url' => '/jobs'],
                    ['label' => 'Over Ons', 'url' => '/over-ons'],
                ],
            ],
        ], false);

        $labels = array_column($prepared['footer']['quick_links'], 'label');
        $this->assertSame(WebsitePage::DEFAULT_FOOTER_TAGLINE, $prepared['footer']['tagline']);
        $this->assertSame(['Home', 'Over Ons'], $labels);
    }

    public function test_prepare_footer_keeps_vacatures_for_skillmatching(): void
    {
        $tagline = '<p>'.WebsitePage::LEGACY_SKILLMATCHING_FOOTER_TAGLINE.'</p>';
        $prepared = WebsitePage::prepareFooterForPublicDisplay([
            'footer' => [
                'tagline' => $tagline,
                'quick_links' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Vacatures', 'url' => '/jobs'],
                ],
            ],
        ], true);

        $this->assertSame($tagline, $prepared['footer']['tagline']);
        $this->assertSame(['Home', 'Vacatures'], array_column($prepared['footer']['quick_links'], 'label'));
    }
}
