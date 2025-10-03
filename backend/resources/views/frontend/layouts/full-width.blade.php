<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'NEXA Skillmatching')</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <!-- Inter (optioneel) -->
  <link rel="preconnect" href="https://rsms.me/" />
  <link href="https://rsms.me/inter/inter.css" rel="stylesheet" />
  
  <!-- Dark Mode Initial State (FOUC-vrij) -->
  <script>
  (() => {
    const el = document.documentElement
    const saved = localStorage.getItem('theme')
    const prefersDark = matchMedia('(prefers-color-scheme: dark)').matches
    el.classList.toggle('dark', saved ? saved === 'dark' : prefersDark)
  })()
  </script>
</head>
<body class="bg-surface dark:bg-surface-dark text-text dark:text-text-dark antialiased min-h-screen flex flex-col">
  <!-- Header -->
  @include('frontend.layouts.partials.header')
  
  <div class="w-full py-6 flex-1">
    <div class="w-full max-w-none">
      @yield('content')
    </div>
  </div>

  <!-- Footer -->
  @include('frontend.layouts.partials.footer')
</body>
</html>
