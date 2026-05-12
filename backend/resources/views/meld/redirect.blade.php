<!DOCTYPE html>
<html class="h-full" lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.redirect-page-theme', ['flavor' => 'frontend'])
    <title>{{ $title ?? 'Doorsturen' }} - NEXA Skillmatching</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html { color-scheme: light dark; }
    </style>
</head>
<body class="antialiased flex min-h-screen items-center justify-center bg-gray-100 p-4 text-gray-900 dark:bg-slate-950 dark:text-gray-100">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center">
            <a href="{{ url('/') }}" class="inline-block">
                <img src="{{ asset('images/nexa-logo.png') }}" alt="NEXA" class="mx-auto h-10 w-auto">
            </a>
        </div>
        @include('partials.redirect-message')
    </div>
</body>
</html>
