<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>502 - Service Tijdelijk Onbeschikbaar | NEXA Skillmatching</title>
    
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
    
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .dark body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }
        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            width: 100%;
            max-width: 800px;
            text-align: center;
        }
        .dark .error-container {
            background: #1f2937;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        .logo-container {
            margin-bottom: 2rem;
        }
        .logo-container img {
            height: 60px;
            width: auto;
        }
        .error-icon {
            display: flex;
            justify-content: center;
            align-items: center;
            color: #f59e0b;
            margin-bottom: 1.5rem;
        }
        .error-icon svg {
            width: 14rem;
            height: 14rem;
        }
        .dark .error-icon {
            color: #fbbf24;
        }
        .error-title {
            color: #1f2937;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .dark .error-title {
            color: #f9fafb;
        }
        .error-message {
            color: #6b7280;
            font-size: 1.125rem;
            line-height: 1.75;
            margin-bottom: 2rem;
        }
        .dark .error-message {
            color: #d1d5db;
        }
        .btn-container {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 2px solid #e5e7eb;
        }
        .dark .btn-secondary {
            background: #374151;
            color: #f9fafb;
            border-color: #4b5563;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        .dark .btn-secondary:hover {
            background: #4b5563;
        }
        @media (max-width: 640px) {
            .error-container {
                padding: 2rem;
                margin: 1rem;
            }
            .error-title {
                font-size: 2rem;
            }
            .error-icon svg {
                width: 10rem;
                height: 10rem;
            }
            .btn-container {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <div class="error-container">
        <div class="logo-container">
            <img src="{{ asset('images/nexa-skillmatching-logo.png') }}" alt="NEXA Skillmatching" class="mx-auto">
        </div>
        
        <div class="error-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        
        <h1 class="error-title">502 - Service Tijdelijk Onbeschikbaar</h1>
        
        <p class="error-message">
            De website is tijdelijk niet bereikbaar. Onze servers worden momenteel onderhouden of er is een technisch probleem opgetreden.
            <br><br>
            Probeer het over een paar minuten opnieuw. Als het probleem aanhoudt, neem dan contact met ons op.
        </p>

        <div class="btn-container">
            <button onclick="window.location.reload()" class="btn btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Pagina Vernieuwen
            </button>
        </div>
    </div>
    
    <script>
        // Dark mode toggle functionality
        function toggleTheme() {
            const el = document.documentElement;
            const isDark = el.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
        
        // Auto-refresh after 30 seconds (optional)
        // setTimeout(function() {
        //     window.location.reload();
        // }, 30000);
    </script>
</body>
</html>

