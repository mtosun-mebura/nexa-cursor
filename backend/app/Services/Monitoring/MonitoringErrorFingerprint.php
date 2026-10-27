<?php

namespace App\Services\Monitoring;

use App\Models\ErrorLog;

/**
 * Korte, stabiele sleutel per soort fout: ids, getallen, paden en tekstwaarden eruit,
 * zodat dezelfde fout met andere ids één keer wordt opgelost en één keer aan de AI wordt voorgelegd.
 */
class MonitoringErrorFingerprint
{
    public function for(ErrorLog $log): string
    {
        $context = is_array($log->context) ? $log->context : [];

        return sha1(implode('|', [
            (string) $log->source,
            (string) ($context['exception'] ?? ''),
            $this->normalize((string) $log->message),
        ]));
    }

    public function normalize(string $message): string
    {
        $text = mb_strtolower($message);
        $text = preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', '?', $text) ?? $text;
        $text = preg_replace('~(?:/[\w.\-@]+){2,}~', '?', $text) ?? $text;
        $text = preg_replace('/[\w.+\-]+@[\w\-]+\.[\w.\-]+/', '?', $text) ?? $text;
        $text = preg_replace("/'[^']*'/", '?', $text) ?? $text;
        $text = preg_replace('/\b[0-9a-f]{8,}\b/', '?', $text) ?? $text;
        $text = preg_replace('/\d+/', '?', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? $text;

        return mb_substr($text, 0, 300);
    }
}
