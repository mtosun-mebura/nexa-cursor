<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $branding['site_name'] ?? config('app.name'))</title>
    <meta name="description" content="@yield('description', $branding['site_description'] ?? '')">
    <meta property="og:site_name" content="{{ $branding['site_name'] ?? config('app.name') }}">
    <meta property="og:description" content="@yield('description', $branding['site_description'] ?? '')">
    @if(!empty($branding['favicon_url']))
    <link rel="icon" href="{{ $branding['favicon_url'] }}">
    <link rel="shortcut icon" href="{{ $branding['favicon_url'] }}">
    @else
    <link rel="icon" type="image/png" href="{{ asset('images/nexa-x-logo.png') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
    @vite(['resources/css/app.css', 'resources/js/frontend-app.js'])
    <style>
        /* Eén grootte voor alle paginatitels (h1) */
        .kt-page-title { font-size: 1.875rem; font-weight: 700; line-height: 1.2; }
        @media (min-width: 768px) { .kt-page-title { font-size: 2.25rem; } }
        /* Stagingbalk: tekst zwart, donkere achtergrond in dark mode */
        .staging-bar { color: #0f172a; }
        .staging-bar a { color: #0f172a; }
        .staging-bar a:hover { color: #1e293b; }
        .staging-bar button { color: #0f172a; }
        html.dark .staging-bar {
            background-color: #b45309 !important;
            color: #fffbeb;
            border-bottom-color: rgba(180, 83, 9, 0.5);
        }
        html.dark .staging-bar a { color: #fef3c7; }
        html.dark .staging-bar a:hover { color: #fff; }
        html.dark .staging-bar button {
            background: rgba(255,255,255,0.15);
            color: #fffbeb;
            border-color: rgba(254, 243, 199, 0.3);
        }
        html.dark .staging-bar button:hover { background: rgba(255,255,255,0.25); }
        /* Logo light/dark op frontend: toon juiste logo volgens thema */
        .fe-logo-light { display: block !important; }
        .fe-logo-dark { display: none !important; }
        html.dark .fe-logo-light { display: none !important; }
        html.dark .fe-logo-dark { display: block !important; }
        /* Previewbalk: zachtere, minder felle kleur in light en dark mode */
        .preview-bar {
            background-color: #9a3412 !important;
            color: #fff7ed;
            border-bottom: 1px solid rgba(255, 237, 213, 0.24);
        }
        .preview-bar a { color: #fff7ed; }
        .preview-bar a:hover { color: #fff; }
        html.dark .preview-bar {
            background-color: #7c2d12 !important;
            color: #fff7ed;
            border-bottom: 1px solid rgba(255, 237, 213, 0.2);
        }
        html.dark .preview-bar a { color: #fff7ed; }
        html.dark .preview-bar a:hover { color: #fff; }
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
        }
        @media (min-width: 1025px) {
            #website-hamburger-row,
            #website-mobile-menu { display: none !important; }
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
    </style>
    <!-- Dark mode: direct op html zetten vóór first paint (voorkomt witte flits) -->
    <script>
    (function(){
      var el = document.documentElement;
      var stored = localStorage.getItem('website-theme') || localStorage.getItem('theme');
      if (stored === 'dark') { el.classList.add('dark'); }
      else if (stored === 'light') { el.classList.remove('dark'); }
      else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) { el.classList.add('dark'); }
      else { el.classList.remove('dark'); }
    })();
    </script>
    @stack('styles')
</head>
<body class="min-h-screen antialiased flex flex-col theme-{{ $themeSlug ?? 'modern' }} bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100" style="font-family: var(--theme-font-body);">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded-lg z-50">Spring naar hoofdinhoud</a>
    @if(isset($isPreview) && $isPreview && isset($previewEditUrl))
    <div class="preview-bar sticky top-0 z-[100] flex items-center justify-center gap-4 py-2 px-4 text-sm font-medium">
        <span>Dit is een voorbeeld met het gekozen thema.</span>
        <a href="{{ $previewEditUrl }}" class="underline hover:no-underline font-semibold">Terug naar bewerken</a>
    </div>
    @endif
    @if(!empty($isStaging) && !empty($stagingBackUrl))
    <div class="staging-bar sticky top-0 z-[100] flex flex-wrap items-center justify-center gap-4 py-2 px-4 text-sm font-medium bg-amber-600 border-b border-amber-500/30" data-staging-theme-id="{{ $theme->id ?? '' }}" data-staging-theme-slug="{{ $themeSlug ?? '' }}">
        <span>Staging — Thema: {{ $theme->name ?? '—' }} (id: {{ $theme->id ?? '—' }}){{ !empty($stagingModule) ? ' · Module: ' . $stagingModule : '' }}</span>
        <a href="{{ $stagingBackUrl }}" class="underline hover:no-underline font-semibold">Terug naar thema's</a>
        <form action="{{ $stagingPublishUrl ?? '' }}" method="POST" class="inline">
            @csrf
            <input type="hidden" name="theme_id" value="{{ $stagingThemeId ?? '' }}">
            <button type="submit" class="px-3 py-1 rounded bg-white/20 hover:bg-white/30 font-semibold border border-white/30">Publiceren</button>
        </form>
    </div>
    @endif

    <header class="bg-white dark:bg-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="container-custom">
            <div class="flex justify-between items-center h-16 md:h-20">
                <div class="flex items-center gap-2 flex-shrink-0">
                    @php
                        $logoHref = route('home');
                        if (!empty($isStaging) && isset($stagingParams) && isset($menuPages) && $menuPages->isNotEmpty()) {
                            $first = $menuPages->first();
                            $pageParam = in_array($first->page_type, ['home','about','contact'], true) ? $first->page_type : $first->slug;
                            $logoHref = route('admin.frontend-themes.staging', array_merge($stagingParams, ['page' => $pageParam]));
                        }
                    @endphp
                    <a href="{{ $logoHref }}" class="flex items-center" aria-label="{{ $branding['site_name'] ?? 'Home' }}">
                        @if(!empty($branding['logo_url']))
                            @if(!empty($branding['logo_dark_url']))
                                <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['site_name'] ?? '' }}" class="fe-logo-light h-10 md:h-12 w-auto object-contain">
                                <img src="{{ $branding['logo_dark_url'] }}" alt="{{ $branding['site_name'] ?? '' }}" class="fe-logo-dark h-10 md:h-12 w-auto object-contain">
                            @else
                                <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['site_name'] ?? '' }}" class="h-10 md:h-12 w-auto">
                            @endif
                        @else
                            <span class="text-xl font-bold" style="color: var(--theme-primary);">{{ $branding['site_name'] ?? config('app.name') }}</span>
                        @endif
                    </a>
                </div>
                {{-- Desktop: nav verborgen onder 1025px via CSS media query; dan hamburger --}}
                <nav id="website-desktop-nav" class="flex flex-nowrap items-center gap-4 flex-1 justify-center px-4 min-w-0 overflow-hidden" role="navigation" aria-label="Hoofdnavigatie">
                    @forelse(($menuPages ?? collect()) as $menuPage)
                        @php
                            if (!empty($isStaging) && isset($stagingParams)) {
                                $pageParam = in_array($menuPage->page_type, ['home','about','contact'], true) ? $menuPage->page_type : $menuPage->slug;
                                $url = route('admin.frontend-themes.staging', array_merge($stagingParams, ['page' => $pageParam]));
                            } else {
                                $url = match($menuPage->page_type) {
                                    'home' => route('home'),
                                    'about' => route('about'),
                                    'contact' => route('contact'),
                                    default => route('website.page', ['slug' => $menuPage->slug]),
                                };
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
                {{-- Rechterkant desktop: streep (border-l), thema-toggle + Mijn Nexa/Inloggen; verborgen onder 1025px --}}
                <div id="website-desktop-right" class="flex items-center gap-2 lg:gap-4 ml-auto flex-shrink-0 pl-4">
                    @if($themeSettings['dark_mode_available'] ?? true)
                    <span class="sr-only">Weergave</span>
                    <button type="button" id="theme-toggle-btn" class="p-2 rounded-md text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white" aria-label="Wissel licht/donker thema" title="Wissel thema">
                        <svg id="theme-icon-sun" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg id="theme-icon-moon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                    @endif
                    @guest
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-base font-medium text-white transition-colors shrink-0" style="background-color: var(--theme-primary);">Inloggen</a>
                    @else
                    @if($branding['dashboard_link_visible'] ?? true)
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg text-base font-medium transition-colors shrink-0 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">{{ $branding['dashboard_link_label'] ?? 'Mijn Nexa' }}</a>
                    @endif
                    @endguest
                </div>
                {{-- Hamburger rechtsboven: rechts van thema-icoon; zichtbaar onder 1025px (via CSS media query) --}}
                <div id="website-hamburger-row" class="hidden items-center gap-2 ml-auto flex-shrink-0">
                    @if($themeSettings['dark_mode_available'] ?? true)
                    <button type="button" id="theme-toggle-btn-mobile" class="p-2 rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Wissel thema">
                        <svg id="theme-icon-sun-mobile" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg id="theme-icon-moon-mobile" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                    @endif
                    <button type="button" id="website-mobile-menu-toggle" class="p-2 rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Menu openen">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                </div>
            </div>
        </div>
        <div id="website-mobile-menu" class="hidden border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="container-custom py-4 space-y-1">
                @forelse(($menuPages ?? collect()) as $menuPage)
                    @php
                        if (!empty($isStaging) && isset($stagingParams)) {
                            $pageParam = in_array($menuPage->page_type, ['home','about','contact'], true) ? $menuPage->page_type : $menuPage->slug;
                            $url = route('admin.frontend-themes.staging', array_merge($stagingParams, ['page' => $pageParam]));
                        } else {
                            $url = match($menuPage->page_type) {
                                'home' => route('home'),
                                'about' => route('about'),
                                'contact' => route('contact'),
                                default => route('website.page', ['slug' => $menuPage->slug]),
                            };
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
                {{-- Streep boven Mijn Nexa/Inloggen als onderscheid met normale menuitems --}}
                <div class="border-t border-gray-200 dark:border-gray-700 mt-4 pt-4">
                    @guest
                    <a href="{{ route('login') }}" class="block px-4 py-3 rounded-lg font-medium text-white" style="background-color: var(--theme-primary);">Inloggen</a>
                    @else
                    @if($branding['dashboard_link_visible'] ?? true)
                    <a href="{{ route('dashboard') }}" class="block px-4 py-3 rounded-lg font-medium text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">{{ $branding['dashboard_link_label'] ?? 'Mijn Nexa' }}</a>
                    @endif
                    @endguest
                </div>
            </div>
        </div>
    </header>

    <main id="main-content" class="flex-1 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        @yield('content')
    </main>

    <footer class="{{ !empty($homeSections) ? 'bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300' }} border-t border-gray-200 dark:border-gray-600">
        @if(!empty($homeSections) && ($homeSections['visibility']['footer'] ?? true) && (!empty($homeSections['footer']) || !empty($homeSections['copyright'])))
            @php
                $footerData = $homeSections['footer'] ?? [];
                $footerLogoUrl = !empty($footerData['logo_url']) ? $footerData['logo_url'] : ($branding['logo_url'] ?? null);
                $footerLogoDarkUrl = (empty($footerData['logo_url']) && !empty($branding['logo_dark_url'])) ? $branding['logo_dark_url'] : null;
                $footerLogoAlt = !empty($footerData['logo_alt']) ? $footerData['logo_alt'] : ($branding['site_name'] ?? config('app.name'));
                $footerLinkUrl = function($u) {
                    if (empty($u)) return url('/');
                    $u = trim($u);
                    return (strpos($u, 'http') === 0 || strpos($u, '//') === 0) ? $u : url($u);
                };
            @endphp
            <div class="w-full">
                <div class="py-8 container-custom">
                    @php
                        $footVis = $homeSections['visibility'] ?? [];
                        $footerMapVisible = (bool) ($footVis['footer_map'] ?? true);
                        $googleMapsKeyForView = trim((string)($googleMapsApiKey ?? ''));
                        $showFooterMap = $footerMapVisible && $googleMapsKeyForView !== '';
                        $footerMapSize = $footerData['map_size'] ?? 'normal';
                        $footerMapHeightPx = $footerMapSize === 'small' ? 200 : ($footerMapSize === 'large' ? 400 : 300);
                        $footerMapWidthClass = 'w-full';
                        $footerMapCityOnly = !empty($footerData['map_city_only']);
                        $footerMapAddressStr = $footerMapCityOnly
                            ? trim((string) ($footerData['map_city'] ?? ''))
                            : trim(($footerData['map_street'] ?? '') . ' ' . ($footerData['map_huisnummer'] ?? '') . ', ' . ($footerData['map_postcode'] ?? '') . ' ' . ($footerData['map_city'] ?? ''), ' ,');
                        $footerLogoAlign = isset($footerData['logo_align']) && in_array($footerData['logo_align'], ['left', 'center', 'right'], true) ? $footerData['logo_align'] : 'left';
                        $footerLogoAlignWrapper = $footerLogoAlign === 'center' ? 'flex flex-col items-center' : ($footerLogoAlign === 'right' ? 'flex flex-col items-end' : 'flex flex-col items-start');
                        $footerLogoAlignText = $footerLogoAlign === 'center' ? 'text-center' : ($footerLogoAlign === 'right' ? 'text-right' : 'text-left');
                        $footerQuickLinksAlign = isset($footerData['quick_links_align']) && in_array($footerData['quick_links_align'], ['left', 'center', 'right'], true) ? $footerData['quick_links_align'] : 'left';
                        $footerSupportLinksAlign = isset($footerData['support_links_align']) && in_array($footerData['support_links_align'], ['left', 'center', 'right'], true) ? $footerData['support_links_align'] : 'left';
                        $footerQuickLinksAlignClass = $footerQuickLinksAlign === 'center' ? 'text-center' : ($footerQuickLinksAlign === 'right' ? 'text-right' : 'text-left');
                        $footerSupportLinksAlignClass = $footerSupportLinksAlign === 'center' ? 'text-center' : ($footerSupportLinksAlign === 'right' ? 'text-right' : 'text-left');
                        $showQuickLinks = ($footVis['footer_quick_links'] ?? true) && !empty($footerData['quick_links']);
                        $showSupportLinks = ($footVis['footer_support_links'] ?? true) && !empty($footerData['support_links']);
                        $footerLinkColumnsCount = ($showQuickLinks ? 1 : 0) + ($showSupportLinks ? 1 : 0);
                        $footerShowMapRight = $footerMapVisible;
                        $footerGridCols = $footerShowMapRight ? 'md:grid-cols-2' : ($footerLinkColumnsCount === 2 ? 'md:grid-cols-4' : ($footerLinkColumnsCount === 1 ? 'md:grid-cols-3' : 'md:grid-cols-1'));
                        $footerGridWithMapClass = $footerShowMapRight ? ' footer-grid-with-map' : '';
                        $footerFirstColSpan = $footerLinkColumnsCount === 2 ? 'md:col-span-2' : ($footerLinkColumnsCount === 1 ? 'md:col-span-2' : 'md:col-span-1');
                        $footerQuickLinksCol = $footerLinkColumnsCount === 2 ? 'md:col-start-3' : 'md:col-start-3';
                        $footerSupportLinksCol = $footerLinkColumnsCount === 2 ? 'md:col-start-4' : 'md:col-start-3';
                        $footerSocialLinks = [];
                        $footerSocialBases = ['social_facebook' => 'https://www.facebook.com/', 'social_instagram' => 'https://www.instagram.com/', 'social_x' => 'https://x.com/', 'social_linkedin' => 'https://www.linkedin.com/', 'social_youtube' => 'https://www.youtube.com/', 'social_tiktok' => 'https://www.tiktok.com/@'];
                        foreach (['facebook' => 'social_facebook', 'instagram' => 'social_instagram', 'x' => 'social_x', 'linkedin' => 'social_linkedin', 'youtube' => 'social_youtube', 'tiktok' => 'social_tiktok'] as $key => $field) {
                            $u = trim((string)($footerData[$field] ?? ''));
                            if ($u === '') continue;
                            if (strpos($u, 'http') === 0 || strpos($u, '//') === 0) {
                                $footerSocialLinks[$key] = $footerLinkUrl($u);
                            } else {
                                $base = $footerSocialBases[$field];
                                $id = $field === 'social_tiktok' ? ltrim($u, '@') : $u;
                                $footerSocialLinks[$key] = $base . $id;
                            }
                        }
                    @endphp
                    <div class="grid grid-cols-1 {{ $footerGridCols }} gap-6 {{ $footerShowMapRight ? 'md:grid-rows-[auto]' : '' }}{{ $footerGridWithMapClass }}">
                        @if($footerShowMapRight)
                        {{-- Linkerkant (50%): logo + tagline, daaronder Snelle Links (links) en Ondersteuning (rechts) naast elkaar --}}
                        <div class="flex flex-col min-w-0">
                            <div class="{{ $footerLogoAlignWrapper }} w-full max-w-full min-w-0">
                                @if(($footVis['footer_logo'] ?? true) && !empty($footerLogoUrl))
                                    @php $logoHeight = (int) ($footerData['logo_height'] ?? 12); $logoHeight = $logoHeight >= 12 && $logoHeight <= 30 ? $logoHeight : 12; @endphp
                                    @if(!empty($footerLogoDarkUrl))
                                        <img src="{{ $footerLogoUrl }}" alt="{{ $footerLogoAlt }}" class="fe-logo-light w-auto mb-4 h-{{ $logoHeight }} object-contain">
                                        <img src="{{ $footerLogoDarkUrl }}" alt="{{ $footerLogoAlt }}" class="fe-logo-dark w-auto mb-4 h-{{ $logoHeight }} object-contain">
                                    @else
                                        <img src="{{ $footerLogoUrl }}" alt="{{ $footerLogoAlt }}" class="w-auto mb-4 h-{{ $logoHeight }}">
                                    @endif
                                @elseif($footVis['footer_logo'] ?? true)
                                    <span class="font-semibold text-xl mb-4" style="color: var(--theme-primary);">{{ $branding['site_name'] ?? config('app.name') }}</span>
                                @endif
                                @if(($footVis['footer_tagline'] ?? true) && !empty($homeSections['footer']['tagline']))
                                    <div class="text-gray-700 dark:text-gray-200 mb-4 w-full max-w-full min-w-0 leading-relaxed prose prose-sm dark:prose-invert prose-p:my-1 prose-ul:my-1 prose-ol:my-1 max-w-none {{ $footerLogoAlignText }}">
                                        {!! $homeSections['footer']['tagline'] !!}
                                    </div>
                                @endif
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-0 w-full max-w-full min-w-0">
                                @if($showQuickLinks)
                                <div class="{{ $footerQuickLinksAlignClass }} min-w-0">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">{{ $footerData['quick_links_title'] ?? 'Snelle Links' }}</h3>
                                    <ul class="space-y-3">
                                        @foreach($footerData['quick_links'] as $link)
                                            @if(!empty($link['label']))
                                        <li>@if(!empty(trim($link['url'] ?? '')))<a href="{{ $footerLinkUrl($link['url']) }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors duration-200">{{ $link['label'] }}</a>@else<span class="text-gray-800 dark:text-gray-200">{{ $link['label'] }}</span>@endif</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                                @if($showSupportLinks)
                                <div class="{{ $footerSupportLinksAlignClass }} min-w-0">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $footerData['support_links_title'] ?? 'Ondersteuning' }}</h3>
                                    <ul class="space-y-3">
                                        @foreach($footerData['support_links'] as $link)
                                            @if(!empty($link['label']))
                                        <li>@if(!empty(trim($link['url'] ?? '')))<a href="{{ $footerLinkUrl($link['url']) }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors duration-200">{{ $link['label'] }}</a>@else<span class="text-gray-800 dark:text-gray-200">{{ $link['label'] }}</span>@endif</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>
                        </div>
                        {{-- Rechterkant (50%): kaart over volle breedte van de rechterhelft --}}
                        <div class="w-full min-w-0 flex flex-col">
                            <div class="w-full min-w-0 flex-1 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 mt-2 md:mt-0" style="height: {{ $footerMapHeightPx }}px;">
                                @if($showFooterMap)
                                <div id="footer-google-map" class="w-full h-full min-h-full block min-w-0 box-border" style="width: 100%; height: 100%; min-height: 100%; min-width: 0;" data-api-key="{{ $googleMapsKeyForView }}" data-map-id="{{ $googleMapsMapId ?? '' }}" data-lat="{{ $footerData['map_lat'] ?? '' }}" data-lng="{{ $footerData['map_lng'] ?? '' }}" data-zoom="{{ $footerData['map_zoom'] ?? 17 }}" data-address="{{ $footerMapAddressStr }}" data-show-address-balloon="{{ !empty($footerData['map_show_address_balloon']) ? '1' : '0' }}"></div>
                                @else
                                <div class="w-full h-full min-h-[8rem] flex items-center justify-center text-sm text-gray-500 dark:text-gray-400 px-4 text-center">
                                    Stel de Google Maps API-sleutel in via <strong>Admin → Instellingen → Maps</strong> om de kaart te tonen.
                                </div>
                                @endif
                            </div>
                        </div>
                        @else
                        {{-- Geen map of geen linkkolommen: één kolom logo+tagline+kaart --}}
                        <div class="col-span-1 {{ $footerFirstColSpan }}">
                            <div class="{{ $footerLogoAlignWrapper }}">
                                @if(($footVis['footer_logo'] ?? true) && !empty($footerLogoUrl))
                                    @php $logoHeight = (int) ($footerData['logo_height'] ?? 12); $logoHeight = $logoHeight >= 12 && $logoHeight <= 30 ? $logoHeight : 12; @endphp
                                    @if(!empty($footerLogoDarkUrl))
                                        <img src="{{ $footerLogoUrl }}" alt="{{ $footerLogoAlt }}" class="fe-logo-light w-auto mb-4 h-{{ $logoHeight }} object-contain">
                                        <img src="{{ $footerLogoDarkUrl }}" alt="{{ $footerLogoAlt }}" class="fe-logo-dark w-auto mb-4 h-{{ $logoHeight }} object-contain">
                                    @else
                                        <img src="{{ $footerLogoUrl }}" alt="{{ $footerLogoAlt }}" class="w-auto mb-4 h-{{ $logoHeight }}">
                                    @endif
                                @elseif($footVis['footer_logo'] ?? true)
                                    <span class="font-semibold text-xl mb-4" style="color: var(--theme-primary);">{{ $branding['site_name'] ?? config('app.name') }}</span>
                                @endif
                                @if(($footVis['footer_tagline'] ?? true) && !empty($homeSections['footer']['tagline']))
                                    <div class="text-gray-700 dark:text-gray-200 mb-4 w-full leading-relaxed prose prose-sm dark:prose-invert prose-p:my-1 prose-ul:my-1 prose-ol:my-1 max-w-none {{ $footerLogoAlignText }}">
                                        {!! $homeSections['footer']['tagline'] !!}
                                    </div>
                                @endif
                            </div>
                            @if($footerMapVisible)
                            <div class="w-full rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 mt-2" style="height: {{ $footerMapHeightPx }}px;">
                                @if($showFooterMap)
                                <div id="footer-google-map" class="w-full h-full min-h-full block" style="width: 100%; height: 100%; min-height: 100%;" data-api-key="{{ $googleMapsKeyForView }}" data-map-id="{{ $googleMapsMapId ?? '' }}" data-lat="{{ $footerData['map_lat'] ?? '' }}" data-lng="{{ $footerData['map_lng'] ?? '' }}" data-zoom="{{ $footerData['map_zoom'] ?? 17 }}" data-address="{{ $footerMapAddressStr }}" data-show-address-balloon="{{ !empty($footerData['map_show_address_balloon']) ? '1' : '0' }}"></div>
                                @else
                                <div class="w-full h-full min-h-[8rem] flex items-center justify-center text-sm text-gray-500 dark:text-gray-400 px-4 text-center">
                                    Stel de Google Maps API-sleutel in via <strong>Admin → Instellingen → Maps</strong> om de kaart te tonen.
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                        @endif
                        @if(!$footerShowMapRight)
                        @if($showQuickLinks)
                        <div class="{{ $footerQuickLinksCol }} {{ $footerQuickLinksAlignClass }}">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">{{ $footerData['quick_links_title'] ?? 'Snelle Links' }}</h3>
                            <ul class="space-y-3">
                                @foreach($footerData['quick_links'] as $link)
                                    @if(!empty($link['label']))
                                <li>@if(!empty(trim($link['url'] ?? '')))<a href="{{ $footerLinkUrl($link['url']) }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors duration-200">{{ $link['label'] }}</a>@else<span class="text-gray-800 dark:text-gray-200">{{ $link['label'] }}</span>@endif</li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        @if($showSupportLinks)
                        <div class="{{ $footerSupportLinksCol }} {{ $footerSupportLinksAlignClass }}">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $footerData['support_links_title'] ?? 'Ondersteuning' }}</h3>
                            <ul class="space-y-3">
                                @foreach($footerData['support_links'] as $link)
                                    @if(!empty($link['label']))
                                <li>@if(!empty(trim($link['url'] ?? '')))<a href="{{ $footerLinkUrl($link['url']) }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors duration-200">{{ $link['label'] }}</a>@else<span class="text-gray-800 dark:text-gray-200">{{ $link['label'] }}</span>@endif</li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        @endif
                    </div>
                    @if(($footVis['footer_social'] ?? true) && count($footerSocialLinks) > 0)
                        <div class="w-full mt-6 pt-6 border-t border-gray-200 dark:border-gray-600">
                            @include('frontend.layouts.partials.footer-social-icons', ['footerSocialLinks' => $footerSocialLinks, 'footerLogoAlign' => 'center'])
                        </div>
                    @endif
                </div>
                @if(!empty($homeSections['copyright']))
                    <div class="border-t border-gray-300 dark:border-gray-600 py-4 container-custom">
                        <p class="text-gray-700 dark:text-gray-200 text-sm">
                            {{ str_replace('{year}', date('Y'), $homeSections['copyright']) }}
                        </p>
                    </div>
                @endif
            </div>
        @else
            <div class="container-custom py-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    @if(!empty($branding['logo_url']))
                        @if(!empty($branding['logo_dark_url']))
                            <img src="{{ $branding['logo_url'] }}" alt="" class="fe-logo-light h-8 w-auto opacity-80 object-contain">
                            <img src="{{ $branding['logo_dark_url'] }}" alt="" class="fe-logo-dark h-8 w-auto opacity-80 object-contain">
                        @else
                            <img src="{{ $branding['logo_url'] }}" alt="" class="h-8 w-auto opacity-80">
                        @endif
                    @else
                        <span class="font-semibold" style="color: var(--theme-primary);">{{ $branding['site_name'] ?? config('app.name') }}</span>
                    @endif
                    <p class="text-sm">
                        {{ !empty($themeSettings['footer_text']) ? $themeSettings['footer_text'] : '© ' . date('Y') . ' ' . ($branding['site_name'] ?? config('app.name')) }}
                    </p>
                </div>
            </div>
        @endif
    </footer>

    @php
        $frontendEnv = app(\App\Services\EnvService::class);
        $whatsappWidgetEnabled = (string) $frontendEnv->get('WHATSAPP_WIDGET_ENABLED', '0') === '1';
        $whatsappWidgetPhoneRaw = trim((string) $frontendEnv->get('WHATSAPP_WIDGET_PHONE', ''));
        $whatsappWidgetPhoneDigits = preg_replace('/\D+/', '', $whatsappWidgetPhoneRaw);
        $whatsappWidgetMessage = trim((string) $frontendEnv->get('WHATSAPP_WIDGET_DEFAULT_MESSAGE', 'Hallo, ik heb een vraag over jullie diensten.'));
        if ($whatsappWidgetMessage === '') {
            $whatsappWidgetMessage = 'Hallo, ik heb een vraag over jullie diensten.';
        }
    @endphp
    @if($whatsappWidgetEnabled && !empty($whatsappWidgetPhoneDigits))
        <div id="frontend-whatsapp-widget"
             class="pointer-events-auto"
             style="position: fixed; right: 20px; bottom: 20px; z-index: 9999;">
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
                    <img src="{{ asset('assets/media/app/whatsapp-icon.svg') }}" alt="" class="h-9 w-9" aria-hidden="true">
                </a>
            </div>
            <button type="button"
                    id="frontend-whatsapp-widget-toggle"
                    aria-label="Open WhatsApp opties"
                    aria-expanded="false"
                    class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-xl hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#25D366] transition-all">
                <span id="frontend-whatsapp-widget-icon-open" class="inline-flex" style="display: inline-flex;">
                    <img src="{{ asset('assets/media/app/whatsapp-icon.svg') }}" alt="" class="h-9 w-9" aria-hidden="true">
                </span>
                <span id="frontend-whatsapp-widget-icon-close" class="inline-flex" style="display: none;">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
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
            function getStored() {
                try {
                    return localStorage.getItem('website-theme') || localStorage.getItem('theme');
                } catch (e) { return null; }
            }
            function setStored(v) {
                try {
                    localStorage.setItem('website-theme', v);
                    localStorage.setItem('theme', v);
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
                var intervalMs = 5000;

                function show(pos) {
                    var n = items.length;
                    current = (pos % n + n) % n;
                    items.forEach(function(el, i) {
                        if (i === current) {
                            el.classList.remove('opacity-0', 'z-0', 'pointer-events-none');
                            el.classList.add('opacity-100', 'z-10');
                            el.setAttribute('data-carousel-item', 'active');
                        } else {
                            el.classList.remove('opacity-100', 'z-10');
                            el.classList.add('opacity-0', 'z-0', 'pointer-events-none');
                            el.setAttribute('data-carousel-item', '');
                        }
                    });
                    indicators.forEach(function(btn, i) {
                        btn.setAttribute('aria-current', i === current ? 'true' : 'false');
                        btn.style.background = i === current ? '#ffffff' : '#9ca3af';
                    });
                }

                function next() { show(current + 1); }
                function prev() { show(current - 1); }

                if (prevBtn) prevBtn.addEventListener('click', function() { prev(); if (interval) clearInterval(interval); interval = isSlide ? setInterval(next, intervalMs) : null; });
                if (nextBtn) nextBtn.addEventListener('click', function() { next(); if (interval) clearInterval(interval); interval = isSlide ? setInterval(next, intervalMs) : null; });
                indicators.forEach(function(btn, i) {
                    btn.addEventListener('click', function() { show(i); if (interval) clearInterval(interval); interval = isSlide ? setInterval(next, intervalMs) : null; });
                });

                show(0);
                if (isSlide) interval = setInterval(next, intervalMs);
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCarousels);
        } else {
            initCarousels();
        }

        (function() {
            var mapEl = document.getElementById('footer-google-map');
            if (!mapEl) return;
            var apiKey = (mapEl.getAttribute('data-api-key') || '').trim();
            if (!apiKey) return;
            var mapId = (mapEl.getAttribute('data-map-id') || '').trim();
            if (!mapId) mapId = 'DEMO_MAP_ID';
            var useAdvancedMarker = true;
            var latStr = (mapEl.getAttribute('data-lat') || '').trim();
            var lngStr = (mapEl.getAttribute('data-lng') || '').trim();
            var address = (mapEl.getAttribute('data-address') || '').trim();
            var zoomStr = (mapEl.getAttribute('data-zoom') || '').trim();
            var showAddressBalloon = (mapEl.getAttribute('data-show-address-balloon') || '') === '1';
            var lat = parseFloat(latStr);
            var lng = parseFloat(lngStr);
            var zoom = (zoomStr !== '' && !isNaN(parseInt(zoomStr, 10))) ? parseInt(zoomStr, 10) : 17;
            if (zoom < 1 || zoom > 20) zoom = 17;
            var hasCoords = latStr !== '' && lngStr !== '' && !isNaN(lat) && !isNaN(lng);

            window.initFooterMap = function() {
                if (typeof google === 'undefined' || !google.maps || !google.maps.Map) return;
                var center = { lat: 52.3676, lng: 4.9041 };
                if (hasCoords) {
                    center = { lat: lat, lng: lng };
                }
                var useAdvanced = useAdvancedMarker && mapId && mapId.length > 0;
                var mapOptions = {
                    center: center,
                    zoom: zoom,
                    scrollwheel: false,
                    mapTypeControl: true,
                    streetViewControl: false,
                    fullscreenControl: true,
                    zoomControl: true
                };
                if (useAdvanced) mapOptions.mapId = mapId;
                var map;
                try {
                    map = new google.maps.Map(mapEl, mapOptions);
                } catch (e) {
                    delete mapOptions.mapId;
                    useAdvanced = false;
                    map = new google.maps.Map(mapEl, mapOptions);
                }
                function addMarkerSafe(m, pos) {
                    if (useAdvanced && google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                        try {
                            return new google.maps.marker.AdvancedMarkerElement({ map: m, position: pos });
                        } catch (err) {
                            return new google.maps.Marker({ position: pos, map: m });
                        }
                    }
                    return new google.maps.Marker({ position: pos, map: m });
                }
                function openAddressBalloon(marker, addr) {
                    addr = (addr != null) ? String(addr).trim() : '';
                    if (!addr || !showAddressBalloon || !google.maps.InfoWindow) return;
                    var infoWindow = new google.maps.InfoWindow({ content: '<div style="padding: 4px 8px 6px; font-size: 14px; color: #000; line-height: 1.25; margin: 0;">' + addr.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>' });
                    infoWindow.open(map, marker);
                }
                if (hasCoords) {
                    var marker = addMarkerSafe(map, center);
                    openAddressBalloon(marker, address);
                } else if (address && google.maps.Geocoder) {
                    var geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ address: address }, function(results, status) {
                        if (status === 'OK' && results && results[0]) {
                            var loc = results[0].geometry.location;
                            map.setCenter(loc);
                            map.setZoom(zoom);
                            var marker = addMarkerSafe(map, loc);
                            openAddressBalloon(marker, address);
                        } else {
                            addMarkerSafe(map, center);
                        }
                    });
                } else {
                    addMarkerSafe(map, center);
                }
                function triggerResize() {
                    if (google.maps && google.maps.event && map) {
                        google.maps.event.trigger(map, 'resize');
                    }
                }
                setTimeout(triggerResize, 100);
                setTimeout(triggerResize, 300);
                setTimeout(triggerResize, 600);
                if (typeof window !== 'undefined') {
                    window.addEventListener('load', triggerResize);
                    if (typeof ResizeObserver !== 'undefined' && mapEl && mapEl.parentElement) {
                        var ro = new ResizeObserver(function() { setTimeout(triggerResize, 50); });
                        ro.observe(mapEl.parentElement);
                    }
                }
            };
            var s = document.createElement('script');
            s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(apiKey) + (useAdvancedMarker ? '&libraries=marker' : '') + '&callback=initFooterMap&loading=async';
            s.async = true;
            document.head.appendChild(s);
        })();

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
    @stack('scripts')
</body>
</html>
