<?php

namespace App\Services\AiChat;

use App\Enums\AiChat\AiChatChannel;
use App\Models\GeneralSetting;
use App\Models\User;

/**
 * Configureerbare standaardteksten voor de AI-chat (per tenant).
 */
final class AiChatMessageSettingsService
{
    public function modulePrefix(string $module = 'taxi'): string
    {
        return 'ai_chat_'.strtolower(trim($module)).'_';
    }

    /**
     * @return array<string, string>
     */
    public function defaults(string $module = 'taxi'): array
    {
        $isTaxi = strtolower(trim($module)) === 'taxi';

        return [
            'greeting' => $isTaxi
                ? 'Hallo! Ik ben je taxi-assistent. Stel je vraag over ritten, boeken of tarieven — ik help je graag verder.'
                : 'Hallo! Ik ben je AI-assistent. Hoe kan ik je vandaag helpen?',
            'title' => $isTaxi ? 'Taxi-assistent' : 'AI-assistent',
            'subtitle' => $isTaxi ? 'Powered by Nexa Taxi' : 'Altijd beschikbaar',
            'not_found_message' => 'Ik kan helaas geen informatie vinden over je vraag. Probeer het anders te formuleren of neem contact met ons op.',
            'unavailable_message' => 'Ik kan helaas geen informatie vinden over je vraag. Probeer het anders te formuleren of neem contact met ons op.',
            'live_data_denied_message' => (string) config(
                'ai_chat.live_data_denied_message',
                'Daar kan ik je helaas geen informatie over geven. Stel je vraag gerust op een andere manier, of neem contact met ons op.'
            ),
            'own_ride_denied_guest_message' => 'Om de status van je reservering te bekijken, log in op Mijn Taxi met het e-mailadres waarmee je hebt geboekt.',
            'own_ride_denied_logged_in_message' => 'Ik heb geen toegang tot je reserveringsgegevens. Log in op Mijn Taxi met hetzelfde e-mailadres als bij je boeking, of bekijk je ritten daar direct.',
            'public_rates_denied_mijn_taxi_message' => 'Voor tarieven en prijsopgaves (bijvoorbeeld een rit naar Schiphol) kun je het beste onze website gebruiken — open daar de chat voor een directe offerte. In Mijn Taxi help ik je graag met de status van je reservering of andere vragen over je account.',
            'public_rates_denied_admin_message' => 'Publieke tarieven en ritoffertes zijn alleen beschikbaar via de website-chat. In het adminpaneel kan ik je helpen met operationele vragen over ritten, chauffeurs en planning.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function all(?int $companyId = null, string $module = 'taxi'): array
    {
        $defaults = $this->defaults($module);
        $resolved = [];
        foreach (array_keys($defaults) as $key) {
            $resolved[$key] = $this->get($key, $module, $companyId);
        }

        return $resolved;
    }

    public function greeting(?int $companyId = null, string $module = 'taxi'): string
    {
        return $this->get('greeting', $module, $companyId);
    }

    public function title(?int $companyId = null, string $module = 'taxi'): string
    {
        return $this->get('title', $module, $companyId);
    }

    public function subtitle(?int $companyId = null, string $module = 'taxi'): string
    {
        return $this->get('subtitle', $module, $companyId);
    }

    public function notFoundMessage(?int $companyId = null, string $module = 'taxi'): string
    {
        return $this->get('not_found_message', $module, $companyId);
    }

    public function unavailableMessage(?int $companyId = null, string $module = 'taxi'): string
    {
        return $this->get('unavailable_message', $module, $companyId);
    }

    public function liveDataDeniedMessage(?int $companyId = null, string $module = 'taxi'): string
    {
        return $this->get('live_data_denied_message', $module, $companyId);
    }

    public function ownRideDeniedMessage(?User $user = null, ?int $companyId = null, string $module = 'taxi'): string
    {
        $key = $user === null
            ? 'own_ride_denied_guest_message'
            : 'own_ride_denied_logged_in_message';

        return $this->get($key, $module, $companyId);
    }

    public function publicRatesDeniedMessage(
        AiChatChannel $channel,
        ?int $companyId = null,
        string $module = 'taxi',
    ): string {
        $key = match ($channel) {
            AiChatChannel::MijnTaxi => 'public_rates_denied_mijn_taxi_message',
            AiChatChannel::Admin => 'public_rates_denied_admin_message',
            default => 'public_rates_denied_mijn_taxi_message',
        };

        return $this->get($key, $module, $companyId);
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function save(array $values, ?int $companyId = null, string $module = 'taxi'): void
    {
        $companyId ??= GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null) {
            return;
        }

        $prefix = $this->modulePrefix($module);
        foreach (array_keys($this->defaults($module)) as $key) {
            if (! array_key_exists($key, $values)) {
                continue;
            }
            $value = trim((string) ($values[$key] ?? ''));
            GeneralSetting::set($prefix.$key, $value, $companyId);
        }
    }

    private function get(string $key, string $module, ?int $companyId): string
    {
        $defaults = $this->defaults($module);
        $default = $defaults[$key] ?? '';

        $companyId ??= GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null) {
            return $default;
        }

        $stored = trim((string) GeneralSetting::get($this->modulePrefix($module).$key, '', $companyId));

        return $stored !== '' ? $stored : $default;
    }
}
