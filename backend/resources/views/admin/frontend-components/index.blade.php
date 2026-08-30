@extends('admin.layouts.app')

@section('title', 'Componenten')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Front-end Componenten</h1>
            <p class="text-sm text-muted-foreground mt-1">Platformbreed — geldt voor alle tenants</p>
        </div>
    </div>
            <p class="text-sm text-muted-foreground mb-6">Algemene en module-componenten gelden voor alle tenants. Thema-componenten komen uit Landwind, Play Tailwind of Vue Material Kit; u sleept ze in de pagina-editor op elke pagina, ongeacht het gekoppelde thema.</p>

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
                            <th class="pb-3 font-medium text-foreground">Thema</th>
                            <th class="pb-3 font-medium text-foreground">Omschrijving</th>
                            <th class="pb-3 font-medium text-foreground text-center">Demo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($components as $c)
                        <tr id="frontend-comp-{{ $c->id }}" class="border-b border-border last:border-0">
                            <td class="py-3 text-foreground font-medium">{{ $c->name }}</td>
                            <td class="py-3">
                                @if(!empty($c->theme_name))
                                    <span class="inline-flex items-center rounded-md bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-900 dark:bg-orange-500/20 dark:text-orange-100">{{ $c->theme_name }}</span>
                                @else
                                    <span class="text-muted-foreground">—</span>
                                @endif
                            </td>
                            <td class="py-3 text-muted-foreground max-w-md">{{ $c->description ?? '—' }}</td>
                            <td class="py-3 text-center">
                                <a href="{{ route('admin.frontend-components.demo', ['componentId' => $c->id]) }}" class="js-frontend-component-demo inline-flex items-center text-muted-foreground hover:text-primary transition-colors" aria-label="Demo bekijken">
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
<script>
(function () {
    var KEY = 'admin-frontend-components-list-scroll';
    function save() {
        try { sessionStorage.setItem(KEY, String(window.scrollY || window.pageYOffset || 0)); } catch (err) {}
    }
    document.querySelectorAll('.js-frontend-component-demo').forEach(function (link) {
        link.addEventListener('click', save);
    });
    window.addEventListener('pagehide', save);
    function restore() {
        var y = NaN;
        try { y = parseInt(sessionStorage.getItem(KEY) || '', 10); } catch (err) {}
        if (!isNaN(y) && y > 0) {
            window.scrollTo(0, y);
            return;
        }
        var hash = (location.hash || '').replace(/^#/, '');
        if (!hash) return;
        var row = document.getElementById(hash);
        if (row) row.scrollIntoView({ block: 'center' });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restore);
    } else {
        restore();
    }
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) restore();
    });
})();
</script>
@endsection
