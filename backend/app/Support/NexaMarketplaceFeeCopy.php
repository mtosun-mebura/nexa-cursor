<?php

namespace App\Support;

use App\Models\NexaSuiteMarketplaceSetting;
use Throwable;

/**
 * Publieke uitleg van de NEXA Suite-provisie: alleen ritten via nexasuite.nl.
 */
class NexaMarketplaceFeeCopy
{
    public const FAQ_QUESTION = 'Betaal ik commissie per rit?';

    public static function percent(): int
    {
        try {
            return max(0, min(100, (int) NexaSuiteMarketplaceSetting::current()->fee_percent));
        } catch (Throwable) {
            return max(0, min(100, (int) config('nexa_suite_marketplace.fee_percent', 10)));
        }
    }

    public static function packagesSubtitle(): string
    {
        return 'Een vast maandbedrag voor het platform en je eigen website. Een eenmalig bedrag om jouw website live te zetten. Geen provisie over ritten via jouw eigen site.';
    }

    public static function faqSubtitle(): string
    {
        return 'Vast maandbedrag voor ritten via jouw eigen website. Provisie alleen bij ritten via nexasuite.nl.';
    }

    public static function faqAnswer(): string
    {
        $percent = self::percent();

        return 'Nee, niet voor ritten via jouw eigen website. Die vallen onder het maandabonnement: daarover betaal je geen provisie per rit, de ritomzet blijft van jou. Alleen ritten die klanten op de algemene website nexasuite.nl boeken (niet op jouw eigen site) gaan naar het dichtstbijzijnde aangesloten taxibedrijf. Over díe ritten betaal je '.$percent.'% provisie over de ritomzet, exclusief btw.';
    }

    public static function featureTitle(): string
    {
        return 'Geen provisie op je eigen site';
    }

    public static function featureDescription(): string
    {
        return 'Boekingen via jouw eigen website vallen onder het maandabonnement. Alleen ritten vanaf nexasuite.nl (dichtstbijzijnde aangesloten taxibedrijf) kennen een provisie van '.self::percent().'%.';
    }

    public static function ownSiteStatsLabel(): string
    {
        return 'Commissie op je eigen website';
    }

    public static function statsSubtitle(): string
    {
        return 'Eén platform. Vast maandbedrag voor je eigen site. Altijd boekbaar.';
    }

    public static function pricingNoticeOwnSite(): string
    {
        return 'Het maandabonnement geldt voor NEXA Suite op jouw eigen website. Boekingen daarop kosten geen extra fee per rit.';
    }

    public static function pricingNoticeMarketplace(): string
    {
        return 'Anders is het bij ritten via de algemene website nexasuite.nl (bijvoorbeeld /boek of /taxi). Die gaan naar het dichtstbijzijnde aangesloten taxibedrijf. Over die ritten betaal je '.self::percent().'% provisie over de ritomzet (excl. btw).';
    }

    public static function chatAnswer(): string
    {
        return self::faqAnswer()."\n\nMeer: [Prijzen](/prijzen) en [algemene voorwaarden](/voorwaarden).";
    }

    /**
     * Zet achterhaalde “geen commissie”-copy op de centrale marketingpagina’s recht, met het actuele percentage.
     *
     * @param  array<string, mixed>  $sections
     * @return array<string, mixed>
     */
    public static function applyToHomeSections(array $sections): array
    {
        $hadCommissionFaq = false;
        $faqSectionKey = null;

        foreach ($sections as $key => $section) {
            if (! is_array($section)) {
                continue;
            }

            $items = $section['items'] ?? null;
            if (! is_array($items)) {
                continue;
            }

            $looksLikeFaq = false;
            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $question = trim((string) ($item['question'] ?? ''));
                $title = trim((string) ($item['title'] ?? ''));
                $label = trim((string) ($item['label'] ?? ''));

                if ($question !== '') {
                    $looksLikeFaq = true;
                    $faqSectionKey = $key;
                    if (self::mentionsCommission($question)) {
                        $sections[$key]['items'][$index]['answer'] = self::faqAnswer();
                        $hadCommissionFaq = true;
                    }
                }

                if ($title !== '' && self::mentionsCommission($title) && array_key_exists('description', $item)) {
                    $sections[$key]['items'][$index]['title'] = self::featureTitle();
                    $sections[$key]['items'][$index]['description'] = self::featureDescription();
                }

                if ($label !== '' && self::mentionsCommission($label) && array_key_exists('value', $item)) {
                    $sections[$key]['items'][$index]['value'] = '0';
                    $sections[$key]['items'][$index]['suffix'] = '';
                    $sections[$key]['items'][$index]['label'] = self::ownSiteStatsLabel();
                }
            }

            if (isset($section['subtitle']) && is_string($section['subtitle']) && self::mentionsCommission($section['subtitle'])) {
                $sections[$key]['subtitle'] = $looksLikeFaq ? self::faqSubtitle() : self::statsSubtitle();
            }
        }

        if (! $hadCommissionFaq && $faqSectionKey !== null && is_array($sections[$faqSectionKey]['items'] ?? null)) {
            $sections[$faqSectionKey]['items'] = array_values(array_merge(
                [[
                    'question' => self::FAQ_QUESTION,
                    'answer' => self::faqAnswer(),
                ]],
                $sections[$faqSectionKey]['items']
            ));
        }

        return $sections;
    }

    public static function mentionsCommission(string $text): bool
    {
        $hay = mb_strtolower($text);

        return str_contains($hay, 'commissie')
            || str_contains($hay, 'provisie')
            || str_contains($hay, 'marktplaats-commissie');
    }
}
