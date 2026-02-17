@php
    $homeSections = $homeSections ?? \App\Models\WebsitePage::defaultHomeSections();
    $visibility = $homeSections['visibility'] ?? [];
    $defaultSectionOrder = ['hero', 'stats', 'why_nexa', 'features', 'component:nexa.recente_vacatures', 'cta'];
    $sectionOrder = $homeSections['section_order'] ?? $defaultSectionOrder;
    if (!is_array($sectionOrder)) {
        $sectionOrder = $defaultSectionOrder;
    }
    $missingInOrder = array_diff($defaultSectionOrder, $sectionOrder);
    if (!empty($missingInOrder)) {
        foreach (array_values($missingInOrder) as $key) {
            $pos = array_search($key, $defaultSectionOrder, true);
            if ($pos !== false) {
                array_splice($sectionOrder, $pos, 0, [$key]);
            }
        }
        $sectionOrder = array_values($sectionOrder);
    }
    $url = function($u) {
        if (empty($u)) return url('/');
        $u = trim($u);
        return (strpos($u, 'http') === 0 || strpos($u, '//') === 0) ? $u : url($u);
    };
    $baseType = function($key) {
        $types = ['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken'];
        if (in_array($key, $types, true)) return $key;
        $base = preg_replace('/_\d+$/', '', $key);
        return in_array($base, $types, true) ? $base : null;
    };
