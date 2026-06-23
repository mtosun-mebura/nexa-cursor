<?php

namespace App\Services\AiChat;

use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatIntent;
use App\Enums\AiChat\AiChatResponseMode;
use Illuminate\Support\Str;

/**
 * Classificeert gebruikersvragen naar intents (keyword-first, geen LLM).
 */
final class AiChatIntentDetector
{
    /**
     * @return array{intent: AiChatIntent, query_hint: ?string, response_mode: AiChatResponseMode}
     */
    public function detect(string $message, AiChatRequestContext $context): array
    {
        $text = mb_strtolower(trim($message));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $responseMode = $this->detectResponseMode($text);

        if ($this->isOwnRideQuestion($text, $context)) {
            return [
                'intent' => AiChatIntent::MijnRit,
                'query_hint' => $this->ownRideHint($text, $responseMode),
                'response_mode' => $responseMode,
            ];
        }

        if ($this->isRouteTravelQuestion($text, $context)
            || $this->isRouteQuoteQuestion($text, $context)
            || $this->isRouteBookingQuestion($text, $context)) {
            return ['intent' => AiChatIntent::RitOfferte, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->isAdminOperationalQuestion($text, $context)) {
            return $this->detectAdminIntent($text, $responseMode);
        }

        if ($this->isPublicOperationalProbe($text, $context)) {
            return $this->detectAdminIntent($text, $responseMode);
        }

        if ($this->matchesAny($text, [
            'tarief', 'tarieven', 'instaptarief', 'kilometertarief', 'kilometer tarief',
            'kost een rit', 'wat kost', 'prijs per km', 'prijs per kilometer', 'prijzen',
            'nachttarief', 'nacht tarief', 'wachttarief', 'wacht tarief', 'minuuttarief',
            'rit kost', 'rit kosten', 'per km', 'per kilometer', 'per minuut', 'berekenen jullie de prijs',
        ])) {
            return ['intent' => AiChatIntent::Tarieven, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, [
            'hoe annuleer', 'annuleren', 'annulering', 'annuleringsvoorwaarde', 'ophaaltijd aanpassen',
            'rit wijzigen', 'wijzigen', 'kan ik mijn rit',
        ]) && ! $this->matchesAny($text, ['welke ritten zijn geannuleerd', 'klanten hebben een rit geannuleerd'])) {
            return ['intent' => AiChatIntent::Annuleren, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, [
            'betaalmethode', 'betalen', 'pinnen', 'contant betalen', 'creditcard', 'achteraf betalen',
        ])) {
            return ['intent' => AiChatIntent::Betalen, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, [
            'reserveren', 'reservering', 'boeken', 'boeking', 'bestellen', 'retourrit', 'online betalen',
            'hoe ver van tevoren', 'direct een taxi',
        ]) && ! $this->isOwnRideQuestion($text, $context)
            && ! $this->isRouteBookingQuestion($text, $context)) {
            return ['intent' => AiChatIntent::Reserveren, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, [
            'contact', 'telefoonnummer', 'openingstijd', 'gevestigd', 'bereikbaar', 'email', 'e-mail',
        ])) {
            return ['intent' => AiChatIntent::Contact, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, [
            'luchthavenvervoer', 'zakelijk vervoer', 'groepsvervoer', 'rolstoel', 'duitsland',
            "'s nachts", 's nachts', 'kinderen vervoeren', 'huisdier', 'dienst', 'diensten',
            'hebben jullie', 'doen jullie', 'kunnen jullie', 'rijden jullie',
        ])) {
            return ['intent' => AiChatIntent::Diensten, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        return ['intent' => AiChatIntent::Faq, 'query_hint' => null, 'response_mode' => $responseMode];
    }

    private function isPublicOperationalProbe(string $text, AiChatRequestContext $context): bool
    {
        if ($context->isAdminChannel() || $context->isMijnTaxiChannel()) {
            return false;
        }

        return $this->matchesAny($text, [
            'welke ritten', 'ritten staan', 'hoeveel ritten', 'welke chauffeur', 'welke chauffeurs',
            'omzet', 'welke klanten', 'welke ritten zijn geannuleerd',
        ]);
    }

    private function isAdminOperationalQuestion(string $text, AiChatRequestContext $context): bool
    {
        if (! $context->isAdminChannel()) {
            return false;
        }

        $operationalNeedles = [
            'welke ritten', 'ritten staan', 'hoeveel ritten', 'ritten hebben we', 'ritten zijn',
            'welke chauffeur', 'welke chauffeurs', 'chauffeurs zijn', 'chauffeurs hebben',
            'omzet', 'welke klanten', 'klanten hebben', 'planning', 'voertuigen zijn',
            'voertuig is', 'onderweg', 'geannuleerd', 'zonder chauffeur', 'geen chauffeur',
            'geen voertuig', 'open rit', 'nog bevestig', 'luchthavenrit', 'schiphol',
            'dubbel ingepland', 'overlappen', 'binnen een uur',
        ];

        return $this->matchesAny($text, $operationalNeedles);
    }

    /**
     * @return array{intent: AiChatIntent, query_hint: ?string, response_mode: AiChatResponseMode}
     */
    private function detectAdminIntent(string $text, AiChatResponseMode $responseMode): array
    {
        if ($this->matchesAny($text, ['omzet vandaag', 'omzet van vandaag', 'verwachte omzet van vandaag'])) {
            return ['intent' => AiChatIntent::OmzetVandaag, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['omzet morgen', 'verwachte omzet morgen', 'verwachte omzet van morgen'])) {
            return ['intent' => AiChatIntent::OmzetMorgen, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['omzet vorige maand', 'omzet van vorige maand', 'was de omzet vorige'])) {
            return ['intent' => AiChatIntent::OmzetVorigeMaand, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['hoogste omzet', 'meeste omzet']) && $this->matchesAny($text, ['rit', 'ritten'])) {
            return ['intent' => AiChatIntent::RittenHoogsteOmzet, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['luchthavenrit', 'luchthavenritten', 'schiphol'])
            && $this->matchesAny($text, ['deze maand', 'uitgevoerd'])) {
            return [
                'intent' => AiChatIntent::LuchthavenrittenDezeMaand,
                'query_hint' => null,
                'response_mode' => $this->matchesAny($text, ['hoeveel', 'aantal']) ? AiChatResponseMode::Count : $responseMode,
            ];
        }

        if ($this->matchesAny($text, ['dubbel ingepland', 'overlappen', 'overlapping', 'logistiek onhandig'])) {
            return ['intent' => AiChatIntent::Planning, 'query_hint' => 'overlapping', 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['binnen een uur', 'vertrekken binnen'])) {
            return ['intent' => AiChatIntent::Planning, 'query_hint' => 'binnen_uur', 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['zonder chauffeur', 'geen chauffeur', 'nog geen chauffeur', 'geen chauffeur toegewezen'])) {
            return ['intent' => AiChatIntent::RittenZonderChauffeur, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['geen voertuig', 'zonder voertuig', 'nog geen voertuig'])) {
            return ['intent' => AiChatIntent::RittenZonderVoertuig, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['nog bevestig', 'moeten nog bevestig', 'wachten op bevestig', 'open rit', 'open ritten', 'unconfirmed'])) {
            return ['intent' => AiChatIntent::OpenRitten, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['geannuleerd']) && $this->matchesAny($text, ['klant', 'klanten'])) {
            return ['intent' => AiChatIntent::KlantenGeannuleerd, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['geannuleerd', 'geannuleerde ritten']) && $this->matchesAny($text, ['rit', 'ritten'])) {
            return ['intent' => AiChatIntent::RittenGeannuleerd, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['langer dan 1 uur', 'langer dan een uur', 'duur langer'])) {
            return ['intent' => AiChatIntent::RittenLang, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['voor 08:00', 'voor 8 uur', 'vertrekken voor 08', 'vertrekken voor 8'])) {
            return ['intent' => AiChatIntent::RittenVoorAchtUur, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['luchthavenrit', 'luchthavenritten', 'schiphol']) && $this->matchesAny($text, ['morgen'])) {
            if ($this->matchesAny($text, ['chauffeur', 'chauffeurs', 'wie rijdt'])) {
                return ['intent' => AiChatIntent::ChauffeursSchipholMorgen, 'query_hint' => null, 'response_mode' => $responseMode];
            }

            return ['intent' => AiChatIntent::RittenLuchthavenMorgen, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['meeste ritten', 'meeste rit']) && $this->matchesAny($text, ['chauffeur', 'chauffeurs'])) {
            return ['intent' => AiChatIntent::ChauffeursMeesteRittenVandaag, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['onderweg', 'dienst nog niet gestart', 'nog niet gestart'])) {
            $hint = $this->matchesAny($text, ['niet gestart']) ? 'niet_gestart' : 'onderweg';

            return ['intent' => AiChatIntent::ChauffeursOnderweg, 'query_hint' => $hint, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['geen rit toegewezen', 'zonder rit', 'nog geen rit']) && $this->matchesAny($text, ['chauffeur', 'chauffeurs'])) {
            return ['intent' => AiChatIntent::ChauffeursZonderRit, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['beschikbaar', 'vrij', 'vrije chauffeur']) && $this->matchesAny($text, ['morgen', 'chauffeur', 'chauffeurs'])) {
            return ['intent' => AiChatIntent::VrijeChauffeursMorgen, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['chauffeur', 'chauffeurs']) && $this->matchesAny($text, ['vandaag']) && $this->matchesAny($text, ['rit', 'ritten'])) {
            return ['intent' => AiChatIntent::ChauffeursVandaag, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['meeste ritten geboekt', 'meeste ritten']) && $this->matchesAny($text, ['klant', 'klanten'])) {
            return ['intent' => AiChatIntent::KlantenMeesteRitten, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['nieuw deze maand', 'nieuwe klanten']) && $this->matchesAny($text, ['klant', 'klanten'])) {
            return ['intent' => AiChatIntent::KlantenNieuwDezeMaand, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['deze maand']) && $this->matchesAny($text, ['klant', 'klanten', 'geboekt'])) {
            return ['intent' => AiChatIntent::KlantenDezeMaand, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['luchthavenrit', 'luchthaven', 'schiphol']) && $this->matchesAny($text, ['klant', 'klanten'])) {
            return ['intent' => AiChatIntent::KlantenLuchthaven, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['voertuigen morgen', 'voertuig morgen', 'morgen ingepland']) && $this->matchesAny($text, ['voertuig', 'voertuigen'])) {
            return ['intent' => AiChatIntent::VoertuigenMorgen, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['voertuigen beschikbaar', 'voertuig beschikbaar', 'beschikbare voertuigen'])) {
            return ['intent' => AiChatIntent::VoertuigenBeschikbaar, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['rit morgen', 'ritten morgen', 'morgen gepland', 'planning morgen', 'staat morgen', 'staan morgen', 'staan morgen gepland'])) {
            return ['intent' => AiChatIntent::RittenMorgen, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, [
            'rit vandaag', 'ritten vandaag', 'vandaag gepland', 'hoeveel ritten vandaag',
            'planning vandaag', 'hebben we vandaag', 'staan vandaag gepland',
        ])) {
            return ['intent' => AiChatIntent::RittenVandaag, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        if ($this->matchesAny($text, ['hoeveel ritten', 'ritten hebben we']) && $this->matchesAny($text, ['morgen'])) {
            return ['intent' => AiChatIntent::RittenMorgen, 'query_hint' => null, 'response_mode' => AiChatResponseMode::Count];
        }

        if ($this->matchesAny($text, ['hoeveel ritten', 'ritten hebben we']) && $this->matchesAny($text, ['vandaag'])) {
            return ['intent' => AiChatIntent::RittenVandaag, 'query_hint' => null, 'response_mode' => AiChatResponseMode::Count];
        }

        if ($this->matchesAny($text, [
            'rit', 'ritten', 'chauffeur', 'chauffeurs', 'klant', 'klanten', 'voertuig', 'voertuigen',
            'factuur', 'facturen', 'reservering', 'reserveringen', 'planning',
        ]) && ! $this->matchesAny($text, ['tarief', 'tarieven', 'boeken', 'boeking'])) {
            return ['intent' => AiChatIntent::RittenKomend, 'query_hint' => null, 'response_mode' => $responseMode];
        }

        return ['intent' => AiChatIntent::Faq, 'query_hint' => null, 'response_mode' => $responseMode];
    }

    private function detectResponseMode(string $text): AiChatResponseMode
    {
        if ($this->matchesAny($text, ['hoeveel', 'aantal', 'totaal aantal'])) {
            return AiChatResponseMode::Count;
        }

        if ($this->matchesAny($text, ['omzet', 'verwachte omzet', 'meeste', 'drukste'])) {
            return AiChatResponseMode::Summary;
        }

        return AiChatResponseMode::List;
    }

    private function isRouteTravelQuestion(string $text, AiChatRequestContext $context): bool
    {
        if ($context->isMijnTaxiChannel()) {
            return false;
        }

        if ($this->matchesAny($text, [
            'mijn rit', 'volgende rit', 'eerst volgende', 'eerste volgende',
            'welke rit', 'welke ritten', 'hoeveel rit', 'hoeveel ritten',
            'chauffeur', 'chauffeurs', 'klant', 'klanten', 'omzet', 'planning',
        ])) {
            return false;
        }

        if (preg_match('/\b(?:ik\s+)?(?:wil|moet|ga|wilt)\s+(?:graag\s+)?(?:naar|to)\s+.+/iu', $text)) {
            return true;
        }

        if (preg_match('/\b(?:kan|kun)\s+ik\s+(?:ook\s+)?(?:naar|to)\s+.+/iu', $text)) {
            return true;
        }

        if (preg_match('/\b(?:taxi|taxirit|rit)\s+naar\s+.+/iu', $text)) {
            return true;
        }

        return false;
    }

    private function isRouteQuoteQuestion(string $text, AiChatRequestContext $context): bool
    {
        if ($context->isMijnTaxiChannel()) {
            return false;
        }

        if (! $this->matchesAny($text, [
            'wat kost', 'kost een rit', 'kost een taxirit', 'prijs van een rit',
            'prijs voor een rit', 'offerte voor', 'prijsindicatie', 'prijs berekenen',
        ])) {
            return false;
        }

        if ($this->matchesAny($text, ['mijn rit', 'volgende rit', 'eerst volgende', 'eerste volgende'])) {
            return false;
        }

        if (preg_match('/\b(?:van|from)\s+.+\s+(?:naar|to)\s+.+/iu', $text)) {
            return true;
        }

        if ($this->matchesAny($text, ['rit naar', 'taxirit naar', 'naar schiphol', 'naar luchthaven', 'naar airport'])) {
            return true;
        }

        if ($this->matchesAny($text, ['schiphol', 'airport', 'luchthaven', 'duesseldorf', 'düsseldorf', 'duisburg'])) {
            return true;
        }

        return false;
    }

    private function isRouteBookingQuestion(string $text, AiChatRequestContext $context): bool
    {
        if ($context->isMijnTaxiChannel()) {
            return false;
        }

        if (! $this->matchesAny($text, [
            'boek een rit', 'boek een taxirit', 'rit boeken', 'boeken een rit',
            'wil een rit boeken', 'kan ik een rit boeken', 'boek mijn rit',
        ])) {
            return false;
        }

        if (preg_match('/\b(?:van|from)\s+.+\s+(?:naar|to)\s+.+/iu', $text)) {
            return true;
        }

        if ($this->matchesAny($text, ['rit naar', 'taxirit naar', 'naar schiphol', 'naar luchthaven', 'naar airport'])) {
            return true;
        }

        if ($this->matchesAny($text, ['schiphol', 'airport', 'luchthaven', 'duesseldorf', 'düsseldorf', 'duisburg'])) {
            return true;
        }

        return preg_match('/\b(?:naar|to)\s+.+/iu', $text) === 1;
    }

    private function isOwnRideQuestion(string $text, AiChatRequestContext $context): bool
    {
        if ($this->matchesAny($text, [
            'mijn rit', 'mijn ritten', 'mijn reservering', 'mijn boeking',
            'word ik opgehaald', 'wordt ik opgehaald',
            'status van mijn rit', 'wie is mijn chauffeur', 'is mijn reservering bevestigd',
            'wanneer word ik opgehaald', 'wanneer wordt ik opgehaald',
            'wanneer haal je me op', 'mijn ophaaltijd', 'ophaaltijd van mijn',
        ])) {
            return true;
        }

        if ($this->matchesAny($text, ['heb ik', 'ik heb']) && $this->matchesAny($text, ['rit', 'ritten'])) {
            if ($context->isAdminChannel() && ! $this->matchesAny($text, ['mijn', 'gepland', 'voltooid', 'voltooide'])) {
                return false;
            }

            return true;
        }

        if ($this->matchesAny($text, ['volgende rit', 'laatste rit', 'eerstvolgende rit', 'eerste volgende rit'])) {
            return true;
        }

        if ($this->matchesAny($text, ['factuur', 'pdf']) && $this->matchesAny($text, ['mijn', 'laatste', 'rit'])) {
            return true;
        }

        if ($this->matchesAny($text, ['prijs', 'kost', 'kosten', 'bedrag'])
            && $this->matchesAny($text, ['mijn', 'volgende', 'eerst', 'eerste'])) {
            return true;
        }

        if ($this->matchesAny($text, ['wanneer', 'hoe laat']) && $this->matchesAny($text, ['opgehaald', 'ophaaltijd'])) {
            return true;
        }

        if ($this->matchesAny($text, ['wanneer', 'hoe laat']) && $this->matchesAny($text, ['mijn rit', 'mijn reservering', 'mijn boeking'])) {
            return true;
        }

        return false;
    }

    private function ownRideHint(string $text, AiChatResponseMode $responseMode): ?string
    {
        if ($this->matchesAny($text, ['factuur', 'pdf'])) {
            return 'factuur';
        }

        if ($this->matchesAny($text, ['prijs', 'kost', 'kosten', 'bedrag'])) {
            return 'prijs';
        }

        if ($this->matchesAny($text, ['vandaag'])) {
            return 'vandaag';
        }

        if ($this->matchesAny($text, ['morgen'])) {
            return 'morgen';
        }

        if ($this->matchesAny($text, ['aankomend', 'aankomende', 'komende', 'toekomstige', 'in de toekomst'])) {
            return 'aankomend';
        }

        if ($responseMode === AiChatResponseMode::Count && $this->matchesAny($text, ['voltooid', 'voltooide', 'afgerond'])) {
            return 'voltooid';
        }

        if ($this->matchesAny($text, ['gepland', 'ingepland'])) {
            return 'gepland';
        }

        if ($this->matchesAny($text, ['volgende rit', 'eerstvolgende rit', 'eerste volgende rit', 'volgende'])) {
            return 'volgende';
        }

        if ($this->matchesAny($text, ['chauffeur', 'wie is'])) {
            return 'chauffeur';
        }

        if ($this->matchesAny($text, ['status', 'bevestigd'])) {
            return 'status';
        }

        if ($this->matchesAny($text, ['opgehaald', 'ophaaltijd', 'wanneer'])) {
            return 'ophaaltijd';
        }

        return null;
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
