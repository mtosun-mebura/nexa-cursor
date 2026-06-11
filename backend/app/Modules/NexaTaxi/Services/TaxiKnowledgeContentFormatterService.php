<?php

namespace App\Modules\NexaTaxi\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Maakt ruwe kennisbanktekst leesbaar voor de AI-chat (volledig of ingekort).
 */
final class TaxiKnowledgeContentFormatterService
{
    private const SHORT_MAX_CHARS = 520;

    /**
     * @return array{html: string, mode: string, source: string}
     */
    public function format(string $input, bool $shorten = false, ?string $title = null, ?string $category = null): array
    {
        $plain = $this->toPlainText($input);
        $plain = $this->stripAiPreamble($plain);
        $plain = $this->normalizePlainText($plain);

        if ($plain === '') {
            return [
                'html' => '<p></p>',
                'mode' => $shorten ? 'short' : 'full',
                'source' => 'empty',
            ];
        }

        $fromLlm = $this->tryFormatWithOpenAi($plain, $shorten, $title, $category);
        if ($fromLlm !== null) {
            return [
                'html' => $this->sanitizeHtml($fromLlm),
                'mode' => $shorten ? 'short' : 'full',
                'source' => 'openai',
            ];
        }

        if ($shorten) {
            if ($this->isAlreadyShortened($plain)) {
                return [
                    'html' => $this->ensureShortDisplayHtml($input, $plain),
                    'mode' => 'short',
                    'source' => 'noop',
                ];
            }

            return [
                'html' => $this->shortenToHtml($plain, $title, $category),
                'mode' => 'short',
                'source' => 'rules',
            ];
        }

        return [
            'html' => $this->structureLongContentAsHtml($plain, $title),
            'mode' => 'full',
            'source' => 'rules',
        ];
    }

    private function toPlainText(string $input): string
    {
        $text = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/li>\s*<li[^>]*>/i', "\n- ", $text) ?? $text;
        $text = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $text) ?? $text;
        $text = preg_replace('/<\/h[1-6]>/i', "\n\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return trim($text);
    }

    private function stripAiPreamble(string $text): string
    {
        $patterns = [
            '/^De onderstaande tekst is netjes opgemaakt[^.]*\.\s*/iu',
            '/^Gebaseerd op het aangeleverde document\.?\s*/iu',
            '/^Hieronder (?:staat|volgt)[^.]*\.\s*/iu',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text) ?? $text;
        }

