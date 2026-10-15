@extends('admin.layouts.app')

@section('title', $isNew ? 'Nieuwsbrief designer' : 'Nieuwsbrief bewerken')

@include('admin.partials.ajax-action-button-styles')

@section('content')
@php
    $b = is_array($campaign->blocks) ? $campaign->blocks : [];
    $features = old('features', $b['features'] ?? []);
    if (! is_array($features)) {
        $features = [];
    }
    $features = array_values($features);
    while (count($features) < 3) {
        $features[] = ['title' => '', 'text' => '', 'image' => ''];
    }
    $stockLabels = [
        'hero' => 'Platform',
        'booking' => 'Boeken',
        'driver' => 'Chauffeur',
        'contract' => 'Contract',
        'website' => 'Website',
        'planning' => 'Planning',
    ];
@endphp
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-3 pb-7.5">
        <div class="min-w-0">
            <h1 class="text-xl font-medium leading-none text-mono">{{ $isNew ? 'Nieuwsbrief designer' : 'Nieuwsbrief bewerken' }}</h1>
            <p class="text-sm text-muted-foreground mt-2 mb-0">Compacte wervende mail met website-plaatjes en een knop naar de contactpagina.</p>
        </div>
        <a href="{{ route('admin.newsletters.index') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Overzicht
        </a>
    </div>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="kt-card mb-5 w-full min-w-0">
        <div class="kt-card-header px-5 py-5">
            <h5 class="kt-card-title mb-0">AI schrijft de nieuwsbrief</h5>
        </div>
        <div class="kt-card-content p-5 min-w-0">
            <label for="ai-brief" class="kt-label mb-1">Opdracht (optioneel)</label>
            <textarea id="ai-brief" class="kt-textarea w-full" rows="3" placeholder="Bijvoorbeeld: nadruk op contractvervoer in Utrecht en geen commissie."></textarea>
            <div class="flex flex-wrap items-center gap-2 mt-3">
                <button type="button" id="ai-generate-btn" class="kt-btn kt-btn-primary admin-ajax-action-btn">
                    @include('admin.partials.ajax-action-spinner')
                    <i class="ki-filled ki-click admin-ajax-action-idle-icon me-2"></i>
                    <span class="admin-ajax-action-label">Laat AI schrijven</span>
                </button>
                <span id="ai-generate-status" class="text-sm text-muted-foreground"></span>
            </div>
        </div>
    </div>

    <form id="newsletter-form"
          action="{{ $isNew ? route('admin.newsletters.store') : route('admin.newsletters.update', $campaign) }}"
          method="POST"
          data-validate="true"
          novalidate>
        @csrf
        @if(! $isNew)
            @method('PUT')
        @endif

        <div class="grid gap-5 xl:grid-cols-2 min-w-0">
            <div class="grid gap-5 min-w-0">
                <div class="kt-card w-full min-w-0">
                    <div class="kt-card-header px-5 py-5">
                        <h5 class="kt-card-title mb-0">Kop en intro</h5>
                    </div>
                    <div class="kt-card-content p-5 space-y-4 min-w-0">
                        <div>
                            <label class="kt-label mb-1" for="name">Interne naam *</label>
                            <input type="text" id="name" name="name" class="kt-input w-full" required value="{{ old('name', $campaign->name) }}">
                        </div>
                        <div>
                            <label class="kt-label mb-1" for="subject">Onderwerpregel *</label>
                            <input type="text" id="subject" name="subject" class="kt-input w-full" required value="{{ old('subject', $campaign->subject) }}">
                        </div>
                        <div>
                            <label class="kt-label mb-1" for="preview_text">Inbox-preview</label>
                            <input type="text" id="preview_text" name="preview_text" class="kt-input w-full" value="{{ old('preview_text', $campaign->preview_text) }}">
                        </div>
                        <div>
                            <label class="kt-label mb-1" for="eyebrow">Eyebrow</label>
                            <input type="text" id="eyebrow" name="eyebrow" class="kt-input w-full" value="{{ old('eyebrow', $b['eyebrow'] ?? '') }}">
                        </div>
                        <div>
                            <label class="kt-label mb-1" for="title">Kop *</label>
                            <input type="text" id="title" name="title" class="kt-input w-full" required value="{{ old('title', $b['title'] ?? '') }}">
                        </div>
                        <div>
                            <label class="kt-label mb-1" for="intro">Intro *</label>
                            <textarea id="intro" name="intro" class="kt-textarea w-full" rows="5" required>{{ old('intro', $b['intro'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="kt-label mb-1" for="highlight">Uitgelichte regel</label>
                            <textarea id="highlight" name="highlight" class="kt-textarea w-full" rows="2">{{ old('highlight', $b['highlight'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="kt-card w-full min-w-0">
                    <div class="kt-card-header px-5 py-5">
                        <h5 class="kt-card-title mb-0">Hero-plaatje</h5>
                    </div>
                    <div class="kt-card-content p-5 min-w-0">
                        <input type="hidden" id="hero_image" name="hero_image" class="nl-image-input" value="{{ old('hero_image', $b['hero_image'] ?? '') }}">
                        <div class="flex flex-wrap gap-2" data-image-target="hero_image">
                            @foreach($stockImages as $key => $url)
                                <button type="button" class="nl-stock-btn border border-border rounded-md overflow-hidden p-0 w-24 h-16 bg-white" data-url="{{ $url }}" title="{{ $stockLabels[$key] ?? $key }}">
                                    <img src="{{ $url }}" alt="{{ $stockLabels[$key] ?? $key }}" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                        <p class="text-xs text-muted-foreground mt-2 mb-0">Klik een plaatje uit de website-bibliotheek.</p>
                    </div>
                </div>

                @foreach($features as $i => $feature)
                    <div class="kt-card w-full min-w-0">
                        <div class="kt-card-header px-5 py-5">
                            <h5 class="kt-card-title mb-0">Voordeel {{ $i + 1 }}</h5>
                        </div>
                        <div class="kt-card-content p-5 space-y-3 min-w-0">
                            <input type="text" name="features[{{ $i }}][title]" class="kt-input w-full" placeholder="Titel" value="{{ $feature['title'] ?? '' }}">
                            <textarea name="features[{{ $i }}][text]" class="kt-textarea w-full" rows="3" placeholder="Korte overtuigende zin">{{ $feature['text'] ?? '' }}</textarea>
                            <input type="hidden" id="feature_image_{{ $i }}" name="features[{{ $i }}][image]" class="nl-image-input" value="{{ $feature['image'] ?? '' }}">
                            <div class="flex flex-wrap gap-2" data-image-target="feature_image_{{ $i }}">
                                @foreach($stockImages as $key => $url)
                                    <button type="button" class="nl-stock-btn border border-border rounded-md overflow-hidden p-0 w-20 h-14 bg-white" data-url="{{ $url }}">
                                        <img src="{{ $url }}" alt="" class="w-full h-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="kt-card w-full min-w-0">
                    <div class="kt-card-header px-5 py-5">
                        <h5 class="kt-card-title mb-0">Aanmelden</h5>
                    </div>
                    <div class="kt-card-content p-5 space-y-3 min-w-0">
                        <div>
                            <label class="kt-label mb-1" for="cta_label">Knoptekst *</label>
                            <input type="text" id="cta_label" name="cta_label" class="kt-input w-full" required value="{{ old('cta_label', $b['cta_label'] ?? 'Aanmelden via contact') }}">
                        </div>
                        <div>
                            <label class="kt-label mb-1" for="cta_url">Link</label>
                            <input type="text" id="cta_url" name="cta_url" class="kt-input w-full" value="{{ old('cta_url', $b['cta_url'] ?? '/contact') }}">
                            <p class="text-xs text-muted-foreground mt-1 mb-0">Standaard de contactpagina. Onderaan de mail staat altijd een afmeldlink.</p>
                        </div>
                    </div>
                </div>

                <div class="admin-form-actions flex flex-wrap items-center gap-2.5 w-full min-w-0">
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check me-2"></i>
                        Opslaan
                    </button>
                </div>
            </div>

            <div class="min-w-0">
                <div class="kt-card w-full min-w-0 xl:sticky xl:top-24">
                    <div class="kt-card-header px-5 py-5 flex items-center justify-between gap-3">
                        <h5 class="kt-card-title mb-0">Voorbeeld</h5>
                        <button type="button"
                                id="newsletter-preview-expand"
                                class="kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-primary shrink-0"
                                title="Voorbeeld tonen"
                                aria-label="Voorbeeld tonen"
                                aria-controls="newsletter-preview-modal"
                                aria-expanded="false">
                            <i class="ki-filled ki-eye text-base" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="kt-card-content p-3 sm:p-5 min-w-0">
                        <iframe id="newsletter-preview"
                                title="Voorbeeld nieuwsbrief"
                                class="newsletter-preview-frame w-full bg-white border border-border rounded-lg"
                                style="color-scheme:light;"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div id="newsletter-preview-modal"
     class="newsletter-preview-modal"
     role="dialog"
     aria-modal="true"
     aria-labelledby="newsletter-preview-modal-title"
     hidden>
    <button type="button"
            class="newsletter-preview-modal__backdrop"
            data-newsletter-preview-dismiss
            aria-label="Voorbeeld sluiten"></button>
    <div class="newsletter-preview-modal__dialog">
        <header class="kt-card-header px-5 py-5 flex items-center justify-between gap-3 border-b border-border">
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-wide text-muted-foreground mb-0">Voorbeeld</p>
                <h2 id="newsletter-preview-modal-title" class="text-base font-semibold text-foreground mb-0 mt-0.5">Nieuwsbrief</h2>
            </div>
            <button type="button"
                    class="kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground shrink-0"
                    data-newsletter-preview-dismiss
                    title="Sluiten"
                    aria-label="Sluiten">
                <i class="ki-filled ki-cross text-base" aria-hidden="true"></i>
            </button>
        </header>
        <div class="newsletter-preview-modal__body">
            <iframe id="newsletter-preview-modal-frame"
                    title="Voorbeeld nieuwsbrief groot"
                    class="newsletter-preview-modal__frame"></iframe>
        </div>
    </div>
</div>
<style>
    .newsletter-preview-frame {
        height: clamp(640px, 78vh, 960px);
        min-height: 640px;
    }
    .newsletter-preview-modal {
        position: fixed;
        inset: 0;
        z-index: 12000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
    }
    .newsletter-preview-modal.is-open {
        display: flex;
    }
    .newsletter-preview-modal__backdrop {
        position: absolute;
        inset: 0;
        border: 0;
        margin: 0;
        padding: 0;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        cursor: pointer;
    }
    .newsletter-preview-modal__dialog {
        position: relative;
        z-index: 1;
        width: min(1200px, calc(100vw - 2.5rem));
        height: min(920px, calc(100vh - 2.5rem));
        display: flex;
        flex-direction: column;
        border-radius: 1rem;
        border: 1px solid rgba(15, 23, 42, 0.12);
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 25px 50px -12px rgba(2, 6, 23, 0.35);
        overflow: hidden;
    }
    html.dark .newsletter-preview-modal__dialog,
    .dark .newsletter-preview-modal__dialog {
        background: #0b0f19;
        color: #f8fafc;
        border-color: rgba(148, 163, 184, 0.12);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.65);
    }
    .newsletter-preview-modal__body {
        position: relative;
        flex: 1;
        min-height: 0;
        overflow: hidden;
    }
    .newsletter-preview-modal__frame {
        width: 100%;
        height: 100%;
        border: 0;
        background: transparent;
        color-scheme: light;
    }
</style>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('newsletter-form');
    const preview = document.getElementById('newsletter-preview');
    const previewModal = document.getElementById('newsletter-preview-modal');
    const previewModalFrame = document.getElementById('newsletter-preview-modal-frame');
    const previewExpand = document.getElementById('newsletter-preview-expand');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const previewUrl = @json(route('admin.newsletters.preview'));
    const generateUrl = @json(route('admin.newsletters.generate'));
    let previewTimer = null;
    let previewHtml = '';

    function fillField(id, value) {
        const el = document.getElementById(id);
        if (el && value != null) el.value = value;
    }

    function setPreview(html) {
        if (typeof html !== 'string') return;
        previewHtml = html;
        if (preview) preview.srcdoc = html;
        if (previewModalFrame && previewModal && previewModal.classList.contains('is-open')) {
            previewModalFrame.srcdoc = html;
        }
    }
    setPreview(@json($previewHtml));

    function openPreviewModal() {
        if (!previewModal || !previewModalFrame) return;
        previewModalFrame.srcdoc = previewHtml;
        previewModal.hidden = false;
        previewModal.classList.add('is-open', 'builder-block-preview-modal');
        if (previewExpand) previewExpand.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closePreviewModal() {
        if (!previewModal || !previewModal.classList.contains('is-open')) return;
        previewModal.classList.remove('is-open', 'builder-block-preview-modal');
        previewModal.hidden = true;
        if (previewExpand) previewExpand.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    if (previewExpand) previewExpand.addEventListener('click', openPreviewModal);
    if (previewModal) {
        previewModal.addEventListener('click', function (event) {
            if (event.target.closest('[data-newsletter-preview-dismiss]')) closePreviewModal();
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closePreviewModal();
    });

    function refreshPreview() {
        const data = new FormData(form);
        fetch(previewUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: data
        }).then(function (r) { return r.json(); }).then(function (json) {
            if (json.preview_html) setPreview(json.preview_html);
        }).catch(function () {});
    }

    form.addEventListener('input', function () {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(refreshPreview, 400);
    });

    document.querySelectorAll('[data-image-target]').forEach(function (group) {
        const targetId = group.getAttribute('data-image-target');
        group.querySelectorAll('.nl-stock-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = document.getElementById(targetId);
                if (input) input.value = btn.getAttribute('data-url') || '';
                refreshPreview();
            });
        });
    });

    const aiBtn = document.getElementById('ai-generate-btn');
    const aiStatus = document.getElementById('ai-generate-status');
    aiBtn.addEventListener('click', function () {
        aiBtn.classList.add('is-loading');
        aiBtn.disabled = true;
        aiStatus.textContent = 'AI schrijft…';
        const body = new FormData();
        body.append('brief', document.getElementById('ai-brief').value);
        fetch(generateUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        }).then(function (r) { return r.json(); }).then(function (json) {
            const blocks = json.blocks || {};
            fillField('name', json.name);
            fillField('subject', json.subject);
            fillField('preview_text', json.preview_text);
            fillField('eyebrow', blocks.eyebrow);
            fillField('title', blocks.title);
            fillField('intro', blocks.intro);
            if (blocks.highlight) fillField('highlight', blocks.highlight);
            fillField('cta_label', blocks.cta_label);
            fillField('cta_url', blocks.cta_url || '/contact');
            fillField('hero_image', blocks.hero_image);
            (blocks.features || []).slice(0, 3).forEach(function (feature, i) {
                const title = form.querySelector('[name="features[' + i + '][title]"]');
                const text = form.querySelector('[name="features[' + i + '][text]"]');
                const image = document.getElementById('feature_image_' + i);
                if (title) title.value = feature.title || '';
                if (text) text.value = feature.text || '';
                if (image) image.value = feature.image || '';
            });
            if (json.preview_html) setPreview(json.preview_html);
            aiStatus.textContent = 'Tekst ingevuld. Controleer en sla op.';
            aiBtn.classList.remove('is-loading');
            aiBtn.classList.add('is-success');
            aiBtn.disabled = false;
        }).catch(function () {
            aiStatus.textContent = 'AI niet bereikbaar. Standaardtekst blijft staan.';
            aiBtn.classList.remove('is-loading');
            aiBtn.classList.add('is-error');
            aiBtn.disabled = false;
        });
    });
})();
</script>
@endpush
