<?php

namespace Tests\Unit;

use App\Models\FrontendTheme;
use App\Models\WebsitePage;
use App\Services\InfoRequestFormPreviewContextService;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InfoRequestFormPreviewContextServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('frontend_themes')) {
            $this->markTestSkipped('frontend_themes table required (run migrations)');
        }
    }

    #[Test]
    public function test_finds_text_block_form_with_section_width_percent(): void
    {
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );

        $page = WebsitePage::create([
            'slug' => 'contact',
            'title' => 'Contact',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['text_block_1'],
                'text_block_1' => [
                    'content' => '<p>Openingstijden</p>',
                    'alignment' => 'left',
                    'side_component_key' => 'email_template_1',
                    'side_template_id' => 12,
                    'width_percent' => 80,
                ],
            ],
        ]);

        $service = app(InfoRequestFormPreviewContextService::class);
        $contexts = $service->contextsForCompany($page->company_id);

        $this->assertCount(1, $contexts);
        $this->assertSame(80, $contexts[0]['width_percent']);
        $this->assertSame('text_block_half', $contexts[0]['layout']);
        $this->assertSame('Contact', $contexts[0]['page_title']);
    }

    #[Test]
    public function test_default_context_uses_full_section_width(): void
    {
        $service = app(InfoRequestFormPreviewContextService::class);
        $default = $service->defaultContext([]);

        $this->assertSame(100, $default['width_percent']);
        $this->assertSame('text_block_half', $default['layout']);
    }
}
