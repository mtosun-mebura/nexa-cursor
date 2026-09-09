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
    case RittenUitgevoerd = 'ritten_uitgevoerd';
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
    case OmzetDezeWeek = 'omzet_deze_week';
    case OmzetDezeMaand = 'omzet_deze_maand';
    case OmzetDitJaar = 'omzet_dit_jaar';
    case OmzetVorigeMaand = 'omzet_vorige_maand';
    case InkomstenOverzicht = 'inkomsten_overzicht';
    case LuchthavenrittenDezeMaand = 'luchthavenritten_deze_maand';

    // Admin — ritten extra periodes
    case RittenDezeWeek = 'ritten_deze_week';
    case RittenDezeMaand = 'ritten_deze_maand';

    // Admin — facturen (klantfacturen van de tenant)
    case FacturenOpenstaand = 'facturen_openstaand';
    case FacturenBetaald = 'facturen_betaald';
    case FacturenAchterstallig = 'facturen_achterstallig';
    case FacturenOverzicht = 'facturen_overzicht';

    // Admin — planning & voertuigen
    case Planning = 'planning';
    case PlanningChauffeurs = 'planning_chauffeurs';
    case ChauffeursOnline = 'chauffeurs_online';
    case ChauffeursOverzicht = 'chauffeurs_overzicht';
    case VoertuigenMorgen = 'voertuigen_morgen';
    case VoertuigenBeschikbaar = 'voertuigen_beschikbaar';

    // Super-admin / tenant — NEXA-abonnement en SaaS-facturen
    case PlatformTenantAbonnement = 'platform_tenant_abonnement';
    case PlatformTenantFacturen = 'platform_tenant_facturen';
    case PlatformTenantsOnbetaald = 'platform_tenants_onbetaald';
    case PlatformActiesNodig = 'platform_acties_nodig';

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
            self::RittenUitgevoerd,
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
            self::OmzetDezeWeek,
            self::OmzetDezeMaand,
            self::OmzetDitJaar,
            self::OmzetVorigeMaand,
            self::InkomstenOverzicht,
            self::LuchthavenrittenDezeMaand,
            self::RittenDezeWeek,
            self::RittenDezeMaand,
            self::FacturenOpenstaand,
            self::FacturenBetaald,
            self::FacturenAchterstallig,
            self::FacturenOverzicht,
            self::Planning,
            self::PlanningChauffeurs,
            self::ChauffeursOnline,
            self::ChauffeursOverzicht,
            self::VoertuigenMorgen,
            self::VoertuigenBeschikbaar,
            self::PlatformTenantAbonnement,
            self::PlatformTenantFacturen,
            self::PlatformTenantsOnbetaald,
            self::PlatformActiesNodig,
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

    public function isPlatformIntent(): bool
    {
        return in_array($this, [
            self::PlatformTenantAbonnement,
            self::PlatformTenantFacturen,
            self::PlatformTenantsOnbetaald,
            self::PlatformActiesNodig,
        ], true);
    }

    public function isPlatformWideIntent(): bool
    {
        return in_array($this, [
            self::PlatformTenantsOnbetaald,
            self::PlatformActiesNodig,
        ], true);
    }

    public function allowsCompanyIdZero(): bool
    {
        return $this->isPlatformIntent();
    }

    public function usesTaxiDatabase(): bool
    {
        if (! $this->requiresLiveData()) {
            return false;
        }

        if ($this->isPlatformIntent() || $this->usesCompanyInvoices()) {
            return false;
        }

        return true;
    }

    public function usesCompanyInvoices(): bool
    {
        return in_array($this, [
            self::FacturenOpenstaand,
            self::FacturenBetaald,
            self::FacturenAchterstallig,
            self::FacturenOverzicht,
        ], true);
    }

    public function isRevenueIntent(): bool
    {
        return in_array($this, [
            self::OmzetVandaag,
            self::OmzetMorgen,
            self::OmzetDezeWeek,
            self::OmzetDezeMaand,
            self::OmzetDitJaar,
            self::OmzetVorigeMaand,
            self::InkomstenOverzicht,
        ], true);
    }
}
