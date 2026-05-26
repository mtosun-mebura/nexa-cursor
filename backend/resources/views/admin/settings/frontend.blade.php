@extends('admin.layouts.app')

@section('title', 'Front-end configuraties')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Front-end configuraties
        </h1>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="success-alert" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-information me-2"></i>
            <strong>Er zijn validatiefouten opgetreden:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('admin.settings.partials.tenant-scope-notice')

    <div class="grid gap-5 lg:gap-7.5">
        <!-- Coming Soon pagina -->
        <div class="kt-card min-w-full">
            <div class="kt-card-header flex flex-wrap items-start justify-between gap-3 py-5">
                <div>
                    <h3 class="kt-card-title">
                        <i class="ki-filled ki-calendar-tick me-2"></i> Coming Soon-pagina
                    </h3>
                    <p class="text-sm text-muted-foreground mt-1">Deze pagina wordt getoond op de homepage zolang er geen actieve module is. Logo en favicon komen uit Algemene configuraties.</p>
                </div>
                <a href="{{ route('admin.settings.frontend.preview') }}" target="_blank" rel="noopener noreferrer" class="kt-btn kt-btn-outline shrink-0">
                    <i class="ki-filled ki-eye me-2"></i> Voorbeeld pagina
                </a>
            </div>
            <div class="px-5 lg:px-7.5 pb-5 border-b border-border">
                <h4 class="pt-1 text-sm font-medium text-secondary-foreground mb-1">Centrale afbeelding</h4>
                <p class="text-sm text-muted-foreground mb-4">Optioneel: een afbeelding in het midden van het scherm, boven de titel en tekst op de Coming Soon-pagina.</p>
                <div class="flex flex-wrap sm:flex-nowrap gap-5 lg:gap-7.5 max-w-3xl w-full items-start">
                    <div id="coming-soon-image-preview-wrap" class="flex flex-col items-center {{ !empty($comingSoonImageUrl) ? '' : 'hidden' }}">
                        <img alt="Coming Soon afbeelding preview" class="h-[200px] w-auto max-w-full object-contain rounded-lg border border-input shrink-0 cursor-pointer hover:opacity-90 transition-opacity" src="{{ !empty($comingSoonImageUrl) ? route('admin.settings.coming-soon-image').'?t='.time() : '' }}" id="coming-soon-image-preview" title="Klik om groot te bekijken"/>
                        <button type="button" class="image-remove-btn kt-btn kt-btn-sm kt-btn-outline kt-btn-icon text-destructive mt-2" id="coming-soon-image-remove-btn" data-url-input-id="hero-coming-soon-center_image-url" data-preview-id="coming-soon-image-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen">
                            <i class="ki-filled ki-trash text-lg"></i>
                        </button>
                    </div>
                    <div class="flex flex-col flex-1 min-w-[180px]">
                        <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30 cursor-pointer hover:border-primary transition-colors w-full max-w-md" data-section-key="coming-soon" data-field="center_image" id="coming-soon-image-upload-area" role="button" tabindex="0" title="Klik of sleep een afbeelding">
                            <div class="flex flex-col place-items-center place-content-center text-center rounded-xl w-full pointer-events-none">
                                <i class="ki-filled ki-picture text-2xl text-primary mb-1"></i>
                                <span class="text-mono text-xs font-medium text-primary">Klik of sleep afbeelding</span>
                                <span class="text-xs text-secondary-foreground mt-0.5">JPG, PNG, WebP (max. 5MB)</span>
                            </div>
                        </div>
                        <input type="file" id="hero-coming-soon-center_image" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp" aria-label="Coming Soon afbeelding kiezen">
                        <input type="hidden" id="hero-coming-soon-center_image-url" value="{{ !empty($comingSoonImageUrl) ? route('admin.settings.coming-soon-image') : '' }}">
                        <p id="coming-soon-image-error" class="text-sm text-destructive mt-2 hidden" role="alert"></p>
                    </div>
                </div>
                <div id="coming-soon-image-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 backdrop-blur-sm" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Afbeelding groot">
                    <div class="relative max-h-[90vh] max-w-[90vw] p-4">
                        <button type="button" id="coming-soon-image-modal-close" class="absolute -top-2 -right-2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-background border border-input text-foreground shadow-md hover:bg-muted" aria-label="Sluiten">
                            <i class="ki-filled ki-cross text-xl"></i>
                        </button>
                        <img id="coming-soon-image-modal-img" src="" alt="Grote weergave" class="max-h-[85vh] w-auto max-w-full object-contain rounded-lg shadow-xl">
                    </div>
                </div>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <form method="POST" action="{{ route('admin.settings.coming-soon.update') }}" id="coming-soon-form">
                    @csrf
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Titel *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           class="kt-input @error('coming_soon_title') border-destructive @enderror"
                                           id="coming_soon_title"
                                           name="coming_soon_title"
                                           value="{{ old('coming_soon_title', $comingSoonSettings['coming_soon_title'] ?? '') }}"
                                           placeholder="We zijn bijna live"
                                           required>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Hoofdtitel op de coming soon-pagina</div>
                                @error('coming_soon_title')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Tekst *</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <textarea rows="4"
                                              class="kt-input pt-1 @error('coming_soon_text') border-destructive @enderror"
                                              id="coming_soon_text"
                                              name="coming_soon_text"
                                              placeholder="Onze website wordt op dit moment voor u klaargemaakt..."
                                              required>{{ old('coming_soon_text', $comingSoonSettings['coming_soon_text'] ?? '') }}</textarea>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Korte uitleg onder de titel</div>
                                @error('coming_soon_text')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Secundaire tekst</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           class="kt-input @error('coming_soon_secondary_text') border-destructive @enderror"
                                           id="coming_soon_secondary_text"
                                           name="coming_soon_secondary_text"
                                           value="{{ old('coming_soon_secondary_text', $comingSoonSettings['coming_soon_secondary_text'] ?? '') }}"
                                           placeholder="Heeft u vragen? Neem gerust contact met ons op.">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Optionele regel onder de hoofdtekst</div>
                                @error('coming_soon_secondary_text')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">E-mail tonen</td>
                            <td class="min-w-48 w-full">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                           name="coming_soon_show_email"
                                           value="1"
                                           {{ old('coming_soon_show_email', $comingSoonSettings['coming_soon_show_email'] ?? '1') === '1' ? 'checked' : '' }}
                                           class="kt-checkbox rounded border-input">
                                    <span class="text-sm">Toon contact-e-mail op de coming soon-pagina</span>
                                </label>
                                <div class="text-xs text-muted-foreground mt-1">Als uitgeschakeld wordt het e-mailadres niet getoond</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Contact e-mailadres</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="email"
                                           class="kt-input @error('coming_soon_contact_email') border-destructive @enderror"
                                           id="coming_soon_contact_email"
                                           name="coming_soon_contact_email"
                                           value="{{ old('coming_soon_contact_email', $comingSoonSettings['coming_soon_contact_email'] ?? '') }}"
                                           placeholder="info@voorbeeld.nl">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Wordt alleen getoond als "E-mail tonen" aanstaat</div>
                                @error('coming_soon_contact_email')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Label bij e-mail</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           class="kt-input @error('coming_soon_contact_label') border-destructive @enderror"
                                           id="coming_soon_contact_label"
                                           name="coming_soon_contact_label"
                                           value="{{ old('coming_soon_contact_label', $comingSoonSettings['coming_soon_contact_label'] ?? 'E-mail') }}"
                                           placeholder="E-mail">
                                </div>
                                @error('coming_soon_contact_label')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Footertekst</td>
                            <td class="min-w-48 w-full">
                                <div class="relative">
                                    <input type="text"
                                           class="kt-input @error('coming_soon_footer_text') border-destructive @enderror"
                                           id="coming_soon_footer_text"
                                           name="coming_soon_footer_text"
                                           value="{{ old('coming_soon_footer_text', $comingSoonSettings['coming_soon_footer_text'] ?? '') }}"
                                           placeholder="© {year} {site}. Binnenkort beschikbaar.">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">Gebruik <code>{year}</code> voor het jaar en <code>{site}</code> voor de sitenaam.</div>
                                @error('coming_soon_footer_text')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    <div class="kt-card-footer flex justify-end items-center gap-5 pt-5 border-t border-border">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check me-2"></i> Coming soon-instellingen opslaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.transition = 'opacity 0.3s ease-out';
            successAlert.style.opacity = '0';
            setTimeout(function() { successAlert.remove(); }, 300);
        }, 5000);
    }

    const csImgInput = document.getElementById('hero-coming-soon-center_image');
    const csImgArea = document.getElementById('coming-soon-image-upload-area');
    const csImgPreview = document.getElementById('coming-soon-image-preview');
    const csImgPreviewWrap = document.getElementById('coming-soon-image-preview-wrap');
    const csImgRemoveBtn = document.getElementById('coming-soon-image-remove-btn');
    const csImgErrorEl = document.getElementById('coming-soon-image-error');
    const csImgUrlHidden = document.getElementById('hero-coming-soon-center_image-url');
    const csImgModal = document.getElementById('coming-soon-image-modal');
    const csImgModalImg = document.getElementById('coming-soon-image-modal-img');
    const csImgModalClose = document.getElementById('coming-soon-image-modal-close');

    function showCsImgError(msg) {
        if (csImgErrorEl) {
            csImgErrorEl.textContent = msg || 'Upload mislukt.';
            csImgErrorEl.classList.remove('hidden');
        }
        if (typeof alert !== 'undefined') alert(msg || 'Upload mislukt.');
    }
    function clearCsImgError() {
        if (csImgErrorEl) {
            csImgErrorEl.textContent = '';
            csImgErrorEl.classList.add('hidden');
        }
    }

    function handleComingSoonImageFile(file) {
        if (!csImgInput) return;
        clearCsImgError();
        var allowed = ['image/svg+xml', 'image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
        if (!allowed.includes(file.type)) {
            showCsImgError('Ongeldig bestandstype. Alleen SVG, PNG, JPG, GIF en WebP zijn toegestaan.');
            csImgInput.value = '';
            return;
        }
        var maxBytes = 5 * 1024 * 1024;
        if (file.size > maxBytes) {
            showCsImgError('Het bestand is te groot. Maximaal 5MB toegestaan. Uw bestand is ' + Math.round(file.size / 1024) + ' KB.');
            csImgInput.value = '';
            return;
        }
        var formData = new FormData();
        formData.append('coming_soon_image', file);
        var csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) formData.append('_token', csrf.getAttribute('content'));
        fetch('{{ route("admin.settings.upload-coming-soon-image") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) {
            var status = r.status;
            return r.text().then(function(text) {
                var data;
                try { data = text ? JSON.parse(text) : {}; } catch (e) {
                    if (status === 413) throw new Error('Het bestand is te groot. Maximaal 5MB toegestaan.');
                    throw new Error('Upload mislukt. De server gaf een onverwachte reactie (status ' + status + ').');
                }
                if (r.ok) return data;
                var msg = (data.errors && data.errors.coming_soon_image && data.errors.coming_soon_image[0])
                    ? data.errors.coming_soon_image[0]
                    : (data.message || 'Upload mislukt. Controleer het bestand (max. 5MB, JPEG/PNG/GIF/SVG/WebP).');
                throw new Error(msg);
            });
        })
        .then(function(data) {
            if (data.success && csImgPreview && csImgPreviewWrap) {
                var url = data.image_url || '';
                csImgPreview.src = url;
                csImgPreviewWrap.classList.remove('hidden');
                if (csImgUrlHidden && url) csImgUrlHidden.value = url.split('?')[0];
                clearCsImgError();
            } else {
                showCsImgError(data.message || 'Upload mislukt.');
            }
        })
        .catch(function(err) {
            showCsImgError(err && err.message ? err.message : 'Upload mislukt. Controleer het bestand (max. 5MB) of probeer het later opnieuw.');
        });
        csImgInput.value = '';
    }

    if (csImgInput && csImgArea && typeof window.bindAdminUploadAreaClick === 'function') {
        window.bindAdminUploadAreaClick(csImgArea, csImgInput, { clearInputFirst: false });
    }

    if (csImgInput && csImgArea) {
        csImgArea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (typeof window.openAdminFilePicker === 'function') {
                    window.openAdminFilePicker(csImgInput, { clearInputFirst: false });
                } else {
                    csImgInput.click();
                }
            }
        });
        csImgArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            csImgArea.classList.add('border-primary');
        });
        csImgArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            csImgArea.classList.remove('border-primary');
        });
        csImgArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            csImgArea.classList.remove('border-primary');
            var files = e.dataTransfer && e.dataTransfer.files;
            if (files && files.length > 0) handleComingSoonImageFile(files[0]);
        });
        csImgInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) handleComingSoonImageFile(this.files[0]);
        });
    }

    function closeCsImgModal() {
        if (csImgModal) {
            csImgModal.classList.add('hidden');
            csImgModal.classList.remove('flex');
            document.body.style.overflow = '';
        }
    }
    if (csImgPreview && csImgModal && csImgModalImg) {
        csImgPreview.addEventListener('click', function() {
            var src = this.src || this.getAttribute('src');
            if (!src) return;
            csImgModalImg.src = src;
            csImgModal.classList.remove('hidden');
            csImgModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        });
    }
    if (csImgModalClose) csImgModalClose.addEventListener('click', closeCsImgModal);
    if (csImgModal) {
        csImgModal.addEventListener('click', function(e) {
            if (e.target === csImgModal) closeCsImgModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && csImgModal && !csImgModal.classList.contains('hidden')) closeCsImgModal();
        });
    }

    if (csImgRemoveBtn && csImgPreviewWrap) {
        csImgRemoveBtn.addEventListener('click', function() {
            var token = document.querySelector('meta[name="csrf-token"]');
            fetch('{{ route("admin.settings.remove-coming-soon-image") }}', {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    csImgPreviewWrap.classList.add('hidden');
                    if (csImgPreview) csImgPreview.src = '';
                    if (csImgUrlHidden) csImgUrlHidden.value = '';
                }
            });
        });
    }
});
</script>
@endsection
