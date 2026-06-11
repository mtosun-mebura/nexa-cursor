<?php

namespace App\DTO\AiChat;

use App\Enums\AiChat\AiChatIntent;
use App\Enums\AiChat\AiChatResponseMode;

final readonly class AiChatIntentResult
{
    public function __construct(
        public AiChatIntent $intent,
        public bool $isAdmin,
        public bool $allowLiveData,
        public bool $allowPublicRates = false,
        public ?string $queryHint = null,
        public AiChatResponseMode $responseMode = AiChatResponseMode::List,
    ) {}

    /**
     * @return array{
     *     intent: string,
     *     isAdmin: bool,
     *     allowLiveData: bool,
     *     allowPublicRates: bool,
     *     query_hint?: string,
     *     response_mode: string
     * }
     */
    public function toArray(): array
    {
        $payload = [
            'intent' => $this->intent->value,
            'isAdmin' => $this->isAdmin,
            'allowLiveData' => $this->allowLiveData,
            'allowPublicRates' => $this->allowPublicRates,
            'response_mode' => $this->responseMode->value,
        ];

        if ($this->queryHint !== null && $this->queryHint !== '') {
            $payload['query_hint'] = $this->queryHint;
        }

        return $payload;
    }
}
