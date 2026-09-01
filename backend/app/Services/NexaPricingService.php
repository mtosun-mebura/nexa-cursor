<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\WebsitePage;
use App\Support\TenantPackageAddon;
use App\Support\TenantPackageCapability;

/**
 * Publieke NEXA Suite-prijzen: config-defaults, overschrijfbaar via super-admin.
 */
class NexaPricingService
{
    public const SETTING_KEY = 'nexa_pricing';

    public const PACKAGES_SECTION_KEY = 'component:website.pricing_packages';

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $config = config('nexa_pricing', []);

        return $this->normalize(is_array($config) ? $config : []);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $raw = GeneralSetting::get(self::SETTING_KEY, null);
        if (! is_string($raw) || trim($raw) === '') {
            return $this->defaults();
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $this->defaults();
        }

        return $this->normalize($decoded);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function save(array $input): array
    {
        $pricing = $this->normalize($input);
        GeneralSetting::set(self::SETTING_KEY, json_encode($pricing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->syncWebsitePage($pricing);

        return $pricing;
    }

    /**
     * @return array<string, mixed>
     */
    public function sectionPayload(?array $pricing = null): array
    {
        $pricing = $pricing ?? $this->get();
        $packages = [];
        foreach ($pricing['packages'] as $package) {
            $features = $package['features'];
            unset($package['entitlements']);
            $packages[] = array_merge($package, [
                'features_text' => implode("\n", $features),
            ]);
        }

        $website = $pricing['website'];
        $website['features_text'] = implode("\n", $website['features']);

        return [
            'eyebrow' => $pricing['eyebrow'],
            'title' => $pricing['title'],
            'subtitle' => $pricing['subtitle'],
            'note' => $pricing['vat_note'],
            'packages' => $packages,
            'website' => $website,
            'addons' => $pricing['addons'],
        ];
    }

    /**
     * Unieke kenmerken van alle maandpakketten, in volgorde van eerste voorkomen.
     *
     * @param  array<string, mixed>|null  $pricing
     * @return list<string>
     */
    public function featureCatalog(?array $pricing = null): array
    {
        $pricing = $pricing ?? $this->get();
        $packages = isset($pricing['packages']) && is_array($pricing['packages']) ? $pricing['packages'] : [];
        $labels = [];

        foreach ($packages as $package) {
            if (! is_array($package)) {
                continue;
            }
            $features = $package['features'] ?? [];
            if (! empty($package['features_text']) && is_string($package['features_text'])) {
                $features = preg_split('/\r\n|\r|\n/', $package['features_text']) ?: [];
            }
            if (! is_array($features)) {
                continue;
            }
            foreach ($features as $feature) {
                $label = trim((string) $feature);
                if ($label === '' || in_array($label, $labels, true)) {
                    continue;
                }
                $labels[] = $label;
            }
        }

        return $labels;
    }

    /**
     * Kenmerken als vergelijkingsmatrix: "Alles van …" telt als overname van het vorige pakket.
     *
     * @param  array<string, mixed>|null  $pricing
     * @return list<array{label: string, included: list<bool>}>
     */
    public function featureComparison(?array $pricing = null): array
    {
        $pricing = $pricing ?? $this->get();
        $packages = isset($pricing['packages']) && is_array($pricing['packages']) ? $pricing['packages'] : [];
        $expanded = [];
        $accumulated = [];

        foreach ($packages as $package) {
            $features = isset($package['features']) && is_array($package['features']) ? $package['features'] : [];
            $list = [];
            foreach ($features as $feature) {
                $label = trim((string) $feature);
                if ($label === '') {
                    continue;
                }
                if (preg_match('/^Alles van\b/iu', $label) === 1) {
                    $list = array_merge($list, $accumulated);

                    continue;
                }
                $list[] = $label;
            }
            $list = array_values(array_unique($list));
            $expanded[] = $list;
            $accumulated = $list;
        }

        $labels = [];
        foreach ($expanded as $list) {
            foreach ($list as $label) {
                if (! in_array($label, $labels, true)) {
                    $labels[] = $label;
                }
            }
        }

        $rows = [];
        foreach ($labels as $label) {
            $included = [];
            foreach ($expanded as $list) {
                $included[] = in_array($label, $list, true);
            }
            $rows[] = [
                'label' => $label,
                'included' => $included,
            ];
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function packageNames(?array $pricing = null): array
    {
        $pricing = $pricing ?? $this->get();
        $names = [];
        foreach ($pricing['packages'] ?? [] as $package) {
            if (! is_array($package)) {
                continue;
            }
            $name = trim((string) ($package['name'] ?? ''));
            if ($name !== '' && ! in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @return array<string, string> key => naam
     */
    public function packagesForSelect(?array $pricing = null): array
    {
        $pricing = $pricing ?? $this->get();
        $out = [];
        foreach ($pricing['packages'] ?? [] as $package) {
            if (! is_array($package)) {
                continue;
            }
            $key = trim((string) ($package['key'] ?? ''));
            $name = trim((string) ($package['name'] ?? ''));
            if ($key === '' || $name === '') {
                continue;
            }
            $out[$key] = $name;
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function modulesCatalog(?array $pricing = null): array
    {
        $pricing = $pricing ?? $this->get();

        return TenantPackageAddon::catalogWithPrices(
            is_array($pricing['modules'] ?? null) ? $pricing['modules'] : []
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function packageByKey(string $key, ?array $pricing = null): ?array
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }
        $pricing = $pricing ?? $this->get();
        foreach ($pricing['packages'] ?? [] as $package) {
            if (! is_array($package)) {
                continue;
            }
            if (strcasecmp((string) ($package['key'] ?? ''), $key) === 0) {
                return $package;
            }
        }

        return null;
    }

    public function packageRank(string $key, ?array $pricing = null): ?int
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }
        $pricing = $pricing ?? $this->get();
        foreach (array_values($pricing['packages'] ?? []) as $index => $package) {
            if (! is_array($package)) {
                continue;
            }
            if (strcasecmp((string) ($package['key'] ?? ''), $key) === 0) {
                return (int) $index;
            }
        }

        return null;
    }

    public function monthlyAmountForKey(string $key, ?array $pricing = null): ?float
    {
        $package = $this->packageByKey($key, $pricing);
        if ($package === null) {
            return null;
        }

        return $this->parseMonthlyAmount((string) ($package['price'] ?? ''));
    }

    public function parseMonthlyAmount(string $value): float
    {
        $stripped = trim((string) preg_replace('/^€\s*/u', '', trim($value)));
        $stripped = (string) preg_replace('/,-$/', '', $stripped);
        $stripped = str_replace(' ', '', $stripped);
        if (preg_match('/^\d+,\d{1,2}$/', $stripped) === 1) {
            $stripped = str_replace(',', '.', $stripped);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+,\d{1,2}$/', $stripped) === 1) {
            $stripped = str_replace('.', '', $stripped);
            $stripped = str_replace(',', '.', $stripped);
        }

        return round(max(0, (float) $stripped), 2);
    }

    public function matchPackageName(?string $value, ?array $pricing = null): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        foreach ($this->packageNames($pricing) as $name) {
            if (strcasecmp($name, $value) === 0) {
                return $name;
            }
        }

        return null;
    }

    public function interestMessage(string $packageName): string
    {
        $name = trim($packageName);

        return 'Ik ben geïnteresseerd in het pakket '.$name.'. Neem gerust contact met me op over de mogelijkheden en hoe we kunnen starten.';
    }

    public function signupUrl(array $package): string
    {
        $url = trim((string) ($package['cta_url'] ?? '/contact'));
        if ($url === '') {
            $url = '/contact';
        }
        $name = trim((string) ($package['name'] ?? ''));
        if ($name === '') {
            return $url;
        }
        $base = parse_url($url, PHP_URL_PATH) ?: $url;
        if ($base === '/contact' || str_ends_with($base, '/contact')) {
            $separator = str_contains($url, '?') ? '&' : '?';

            return $url.$separator.'pakket='.rawurlencode($name);
        }

        return $url;
    }

    public function startPrice(?array $pricing = null): string
    {
        $pricing = $pricing ?? $this->get();
        $first = $pricing['packages'][0] ?? [];

        return trim((string) ($first['price'] ?? '49'));
    }

    /**
     * @param  array<string, mixed>  $package
     */
    public function packageOffer(array $package): string
    {
        return trim((string) ($package['offer'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $package
     */
    public function packageFreeMonths(array $package): int
    {
        $raw = $package['free_months'] ?? 0;
        if (is_string($raw) && trim($raw) === '') {
            return 0;
        }

        return max(0, min(24, (int) $raw));
    }

    public function freeMonthsLabel(int $months): string
    {
        if ($months <= 0) {
            return '';
        }

        return $months === 1 ? '1 maand gratis' : $months.' maanden gratis';
    }

    public function looksLikeAmount(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        $stripped = trim((string) preg_replace('/^€\s*/u', '', $value));

        return preg_match('/^\d+,-$/', $stripped) === 1
            || preg_match('/^\d+([.,]\d{1,2})?$/', $stripped) === 1;
    }

    public function displayAmount(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $stripped = trim((string) preg_replace('/^€\s*/u', '', $value));
        if (preg_match('/^\d+,-$/', $stripped) === 1) {
            return '€ '.$stripped;
        }
        if (preg_match('/^\d+([.,]\d{1,2})?$/', $stripped) !== 1) {
            return $value;
        }
        $normalized = str_replace('.', ',', $stripped);
        if (preg_match('/^\d+(,00)?$/', $normalized) === 1) {
            return '€ '.explode(',', $normalized)[0].',-';
        }

        return '€ '.$normalized;
    }

    /**
     * @param  array<string, mixed>  $package
     * @return array{has_deal: bool, hero: string, after_label: string, was_label: string, period: string, price_label: string}
     */
    public function packagePricePresentation(array $package): array
    {
        $priceLabel = $this->displayAmount((string) ($package['price'] ?? ''));
        $period = trim((string) ($package['period'] ?? ''));
        $offer = $this->packageOffer($package);
        $offerLabel = $offer !== '' ? $this->displayAmount($offer) : '';
        $offerIsPrice = $offer !== '' && $this->looksLikeAmount($offer);
        $freeMonths = $this->packageFreeMonths($package);
        $hasDeal = $freeMonths > 0 || $offerLabel !== '';

        if (! $hasDeal) {
            return [
                'has_deal' => false,
                'hero' => $priceLabel,
                'after_label' => '',
                'was_label' => '',
                'period' => $period,
                'price_label' => $priceLabel,
            ];
        }

        $ongoingPrice = $offerIsPrice ? $offerLabel : $priceLabel;

        if ($freeMonths > 0) {
            return [
                'has_deal' => true,
                'hero' => $this->freeMonthsLabel($freeMonths),
                'after_label' => $ongoingPrice !== '' ? 'daarna '.$ongoingPrice : '',
                'was_label' => ($offerIsPrice && $offerLabel !== $priceLabel) ? $priceLabel : '',
                'period' => $period,
                'price_label' => $priceLabel,
            ];
        }

        return [
            'has_deal' => true,
            'hero' => $offerLabel,
            'after_label' => '',
            'was_label' => $priceLabel,
            'period' => $period,
            'price_label' => $priceLabel,
        ];
    }

    public function websitePrice(?array $pricing = null): string
    {
        $pricing = $pricing ?? $this->get();

        return trim((string) ($pricing['website']['price_label'] ?? '750'));
    }

    /**
     * @param  array<string, mixed>|null  $website
     * @return array{has_deal: bool, hero: string, after_label: string, was_label: string, period: string, price_label: string}
     */
    public function websitePricePresentation(?array $website = null): array
    {
        if ($website === null) {
            $website = $this->get()['website'] ?? [];
        }

        return $this->packagePricePresentation([
            'price' => (string) ($website['price_label'] ?? ''),
            'offer' => (string) ($website['offer'] ?? ''),
            'period' => (string) ($website['period'] ?? 'eenmalig'),
            'free_months' => 0,
        ]);
    }

    public function faqAnswer(string $contactEmail): string
    {
        return $this->faqPackagesOverview($contactEmail);
    }

    public function faqPackagesOverview(string $contactEmail): string
    {
        $pricing = $this->get();
        $blocks = [];
        foreach ($pricing['packages'] as $package) {
            if (! is_array($package)) {
                continue;
            }
            $block = $this->formatFaqPackageBlock($package);
            if ($block !== '') {
                $blocks[] = $block;
            }
        }

        $intro = $blocks === []
            ? 'NEXA Suite heeft een vast maandbedrag voor het platform. Geen marktplaats-commissie.'
            : 'NEXA Suite heeft '.count($blocks).' maandpakketten. Een vast bedrag, geen marktplaats-commissie.';

        $parts = [$intro, implode("\n\n", $blocks)];
        $website = $this->formatFaqWebsiteBlock($pricing);
        if ($website !== '') {
            $parts[] = $website;
        }

        $addonLine = $this->formatFaqAddonLine($pricing);
        if ($addonLine !== '') {
            $parts[] = $addonLine;
        }

        $vat = trim((string) ($pricing['vat_note'] ?? ''));
        if ($vat !== '') {
            $parts[] = $vat;
        }

        $parts[] = 'Volledig overzicht: [Prijzen](/prijzen). Voorstel op maat: [contactformulier](/contact).'."\n\n".'E-mail: '.$contactEmail.'.';

        return implode("\n\n", array_filter($parts));
    }

    public function faqPackageAnswer(string $packageName, string $contactEmail): string
    {
        $pricing = $this->get();
        $package = $this->packageByName($pricing, $packageName);
        if ($package === null) {
            return $this->faqPackagesOverview($contactEmail);
        }

        $block = $this->formatFaqPackageBlock($package);
        $url = $this->signupUrl($package);
        $vat = trim((string) ($pricing['vat_note'] ?? ''));
        $suffix = 'Aanvragen: [contactformulier]('.$url.'). Alle pakketten: [Prijzen](/prijzen).'."\n\n".'E-mail: '.$contactEmail.'.';

        return trim($block."\n\n".($vat !== '' ? $vat."\n\n" : '').$suffix);
    }

    public function faqWebsiteAnswer(string $contactEmail): string
    {
        $pricing = $this->get();
        $block = $this->formatFaqWebsiteBlock($pricing);
        if ($block === '') {
            return $this->faqPackagesOverview($contactEmail);
        }

        $vat = trim((string) ($pricing['vat_note'] ?? ''));

        return trim($block."\n\nDaarna betaal je alleen het maandabonnement (Start, Pro of Business).\n\n".($vat !== '' ? $vat."\n\n" : '').'Meer: [Prijzen](/prijzen). Bespreken: [contactformulier](/contact).'."\n\n".'E-mail: '.$contactEmail.'.');
    }

    /**
     * @param  array<string, mixed>  $pricing
     */
    public function syncWebsitePage(?array $pricing = null): WebsitePage
    {
        $pricing = $pricing ?? $this->get();
        $central = app(CentralWelcomePageService::class);
        $central->ensureMarketingPagesExist();

        $page = WebsitePage::query()
            ->where('slug', CentralWelcomePageService::PRIJZEN_SLUG)
            ->whereNull('module_name')
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn((new WebsitePage)->getTable(), 'company_id'),
                fn ($q) => $q->whereNull('company_id')
            )
            ->first();

        if ($page === null) {
            $page = $central->ensureMarketingPagesExist()
                ->first(fn (WebsitePage $item) => $item->slug === CentralWelcomePageService::PRIJZEN_SLUG);
        }

        if ($page === null) {
            throw new \RuntimeException('De prijzenpagina kon niet worden aangemaakt.');
        }

        $sections = is_array($page->home_sections) ? $page->home_sections : [];
        $start = $this->startPrice($pricing);
        $websitePrice = $this->websitePrice($pricing);

        $hero = is_array($sections['hero'] ?? null) ? $sections['hero'] : [];
        $hero['title'] = 'Duidelijke prijzen. Start bij € '.$start.' per maand.';
        $hero['title_highlight'] = '€ '.$start.' per maand';
        $hero['subtitle'] = 'Kies het pakket dat bij je ritten past. Website live zetten vanaf € '.$websitePrice.' eenmalig. Daarna alleen het abonnement.';
        $sections['hero'] = $hero;
        $existingPackages = is_array($sections[self::PACKAGES_SECTION_KEY] ?? null)
            ? $sections[self::PACKAGES_SECTION_KEY]
            : [];
        $payload = $this->sectionPayload($pricing);
        $payload['width_percent'] = max(30, min(100, (int) ($existingPackages['width_percent'] ?? 100)));
        $sections[self::PACKAGES_SECTION_KEY] = $payload;

        $order = is_array($sections['section_order'] ?? null) ? $sections['section_order'] : [];
        if (! in_array(self::PACKAGES_SECTION_KEY, $order, true)) {
            array_splice($order, min(1, count($order)), 0, [self::PACKAGES_SECTION_KEY]);
            $sections['section_order'] = $order;
        }
        $visibility = is_array($sections['visibility'] ?? null) ? $sections['visibility'] : [];
        $visibility[self::PACKAGES_SECTION_KEY] = true;
        $sections['visibility'] = $visibility;

        $page->home_sections = $sections;
        $page->title = 'Prijzen';
        if (trim((string) ($page->menu_title ?? '')) === '') {
            $page->menu_title = 'Prijzen';
        }
        $page->meta_description = 'NEXA Suite vanaf €'.$start.' per maand. Website live zetten vanaf €'.$websitePrice.' eenmalig.';
        $page->is_active = true;
        $page->show_in_menu = true;
        $page->save();

        return $page->fresh();
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function normalize(array $raw): array
    {
        $lines = static function (mixed $features, mixed $featuresText = ''): array {
            if (is_string($featuresText) && trim($featuresText) !== '') {
                $features = preg_split('/\r\n|\r|\n/', $featuresText) ?: [];
            }
            if (! is_array($features)) {
                return [];
            }

            return array_values(array_filter(array_map(
                static fn ($line) => trim((string) $line),
                $features
            ), static fn ($line) => $line !== ''));
        };

        $packages = [];
        $usedKeys = [];
        foreach (isset($raw['packages']) && is_array($raw['packages']) ? array_values($raw['packages']) : [] as $package) {
            if (! is_array($package)) {
                continue;
            }
            $name = trim((string) ($package['name'] ?? ''));
            $price = trim((string) ($package['price'] ?? ''));
            $features = $lines($package['features'] ?? [], $package['features_text'] ?? '');
            if ($name === '' && $price === '' && $features === []) {
                continue;
            }
            $key = $this->uniquePackageKey($package, $name, $usedKeys);
            $usedKeys[] = $key;
            $packages[] = [
                'key' => $key,
                'name' => $name,
                'audience' => trim((string) ($package['audience'] ?? '')),
                'price' => $price,
                'offer' => trim((string) ($package['offer'] ?? '')),
                'free_months' => $this->packageFreeMonths($package),
                'period' => trim((string) ($package['period'] ?? 'per maand')),
                'badge' => trim((string) ($package['badge'] ?? '')),
                'highlighted' => filter_var($package['highlighted'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'cta_text' => trim((string) ($package['cta_text'] ?? 'Aanvragen')),
                'cta_url' => trim((string) ($package['cta_url'] ?? '/contact')),
                'features' => $features,
                'entitlements' => TenantPackageCapability::normalize(
                    is_array($package['entitlements'] ?? null) ? $package['entitlements'] : [],
                    $key
                ),
            ];
        }

        $websiteRaw = is_array($raw['website'] ?? null) ? $raw['website'] : [];
        $websiteFeatures = $lines($websiteRaw['features'] ?? [], $websiteRaw['features_text'] ?? '');

        $addons = [];
        foreach (isset($raw['addons']) && is_array($raw['addons']) ? array_values($raw['addons']) : [] as $addon) {
            if (! is_array($addon)) {
                continue;
            }
            $name = trim((string) ($addon['name'] ?? ''));
            $price = trim((string) ($addon['price'] ?? ''));
            $description = trim((string) ($addon['description'] ?? ''));
            if ($name === '' && $price === '' && $description === '') {
                continue;
            }
            $addons[] = [
                'name' => $name,
                'price' => $price,
                'description' => $description,
            ];
        }

        return [
            'vat_note' => trim((string) ($raw['vat_note'] ?? '')),
            'eyebrow' => trim((string) ($raw['eyebrow'] ?? 'Prijzen')),
            'title' => trim((string) ($raw['title'] ?? '')),
            'subtitle' => trim((string) ($raw['subtitle'] ?? '')),
            'packages' => $packages,
            'website' => [
                'title' => trim((string) ($websiteRaw['title'] ?? 'Website live zetten')),
                'price_prefix' => trim((string) ($websiteRaw['price_prefix'] ?? 'vanaf')),
                'price_label' => trim((string) ($websiteRaw['price_label'] ?? '')),
                'offer' => trim((string) ($websiteRaw['offer'] ?? '')),
                'period' => trim((string) ($websiteRaw['period'] ?? 'eenmalig')),
                'subtitle' => trim((string) ($websiteRaw['subtitle'] ?? '')),
                'cta_text' => trim((string) ($websiteRaw['cta_text'] ?? 'Website bespreken')),
                'cta_url' => trim((string) ($websiteRaw['cta_url'] ?? '/contact')),
                'features' => $websiteFeatures,
            ],
            'addons' => $addons,
            'modules' => TenantPackageAddon::normalizeCatalog(
                is_array($raw['modules'] ?? null) ? $raw['modules'] : []
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $package
     * @param  list<string>  $usedKeys
     */
    private function uniquePackageKey(array $package, string $name, array $usedKeys): string
    {
        $key = trim((string) ($package['key'] ?? ''));
        if ($key === '') {
            $key = TenantPackageCapability::slugFromName($name);
        } else {
            $key = TenantPackageCapability::slugFromName($key);
        }
        $base = $key;
        $i = 2;
        while (in_array($key, $usedKeys, true)) {
            $key = $base.'-'.$i;
            $i++;
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>  $package
     */
    private function formatFaqPackageBlock(array $package): string
    {
        $name = trim((string) ($package['name'] ?? ''));
        if ($name === '') {
            return '';
        }

        $period = trim((string) ($package['period'] ?? 'per maand'));
        $price = $this->faqPricePhrase($package, (string) ($package['price'] ?? ''));
        $audience = trim((string) ($package['audience'] ?? ''));
        $highlighted = ! empty($package['highlighted']);
        $title = '**'.$name.'**';
        if ($highlighted) {
            $title .= ' (aanbevolen)';
        }
        if ($price !== '') {
            $title .= ' — '.$price.($period !== '' ? ' '.$period : '');
        }

        $lines = [$title];
        if ($audience !== '') {
            $lines[] = $audience.'.';
        }

        $features = isset($package['features']) && is_array($package['features']) ? $package['features'] : [];
        foreach ($features as $feature) {
            $label = trim((string) $feature);
            if ($label === '') {
                continue;
            }
            $lines[] = '- '.$label;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $pricing
     */
    private function formatFaqWebsiteBlock(array $pricing): string
    {
        $website = is_array($pricing['website'] ?? null) ? $pricing['website'] : [];
        $title = trim((string) ($website['title'] ?? 'Website live zetten'));
        $prefix = trim((string) ($website['price_prefix'] ?? 'vanaf'));
        $period = trim((string) ($website['period'] ?? 'eenmalig'));
        $subtitle = trim((string) ($website['subtitle'] ?? ''));
        $deal = $this->websitePricePresentation($website);
        if ($deal['has_deal']) {
            $headline = '**'.$title.'** — '.$deal['hero'];
            if ($deal['was_label'] !== '') {
                $headline .= ' (was '.$deal['was_label'].')';
            }
            if ($period !== '') {
                $headline .= ', '.$period;
            }
        } else {
            $price = $this->displayAmount((string) ($website['price_label'] ?? $this->websitePrice($pricing)));
            $headline = '**'.$title.'** — '.trim($prefix.' '.$price).($period !== '' ? ', '.$period : '');
        }
        $lines = [$headline];
        if ($subtitle !== '') {
            $lines[] = $subtitle;
        }
        $features = isset($website['features']) && is_array($website['features']) ? $website['features'] : [];
        foreach ($features as $feature) {
            $label = trim((string) $feature);
            if ($label === '') {
                continue;
            }
            $lines[] = '- '.$label;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $pricing
     */
    private function formatFaqAddonLine(array $pricing): string
    {
        $addonBits = [];
        foreach ($pricing['addons'] ?? [] as $addon) {
            if (! is_array($addon)) {
                continue;
            }
            $name = trim((string) ($addon['name'] ?? ''));
            $price = trim((string) ($addon['price'] ?? ''));
            $description = trim((string) ($addon['description'] ?? ''));
            if ($name === '' && $price === '') {
                continue;
            }
            $bit = trim($name.($price !== '' ? ' '.$price : ''));
            if ($description !== '') {
                $bit .= ' ('.$description.')';
            }
            $addonBits[] = $bit;
        }

        return $addonBits !== [] ? 'Extra’s: '.implode('; ', $addonBits).'.' : '';
    }

    /**
     * @param  array<string, mixed>  $package
     */
    private function faqPricePhrase(array $package, string $fallback): string
    {
        $price = trim((string) ($package['price'] ?? $fallback));
        $display = $this->displayAmount($price);
        $freeMonths = $this->packageFreeMonths($package);
        $offer = $this->packageOffer($package);
        $extras = [];
        if ($freeMonths > 0) {
            $extras[] = $this->freeMonthsLabel($freeMonths).', daarna '.$display;
        }
        if ($offer !== '') {
            $extras[] = 'aanbiedingsprijs '.$this->displayAmount($offer);
        }
        if ($extras === []) {
            return $display;
        }

        return $display.' ('.implode('; ', $extras).')';
    }

    /**
     * @param  array<string, mixed>  $pricing
     * @return array<string, mixed>|null
     */
    private function packageByName(array $pricing, string $name): ?array
    {
        foreach ($pricing['packages'] ?? [] as $package) {
            if (is_array($package) && strcasecmp((string) ($package['name'] ?? ''), $name) === 0) {
                return $package;
            }
        }

        return null;
    }
}
