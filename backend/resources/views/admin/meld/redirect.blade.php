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
    @include('partials.redirect-page-styles')
</head>
<body class="redirect-page-body antialiased">
    <div class="redirect-page-shell">
        <div class="redirect-page-logo">
            @include('partials.nexa-brand-logo', ['class' => 'h-10 w-auto mx-auto object-contain'])
        </div>
        @include('partials.redirect-message', ['redirectSeconds' => 5])
    </div>
</body>
</html>
