@extends('admin.layouts.app')

@section('title', 'Bedrijf Details - ' . $company->name)

@section('content')

@if(session('success'))
    <div class="kt-alert kt-alert-success mb-5" role="alert">
        <i class="ki-filled ki-check-circle me-2"></i>
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="kt-alert kt-alert-danger mb-5" role="alert">
        <i class="ki-filled ki-cross-circle me-2"></i>
        {{ session('error') }}
    </div>
@endif

<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @if($company->logo_blob)
                @php
                    $companyHeroLogoDarkUrl = ! empty($company->logo_dark_blob)
                        ? route('admin.companies.logo.dark', $company)
                        : route('admin.companies.logo', $company);
                @endphp
                <div class="rounded-lg shrink-0 inline-block" style="background: transparent; padding: 3px;">
                    <img class="logo-light rounded-lg w-auto object-contain bg-transparent dark:hidden" style="height: 80px; display: block; padding: 8px;" src="{{ route('admin.companies.logo', $company) }}" alt="{{ $company->name }}">
                    <img class="logo-dark rounded-lg w-auto object-contain bg-transparent hidden dark:block" style="height: 80px; display: block; padding: 8px;" src="{{ $companyHeroLogoDarkUrl }}" alt="{{ $company->name }}">
                </div>
            @else
                <div class="rounded-lg border-3 border-primary h-[100px] w-[100px] lg:h-[150px] lg:w-[150px] shrink-0 flex items-center justify-center bg-primary/10 text-primary text-2xl font-semibold">
                    {{ strtoupper(substr($company->name, 0, 2)) }}
                </div>
            @endif
            <div class="flex items-center gap-1.5">
                <x-heroicon-o-building-office-2 id="company-main-icon-hero" class="w-5 h-5 lg:w-6 lg:h-6 text-primary {{ ($company->is_main || $company->mainLocation) ? '' : 'hidden' }}" />
                <div class="text-xl lg:text-2xl leading-6 font-semibold text-mono">
                    {{ $company->name }}
                </div>
            </div>
            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <x-heroicon-o-building-office-2 class="w-4 h-4 text-muted-foreground" />
                    @php
                        $isActive = isset($company->is_active) ? $company->is_active : true;
                    @endphp
                    <span id="company-status-hero" class="font-medium {{ $isActive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $isActive ? 'Actief' : 'Inactief' }}
                    </span>
                </div>
                @if($company->city)
                    <div class="flex gap-1.25 items-center">
                        <i class="ki-filled ki-geolocation text-muted-foreground text-sm"></i>
                        <span class="text-secondary-foreground font-medium">
                            {{ $company->city }}{{ $company->country ? ', ' . $company->country : '' }}
                        </span>
                    </div>
                @endif
                @if($company->email)
                    <div class="flex gap-1.25 items-center">
                        <x-heroicon-o-envelope class="w-4 h-4 text-muted-foreground" />
                        <a class="text-secondary-foreground font-medium hover:text-primary" href="mailto:{{ $company->email }}">
                            {{ $company->email }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- End of Container -->
</div>

<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10">
        @if(auth()->user()->hasRole('super-admin'))
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.companies.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        @else
        <div></div>
        @endif
        @can('edit-companies')
        <div class="flex items-center gap-2.5">
            <div class="flex items-center gap-2">
                <form action="{{ route('admin.companies.toggle-main-location', $company) }}" method="POST" id="toggle-main-location-form-header" style="display: inline-flex; align-items: center;">
                    @csrf
                    <label class="kt-label mb-0 flex items-center">
                        <input type="checkbox" 
                               class="kt-switch kt-switch-sm" 
                               id="toggle-main-location-checkbox-header"
                               {{ $company->is_main || $company->mainLocation ? 'checked' : '' }}/>
                        <span class="ml-2">Hoofdkantoor</span>
                    </label>
                </form>
                <span class="text-orange-500 dark:text-orange-400 flex items-center">|</span>
                <form action="{{ route('admin.companies.toggle-status', $company) }}" method="POST" id="toggle-status-form-header" style="display: inline-flex; align-items: center;">
                    @csrf
                    <label class="kt-label mb-0 flex items-center" for="is_active_header">
                        <input type="checkbox" 
                               class="kt-switch kt-switch-sm" 
                               id="is_active_header"
                               {{ isset($company->is_active) && $company->is_active ? 'checked' : '' }}/>
                        <span class="ml-2">Actief</span>
                    </label>
                </form>
            </div>
            @if($company->hasSkillmatchingModule())
            <span class="text-orange-500 dark:text-orange-400 flex items-center">|</span>
            <a href="{{ route('admin.companies.pipeline-templates.index', $company) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-diagram-3 me-2"></i>
                Pipeline Templates
            </a>
            @endif
            <a href="{{ route('admin.companies.edit', $company) }}" class="kt-btn kt-btn-primary ml-auto">
                <i class="ki-filled ki-notepad-edit me-2"></i>
                Bewerken
            </a>
        </div>
        @endcan
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <!-- begin: grid — bedrijfsinfo eerst, contact eronder (volle breedte i.p.v. smalle kolom) -->
    <div class="flex flex-col gap-5 lg:gap-7.5 items-stretch">
        <!-- Bedrijfsinformatie -->
        <div class="kt-card w-full flex flex-col">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Bedrijfsinformatie
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">
                            Bedrijfsnaam
                        </td>
                        <td class="min-w-48 w-full text-foreground font-normal">
                            <div class="flex items-start gap-2">
                                <x-heroicon-o-building-office-2 id="company-main-icon-table" class="w-5 h-5 font-bold text-gray-700 dark:text-white flex-shrink-0 {{ ($company->is_main || $company->mainLocation) ? '' : 'hidden' }}" />
                                <span>{{ $company->name }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            KVK Nummer
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $company->kvk_number ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Branche
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $company->industry ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Bedrijfstype
                        </td>
                        <td class="text-foreground font-normal">
                            @if($company->is_intermediary)
                                <span class="kt-badge kt-badge-sm kt-badge-info">Tussenpartij / Recruiter</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-success">Directe werkgever</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">
                            Beschrijving
                        </td>
                        <td class="text-foreground font-normal break-words" style="word-wrap: break-word; overflow-wrap: break-word;">
                            {{ $company->description ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Gebouw-illustratie
                        </td>
                        <td class="text-foreground font-normal">
                            @php
                                $bi = (int) ($company->building_image ?? 0);
                                $biLabels = [1 => 'Oranje gevel', 2 => 'Twee torens', 3 => 'Wit minimalisme'];
                            @endphp
                            @if(isset($biLabels[$bi]))
                                <span class="inline-flex items-center gap-2">
                                    @if($company->buildingImageAssetUrl())
                                        <img src="{{ $company->buildingImageAssetUrl() }}" alt="" class="h-10 w-auto rounded border border-border" width="40" height="40">
                                    @endif
                                    <span>{{ $biLabels[$bi] }}</span>
                                </span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Contactpersoon
                        </td>
                        <td class="text-foreground font-normal">
                            @php
                                $cn = trim(implode(' ', array_filter([
                                    $company->contact_first_name,
                                    $company->contact_middle_name,
                                    $company->contact_last_name,
                                ])));
                            @endphp
                            {{ $cn !== '' ? $cn : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">
                            Bedrijf als hoofdkantoor (wizard)
                        </td>
                        <td class="text-foreground font-normal">
                            {{ $company->is_main ? 'Ja' : 'Nee' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Contact Informatie -->
        <div class="kt-card w-full flex flex-col">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Contact Informatie
                </h3>
            </div>
            <div class="kt-card-content">
                @php
                    // Eén adresbron voor weergave én kaart: hoofdkantoor of bedrijfsadres
                    $contactSource = $company->mainLocation ?: $company;
                    $addressLine1 = trim(($contactSource->street ?? '') . ' ' . ($contactSource->house_number ?? '') . (isset($contactSource->house_number_extension) && $contactSource->house_number_extension ? '-' . $contactSource->house_number_extension : ''));
                    $postalRaw = trim((string) ($contactSource->postal_code ?? ''));
                    // Weergave: NL "1234 AB" | Geocode-string: "1234AB" (zonder spatie) — gewenst voor betrouwbare Maps-load
                    $postalDisplay = $postalRaw;
                    $postalGeocode = str_replace(' ', '', $postalRaw);
                    if ($postalRaw !== '' && preg_match('/^(\d{4})\s*([A-Za-z]{2})$/u', str_replace(' ', '', $postalRaw), $pcm)) {
                        $postalDisplay = $pcm[1].' '.strtoupper($pcm[2]);
                        $postalGeocode = $pcm[1].strtoupper($pcm[2]);
                    }
                    $addressLine2 = trim($postalDisplay.' '.trim((string) ($contactSource->city ?? '')));
                    $addressLine2Geocode = trim($postalGeocode.' '.trim((string) ($contactSource->city ?? '')));
                    $addressLine3 = $contactSource->country ?? '';
                    $addressParts = array_filter([$addressLine1, $addressLine2, $addressLine3]);
                    $addressPartsForGeocode = array_filter([$addressLine1, $addressLine2Geocode, $addressLine3]);
                    $addrQuery = ! empty($addressPartsForGeocode) ? implode(', ', $addressPartsForGeocode) : '';
                    $mapCfgZoom = max(1, min(21, (int) (string) ($googleMapsZoom ?? 12)));
                    $mapCfgCenterLat = (float) ($googleMapsCenterLat ?? 52.3676);
                    $mapCfgCenterLng = (float) ($googleMapsCenterLng ?? 4.9041);
                    $mapCfgType = trim((string) ($googleMapsType ?? 'roadmap')) ?: 'roadmap';
                    $mapFallbackLat = null;
                    $mapFallbackLng = null;
                    $mfLat = $contactSource->latitude ?? null;
                    $mfLng = $contactSource->longitude ?? null;
                    if ($mfLat !== null && $mfLng !== null && is_numeric($mfLat) && is_numeric($mfLng)) {
                        $mfLat = (float) $mfLat;
                        $mfLng = (float) $mfLng;
                        if ($mfLat != 0.0 && $mfLng != 0.0 && abs($mfLat) <= 90 && abs($mfLng) <= 180) {
                            $mapFallbackLat = $mfLat;
                            $mapFallbackLng = $mfLng;
                        }
                    }
                    $resolvedLat = null;
                    $resolvedLng = null;
                    $mapsKeyTrim = trim((string) ($googleMapsApiKey ?? ''));
                    if ($mapsKeyTrim !== '' && $addrQuery !== '') {
                        try {
                            $cacheKey = 'maps.geocode.company-show.'.md5($addrQuery);
                            $resolved = \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400, function () use ($addrQuery, $mapsKeyTrim) {
                                $response = \Illuminate\Support\Facades\Http::timeout(8)->get(
                                    'https://maps.googleapis.com/maps/api/geocode/json',
                                    [
                                        'address' => $addrQuery,
                                        'key' => $mapsKeyTrim,
                                        'region' => 'nl',
                                    ]
                                );
                                if (! $response->successful()) {
                                    return null;
                                }
                                $data = $response->json();
                                if (($data['status'] ?? '') !== 'OK' || empty($data['results'][0]['geometry']['location'])) {
                                    return null;
                                }
                                $loc = $data['results'][0]['geometry']['location'];

                                return [
                                    'lat' => (float) $loc['lat'],
                                    'lng' => (float) $loc['lng'],
                                ];
                            });
                            if (is_array($resolved) && isset($resolved['lat'], $resolved['lng'])) {
                                $resolvedLat = $resolved['lat'];
                                $resolvedLng = $resolved['lng'];
                            }
                        } catch (\Throwable $e) {
                            $resolvedLat = null;
                            $resolvedLng = null;
                        }
                    }
                    $companyContactStaticMapUrl = null;
                    if ($mapsKeyTrim !== '') {
                        $pinLat = $resolvedLat ?? $mapFallbackLat;
                        $pinLng = $resolvedLng ?? $mapFallbackLng;
                        $mapTypeStatic = in_array($mapCfgType, ['roadmap', 'satellite', 'hybrid', 'terrain'], true) ? $mapCfgType : 'roadmap';
                        $staticParams = [
                            'size' => '640x296',
                            'scale' => '2',
                            'maptype' => $mapTypeStatic,
                            'key' => $mapsKeyTrim,
                            'language' => 'nl',
                            'region' => 'nl',
                        ];
                        if ($pinLat !== null && $pinLng !== null) {
                            $ll = sprintf('%.7f,%.7f', $pinLat, $pinLng);
                            $staticParams['center'] = $ll;
                            $staticParams['zoom'] = (string) $mapCfgZoom;
                            $staticParams['markers'] = 'color:red|'.$ll;
                        } elseif ($addrQuery !== '') {
                            $staticParams['center'] = $addrQuery;
                            $staticParams['zoom'] = (string) max(8, min(18, $mapCfgZoom));
                        } else {
                            $staticParams['center'] = sprintf('%.7f,%.7f', $mapCfgCenterLat, $mapCfgCenterLng);
                            $staticParams['zoom'] = (string) max(6, min(12, $mapCfgZoom));
                        }
                        $companyContactStaticMapUrl = 'https://maps.googleapis.com/maps/api/staticmap?'.http_build_query($staticParams, '', '&', PHP_QUERY_RFC3986);
                    }
                @endphp
                <div class="flex flex-col md:flex-row md:items-stretch gap-5">
                    <div class="rounded-xl w-full md:w-1/2 min-w-0 bg-muted/30 overflow-hidden flex items-center justify-center" id="company_contact_map" style="height: 208px;">
                        @if(! empty($companyContactStaticMapUrl))
                            <img src="{{ $companyContactStaticMapUrl }}"
                                 alt=""
                                 class="nexa-company-static-map w-full h-full max-w-none object-cover object-center rounded-xl"
                                 width="640"
                                 height="296"
                                 decoding="async"
                                 fetchpriority="low" />
                        @endif
                    </div>
                    <div class="flex flex-col gap-2.5 w-full md:w-1/2 min-w-0">
                        @if(!empty($addressParts))
                        <div class="flex items-start gap-2.5">
                            <span class="mt-0.5">
                                <i class="ki-filled ki-map text-lg text-muted-foreground"></i>
                            </span>
                            <div class="flex flex-col gap-0.5">
                                @foreach($addressParts as $part)
                                <span class="text-sm text-mono">{{ $part }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if($company->email)
                        <div class="flex items-center gap-2.5">
                            <span>
                                <i class="ki-filled ki-sms text-lg text-muted-foreground"></i>
                            </span>
                            <a class="link text-sm font-medium" href="mailto:{{ $company->email }}">
                                {{ $company->email }}
                            </a>
                        </div>
                        @endif
                        @if($company->phone)
                        <div class="flex items-center gap-2.5">
                            <span>
                                <i class="ki-filled ki-whatsapp text-lg text-muted-foreground"></i>
                            </span>
                            <span class="text-sm text-mono">
                                {{ $company->phone }}
                            </span>
                        </div>
                        @endif
                        @if($company->website)
                        <div class="flex items-center gap-2.5">
                            <span>
                                <i class="ki-filled ki-dribbble text-lg text-muted-foreground"></i>
                            </span>
                            <a class="link text-sm font-medium" href="{{ $company->website }}" target="_blank">
                                {{ $company->website }}
                            </a>
                        </div>
                        @endif
                        @if($company->latitude !== null && $company->latitude !== '' && $company->longitude !== null && $company->longitude !== '')
                        <div class="flex items-center gap-2.5">
                            <span>
                                <i class="ki-filled ki-geolocation text-lg text-muted-foreground"></i>
                            </span>
                            <span class="text-sm text-mono text-muted-foreground">{{ $company->latitude }}, {{ $company->longitude }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end: grid -->
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <div class="kt-card min-w-full mt-5 lg:mt-7.5">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-3">
            <h3 class="kt-card-title">
                Gekoppelde modules
            </h3>
            @can('edit-companies')
                <a href="{{ route('admin.companies.edit', $company) }}#company-modules" class="kt-btn kt-btn-sm kt-btn-outline">
                    <i class="ki-filled ki-notepad-edit me-1"></i>
                    Aanpassen
                </a>
            @endcan
        </div>
        <p class="text-sm text-secondary-foreground px-6 pt-2 pb-3 mb-0">
            Zelfde keuze als in de tenant-wizard (stap Modules). Alleen gekoppelde modules zijn beschikbaar voor dit bedrijf.
        </p>
        @if($company->modules->isEmpty())
            <div class="kt-card-content pb-6">
                <p class="text-sm text-muted-foreground mb-0">Geen modules gekoppeld.</p>
            </div>
        @else
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <thead>
                        <tr>
                            <th class="min-w-48 text-start">Module</th>
                            <th class="min-w-32 text-start">Technische naam</th>
                            <th class="min-w-32 text-start">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($company->modules->sortBy('display_name') as $mod)
                            <tr>
                                <td class="font-medium text-foreground">{{ $mod->display_name }}</td>
                                <td><code class="text-xs">{{ $mod->name }}</code></td>
                                <td>
                                    @if($mod->installed && $mod->active)
                                        <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                    @elseif($mod->installed)
                                        <span class="kt-badge kt-badge-sm kt-badge-warning">Geïnstalleerd</span>
                                    @else
                                        <span class="kt-badge kt-badge-sm kt-badge-outline">Niet geïnstalleerd</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <div class="kt-card min-w-full mt-5 lg:mt-7.5">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                Gebruikers &amp; website
            </h3>
        </div>
        <div class="kt-card-content">
            <ul class="list-none space-y-3 text-sm text-muted-foreground m-0 p-0">
                <li class="flex flex-wrap items-center gap-2">
                    <span class="text-foreground font-medium">{{ $company->users->count() }}</span>
                    <span>gebruiker(s) gekoppeld aan dit bedrijf.</span>
                    @can('view-users')
                        <a href="{{ route('admin.users.index', ['company' => $company->id]) }}" class="text-primary font-medium hover:underline">Gebruikers bekijken</a>
                    @endcan
                </li>
                @if(! empty($companyWebsiteDevPreviewUrl))
                    <li class="flex flex-wrap items-center gap-3">
                        <a href="{{ $companyWebsiteDevPreviewUrl }}" target="_blank" rel="noopener noreferrer" class="kt-btn kt-btn-sm kt-btn-outline shrink-0">
                            Website openen (dev)
                        </a>
                        <span class="text-xs text-muted-foreground">
                            Opent <code class="text-xs">{{ parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost' }}</code> met <code class="text-xs">{{ config('tenancy.dev_effective_host_query_param') }}={{ $companyWebsiteDevPreviewHost }}</code>.
                        </span>
                    </li>
                @endif
                @if(auth()->user()->hasRole('super-admin'))
                    <li class="flex flex-wrap items-center gap-2">
                        <span>Website-pagina's voor deze tenant beheren (zoals in wizard stap Website).</span>
                        <a href="{{ route('admin.website-pages.index', ['from_wizard' => 1, 'wizard_company' => $company->id, 'wizard_step' => 6]) }}" class="text-primary font-medium hover:underline">Naar website-pagina's</a>
                    </li>
                    <li class="flex flex-col gap-1 pt-2 border-t border-border mt-2">
                        <p class="text-xs text-muted-foreground mb-0">Volledige tenant-ZIP (pagina’s, media, tenant-instellingen): <strong class="text-foreground font-medium">Configuraties → Omgeving-sync</strong>. Database-push naar een andere omgeving staat daar ook.</p>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <div class="kt-card min-w-full mt-5 lg:mt-7.5">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                Tenant domeinen (SaaS)
            </h3>
        </div>
        <p class="text-sm text-secondary-foreground px-6 pt-2 pb-3 mb-0">
            Bezoekers die via deze host binnenkomen krijgen de tenant-context van dit bedrijf. De host uit <code class="text-xs">APP_URL</code> en domeinen in <code class="text-xs">TENANCY_CENTRAL_DOMAINS</code> worden niet als tenant opgelost.
        </p>

        <p id="company-domains-empty" class="text-sm text-secondary-foreground px-6 pb-4 mb-0 {{ $company->domains->isNotEmpty() ? 'hidden' : '' }}">Nog geen domeinen gekoppeld.</p>

        <div id="company-domains-table-wrap" class="kt-card-table kt-scrollable-x-auto pb-3 {{ $company->domains->isEmpty() ? 'hidden' : '' }}">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                <thead>
                    <tr>
                        <th class="min-w-48 text-start">Host</th>
                        <th class="min-w-32 text-start">Primair</th>
                        @can('edit-companies')
                        <th class="w-[120px] text-end">Acties</th>
                        @endcan
                    </tr>
                </thead>
                <tbody id="company-domains-tbody">
                    @include('admin.companies.partials.domain-table-rows', ['company' => $company])
                </tbody>
            </table>
        </div>

        @can('edit-companies')
        <form id="company-domain-add-form" action="{{ route('admin.companies.domains.store', $company) }}" method="post" class="{{ $company->domains->isNotEmpty() ? 'border-t border-border' : 'pt-2' }}">
            @csrf
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Hostnaam</td>
                        <td class="min-w-48 w-full">
                            <input type="text" name="host" id="domain_host" value="{{ old('host') }}" class="kt-input @error('host') border-destructive @enderror" placeholder="bijv. klant.jouwdomein.nl" required autocomplete="off" @error('host') data-server-error="1" @enderror>
                            <div id="domain-host-error-ajax" class="text-xs text-destructive mt-1 hidden" role="alert"></div>
                            @error('host')
                                <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="host">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Primair domein</td>
                        <td class="min-w-48 w-full">
                            <input type="hidden" name="is_primary" value="0">
                            <label class="kt-label flex items-center gap-2 mb-0">
                                <input type="checkbox" name="is_primary" value="1" class="kt-switch kt-switch-sm" {{ old('is_primary') ? 'checked' : '' }}>
                                Instellen als primair domein
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top"></td>
                        <td class="min-w-48 w-full">
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus me-2"></i>
                                Domein toevoegen
                            </button>
                        </td>
                    </tr>
                </table>
            </div>
        </form>
        @endcan
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <!-- Vestigingen -->
    <div class="kt-card min-w-full mt-5 lg:mt-7.5">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                Vestigingen
            </h3>
            @can('edit-companies')
            <a href="{{ route('admin.companies.locations.create', $company) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuwe Vestiging
            </a>
            @endcan
        </div>
        <div class="kt-card-content">
            @if($company->locations->count() > 0)
                <div class="kt-scrollable-x-auto">
                    <table class="kt-table table-auto kt-table-border">
                        <thead>
                            <tr>
                                <th>Naam</th>
                                <th>Adres</th>
                                <th>Contact</th>
                                <th>Status</th>
                                @can('edit-companies')
                                <th class="w-[60px] text-center">Acties</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($company->locations as $location)
                                <tr class="location-row cursor-pointer" data-href="{{ route('admin.companies.locations.show', [$company, $location]) }}" style="cursor: pointer;">
                                    <td>
                                        <div class="flex items-center gap-2">
                                            @if($location->is_main)
                                                <x-heroicon-o-building-office-2 class="w-5 h-5 font-bold text-gray-700 dark:text-white" />
                                            @endif
                                            {{ $location->name }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($location->street || $location->city)
                                            {{ $location->street }} {{ $location->house_number }}{{ $location->house_number_extension ? '-' . $location->house_number_extension : '' }}<br>
                                            {{ $location->postal_code }} {{ $location->city }}<br>
                                            {{ $location->country }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($location->phone || $location->email)
                                            <div class="flex flex-col gap-1">
                                                @if($location->phone)
                                                    <div><i class="ki-filled ki-phone me-1"></i> {{ $location->phone }}</div>
                                                @endif
                                                @if($location->email)
                                                    <div><i class="ki-filled ki-sms me-1"></i> {{ $location->email }}</div>
                                                @endif
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="location-status-cell">
                                        @can('edit-companies')
                                        @if($location->is_active)
                                            <form action="{{ route('admin.companies.locations.toggle-status', [$company, $location]) }}" method="POST" class="location-toggle-status-form-{{ $location->id }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-success location-status-button" data-location-id="{{ $location->id }}" data-company-id="{{ $company->id }}">
                                                    Actief
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.companies.locations.toggle-status', [$company, $location]) }}" method="POST" class="location-toggle-status-form-{{ $location->id }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-danger location-status-button" data-location-id="{{ $location->id }}" data-company-id="{{ $company->id }}">
                                                    Inactief
                                                </button>
                                            </form>
                                        @endif
                                        @else
                                        @if($location->is_active)
                                            <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                        @else
                                            <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                        @endif
                                        @endcan
                                    </td>
                                    @can('edit-companies')
                                    <td class="w-[60px] location-actions-cell">
                                        <div class="kt-menu flex justify-center" data-kt-menu="true">
                                            <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                    <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                </button>
                                                <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                    <div class="kt-menu-item">
                                                        <a class="kt-menu-link" href="{{ route('admin.companies.locations.edit', [$company, $location]) }}">
                                                            <span class="kt-menu-icon">
                                                                <i class="ki-filled ki-pencil"></i>
                                                            </span>
                                                            <span class="kt-menu-title">Bewerken</span>
                                                        </a>
                                                    </div>
                                                    <div class="kt-menu-separator"></div>
                                                    @if($location->is_active)
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.companies.locations.toggle-status', [$company, $location]) }}" method="POST" class="location-toggle-status-form-menu-{{ $location->id }}" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="kt-menu-link w-full text-left text-danger location-status-button-menu" data-location-id="{{ $location->id }}" data-company-id="{{ $company->id }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-cross-circle"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Deactiveren</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @else
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.companies.locations.toggle-status', [$company, $location]) }}" method="POST" class="location-toggle-status-form-menu-{{ $location->id }}" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="kt-menu-link w-full text-left text-success location-status-button-menu" data-location-id="{{ $location->id }}" data-company-id="{{ $company->id }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-check-circle"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Activeren</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @endif
                                                    @can('delete-companies')
                                                    <div class="kt-menu-separator"></div>
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.companies.locations.destroy', [$company, $location]) }}"
                                                              method="POST"
                                                              style="display: inline;"
                                                              onsubmit="return confirm('Weet je zeker dat je deze vestiging wilt verwijderen?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="kt-menu-link w-full text-left text-danger">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-trash"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Verwijderen</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-muted-foreground">
                    <i class="ki-filled ki-information-5 text-4xl mb-2"></i>
                    <p>Nog geen vestigingen toegevoegd.</p>
                </div>
            @endif
        </div>
    </div>
</div>
<!-- End of Container -->

@push('scripts')
@can('edit-companies')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Main location toggle (header)
        const mainLocationCheckboxHeader = document.getElementById('toggle-main-location-checkbox-header');
        const mainLocationFormHeader = document.getElementById('toggle-main-location-form-header');
        
        if (mainLocationCheckboxHeader && mainLocationFormHeader) {
            mainLocationCheckboxHeader.addEventListener('change', function(e) {
                e.preventDefault();
                
                const formData = new FormData(mainLocationFormHeader);
                const url = mainLocationFormHeader.action;
                const originalChecked = this.checked;
                
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    // First check if response is ok
                    if (!response.ok) {
                        // Try to get error message from response
                        return response.text().then(text => {
                            let errorMessage = 'Network response was not ok';
                            try {
                                const jsonData = JSON.parse(text);
                                errorMessage = jsonData.message || jsonData.error || errorMessage;
                            } catch (e) {
                                // If not JSON, use status text
                                errorMessage = response.statusText || errorMessage;
                            }
                            throw new Error(errorMessage + ' (Status: ' + response.status + ')');
                        });
                    }
                    // Try to parse as JSON
                    return response.json().catch(() => {
                        throw new Error('Invalid JSON response from server');
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Update checkbox state based on response
                        if (data.has_main_location !== undefined) {
                            this.checked = data.has_main_location;
                            
                            // Show/hide main location icons
                            const heroIcon = document.getElementById('company-main-icon-hero');
                            const tableIcon = document.getElementById('company-main-icon-table');
                            
                            if (data.has_main_location) {
                                // Show icons
                                if (heroIcon) {
                                    heroIcon.classList.remove('hidden');
                                }
                                if (tableIcon) {
                                    tableIcon.classList.remove('hidden');
                                }
                            } else {
                                // Hide icons
                                if (heroIcon) {
                                    heroIcon.classList.add('hidden');
                                }
                                if (tableIcon) {
                                    tableIcon.classList.add('hidden');
                                }
                            }
                        }
                    } else {
                        throw new Error(data.message || 'Wijziging mislukt');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack
                    });
                    // Revert checkbox state on error
                    this.checked = !originalChecked;
                    // Show detailed error message
                    const errorMessage = error.message || 'Er is een fout opgetreden bij het wijzigen van het hoofdkantoor.';
                    alert('Fout: ' + errorMessage);
                });
            });
        }
        
        // Company status toggle (header)
        const isActiveHeader = document.getElementById('is_active_header');
        const isActiveFormHeader = document.getElementById('toggle-status-form-header');
        
        if (isActiveHeader && isActiveFormHeader) {
            isActiveHeader.addEventListener('change', function(e) {
                e.preventDefault();
                
                const formData = new FormData(isActiveFormHeader);
                const url = isActiveFormHeader.action;
                const originalChecked = this.checked;
                
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    // First check if response is ok
                    if (!response.ok) {
                        // Try to get error message from response
                        return response.text().then(text => {
                            let errorMessage = 'Network response was not ok';
                            try {
                                const jsonData = JSON.parse(text);
                                errorMessage = jsonData.message || jsonData.error || errorMessage;
                            } catch (e) {
                                // If not JSON, use status text
                                errorMessage = response.statusText || errorMessage;
                            }
                            throw new Error(errorMessage + ' (Status: ' + response.status + ')');
                        });
                    }
                    // Try to parse as JSON
                    return response.json().catch(() => {
                        throw new Error('Invalid JSON response from server');
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Update checkbox state based on response
                        if (data.is_active !== undefined) {
                            this.checked = data.is_active;
                            
                            // Update hero status text
                            const heroStatusElement = document.getElementById('company-status-hero');
                            if (heroStatusElement) {
                                if (data.is_active) {
                                    heroStatusElement.textContent = 'Actief';
                                    heroStatusElement.className = 'font-medium text-green-600 dark:text-green-400';
                                } else {
                                    heroStatusElement.textContent = 'Inactief';
                                    heroStatusElement.className = 'font-medium text-red-600 dark:text-red-400';
                                }
                            }
                        }
                    } else {
                        throw new Error(data.message || 'Wijziging mislukt');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack
                    });
                    // Revert checkbox state on error
                    this.checked = !originalChecked;
                    // Show detailed error message
                    const errorMessage = error.message || 'Er is een fout opgetreden bij het wijzigen van de status.';
                    alert('Fout: ' + errorMessage);
                });
            });
        }
        
        // Location status buttons (table cell)
        const locationStatusButtons = document.querySelectorAll('.location-status-button');
        locationStatusButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const locationId = this.getAttribute('data-location-id');
                const companyId = this.getAttribute('data-company-id');
                const form = this.closest('form');
                const formData = new FormData(form);
                const url = form.action;
                const originalButton = this;
                const originalText = this.textContent;
                const originalClass = this.className;
                
                // Disable button during request
                this.disabled = true;
                
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.is_active !== undefined) {
                        // Update button based on new status
                        if (data.is_active) {
                            originalButton.textContent = 'Actief';
                            originalButton.className = 'kt-btn kt-btn-sm kt-btn-success location-status-button';
                        } else {
                            originalButton.textContent = 'Inactief';
                            originalButton.className = 'kt-btn kt-btn-sm kt-btn-danger location-status-button';
                        }
                        originalButton.setAttribute('data-location-id', locationId);
                        originalButton.setAttribute('data-company-id', companyId);
                        
                        // Reload page to update menu items
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Er is een fout opgetreden bij het wijzigen van de status.');
                    originalButton.disabled = false;
                });
            });
        });
        
        // Location status buttons (menu dropdown)
        const locationStatusMenuButtons = document.querySelectorAll('.location-status-button-menu');
        locationStatusMenuButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const locationId = this.getAttribute('data-location-id');
                const companyId = this.getAttribute('data-company-id');
                const form = this.closest('form');
                const formData = new FormData(form);
                const url = form.action;
                
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Reload page to update status
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Er is een fout opgetreden bij het wijzigen van de status.');
                });
            });
        });
        
        // Make location table rows clickable (except actions column and status toggle)
        document.addEventListener('click', function(e) {
            const row = e.target.closest('tr');
            if (!row || !row.classList.contains('location-row')) return;

            // Don't navigate if clicking on actions column, status column, menu, or buttons
            if (e.target.closest('.location-actions-cell') || 
                e.target.closest('.location-status-cell') || 
                e.target.closest('.kt-menu') || 
                e.target.closest('button') || 
                e.target.closest('a') ||
                e.target.closest('form')) {
                return;
            }

            // Try to get URL from data-href
            let url = row.getAttribute('data-href');
            if (url) {
                window.location.href = url;
            }
        });
        
        // Stop propagation for status button clicks
        document.querySelectorAll('.location-status-button, .location-status-button-menu').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        // Tenant domeinen: tabel bijwerken zonder volledige pagina-refresh
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const domainTbody = document.getElementById('company-domains-tbody');
        const domainEmptyMsg = document.getElementById('company-domains-empty');
        const domainTableWrap = document.getElementById('company-domains-table-wrap');
        const domainAddForm = document.getElementById('company-domain-add-form');

        function applyCompanyDomainsTable(data) {
            if (domainTbody && data.tbody_html !== undefined) {
                domainTbody.innerHTML = data.tbody_html;
            }
            const hasDomains = data.has_domains !== false;
            if (hasDomains) {
                domainEmptyMsg?.classList.add('hidden');
                domainTableWrap?.classList.remove('hidden');
                domainAddForm?.classList.add('border-t', 'border-border');
                domainAddForm?.classList.remove('pt-2');
            } else {
                domainEmptyMsg?.classList.remove('hidden');
                domainTableWrap?.classList.add('hidden');
                domainAddForm?.classList.remove('border-t', 'border-border');
                domainAddForm?.classList.add('pt-2');
            }
        }

        function fetchJsonDomainAction(url, formData) {
            return fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            }).then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, status: response.status, data: data };
                });
            });
        }

        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement) || !form.classList.contains('js-company-domain-action')) {
                return;
            }
            e.preventDefault();
            if (form.getAttribute('data-domain-destroy') === '1') {
                if (!window.confirm('Domein verwijderen?')) {
                    return;
                }
            }
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            fetchJsonDomainAction(form.action, new FormData(form))
                .then(function(result) {
                    if (!result.ok) {
                        throw new Error((result.data && result.data.message) ? result.data.message : 'Actie mislukt');
                    }
                    applyCompanyDomainsTable(result.data);
                })
                .catch(function(err) {
                    alert(err.message || 'Er is een fout opgetreden.');
                })
                .finally(function() {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                });
        });

        if (domainAddForm) {
            const hostInput = document.getElementById('domain_host');
            const ajaxErr = document.getElementById('domain-host-error-ajax');

            function clearDomainHostErrors() {
                if (ajaxErr) {
                    ajaxErr.textContent = '';
                    ajaxErr.classList.add('hidden');
                }
                if (hostInput) {
                    hostInput.classList.remove('border-destructive');
                }
            }

            hostInput?.addEventListener('input', clearDomainHostErrors);

            domainAddForm.addEventListener('submit', function(e) {
                e.preventDefault();
                clearDomainHostErrors();
                const submitBtn = domainAddForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }

                fetchJsonDomainAction(domainAddForm.action, new FormData(domainAddForm))
                .then(function(result) {
                    if (!result.ok) {
                        if (result.status === 422 && result.data && result.data.errors && result.data.errors.host) {
                            const msg = Array.isArray(result.data.errors.host) ? result.data.errors.host[0] : result.data.errors.host;
                            if (ajaxErr) {
                                ajaxErr.textContent = msg;
                                ajaxErr.classList.remove('hidden');
                            }
                            if (hostInput) {
                                hostInput.classList.add('border-destructive');
                            }
                            return;
                        }
                        throw new Error((result.data && result.data.message) ? result.data.message : 'Opslaan mislukt');
                    }
                    applyCompanyDomainsTable(result.data);
                    domainAddForm.reset();
                })
                .catch(function(err) {
                    alert(err.message || 'Er is een fout opgetreden bij het toevoegen van het domein.');
                })
                .finally(function() {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                });
            });
        }
    });
