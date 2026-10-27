<?php

namespace App\Services\Monitoring;

use App\Models\ErrorLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Compacte AI-diagnose: één kleine JSON-vraag per unieke foutvingerafdruk, resultaat 7 dagen gecachet.
 * De AI mag alleen een actie uit MonitoringSafeAction voorstellen; de rest is tekst voor de super-admin.
 */
class MonitoringAiDiagnoser
{
    private const CACHE_DAYS = 7;

    public function __construct(private MonitoringErrorFingerprint $fingerprints) {}

    public function configured(): bool
    {
        $key = config('services.openai.api_key');

        return is_string($key) && trim($key) !== '';
    }

    /**
     * @return array{category: string, safe_action: ?string, diagnosis_nl: string, proposed_fix_nl: string}|null
     */
    public function diagnose(ErrorLog $log, string $fingerprint): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        $cacheKey = 'monitoring:ai:'.$fingerprint;
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->ask($log);
        if ($result !== null) {
            Cache::put($cacheKey, $result, now()->addDays(self::CACHE_DAYS));
        }

        return $result;
    }

    /**
     * @return array{category: string, safe_action: ?string, diagnosis_nl: string, proposed_fix_nl: string}|null
     */
    private function ask(ErrorLog $log): ?array
    {
        $context = is_array($log->context) ? $log->context : [];
        $payload = json_encode(array_filter([
            'bron' => (string) $log->source,
            'exception' => (string) ($context['exception'] ?? ''),
            'bericht' => $this->fingerprints->normalize((string) $log->message),
            'frames' => array_slice(array_map('strval', (array) ($context['frames'] ?? [])), 0, 3),
            'bestand' => isset($context['file']) ? $context['file'].':'.($context['line'] ?? '') : '',
        ], fn ($value) => $value !== '' && $value !== []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $actions = implode(', ', MonitoringSafeAction::values());
        $prompt = <<<PROMPT
Classificeer deze Laravel-serverfout. Antwoord alleen met JSON:
{"category":"korte_snake_case","safe_action":null|"<een van: {$actions}>","diagnosis_nl":"max 2 zinnen Nederlands","proposed_fix_nl":"max 2 zinnen Nederlands, concreet"}
Regels: safe_action alleen als die de fout zeker en veilig oplost, anders null. Nooit iets voorstellen dat betalingen, facturen, incasso of terugbetalingen raakt. Verzin geen acties buiten de lijst.
Fout: {$payload}
PROMPT;

        try {
            $response = Http::withToken((string) config('services.openai.api_key'))
                ->timeout(20)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'temperature' => 0.1,
                    'max_tokens' => 300,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => 'Je bent een zuinige Laravel-foutclassificeerder. Antwoord uitsluitend met geldig JSON.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('monitoring.ai_diagnosis_failed', ['status' => $response->status()]);

                return null;
            }

            $decoded = json_decode((string) $response->json('choices.0.message.content'), true);
        } catch (Throwable $e) {
            Log::warning('monitoring.ai_diagnosis_failed', ['error' => class_basename($e)]);

            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        $action = $decoded['safe_action'] ?? null;

        return [
            'category' => mb_substr(trim((string) ($decoded['category'] ?? 'onbekend')), 0, 40),
            'safe_action' => is_string($action) && trim($action) !== '' ? trim($action) : null,
            'diagnosis_nl' => mb_substr(trim((string) ($decoded['diagnosis_nl'] ?? '')), 0, 400),
            'proposed_fix_nl' => mb_substr(trim((string) ($decoded['proposed_fix_nl'] ?? '')), 0, 400),
        ];
    }
}
