@php
    $homeSections = $homeSections ?? \App\Models\WebsitePage::defaultHomeSections();
    $emailTemplateBySectionKey = $emailTemplateBySectionKey ?? [];
    $visibility = $homeSections['visibility'] ?? [];
    $isNexaOrSkillmatching = !isset($page) || $page->module_name === null || strtolower((string)$page->module_name) === 'skillmatching';
    $defaultSectionOrder = ['hero', 'why_nexa', 'features', 'stats', 'cta'];
    $sectionOrder = $homeSections['section_order'] ?? $defaultSectionOrder;
    if (!is_array($sectionOrder)) {
        $sectionOrder = $defaultSectionOrder;
    }
    $sectionOrder = array_values($sectionOrder);
    // Alleen opgeslagen section_order tonen; geen ontbrekende default-secties terugzetten (verwijderde secties blijven weg).
    $url = function($u) {
        if (empty($u)) return url('/');
        $u = trim($u);
        return (strpos($u, 'http') === 0 || strpos($u, '//') === 0) ? $u : url($u);
    };
    $baseType = function($key) {
        $types = ['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken', 'featured_services', 'email_template', 'text_block'];
        if (in_array($key, $types, true)) return $key;
        $base = preg_replace('/_\d+$/', '', $key);
        return in_array($base, $types, true) ? $base : null;
    };
