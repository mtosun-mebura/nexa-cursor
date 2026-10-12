<?php

namespace Tests\Unit;

use App\Services\CompanySearch\DomainService;
use App\Services\CompanySearch\SafeUrlFetcher;
use App\Services\TaxiPartnerFinder\KnvTaxiSourceAdapter;
use App\Services\TaxiPartnerFinder\TaxiProspectNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnvTaxiHtmlParserTest extends TestCase
{
    private function adapter(): KnvTaxiSourceAdapter
    {
        return new KnvTaxiSourceAdapter(
            new SafeUrlFetcher(new DomainService),
            new TaxiProspectNormalizer(new DomainService),
        );
    }

    #[Test]
    public function it_parses_knv_member_blocks_from_fixture(): void
    {
        $html = file_get_contents(base_path('tests/fixtures/knv-ledenoverzicht-sample.html'));
        $this->assertNotFalse($html);

        $rows = $this->adapter()->parseHtml($html);
        $this->assertGreaterThanOrEqual(2, count($rows));

        $first = $rows[0];
        $this->assertNotSame('', $first->companyName);
        $this->assertSame('knv', $first->sourceType);
        $this->assertSame('member', $first->knvMemberStatus);
        $this->assertNotEmpty($first->externalReference);
        $this->assertArrayHasKey('consulted_at', $first->rawMetadata);
    }

    #[Test]
    public function it_returns_empty_on_changed_structure_without_members(): void
    {
        $rows = $this->adapter()->parseHtml('<html><body><h1>Ledenoverzicht</h1><p>Geen blokken</p></body></html>');
        $this->assertSame([], $rows);
    }

    #[Test]
    public function it_allows_members_without_email(): void
    {
        $html = <<<'HTML'
<div id="btn-9999" class="container member-background">
  <div class="member-info"><div class="member-sector-name">KNV</div><h5>Taxi Zonder Mail</h5></div>
  <div class="member-open">
    <div class="member-contact">Voorbeeldstraat 1</div>
    <div class="member-contact">1234 AB Utrecht</div>
    <div class="member-contact">Utrecht</div>
    <div class="member-contact">Netherlands</div>
    <div class="member-contact"><span>Tel:</span> <a href="tel:0301234567">0301234567</a></div>
  </div>
</div>
HTML;
        $rows = $this->adapter()->parseHtml($html);
        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]->email);
        $this->assertNotNull($rows[0]->phone);
        $this->assertSame('Utrecht', $rows[0]->province);
    }
}
