@extends('admin.layouts.app')

@section('title', 'GPS-configuratie')

@section('content')
<style>
    .nexa-gps-preview-stage {
        min-height: 180px;
        background:
            linear-gradient(rgba(148,163,184,.25) 1px, transparent 1px) 0 0 / 24px 24px,
            linear-gradient(90deg, rgba(148,163,184,.25) 1px, transparent 1px) 0 0 / 24px 24px,
            #e2e8f0;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 20px;
        padding: 24px;
    }
    .nexa-gps-style-option {
        cursor: pointer;
    }
    .nexa-gps-style-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .nexa-gps-style-card {
        border: 2px solid var(--border);
        border-radius: 12px;
        padding: 12px;
        background: var(--background);
        min-height: 168px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }
    .nexa-gps-color-swatch {
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px rgba(15,23,42,.2);
        cursor: pointer;
    }
    .nexa-gps-color-swatch.is-active {
        box-shadow: 0 0 0 2px #2563eb;
    }
</style>

<div class="kt-container-fixed min-w-0">
    <div class="pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">GPS-configuratie</h1>
        <p class="text-sm text-muted-foreground mt-2 mb-0 leading-relaxed">
            Kies hoe auto’s en kentekens op de live kaart worden getoond, en hoe vaak posities worden ververst.
            @if($canManage && empty($noTenantSelected))
                Wijzigingen worden automatisch opgeslagen.
            @endif
        </p>
        <p id="gps-save-status" class="text-xs text-muted-foreground mt-1 mb-0 min-h-4" aria-live="polite"></p>
        <a href="{{ $mapUrl }}" class="kt-btn kt-btn-outline mt-3">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug naar kaart
        </a>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5">{{ session('success') }}</div>
    @endif
    @if($errors->has('tenant'))
        <div class="kt-alert kt-alert-warning mb-5">{{ $errors->first('tenant') }}</div>
    @endif
    @if(!empty($noTenantSelected))
        <div class="kt-alert kt-alert-warning mb-5">
            Selecteer eerst een tenant (bedrijf) in de zijbalk. GPS-configuratie wordt per bedrijf opgeslagen.
        </div>
    @endif

    <form action="{{ $saveUrl }}" method="POST" class="grid gap-5 lg:gap-7.5 w-full min-w-0" id="gps-appearance-form">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:grid-cols-5 lg:gap-7.5 items-start">
            <div class="lg:col-span-3 grid gap-5">
                <div class="kt-card w-full min-w-0">
                    <div class="kt-card-header px-5 py-5">
                        <h5 class="kt-card-title mb-0">Autoplaatje</h5>
                    </div>
                    <div class="kt-card-content p-5 lg:p-6 space-y-5">
                        <div>
                            <p class="text-sm font-medium text-foreground mb-3">Type</p>
                            <div class="grid grid-cols-3 gap-3">
                                @foreach($carStyles as $styleKey => $styleLabel)
                                    <div class="nexa-gps-style-card">
                                        <span class="nexa-gps-style-preview" data-style="{{ $styleKey }}"></span>
                                        <span class="text-sm font-medium text-foreground">{{ $styleLabel }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-foreground mb-2">Kleur</p>
                            <div class="flex flex-wrap items-center gap-4 mb-3">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="car_color_mode" value="single" data-gps-preview @checked(old('car_color_mode', $appearance['car_color_mode']) === 'single')>
                                    Eén kleur per type auto
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="car_color_mode" value="per_vehicle" data-gps-preview @checked(old('car_color_mode', $appearance['car_color_mode']) === 'per_vehicle')>
                                    Eigen kleur per voertuig
                                </label>
                            </div>

                            <div id="gps-single-color-controls" class="{{ old('car_color_mode', $appearance['car_color_mode']) === 'single' ? '' : 'hidden' }}">
                                @foreach($carStyles as $styleKey => $styleLabel)
                                    @php
                                        $typeColor = old('type_colors.'.$styleKey, $appearance['type_colors'][$styleKey] ?? $appearance['car_color']);
                                    @endphp
                                    <div class="rounded-xl border border-border p-4 {{ $loop->first ? '' : 'mt-3' }}" data-gps-type-row="{{ $styleKey }}">
                                        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                                            <p class="font-medium text-foreground mb-0">{{ $styleLabel }}</p>
                                            <div class="flex items-center gap-2">
                                                <input type="color" id="gps-type-color-picker-{{ $styleKey }}" class="h-10 w-14 cursor-pointer rounded border border-input p-1" value="{{ $typeColor }}" aria-label="Kleur {{ $styleLabel }}">
                                                <input type="text" name="type_colors[{{ $styleKey }}]" id="gps-type-color-{{ $styleKey }}" data-gps-preview class="kt-input font-mono text-sm w-28" maxlength="7" value="{{ $typeColor }}">
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            @foreach($carColorPresets as $preset)
                                                <button type="button" class="nexa-gps-color-swatch" data-gps-type-color="{{ $styleKey }}" data-gps-color="{{ $preset['hex'] }}" style="background: {{ $preset['hex'] }}" title="{{ $preset['label'] }}"></button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                                @error('type_colors.*')
                                    <p class="text-xs text-destructive mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="gps-per-vehicle-controls" class="{{ old('car_color_mode', $appearance['car_color_mode']) === 'per_vehicle' ? '' : 'hidden' }}">
                                @if(($fleet ?? []) === [])
                                    <p class="text-sm text-muted-foreground mb-0">Er zijn nog geen voertuigen. Voeg eerst voertuigen toe om per auto een kleur in te stellen.</p>
                                @else
                                    @foreach($fleet as $vehicle)
                                    @php
                                        $vehicleColor = old('vehicle_colors.'.$vehicle['id'], $vehicle['color']);
                                    @endphp
                                    <div class="rounded-xl border border-border p-4 {{ $loop->first ? '' : 'mt-3' }}" data-gps-vehicle-row="{{ $vehicle['id'] }}">
                                        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                                            <div class="min-w-0">
                                                <p class="font-mono font-semibold tracking-wide text-foreground mb-0">{{ $vehicle['license_plate'] }}</p>
                                                <p class="text-sm text-muted-foreground mb-0">{{ $vehicle['driver_name'] }}{{ $vehicle['active'] ? '' : ' · Inactief' }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <input type="color" id="gps-vehicle-color-picker-{{ $vehicle['id'] }}" class="h-10 w-14 cursor-pointer rounded border border-input p-1" value="{{ $vehicleColor }}" aria-label="Kleur {{ $vehicle['license_plate'] }}">
                                                <input type="text" name="vehicle_colors[{{ $vehicle['id'] }}]" id="gps-vehicle-color-{{ $vehicle['id'] }}" data-gps-preview class="kt-input font-mono text-sm w-28" maxlength="7" value="{{ $vehicleColor }}">
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            @foreach($carColorPresets as $preset)
                                                <button type="button" class="nexa-gps-color-swatch" data-gps-vehicle-color="{{ $vehicle['id'] }}" data-gps-color="{{ $preset['hex'] }}" style="background: {{ $preset['hex'] }}" title="{{ $preset['label'] }}"></button>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="kt-card w-full min-w-0">
                    <div class="kt-card-header px-5 py-5">
                        <h5 class="kt-card-title mb-0">Kenteken</h5>
                    </div>
                    <div class="kt-card-content p-5 lg:p-6">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="kt-label mb-1" for="gps-plate-bg">Achtergrond kader</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" class="h-10 w-14 cursor-pointer rounded border border-input p-1" id="gps-plate-bg-picker" value="{{ old('plate_background', $appearance['plate_background']) }}">
                                    <input type="text" name="plate_background" id="gps-plate-bg" data-gps-preview class="kt-input font-mono text-sm" maxlength="7" value="{{ old('plate_background', $appearance['plate_background']) }}">
                                </div>
                                @error('plate_background')
                                    <p class="text-xs text-destructive mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="kt-label mb-1" for="gps-plate-text">Tekst</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" class="h-10 w-14 cursor-pointer rounded border border-input p-1" id="gps-plate-text-picker" value="{{ old('plate_text_color', $appearance['plate_text_color']) }}">
                                    <input type="text" name="plate_text_color" id="gps-plate-text" data-gps-preview class="kt-input font-mono text-sm" maxlength="7" value="{{ old('plate_text_color', $appearance['plate_text_color']) }}">
                                </div>
                                @error('plate_text_color')
                                    <p class="text-xs text-destructive mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="kt-label mb-1" for="gps-plate-border">Rand</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" class="h-10 w-14 cursor-pointer rounded border border-input p-1" id="gps-plate-border-picker" value="{{ old('plate_border_color', $appearance['plate_border_color']) }}">
                                    <input type="text" name="plate_border_color" id="gps-plate-border" data-gps-preview class="kt-input font-mono text-sm" maxlength="7" value="{{ old('plate_border_color', $appearance['plate_border_color']) }}">
                                </div>
                                @error('plate_border_color')
                                    <p class="text-xs text-destructive mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="kt-card w-full min-w-0">
                    <div class="kt-card-header px-5 py-5">
                        <h5 class="kt-card-title mb-0">Vernieuwen</h5>
                    </div>
                    <div class="kt-card-content p-5 lg:p-6">
                        <label class="kt-label mb-1" for="gps-refresh-seconds">Aantal seconden tussen positie-updates</label>
                        <input type="number" name="refresh_seconds" id="gps-refresh-seconds" class="kt-input w-20 tabular-nums @error('refresh_seconds') border-destructive @enderror" min="{{ $minRefresh }}" max="{{ $maxRefresh }}" step="1" inputmode="numeric" maxlength="4" required value="{{ old('refresh_seconds', $appearance['refresh_seconds']) }}">
                        <p class="text-xs text-muted-foreground mt-1">Tussen {{ $minRefresh }} en {{ $maxRefresh }} seconden. 1 seconde geeft de soepelste beweging op de kaart.</p>
                        @error('refresh_seconds')
                            <p class="text-xs text-destructive mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="kt-card w-full min-w-0 lg:sticky lg:top-24">
                    <div class="kt-card-header px-5 py-5">
                        <h5 class="kt-card-title mb-0">Voorbeeld</h5>
                    </div>
                    <div class="kt-card-content p-5 lg:p-6">
                        <div class="nexa-gps-preview-stage" id="gps-live-preview"></div>
                        <p class="text-xs text-muted-foreground mt-3 mb-0">Het kenteken staat boven de auto, met een eigen kader zodat de tekst leesbaar blijft.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .nexa-gps-marker {
        display: flex;
        flex-direction: column;
        align-items: center;
        pointer-events: none;
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
        border: 2px solid #111827;
        border-radius: 4px;
        padding: 5px 8px;
        margin-bottom: 6px;
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(0,0,0,.35);
    }
    .nexa-gps-car-rot {
        position: relative;
        z-index: 1;
        display: flex;
        transform-origin: 50% 55%;
        filter: drop-shadow(0 2px 3px rgba(0,0,0,.35));
    }
    .nexa-gps-car-img {
        display: block;
        object-fit: contain;
    }
</style>
@include('taxi::admin.gps-tracking.partials.marker-script')
<script>
window.NexaGpsFleet = @json($fleet ?? []);
(function () {
    var form = document.getElementById('gps-appearance-form');
    var preview = document.getElementById('gps-live-preview');
    var singleControls = document.getElementById('gps-single-color-controls');
    var perVehicleControls = document.getElementById('gps-per-vehicle-controls');
    var saveStatus = document.getElementById('gps-save-status');
    var canAutosave = @json($canManage && empty($noTenantSelected));
    var saveTimer = null;
    var saveRequest = null;
    var lastSaved = '';
    var hexOk = /^#?[0-9A-Fa-f]{6}$/;
    var minRefresh = {{ (int) $minRefresh }};
    var maxRefresh = {{ (int) $maxRefresh }};
    function setSaveStatus(text) {
        if (saveStatus) saveStatus.textContent = text || '';
    }
    function formPayload() {
        return new URLSearchParams(new FormData(form)).toString();
    }
    function refreshIsValid() {
        var seconds = document.getElementById('gps-refresh-seconds');
        if (!seconds) return false;
        var n = parseInt(seconds.value, 10);
        return Number.isInteger(n) && n >= minRefresh && n <= maxRefresh;
    }
    function colorsAreValid() {
        var ok = true;
        form.querySelectorAll('input[data-gps-preview][type="text"]').forEach(function (el) {
            if (el.value && !hexOk.test(el.value)) ok = false;
        });
        return ok;
    }
    function saveNow() {
        if (!canAutosave || !form) return;
        if (!refreshIsValid() || !colorsAreValid()) return;
        var payload = formPayload();
        if (payload === lastSaved) return;
        if (saveRequest) saveRequest.abort();
        lastSaved = payload;
        setSaveStatus('Opslaan…');
        saveRequest = new AbortController();
        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new FormData(form),
            signal: saveRequest.signal,
            credentials: 'same-origin'
        }).then(function (res) {
            if (!res.ok) throw res;
            setSaveStatus('Opgeslagen');
        }).catch(function (err) {
            if (err && err.name === 'AbortError') return;
            lastSaved = '';
            setSaveStatus('Opslaan mislukt. Probeer het opnieuw.');
        });
    }
    function scheduleSave(delay) {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveNow, delay || 0);
    }
    function clampRefreshDigits(el) {
        if (!el) return;
        var digits = String(el.value || '').replace(/\D/g, '').slice(0, 4);
        if (el.value !== digits) el.value = digits;
    }
    function val(name) {
        var el = form.querySelector('[name="' + name + '"]');
        if (!el) return '';
        if (el.type === 'radio') {
            var checked = form.querySelector('[name="' + name + '"]:checked');
            return checked ? checked.value : '';
        }
        return el.value;
    }
    function vehicleColor(id) {
        var input = document.getElementById('gps-vehicle-color-' + id);
        return input && input.value ? input.value : '#ea580c';
    }
    function typeColor(style) {
        var input = document.getElementById('gps-type-color-' + style);
        return input && input.value ? input.value : '#ea580c';
    }
    function appearance() {
        return {
            car_color_mode: val('car_color_mode') || 'single',
            car_color: typeColor('sedan'),
            type_colors: {
                sedan: typeColor('sedan'),
                van: typeColor('van'),
                bus: typeColor('bus')
            },
            plate_background: val('plate_background') || '#f7e125',
            plate_text_color: val('plate_text_color') || '#111827',
            plate_border_color: val('plate_border_color') || '#111827'
        };
    }
    function previewCars() {
        var mode = appearance().car_color_mode;
        var fleet = window.NexaGpsFleet || [];
        if (mode === 'per_vehicle' && fleet.length) {
            return fleet.map(function (v) {
                return {
                    license_plate: v.license_plate,
                    driver_name: v.driver_name,
                    vehicle_id: v.id,
                    car_style: v.car_style || 'sedan',
                    is_online: true,
                    color: vehicleColor(v.id)
                };
            });
        }
        return ['sedan', 'van', 'bus'].map(function (style) {
            var labels = { sedan: 'Auto', van: 'Busje', bus: 'Bus' };
            return {
                license_plate: '12-GPS-1',
                driver_name: labels[style],
                car_style: style,
                is_online: true,
                color: typeColor(style)
            };
        });
    }
    function syncColorModeUi() {
        var mode = val('car_color_mode') || 'single';
        if (singleControls) singleControls.classList.toggle('hidden', mode !== 'single');
        if (perVehicleControls) perVehicleControls.classList.toggle('hidden', mode !== 'per_vehicle');
    }
    function renderPreview() {
        if (!preview || !window.NexaGpsMarker) return;
        syncColorModeUi();
        preview.innerHTML = '';
        var cars = previewCars();
        cars.forEach(function (item) {
            var col = document.createElement('div');
            col.className = 'flex flex-col items-center gap-1';
            col.appendChild(window.NexaGpsMarker.markerNode(item, appearance(), 20, 'preview'));
            if (item.driver_name) {
                var cap = document.createElement('span');
                cap.className = 'text-xs text-slate-600';
                cap.textContent = item.driver_name;
                col.appendChild(cap);
            }
            preview.appendChild(col);
        });
        var styleColor = appearance().type_colors || {};
        document.querySelectorAll('.nexa-gps-style-preview').forEach(function (el) {
            var style = el.getAttribute('data-style');
            window.NexaGpsMarker.paintCarInto(el, style, styleColor[style] || typeColor(style), 'picker');
        });
        document.querySelectorAll('[data-gps-type-color]').forEach(function (btn) {
            var hex = typeColor(btn.getAttribute('data-gps-type-color'));
            btn.classList.toggle('is-active', btn.getAttribute('data-gps-color').toLowerCase() === String(hex).toLowerCase());
        });
        document.querySelectorAll('[data-gps-vehicle-color]').forEach(function (btn) {
            var hex = vehicleColor(btn.getAttribute('data-gps-vehicle-color'));
            btn.classList.toggle('is-active', btn.getAttribute('data-gps-color').toLowerCase() === String(hex).toLowerCase());
        });
    }
    function bindPair(pickerId, inputId) {
        var picker = document.getElementById(pickerId);
        var input = document.getElementById(inputId);
        if (!picker || !input) return;
        picker.addEventListener('input', function () {
            input.value = picker.value;
            renderPreview();
        });
        picker.addEventListener('change', function () {
            input.value = picker.value;
            renderPreview();
            saveNow();
        });
        input.addEventListener('input', function () {
            if (hexOk.test(input.value)) {
                picker.value = input.value.charAt(0) === '#' ? input.value : '#' + input.value;
            }
            renderPreview();
        });
        input.addEventListener('change', function () {
            if (hexOk.test(input.value)) saveNow();
        });
    }
    bindPair('gps-plate-bg-picker', 'gps-plate-bg');
    bindPair('gps-plate-text-picker', 'gps-plate-text');
    bindPair('gps-plate-border-picker', 'gps-plate-border');
    ['sedan', 'van', 'bus'].forEach(function (style) {
        bindPair('gps-type-color-picker-' + style, 'gps-type-color-' + style);
    });
    (window.NexaGpsFleet || []).forEach(function (v) {
        bindPair('gps-vehicle-color-picker-' + v.id, 'gps-vehicle-color-' + v.id);
    });
    form.addEventListener('click', function (event) {
        var typeBtn = event.target.closest('[data-gps-type-color]');
        if (typeBtn) {
            var style = typeBtn.getAttribute('data-gps-type-color');
            var hex = typeBtn.getAttribute('data-gps-color');
            var input = document.getElementById('gps-type-color-' + style);
            var picker = document.getElementById('gps-type-color-picker-' + style);
            if (input) input.value = hex;
            if (picker) picker.value = hex;
            renderPreview();
            saveNow();
            return;
        }
        var vehicleBtn = event.target.closest('[data-gps-vehicle-color]');
        if (!vehicleBtn) return;
        var id = vehicleBtn.getAttribute('data-gps-vehicle-color');
        var vehicleHex = vehicleBtn.getAttribute('data-gps-color');
        var vehicleInput = document.getElementById('gps-vehicle-color-' + id);
        var vehiclePicker = document.getElementById('gps-vehicle-color-picker-' + id);
        if (vehicleInput) vehicleInput.value = vehicleHex;
        if (vehiclePicker) vehiclePicker.value = vehicleHex;
        renderPreview();
        saveNow();
    });
    var refreshInput = document.getElementById('gps-refresh-seconds');
    if (refreshInput) {
        refreshInput.addEventListener('input', function () {
            clampRefreshDigits(refreshInput);
            scheduleSave(400);
        });
    }
    form.addEventListener('change', function (event) {
        renderPreview();
        if (event.target && event.target.id === 'gps-refresh-seconds') {
            clampRefreshDigits(event.target);
        }
        saveNow();
    });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        saveNow();
    });
    lastSaved = formPayload();
    renderPreview();
})();
</script>
@endsection
