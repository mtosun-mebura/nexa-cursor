<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $branding['site_name'] ?? config('app.name'))</title>
    <meta name="description" content="@yield('description', '')">
    @if(!empty($branding['favicon_url']))
    <link rel="icon" href="{{ $branding['favicon_url'] }}">
    <link rel="shortcut icon" href="{{ $branding['favicon_url'] }}">
    @else
    <link rel="icon" type="image/png" href="{{ asset('images/nexa-x-logo.png') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Georgia&display=swap" rel="stylesheet">
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
    <div class="sticky top-0 z-[100] flex items-center justify-center gap-4 py-2 px-4 text-sm font-medium text-gray-900 dark:text-white bg-[var(--theme-primary)] dark:opacity-95">
        <span>Dit is een voorbeeld met het gekozen thema.</span>
        <a href="{{ $previewEditUrl }}" class="underline hover:no-underline font-semibold text-gray-900 hover:text-gray-700 dark:text-white dark:hover:text-gray-100">Terug naar bewerken</a>
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
                            <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['site_name'] ?? '' }}" class="h-10 md:h-12 w-auto">
                        @else
                            <span class="text-xl font-bold" style="color: var(--theme-primary);">{{ $branding['site_name'] ?? config('app.name') }}</span>
                        @endif
                    </a>
                </div>
                {{-- Desktop: nav verborgen onder 1025px via CSS media query; dan hamburger --}}
                <nav id="website-desktop-nav" class="flex flex-nowrap items-center gap-4 flex-1 justify-center px-4 min-w-0 overflow-hidden" role="navigation" aria-label="Hoofdnavigatie">
                    @forelse($menuPages ?? [] as $menuPage)
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
                    {{-- App-links (alleen ingelogd): direct achter de andere menuitems, mee in het midden uitgelijnd --}}
                    @auth
                    <a href="{{ route('dashboard') }}" class="text-gray-900 dark:text-gray-100 hover:opacity-90 px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('dashboard') ? 'opacity-100 font-semibold' : '' }}" style="{{ request()->routeIs('dashboard') ? 'color: var(--theme-primary);' : '' }}">Dashboard</a>
                    <a href="{{ route('jobs.index') }}" class="text-gray-900 dark:text-gray-100 hover:opacity-90 px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('jobs.*') ? 'opacity-100 font-semibold' : '' }}" style="{{ request()->routeIs('jobs.*') ? 'color: var(--theme-primary);' : '' }}">Vacatures</a>
                    <a href="{{ route('matches') }}" class="text-gray-900 dark:text-gray-100 hover:opacity-90 px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('matches') ? 'opacity-100 font-semibold' : '' }}" style="{{ request()->routeIs('matches') ? 'color: var(--theme-primary);' : '' }}">Matches</a>
                    <a href="{{ route('agenda') }}" class="text-gray-900 dark:text-gray-100 hover:opacity-90 px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('agenda') ? 'opacity-100 font-semibold' : '' }}" style="{{ request()->routeIs('agenda') ? 'color: var(--theme-primary);' : '' }}">Agenda</a>
                    @endauth
                </nav>
                {{-- Rechterkant desktop: streep (border-l), thema-toggle + Mijn Nexa/Inloggen; verborgen onder 1025px --}}
                <div id="website-desktop-right" class="flex items-center gap-2 lg:gap-4 ml-auto flex-shrink-0 pl-4 border-l border-gray-200 dark:border-gray-700">
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
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg text-base font-medium transition-colors shrink-0 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">{{ $branding['dashboard_link_label'] ?? 'Mijn Nexa' }}</a>
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
                @forelse($menuPages ?? [] as $menuPage)
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
                <a href="{{ route('dashboard') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">Dashboard</a>
                @endauth
                <a href="{{ route('jobs.index') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">Vacatures</a>
                @auth
                <a href="{{ route('matches') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">Matches</a>
                <a href="{{ route('agenda') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">Agenda</a>
                @endauth
                {{-- Streep boven Mijn Nexa/Inloggen als onderscheid met normale menuitems --}}
                <div class="border-t border-gray-200 dark:border-gray-700 mt-4 pt-4">
                    @guest
                    <a href="{{ route('login') }}" class="block px-4 py-3 rounded-lg font-medium text-white" style="background-color: var(--theme-primary);">Inloggen</a>
                    @else
                    <a href="{{ route('dashboard') }}" class="block px-4 py-3 rounded-lg font-medium text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">{{ $branding['dashboard_link_label'] ?? 'Mijn Nexa' }}</a>
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
                $footerLogoAlt = !empty($footerData['logo_alt']) ? $footerData['logo_alt'] : ($branding['site_name'] ?? config('app.name'));
                $footerLinkUrl = function($u) {
                    if (empty($u)) return url('/');
                    $u = trim($u);
                    return (strpos($u, 'http') === 0 || strpos($u, '//') === 0) ? $u : url($u);
                };
            @endphp
            <div class="w-full">
                <div class="py-8 container-custom">
                    @php $footVis = $homeSections['visibility'] ?? []; @endphp
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="col-span-1 md:col-span-2">
                            @if(($footVis['footer_logo'] ?? true) && !empty($footerLogoUrl))
                                @php $logoHeight = (int) ($footerData['logo_height'] ?? 12); $logoHeight = $logoHeight >= 12 && $logoHeight <= 30 ? $logoHeight : 12; @endphp
                                {{-- Tailwind: footer logo heights h-12 t/m h-30 --}}
                                <img src="{{ $footerLogoUrl }}" alt="{{ $footerLogoAlt }}" class="w-auto mb-4 h-{{ $logoHeight }}">
                            @elseif($footVis['footer_logo'] ?? true)
                                <span class="font-semibold text-xl" style="color: var(--theme-primary);">{{ $branding['site_name'] ?? config('app.name') }}</span>
                            @endif
                            @if(($footVis['footer_tagline'] ?? true) && !empty($homeSections['footer']['tagline']))
                                <div class="text-gray-700 dark:text-gray-200 mb-4 w-full leading-relaxed prose prose-sm dark:prose-invert prose-p:my-1 prose-ul:my-1 prose-ol:my-1 max-w-none">
                                    {!! $homeSections['footer']['tagline'] !!}
                                </div>
                            @endif
                        </div>
                        @if(($footVis['footer_quick_links'] ?? true) && !empty($footerData['quick_links']))
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">{{ $footerData['quick_links_title'] ?? 'Snelle Links' }}</h3>
                            <ul class="space-y-3">
                                @foreach($footerData['quick_links'] as $link)
                                    @if(!empty($link['label']))
                                <li><a href="{{ $footerLinkUrl($link['url'] ?? '') }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors duration-200">{{ $link['label'] }}</a></li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        @if(($footVis['footer_support_links'] ?? true) && !empty($footerData['support_links']))
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $footerData['support_links_title'] ?? 'Ondersteuning' }}</h3>
                            <ul class="space-y-3">
                                @foreach($footerData['support_links'] as $link)
                                    @if(!empty($link['label']))
                                <li><a href="{{ $footerLinkUrl($link['url'] ?? '') }}" class="text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition-colors duration-200">{{ $link['label'] }}</a></li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
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
                        <img src="{{ $branding['logo_url'] }}" alt="" class="h-8 w-auto opacity-80">
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

    <script>
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
            }
            function isDark() { return html.classList.contains('dark'); }
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
    </script>
    @stack('scripts')
</body>
</html>
