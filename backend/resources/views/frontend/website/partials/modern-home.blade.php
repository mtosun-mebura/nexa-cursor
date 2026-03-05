@php
    $homeSections = $homeSections ?? \App\Models\WebsitePage::defaultHomeSections();
    $visibility = $homeSections['visibility'] ?? [];
    $defaultSectionOrder = ['hero', 'why_nexa', 'features', 'stats', 'component:nexa.recente_vacatures', 'cta'];
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
        $types = ['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken', 'featured_services'];
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
    @if($isComponent && (($component && view()->exists($component->view ?? '')) || $sectionKey === 'component:nexa.recente_vacatures' || $sectionKey === 'component:taxiroyaal.tarieven' || $sectionKey === 'component:taxiroyaal.boekingsmodule'))
        @if($sectionKey === 'component:nexa.recente_vacatures' && view()->exists('frontend.website.components.recente-vacatures'))
            @include('frontend.website.components.recente-vacatures', ['jobs' => $jobs ?? collect()])
        @elseif($sectionKey === 'component:taxiroyaal.tarieven' && view()->exists('frontend.website.components.taxiroyaal-tarieven'))
            @include('frontend.website.components.taxiroyaal-tarieven', ['homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey])
        @elseif($sectionKey === 'component:taxiroyaal.boekingsmodule' && view()->exists('frontend.website.components.taxiroyaal-boekingsmodule'))
            @include('frontend.website.components.taxiroyaal-boekingsmodule', ['homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey])
        @elseif($component && view()->exists($component->view ?? ''))
            @include($component->view, ['jobs' => $jobs ?? collect(), 'homeSections' => $homeSections ?? [], 'sectionKey' => $sectionKey])
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
        @include('frontend.website.blocks.stats', ['sectionData' => $sectionData, 'visibility' => $visibility, 'sectionKey' => $sectionKey])
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
@php
    $featuresRevealDuration = '0.6s';
    $featuresRevealDelayStepMs = 200;
    $featuresTitleDelayMs = 0;
    $featuresFirstCardDelayMs = 100;
    $featuresEasing = 'cubic-bezier(0.25, 0.46, 0.45, 0.94)';
@endphp
<!-- Wat Wij Bieden -->
<section class="modern-home-features py-16 md:py-20 bg-white dark:bg-gray-900 scroll-reveal-section" data-scroll-reveal>
    <div class="container-custom">
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
                    $iconName = $item['icon'] ?? ($fi === 0 ? 'light-bulb' : 'bolt');
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
                    $cardRevealDelayMs = $featuresFirstCardDelayMs + $fi * $featuresRevealDelayStepMs;
                    $cardRevealStyle = 'transition: opacity ' . $featuresRevealDuration . ' ' . $featuresEasing . ', transform ' . $featuresRevealDuration . ' ' . $featuresEasing . '; transition-delay: ' . $cardRevealDelayMs . 'ms;';
                @endphp
                <div class="scroll-reveal-item" style="{{ $cardRevealStyle }}">
                <div class="features-card rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-6 transition-colors h-full">
                    <div class="flex flex-col w-full {{ $iconAlignItems }} {{ $iconAlignText }}">
                        <div class="features-card-icon w-12 h-12 {{ $fi === 0 ? 'bg-blue-100 dark:bg-blue-500/20' : 'bg-green-100 dark:bg-green-500/20' }} rounded-lg flex items-center justify-center shrink-0">
                            <svg class="{{ $iconSizeClass }} {{ $fi === 0 ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">{!! $iconDef['svg'] ?? '' !!}</svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mt-4 mb-3">{{ $item['title'] ?? '' }}</h3>
                        <div class="text-gray-600 dark:text-gray-300 prose prose-sm dark:prose-invert prose-p:my-1 prose-ul:my-1 prose-ol:my-1 max-w-none">{!! $item['description'] ?? '' !!}</div>
                    </div>
                </div>
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
    @keyframes features-icon-bounce-left {
        0% { transform: translateX(0); }
        50% { transform: translateX(-8px); }
        100% { transform: translateX(-3px); }
    }
    .modern-home-features .features-card:hover .features-card-icon {
        animation: features-icon-bounce-left 0.4s ease-out forwards;
    }
</style>
@endpush
@push('scripts')
<script>
(function() {
    function initScrollReveal() {
        var sections = document.querySelectorAll('[data-scroll-reveal]');
        if (!sections.length) return;
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) entry.target.classList.add('is-in-view');
            });
        }, { rootMargin: '0px 0px -80px 0px', threshold: 0.08 });
        sections.forEach(function(s) { observer.observe(s); });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initScrollReveal);
    else initScrollReveal();
})();
</script>
@endpush
    @endif

    @if($base === 'cards_ronde_hoeken' && $v(''))
        @include('frontend.website.partials.cards-ronde-hoeken', ['items' => $sectionData['items'] ?? [], 'visibility' => $visibility, 'sectionKey' => $sectionKey, 'cards_per_row' => $sectionData['cards_per_row'] ?? 4])
    @endif
    @if($base === 'featured_services' && $v(''))
        @include('frontend.website.blocks.featured_services', ['block' => ['data' => $sectionData]])
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
