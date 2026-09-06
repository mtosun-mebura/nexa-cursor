@php
    $themeSlugForHome = $themeSlugForHome ?? ($themeSlug ?? 'modern');
    $homeSections = $homeSections ?? \App\Models\WebsitePage::defaultHomeSectionsForTheme($themeSlugForHome);
    $emailTemplateBySectionKey = $emailTemplateBySectionKey ?? [];
    $visibility = $homeSections['visibility'] ?? [];
    $isNexaOrSkillmatching = !isset($page) || $page->module_name === null || strtolower((string)$page->module_name) === 'skillmatching';
    $defaultSectionOrder = ['hero', 'why_nexa', 'features', 'stats', 'cta', 'carousel'];
    $sectionOrder = $homeSections['section_order'] ?? $defaultSectionOrder;
    if (!is_array($sectionOrder)) {
        $sectionOrder = $defaultSectionOrder;
    }
    $sectionOrder = array_values($sectionOrder);
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
    $primaryColor = $themeSettings['primary_color'] ?? '#2563eb';
    $normHex = function($v, $fallback) {
        if ($v === null || $v === '') return $fallback;
        $v = ltrim(trim((string) $v), '#');
        return $v === '' ? $fallback : '#' . $v;
    };
    $componentService = app(\App\Services\FrontendComponentService::class);
    $heroVariant = $heroVariant ?? 'split';
    $defaultHeroImage = $defaultHeroImage ?? '';
    $wrapperClass = $wrapperClass ?? 'cms-section-home';
    $featuresCardClass = $featuresCardClass ?? 'rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-10 min-h-[18rem] shadow-sm hover:shadow-md transition-shadow';
    $iconWrapClass = $iconWrapClass ?? 'flex items-center justify-center w-[4.5rem] h-[4.5rem] py-2 rounded-lg text-white text-2xl shrink-0';
