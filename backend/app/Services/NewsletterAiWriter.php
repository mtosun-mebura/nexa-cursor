<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NewsletterAiWriter
{
    /**
     * @return array<string, mixed>
     */
    public function defaultBlocks(): array
    {
        $images = config('newsletter.stock_images', []);

        return [
            'hero_image' => $images['hero'] ?? 'assets/marketing/images/hero-nexa-platform.png',
            'eyebrow' => 'Voor taxibedrijven',
            'title' => 'Ritten, chauffeurs en contracten in één systeem',
            'intro' => "NEXA Suite is het complete platform voor taxibedrijven: een eigen website met boekingsknop, dispatch, chauffeur-app en contractvervoer. Geen marktplaats-commissie. U houdt de klant, de rit en de factuur in eigen hand.\n\nIn vijf minuten ziet u of het past. Meld u aan via de contactpagina; we zetten het systeem klaar voor uw bedrijf.",
            'features' => [
                [
                    'image' => $images['booking'] ?? '',
                    'title' => 'Website met boeking',
                    'text' => 'Klanten boeken op uw site, in uw merkkleuren. Geen extra platform ertussen.',
                ],
                [
                    'image' => $images['driver'] ?? '',
                    'title' => 'Chauffeur-app',
                    'text' => 'Ritten toewijzen, status live, betalen via Mollie of factuur. iPhone en Android.',
                ],
                [
                    'image' => $images['contract'] ?? '',
                    'title' => 'Contractvervoer',
                    'text' => 'School, zorg en zakelijk: planning, ouder-app, afmeldingen en maandfactuur.',
                ],
            ],
            'cta_label' => 'Aanmelden via contact',
            'cta_url' => '/contact',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function generate(?string $brief = null): array
    {
        $defaults = $this->defaultBlocks();
        $fromLlm = $this->tryOpenAi($brief, $defaults);

        return $this->mergeBlocks($defaults, $fromLlm ?? []);
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>|null
     */
    private function tryOpenAi(?string $brief, array $defaults): ?array
    {
        $apiKey = config('services.openai.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        $model = config('services.openai.model', 'gpt-4o-mini');
        $prompt = [
            'opdracht' => 'Schrijf een korte, overtuigende B2B-nieuwsbrief voor taxibedrijven in Nederland. Wervend, concreet, geen loze marketing. Geen gedachtestreepje. CTA wijst naar de contactpagina.',
            'brief_van_gebruiker' => trim((string) $brief),
            'product' => [
                'naam' => 'NEXA Suite',
                'punten' => [
                    'Website met boekingsmodule in eigen merk',
                    'Dispatch en chauffeur-app',
                    'Contractvervoer met ouder-app en maandfactuur',
                    'Vast maandbedrag, geen marktplaats-commissie',
                    'Pakketten Start 49, Pro 99, Business 179 euro per maand excl. btw',
                ],
            ],
            'gewenst_json' => [
                'eyebrow' => 'korte regel',
                'title' => 'kop, max 70 tekens',
                'intro' => '2 korte alinea\'s, max 420 tekens',
                'features' => [
                    ['title' => '', 'text' => 'max 140 tekens'],
                    ['title' => '', 'text' => ''],
                    ['title' => '', 'text' => ''],
                ],
                'cta_label' => 'korte knoptekst',
                'subject' => 'onderwerpregel, max 70 tekens',
                'preview_text' => 'inbox-preview, max 90 tekens',
                'name' => 'interne campagnenaam',
            ],
        ];

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.5,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Je bent een Nederlandse B2B-copywriter voor taxisoftware. Antwoord uitsluitend met geldig JSON. Gebruik geen em-dash.',
                        ],
                        ['role' => 'user', 'content' => json_encode($prompt, JSON_UNESCAPED_UNICODE)],
                    ],
                ]);
            if (! $response->successful()) {
                return null;
            }
            $decoded = json_decode((string) $response->json('choices.0.message.content'), true);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergeBlocks(array $defaults, array $incoming): array
    {
        $out = $defaults;
        foreach (['eyebrow', 'title', 'intro', 'cta_label'] as $key) {
            $value = trim((string) ($incoming[$key] ?? ''));
            if ($value !== '') {
                $out[$key] = $value;
            }
        }
        $out['cta_url'] = '/contact';
        $features = is_array($incoming['features'] ?? null) ? array_values($incoming['features']) : [];
        foreach ($out['features'] as $i => $feature) {
            $row = is_array($features[$i] ?? null) ? $features[$i] : [];
            $title = trim((string) ($row['title'] ?? ''));
            $text = trim((string) ($row['text'] ?? ''));
            if ($title !== '') {
                $out['features'][$i]['title'] = $title;
            }
            if ($text !== '') {
                $out['features'][$i]['text'] = $text;
            }
        }
        $out['_meta'] = [
            'subject' => trim((string) ($incoming['subject'] ?? $out['title'])),
            'preview_text' => trim((string) ($incoming['preview_text'] ?? '')),
            'name' => trim((string) ($incoming['name'] ?? $out['title'])),
        ];

        return $out;
    }
}
