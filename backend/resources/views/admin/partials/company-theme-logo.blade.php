{{-- Light- én dark-logo van de tenant; zichtbaarheid via .logo-light / .logo-dark in de admin-layout. --}}
@php
    $logoCompany = $company ?? null;
    $logoHeight = $height ?? 80;
    $logoClass = $class ?? 'rounded-lg w-auto object-contain bg-transparent';
@endphp
@if($logoCompany && $logoCompany->hasAdminLogo())
    <div class="rounded-lg shrink-0 inline-block" style="background: transparent; padding: 3px;">
        <img class="logo-light {{ $logoClass }}"
             style="height: {{ (int) $logoHeight }}px; padding: 8px;"
             src="{{ $logoCompany->adminLogoLightUrl() }}"
             alt="{{ $logoCompany->name }}">
        <img class="logo-dark {{ $logoClass }}"
             style="height: {{ (int) $logoHeight }}px; padding: 8px;"
             src="{{ $logoCompany->adminLogoDarkUrl() }}"
             alt="{{ $logoCompany->name }}">
    </div>
@endif
