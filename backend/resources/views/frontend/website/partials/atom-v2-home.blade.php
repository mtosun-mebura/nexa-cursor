@php
    $homeSections = $homeSections ?? \App\Models\WebsitePage::defaultHomeSectionsForTheme('atom-v2');
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
    $atomAsset = fn($path) => asset('frontend-themes/atom-v2/' . ltrim($path, '/'));
    $primaryColor = $themeSettings['primary_color'] ?? '#5540af';
    $componentService = app(\App\Services\FrontendComponentService::class);
@endphp
{{-- Atom v2 thema home: secties in section_order (inclusief hero_2, component:nexa.recente_vacatures) --}}
<div class="atom-v2-home">
@foreach($sectionOrder as $sectionKey)
    @php
        $isComponent = $componentService::isComponentKey($sectionKey);
        $component = $isComponent ? $componentService->getById($componentService::componentIdFromKey($sectionKey)) : null;
    @endphp
    @if($isComponent && (($component && view()->exists($component->view ?? '')) || $sectionKey === 'component:nexa.recente_vacatures' || $sectionKey === 'component:taxiroyaal.tarieven' || $sectionKey === 'component:taxiroyaal.boekingsmodule' || $sectionKey === 'component:website.google_reviews' || $sectionKey === 'component:nexa.google_reviews'))
        @if($sectionKey === 'component:nexa.recente_vacatures' && $isNexaOrSkillmatching && view()->exists('frontend.website.components.recente-vacatures'))
            @include('frontend.website.components.recente-vacatures', ['jobs' => $jobs ?? collect()])
        @elseif($sectionKey === 'component:taxiroyaal.tarieven' && view()->exists('frontend.website.components.taxiroyaal-tarieven'))
            @include('frontend.website.components.taxiroyaal-tarieven', ['homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey])
        @elseif($sectionKey === 'component:taxiroyaal.boekingsmodule' && view()->exists('frontend.website.components.taxiroyaal-boekingsmodule'))
            @include('frontend.website.components.taxiroyaal-boekingsmodule', ['homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey])
        @elseif(($sectionKey === 'component:website.google_reviews' || $sectionKey === 'component:nexa.google_reviews') && view()->exists('frontend.website.components.google-reviews'))
            @include('frontend.website.components.google-reviews', ['reviews' => $googleReviews ?? [], 'googleReviews' => $googleReviews ?? []])
        @elseif($component && !empty($component->view) && view()->exists($component->view))
            @include($component->view, ['jobs' => $jobs ?? collect(), 'homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey])
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
        $heroBgUrl = !empty($sectionData['background_image_url']) ? app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($sectionData['background_image_url']) : $atomAsset('assets/img/bg-hero.jpg');
        $heroAuthorUrl = !empty($sectionData['author_image_url']) ? app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($sectionData['author_image_url']) : $atomAsset('assets/img/blog-author.jpg');
        $overlayFrom = $sectionData['overlay_color_from'] ?? '#5540ae';
        $overlayTo = $sectionData['overlay_color_to'] ?? '#412f90';
        $overlayOpacity = max(0, min(100, (int) ($sectionData['overlay_opacity'] ?? 95)));
        $overlayAlpha = $overlayOpacity / 100;
        $hexToRgb = function($hex) {
            $hex = ltrim($hex, '#');
            if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            if (strlen($hex) !== 6) return [85, 64, 174];
            return [ hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2)) ];
        };
        $fromRgb = $hexToRgb($overlayFrom);
        $toRgb = $hexToRgb($overlayTo);
        $heroOverlayStyle = 'background-image: linear-gradient(to right, rgba('.$fromRgb[0].','.$fromRgb[1].','.$fromRgb[2].','.$overlayAlpha.'), rgba('.$toRgb[0].','.$toRgb[1].','.$toRgb[2].','.$overlayAlpha.'));';
    @endphp
    {{-- Hero: full-width bg, gradient, title, CTA; afbeeldingen en overloop aanpasbaar via Admin > Website-pagina's > Hero --}}
    <div class="relative bg-cover bg-center bg-no-repeat py-8" style="background-image: url({{ $heroBgUrl }});">
        <div class="absolute inset-0 z-20 bg-cover bg-center bg-no-repeat" style="{{ $heroOverlayStyle }}" aria-hidden="true"></div>
        <div class="container relative z-30 pt-20 pb-12 sm:pt-56 sm:pb-48 lg:pt-64 lg:pb-48">
            <div class="flex flex-col items-center justify-center lg:flex-row">
                <div class="rounded-full border-8 shadow-xl flex-shrink-0" style="border-color: {{ $primaryColor }};">
                    <img src="{{ $heroAuthorUrl }}" class="h-48 rounded-full sm:h-56 w-48 sm:w-56 object-cover" alt="">
                </div>
                <div class="pt-8 sm:pt-10 lg:pl-8 lg:pt-0 text-center lg:text-left">
                    @if($v('_title'))
                    <h1 class="font-header text-4xl text-white sm:text-5xl md:text-6xl">
                        @php
                            $heroTitle = $sectionData['title'] ?? 'Welkom bij Nexa';
                            $heroHighlight = $sectionData['title_highlight'] ?? 'Nexa';
                            $parts = $heroHighlight !== '' ? explode($heroHighlight, $heroTitle, 2) : [$heroTitle];
                        @endphp
                        @if(count($parts) === 2)
                            {{ trim($parts[0]) }} <span class="text-yellow">{{ $heroHighlight }}</span> {{ trim($parts[1]) }}
                        @else
                            {{ $heroTitle }}
                        @endif
                    </h1>
                    @endif
                    @if($v('_subtitle') && !empty($sectionData['subtitle']))
                    <div class="pt-3 font-body text-lg uppercase text-white sm:pt-5">{!! $sectionData['subtitle'] !!}</div>
                    @endif
                    @if($v('_cta') && (!empty($sectionData['cta_primary_text']) || !empty($sectionData['cta_secondary_text'])))
                    <div class="flex flex-col justify-center pt-6 sm:flex-row sm:pt-5 lg:justify-start gap-4">
                        @if(!empty($sectionData['cta_primary_text']))
                        <a href="{{ $url($sectionData['cta_primary_url'] ?? '/register') }}" class="inline-flex items-center justify-center rounded px-8 py-3 font-header font-bold uppercase text-white hover:opacity-90" style="background-color: {{ $primaryColor }};">
                            {{ $sectionData['cta_primary_text'] }}
                        </a>
                        @endif
                        @if(!empty($sectionData['cta_secondary_text']))
                        <a href="{{ $url($sectionData['cta_secondary_url'] ?? '/jobs') }}" class="inline-flex items-center justify-center rounded border-2 border-white px-8 py-3 font-header font-bold uppercase text-white hover:bg-white/20 transition-colors">
                            {{ $sectionData['cta_secondary_text'] }}
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($base === 'why_nexa' && $v(''))
    <div class="bg-grey-50" id="about">
        <div class="container flex flex-col items-center py-16 md:py-20 lg:flex-row">
            <div class="w-full text-center sm:w-3/4 lg:w-3/5 lg:text-left">
                @if($v('_title'))
                <h2 class="font-header text-4xl font-semibold uppercase sm:text-5xl lg:text-6xl" style="color: {{ $primaryColor }};">
                    {{ $sectionData['title'] ?? 'Over ons' }}
                </h2>
                @endif
                @if($v('_subtitle'))
                <div class="pt-6 font-body leading-relaxed text-grey-20 dark:text-white">
                    {!! $sectionData['subtitle'] ?? 'Wij verbinden talent met kansen.' !!}
                </div>
                @endif
            </div>
        </div>
    </div>
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
    <div class="container py-16 md:py-20" id="services">
        @if($v('_section_title') && !empty($sectionData['section_title']))
        <h2 class="text-center font-header text-4xl font-semibold uppercase sm:text-5xl lg:text-6xl" style="color: {{ $primaryColor }};">
            {{ $sectionData['section_title'] }}
        </h2>
        @endif
        <div class="flex justify-center pt-10 md:pt-12">
            <div class="grid gap-6 md:gap-10 w-max max-w-full" style="grid-template-columns: repeat({{ $featuresCols }}, minmax(0, 20rem));">
                @foreach($featuresItems as $entry)
                @php
                    $item = $entry['item'];
                    $iconAlign = $item['icon_align'] ?? 'center';
                    $iconAlignItems = $iconAlign === 'right' ? 'items-end' : ($iconAlign === 'left' ? 'items-start' : 'items-center');
                    $iconAlignText = $iconAlign === 'right' ? 'text-right' : ($iconAlign === 'left' ? 'text-left' : 'text-center');
                @endphp
                <div class="group rounded px-8 py-12 shadow hover:opacity-90 transition-opacity flex flex-col w-full {{ $iconAlignItems }} {{ $iconAlignText }}" style="background-color: {{ $primaryColor }};">
                    <div class="h-24 w-24 shrink-0 xl:h-28 xl:w-28 flex items-center justify-center">
                        <i class="bx bx-bulb text-6xl text-white xl:text-7xl"></i>
                    </div>
                    <div class="{{ $iconAlignText }}">
                        <h3 class="pt-8 text-lg font-semibold uppercase text-yellow lg:text-xl">
                            {{ $item['title'] ?? 'Dienst' }}
                        </h3>
                        <div class="text-white pt-4 text-sm md:text-base opacity-90">
                            {!! $item['description'] ?? '' !!}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
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
    @php $ctaBgUrl = !empty($sectionData['background_image_url']) ? app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($sectionData['background_image_url']) : $atomAsset('assets/img/bg-cta.jpg'); @endphp
    <div class="relative bg-cover bg-center bg-no-repeat py-16 lg:py-24" style="background-image: url({{ $ctaBgUrl }}); background-color: {{ $primaryColor }}; background-blend-mode: multiply;">
        <div class="container relative z-30">
            @if($v('_title'))
            <h3 class="text-center font-header text-3xl uppercase leading-tight tracking-wide text-white sm:text-4xl lg:text-5xl">
                {!! nl2br(e($sectionData['title'] ?? 'Klaar om te starten?')) !!}
            </h3>
            @endif
            @if($v('_subtitle') && !empty($sectionData['subtitle']))
            <div class="mt-4 text-center font-body text-white/90" style="color: rgba(255,255,255,0.9);">{!! $sectionData['subtitle'] !!}</div>
            @endif
            @if($v('_cta') && (!empty($sectionData['cta_primary_text']) || !empty($sectionData['cta_secondary_text'])))
            <div class="mt-6 flex flex-col justify-center gap-4 sm:flex-row sm:gap-4">
                @if(!empty($sectionData['cta_primary_text']))
                <a href="{{ $url($sectionData['cta_primary_url'] ?? '/register') }}" class="inline-flex items-center justify-center rounded bg-yellow px-8 py-3 font-body font-bold uppercase transition-colors hover:opacity-90" style="color: {{ $primaryColor }};">
                    {{ $sectionData['cta_primary_text'] }}
                </a>
                @endif
                @if(!empty($sectionData['cta_secondary_text']))
                <a href="{{ $url($sectionData['cta_secondary_url'] ?? '/jobs') }}" class="inline-flex items-center justify-center rounded border-2 border-white px-8 py-3 font-body font-bold uppercase text-white hover:bg-white/20 transition-colors">
                    {{ $sectionData['cta_secondary_text'] }}
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
    @endif

    @if($base === 'carousel' && $v(''))
    <div class="w-full pt-8 md:pt-12">
        @include('frontend.website.partials.carousel', ['items' => $sectionData['items'] ?? []])
    </div>
    @endif
    @endif
@endforeach
</div>
