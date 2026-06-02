@extends('frontend.layouts.app')

@section('title', ($branding['dashboard_link_label'] ?? 'Mijn Taxi').' - '.($branding['site_name'] ?? 'Nexa'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('metronic-v9.4.13/demo1/assets/vendors/keenicons/styles.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('metronic-v9.4.13/demo1/assets/css/styles.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('metronic-v9.4.13/demo1/assets/vendors/ktui/ktui.min.js') }}"></script>
    <script src="{{ asset('metronic-v9.4.13/demo1/assets/js/core.bundle.js') }}"></script>
    @vite('resources/js/taxi-portal-app.ts')
@endpush

@section('content')
<div class="demo1 kt-sidebar-fixed kt-header-fixed flex h-full bg-background text-base text-foreground antialiased">
    <div id="taxi-portal-app" class="min-h-[calc(100vh-0px)] w-full"></div>
</div>
@endsection
