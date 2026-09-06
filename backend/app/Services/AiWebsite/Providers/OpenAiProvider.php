<?php

namespace App\Services\AiWebsite\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiProvider implements AiProviderInterface
{
    public function generateStructured(string $promptVersion, array $payload, string $systemPrompt): ?array
    {
        $apiKey = config('services.openai.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        $model = (string) config('ai_website.planner_model', config('services.openai.model', 'gpt-4o-mini'));
        $user = json_encode(
            array_merge(['prompt_version' => $promptVersion], $payload),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if ($user === false) {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.4,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);
            if (! $response->successful()) {
                Log::warning('AiWebsite OpenAI HTTP-fout', [
                    'status' => $response->status(),
                    'prompt_version' => $promptVersion,
                ]);

                return null;
            }
            $decoded = json_decode((string) $response->json('choices.0.message.content'), true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $e) {
            Log::warning('AiWebsite OpenAI uitzondering', [
                'error' => $e->getMessage(),
                'prompt_version' => $promptVersion,
            ]);

            return null;
        }
    }
}
