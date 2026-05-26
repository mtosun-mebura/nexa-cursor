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
}