@endphp
{{-- Metronic thema home: secties in volgorde van section_order (bewerkbaar via Admin > Website Pagina's > Home). Dynamische keys (hero_2, features_2) en component:module.key ondersteund. --}}
@foreach($sectionOrder as $sectionKey)
    @php
        $componentService = app(\App\Services\FrontendComponentService::class);
        $isComponent = $componentService::isComponentKey($sectionKey);
        $component = $isComponent ? $componentService->getById($componentService::componentIdFromKey($sectionKey)) : null;
    @endphp
    @if($isComponent && (($component && view()->exists($component->view ?? '')) || $sectionKey === 'component:nexa.recente_vacatures' || $sectionKey === 'component:taxi.tarieven' || $sectionKey === 'component:taxi.boekingsmodule' || $sectionKey === 'component:website.google_reviews' || $sectionKey === 'component:nexa.google_reviews' || $sectionKey === 'component:website.nexa_modules_overview'))
        @if($visibility[$sectionKey] ?? true)
        @if($sectionKey === 'component:nexa.recente_vacatures' && $isNexaOrSkillmatching && view()->exists('frontend.website.components.recente-vacatures'))
            @include('frontend.website.components.recente-vacatures', ['jobs' => $jobs ?? collect()])
        @elseif($sectionKey === 'component:taxi.tarieven' && view()->exists('frontend.website.components.nexataxi-tarieven'))
            @include('frontend.website.components.nexataxi-tarieven', ['homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey, 'websitePageCompanyId' => isset($page) && $page->company_id ? (int) $page->company_id : null])
        @elseif($sectionKey === 'component:taxi.boekingsmodule' && view()->exists('frontend.website.components.nexataxi-boekingsmodule'))
            @include('frontend.website.components.nexataxi-boekingsmodule', [
                'homeSections' => $homeSections ?? [],
                'sectionKey' => $sectionKey,
                'page' => $page ?? null,
                'googleMapsApiKey' => $googleMapsApiKey ?? '',
                'websitePageCompanyId' => isset($page) && $page->company_id ? (int) $page->company_id : null,
            ])
        @elseif(($sectionKey === 'component:website.google_reviews' || $sectionKey === 'component:nexa.google_reviews') && view()->exists('frontend.website.components.google-reviews'))
            @include('frontend.website.components.google-reviews', ['reviews' => $googleReviews ?? [], 'googleReviews' => $googleReviews ?? []])
        @elseif($sectionKey === 'component:website.nexa_modules_overview' && view()->exists('frontend.website.components.nexa-modules-overview'))
            @include('frontend.website.components.nexa-modules-overview', ['homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey])
        @elseif($component && view()->exists($component->view ?? ''))
            @include($component->view, ['jobs' => $jobs ?? collect(), 'homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey])
        @endif
        @endif
    @else
    @php
        $base = $baseType($sectionKey);
        if ($base === null) continue;
        $sectionData = $homeSections[$sectionKey] ?? [];
        $v = function($suffix) use ($visibility, $sectionKey) { return $visibility[$sectionKey . $suffix] ?? true; };
    @endphp
    @if($base === 'hero' && $v(''))
@php
    $heroBgUrl = !empty($sectionData['background_image_url']) ? app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($sectionData['background_image_url']) : '';
    $heroBgStyle = $heroBgUrl !== '' ? 'background-image: url(' . e($heroBgUrl) . ');' : '';
    $overlayFrom = $sectionData['overlay_color_from'] ?? '#1e3a8a';
    $overlayTo = $sectionData['overlay_color_to'] ?? '#312e81';
    $overlayOpacity = max(0, min(100, (int) ($sectionData['overlay_opacity'] ?? 85)));
    $overlayAlpha = $overlayOpacity / 100;
    $hexToRgb = function($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        if (strlen($hex) !== 6) return [30, 58, 138];
        return [ hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2)) ];
    };
    $fromRgb = $hexToRgb($overlayFrom);
    $toRgb = $hexToRgb($overlayTo);
    $heroOverlayStyle = 'background-image: linear-gradient(to right, rgba('.$fromRgb[0].','.$fromRgb[1].','.$fromRgb[2].','.$overlayAlpha.'), rgba('.$toRgb[0].','.$toRgb[1].','.$toRgb[2].','.$overlayAlpha.'));';
@endphp
<!-- Hero -->
<section class="modern-home-hero py-16 md:py-24 relative overflow-hidden scroll-reveal-section {{ $heroBgUrl === '' ? 'bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900' : '' }}" data-scroll-reveal>
    @if($heroBgUrl !== '')
    <div class="absolute inset-0 z-0 bg-cover bg-center bg-no-repeat" style="{{ $heroBgStyle }}" aria-hidden="true"></div>
    <div class="absolute inset-0 z-[1] bg-cover bg-center bg-no-repeat" style="{{ $heroOverlayStyle }}" aria-hidden="true"></div>
    @endif
    @if(!empty($sectionData['overlay']))
    <div class="absolute inset-0 z-[2] bg-black/10 dark:bg-black/20" aria-hidden="true"></div>
    @endif
    <div class="website-section-inner relative z-10">
        @php
            $heroRevealDur = '0.7s';
            $heroRevealEase = 'cubic-bezier(0.25, 0.46, 0.45, 0.94)';
            $heroRevealStyle = function ($delayMs) use ($heroRevealDur, $heroRevealEase) {
                return 'transition: opacity ' . $heroRevealDur . ' ' . $heroRevealEase . ', transform ' . $heroRevealDur . ' ' . $heroRevealEase . '; transition-delay: ' . (int) $delayMs . 'ms;';
            };
        @endphp
        <div class="w-full text-center">
            @if($v('_title'))
            @php
                $heroTitle = $sectionData['title'] ?? 'Vind je droombaan met AI';
                $heroHighlight = $sectionData['title_highlight'] ?? 'droombaan';
                $heroHighlightColor = trim((string) ($sectionData['title_highlight_color'] ?? ''));
                $heroHighlightColor = preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $heroHighlightColor) ? $heroHighlightColor : '';
                $heroTitleParts = $heroHighlight !== '' ? explode($heroHighlight, $heroTitle, 2) : [$heroTitle];
                $heroTitleRightDelayMs = 500;
            @endphp
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
                @if(count($heroTitleParts) === 2)
                    <span class="scroll-reveal-item hero-reveal-title-left inline-block" style="{{ $heroRevealStyle(0) }}">{{ trim($heroTitleParts[0]) }}</span><span class="inline-block">&nbsp;</span><span class="scroll-reveal-item hero-reveal-title-right inline-block" style="{{ $heroRevealStyle($heroTitleRightDelayMs) }}"><span @class(['text-blue-200 dark:text-blue-300' => $heroHighlightColor === '']) @if($heroHighlightColor !== '') style="color: {{ $heroHighlightColor }};" @endif>{{ $heroHighlight }}</span>{{ trim($heroTitleParts[1]) !== '' ? ' ' . trim($heroTitleParts[1]) : '' }}</span>
                @else
                    @php
                        $heroTitleSplit = preg_split('/\s+/', trim($heroTitle), 2, PREG_SPLIT_NO_EMPTY);
                        $heroTitleLeft = $heroTitleSplit[0] ?? '';
                        $heroTitleRest = $heroTitleSplit[1] ?? '';
                    @endphp
                    @if($heroTitleRest !== '')
                        <span class="scroll-reveal-item hero-reveal-title-left inline-block" style="{{ $heroRevealStyle(0) }}">{{ $heroTitleLeft }}</span><span class="inline-block">&nbsp;</span><span class="scroll-reveal-item hero-reveal-title-right inline-block" style="{{ $heroRevealStyle($heroTitleRightDelayMs) }}">{{ $heroTitleRest }}</span>
                    @else
                        <span class="scroll-reveal-item hero-reveal-title-left inline-block" style="{{ $heroRevealStyle(0) }}">{{ $heroTitle }}</span>
                    @endif
                @endif
            </h1>
            @endif
            @if($v('_subtitle'))
            @php
                $heroSubtitleColor = trim((string) ($sectionData['subtitle_color'] ?? ''));
                $heroSubtitleColorStyle = ($heroSubtitleColor !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $heroSubtitleColor)) ? 'color: ' . $heroSubtitleColor . ';' : '';
            @endphp
            <div class="scroll-reveal-item hero-reveal-zoom text-xl mb-8 w-full leading-relaxed max-w-3xl mx-auto prose prose-invert prose-p:my-2 prose-ul:my-2 prose-ol:my-2 max-w-none {{ $heroSubtitleColorStyle === '' ? 'text-blue-100 dark:text-blue-200' : '' }}" style="{{ $heroRevealStyle(320) }}{{ $heroSubtitleColorStyle }}">
                {!! $sectionData['subtitle'] ?? 'Ons geavanceerde AI-platform matcht jouw vaardigheden met de perfecte vacatures van topbedrijven. Start vandaag nog je carrière.' !!}
            </div>
            @endif
            @if($v('_cta'))
            @php
                $heroPrimaryStyle = '';
                if (!empty($sectionData['cta_primary_bg']) || !empty($sectionData['cta_primary_border']) || !empty($sectionData['cta_primary_text_color'])) {
                    $heroPrimaryStyle = 'background-color:' . ($sectionData['cta_primary_bg'] ?? 'inherit') . ';border: 2px solid ' . ($sectionData['cta_primary_border'] ?? 'transparent') . ';color:' . ($sectionData['cta_primary_text_color'] ?? 'inherit') . ';';
                }
                $heroSecondaryStyle = '';
                if (!empty($sectionData['cta_secondary_bg']) || !empty($sectionData['cta_secondary_border']) || !empty($sectionData['cta_secondary_text_color'])) {
                    $heroSecondaryStyle = 'background-color:' . ($sectionData['cta_secondary_bg'] ?? 'transparent') . ';border: 2px solid ' . ($sectionData['cta_secondary_border'] ?? 'currentColor') . ';color:' . ($sectionData['cta_secondary_text_color'] ?? 'inherit') . ';';
                }
            @endphp
            @php
                $heroBtnPrimaryDelayMs = 400;
                $heroBtnSecondaryDelayMs = 560;
                $heroBtnPrimaryFullStyle = trim($heroRevealStyle($heroBtnPrimaryDelayMs) . ' ' . $heroPrimaryStyle);
                $heroBtnSecondaryFullStyle = trim($heroRevealStyle($heroBtnSecondaryDelayMs) . ' ' . $heroSecondaryStyle);
            @endphp
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ $url($sectionData['cta_primary_url'] ?? '/register') }}" class="scroll-reveal-item hero-reveal-btn hero-reveal-btn-primary inline-flex items-center justify-center px-8 py-4 rounded-lg font-semibold bg-white text-blue-600 hover:bg-blue-50 dark:bg-blue-600 dark:text-white dark:hover:bg-blue-700 transition-[transform,opacity,box-shadow,background-color,border-color,color] duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5" @if($heroBtnPrimaryFullStyle !== '') style="{{ $heroBtnPrimaryFullStyle }}" @endif>
                    {{ $sectionData['cta_primary_text'] ?? 'Gratis account aanmaken' }}
                </a>
                <a href="{{ $url($sectionData['cta_secondary_url'] ?? '/jobs') }}" class="scroll-reveal-item hero-reveal-btn hero-reveal-btn-secondary inline-flex items-center justify-center px-8 py-4 bg-transparent hover:bg-white text-white hover:text-blue-600 dark:hover:text-blue-700 font-semibold rounded-lg border-2 border-white hover:border-white shadow-lg hover:shadow-xl transition-[transform,opacity,box-shadow,background-color,border-color,color] duration-200 hover:-translate-y-0.5" @if($heroBtnSecondaryFullStyle !== '') style="{{ $heroBtnSecondaryFullStyle }}" @endif>
                    {{ $sectionData['cta_secondary_text'] ?? 'Vacatures bekijken' }}
                </a>
            </div>
            @endif
        </div>
    </div>
</section>
    @endif

    @if($base === 'stats' && $v(''))
        @include('frontend.website.blocks.stats', ['sectionData' => $sectionData, 'visibility' => $visibility, 'sectionKey' => $sectionKey])
    @endif

    @if($base === 'why_nexa' && $v(''))
<!-- Waarom Nexa -->
<section class="modern-home-waarom py-16 md:py-20 bg-white dark:bg-gray-900">
    <div class="website-section-inner">
        <div class="max-w-5xl mx-auto text-center">
            @if($v('_title'))
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                {{ $sectionData['title'] ?? 'Waarom kiezen voor Nexa?' }}
            </h2>
            @endif
            @if($v('_subtitle'))
            @php
                $whySubtitleColor = trim((string) ($sectionData['subtitle_color'] ?? ''));
                $whySubtitleColorStyle = ($whySubtitleColor !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $whySubtitleColor)) ? 'color: ' . $whySubtitleColor . ';' : '';
            @endphp
            <div class="text-xl leading-relaxed prose prose-gray dark:prose-invert prose-p:my-2 prose-ul:my-2 prose-ol:my-2 max-w-none mx-auto {{ $whySubtitleColorStyle === '' ? 'text-gray-600 dark:text-gray-300' : '' }}" @if($whySubtitleColorStyle !== '') style="{{ $whySubtitleColorStyle }}" @endif>
                {!! $sectionData['subtitle'] ?? 'Onze geavanceerde AI-technologie maakt het vinden van de perfecte baan eenvoudiger dan ooit.' !!}
            </div>
            @endif
        </div>
    </div>
</section>
    @endif

    @if($base === 'features' && $v(''))
@php
    $featuresRevealDuration = '0.6s';
    $featuresRevealDelayStepMs = 200;
    $featuresTitleDelayMs = 0;
    $featuresFirstCardDelayMs = 100;
    $featuresEasing = 'cubic-bezier(0.25, 0.46, 0.45, 0.94)';
@endphp
<!-- Wat Wij Bieden -->
<section class="modern-home-features py-16 md:py-20 bg-white dark:bg-gray-900 scroll-reveal-section" data-scroll-reveal>
    <div class="website-section-inner">
        <div class="max-w-5xl mx-auto">
            @if($visibility[$sectionKey . '_section_title'] ?? $visibility['features_section_title'] ?? true)
            <div class="scroll-reveal-item text-center mb-8" style="transition: opacity {{ $featuresRevealDuration }} {{ $featuresEasing }}, transform {{ $featuresRevealDuration }} {{ $featuresEasing }}; transition-delay: {{ $featuresTitleDelayMs }}ms;">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">
                    {{ $sectionData['section_title'] ?? 'Wat Wij Bieden' }}
                </h2>
            </div>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach(($sectionData['items'] ?? []) as $fi => $item)
                @if($visibility[$sectionKey . '_item_' . $fi] ?? $visibility['features_item_'.$fi] ?? true)
                @php
                    $cardRevealDelayMs = $featuresFirstCardDelayMs + $fi * $featuresRevealDelayStepMs;
                    $cardRevealStyle = 'transition: opacity ' . $featuresRevealDuration . ' ' . $featuresEasing . ', transform ' . $featuresRevealDuration . ' ' . $featuresEasing . '; transition-delay: ' . $cardRevealDelayMs . 'ms;';
                @endphp
                <div class="scroll-reveal-item h-full" style="{{ $cardRevealStyle }}">
                    @include('frontend.website.components.features-card', ['item' => $item, 'index' => $fi, 'class' => 'h-full'])
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>
</section>
@push('styles')
<style>
    /* Zelfde invliegen als Elementor Overige Diensten: van beneden, lichte scale */
    .modern-home-features.scroll-reveal-section .scroll-reveal-item {
        opacity: 0;
        transform: translateY(48px) scale(0.98);
        transform-origin: center center;
        will-change: opacity, transform;
    }
    .modern-home-features.scroll-reveal-section.is-in-view .scroll-reveal-item {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
</style>
@endpush
    @endif

    @if($base === 'cards_ronde_hoeken' && $v(''))
        @include('frontend.website.partials.cards-ronde-hoeken', ['items' => $sectionData['items'] ?? [], 'visibility' => $visibility, 'sectionKey' => $sectionKey, 'cards_per_row' => $sectionData['cards_per_row'] ?? 4])
    @endif
    @if($base === 'featured_services' && $v(''))
        @include('frontend.website.blocks.featured_services', ['block' => ['data' => $sectionData]])
    @endif

    @if($base === 'carousel' && $v(''))
        @include('frontend.website.partials.carousel', ['items' => $sectionData['items'] ?? [], 'carouselId' => 'modern-home-' . preg_replace('/[^a-z0-9_-]/i', '', $sectionKey), 'intervalSeconds' => (int) ($sectionData['interval_seconds'] ?? 5), 'maxHeightPercent' => (int) ($sectionData['max_height_percent'] ?? 0)])
    @endif

    @if($base === 'email_template' && $v(''))
        @php
            $emailTemplateForSection = $emailTemplateBySectionKey[$sectionKey] ?? null;
            $sectionFormFields = $emailTemplateForSection ? $emailTemplateForSection->getOrderedFormFields() : collect();
            if ($sectionFormFields->isEmpty()) {
                $sectionFormFields = $infoRequestFormFields ?? collect();
            }
        @endphp
        @if($emailTemplateForSection)
            @include('frontend.website.components.email-template-section', ['sectionData' => $sectionData, 'sectionKey' => $sectionKey, 'emailTemplate' => $emailTemplateForSection, 'formFields' => $sectionFormFields])
        @endif
    @endif

    @if($base === 'text_block' && $v(''))
        @include('frontend.website.components.text-block-section', ['sectionData' => $sectionData, 'sectionKey' => $sectionKey, 'homeSections' => $homeSections, 'emailTemplateBySectionKey' => $emailTemplateBySectionKey])
    @endif

    @if($base === 'cta' && $v(''))
@php
    $ctaBgUrl = !empty($sectionData['background_image_url']) ? app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($sectionData['background_image_url']) : '';
    $ctaBgStyle = $ctaBgUrl !== '' ? 'background-image: url(' . e($ctaBgUrl) . ');' : '';
@endphp
<!-- CTA -->
<section class="modern-home-cta py-16 relative overflow-hidden scroll-reveal-section {{ $ctaBgUrl === '' ? 'bg-gray-100 dark:bg-gray-900' : '' }}" data-scroll-reveal>
    @if($ctaBgUrl !== '')
    <div class="absolute inset-0 z-0 bg-cover bg-center bg-no-repeat" style="{{ $ctaBgStyle }}" aria-hidden="true"></div>
    <div class="absolute inset-0 z-[1] bg-gray-900/70 dark:bg-gray-900/80" aria-hidden="true"></div>
    @endif
    @php
        $ctaRevealDurTitle = '0.78s';
        $ctaRevealDurFast = '0.39s';
        $ctaRevealDurFastMs = 390;
        $ctaRevealDurBtn = '0.72s';
        $ctaRevealEase = 'cubic-bezier(0.2, 0.85, 0.25, 1)';
        $ctaSubtitleDelayMs = 360;
        $ctaBtnPrimaryDelayMs = $ctaSubtitleDelayMs + $ctaRevealDurFastMs + 50;
        $ctaBtnSecondaryDelayMs = $ctaBtnPrimaryDelayMs + 100;
        $ctaRevealStyleRiseTitle = function ($delayMs) use ($ctaRevealDurTitle, $ctaRevealEase) {
            return 'transition: opacity ' . $ctaRevealDurTitle . ' ' . $ctaRevealEase . ', transform ' . $ctaRevealDurTitle . ' ' . $ctaRevealEase . '; transition-delay: ' . (int) $delayMs . 'ms;';
        };
        $ctaRevealStyleRiseFast = function ($delayMs) use ($ctaRevealDurFast, $ctaRevealEase) {
            return 'transition: opacity ' . $ctaRevealDurFast . ' ' . $ctaRevealEase . ', transform ' . $ctaRevealDurFast . ' ' . $ctaRevealEase . '; transition-delay: ' . (int) $delayMs . 'ms;';
        };
        $ctaRevealStyleBtn = function ($delayMs) use ($ctaRevealDurBtn, $ctaRevealEase) {
            return 'transition: opacity ' . $ctaRevealDurBtn . ' ' . $ctaRevealEase . ', transform ' . $ctaRevealDurBtn . ' ' . $ctaRevealEase . '; transition-delay: ' . (int) $delayMs . 'ms;';
        };
    @endphp
    <div class="website-section-inner relative z-10 text-center {{ $ctaBgUrl !== '' ? 'text-white' : '' }}">
        @if($v('_title'))
        <h2 class="scroll-reveal-item cta-reveal-rise text-3xl md:text-4xl font-bold {{ $ctaBgUrl !== '' ? 'text-white' : 'text-gray-900 dark:text-white' }} mb-4" style="{{ $ctaRevealStyleRiseTitle(0) }}">{{ $sectionData['title'] ?? 'Klaar om je carrière te starten?' }}</h2>
        @endif
        @if($v('_subtitle'))
        @php
            $ctaSubtitleColor = trim((string) ($sectionData['subtitle_color'] ?? ''));
            $ctaSubtitleColorStyle = ($ctaSubtitleColor !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $ctaSubtitleColor)) ? 'color: ' . $ctaSubtitleColor . ';' : '';
            $ctaSubtitleThemeClasses = $ctaSubtitleColorStyle === '' ? ($ctaBgUrl !== '' ? 'text-blue-100' : 'text-gray-600 dark:text-gray-300') : '';
        @endphp
        <div class="scroll-reveal-item cta-reveal-rise text-lg {{ $ctaSubtitleThemeClasses }} mb-8 prose {{ $ctaBgUrl !== '' ? 'prose-invert prose-p:my-2 prose-ul:my-2 prose-ol:my-2' : 'prose-gray dark:prose-invert prose-p:my-2 prose-ul:my-2 prose-ol:my-2' }} max-w-none" style="{{ $ctaRevealStyleRiseFast($ctaSubtitleDelayMs) }}{{ $ctaSubtitleColorStyle }}">{!! $sectionData['subtitle'] ?? 'Sluit je aan bij duizenden professionals die hun droombaan hebben gevonden.' !!}</div>
        @endif
        @if($v('_buttons'))
        @php
            $ctaPrimaryStyle = !empty($sectionData['cta_primary_bg']) ? 'background-color:' . $sectionData['cta_primary_bg'] . ';' : 'background-color: var(--theme-primary);';
            $ctaPrimaryStyle .= !empty($sectionData['cta_primary_text_color']) ? 'color:' . $sectionData['cta_primary_text_color'] . ';' : 'color: #fff;';
            if (!empty($sectionData['cta_primary_border'])) { $ctaPrimaryStyle .= 'border: 2px solid ' . $sectionData['cta_primary_border'] . ';'; }
            $ctaSecondaryStyle = '';
            if (!empty($sectionData['cta_secondary_bg'])) { $ctaSecondaryStyle .= 'background-color:' . $sectionData['cta_secondary_bg'] . ';'; }
            if (!empty($sectionData['cta_secondary_border'])) { $ctaSecondaryStyle .= 'border: 2px solid ' . $sectionData['cta_secondary_border'] . ';'; }
            if (!empty($sectionData['cta_secondary_text_color'])) { $ctaSecondaryStyle .= 'color:' . $sectionData['cta_secondary_text_color'] . ';'; }
            $ctaBtnPrimaryFullStyle = trim($ctaRevealStyleBtn($ctaBtnPrimaryDelayMs) . ' ' . $ctaPrimaryStyle);
            $ctaBtnSecondaryFullStyle = trim($ctaRevealStyleBtn($ctaBtnSecondaryDelayMs) . ' ' . $ctaSecondaryStyle);
        @endphp
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ $url($sectionData['cta_primary_url'] ?? '/register') }}" class="scroll-reveal-item cta-reveal-btn cta-reveal-btn-left inline-flex items-center justify-center px-8 py-4 rounded-lg font-semibold text-white transition-[transform,opacity,box-shadow,background-color,border-color,color] duration-200 hover:opacity-90 hover:shadow-lg hover:-translate-y-0.5" @if($ctaBtnPrimaryFullStyle !== '') style="{{ $ctaBtnPrimaryFullStyle }}" @endif>{{ $sectionData['cta_primary_text'] ?? 'Gratis account aanmaken' }}</a>
            <a href="{{ $url($sectionData['cta_secondary_url'] ?? '/jobs') }}" class="scroll-reveal-item cta-reveal-btn cta-reveal-btn-right inline-flex items-center justify-center px-8 py-4 rounded-lg font-semibold border-2 bg-white border-gray-800 text-gray-900 hover:bg-gray-800 hover:text-white hover:border-gray-800 dark:bg-gray-700 dark:border-gray-300 dark:text-white dark:hover:bg-gray-100 dark:hover:text-gray-900 dark:hover:border-gray-100 hover:shadow-lg hover:-translate-y-0.5 transition-[transform,opacity,box-shadow,background-color,border-color,color] duration-200 focus-visible:outline focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-gray-900 dark:focus-visible:ring-gray-100" @if($ctaBtnSecondaryFullStyle !== '') style="{{ $ctaBtnSecondaryFullStyle }}" @endif>{{ $sectionData['cta_secondary_text'] ?? 'Vacatures bekijken' }}</a>
        </div>
        @endif
    </div>
