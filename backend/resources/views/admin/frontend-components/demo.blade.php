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
            </p>
        </div>
        <a href="{{ route('admin.frontend-components.index') }}" class="kt-btn kt-btn-outline mt-3 inline-flex">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug naar componenten
        </a>
    </div>

    <div class="kt-card">
        <div class="kt-card-header">
            <h2 class="kt-card-title">Preview</h2>
        </div>
        <div class="kt-card-content p-0">
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
@endsection
