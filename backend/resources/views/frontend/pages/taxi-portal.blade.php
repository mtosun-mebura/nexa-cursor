@extends('frontend.layouts.app')

@section('title', ($branding['dashboard_link_label'] ?? 'Mijn Taxi').' - '.($branding['site_name'] ?? 'Nexa'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('metronic-v9.4.13/demo1/assets/vendors/keenicons/styles.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('metronic-v9.4.13/demo1/assets/css/styles.css') }}">
@endpush

@section('content')
    @php
        $logoAlt = $branding['site_name'] ?? config('app.name', 'Nexa');
        $logoHref = route('home');
        $logoLight = $branding['logo_url'] ?? asset('images/nexa-logo.png');
        $logoDark = $branding['logo_dark_url'] ?? $logoLight;
    @endphp

    <div
        id="taxi-portal-app"
        class="w-full flex-1"
        data-logo-alt="{{ $logoAlt }}"
        data-logo-href="{{ $logoHref }}"
        data-logo-light="{{ $logoLight }}"
        data-logo-dark="{{ $logoDark }}"
    ></div>
@endsection

@push('scripts')
    <script src="{{ asset('metronic-v9.4.13/demo1/assets/vendors/ktui/ktui.min.js') }}"></script>
    <script src="{{ asset('metronic-v9.4.13/demo1/assets/js/core.bundle.js') }}"></script>
    @vite('resources/js/taxi-portal-app.ts')
@endpush
