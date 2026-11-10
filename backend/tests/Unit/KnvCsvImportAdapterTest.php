<?php

namespace Tests\Unit;

use App\Services\TaxiPartnerFinder\KnvCsvImportAdapter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnvCsvImportAdapterTest extends TestCase
{
    #[Test]
    public function it_parses_dutch_csv_headers(): void
    {
        $csv = <<<'CSV'
bedrijfsnaam;postcode;plaats;provincie;telefoon;email;website
Taxi CSV Test;7511 AA;Enschede;Overijssel;053-1234567;info@csv-taxi.test;https://csv-taxi.test
CSV;

        $candidates = app(KnvCsvImportAdapter::class)->parse($csv);
        $this->assertCount(1, $candidates);
        $this->assertSame('Taxi CSV Test', $candidates[0]->companyName);
        $this->assertSame('7511AA', $candidates[0]->postalCode);
        $this->assertSame('info@csv-taxi.test', $candidates[0]->email);
        $this->assertSame('knv_csv', $candidates[0]->sourceType);
    }
}
