@php
    $totalVacancies = $company->vacancies()->where('status', 'active')->count();
    $totalUsers = \App\Models\User::where('company_id', $company->id)->count();
    $totalRevenue = \App\Models\Payment::where('company_id', $company->id)->where('status', 'paid')->sum('amount');
    $companyRank = \App\Models\Company::where('created_at', '<=', $company->created_at)->count();
    $locationsCount = $company->locations()->count();
    $activeVacancies = $company->vacancies()->where('status', 'active')->get();
    $totalMatches = \App\Models\JobMatch::whereHas('vacancy', function($q) use ($company) {
        $q->where('company_id', $company->id);
    })->count();
    $companyUsers = $company->users()->limit(8)->get();
@endphp

<style>
    .hero-bg {
        background-image: url('assets/media/images/2600x1200/bg-1.png');
    }
    .dark .hero-bg {
        background-image: url('assets/media/images/2600x1200/bg-1-dark.png');
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

<div class="kt-container-fixed mb-5 lg:mb-7.5">
    <div class="kt-card">
        <div class="kt-card-content">
            <div class="flex lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $totalVacancies }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Vacatures
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ number_format($totalUsers, 0, ',', '.') }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Gebruikers
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $totalMatches }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Matches
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5 mt-5 lg:mt-7.5">
        <div class="col-span-1">
            <div class="flex flex-col gap-5 lg:gap-7.5">
                {{-- Bedrijfsinformatie --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">
                            Bedrijfsinformatie
                        </h3>
                    </div>
                    <div class="kt-card-content pt-3.5 pb-3.5">
                        <table class="w-full">
                            <tbody>
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-4 pe-4 lg:pe-10 align-top" style="min-width: 120px;">
                                        Vestigingen:
                                    </td>
                                    <td class="text-sm text-mono pb-4 align-top">
                                        {{ $locationsCount + 1 }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-4 pe-4 lg:pe-10 align-top" style="min-width: 120px;">
                                        Opgericht:
                                    </td>
                                    <td class="text-sm text-mono pb-4 align-top">
                                        {{ $company->created_at->format('Y') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-4 pe-4 lg:pe-10 align-top" style="min-width: 120px;">
                                        Status:
                                    </td>
                                    <td class="text-sm text-mono pb-4 align-top">
                                        <span class="kt-badge kt-badge-sm {{ $company->is_active ? 'kt-badge-success' : 'kt-badge-warning' }} kt-badge-outline">
                                            {{ $company->is_active ? 'Actief' : 'Inactief' }}
                                        </span>
                                    </td>
                                </tr>
                                @if($company->country)
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-4 pe-4 lg:pe-10 align-top" style="min-width: 120px;">
                                        Land:
                                    </td>
                                    <td class="text-sm text-mono pb-4 align-top">
                                        {{ $company->country }}
                                    </td>
                                </tr>
                                @endif
                                @if($company->industry)
                                <tr>
                                    <td class="text-sm text-secondary-foreground pb-4 pe-4 lg:pe-10 align-top" style="min-width: 120px;">
                                        Sector:
                                    </td>
                                    <td class="text-sm text-mono pb-4 align-top">
                                        {{ $company->industry }}
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Open Jobs --}}
                @if($activeVacancies->count() > 0)
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">
                            Openstaande Vacatures
                        </h3>
                    </div>
                    <div class="kt-card-content">
                        <div class="grid gap-5">
                            @foreach($activeVacancies as $vacancy)
                            <div class="flex align-start gap-3.5">
                                <div class="flex items-center justify-center w-[1.875rem] h-[1.875rem] bg-accent/60 rounded-lg border border-input">
                                    <i class="ki-filled ki-briefcase text-base text-secondary-foreground"></i>
                                </div>
                                <div class="flex flex-col justify-start gap-1">
                                    <a class="text-sm font-semibold leading-none kt-link" href="{{ route('admin.vacancies.show', $vacancy) }}">
                                        {{ $vacancy->category->name ?? 'Vacature' }}
                                    </a>
                                    <span class="text-sm font-medium text-mono">
                                        {{ $vacancy->title }}
                                    </span>
                                    @if($vacancy->min_salary || $vacancy->max_salary)
                                        <span class="text-xs text-secondary-foreground">
                                            @if($vacancy->min_salary && $vacancy->max_salary)
                                                €{{ number_format($vacancy->min_salary, 0, ',', '.') }} - €{{ number_format($vacancy->max_salary, 0, ',', '.') }}
                                            @elseif($vacancy->min_salary)
                                                Vanaf €{{ number_format($vacancy->min_salary, 0, ',', '.') }}
                                            @elseif($vacancy->max_salary)
                                                Tot €{{ number_format($vacancy->max_salary, 0, ',', '.') }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @if($activeVacancies->count() > 10)
                    <div class="kt-card-footer justify-center">
                        <a class="kt-link kt-link-underlined kt-link-dashed" href="{{ route('admin.vacancies.index', ['company_id' => $company->id]) }}">
                            Bekijk alle vacatures
                        </a>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Gebruikers --}}
                @if($companyUsers->count() > 0)
                <div class="kt-card">
                    <div class="kt-card-header gap-2">
                        <h3 class="kt-card-title">
                            Gebruikers
                        </h3>
                    </div>
                    <div class="kt-card-content">
                        <div class="flex flex-col gap-3">
                            @foreach($companyUsers as $user)
                                <div class="flex items-center gap-3">
                                    @if($user->photo_blob)
                                        <img class="rounded-full h-[36px] w-[36px] object-cover shrink-0" src="{{ $user->photo_blob ? route('secure.photo', ['token' => $user->getPhotoToken()]) : asset('assets/media/avatars/300-2.png') }}" alt="{{ $user->first_name }} {{ $user->last_name }}"/>
                                    @else
                                        <div class="rounded-full h-[36px] w-[36px] bg-accent/60 border border-input flex items-center justify-center shrink-0">
                                            <span class="text-xs font-semibold text-secondary-foreground">
                                                {{ strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                    <div class="flex flex-col">
                                        <span class="text-sm font-semibold text-foreground leading-none">
                                            {{ $user->first_name }} {{ $user->last_name }}
                                        </span>
                                        @if($user->email)
                                        <span class="text-xs text-secondary-foreground leading-none mt-1">
                                            {{ $user->email }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @if($company->users()->count() > 8)
                    <div class="kt-card-footer justify-center">
                        <a class="kt-link kt-link-underlined kt-link-dashed" href="{{ route('admin.users.index', ['company_id' => $company->id]) }}">
                            Bekijk alle gebruikers
                        </a>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <div class="col-span-1 lg:col-span-2">
            <div class="flex flex-col gap-5 lg:gap-7.5">
                {{-- Company Profile --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">
                            Bedrijfsprofiel
                        </h3>
                        @can('edit-companies')
                        <a href="{{ route('admin.companies.edit', $company) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Bewerken">
                            <i class="ki-filled ki-pencil"></i>
                        </a>
                        @endcan
                    </div>
                    <div class="kt-card-content">
                        <h3 class="text-base font-semibold text-mono leading-none mb-5">
                            Hoofdkantoor
                        </h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 mb-10 items-stretch">
                            {{-- Expliciete px-hoogte: Google Maps vult anders een 0px canvas in grid/flex layouts. --}}
                            <div class="w-full min-w-0 min-h-[220px] self-stretch">
                                <div id="company_profile_map" class="w-full rounded-xl overflow-hidden bg-muted/20 shadow-sm" style="height: 220px; min-height: 220px; position: relative;" aria-label="Kaart hoofdkantoor"></div>
                            </div>
                            <div class="flex flex-col gap-3.5 min-w-0">
                                @if($company->website)
                                <div class="flex items-start gap-3">
                                    <i class="ki-filled ki-dribbble text-lg text-muted-foreground shrink-0 mt-0.5" aria-hidden="true"></i>
                                    <a class="link text-sm font-medium text-foreground leading-snug break-all min-w-0" href="{{ $company->website }}" target="_blank" rel="noopener noreferrer">
                                        {{ $company->website }}
                                    </a>
                                </div>
                                @endif
                                @if($company->email)
                                <div class="flex items-start gap-3">
                                    <i class="ki-filled ki-sms text-lg text-muted-foreground shrink-0 mt-0.5" aria-hidden="true"></i>
                                    <a class="link text-sm font-medium text-foreground leading-snug break-all min-w-0" href="mailto:{{ $company->email }}">
                                        {{ $company->email }}
                                    </a>
                                </div>
                                @endif
                                @if($company->phone)
                                <div class="flex items-start gap-3">
                                    <i class="ki-filled ki-whatsapp text-lg text-muted-foreground shrink-0 mt-0.5" aria-hidden="true"></i>
                                    <span class="text-sm font-medium text-foreground tabular-nums tracking-tight min-w-0">
                                        {{ $company->phone }}
                                    </span>
                                </div>
                                @endif
                                @php
                                    $address = '';
                                    if ($company->mainLocation) {
                                        $address = $company->mainLocation->street . ' ' . $company->mainLocation->house_number . ($company->mainLocation->house_number_extension ? '-' . $company->mainLocation->house_number_extension : '') . ', ' . $company->mainLocation->postal_code . ' ' . $company->mainLocation->city . ($company->mainLocation->country ? ', ' . $company->mainLocation->country : '');
                                    } elseif ($company->street) {
                                        $address = $company->street . ' ' . $company->house_number . ($company->house_number_extension ? '-' . $company->house_number_extension : '') . ', ' . $company->postal_code . ' ' . $company->city . ($company->country ? ', ' . $company->country : '');
                                    } else {
                                        $address = '430 E 6th St, New York, 10009.';
                                    }
                                @endphp
                                @if($address)
                                <div class="flex items-start gap-3">
                                    <i class="ki-filled ki-map text-lg text-muted-foreground shrink-0 mt-0.5" aria-hidden="true"></i>
                                    <span class="text-sm font-medium text-foreground leading-relaxed min-w-0">
                                        {{ $address }}
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @if($company->description)
                        <div class="grid gap-2.5 mb-7">
                            <div class="text-base font-semibold text-mono">
                                Over
                            </div>
                            <p class="text-sm text-foreground leading-5.5">
                                {{ $company->description }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Locations --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">
                            Vestigingen
                        </h3>
                    </div>
                    <div class="kt-card-content p-5 lg:p-7.5 lg:pb-7">
                        <div class="flex gap-5 kt-scrollable-x">
                            @php
                                $showHoofdkantoorCard = $company->shouldShowHoofdkantoorCardFromCompany();
                            @endphp
                            @if($showHoofdkantoorCard)
                                <div class="kt-card shadow-none w-[280px] border-0 mb-4 shrink-0">
                                    @if($company->buildingImageAssetUrl())
                                        <img alt="Hoofdkantoor" class="rounded-t-xl w-full max-w-[280px] h-[160px] object-cover shrink-0 bg-muted" src="{{ $company->buildingImageAssetUrl() }}"/>
                                    @elseif($company->logo_blob)
                                        <img alt="Hoofdkantoor" class="rounded-t-xl w-full max-w-[280px] h-[160px] object-cover shrink-0 bg-muted" src="{{ route('admin.companies.logo', $company) }}"/>
                                    @else
                                        <div class="rounded-t-xl w-full max-w-[280px] h-[160px] shrink-0 bg-primary/10 flex items-center justify-center text-primary text-2xl font-semibold">
                                            {{ strtoupper(substr($company->name, 0, 2)) }}
                                        </div>
                                    @endif
                                    <div class="kt-card-border kt-card-rounded-b px-3.5 h-full pt-3 pb-3.5">
                                        <a class="font-medium block text-mono hover:text-primary text-base mb-2" href="{{ route('admin.companies.edit', $company) }}">
                                            Hoofdkantoor
                                        </a>
                                        <p class="text-sm text-secondary-foreground">
                                            {{ $company->street }} {{ $company->house_number }}{{ $company->house_number_extension ? '-' . $company->house_number_extension : '' }} <br>
                                            {{ $company->postal_code }} {{ $company->city }} <br>
                                            {{ $company->country ? $company->country : '' }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                            @if($company->locations->count() > 0)
                                @foreach($company->locations as $index => $location)
                                    <div class="kt-card shadow-none w-[280px] border-0 mb-4 shrink-0">
                                        <img alt="{{ $location->name }}" class="rounded-t-xl max-w-[280px] shrink-0" src="{{ asset('assets/media/images/600x400/' . (($index % 3) + 10) . '.jpg') }}"/>
                                        <div class="kt-card-border kt-card-rounded-b px-3.5 h-full pt-3 pb-3.5">
                                            <a class="font-medium block text-mono hover:text-primary text-base mb-2" href="{{ route('admin.companies.locations.show', [$company, $location]) }}">
                                                {{ $location->name }}
                                            </a>
                                            <p class="text-sm text-secondary-foreground">
                                                {{ $location->street }} {{ $location->house_number }}{{ $location->house_number_extension ? '-' . $location->house_number_extension : '' }} <br> {{ $location->postal_code }} {{ $location->city }} <br> {{ $location->country ? $location->country : '' }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            @elseif(!$showHoofdkantoorCard)
                                <p class="text-sm text-secondary-foreground">
                                    Nog geen vestigingen. Voeg vestigingen toe via
                                    <a class="link font-medium" href="{{ route('admin.companies.locations.create', $company) }}">nieuwe vestiging</a>
                                    of vul het bedrijfsadres in bij
                                    <a class="link font-medium" href="{{ route('admin.companies.edit', $company) }}">bedrijfsgegevens</a>.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if(!empty($googleMapsApiKey))
<script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mapElement = document.getElementById('company_profile_map');
    if (!mapElement) return;

    @php
        $address = '';
        $lat = null;
        $lng = null;
        
        if ($company->mainLocation) {
            $address = $company->mainLocation->street . ' ' . $company->mainLocation->house_number . ($company->mainLocation->house_number_extension ? '-' . $company->mainLocation->house_number_extension : '') . ', ' . $company->mainLocation->postal_code . ' ' . $company->mainLocation->city . ($company->mainLocation->country ? ', ' . $company->mainLocation->country : '');
            $lat = $company->mainLocation->latitude;
            $lng = $company->mainLocation->longitude;
        } elseif ($company->street || $company->city) {
            $address = $company->street . ' ' . $company->house_number . ($company->house_number_extension ? '-' . $company->house_number_extension : '') . ', ' . $company->postal_code . ' ' . $company->city . ($company->country ? ', ' . $company->country : '');
            $lat = $company->latitude;
            $lng = $company->longitude;
        }
        
        $defaultLat = $googleMapsCenterLat;
        $defaultLng = $googleMapsCenterLng;
        $defaultZoom = $googleMapsZoom;
    @endphp

    function initGoogleMap() {
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            console.error('Google Maps API not loaded');
            return;
        }

        const mapLat = {{ $lat ?: 'null' }} || {{ $defaultLat }};
        const mapLng = {{ $lng ?: 'null' }} || {{ $defaultLng }};
        const mapZoom = {{ $lat && $lng ? '16' : $defaultZoom }};

        const googleMap = new google.maps.Map(mapElement, {
            center: { lat: mapLat, lng: mapLng },
            zoom: mapZoom,
            mapTypeId: '{{ $googleMapsType }}',
            mapTypeControl: false
        });

        function triggerMapResize() {
            if (google.maps && google.maps.event) {
                google.maps.event.trigger(googleMap, 'resize');
            }
        }
        google.maps.event.addListenerOnce(googleMap, 'idle', triggerMapResize);
        setTimeout(triggerMapResize, 250);
        window.addEventListener('resize', triggerMapResize);

        @if($address)
        const addressText = @json($address);
        
        @if($lat && $lng)
        // Alleen marker + tooltip (title); geen InfoWindow-ballon met sluitknop.
        new google.maps.Marker({
            position: { lat: {{ $lat }}, lng: {{ $lng }} },
            map: googleMap,
            title: addressText
        });
        @else
        // Geocode address if no coordinates
        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ address: addressText }, function(results, status) {
            if (status === 'OK' && results[0]) {
                const location = results[0].geometry.location;
                googleMap.setCenter(location);
                googleMap.setZoom(16);

                new google.maps.Marker({
                    position: location,
                    map: googleMap,
                    title: addressText
                });
            }
        });
        @endif
        @endif
    }

    // Wait for Google Maps to load
    function waitForGoogleMaps() {
        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
            initGoogleMap();
        } else {
            setTimeout(waitForGoogleMaps, 100);
        }
    }
    
    waitForGoogleMaps();
});
</script>
@endif
@endpush

