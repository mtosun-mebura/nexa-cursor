<?php

namespace App\Services\AiChat;

use App\DTO\AiChat\AiChatIntentResult;
use App\DTO\AiChat\AiChatRequestContext;

final class AiChatIntentService
{
    public function __construct(
        private readonly AiChatAccessService $accessService,
        private readonly AiChatIntentDetector $detector,
    ) {}

    public function classify(string $message, AiChatRequestContext $context): AiChatIntentResult
    {
        $detected = $this->detector->detect($message, $context);
        $intent = $detected['intent'];

        $isAdmin = $this->accessService->userMayQueryLiveData($context->user)
            && $context->isAdminChannel();

        $allowLiveData = $this->accessService->resolveIntentAccess($intent, $context);
        $allowPublicRates = $this->accessService->resolvePublicRatesAccess($intent);

        return new AiChatIntentResult(
            intent: $intent,
            isAdmin: $isAdmin,
            allowLiveData: $allowLiveData,
            allowPublicRates: $allowPublicRates,
            queryHint: $detected['query_hint'],
            responseMode: $detected['response_mode'],
        );
    }
}
