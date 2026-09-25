<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NexaHeroTitleHtmlTest extends TestCase
{
    #[Test]
    public function it_keeps_enter_as_a_line_break_and_escapes_html(): void
    {
        $html = nexa_hero_title_html("Eerste regel\nTweede <regel>");

        $this->assertStringContainsString('Eerste regel<br>', $html);
        $this->assertStringContainsString('Tweede &lt;regel&gt;', $html);
        $this->assertStringNotContainsString('<regel>', $html);
    }

    #[Test]
    public function it_wraps_a_highlight_word_and_keeps_a_break_after_it(): void
    {
        $html = nexa_hero_title_html(
            "Laat klanten zelf boeken.\nBoek een taxi.",
            'zelf boeken',
            '#93c5fd'
        );

        $this->assertStringContainsString('<span style="color: #93c5fd;">zelf boeken</span>', $html);
        $this->assertStringContainsString('zelf boeken</span>.<br>', $html);
        $this->assertStringContainsString('Boek een taxi.', $html);
    }

    #[Test]
    public function it_highlights_multiple_phrases_separated_by_pipe_without_matching_inside_words(): void
    {
        $html = nexa_hero_title_html(
            "Laat klanten zelf boeken.\nBen je een reiziger, boek dan snel een Taxi.",
            'zelf boeken | boek',
            '#93c5fd'
        );

        $this->assertSame(1, substr_count($html, '>zelf boeken</span>'));
        $this->assertSame(1, substr_count($html, '>boek</span>'));
        $this->assertStringContainsString('<span style="color: #93c5fd;">zelf boeken</span>', $html);
        $this->assertStringContainsString('<span style="color: #93c5fd;">boek</span> dan snel', $html);
        $this->assertStringNotContainsString('>boeken</span>', $html);
    }
}
