<?php

namespace App\Services\AiChat;

use App\DTO\AiChat\AiChatIntentResult;
use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatIntent;
use Illuminate\Support\Str;

final class AiChatIntentService
{
    public function __construct(
        private readonly AiChatAccessService $accessService,
    ) {}

    public function classify(string $message, AiChatRequestContext $context): AiChatIntentResult
    {
        $intent = $this->detectIntent($message);
        $isAdmin = $this->accessService->userMayQueryLiveData($context->user)
            && $context->isAdminChannel();
        $allowLiveData = $this->accessService->resolveIntentAccess(
            $intent,
            $context->user,
            $context->isAdminChannel()
        );
        $allowPublicRates = $this->accessService->resolvePublicRatesAccess($intent);

        return new AiChatIntentResult(
            intent: $intent,
            isAdmin: $isAdmin,
            allowLiveData: $allowLiveData,
            allowPublicRates: $allowPublicRates,
        );
    }

    private function detectIntent(string $message): AiChatIntent
    {
        $text = mb_strtolower(trim($message));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        if ($this->matchesAny($text, [
            'tarief', 'tarieven', 'instaptarief', 'kilometertarief', 'kilometer tarief',
            'kost een rit', 'wat kost', 'prijs per km', 'prijs per kilometer', 'prijzen',
            'nachttarief', 'nacht tarief', 'wachttarief', 'wacht tarief', 'minuuttarief',
            'rit kost', 'rit kosten', 'per km', 'per kilometer', 'per minuut',
        ])) {
            return AiChatIntent::Tarieven;
        }

        if ($this->matchesAny($text, [
            'open rit', 'open ritten', 'nog bevestig', 'wachten op bevestig', 'unconfirmed', 'pending',
        ])) {
            return AiChatIntent::OpenRitten;
        }

        if ($this->matchesAny($text, [
            'vrije chauffeur', 'beschikbare chauffeur', 'chauffeur morgen', 'chauffeurs morgen',
            'wie rijdt morgen', 'welke chauffeur',
        ])) {
            return AiChatIntent::VrijeChauffeursMorgen;
        }

        if ($this->matchesAny($text, [
            'rit morgen', 'ritten morgen', 'morgen gepland', 'planning morgen', 'staat morgen',
        ])) {
            return AiChatIntent::RittenMorgen;
        }

        if ($this->matchesAny($text, [
            'rit vandaag', 'ritten vandaag', 'vandaag gepland', 'hoeveel ritten vandaag', 'planning vandaag',
        ])) {
            return AiChatIntent::RittenVandaag;
        }

        if ($this->matchesAny($text, [
            'rit', 'ritten', 'chauffeur', 'chauffeurs', 'klant', 'klanten', 'voertuig', 'voertuigen',
            'factuur', 'facturen', 'reservering', 'reserveringen', 'database', 'sql',
        ]) && ! $this->matchesAny($text, [
            'annuleren', 'tarief', 'tarieven', 'dienst', 'diensten', 'luchthaven', 'boeken', 'boeking',
        ])) {
            // Operationele termen zonder FAQ-context → live intent, toegang wordt apart afgedwongen.
            return AiChatIntent::RittenVandaag;
        }

        return AiChatIntent::Faq;
    }

    /**
     * @param  list<string>  $needles
     */
    private function matchesAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (Str::contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
