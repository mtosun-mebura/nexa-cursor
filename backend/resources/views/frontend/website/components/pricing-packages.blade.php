@php
    $sectionKey = $sectionKey ?? 'component:website.pricing_packages';
    $incomingSection = is_array($sectionData ?? null) ? $sectionData : [];
    $pageSection = is_array(($homeSections ?? [])[$sectionKey] ?? null) ? $homeSections[$sectionKey] : $incomingSection;
    $widthPercent = max(30, min(100, (int) ($pageSection['width_percent'] ?? 100)));
    $clampFontPx = static function ($value, int $fallback): int {
        $n = (int) $value;
        if ($n < 10 || $n > 24) {
            return $fallback;
        }

        return $n;
    };
    $packagesFontPx = $clampFontPx($pageSection['packages_font_size_px'] ?? 14, 14);
    $websiteFontPx = $clampFontPx($pageSection['website_font_size_px'] ?? 14, 14);
    $addonsFontPx = $clampFontPx($pageSection['addons_font_size_px'] ?? 14, 14);
    $websiteWidthPct = max(30, min(100, (int) ($pageSection['website_width_percent'] ?? 50)));
    /** @var \App\Services\NexaPricingService $pricingService */
    $pricingService = app(\App\Services\NexaPricingService::class);
    $sectionData = $pricingService->get();

    $eyebrow = trim((string) ($sectionData['eyebrow'] ?? 'Prijzen'));
    $title = trim((string) ($sectionData['title'] ?? ''));
    $subtitle = trim((string) ($sectionData['subtitle'] ?? ''));
    $note = trim((string) ($sectionData['vat_note'] ?? ''));

    $packages = isset($sectionData['packages']) && is_array($sectionData['packages'])
        ? array_values($sectionData['packages'])
        : [];

    $website = isset($sectionData['website']) && is_array($sectionData['website'])
        ? $sectionData['website']
        : [];

    $addons = isset($sectionData['addons']) && is_array($sectionData['addons'])
        ? array_values($sectionData['addons'])
        : [];

    $comparisonRows = $packages !== [] ? $pricingService->featureComparison($sectionData) : [];
    $packageCount = count($packages);
    $isPrijzenPage = request()->is('prijzen');
    $lastRowIndex = count($comparisonRows) - 1;

    $signupLabel = static function (array $package): string {
        $text = trim((string) ($package['cta_text'] ?? ''));
        if ($text === '' || preg_match('/aanvragen/i', $text) === 1) {
            return 'Aanmelden';
        }

        return $text;
    };
