@extends('admin.layouts.app')

@section('title', 'AI Afbeeldingen')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-col items-start gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">AI Afbeeldingen</h1>
        <p class="text-sm text-muted-foreground mb-0">Genereer afbeeldingen met AI en gebruik ze overal in het admin-paneel: kopieer een afbeelding en plak 'm (Ctrl+V), of sleep 'm direct in een upload-veld.</p>
    </div>

    @if(! $openAiConfigured)
        <div class="kt-alert kt-alert-warning mb-5">
            Er is geen OpenAI API-sleutel gezet (<code>OPENAI_API_KEY</code>). Genereren werkt pas zodra deze is ingesteld.
        </div>
    @endif

    <div id="ai-image-error" class="kt-alert kt-alert-danger mb-5" hidden></div>

    <div class="kt-card w-full min-w-0 mb-6 overflow-hidden">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
            <h3 class="kt-card-title mb-0">Nieuwe afbeelding genereren</h3>
        </div>
        <div class="kt-card-content p-5">
            <div class="grid gap-6 lg:grid-cols-2 items-start">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <label class="kt-form-label mb-0" for="ai-image-prompt">Omschrijving</label>
                        <button type="button"
                                id="ai-image-prompt-clear"
                                class="ai-image-prompt-clear shrink-0 inline-flex items-center justify-center size-8 rounded-md text-muted-foreground hover:text-foreground hover:bg-muted/60"
                                data-tooltip="Wist de omschrijving in één keer"
                                aria-label="Tekst wissen">
                            <i class="ki-eraser ki-duotone text-xl leading-none" aria-hidden="true"></i>
                        </button>
                    </div>
                    <textarea id="ai-image-prompt" class="kt-textarea w-full" rows="6" maxlength="3500" placeholder="Bijv. Een luxe zwarte Mercedes taxi bij avond in een Europese binnenstad, cinematische verlichting, realistische foto"></textarea>
                    <div id="ai-image-source" class="ai-image-source hidden mt-3 items-center gap-3 rounded-xl border border-border p-3">
                        <img id="ai-image-source-thumb" src="" alt="" class="size-12 rounded-lg object-cover shrink-0 ring-1 ring-border">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-foreground mb-0">Bron voor aanpassing</p>
                            <p class="text-xs text-muted-foreground mb-0">Dit plaatje gaat mee als context bij genereren.</p>
                        </div>
                        <button type="button" id="ai-image-source-clear" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" aria-label="Bron verwijderen" title="Bron verwijderen">
                            <i class="ki-filled ki-cross"></i>
                        </button>
                    </div>
                    <div class="flex items-center justify-between gap-3 mt-3">
                        <p id="ai-image-prompt-hint" class="text-xs text-muted-foreground mb-0">Pas de tekst aan en genereer opnieuw voor een nieuwe variant — het vorige resultaat blijft in de galerij staan.</p>
                        <button type="button" id="ai-image-generate-btn" class="kt-btn kt-btn-primary shrink-0">
                            <span id="ai-image-generate-label">Genereer</span>
                            <span id="ai-image-generate-spinner" class="ki-filled ki-arrows-circle animate-spin ms-1" hidden></span>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="kt-form-label mb-1.5">Resultaat</label>
                    <div id="ai-image-preview" class="ai-image-preview-box rounded-lg border border-dashed border-input flex items-center justify-center overflow-hidden bg-muted/30" style="min-height: 16rem;">
                        <span class="text-sm text-muted-foreground px-4 text-center">Nog geen afbeelding gegenereerd.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="kt-card w-full min-w-0">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
            <h3 class="kt-card-title mb-0">Galerij</h3>
        </div>
        <div class="kt-card-content p-5">
            <div id="ai-image-gallery" class="ai-image-gallery-grid">
                @foreach($images as $image)
                    @include('admin.ai-images.partials.gallery-item', ['image' => $image])
                @endforeach
            </div>
            @if($images->isEmpty())
                <p id="ai-image-gallery-empty" class="text-sm text-muted-foreground mb-0">Nog geen afbeeldingen gegenereerd.</p>
            @endif
            @if($images->hasPages())
                <div class="mt-5">
                    {{ $images->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<div id="ai-image-lightbox"
     class="ai-image-lightbox hidden fixed inset-0 z-[100000] items-center justify-center p-4"
     hidden>
    <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-md" data-ai-image-lightbox-dismiss></div>
    <div class="ai-image-lightbox__content relative z-10">
        <button type="button" class="ai-image-lightbox__close kt-btn kt-btn-icon kt-btn-outline" aria-label="Sluiten">
            <i class="ki-filled ki-cross"></i>
        </button>
        <img src="" alt="">
    </div>
</div>

@push('styles')
<style>
    .ai-image-gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 1rem;
    }
    .ai-image-card {
        position: relative;
        border-radius: 0.5rem;
        overflow: visible;
        border: 1px solid var(--input, #e5e7eb);
        background: var(--muted, #f8fafc);
        cursor: grab;
    }
    .ai-image-card img {
        display: block;
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        border-radius: 0.5rem;
    }
    .ai-image-card__actions {
        position: absolute;
        inset: auto 0 0 0;
        display: flex;
        gap: 0.15rem;
        padding: 0.4rem 0.25rem 0.5rem;
        background: none;
        opacity: 0;
        pointer-events: none;
        transition: none;
        z-index: 2;
    }
    .ai-image-card.is-source {
        outline: 2px solid var(--primary, #3b82f6);
        outline-offset: 2px;
    }
    .ai-image-card:hover .ai-image-card__actions,
    .ai-image-card:focus-within .ai-image-card__actions {
        opacity: 1;
        pointer-events: auto;
    }
    .ai-image-source:not(.hidden) {
        display: flex;
    }
    .ai-image-prompt-clear {
        position: relative;
        border: none;
        background: transparent;
        cursor: pointer;
        line-height: 1;
    }
    .ai-image-prompt-clear:focus-visible {
        outline: 2px solid var(--primary, #3b82f6);
        outline-offset: 2px;
    }
    .ai-image-prompt-clear::after {
        content: attr(data-tooltip);
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        padding: 0.3rem 0.55rem;
        border-radius: 0.375rem;
        background: #111827;
        color: #f9fafb;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1.2;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        z-index: 5;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.22);
    }
    .ai-image-prompt-clear:hover::after,
    .ai-image-prompt-clear:focus-visible::after {
        opacity: 1;
    }
    .ai-image-card__actions button {
        position: relative;
        flex: 1;
        background: none !important;
        border: none;
        border-radius: 0;
        padding: 0.2rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        box-shadow: none !important;
        transition: none;
    }
    .ai-image-card__actions button i {
        font-size: 1.25rem;
        line-height: 1;
        color: inherit;
        background: none !important;
        filter: drop-shadow(0 0 1px #000) drop-shadow(0 1px 2px rgba(0,0,0,0.9));
    }
    .ai-image-card__actions button[data-action="delete"] {
        color: #fecaca;
    }
    .ai-image-card__actions button:hover,
    .ai-image-card__actions button:focus-visible {
        background: none !important;
        color: #fff;
        transform: scale(1.12);
    }
    .ai-image-card__actions button[data-action="delete"]:hover,
    .ai-image-card__actions button[data-action="delete"]:focus-visible {
        color: #fca5a5;
    }
    .ai-image-card__actions button::after {
        content: attr(data-label);
        position: absolute;
        left: 50%;
        bottom: calc(100% + 6px);
        transform: translateX(-50%);
        padding: 0.2rem 0.45rem;
        border-radius: 0.25rem;
        background: rgba(15, 23, 42, 0.92);
        color: #fff;
        font-size: 0.6875rem;
        font-weight: 500;
        line-height: 1.2;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        z-index: 2;
        transition: none;
    }
    .ai-image-card__actions button:hover::after,
    .ai-image-card__actions button:focus-visible::after {
        opacity: 1;
    }
    .ai-image-preview-box img {
        display: block;
        max-width: 100%;
        max-height: 26rem;
        object-fit: contain;
    }
    .ai-image-lightbox__content {
        max-width: 92vw;
        max-height: 92vh;
    }
    .ai-image-lightbox__content img {
        display: block;
        max-width: 92vw;
        max-height: 92vh;
        border-radius: 0.5rem;
    }
    .ai-image-lightbox__close {
        position: absolute;
        top: -0.75rem;
        right: -0.75rem;
        z-index: 1;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var promptEl = document.getElementById('ai-image-prompt');
    var btn = document.getElementById('ai-image-generate-btn');
    var label = document.getElementById('ai-image-generate-label');
    var spinner = document.getElementById('ai-image-generate-spinner');
    var preview = document.getElementById('ai-image-preview');
    var gallery = document.getElementById('ai-image-gallery');
    var emptyMsg = document.getElementById('ai-image-gallery-empty');
    var errorBox = document.getElementById('ai-image-error');
    var generateUrl = @json(route('admin.ai-images.generate'));
    var sourceBox = document.getElementById('ai-image-source');
    var sourceThumb = document.getElementById('ai-image-source-thumb');
    var sourceClear = document.getElementById('ai-image-source-clear');
    var promptHint = document.getElementById('ai-image-prompt-hint');
    var selectedSourceUuid = null;
    var defaultHint = 'Pas de tekst aan en genereer opnieuw voor een nieuwe variant — het vorige resultaat blijft in de galerij staan.';
    var sourceHint = 'Beschrijf de aanpassing. Het gekozen plaatje (logo/stijl) gaat als context mee.';

    function showError(message) {
        errorBox.textContent = message;
        errorBox.hidden = false;
    }
    function clearError() {
        errorBox.hidden = true;
        errorBox.textContent = '';
    }

    function setLoading(loading) {
        btn.disabled = loading;
        spinner.hidden = !loading;
        label.textContent = loading ? 'Bezig…' : 'Genereer';
    }

    function galleryItemHtml(item) {
        var safePrompt = (item.prompt || '').replace(/"/g, '&quot;');
        return '' +
            '<div class="ai-image-card" draggable="true" data-uuid="' + item.uuid + '" data-url="' + item.url + '" data-download-url="' + item.url + '?download=1" data-prompt="' + safePrompt + '" data-delete-id="' + item.id + '">' +
                '<img src="' + item.url + '" alt="" loading="lazy">' +
                '<div class="ai-image-card__actions">' +
                    '<button type="button" data-action="open" data-label="Bekijken" aria-label="Bekijken"><i class="ki-outline ki-eye"><\/i></button>' +
                    '<button type="button" data-action="reuse" data-label="Hergebruiken" aria-label="Hergebruiken als bron"><i class="ki-outline ki-arrow-up"><\/i></button>' +
                    '<button type="button" data-action="copy" data-label="Kopiëren" aria-label="Kopiëren"><i class="ki-outline ki-copy"><\/i></button>' +
                    '<button type="button" data-action="download" data-label="Downloaden" aria-label="Downloaden"><i class="ki-outline ki-file-down"><\/i></button>' +
                    '<button type="button" data-action="delete" data-label="Verwijderen" aria-label="Verwijderen"><i class="ki-outline ki-trash"><\/i></button>' +
                '<\/div>' +
            '<\/div>';
    }

    function prependGalleryItem(item) {
        if (emptyMsg) {
            emptyMsg.remove();
        }
        gallery.insertAdjacentHTML('afterbegin', galleryItemHtml(item));
    }

    function clearSource() {
        selectedSourceUuid = null;
        if (sourceBox) {
            sourceBox.classList.add('hidden');
        }
        if (sourceThumb) {
            sourceThumb.src = '';
        }
        if (promptHint) {
            promptHint.textContent = defaultHint;
        }
        gallery.querySelectorAll('.ai-image-card.is-source').forEach(function (card) {
            card.classList.remove('is-source');
        });
    }

    function selectSource(card) {
        var uuid = card.getAttribute('data-uuid');
        var url = card.getAttribute('data-url');
        var originalPrompt = card.getAttribute('data-prompt') || '';
        selectedSourceUuid = uuid;
        gallery.querySelectorAll('.ai-image-card.is-source').forEach(function (el) {
            el.classList.remove('is-source');
        });
        card.classList.add('is-source');
        if (sourceThumb) {
            sourceThumb.src = url;
        }
        if (sourceBox) {
            sourceBox.classList.remove('hidden');
        }
        if (promptHint) {
            promptHint.textContent = sourceHint;
        }
        if (promptEl && !(promptEl.value || '').trim() && originalPrompt) {
            promptEl.value = originalPrompt;
        }
        if (promptEl) {
            promptEl.focus();
            promptEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    if (sourceClear) {
        sourceClear.addEventListener('click', clearSource);
    }

    var promptClear = document.getElementById('ai-image-prompt-clear');
    if (promptClear) {
        promptClear.addEventListener('click', function () {
            if (promptEl) {
                promptEl.value = '';
                promptEl.focus();
            }
        });
    }

    btn.addEventListener('click', function () {
        var prompt = (promptEl.value || '').trim();
        clearError();
        if (!prompt) {
            showError('Vul eerst een omschrijving in.');
            return;
        }
        setLoading(true);
        preview.innerHTML = '<span class="text-sm text-muted-foreground px-4 text-center">Bezig met genereren…</span>';

        var payload = { prompt: prompt };
        if (selectedSourceUuid) {
            payload.source_uuid = selectedSourceUuid;
        }

        fetch(generateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        })
        .then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data: data }; });
        })
        .then(function (result) {
            setLoading(false);
            if (!result.ok) {
                preview.innerHTML = '<span class="text-sm text-muted-foreground px-4 text-center">Nog geen afbeelding gegenereerd.</span>';
                showError(result.data.message || 'Genereren is mislukt.');
                return;
            }
            preview.innerHTML = '<img src="' + result.data.url + '" alt="">';
            prependGalleryItem(result.data);
        })
        .catch(function () {
            setLoading(false);
            preview.innerHTML = '<span class="text-sm text-muted-foreground px-4 text-center">Nog geen afbeelding gegenereerd.</span>';
            showError('Genereren is mislukt door een netwerkfout.');
        });
    });

    // Lightbox
    var lightbox = document.getElementById('ai-image-lightbox');
    var lightboxImg = lightbox.querySelector('img');
    function mountOverlay(el) {
        if (el && el.parentElement !== document.body) {
            document.body.appendChild(el);
        }
    }
    function showOverlay(el) {
        mountOverlay(el);
        el.hidden = false;
        el.classList.remove('hidden');
        el.classList.add('flex');
    }
    function hideOverlay(el) {
        el.hidden = true;
        el.classList.add('hidden');
        el.classList.remove('flex');
    }
    mountOverlay(lightbox);
    function openLightbox(url) {
        lightboxImg.src = url;
        showOverlay(lightbox);
    }
    function closeLightbox() {
        hideOverlay(lightbox);
        lightboxImg.src = '';
    }
    lightbox.querySelector('[data-ai-image-lightbox-dismiss]').addEventListener('click', closeLightbox);
    lightbox.querySelector('.ai-image-lightbox__close').addEventListener('click', closeLightbox);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeLightbox();
        }
    });

    function deleteGalleryCard(card) {
        if (!card) return;
        var id = card.getAttribute('data-delete-id');
        fetch('/admin/ai-images/' + id, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(function (res) { return res.ok; })
        .then(function (ok) {
            if (!ok) {
                return;
            }
            if (card.getAttribute('data-uuid') === selectedSourceUuid) {
                clearSource();
            }
            card.remove();
            if (!gallery.querySelector('.ai-image-card')) {
                gallery.insertAdjacentHTML('afterend', '<p id="ai-image-gallery-empty" class="text-sm text-muted-foreground mb-0">Nog geen afbeeldingen gegenereerd.</p>');
            }
        })
        .catch(function () {});
    }

    function openConfirm(card) {
        var message = 'Deze afbeelding definitief verwijderen?';
        if (typeof window.showAdminConfirm === 'function') {
            window.showAdminConfirm({
                title: 'Afbeelding verwijderen',
                message: message,
                confirmLabel: 'Verwijderen'
            }).then(function (ok) {
                if (ok) {
                    deleteGalleryCard(card);
                }
            });
            return;
        }
        if (window.confirm(message)) {
            deleteGalleryCard(card);
        }
    }

    // Gallery actions (event delegation)
    gallery.addEventListener('click', function (e) {
        var btnEl = e.target.closest('button[data-action]');
        if (!btnEl) return;
        var card = btnEl.closest('.ai-image-card');
        var action = btnEl.getAttribute('data-action');
        var url = card.getAttribute('data-url');

        if (action === 'open') {
            openLightbox(url);
        } else if (action === 'reuse') {
            selectSource(card);
        } else if (action === 'download') {
            var a = document.createElement('a');
            a.href = card.getAttribute('data-download-url');
            a.click();
        } else if (action === 'delete') {
            openConfirm(card);
        } else if (action === 'copy') {
            fetch(url).then(function (r) { return r.blob(); }).then(function (blob) {
                if (navigator.clipboard && window.ClipboardItem) {
                    return navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
                }
                return navigator.clipboard.writeText(window.location.origin + url);
            }).then(function () {
                btnEl.innerHTML = '<i class="ki-filled ki-check"></i>';
                setTimeout(function () { btnEl.innerHTML = '<i class="ki-filled ki-copy"></i>'; }, 1200);
            }).catch(function () { /* clipboard kan geweigerd zijn; negeer stil */ });
        }
    });

    // Drag source: custom payload zodat de globale drop-brug 'm herkent, + normale URL-fallback.
    gallery.addEventListener('dragstart', function (e) {
        var card = e.target.closest('.ai-image-card');
        if (!card) return;
        var payload = JSON.stringify({ uuid: card.getAttribute('data-uuid'), url: card.getAttribute('data-url') });
        e.dataTransfer.setData('application/x-nexa-ai-image', payload);
        e.dataTransfer.setData('text/uri-list', window.location.origin + card.getAttribute('data-url'));
        e.dataTransfer.setData('text/plain', window.location.origin + card.getAttribute('data-url'));
        e.dataTransfer.effectAllowed = 'copy';
    });
})();
</script>
@endpush
@endsection
