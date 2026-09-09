@extends('admin.layouts.app')

@section('title', 'GPS-tracker')

@section('content')
<style>
    .gps-map-canvas {
        width: 100%;
        height: min(68vh, 640px);
        min-height: 420px;
        background: #e5e7eb;
    }
    html.dark .gps-map-canvas,
    .gps-map-canvas.is-dark {
        background: #242f3e;
    }
    .gps-map-canvas.is-light {
        background: #e5e7eb;
    }
    .nexa-gps-marker {
        display: flex;
        flex-direction: column;
        align-items: center;
        pointer-events: auto;
        cursor: pointer;
        white-space: nowrap;
        z-index: auto;
        isolation: auto;
    }
    .nexa-gps-plate {
        position: relative;
        z-index: 2;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.06em;
        line-height: 1;
        background: #f7e125;
        color: #111827;
        border: 2px solid #111827;
        border-radius: 4px;
        padding: 5px 8px;
        margin-bottom: 6px;
        white-space: nowrap;
        box-shadow: 0 2px 8px rgba(0,0,0,.45);
        text-shadow: none;
    }
    .nexa-gps-car-rot {
        position: relative;
        z-index: 1;
        display: flex;
        transform-origin: 50% 55%;
        filter: drop-shadow(0 2px 3px rgba(0,0,0,.4));
    }
    .nexa-gps-marker-plate .nexa-gps-plate {
        margin-bottom: 0;
    }
    .nexa-gps-car-img {
        display: block;
        object-fit: contain;
        pointer-events: none;
    }
    .nexa-gps-marker.is-offline .nexa-gps-car-rot {
        opacity: 0.78;
        filter: grayscale(0.45) drop-shadow(0 3px 5px rgba(0,0,0,.4));
    }
    .nexa-gps-legend-wrap {
        border: 1px solid color-mix(in oklab, var(--foreground) 22%, var(--border));
        border-radius: 14px;
        overflow: hidden;
        background: color-mix(in oklab, var(--foreground) 3%, var(--background));
    }
    .nexa-gps-legend-wrap .kt-table {
        margin-bottom: 0;
    }
    .nexa-gps-map-card,
    .gps-map-canvas {
        scroll-margin-top: 5.5rem;
    }
    .nexa-gps-focus-btn {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.85rem;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px rgba(15,23,42,.18);
        cursor: pointer;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        flex-shrink: 0;
    }
    .nexa-gps-focus-btn.is-light {
        color: #111827;
    }
    .nexa-gps-focus-btn i {
        font-size: 1.15rem;
        line-height: 1;
        pointer-events: none;
        filter: drop-shadow(0 1px 1px rgba(0,0,0,.35));
    }
    .nexa-gps-focus-btn:hover {
        transform: scale(1.06);
    }
    .nexa-gps-focus-btn.is-following {
        box-shadow: 0 0 0 1px rgba(15,23,42,.18), 0 0 0 3px #2563eb;
    }
    .nexa-gps-focus-tooltip {
        position: fixed;
        z-index: 100050;
        pointer-events: none;
        display: inline-block;
        width: max-content;
        max-width: min(16rem, calc(100vw - 16px));
        background: var(--mono, #111827);
        color: var(--mono-foreground, #fff);
        font-size: 0.75rem;
        line-height: 1.35;
        font-weight: 600;
        padding: 0.45rem 0.65rem;
        border-radius: 0.45rem;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.22);
        white-space: nowrap;
    }
    html.dark .nexa-gps-focus-tooltip {
        border: 1px solid var(--border);
    }
    .gps-view-toggle {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        padding: 3px;
        border: 1px solid var(--border);
        border-radius: 999px;
        background: var(--background);
    }
    .gps-view-toggle button {
        border: 0;
        background: transparent;
        border-radius: 999px;
        padding: 6px 14px;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--muted-foreground);
        cursor: pointer;
        line-height: 1.2;
    }
    .gps-view-toggle button.is-active {
        background: #2563eb;
        color: #fff;
    }
    .nexa-gps-seen {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
</style>

<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-medium leading-none text-mono">GPS-tracker</h1>
            <p class="text-sm text-muted-foreground mt-2 mb-0 leading-relaxed" id="gps-page-intro">
                Live locatie van online voertuigen. Chauffeurs moeten online staan in de chauffeur-app en locatie delen.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <div class="gps-view-toggle" role="group" aria-label="Kaartweergave">
                <button type="button" id="gps-view-online" class="is-active" aria-pressed="true">Online</button>
                <button type="button" id="gps-view-offline" aria-pressed="false">Offline</button>
            </div>
            <a href="{{ $settingsUrl }}" class="kt-btn kt-btn-outline">Configuratie</a>
            @if($canManageCode)
                <button type="button" class="kt-btn kt-btn-outline" id="gps-open-code">
                    Veiligheidscode
                </button>
            @endif
        </div>
    </div>

    @if($errors->has('tenant'))
        <div class="kt-alert kt-alert-warning mb-5">{{ $errors->first('tenant') }}</div>
    @endif
    @if(!empty($noTenantSelected))
        <div class="kt-alert kt-alert-warning mb-5">
            Selecteer eerst een tenant (bedrijf) in de zijbalk. GPS-tracker toont alleen voertuigen van het geselecteerde bedrijf.
        </div>
    @endif
    @if(trim((string) $googleMapsApiKey) === '')
        <div class="kt-alert kt-alert-warning mb-5">
            Er is geen Google Maps-sleutel ingesteld. Voeg die toe bij Algemene configuraties → Google Maps.
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card w-full min-w-0 overflow-hidden nexa-gps-map-card" id="gps-map-card">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <div class="flex items-center gap-2 min-w-0">
                    <h5 class="kt-card-title mb-0">Live kaart</h5>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-outline shrink-0" id="gps-map-theme" aria-pressed="false" aria-label="Schakel kaart naar donkere modus" data-gps-tooltip="Donkere kaart">
                        <i class="ki-filled ki-moon" id="gps-map-theme-icon"></i>
                    </button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm text-muted-foreground" id="gps-status-label">Laden…</span>
                    @if(!empty($canUseLiveDemo))
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-foreground cursor-pointer select-none" for="gps-live-demo" title="Laat alle geconfigureerde voertuigen rijden in de vestigingsstad">
                            <input type="checkbox"
                                   class="kt-switch kt-switch-sm shrink-0"
                                   id="gps-live-demo"
                                   role="switch"
                                   {{ !empty($liveDemoEnabled) ? 'checked' : '' }}>
                            <span>Demo</span>
                        </label>
                    @endif
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-primary" id="gps-follow-fleet" aria-pressed="true" title="Kaart volgt automatisch alle auto’s en kentekens">
                        <i class="ki-filled ki-geolocation me-1"></i>
                        Volg vloot
                    </button>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" id="gps-fit-bounds" title="Toon alle auto’s weer">
                        <i class="ki-filled ki-arrows-circle me-1"></i>
                        Alles tonen
                    </button>
                </div>
            </div>
            <div id="gps-tracking-map" class="gps-map-canvas"
                 data-map-id="{{ $googleMapsMapId }}"
                 data-lat="{{ $centerLat }}"
                 data-lng="{{ $centerLng }}"
                 data-zoom="{{ $googleMapsZoom }}"
                 data-type="{{ $googleMapsType }}"></div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h5 class="kt-card-title mb-0">Chauffeurs en kentekens</h5>
                <span class="text-sm text-muted-foreground" id="gps-legend-count"></span>
            </div>
            <div class="kt-card-content p-5 lg:p-6" id="gps-legend">
                <p class="text-sm text-muted-foreground mb-0">Nog geen voertuigen op de kaart.</p>
            </div>
        </div>
    </div>
</div>

<div id="gps-unlock-modal" class="fixed inset-0 z-[100000] hidden items-center justify-center bg-zinc-950/70 p-4 backdrop-blur-md" role="dialog" aria-modal="true" aria-labelledby="gps-unlock-title">
    <div class="w-full max-w-md rounded-2xl border border-border bg-background shadow-2xl">
        <div class="border-b border-border px-6 py-5">
            <h2 id="gps-unlock-title" class="text-lg font-semibold text-foreground mb-1">Offline voertuigen tonen</h2>
            <p class="text-sm text-muted-foreground mb-0">Voer de veiligheidscode in om de laatste bekende locatie van offline chauffeurs te zien. Online voertuigen verdwijnen van de kaart tot je terugschakelt naar Online.</p>
        </div>
        <form id="gps-unlock-form" class="px-6 py-5 space-y-4">
            <div>
                <label for="gps-unlock-code" class="kt-label mb-1">Veiligheidscode</label>
                <input id="gps-unlock-code" type="password" inputmode="numeric" pattern="[0-9]{4,8}" autocomplete="off" class="kt-input w-full" maxlength="8" data-gps-code-input>
                <p class="text-xs text-destructive mt-1 hidden" id="gps-unlock-code-error"></p>
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" class="kt-btn kt-btn-light" data-gps-close="unlock">Annuleren</button>
                <button type="submit" class="kt-btn kt-btn-primary">Toon locaties</button>
            </div>
        </form>
    </div>
</div>

@if($canManageCode)
<div id="gps-code-modal" class="fixed inset-0 z-[100000] hidden items-center justify-center bg-zinc-950/70 p-4 backdrop-blur-md" role="dialog" aria-modal="true" aria-labelledby="gps-code-title">
    <div class="w-full max-w-md rounded-2xl border border-border bg-background shadow-2xl">
        <div class="border-b border-border px-6 py-5">
            <h2 id="gps-code-title" class="text-lg font-semibold text-foreground mb-1">Veiligheidscode</h2>
            <p class="text-sm text-muted-foreground mb-0">Deze code is nodig om offline chauffeurs op de kaart te zien. Gebruik 4 tot 8 cijfers.</p>
        </div>
        <form id="gps-code-form" method="POST" action="{{ $codeUrl }}" class="px-6 py-5 space-y-4" novalidate>
            @csrf
            @method('PUT')
            @if($offlineCodeSet)
                <div>
                    <label for="gps-current-code" class="kt-label mb-1">Huidige code</label>
                    <input id="gps-current-code" type="password" name="current_code" inputmode="numeric" pattern="[0-9]{4,8}" autocomplete="off" class="kt-input w-full @error('current_code') border-destructive @enderror" maxlength="8" data-gps-code-input>
                    <p class="text-xs text-destructive mt-1{{ $errors->has('current_code') ? '' : ' hidden' }}" id="gps-current-code-error" data-gps-code-error>{{ $errors->first('current_code') }}</p>
                </div>
            @endif
            <div>
                <label for="gps-new-code" class="kt-label mb-1">Nieuwe code</label>
                <input id="gps-new-code" type="password" name="code" inputmode="numeric" pattern="[0-9]{4,8}" autocomplete="off" class="kt-input w-full @error('code') border-destructive @enderror" maxlength="8" required data-gps-code-input>
                <p class="text-xs text-destructive mt-1{{ $errors->has('code') ? '' : ' hidden' }}" id="gps-new-code-error" data-gps-code-error>{{ $errors->first('code') }}</p>
            </div>
            <div>
                <label for="gps-new-code-confirm" class="kt-label mb-1">Bevestig code</label>
                <input id="gps-new-code-confirm" type="password" name="code_confirmation" inputmode="numeric" pattern="[0-9]{4,8}" autocomplete="off" class="kt-input w-full @error('code_confirmation') border-destructive @enderror" maxlength="8" required data-gps-code-input>
                <p class="text-xs text-destructive mt-1{{ $errors->has('code_confirmation') ? '' : ' hidden' }}" id="gps-new-code-confirm-error" data-gps-code-error>{{ $errors->first('code_confirmation') }}</p>
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" class="kt-btn kt-btn-light" data-gps-close="code">Annuleren</button>
                <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
            </div>
        </form>
    </div>
</div>
@endif

<div id="nexa-gps-focus-tooltip" class="nexa-gps-focus-tooltip" hidden role="tooltip"></div>

@include('taxi::admin.gps-tracking.partials.marker-script')
<script>
window.initGpsTrackingMap = function () {
    if (window.__nexaGpsMapReady) {
        return;
    }
    window.__nexaGpsMapReady = true;
    if (typeof window.__nexaGpsBoot === 'function') {
        window.__nexaGpsBoot();
    }
};
</script>
@if(trim((string) $googleMapsApiKey) !== '')
<script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&callback=initGpsTrackingMap" async defer></script>
@endif
<script>
(function () {
    var appearance = @json($appearance);
    var cfg = {
        positionsUrl: @json($positionsUrl),
        unlockUrl: @json($unlockUrl),
        lockUrl: @json($lockUrl),
        demoUrl: @json($demoUrl ?? ''),
        csrf: document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '',
        pollMs: Math.max(1, parseInt(appearance.refresh_seconds, 10) || 1) * 1000,
        view: 'online',
        noTenant: @json(!empty($noTenantSelected)),
        offlineUnlocked: @json(!empty($offlineUnlocked)),
        offlineCodeSet: @json(!empty($offlineCodeSet)),
        openCodeModal: @json($errors->has('current_code') || $errors->has('code') || $errors->has('code_confirmation')),
        hasMapsKey: @json(trim((string) $googleMapsApiKey) !== ''),
        canUseLiveDemo: @json(!empty($canUseLiveDemo))
    };

    var map = null;
    var OverlayClass = null;
    var markers = {};
    var lastPos = {};
    var animations = {};
    var pollTimer = null;
    var fittedOnce = false;
    var lastItems = [];
    var followFleet = true;
    var followVehicleId = null;
    var programmaticMove = false;
    var lastFollowPanAt = 0;
    var mapThemeUserOverride = false;
    var mapDark = false;
    var themeObs = null;

    var GPS_MAP_DARK_STYLES = [
        { elementType: 'geometry', stylers: [{ color: '#242f3e' }] },
        { elementType: 'labels.text.stroke', stylers: [{ color: '#242f3e' }] },
        { elementType: 'labels.text.fill', stylers: [{ color: '#746855' }] },
        { featureType: 'administrative.locality', elementType: 'labels.text.fill', stylers: [{ color: '#d59563' }] },
        { featureType: 'poi', elementType: 'labels.text.fill', stylers: [{ color: '#d59563' }] },
        { featureType: 'poi.park', elementType: 'geometry', stylers: [{ color: '#263c3f' }] },
        { featureType: 'poi.park', elementType: 'labels.text.fill', stylers: [{ color: '#6b9a76' }] },
        { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#38414e' }] },
        { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#212a37' }] },
        { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#9ca5b3' }] },
        { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#746855' }] },
        { featureType: 'road.highway', elementType: 'geometry.stroke', stylers: [{ color: '#1f2835' }] },
        { featureType: 'road.highway', elementType: 'labels.text.fill', stylers: [{ color: '#f3d19c' }] },
        { featureType: 'transit', elementType: 'geometry', stylers: [{ color: '#2f3948' }] },
        { featureType: 'transit.station', elementType: 'labels.text.fill', stylers: [{ color: '#d59563' }] },
        { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#17263c' }] },
        { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#515c6d' }] },
        { featureType: 'water', elementType: 'labels.text.stroke', stylers: [{ color: '#17263c' }] }
    ];

    function $(id) { return document.getElementById(id); }

    function adminIsDark() {
        var root = document.documentElement;
        if (root.classList.contains('dark')) return true;
        if (root.classList.contains('light')) return false;
        var mode = root.getAttribute('data-kt-theme-mode');
        if (mode === 'dark') return true;
        if (mode === 'light') return false;
        return document.body.classList.contains('dark');
    }

    function mapThemeOptions(dark) {
        return { styles: dark ? GPS_MAP_DARK_STYLES : [] };
    }

    function updateMapThemeButton() {
        var btn = $('gps-map-theme');
        var icon = $('gps-map-theme-icon');
        if (!btn) return;
        if (icon) icon.className = mapDark ? 'ki-filled ki-sun' : 'ki-filled ki-moon';
        btn.setAttribute('aria-pressed', mapDark ? 'true' : 'false');
        btn.setAttribute('aria-label', mapDark ? 'Schakel kaart naar lichte modus' : 'Schakel kaart naar donkere modus');
        btn.setAttribute('data-gps-tooltip', mapDark ? 'Lichte kaart' : 'Donkere kaart');
    }

    function applyMapTheme(dark, fromUser) {
        if (fromUser) {
            mapThemeUserOverride = true;
            if (themeObs) themeObs.disconnect();
        }
        mapDark = !!dark;
        var canvas = $('gps-tracking-map');
        if (canvas) {
            canvas.classList.toggle('is-dark', mapDark);
            canvas.classList.toggle('is-light', !mapDark);
        }
        if (map && typeof google !== 'undefined' && google.maps) {
            map.setOptions(mapThemeOptions(mapDark));
        }
        updateMapThemeButton();
    }

    mapDark = adminIsDark();

    function jsonHeaders() {
        return {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': cfg.csrf
        };
    }

    var GPS_CODE_MIN = 4;
    var GPS_CODE_MAX = 8;
    var GPS_CODE_DIGIT_MSG = 'De code bestaat uit ' + GPS_CODE_MIN + ' tot ' + GPS_CODE_MAX + ' cijfers.';

    function gpsCodeDigits(value) {
        return String(value || '').replace(/\D+/g, '').slice(0, GPS_CODE_MAX);
    }

    function gpsCodeFormatError(value) {
        var digits = gpsCodeDigits(value);
        if (digits.length < GPS_CODE_MIN || digits.length > GPS_CODE_MAX || digits !== String(value || '')) {
            return GPS_CODE_DIGIT_MSG;
        }
        return '';
    }

    function setGpsFieldError(input, message) {
        if (!input) return;
        var err = $(input.id + '-error');
        input.classList.toggle('border-destructive', !!message);
        if (!err) return;
        err.textContent = message || '';
        err.classList.toggle('hidden', !message);
    }

    function bindGpsDigitInputs() {
        document.querySelectorAll('[data-gps-code-input]').forEach(function (input) {
            input.addEventListener('input', function () {
                var next = gpsCodeDigits(input.value);
                if (input.value !== next) input.value = next;
                if (input.getAttribute('data-gps-code-checked') === '1') {
                    if (input.closest('#gps-code-form')) validateGpsCodeForm(false);
                    else setGpsFieldError(input, gpsCodeDigits(input.value) === '' ? 'Vul de veiligheidscode in.' : gpsCodeFormatError(input.value));
                }
            });
        });
        document.querySelectorAll('#gps-code-form [data-gps-code-error]').forEach(function (err) {
            if (err.classList.contains('hidden') || !err.textContent.trim()) return;
            var wrap = err.previousElementSibling;
            var input = wrap && wrap.matches && wrap.matches('[data-gps-code-input]')
                ? wrap
                : (wrap ? wrap.querySelector('[data-gps-code-input]') : null);
            if (input) input.setAttribute('data-gps-code-checked', '1');
        });
    }

    function validateGpsCodeForm(markChecked) {
        var form = $('gps-code-form');
        if (!form) return true;
        var current = $('gps-current-code');
        var neu = $('gps-new-code');
        var confirm = $('gps-new-code-confirm');
        var ok = true;
        if (markChecked) {
            [current, neu, confirm].forEach(function (el) {
                if (el) el.setAttribute('data-gps-code-checked', '1');
            });
        }
        if (current) {
            var currentVal = gpsCodeDigits(current.value);
            var currentMsg = currentVal === '' ? 'Vul de huidige veiligheidscode in.' : gpsCodeFormatError(current.value);
            setGpsFieldError(current, currentMsg);
            if (currentMsg) ok = false;
        }
        if (neu) {
            var newVal = gpsCodeDigits(neu.value);
            var newMsg = newVal === '' ? 'Vul een nieuwe veiligheidscode in.' : gpsCodeFormatError(neu.value);
            setGpsFieldError(neu, newMsg);
            if (newMsg) ok = false;
        }
        if (confirm) {
            var confirmVal = gpsCodeDigits(confirm.value);
            var confirmMsg = '';
            if (confirmVal === '') confirmMsg = 'Bevestig de nieuwe veiligheidscode.';
            else if (gpsCodeFormatError(confirm.value)) confirmMsg = GPS_CODE_DIGIT_MSG;
            else if (neu && confirmVal !== gpsCodeDigits(neu.value)) confirmMsg = 'De opgegeven nieuwe codes komen niet overeen.';
            setGpsFieldError(confirm, confirmMsg);
            if (confirmMsg) ok = false;
        }
        return ok;
    }

    function setModal(id, open) {
        var el = $(id);
        if (!el) return;
        el.classList.toggle('hidden', !open);
        el.classList.toggle('flex', open);
    }

    function headingBetween(from, to) {
        var lat1 = from.lat * Math.PI / 180;
        var lat2 = to.lat * Math.PI / 180;
        var dLng = (to.lng - from.lng) * Math.PI / 180;
        var y = Math.sin(dLng) * Math.cos(lat2);
        var x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLng);
        return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
    }

    function shortestHeadingDelta(from, to) {
        return ((to - from + 540) % 360) - 180;
    }

    function parseHeading(el) {
        if (!el || !el.style || !el.style.transform) return null;
        var match = /rotate\((-?[\d.]+)deg\)/.exec(el.style.transform);
        return match ? Number(match[1]) : null;
    }

    function markerLatLng(marker) {
        var pos = marker.getPosition ? marker.getPosition() : marker.position;
        if (pos && typeof pos.lat === 'function') return pos;
        return new google.maps.LatLng(Number(pos.lat), Number(pos.lng));
    }

    function ensureOverlayClass() {
        if (OverlayClass) return OverlayClass;
        OverlayClass = class GpsCarOverlay extends google.maps.OverlayView {
            constructor(position, content) {
                super();
                this.position = new google.maps.LatLng(position.lat, position.lng);
                this.contentEl = content;
                this.div = null;
                this.plateDiv = null;
            }
            mountParts(content) {
                var plate = content.querySelector('.nexa-gps-plate');
                var car = content.querySelector('.nexa-gps-car-rot');
                var id = content.getAttribute('data-gps-marker-id');
                var title = content.title || '';
                var offline = content.classList.contains('is-offline');

                var carDiv = document.createElement('div');
                carDiv.className = 'nexa-gps-marker nexa-gps-marker-car' + (offline ? ' is-offline' : '');
                carDiv.style.position = 'absolute';
                carDiv.title = title;
                if (id) carDiv.setAttribute('data-gps-marker-id', id);
                if (car) carDiv.appendChild(car);

                var plateDiv = document.createElement('div');
                plateDiv.className = 'nexa-gps-marker nexa-gps-marker-plate' + (offline ? ' is-offline' : '');
                plateDiv.style.position = 'absolute';
                plateDiv.title = title;
                if (id) plateDiv.setAttribute('data-gps-marker-id', id);
                if (plate) plateDiv.appendChild(plate);

                return { carDiv: carDiv, plateDiv: plateDiv };
            }
            pinStack() {
                if (this.div && this.div.parentNode) {
                    this.div.parentNode.style.zIndex = '1';
                }
                if (this.plateDiv && this.plateDiv.parentNode) {
                    this.plateDiv.parentNode.style.zIndex = '10000';
                }
            }
            onAdd() {
                var parts = this.mountParts(this.contentEl);
                this.div = parts.carDiv;
                this.plateDiv = parts.plateDiv;
                var panes = this.getPanes();
                panes.overlayMouseTarget.appendChild(this.div);
                (panes.floatPane || panes.overlayMouseTarget).appendChild(this.plateDiv);
                this.pinStack();
                this.bindFocus();
            }
            draw() {
                if (!this.div) return;
                var proj = this.getProjection();
                if (!proj) return;
                var p = proj.fromLatLngToDivPixel(this.position);
                if (!p) return;
                var carW = this.div.offsetWidth || 28;
                var carH = this.div.offsetHeight || 40;
                var plateW = this.plateDiv ? (this.plateDiv.offsetWidth || 72) : 0;
                var plateH = this.plateDiv ? (this.plateDiv.offsetHeight || 22) : 0;
                var gap = 6;
                var totalH = plateH + gap + carH;
                var top = p.y - totalH + 12;
                this.div.style.left = (p.x - carW / 2) + 'px';
                this.div.style.top = (top + plateH + gap) + 'px';
                if (this.plateDiv) {
                    this.plateDiv.style.left = (p.x - plateW / 2) + 'px';
                    this.plateDiv.style.top = top + 'px';
                }
                this.pinStack();
            }
            onRemove() {
                if (this.div && this.div.parentNode) this.div.parentNode.removeChild(this.div);
                if (this.plateDiv && this.plateDiv.parentNode) this.plateDiv.parentNode.removeChild(this.plateDiv);
                this.div = null;
                this.plateDiv = null;
            }
            getPosition() { return this.position; }
            setPosition(pos) {
                this.position = pos instanceof google.maps.LatLng ? pos : new google.maps.LatLng(pos.lat, pos.lng);
                this.draw();
            }
            setContent(el) {
                this.contentEl = el;
                var parts = this.mountParts(el);
                if (this.div && this.div.parentNode) {
                    this.div.parentNode.replaceChild(parts.carDiv, this.div);
                }
                if (this.plateDiv && this.plateDiv.parentNode) {
                    this.plateDiv.parentNode.replaceChild(parts.plateDiv, this.plateDiv);
                }
                this.div = parts.carDiv;
                this.plateDiv = parts.plateDiv;
                this.bindFocus();
                this.pinStack();
                this.draw();
            }
            bindFocus() {
                var self = this;
                function onClick(ev) {
                    ev.preventDefault();
                    var node = ev.currentTarget;
                    var id = node && node.getAttribute('data-gps-marker-id');
                    if (id) focusVehicle(id);
                }
                if (this.div) this.div.onclick = onClick;
                if (this.plateDiv) this.plateDiv.onclick = onClick;
            }
        };
        return OverlayClass;
    }

    function animateTo(key, marker, lat, lng, heading, fromHeading) {
        if (animations[key]) cancelAnimationFrame(animations[key]);
        var start = markerLatLng(marker);
        var fromLat = start.lat();
        var fromLng = start.lng();
        var rot = marker.div ? marker.div.querySelector('.nexa-gps-car-rot') : null;
        if (fromHeading == null) fromHeading = parseHeading(rot);
        if (fromHeading == null) fromHeading = heading;
        var headingDelta = heading == null || fromHeading == null ? 0 : shortestHeadingDelta(fromHeading, heading);
        var duration = Math.max(400, Math.min(cfg.pollMs, 1800));
        var started = performance.now();
        function step(now) {
            var t = Math.min(1, (now - started) / duration);
            marker.setPosition({ lat: fromLat + (lat - fromLat) * t, lng: fromLng + (lng - fromLng) * t });
            var liveRot = marker.div ? marker.div.querySelector('.nexa-gps-car-rot') : rot;
            if (liveRot && heading != null) {
                liveRot.style.transform = 'rotate(' + (fromHeading + headingDelta * t) + 'deg)';
            }
            if (followVehicleId && String(followVehicleId) === String(key)) keepFollowedInView(t >= 1);
            if (t < 1) animations[key] = requestAnimationFrame(step);
            else delete animations[key];
        }
        animations[key] = requestAnimationFrame(step);
    }

    function upsertMarker(item) {
        if (!map || item.lat == null || item.lng == null) return;
        var pos = { lat: Number(item.lat), lng: Number(item.lng) };
        var prev = lastPos[item.id];
        var serverHeading = item.heading != null && item.heading !== '' ? Number(item.heading) : NaN;
        var heading = !isNaN(serverHeading)
            ? serverHeading
            : (prev ? headingBetween(prev, pos) : 0);
        lastPos[item.id] = pos;
        var content = window.NexaGpsMarker.markerNode(item, appearance, heading);
        var existing = markers[item.id];
        if (existing) {
            var fromHeading = parseHeading(existing.div ? existing.div.querySelector('.nexa-gps-car-rot') : null);
            existing.setContent(content);
            animateTo(item.id, existing, pos.lat, pos.lng, heading, fromHeading);
            return;
        }
        var Overlay = ensureOverlayClass();
        var overlay = new Overlay(pos, content);
        overlay.setMap(map);
        markers[item.id] = overlay;
    }

    function removeMissing(ids) {
        Object.keys(markers).forEach(function (id) {
            if (ids[id]) return;
            markers[id].setMap(null);
            delete markers[id];
            delete lastPos[id];
            if (followVehicleId === id) followVehicleId = null;
        });
    }

    function formatSeen(iso) {
        if (!iso) return 'onbekend';
        var d = new Date(iso);
        if (isNaN(d.getTime())) return 'onbekend';
        return d.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    function seenHtml(iso, id) {
        return '<span class="nexa-gps-seen" data-gps-seen="' + window.NexaGpsMarker.escapeHtml(String(id || '')) + '">' + formatSeen(iso) + '</span>';
    }

    function legendSignature(items) {
        return items.map(function (item) {
            return String(item.id) + ':' + (item.is_online ? '1' : '0') + ':' + window.NexaGpsMarker.escapeHtml(item.license_plate || '') + ':' + window.NexaGpsMarker.escapeHtml(item.driver_name || '');
        }).join('|');
    }

    function renderLegend(items) {
        var box = $('gps-legend');
        var count = $('gps-legend-count');
        if (!box) return;
        if (!items.length) {
            var empty = cfg.view === 'offline'
                ? 'Geen offline voertuigen. De laatste bekende locatie is alleen zichtbaar als een chauffeur offline is.'
                : 'Nog geen voertuigen op de kaart. Zet een chauffeur online in de chauffeur-app.';
            box.innerHTML = '<p class="text-sm text-muted-foreground mb-0">' + empty + '</p>';
            box.removeAttribute('data-gps-legend-sig');
            if (count) count.textContent = '';
            return;
        }
        if (count) count.textContent = items.length + (items.length === 1 ? ' voertuig' : ' voertuigen');
        var signature = legendSignature(items);
        if (box.getAttribute('data-gps-legend-sig') === signature && box.querySelector('[data-gps-focus]')) {
            items.forEach(function (item) {
                var seen = box.querySelector('[data-gps-seen="' + window.NexaGpsMarker.escapeHtml(String(item.id)) + '"]');
                if (seen) seen.textContent = formatSeen(item.location_updated_at || item.last_seen_at);
            });
            syncFollowedLegend();
            return;
        }
        box.setAttribute('data-gps-legend-sig', signature);
        var html = '<div class="nexa-gps-legend-wrap overflow-x-auto"><table class="kt-table align-middle text-sm w-full admin-keep-table-layout" data-admin-no-cards="true"><thead><tr><th class="w-14"></th><th>Kenteken</th><th>Chauffeur</th><th>Status</th><th>Laatst gezien</th></tr></thead><tbody>';
        items.forEach(function (item) {
            var color = window.NexaGpsMarker.bodyColor(item, appearance);
            var luma = (function (hex) {
                var h = String(hex || '').replace('#', '');
                if (h.length !== 6) return 0;
                return (0.2126 * parseInt(h.slice(0, 2), 16) + 0.7152 * parseInt(h.slice(2, 4), 16) + 0.0722 * parseInt(h.slice(4, 6), 16)) / 255;
            })(color);
            var plate = window.NexaGpsMarker.escapeHtml(item.license_plate || '—');
            var focusHint = followVehicleId === String(item.id)
                ? 'Kaart volgt ' + plate
                : 'Zoom in op ' + plate + ' op de kaart';
            html += '<tr>' +
                '<td><button type="button" class="nexa-gps-focus-btn' + (luma > 0.72 ? ' is-light' : '') + (followVehicleId === String(item.id) ? ' is-following' : '') + '" data-gps-focus="' + window.NexaGpsMarker.escapeHtml(item.id) + '" data-gps-plate="' + plate + '" data-gps-tooltip="' + focusHint + '" style="background:' + color + '" aria-label="' + focusHint + '" aria-pressed="' + (followVehicleId === String(item.id) ? 'true' : 'false') + '" onclick="if(window.nexaGpsFocusVehicle)window.nexaGpsFocusVehicle(this.getAttribute(\'data-gps-focus\'))"><i class="ki-filled ki-geolocation"></i></button></td>' +
                '<td class="font-medium text-foreground">' + plate + (item.vehicle_name ? '<div class="text-xs text-muted-foreground">' + window.NexaGpsMarker.escapeHtml(item.vehicle_name) + '</div>' : '') + '</td>' +
                '<td>' + window.NexaGpsMarker.escapeHtml(item.driver_name) + '</td>' +
                '<td>' + (item.is_online ? '<span class="text-green-600">Online</span>' : '<span class="text-muted-foreground">Offline</span>') + '</td>' +
                '<td class="text-muted-foreground">' + seenHtml(item.location_updated_at || item.last_seen_at, item.id) + '</td>' +
                '</tr>';
        });
        html += '</tbody></table></div>';
        box.innerHTML = html;
        hideFocusTooltip();
    }

    function hideFocusTooltip() {
        var tip = $('nexa-gps-focus-tooltip');
        if (!tip) return;
        tip.hidden = true;
        tip.textContent = '';
        tip.style.visibility = '';
    }

    function showFocusTooltip(btn) {
        var tip = $('nexa-gps-focus-tooltip');
        var label = btn.getAttribute('data-gps-tooltip') || '';
        if (!tip || !label) return;
        tip.textContent = label;
        tip.style.visibility = 'hidden';
        tip.hidden = false;
        tip.style.left = '0px';
        tip.style.top = '0px';
        var rect = btn.getBoundingClientRect();
        var tipRect = tip.getBoundingClientRect();
        var left = rect.left + (rect.width / 2) - (tipRect.width / 2);
        left = Math.max(8, Math.min(left, window.innerWidth - tipRect.width - 8));
        var top = rect.top - tipRect.height - 8;
        if (top < 8) top = rect.bottom + 8;
        tip.style.left = left + 'px';
        tip.style.top = top + 'px';
        tip.style.visibility = 'visible';
    }

    function setFollowFleet(on, fit) {
        followFleet = !!on;
        if (followFleet) followVehicleId = null;
        var btn = $('gps-follow-fleet');
        if (btn) {
            btn.setAttribute('aria-pressed', followFleet ? 'true' : 'false');
            btn.classList.toggle('kt-btn-primary', followFleet);
            btn.classList.toggle('kt-btn-outline', !followFleet);
        }
        syncFollowedLegend();
        if (fit && followFleet) fitAll();
    }

    function syncFollowedLegend() {
        document.querySelectorAll('[data-gps-focus]').forEach(function (btn) {
            var on = followVehicleId != null && btn.getAttribute('data-gps-focus') === String(followVehicleId);
            var plate = btn.getAttribute('data-gps-plate') || '';
            var hint = on
                ? ('Kaart volgt ' + plate)
                : ('Zoom in op ' + plate + ' op de kaart');
            btn.classList.toggle('is-following', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.setAttribute('aria-label', hint);
            btn.setAttribute('data-gps-tooltip', hint);
        });
    }

    function runProgrammatic(fn) {
        programmaticMove = true;
        fn();
        if (map) {
            google.maps.event.addListenerOnce(map, 'idle', function () {
                programmaticMove = false;
            });
        } else {
            programmaticMove = false;
        }
        window.setTimeout(function () { programmaticMove = false; }, 1200);
    }

    function closeMapOverlays() {
        var drawer = document.getElementById('chat_drawer');
        if (!drawer) return;
        var style = window.getComputedStyle(drawer);
        var hidden = drawer.classList.contains('hidden')
            || drawer.getAttribute('data-drawer-closed') === 'true'
            || style.display === 'none'
            || style.visibility === 'hidden';
        if (hidden) return;
        drawer.removeAttribute('data-user-opened');
        var dismiss = drawer.querySelector('[data-kt-drawer-dismiss="true"]');
        if (dismiss) dismiss.click();
        else if (typeof window.handleDrawerClose === 'function') window.handleDrawerClose();
    }

    function scrollMapIntoView() {
        closeMapOverlays();
        var el = document.getElementById('gps-tracking-map');
        var card = document.getElementById('gps-map-card') || (el && el.closest('.kt-card'));
        var target = card || el;
        if (!target) return;
        var header = document.getElementById('header');
        var offset = header ? Math.ceil(header.getBoundingClientRect().height) + 12 : 88;
        target.style.scrollMarginTop = offset + 'px';
        var top = Math.max(0, target.getBoundingClientRect().top + (window.pageYOffset || document.documentElement.scrollTop) - offset);
        window.scrollTo(0, top);
        if (typeof target.scrollIntoView === 'function') {
            target.scrollIntoView({ block: 'start', inline: 'nearest' });
        }
        if (map && typeof google !== 'undefined' && google.maps) {
            window.setTimeout(function () {
                google.maps.event.trigger(map, 'resize');
            }, 80);
        }
    }

    function focusVehicle(id) {
        if (focusVehicle.lock) return;
        focusVehicle.lock = true;
        window.setTimeout(function () { focusVehicle.lock = false; }, 0);
        hideFocusTooltip();
        scrollMapIntoView();
        if (!map || !markers[id]) return;
        followVehicleId = String(id);
        setFollowFleet(false, false);
        syncFollowedLegend();
        runProgrammatic(function () {
            map.panTo(markerLatLng(markers[id]));
            var z = map.getZoom() || 14;
            if (z < 16) map.setZoom(16);
        });
    }
    window.nexaGpsFocusVehicle = focusVehicle;

    function fitAll() {
        var ids = Object.keys(markers);
        if (!map || !ids.length) return;
        runProgrammatic(function () {
            if (ids.length === 1) {
                map.setCenter(markerLatLng(markers[ids[0]]));
                if ((map.getZoom() || 14) > 16) map.setZoom(15);
                return;
            }
            var bounds = new google.maps.LatLngBounds();
            ids.forEach(function (id) { bounds.extend(markerLatLng(markers[id])); });
            map.fitBounds(bounds, { top: 120, right: 88, bottom: 64, left: 88 });
            google.maps.event.addListenerOnce(map, 'idle', function () {
                if ((map.getZoom() || 0) > 16) {
                    programmaticMove = true;
                    map.setZoom(16);
                }
            });
        });
    }

    function markerOverlayRect(marker) {
        var car = marker && marker.div;
        var plate = marker && marker.plateDiv;
        if (!car && !plate) return null;
        var r = car ? car.getBoundingClientRect() : plate.getBoundingClientRect();
        if (car && plate) {
            var p = plate.getBoundingClientRect();
            return {
                left: Math.min(r.left, p.left),
                right: Math.max(r.right, p.right),
                top: Math.min(r.top, p.top),
                bottom: Math.max(r.bottom, p.bottom)
            };
        }
        return r;
    }

    function markerLeavesViewport(id, pad) {
        if (!map) return false;
        var mapDiv = map.getDiv();
        if (!mapDiv) return false;
        var view = mapDiv.getBoundingClientRect();
        var edge = pad == null ? 10 : pad;
        var ids = id ? [id] : Object.keys(markers);
        return ids.some(function (markerId) {
            var marker = markers[markerId];
            if (!marker) return false;
            var r = markerOverlayRect(marker);
            if (!r) {
                var pos = markerLatLng(marker);
                var bounds = map.getBounds();
                return !bounds || !bounds.contains(pos);
            }
            return r.left < view.left + edge
                || r.right > view.right - edge
                || r.top < view.top + edge
                || r.bottom > view.bottom - edge;
        });
    }

    function followedOverflow(pad) {
        if (!followVehicleId || !map || !markers[followVehicleId]) return null;
        var mapDiv = map.getDiv();
        if (!mapDiv) return null;
        var view = mapDiv.getBoundingClientRect();
        var edge = pad == null ? 88 : pad;
        var r = markerOverlayRect(markers[followVehicleId]);
        if (!r) {
            var pos = markerLatLng(markers[followVehicleId]);
            var bounds = map.getBounds();
            if (bounds && bounds.contains(pos)) return null;
            return { x: 0, y: 0, panTo: pos };
        }
        var dx = 0;
        var dy = 0;
        if (r.left < view.left + edge) dx = r.left - (view.left + edge);
        else if (r.right > view.right - edge) dx = r.right - (view.right - edge);
        if (r.top < view.top + edge) dy = r.top - (view.top + edge);
        else if (r.bottom > view.bottom - edge) dy = r.bottom - (view.bottom - edge);
        if (dx === 0 && dy === 0) return null;
        return { x: dx, y: dy };
    }

    function keepFollowedInView(force) {
        if (!followVehicleId || followFleet || !map) return;
        if (!markers[followVehicleId]) {
            followVehicleId = null;
            syncFollowedLegend();
            return;
        }
        var now = performance.now();
        if (!force && now - lastFollowPanAt < 120) return;
        var delta = followedOverflow(88);
        if (!delta) return;
        lastFollowPanAt = now;
        programmaticMove = true;
        if (delta.panTo) {
            map.panTo(delta.panTo);
        } else {
            map.panBy(delta.x, delta.y);
        }
        window.setTimeout(function () { programmaticMove = false; }, 400);
    }

    function ensureAllVisible() {
        if (followVehicleId) {
            keepFollowedInView(true);
            return;
        }
        if (!followFleet) return;
        var ids = Object.keys(markers);
        if (!map || !ids.length) return;
        if (!map.getBounds() || markerLeavesViewport(null, 10)) fitAll();
    }

    function setViewUi(view, unlocked) {
        cfg.view = view === 'offline' ? 'offline' : 'online';
        if (typeof unlocked === 'boolean') cfg.offlineUnlocked = unlocked;
        var onlineBtn = $('gps-view-online');
        var offlineBtn = $('gps-view-offline');
        if (onlineBtn) {
            onlineBtn.classList.toggle('is-active', cfg.view === 'online');
            onlineBtn.setAttribute('aria-pressed', cfg.view === 'online' ? 'true' : 'false');
        }
        if (offlineBtn) {
            offlineBtn.classList.toggle('is-active', cfg.view === 'offline');
            offlineBtn.setAttribute('aria-pressed', cfg.view === 'offline' ? 'true' : 'false');
        }
        var intro = $('gps-page-intro');
        if (intro) {
            intro.textContent = cfg.view === 'offline'
                ? 'Laatst bekende locatie van offline voertuigen. Online auto’s zijn verborgen tot je terugschakelt naar Online.'
                : 'Live locatie van online voertuigen. Chauffeurs moeten online staan in de chauffeur-app en locatie delen.';
        }
    }

    function askOfflineCode() {
        if (!cfg.offlineCodeSet) {
            if ($('gps-code-modal')) { setModal('gps-code-modal', true); return; }
            alert('Stel eerst een veiligheidscode in.');
            return;
        }
        setModal('gps-unlock-modal', true);
        var input = $('gps-unlock-code');
        if (input) {
            input.value = '';
            input.removeAttribute('data-gps-code-checked');
            setGpsFieldError(input, '');
            input.focus();
        }
    }

    async function switchToOnline() {
        if (cfg.offlineUnlocked) {
            try { await fetch(cfg.lockUrl, { method: 'POST', headers: jsonHeaders(), credentials: 'same-origin' }); } catch (e) {}
        }
        setViewUi('online', false);
        fittedOnce = false;
        setFollowFleet(true, false);
        loadPositions();
    }

    async function loadPositions() {
        if (cfg.noTenant) {
            var status = $('gps-status-label');
            if (status) status.textContent = 'Geen bedrijf geselecteerd';
            return;
        }
        try {
            var res = await fetch(cfg.positionsUrl + '?view=' + encodeURIComponent(cfg.view), { headers: jsonHeaders(), credentials: 'same-origin' });
            var data = await res.json();
            if (!res.ok) {
                if (cfg.view === 'offline') setViewUi('online', false);
                throw new Error(data.message || 'Locaties konden niet worden geladen.');
            }
            var items = Array.isArray(data.vehicles) ? data.vehicles : [];
            lastItems = items;
            if (data.view) setViewUi(data.view, !!data.offline_unlocked);
            var keep = {};
            items.forEach(function (item) {
                keep[item.id] = true;
                upsertMarker(item);
            });
            removeMissing(keep);
            renderLegend(items);
            var statusEl = $('gps-status-label');
            if (statusEl) {
                var countLabel = cfg.view === 'offline'
                    ? (items.length + ' offline')
                    : (items.length + ' online');
                statusEl.textContent = data.live_demo ? (countLabel + ' · demo') : countLabel;
            }
            if (items.length) {
                if (!fittedOnce) {
                    fittedOnce = true;
                    fitAll();
                    google.maps.event.addListenerOnce(map, 'idle', fitAll);
                } else {
                    ensureAllVisible();
                }
            }
        } catch (e) {
            var statusErr = $('gps-status-label');
            if (statusErr) statusErr.textContent = e.message || 'Kon locaties niet laden';
        }
    }

    function bootMap() {
        var el = $('gps-tracking-map');
        if (!el || typeof google === 'undefined' || !google.maps) return;
        var center = {
            lat: parseFloat(el.getAttribute('data-lat') || '52.3676'),
            lng: parseFloat(el.getAttribute('data-lng') || '4.9041')
        };
        var zoom = parseInt(el.getAttribute('data-zoom') || '12', 10);
        var mapOpts = {
            center: center,
            zoom: isNaN(zoom) ? 12 : zoom,
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true
        };
        Object.assign(mapOpts, mapThemeOptions(mapDark));
        map = new google.maps.Map(el, mapOpts);
        map.addListener('dragstart', function () {
            if (programmaticMove) return;
            followVehicleId = null;
            setFollowFleet(false, false);
        });
        map.addListener('zoom_changed', function () {
            if (programmaticMove) return;
            if (followVehicleId) return;
            setFollowFleet(false, false);
        });
        loadPositions();
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(loadPositions, cfg.pollMs);
    }

    window.__nexaGpsBoot = bootMap;

    document.addEventListener('click', function (e) {
        var close = e.target.closest('[data-gps-close]');
        if (close) setModal(close.getAttribute('data-gps-close') === 'code' ? 'gps-code-modal' : 'gps-unlock-modal', false);
        if (e.target.id === 'gps-unlock-modal' || e.target.id === 'gps-code-modal') setModal(e.target.id, false);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            setModal('gps-unlock-modal', false);
            setModal('gps-code-modal', false);
        }
    });

    var viewOnline = $('gps-view-online');
    if (viewOnline) viewOnline.addEventListener('click', function () {
        if (cfg.view === 'online') return;
        switchToOnline();
    });
    var viewOffline = $('gps-view-offline');
    if (viewOffline) viewOffline.addEventListener('click', function () {
        if (cfg.view === 'offline') return;
        askOfflineCode();
    });
    var openCode = $('gps-open-code');
    if (openCode) openCode.addEventListener('click', function () { setModal('gps-code-modal', true); });
    bindGpsDigitInputs();
    var codeForm = $('gps-code-form');
    if (codeForm) {
        codeForm.addEventListener('submit', function (e) {
            if (!validateGpsCodeForm(true)) e.preventDefault();
        });
    }
    var unlockForm = $('gps-unlock-form');
    if (unlockForm) {
        unlockForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            var input = $('gps-unlock-code');
            if (input) input.setAttribute('data-gps-code-checked', '1');
            var formatMsg = !input || gpsCodeDigits(input.value) === '' ? 'Vul de veiligheidscode in.' : gpsCodeFormatError(input.value);
            if (formatMsg) {
                setGpsFieldError(input, formatMsg);
                return;
            }
            setGpsFieldError(input, '');
            try {
                var res = await fetch(cfg.unlockUrl, {
                    method: 'POST',
                    headers: jsonHeaders(),
                    credentials: 'same-origin',
                    body: JSON.stringify({ code: input ? input.value : '' })
                });
                var data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Code onjuist.');
                setModal('gps-unlock-modal', false);
                setViewUi('offline', true);
                fittedOnce = false;
                setFollowFleet(true, false);
                loadPositions();
            } catch (ex) {
                setGpsFieldError(input, ex.message || 'Code onjuist.');
            }
        });
    }
    var fitBtn = $('gps-fit-bounds');
    if (fitBtn) fitBtn.addEventListener('click', function () {
        setFollowFleet(true, true);
    });
    var followBtn = $('gps-follow-fleet');
    if (followBtn) followBtn.addEventListener('click', function () {
        setFollowFleet(!followFleet, !followFleet);
    });
    var demoSwitch = $('gps-live-demo');
    if (demoSwitch && cfg.canUseLiveDemo && cfg.demoUrl) {
        demoSwitch.addEventListener('change', async function () {
            var enabled = !!demoSwitch.checked;
            demoSwitch.disabled = true;
            try {
                var res = await fetch(cfg.demoUrl, {
                    method: 'POST',
                    headers: jsonHeaders(),
                    credentials: 'same-origin',
                    body: JSON.stringify({ enabled: enabled })
                });
                var data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Demo kon niet worden gezet.');
                demoSwitch.checked = !!data.enabled;
                if (demoSwitch.checked) {
                    fittedOnce = false;
                    setFollowFleet(true, false);
                }
                loadPositions();
            } catch (ex) {
                demoSwitch.checked = !enabled;
            }
            demoSwitch.disabled = false;
        });
    }
    var themeBtn = $('gps-map-theme');
    if (themeBtn) {
        themeBtn.onclick = function () {
            applyMapTheme(!mapDark, true);
            hideFocusTooltip();
        };
        themeBtn.addEventListener('pointerover', function () {
            showFocusTooltip(themeBtn);
        });
        themeBtn.addEventListener('pointerout', function (e) {
            if (e.relatedTarget && themeBtn.contains(e.relatedTarget)) return;
            hideFocusTooltip();
        });
    }
    applyMapTheme(adminIsDark(), false);
    if (window.MutationObserver) {
        themeObs = new MutationObserver(function () {
            if (mapThemeUserOverride) return;
            var next = adminIsDark();
            if (next !== mapDark) applyMapTheme(next, false);
        });
        themeObs.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-kt-theme-mode'] });
        themeObs.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    }
    var legendBox = $('gps-legend');
    if (legendBox) {
        legendBox.addEventListener('pointerover', function (e) {
            var btn = e.target.closest('[data-gps-tooltip]');
            if (btn && legendBox.contains(btn)) showFocusTooltip(btn);
        });
        legendBox.addEventListener('pointerout', function (e) {
            var btn = e.target.closest('[data-gps-tooltip]');
            if (!btn) return;
            if (e.relatedTarget && btn.contains(e.relatedTarget)) return;
            hideFocusTooltip();
        });
        legendBox.addEventListener('scroll', hideFocusTooltip, true);
    }
    window.addEventListener('scroll', hideFocusTooltip, true);

    setViewUi('online', false);
    if (cfg.openCodeModal) setModal('gps-code-modal', true);

    if (cfg.hasMapsKey && typeof google !== 'undefined' && google.maps) bootMap();
    else if (!cfg.hasMapsKey) {
        var status = $('gps-status-label');
        if (status) status.textContent = 'Geen Google Maps-sleutel';
        if (!cfg.noTenant) loadPositions();
    }
})();
</script>
@endsection
