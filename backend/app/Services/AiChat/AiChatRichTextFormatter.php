<?php

namespace App\Services\AiChat;

/**
 * Zet WYSIWYG/HTML om naar chat-tekst met markdown-links [label](url).
 */
final class AiChatRichTextFormatter
{
    /**
     * Woorden die in vastgeplakte PDF-tekst een nieuwe zin/kop starten.
     * Geen generieke camelCase-split: die breekt iPhone, iPad, WhatsApp, MacBook.
     */
    private const SENTENCE_STARTERS = 'De|Het|Een|In|Op|Voor|Na|Bij|Met|Van|Aan|Uit|Over|Onder|Deze|Dit|Dat|Die|Als|Wanneer|Artikel|The|This|That|For|With|From';

    public function htmlToChatText(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $value = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace_callback(
            '/<a\s+[^>]*href=(["\'])([^"\']+)\1[^>]*>(.*?)<\/a>/is',
            function (array $matches): string {
                $url = trim($matches[2]);
                $label = trim(strip_tags($matches[3]));
                if ($label === '') {
                    $label = $url;
                }

                return '['.$label.']('.$url.')';
            },
            $value
        ) ?? $value;

        $value = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $value) ?? $value;
        $value = preg_replace('/<br\s*\/?>/i', "\n", $value) ?? $value;
        $value = preg_replace('/<\/li>\s*<li[^>]*>/i', "\n- ", $value) ?? $value;
        $value = preg_replace('/<li[^>]*>/i', "\n- ", $value) ?? $value;
        $value = preg_replace('/<\/li>/i', '', $value) ?? $value;
        $value = preg_replace('/<\/?(ul|ol)[^>]*>/i', "\n", $value) ?? $value;
        $value = preg_replace('/<h[1-6][^>]*>/i', "\n\n", $value) ?? $value;
        $value = preg_replace('/<\/h[1-6]>/i', "\n", $value) ?? $value;
        $value = strip_tags($value);
        $value = str_replace("\xc2\xa0", ' ', $value);
        $value = preg_replace("/[ \t]+\n/u", "\n", $value) ?? $value;
        $value = preg_replace("/\n{3,}/u", "\n\n", $value) ?? $value;

        return $this->ungluePdfText(trim($value));
    }

    /**
     * Herstelt PDF/kennisbank-tekst waarin woorden en zinnen aan elkaar geplakt zijn.
     */
    public function ungluePdfText(string $text): string
    {
        $value = str_replace("\xc2\xa0", ' ', $text);
        $value = preg_replace('/\r\n?/', "\n", $value) ?? $value;

        $placeholders = [];
        $value = $this->protectUnglueTokens($value, $placeholders);

        $value = preg_replace('/([.!?])([A-ZÀ-Ý])/u', '$1 $2', $value) ?? $value;
        $value = preg_replace(
            '/([a-zà-ÿ])('.self::SENTENCE_STARTERS.')(?=\s|$|[.!,?;:])/u',
            "$1\n\n$2",
            $value
        ) ?? $value;
        $value = preg_replace('/\b(artikel\s+\d+)/iu', "\n\n$1", $value) ?? $value;
        $value = preg_replace("/[ \t]+\n/u", "\n", $value) ?? $value;
        $value = preg_replace("/\n{3,}/u", "\n\n", $value) ?? $value;
        $value = preg_replace('/[ \t]{2,}/u', ' ', $value) ?? $value;

        return trim($this->restoreUnglueTokens($value, $placeholders));
    }

    /**
     * @param  array<string, string>  $placeholders
     */
    private function protectUnglueTokens(string $value, array &$placeholders): string
    {
        $protect = static function (array $matches) use (&$placeholders): string {
            $token = '§UG'.count($placeholders).'§';
            $placeholders[$token] = $matches[0];

            return $token;
        };

        $value = preg_replace_callback('/\[[^\]]+\]\([^)]+\)/', $protect, $value) ?? $value;
        // iPhone, iPad, iOS, eBay: 1–2 kleine letters + hoofdletter, één token.
        $value = preg_replace_callback('/\b[a-zà-ÿ]{1,2}[A-ZÀ-Ý][A-Za-zÀ-ÿ]*\b/u', $protect, $value) ?? $value;

        return $value;
    }

    /**
     * @param  array<string, string>  $placeholders
     */
    private function restoreUnglueTokens(string $value, array $placeholders): string
    {
        if ($placeholders === []) {
            return $value;
        }

        return strtr($value, $placeholders);
    }
}
