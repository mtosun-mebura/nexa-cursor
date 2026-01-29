<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Doorsturen' }} - NEXA Skillmatching</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f3f4f6; }
        .dark body { background: #111827; }
    </style>
</head>
<body class="antialiased p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <a href="{{ url('/') }}" class="inline-block">
                <img src="{{ asset('images/nexa-skillmatching-logo.png') }}" alt="NEXA" class="h-10 w-auto mx-auto">
            </a>
        </div>
        @include('partials.redirect-message')
    </div>
</body>
</html>
