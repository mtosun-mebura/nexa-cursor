<?php

namespace App\DTO\AiChat;

use App\Enums\AiChat\AiChatIntent;

final readonly class AiChatWebhookPayload
{
    public function __construct(
        public AiChatRequestContext $context,
        public string $message,
        public AiChatIntentResult $intent,
        public ?string $sqlToken = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = array_merge(
            $this->context->toIdentityArray(),
            $this->intent->toArray(),
            [
                'message' => $this->message,
                'useRag' => $this->intent->intent->usesRag(),
            ]
        );

        if ($this->sqlToken !== null) {
            $payload['sql_token'] = $this->sqlToken;
        }

        $payload['laravel_live_query_url'] = self::resolveLiveQueryUrl();
        $payload['laravel_rag_search_url'] = self::resolveRagSearchUrl();

        return $payload;
    }

    public static function resolveLiveQueryUrl(): string
    {
        $base = rtrim((string) config('ai_chat.laravel_api_url', config('app.url')), '/');
        $path = (string) config('ai_chat.laravel_live_query_path', '/api/ai-chat/live-query');

        return $base.$path;
    }

    public static function resolveRagSearchUrl(): string
    {
        $base = rtrim((string) config('ai_chat.laravel_api_url', config('app.url')), '/');
        $path = (string) config('ai_chat.laravel_rag_search_path', '/integrations/n8n/ai-chat/rag-search');

        return $base.$path;
    }
}
