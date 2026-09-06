<?php

namespace Tests\Unit;

use App\Services\WebsiteAiSourceReader;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsiteAiSourceReaderTest extends TestCase
{
    #[Test]
    public function it_extracts_title_headings_and_text_from_html(): void
    {
        Http::fake([
            'https://bron.example/*' => Http::response(
                '<html><head><title>Taxi Bron</title><meta name="description" content="Ritten in de regio"></head><body><h1>Welkom</h1><p>Wij rijden op Schiphol.</p><a href="/over-ons">Over</a></body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $result = app(WebsiteAiSourceReader::class)->read('https://bron.example');

        $this->assertSame('https://bron.example/', $result['url']);
        $this->assertNotEmpty($result['pages']);
        $this->assertStringContainsString('Taxi Bron', $result['summary']);
        $this->assertStringContainsString('Schiphol', $result['summary']);
    }

    #[Test]
    public function empty_url_returns_empty_result(): void
    {
        $result = app(WebsiteAiSourceReader::class)->read('  ');
        $this->assertSame('', $result['url']);
        $this->assertSame([], $result['pages']);
        $this->assertSame('', $result['summary']);
    }
}
