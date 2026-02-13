@extends('admin.layouts.app')

@push('styles')
<style>
    /* Actief thema: Website tonen (oranje), Publiceren (groen), Instellingen (grijs) */
    .kt-btn-warning {
        background-color: #ea580c;
        color: white;
        --tw-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        box-shadow: var(--tw-shadow);
    }
    .kt-btn-warning:hover {
        background-color: #c2410c;
        color: white;
    }
    .kt-btn-success {
        background-color: #16a34a;
        color: white;
        --tw-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        box-shadow: var(--tw-shadow);
    }
    .kt-btn-success:hover {
        background-color: #15803d;
        color: white;
    }
    /* Instellingen-knop: lichtgrijs, goed zichtbaar op donkere achtergrond */
    .kt-card-content .kt-btn-ghost.text-muted-foreground,
    a.kt-btn-ghost.text-muted-foreground {
        background-color: #e5e7eb !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
    }
    .kt-card-content .kt-btn-ghost.text-muted-foreground:hover,
    a.kt-btn-ghost.text-muted-foreground:hover {
        background-color: #d1d5db !important;
        color: #1f2937 !important;
        border-color: #9ca3af !important;
    }
    .dark .kt-card-content .kt-btn-ghost.text-muted-foreground,
    .dark a.kt-btn-ghost.text-muted-foreground {
        background-color: #4b5563 !important;
        color: #e5e7eb !important;
        border-color: #6b7280 !important;
    }
    .dark .kt-card-content .kt-btn-ghost.text-muted-foreground:hover,
    .dark a.kt-btn-ghost.text-muted-foreground:hover {
        background-color: #6b7280 !important;
        color: #f3f4f6 !important;
        border-color: #9ca3af !important;
    }

    /* Thema-kaart knoppen iets groter */
    .theme-card-actions .kt-btn {
        min-height: 2.25rem;
        padding: 0.375rem 0.875rem;
        font-size: 0.875rem;
    }
</style>
@endpush

@section('title', "Frontend Thema's")

@section('content')
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
    <p class="text-sm text-muted-foreground mb-5">De onderstaande thema's zijn gratis te gebruiken. Klik op <strong>Activeren</strong> om een thema te gebruiken; het actieve thema bepaalt het uiterlijk van de website. Via <strong>Instellingen</strong> pas je kleur, lettertypen en footertekst aan.</p>

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
            <div class="kt-card overflow-hidden {{ $theme->is_active ? 'ring-2 ring-primary' : '' }}">
                {{-- Screenshot / preview van het thema --}}
                <div class="aspect-video w-full bg-muted flex items-center justify-center overflow-hidden">
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
                <div class="kt-card-header flex items-center justify-between">
                    <h3 class="kt-card-title">{{ $theme->name }}</h3>
                    @if($theme->is_active)
                        <span class="kt-badge kt-badge-success">Actief</span>
                    @endif
                </div>
                <div class="kt-card-content">
                    <p class="text-sm text-muted-foreground mb-4">{{ $theme->description }}</p>
                    <div class="flex flex-wrap gap-2 theme-card-actions">
                        @if(!$theme->is_active)
                            <form action="{{ route('admin.frontend-themes.set-active', $theme) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-primary">Activeren</button>
                            </form>
                        @else
                            <a href="{{ route('admin.frontend-themes.staging', ['theme_id' => $theme->id, 'module' => '']) }}" target="_blank" rel="noopener noreferrer" class="kt-btn kt-btn-sm kt-btn-warning" title="Staging met dit thema">Website tonen</a>
                            <form action="{{ route('admin.frontend-themes.publish') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="theme_id" value="{{ $theme->id }}">
                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-success" title="Thema publiceren en demo-URL's omzetten naar daadwerkelijke URL's">Publiceren</button>
                            </form>
                            <a href="{{ route('admin.frontend-themes.edit', $theme) }}" class="kt-btn kt-btn-sm kt-btn-ghost text-muted-foreground">Instellingen</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(count($installedModules) > 0)
        <div class="mt-8">
            <h2 class="text-lg font-medium mb-3">Thema per module</h2>
            <p class="text-sm text-muted-foreground mb-4">Stel per module in welk thema gebruikt wordt voor website-pagina's van die module. Kernpagina's (geen module) gebruiken het actieve standaardthema hierboven.</p>
            <div class="kt-card">
                <div class="kt-card-table kt-scrollable-x-auto">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm">
                        <thead>
                            <tr>
                                <th class="text-secondary-foreground font-normal text-left">Module</th>
                                <th class="text-secondary-foreground font-normal text-left">Thema voor website-pagina's</th>
                                <th class="text-secondary-foreground font-normal text-left w-40">Website</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($installedModules as $module)
                                    @php
                                    $moduleName = $module->getName();
                                    $moduleModel = $moduleModels[$moduleName] ?? null;
                                    $currentThemeId = $moduleModel?->frontend_theme_id;
                                    $effectiveThemeId = $currentThemeId ?? $activeThemeId;
                                    $moduleStagingUrl = $moduleStagingUrls[$moduleName] ?? null;
                                @endphp
                                <tr>
                                    <td class="font-medium">{{ $module->getDisplayName() }}</td>
                                    <td>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <form action="{{ route('admin.frontend-themes.update-module-theme') }}" method="POST" class="inline-flex items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="module_name" value="{{ $moduleName }}">
                                                <select name="frontend_theme_id" class="kt-input text-sm w-48">
                                                    <option value="" {{ !$effectiveThemeId ? 'selected' : '' }}>— Standaardthema —</option>
                                                    @foreach($themes as $theme)
                                                        <option value="{{ $theme->id }}" {{ (string)$effectiveThemeId === (string)$theme->id ? 'selected' : '' }}>{{ $theme->name }}{{ $theme->is_active ? ' (actief)' : '' }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">Opslaan</button>
                                            </form>
                                            @if($activeThemeId && (string)$currentThemeId !== (string)$activeThemeId)
                                            <form action="{{ route('admin.frontend-themes.update-module-theme') }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="module_name" value="{{ $moduleName }}">
                                                <input type="hidden" name="frontend_theme_id" value="{{ $activeThemeId }}">
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-ghost text-primary">Gebruik actief thema</button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($moduleStagingUrl)
                                        <a href="{{ $moduleStagingUrl }}" target="_blank" rel="noopener noreferrer" class="kt-btn kt-btn-sm kt-btn-outline inline-flex items-center gap-1" title="Staging: thema en pagina's van deze module">
                                            <i class="ki-filled ki-eye text-sm"></i>
                                            Website tonen
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
