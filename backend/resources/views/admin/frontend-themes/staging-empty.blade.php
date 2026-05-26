@extends('admin.layouts.app')

@section('title', 'Staging – Geen pagina\'s')

@section('content')
<div class="kt-container-fixed">
    <div class="kt-card max-w-xl mx-auto mt-8">
        <div class="kt-card-header">
            <h2 class="kt-card-title">Staging</h2>
        </div>
        <div class="kt-card-content">
            <p class="text-muted-foreground mb-4">Thema: <strong>{{ $theme->name }}</strong>@if($module) · Module: <strong>{{ $module }}</strong>@endif</p>
            <p class="text-muted-foreground mb-6">Er zijn nog geen actieve website-pagina's voor deze module. Maak eerst pagina's aan bij Website-pagina's en koppel ze aan de module.</p>
            <a href="{{ $backUrl }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug naar thema's
            </a>
        </div>
    </div>
</div>
@endsection
