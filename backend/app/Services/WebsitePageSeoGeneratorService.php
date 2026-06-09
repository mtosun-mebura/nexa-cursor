<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebsitePageSeoGeneratorService
{
    private const META_MAX = 160;

    /**
     * @param  array{
     *     title?: string,
     *     page_type?: string,
     *     module_name?: string|null,
     *     module_display_name?: string|null,
     *     slug?: string,
     *     site_name?: string,
     *     site_description?: string,
     *     company_name?: string|null,
     *     include_sections?: bool,
     * }  $input
     * @return array{
     *     title: string,
     *     meta_description: string,
     *     sections: array<string, array<string, string>>,
     *     tips: list<string>,
     *     source: string,
     * }
     */
    public function generate(array $input): array
    {
        $context = $this->buildContext($input);

        $fromLlm = $this->tryGenerateWithOpenAi($context);
        if ($fromLlm !== null) {
            return $this->normalizeResult($fromLlm, $context, 'openai');
        }

        return $this->normalizeResult($this->generateWithTemplates($context), $context, 'template');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildContext(array $input): array
    {
        $pageType = (string) ($input['page_type'] ?? 'custom');
        $title = trim((string) ($input['title'] ?? ''));
        $siteName = trim((string) ($input['site_name'] ?? config('app.name', 'Nexa')));
        $siteDescription = trim((string) ($input['site_description'] ?? ''));
        $moduleDisplay = trim((string) ($input['module_display_name'] ?? $input['module_name'] ?? ''));
        $companyName = trim((string) ($input['company_name'] ?? ''));

        $brand = $companyName !== '' ? $companyName : $siteName;
        if ($moduleDisplay !== '' && $moduleDisplay !== $brand) {
            $brand = $brand.' · '.$moduleDisplay;
        }

        return [
            'title' => $title,
            'page_type' => $pageType,
            'page_type_label' => $this->pageTypeLabel($pageType),
            'slug' => trim((string) ($input['slug'] ?? '')),
            'site_name' => $siteName,
            'site_description' => $siteDescription,
            'module_name' => trim((string) ($input['module_name'] ?? '')),
            'module_display_name' => $moduleDisplay,
            'company_name' => $companyName,
            'brand' => $brand,
            'include_sections' => (bool) ($input['include_sections'] ?? true),
        ];
    }

    private function pageTypeLabel(string $pageType): string
    {
        return match ($pageType) {
            'home' => 'Home',
            'about' => 'Over ons',
            'contact' => 'Contact',
            'module' => 'Modulepagina',
            default => 'Pagina',
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    private function tryGenerateWithOpenAi(array $context): ?array
    {
        $apiKey = config('services.openai.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        $model = config('services.openai.model', 'gpt-4o-mini');
        $prompt = $this->buildLlmPrompt($context);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.6,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Je bent een Nederlandse SEO- en GEO-specialist (Google + AI-zoekmachines). Antwoord uitsluitend met geldig JSON.',
                        ],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if (! $response->successful()) {
                return null;
            }

            $content = $response->json('choices.0.message.content');
            if (! is_string($content) || $content === '') {
                return null;
            }

            $decoded = json_decode($content, true);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildLlmPrompt(array $context): string
    {
        $payload = json_encode([
            'brand' => $context['brand'],
            'page_type' => $context['page_type_label'],
            'current_title' => $context['title'],
            'site_description' => $context['site_description'],
            'slug' => $context['slug'],
            'include_sections' => $context['include_sections'],
        ], JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Genereer SEO/GEO-teksten voor een Nederlandse websitepagina. JSON-schema:
{
  "title": "paginatitel, max 60 tekens, duidelijk merk + paginatype",
  "meta_description": "150-160 tekens, feitelijk, geen clickbait, geschikt voor Google snippet én AI-citaties",
  "sections": {
    "hero": {
      "title": "korte hero-titel",
      "subtitle": "1-2 zinnen ondertitel, plain text",
      "cta_primary_text": "primaire knop",
      "cta_secondary_text": "secundaire knop"
    }
  },
  "tips": ["max 3 korte tips voor structured data / E-E-A-T"]
}
Context: {$payload}
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function generateWithTemplates(array $context): array
    {
        $brand = (string) $context['brand'];
        $pageLabel = (string) $context['page_type_label'];
        $title = (string) $context['title'];
        $siteDescription = (string) $context['site_description'];

        $pageTitle = $title !== '' ? $title : $pageLabel;
        $seoTitle = $this->truncate($pageTitle.' | '.$brand, 60);

        $meta = $this->buildMetaDescription($context, $pageTitle, $brand, $siteDescription);

        $sections = [];
        if ($context['include_sections']) {
            $sections['hero'] = $this->heroSectionCopy($context, $pageTitle, $brand);
        }

        return [
            'title' => $seoTitle,
            'meta_description' => $meta,
            'sections' => $sections,
            'tips' => $this->seoTips($context),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildMetaDescription(array $context, string $pageTitle, string $brand, string $siteDescription): string
    {
        $pageType = (string) $context['page_type'];
        $fragments = [];

        if ($siteDescription !== '') {
            $fragments[] = Str::limit($siteDescription, 80, '');
        }

        $fragments[] = match ($pageType) {
            'home' => "{$brand}: {$pageTitle}. Betrouwbare partner voor uw doelgroep — duidelijke diensten, contact en actuele informatie.",
            'about' => "Leer {$brand} kennen. {$pageTitle}: missie, expertise en waarom klanten voor ons kiezen.",
            'contact' => "Neem contact op met {$brand}. {$pageTitle}: bereikbaarheid, locatie en snelle reactie op vragen.",
            default => "{$pageTitle} bij {$brand}. Relevante informatie, heldere uitleg en een duidelijke volgende stap voor bezoekers.",
        };

        $meta = $this->truncate(implode(' ', array_filter($fragments)), self::META_MAX);

        if (mb_strlen($meta) < 120) {
            $meta = $this->truncate(
                "{$brand} — {$pageTitle}. Ontdek wat wij doen, voor wie wij werken en hoe u direct contact opneemt. Geschikt voor Google en AI-overzichten.",
                self::META_MAX
            );
        }

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    private function heroSectionCopy(array $context, string $pageTitle, string $brand): array
    {
        $pageType = (string) $context['page_type'];

        return match ($pageType) {
            'home' => [
                'title' => $pageTitle !== '' ? $pageTitle : "Welkom bij {$brand}",
                'subtitle' => "{$brand} helpt u met professionele oplossingen. Ontdek onze diensten en neem vrijblijvend contact op.",
                'cta_primary_text' => 'Neem contact op',
                'cta_secondary_text' => 'Meer informatie',
            ],
            'about' => [
                'title' => $pageTitle !== '' ? $pageTitle : "Over {$brand}",
                'subtitle' => "Wij zijn {$brand}: ervaring, transparantie en focus op resultaat voor onze klanten.",
                'cta_primary_text' => 'Ons team',
                'cta_secondary_text' => 'Contact',
            ],
            'contact' => [
                'title' => $pageTitle !== '' ? $pageTitle : 'Contact',
                'subtitle' => "Vragen voor {$brand}? Stuur een bericht — wij reageren zo snel mogelijk.",
                'cta_primary_text' => 'Bericht sturen',
                'cta_secondary_text' => 'Bel ons',
            ],
            default => [
                'title' => $pageTitle,
                'subtitle' => "Alles over {$pageTitle} bij {$brand}. Duidelijke uitleg en een sterke volgende stap.",
                'cta_primary_text' => 'Start hier',
                'cta_secondary_text' => 'Meer lezen',
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<string>
     */
    private function seoTips(array $context): array
    {
        $brand = (string) $context['brand'];

        return [
            "Gebruik de paginatitel en meta-omschrijving consistent — AI-zoekmachines citeren vaak de eerste zin met merknaam ({$brand}).",
            'Op de live pagina wordt automatisch structured data (Organization + WebPage) in JSON-LD gezet voor Google en AI.',
            'Houd hero-teksten feitelijk en scanbaar: wie u bent, wat u doet, en welke actie bezoekers kunnen nemen.',
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $context
     * @return array{
     *     title: string,
     *     meta_description: string,
     *     sections: array<string, array<string, string>>,
     *     tips: list<string>,
     *     source: string,
     * }
     */
    private function normalizeResult(array $raw, array $context, string $source): array
    {
        $title = trim((string) ($raw['title'] ?? $context['title'] ?? ''));
        if ($title === '') {
            $title = (string) ($context['page_type_label'] ?? 'Pagina');
        }
        $title = $this->truncate($title, 60);

        $meta = trim((string) ($raw['meta_description'] ?? ''));
        if ($meta === '') {
            $meta = $this->buildMetaDescription(
                $context,
                (string) ($context['title'] ?? $title),
                (string) $context['brand'],
                (string) $context['site_description']
            );
        }
        $meta = $this->truncate($meta, self::META_MAX);

        $sections = [];
        if ($context['include_sections'] && isset($raw['sections']) && is_array($raw['sections'])) {
            $hero = $raw['sections']['hero'] ?? null;
            if (is_array($hero)) {
                $sections['hero'] = array_filter([
                    'title' => trim((string) ($hero['title'] ?? '')),
                    'subtitle' => trim(strip_tags((string) ($hero['subtitle'] ?? ''))),
                    'cta_primary_text' => trim((string) ($hero['cta_primary_text'] ?? '')),
                    'cta_secondary_text' => trim((string) ($hero['cta_secondary_text'] ?? '')),
                ], fn ($v) => $v !== '');
            }
        }
        if ($sections === [] && $context['include_sections']) {
            $sections['hero'] = $this->heroSectionCopy(
                $context,
                (string) ($context['title'] ?? $title),
                (string) $context['brand']
            );
        }

        $tips = [];
        if (isset($raw['tips']) && is_array($raw['tips'])) {
            foreach ($raw['tips'] as $tip) {
                $t = trim((string) $tip);
                if ($t !== '') {
                    $tips[] = $t;
                }
            }
        }
        if ($tips === []) {
            $tips = $this->seoTips($context);
        }

        return [
            'title' => $title,
            'meta_description' => $meta,
            'sections' => $sections,
            'tips' => array_slice($tips, 0, 5),
            'source' => $source,
        ];
    }

    private function truncate(string $text, int $max): string
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max - 1);
        $lastSpace = mb_strrpos($cut, ' ');
        if ($lastSpace !== false && $lastSpace > (int) ($max * 0.6)) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, '.,;:-').'…';
    }
}
