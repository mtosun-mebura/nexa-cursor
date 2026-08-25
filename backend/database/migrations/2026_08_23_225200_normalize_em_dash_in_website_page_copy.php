<?php

use App\Models\WebsitePage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private const REPLACEMENTS = [
        'Merkbare tenant-sites met boekingsmodule, reviews en SEO — zonder apart CMS.' => 'Merkbare tenant-sites met boekingsmodule, reviews en SEO, zonder apart CMS.',
        'Werkplaats / werkorders — positioneer als “binnenkort”, niet als live product.' => 'Werkplaats / werkorders: positioneer als “binnenkort”, niet als live product.',
        'Minder telefoonchaos, meer boekingen — white-label en klaar om te groeien.' => 'Minder telefoonchaos, meer boekingen. White-label en klaar om te groeien.',
        'Plan een korte demo. We laten website, dispatch en chauffeur-app zien — met jullie merkkleuren.' => 'Plan een korte demo. We laten website, dispatch en chauffeur-app zien, met jullie merkkleuren.',
        'We laten website, dispatch en chauffeur-app zien — met jullie merkkleuren.' => 'We laten website, dispatch en chauffeur-app zien, met jullie merkkleuren.',
        'Boeking, chauffeur-app en contractportaal — zoals klanten het zien.' => 'Boeking, chauffeur-app en contractportaal, zoals klanten het zien.',
        'Taxi eerst — de rest groeit mee' => 'Taxi eerst. De rest groeit mee',
        'Schoolroutes, planning, ouderportaal en afmeldingen — upsell binnen Taxi.' => 'Schoolroutes, planning, ouderportaal en afmeldingen: upsell binnen Taxi.',
        'Ritten toewijzen, waves, accept/decline, redispatch — overzicht voor de centrale.' => 'Ritten toewijzen, waves, accept/decline, redispatch: overzicht voor de centrale.',
        'Inbox, actieve rit en geplande ritten — zoals de chauffeur het ziet.' => 'Inbox, actieve rit en geplande ritten, zoals de chauffeur het ziet.',
        'Zelfde planning, zelfde afmeldingen, zelfde factuur — of je nu kinderen naar school brengt of cliënten naar het ziekenhuis.' => 'Zelfde planning, zelfde afmeldingen, zelfde factuur, of je nu kinderen naar school brengt of cliënten naar het ziekenhuis.',
        'Website builder — merk + boeking zonder apart CMS' => 'Website builder: merk + boeking zonder apart CMS',
        'Taxibedrijf wil “online zichtbaar” — de website is de haak.' => 'Taxibedrijf wil “online zichtbaar”: de website is de haak.',
        'Modulair SaaS voor taxibedrijven: website, online boeking, chauffeur-app en contractvervoer — white-label per tenant.' => 'Modulair SaaS voor taxibedrijven: website, online boeking, chauffeur-app en contractvervoer. White-label per tenant.',
        'Start — €' => 'Start: €',
        'Pro — €' => 'Pro: €',
        'Business — €' => 'Business: €',
        'Vanaf € 750 — ' => 'Vanaf € 750: ',
        '€ 49 / maand — ' => '€ 49 / maand: ',
        '€ 99 / maand — ' => '€ 99 / maand: ',
        '€ 179 / maand — ' => '€ 179 / maand: ',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('website_pages')) {
            return;
        }

        WebsitePage::query()->chunkById(100, function ($pages): void {
            foreach ($pages as $page) {
                $sections = $page->home_sections;
                if (! is_array($sections)) {
                    continue;
                }
                $updated = $this->normalizeEmDashInValue($sections);
                if ($updated === $sections) {
                    continue;
                }
                $page->home_sections = $updated;
                $page->save();
            }
        });
    }

    public function down(): void
    {
        // Tekstnormalisatie wordt niet teruggedraaid.
    }

  /**
   * @param  mixed  $value
   * @return mixed
   */
    private function normalizeEmDashInValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->normalizeEmDashString($value);
        }
        if (! is_array($value)) {
            return $value;
        }
        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeEmDashInValue($item);
        }

        return $value;
    }

    private function normalizeEmDashString(string $value): string
    {
        $normalized = str_replace(array_keys(self::REPLACEMENTS), array_values(self::REPLACEMENTS), $value);
        if (str_contains($normalized, '—')) {
            $normalized = preg_replace('/\s—\s/u', ', ', $normalized) ?? $normalized;
        }

        return $normalized;
    }
};