@endphp
<div class="{{ $wrapperClass }}">
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
        $heroImg = !empty($sectionData['background_image_url'])
            ? app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($sectionData['background_image_url'])
            : $defaultHeroImage;
        $heroSideImg = !empty($sectionData['author_image_url'])
            ? app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($sectionData['author_image_url'])
            : $heroImg;
    @endphp

    @if($base === 'hero' && ($v('') && ($v('_title') || $v('_subtitle') || $v('_cta'))))
        @include('frontend.website.partials.cms-section-hero', [
            'heroVariant' => $heroVariant,
            'heroImg' => $heroImg,
            'heroSideImg' => $heroSideImg,
            'sectionData' => $sectionData,
            'v' => $v,
            'url' => $url,
            'primaryColor' => $primaryColor,
            'normHex' => $normHex,
        ])
    @endif

    @if($base === 'why_nexa' && $v(''))
    @php $whyBg = \App\Models\WebsitePage::whyNexaBackgroundPresentation($sectionData); @endphp
    <section class="why-nexa-reveal-section pt-8 md:pt-10 pb-12 md:pb-16 scroll-reveal-section {{ $whyBg['surface_class'] }} {{ $whyBg['wrapper_class'] }}" id="about" data-scroll-reveal @if($whyBg['color_style'] !== '') style="{{ $whyBg['color_style'] }}" @endif>
        @include('frontend.website.partials.why-nexa-background-layers')
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                @if($v('_title'))
                <h2 class="why-nexa-reveal-title text-3xl font-bold sm:text-4xl lg:text-5xl {{ $whyBg['title_color_style'] === '' ? 'text-gray-900 dark:text-white' : '' }}" style="{{ $whyBg['title_color_style'] !== '' ? $whyBg['title_color_style'] : 'color: '.$primaryColor.';' }}">
                    {{ $sectionData['title'] ?? 'Over ons' }}
                </h2>
                @endif
                @if($v('_subtitle'))
                <div class="why-nexa-reveal-subtitle mt-6 text-lg text-gray-600 dark:text-gray-300 leading-relaxed">{!! $sectionData['subtitle'] ?? 'Wij verbinden talent met kansen.' !!}</div>
                @endif
            </div>
        </div>
    </section>
    @push('styles')
    <style>
        /* Eigen stijl: titel "materialiseert" via een horizontale clip-wipe, ondertitel
           volgt met een korte fade-up. Nog niet elders op deze pagina gebruikt. */
        .why-nexa-reveal-title {
            clip-path: inset(0 100% 0 0);
            opacity: 0;
            transition: clip-path 0.9s cubic-bezier(0.65, 0, 0.35, 1), opacity 0.25s ease;
        }
        .why-nexa-reveal-section.is-in-view .why-nexa-reveal-title {
            clip-path: inset(0 0 0 0);
            opacity: 1;
        }
        .why-nexa-reveal-subtitle {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity 0.7s ease 0.35s, transform 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.35s;
        }
        .why-nexa-reveal-section.is-in-view .why-nexa-reveal-subtitle {
            opacity: 1;
            transform: translateY(0);
        }
        @media (prefers-reduced-motion: reduce) {
            .why-nexa-reveal-title {
                clip-path: none;
                opacity: 1;
                transition: none;
            }
            .why-nexa-reveal-subtitle {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>
    @endpush
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
        $featuresFirstCardDelayMs = 100;
        $featuresRevealDelayStepMs = 110;
    @endphp
    <section class="cms-home-features pt-8 md:pt-10 pb-12 md:pb-16 bg-gray-50 dark:bg-gray-800/50 scroll-reveal-section" id="services" data-scroll-reveal-late>
        <div class="container mx-auto px-4">
            @if($v('_section_title') && !empty($sectionData['section_title']))
            <h2 class="cms-features-title scroll-reveal-item text-center text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl lg:text-5xl mb-12" style="color: {{ $primaryColor }};">
                {{ $sectionData['section_title'] }}
            </h2>
            @endif
            <div class="flex justify-center">
                <div class="grid gap-6 md:gap-10 w-max max-w-full" style="grid-template-columns: repeat({{ $featuresCols }}, minmax(0, 20rem));">
                    @foreach($featuresItems as $entry)
                    @php
                        $item = $entry['item'];
                        $cardIndex = $entry['index'];
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
                        $cardRevealDelayMs = $featuresFirstCardDelayMs + $cardIndex * $featuresRevealDelayStepMs;
                    @endphp
                    <div class="scroll-reveal-item cms-feature-card h-full" style="--cms-feature-delay: {{ $cardRevealDelayMs }}ms;">
                        <div class="cms-feature-card-inner {{ $featuresCardClass }} h-full flex flex-col">
                        <div class="flex flex-col w-full h-full {{ $iconAlignItems }} {{ $iconAlignText }}">
                            <div class="cms-feature-icon flex items-center justify-center w-14 h-14 shrink-0" style="color: {{ $primaryColor }};">
                                <svg class="{{ $iconSizeClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">{!! $iconDef['svg'] ?? '' !!}</svg>
                            </div>
                            <h3 class="cms-feature-title mt-6 text-xl font-semibold text-gray-900 dark:text-white">
                                {{ $item['title'] ?? 'Dienst' }}
                            </h3>
                            <p class="mt-3 text-gray-600 dark:text-gray-400">
                                {!! $item['description'] ?? '' !!}
                            </p>
                        </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @push('styles')
    <style>
        /* Eigen, herkenbare choreografie voor dit blok: kaarten klappen als een klep vanaf
           de onderkant omhoog (3D flip), titel zoomt scherp uit een waas. Bewust anders dan
           de fade/slide-animaties van de overige secties op de pagina. */
        .cms-home-features .cms-features-title {
            opacity: 0;
            transform: scale(0.72);
            filter: blur(10px);
            transition: opacity 0.65s cubic-bezier(0.34, 1.56, 0.64, 1), transform 0.65s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.5s ease;
            will-change: opacity, transform, filter;
        }
        .cms-home-features.is-in-view .cms-features-title {
            opacity: 1;
            transform: scale(1);
            filter: blur(0);
        }
        .cms-home-features .cms-feature-card {
            opacity: 0;
            perspective: 1000px;
            transform: perspective(1000px) rotateX(-72deg) translateY(36px);
            transform-origin: center bottom;
            transition: opacity 0.7s cubic-bezier(0.34, 1.56, 0.64, 1), transform 0.7s cubic-bezier(0.34, 1.56, 0.64, 1);
            transition-delay: var(--cms-feature-delay, 0ms);
            will-change: opacity, transform;
        }
        .cms-home-features.is-in-view .cms-feature-card {
            opacity: 1;
            transform: perspective(1000px) rotateX(0deg) translateY(0);
        }
        .cms-home-features .cms-feature-card-inner {
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease, border-color 0.3s ease;
        }
        .cms-home-features .cms-feature-card-inner:hover {
            transform: translateY(-10px) scale(1.025);
            border-color: {{ $primaryColor }};
            box-shadow: 0 22px 40px -22px color-mix(in srgb, {{ $primaryColor }} 55%, transparent), 0 8px 18px -10px rgb(0 0 0 / 0.18);
        }
        .cms-home-features .cms-feature-icon {
            transform: scale(0) rotate(200deg);
            transition: transform 0.55s cubic-bezier(0.34, 1.56, 0.64, 1);
            transition-delay: calc(var(--cms-feature-delay, 0ms) + 160ms);
        }
        .cms-home-features.is-in-view .cms-feature-card .cms-feature-icon {
            transform: scale(1) rotate(0deg);
        }
        .cms-home-features .cms-feature-card-inner:hover .cms-feature-icon {
            animation: cms-feature-icon-bounce 0.65s ease;
        }
        .cms-home-features .cms-feature-card-inner:hover .cms-feature-title {
            color: {{ $primaryColor }};
        }
        .cms-home-features .cms-feature-title {
            transition: color 0.25s ease;
        }
        @keyframes cms-feature-icon-bounce {
            0%, 100% { transform: scale(1) rotate(0deg); }
            30% { transform: scale(1.18) rotate(-10deg); }
            60% { transform: scale(0.94) rotate(8deg); }
        }
        @media (prefers-reduced-motion: reduce) {
            .cms-home-features .cms-features-title,
            .cms-home-features .cms-feature-card,
            .cms-home-features .cms-feature-icon {
                opacity: 1;
                transform: none;
                filter: none;
                transition: none;
            }
            .cms-home-features .cms-feature-card-inner:hover {
                transform: none;
            }
            .cms-home-features .cms-feature-card-inner:hover .cms-feature-icon {
                animation: none;
            }
        }
    </style>
    @endpush
    @push('scripts')
    <script>
    (function () {
        // Eigen, latere trigger dan de site-brede scroll-reveal (data-scroll-reveal): de
        // animatie moet pas starten als het blok echt goed in beeld is tijdens het scrollen,
        // niet al ruim ervoor.
        function bindLateFeaturesReveal() {
            var sections = document.querySelectorAll('[data-scroll-reveal-late]');
            if (!sections.length) return;
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduce || !('IntersectionObserver' in window)) {
                sections.forEach(function (el) { el.classList.add('is-in-view'); });
                return;
            }
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-in-view');
                    observer.unobserve(entry.target);
                });
            }, { rootMargin: '0px 0px -15% 0px', threshold: 0.25 });
            sections.forEach(function (el) {
                if (el.getAttribute('data-scroll-reveal-late-bound') === '1') return;
                el.setAttribute('data-scroll-reveal-late-bound', '1');
                observer.observe(el);
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindLateFeaturesReveal);
        } else {
            bindLateFeaturesReveal();
        }
    })();
    </script>
    @endpush
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
    <section class="cta-reveal-section py-16 lg:py-24 relative overflow-hidden scroll-reveal-section {{ $ctaSectionClass ?? '' }}" data-scroll-reveal style="background-color: {{ $primaryColor }}; @if($ctaBgUrl) background-image: url({{ $ctaBgUrl }}); background-size: cover; background-blend-mode: multiply; @endif {{ $ctaSectionStyle ?? '' }}">
        <div class="cta-reveal-glow" aria-hidden="true"></div>
        <div class="container mx-auto px-4 relative z-10">
            @if($v('_title'))
            <h3 class="cta-reveal-title text-center text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                {!! nl2br(e($sectionData['title'] ?? 'Klaar om te starten?')) !!}
            </h3>
            @endif
            @if($v('_subtitle') && !empty($sectionData['subtitle']))
            <div class="cta-reveal-subtitle mt-4 text-center text-lg text-white/90" style="color: rgba(255,255,255,0.9);">{!! $sectionData['subtitle'] !!}</div>
            @endif
            @if($v('_cta') && (($v('_cta_primary') && !empty($sectionData['cta_primary_text'])) || ($v('_cta_secondary') && !empty($sectionData['cta_secondary_text']))))
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4">
                @if($v('_cta_primary') && !empty($sectionData['cta_primary_text']))
                @php
                    $ctaPrimaryBg = $normHex($sectionData['cta_primary_bg'] ?? null, '#ffffff');
                    $ctaPrimaryColor = $normHex($sectionData['cta_primary_text_color'] ?? null, $primaryColor);
                    $ctaPrimaryBorderRaw = $sectionData['cta_primary_border'] ?? '';
                    $ctaPrimaryBorder = $ctaPrimaryBorderRaw !== '' ? $normHex($ctaPrimaryBorderRaw, '#ffffff') : 'transparent';
                    $ctaPrimaryHoverCss = \App\Services\WebsiteBuilderService::ctaButtonHoverCss($sectionData, 'cta_primary');
                @endphp
                <a href="{{ $url($sectionData['cta_primary_url'] ?? '/register') }}" class="cta-reveal-btn inline-flex justify-center items-center px-8 py-4 text-base font-bold rounded-lg border-2 transition-all duration-200 hover:shadow-xl hover:-translate-y-1 hover:brightness-90 dark:hover:brightness-125{{ $ctaPrimaryHoverCss !== '' ? ' nexa-cta-btn--custom-hover' : '' }}" style="background-color: {{ $ctaPrimaryBg }}; color: {{ $ctaPrimaryColor }}; border-color: {{ $ctaPrimaryBorder }};{{ $ctaPrimaryHoverCss }}">
                    {{ $sectionData['cta_primary_text'] }}
                </a>
                @endif
                @if($v('_cta_secondary') && !empty($sectionData['cta_secondary_text']))
                @php
                    $ctaSecondaryBgRaw = $sectionData['cta_secondary_bg'] ?? '';
                    $ctaSecondaryBg = $ctaSecondaryBgRaw !== '' ? $normHex($ctaSecondaryBgRaw, '') : 'transparent';
                    $ctaSecondaryBorder = $normHex($sectionData['cta_secondary_border'] ?? null, '#ffffff');
                    $ctaSecondaryColor = $normHex($sectionData['cta_secondary_text_color'] ?? null, '#ffffff');
                    $ctaSecondaryHoverCss = \App\Services\WebsiteBuilderService::ctaButtonHoverCss($sectionData, 'cta_secondary');
                @endphp
                <a href="{{ $url($sectionData['cta_secondary_url'] ?? '/jobs') }}" class="cta-reveal-btn inline-flex justify-center items-center px-8 py-4 text-base font-bold border-2 rounded-lg transition-all duration-200 hover:bg-white/40 hover:shadow-xl hover:-translate-y-1{{ $ctaSecondaryHoverCss !== '' ? ' nexa-cta-btn--custom-hover' : '' }}" style="background-color: {{ $ctaSecondaryBg }}; border-color: {{ $ctaSecondaryBorder }}; color: {{ $ctaSecondaryColor }};{{ $ctaSecondaryHoverCss }}">
                    {{ $sectionData['cta_secondary_text'] }}
                </a>
                @endif
            </div>
            @endif
        </div>
    </section>
    @push('styles')
    <style>
        /* Eigen stijl: een zachte lichtgloed "ademt" open op de achtergrond, titel en
           ondertitel komen omhoog gefaseerd, knoppen "poppen" er met een veer-bounce na
           elkaar bij. Dit is de laatste sectie op de pagina, dus mag net iets uitbundiger. */
        .cta-reveal-section .cta-reveal-glow {
            position: absolute;
            inset: -25%;
            background: radial-gradient(circle at 50% 35%, rgba(255, 255, 255, 0.32), transparent 62%);
            opacity: 0;
            transform: scale(0.55);
            transition: opacity 1.1s ease, transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
        }
        .cta-reveal-section.is-in-view .cta-reveal-glow {
            opacity: 1;
            transform: scale(1);
        }
        .cta-reveal-title {
            opacity: 0;
            transform: translateY(26px) scale(0.95);
            transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .cta-reveal-section.is-in-view .cta-reveal-title {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        .cta-reveal-subtitle {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.7s ease 0.18s, transform 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.18s;
        }
        .cta-reveal-section.is-in-view .cta-reveal-subtitle {
            opacity: 1;
            transform: translateY(0);
        }
        .cta-reveal-btn {
            opacity: 0;
            transform: translateY(22px) scale(0.82);
            transition: opacity 0.55s cubic-bezier(0.34, 1.56, 0.64, 1), transform 0.55s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .cta-reveal-section.is-in-view .cta-reveal-btn {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        .cta-reveal-btn:nth-of-type(1) { transition-delay: 0.32s; }
        .cta-reveal-btn:nth-of-type(2) { transition-delay: 0.44s; }
        @media (prefers-reduced-motion: reduce) {
            .cta-reveal-glow,
            .cta-reveal-title,
            .cta-reveal-subtitle,
            .cta-reveal-btn {
                opacity: 1 !important;
                transform: none !important;
                transition: none !important;
            }
        }
    </style>
    @endpush
    @endif

    @if($base === 'carousel' && $v(''))
    <div class="w-full pt-8 md:pt-12 bg-gray-50 dark:bg-gray-800/50">
        @include('frontend.website.partials.carousel', ['items' => $sectionData['items'] ?? [], 'intervalSeconds' => (int) ($sectionData['interval_seconds'] ?? 5), 'maxHeightPercent' => (int) ($sectionData['max_height_percent'] ?? 0)])
    </div>
    @endif
    @endif
@endforeach
</div>