@endphp
<link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
<style>
    #prijzen-pakketten {
        --background: #fff;
        --foreground: #3f3f46;
        --primary: #3b82f6;
        --primary-foreground: #fff;
        --secondary: #f4f4f5;
        --secondary-foreground: #3f3f46;
        --muted: #f4f4f5;
        --muted-foreground: #71717a;
        --accent: #f4f4f5;
        --accent-foreground: #18181b;
        --mono: #09090b;
        --border: #e4e4e7;
        --input: #e4e4e7;
        --radius: 0.5rem;
        --spacing: 0.25rem;
        --color-green-50: #f0fdf4;
        --color-green-200: #bbf7d0;
        --color-green-500: #22c55e;
        --color-green-700: #15803d;
    }
    html.dark #prijzen-pakketten {
        --background: #18181b;
        --foreground: #d4d4d8;
        --secondary: #27272a;
        --secondary-foreground: #e4e4e7;
        --muted: #27272a;
        --muted-foreground: #a1a1aa;
        --accent: #27272a;
        --accent-foreground: #fafafa;
        --mono: #f4f4f5;
        --border: #3f3f46;
        --input: #3f3f46;
        --color-green-50: #052e16;
        --color-green-200: #166534;
        --color-green-700: #4ade80;
    }
    #prijzen-pakketten .text-mono { color: var(--mono); }
    #prijzen-pakketten .text-secondary-foreground { color: var(--muted-foreground); }
    #prijzen-pakketten .text-foreground { color: var(--foreground); }
    #prijzen-pakketten .text-green-500 { color: var(--color-green-500); }
    #prijzen-pakketten .nexa-plan-absent {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.125rem;
        height: 1.125rem;
        color: var(--muted-foreground);
        font-size: 1.125rem;
        font-weight: 600;
        line-height: 1;
    }
    #prijzen-pakketten .bg-muted\/40 { background-color: color-mix(in oklab, var(--muted) 40%, transparent); }
    #prijzen-pakketten .kt-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: transparent;
    }
    #prijzen-pakketten .kt-table td {
        border-color: var(--border);
        border-style: solid;
        border-width: 0;
        vertical-align: top;
        background: var(--background);
    }
    #prijzen-pakketten .kt-table td.nexa-plan-intro {
        background: transparent;
    }
    #prijzen-pakketten .kt-table td.nexa-plan-check {
        vertical-align: middle;
        text-align: center;
    }
    #prijzen-pakketten .nexa-plan-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 1.25rem;
        line-height: 1;
    }
    #prijzen-pakketten .kt-table td.bg-muted\/40 {
        background-color: color-mix(in oklab, var(--muted) 40%, transparent);
    }
    #prijzen-pakketten .kt-table td.border-t { border-top-width: 1px; }
    #prijzen-pakketten .kt-table td.border-b { border-bottom-width: 1px; }
    #prijzen-pakketten .kt-table td.border-s,
    #prijzen-pakketten .kt-table td.ltr\:border-l { border-left-width: 1px; }
    #prijzen-pakketten .kt-table td.border-e { border-right-width: 1px; }
    #prijzen-pakketten .kt-table td.border-b-0 { border-bottom-width: 0; }
    #prijzen-pakketten .ltr\:rounded-tl-xl { border-top-left-radius: 0.75rem; }
    #prijzen-pakketten .ltr\:rounded-tr-xl { border-top-right-radius: 0.75rem; }
    #prijzen-pakketten .ltr\:rounded-bl-xl { border-bottom-left-radius: 0.75rem; }
    #prijzen-pakketten .ltr\:rounded-br-xl { border-bottom-right-radius: 0.75rem; }
    #prijzen-pakketten .mt-7\.5 { margin-top: 1.875rem; }
    @media (min-width: 768px) {
        #prijzen-pakketten .md\:p-7\.5 { padding: 1.875rem; }
        #prijzen-pakketten .md\:flex { display: flex; }
        #prijzen-pakketten .md\:items-start { align-items: flex-start; }
        #prijzen-pakketten .md\:items-center { align-items: center; }
        #prijzen-pakketten .md\:justify-between { justify-content: space-between; }
        #prijzen-pakketten .md\:gap-10 { gap: 2.5rem; }
        #prijzen-pakketten .md\:mt-0 { margin-top: 0; }
        #prijzen-pakketten .md\:text-right { text-align: right; }
        #prijzen-pakketten .md\:flex-row { flex-direction: row; }
        #prijzen-pakketten .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    #prijzen-pakketten .kt-btn {
        display: inline-flex;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        height: 2.125rem;
        gap: 0.375rem;
        border-radius: calc(var(--radius) - 2px);
        padding-inline: 0.75rem;
        font-size: 0.8125rem;
        line-height: 1.25rem;
        font-weight: 500;
        white-space: nowrap;
        text-decoration: none;
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        transition: color .15s ease, background-color .15s ease, box-shadow .15s ease;
    }
    #prijzen-pakketten .kt-btn-primary {
        background-color: var(--primary);
        color: var(--primary-foreground);
        border: 0;
    }
    #prijzen-pakketten .kt-btn-primary:hover {
        background-color: color-mix(in oklab, var(--primary) 90%, transparent);
        color: var(--primary-foreground);
    }
    #prijzen-pakketten .kt-btn-outline {
        border: 1px solid var(--input);
        background-color: var(--background);
        color: var(--secondary-foreground);
        box-shadow: none;
    }
    #prijzen-pakketten .kt-btn-outline:hover {
        background-color: var(--accent);
        color: var(--accent-foreground);
    }
    #prijzen-pakketten .kt-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 1.5rem;
        min-width: 1.5rem;
        gap: 0.375rem;
        border-radius: calc(var(--radius) - 2px);
        padding-inline: 0.45rem;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1rem;
        white-space: nowrap;
    }
    #prijzen-pakketten .kt-badge-sm {
        height: 1.25rem;
        min-width: 1.25rem;
        gap: 0.25rem;
        border-radius: calc(var(--radius) - 4px);
        padding-inline: 0.325rem;
        font-size: 0.6875rem;
        line-height: 0.75rem;
    }
    #prijzen-pakketten .kt-badge-outline {
        border: 1px solid var(--border);
        background-color: var(--muted);
        color: var(--secondary-foreground);
    }
    #prijzen-pakketten .kt-badge-outline.kt-badge-success {
        border-color: var(--color-green-200);
        background-color: var(--color-green-50);
        color: var(--color-green-700);
    }
    #prijzen-pakketten .nexa-plan-badge {
        height: 1.625rem;
        min-width: 1.625rem;
        padding-inline: 0.6rem;
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1rem;
    }
    #prijzen-pakketten .p-5\! { padding: 1.25rem !important; }
    #prijzen-pakketten .pt-7\.5\! { padding-top: 1.875rem !important; }
    #prijzen-pakketten .px-5\! { padding-left: 1.25rem !important; padding-right: 1.25rem !important; }
    #prijzen-pakketten .py-3\.5\! { padding-top: 0.875rem !important; padding-bottom: 0.875rem !important; }
    #prijzen-pakketten .pb-2\! { padding-bottom: 0.5rem !important; }
    #prijzen-pakketten .pt-2\! { padding-top: 0.5rem !important; }
    #prijzen-pakketten .pb-5\! { padding-bottom: 1.25rem !important; }
    #prijzen-pakketten .py-0\! { padding-top: 0 !important; padding-bottom: 0 !important; }
    #prijzen-pakketten .nexa-price-regular {
        font-size: 1.5rem;
        font-weight: 600;
        line-height: 1;
        color: var(--mono);
    }
    #prijzen-pakketten .nexa-price-period {
        color: var(--muted-foreground);
        font-size: 0.75rem;
        line-height: 1;
    }
    #prijzen-pakketten .nexa-plan-price {
        height: 1px;
        vertical-align: stretch;
    }
    #prijzen-pakketten .nexa-plan-price__inner {
        height: 100%;
        display: flex;
        flex-direction: column;
        padding-top: 0.5rem;
        padding-bottom: 0.75rem;
        box-sizing: border-box;
    }
    #prijzen-pakketten .nexa-price-deal {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.45rem;
        width: 100%;
        min-width: 0;
        flex: 1 1 auto;
        height: 100%;
        padding: 0.65rem 0.75rem;
        border-radius: 0.65rem;
        background: color-mix(in srgb, #f97316 9%, transparent);
        border: 1px solid color-mix(in srgb, #f97316 22%, var(--border));
        box-sizing: border-box;
    }
    #prijzen-pakketten .nexa-price-block {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        gap: 0.3rem;
        min-width: 0;
        flex: 1 1 auto;
        height: 100%;
    }
    html.dark #prijzen-pakketten .nexa-price-deal {
        background: color-mix(in srgb, #f97316 16%, transparent);
        border-color: color-mix(in srgb, #fb923c 32%, var(--border));
    }
    #prijzen-pakketten .nexa-price-deal__tag {
        display: inline-flex;
        align-items: center;
        height: 1.15rem;
        padding: 0 0.4rem;
        border-radius: 999px;
        font-size: 0.625rem;
        font-weight: 650;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #ea580c;
        background: color-mix(in srgb, #f97316 16%, #fff);
    }
    html.dark #prijzen-pakketten .nexa-price-deal__tag {
        color: #fed7aa;
        background: color-mix(in srgb, #f97316 28%, transparent);
    }
    #prijzen-pakketten .nexa-price-offer {
        color: #ea580c;
        font-size: 1.2rem;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: -0.02em;
        max-width: 100%;
    }
    html.dark #prijzen-pakketten .nexa-price-offer {
        color: #fb923c;
    }
    #prijzen-pakketten .nexa-price-deal__after {
        color: var(--mono);
        font-size: 0.9375rem;
        font-weight: 600;
        line-height: 1.25;
    }
    #prijzen-pakketten .nexa-price-deal__was {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.2rem;
        margin-top: auto;
    }
    #prijzen-pakketten .nexa-price-deal__was-line {
        display: inline-flex;
        align-items: baseline;
        gap: 0.3rem;
    }
    #prijzen-pakketten .nexa-price-deal__was-label {
        color: var(--muted-foreground);
        font-size: 0.75rem;
    }
    #prijzen-pakketten .nexa-price-original {
        position: relative;
        display: inline-block;
        color: var(--muted-foreground);
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1.2;
    }
    #prijzen-pakketten .nexa-price-original--struck::after {
        content: '';
        position: absolute;
        left: -6%;
        right: -6%;
        top: 52%;
        border-top: 2px solid #dc2626;
        transform: rotate(-16deg);
        pointer-events: none;
    }
    html.dark #prijzen-pakketten .nexa-price-original--struck::after {
        border-top-color: #f87171;
    }
    #prijzen-pakketten .nexa-website-price .nexa-price-deal {
        min-width: 11rem;
        height: auto;
        flex: 0 0 auto;
        align-items: center;
        text-align: center;
    }
    #prijzen-pakketten .nexa-website-price .nexa-price-deal__prefix {
        color: var(--muted-foreground);
        font-size: 0.75rem;
        line-height: 1;
    }
    #prijzen-pakketten .nexa-website-price .nexa-price-deal__was {
        align-items: center;
    }
    .nexa-pricing-packages__scale {
        width: 100%;
        max-width: 100%;
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
    }
    @media (min-width: 768px) {
        .nexa-pricing-packages__scale {
            width: var(--nexa-pricing-scale, 100%);
        }
    }
    #prijzen-pakketten .nexa-pricing-block {
        --nexa-pricing-fs: 14px;
        font-size: var(--nexa-pricing-fs);
    }
    #prijzen-pakketten .nexa-pricing-block .text-xs,
    #prijzen-pakketten .nexa-pricing-block .nexa-price-period,
    #prijzen-pakketten .nexa-pricing-block .nexa-price-deal__was-label,
    #prijzen-pakketten .nexa-pricing-block .nexa-price-deal__prefix,
    #prijzen-pakketten .nexa-pricing-block .kt-badge-sm {
        font-size: 0.85em !important;
    }
    #prijzen-pakketten .nexa-pricing-block .text-sm,
    #prijzen-pakketten .nexa-pricing-block .text-secondary-foreground,
    #prijzen-pakketten .nexa-pricing-block .kt-btn {
        font-size: 1em !important;
    }
    #prijzen-pakketten .nexa-pricing-block .kt-badge,
    #prijzen-pakketten .nexa-pricing-block .nexa-plan-badge,
    #prijzen-pakketten .nexa-pricing-block .nexa-price-original,
    #prijzen-pakketten .nexa-pricing-block .nexa-price-deal__tag {
        font-size: 0.9em !important;
    }
    #prijzen-pakketten .nexa-pricing-block h3,
    #prijzen-pakketten .nexa-pricing-block h4,
    #prijzen-pakketten .nexa-pricing-block .text-lg,
    #prijzen-pakketten .nexa-pricing-block .nexa-price-deal__after {
        font-size: 1.2em !important;
    }
    #prijzen-pakketten .nexa-pricing-block .nexa-price-offer {
        font-size: 1.35em !important;
    }
    #prijzen-pakketten .nexa-pricing-block .nexa-price-regular,
    #prijzen-pakketten .nexa-pricing-block .text-2xl {
        font-size: 1.55em !important;
    }
    #prijzen-pakketten .nexa-pricing-website-copy {
        width: var(--nexa-pricing-website-width, 50%);
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }
    @media (max-width: 767px) {
        #prijzen-pakketten .nexa-pricing-website-copy {
            width: 100%;
        }
    }

    #prijzen-pakketten .nexa-pricing-reveal--block {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.65s ease, transform 0.65s ease;
        will-change: opacity, transform;
    }
    #prijzen-pakketten .nexa-pricing-reveal--block.is-in-view {
        opacity: 1;
        transform: none;
        will-change: auto;
    }
    #prijzen-pakketten .nexa-pricing-reveal--intro .nexa-pricing-reveal__item,
    #prijzen-pakketten .nexa-pricing-reveal--addons .nexa-pricing-reveal__card {
        opacity: 0;
        transform: translateY(16px);
        transition: opacity 0.55s ease, transform 0.55s ease;
        transition-delay: var(--nexa-pricing-reveal-delay, 0ms);
        will-change: opacity, transform;
    }
    #prijzen-pakketten .nexa-pricing-reveal--intro.is-in-view .nexa-pricing-reveal__item,
    #prijzen-pakketten .nexa-pricing-reveal--addons.is-in-view .nexa-pricing-reveal__card {
        opacity: 1;
        transform: none;
        will-change: auto;
    }
    #prijzen-pakketten .nexa-pricing-reveal--addons.is-in-view .nexa-pricing-reveal__card:nth-child(2) {
        transition-delay: 90ms;
    }
    #prijzen-pakketten .nexa-pricing-reveal--addons.is-in-view .nexa-pricing-reveal__card:nth-child(3) {
        transition-delay: 180ms;
    }
    @media (prefers-reduced-motion: reduce) {
        #prijzen-pakketten .nexa-pricing-reveal--block,
        #prijzen-pakketten .nexa-pricing-reveal--intro .nexa-pricing-reveal__item,
        #prijzen-pakketten .nexa-pricing-reveal--addons .nexa-pricing-reveal__card {
            opacity: 1;
            transform: none;
            transition: none;
            will-change: auto;
        }
    }
