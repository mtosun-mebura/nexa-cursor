<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings['coming_soon_title'] ?? 'We zijn bijna live' }} – {{ $settings['site_name'] ?? config('app.name') }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($settings['coming_soon_text'] ?? ''), 160) }}">
    <meta name="robots" content="noindex, nofollow">

    @if(!empty($settings['favicon_url']))
        <link rel="icon" href="{{ $settings['favicon_url'] }}">
        <link rel="shortcut icon" href="{{ $settings['favicon_url'] }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
    (() => {
      const el = document.documentElement;
      const saved = localStorage.getItem('theme');
      el.classList.remove('dark');
      if (saved === 'dark') el.classList.add('dark');
      else if (saved !== 'light') {
        if (matchMedia('(prefers-color-scheme: dark)').matches) {
          el.classList.add('dark');
          localStorage.setItem('theme', 'dark');
        } else localStorage.setItem('theme', 'light');
      }
    })();
    </script>

    @vite(['resources/css/app.css'])
    <style>
        .coming-soon-gradient {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 50%, #1e293b 100%);
        }
        .dark .coming-soon-gradient {
            background: linear-gradient(135deg, #0f172a 0%, #020617 50%, #0f172a 100%);
        }
        /* Zelfde zachte oranje en knop als website preview-balk */
        .coming-soon-admin-preview-bar {
            background-color: #f97316;
            color: #fff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        .coming-soon-admin-preview-bar-title {
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-weight: 600;
            color: #ffffff;
        }
        .coming-soon-back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.625rem;
            font-size: 0.8125rem;
            line-height: 1.2;
            font-weight: 600;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 0.375rem;
            background-color: transparent;
            border: 1px solid #ffffff;
            transition: background-color 0.15s ease;
        }
        .coming-soon-back-link:hover {
            background-color: rgba(255, 255, 255, 0.12);
            color: #ffffff !important;
        }
        .coming-soon-back-link:focus {
            outline: 2px solid rgba(255, 255, 255, 0.85);
            outline-offset: 2px;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 antialiased font-sans">
    @if(!empty($adminPreviewReturnUrl))
        <div class="coming-soon-admin-preview-bar fixed top-0 left-0 right-0 z-[100] flex h-10 w-full flex-nowrap items-center gap-3 px-4 leading-none" role="banner" aria-label="Voorbeeldmodus">
            <a href="{{ $adminPreviewReturnUrl }}" class="coming-soon-back-link shrink-0">
                <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                Terug naar admin
            </a>
            <span class="coming-soon-admin-preview-bar-title min-w-0 flex-1 truncate text-center">Voorbeeld — Coming Soon-pagina</span>
        </div>
    @endif
    <div id="main-content" class="coming-soon-gradient min-h-screen flex flex-col items-center justify-center px-4 py-12 sm:py-16 {{ !empty($adminPreviewReturnUrl) ? 'pt-10' : '' }}">
        <div class="w-full max-w-xl mx-auto text-center space-y-8">
            @if(!empty($settings['logo_url']))
                <div class="flex justify-center mb-10">
                    <img src="{{ $settings['logo_url'] }}" alt="{{ $settings['site_name'] ?? config('app.name') }}" class="h-12 w-auto object-contain sm:h-14 dark:brightness-0 dark:invert" />
                </div>
            @endif

            @if(!empty($settings['coming_soon_image_url']))
                <div class="flex justify-center -mt-2 mb-6">
                    <img src="{{ $settings['coming_soon_image_url'] }}" alt="" class="max-w-[min(100%,28rem)] max-h-56 w-auto h-auto object-contain mx-auto drop-shadow-lg rounded-lg" loading="lazy" decoding="async" />
                </div>
            @endif

            <div class="space-y-4">
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-white dark:text-slate-100 tracking-tight">
                    {{ $settings['coming_soon_title'] ?? 'We zijn bijna live' }}
                </h1>
                <p class="text-lg sm:text-xl text-slate-200 dark:text-slate-300 leading-relaxed max-w-xl mx-auto">
                    {{ $settings['coming_soon_text'] ?? 'Onze website wordt op dit moment voor u klaargemaakt. Binnenkort vindt u hier alle informatie en mogelijkheden.' }}
                </p>
                @if(!empty($settings['coming_soon_secondary_text']))
                    <p class="text-base text-slate-300 dark:text-slate-400">
                        {{ $settings['coming_soon_secondary_text'] }}
                    </p>
                @endif
            </div>

            @if($showEmail && !empty($contactEmail))
                <div class="pt-4 text-sm text-slate-400 dark:text-slate-500">
                    <span>{{ rtrim($settings['coming_soon_contact_label'] ?? 'E-mail', ':') }}:</span>
                    <a href="mailto:{{ $contactEmail }}" class="text-white dark:text-slate-200 font-medium hover:underline focus:outline-none focus:ring-2 focus:ring-white/50 rounded ml-1">
                        {{ $contactEmail }}
                    </a>
                </div>
            @endif

            @php
                $footerText = \Illuminate\Support\Str::replace(
                    ['{year}', '{site}'],
                    [date('Y'), $settings['site_name'] ?? config('app.name')],
                    $settings['coming_soon_footer_text'] ?? '© {year} {site}. Binnenkort beschikbaar.'
                );
            @endphp
            <p class="text-xs text-slate-500 dark:text-slate-600 mt-5">{{ $footerText }}</p>
        </div>
    </div>
</body>
</html>
