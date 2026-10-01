<?php

namespace Tests\Unit;

use App\Models\WebsitePage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePageCopyrightRenderTest extends TestCase
{
    #[Test]
    public function render_copyright_replaces_year_and_markdown_link(): void
    {
        $html = WebsitePage::renderCopyrightHtml(
            '© {year} [NEXA Suite](https://nexasuite.nl) applicatie. Alle rechten voorbehouden.'
        );

        $this->assertStringContainsString((string) date('Y'), $html);
        $this->assertStringContainsString('href="https://nexasuite.nl"', $html);
        $this->assertStringContainsString('>NEXA Suite</a>', $html);
        $this->assertStringContainsString('text-decoration:underline', $html);
        $this->assertStringNotContainsString('{year}', $html);
        $this->assertStringNotContainsString('[NEXA Suite]', $html);
    }

    #[Test]
    public function render_copyright_rejects_non_http_urls(): void
    {
        $html = WebsitePage::renderCopyrightHtml('© {year} [Hack](javascript:alert(1)).');

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringContainsString('Hack', $html);
    }

    #[Test]
    public function footer_plain_text_strips_markdown_links(): void
    {
        $plain = WebsitePage::footerPlainText(
            '© {year} [NEXA Suite](https://nexasuite.nl). Alle rechten voorbehouden.'
        );

        $this->assertSame('© {year} NEXA Suite. Alle rechten voorbehouden.', $plain);
    }
}
