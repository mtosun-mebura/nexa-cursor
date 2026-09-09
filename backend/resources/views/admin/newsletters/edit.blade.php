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
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">{{ $isNew ? 'Nieuwsbrief designer' : 'Nieuwsbrief bewerken' }}</h1>
            <p class="text-sm text-muted-foreground mt-2 mb-0">Compacte wervende mail met website-plaatjes en een knop naar de contactpagina.</p>
        </div>
        <a href="{{ route('admin.newsletters.index') }}" class="kt-btn kt-btn-outline">
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

    <div class="kt-card mb-5">
        <div class="kt-card-header">
            <h5 class="kt-card-title">AI schrijft de nieuwsbrief</h5>
        </div>
        <div class="kt-card-content">
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

        <div class="grid gap-5 xl:grid-cols-2">
            <div class="grid gap-5">
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h5 class="kt-card-title">Kop en intro</h5>
                    </div>
                    <div class="kt-card-content space-y-4">
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
                    </div>
                </div>

                <div class="kt-card">
                    <div class="kt-card-header">
                        <h5 class="kt-card-title">Hero-plaatje</h5>
                    </div>
                    <div class="kt-card-content">
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

                @foreach(array_slice($features, 0, 3) as $i => $feature)
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h5 class="kt-card-title">Voordeel {{ $i + 1 }}</h5>
                        </div>
                        <div class="kt-card-content space-y-3">
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

                <div class="kt-card">
                    <div class="kt-card-header">
                        <h5 class="kt-card-title">Aanmelden</h5>
                    </div>
                    <div class="kt-card-content space-y-3">
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

                <div>
                    <button type="submit" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-check me-2"></i>
                        Opslaan
                    </button>
                </div>
            </div>

            <div>
                <div class="kt-card xl:sticky xl:top-24">
                    <div class="kt-card-header">
                        <h5 class="kt-card-title">Voorbeeld</h5>
                    </div>
                    <div class="kt-card-content p-3 bg-muted/30">
                        <iframe id="newsletter-preview"
                                title="Voorbeeld nieuwsbrief"
                                class="w-full h-[820px] bg-white border border-border rounded-lg"
                                style="color-scheme:light;"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('newsletter-form');
    const preview = document.getElementById('newsletter-preview');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const previewUrl = @json(route('admin.newsletters.preview'));
    const generateUrl = @json(route('admin.newsletters.generate'));
    let previewTimer = null;

    function fillField(id, value) {
        const el = document.getElementById(id);
        if (el && value != null) el.value = value;
    }

    function setPreview(html) {
        if (preview && typeof html === 'string') preview.srcdoc = html;
    }
    setPreview(@json($previewHtml));

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
