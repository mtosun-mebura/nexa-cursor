<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $googleMapsKeyTrimmed = trim((string) ($googleMapsApiKey ?? ''));
    $footerMapEarlyLoad = ! empty($homeSections)
        && ($homeSections['visibility']['footer'] ?? true)
        && ($homeSections['visibility']['footer_map'] ?? true)
        && $googleMapsKeyTrimmed !== '';
    $websiteThemeStorageKey = \App\Support\Tenancy\WebsiteThemeStorage::storageKey();
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $branding['site_name'] ?? config('app.name'))</title>
    <meta name="description" content="@yield('description', $branding['site_description'] ?? '')">
    <meta property="og:site_name" content="{{ $branding['site_name'] ?? config('app.name') }}">
    <meta property="og:description" content="@yield('description', $branding['site_description'] ?? '')">
    @php $seoTracking = $seoTracking ?? []; @endphp
    @include('frontend.layouts.partials.google-seo-tracking')
    @include('frontend.layouts.partials.website-structured-data')
    @if(!empty($branding['favicon_url']))
    <link rel="icon" href="{{ $branding['favicon_url'] }}">
    <link rel="shortcut icon" href="{{ $branding['favicon_url'] }}">
    @else
    <link rel="icon" type="image/png" href="{{ asset('images/nexa-x-logo.png') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @if(!empty($footerMapEarlyLoad))
    @include('frontend.layouts.partials.website-footer-map-scripts')
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Georgia&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanilla-cookieconsent@3.1.0/dist/cookieconsent.css">
    @if(!empty($loadAtomV2Styles))
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link href="{{ asset('frontend-themes/atom-v2/assets/styles/main.min.css') }}" rel="stylesheet">
    @endif
    <style>
        :root {
            --theme-primary: {{ $themeSettings['primary_color'] ?? '#2563eb' }};
            --theme-font-heading: {{ $themeSettings['font_heading'] ?? 'Inter' }}, sans-serif;
            --theme-font-body: {{ $themeSettings['font_body'] ?? 'Inter' }}, sans-serif;
        }
        body.theme-modern { --theme-primary: {{ $themeSettings['primary_color'] ?? '#2563eb' }}; }
        body.theme-classic { --theme-primary: {{ $themeSettings['primary_color'] ?? '#1e40af' }}; }
        body.theme-minimal { --theme-primary: {{ $themeSettings['primary_color'] ?? '#0f172a' }}; }
    </style>
    @include('frontend.layouts.partials.vite-frontend-assets')
    <style>
        /* Eén grootte voor alle paginatitels (h1) */
        .kt-page-title { font-size: 1.875rem; font-weight: 700; line-height: 1.2; }
        @media (min-width: 768px) { .kt-page-title { font-size: 2.25rem; } }
        /* Logo light/dark op frontend: toon juiste logo volgens thema */
        .fe-logo-light { display: block !important; }
        .fe-logo-dark { display: none !important; }
        html.dark .fe-logo-light { display: none !important; }
        html.dark .fe-logo-dark { display: block !important; }
        /* Previewbalk: zachter oranje; één regel; knop links, titel gecentreerd in resterende ruimte */
        .preview-bar {
            background-color: #f97316 !important;
            color: #ffffff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        html.dark .preview-bar {
            background-color: #f97316 !important;
            color: #ffffff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        .preview-bar-back {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.625rem;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.2;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 0.375rem;
            background-color: transparent;
            border: 1px solid #ffffff;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .preview-bar-back:hover {
            background-color: rgba(255, 255, 255, 0.12);
            color: #ffffff !important;
        }
        .preview-bar-back:focus {
            outline: 2px solid rgba(255, 255, 255, 0.85);
            outline-offset: 2px;
        }
        .preview-bar-back svg {
            flex-shrink: 0;
        }
        /* Modern home: donkere secties in dark mode (fallback zodat blokken altijd donker zijn) */
        html.dark .modern-home-stats,
        html.dark .modern-home-waarom,
        html.dark .modern-home-cta {
            background-color: #111827 !important; /* gray-900 */
        }
        html.dark .modern-home-stats .text-gray-600,
        html.dark .modern-home-waarom .text-gray-600,
        html.dark .modern-home-cta .text-gray-600 { color: #d1d5db; }
        html.dark .modern-home-stats .text-gray-900,
        html.dark .modern-home-waarom .text-gray-900,
        html.dark .modern-home-cta .text-gray-900 { color: #f9fafb; }
        /* Atom v2: dark mode voor header, menu en alle secties (thema-CSS heeft geen dark variant) */
        html.dark .theme-atom-v2 header,
        html.dark .theme-atom-v2 #website-mobile-menu { background-color: #111827 !important; border-color: #374151; }
        html.dark .theme-atom-v2 .atom-v2-home .bg-grey-50,
        html.dark .theme-atom-v2 .atom-v2-home .bg-white { background-color: #1f2937 !important; }
        html.dark .theme-atom-v2 header .text-gray-900,
        html.dark .theme-atom-v2 header a:not([style*="color"]) { color: #f3f4f6 !important; }
        html.dark .theme-atom-v2 .atom-v2-home #statistics .bg-white { background-color: #1f2937 !important; }
        html.dark .theme-atom-v2 .atom-v2-home #statistics .text-grey-dark,
        html.dark .theme-atom-v2 .atom-v2-home #statistics h4 { color: #e5e7eb !important; }
        /* Atom v2 statistics: icoontjes wit in dark mode */
        html.dark .theme-atom-v2 .atom-v2-home #statistics img.atom-v2-stat-icon { filter: brightness(0) invert(1); }
        /* Atom v2 about: paragraaftekst wit in dark mode */
        html.dark .theme-atom-v2 .atom-v2-home #about .text-grey-20 { color: #fff !important; }
        /* Atom v2 statistics: betere leesbaarheid in light mode */
        .theme-atom-v2 .atom-v2-home #statistics .text-grey-dark,
        .theme-atom-v2 .atom-v2-home #statistics h4.text-grey-dark { color: #374151; }
        /* Nextly template: dark mode header/footer consistent met andere thema's */
        html.dark .theme-nextly-template header,
        html.dark .theme-nextly-template #website-mobile-menu { background-color: #111827 !important; border-color: #374151; }
        html.dark .theme-nextly-template header .text-gray-900,
        html.dark .theme-nextly-template header a:not([style*="color"]) { color: #f3f4f6 !important; }
        /* Dark mode: alle hoofdlagen donkere achtergrond (overschrijft thema-CSS) */
        html.dark body,
        html.dark body #main-content,
        html.dark body header,
        html.dark body footer {
            background-color: #111827 !important;
        }
        html.dark body { color: #f3f4f6; }
        /* Next Landing VPN: bodytekst wit (geërfde kleur voor secties op donkere/gekleurde achtergrond) */
        body.theme-next-landing-vpn { color: rgba(255, 255, 255, 1); }
        /* Next Landing VPN: CTA-sectie (#4f46e5 etc.): titel en subtitel altijd wit */
        #next-landing-vpn-cta .next-landing-vpn-cta-subtitle,
        #next-landing-vpn-cta h3 { color: #ffffff !important; }
        /* Dark mode: footertekst leesbaar houden */
        /* Header: onder 1025px alleen logo + thema-toggle + hamburger; alle menuitems + Mijn Nexa in uitklapmenu */
        @media (max-width: 1024px) {
            #website-desktop-nav,
            #website-desktop-right { display: none !important; }
            #website-hamburger-row { display: flex !important; }
            #website-mobile-menu-toggle-wrap { display: flex !important; }
            header:has(#website-mobile-menu) { position: sticky; }
            #website-mobile-menu {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                z-index: 50;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
                max-height: calc(100dvh - 4rem);
                overflow-y: auto;
            }
            @media (min-width: 768px) {
                #website-mobile-menu { max-height: calc(100dvh - 5rem); }
            }
        }
        @media (min-width: 1025px) {
            #website-hamburger-row,
            #website-mobile-menu,
            #website-mobile-menu-toggle-wrap { display: none !important; }
        }
        html.dark footer,
        html.dark footer p,
        html.dark footer a { color: #e5e7eb !important; }
        html.dark footer a:hover { color: #93c5fd !important; }
        html.dark footer h3 { color: #ffffff !important; }
        /* Footer: witte lijntjes grijs in dark mode */
        html.dark footer,
        html.dark footer .border-t { border-color: #4b5563 !important; }
        /* Scroll-to-top knop: rechtsonder, verschijnt bij scrollen */
        .scrollup {
            position: fixed;
            right: 20px;
            bottom: 24px;
            width: 40px;
            height: 40px;
            z-index: 9997;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--theme-primary, #2563eb);
            color: #fff;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.25s ease, visibility 0.25s ease, transform 0.25s ease, background-color 0.2s ease;
        }
        .scrollup:hover {
            filter: brightness(1.1);
        }
        .scrollup.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .scrollup.right {
            right: 84px;
            bottom: 24px;
        }
        @media (min-width: 768px) {
            .scrollup.right { right: 88px; bottom: 28px; }
        }
        body:not(:has(#frontend-whatsapp-widget)) .scrollup.right {
            right: 20px;
        }
        @media (min-width: 768px) {
            body:not(:has(#frontend-whatsapp-widget)) .scrollup.right { right: 24px; bottom: 28px; }
        }
        /* Footer: wrapper zonder blok-translate; losse animaties op kinderen */
        .site-footer-reveal.scroll-reveal-section .footer-reveal-soft {
            opacity: 1;
            transform: none;
        }
        @keyframes footer-slide-from-left {
            from { opacity: 0; transform: translateX(-36px); }
            to { opacity: 1; transform: translateX(0); }
        }
        /* Merk: rustig fade-in vanuit links */
        @keyframes footer-brand-fade-in-left {
            from { opacity: 0; transform: translateX(-32px); }
            to { opacity: 1; transform: translateX(0); }
        }
        /* Kaart: rustig fade-in vanuit onderen */
        @keyframes footer-map-fade-in-up {
            from { opacity: 0; transform: translateY(48px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .site-footer-reveal.scroll-reveal-section:not(.is-in-view) .footer-animate-brand {
            opacity: 0;
            transform: translateX(-32px);
        }
        .site-footer-reveal.scroll-reveal-section.is-in-view .footer-animate-brand {
            animation: footer-brand-fade-in-left 1.15s cubic-bezier(0.22, 0.08, 0.2, 1) both;
        }
        .site-footer-reveal.scroll-reveal-section:not(.is-in-view) .footer-animate-tagline {
            opacity: 0;
            transform: translateX(-36px);
        }
        .site-footer-reveal.scroll-reveal-section.is-in-view .footer-animate-tagline {
            animation: footer-slide-from-left 0.62s cubic-bezier(0.22, 1, 0.36, 1) both;
            animation-delay: 0.78s;
        }
        .site-footer-reveal.scroll-reveal-section:not(.is-in-view) .footer-footer-anim-left {
            opacity: 0;
            transform: translateX(-36px);
        }
        .site-footer-reveal.scroll-reveal-section.is-in-view .footer-footer-anim-left {
            animation: footer-slide-from-left 0.48s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .site-footer-reveal.scroll-reveal-section .footer-map-reveal {
            transform-origin: center center;
            will-change: transform, opacity;
        }
        .site-footer-reveal.scroll-reveal-section:not(.is-in-view) .footer-map-reveal {
            opacity: 0;
            transform: translateY(48px);
        }
        .site-footer-reveal.scroll-reveal-section.is-in-view .footer-map-reveal {
            animation: footer-map-fade-in-up 1.2s cubic-bezier(0.22, 0.08, 0.2, 1) both;
        }
        @media (prefers-reduced-motion: reduce) {
            .site-footer-reveal.scroll-reveal-section .footer-animate-brand,
            .site-footer-reveal.scroll-reveal-section .footer-animate-tagline,
            .site-footer-reveal.scroll-reveal-section .footer-footer-anim-left,
            .site-footer-reveal.scroll-reveal-section .footer-map-reveal {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
                transition: none !important;
            }
        }
    </style>
    <!-- Dark mode: direct op html zetten vóór first paint (voorkomt witte flits) -->
    <script>
    (function(){
      var el = document.documentElement;
      var storageKey = @json($websiteThemeStorageKey);
      var stored = localStorage.getItem(storageKey);
      if (stored !== 'dark' && stored !== 'light') {
        stored = localStorage.getItem('website-theme') || localStorage.getItem('theme');
      }
      if (stored === 'dark') { el.classList.add('dark'); }
      else if (stored === 'light') { el.classList.remove('dark'); }
      else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) { el.classList.add('dark'); }
      else { el.classList.remove('dark'); }
    })();
    </script>
    <script>
    (function() {
        function parseRootMarginBottomPx(rootMargin) {
            if (!rootMargin || typeof rootMargin !== 'string') return 0;
            var parts = rootMargin.trim().split(/\s+/);
            var bottom = parts.length >= 3 ? parts[2] : parts[0];
            var vh = window.innerHeight || document.documentElement.clientHeight || 0;
            if (String(bottom).indexOf('%') !== -1) {
                return vh * ((parseFloat(bottom, 10) || 0) / 100);
            }
            return parseFloat(bottom, 10) || 0;
        }
        function isRoughlyInViewport(el, rootMargin) {
            if (!el || !el.getBoundingClientRect) return false;
            var rect = el.getBoundingClientRect();
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var vw = window.innerWidth || document.documentElement.clientWidth;
            var bottomMargin = parseRootMarginBottomPx(rootMargin);
            var visibleBottom = bottomMargin >= 0 ? vh + bottomMargin : vh + bottomMargin;
            return rect.bottom > 0 && rect.top < visibleBottom && rect.right > 0 && rect.left < vw;
        }
        window.nexaObserveWhenVisible = function(targets, callback, options) {
            options = options || {};
            var list = typeof targets === 'string'
                ? Array.prototype.slice.call(document.querySelectorAll(targets))
                : (targets && targets.length !== undefined ? Array.prototype.slice.call(targets) : (targets ? [targets] : []));
            if (!list.length) return null;
            var rootMargin = options.rootMargin || '0px';
            var threshold = options.threshold !== undefined ? options.threshold : 0;
            var once = options.once !== false;
            function run(el) {
                if (once && el.getAttribute('data-nexa-visible-fired') === '1') return;
                if (once) el.setAttribute('data-nexa-visible-fired', '1');
                callback(el);
            }
            if (!('IntersectionObserver' in window)) {
                list.forEach(run);
                return null;
            }
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        run(entry.target);
                        if (once) observer.unobserve(entry.target);
                    }
                });
            }, { root: options.root || null, rootMargin: rootMargin, threshold: threshold });
            function checkAll() {
                list.forEach(function(el) {
                    if (isRoughlyInViewport(el, rootMargin)) run(el);
                });
            }
            list.forEach(function(el) { observer.observe(el); });
            checkAll();
            requestAnimationFrame(function() { requestAnimationFrame(checkAll); });
            window.addEventListener('load', checkAll, { once: true });
            return observer;
        };
    })();
    </script>
    @stack('styles')
</head>
<body class="min-h-screen antialiased flex flex-col theme-{{ $themeSlug ?? 'modern' }} bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100" style="font-family: var(--theme-font-body);">
@if(!empty($seoTracking['tag_manager_id']))
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $seoTracking['tag_manager_id'] }}" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
@endif
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded-lg z-50">Spring naar hoofdinhoud</a>
    @php
        $previewThemeSuffix = !empty($theme->name ?? null) ? ': thema: '.$theme->name : '';
        $hidePreviewChrome = request()->boolean('embed');
    @endphp
    @if(isset($isPreview) && $isPreview && isset($previewEditUrl) && ! $hidePreviewChrome)
    <div class="preview-bar sticky top-0 z-[100] flex min-h-11 w-full flex-nowrap items-center gap-2.5 px-3 py-1.5 sm:gap-3 sm:px-4 text-sm font-medium leading-snug text-white" role="banner" aria-label="Voorbeeldmodus">
        <a href="{{ $previewEditUrl }}" class="preview-bar-back shrink-0">
            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            Terug naar admin
        </a>
        <span class="min-w-0 flex-1 truncate text-center text-sm font-medium leading-snug">Dit is een voorbeeld met het gekozen thema{{ $previewThemeSuffix }}.</span>
    </div>
    @endif
    @if(isset($isPreview) && $isPreview && !empty($previewPageInactive))
    <div class="sticky {{ $hidePreviewChrome ? 'top-0' : 'top-11' }} z-[99] w-full border-b border-amber-300 bg-amber-50 px-3 py-2 text-center text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950/80 dark:text-amber-100 sm:px-4" role="status">
        Deze pagina staat op <strong>Inactief</strong>. Op de live website (inclusief &ldquo;Website openen (dev)&rdquo;) is hij niet zichtbaar totdat je <strong>Actief</strong> aanvinkt en opslaat.
    </div>
    @endif
    @if(!empty($isStaging) && !empty($stagingBackUrl))
    <div class="preview-bar sticky top-0 z-[100] flex min-h-11 w-full flex-nowrap items-center gap-2.5 px-3 py-1.5 sm:gap-3 sm:px-4 text-sm font-medium leading-snug text-white" data-staging-theme-id="{{ $theme->id ?? '' }}" data-staging-theme-slug="{{ $themeSlug ?? '' }}" role="banner" aria-label="Stagingmodus">
        <a href="{{ $stagingBackUrl }}" class="preview-bar-back shrink-0">
            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            Terug naar admin
        </a>
        <span class="min-w-0 flex-1 truncate text-center text-sm font-medium leading-snug">Dit is een voorbeeld met het gekozen thema{{ $previewThemeSuffix }}.</span>
    </div>
    @endif

    @php
        $hideWebsiteMenu = isset($page) && \App\Models\WebsitePage::isCentralMarketingWelcomeSlug($page->slug ?? null);
    @endphp
    <header class="bg-white dark:bg-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="container-custom">
            <div class="flex justify-between items-center h-16 md:h-20">
                <div class="flex items-center gap-2 flex-shrink-0">
                    @unless($hideWebsiteMenu)
                    <div id="website-mobile-menu-toggle-wrap" class="hidden flex-shrink-0">
                        <button type="button" id="website-mobile-menu-toggle" class="p-2 rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Menu openen">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                    </div>
                    @endunless
                    @php
                        $logoHref = route('home');
                        if (!empty($isStaging) && isset($stagingParams) && isset($menuPages) && $menuPages->isNotEmpty()) {
                            $first = $menuPages->first();
                            $pageParam = in_array($first->page_type, ['home','about','contact'], true) ? $first->page_type : $first->slug;
                            $logoHref = route('admin.frontend-themes.staging', array_merge($stagingParams, ['page' => $pageParam]));
                        }
                    @endphp
                    @include('frontend.layouts.partials.brand-logo', [
                        'branding' => $branding,
                        'logoHref' => $logoHref,
                        'logoHrefTenantAware' => empty($isStaging),
                    ])
                </div>
                {{-- Desktop: nav verborgen onder 1025px via CSS media query; dan hamburger --}}
                @unless($hideWebsiteMenu)
                <nav id="website-desktop-nav" class="flex flex-nowrap items-center gap-4 flex-1 justify-center px-4 min-w-0 overflow-hidden" role="navigation" aria-label="Hoofdnavigatie">
                    @forelse(($menuPages ?? collect()) as $menuPage)
                        @php
                            if (!empty($isStaging) && isset($stagingParams)) {
                                $pageParam = in_array($menuPage->page_type, ['home','about','contact'], true) ? $menuPage->page_type : $menuPage->slug;
                                $url = route('admin.frontend-themes.staging', array_merge($stagingParams, ['page' => $pageParam]));
                            } else {
                                $url = $menuPage->page_type === 'home' ? route('home') : route('website.page', ['slug' => $menuPage->slug]);
                            }
                            $isActive = isset($page) && $page->id === $menuPage->id;
                        @endphp
                        <a href="{{ $url }}" class="text-gray-900 dark:text-gray-100 hover:opacity-90 px-3 py-2 rounded-md text-base font-medium transition-colors {{ $isActive ? 'opacity-100 font-semibold' : '' }}" style="{{ $isActive ? 'color: var(--theme-primary);' : '' }}">{{ $menuPage->page_type === 'home' ? 'Home' : $menuPage->title }}</a>
                    @empty
                        {{-- Fallback als er geen menu-pagina's uit de database komen --}}
                        <a href="{{ route('home') }}" class="text-gray-900 dark:text-gray-100 hover:opacity-90 px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('home') && !request()->routeIs('home.*') ? 'opacity-100 font-semibold' : '' }}" style="{{ request()->routeIs('home') && !request()->routeIs('home.*') ? 'color: var(--theme-primary);' : '' }}">Home</a>
                        <a href="{{ route('about') }}" class="text-gray-900 dark:text-gray-100 hover:opacity-90 px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('about') ? 'opacity-100 font-semibold' : '' }}" style="{{ request()->routeIs('about') ? 'color: var(--theme-primary);' : '' }}">Over ons</a>
                    @endforelse
                    {{-- App-links (alleen wanneer Nexa Skillmatching actief is): Dashboard, Vacatures, Matches, Agenda --}}
                    @auth
                    @if($showSkillmatchingAppLinks ?? false)
                    <a href="{{ route('dashboard') }}" class="text-gray-900 dark:text-gray-100 hover:opacity-90 px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('dashboard') ? 'opacity-100 font-semibold' : '' }}" style="{{ request()->routeIs('dashboard') ? 'color: var(--theme-primary);' : '' }}">Dashboard</a>
                    <a href="{{ route('jobs.index') }}" class="text-gray-900 dark:text-gray-100 hover:opacity-90 px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('jobs.*') ? 'opacity-100 font-semibold' : '' }}" style="{{ request()->routeIs('jobs.*') ? 'color: var(--theme-primary);' : '' }}">Vacatures</a>
                    <a href="{{ route('matches') }}" class="text-gray-900 dark:text-gray-100 hover:opacity-90 px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('matches') ? 'opacity-100 font-semibold' : '' }}" style="{{ request()->routeIs('matches') ? 'color: var(--theme-primary);' : '' }}">Matches</a>
                    <a href="{{ route('agenda') }}" class="text-gray-900 dark:text-gray-100 hover:opacity-90 px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('agenda') ? 'opacity-100 font-semibold' : '' }}" style="{{ request()->routeIs('agenda') ? 'color: var(--theme-primary);' : '' }}">Agenda</a>
                    @endif
                    @endauth
                </nav>
                @endunless
                {{-- Rechterkant desktop: streep (border-l), thema-toggle + Mijn Nexa/Inloggen; verborgen onder 1025px --}}
                <div id="website-desktop-right" class="flex items-center gap-2 lg:gap-4 ml-auto flex-shrink-0 pl-4">
                    @if($themeSettings['dark_mode_available'] ?? true)
                    <span class="sr-only">Weergave</span>
                    <button type="button" id="theme-toggle-btn" class="p-2 rounded-md text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white" aria-label="Wissel licht/donker thema" title="Wissel thema">
                        <svg id="theme-icon-sun" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg id="theme-icon-moon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                    @endif
                    @if(\App\Models\GeneralSetting::get('ai_chat_enabled', '0') === '1')
                    @include('frontend.components.ai-chatbot-trigger')
                    @endif
                    @if($branding['dashboard_link_visible'] ?? false)
                    @php
                        $portalUrl = $branding['dashboard_link_url'] ?? route('dashboard');
                        $portalLabel = $branding['dashboard_link_label'] ?? 'Mijn Nexa';
                    @endphp
                    @guest
                    <a href="{{ route('login', ['intended' => $portalUrl]) }}" class="px-4 py-2 rounded-lg text-base font-medium text-white transition-colors shrink-0 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400">{{ $portalLabel }}</a>
                    @else
                    <a href="{{ $portalUrl }}" class="px-4 py-2 rounded-lg text-base font-medium text-white transition-colors shrink-0 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400">{{ $portalLabel }}</a>
                    @endguest
                    @endif
                </div>
                {{-- Smalle viewport: desktop-kolom met Mijn Nexa is verborgen; knop hier tonen zodat hij niet alleen in het dichte hamburgerpaneel zit --}}
                <div id="website-hamburger-row" class="hidden items-center gap-2 ml-auto flex-shrink-0">
                    @if($branding['dashboard_link_visible'] ?? false)
                    @php
                        $portalUrlMobile = $branding['dashboard_link_url'] ?? route('dashboard');
                        $portalLabelMobile = $branding['dashboard_link_label'] ?? 'Mijn Nexa';
                    @endphp
                    @guest
                    <a href="{{ route('login', ['intended' => $portalUrlMobile]) }}" class="px-3 py-1.5 rounded-lg text-sm font-medium text-white shrink-0 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400 sm:px-4 sm:py-2 sm:text-base">{{ $portalLabelMobile }}</a>
                    @else
                    <a href="{{ $portalUrlMobile }}" class="px-3 py-1.5 rounded-lg text-sm font-medium text-white shrink-0 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400 sm:px-4 sm:py-2 sm:text-base">{{ $portalLabelMobile }}</a>
                    @endguest
                    @endif
                    @if($themeSettings['dark_mode_available'] ?? true)
                    <button type="button" id="theme-toggle-btn-mobile" class="p-2 rounded-lg text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-white" aria-label="Wissel thema">
                        <svg id="theme-icon-sun-mobile" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg id="theme-icon-moon-mobile" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                    @endif
                    @if(\App\Models\GeneralSetting::get('ai_chat_enabled', '0') === '1')
                    @include('frontend.components.ai-chatbot-trigger')
                    @endif
                </div>
            </div>
        </div>
        @unless($hideWebsiteMenu)
        <div id="website-mobile-menu" class="hidden border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="container-custom py-4 space-y-1">
                @forelse(($menuPages ?? collect()) as $menuPage)
                    @php
                        if (!empty($isStaging) && isset($stagingParams)) {
                            $pageParam = in_array($menuPage->page_type, ['home','about','contact'], true) ? $menuPage->page_type : $menuPage->slug;
                            $url = route('admin.frontend-themes.staging', array_merge($stagingParams, ['page' => $pageParam]));
                        } else {
                            $url = $menuPage->page_type === 'home' ? route('home') : route('website.page', ['slug' => $menuPage->slug]);
                        }
                    @endphp
                    <a href="{{ $url }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">{{ $menuPage->page_type === 'home' ? 'Home' : $menuPage->title }}</a>
                @empty
                    <a href="{{ route('home') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">Home</a>
                    <a href="{{ route('about') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">Over ons</a>
                @endforelse
                @auth
                @if($showSkillmatchingAppLinks ?? false)
                <a href="{{ route('dashboard') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">Dashboard</a>
                <a href="{{ route('jobs.index') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">Vacatures</a>
                <a href="{{ route('matches') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">Matches</a>
                <a href="{{ route('agenda') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">Agenda</a>
                @endif
                @endauth
                {{-- Mijn Nexa / Inloggen: zie #website-hamburger-row (smalle viewport) en #website-desktop-right (breed) --}}
            </div>
        </div>
        @endunless
    </header>

    <main id="main-content" class="flex-1 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        @yield('content')
    </main>

    @include('frontend.layouts.partials.website-footer', [
        'homeSections' => $homeSections,
        'branding' => $branding,
        'themeSettings' => $themeSettings ?? [],
    ])


    @php
        // WhatsApp-widget uitsluitend op basis van de (tenant-)instelling (general_settings), niet uit .env.
        // De controller geeft $whatsappWidget door; voor pagina's die deze layout zonder die variabele gebruiken
        // valt de widget terug op "uit" (geen .env-afhankelijkheid).
        $whatsappWidget = $whatsappWidget ?? ['enabled' => false, 'phone' => '', 'message' => ''];
        $whatsappWidgetEnabled = (bool) ($whatsappWidget['enabled'] ?? false);
        $whatsappWidgetPhoneDigits = preg_replace('/\D+/', '', (string) ($whatsappWidget['phone'] ?? ''));
        $whatsappWidgetMessage = trim((string) ($whatsappWidget['message'] ?? ''));
        if ($whatsappWidgetMessage === '') {
            $whatsappWidgetMessage = 'Hallo, ik heb een vraag over jullie diensten.';
        }
    @endphp
    @if($whatsappWidgetEnabled && !empty($whatsappWidgetPhoneDigits))
        <div id="frontend-whatsapp-widget"
             class="pointer-events-auto"
             style="position: fixed; right: 20px; bottom: 20px; z-index: 100100;">
            <div id="frontend-whatsapp-widget-menu"
                 class="absolute right-0 flex flex-col items-end gap-3"
                 style="display: none; bottom: 76px;">
                <a href="tel:{{ $whatsappWidgetPhoneDigits }}"
                   title="Bellen"
                   aria-label="Bellen"
                   class="inline-flex h-14 w-14 items-center justify-center text-white shadow-xl hover:brightness-110 transition-all"
                   style="background-color: #25D366; border-radius: 9999px;">
                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3.5 5.2c0-.94.76-1.7 1.7-1.7h2.05c.8 0 1.5.56 1.67 1.35l.56 2.6c.12.54-.05 1.1-.45 1.48l-1.04.98a13.52 13.52 0 0 0 5.1 5.1l.98-1.04c.38-.4.94-.57 1.48-.45l2.6.56c.79.17 1.35.87 1.35 1.67v2.05c0 .94-.76 1.7-1.7 1.7h-.85C9.65 19.6 3.5 13.45 3.5 5.2v0Z" fill="currentColor"/>
                    </svg>
                </a>
                <a href="https://wa.me/{{ $whatsappWidgetPhoneDigits }}?text={{ urlencode($whatsappWidgetMessage) }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   title="Bericht sturen"
                   aria-label="Bericht sturen"
                   class="inline-flex h-14 w-14 items-center justify-center text-white shadow-xl hover:brightness-110 transition-all"
                   style="background-color: #25D366; border-radius: 9999px;">
                    <span class="pointer-events-none inline-flex h-9 w-9 items-center justify-center" aria-hidden="true">
                        <svg class="h-9 w-9 max-h-full max-w-full" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.881 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                    </span>
                </a>
            </div>
            <button type="button"
                    id="frontend-whatsapp-widget-toggle"
                    aria-label="Open WhatsApp opties"
                    aria-expanded="false"
                    class="relative z-[1] inline-flex h-14 w-14 cursor-pointer touch-manipulation items-center justify-center rounded-full bg-[#25D366] text-white shadow-xl hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#25D366] transition-all">
                <span id="frontend-whatsapp-widget-icon-open" class="inline-flex" style="display: inline-flex;">
                    <span class="pointer-events-none inline-flex h-9 w-9 items-center justify-center" aria-hidden="true">
                        <svg class="h-9 w-9 max-h-full max-w-full" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.881 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                    </span>
                </span>
                <span id="frontend-whatsapp-widget-icon-close" class="inline-flex" style="display: none;">
                    <svg class="pointer-events-none h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
                    </svg>
                </span>
            </button>
        </div>
    @endif

    <button type="button"
            id="scrollup-btn"
            class="scrollup right"
            aria-label="Naar boven scrollen"
            title="Naar boven">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>

    <button type="button"
            id="cookie-settings-btn"
            class="fixed left-4 bottom-4 z-[9998] rounded-full border border-gray-300 bg-white/95 px-4 py-2 text-xs font-medium text-gray-700 shadow-md backdrop-blur hover:bg-white dark:border-gray-600 dark:bg-gray-800/95 dark:text-gray-200 dark:hover:bg-gray-800">
        Cookie-instellingen
    </button>

    <script src="https://cdn.jsdelivr.net/npm/vanilla-cookieconsent@3.1.0/dist/cookieconsent.umd.js"></script>

    <script>
        (function() {
            if (typeof window.CookieConsent === 'undefined') return;

            window.CookieConsent.run({
                guiOptions: {
                    consentModal: {
                        layout: 'box',
                        position: 'bottom right',
                        equalWeightButtons: true,
                        flipButtons: false
                    },
                    preferencesModal: {
                        layout: 'box',
                        position: 'right',
                        equalWeightButtons: true,
                        flipButtons: false
                    }
                },
                categories: {
                    necessary: {
                        enabled: true,
                        readOnly: true
                    },
                    analytics: {
                        enabled: false
                    },
                    marketing: {
                        enabled: false
                    }
                },
                language: {
                    default: 'nl',
                    translations: {
                        nl: {
                            consentModal: {
                                title: 'Wij gebruiken cookies',
                                description: 'We gebruiken noodzakelijke cookies om de site goed te laten werken. Met jouw toestemming gebruiken we ook analytics en marketing cookies.',
                                acceptAllBtn: 'Alles accepteren',
                                acceptNecessaryBtn: 'Alleen noodzakelijk',
                                showPreferencesBtn: 'Voorkeuren beheren',
                                footer: '<a href="{{ route('privacy') }}">Privacybeleid</a>'
                            },
                            preferencesModal: {
                                title: 'Cookievoorkeuren',
                                acceptAllBtn: 'Alles accepteren',
                                acceptNecessaryBtn: 'Alleen noodzakelijk',
                                savePreferencesBtn: 'Voorkeuren opslaan',
                                closeIconLabel: 'Sluiten',
                                sections: [
                                    {
                                        title: 'Cookiegebruik',
                                        description: 'Kies per categorie welke cookies je wilt toestaan.'
                                    },
                                    {
                                        title: 'Noodzakelijk',
                                        description: 'Deze cookies zijn nodig voor basisfunctionaliteit en kunnen niet worden uitgeschakeld.',
                                        linkedCategory: 'necessary'
                                    },
                                    {
                                        title: 'Analytics',
                                        description: 'Helpt ons het gebruik van de website te meten en verbeteren.',
                                        linkedCategory: 'analytics'
                                    },
                                    {
                                        title: 'Marketing',
                                        description: 'Wordt gebruikt voor marketing en personalisatie.',
                                        linkedCategory: 'marketing'
                                    }
                                ]
                            }
                        }
                    }
                },
                onConsent: function() {
                    var btn = document.getElementById('cookie-settings-btn');
                    if (btn) btn.style.display = 'none';
                    window.dispatchEvent(new CustomEvent('cookie-consent-updated', {
                        detail: {
                            analytics: window.CookieConsent.acceptedCategory('analytics'),
                            marketing: window.CookieConsent.acceptedCategory('marketing')
                        }
                    }));
                },
                onChange: function() {
                    var btn = document.getElementById('cookie-settings-btn');
                    if (btn) btn.style.display = 'none';
                    window.dispatchEvent(new CustomEvent('cookie-consent-updated', {
                        detail: {
                            analytics: window.CookieConsent.acceptedCategory('analytics'),
                            marketing: window.CookieConsent.acceptedCategory('marketing')
                        }
                    }));
                }
            });

            var cookieSettingsBtn = document.getElementById('cookie-settings-btn');
            if (cookieSettingsBtn) {
                cookieSettingsBtn.addEventListener('click', function() {
                    if (window.CookieConsent && typeof window.CookieConsent.showPreferences === 'function') {
                        window.CookieConsent.showPreferences();
                    }
                });
                try {
                    var hasStored = localStorage.getItem('cc_cookie') || (document.cookie.indexOf('cc_cookie=') !== -1);
                    if (hasStored) cookieSettingsBtn.style.display = 'none';
                } catch (e) {}
            }
        })();

        (function() {
            var toggle = document.getElementById('website-mobile-menu-toggle');
            var menu = document.getElementById('website-mobile-menu');
            if (toggle && menu) {
                toggle.addEventListener('click', function() { menu.classList.toggle('hidden'); });
                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 1025) menu.classList.add('hidden');
                });
            }
        })();

        (function() {
            var html = document.documentElement;
            var storageKey = @json($websiteThemeStorageKey);
            function getStored() {
                try {
                    var scoped = localStorage.getItem(storageKey);
                    if (scoped === 'dark' || scoped === 'light') {
                        return scoped;
                    }
                    return localStorage.getItem('website-theme') || localStorage.getItem('theme');
                } catch (e) { return null; }
            }
            function setStored(v) {
                try {
                    localStorage.setItem(storageKey, v);
                } catch (e) {}
            }
            function applyTheme(isDark) {
                if (isDark) {
                    html.classList.add('dark');
                } else {
                    html.classList.remove('dark');
                }
                syncFeLogos();
            }
            function isDark() { return html.classList.contains('dark'); }
            function syncFeLogos() {
                var dark = isDark();
                document.querySelectorAll('.fe-logo-light').forEach(function(el) { el.style.setProperty('display', dark ? 'none' : 'block', 'important'); });
                document.querySelectorAll('.fe-logo-dark').forEach(function(el) { el.style.setProperty('display', dark ? 'block' : 'none', 'important'); });
            }
            function updateIcons() {
                var dark = isDark();
                var suns = document.querySelectorAll('#theme-icon-sun, #theme-icon-sun-mobile');
                var moons = document.querySelectorAll('#theme-icon-moon, #theme-icon-moon-mobile');
                suns.forEach(function(el) { el.style.display = dark ? 'block' : 'none'; });
                moons.forEach(function(el) { el.style.display = dark ? 'none' : 'block'; });
            }
            function toggleTheme() {
                var next = !isDark();
                setStored(next ? 'dark' : 'light');
                applyTheme(next);
                updateIcons();
                document.dispatchEvent(new CustomEvent('nexataxi-website-theme-changed'));
            }
            function initTheme() {
                var stored = getStored();
                if (stored === 'dark') { applyTheme(true); }
                else if (stored === 'light') { applyTheme(false); }
                else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) { applyTheme(true); }
                else { applyTheme(false); }
                updateIcons();
                syncFeLogos();
            }
            function init() {
                initTheme();
                document.addEventListener('click', function(e) {
                    if (e.target.closest('#theme-toggle-btn') || e.target.closest('#theme-toggle-btn-mobile')) {
                        e.preventDefault();
                        e.stopPropagation();
                        toggleTheme();
                    }
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();

        // Carousel (Flowbite-style): prev/next, indicators, optional autoplay
        function initCarousels() {
            var carousels = document.querySelectorAll('[data-carousel="slide"], [data-carousel="static"]');
            carousels.forEach(function(root) {
                var wrapper = root.querySelector('.relative.overflow-hidden');
                if (!wrapper) wrapper = root.querySelector('[class*="overflow-hidden"]') || root.children[0];
                var items = root.querySelectorAll('[data-carousel-item]');
                if (!items.length) return;
                var indicators = root.querySelectorAll('[data-carousel-slide-to]');
                var prevBtn = root.querySelector('[data-carousel-prev]');
                var nextBtn = root.querySelector('[data-carousel-next]');
                var current = 0;
                var isSlide = root.getAttribute('data-carousel') === 'slide';
                var interval = null;
                var intervalMs = null;
                var intervalSecAttr = root.getAttribute('data-carousel-interval');
                if (intervalSecAttr !== null && intervalSecAttr !== '') {
                    var intervalSec = parseInt(intervalSecAttr, 10);
                    if (!isNaN(intervalSec) && intervalSec > 0) {
                        intervalMs = intervalSec * 1000;
                    }
                } else if (isSlide) {
                    intervalMs = 5000;
                }

                function restartAutoplay() {
                    if (interval) clearInterval(interval);
                    interval = null;
                    if (isSlide && intervalMs) interval = setInterval(next, intervalMs);
                }

                function playCarouselCaption(slideEl) {
                    root.querySelectorAll('[data-carousel-caption]').forEach(function(c) {
                        c.classList.remove('is-visible');
                    });
                    if (!slideEl) return;
                    var cap = slideEl.querySelector('[data-carousel-caption]');
                    if (!cap) return;
                    requestAnimationFrame(function() {
                        requestAnimationFrame(function() {
                            cap.classList.add('is-visible');
                        });
                    });
                }

                function show(pos) {
                    var n = items.length;
                    var nextIndex = (pos % n + n) % n;
                    var prevIndex = current;
                    if (nextIndex === prevIndex && items[prevIndex] && items[prevIndex].classList.contains('opacity-100')) {
                        indicators.forEach(function(btn, i) {
                            btn.setAttribute('aria-current', i === current ? 'true' : 'false');
                            btn.style.background = i === current ? '#ffffff' : '#9ca3af';
                        });
                        playCarouselCaption(items[nextIndex]);
                        return;
                    }
                    current = nextIndex;
                    items.forEach(function(el, i) {
                        if (i === current) {
                            el.classList.remove('opacity-0', 'pointer-events-none', 'z-0');
                            el.classList.add('opacity-100', 'z-20');
                            el.setAttribute('data-carousel-item', 'active');
                        } else if (i === prevIndex) {
                            el.classList.remove('opacity-100', 'z-20', 'z-0');
                            el.classList.add('opacity-0', 'z-10', 'pointer-events-none');
                            el.setAttribute('data-carousel-item', '');
                            (function(outgoing) {
                                function onFadeEnd(e) {
                                    if (e.propertyName !== 'opacity') return;
                                    outgoing.classList.remove('z-10');
                                    outgoing.classList.add('z-0');
                                    outgoing.removeEventListener('transitionend', onFadeEnd);
                                }
                                outgoing.addEventListener('transitionend', onFadeEnd);
                            })(el);
                        } else {
                            el.classList.remove('opacity-100', 'z-20', 'z-10');
                            el.classList.add('opacity-0', 'z-0', 'pointer-events-none');
                            el.setAttribute('data-carousel-item', '');
                        }
                    });
                    indicators.forEach(function(btn, i) {
                        btn.setAttribute('aria-current', i === current ? 'true' : 'false');
                        btn.style.background = i === current ? '#ffffff' : '#9ca3af';
                    });
                    playCarouselCaption(items[current]);
                }

                function next() { show(current + 1); }
                function prev() { show(current - 1); }

                if (prevBtn) prevBtn.addEventListener('click', function() { prev(); restartAutoplay(); });
                if (nextBtn) nextBtn.addEventListener('click', function() { next(); restartAutoplay(); });
                indicators.forEach(function(btn, i) {
                    btn.addEventListener('click', function() { show(i); restartAutoplay(); });
                });

                show(0);
                restartAutoplay();
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCarousels);
        } else {
            initCarousels();
        }

        (function() {
            var widget = document.getElementById('frontend-whatsapp-widget');
            var toggle = document.getElementById('frontend-whatsapp-widget-toggle');
            var menu = document.getElementById('frontend-whatsapp-widget-menu');
            var openIcon = document.getElementById('frontend-whatsapp-widget-icon-open');
            var closeIcon = document.getElementById('frontend-whatsapp-widget-icon-close');
            if (!widget || !toggle || !menu || !openIcon || !closeIcon) return;

            function closeMenu() {
                menu.style.display = 'none';
                toggle.setAttribute('aria-expanded', 'false');
                openIcon.style.display = 'inline-flex';
                closeIcon.style.display = 'none';
                toggle.style.backgroundColor = '#25D366';
            }

            function openMenu() {
                menu.style.display = 'flex';
                toggle.setAttribute('aria-expanded', 'true');
                openIcon.style.display = 'none';
                closeIcon.style.display = 'inline-flex';
                toggle.style.backgroundColor = '#ff6b6b';
            }

            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (menu.style.display === 'none' || menu.style.display === '') {
                    openMenu();
                } else {
                    closeMenu();
                }
            });

            document.addEventListener('click', function(e) {
                if (!widget.contains(e.target)) {
                    closeMenu();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeMenu();
                }
            });

            closeMenu();
        })();

        (function() {
            var btn = document.getElementById('scrollup-btn');
            if (!btn) return;
            var scrollThreshold = 280;
            function updateVisibility() {
                if (window.scrollY > scrollThreshold) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            }
            function scrollToTop() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            window.addEventListener('scroll', function() {
                updateVisibility();
            }, { passive: true });
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                scrollToTop();
            });
            updateVisibility();
        })();
    </script>
    @if(\App\Models\GeneralSetting::get('ai_chat_enabled', '0') === '1')
        @include('frontend.layouts.partials.ai-chatbot-include')
    @endif
    @stack('scripts')
    <script>
    (function() {
        function initScrollRevealSections() {
            var sections = document.querySelectorAll('[data-scroll-reveal]');
            if (!sections.length) return;
            var opts = { rootMargin: '0px 0px 22% 0px', threshold: 0.04 };
            function onSectionInView(el) {
                el.classList.add('is-in-view');
                if (el.classList.contains('site-footer-reveal') && typeof window.resizeFooterMap === 'function') {
                    setTimeout(window.resizeFooterMap, 80);
                    setTimeout(window.resizeFooterMap, 350);
                    setTimeout(window.resizeFooterMap, 900);
                }
            }
            if (typeof window.nexaObserveWhenVisible === 'function') {
                window.nexaObserveWhenVisible(sections, onSectionInView, opts);
                return;
            }
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) onSectionInView(entry.target);
                });
            }, opts);
            sections.forEach(function(el) { observer.observe(el); });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initScrollRevealSections);
        } else {
            initScrollRevealSections();
        }
        window.addEventListener('load', function() {
            setTimeout(function() {
                if (typeof window.resizeFooterMap === 'function') window.resizeFooterMap();
            }, 600);
        });
    })();
    </script>

</body>
</html>
