@extends('admin.layouts.app')

@push('styles')
<style>
    .theme-card-icon {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        padding: 0;
        border: none;
        border-radius: 0.5rem;
        background: transparent;
        color: var(--muted-foreground);
        text-decoration: none;
        cursor: pointer;
        line-height: 1;
    }
    .theme-card-icon i {
        font-size: 1.25rem;
    }
    .theme-card-icon:hover,
    .theme-card-icon:focus-visible {
        color: var(--foreground);
        background: color-mix(in oklab, var(--muted) 80%, transparent);
        outline: none;
    }
    .theme-card-icon--preview:hover,
    .theme-card-icon--preview:focus-visible { color: #ea580c; }
    .theme-card-icon--unpublish:hover,
    .theme-card-icon--unpublish:focus-visible { color: var(--destructive); }
    .theme-card-icon--activate:hover,
    .theme-card-icon--activate:focus-visible { color: #16a34a; }
    .theme-card-icon::after {
        content: attr(data-tooltip);
        position: absolute;
        left: 50%;
        bottom: calc(100% + 0.5rem);
        transform: translateX(-50%) translateY(0.25rem);
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
        box-shadow: 0 4px 12px rgb(0 0 0 / 0.18);
        transition: opacity 0.12s ease, transform 0.12s ease;
        z-index: 20;
    }
    .theme-card-icon:hover::after,
    .theme-card-icon:focus-visible::after {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    .dark .theme-card-icon::after {
        background: #f3f4f6;
        color: #111827;
    }
</style>
@endpush

@section('title', "Frontend Thema's")

@section('content')
@php $themeStagingUrls = $themeStagingUrls ?? []; @endphp
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Frontend Thema's</h1>
        <div class="flex flex-wrap items-center gap-2">
            @if(!empty($stagingUrlTop))
            <a href="{{ $stagingUrlTop }}" target="_blank" rel="noopener noreferrer" class="kt-btn kt-btn-outline" title="Staging: gekozen thema met pagina's en functionaliteit">
                <i class="ki-filled ki-eye me-2"></i>
                Website tonen
            </a>
            @endif
            <a href="{{ route('admin.frontend-themes.setup') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-information-2 me-2"></i>
                Setup tonen
            </a>
        </div>
    </div>
    <p class="text-sm text-muted-foreground mb-5">De onderstaande thema's zijn gratis te gebruiken. Klik op <strong>Activeren</strong> om een thema beschikbaar te maken — meerdere thema's kunnen tegelijk actief zijn. Per <strong>tenant</strong> kies je hieronder welk thema op die website komt. Via <strong>Instellingen</strong> pas je kleur, lettertypen en footertekst aan.</p>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-destructive mb-5">
            <i class="ki-filled ki-information-2 me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        @foreach($themes as $theme)
            @php
                $isThemeActive = ((string)($activeThemeId ?? '') === (string)$theme->id) || (bool)$theme->is_active;
            @endphp
            <div class="kt-card h-full">
                {{-- Screenshot / preview van het thema --}}
                <div class="aspect-video w-full bg-muted flex items-center justify-center overflow-hidden rounded-t-[calc(var(--radius)+4px)]">
                    @if($theme->preview_path && file_exists(public_path($theme->preview_path)))
                        @php
                            $previewSrc = str_ends_with(strtolower($theme->preview_path), '.svg')
                                ? route('admin.frontend-themes.preview', ['path' => $theme->preview_path])
                                : asset($theme->preview_path);
                        @endphp
                        <img src="{{ $previewSrc }}"
                             alt="Voorbeeld {{ $theme->name }}"
                             class="w-full h-full object-cover object-top">
                    @else
                        <div class="text-muted-foreground text-sm p-4 text-center">
                            <i class="ki-filled ki-picture text-4xl block mb-2 opacity-50"></i>
                            Geen screenshot
                        </div>
                    @endif
                </div>
                <div class="kt-card-header flex items-center justify-between px-5 py-5">
                    <h3 class="kt-card-title mb-0">{{ $theme->name }}</h3>
                    @if($isThemeActive)
                        <span class="kt-badge kt-badge-success">Actief</span>
                    @endif
                </div>
                <div class="kt-card-content p-5 flex flex-1 flex-col">
                    <p class="text-sm text-muted-foreground mb-0">{{ $theme->description }}</p>
                    <div class="theme-card-actions mt-auto pt-4 flex w-full items-center {{ $isThemeActive ? 'justify-between' : 'justify-center' }}">
                        @if(!$isThemeActive)
                            <form action="{{ route('admin.frontend-themes.set-active', $theme) }}" method="POST" class="inline-flex">
                                @csrf
                                <button type="submit" class="theme-card-icon theme-card-icon--activate" data-tooltip="Activeren" aria-label="Thema activeren">
                                    <i class="ki-filled ki-check-circle" aria-hidden="true"></i>
                                </button>
                            </form>
                        @else
                            <a href="{{ $themeStagingUrls[$theme->id] ?? route('admin.frontend-themes.staging', ['theme_id' => $theme->id]) }}" target="_blank" rel="noopener noreferrer" class="theme-card-icon theme-card-icon--preview" data-tooltip="Website tonen" aria-label="Website van dit thema tonen">
                                <i class="ki-filled ki-eye" aria-hidden="true"></i>
                            </a>
                            <form action="{{ route('admin.frontend-themes.unpublish') }}" method="POST" class="inline-flex" onsubmit="return confirm('Thema de-publiceren? Tenants met dit thema tonen Coming soon tot een ander thema is gekozen.');">
                                @csrf
                                <input type="hidden" name="theme_id" value="{{ $theme->id }}">
                                <button type="submit" class="theme-card-icon theme-card-icon--unpublish" data-tooltip="De-publiceren" aria-label="Thema de-publiceren">
                                    <i class="ki-filled ki-cross-circle" aria-hidden="true"></i>
                                </button>
                            </form>
                            <a href="{{ route('admin.frontend-themes.edit', $theme) }}" class="theme-card-icon" data-tooltip="Instellingen" aria-label="Thema-instellingen">
                                <i class="ki-filled ki-setting-2" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
            <h2 class="text-lg font-medium mb-3">Thema per tenant</h2>
            <p class="text-sm text-muted-foreground mb-4">Elke tenant kan een eigen website-thema hebben. Alleen geactiveerde thema's zijn kiesbaar. Je kunt dit ook wijzigen bij Bedrijf bewerken.</p>
            <div class="kt-card">
                <div class="kt-card-table kt-scrollable-x-auto">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm">
                        <thead>
                            <tr>
                                <th class="text-secondary-foreground font-normal text-left">Tenant</th>
                                <th class="text-secondary-foreground font-normal text-left">Website-thema</th>
                                <th class="text-secondary-foreground font-normal text-left w-40"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($companies ?? [] as $company)
                                @php
                                    $currentThemeId = $company->frontend_theme_id;
                                @endphp
                                <tr>
                                    <td class="font-medium">
                                        {{ $company->name }}
                                        @if(! $company->is_active)
                                            <span class="kt-badge kt-badge-outline text-muted-foreground ms-1">Inactief</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.frontend-themes.update-company-theme') }}" method="POST" class="inline-flex flex-wrap items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="company_id" value="{{ $company->id }}">
                                            <select name="frontend_theme_id" class="kt-input text-sm w-56">
                                                <option value="" {{ ! $currentThemeId ? 'selected' : '' }}>— Geen thema —</option>
                                                @foreach($themes as $theme)
                                                    @if($theme->is_active || (string) $currentThemeId === (string) $theme->id)
                                                        <option value="{{ $theme->id }}" {{ (string) $currentThemeId === (string) $theme->id ? 'selected' : '' }}>
                                                            {{ $theme->name }}{{ $theme->is_active ? '' : ' (niet actief)' }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">Opslaan</button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.companies.edit', $company) }}" class="kt-btn kt-btn-sm kt-btn-ghost">Bedrijf bewerken</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted-foreground">Nog geen tenants. Maak eerst een bedrijf aan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</div>
@endsection
