<?php

namespace Tests\Unit;

use App\Services\CompanySearch\ContactNameExtractor;
use App\Services\CompanySearch\ContactNameParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactNameParserTest extends TestCase
{
    #[Test]
    public function it_splits_dutch_tussenvoegsel(): void
    {
        $parser = new ContactNameParser;
        $parsed = $parser->parse('Jan van der Berg');

        $this->assertSame([
            'first_name' => 'Jan',
            'middle_name' => 'van der',
            'last_name' => 'Berg',
        ], $parsed);
        $this->assertSame([
            'first_name' => 'Kees',
            'middle_name' => 'de',
            'last_name' => 'Vries',
        ], $parser->parse('Kees de Vries'));
    }

    #[Test]
    public function it_parses_last_name_first_and_hunter_parts(): void
    {
        $parser = new ContactNameParser;

        $this->assertSame('Jan', $parser->parse('de Vries, Jan')['first_name'] ?? null);
        $this->assertSame('de', $parser->parse('de Vries, Jan')['middle_name'] ?? null);
        $this->assertSame([
            'first_name' => 'Lisa',
            'middle_name' => 'van den',
            'last_name' => 'Berg',
        ], $parser->fromParts('Lisa', 'van den Berg'));
    }

    #[Test]
    public function it_parses_personal_email_and_skips_generic(): void
    {
        $parser = new ContactNameParser;

        $this->assertSame([
            'first_name' => 'Jan',
            'middle_name' => 'de',
            'last_name' => 'Vries',
        ], $parser->fromEmail('jan.de.vries@taxi-enschede.test'));
        $this->assertNull($parser->fromEmail('info@taxi-enschede.test'));
        $this->assertNull($parser->parse('Taxi Enschede'));
        $this->assertNull($parser->parse('Mail ons'));
    }

    #[Test]
    public function extractor_reads_json_ld_founder_and_mailto_name(): void
    {
        $html = '<html><body>'
            .'<script type="application/ld+json">{"@type":"LocalBusiness","founder":{"@type":"Person","name":"Kees van Dijk"}}</script>'
            .'<a href="mailto:info@taxi-enschede.test">Mail ons</a>'
            .'</body></html>';

        $parsed = app(ContactNameExtractor::class)->fromHtml($html, 'info@taxi-enschede.test');

        $this->assertSame('Kees', $parsed['first_name'] ?? null);
        $this->assertSame('van', $parsed['middle_name'] ?? null);
        $this->assertSame('Dijk', $parsed['last_name'] ?? null);
    }
}
