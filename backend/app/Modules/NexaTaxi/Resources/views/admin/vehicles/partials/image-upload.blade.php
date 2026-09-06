@php
    $placeholderUrl = asset('modules/nexa-taxi/vehicle-placeholder.png');
    $imgUrl = trim((string) ($imgUrl ?? ''));
    $imgDisplayUrl = $imgUrl !== ''
        ? (app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($imgUrl) ?: $imgUrl)
        : $placeholderUrl;
    $hasCustomImage = $imgUrl !== '';
@endphp
<div class="text-xs text-muted-foreground mb-2">Optioneel. JPG, PNG of WebP, max. 5MB.</div>
<div id="vehicle-image-client-msg" class="text-xs text-destructive mt-1 mb-2 hidden" role="status" aria-live="polite"></div>
<div class="vehicle-image-upload-area relative flex flex-col items-center justify-center gap-1.5 p-2.5 border border-input rounded-xl border-dashed bg-muted/30 cursor-pointer hover:border-primary/50 transition-colors w-full max-w-md" data-section-key="vehicle" data-field="image">
    <div class="inline-flex max-w-full overflow-hidden rounded-2xl leading-none">
        <img
            alt="Voertuig"
            id="vehicle-image-preview"
            class="block max-h-[200px] max-w-full h-auto w-auto rounded-2xl"
            src="{{ $imgDisplayUrl }}"
            data-default-src="{{ $placeholderUrl }}"
            width="360"
            height="200"
        >
    </div>
    <span class="text-xs text-muted-foreground text-center">Klik of sleep afbeelding</span>
    <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
    <button type="button" class="vehicle-image-remove-btn image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive shadow hover:bg-destructive/10 {{ $hasCustomImage ? '' : 'hidden' }}" data-url-input-id="vehicle_image_url" data-preview-id="vehicle-image-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen">
        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
    </button>
</div>
<input type="file" class="vehicle-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" id="vehicle-image-file">
<input type="hidden" name="image_url" id="vehicle_image_url" value="{{ $imgUrl }}">

@once
@push('scripts')
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
    function placeholderSrc() {
        return (preview && preview.getAttribute('data-default-src')) || '';
    }
    function showPreview(src, custom) {
        if (!preview) return;
        preview.src = src || placeholderSrc();
        preview.classList.remove('hidden');
        if (removeBtn) removeBtn.classList.toggle('hidden', !custom);
    }
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
                    showPreview(storageUrlToFileUrl(d.url), true);
                }
            })
            .catch(function(err) { showVehicleImageMsg(err.message || 'Upload mislukt.'); });
        fileInput.value = '';
    }
    if (typeof window.bindAdminUploadAreaClick === 'function') {
        window.bindAdminUploadAreaClick(area, fileInput, { clearInputFirst: false });
    } else {
        area.addEventListener('click', function(e) {
            if (e.target.closest('.vehicle-image-remove-btn')) return;
            e.preventDefault();
            fileInput.click();
        });
    }
    area.addEventListener('dragover', function(e) { e.preventDefault(); e.stopPropagation(); area.classList.add('border-primary'); });
    area.addEventListener('dragleave', function(e) { e.preventDefault(); area.classList.remove('border-primary'); });
    area.addEventListener('drop', function(e) { e.preventDefault(); area.classList.remove('border-primary'); if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]); });
    fileInput.addEventListener('change', function() { if (this.files && this.files.length) handleFile(this.files[0]); });
    if (removeBtn && urlInput && preview) {
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showVehicleImageMsg('');
            urlInput.value = '';
            showPreview(placeholderSrc(), false);
        });
    }
})();
</script>
@endpush
@endonce
