<?php

namespace Tests\Unit;

use App\Services\CompanySearch\DomainService;
use App\Services\CompanySearch\EmailExtractorService;
use App\Services\CompanySearch\PhoneExtractorService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanySearchExtractorsTest extends TestCase
{
    #[Test]
    public function email_extractor_prefers_mailto_on_contact_page(): void
    {
        $html = <<<'HTML'
<html>
<body>
<a href="mailto:info@example.nl">Email ons</a>
<a href="tel:+31531234567">Bel ons</a>
</body>
</html>
HTML;
        $emails = app(EmailExtractorService::class)->extract($html, 'https://example.nl/contact', 'https://example.nl');

        $this->assertSame('info@example.nl', $emails[0]['email']);
        $this->assertSame('website_mailto', $emails[0]['source']);
        $this->assertGreaterThanOrEqual(100, $emails[0]['confidence']);
    }

    #[Test]
    public function email_extractor_ignores_junk_and_foreign_domains(): void
    {
        $html = 'Mail: noreply@example.nl of info@anderbedrijf.nl';
        $best = app(EmailExtractorService::class)->best($html, 'https://janssen.nl', 'https://janssen.nl');

        $this->assertNull($best);
    }

    #[Test]
    public function phone_extractor_reads_tel_link(): void
    {
        $html = '<a href="tel:+31531234567">Bel ons</a>';
        $phone = app(PhoneExtractorService::class)->fromHtml($html);

        $this->assertSame('0531234567', $phone);
    }

    #[Test]
    public function domain_service_strips_www_and_path(): void
    {
        $domains = app(DomainService::class);

        $this->assertSame('example.nl', $domains->host('https://www.example.nl/contact?x=1'));
        $this->assertSame('https://www.example.nl', $domains->origin('https://www.example.nl/contact'));
        $this->assertTrue($domains->emailMatchesDomain('info@example.nl', 'https://www.example.nl'));
        $this->assertTrue($domains->emailMatchesDomain('info@stadstaxi-utrecht.nl', 'https://stadstaxi-utrecht.test'));
        $this->assertFalse($domains->emailMatchesDomain('info@anderbedrijf.nl', 'https://janssen.nl'));
    }
}
