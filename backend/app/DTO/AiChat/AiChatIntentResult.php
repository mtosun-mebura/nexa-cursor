<?php

namespace App\DTO\AiChat;

use App\Enums\AiChat\AiChatIntent;

final readonly class AiChatIntentResult
{
    public function __construct(
        public AiChatIntent $intent,
        public bool $isAdmin,
        public bool $allowLiveData,
        public bool $allowPublicRates = false,
    ) {}

    /**
     * @return array{intent: string, isAdmin: bool, allowLiveData: bool, allowPublicRates: bool}
     */
    public function toArray(): array
    {
        return [
            'intent' => $this->intent->value,
            'isAdmin' => $this->isAdmin,
            'allowLiveData' => $this->allowLiveData,
            'allowPublicRates' => $this->allowPublicRates,
        ];
    }
}
