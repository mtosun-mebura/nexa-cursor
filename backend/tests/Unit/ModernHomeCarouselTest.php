<?php

namespace Tests\Unit;

use Tests\TestCase;

class ModernHomeCarouselTest extends TestCase
{
    public function test_modern_home_renders_carousel_when_in_section_order(): void
    {
        $homeSections = [
            'section_order' => ['carousel'],
            'visibility' => ['carousel' => true],
            'carousel' => [
                'items' => [
                    [
                        'uuid' => 'slide-uuid-1',
                        'alt' => 'Eerste slide tekst',
                        'text_color' => '#fbbf24',
                        'text_bg_color' => '#1e3a5f',
                        'text_bg_opacity' => 50,
                        'text_size_px' => 32,
                        'text_position' => 'center',
                        'text_animation' => 'zoom',
                        'text_animation_duration_ms' => 1500,
                        'text_animation_stagger_ms' => 150,
                    ],
                ],
                'interval_seconds' => 8,
            ],
        ];

        $html = view('frontend.website.partials.modern-home', [
            'homeSections' => $homeSections,
        ])->render();

        $this->assertStringContainsString('data-carousel="slide"', $html);
        $this->assertStringContainsString('data-carousel-interval="8"', $html);
        $this->assertStringContainsString('/website-media/slide-uuid-1', $html);
        $this->assertStringContainsString('data-carousel-caption', $html);
        $this->assertStringContainsString('carousel-caption-word', $html);
        $this->assertStringContainsString('color: #fbbf24', $html);
        $this->assertStringContainsString('Eerste</span>', $html);
        $this->assertStringContainsString('slide</span>', $html);
        $this->assertStringContainsString('tekst</span>', $html);
        $this->assertStringContainsString('carousel-caption-pos-center', $html);
        $this->assertStringContainsString('carousel-anim-zoom', $html);
        $this->assertStringContainsString('--caption-size-max: 32px', $html);
        $this->assertStringContainsString('font-size: clamp(', $html);
        $this->assertStringContainsString('aspect-ratio:', $html);
        $this->assertStringContainsString('data-carousel-animation="zoom"', $html);
        $this->assertStringContainsString('carousel-caption-text-block', $html);
        $this->assertStringContainsString('background-color: rgba(30, 58, 95, 0.5)', $html);
        $this->assertStringContainsString('--caption-anim-duration: 1500ms', $html);
        $this->assertStringContainsString('transition-delay: 0ms', $html);
        $this->assertStringContainsString('transition-delay: 150ms', $html);
        $this->assertStringContainsString('transition-delay: 300ms', $html);
    }
}
