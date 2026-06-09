<?php

namespace App\Services\AiChat;

/**
 * Zet WYSIWYG/HTML om naar chat-tekst met markdown-links [label](url).
 */
final class AiChatRichTextFormatter
{
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
        $value = strip_tags($value);
        $value = str_replace("\xc2\xa0", ' ', $value);
        $value = preg_replace("/[ \t]+\n/u", "\n", $value) ?? $value;
        $value = preg_replace("/\n{3,}/u", "\n\n", $value) ?? $value;

        return trim($value);
    }
}
