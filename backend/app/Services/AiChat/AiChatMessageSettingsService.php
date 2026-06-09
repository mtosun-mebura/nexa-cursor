<?php

namespace App\Services\AiChat;

use App\Models\GeneralSetting;

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
