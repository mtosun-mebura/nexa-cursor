<!DOCTYPE html>
<html class="h-full" lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Doorsturen' }} - NEXA Admin</title>
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f3f4f6; }
    </style>
</head>
<body class="antialiased p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <span class="text-xl font-semibold text-gray-800">NEXA Skillmatching</span>
        </div>
        @include('partials.redirect-message')
    </div>
</body>
</html>
