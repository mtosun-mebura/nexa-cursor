{{-- Standaard NEXA-logo (zelfde als admin login). Root-relatief = werkt op elk host/subdomein. --}}
@php
    $nexaLogoClass = $class ?? 'h-10 w-auto mx-auto object-contain';
    $nexaLogoSrc = '/images/nexa-logo.png';
    $nexaLogoAlt = $alt ?? 'NEXA';
@endphp
<img
    src="{{ $nexaLogoSrc }}"
    alt="{{ $nexaLogoAlt }}"
    class="{{ $nexaLogoClass }}"
    width="140"
    height="40"
    decoding="async"
/>