</section>
    @endif
    @endif
@endforeach
@push('styles')
<style>
    /* Hero titel: helft van links, helft van rechts (+500ms via inline delay) */
    .modern-home-hero.scroll-reveal-section .scroll-reveal-item.hero-reveal-title-left {
        opacity: 0;
        transform: translateX(-56px);
        will-change: opacity, transform;
    }
    .modern-home-hero.scroll-reveal-section .scroll-reveal-item.hero-reveal-title-right {
        opacity: 0;
        transform: translateX(56px);
        will-change: opacity, transform;
    }
    .modern-home-hero.scroll-reveal-section.is-in-view .scroll-reveal-item.hero-reveal-title-left,
    .modern-home-hero.scroll-reveal-section.is-in-view .scroll-reveal-item.hero-reveal-title-right {
        opacity: 1;
        transform: translateX(0);
    }
    /* Hero subtitel: zoom omhoog (ongewijzigd patroon) */
    .modern-home-hero.scroll-reveal-section .scroll-reveal-item.hero-reveal-zoom {
        opacity: 0;
        transform: translateY(40px) scale(0.94);
        transform-origin: center center;
        will-change: opacity, transform;
    }
    .modern-home-hero.scroll-reveal-section.is-in-view .scroll-reveal-item.hero-reveal-zoom {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    /* Hero knoppen: eerst linksonder, dan rechtsonder, fade omhoog */
    .modern-home-hero.scroll-reveal-section .scroll-reveal-item.hero-reveal-btn-primary {
        opacity: 0;
        transform: translateY(52px) translateX(-36px);
        will-change: opacity, transform;
    }
    .modern-home-hero.scroll-reveal-section .scroll-reveal-item.hero-reveal-btn-secondary {
        opacity: 0;
        transform: translateY(52px) translateX(36px);
        will-change: opacity, transform;
    }
    .modern-home-hero.scroll-reveal-section.is-in-view .scroll-reveal-item.hero-reveal-btn-primary,
    .modern-home-hero.scroll-reveal-section.is-in-view .scroll-reveal-item.hero-reveal-btn-secondary {
        opacity: 1;
        transform: translate(0, 0);
    }
    /* CTA titel + subtitel: van onder omhoog faden (subtitel ~360ms na titel; knoppen kort na subtitel-einde; subtitel 0.39s, knoppen langzamer 0.72s) */
    .modern-home-cta.scroll-reveal-section .scroll-reveal-item.cta-reveal-rise {
        opacity: 0;
        transform: translateY(120px);
        transform-origin: center top;
        will-change: opacity, transform;
    }
    .modern-home-cta.scroll-reveal-section.is-in-view .scroll-reveal-item.cta-reveal-rise {
        opacity: 1;
        transform: translateY(0);
    }
    /* CTA knoppen: ver van links / rechts invliegen (transition-duration in Blade: $ctaRevealDurBtn) */
    .modern-home-cta.scroll-reveal-section .scroll-reveal-item.cta-reveal-btn-left {
        opacity: 0;
        transform: translateX(-120px);
        will-change: opacity, transform;
    }
    .modern-home-cta.scroll-reveal-section .scroll-reveal-item.cta-reveal-btn-right {
        opacity: 0;
        transform: translateX(120px);
        will-change: opacity, transform;
    }
    .modern-home-cta.scroll-reveal-section.is-in-view .scroll-reveal-item.cta-reveal-btn-left,
    .modern-home-cta.scroll-reveal-section.is-in-view .scroll-reveal-item.cta-reveal-btn-right {
        opacity: 1;
        transform: translateX(0);
    }
    @media (prefers-reduced-motion: reduce) {
        .modern-home-hero.scroll-reveal-section .scroll-reveal-item.hero-reveal-title-left,
        .modern-home-hero.scroll-reveal-section .scroll-reveal-item.hero-reveal-title-right,
        .modern-home-hero.scroll-reveal-section .scroll-reveal-item.hero-reveal-zoom,
        .modern-home-hero.scroll-reveal-section .scroll-reveal-item.hero-reveal-btn,
        .modern-home-cta.scroll-reveal-section .scroll-reveal-item.cta-reveal-rise,
        .modern-home-cta.scroll-reveal-section .scroll-reveal-item.cta-reveal-btn {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
        }
    }
</style>
@endpush
