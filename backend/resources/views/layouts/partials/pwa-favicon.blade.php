@php
    $faviconMeta = app(\App\Services\WebsiteBuilderService::class)->publicFaviconMeta();
    $faviconUrl = $faviconMeta['url'];
    $faviconType = $faviconMeta['type'];
@endphp
<link rel="manifest" href="{{ route('admin.manifest') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="theme-color" content="#2563eb">
<link href="{{ $faviconUrl }}" rel="apple-touch-icon">
<link href="{{ $faviconUrl }}" rel="icon" type="{{ $faviconType }}">
<link href="{{ $faviconUrl }}" rel="shortcut icon" type="{{ $faviconType }}">
