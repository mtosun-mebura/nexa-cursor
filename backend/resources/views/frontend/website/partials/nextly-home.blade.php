@php
    $homeSections = $homeSections ?? \App\Models\WebsitePage::defaultHomeSectionsForTheme('nextly-template');
    $emailTemplateBySectionKey = $emailTemplateBySectionKey ?? [];
    $visibility = $homeSections['visibility'] ?? [];
    $isNexaOrSkillmatching = !isset($page) || $page->module_name === null || strtolower((string)$page->module_name) === 'skillmatching';
    $defaultSectionOrder = ['hero', 'why_nexa', 'features', 'stats', 'cta', 'carousel'];
    $sectionOrder = $homeSections['section_order'] ?? $defaultSectionOrder;
    if (!is_array($sectionOrder)) {
        $sectionOrder = $defaultSectionOrder;
    }
    $sectionOrder = array_values($sectionOrder);
    // Alleen opgeslagen section_order tonen; verwijderde secties blijven weg.
    $baseTypes = ['hero', 'why_nexa', 'features', 'stats', 'cta', 'carousel', 'cards_ronde_hoeken', 'featured_services', 'email_template', 'text_block'];
    $baseType = function($key) use ($baseTypes) {
        if (in_array($key, $baseTypes, true)) return $key;
        $base = preg_replace('/_\d+$/', '', (string) $key);
        return in_array($base, $baseTypes, true) ? $base : null;
    };
    $url = function($u) {
        if (empty($u)) return url('/');
        $u = trim($u);
        return (strpos($u, 'http') === 0 || strpos($u, '//') === 0) ? $u : url($u);
    };
    $nextlyAsset = fn($path) => asset('frontend-themes/nextly-template/public/' . ltrim($path, '/'));
    $primaryColor = $themeSettings['primary_color'] ?? '#2563eb';
    $normHex = function($v, $fallback) {
        if ($v === null || $v === '') return $fallback;
        $v = ltrim(trim((string) $v), '#');
        return $v === '' ? $fallback : '#' . $v;
    };
    $componentService = app(\App\Services\FrontendComponentService::class);
