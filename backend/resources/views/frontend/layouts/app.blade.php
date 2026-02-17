<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="auth-check" content="{{ auth()->check() ? 'true' : 'false' }}">
    
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
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/frontend-app.js'])
    <style>
        /* Dark mode: donkere achtergronden forceren (fallback) */
        html.dark body,
        html.dark body #main-content,
        html.dark header,
        html.dark footer { background-color: #111827 !important; }
        html.dark body { color: #f3f4f6; }
        /* Dark mode: footertekst leesbaar */
        html.dark footer,
        html.dark footer p,
        html.dark footer a { color: #e5e7eb !important; }
        html.dark footer a:hover { color: #93c5fd !important; }
        html.dark footer h3 { color: #ffffff !important; }
        /* Footer: witte lijntjes grijs in dark mode */
        html.dark footer,
        html.dark footer .border-t { border-color: #4b5563 !important; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased flex flex-col">
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
    
    <!-- AI Chatbot (alleen tonen als ingeschakeld in Instellingen > Algemene configuraties) -->
    @if(\App\Models\GeneralSetting::get('ai_chat_enabled', '0') === '1')
        @include('frontend.components.ai-chatbot')
    @endif
    
    <!-- Frontend Header Badges -->
    <script src="{{ asset('js/frontend-header-badges.js') }}"></script>
    <script src="{{ asset('js/frontend-chat.js') }}"></script>
    <script src="{{ asset('js/notifications-drawer.js') }}"></script>
    
    <!-- Ctrl+S / Cmd+S: opslaan op pagina's met een Opslaan-knop -->
    <script>
    (function() {
        document.addEventListener('keydown', function(e) {
            var isSave = (e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S');
            if (!isSave) return;
            e.preventDefault();
            var content = document.getElementById('main-content');
            if (!content) return;
            var forms = content.querySelectorAll('form');
            for (var i = 0; i < forms.length; i++) {
                var form = forms[i];
                if (form.closest('[role="dialog"]') || form.closest('.modal')) continue;
                var btn = form.querySelector('button[type="submit"].btn-primary');
                if (!btn) btn = form.querySelector('button[type="submit"][class*="btn-primary"]');
                if (!btn) continue;
                var text = (btn.textContent || btn.innerText || '').trim();
                var isSaveBtn = /opslaan|opsla|save|toevoegen/i.test(text);
                if (isSaveBtn) {
                    if (typeof form.requestSubmit === 'function') form.requestSubmit(btn);
                    else form.submit();
                    return;
                }
            }
        });
    })();
    </script>
    <!-- Dark mode toggle (header uses toggleDarkMode()) -->
    <script>
      function toggleDarkMode() {
        var html = document.documentElement;
        var isDark = html.classList.contains('dark');
        if (isDark) {
          html.classList.remove('dark');
          localStorage.setItem('theme', 'light');
        } else {
          html.classList.add('dark');
          localStorage.setItem('theme', 'dark');
        }
      }
    </script>
    <!-- Hide user dropdown if not authenticated -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const authMeta = document.querySelector('meta[name="auth-check"]');
        const isAuthenticated = authMeta && authMeta.getAttribute('content') === 'true';
        
        if (!isAuthenticated) {
          // Hide user dropdown container
          const userDropdown = document.querySelector('.user-dropdown-container');
          if (userDropdown) {
            userDropdown.style.display = 'none';
            userDropdown.style.visibility = 'hidden';
            userDropdown.style.opacity = '0';
          }
          
          // Hide all user dropdown menus
          const userDropdownMenus = document.querySelectorAll('[data-kt-dropdown-toggle="true"]');
          userDropdownMenus.forEach(menu => {
            const container = menu.closest('.user-dropdown-container');
            if (container) {
              container.style.display = 'none';
              container.style.visibility = 'hidden';
            }
          });
        }
      });
    </script>
    
    @stack('scripts')
</body>
</html>