</style>
<section id="prijzen-pakketten" class="nexa-pricing-packages pt-6 md:pt-8 pb-10 md:pb-14">
    <div class="nexa-pricing-packages__scale" style="--nexa-pricing-scale: {{ $widthPercent }}%;">
    <div class="website-section-inner">
        @if($eyebrow !== '' || $title !== '' || $subtitle !== '')
        <div class="text-center mb-10 md:mb-12 max-w-3xl mx-auto nexa-pricing-reveal nexa-pricing-reveal--intro" data-scroll-reveal>
            @if($eyebrow !== '')
            <p class="text-3xl md:text-4xl font-bold text-blue-600 dark:text-blue-300 mb-6 nexa-pricing-reveal__item" style="--nexa-pricing-reveal-delay: 0ms;">{{ $eyebrow }}</p>
            @endif
            @if($title !== '')
            <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mb-3 nexa-pricing-reveal__item" style="--nexa-pricing-reveal-delay: 70ms;">{{ $title }}</h2>
            @endif
            @if($subtitle !== '')
            <p class="text-gray-600 dark:text-gray-300 nexa-pricing-reveal__item" style="--nexa-pricing-reveal-delay: 140ms;">{{ $subtitle }}</p>
            @endif
        </div>
        @endif

        @if($packages !== [])
        <div class="kt-scrollable-x-auto pt-3 -mt-3 nexa-pricing-block nexa-pricing-block--packages nexa-pricing-reveal nexa-pricing-reveal--block" data-scroll-reveal style="--nexa-pricing-fs: {{ $packagesFontPx }}px;">
            <table class="kt-table table-fixed border-separate border-spacing-0 w-full min-w-0 rounded-xl">
                <tr class="*:border-border">
                    <td class="nexa-plan-intro border-b-0 align-bottom p-5! pt-7.5!" rowspan="3">
                        <div class="text-mono text-sm leading-none font-medium pb-2">Wat zit erin</div>
                        <div class="text-secondary-foreground text-sm">excl. btw · 12 maanden</div>
                    </td>
                    @foreach($packages as $index => $package)
                        @php
                            $highlighted = filter_var($package['highlighted'] ?? false, FILTER_VALIDATE_BOOLEAN);
                            $isFirst = $index === 0;
                            $isLast = $index === $packageCount - 1;
                            $cellClass = 'border-t ltr:border-l rtl:border-s p-5! pt-7.5! pb-2! relative';
                            if ($highlighted) {
                                $cellClass .= ' bg-muted/40';
                            }
                            if ($isFirst) {
                                $cellClass .= ' ltr:rounded-tl-xl rtl:rounded-tr-xl';
                            }
                            if ($isLast) {
                                $cellClass .= ' ltr:rounded-tr-xl rtl:rounded-tl-xl border-e';
                            }
                        @endphp
                        <td class="{{ $cellClass }}">
                            @if($highlighted && ! empty($package['badge']))
                            <span class="absolute kt-badge nexa-plan-badge kt-badge-outline kt-badge-success top-0 start-1/2 rtl:translate-x-1/2 -translate-x-1/2 -translate-y-1/2">
                                {{ $package['badge'] }}
                            </span>
                            @endif
                            <h3 class="text-lg text-mono font-medium pb-2">{{ $package['name'] ?? '' }}</h3>
                            <div class="text-secondary-foreground text-sm">{{ $package['audience'] ?? '' }}</div>
                        </td>
                    @endforeach
                </tr>
                <tr class="*:border-border">
                    @foreach($packages as $index => $package)
                        @php
                            $highlighted = filter_var($package['highlighted'] ?? false, FILTER_VALIDATE_BOOLEAN);
                            $isLast = $index === $packageCount - 1;
                            $deal = $pricingService->packagePricePresentation($package);
                            $cellClass = 'nexa-plan-price ltr:border-l rtl:border-s px-5! py-0!';
                            if ($highlighted) {
                                $cellClass .= ' bg-muted/40';
                            }
                            if ($isLast) {
                                $cellClass .= ' border-e';
                            }
                        @endphp
                        <td class="{{ $cellClass }}">
                            <div class="nexa-plan-price__inner">
                                @if($deal['has_deal'])
                                <div class="nexa-price-deal">
                                    <span class="nexa-price-deal__tag">Aanbieding</span>
                                    <div class="nexa-price-offer">{{ $deal['hero'] }}</div>
                                    @if($deal['after_label'] !== '')
                                    <div class="nexa-price-deal__after">{{ $deal['after_label'] }}</div>
                                    @endif
                                    <div class="nexa-price-deal__was">
                                        @if($deal['was_label'] !== '')
                                        <span class="nexa-price-deal__was-line">
                                            <span class="nexa-price-deal__was-label">was</span>
                                            <span class="nexa-price-original nexa-price-original--struck">{{ $deal['was_label'] }}</span>
                                        </span>
                                        @endif
                                        @if($deal['period'] !== '')
                                        <span class="nexa-price-period">{{ $deal['period'] }}</span>
                                        @endif
                                    </div>
                                </div>
                                @else
                                <div class="nexa-price-block">
                                    <div class="nexa-price-regular">{{ $deal['hero'] }}</div>
                                    @if($deal['period'] !== '')
                                    <div class="nexa-price-period">{{ $deal['period'] }}</div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </td>
                    @endforeach
                </tr>
                <tr class="*:border-border">
                    @foreach($packages as $index => $package)
                        @php
                            $highlighted = filter_var($package['highlighted'] ?? false, FILTER_VALIDATE_BOOLEAN);
                            $ctaUrl = $pricingService->signupUrl($package);
                            $ctaText = $signupLabel($package);
                            $isLast = $index === $packageCount - 1;
                            $cellClass = 'ltr:border-l rtl:border-s px-5! pt-2! pb-5!';
                            if ($highlighted) {
                                $cellClass .= ' bg-muted/40';
                            }
                            if ($isLast) {
                                $cellClass .= ' border-e';
                            }
                        @endphp
                        <td class="{{ $cellClass }}">
                            <a href="{{ $ctaUrl }}" class="kt-btn {{ $highlighted ? 'kt-btn-primary' : 'kt-btn-outline' }} text-center flex justify-center w-full">{{ $ctaText }}</a>
                        </td>
                    @endforeach
                </tr>
                @foreach($comparisonRows as $rowIndex => $row)
                <tr class="*:border-border">
                    <td class="border-s border-b px-5! py-3.5! {{ $rowIndex === 0 ? 'border-t ltr:rounded-tl-xl rtl:rounded-tr-xl' : '' }} {{ $rowIndex === $lastRowIndex ? 'ltr:rounded-bl-xl rtl:rounded-br-xl' : '' }}">
                        <div class="text-mono text-sm leading-none font-medium">{{ $row['label'] }}</div>
                    </td>
                    @foreach($packages as $index => $package)
                        @php
                            $highlighted = filter_var($package['highlighted'] ?? false, FILTER_VALIDATE_BOOLEAN);
                            $included = (bool) ($row['included'][$index] ?? false);
                            $isLast = $index === $packageCount - 1;
                            $cellClass = 'nexa-plan-check border-b border-s px-5! py-3.5!';
                            if ($rowIndex === 0) {
                                $cellClass .= ' border-t';
                            }
                            if ($highlighted) {
                                $cellClass .= ' bg-muted/40';
                            }
                            if ($isLast) {
                                $cellClass .= ' border-e';
                            }
                            if ($rowIndex === $lastRowIndex && $isLast) {
                                $cellClass .= ' ltr:rounded-br-xl rtl:rounded-bl-xl';
                            }
                        @endphp
                        <td class="{{ $cellClass }}">
                            <span class="nexa-plan-mark">
                            @if($included)
                                <i class="ki-filled ki-check text-green-500 text-lg"></i>
                                <span class="sr-only">Inbegrepen</span>
                            @else
                                <span class="nexa-plan-absent" aria-hidden="true">–</span>
                                <span class="sr-only">Niet inbegrepen</span>
                            @endif
                            </span>
                        </td>
                    @endforeach
                </tr>
                @endforeach
            </table>
        </div>
        @endif

        @if(!empty($website['title']) || !empty($website['price_label']))
        @php
            $websiteFeatures = isset($website['features']) && is_array($website['features'])
                ? array_values(array_filter(array_map(static fn ($line) => trim((string) $line), $website['features']), fn ($line) => $line !== ''))
                : [];
            $websiteCta = trim((string) ($website['cta_text'] ?? 'Website bespreken'));
            $websiteUrl = trim((string) ($website['cta_url'] ?? '/contact'));
            $websiteDeal = $pricingService->websitePricePresentation($website);
            $websitePrefix = trim((string) ($website['price_prefix'] ?? 'vanaf'));
        @endphp
        <div class="mt-7.5 rounded-xl border border-border p-5 md:p-7.5 md:flex md:items-start md:justify-between md:gap-10 nexa-pricing-block nexa-pricing-block--website nexa-pricing-reveal nexa-pricing-reveal--block" data-scroll-reveal style="border-color: var(--border); background: var(--background); --nexa-pricing-fs: {{ $websiteFontPx }}px; --nexa-pricing-website-width: {{ $websiteWidthPct }}%;">
            <div class="nexa-pricing-website-copy">
                <div class="kt-badge nexa-plan-badge kt-badge-outline mb-3">Eenmalig</div>
                <h3 class="text-lg text-mono font-medium pb-2">{{ $website['title'] ?? 'Website live zetten' }}</h3>
                @if(!empty($website['subtitle']))
                <div class="text-secondary-foreground text-sm mb-4">{{ $website['subtitle'] }}</div>
                @endif
                @if($websiteFeatures !== [])
                <ul class="space-y-2 text-sm text-foreground">
                    @foreach($websiteFeatures as $feature)
                        <li class="flex items-start gap-2"><i class="ki-filled ki-check text-green-500 text-lg"></i><span>{{ $feature }}</span></li>
                    @endforeach
                </ul>
                @endif
            </div>
            <div class="mt-6 md:mt-0 shrink-0 nexa-website-price {{ $websiteDeal['has_deal'] ? 'text-center' : 'text-left md:text-right' }}">
                @if($websiteDeal['has_deal'])
                <div class="nexa-price-deal nexa-price-deal--website">
                    <span class="nexa-price-deal__tag">Aanbieding</span>
                    @if($websitePrefix !== '')
                    <div class="nexa-price-deal__prefix">{{ $websitePrefix }}</div>
                    @endif
                    <div class="nexa-price-offer">{{ $websiteDeal['hero'] }}</div>
                    <div class="nexa-price-deal__was">
                        @if($websiteDeal['was_label'] !== '')
                        <span class="nexa-price-deal__was-line">
                            <span class="nexa-price-deal__was-label">was</span>
                            <span class="nexa-price-original nexa-price-original--struck">{{ $websiteDeal['was_label'] }}</span>
                        </span>
                        @endif
                        @if($websiteDeal['period'] !== '')
                        <span class="nexa-price-period">{{ $websiteDeal['period'] }}</span>
                        @endif
                    </div>
                </div>
                @else
                @if($websitePrefix !== '')
                <div class="text-secondary-foreground text-sm mb-1">{{ $websitePrefix }}</div>
                @endif
                <div class="text-2xl text-mono font-semibold leading-none">{{ $websiteDeal['price_label'] !== '' ? $websiteDeal['price_label'] : $pricingService->displayAmount((string) ($website['price_label'] ?? '750')) }}</div>
                @if($websiteDeal['period'] !== '')
                <div class="text-secondary-foreground text-xs mt-1">{{ $websiteDeal['period'] }}</div>
                @endif
                @endif
                @if($websiteCta !== '')
                <a href="{{ $websiteUrl !== '' ? $websiteUrl : '/contact' }}" class="kt-btn kt-btn-primary mt-4">{{ $websiteCta }}</a>
                @endif
            </div>
        </div>
        @endif

        @if($addons !== [])
        <div class="mt-7.5 grid grid-cols-1 md:grid-cols-3 gap-5 nexa-pricing-block nexa-pricing-block--addons nexa-pricing-reveal nexa-pricing-reveal--addons" data-scroll-reveal style="--nexa-pricing-fs: {{ $addonsFontPx }}px;">
            @foreach($addons as $addon)
            <div class="rounded-xl border p-5 nexa-pricing-reveal__card" style="border-color: var(--border); background: var(--background);">
                <h4 class="text-mono font-medium">{{ $addon['name'] ?? '' }}</h4>
                <div class="text-sm font-medium text-primary mt-1" style="color: var(--primary);">{{ $addon['price'] ?? '' }}</div>
                @if(!empty($addon['description']))
                <div class="text-secondary-foreground text-sm mt-2">{{ $addon['description'] }}</div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        <div class="mt-7.5 rounded-xl border p-5 md:p-7.5 flex flex-col md:flex-row md:items-center md:justify-between gap-5 nexa-pricing-reveal nexa-pricing-reveal--block" data-scroll-reveal style="border-color: var(--border); background: var(--muted);">
            <div>
                <h3 class="text-lg text-mono font-medium mb-1">Klaar om te starten?</h3>
                <div class="text-secondary-foreground text-sm">Meld je aan. We nemen contact op over onboarding of een voorstel op maat.</div>
            </div>
            <div class="flex flex-wrap gap-2.5 shrink-0">
                <a href="{{ url('/contact') }}" class="kt-btn kt-btn-primary">Aanmelden</a>
                @unless($isPrijzenPage)
                <a href="{{ url('/prijzen') }}" class="kt-btn kt-btn-outline">Alle prijzen</a>
                @endunless
            </div>
        </div>

        @if($note !== '')
        <p class="text-center text-xs text-secondary-foreground mt-8 nexa-pricing-reveal nexa-pricing-reveal--block" data-scroll-reveal>{{ $note }}</p>
        @endif
    </div>
    </div>