        return trim($text);
    }

    private function normalizePlainText(string $text): string
    {
        $text = preg_replace("/[ \t]+/u", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        // Ontbrekende spaties na punten (plaktekst uit PDF/Word)
        $text = preg_replace('/\.([A-ZÀ-ÖØ-Þ])/u', ". $1", $text) ?? $text;
        $text = preg_replace('/\)([A-ZÀ-ÖØ-Þ])/u', ') $1', $text) ?? $text;

        // Inline opsommingen: "tekst - item - item" → eigen regels
        $text = preg_replace('/\s+-\s+/', "\n- ", $text) ?? $text;

        // Intro vóór opsomming op aparte alinea
        $text = preg_replace('/:\s*\n-\s+/', ":\n\n- ", $text) ?? $text;

        // Artikelen en koppen op eigen regel
        $text = preg_replace('/\s*(Artikel\s+\d+\s*[–—-]\s*)/iu', "\n\n$1", $text) ?? $text;
        $text = preg_replace('/\s*(Gedeponeerd door\s+)/iu', "\n\n$1", $text) ?? $text;

        return trim($text);
    }

    private function structureLongContentAsHtml(string $plain, ?string $title = null): string
    {
        $lines = preg_split('/\n+/u', $plain) ?: [$plain];
        $htmlParts = [];
        $listItems = [];
        $isFirstContent = true;

        $flushList = function () use (&$listItems, &$htmlParts): void {
            if ($listItems === []) {
                return;
            }
            $htmlParts[] = '<ul>'.implode('', array_map(
                static fn (string $item): string => '<li>'.e($item).'</li>',
                $listItems
            )).'</ul>';
            $listItems = [];
        };

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $flushList();
                continue;
            }

            if (preg_match('/^-\s+(.+)$/u', $line, $matches)) {
                $listItems[] = trim($matches[1]);
                $isFirstContent = false;

                continue;
            }

            $flushList();

            if (preg_match('/^Artikel\s+\d+/iu', $line)) {
                $htmlParts[] = '<h3>'.e($line).'</h3>';
                $isFirstContent = false;

                continue;
            }

            if ($isFirstContent && $title !== null && trim($title) !== '' && mb_stripos($line, trim($title)) === 0) {
                $remainder = trim(mb_substr($line, mb_strlen(trim($title))));
                $htmlParts[] = '<h3>'.e(trim($title)).'</h3>';
                if ($remainder !== '') {
                    $htmlParts[] = '<p>'.e($remainder).'</p>';
                }
                $isFirstContent = false;

                continue;
            }

            if ($isFirstContent && mb_strlen($line) <= 80 && ! str_ends_with($line, '.')) {
                $htmlParts[] = '<h3>'.e($line).'</h3>';
                $isFirstContent = false;

                continue;
            }

            if (preg_match('/^(Gedeponeerd door|Algemene Voorwaarden)/iu', $line)) {
                $htmlParts[] = '<p><strong>'.e($line).'</strong></p>';
            } else {
                $htmlParts[] = '<p>'.e($line).'</p>';
            }

            $isFirstContent = false;
        }

        $flushList();

        if ($htmlParts === []) {
            return '<p>'.e($plain).'</p>';
        }

        return implode("\n", $htmlParts);
    }

    private function shortenToHtml(string $plain, ?string $title, ?string $category): string
    {
        $lines = preg_split('/\n+/u', $plain) ?: [$plain];
        $introLines = [];
        $bulletItems = [];
        $seenBullets = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^-\s+(.+)$/u', $line, $matches)) {
                $seenBullets = true;
                $bulletItems[] = trim($matches[1]);

                continue;
            }
            if ($seenBullets) {
                continue;
            }
            $introLines[] = $line;
        }

        if ($bulletItems !== []) {
            $intro = $this->pickShortIntro($introLines, $title);
            $pickedBullets = array_slice($this->scoreBulletItems($bulletItems, $category), 0, 3);
            $parts = [];
            if ($intro !== '') {
                $parts[] = '<p>'.e($intro).'</p>';
            }
            if ($pickedBullets !== []) {
                $parts[] = '<ul>'.implode('', array_map(
                    static fn (string $item): string => '<li>'.e($item).'</li>',
                    $pickedBullets
                )).'</ul>';
            }

            return implode("\n", $parts) ?: '<p>'.e(Str::limit($plain, self::SHORT_MAX_CHARS)).'</p>';
        }

        return $this->plainToSimpleHtml($this->shortenPlainText($plain, $title, $category));
    }

    /**
     * @param  list<string>  $introLines
     */
    private function pickShortIntro(array $introLines, ?string $title): string
    {
        $intro = trim(implode(' ', $introLines));
        if ($intro === '' && $title !== null) {
            return trim($title);
        }

        if (mb_strlen($intro) > 220) {
            return Str::limit($intro, 220);
        }

        return $intro;
    }

    /**
     * @param  list<string>  $items
     * @return list<string>
     */
    private function scoreBulletItems(array $items, ?string $category): array
    {
        $priorityTerms = $this->priorityTermsForCategory($category);
        $scored = [];

        foreach ($items as $index => $item) {
            $score = max(0, 10 - $index);
            $lower = mb_strtolower($item);
            foreach ($priorityTerms as $term) {
                if (str_contains($lower, $term)) {
                    $score += 3;
                }
            }
            $scored[] = ['item' => $item, 'score' => $score, 'index' => $index];
        }

        usort($scored, function (array $a, array $b): int {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            return $a['index'] <=> $b['index'];
        });

        return array_values(array_map(static fn (array $row): string => $row['item'], $scored));
    }

    private function shortenPlainText(string $plain, ?string $title, ?string $category): string
    {
        $sentences = $this->splitSentences($plain);
        if ($sentences === []) {
            return Str::limit($plain, self::SHORT_MAX_CHARS);
        }

        $priorityTerms = $this->priorityTermsForCategory($category);
        $scored = [];

        foreach ($sentences as $index => $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '' || mb_strlen($sentence) < 20) {
                continue;
            }

            $score = 0;
            $lower = mb_strtolower($sentence);
            foreach ($priorityTerms as $term) {
                if (str_contains($lower, $term)) {
                    $score += 3;
                }
            }

            if ($index < 3) {
                $score += 1;
            }

            if (preg_match('/^Artikel\s+\d+/iu', $sentence)) {
                $score -= 5;
            }

            $scored[] = ['sentence' => $sentence, 'score' => $score, 'index' => $index];
        }

        usort($scored, function (array $a, array $b): int {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            return $a['index'] <=> $b['index'];
        });

        $picked = [];
        $length = 0;

        foreach ($scored as $item) {
            $sentence = $item['sentence'];
            $nextLength = $length + mb_strlen($sentence) + 1;
            if ($nextLength > self::SHORT_MAX_CHARS && $picked !== []) {
                break;
            }
            $picked[] = $sentence;
            $length = $nextLength;
            if (count($picked) >= 3) {
                break;
            }
        }

        if ($picked === []) {
            $picked[] = $sentences[0];
        }

        $intro = trim((string) $title);
        if ($intro !== '' && ! str_contains(mb_strtolower(implode(' ', $picked)), mb_strtolower($intro))) {
            array_unshift($picked, $intro);
        }

        return implode(' ', array_unique($picked));
    }

    /**
     * @return list<string>
     */
    private function splitSentences(string $text): array
    {
        $chunks = preg_split('/(?<=[.!?…])\s+/u', $text) ?: [];

        return array_values(array_filter(array_map('trim', $chunks)));
    }

    /**
     * @return list<string>
     */
    private function priorityTermsForCategory(?string $category): array
    {
        return match ($category) {
            'voorwaarden', 'annuleren' => ['annuleer', 'kosteloos', 'vooraf', 'voorwaarde', '24 uur', 'wijzig'],
            'tarieven' => ['tarief', 'kost', 'prijs', 'kilometer', 'instap'],
            'contact' => ['telefoon', 'bereikbaar', 'contact', 'e-mail', 'adres'],
            'betalen' => ['betaal', 'pin', 'contant', 'creditcard', 'factuur'],
            'reserveren' => ['reserveer', 'boek', 'afspraak', 'online'],
            default => ['contact', 'pagina', 'website', 'link', 'service', 'vervoer', 'rit', 'beschikbaar'],
        };
    }

    private function isAlreadyShortened(string $plain): bool
    {
        if (mb_strlen($plain) > self::SHORT_MAX_CHARS + 60) {
            return false;
        }

        if (preg_match('/Artikel\s+\d+/iu', $plain)) {
            return false;
        }

        $bulletCount = preg_match_all('/^-\s+/mu', $plain) ?: 0;

        return $bulletCount <= 3;
    }

    private function ensureShortDisplayHtml(string $input, string $plain): string
    {
        $html = trim($input);
        if ($html !== '' && $html !== $plain && preg_match('/<(p|ul|h3|li)\b/i', $html)) {
            return $this->sanitizeHtml($html);
        }

        return $this->shortenToHtml($plain, null, null);
    }

    private function plainToSimpleHtml(string $plain): string
    {
        $plain = trim($plain);
        if ($plain === '') {
            return '<p></p>';
        }

        return '<p>'.e($plain).'</p>';
    }

    private function sanitizeHtml(string $html): string
    {
        $allowed = '<p><h3><strong><em><ul><ol><li><br><a>';
        $clean = strip_tags($html, $allowed);

        return trim($clean) !== '' ? $clean : '<p></p>';
    }

    public function normalizeDisplayHtml(string $html): string
    {
        if (trim($html) === '') {
            return '<p></p>';
        }

        $html = $this->repairListsWithoutLi($html);
        $html = $this->repairBulletParagraphs($html);
        $html = $this->repairConsecutiveShortParagraphLists($html);

        return $this->sanitizeHtml($html);
    }

    private function repairBulletParagraphs(string $html): string
    {
        $repaired = preg_replace_callback(
            '/<p[^>]*>(.*?)<\/p>/is',
            function (array $matches): string {
                $inner = trim($matches[1]);
                if ($inner === '' || stripos($inner, '<ul') !== false || stripos($inner, '<li') !== false) {
                    return $matches[0];
                }

                $plain = html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $plain = trim(str_replace("\xc2\xa0", ' ', $plain));

                if (preg_match('/(?:^|\n)\s*-\s+/u', $plain)) {
                    $lines = preg_split('/\n+/u', $plain) ?: [];
                    $introParts = [];
                    $bullets = [];
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line === '') {
                            continue;
                        }
                        if (preg_match('/^-\s+(.+)$/u', $line, $bulletMatch)) {
                            $bullets[] = trim($bulletMatch[1]);
                        } elseif ($bullets === []) {
                            $introParts[] = $line;
                        }
                    }

                    if ($bullets !== []) {
                        $html = $introParts !== [] ? '<p>'.e(implode(' ', $introParts)).'</p>' : '';
                        $html .= '<ul>'.implode('', array_map(
                            static fn (string $bullet): string => '<li>'.e($bullet).'</li>',
                            $bullets
                        )).'</ul>';

                        return $html;
                    }
                }

                if (substr_count($plain, ' - ') >= 2) {
                    $parts = preg_split('/\s+-\s+/u', $plain) ?: [];
                    $parts = array_values(array_filter(array_map('trim', $parts), static fn (string $part): bool => $part !== ''));
                    if (count($parts) >= 3) {
                        $intro = array_shift($parts);

                        return '<p>'.e($intro).'</p><ul>'.implode('', array_map(
                            static fn (string $part): string => '<li>'.e($part).'</li>',
                            $parts
                        )).'</ul>';
                    }
                }

                return $matches[0];
            },
            $html
        );

        return is_string($repaired) ? $repaired : $html;
    }

    private function repairConsecutiveShortParagraphLists(string $html): string
    {
        if (stripos($html, '<ul') !== false) {
            return $html;
        }

        $repaired = preg_replace_callback(
            '/((?:<h3[^>]*>.*?<\/h3>\s*)?(?:<p[^>]*>.*?<\/p>\s*))((?:<p[^>]*>.*?<\/p>\s*){2,})/is',
            function (array $matches): string {
                $prefix = $matches[1];
                preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $matches[2], $paragraphs, PREG_SET_ORDER);
                if (count($paragraphs) < 2) {
                    return $matches[0];
                }

                $items = [];
                foreach ($paragraphs as $paragraph) {
                    $text = trim(html_entity_decode(strip_tags($paragraph[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if ($text === '' || mb_strlen($text) > 140) {
                        return $matches[0];
                    }
                    $items[] = $text;
                }

                if (count($items) < 2) {
                    return $matches[0];
                }

                return $prefix.'<ul>'.implode('', array_map(
                    static fn (string $item): string => '<li>'.e($item).'</li>',
                    $items
                )).'</ul>';
            },
            $html,
            1
        );

        return is_string($repaired) ? $repaired : $html;
    }

    private function repairListsWithoutLi(string $html): string
    {
        $repaired = preg_replace_callback(
            '/<ul([^>]*)>(.*?)<\/ul>/is',
            function (array $matches): string {
                $attrs = $matches[1];
                $inner = trim($matches[2]);
                if ($inner === '' || stripos($inner, '<li') !== false) {
                    return $matches[0];
                }

                if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $inner, $paragraphs, PREG_SET_ORDER)) {
                    $items = array_map(
                        static fn (array $paragraph): string => '<li>'.trim($paragraph[1]).'</li>',
                        $paragraphs
                    );

                    return '<ul'.$attrs.'>'.implode('', $items).'</ul>';
                }

                if (stripos($inner, '<') === false) {
                    $parts = preg_split('/(?<=[a-zà-ü])\s+(?=[A-ZÁ-ÜÄÖÏ])/u', trim($inner)) ?: [];
                    $parts = array_values(array_filter(array_map('trim', $parts), static fn (string $part): bool => $part !== ''));
                    if (count($parts) >= 2) {
                        $items = array_map(
                            static fn (string $part): string => '<li>'.e($part).'</li>',
                            $parts
                        );

                        return '<ul'.$attrs.'>'.implode('', $items).'</ul>';
                    }
                }

                return $matches[0];
            },
            $html
        );

        return is_string($repaired) ? $repaired : $html;
    }

    private function tryFormatWithOpenAi(string $plain, bool $shorten, ?string $title, ?string $category): ?string
    {
        $apiKey = config('services.openai.api_key');
        if (! is_string($apiKey) || $apiKey === '') {
            return null;
        }

        $model = (string) config('services.openai.model', 'gpt-4o-mini');
        $modeLabel = $shorten
            ? 'Schrijf een korte, klantvriendelijke samenvatting (maximaal 500 tekens platte tekst) geschikt voor een taxi-chatbot. Gebruik <p> en eventueel <ul><li>.'
            : 'Structureer de tekst netjes met HTML voor een rich-text editor: gebruik <p>, <h3> voor koppen, <ul><li> voor opsommingen, en <strong> voor nadruk. Geen AI-inleidingen.';

        $prompt = <<<PROMPT
{$modeLabel}

Titel: {$title}
Categorie: {$category}

Brontekst:
---
{$plain}
---

Antwoord uitsluitend met geldig JSON: {"html": "..."}
PROMPT;

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.3,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Je bent een Nederlandse redacteur voor een taxi-kennisbank. Gebruik alleen feiten uit de brontekst. Geen verzonnen informatie.',
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
            $html = is_array($decoded) ? ($decoded['html'] ?? null) : null;

            return is_string($html) && trim($html) !== '' ? $html : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
