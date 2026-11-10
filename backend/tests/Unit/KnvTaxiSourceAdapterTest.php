<?php

namespace Tests\Unit;

use App\Services\CompanySearch\DomainService;
use App\Services\CompanySearch\SafeUrlFetcher;
use App\Services\TaxiPartnerFinder\KnvTaxiSourceAdapter;
use App\Services\TaxiPartnerFinder\TaxiProspectNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnvTaxiSourceAdapterTest extends TestCase
{
    private function adapter(): KnvTaxiSourceAdapter
    {
        $normalizer = new TaxiProspectNormalizer(new DomainService);

        return new KnvTaxiSourceAdapter(
            $this->createMock(SafeUrlFetcher::class),
            $normalizer
        );
    }

    #[Test]
    public function it_parses_knv_member_blocks_from_fixture(): void
    {
        $html = file_get_contents(base_path('tests/fixtures/knv-ledenoverzicht-sample.html'));
        $this->assertNotFalse($html);

        $candidates = $this->adapter()->parseHtml($html, 'https://www.knv.nl/ledenoverzicht-taxi/');

        $this->assertGreaterThanOrEqual(2, count($candidates));
        $withEmail = collect($candidates)->first(fn ($c) => filled($c->email));
        $this->assertNotNull($withEmail);
        $this->assertNotSame('', $withEmail->companyName);
        $this->assertSame('member', $withEmail->knvMemberStatus);
        $this->assertSame('knv', $withEmail->sourceType);
        $this->assertNotNull($withEmail->externalReference);
        $this->assertArrayHasKey('consulted_at', $withEmail->rawMetadata);
    }

    #[Test]
    public function it_returns_empty_on_changed_html_structure(): void
    {
        $candidates = $this->adapter()->parseHtml('<html><body><p>geen leden</p></body></html>');
        $this->assertSame([], $candidates);
    }

    #[Test]
    public function it_allows_missing_email(): void
    {
        $html = file_get_contents(base_path('tests/fixtures/knv-ledenoverzicht-sample.html'));
        $candidates = $this->adapter()->parseHtml((string) $html);
        $withoutEmail = collect($candidates)->first(fn ($c) => blank($c->email));
        $this->assertNotNull($withoutEmail);
        $this->assertNotSame('', $withoutEmail->companyName);
    }
}
