<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\WebsitePage;
use App\Services\WebsiteBuilderService;
use App\Services\WebsitePageSeoGeneratorService;
use App\Support\WebsiteSeoMeta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Vernieuw titel/meta voor taxi-tenant websitepagina's (o.a. Taxi Royaal, NexaTaxi).
 * Gebruikt template-SEO (geen OpenAI-vereiste) zodat PROD-deploy betrouwbaar blijft.
 */
class RefreshTaxiWebsiteSeoCommand extends Command
{
    protected $signature = 'nexa:refresh-taxi-website-seo
                            {--company= : Optioneel company id}
                            {--dry-run : Toon wijzigingen zonder op te slaan}';

    protected $description = 'Genereer SEO-titels/meta voor alle taxi-tenant websites (incl. Royaal)';

    public function handle(
        WebsitePageSeoGeneratorService $seoGenerator,
        WebsiteBuilderService $websiteBuilder,
    ): int {
        $dry = (bool) $this->option('dry-run');
        $onlyId = $this->option('company');

        $query = Company::query()->orderBy('id');
        if ($onlyId !== null && $onlyId !== '') {
            $query->whereKey((int) $onlyId);
        }

        $updated = 0;
        $skipped = 0;

        foreach ($query->cursor() as $company) {
            if (! $company instanceof Company) {
                continue;
            }
            if (! $this->isTaxiCompany($company)) {
                $skipped++;

                continue;
            }

            $pages = WebsitePage::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->get();

            foreach ($pages as $page) {
                if (! $page instanceof WebsitePage) {
                    continue;
                }

                $branding = $websiteBuilder->getSiteBrandingForWebsitePage($page);
                $homeSections = is_array($page->home_sections) ? $page->home_sections : [];
                $pageContent = trim(
                    $seoGenerator->extractTextFromHomeSections($homeSections)
                    ."\n\n"
                    .$seoGenerator->normalizePageContent(['page_content' => (string) ($page->content ?? '')])
                );

                $result = $seoGenerator->generate([
                    'title' => (string) ($page->title ?? ''),
                    'page_type' => (string) ($page->page_type ?? 'custom'),
                    'module_name' => 'taxi',
                    'module_display_name' => 'Nexa Taxi',
                    'slug' => (string) ($page->slug ?? ''),
                    'site_name' => $branding['site_name'] ?? $company->name,
                    'site_description' => $branding['site_description'] ?? '',
                    'company_name' => $company->name,
                    'company_city' => (string) ($company->city ?? ''),
                    'include_sections' => false,
                    'page_content' => $pageContent,
                    'home_sections' => $homeSections,
                ]);

                $title = trim((string) ($result['title'] ?? ''));
                $meta = trim((string) ($result['meta_description'] ?? ''));

                // Homepage: forceer Taxi-keyword vroeg in de Google-titel.
                if (($page->page_type ?? '') === 'home' || in_array(strtolower((string) $page->slug), ['', 'home'], true)) {
                    $title = WebsiteSeoMeta::resolveTitle(
                        $title !== '' ? $title : (string) $company->name,
                        WebsiteSeoMeta::brandLabel($branding) ?: (string) $company->name,
                        'home',
                    );
                }

                if ($title === '' && $meta === '') {
                    continue;
                }

                $this->line(sprintf(
                    '[%s #%d %s] title=%s | meta=%s',
                    $company->name,
                    $page->id,
                    $page->slug,
                    $title !== '' ? $title : '(ongewijzigd)',
                    $meta !== '' ? mb_substr($meta, 0, 80).'…' : '(ongewijzigd)',
                ));

                if ($dry) {
                    continue;
                }

                $data = [];
                if ($title !== '') {
                    $data['title'] = $title;
                }
                if ($meta !== '') {
                    $data['meta_description'] = $meta;
                }
                if ($data === []) {
                    continue;
                }

                try {
                    $page->fill($data);
                    $page->save();
                    $updated++;
                } catch (\Throwable $e) {
                    Log::warning('nexa:refresh-taxi-website-seo failed', [
                        'company_id' => $company->id,
                        'page_id' => $page->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error('Failed page #'.$page->id.': '.$e->getMessage());
                }
            }
        }

        $this->info($dry
            ? "Dry-run klaar (taxi skipped non-taxi companies: {$skipped})."
            : "SEO bijgewerkt voor {$updated} pagina(s); non-taxi companies overgeslagen: {$skipped}."
        );

        return self::SUCCESS;
    }

    private function isTaxiCompany(Company $company): bool
    {
        try {
            if ($company->hasTaxiModule()) {
                return true;
            }
        } catch (\Throwable) {
            // ignore
        }

        $blob = mb_strtolower(trim(($company->name ?? '').' '.($company->slug ?? '')));

        return str_contains($blob, 'taxi') || str_contains($blob, 'royaal');
    }
}
