<?php

namespace App\Services\AiWebsite\Planning;

use App\Models\Company;
use App\Services\AiWebsite\AiScalar;
use App\Services\AiWebsite\Providers\AiProviderInterface;

class AiWebsiteBriefService
{
    public function __construct(protected AiProviderInterface $provider) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $source
     * @return array{brief: array<string, mixed>, used_openai: bool}
     */
    public function make(Company $company, array $input, array $source): array
    {
        $fallback = $this->fallbackBrief($company, $input, $source);
        $fromLlm = $this->provider->generateStructured(
            (string) config('ai_website.prompt_versions.website-brief', 'website-brief:v1'),
            [
                'bedrijf' => $this->companyFacts($company),
                'invoer' => [
                    'context' => (string) ($input['context'] ?? ''),
                    'goals' => $input['goals'] ?? [],
                    'style' => (string) ($input['style'] ?? ''),
                    'tone' => (string) ($input['tone'] ?? ''),
                    'source_url' => (string) ($input['source_url'] ?? ''),
                ],
                'bronwebsite' => $source['summary'] ?? '',
                'trusted_facts_bron' => $this->companyFacts($company),
                'gewenst' => array_keys($fallback),
            ],
            'Je bent een Nederlandse strategisch website-adviseur voor de NEXA PageBuilder. Antwoord uitsluitend met JSON. Verzin geen feiten (telefoon, adres, jaren, prijzen, certificeringen). Marketingzinnen mogen wel. Geen em-dash.'
        );

        if (is_array($fromLlm)) {
            return [
                'brief' => $this->mergeBrief($fallback, $fromLlm),
                'used_openai' => true,
            ];
        }

        return ['brief' => $fallback, 'used_openai' => false];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    public function fallbackBrief(Company $company, array $input, array $source): array
    {
        $city = trim((string) ($company->city ?? '')) ?: 'Nederland';
        $context = trim((string) ($input['context'] ?? ''));
        $style = trim((string) ($input['style'] ?? 'professional')) ?: 'professional';
        $tone = trim((string) ($input['tone'] ?? 'zakelijk')) ?: 'zakelijk';
        $goals = is_array($input['goals'] ?? null) ? array_values($input['goals']) : [];

        return [
            'business_type' => trim((string) ($company->industry ?? '')) ?: 'Dienstverlening',
            'business_summary' => $context !== '' ? $context : ($company->name.' in '.$city.'.'),
            'target_audiences' => ['Particulieren en bedrijven in '.$city],
            'customer_problems' => ['Onzekerheid over planning en bereikbaarheid'],
            'services' => $this->guessServices($context, $company),
            'unique_selling_points' => ['Persoonlijk contact', 'Lokaal in '.$city, 'Duidelijke afspraken'],
            'brand_personality' => $style,
            'tone_of_voice' => $tone,
            'desired_conversion' => $goals[0] ?? 'leads',
            'primary_cta' => 'Neem contact op',
            'secondary_cta' => 'Over ons',
            'locations' => array_values(array_filter([$city])),
            'visual_direction' => [
                'style' => 'realistic professional photography',
                'lighting' => 'natural daylight',
                'people' => 'authentic Dutch professionals',
                'color_mood' => 'warm neutral',
                'avoid' => ['obvious stock photography', 'text in image', 'unrealistic poses'],
            ],
            'trusted_facts' => $this->trustedFacts($company),
        ];
    }

    /**
     * @param  array<string, mixed>  $fallback
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function mergeBrief(array $fallback, array $raw): array
    {
        $out = $fallback;
        foreach (['business_type', 'business_summary', 'brand_personality', 'tone_of_voice', 'desired_conversion', 'primary_cta', 'secondary_cta'] as $key) {
            $value = AiScalar::string($raw[$key] ?? '');
            if ($value !== '') {
                $out[$key] = $value;
            }
        }
        foreach (['target_audiences', 'customer_problems', 'services', 'unique_selling_points', 'locations'] as $key) {
            if (is_array($raw[$key] ?? null) && $raw[$key] !== []) {
                $out[$key] = AiScalar::stringList($raw[$key]);
            }
        }
        if (is_array($raw['visual_direction'] ?? null)) {
            $visual = $fallback['visual_direction'];
            foreach ($raw['visual_direction'] as $visualKey => $visualValue) {
                if ($visualKey === 'avoid') {
                    $visual['avoid'] = AiScalar::stringList($visualValue);
                    continue;
                }
                $text = AiScalar::string($visualValue);
                if ($text !== '') {
                    $visual[$visualKey] = $text;
                }
            }
            $out['visual_direction'] = $visual;
        }
        $out['trusted_facts'] = $fallback['trusted_facts'];

        return $out;
    }

    /**
     * @return list<array{key: string, value: string, source: string}>
     */
    private function trustedFacts(Company $company): array
    {
        $facts = [];
        foreach ([
            'name' => $company->name,
            'city' => $company->city,
            'phone' => $company->phone,
            'email' => $company->email,
            'website' => $company->website,
            'industry' => $company->industry,
            'street' => $company->street,
            'postal_code' => $company->postal_code,
        ] as $key => $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $facts[] = ['key' => $key, 'value' => $value, 'source' => 'nexa_company'];
            }
        }

        return $facts;
    }

    /**
     * @return array<string, string>
     */
    private function companyFacts(Company $company): array
    {
        return [
            'naam' => (string) $company->name,
            'plaats' => (string) ($company->city ?? ''),
            'branche' => (string) ($company->industry ?? ''),
            'telefoon' => (string) ($company->phone ?? ''),
            'e-mail' => (string) ($company->email ?? ''),
            'website' => (string) ($company->website ?? ''),
            'omschrijving' => (string) ($company->description ?? ''),
        ];
    }

    /**
     * @return list<string>
     */
    private function guessServices(string $context, Company $company): array
    {
        $hay = mb_strtolower($context.' '.(string) ($company->industry ?? '').' '.(string) $company->name);
        if (str_contains($hay, 'taxi') || str_contains($hay, 'vervoer')) {
            return ['Luchthavenvervoer', 'Zakelijke ritten', 'Stadsritten'];
        }
        if (str_contains($hay, 'loodgieter') || str_contains($hay, 'installatie')) {
            return ['Lekkages', 'CV-installaties', 'Sanitair'];
        }

        return ['Advies', 'Uitvoering', 'Onderhoud'];
    }
}