</section>
<noscript>
    <style>
        #prijzen-pakketten .nexa-pricing-reveal--block,
        #prijzen-pakketten .nexa-pricing-reveal--intro .nexa-pricing-reveal__item,
        #prijzen-pakketten .nexa-pricing-reveal--addons .nexa-pricing-reveal__card {
            opacity: 1 !important;
            transform: none !important;
        }
    </style>
</noscript>
@once
@push('scripts')
<script>
(function () {
    var root = document.getElementById('prijzen-pakketten');
    if (!root || root.getAttribute('data-nexa-pricing-reveal-init') === '1') return;
    root.setAttribute('data-nexa-pricing-reveal-init', '1');
    var blocks = root.querySelectorAll('.nexa-pricing-reveal');
    if (!blocks.length) return;
    function show(el) {
        el.classList.add('is-in-view');
    }
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        Array.prototype.forEach.call(blocks, show);
        return;
    }
    function isVisibleEnough(el) {
        var rect = el.getBoundingClientRect();
        var vh = window.innerHeight || document.documentElement.clientHeight || 0;
        return rect.bottom > 40 && rect.top < vh * 0.88;
    }
    function revealVisible() {
        Array.prototype.forEach.call(blocks, function (el) {
            if (!el.classList.contains('is-in-view') && isVisibleEnough(el)) {
                show(el);
            }
        });
    }
    var opts = { threshold: 0.12, rootMargin: '0px 0px -12% 0px', once: true };
    function start() {
        if (typeof window.nexaObserveWhenVisible === 'function') {
            window.nexaObserveWhenVisible(blocks, show, opts);
        } else if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        show(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, opts);
            Array.prototype.forEach.call(blocks, function (el) { observer.observe(el); });
        }
        revealVisible();
        window.addEventListener('scroll', revealVisible, { passive: true });
        window.addEventListener('resize', revealVisible);
    }
    requestAnimationFrame(function () {
        requestAnimationFrame(start);
    });
})();
</script>
@endpush
@endonce