</script>
@endcan

@endpush

@push('styles')
<style>
    /* Success and Danger button styles */
    .kt-btn-success {
        background-color: #10b981;
        color: white;
    }
    .kt-btn-success:hover {
        background-color: #059669;
    }
    .kt-btn-danger {
        background-color: #ef4444;
        color: white;
    }
    .kt-btn-danger:hover {
        background-color: #dc2626;
    }
    .dark .kt-btn-success {
        background-color: #059669;
    }
    .dark .kt-btn-success:hover {
        background-color: #047857;
    }
    .dark .kt-btn-danger {
        background-color: #dc2626;
    }
    .dark .kt-btn-danger:hover {
        background-color: #b91c1c;
    }
    
    /* Vertical alignment for Hoofdkantoor and Actief toggles */
    #toggle-main-location-form-header,
    #toggle-status-form-header {
        display: inline-flex !important;
        align-items: center !important;
        vertical-align: middle;
    }
    
    #toggle-main-location-form-header label,
    #toggle-status-form-header label,
    label[for="is_active_header"] {
        display: flex !important;
        align-items: center !important;
        margin-bottom: 0 !important;
        vertical-align: middle;
    }
    
    #toggle-main-location-checkbox-header,
    #is_active_header {
        vertical-align: middle;
    }
    
    /* Remove all borders between table rows in show forms */
    .kt-table-border-dashed tbody tr {
        border-bottom: none !important;
    }
    /* Uniform row height for all table rows */
    .kt-table-border-dashed tbody tr,
    .kt-table-border-dashed tbody tr td {
        height: auto;
        min-height: 48px;
    }
    .kt-table-border-dashed tbody tr td {
        padding-top: 12px;
        padding-bottom: 12px;
        vertical-align: top;
    }
    
    /* Labels (first column) should align with top of content */
    .kt-table-border-dashed tbody tr td:first-child {
        vertical-align: top;
        padding-top: 12px;
    }
    
    /* Content (second column) should align with top */
    .kt-table-border-dashed tbody tr td:last-child {
        vertical-align: top;
        padding-top: 12px;
    }
    
    /* Ensure all table cells align to top */
    .kt-table-border-dashed tbody tr td {
        vertical-align: top !important;
    }
    
    /* Location row hover styling (same as company-row on index page) */
    .location-row {
        cursor: pointer !important;
    }
    .location-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .location-row:hover {
            background-color: color-mix(in oklab, var(--muted) 50%, transparent) !important;
        }
    }
</style>
@endpush

@endsection
