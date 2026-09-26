<?php

namespace Tests\Unit;

use App\Support\WebsiteSeoMeta;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsiteSeoMetaTest extends TestCase
{
    #[Test]
    public function it_rejects_editorjs_json_as_description(): void
    {
        $json = '{"blocks":[],"time":1780569359355,"version":"2.0"}';
        $this->assertFalse(WebsiteSeoMeta::isUsableDescription($json));
        $this->assertTrue(WebsiteSeoMeta::looksLikeEditorJs($json));
    }

    #[Test]
    public function it_falls_back_to_brand_copy_when_meta_is_editorjs(): void
    {
        $desc = WebsiteSeoMeta::resolveDescription(
            '{"blocks":[],"time":1,"version":"2.0"}',
            '{"blocks":[],"time":1,"version":"2.0"}',
            '',
            'Taxi Royaal',
        );

        $this->assertStringContainsString('Taxi Royaal', $desc);
        $this->assertStringNotContainsString('blocks', $desc);
    }

    #[Test]
    public function it_uses_valid_meta_description(): void
    {
        $desc = WebsiteSeoMeta::resolveDescription(
            'Betrouwbaar taxivervoer in Enschede en Twente. Boek online.',
            null,
            null,
            'Taxi Royaal',
        );

        $this->assertSame('Betrouwbaar taxivervoer in Enschede en Twente. Boek online.', $desc);
    }

    #[Test]
    public function brand_label_prefers_logo_alt(): void
    {
        $this->assertSame('Taxi Royaal', WebsiteSeoMeta::brandLabel([
            'site_name' => 'NEXA Taxi',
            'logo_alt' => 'Taxi Royaal',
        ]));
    }
}
