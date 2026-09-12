@extends('admin.layouts.app')

@section('title', 'Rit #'.$ride->id)

@section('content')
<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
    .ride-track-map-wrap {
        position: relative;
    }
    .ride-track-map {
        width: 100%;
        height: min(52vh, 480px);
        min-height: 320px;
        background: #e5e7eb;
    }
    html.dark .ride-track-map {
        background: #242f3e;
    }
    .ride-track-overlay {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 2;
        max-width: min(22rem, calc(100% - 24px));
        border-radius: 14px;
        border: 1px solid color-mix(in oklab, var(--foreground) 12%, var(--border));
        background: #ffffff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        padding: 12px 14px;
        pointer-events: none;
    }
    html.dark .ride-track-overlay {
        background: #0b0f19;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.35);
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <div class="kt-container-fixed min-w-0 px-3">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @if($ride->vehicle?->image_url)
                <img
                    src="{{ app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($ride->vehicle->image_url) }}"
                    alt="{{ $ride->vehicle->name ?? 'Voertuig' }}"
                    class="w-full max-w-sm max-h-48 object-contain rounded-lg border border-border bg-white"
                >
            @elseif($ride->vehicle)
                <div class="w-full max-w-sm h-48 rounded-lg border border-border flex items-center justify-center bg-primary/10 text-primary text-3xl font-semibold">
                    <i class="ki-filled ki-car"></i>
                </div>
            @endif

            <div class="text-lg leading-5 font-semibold text-mono text-center">
                Rit #{{ $ride->id }}
            </div>
            @if($ride->isNexaSuiteBooking())
                <span class="kt-badge kt-badge-outline kt-badge-warning rounded-[30px] mt-1">Algemene boeking via NEXA Suite</span>
            @endif

            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <x-heroicon-o-building-office-2 class="w-4 h-4 text-muted-foreground" />
                    <span class="text-secondary-foreground font-medium">{{ $ride->vehicle?->company?->name ?? '—' }}</span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-car text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">{{ $ride->vehicle?->name ?? '—' }}</span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-calendar text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">{{ $ride->pickup_at->format('d-m-Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed min-w-0">
    <div class="admin-page-actions flex flex-wrap items-center gap-2.5 pb-7.5 w-full min-w-0">
        <a href="{{ ($rideBackUrl ?? route('admin.taxi.ride_requests.index')) }}#ritten-overzicht" class="kt-btn kt-btn-outline shrink-0"><i class="ki-filled ki-arrow-left me-2"></i>Terug</a>
        @can('rides.view')
            @if($notificationLogTableExists ?? false)
            <a href="{{ route('admin.taxi.ride_requests.notification_log', $ride) }}" class="kt-btn kt-btn-outline">
                Notificatielog
                @if(($notificationLogCount ?? 0) > 0)
                    ({{ $notificationLogCount }})
                @endif
            </a>
            @endif
        @endcan
        @can('rides.update')
        <a href="{{ route('admin.taxi.ride_requests.edit', $ride) }}" class="kt-btn kt-btn-outline">Bewerken</a>
        @if(($dispatchTablesExist ?? false) && $ride->canRedispatchToDrivers())
        <form
            action="{{ route('admin.taxi.ride_requests.reoffer_dispatch', $ride) }}"
            method="POST"
            class="inline"
            onsubmit="return confirm('Rit opnieuw aanbieden aan chauffeurs?\n\nDe huidige toewijzing (voertuig/chauffeur) wordt gewist en online chauffeurs ontvangen een nieuw aanbod.');"
        >
            @csrf
            <button type="submit" class="kt-btn kt-btn-outline">Opnieuw aanbieden</button>
        </form>
        @endif
        @endcan
    </div>

    @php
        $rideTrack = $rideTrack ?? [];
        $hasRideMap = (($rideTrack['path'] ?? []) !== []) || !empty($rideTrack['pickup']) || !empty($rideTrack['dropoff']);
        $actualDistanceLabel = $rideTrack['distance_label'] ?? null;
        $actualDurationLabel = $rideTrack['duration_label'] ?? null;
        $plannedDistanceLabel = $rideTrack['planned_distance_label'] ?? ($ride->distance_km !== null ? $ride->distance_km.' km' : null);
        $plannedDurationLabel = $rideTrack['planned_duration_label'] ?? ($ride->duration_minutes !== null ? $ride->duration_minutes.' min' : null);
        $hasRecordedTrack = !empty($rideTrack['has_recorded_track']);
    @endphp

    @if($hasRideMap)
    <div class="kt-card w-full min-w-0 mb-5 overflow-hidden">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
            <h3 class="kt-card-title mb-0">Afgelegde rit</h3>
            @if($hasRecordedTrack)
                <span class="text-sm text-muted-foreground">GPS-spoor van de chauffeur</span>
            @else
                <span class="text-sm text-muted-foreground">Geplande route (nog geen GPS-spoor)</span>
            @endif
        </div>
        <div class="ride-track-map-wrap">
            <div id="ride-track-map" class="ride-track-map"></div>
            @if($actualDistanceLabel || $actualDurationLabel || $plannedDistanceLabel || $plannedDurationLabel)
            <div class="ride-track-overlay text-sm">
                @if($actualDistanceLabel)
                    <div class="font-semibold text-foreground">{{ $actualDistanceLabel }}@if($actualDurationLabel) · {{ $actualDurationLabel }}@endif</div>
                @elseif($actualDurationLabel)
                    <div class="font-semibold text-foreground">{{ $actualDurationLabel }}</div>
                @endif
                @if($hasRecordedTrack && ($rideTrack['started_at'] || $rideTrack['completed_at']))
                    <div class="text-muted-foreground mt-0.5">
                        {{ $rideTrack['started_at'] ?? '—' }} – {{ $rideTrack['completed_at'] ?? '—' }}
                    </div>
                @endif
                @if($plannedDistanceLabel || $plannedDurationLabel)
                    <div class="text-muted-foreground mt-1">
                        Gepland: {{ $plannedDistanceLabel ?? '—' }}@if($plannedDurationLabel) · {{ $plannedDurationLabel }}@endif
                    </div>
                @endif
            </div>
            @endif
        </div>
        @if(trim((string) ($googleMapsApiKey ?? '')) === '')
            <div class="p-5">
                <p class="text-sm text-muted-foreground mb-0">Er is geen Google Maps-sleutel ingesteld. Voeg die toe bij Algemene configuraties → Google Maps.</p>
            </div>
        @endif
    </div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:gap-7.5">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Route &amp; datum</h3></div>
            <div class="kt-card-content p-5 space-y-3 text-sm admin-detail-field-list">
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Ophalen:</span><span class="flex-1">{{ $ride->pickup_address }}</span></p>
                @foreach(($stopoverAddresses ?? $ride->stopover_addresses) as $stopIndex => $stopAddress)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Tussenstop {{ chr(66 + $stopIndex) }}:</span><span class="flex-1">{{ $stopAddress }}</span></p>
                @endforeach
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Afzetten:</span><span class="flex-1">{{ $ride->dropoff_address }}</span></p>
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Datum/tijd:</span><span class="flex-1">{{ $ride->pickup_at->format('d-m-Y H:i') }}</span></p>
                @if($actualDistanceLabel)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Afstand:</span><span class="flex-1">{{ $actualDistanceLabel }}@if($hasRecordedTrack && $plannedDistanceLabel && $plannedDistanceLabel !== $actualDistanceLabel) <span class="text-muted-foreground">(gepland {{ $plannedDistanceLabel }})</span>@endif</span></p>
                @elseif($plannedDistanceLabel)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Afstand:</span><span class="flex-1">{{ $plannedDistanceLabel }}</span></p>
                @endif
                @if($actualDurationLabel)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Rijtijd:</span><span class="flex-1">{{ $actualDurationLabel }}@if($hasRecordedTrack && $plannedDurationLabel && $plannedDurationLabel !== $actualDurationLabel) <span class="text-muted-foreground">(gepland {{ $plannedDurationLabel }})</span>@endif</span></p>
                @elseif($plannedDurationLabel)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Rijtijd:</span><span class="flex-1">{{ $plannedDurationLabel }}</span></p>
                @endif
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Passagiers:</span><span class="flex-1">{{ $ride->passengers }}</span></p>
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Status:</span><span class="flex-1">{{ $ride->status_label }}</span></p>
                @if($ride->isNexaSuiteBooking())
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Bron:</span><span class="flex-1">Algemene boeking via NEXA Suite (niet via de eigen website)</span></p>
                @endif
                @if($ride->quoted_price !== null)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Offerteprijs:</span><span class="flex-1">€ {{ number_format($ride->quoted_price, 2, ',', '.') }}</span></p>
                @endif
            </div>
        </div>
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Klant &amp; toewijzing</h3></div>
            <div class="kt-card-content p-5 space-y-3 text-sm admin-detail-field-list">
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Naam:</span><span class="flex-1">{{ $ride->customer_name }}</span></p>
                @if($ride->customer_email)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">E-mail:</span><span class="flex-1"><a href="mailto:{{ $ride->customer_email }}">{{ $ride->customer_email }}</a></span></p>
                @endif
                @if($ride->customer_phone)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Telefoon:</span><span class="flex-1"><a href="tel:{{ $ride->customer_phone }}">{{ $ride->customer_phone }}</a></span></p>
                @endif
                @if($ride->customer_note)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Opmerking:</span><span class="flex-1">{{ $ride->customer_note }}</span></p>
                @endif
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Voertuig:</span><span class="flex-1">{{ $ride->vehicle?->name ?? '—' }}</span></p>
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Chauffeur:</span><span class="flex-1">{{ $ride->driver ? $ride->driver->first_name . ' ' . $ride->driver->last_name : '—' }}</span></p>
            </div>
        </div>
    </div>

    @can('rides.update')
    <div class="kt-card w-full min-w-0 mt-5">
        <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Toewijzen</h3></div>
        <div class="kt-card-content p-5">
            <form action="{{ route('admin.taxi.ride_requests.assign', $ride) }}" method="POST" class="flex flex-col sm:flex-row flex-wrap gap-4 items-stretch sm:items-end w-full min-w-0">
                @csrf
                <div class="flex flex-col gap-2.5 flex-1 min-w-[12rem]">
                    <label class="kt-form-label">Voertuig</label>
                    <select name="vehicle_id" class="kt-input w-full max-w-md">
                        <option value="">— Geen —</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" {{ $ride->vehicle_id == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-2.5 flex-1 min-w-[12rem]">
                    <label class="kt-form-label">Chauffeur</label>
                    <select name="driver_id" class="kt-input w-full max-w-md">
                        <option value="">— Geen —</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}" {{ $ride->driver_id == $d->id ? 'selected' : '' }}>{{ $d->first_name }} {{ $d->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="kt-btn kt-btn-primary w-full sm:w-auto shrink-0">Toewijzing opslaan</button>
            </form>
        </div>
    </div>
    <div class="kt-card w-full min-w-0 mt-5">
        <div class="kt-card-header px-5 py-5"><h3 class="kt-card-title mb-0">Communicatie</h3></div>
        <div class="kt-card-content p-5">
            <p class="text-sm text-muted-foreground mb-2">Kopieer onderstaande tekst voor WhatsApp of e-mail naar de klant.</p>
            <textarea readonly class="kt-input w-full font-mono text-sm resize-y pt-1" rows="8" id="whatsapp-text" style="min-height: 12rem !important; height: auto !important; box-sizing: border-box;">Rit {{ $ride->pickup_at->format('d-m-Y H:i') }}
Ophalen: {{ $ride->pickup_address }}
@foreach(($stopoverAddresses ?? $ride->stopover_addresses) as $stopIndex => $stopAddress)
Tussenstop {{ chr(66 + $stopIndex) }}: {{ $stopAddress }}
@endforeach
Afzetten: {{ $ride->dropoff_address }}
@if($ride->quoted_price)Geschatte prijs: € {{ number_format($ride->quoted_price, 2, ',', '.') }}
@endif
– Nexa Taxi</textarea>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-outline mt-2" onclick="navigator.clipboard.writeText(document.getElementById('whatsapp-text').value); this.textContent='Gekopieerd!'; setTimeout(() => this.textContent='Kopieer WhatsApp-tekst', 2000);">Kopieer WhatsApp-tekst</button>
        </div>
    </div>
    @endcan
</div>

@if($hasRideMap && trim((string) ($googleMapsApiKey ?? '')) !== '')
<script>
window.initRideTrackMap = function () {
    var el = document.getElementById('ride-track-map');
    var track = @json($rideTrack);
    var stopovers = @json($stopoverAddresses ?? []);
    if (!el || typeof google === 'undefined' || !google.maps) return;

    function toLatLngLiteral(p) {
        if (!p) return null;
        var lat = Number(p.lat);
        var lng = Number(p.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
        return { lat: lat, lng: lng };
    }
    function toLatLng(p) {
        var lit = toLatLngLiteral(p);
        return lit ? new google.maps.LatLng(lit.lat, lit.lng) : null;
    }
    function haversineMeters(a, b) {
        var r = 6371000;
        var dLat = (b.lat - a.lat) * Math.PI / 180;
        var dLng = (b.lng - a.lng) * Math.PI / 180;
        var lat1 = a.lat * Math.PI / 180;
        var lat2 = b.lat * Math.PI / 180;
        var h = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return 2 * r * Math.asin(Math.min(1, Math.sqrt(h)));
    }
    function distanceToSegmentMeters(p, a, b) {
        var lat0 = ((a.lat + b.lat) / 2) * Math.PI / 180;
        var cosLat = Math.cos(lat0) || 1e-6;
        var ax = a.lng * Math.PI / 180 * cosLat;
        var ay = a.lat * Math.PI / 180;
        var bx = b.lng * Math.PI / 180 * cosLat;
        var by = b.lat * Math.PI / 180;
        var px = p.lng * Math.PI / 180 * cosLat;
        var py = p.lat * Math.PI / 180;
        var dx = bx - ax;
        var dy = by - ay;
        var len2 = dx * dx + dy * dy;
        if (len2 <= 1e-18) return haversineMeters(p, a);
        var t = Math.max(0, Math.min(1, ((px - ax) * dx + (py - ay) * dy) / len2));
        return haversineMeters(p, {
            lat: (ay + t * dy) * 180 / Math.PI,
            lng: ((ax + t * dx) / cosLat) * 180 / Math.PI
        });
    }
    function pathLooksStraight(pts) {
        if (!pts || pts.length < 2) return true;
        if (pts.length === 2) return true;
        var originPt = pts[0];
        var destPt = pts[pts.length - 1];
        var maxDev = 0;
        for (var i = 1; i < pts.length - 1; i++) {
            maxDev = Math.max(maxDev, distanceToSegmentMeters(pts[i], originPt, destPt));
        }
        return maxDev < 15;
    }

    var path = Array.isArray(track.path) ? track.path.map(toLatLngLiteral).filter(Boolean) : [];
    var pickup = toLatLngLiteral(track.pickup);
    var dropoff = toLatLngLiteral(track.dropoff);
    var origin = pickup || path[0];
    var dest = dropoff || path[path.length - 1];
    var color = track.has_recorded_track ? '#2563eb' : '#0f766e';
    var weight = track.has_recorded_track ? 6 : 5;
    var map = new google.maps.Map(el, {
        center: origin || dest || { lat: 52.3676, lng: 4.9041 },
        zoom: {{ (int) ($googleMapsZoom ?? 12) ?: 12 }},
        mapTypeControl: true,
        streetViewControl: false,
        fullscreenControl: true,
        zoomControl: true
    });
    if (pickup) {
        new google.maps.Marker({ position: pickup, map: map, label: 'A', title: track.pickup_address || 'Ophalen' });
    }
    if (dropoff) {
        new google.maps.Marker({ position: dropoff, map: map, label: 'B', title: track.dropoff_address || 'Afzetten' });
    }

    var waypoints = [];
    (Array.isArray(stopovers) ? stopovers : []).forEach(function (address) {
        if (typeof address === 'string' && address.trim() !== '') {
            waypoints.push({ location: address.trim(), stopover: true });
        }
    });
    waypoints = waypoints.slice(0, 23);

    function fitTo(points) {
        var bounds = new google.maps.LatLngBounds();
        points.forEach(function (p) { bounds.extend(p); });
        if (pickup) bounds.extend(pickup);
        if (dropoff) bounds.extend(dropoff);
        if (!bounds.isEmpty()) {
            map.fitBounds(bounds);
        }
    }
    function drawPoly(points) {
        var latLngs = points.map(function (p) { return toLatLng(p); }).filter(Boolean);
        if (latLngs.length < 2) return;
        new google.maps.Polyline({
            path: latLngs,
            geodesic: false,
            strokeColor: color,
            strokeOpacity: 0.95,
            strokeWeight: weight,
            zIndex: 20,
            map: map
        });
        fitTo(latLngs);
    }
    function drawDrivingRoute() {
        if (!origin || !dest || !google.maps.DirectionsService || !google.maps.DirectionsRenderer) {
            if (path.length >= 2) drawPoly(path);
            return;
        }
        var renderer = new google.maps.DirectionsRenderer({
            map: map,
            suppressMarkers: true,
            preserveViewport: false,
            polylineOptions: {
                strokeColor: color,
                strokeOpacity: 0.95,
                strokeWeight: weight,
                zIndex: 20,
                geodesic: false
            }
        });
        var service = new google.maps.DirectionsService();
        service.route({
            origin: origin,
            destination: dest,
            waypoints: waypoints,
            travelMode: google.maps.TravelMode.DRIVING,
            provideRouteAlternatives: false
        }, function (result, status) {
            if (status === 'OK' && result && result.routes && result.routes[0]) {
                renderer.setDirections(result);
                return;
            }
            if (path.length >= 2) {
                drawPoly(path);
            }
        });
    }

    google.maps.event.addListenerOnce(map, 'idle', function () {
        if (track.has_recorded_track && path.length >= 2 && !pathLooksStraight(path)) {
            drawPoly(path);
            return;
        }
        drawDrivingRoute();
    });
};
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&callback=initRideTrackMap" async defer></script>
@endif
@endsection
