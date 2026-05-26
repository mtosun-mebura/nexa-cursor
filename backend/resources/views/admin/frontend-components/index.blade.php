@extends('admin.layouts.app')

@section('title', 'Componenten')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Front-end Componenten</h1>
            @if(!empty($activeModuleName))
                <p class="text-sm text-muted-foreground mt-1">Alle componenten uit de registry. Actieve module: <strong>{{ $activeModuleName }}</strong></p>
            @endif
        </div>
    </div>
    <p class="text-sm text-muted-foreground mb-6">Deze componenten worden automatisch door de applicatie gezien en kunnen op website-pagina's (bijv. home) worden toegevoegd via de sectie-editor. Aanpassen van een component kan alleen in de code, niet via beheer.</p>

    @forelse($grouped as $moduleName => $components)
    <div class="kt-card mb-6">
        <div class="kt-card-header flex items-center justify-between gap-2">
            <h2 class="kt-card-title text-base">{{ $moduleName }}</h2>
        </div>
        <div class="kt-card-table p-4">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border text-left">
                            <th class="pb-3 font-medium text-foreground">Component</th>
                            <th class="pb-3 font-medium text-foreground">Omschrijving</th>
                            <th class="pb-3 font-medium text-foreground text-center">Demo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($components as $c)
                        <tr class="border-b border-border last:border-0">
                            <td class="py-3 text-foreground font-medium">{{ $c->name }}</td>
                            <td class="py-3 text-muted-foreground max-w-md">{{ $c->description ?? '—' }}</td>
                            <td class="py-3 text-center">
                                <a href="{{ route('admin.frontend-components.demo', ['componentId' => $c->id]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-muted-foreground hover:text-primary transition-colors" aria-label="Demo bekijken">
                                    <i class="ki-filled ki-eye text-base"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @empty
    <div class="kt-card p-8 text-center text-muted-foreground">
        <p>Er zijn geen componenten geregistreerd. Voeg ze toe in <code class="text-foreground">config/frontend_components.php</code>.</p>
    </div>
    @endforelse
</div>
@endsection
