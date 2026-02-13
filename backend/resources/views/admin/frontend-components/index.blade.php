@extends('admin.layouts.app')

@section('title', 'Componenten')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Front-end Componenten</h1>
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
                            <th class="pb-3 font-medium text-foreground">ID</th>
                            <th class="pb-3 font-medium text-foreground">Omschrijving</th>
                            <th class="pb-3 font-medium text-foreground">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($components as $c)
                        <tr class="border-b border-border/60 last:border-0">
                            <td class="py-3 text-foreground font-medium">{{ $c->name }}</td>
                            <td class="py-3 font-mono text-muted-foreground text-xs">{{ $c->id }}</td>
                            <td class="py-3 text-muted-foreground max-w-md">{{ $c->description ?? '—' }}</td>
                            <td class="py-3 font-mono text-muted-foreground text-xs">{{ $c->view ?? '—' }}</td>
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
