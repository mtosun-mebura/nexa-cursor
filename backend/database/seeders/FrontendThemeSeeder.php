<?php

namespace Database\Seeders;

use App\Models\FrontendTheme;
use Illuminate\Database\Seeder;

class FrontendThemeSeeder extends Seeder
{
    /**
     * Standaard blok-definities per thema (volgorde en type; data wordt door gebruiker ingevuld).
     * Gebaseerd op analyse van backend/themas: atom-v2, nextly-template, next-landing-vpn en generiek modern.
     */
    public function run(): void
    {
        FrontendTheme::whereIn('slug', ['classic', 'minimal'])->delete();

        $themes = [
            [
                'slug' => 'modern',
                'name' => 'Modern',
                'description' => 'Strak en modern design met veel witruimte. Huidige website-layout (Home-pagina).',
                'preview_path' => 'frontend-themes/modern-home.png',
                'is_active' => true,
                'settings' => [
                    'primary_color' => '#2563eb',
                    'font_heading' => 'Inter',
                    'font_body' => 'Inter',
                    'footer_text' => '',
                    'dark_mode_available' => true,
                ],
                'default_blocks' => $this->blocksModern(),
            ],
            [
                'slug' => 'atom-v2',
                'name' => 'Atom v2',
                'description' => 'Landingpagina-thema met hero, diensten en portfolio. Tailwind, aanpasbare primaire kleur en lettertypen.',
                'preview_path' => 'frontend-themes/atom-v2/assets/img/bg-hero.jpg',
                'is_active' => false,
                'settings' => [
                    'primary_color' => '#5540af',
                    'font_heading' => 'Raleway',
                    'font_body' => 'Open Sans',
                    'footer_text' => '',
                    'dark_mode_available' => true,
                ],
                'default_blocks' => $this->blocksAtomV2(),
            ],
            [
                'slug' => 'nextly-template',
                'name' => 'Nextly Template',
                'description' => 'Next.js-thema met hero, voordelen, FAQ en testimonials. Primaire kleur en lettertypen instelbaar.',
                'preview_path' => 'frontend-themes/nextly-template/public/img/hero.png',
                'is_active' => false,
                'settings' => [
                    'primary_color' => '#2563eb',
                    'font_heading' => 'Inter',
                    'font_body' => 'Inter',
                    'footer_text' => '',
                    'dark_mode_available' => true,
                ],
                'default_blocks' => $this->blocksNextlyTemplate(),
            ],
            [
                'slug' => 'next-landing-vpn',
                'name' => 'Next Landing VPN',
                'description' => 'Landingpagina-thema met features, pricing en testimonials. Kleur en typografie aanpasbaar.',
                'preview_path' => 'frontend-themes/next-landing-vpn/landingpage.png',
                'is_active' => false,
                'settings' => [
                    'primary_color' => '#4f46e5',
                    'font_heading' => 'Inter',
                    'font_body' => 'Inter',
                    'footer_text' => '',
                    'dark_mode_available' => true,
                ],
                'default_blocks' => $this->blocksNextLandingVpn(),
            ],
        ];

        foreach ($themes as $theme) {
            FrontendTheme::updateOrCreate(
                ['slug' => $theme['slug']],
                $theme
            );
        }
    }

    private function blocksModern(): array
    {
        return [
            ['type' => 'header', 'data' => ['text' => '', 'level' => 1], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
            ['type' => 'slider', 'data' => ['items' => [['url' => '', 'caption' => '']]], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
            ['type' => 'image', 'data' => ['url' => '', 'caption' => ''], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
        ];
    }

    /** Atom v2: hero, about, services (cards), portfolio (images), clients, work, statistics, blog, cta, contact. */
    private function blocksAtomV2(): array
    {
        return [
            ['type' => 'header', 'data' => ['text' => '', 'level' => 1], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
            ['type' => 'image', 'data' => ['url' => '', 'caption' => ''], 'width' => 'half'],
            ['type' => 'image', 'data' => ['url' => '', 'caption' => ''], 'width' => 'half'],
            ['type' => 'image', 'data' => ['url' => '', 'caption' => ''], 'width' => 'half'],
            ['type' => 'image', 'data' => ['url' => '', 'caption' => ''], 'width' => 'half'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => ['']], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => ['']], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'table', 'data' => ['content' => [['', '', ''], ['', '', '']]], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'image', 'data' => ['url' => '', 'caption' => ''], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
        ];
    }

    /** Nextly: Hero, SectionTitle, Benefits x2, Video, Testimonials, Faq, Cta. */
    private function blocksNextlyTemplate(): array
    {
        return [
            ['type' => 'header', 'data' => ['text' => '', 'level' => 1], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
            ['type' => 'imageAligned', 'data' => ['url' => '', 'caption' => '', 'alignment' => 'left'], 'width' => 'half'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'half'],
            ['type' => 'imageAligned', 'data' => ['url' => '', 'caption' => '', 'alignment' => 'right'], 'width' => 'half'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'half'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
            ['type' => 'slider', 'data' => ['items' => [['url' => '', 'caption' => ''], ['url' => '', 'caption' => '']]], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'quote', 'data' => ['text' => '', 'caption' => ''], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => ['', '']], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
        ];
    }

    /** Next Landing VPN: Hero, Feature, Pricing, Testimoni. */
    private function blocksNextLandingVpn(): array
    {
        return [
            ['type' => 'header', 'data' => ['text' => '', 'level' => 1], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
            ['type' => 'image', 'data' => ['url' => '', 'caption' => ''], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'paragraph', 'data' => ['text' => ''], 'width' => 'full'],
            ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => ['', '', '']], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'table', 'data' => ['content' => [['', '', ''], ['', '', ''], ['', '', '']]], 'width' => 'full'],
            ['type' => 'header', 'data' => ['text' => '', 'level' => 2], 'width' => 'full'],
            ['type' => 'slider', 'data' => ['items' => [['url' => '', 'caption' => ''], ['url' => '', 'caption' => '']]], 'width' => 'full'],
        ];
    }
}
