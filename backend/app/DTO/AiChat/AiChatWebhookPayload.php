<?php

namespace App\DTO\AiChat;

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
            [
                'message' => $this->message,
                'intent' => $this->intent->intent->value,
                'isAdmin' => $this->intent->isAdmin,
                'allowLiveData' => $this->intent->allowLiveData,
                'allowPublicRates' => $this->intent->allowPublicRates,
            ]
        );

        if ($this->sqlToken !== null) {
            $payload['sql_token'] = $this->sqlToken;
        }

        $payload['laravel_live_query_url'] = self::resolveLiveQueryUrl();

        return $payload;
    }

    public static function resolveLiveQueryUrl(): string
    {
        $base = rtrim((string) config('ai_chat.laravel_api_url', config('app.url')), '/');
        $path = (string) config('ai_chat.laravel_live_query_path', '/api/ai-chat/live-query');

        return $base.$path;
    }
}
