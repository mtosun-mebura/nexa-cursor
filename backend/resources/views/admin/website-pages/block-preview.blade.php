<!DOCTYPE html>
<html lang="nl" class="{{ !empty($previewDark) ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="color-scheme" content="{{ !empty($previewDark) ? 'dark' : 'light' }}">
    <title>{{ $previewLabel ?? 'Blokvoorbeeld' }}</title>
    <style>
        :root {
            --theme-primary: {{ $themeSettings['primary_color'] ?? '#2563eb' }};
            --theme-secondary: {{ $themeSettings['secondary_color'] ?? '#0f172a' }};
            --theme-font-heading: {{ $themeSettings['font_heading'] ?? 'Inter' }}, sans-serif;
            --theme-font-body: {{ $themeSettings['font_body'] ?? 'Inter' }}, sans-serif;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: transparent;
        }
        html:not(.dark), html:not(.dark) body {
            color: #0f172a;
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 transparent;
        }
        html.dark, html.dark body {
            color: #f8fafc;
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 transparent;
        }
        html:not(.dark)::-webkit-scrollbar,
        html:not(.dark) body::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        html:not(.dark)::-webkit-scrollbar-track,
        html:not(.dark) body::-webkit-scrollbar-track {
            background: transparent;
        }
        html:not(.dark)::-webkit-scrollbar-thumb,
        html:not(.dark) body::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        html.dark::-webkit-scrollbar,
        html.dark body::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        html.dark::-webkit-scrollbar-track,
        html.dark body::-webkit-scrollbar-track {
            background: transparent;
        }
        html.dark::-webkit-scrollbar-thumb,
        html.dark body::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        body.theme-modern { --theme-primary: {{ $themeSettings['primary_color'] ?? '#2563eb' }}; }
        #block-preview-root {
            padding: 1rem 0 2rem;
        }
        .website-section-inner {
            width: min(1120px, calc(100% - 2rem));
            margin-left: auto;
            margin-right: auto;
        }
    </style>
    @include('frontend.layouts.partials.vite-frontend-assets')
    @if(!empty($loadAtomV2Styles))
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link href="{{ asset('frontend-themes/atom-v2/assets/styles/main.min.css') }}" rel="stylesheet">
    @endif
