<?php

namespace App\Services;

use Illuminate\Http\Request;

class PublicFormProtection
{
    public const HONEYPOT_FIELD = 'company_website';

    /**
     * Bots vullen verborgen velden vaak automatisch. Mensen laten dit leeg.
     */
    public function honeypotFilled(Request $request): bool
    {
        return trim((string) $request->input(self::HONEYPOT_FIELD, '')) !== '';
    }

    /**
     * Markup of event-handlers die in e-mail/HTML misbruikt kunnen worden.
     */
    public function containsMaliciousMarkup(string $value): bool
    {
        if (str_contains($value, "\0")) {
            return true;
        }

        return (bool) preg_match(
            '/<\s*(script|iframe|object|embed|link|meta|svg|form|img|video|audio|base)\b|javascript\s*:|data\s*:\s*text\/html|on(error|load|click|mouseover|focus|submit|change)\s*=/i',
            $value
        );
    }

    /**
     * Nieuwe regels in naam/e-mail kunnen e-mailheaders beïnvloeden.
     */
    public function containsHeaderInjection(string $value): bool
    {
        return str_contains($value, "\n") || str_contains($value, "\r") || str_contains($value, '%0a') || str_contains($value, '%0d');
    }

    public function sanitizePlainText(string $value, bool $allowNewlines = false): string
    {
        $value = str_replace("\0", '', $value);

        if ($allowNewlines) {
            $value = str_replace(["\r\n", "\r"], "\n", $value);
        } else {
            $value = str_replace(["\r", "\n"], ' ', $value);
        }

        $value = strip_tags($value);
        $value = preg_replace('/[^\P{C}\n\t]/u', '', $value) ?? $value;

        return trim($value);
    }
}
