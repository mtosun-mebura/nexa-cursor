@php
    $homeSections = $homeSections ?? \App\Models\WebsitePage::defaultHomeSectionsForTheme('next-landing-vpn');
    $visibility = $homeSections['visibility'] ?? [];
    $defaultSectionOrder = ['hero', 'features', 'cta', 'carousel'];
    $sectionOrder = $homeSections['section_order'] ?? $defaultSectionOrder;
    if (!is_array($sectionOrder)) {
        $sectionOrder = $defaultSectionOrder;
    }
    $sectionOrder = array_values($sectionOrder);
    $missingInOrder = array_diff($defaultSectionOrder, $sectionOrder);
    if (!empty($missingInOrder)) {
        foreach (array_values($missingInOrder) as $key) {
            $pos = array_search($key, $defaultSectionOrder, true);
            if ($pos !== false) {
                array_splice($sectionOrder, min($pos, count($sectionOrder)), 0, [$key]);
            }
        }
        $sectionOrder = array_values($sectionOrder);
    }
    $baseTypes = ['hero', 'features', 'cta', 'carousel', 'cards_ronde_hoeken'];
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
    $vpnAsset = fn($path) => asset('frontend-themes/next-landing-vpn/public/assets/' . ltrim($path, '/'));
    $primaryColor = $themeSettings['primary_color'] ?? '#f97316';
    $normHex = function($v, $fallback) {
        if ($v === null || $v === '') return $fallback;
        $v = ltrim(trim((string) $v), '#');
        return $v === '' ? $fallback : '#' . $v;
    };
    $componentService = app(\App\Services\FrontendComponentService::class);
