<?php

namespace App\Services\AiChat;

/**
 * Lokale fallback op de Nexa Taxi kennisbank wanneer n8n geen antwoord geeft.
 */
final class AiChatKnowledgeFallbackService
{
    public function __construct(
        private readonly AiChatRagSearchService $ragSearchService,
    ) {}

    public function search(string $message, ?string $module = 'taxi'): ?string
    {
        return $this->ragSearchService->searchFromMessage($message, (string) ($module ?? 'taxi'));
    }
}
