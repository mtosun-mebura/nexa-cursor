{{-- Platform Dummy voertuigen (Configuraties → Algemene configuraties) --}}
@php
    $vehicleDummySettings = $vehicleDummySettings ?? app(\App\Services\VehicleDummyImageService::class)->settingsPayload();
    $dummyBg = $vehicleDummySettings['background_color'] ?? null;
    $dummyBgPicker = $dummyBg ?: '#e5e7eb';
    $dummyTransparent = $dummyBg === null || $dummyBg === '';
@endphp
@once
@push('styles')
<style>
    /* Expliciet: theme/dark mode mag selectierand niet wegpoetsen */
    #dummy-voertuigen .vehicle-dummy-select-btn {
        border-width: 2px;
        border-style: solid;
        border-color: var(--border, #374151);
    }
    #dummy-voertuigen .vehicle-dummy-tile.is-selected .vehicle-dummy-select-btn,
    #dummy-voertuigen .vehicle-dummy-select-btn[aria-pressed="true"] {
        border-width: 3px !important;
        border-color: #22c55e !important; /* green-500, zichtbaar op dark UI */
        box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.35);
    }
</style>
@endpush
@endonce
<div class="kt-card mb-8 settings-collapsible-card settings-collapsible-card--collapsed" id="dummy-voertuigen">
    @include('admin.settings.partials.collapsible-header', ['titleHtml' => e(__('Dummy voertuigen'))])
    <div class="settings-collapsible-body">
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <p class="settings-section-intro">
                {{ __('Kies per type (Auto, Busje, Bus) welke placeholder getoond wordt als een voertuig geen foto heeft. Upload eigen afbeeldingen en stel optioneel een achtergrondkleur in (standaard transparant).') }}
            </p>

            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                <tbody>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Achtergrondkleur') }}</td>
                    <td class="min-w-48 w-full">
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="kt-label flex items-center gap-2 mb-0" for="vehicle-dummy-bg-transparent">
                                <input type="checkbox"
                                       id="vehicle-dummy-bg-transparent"
                                       class="kt-checkbox shrink-0"
                                       {{ $dummyTransparent ? 'checked' : '' }}
                                       aria-label="{{ __('Transparant') }}">
                                <span class="text-sm text-muted-foreground">{{ __('Transparant (standaard)') }}</span>
                            </label>
                            <input type="color"
                                   id="vehicle-dummy-bg-picker"
                                   class="h-9 w-14 cursor-pointer rounded border border-input bg-background p-1 shrink-0"
                                   value="{{ $dummyBgPicker }}"
                                   title="{{ __('Kies achtergrondkleur') }}"
                                   aria-label="{{ __('Achtergrondkleur dummy-vlak') }}">
                            <input type="text"
                                   class="kt-input font-mono text-sm w-28 admin-field-fit"
                                   id="vehicle-dummy-bg-hex"
                                   value="{{ $dummyTransparent ? '' : $dummyBg }}"
                                   maxlength="7"
                                   placeholder="#RRGGBB"
                                   pattern="^#?([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})?$">
                            <button type="button" class="kt-btn kt-btn-outline kt-btn-sm" id="vehicle-dummy-bg-save">
                                {{ __('Opslaan') }}
                            </button>
                        </div>
                        <div class="text-xs text-muted-foreground mt-1">{{ __('Kleur achter het dummyplaatje in lijsten en formulieren. Leeg/transparant = geen achtergrondvlak.') }}</div>
                        <div id="vehicle-dummy-bg-msg" class="text-sm mt-1 hidden" role="status" aria-live="polite"></div>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">{{ __('Afbeeldingen') }}</td>
                    <td class="min-w-48 w-full align-top pt-4">
                        <div id="vehicle-dummy-catalog" class="flex flex-col gap-6">
                            @foreach(($vehicleDummySettings['types'] ?? []) as $typeKey => $typeBlock)
                                <div class="vehicle-dummy-type-block" data-type="{{ $typeKey }}">
                                    <h4 class="text-sm font-medium text-mono mb-3">{{ __($typeBlock['label'] ?? $typeKey) }}</h4>
                                    <div class="flex flex-wrap gap-3 vehicle-dummy-type-grid">
                                        @foreach(($typeBlock['images'] ?? []) as $img)
                                            @php $isSelected = !empty($img['selected']); @endphp
                                            <div class="vehicle-dummy-tile relative flex flex-col items-center gap-1.5 {{ $isSelected ? 'is-selected' : '' }}"
                                                 data-id="{{ $img['id'] }}"
                                                 data-type="{{ $typeKey }}"
                                                 data-builtin="{{ !empty($img['builtin']) ? '1' : '0' }}">
                                                <button type="button"
                                                        class="vehicle-dummy-select-btn block p-2.5 border-2 rounded-xl overflow-hidden transition-colors {{ $isSelected ? 'border-green-500' : 'border-border hover:border-primary/50' }}"
                                                        data-id="{{ $img['id'] }}"
                                                        data-type="{{ $typeKey }}"
                                                        aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                                        aria-label="{{ __('Selecteer :label', ['label' => $img['label'] ?? $img['id']]) }}"
                                                        style="background: {{ $dummyTransparent ? 'transparent' : e($dummyBg) }};">
                                                    <img src="{{ $img['url'] }}"
                                                         alt=""
                                                         class="w-[140px] h-[80px] object-contain block"
                                                         width="140"
                                                         height="80">
                                                </button>
                                                @if(empty($img['builtin']))
                                                    <button type="button"
                                                            class="vehicle-dummy-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive"
                                                            data-id="{{ $img['id'] }}"
                                                            title="{{ __('Verwijderen') }}"
                                                            aria-label="{{ __('Verwijderen') }}">
                                                        <i class="ki-filled ki-trash"></i>
                                                    </button>
                                                @else
                                                    <span class="text-[11px] text-muted-foreground">{{ __('Standaard') }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 pt-4 border-t border-border">
                            <p class="text-sm font-medium text-secondary-foreground mb-2">{{ __('Nieuwe afbeelding toevoegen') }}</p>
                            <div class="flex flex-wrap items-end gap-3 mb-3">
                                <div>
                                    <label for="vehicle-dummy-upload-type" class="text-xs text-muted-foreground block mb-1">{{ __('Type') }}</label>
                                    <select id="vehicle-dummy-upload-type" class="kt-select admin-field-fit" data-kt-select="true">
                                        <option value="car">{{ __('Auto') }}</option>
                                        <option value="van">{{ __('Busje') }}</option>
                                        <option value="bus">{{ __('Bus') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div id="vehicle-dummy-upload-area"
                                 class="hero-image-upload-area relative flex flex-col items-center justify-center gap-1.5 p-5 border border-input rounded-xl border-dashed bg-muted/30 cursor-pointer hover:border-primary/50 transition-colors w-full max-w-md"
                                 role="button"
                                 tabindex="0">
                                <i class="ki-filled ki-picture text-2xl text-primary"></i>
                                <span class="text-xs text-muted-foreground text-center">{{ __('Klik of sleep afbeelding') }}</span>
                                <span class="text-xs text-muted-foreground">{{ __('JPG, PNG, WebP (max. 5MB)') }}</span>
                            </div>
                            <input type="file" id="vehicle-dummy-file-input" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                            <div id="vehicle-dummy-upload-msg" class="text-sm mt-2 hidden" role="status" aria-live="polite"></div>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var uploadUrl = @json(route('admin.settings.vehicle-dummies.upload'));
    var selectUrl = @json(route('admin.settings.vehicle-dummies.select'));
    var removeUrl = @json(route('admin.settings.vehicle-dummies.remove'));
    var bgUrl = @json(route('admin.settings.vehicle-dummies.background'));
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';

    function showMsg(el, msg, ok) {
        if (!el) return;
        el.textContent = msg || '';
        el.classList.toggle('hidden', !msg);
        el.classList.toggle('text-destructive', !!msg && !ok);
        el.classList.toggle('text-success', !!msg && !!ok);
        el.classList.toggle('text-green-600', !!msg && !!ok);
    }

    function csrfHeaders() {
        return {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token
        };
    }

    function applyBgToTiles(color) {
        var transparent = !color;
        document.querySelectorAll('.vehicle-dummy-select-btn').forEach(function (btn) {
            btn.style.background = transparent ? 'transparent' : color;
        });
    }

    function renderCatalog(settings) {
        var root = document.getElementById('vehicle-dummy-catalog');
        if (!root || !settings || !settings.types) return;
        var bg = settings.background_color || null;
        var html = '';
        Object.keys(settings.types).forEach(function (typeKey) {
            var block = settings.types[typeKey];
            html += '<div class="vehicle-dummy-type-block" data-type="' + typeKey + '">';
            html += '<h4 class="text-sm font-medium text-mono mb-3">' + (block.label || typeKey) + '</h4>';
            html += '<div class="flex flex-wrap gap-3 vehicle-dummy-type-grid">';
            (block.images || []).forEach(function (img) {
                var selected = !!img.selected;
                html += '<div class="vehicle-dummy-tile relative flex flex-col items-center gap-1.5' + (selected ? ' is-selected' : '') + '" data-id="' + img.id + '" data-type="' + typeKey + '" data-builtin="' + (img.builtin ? '1' : '0') + '">';
                html += '<button type="button" class="vehicle-dummy-select-btn block p-2.5 border-2 rounded-xl overflow-hidden transition-colors ' + (selected ? 'border-green-500' : 'border-border hover:border-primary/50') + '" data-id="' + img.id + '" data-type="' + typeKey + '" aria-pressed="' + (selected ? 'true' : 'false') + '" style="background: ' + (bg ? bg : 'transparent') + ';">';
                html += '<img src="' + img.url + '" alt="" class="w-[140px] h-[80px] object-contain block" width="140" height="80">';
                html += '</button>';
                if (!img.builtin) {
                    html += '<button type="button" class="vehicle-dummy-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive" data-id="' + img.id + '" title="Verwijderen" aria-label="Verwijderen"><i class="ki-filled ki-trash"></i></button>';
                } else {
                    html += '<span class="text-[11px] text-muted-foreground">Standaard</span>';
                }
                html += '</div>';
            });
            html += '</div></div>';
        });
        root.innerHTML = html;
        bindCatalog();
    }

    function bindCatalog() {
        document.querySelectorAll('.vehicle-dummy-select-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var id = btn.getAttribute('data-id');
                var type = btn.getAttribute('data-type');
                fetch(selectUrl, {
                    method: 'POST',
                    headers: Object.assign({ 'Content-Type': 'application/json' }, csrfHeaders()),
                    body: JSON.stringify({ id: id, type: type })
                })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        if (res.ok && res.d.success && res.d.settings) {
                            renderCatalog(res.d.settings);
                        }
                    })
                    .catch(function () {});
            });
        });
        document.querySelectorAll('.vehicle-dummy-remove-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (!confirm(@json(__('Deze afbeelding verwijderen?')))) return;
                var id = btn.getAttribute('data-id');
                fetch(removeUrl, {
                    method: 'POST',
                    headers: Object.assign({ 'Content-Type': 'application/json' }, csrfHeaders()),
                    body: JSON.stringify({ id: id })
                })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                    .then(function (res) {
                        if (res.ok && res.d.success && res.d.settings) {
                            renderCatalog(res.d.settings);
                        }
                    })
                    .catch(function () {});
            });
        });
    }

    var transparentCb = document.getElementById('vehicle-dummy-bg-transparent');
    var picker = document.getElementById('vehicle-dummy-bg-picker');
    var hexInput = document.getElementById('vehicle-dummy-bg-hex');
    var bgSave = document.getElementById('vehicle-dummy-bg-save');
    var bgMsg = document.getElementById('vehicle-dummy-bg-msg');

    function currentPickerHex() {
        return picker && picker.value ? String(picker.value).toUpperCase() : '#E5E7EB';
    }

    function syncBgControls() {
        var transparent = transparentCb && transparentCb.checked;
        if (transparent && hexInput) {
            hexInput.value = '';
        }
        applyBgToTiles(transparent ? null : ((hexInput && hexInput.value) || currentPickerHex()));
    }

    if (transparentCb) {
        transparentCb.addEventListener('change', syncBgControls);
    }
    if (picker && hexInput) {
        // Native color dialog only opens when the input is enabled — never disable it.
        picker.addEventListener('input', function () {
            if (transparentCb) transparentCb.checked = false;
            hexInput.value = picker.value.toUpperCase();
            applyBgToTiles(hexInput.value);
        });
        picker.addEventListener('click', function () {
            // Choosing a color implies leaving transparent mode.
            if (transparentCb && transparentCb.checked) {
                transparentCb.checked = false;
            }
        });
        hexInput.addEventListener('input', function () {
            var v = (hexInput.value || '').trim();
            if (v === '') {
                if (transparentCb) transparentCb.checked = true;
                applyBgToTiles(null);
                return;
            }
            if (transparentCb) transparentCb.checked = false;
            if (/^#?[A-Fa-f0-9]{6}$/.test(v)) {
                if (v.charAt(0) !== '#') v = '#' + v;
                picker.value = v;
                applyBgToTiles(v.toUpperCase());
            }
        });
    }
    if (bgSave) {
        bgSave.addEventListener('click', function () {
            var transparent = transparentCb && transparentCb.checked;
            var color = transparent
                ? ''
                : ((hexInput && hexInput.value) || currentPickerHex() || '').trim();
            fetch(bgUrl, {
                method: 'POST',
                headers: Object.assign({ 'Content-Type': 'application/json' }, csrfHeaders()),
                body: JSON.stringify({ background_color: color })
            })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    showMsg(bgMsg, (res.d && res.d.message) || (res.ok ? @json(__('Opgeslagen.')) : @json(__('Opslaan mislukt.'))), res.ok && res.d && res.d.success);
                    if (res.ok && res.d && res.d.settings) {
                        var saved = res.d.settings.background_color || null;
                        if (transparentCb) transparentCb.checked = !saved;
                        if (hexInput) hexInput.value = saved || '';
                        if (picker && saved) picker.value = saved;
                        applyBgToTiles(saved);
                    }
                })
                .catch(function () {
                    showMsg(bgMsg, @json(__('Opslaan mislukt.')), false);
                });
        });
    }

    var area = document.getElementById('vehicle-dummy-upload-area');
    var fileInput = document.getElementById('vehicle-dummy-file-input');
    var typeSelect = document.getElementById('vehicle-dummy-upload-type');
    var uploadMsg = document.getElementById('vehicle-dummy-upload-msg');

    function handleFile(file) {
        showMsg(uploadMsg, '', true);
        if (!file || !file.type || !file.type.match(/^image\/(jpeg|png|gif|webp)$/i)) {
            showMsg(uploadMsg, @json(__('Alleen JPG, PNG, GIF of WebP (max. 5MB).')), false);
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showMsg(uploadMsg, @json(__('Bestand mag maximaal 5MB zijn.')), false);
            return;
        }
        var type = typeSelect ? typeSelect.value : 'car';
        var fd = new FormData();
        fd.append('image', file);
        fd.append('type', type);
        fd.append('_token', token);
        fetch(uploadUrl, { method: 'POST', body: fd, headers: csrfHeaders() })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                if (res.ok && res.d.success) {
                    showMsg(uploadMsg, res.d.message || @json(__('Geüpload.')), true);
                    if (res.d.settings) renderCatalog(res.d.settings);
                } else {
                    showMsg(uploadMsg, (res.d && res.d.message) || @json(__('Upload mislukt.')), false);
                }
            })
            .catch(function () {
                showMsg(uploadMsg, @json(__('Upload mislukt.')), false);
            });
        if (fileInput) fileInput.value = '';
    }

    if (area && fileInput) {
        if (typeof window.bindAdminUploadAreaClick === 'function') {
            window.bindAdminUploadAreaClick(area, fileInput, { clearInputFirst: false });
        } else {
            area.addEventListener('click', function (e) {
                e.preventDefault();
                fileInput.click();
            });
        }
        area.addEventListener('dragover', function (e) { e.preventDefault(); area.classList.add('border-primary'); });
        area.addEventListener('dragleave', function (e) { e.preventDefault(); area.classList.remove('border-primary'); });
        area.addEventListener('drop', function (e) {
            e.preventDefault();
            area.classList.remove('border-primary');
            if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', function () {
            if (this.files && this.files.length) handleFile(this.files[0]);
        });
    }

    bindCatalog();
})();
</script>
@endpush
@endonce
