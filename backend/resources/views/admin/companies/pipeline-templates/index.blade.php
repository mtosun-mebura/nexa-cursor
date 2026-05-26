@extends('admin.layouts.app')

@section('title', 'Pipeline Templates - ' . $company->name)

@section('content')

<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10 mt-5">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
            <h1 class="text-2xl font-semibold text-mono">Pipeline Templates</h1>
        </div>
        <div class="flex items-center gap-2.5">
            <form action="{{ route('admin.companies.pipeline-templates.create-from-default', $company) }}" method="POST">
                @csrf
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-plus me-2"></i>
                    Nieuw template (van standaard)
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5">
            <div class="kt-alert-content">
                <i class="ki-filled ki-check-circle me-2"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5">
            <div class="kt-alert-content">
                <i class="ki-filled ki-cross-circle me-2"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <div class="kt-card">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Pipeline Templates voor {{ $company->name }}</h3>
        </div>
        <div class="kt-card-content">
            @if($templates->count() > 0)
                <div class="grid grid-cols-1 gap-4">
                    @foreach($templates as $template)
                        <div class="kt-card hover:shadow-lg transition-shadow">
                            <div class="kt-card-content">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h4 class="text-lg font-semibold text-mono">{{ $template->name }}</h4>
                                            @if($template->is_default)
                                                <span class="kt-badge kt-badge-sm kt-badge-success">Standaard</span>
                                            @endif
                                            @if($template->company_id === null)
                                                <span class="kt-badge kt-badge-sm kt-badge-info">Globaal</span>
                                            @endif
                                        </div>
                                        @if($template->description)
                                            <p class="text-sm text-muted-foreground mb-2">{{ $template->description }}</p>
                                        @endif
                                        <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                            <span><i class="ki-filled ki-calendar me-1"></i> Versie {{ $template->version }}</span>
                                            <span><i class="ki-filled ki-list me-1"></i> {{ count($template->stages) }} stappen</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($template->company_id === $company->id || $template->company_id === null)
                                            <a href="{{ route('admin.companies.pipeline-templates.edit', [$company, $template]) }}" class="kt-btn kt-btn-primary">
                                                <i class="ki-filled ki-notepad-edit me-2"></i>
                                                Bewerken
                                            </a>
                                        @else
                                            <span class="text-sm text-muted-foreground">Niet bewerkbaar</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10">
                    <i class="ki-filled ki-information-2 text-4xl text-muted-foreground mb-4"></i>
                    <p class="text-muted-foreground mb-4">Geen pipeline templates gevonden.</p>
                    <form action="{{ route('admin.companies.pipeline-templates.create-from-default', $company) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus me-2"></i>
                            Maak eerste template aan
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection









