<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\WebsitePage;
use App\Services\NexaTaxiBookingPricingService;
use App\Support\TenantPackageAddon;
use Illuminate\Support\Facades\Schema;

class TaxiTosunBookingGpsDemoService
{
    public const COMPANY_SLUGS = ['taxitosun', 'taxi-tosun'];

    /**
     * Zet GPS-trackers aan voor Taxi Tosun en schakelt de demo-vloot in op de boekingsmodule v2.
     *
     * @return array{company: Company, pages: int}
     */
    public function enable(): array
    {
        $company = $this->findCompany();
        if ($company === null) {
            throw new \RuntimeException('Bedrijf Taxi Tosun is niet gevonden (slug taxitosun / taxi-tosun of naam).');
        }

        $this->enableGpsAddon($company);
        $pagesUpdated = $this->enableBookingDemoFlags($company);

        return [
            'company' => $company->fresh(),
            'pages' => $pagesUpdated,
        ];
    }

    public function findCompany(): ?Company
    {
        $company = Company::query()
            ->whereIn('slug', self::COMPANY_SLUGS)
            ->first();
        if ($company) {
            return $company;
        }

        return Company::query()
            ->whereRaw('LOWER(name) LIKE ?', ['%taxi tosun%'])
            ->orWhereRaw('LOWER(name) = ?', ['taxitosun'])
            ->first();
    }

    private function enableGpsAddon(Company $company): void
    {
        $addons = is_array($company->package_addons) ? $company->package_addons : [];
        $addons[TenantPackageAddon::GPS_TRACKING] = 1;
        $company->package_addons = $addons;
        if (! $company->latitude || ! $company->longitude) {
            $company->latitude = $company->latitude ?: '52.2215';
            $company->longitude = $company->longitude ?: '6.8937';
            $company->city = $company->city ?: 'Enschede';
        }
        $company->save();
    }

    private function enableBookingDemoFlags(Company $company): int
    {
        if (! Schema::hasTable('website_pages') || ! Schema::hasColumn('website_pages', 'home_sections')) {
            return 0;
        }

        $pricing = app(NexaTaxiBookingPricingService::class);
        $updated = 0;
        $pages = WebsitePage::query()
            ->where('company_id', $company->id)
            ->where(function ($query) {
                $query->where('page_type', 'home')->orWhere('slug', 'home');
            })
            ->get();

        foreach ($pages as $page) {
            $sections = is_array($page->home_sections) ? $page->home_sections : [];
            $changed = false;
            foreach (['component:taxi.boekingsmodule_v2', 'component:taxi.boekingsmodule'] as $key) {
                if (! isset($sections[$key]) || ! is_array($sections[$key])) {
                    continue;
                }
                $merged = $pricing->mergeSectionConfig($sections[$key]);
                $merged['logic']['show_live_fleet'] = true;
                $merged['logic']['show_live_fleet_demo'] = true;
                $sections[$key] = $merged;
                $changed = true;
            }
            if ($changed) {
                $page->home_sections = $sections;
                $page->save();
                $updated++;
            }
        }

        return $updated;
    }
}
