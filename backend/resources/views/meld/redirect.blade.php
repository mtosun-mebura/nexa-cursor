<!DOCTYPE html>
<html class="h-full" lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.redirect-page-theme', ['flavor' => 'frontend'])
    @include('partials.redirect-page-styles')
    <title>{{ $title ?? 'Doorsturen' }} - NEXA Skillmatching</title>
</head>
<body class="redirect-page-body antialiased">
    <div class="redirect-page-shell">
        <div class="redirect-page-logo">
            <a href="{{ url('/') }}">
                @include('partials.nexa-brand-logo', ['class' => 'mx-auto h-10 w-auto object-contain'])
            </a>
        </div>
        @include('partials.redirect-message')
    </div>
</body>
</html>
