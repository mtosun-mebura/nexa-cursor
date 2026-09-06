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
                    <label class="kt-form-label mb-1.5" for="ai-image-prompt">Omschrijving</label>
                    <textarea id="ai-image-prompt" class="kt-textarea w-full" rows="6" maxlength="3500" placeholder="Bijv. Een luxe zwarte Mercedes taxi bij avond in een Europese binnenstad, cinematische verlichting, realistische foto"></textarea>
                    <div class="flex items-center justify-between mt-3">
                        <p class="text-xs text-muted-foreground mb-0">Pas de tekst aan en genereer opnieuw voor een nieuwe variant — het vorige resultaat blijft in de galerij staan.</p>
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

<div id="ai-image-lightbox" class="ai-image-lightbox" hidden>
    <div class="ai-image-lightbox__backdrop"></div>
    <div class="ai-image-lightbox__content">
        <button type="button" class="ai-image-lightbox__close kt-btn kt-btn-icon kt-btn-outline" aria-label="Sluiten">
            <i class="ki-filled ki-cross"></i>
        </button>
        <img src="" alt="">
    </div>
</div>

<div id="ai-image-confirm" class="ai-image-confirm" hidden>
    <div class="ai-image-confirm__backdrop"></div>
    <div class="ai-image-confirm__dialog kt-card">
        <div class="kt-card-content p-5">
            <p class="mb-4">Deze afbeelding definitief verwijderen?</p>
            <div class="flex justify-end gap-2">
                <button type="button" id="ai-image-confirm-cancel" class="kt-btn kt-btn-outline">Annuleren</button>
                <button type="button" id="ai-image-confirm-ok" class="kt-btn kt-btn-danger">Verwijderen</button>
            </div>
        </div>
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
        overflow: hidden;
        border: 1px solid var(--input, #e5e7eb);
        background: var(--muted, #f8fafc);
        cursor: grab;
    }
    .ai-image-card img {
        display: block;
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: cover;
    }
    .ai-image-card__actions {
        position: absolute;
        inset: auto 0 0 0;
        display: flex;
        gap: 0.25rem;
        padding: 0.375rem;
        background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
        opacity: 0;
        transition: opacity 0.15s ease;
    }
    .ai-image-card:hover .ai-image-card__actions,
    .ai-image-card:focus-within .ai-image-card__actions {
        opacity: 1;
    }
    .ai-image-card__actions button {
        flex: 1;
        background: rgba(255,255,255,0.92);
        border: none;
        border-radius: 0.25rem;
        padding: 0.25rem;
        font-size: 0.75rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .ai-image-card__actions button:hover {
        background: #fff;
    }
    .ai-image-preview-box img {
        display: block;
        max-width: 100%;
        max-height: 26rem;
        object-fit: contain;
    }
    .ai-image-lightbox,
    .ai-image-confirm {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .ai-image-lightbox__backdrop,
    .ai-image-confirm__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.75);
    }
    .ai-image-lightbox__content {
        position: relative;
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
    .ai-image-confirm__dialog {
        position: relative;
        max-width: 24rem;
        width: 90vw;
    }
</style>
@endpush

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
                    '<button type="button" data-action="open" title="Bekijken"><i class="ki-filled ki-eye"></i></button>' +
                    '<button type="button" data-action="copy" title="Kopiëren"><i class="ki-filled ki-copy"></i></button>' +
                    '<button type="button" data-action="download" title="Downloaden"><i class="ki-filled ki-file-down"></i></button>' +
                    '<button type="button" data-action="delete" title="Verwijderen"><i class="ki-filled ki-trash"></i></button>' +
                '</div>' +
            '</div>';
    }

    function prependGalleryItem(item) {
        if (emptyMsg) {
            emptyMsg.remove();
        }
        gallery.insertAdjacentHTML('afterbegin', galleryItemHtml(item));
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

        fetch(generateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ prompt: prompt }),
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
    function openLightbox(url) {
        lightboxImg.src = url;
        lightbox.hidden = false;
    }
    function closeLightbox() {
        lightbox.hidden = true;
        lightboxImg.src = '';
    }
    lightbox.querySelector('.ai-image-lightbox__backdrop').addEventListener('click', closeLightbox);
    lightbox.querySelector('.ai-image-lightbox__close').addEventListener('click', closeLightbox);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeLightbox();
            closeConfirm();
        }
    });

    // Delete confirm
    var confirmBox = document.getElementById('ai-image-confirm');
    var confirmOk = document.getElementById('ai-image-confirm-ok');
    var confirmCancel = document.getElementById('ai-image-confirm-cancel');
    var pendingDeleteCard = null;
    function openConfirm(card) {
        pendingDeleteCard = card;
        confirmBox.hidden = false;
    }
    function closeConfirm() {
        confirmBox.hidden = true;
        pendingDeleteCard = null;
    }
    confirmBox.querySelector('.ai-image-confirm__backdrop').addEventListener('click', closeConfirm);
    confirmCancel.addEventListener('click', closeConfirm);
    confirmOk.addEventListener('click', function () {
        if (!pendingDeleteCard) return;
        var id = pendingDeleteCard.getAttribute('data-delete-id');
        var card = pendingDeleteCard;
        confirmOk.disabled = true;
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
            confirmOk.disabled = false;
            closeConfirm();
            if (ok) {
                card.remove();
                if (!gallery.querySelector('.ai-image-card')) {
                    gallery.insertAdjacentHTML('afterend', '<p id="ai-image-gallery-empty" class="text-sm text-muted-foreground mb-0">Nog geen afbeeldingen gegenereerd.</p>');
                }
            }
        })
        .catch(function () {
            confirmOk.disabled = false;
            closeConfirm();
        });
    });

    // Gallery actions (event delegation)
    gallery.addEventListener('click', function (e) {
        var btnEl = e.target.closest('button[data-action]');
        if (!btnEl) return;
        var card = btnEl.closest('.ai-image-card');
        var action = btnEl.getAttribute('data-action');
        var url = card.getAttribute('data-url');

        if (action === 'open') {
            openLightbox(url);
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
@endsection
