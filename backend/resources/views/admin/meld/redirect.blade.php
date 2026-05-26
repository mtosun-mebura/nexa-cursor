<!DOCTYPE html>
<html class="h-full" lang="nl" data-kt-theme="true" data-kt-theme-mode="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.redirect-page-theme', ['flavor' => 'admin'])
    <title>{{ $title ?? 'Doorsturen' }} - NEXA Admin</title>
    {{-- Fallback: redirect naar login na 5 sec als JavaScript uitstaat --}}
    <meta http-equiv="refresh" content="5;url={{ $redirectUrl ?? route('admin.login') }}">
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html { color-scheme: light dark; }
    </style>
</head>
<body class="antialiased flex min-h-screen items-center justify-center bg-gray-100 p-4 text-gray-900 dark:bg-slate-950 dark:text-gray-100">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center">
            <span class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $appName ?? config('app.name') }}</span>
        </div>
        @include('partials.redirect-message', ['redirectSeconds' => 5])
    </div>
</body>
</html>
