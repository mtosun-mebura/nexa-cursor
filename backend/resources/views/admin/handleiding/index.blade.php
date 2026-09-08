@extends('admin.layouts.app')

@push('styles')
@include('admin.handleiding.partials.styles')
@endpush

@section('title', 'Handleiding')

@section('content')
<div class="kt-card w-full min-w-0">
    <div class="kt-card-header flex flex-col gap-4 px-5 py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="kt-card-title text-2xl mb-1">Handleiding</h1>
                <p class="text-sm text-muted-foreground mb-0">
                    Stap-voor-stap uitleg van Nexa, met schermvoorbeelden en praktische tips.
                </p>
            </div>
            <span class="kt-badge kt-badge-light shrink-0" data-handleiding-count>{{ count($pages) }} {{ count($pages) === 1 ? 'onderwerp' : 'onderwerpen' }}</span>
        </div>
        <label class="kt-input w-full max-w-xl">
            <i class="ki-filled ki-magnifier"></i>
            <input type="search"
                   data-handleiding-search
                   placeholder="Zoek op onderwerp of trefwoord…"
                   autocomplete="off"
                   aria-label="Zoek in de handleiding">
        </label>
    </div>
    <div class="kt-card-content p-5 lg:p-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" data-handleiding-grid>
            @foreach($pages as $pageSlug => $pageItem)
                @php
                    $searchText = mb_strtolower(trim(implode(' ', array_filter([
                        $pageSlug,
                        $pageItem['title'] ?? '',
                        $pageItem['summary'] ?? '',
                    ]))));
                @endphp
                <a href="{{ route('admin.handleiding.show', $pageSlug) }}"
                   class="group flex flex-col rounded-xl border border-border bg-muted/10 p-5 transition-all hover:border-primary/40 hover:bg-accent/30 hover:shadow-sm"
                   data-handleiding-card
                   data-search-text="{{ $searchText }}">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <i class="ki-filled {{ $pageItem['icon'] ?? 'ki-document' }} text-lg"></i>
                        </span>
                        @if(!empty($pageItem['estimated_minutes']))
                            <span class="text-xs text-muted-foreground ms-auto">± {{ $pageItem['estimated_minutes'] }} min</span>
                        @endif
                    </div>
                    <h2 class="text-base font-semibold text-foreground group-hover:text-primary mb-1">{{ $pageItem['title'] }}</h2>
                    @if(!empty($pageItem['super_admin_only']))
                        <span class="kt-badge kt-badge-light self-start mb-2 text-[10px]">Alleen super-admin</span>
                    @endif
                    <p class="text-sm text-muted-foreground mb-4 grow">{{ $pageItem['summary'] ?? '' }}</p>
                    <span class="text-sm font-medium text-primary inline-flex items-center gap-1">
                        Lezen
                        <i class="ki-filled ki-arrow-right text-xs transition-transform group-hover:translate-x-0.5"></i>
                    </span>
                </a>
            @endforeach
        </div>
        <p class="hidden text-sm text-muted-foreground mb-0" data-handleiding-empty role="status">
            Geen onderwerpen gevonden. Pas de zoekopdracht aan of wis het veld om alles weer te zien.
        </p>

        <div class="handleiding-tip mt-6">
            @if(auth()->user()?->isSuperAdmin())
                <strong class="text-foreground">Tip:</strong> hoofdstukken met het label <strong>Alleen super-admin</strong> ziet u als enige.
                Tenants zien die niet. De overige onderwerpen volgen het pakket van de geselecteerde tenant (of alle onderwerpen als u geen tenant heeft gekozen).
            @else
                <strong class="text-foreground">Tip:</strong> u ziet alleen onderwerpen die bij uw pakket horen.
                Na een upgrade verschijnen extra hoofdstukken automatisch in dit overzicht.
            @endif
        </div>
    </div>
</div>
<script>
(function () {
    var input = document.querySelector('[data-handleiding-search]');
    var grid = document.querySelector('[data-handleiding-grid]');
    var empty = document.querySelector('[data-handleiding-empty]');
    var countBadge = document.querySelector('[data-handleiding-count]');
    if (!input || !grid) {
        return;
    }

    var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-handleiding-card]'));
    var tip = document.querySelector('.handleiding-tip');

    function normalize(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function applyFilter() {
        var query = normalize(input.value);
        var visible = 0;
        cards.forEach(function (card) {
            var haystack = normalize(card.getAttribute('data-search-text') || card.textContent);
            var match = query === '' || haystack.indexOf(query) !== -1;
            card.classList.toggle('hidden', !match);
            if (match) {
                visible += 1;
            }
        });
        if (empty) {
            empty.classList.toggle('hidden', visible !== 0);
        }
        if (countBadge) {
            countBadge.textContent = visible + ' ' + (visible === 1 ? 'onderwerp' : 'onderwerpen');
        }
        grid.classList.toggle('hidden', visible === 0);
        if (tip) {
            tip.classList.toggle('hidden', query !== '');
        }
    }

    input.addEventListener('input', applyFilter);
    input.addEventListener('search', applyFilter);
})();
</script>
@endsection
