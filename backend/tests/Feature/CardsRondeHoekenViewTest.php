<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verifies cards partial outputs admin font_size, text_align and h1/h2/h3 from WYSIWYG.
 */
class CardsRondeHoekenViewTest extends TestCase
{
    /**
     * Frontend cards partial: admin font_size and h1/h2/h3 from WYSIWYG must appear in output.
     */
    public function test_cards_ronde_hoeken_renders_font_size_and_headings(): void
    {
        $items = [
            [
                'text' => '<h1>Title</h1><h2>Sub</h2><h3>Small</h3><p>Body</p>',
                'font_size' => 24,
                'font_style' => 'normal',
                'card_size' => 'normal',
                'text_align' => 'center',
            ],
        ];
        $visibility = ['cards_ronde_hoeken_item_0' => true];

        $html = view('frontend.website.partials.cards-ronde-hoeken', [
            'items' => $items,
            'visibility' => $visibility,
            'sectionKey' => 'cards_ronde_hoeken',
        ])->render();

        $this->assertStringContainsString('--card-fs: 24px', $html, 'Card font size from admin should be in inline style');
        $this->assertStringContainsString('cards-ronde-hoeken-prose', $html, 'Prose wrapper should have scaling class');
        $this->assertStringContainsString('cards-ronde-hoeken-text', $html, 'Text block should have card text class');
        $this->assertStringContainsString('<h1>Title</h1>', $html, 'H1 from WYSIWYG should be output');
        $this->assertStringContainsString('<h2>Sub</h2>', $html, 'H2 from WYSIWYG should be output');
        $this->assertStringContainsString('<h3>Small</h3>', $html, 'H3 from WYSIWYG should be output');
        $this->assertStringContainsString('text-center', $html, 'Text align from admin should be applied');
    }
}