@endphp
{{-- Modern thema home: secties in volgorde van section_order (bewerkbaar via Admin > Website Pagina's > Home). Dynamische keys (hero_2, features_2) en component:module.key ondersteund. --}}
@foreach($sectionOrder as $sectionKey)
    @php
        $componentService = app(\App\Services\FrontendComponentService::class);
        $isComponent = $componentService::isComponentKey($sectionKey);
        $component = $isComponent ? $componentService->getById($componentService::componentIdFromKey($sectionKey)) : null;
    @endphp
    @if($isComponent && (($component && view()->exists($component->view)) || $sectionKey === 'component:nexa.recente_vacatures'))
        @if($sectionKey === 'component:nexa.recente_vacatures' && view()->exists('frontend.website.components.recente-vacatures'))
            @include('frontend.website.components.recente-vacatures', ['jobs' => $jobs ?? collect()])
        @elseif($component && view()->exists($component->view))
            @include($component->view, ['jobs' => $jobs ?? collect()])
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
    $heroBgUrl = !empty($sectionData['background_image_url']) ? $sectionData['background_image_url'] : '';
    $heroBgStyle = $heroBgUrl !== '' ? 'background-image: url(' . e($heroBgUrl) . ');' : '';
@endphp
<!-- Hero -->
<section class="py-16 md:py-24 relative overflow-hidden {{ $heroBgUrl === '' ? 'bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900' : '' }}">
    @if($heroBgUrl !== '')
    <div class="absolute inset-0 z-0 bg-cover bg-center bg-no-repeat" style="{{ $heroBgStyle }}" aria-hidden="true"></div>
    <div class="absolute inset-0 z-[1] bg-gradient-to-br from-blue-600/85 via-blue-700/85 to-purple-800/85 dark:from-gray-900/90 dark:via-blue-900/90 dark:to-purple-900/90" aria-hidden="true"></div>
    @endif
    @if(!empty($sectionData['overlay']))
    <div class="absolute inset-0 z-[2] bg-black/10 dark:bg-black/20" aria-hidden="true"></div>
    @endif
    <div class="container-custom relative z-10">
        <div class="w-full text-center">
            @if($v('_title'))
            @php
                $heroTitle = $sectionData['title'] ?? 'Vind je droombaan met AI';
                $heroHighlight = $sectionData['title_highlight'] ?? 'droombaan';
                $heroTitleParts = $heroHighlight !== '' ? explode($heroHighlight, $heroTitle, 2) : [$heroTitle];
            @endphp
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
                @if(count($heroTitleParts) === 2)
                    {{ trim($heroTitleParts[0]) }} <span class="text-blue-200 dark:text-blue-300">{{ $heroHighlight }}</span> {{ trim($heroTitleParts[1]) }}
                @else
                    {{ $heroTitle }}
                @endif
            </h1>
            @endif
            @if($v('_subtitle'))
            <div class="text-xl text-blue-100 dark:text-blue-200 mb-8 w-full leading-relaxed max-w-3xl mx-auto prose prose-invert prose-p:my-2 prose-ul:my-2 prose-ol:my-2 max-w-none">
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
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ $url($sectionData['cta_primary_url'] ?? '/register') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-lg font-semibold bg-white text-blue-600 hover:bg-blue-50 dark:bg-blue-600 dark:text-white dark:hover:bg-blue-700 transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5" @if($heroPrimaryStyle) style="{{ $heroPrimaryStyle }}" @endif>
                    {{ $sectionData['cta_primary_text'] ?? 'Gratis account aanmaken' }}
                </a>
                <a href="{{ $url($sectionData['cta_secondary_url'] ?? '/jobs') }}" class="inline-flex items-center justify-center px-8 py-4 bg-transparent hover:bg-white text-white hover:text-blue-600 dark:hover:text-blue-700 font-semibold rounded-lg border-2 border-white hover:border-white shadow-lg hover:shadow-xl transition-all hover:-translate-y-0.5" @if($heroSecondaryStyle) style="{{ $heroSecondaryStyle }}" @endif>
                    {{ $sectionData['cta_secondary_text'] ?? 'Vacatures bekijken' }}
                </a>
            </div>
            @endif
        </div>
    </div>
</section>
    @endif

    @if($base === 'stats' && $v(''))
<!-- Stats -->
<section class="modern-home-stats py-16 bg-gray-100 dark:bg-gray-900">
    <div class="container-custom">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            @foreach(array_slice(array_merge($sectionData, [['value'=>'','label'=>''],['value'=>'','label'=>''],['value'=>'','label'=>''],['value'=>'','label'=>'']]), 0, 4) as $i => $stat)
            @if($visibility[$sectionKey . '_' . $i] ?? $visibility['stats_'.$i] ?? true)
            <div class="text-center p-6">
                <div class="text-3xl font-bold {{ $i === 0 ? 'text-blue-600 dark:text-blue-400' : ($i === 1 ? 'text-green-600 dark:text-green-400' : ($i === 2 ? 'text-gray-900 dark:text-white' : 'text-orange-600 dark:text-orange-400')) }} mb-2">{{ $stat['value'] ?? '' }}</div>
                <div class="text-gray-600 dark:text-gray-300">{{ $stat['label'] ?? '' }}</div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</section>
    @endif

    @if($base === 'why_nexa' && $v(''))
<!-- Waarom Nexa -->
<section class="modern-home-waarom py-16 md:py-20 bg-white dark:bg-gray-900">
    <div class="container-custom">
        <div class="max-w-5xl mx-auto text-center">
            @if($v('_title'))
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                {{ $sectionData['title'] ?? 'Waarom kiezen voor Nexa?' }}
            </h2>
            @endif
            @if($v('_subtitle'))
            <div class="text-xl text-gray-600 dark:text-gray-300 leading-relaxed prose prose-gray dark:prose-invert prose-p:my-2 prose-ul:my-2 prose-ol:my-2 max-w-none mx-auto">
                {!! $sectionData['subtitle'] ?? 'Onze geavanceerde AI-technologie maakt het vinden van de perfecte baan eenvoudiger dan ooit.' !!}
            </div>
            @endif
        </div>
    </div>
</section>
    @endif

    @if($base === 'features' && $v(''))
<!-- Wat Wij Bieden -->
<section class="modern-home-features py-16 md:py-20 bg-white dark:bg-gray-900">
    <div class="container-custom">
        <div class="max-w-5xl mx-auto">
            @if($visibility[$sectionKey . '_section_title'] ?? $visibility['features_section_title'] ?? true)
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                {{ $sectionData['section_title'] ?? 'Wat Wij Bieden' }}
            </h2>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach(($sectionData['items'] ?? []) as $fi => $item)
                @if($visibility[$sectionKey . '_item_' . $fi] ?? $visibility['features_item_'.$fi] ?? true)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-6 hover:border-blue-500/50 transition-colors">
                    <div class="w-12 h-12 {{ $fi === 0 ? 'bg-blue-100 dark:bg-blue-500/20' : 'bg-green-100 dark:bg-green-500/20' }} rounded-lg flex items-center justify-center mb-4">
                        @if(($item['icon'] ?? '') === 'lightning')
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        @else
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                        @endif
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">{{ $item['title'] ?? '' }}</h3>
                    <div class="text-gray-600 dark:text-gray-300 prose prose-sm dark:prose-invert prose-p:my-1 prose-ul:my-1 prose-ol:my-1 max-w-none">{!! $item['description'] ?? '' !!}</div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>
</section>
    @endif

    @if($base === 'cards_ronde_hoeken' && $v(''))
        @include('frontend.website.partials.cards-ronde-hoeken', ['items' => $sectionData['items'] ?? [], 'visibility' => $visibility, 'sectionKey' => $sectionKey])
    @endif

    @if($base === 'cta' && $v(''))
@php
    $ctaBgUrl = !empty($sectionData['background_image_url']) ? $sectionData['background_image_url'] : '';
    $ctaBgStyle = $ctaBgUrl !== '' ? 'background-image: url(' . e($ctaBgUrl) . ');' : '';
@endphp
<!-- CTA -->
<section class="modern-home-cta py-16 relative overflow-hidden {{ $ctaBgUrl === '' ? 'bg-gray-100 dark:bg-gray-900' : '' }}">
    @if($ctaBgUrl !== '')
    <div class="absolute inset-0 z-0 bg-cover bg-center bg-no-repeat" style="{{ $ctaBgStyle }}" aria-hidden="true"></div>
    <div class="absolute inset-0 z-[1] bg-gray-900/70 dark:bg-gray-900/80" aria-hidden="true"></div>
    @endif
    <div class="container-custom relative z-10 text-center {{ $ctaBgUrl !== '' ? 'text-white' : '' }}">
        @if($v('_title'))
        <h2 class="text-3xl md:text-4xl font-bold {{ $ctaBgUrl !== '' ? 'text-white' : 'text-gray-900 dark:text-white' }} mb-4">{{ $sectionData['title'] ?? 'Klaar om je carrière te starten?' }}</h2>
        @endif
        @if($v('_subtitle'))
        <div class="text-lg {{ $ctaBgUrl !== '' ? 'text-blue-100' : 'text-gray-600 dark:text-gray-300' }} mb-8 prose {{ $ctaBgUrl !== '' ? 'prose-invert prose-p:my-2 prose-ul:my-2 prose-ol:my-2' : 'prose-gray dark:prose-invert prose-p:my-2 prose-ul:my-2 prose-ol:my-2' }} max-w-none">{!! $sectionData['subtitle'] ?? 'Sluit je aan bij duizenden professionals die hun droombaan hebben gevonden.' !!}</div>
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
        @endphp
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ $url($sectionData['cta_primary_url'] ?? '/register') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-lg font-semibold text-white transition-all hover:opacity-90 hover:shadow-lg hover:-translate-y-0.5" style="{{ $ctaPrimaryStyle }}">{{ $sectionData['cta_primary_text'] ?? 'Gratis account aanmaken' }}</a>
            <a href="{{ $url($sectionData['cta_secondary_url'] ?? '/jobs') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-lg font-semibold border-2 bg-white border-gray-800 text-gray-900 hover:bg-gray-800 hover:text-white hover:border-gray-800 dark:bg-gray-700 dark:border-gray-300 dark:text-white dark:hover:bg-gray-100 dark:hover:text-gray-900 dark:hover:border-gray-100 hover:shadow-lg hover:-translate-y-0.5 transition-all focus-visible:outline focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-gray-900 dark:focus-visible:ring-gray-100" @if($ctaSecondaryStyle) style="{{ $ctaSecondaryStyle }}" @endif>{{ $sectionData['cta_secondary_text'] ?? 'Vacatures bekijken' }}</a>
        </div>
        @endif
    </div>
</section>
    @endif
    @endif
@endforeach
