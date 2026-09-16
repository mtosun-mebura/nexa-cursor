<?php

namespace Tests\Unit;

use App\Support\NexaLegalLinks;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NexaLegalLinksTest extends TestCase
{
    #[Test]
    public function marketing_url_prefers_configured_host(): void
    {
        config(['nexa.marketing_url' => 'https://nexasuite.nl']);

        $this->assertSame('https://nexasuite.nl/voorwaarden', NexaLegalLinks::termsUrl());
        $this->assertSame('https://nexasuite.nl/disclaimer', NexaLegalLinks::disclaimerUrl());
    }

    #[Test]
    public function html_footer_is_light_gray_and_not_duplicated(): void
    {
        $once = NexaLegalLinks::ensureHtmlFooter('<html><body>Bericht</body></html>');
        $twice = NexaLegalLinks::ensureHtmlFooter($once);

        $this->assertSame(1, substr_count($once, NexaLegalLinks::MARKER));
        $this->assertSame($once, $twice);
        $this->assertStringContainsString('color:#9ca3af', $once);
    }
}
