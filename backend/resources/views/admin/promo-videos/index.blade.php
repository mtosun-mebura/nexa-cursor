@extends('admin.layouts.app')

@section('title', __('Promo video’s'))

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-col items-start gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">{{ __('Promo video’s') }}</h1>
        <p class="text-sm text-muted-foreground mb-0">
            {{ __('Maak Remotion-promotievideo’s voor Nexa Suite / NexaTaxi. Pas merkteksten en kleuren aan, bekijk de preview en exporteer een MP4.') }}
        </p>
    </div>

    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5">{{ session('error') }}</div>
    @endif

    @if(! $projectInstalled)
        <div class="kt-alert kt-alert-warning mb-5">
            {{ __('Remotion-pakket is nog niet geïnstalleerd.') }}
            <code class="ms-1">cd promo-videos && npm install</code>
        </div>
    @endif

    @if(! $playerBuilt)
        <div class="kt-alert kt-alert-warning mb-5">
            {{ __('Player-build ontbreekt. Preview in deze pagina werkt na:') }}
            <code class="ms-1">cd promo-videos && npm run player:build</code>
            — {{ __('of open Remotion Studio:') }}
            <code class="ms-1">{{ $studioHint }}</code>
        </div>
    @endif

    <form action="{{ route('admin.promo-videos.preview') }}" method="POST" data-validate="true" novalidate id="promo-video-form" class="grid gap-5 lg:gap-7.5">
        @csrf

        <div class="grid gap-5 lg:grid-cols-2 lg:gap-7.5 items-start">
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5">
                    <h3 class="kt-card-title mb-0">{{ __('Video-instellingen') }}</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Composition') }}</td>
                                <td class="min-w-48 w-full">
                                    <select name="composition" id="promo-composition" class="kt-select admin-field-fit @error('composition') border-destructive @enderror" data-kt-select="true" required>
                                        @foreach($compositions as $id => $meta)
                                            <option value="{{ $id }}" @selected(old('composition', $composition) === $id)>
                                                {{ __($meta['label']) }} ({{ $meta['width'] }}×{{ $meta['height'] }}, {{ $meta['duration_seconds'] }}s)
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="field-feedback text-sm mt-1" data-field="composition" aria-live="polite"></div>
                                    @error('composition')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="composition" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Bedrijfsnaam') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="companyName" id="promo-companyName" value="{{ old('companyName', $props['companyName']) }}" class="kt-input @error('companyName') border-destructive @enderror" required maxlength="120">
                                    <div class="field-feedback text-sm mt-1" data-field="companyName" aria-live="polite"></div>
                                    @error('companyName')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="companyName" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Product') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="productName" id="promo-productName" value="{{ old('productName', $props['productName']) }}" class="kt-input @error('productName') border-destructive @enderror" required maxlength="120">
                                    <div class="field-feedback text-sm mt-1" data-field="productName" aria-live="polite"></div>
                                    @error('productName')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="productName" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Tagline') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="tagline" id="promo-tagline" value="{{ old('tagline', $props['tagline']) }}" class="kt-input @error('tagline') border-destructive @enderror" required maxlength="240">
                                    <div class="field-feedback text-sm mt-1" data-field="tagline" aria-live="polite"></div>
                                    @error('tagline')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="tagline" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Subtitel') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="subtitle" id="promo-subtitle" value="{{ old('subtitle', $props['subtitle']) }}" class="kt-input @error('subtitle') border-destructive @enderror" required maxlength="240">
                                    <div class="field-feedback text-sm mt-1" data-field="subtitle" aria-live="polite"></div>
                                    @error('subtitle')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="subtitle" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('CTA-tekst') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="ctaText" id="promo-ctaText" value="{{ old('ctaText', $props['ctaText']) }}" class="kt-input @error('ctaText') border-destructive @enderror" required maxlength="120">
                                    <div class="field-feedback text-sm mt-1" data-field="ctaText" aria-live="polite"></div>
                                    @error('ctaText')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="ctaText" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Website') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="websiteUrl" id="promo-websiteUrl" value="{{ old('websiteUrl', $props['websiteUrl']) }}" class="kt-input @error('websiteUrl') border-destructive @enderror" required maxlength="120">
                                    <div class="field-feedback text-sm mt-1" data-field="websiteUrl" aria-live="polite"></div>
                                    @error('websiteUrl')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="websiteUrl" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Primaire kleur') }}</td>
                                <td class="min-w-48 w-full">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input type="color" id="promo-primaryColor-picker" value="{{ old('primaryColor', $props['primaryColor']) }}" class="h-10 w-14 rounded border border-input bg-transparent cursor-pointer" aria-label="{{ __('Primaire kleur') }}">
                                        <input type="text" name="primaryColor" id="promo-primaryColor" value="{{ old('primaryColor', $props['primaryColor']) }}" class="kt-input w-32 @error('primaryColor') border-destructive @enderror" required pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                    <div class="field-feedback text-sm mt-1" data-field="primaryColor" aria-live="polite"></div>
                                    @error('primaryColor')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="primaryColor" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Accentkleur') }}</td>
                                <td class="min-w-48 w-full">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input type="color" id="promo-accentColor-picker" value="{{ old('accentColor', $props['accentColor']) }}" class="h-10 w-14 rounded border border-input bg-transparent cursor-pointer" aria-label="{{ __('Accentkleur') }}">
                                        <input type="text" name="accentColor" id="promo-accentColor" value="{{ old('accentColor', $props['accentColor']) }}" class="kt-input w-32 @error('accentColor') border-destructive @enderror" required pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                    <div class="field-feedback text-sm mt-1" data-field="accentColor" aria-live="polite"></div>
                                    @error('accentColor')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="accentColor" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Achtergrond') }}</td>
                                <td class="min-w-48 w-full">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input type="color" id="promo-backgroundColor-picker" value="{{ old('backgroundColor', $props['backgroundColor']) }}" class="h-10 w-14 rounded border border-input bg-transparent cursor-pointer" aria-label="{{ __('Achtergrond') }}">
                                        <input type="text" name="backgroundColor" id="promo-backgroundColor" value="{{ old('backgroundColor', $props['backgroundColor']) }}" class="kt-input w-32 @error('backgroundColor') border-destructive @enderror" required pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                    <div class="field-feedback text-sm mt-1" data-field="backgroundColor" aria-live="polite"></div>
                                    @error('backgroundColor')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="backgroundColor" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('Logo') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="hidden" name="logoUrl" id="hero-promo-logoUrl" value="{{ old('logoUrl', $props['logoUrl']) }}">
                                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 border border-input rounded-xl border-dashed bg-muted/30 min-h-[130px] w-full max-w-full cursor-pointer hover:border-primary transition-colors"
                                         data-section-key="promo"
                                         data-field="logoUrl"
                                         role="button"
                                         tabindex="0"
                                         aria-label="{{ __('Logo uploaden') }}">
                                        <span class="text-xs text-muted-foreground text-center">{{ __('Klik of sleep afbeelding') }}</span>
                                        <span class="text-xs text-muted-foreground text-center">{{ __('JPG, PNG, WebP (max. 5MB)') }}</span>
                                    </div>
                                    <input type="file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/webp" data-section-key="promo" data-field="logoUrl">
                                    <div class="mt-3 flex flex-wrap items-center gap-3">
                                        <img id="promo-logo-preview"
                                             src="{{ old('logoUrl', $props['logoUrl']) && str_starts_with(old('logoUrl', $props['logoUrl']), 'http') ? old('logoUrl', $props['logoUrl']) : (str_starts_with(old('logoUrl', $props['logoUrl']), '/') ? old('logoUrl', $props['logoUrl']) : asset('promo-videos-player/'.ltrim(old('logoUrl', $props['logoUrl']), '/'))) }}"
                                             alt="{{ __('Logo preview') }}"
                                             class="h-14 w-auto max-w-[180px] object-contain rounded-lg border border-border bg-muted/20 p-1"
                                             onerror="this.style.display='none'">
                                        <button type="button"
                                                class="image-remove-btn kt-btn kt-btn-sm kt-btn-outline"
                                                data-url-input-id="hero-promo-logoUrl"
                                                data-preview-id="promo-logo-preview">
                                            {{ __('Logo wissen') }}
                                        </button>
                                    </div>
                                    <p class="text-xs text-muted-foreground mt-2 mb-0">{{ __('Leeg = standaard NexaTaxi-logo uit Remotion-assets.') }}</p>
                                    <div class="field-feedback text-sm mt-1" data-field="logoUrl" aria-live="polite"></div>
                                    @error('logoUrl')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="logoUrl" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">{{ __('App-schermen') }}</td>
                                <td class="min-w-48 w-full">
                                    <input type="hidden" name="showAppScreens" value="0">
                                    <label class="kt-label flex items-center gap-2 mb-0" for="promo-showAppScreens">
                                        <input type="checkbox" name="showAppScreens" id="promo-showAppScreens" value="1" class="kt-switch kt-switch-sm shrink-0" @checked(old('showAppScreens', $props['showAppScreens']))>
                                        <span class="text-sm text-muted-foreground">{{ __('Toon telefoonframes met app-visuals') }}</span>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                    <h3 class="kt-card-title mb-0">{{ __('Preview') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="promo-live-preview-btn" class="kt-btn kt-btn-outline kt-btn-sm">
                            <i class="ki-filled ki-eye me-1"></i>
                            {{ __('Live bijwerken') }}
                        </button>
                        @if($playerBuilt)
                            <a href="{{ $playerUrl }}" target="_blank" rel="noopener" class="kt-btn kt-btn-outline kt-btn-sm">
                                <i class="ki-filled ki-exit-right-corner me-1"></i>
                                {{ __('Open player') }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="kt-card-content p-5">
                    @if($playerBuilt)
                        <div class="rounded-xl overflow-hidden border border-border bg-[#0b1220] aspect-video w-full">
                            <iframe
                                id="promo-player-iframe"
                                title="{{ __('Promo video preview') }}"
                                src="{{ $playerUrl }}"
                                class="w-full h-full min-h-[280px] border-0"
                                allow="autoplay"
                            ></iframe>
                        </div>
                        <p class="text-xs text-muted-foreground mt-3 mb-0">
                            {{ __('De player draait in de pagina. Wijzig velden en klik “Live bijwerken”, of sla op voor een vaste preview-URL.') }}
                        </p>
                    @else
                        <div class="rounded-xl border border-dashed border-input bg-muted/30 p-8 text-center min-h-[280px] flex flex-col items-center justify-center gap-3">
                            <i class="ki-filled ki-youtube text-3xl text-muted-foreground"></i>
                            <p class="text-sm text-muted-foreground mb-0">{{ __('Bouw eerst de player of gebruik Remotion Studio.') }}</p>
                            <code class="text-xs">cd promo-videos && npm run player:build</code>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-0 w-full min-w-0">
            <button type="submit" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-check me-1"></i>
                {{ __('Preview opslaan') }}
            </button>
            <button type="submit"
                    formaction="{{ route('admin.promo-videos.render') }}"
                    class="kt-btn kt-btn-primary"
                    @disabled(! $projectInstalled)
                    onclick="return confirm(@json(__('Render kan 1–5 minuten duren. Doorgaan?')))">
                <i class="ki-filled ki-cloud-download me-1"></i>
                {{ __('Render MP4') }}
            </button>
        </div>
    </form>

    <div class="kt-card w-full min-w-0 mt-5 lg:mt-7.5">
        <div class="kt-card-header px-5 py-5">
            <h3 class="kt-card-title mb-0">{{ __('Gerenderde video’s') }}</h3>
        </div>
        <div class="kt-card-content p-5">
            @if(count($rendered) === 0)
                <p class="text-sm text-muted-foreground mb-0">{{ __('Nog geen exports. Render een video of gebruik de CLI.') }}</p>
            @else
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full" id="promo-renders-table">
                        <thead>
                            <tr>
                                <th data-label="Bestand">{{ __('Bestand') }}</th>
                                <th data-label="Gewijzigd">{{ __('Gewijzigd') }}</th>
                                <th data-label="Grootte">{{ __('Grootte') }}</th>
                                <th data-label="Acties" class="text-center">{{ __('Acties') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rendered as $video)
                                <tr>
                                    <td>{{ $video['name'] }}</td>
                                    <td>{{ $video['modified_at'] }}</td>
                                    <td>{{ number_format($video['size'] / 1048576, 1) }} MB</td>
                                    <td class="text-center">
                                        <a href="{{ $video['url'] }}" class="kt-btn kt-btn-sm kt-btn-outline" target="_blank" rel="noopener">
                                            {{ __('Download') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="mt-5 pt-5 border-t border-border">
                <h4 class="text-sm font-medium text-foreground mb-2">{{ __('CLI / Studio') }}</h4>
                <ul class="text-sm text-muted-foreground space-y-1 mb-0 ps-4 list-disc">
                    <li><code>cd promo-videos && npm run studio</code> — {{ __('Remotion Studio met live hot-reload') }}</li>
                    <li><code>cd promo-videos && npm run render:promo</code> — {{ __('45s product promo') }}</li>
                    <li><code>php artisan promo-videos:render --composition=NexaAppTeaser</code> — {{ __('15s verticale teaser') }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script>
(function () {
    const form = document.getElementById('promo-video-form');
    const iframe = document.getElementById('promo-player-iframe');
    const liveBtn = document.getElementById('promo-live-preview-btn');
    const uploadUrl = @json(route('admin.promo-videos.upload-logo'));
    const csrf = @json(csrf_token());

    function syncColor(textId, pickerId) {
        const text = document.getElementById(textId);
        const picker = document.getElementById(pickerId);
        if (!text || !picker) return;
        picker.addEventListener('input', () => { text.value = picker.value; });
        text.addEventListener('input', () => {
            if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) picker.value = text.value;
        });
    }
    syncColor('promo-primaryColor', 'promo-primaryColor-picker');
    syncColor('promo-accentColor', 'promo-accentColor-picker');
    syncColor('promo-backgroundColor', 'promo-backgroundColor-picker');

    function collectProps() {
        const fd = new FormData(form);
        return {
            companyName: fd.get('companyName') || '',
            productName: fd.get('productName') || '',
            tagline: fd.get('tagline') || '',
            subtitle: fd.get('subtitle') || '',
            primaryColor: fd.get('primaryColor') || '',
            accentColor: fd.get('accentColor') || '',
            backgroundColor: fd.get('backgroundColor') || '',
            ctaText: fd.get('ctaText') || '',
            websiteUrl: fd.get('websiteUrl') || '',
            logoUrl: fd.get('logoUrl') || 'images/nexa-taxi-logo.png',
            showAppScreens: fd.get('showAppScreens') === '1',
        };
    }

    function compositionKey() {
        const v = document.getElementById('promo-composition')?.value || 'NexaProductPromo';
        return v === 'NexaAppTeaser' ? 'teaser' : 'promo';
    }

    function pushToPlayer() {
        if (!iframe || !iframe.contentWindow) return;
        iframe.contentWindow.postMessage({
            type: 'nexa-promo-props',
            composition: compositionKey(),
            props: collectProps(),
        }, '*');
    }

    function rebuildIframeSrc() {
        if (!iframe) return;
        const props = collectProps();
        const q = new URLSearchParams({
            composition: compositionKey(),
            companyName: props.companyName,
            productName: props.productName,
            tagline: props.tagline,
            subtitle: props.subtitle,
            primaryColor: props.primaryColor,
            accentColor: props.accentColor,
            backgroundColor: props.backgroundColor,
            ctaText: props.ctaText,
            websiteUrl: props.websiteUrl,
            logoUrl: props.logoUrl,
            showAppScreens: props.showAppScreens ? '1' : '0',
        });
        const base = iframe.src.split('?')[0];
        iframe.src = base + '?' + q.toString();
    }

    liveBtn?.addEventListener('click', () => {
        pushToPlayer();
        // Fallback when player was not yet ready for postMessage
        setTimeout(rebuildIframeSrc, 50);
    });

    // Logo upload (foto-upload patroon → eigen endpoint)
    const area = form?.querySelector('.hero-image-upload-area');
    const fileInput = form?.querySelector('.hero-image-file-input');
    const urlInput = document.getElementById('hero-promo-logoUrl');
    const preview = document.getElementById('promo-logo-preview');

    async function uploadLogo(file) {
        if (!file) return;
        const body = new FormData();
        body.append('logo', file);
        const res = await fetch(uploadUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            alert(data.message || @json(__('Upload mislukt.')));
            return;
        }
        if (urlInput) urlInput.value = data.url;
        if (preview) {
            preview.src = data.url;
            preview.style.display = '';
        }
        pushToPlayer();
    }

    area?.addEventListener('click', () => fileInput?.click());
    area?.addEventListener('dragover', (e) => { e.preventDefault(); area.classList.add('border-primary'); });
    area?.addEventListener('dragleave', () => area.classList.remove('border-primary'));
    area?.addEventListener('drop', (e) => {
        e.preventDefault();
        area.classList.remove('border-primary');
        const file = e.dataTransfer?.files?.[0];
        if (file) uploadLogo(file);
    });
    fileInput?.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        if (file) uploadLogo(file);
    });

    document.querySelectorAll('.image-remove-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.getAttribute('data-url-input-id'));
            const img = document.getElementById(btn.getAttribute('data-preview-id'));
            if (input) input.value = 'images/nexa-taxi-logo.png';
            if (img) {
                img.src = @json(asset('promo-videos-player/images/nexa-taxi-logo.png'));
                img.style.display = '';
            }
        });
    });
})();
</script>
@endpush
