@extends('admin.layouts.app')

@section('title', __("Staging – Geen pagina's"))

@section('content')
<div class="kt-container-fixed">
    <div class="kt-card max-w-xl mx-auto mt-8">
        <div class="kt-card-header">
            <h2 class="kt-card-title">{{ __('Staging') }}</h2>
        </div>
        <div class="kt-card-content">
            <p class="text-muted-foreground mb-4">{{ __('Thema:') }} <strong>{{ $theme->name }}</strong>@if($module) · {{ __('Module:') }} <strong>{{ $module }}</strong>@endif</p>
            <p class="text-muted-foreground mb-6">{!! __("Er zijn nog geen actieve website-pagina's voor deze module. Maak eerst pagina's aan bij Website-pagina's en koppel ze aan de module.") !!}</p>
            <a href="{{ $backUrl }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-arrow-left me-2"></i>
                {!! __("Terug naar thema's") !!}
            </a>
        </div>
    </div>
</div>
@endsection
