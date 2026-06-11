<?php

namespace App\Enums\AiChat;

enum AiChatIntent: string
{
    // Publiek — RAG kennisbank
    case Faq = 'faq';
    case Diensten = 'diensten';
    case Reserveren = 'reserveren';
    case Annuleren = 'annuleren';
    case Betalen = 'betalen';
    case Contact = 'contact';

    // Publiek — tarieven (default_rates)
    case Tarieven = 'tarieven';

    // Publiek — route-specifieke prijsopgave (meerstaps)
    case RitOfferte = 'rit_offerte';

    // Ingelogde klant — eigen ritten
    case MijnRit = 'mijn_rit';

    // Admin — ritten
    case RittenMorgen = 'ritten_morgen';
    case RittenVandaag = 'ritten_vandaag';
    case RittenKomend = 'ritten_komend';
    case OpenRitten = 'open_ritten';
    case RittenGeannuleerd = 'ritten_geannuleerd';
    case RittenZonderChauffeur = 'ritten_zonder_chauffeur';
    case RittenZonderVoertuig = 'ritten_zonder_voertuig';
    case RittenLuchthavenMorgen = 'ritten_luchthaven_morgen';
    case RittenVoorAchtUur = 'ritten_voor_08';
    case RittenLang = 'ritten_lang';
    case RittenHoogsteOmzet = 'ritten_hoogste_omzet';

    // Admin — chauffeurs
    case VrijeChauffeursMorgen = 'vrije_chauffeurs_morgen';
    case ChauffeursVandaag = 'chauffeurs_vandaag';
    case ChauffeursMeesteRittenVandaag = 'chauffeurs_meeste_ritten_vandaag';
    case ChauffeursZonderRit = 'chauffeurs_zonder_rit';
    case ChauffeursSchipholMorgen = 'chauffeurs_schiphol_morgen';
    case ChauffeursOnderweg = 'chauffeurs_onderweg';

    // Admin — klanten
    case KlantenMeesteRitten = 'klanten_meeste_ritten';
    case KlantenDezeMaand = 'klanten_deze_maand';
    case KlantenLuchthaven = 'klanten_luchthaven';
    case KlantenGeannuleerd = 'klanten_geannuleerd';
    case KlantenNieuwDezeMaand = 'klanten_nieuw_deze_maand';

    // Admin — omzet
    case OmzetVandaag = 'omzet_vandaag';
    case OmzetMorgen = 'omzet_morgen';
    case OmzetVorigeMaand = 'omzet_vorige_maand';
    case LuchthavenrittenDezeMaand = 'luchthavenritten_deze_maand';

    // Admin — planning & voertuigen
    case Planning = 'planning';
    case VoertuigenMorgen = 'voertuigen_morgen';
    case VoertuigenBeschikbaar = 'voertuigen_beschikbaar';

    /**
     * @return list<self>
     */
    public static function ragIntents(): array
    {
        return [
            self::Faq,
            self::Diensten,
            self::Reserveren,
            self::Annuleren,
            self::Betalen,
            self::Contact,
        ];
    }

    /**
     * @return list<self>
     */
    public static function liveDataIntents(): array
    {
        return [
            self::MijnRit,
            self::RittenMorgen,
            self::RittenVandaag,
            self::RittenKomend,
            self::OpenRitten,
            self::RittenGeannuleerd,
            self::RittenZonderChauffeur,
            self::RittenZonderVoertuig,
            self::RittenLuchthavenMorgen,
            self::RittenVoorAchtUur,
            self::RittenLang,
            self::RittenHoogsteOmzet,
            self::VrijeChauffeursMorgen,
            self::ChauffeursVandaag,
            self::ChauffeursMeesteRittenVandaag,
            self::ChauffeursZonderRit,
            self::ChauffeursSchipholMorgen,
            self::ChauffeursOnderweg,
            self::KlantenMeesteRitten,
            self::KlantenDezeMaand,
            self::KlantenLuchthaven,
            self::KlantenGeannuleerd,
            self::KlantenNieuwDezeMaand,
            self::OmzetVandaag,
            self::OmzetMorgen,
            self::OmzetVorigeMaand,
            self::LuchthavenrittenDezeMaand,
            self::Planning,
            self::VoertuigenMorgen,
            self::VoertuigenBeschikbaar,
        ];
    }

    public function usesRag(): bool
    {
        return in_array($this, self::ragIntents(), true);
    }

    public function requiresLiveData(): bool
    {
        return in_array($this, self::liveDataIntents(), true);
    }

    public function allowsPublicRates(): bool
    {
        return $this === self::Tarieven;
    }

    public function isCustomerOwnData(): bool
    {
        return $this === self::MijnRit;
    }
}
