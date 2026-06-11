@extends('admin.layouts.app')

@section('title', 'Nieuw voertuig')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Nieuw voertuig
        </h1>
        <a href="{{ route('admin.taxi.vehicles.index') }}" class="kt-btn kt-btn-outline shrink-0">
            <svg class="w-4 h-4 me-2 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            Terug
        </a>
    </div>

    <form action="{{ route('admin.taxi.vehicles.store') }}" method="POST" data-validate="true" novalidate>
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            @if(auth()->user()->hasRole('super-admin') && ($superAdminNeedsTenant ?? false))
                <div class="kt-alert kt-alert-danger border border-destructive/40 bg-destructive/10 text-destructive dark:text-red-300" role="alert">
                    <i class="ki-filled ki-information me-2 shrink-0"></i>
                    <span>Selecteer eerst een <strong>tenant</strong> in de tenant-kiezer bovenaan. Zonder tenant kunt u geen voertuig aanmaken.</span>
                </div>
            @endif

            <!-- Voertuiggegevens -->
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">
                        Voertuiggegevens
                    </h3>
                </div>
                <div class="kt-card-content p-0">
                    <input type="hidden" name="company_id" value="{{ old('company_id', $resolvedCompanyId) }}">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                    @error('company_id')
                        <div class="text-xs text-destructive mb-3" data-validation-error="1" data-validation-error-for="company_id">{{ $message }}</div>
                    @enderror
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Voertuigfoto
                            </td>
                            <td class="min-w-48 w-full">
                                <div class="text-xs text-muted-foreground mb-2">Optioneel. JPG, PNG of WebP, max. 5MB.</div>
                                <div id="vehicle-image-client-msg" class="text-xs text-destructive mt-1 mb-2 hidden" role="status" aria-live="polite"></div>
                                <div class="flex flex-col sm:flex-row flex-wrap items-start gap-3 w-full min-w-0">
                                    <div class="shrink-0 flex flex-col items-center w-full sm:w-auto">
                                        <img alt="Voertuig" id="vehicle-image-preview" class="w-full max-w-[200px] max-h-32 object-contain border border-border rounded-lg hidden" src="" data-default-src="">
                                        <button type="button" class="vehicle-image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10 hidden" data-url-input-id="vehicle_image_url" data-preview-id="vehicle-image-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                        </button>
                                    </div>
                                    <div class="vehicle-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30 cursor-pointer hover:border-primary/50 transition-colors w-full max-w-md min-h-[130px]">
                                        <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
                                        <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                                    </div>
                                </div>
                                <input type="file" class="vehicle-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" id="vehicle-image-file">
                                <input type="hidden" name="image_url" id="vehicle_image_url" value="{{ old('image_url') }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Naam *
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="text"
                                       name="name"
                                       class="kt-input @error('name') border-destructive @enderror"
                                       value="{{ old('name') }}"
                                       placeholder="bijv. Auto 1"
                                       required
                                       @error('name') data-server-error="1" @enderror>
                                @error('name')
                                    <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="name">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Type *
                            </td>
                            <td class="min-w-48 w-full">
                                <select name="type" class="kt-input @error('type') border-destructive @enderror" required @error('type') data-server-error="1" @enderror>
                                    @foreach($typeLabels as $value => $label)
                                        <option value="{{ $value }}" {{ old('type', 'car') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="type">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Kenteken *
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="text"
                                       name="license_plate"
                                       class="kt-input @error('license_plate') border-destructive @enderror"
                                       value="{{ old('license_plate') }}"
                                       style="text-transform: uppercase"
                                       maxlength="20"
                                       required
                                       oninput="this.value = this.value.toUpperCase()"
                                       @error('license_plate') data-server-error="1" @enderror>
                                @error('license_plate')
                                    <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="license_plate">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Personenbereik *
                            </td>
                            <td class="min-w-48 w-full">
                                <select name="person_range" class="kt-input @error('person_range') border-destructive @enderror" required @error('person_range') data-server-error="1" @enderror>
                                    @foreach($personRangeLabels as $value => $label)
                                        <option value="{{ $value }}" {{ old('person_range', '1-4') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="text-xs text-muted-foreground mt-1">Bepaalt welke standaardtarievenset wordt gebruikt.</div>
                                @error('person_range')
                                    <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="person_range">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Actief
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="hidden" name="active" value="0">
                                <label class="kt-label flex items-center gap-2 mb-0" for="active">
                                    <input type="checkbox" name="active" id="active" value="1" class="kt-switch kt-switch-sm shrink-0" {{ old('active', true) ? 'checked' : '' }}>
                                    <span class="text-sm text-muted-foreground">Voertuig is actief</span>
                                </label>
                                @error('active')
                                    <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="active">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Foto weergeven
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="hidden" name="show_photo" value="0">
                                <label class="kt-label flex items-center gap-2 mb-0" for="show_photo">
                                    <input type="checkbox" name="show_photo" id="show_photo" value="1" class="kt-switch kt-switch-sm shrink-0" {{ old('show_photo', false) ? 'checked' : '' }}>
                                    <span class="text-sm text-muted-foreground">Toon voertuigfoto in frontend selectie</span>
                                </label>
                                @error('show_photo')
                                    <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="show_photo">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Tarieven (optioneel: leeg = standaardtarieven uit Tarieven-pagina) -->
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">
                        Tarieven
                    </h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0 flex flex-col gap-3">
                    <p class="text-xs text-muted-foreground mb-0">Optioneel. Leeglaten = algemene standaardtarieven worden gebruikt.</p>
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Instaptarief
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="number"
                                       name="base_fare"
                                       class="kt-input @error('base_fare') border-destructive @enderror"
                                       value="{{ old('base_fare') }}"
                                       step="0.01"
                                       min="0"
                                       placeholder="Optioneel"
                                       @error('base_fare') data-server-error="1" @enderror>
                                @error('base_fare')
                                    <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="base_fare">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Wachttarief vooraf p/u
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="number"
                                       name="min_fare"
                                       class="kt-input @error('min_fare') border-destructive @enderror"
                                       value="{{ old('min_fare') }}"
                                       step="0.01"
                                       min="0"
                                       placeholder="Optioneel"
                                       @error('min_fare') data-server-error="1" @enderror>
                                @error('min_fare')
                                    <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="min_fare">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Prijs per km (€)
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="number"
                                       name="price_per_km"
                                       class="kt-input @error('price_per_km') border-destructive @enderror"
                                       value="{{ old('price_per_km') }}"
                                       step="0.01"
                                       min="0"
                                       placeholder="Optioneel"
                                       @error('price_per_km') data-server-error="1" @enderror>
                                @error('price_per_km')
                                    <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="price_per_km">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Prijs per min (€)
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="number"
                                       name="price_per_min"
                                       class="kt-input @error('price_per_min') border-destructive @enderror"
                                       value="{{ old('price_per_min') }}"
                                       step="0.01"
                                       min="0"
                                       placeholder="Optioneel"
                                       @error('price_per_min') data-server-error="1" @enderror>
                                @error('price_per_min')
                                    <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="price_per_min">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Reinigingskosten
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="number"
                                       name="cleaning_costs"
                                       class="kt-input @error('cleaning_costs') border-destructive @enderror"
                                       value="{{ old('cleaning_costs') }}"
                                       step="0.01"
                                       min="0"
                                       placeholder="Optioneel"
                                       @error('cleaning_costs') data-server-error="1" @enderror>
                                @error('cleaning_costs')
                                    <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="cleaning_costs">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Overig -->
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">
                        Overig
                    </h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Notities
                            </td>
                            <td class="min-w-48 w-full">
                                <textarea name="notes" class="kt-input @error('notes') border-destructive @enderror" rows="3" @error('notes') data-server-error="1" @enderror>{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="notes">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 w-full min-w-0">
                <a href="{{ route('admin.taxi.vehicles.index') }}" class="kt-btn kt-btn-outline">
                    <svg class="w-4 h-4 me-2 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary" @if(auth()->user()->hasRole('super-admin') && ($superAdminNeedsTenant ?? false)) disabled aria-disabled="true" @endif>
                    <svg class="w-4 h-4 me-2 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    Voertuig opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script>
(function() {
    var uploadUrl = {!! json_encode(route('admin.taxi.vehicles.upload-image')) !!};
    function showVehicleImageMsg(msg) {
        var el = document.getElementById('vehicle-image-client-msg');
        if (!el) return;
        el.textContent = msg || '';
        el.classList.toggle('hidden', !msg);
        el.classList.toggle('text-destructive', !!msg);
    }
    function storageUrlToFileUrl(url) {
        if (!url || typeof url !== 'string') return url;
        var u = url.trim();
        var path = null;
        if (u.indexOf('/storage/') === 0) {
            path = u.replace(/^\/storage\//, '').split(/[#?]/)[0].replace(/\//g, '--');
        } else if (/^https?:\/\/[^/]+\/storage\//.test(u)) {
            path = u.replace(/^https?:\/\/[^/]+\/storage\//, '').split(/[#?]/)[0].replace(/\//g, '--');
        }
        if (path) return (window.location.origin || '') + '/file/' + path;
        return u;
    }
    var area = document.querySelector('.vehicle-image-upload-area');
    var fileInput = document.getElementById('vehicle-image-file');
    var urlInput = document.getElementById('vehicle_image_url');
    var preview = document.getElementById('vehicle-image-preview');
    var removeBtn = document.querySelector('.vehicle-image-remove-btn');
    if (!area || !fileInput || !urlInput) return;
    function handleFile(file) {
        showVehicleImageMsg('');
        if (!file || !file.type || !file.type.match(/^image\/(jpeg|png|gif|webp)$/i)) { showVehicleImageMsg('Alleen JPG, PNG, GIF of WebP (max. 5MB).'); return; }
        if (file.size > 5 * 1024 * 1024) { showVehicleImageMsg('Bestand mag maximaal 5MB zijn.'); return; }
        var fd = new FormData();
        fd.append('image', file);
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        fetch(uploadUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.ok ? r.json() : r.json().then(function(d) { throw new Error(d.message || 'Upload mislukt'); }); })
            .then(function(d) {
                if (d.success && d.url) {
                    showVehicleImageMsg('');
                    urlInput.value = d.url;
                    if (preview) { preview.src = storageUrlToFileUrl(d.url); preview.classList.remove('hidden'); }
                    if (removeBtn) removeBtn.classList.remove('hidden');
                }
            })
            .catch(function(err) { showVehicleImageMsg(err.message || 'Upload mislukt.'); });
        fileInput.value = '';
    }
    if (typeof window.bindAdminUploadAreaClick === 'function') {
        window.bindAdminUploadAreaClick(area, fileInput, { clearInputFirst: false });
    } else {
        area.addEventListener('click', function(e) { e.preventDefault(); fileInput.click(); });
    }
    area.addEventListener('dragover', function(e) { e.preventDefault(); e.stopPropagation(); area.classList.add('border-primary'); });
    area.addEventListener('dragleave', function(e) { e.preventDefault(); area.classList.remove('border-primary'); });
    area.addEventListener('drop', function(e) { e.preventDefault(); area.classList.remove('border-primary'); if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]); });
    fileInput.addEventListener('change', function() { if (this.files && this.files.length) handleFile(this.files[0]); });
    if (removeBtn && urlInput && preview) {
        removeBtn.addEventListener('click', function() {
            showVehicleImageMsg('');
            urlInput.value = '';
            preview.src = '';
            preview.classList.add('hidden');
            removeBtn.classList.add('hidden');
        });
    }
    if (urlInput.value && preview) { preview.src = storageUrlToFileUrl(urlInput.value); preview.classList.remove('hidden'); if (removeBtn) removeBtn.classList.remove('hidden'); }
})();
</script>
@endpush
@endsection
