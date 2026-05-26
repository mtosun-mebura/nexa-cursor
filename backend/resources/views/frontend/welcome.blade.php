@php
    $logoUrl = null;
    $logoDarkUrl = null;
    $faviconUrl = null;
    $siteName = \App\Models\GeneralSetting::get('site_name', config('app.name', 'NEXA'));
    $logoPath = \App\Models\GeneralSetting::get('logo');
    $logoMode = \App\Models\GeneralSetting::get('logo_mode', 'single');
    $logoDarkPath = \App\Models\GeneralSetting::get('logo_dark');
    $faviconPath = \App\Models\GeneralSetting::get('favicon');
    $websiteBuilder = app(\App\Services\WebsiteBuilderService::class);

    if ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
        $logoUrl = $websiteBuilder->publicFileUrl(ltrim($logoPath, '/'));
    }
    if ($logoMode === 'light_dark' && $logoDarkPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoDarkPath)) {
        $logoDarkUrl = $websiteBuilder->publicFileUrl(ltrim($logoDarkPath, '/'));
    }
    if ($faviconPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($faviconPath)) {
        $faviconUrl = $websiteBuilder->publicFileUrl(ltrim($faviconPath, '/'));
    }
@endphp
<!DOCTYPE html>
<html lang="nl" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $w['meta_title'] ?? 'NEXA — Modulair SaaS Platform' }}</title>
    <meta name="description" content="{{ $w['meta_description'] ?? 'NEXA is een modulair SaaS-platform. Kies de modules die bij uw bedrijf passen: Skillmatching, Taxi en Garage.' }}" />
    @if(!empty($faviconUrl))
    <link rel="icon" href="{{ $faviconUrl }}" />
    <link rel="shortcut icon" href="{{ $faviconUrl }}" />
    @else
    <link rel="icon" href="{{ asset('assets/media/app/mini-logo-circle.svg') }}" type="image/svg+xml" />
    @endif
    @vite(['resources/css/app.css'])
    <style>
        :root { --nexa-primary: #252F4A; --nexa-accent: #3b82f6; --nexa-accent-hover: #2563eb; }
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        .nexa-gradient { background: linear-gradient(135deg, var(--nexa-primary) 0%, #1e3a5f 50%, #1a365d 100%); }
        .hover-card { transition: transform .2s ease, box-shadow .2s ease; }
        .hover-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(0,0,0,.12); }
        .module-icon { width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; border-radius: 14px; font-size: 24px; }
        .feature-check { width: 20px; height: 20px; flex-shrink: 0; color: #22c55e; }

        /* Dark mode */
        html.dark { color-scheme: dark; }
        html.dark body { background: #0f172a; color: #e2e8f0; }
        html.dark .nexa-gradient { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); }
        html.dark .bg-white { background: #1e293b; }
        html.dark .bg-gray-50 { background: #0f172a; }
        html.dark .text-gray-900 { color: #f1f5f9; }
        html.dark .text-gray-500 { color: #94a3b8; }
        html.dark .text-gray-700 { color: #cbd5e1; }
        html.dark .text-gray-400 { color: #64748b; }
        html.dark .border-gray-200 { border-color: #334155; }
        html.dark .border-gray-100 { border-color: #1e293b; }
        html.dark .hover-card:hover { box-shadow: 0 20px 40px rgba(0,0,0,.4); }
        html.dark .bg-blue-100 { background: rgba(59,130,246,.15); }
        html.dark .bg-amber-100 { background: rgba(245,158,11,.15); }
        html.dark .bg-emerald-100 { background: rgba(16,185,129,.15); }
        html.dark .bg-violet-100 { background: rgba(139,92,246,.15); }
        html.dark .bg-rose-100 { background: rgba(244,63,94,.15); }
        html.dark .bg-gray-100 { background: rgba(100,116,139,.15); }
        html.dark .bg-blue-50 { background: rgba(59,130,246,.1); }
        html.dark .bg-amber-50 { background: rgba(245,158,11,.1); }

        /* CTA: expliciet i.c.m. html.dark (betrouwbaarder dan dark: op deze standalone pagina) */
        .welcome-cta-to-admin {
            background-color: #252F4A;
            color: #fff;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        .welcome-cta-to-admin:hover {
            background-color: #3b4a6b;
            color: #fff;
        }
        .welcome-cta-to-admin svg {
            stroke: currentColor;
        }
        html.dark .welcome-cta-to-admin {
            background-color: #f8fafc;
            color: #252F4A;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.15), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        html.dark .welcome-cta-to-admin:hover {
            background-color: #e2e8f0;
            color: #252F4A;
        }
        .welcome-logo-light { display: block; }
        .welcome-logo-dark { display: none; }
        html.dark .welcome-logo-light { display: none; }
        html.dark .welcome-logo-dark { display: block; }
    </style>
</head>
<body class="bg-white text-gray-900 antialiased pt-16">

{{-- ===== Vaste topbalk: logo + thema ===== --}}
<header class="welcome-nav fixed top-0 left-0 right-0 z-50 flex h-16 items-center justify-between gap-4 border-b border-gray-200 bg-white px-4 sm:px-6 dark:border-slate-700 dark:bg-slate-900">
    <a href="{{ url('/') }}" class="flex shrink-0 items-center">
        @if(!empty($logoUrl) && !empty($logoDarkUrl))
            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="welcome-logo-light h-8 w-auto max-w-[200px] object-contain object-left" />
            <img src="{{ $logoDarkUrl }}" alt="{{ $siteName }}" class="welcome-logo-dark h-8 w-auto max-w-[200px] object-contain object-left" />
        @elseif(!empty($logoUrl))
            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-8 w-auto max-w-[200px] object-contain object-left" />
        @else
            <img src="{{ asset('images/nexa-logo.png') }}" alt="NEXA" class="h-8 w-auto max-w-[200px] object-contain object-left" />
        @endif
    </a>
    <button id="theme-toggle" type="button"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-none border-0 bg-transparent p-0 shadow-none outline-none ring-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400 dark:focus-visible:outline-slate-500"
        aria-label="Schakel tussen licht en donker thema">
        <svg id="icon-sun" class="hidden h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/></svg>
        <svg id="icon-moon" class="h-5 w-5 text-slate-700 dark:text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
    </button>
</header>

{{-- ===== Hero ===== --}}
<div class="nexa-gradient relative overflow-hidden" role="banner">
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 1440 600" preserveAspectRatio="none">
            <circle cx="200" cy="100" r="300" fill="white" opacity=".04"/>
            <circle cx="1200" cy="500" r="400" fill="white" opacity=".03"/>
            <circle cx="700" cy="300" r="200" fill="white" opacity=".05"/>
        </svg>
    </div>
    <div class="relative mx-auto max-w-6xl px-6 pb-12 pt-10 text-center text-white sm:pb-14 sm:pt-12 lg:pb-16 lg:pt-14">
        <h1 class="mb-6 text-4xl font-bold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
            {{ $w['hero_title'] ?? 'Welkom bij NEXA' }}
        </h1>
        <p class="mx-auto mb-6 max-w-2xl text-lg leading-relaxed text-blue-100 sm:text-xl lg:mb-8">
            {{ $w['hero_subtitle'] ?? 'Het modulaire SaaS-platform dat meegroeit met uw bedrijf. Kies de modules die u nodig heeft en ga direct aan de slag.' }}
        </p>
        <a href="#modules-overview" class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 font-semibold text-gray-900 shadow-lg transition hover:bg-blue-50">
            {{ $w['hero_button'] ?? 'Bekijk modules' }}
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </a>
    </div>
</div>

{{-- ===== Modules ===== --}}
@include('frontend.website.components.nexa-modules-overview')

{{-- ===== Waarom NEXA ===== --}}
<section class="bg-gray-50 border-y border-gray-100">
    <div class="max-w-6xl mx-auto px-6 py-20 lg:py-24">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ $w['why_title'] ?? 'Waarom NEXA?' }}</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="hover-card bg-white rounded-2xl border border-gray-200 p-6 text-center">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                </div>
                <h3 class="font-semibold mb-1">{{ $w['usp1_title'] ?? 'Modulair' }}</h3>
                <p class="text-sm text-gray-500">{{ $w['usp1_desc'] ?? 'Gebruik alleen de modules die u nodig heeft' }}</p>
            </div>
            <div class="hover-card bg-white rounded-2xl border border-gray-200 p-6 text-center">
                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>
                </div>
                <h3 class="font-semibold mb-1">{{ $w['usp2_title'] ?? 'Multi-tenant' }}</h3>
                <p class="text-sm text-gray-500">{{ $w['usp2_desc'] ?? 'Elk bedrijf krijgt een eigen omgeving en domein' }}</p>
            </div>
            <div class="hover-card bg-white rounded-2xl border border-gray-200 p-6 text-center">
                <div class="w-12 h-12 bg-violet-100 text-violet-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/></svg>
                </div>
                <h3 class="font-semibold mb-1">{{ $w['usp3_title'] ?? 'White-label' }}</h3>
                <p class="text-sm text-gray-500">{{ $w['usp3_desc'] ?? 'Volledig aanpasbaar aan uw huisstijl' }}</p>
            </div>
            <div class="hover-card bg-white rounded-2xl border border-gray-200 p-6 text-center">
                <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                </div>
                <h3 class="font-semibold mb-1">{{ $w['usp4_title'] ?? 'Veilig' }}</h3>
                <p class="text-sm text-gray-500">{{ $w['usp4_desc'] ?? 'Data-isolatie per module met PostgreSQL-schema\'s' }}</p>
            </div>
        </div>
    </div>
</section>

{{-- ===== CTA ===== --}}
<section class="max-w-6xl mx-auto px-6 py-20 lg:py-24 text-center">
    <h2 class="text-3xl sm:text-4xl font-bold mb-4">{{ $w['cta_title'] ?? 'Klaar om te starten?' }}</h2>
    <p class="text-gray-500 max-w-lg mx-auto mb-8">{{ $w['cta_subtitle'] ?? 'Log in op het admin-paneel om modules te installeren, bedrijven aan te maken en uw platform in te richten.' }}</p>
    <a href="{{ route('admin.login') }}" class="welcome-cta-to-admin inline-flex items-center gap-2 rounded-xl px-8 py-3.5 font-semibold transition">
        {{ $w['cta_button'] ?? 'Naar Admin' }}
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
    </a>
</section>

{{-- ===== Footer ===== --}}
<footer class="border-t border-gray-100 py-8">
    <div class="max-w-6xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-400">
        <div class="flex items-center gap-2">
            @if(!empty($logoUrl) && !empty($logoDarkUrl))
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="welcome-logo-light h-4 w-auto object-contain" />
                <img src="{{ $logoDarkUrl }}" alt="{{ $siteName }}" class="welcome-logo-dark h-4 w-auto object-contain" />
            @elseif(!empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-4 w-auto object-contain" />
            @else
                <img src="{{ asset('images/nexa-logo.png') }}" alt="NEXA" class="h-4 w-auto" />
            @endif
            <span>&copy; {{ date('Y') }} Alle rechten voorbehouden.</span>
        </div>
        <a href="{{ route('admin.login') }}" class="hover:text-gray-600 transition">Admin login</a>
    </div>
</footer>

<script>
(function() {
    var html = document.documentElement;
    var toggle = document.getElementById('theme-toggle');
    var iconSun = document.getElementById('icon-sun');
    var iconMoon = document.getElementById('icon-moon');
    function applyTheme(dark) {
        html.classList.toggle('dark', dark);
        iconSun.classList.toggle('hidden', !dark);
        iconMoon.classList.toggle('hidden', dark);
        localStorage.setItem('nexa-theme', dark ? 'dark' : 'light');
    }
    var stored = localStorage.getItem('nexa-theme');
    var prefersDark = stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches);
    applyTheme(prefersDark);
    toggle.addEventListener('click', function() { applyTheme(!html.classList.contains('dark')); });
})();
</script>
</body>
</html>
