<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>@yield('title', 'NEXA Skillmatching')</title>
  @php
      $layoutBranding = app(\App\Services\WebsiteBuilderService::class)->getSiteBranding();
  @endphp
  @vite(['resources/css/app.css', 'resources/js/frontend-app.js'])
  <style>
    .fe-logo-light { display: block !important; }
    .fe-logo-dark { display: none !important; }
    html.dark .fe-logo-light { display: none !important; }
    html.dark .fe-logo-dark { display: block !important; }
  </style>
  <!-- Inter (optioneel) -->
  <link rel="preconnect" href="https://rsms.me/" />
  <link href="https://rsms.me/inter/inter.css" rel="stylesheet" />
  
  <!-- Dark Mode Initial State (FOUC-vrij) - MUST RUN FIRST -->
  <script>
  (() => {
    const el = document.documentElement
    const saved = localStorage.getItem('theme')
    
    // Remove dark class first to ensure clean state
    el.classList.remove('dark')
    
    if (saved === 'dark') {
      // Use saved dark preference
      el.classList.add('dark')
    } else if (saved === 'light') {
      // Use saved light preference (already removed)
      el.classList.remove('dark')
    } else {
      // No saved preference - use system preference
      const prefersDark = matchMedia('(prefers-color-scheme: dark)').matches
      if (prefersDark) {
        el.classList.add('dark')
        localStorage.setItem('theme', 'dark')
      } else {
        localStorage.setItem('theme', 'light')
      }
    }
  })()
  </script>
</head>
<body class="bg-white dark:bg-surface-dark text-text dark:text-text-dark antialiased min-h-screen flex flex-col">
  <!-- Header -->
  @include('frontend.layouts.partials.header', ['branding' => $layoutBranding])
  
  <div class="w-full py-6 container-custom flex-1">
    @yield('content')
  </div>

  <!-- Footer -->
  @include('frontend.layouts.partials.footer', ['branding' => $layoutBranding])
</body>
</html>
