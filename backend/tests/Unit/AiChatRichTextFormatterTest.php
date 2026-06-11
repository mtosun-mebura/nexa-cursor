<?php

namespace Tests\Unit;

use App\Services\AiChat\AiChatRichTextFormatter;
use Tests\TestCase;

class AiChatRichTextFormatterTest extends TestCase
{
    public function test_converts_anchor_to_markdown_link(): void
    {
        $formatter = new AiChatRichTextFormatter();

        $result = $formatter->htmlToChatText(
            'Contactformulier: <a href="/contact">Contact</a>'
        );

        $this->assertSame('Contactformulier: [Contact](/contact)', $result);
    }

    public function test_preserves_mailto_links(): void
    {
        $formatter = new AiChatRichTextFormatter();

        $result = $formatter->htmlToChatText(
            'E-mail: <a href="mailto:test@example.com">test@example.com</a>'
        );

        $this->assertSame('E-mail: [test@example.com](mailto:test@example.com)', $result);
    }

    public function test_converts_html_list_to_bullet_lines(): void
    {
        $formatter = new AiChatRichTextFormatter();

        $result = $formatter->htmlToChatText(
            '<p>Intro</p><ul><li>Eerste punt</li><li>Tweede punt</li></ul>'
        );

        $this->assertStringContainsString('- Eerste punt', $result);
        $this->assertStringContainsString('- Tweede punt', $result);
        $this->assertStringNotContainsString('<li>', $result);
    }
}
