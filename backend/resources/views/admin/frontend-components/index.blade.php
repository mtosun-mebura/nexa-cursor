@extends('admin.layouts.app')

@section('title', __('Componenten'))

@section('content')
@php
    $componentTotal = $grouped->sum(fn ($items) => $items->count());
@endphp
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('Front-end Componenten') }}</h1>
            <p class="text-sm text-muted-foreground mt-1">{{ __('Platformbreed — geldt voor alle tenants') }}</p>
        </div>
    </div>
            <p class="text-sm text-muted-foreground mb-6">{{ __('Algemene en module-componenten gelden voor alle tenants. Thema-componenten komen uit Landwind, Play Tailwind of Vue Material Kit; u sleept ze in de pagina-editor op elke pagina, ongeacht het gekoppelde thema.') }}</p>

    @if($componentTotal > 0)
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <label class="kt-input w-full max-w-md">
            <i class="ki-outline ki-magnifier"></i>
            <input type="text"
                   id="frontend-components-search"
                   class="grow"
                   placeholder="{{ __('Zoek component...') }}"
                   aria-label="{{ __('Zoek component...') }}"
                   autocomplete="off">
            <button type="button"
                    id="frontend-components-search-clear"
                    class="hidden shrink-0 text-muted-foreground hover:text-foreground"
                    aria-label="{{ __('Zoekveld wissen') }}"
                    hidden>
                <i class="ki-outline ki-cross"></i>
            </button>
        </label>
        <p id="frontend-components-count"
           class="text-sm text-muted-foreground mb-0"
           data-count-template="{{ __(':visible van :total componenten') }}"
           aria-live="polite">{{ __(':visible van :total componenten', ['visible' => $componentTotal, 'total' => $componentTotal]) }}</p>
    </div>
    <div id="frontend-components-empty" class="kt-card mb-6 hidden" hidden>
        <div class="kt-card-content p-5">
            <p id="frontend-components-empty-text"
               class="text-sm text-muted-foreground mb-0"
               data-empty-template="{{ __('Geen componenten gevonden voor ":query".') }}"></p>
        </div>
    </div>
    @endif

    <div id="frontend-components-catalog">
    @forelse($grouped as $moduleName => $components)
    <div class="kt-card mb-6" data-component-card>
        <div class="kt-card-header flex items-center justify-between gap-2 px-5 py-5">
            <h2 class="kt-card-title text-base">{{ str_starts_with((string) $moduleName, 'Thema: ') ? __('Thema:').' '.substr((string) $moduleName, 7) : __($moduleName) }}</h2>
        </div>
        <div class="kt-card-table">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                <table class="kt-table admin-fluid-table w-full text-sm">
                    <colgroup>
                        <col style="width:22%">
                        <col style="width:12%">
                        <col style="width:42%">
                        <col style="width:12%">
                        <col style="width:12%">
                    </colgroup>
                    <thead>
                        <tr class="border-b border-border text-left">
                            <th class="px-5! py-4 font-medium text-foreground bg-transparent!" data-label="{{ __('Component') }}">{{ __('Component') }}</th>
                            <th class="px-5! py-4 font-medium text-foreground whitespace-nowrap bg-transparent!" data-label="{{ __('Thema') }}">{{ __('Thema') }}</th>
                            <th class="px-5! py-4 font-medium text-foreground bg-transparent!" data-label="{{ __('Omschrijving') }}">{{ __('Omschrijving') }}</th>
                            <th class="px-5! py-4 font-medium text-foreground whitespace-nowrap bg-transparent!" data-label="{{ __('Status') }}">{{ __('Status') }}</th>
                            <th class="px-5! py-4 font-medium text-foreground text-center whitespace-nowrap bg-transparent!" data-label="{{ __('Demo') }}">{{ __('Demo') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($components as $c)
                        @php
                            $statusSearch = '';
                            if (empty($c->is_section_type)) {
                                $statusSearch = ! empty($c->disabled)
                                    ? trim(__('Activeren').' '.__('Uitgeschakeld'))
                                    : __('Uitschakelen');
                            }
                            $searchText = trim(implode(' ', array_filter([
                                (string) ($c->name ?? ''),
                                (string) ($c->theme_name ?? ''),
                                ! empty($c->description) ? __($c->description) : '',
                                $statusSearch,
                            ], fn ($part) => $part !== '')));
                        @endphp
                        <tr id="frontend-comp-{{ $c->id }}" data-component-row data-search="{{ $searchText }}" class="border-b border-border last:border-0 {{ !empty($c->disabled) && empty($c->is_section_type) ? 'opacity-70' : '' }}">
                            <td class="px-5! py-4 text-foreground font-medium">{{ $c->name }}</td>
                            <td class="px-5! py-4 whitespace-nowrap">
                                @if(!empty($c->theme_name))
                                    <span class="inline-flex items-center rounded-md bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-900 dark:bg-orange-500/20 dark:text-orange-100">{{ $c->theme_name }}</span>
                                @else
                                    <span class="text-muted-foreground">—</span>
                                @endif
                            </td>
                            <td class="px-5! py-4 text-muted-foreground">{{ ! empty($c->description) ? __($c->description) : '—' }}</td>
                            <td class="px-5! py-4 whitespace-nowrap">
                                @if(empty($c->is_section_type))
                                    <form method="POST" action="{{ route('admin.frontend-components.toggle-disabled') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="component_id" value="{{ $c->id }}">
                                        <input type="hidden" name="disabled" value="{{ !empty($c->disabled) ? '0' : '1' }}">
                                        @if(!empty($c->disabled))
                                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">{{ __('Activeren') }}</button>
                                            <span class="ms-2 inline-flex items-center rounded-md bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">{{ __('Uitgeschakeld') }}</span>
                                        @else
                                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-ghost text-muted-foreground">{{ __('Uitschakelen') }}</button>
                                        @endif
                                    </form>
                                @else
                                    <span class="text-muted-foreground">—</span>
                                @endif
                            </td>
                            <td class="px-5! py-4 text-center whitespace-nowrap">
                                @if(empty($c->is_section_type))
                                <a href="{{ route('admin.frontend-components.demo', ['componentId' => $c->id]) }}" class="js-frontend-component-demo inline-flex items-center text-muted-foreground hover:text-primary transition-colors" aria-label="{{ __('Demo bekijken') }}">
                                    <i class="ki-filled ki-eye text-base"></i>
                                </a>
                                @else
                                <span class="text-muted-foreground">—</span>
                                @endif
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
        <p>{{ __('Er zijn geen componenten geregistreerd. Voeg ze toe in') }} <code class="text-foreground">config/frontend_components.php</code>.</p>
    </div>
    @endforelse
    </div>
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
(function () {
    var input = document.getElementById('frontend-components-search');
    var clearBtn = document.getElementById('frontend-components-search-clear');
    var countEl = document.getElementById('frontend-components-count');
    var emptyEl = document.getElementById('frontend-components-empty');
    var emptyText = document.getElementById('frontend-components-empty-text');
    var catalog = document.getElementById('frontend-components-catalog');
    if (!input || !catalog) return;

    var cards = Array.prototype.slice.call(catalog.querySelectorAll('[data-component-card]'));
    var rows = Array.prototype.slice.call(catalog.querySelectorAll('tbody tr[data-component-row]'));
    var total = rows.length;

    function normalize(value) {
        return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function apply() {
        var query = String(input.value || '').trim();
        var needle = normalize(query);
        var visible = 0;

        cards.forEach(function (card) {
            var cardVisible = 0;
            card.querySelectorAll('tbody tr[data-component-row]').forEach(function (row) {
                var haystack = normalize(row.getAttribute('data-search') || row.textContent);
                var match = needle === '' || haystack.indexOf(needle) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) {
                    cardVisible += 1;
                    visible += 1;
                }
            });
            card.style.display = cardVisible === 0 ? 'none' : '';
        });

        if (countEl) {
            var template = countEl.getAttribute('data-count-template') || ':visible van :total componenten';
            countEl.textContent = template.replace(':visible', String(visible)).replace(':total', String(total));
        }

        var showEmpty = needle !== '' && visible === 0;
        if (emptyEl) {
            emptyEl.hidden = !showEmpty;
            emptyEl.classList.toggle('hidden', !showEmpty);
        }
        if (emptyText && showEmpty) {
            var emptyTemplate = emptyText.getAttribute('data-empty-template') || 'Geen componenten gevonden voor ":query".';
            emptyText.textContent = emptyTemplate.replace(':query', query);
        }
        if (clearBtn) {
            var showClear = query !== '';
            clearBtn.hidden = !showClear;
            clearBtn.classList.toggle('hidden', !showClear);
        }
    }

    input.addEventListener('input', apply);
    input.addEventListener('search', apply);
    input.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (!input.value) return;
        event.preventDefault();
        input.value = '';
        apply();
    });
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            input.value = '';
            apply();
            input.focus();
        });
    }
})();
</script>
@endsection
