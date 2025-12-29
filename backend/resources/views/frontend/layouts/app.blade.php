<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Nexa Skillmatching - Vind je droombaan')</title>
    <meta name="description" content="@yield('description', 'Ontdek de perfecte match tussen jouw vaardigheden en vacatures. Ons AI-platform helpt je de ideale baan te vinden.')">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'Nexa Skillmatching - Vind je droombaan')">
    <meta property="og:description" content="@yield('description', 'Ontdek de perfecte match tussen jouw vaardigheden en vacatures.')">
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="@yield('title', 'Nexa Skillmatching - Vind je droombaan')">
    <meta property="twitter:description" content="@yield('description', 'Ontdek de perfecte match tussen jouw vaardigheden en vacatures.')">
    <meta property="twitter:image" content="{{ asset('images/og-image.jpg') }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/nexa-x-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/nexa-x-logo.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/nexa-x-logo.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Dark Mode Initial State (FOUC-vrij) -->
    <script>
    (() => {
      const el = document.documentElement
      const saved = localStorage.getItem('theme')
      const prefersDark = matchMedia('(prefers-color-scheme: dark)').matches
      el.classList.toggle('dark', saved ? saved === 'dark' : prefersDark)
    })()
    </script>
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased flex flex-col">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded-lg z-50">
        Spring naar hoofdinhoud
    </a>
    
    <!-- Header -->
    @include('frontend.layouts.partials.header')
    
    <!-- Main Content -->
    <main id="main-content" class="flex-1">
        <div class="w-full">
            @yield('content')
        </div>
    </main>
    
    <!-- Footer -->
    @include('frontend.layouts.partials.footer')
    
    <!-- AI Chatbot -->
    @include('frontend.components.ai-chatbot')
    
    @stack('scripts')
</body>
</html>