@endphp
{{-- Next Landing VPN thema: hero, features, cta, carousel. Gebruikt thema-assets (Illustration1, Illustration2, Icon/*). --}}
<div class="next-landing-vpn-home">
@foreach($sectionOrder as $sectionKey)
    @php
        $isComponent = $componentService::isComponentKey($sectionKey);
        $component = $isComponent ? $componentService->getById($componentService::componentIdFromKey($sectionKey)) : null;
    @endphp
    @if($isComponent && (($component && view()->exists($component->view ?? '')) || $sectionKey === 'component:nexa.recente_vacatures'))
        @if($sectionKey === 'component:nexa.recente_vacatures' && view()->exists('frontend.website.components.recente-vacatures'))
            @include('frontend.website.components.recente-vacatures', ['jobs' => $jobs ?? collect()])
        @elseif($component && !empty($component->view) && view()->exists($component->view))
            @include($component->view, ['jobs' => $jobs ?? collect()])
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
        $heroImageUrl = !empty($sectionData['author_image_url']) ? $sectionData['author_image_url'] : $vpnAsset('Illustration1.png');
    @endphp
    <section class="max-w-screen-xl mt-12 sm:mt-24 px-6 sm:px-8 lg:px-16 mx-auto" id="hero">
        <div class="grid grid-flow-row sm:grid-flow-col grid-rows-2 md:grid-rows-1 sm:grid-cols-2 gap-8 py-6 sm:py-16">
            <div class="flex flex-col justify-center items-start row-start-2 sm:row-start-1">
                @if($v('_title'))
                <h1 class="text-3xl lg:text-4xl xl:text-5xl font-medium text-gray-900 dark:text-white leading-normal">
                    @php
                        $heroTitle = $sectionData['title'] ?? 'Jouw carrière begint hier';
                        $heroHighlight = $sectionData['title_highlight'] ?? 'hier';
                        $parts = $heroHighlight !== '' ? explode($heroHighlight, $heroTitle, 2) : [$heroTitle];
                    @endphp
                    @if(count($parts) === 2)
                        {{ trim($parts[0]) }} <strong style="color: {{ $primaryColor }};">{{ $heroHighlight }}</strong> {{ trim($parts[1]) }}
                    @else
                        {{ $heroTitle }}
                    @endif
                </h1>
                @endif
                @if($v('_subtitle') && !empty($sectionData['subtitle']))
                <p class="text-white mt-4 mb-6" style="color: #fff;">{!! $sectionData['subtitle'] !!}</p>
                @endif
                @if($v('_cta') && (!empty($sectionData['cta_primary_text']) || !empty($sectionData['cta_secondary_text'])))
                <div class="flex flex-col sm:flex-row gap-4 pt-5">
                    @if(!empty($sectionData['cta_primary_text']))
                    @php
                        $heroPrimaryBg = $normHex($sectionData['cta_primary_bg'] ?? null, $primaryColor);
                        $heroPrimaryColor = $normHex($sectionData['cta_primary_text_color'] ?? null, '#ffffff');
                        $heroPrimaryBorder = $sectionData['cta_primary_border'] ?? '';
                        $heroPrimaryBorder = $heroPrimaryBorder !== '' ? $normHex($heroPrimaryBorder, $primaryColor) : 'transparent';
                    @endphp
                    <a href="{{ $url($sectionData['cta_primary_url'] ?? '/register') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-medium rounded-lg border-2 transition-all duration-200 hover:brightness-90 hover:shadow-xl hover:-translate-y-1" style="background-color: {{ $heroPrimaryBg }}; color: {{ $heroPrimaryColor }}; border-color: {{ $heroPrimaryBorder }};">
                        {{ $sectionData['cta_primary_text'] }}
                    </a>
                    @endif
                    @if(!empty($sectionData['cta_secondary_text']))
                    @php
                        $heroSecondaryBorder = $normHex($sectionData['cta_secondary_border'] ?? null, $primaryColor);
                        $heroSecondaryColor = $normHex($sectionData['cta_secondary_text_color'] ?? null, $primaryColor);
                    @endphp
                    <a href="{{ $url($sectionData['cta_secondary_url'] ?? '/jobs') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-medium rounded-lg border-2 transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-700 hover:shadow-xl hover:-translate-y-1" style="border-color: {{ $heroSecondaryBorder }}; color: {{ $heroSecondaryColor }};">
                        {{ $sectionData['cta_secondary_text'] }}
                    </a>
                    @endif
                </div>
                @endif
            </div>
            <div class="flex w-full row-start-1 sm:row-start-auto">
                <img src="{{ $heroImageUrl }}" alt="" class="h-full w-full object-contain" width="612" height="383">
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
        $featureIllustrationUrl = !empty($sectionData['illustration_url']) ? $sectionData['illustration_url'] : $vpnAsset('Illustration2.png');
    @endphp
    <section class="max-w-screen-xl mt-8 mb-6 sm:mt-14 sm:mb-14 px-6 sm:px-8 lg:px-16 mx-auto" id="feature">
        <div class="grid grid-flow-row sm:grid-flow-col grid-cols-1 sm:grid-cols-2 gap-8 py-8 my-12">
            <div class="flex w-full justify-end order-2 sm:order-1">
                <img src="{{ $featureIllustrationUrl }}" alt="" class="h-full w-full max-w-md object-contain p-4" width="508" height="414">
            </div>
            <div class="flex flex-col justify-center order-1 sm:order-2 w-full lg:w-9/12 ml-auto">
                @if($v('_section_title') && !empty($sectionData['section_title']))
                <h3 class="text-3xl lg:text-4xl font-medium leading-relaxed text-gray-900 dark:text-white" style="color: {{ $primaryColor }};">
                    {{ $sectionData['section_title'] }}
                </h3>
                @endif
                <ul class="mt-4 text-gray-600 dark:text-gray-400 space-y-3 list-none pl-0">
                    @foreach($featuresItems as $entry)
                    @php $item = $entry['item']; @endphp
                    <li class="flex gap-3 items-start">
                        <span class="shrink-0 mt-0.5 w-5 h-5 rounded-full flex items-center justify-center" style="background-color: {{ $primaryColor }};">
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $item['title'] ?? 'Kenmerk' }}</span>
                            @if(!empty($item['description']))
                            <span class="block text-sm mt-0.5">{!! $item['description'] !!}</span>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
    @endif

    @if($base === 'cards_ronde_hoeken' && $v(''))
        @include('frontend.website.partials.cards-ronde-hoeken', ['items' => $sectionData['items'] ?? [], 'visibility' => $visibility, 'sectionKey' => $sectionKey])
    @endif

    @if($base === 'cta' && $v(''))
    @php $ctaBgUrl = !empty($sectionData['background_image_url']) ? $sectionData['background_image_url'] : ''; @endphp
    <section id="next-landing-vpn-cta" class="py-16 lg:py-24 relative overflow-hidden" style="background-color: {{ $primaryColor }}; @if($ctaBgUrl) background-image: url({{ $ctaBgUrl }}); background-size: cover; background-blend-mode: multiply; @endif">
        <div class="max-w-screen-xl px-6 sm:px-8 lg:px-16 mx-auto relative z-10 text-center">
            @if($v('_title'))
            <h3 class="text-2xl sm:text-3xl lg:text-4xl font-medium text-white leading-relaxed">
                {!! nl2br(e($sectionData['title'] ?? 'Klaar om te starten?')) !!}
            </h3>
            @endif
            @if($v('_subtitle') && !empty($sectionData['subtitle']))
            <p class="next-landing-vpn-cta-subtitle mt-2 text-lg max-w-2xl mx-auto" style="color: #fff;">{!! $sectionData['subtitle'] !!}</p>
            @endif
            @if($v('_cta') && (!empty($sectionData['cta_primary_text']) || !empty($sectionData['cta_secondary_text'])))
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4 pt-5">
                @if(!empty($sectionData['cta_primary_text']))
                @php
                    $ctaPrimaryBg = $normHex($sectionData['cta_primary_bg'] ?? null, '#ffffff');
                    $ctaPrimaryColor = $normHex($sectionData['cta_primary_text_color'] ?? null, $primaryColor);
                    $ctaPrimaryBorderRaw = $sectionData['cta_primary_border'] ?? '';
                    $ctaPrimaryBorder = $ctaPrimaryBorderRaw !== '' ? $normHex($ctaPrimaryBorderRaw, $primaryColor) : 'transparent';
                @endphp
                <a href="{{ $url($sectionData['cta_primary_url'] ?? '/register') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-bold rounded-lg border-2 transition-all duration-200 hover:shadow-xl hover:-translate-y-1" style="background-color: {{ $ctaPrimaryBg }}; color: {{ $ctaPrimaryColor }}; border-color: {{ $ctaPrimaryBorder }};">
                    {{ $sectionData['cta_primary_text'] }}
                </a>
                @endif
                @if(!empty($sectionData['cta_secondary_text']))
                @php
                    $ctaSecondaryBorder = $normHex($sectionData['cta_secondary_border'] ?? null, '#ffffff');
                    $ctaSecondaryColor = $normHex($sectionData['cta_secondary_text_color'] ?? null, '#ffffff');
                @endphp
                <a href="{{ $url($sectionData['cta_secondary_url'] ?? '/jobs') }}" class="inline-flex justify-center items-center px-8 py-4 text-base font-bold border-2 border-white rounded-lg text-white transition-all duration-200 hover:bg-white/20 hover:shadow-xl hover:-translate-y-1">
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
        @include('frontend.website.partials.carousel', ['items' => $sectionData['items'] ?? []])
    </div>
    @endif
    @endif
@endforeach
</div>
