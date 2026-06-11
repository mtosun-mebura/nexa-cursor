<?php

namespace App\Services\AiChat;

use App\DTO\AiChat\AiChatIntentResult;
use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatIntent;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

final class AiChatSqlTokenService
{
    public function issue(AiChatRequestContext $context, AiChatIntentResult $intent): ?string
    {
        if (! $intent->allowLiveData && ! $intent->allowPublicRates) {
            return null;
        }

        $ttl = max(30, (int) config('ai_chat.sql_token_ttl_seconds', 120));

        $payload = [
            'company_id' => $context->companyId,
            'channel' => $context->channel->value,
            'user_id' => $context->userId,
            'intent' => $intent->intent->value,
            'allow_live_data' => $intent->allowLiveData,
            'allow_public_rates' => $intent->allowPublicRates,
            'response_mode' => $intent->responseMode->value,
            'exp' => now()->addSeconds($ttl)->timestamp,
        ];

        if ($intent->queryHint !== null && $intent->queryHint !== '') {
            $payload['query_hint'] = $intent->queryHint;
        }

        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{
     *     company_id: int,
     *     user_id: ?int,
     *     intent: string,
     *     allow_live_data: bool,
     *     allow_public_rates: bool,
     *     exp: int
     * }
     */
    public function validate(string $token, AiChatIntent $expectedIntent): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new RuntimeException('Ongeldig of verlopen SQL-token.');
        }

        if (! is_array($payload)) {
            throw new RuntimeException('Ongeldig SQL-token.');
        }

        foreach (['company_id', 'channel', 'intent', 'allow_live_data', 'allow_public_rates', 'exp'] as $key) {
            if (! array_key_exists($key, $payload)) {
                throw new RuntimeException('SQL-token mist verplichte claims.');
            }
        }

        if ((int) $payload['exp'] < now()->timestamp) {
            throw new RuntimeException('SQL-token is verlopen.');
        }

        if ((string) $payload['intent'] !== $expectedIntent->value) {
            throw new RuntimeException('SQL-token intent komt niet overeen.');
        }

        $allowLiveData = $payload['allow_live_data'] === true;
        $allowPublicRates = $payload['allow_public_rates'] === true;

        if ($expectedIntent === AiChatIntent::Tarieven) {
            if (! $allowPublicRates) {
                throw new RuntimeException('Publieke tarieven zijn niet toegestaan voor dit token.');
            }
        } elseif (! $allowLiveData) {
            throw new RuntimeException('Live data is niet toegestaan voor dit token.');
        }

        return [
            'company_id' => (int) $payload['company_id'],
            'channel' => (string) $payload['channel'],
            'user_id' => isset($payload['user_id']) ? (int) $payload['user_id'] : null,
            'intent' => (string) $payload['intent'],
            'allow_live_data' => $allowLiveData,
            'allow_public_rates' => $allowPublicRates,
            'exp' => (int) $payload['exp'],
            'query_hint' => isset($payload['query_hint']) ? (string) $payload['query_hint'] : null,
            'response_mode' => isset($payload['response_mode']) ? (string) $payload['response_mode'] : 'list',
        ];
    }
}
