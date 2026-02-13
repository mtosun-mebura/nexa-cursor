@extends('admin.layouts.app')

@section('title', 'Setup applicatie')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Setup applicatie</h1>
        <a href="{{ route('admin.frontend-themes.index') }}" class="kt-btn kt-btn-outline">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug naar Thema's
        </a>
    </div>
    <p class="text-sm text-muted-foreground mb-5">Overzicht van de technische setup van de applicatie.</p>

    <div class="kt-card max-w-2xl">
        <div class="kt-card-header">
            <h2 class="kt-card-title">Omgeving</h2>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto">
            <table class="kt-table kt-table-border-dashed align-middle text-sm">
                <tbody>
                    <tr>
                        <td class="min-w-48 text-secondary-foreground font-normal">PHP-versie</td>
                        <td><code class="text-sm bg-muted px-1.5 py-0.5 rounded">{{ $setup['php_version'] }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Laravel-versie</td>
                        <td><code class="text-sm bg-muted px-1.5 py-0.5 rounded">{{ $setup['laravel_version'] }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">APP_ENV</td>
                        <td><code class="text-sm bg-muted px-1.5 py-0.5 rounded">{{ $setup['app_env'] }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">APP_DEBUG</td>
                        <td><code class="text-sm bg-muted px-1.5 py-0.5 rounded">{{ $setup['app_debug'] ? 'true' : 'false' }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">APP_URL</td>
                        <td><code class="text-sm bg-muted px-1.5 py-0.5 rounded break-all">{{ $setup['app_url'] }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Actief thema</td>
                        <td><strong>{{ $setup['active_theme'] }}</strong>@if($setup['theme_slug']) <span class="text-muted-foreground">({{ $setup['theme_slug'] }})</span>@endif</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="kt-card max-w-2xl mt-5">
        <div class="kt-card-header">
            <h2 class="kt-card-title">PHP-extensies</h2>
        </div>
        <div class="kt-card-content">
            <p class="text-sm text-muted-foreground mb-3">Geladen extensies (alfabetisch):</p>
            <div class="flex flex-wrap gap-2">
                @foreach($setup['extensions'] as $ext)
                    <span class="inline-block px-2 py-1 text-xs font-mono bg-muted rounded">{{ $ext }}</span>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
