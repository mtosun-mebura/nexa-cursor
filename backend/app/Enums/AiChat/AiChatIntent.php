<?php

namespace App\Enums\AiChat;

enum AiChatIntent: string
{
    case Faq = 'faq';
    case Tarieven = 'tarieven';
    case RittenMorgen = 'ritten_morgen';
    case VrijeChauffeursMorgen = 'vrije_chauffeurs_morgen';
    case RittenVandaag = 'ritten_vandaag';
    case OpenRitten = 'open_ritten';

    /**
     * @return list<self>
     */
    public static function liveDataIntents(): array
    {
        return [
            self::RittenMorgen,
            self::VrijeChauffeursMorgen,
            self::RittenVandaag,
            self::OpenRitten,
        ];
    }

    public function requiresLiveData(): bool
    {
        return in_array($this, self::liveDataIntents(), true);
    }

    public function allowsPublicRates(): bool
    {
        return $this === self::Tarieven;
    }
}