</head>
<body class="theme-{{ $themeSlug ?? 'modern' }} antialiased {{ !empty($previewDark) ? 'dark' : '' }}" data-nexa-block-preview="1">
    <div id="block-preview-root">
        <main id="main-content">
            @include('frontend.website.partials.modern-home', [
                'homeSections' => $homeSections ?? [],
                'emailTemplateBySectionKey' => $emailTemplateBySectionKey ?? [],
                'jobs' => $jobs ?? collect(),
                'googleReviews' => $googleReviews ?? [],
                'googleMapsApiKey' => $googleMapsApiKey ?? '',
                'googleMapsMapId' => $googleMapsMapId ?? '',
                'page' => $page ?? null,
            ])
        </main>
    </div>
    @stack('styles')
    @stack('scripts')
    @include('frontend.website.partials.carousel-init-script')
    <style>
        /* In de preview-popup: vaste min-hoogte i.p.v. alleen vh, anders oogt de carousel te plat. */
        [data-nexa-block-preview] [data-carousel] .carousel-inner-fill {
            min-height: 320px;
        }
        @media (min-width: 768px) {
            [data-nexa-block-preview] [data-carousel] .carousel-inner-fill {
                min-height: 420px;
            }
        }

        /* Boekingsmodule: ronde hoeken + contrast t.o.v. preview-achtergrond (ook in dark). */
        [data-nexa-block-preview] [data-nexataxi-booking-module] .booking-module-layout.website-section-inner--flush {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
        [data-nexa-block-preview] [data-nexataxi-booking-module] .booking-module-card {
            border-radius: 16px !important;
            overflow: hidden !important;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12) !important;
        }
        html.dark [data-nexa-block-preview] [data-nexataxi-booking-module] .booking-module-card,
        [data-nexa-block-preview].dark [data-nexataxi-booking-module] .booking-module-card {
            border-color: rgba(148, 163, 184, 0.38) !important;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.45) !important;
        }
        [data-nexa-block-preview] .cards-ronde-hoeken-section {
            background: transparent !important;
        }

        /* Google Reviews: toon de echte desktop-opmaak (samenvatting links, 3 kaarten rechts), ook in een smallere iframe. */
        [data-nexa-block-preview] .google-reviews-section {
            background: transparent !important;
        }
        [data-nexa-block-preview] .google-reviews-section .grw-layout {
            flex-direction: row !important;
            align-items: stretch !important;
            gap: 2rem !important;
            max-width: 72rem;
            margin-left: auto;
            margin-right: auto;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            width: 100%;
        }
        [data-nexa-block-preview] .google-reviews-section .grw-summary {
            width: 280px !important;
            max-width: 280px !important;
            flex: 0 0 280px !important;
            min-height: 260px;
        }
        [data-nexa-block-preview] .google-reviews-section .grw-write-btn {
            width: 100% !important;
        }
        [data-nexa-block-preview] .google-reviews-section .grw-slider-wrapper .grw-header--carousel-only {
            display: block !important;
        }
    </style>
    <script>
    (function () {
        var ANIMATED_SEL = '.nexa-pros-cons__reveal, .nexa-pros-cons__item-reveal, .scroll-reveal-item, [data-scroll-reveal-item], .nexa-screenshot-gallery__intro, .nexa-screenshot-gallery__item, .footer-animate-brand, .footer-animate-tagline, .footer-footer-anim-left, .footer-map-reveal, .info-req-animate-left, .info-req-animate-right, .info-req-animate-bottom, .grw-summary, .grw-card, .grw-header h2, .grw-header p, .grw-btn-prev, .grw-btn-next, .nexa-pricing-reveal--block, .nexa-pricing-reveal__item, .nexa-pricing-reveal__card';
        var restartTimer = null;

        function clearInline(node) {
            node.style.transition = '';
            node.style.opacity = '';
            node.style.transform = '';
        }

        function snapHidden() {
            document.querySelectorAll('[data-scroll-reveal], [data-scroll-reveal-item], [data-stats-section]').forEach(function (el) {
                el.classList.remove('is-in-view');
            });
            document.querySelectorAll('.stats-count[data-stat-end]').forEach(function (el) {
                var prefix = el.getAttribute('data-stat-prefix') || '';
                var suffix = el.getAttribute('data-stat-suffix') || '';
                el.textContent = prefix + '0' + suffix;
            });
            document.querySelectorAll('.info-request-section').forEach(function (el) {
                el.classList.remove('in-view');
            });
            document.querySelectorAll('[id^="grw-"]').forEach(function (el) {
                el.classList.remove('grw-in-view');
            });
            document.querySelectorAll(ANIMATED_SEL).forEach(function (node) {
                node.style.transition = 'none';
                node.style.opacity = '0';
            });
            void document.body.offsetHeight;
        }

        function playReveal() {
            if (restartTimer) {
                clearTimeout(restartTimer);
                restartTimer = null;
            }
            snapHidden();
            restartTimer = setTimeout(function () {
                document.querySelectorAll(ANIMATED_SEL).forEach(clearInline);
                void document.body.offsetHeight;
                requestAnimationFrame(function () {
                    document.querySelectorAll('[data-scroll-reveal], [data-scroll-reveal-item], [data-stats-section]').forEach(function (el) {
                        el.classList.add('is-in-view');
                    });
                    if (typeof window.nexaRestartStatsCountUp === 'function') {
                        window.nexaRestartStatsCountUp();
                    }
                    if (typeof window.nexaRestartGoogleReviews === 'function') {
                        window.nexaRestartGoogleReviews();
                    }
                    document.querySelectorAll('.info-request-section').forEach(function (el) {
                        el.classList.add('in-view');
                    });
                    document.querySelectorAll('[id^="grw-"]').forEach(function (el) {
                        el.classList.add('grw-in-view');
                    });
                    if (typeof window.nexaRestartCarousels === 'function') {
                        window.nexaRestartCarousels();
                    }
                });
            }, 60);
        }

        function init() {
            playReveal();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        window.addEventListener('load', function () {
            setTimeout(playReveal, 30);
        });

        window.addEventListener('message', function (event) {
            if (!event || !event.data || event.data.type !== 'nexa-block-preview-restart') {
                return;
            }
            playReveal();
        });

        window.nexaBlockPreviewRestart = playReveal;
    })();
    </script>
</body>
</html>
