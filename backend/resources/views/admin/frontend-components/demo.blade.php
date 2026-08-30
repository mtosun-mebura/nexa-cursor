@extends('admin.layouts.app')

@section('title', 'Component demo: ' . ($component->name ?? $component->id))

@section('content')
<div class="kt-container-fixed space-y-5">
    <div>
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Component demo</h1>
            <p class="text-sm text-muted-foreground mt-1">
                <strong>{{ $component->name ?? $component->id }}</strong>
                <span class="font-mono text-xs">({{ $component->id ?? 'onbekend' }})</span>
                @if(!empty($component->theme_name))
                    <span class="ms-2 inline-flex items-center rounded-md bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-900 dark:bg-orange-500/20 dark:text-orange-100">Thema: {{ $component->theme_name }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('admin.frontend-components.index') }}#frontend-comp-{{ $component->id }}" class="kt-btn kt-btn-outline mt-3 inline-flex">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug naar componenten
        </a>
    </div>

    <div class="kt-card">
        <div class="kt-card-header flex items-center justify-between gap-2">
            <h2 class="kt-card-title">Preview</h2>
            <button type="button"
                id="component-preview-replay"
                class="kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground"
                title="Animatie opnieuw afspelen"
                aria-label="Animatie opnieuw afspelen">
                <i class="ki-filled ki-arrows-circle text-base"></i>
            </button>
        </div>
        <div class="kt-card-content p-0" id="component-preview-root">
            @if(!empty($component->is_section_type))
                @include('admin.frontend-components.section-demo', ['sectionType' => $component->section_type ?? '', 'sectionName' => $component->name ?? 'Sectie'])
            @elseif(!empty($component->view) && view()->exists($component->view))
                @include($component->view, [
                    'sectionKey' => $sectionKey,
                    'sectionData' => $homeSections[$sectionKey] ?? [],
                    'homeSections' => $homeSections,
                    'googleReviews' => $googleReviews,
                    'jobs' => $jobs,
                    'page' => $page,
                    'themeSlug' => $themeSlug,
                    'themeSettings' => $themeSettings,
                    'googleMapsApiKey' => $googleMapsApiKey,
                    'emailTemplate' => $emailTemplate ?? null,
                    'formFields' => $formFields ?? collect(),
                    'emailTemplateBySectionKey' => $emailTemplateBySectionKey ?? [],
                ])
            @else
                <div class="p-6 text-sm text-destructive">
                    View niet gevonden: <code>{{ $component->view ?? '—' }}</code>
                </div>
            @endif
        </div>
    </div>
</div>
<script>
(function () {
    var btn = document.getElementById('component-preview-replay');
    var root = document.getElementById('component-preview-root');
    if (!btn || !root) return;
    btn.addEventListener('click', function () {
        var icon = btn.querySelector('i');
        if (icon) icon.classList.add('animate-spin');
        btn.disabled = true;
        if (typeof window.nexaReplayThemeAnimations === 'function') {
            window.nexaReplayThemeAnimations(root);
        } else {
            root.querySelectorAll('[data-scroll-reveal], .scroll-reveal-section, .theme-scroll-reveal').forEach(function (el) {
                el.classList.remove('is-in-view', 'is-visible');
                el.classList.add('theme-anim-reset');
                void el.offsetWidth;
                window.setTimeout(function () {
                    el.classList.remove('theme-anim-reset');
                    void el.offsetWidth;
                    el.classList.add('is-in-view');
                }, 50);
            });
        }
        setTimeout(function () {
            if (icon) icon.classList.remove('animate-spin');
            btn.disabled = false;
        }, 1200);
    });
})();
</script>
@endsection