@endphp
{{-- Nextly thema home: secties in section_order (inclusief hero_2, component:nexa.recente_vacatures) --}}
<div class="nextly-home">
@foreach($sectionOrder as $sectionKey)
    @php
        $isComponent = $componentService::isComponentKey($sectionKey);
        $component = $isComponent ? $componentService->getById($componentService::componentIdFromKey($sectionKey)) : null;
    @endphp
    @if($isComponent && (($component && view()->exists($component->view ?? '')) || $sectionKey === 'component:nexa.recente_vacatures' || $sectionKey === 'component:taxi.tarieven' || $sectionKey === 'component:taxi.boekingsmodule' || $sectionKey === 'component:taxi.boekingsmodule_v2' || $sectionKey === 'component:website.google_reviews' || $sectionKey === 'component:nexa.google_reviews' || $sectionKey === 'component:website.nexa_modules_overview'))
        @if($visibility[$sectionKey] ?? true)
        @if($sectionKey === 'component:nexa.recente_vacatures' && $isNexaOrSkillmatching && view()->exists('frontend.website.components.recente-vacatures'))
            @include('frontend.website.components.recente-vacatures', ['jobs' => $jobs ?? collect()])
        @elseif($sectionKey === 'component:taxi.tarieven' && view()->exists('frontend.website.components.nexataxi-tarieven'))
            @include('frontend.website.components.nexataxi-tarieven', ['homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey, 'websitePageCompanyId' => isset($page) && $page->company_id ? (int) $page->company_id : null])
        @elseif($sectionKey === 'component:taxi.boekingsmodule_v2' && view()->exists('frontend.website.components.nexataxi-boekingsmodule-v2'))
            @include('frontend.website.components.nexataxi-boekingsmodule-v2', ['homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey])
        @elseif($sectionKey === 'component:taxi.boekingsmodule' && view()->exists('frontend.website.components.nexataxi-boekingsmodule'))
            @include('frontend.website.components.nexataxi-boekingsmodule', ['homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey])
        @elseif(($sectionKey === 'component:website.google_reviews' || $sectionKey === 'component:nexa.google_reviews') && view()->exists('frontend.website.components.google-reviews'))
            @include('frontend.website.components.google-reviews', ['reviews' => $googleReviews ?? [], 'googleReviews' => $googleReviews ?? []])
        @elseif($sectionKey === 'component:website.nexa_modules_overview' && view()->exists('frontend.website.components.nexa-modules-overview'))
            @include('frontend.website.components.nexa-modules-overview', ['homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey])
        @elseif($component && !empty($component->view) && view()->exists($component->view))
            @include($component->view, ['jobs' => $jobs ?? collect(), 'homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey])
        @endif
        @endif
    @else
    @php
        $base = $baseType($sectionKey);
        if ($base === null) continue;
        $sectionData = $homeSections[$sectionKey] ?? [];
        $v = function($suffix) use ($visibility, $sectionKey) { return $visibility[$sectionKey . $suffix] ?? ($visibility[$sectionKey] ?? true); };
    @endphp

    @if($base === 'hero' && ($v('') && ($v('_title') || $v('_subtitle') || $v('_cta'))))
    @php
        $heroBgUrl = !empty($sectionData['background_image_url']) ? app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($sectionData['background_image_url']) : $nextlyAsset('img/hero.png');
        $heroAuthorUrl = !empty($sectionData['author_image_url']) ? app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($sectionData['author_image_url']) : $nextlyAsset('img/hero.png');
    @endphp
    <section class="py-12 lg:py-20 bg-gray-50 dark:bg-gray-800/50">
        <div class="container mx-auto px-4">
            <div class="flex flex-col items-center gap-10 lg:flex-row lg:gap-16">
                <div class="w-full lg:w-1/2 order-2 lg:order-1 text-center lg:text-left">
                    @if($v('_title'))
                    <h1 class="text-4xl font-bold leading-tight tracking-tight text-gray-900 dark:text-white lg:text-5xl xl:text-6xl">
                        @php
                            $heroTitle = $sectionData['title'] ?? 'Welkom bij Nexa';
                            $heroHighlight = $sectionData['title_highlight'] ?? 'Nexa';
                            $heroHighlightColor = trim((string) ($sectionData['title_highlight_color'] ?? ''));
                            $heroHighlightColor = preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $heroHighlightColor) ? $heroHighlightColor : $primaryColor;
                            $parts = $heroHighlight !== '' ? explode($heroHighlight, $heroTitle, 2) : [$heroTitle];
                        @endphp
                        @if(count($parts) === 2)
                            {{ trim($parts[0]) }} <span style="color: {{ $heroHighlightColor }};">{{ $heroHighlight }}</span> {{ trim($parts[1]) }}
                        @else
                            {{ $heroTitle }}
                        @endif
                    </h1>
                    @endif
                    @if($v('_subtitle') && !empty($sectionData['subtitle']))
                    @php
                        $heroSubtitleColor = trim((string) ($sectionData['subtitle_color'] ?? ''));
                        $heroSubtitleColorStyle = ($heroSubtitleColor !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $heroSubtitleColor)) ? 'color: ' . $heroSubtitleColor . ';' : '';
                    @endphp
                    <p class="mt-4 text-lg xl:text-xl {{ $heroSubtitleColorStyle === '' ? 'text-gray-600 dark:text-gray-300' : '' }}" @if($heroSubtitleColorStyle !== '') style="{{ $heroSubtitleColorStyle }}" @endif>{!! $sectionData['subtitle'] !!}</p>
                    @endif
                    @if($v('_cta') && (!empty($sectionData['cta_primary_text']) || !empty($sectionData['cta_secondary_text'])))
                    <div class="mt-6 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        @if(!empty($sectionData['cta_primary_text']))
                        @php
                            $heroPrimaryBg = $normHex($sectionData['cta_primary_bg'] ?? null, $primaryColor);
                            $heroPrimaryColor = $normHex($sectionData['cta_primary_text_color'] ?? null, '#ffffff');
                            $heroPrimaryBorder = $sectionData['cta_primary_border'] ?? '';
                            $heroPrimaryBorder = $heroPrimaryBorder !== '' ? $normHex($heroPrimaryBorder, $primaryColor) : 'transparent';
                        @endphp
                        <a href="{{ $url($sectionData['cta_primary_url'] ?? '/register') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-medium rounded-lg border-2 transition-all duration-200 hover:brightness-90 hover:shadow-xl hover:-translate-y-1 dark:hover:brightness-125 dark:hover:shadow-2xl" style="background-color: {{ $heroPrimaryBg }}; color: {{ $heroPrimaryColor }}; border-color: {{ $heroPrimaryBorder }};">
                            {{ $sectionData['cta_primary_text'] }}
                        </a>
                        @endif
                        @if(!empty($sectionData['cta_secondary_text']))
                        @php
                            $heroSecondaryBgRaw = $sectionData['cta_secondary_bg'] ?? '';
                            $heroSecondaryBg = $heroSecondaryBgRaw !== '' ? $normHex($heroSecondaryBgRaw, $primaryColor) : 'transparent';
                            $heroSecondaryBorder = $normHex($sectionData['cta_secondary_border'] ?? null, $primaryColor);
                            $heroSecondaryColor = $normHex($sectionData['cta_secondary_text_color'] ?? null, $primaryColor);
                        @endphp
                        <a href="{{ $url($sectionData['cta_secondary_url'] ?? '/jobs') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-medium rounded-lg border-2 transition-all duration-200 hover:bg-gray-200 hover:shadow-xl dark:hover:bg-gray-600 dark:hover:shadow-2xl hover:-translate-y-1" style="background-color: {{ $heroSecondaryBg }}; border-color: {{ $heroSecondaryBorder }}; color: {{ $heroSecondaryColor }};">
                            {{ $sectionData['cta_secondary_text'] }}
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="w-full lg:w-1/2 order-1 lg:order-2 flex justify-center">
                    <img src="{{ $heroAuthorUrl }}" alt="" class="max-w-md w-full h-auto rounded-lg object-cover shadow-lg">
                </div>
            </div>
        </div>
    </section>
    @endif

    @if($base === 'why_nexa' && $v(''))
    <section class="py-16 md:py-20 bg-white dark:bg-gray-900" id="about">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto text-center">
                @if($v('_title'))
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl lg:text-5xl" style="color: {{ $primaryColor }};">
                    {{ $sectionData['title'] ?? 'Over ons' }}
                </h2>
                @endif
                @if($v('_subtitle'))
                <div class="mt-6 text-lg text-gray-600 dark:text-gray-300 leading-relaxed">{!! $sectionData['subtitle'] ?? 'Wij verbinden talent met kansen.' !!}</div>
                @endif
            </div>
        </div>
    </section>
    @endif

    @if($base === 'features' && $v(''))
    @php
        $featuresItems = [];
        foreach (array_slice($sectionData['items'] ?? [], 0, 6) as $index => $item) {
            if ($visibility['features_item_' . $index] ?? true) {
                $featuresItems[] = ['index' => $index, 'item' => $item];
            }
        }
        $featuresCount = count($featuresItems);
        $featuresCols = $featuresCount > 3 ? 2 : max(1, $featuresCount);
    @endphp
    <section class="py-16 md:py-20 bg-gray-50 dark:bg-gray-800/50" id="services">
        <div class="container mx-auto px-4">
            @if($v('_section_title') && !empty($sectionData['section_title']))
            <h2 class="text-center text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl lg:text-5xl mb-12" style="color: {{ $primaryColor }};">
                {{ $sectionData['section_title'] }}
            </h2>
            @endif
            <div class="flex justify-center">
                <div class="grid gap-6 md:gap-10 w-max max-w-full" style="grid-template-columns: repeat({{ $featuresCols }}, minmax(0, 20rem));">
                    @foreach($featuresItems as $entry)
                    @php
                        $item = $entry['item'];
                        $iconName = $item['icon'] ?? 'light-bulb';
                        $iconDef = config('heroicons.icons.'.$iconName);
                        if (!is_array($iconDef) || empty($iconDef['svg'])) {
                            $iconDef = config('heroicons.icons.light-bulb') ?? ['svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />'];
                        }
                        $iconSize = $item['icon_size'] ?? 'medium';
                        $sizeDef = config('heroicons.sizes.'.$iconSize);
                        $iconSizeClass = is_array($sizeDef) && !empty($sizeDef['class']) ? $sizeDef['class'] : 'w-10 h-10';
                        $iconAlign = $item['icon_align'] ?? 'center';
                        $iconAlignItems = $iconAlign === 'right' ? 'items-end' : ($iconAlign === 'left' ? 'items-start' : 'items-center');
                        $iconAlignText = $iconAlign === 'right' ? 'text-right' : ($iconAlign === 'left' ? 'text-left' : 'text-center');
                    @endphp
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-10 min-h-[18rem] shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex flex-col w-full {{ $iconAlignItems }} {{ $iconAlignText }}">
                            <div class="flex items-center justify-center w-[4.5rem] h-[4.5rem] py-2 rounded-lg text-white text-2xl shrink-0" style="background-color: {{ $primaryColor }};">
                                <svg class="{{ $iconSizeClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">{!! $iconDef['svg'] ?? '' !!}</svg>
                            </div>
                            <h3 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">
                                {{ $item['title'] ?? 'Dienst' }}
                            </h3>
                            <p class="mt-3 text-gray-600 dark:text-gray-400">
                                {!! $item['description'] ?? '' !!}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    @if($base === 'stats' && $v(''))
        @include('frontend.website.blocks.stats', ['sectionData' => $sectionData, 'visibility' => $visibility, 'sectionKey' => $sectionKey])
    @endif

    @if($base === 'cards_ronde_hoeken' && $v(''))
        @include('frontend.website.partials.cards-ronde-hoeken', ['items' => $sectionData['items'] ?? [], 'visibility' => $visibility, 'sectionKey' => $sectionKey, 'cards_per_row' => $sectionData['cards_per_row'] ?? 4])
    @endif
    @if($base === 'featured_services' && $v(''))
        @include('frontend.website.blocks.featured_services', ['block' => ['data' => $sectionData]])
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
    @php $ctaBgUrl = !empty($sectionData['background_image_url']) ? app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($sectionData['background_image_url']) : ''; @endphp
    <section class="py-16 lg:py-24 relative overflow-hidden" style="background-color: {{ $primaryColor }}; @if($ctaBgUrl) background-image: url({{ $ctaBgUrl }}); background-size: cover; background-blend-mode: multiply; @endif">
        <div class="container mx-auto px-4 relative z-10">
            @if($v('_title'))
            <h3 class="text-center text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                {!! nl2br(e($sectionData['title'] ?? 'Klaar om te starten?')) !!}
            </h3>
            @endif
            @if($v('_subtitle') && !empty($sectionData['subtitle']))
            <div class="mt-4 text-center text-lg text-white/90" style="color: rgba(255,255,255,0.9);">{!! $sectionData['subtitle'] !!}</div>
            @endif
            @if($v('_cta') && (!empty($sectionData['cta_primary_text']) || !empty($sectionData['cta_secondary_text'])))
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4">
                @if(!empty($sectionData['cta_primary_text']))
                @php
                    $ctaPrimaryBg = $normHex($sectionData['cta_primary_bg'] ?? null, $primaryColor);
                    $ctaPrimaryColor = $normHex($sectionData['cta_primary_text_color'] ?? null, '#ffffff');
                    $ctaPrimaryBorderRaw = $sectionData['cta_primary_border'] ?? '';
                    $ctaPrimaryBorder = $ctaPrimaryBorderRaw !== '' ? $normHex($ctaPrimaryBorderRaw, $primaryColor) : 'transparent';
                @endphp
                <a href="{{ $url($sectionData['cta_primary_url'] ?? '/register') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-bold rounded-lg border-2 transition-all duration-200 hover:shadow-xl hover:-translate-y-1 hover:brightness-90 dark:hover:brightness-125" style="background-color: {{ $ctaPrimaryBg }}; color: {{ $ctaPrimaryColor }}; border-color: {{ $ctaPrimaryBorder }};">
                    {{ $sectionData['cta_primary_text'] }}
                </a>
                @endif
                @if(!empty($sectionData['cta_secondary_text']))
                @php
                    $ctaSecondaryBgRaw = $sectionData['cta_secondary_bg'] ?? '';
                    $ctaSecondaryBg = $ctaSecondaryBgRaw !== '' ? $normHex($ctaSecondaryBgRaw, '') : 'transparent';
                    $ctaSecondaryBorder = $normHex($sectionData['cta_secondary_border'] ?? null, '#ffffff');
                    $ctaSecondaryColor = $normHex($sectionData['cta_secondary_text_color'] ?? null, '#ffffff');
                @endphp
                <a href="{{ $url($sectionData['cta_secondary_url'] ?? '/jobs') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-bold border-2 rounded-lg transition-all duration-200 hover:bg-white/40 hover:shadow-xl hover:-translate-y-1" style="background-color: {{ $ctaSecondaryBg }}; border-color: {{ $ctaSecondaryBorder }}; color: {{ $ctaSecondaryColor }};">
                    {{ $sectionData['cta_secondary_text'] }}
                </a>
                @endif
            </div>
            @endif
        </div>
    </section>
    @endif

    @if($base === 'carousel' && $v(''))
    <div class="w-full pt-8 md:pt-12 bg-gray-50 dark:bg-gray-800/50">
        @include('frontend.website.partials.carousel', ['items' => $sectionData['items'] ?? [], 'intervalSeconds' => (int) ($sectionData['interval_seconds'] ?? 5), 'maxHeightPercent' => (int) ($sectionData['max_height_percent'] ?? 0)])
    </div>
    @endif
    @endif
@endforeach
</div>
