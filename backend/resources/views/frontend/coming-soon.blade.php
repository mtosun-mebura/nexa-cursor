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
    </style>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 antialiased font-sans">
    <div id="main-content" class="coming-soon-gradient min-h-screen flex flex-col items-center justify-center px-4 py-12 sm:py-16">
        <div class="w-full max-w-xl mx-auto text-center space-y-8">
            @if(!empty($settings['logo_url']))
                <div class="flex justify-center mb-10">
                    <img src="{{ $settings['logo_url'] }}" alt="{{ $settings['site_name'] ?? config('app.name') }}" class="h-12 w-auto object-contain sm:h-14 dark:brightness-0 dark:invert" />
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
