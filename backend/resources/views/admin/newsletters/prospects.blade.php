@extends('admin.layouts.app')

@section('title', 'Klantenlijst nieuwsbrief')

@include('admin.partials.ajax-action-button-styles')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Klantenlijst</h1>
        <p class="text-sm text-muted-foreground mt-2 mb-0">
            Zoek bedrijven op Google Places (branche + regio). We bewaren bedrijfsnaam, contactpersoon (voor de aanhef), adres, e-mail, telefoon en website. Afmelden is definitief.
        </p>
        <div class="pt-3 flex w-full items-center justify-between gap-3">
            <a href="{{ route('admin.newsletters.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
            <a href="{{ route('admin.newsletters.send') }}" class="kt-btn kt-btn-primary ms-auto">Naar versturen</a>
        </div>
    </div>

    @if(session('success') || request()->integer('discovered') > 0)
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') ?: request()->integer('discovered').' bedrijven toegevoegd met e-mail, adres en waar mogelijk telefoon.' }}
        </div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-cross-circle" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <i class="ki-filled ki-cross-circle" aria-hidden="true"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-2 mb-5">
        <div class="kt-card">
            <div class="kt-card-header px-5 py-5">
                <h5 class="kt-card-title mb-0">Zoeken op internet</h5>
            </div>
            <div class="kt-card-content p-5">
                <form method="POST" action="{{ route('admin.newsletters.discover') }}" id="discover-form">
                    @csrf
                    <label class="kt-label mb-1" for="branch">Branche</label>
                    <input type="text" id="branch" name="branch" class="kt-input w-full mb-3" value="{{ old('branch', 'taxi') }}" required>
                    <label class="kt-label mb-1" for="discover-query">Zoekterm (optioneel)</label>
                    <input type="text" id="discover-query" name="query" class="kt-input w-full mb-3" value="{{ old('query') }}" placeholder="bijv. loodgieter">
                    <label class="kt-label mb-1" for="discover-city">Plaats</label>
                    <input type="text" id="discover-city" name="city" class="kt-input w-full mb-1" value="{{ old('city') }}" placeholder="bijv. Enschede">
                    <p class="text-xs text-muted-foreground mb-3">Vul een plaats in <em>of</em> kies hieronder provincies. Met een plaats zoeken we binnen de opgegeven straal.</p>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="kt-label mb-1" for="radius_km">Straal (km)</label>
                            <input type="number" id="radius_km" name="radius_km" class="kt-input w-full" min="5" max="100" value="{{ old('radius_km', 50) }}">
                        </div>
                        <div>
                            <label class="kt-label mb-1" for="max_results">Max. bedrijven</label>
                            <input type="number" id="max_results" name="max_results" class="kt-input w-full" min="1" max="500" value="{{ old('max_results', 300) }}">
                        </div>
                    </div>
                    <p class="kt-label mb-1">Provincies</p>
                    <p class="text-xs text-muted-foreground mb-2">Optioneel als je hierboven een plaats hebt ingevuld.</p>
                    <label class="flex items-center gap-2 text-sm font-medium mb-2">
                        <input type="checkbox" id="discover-provinces-all" class="kt-checkbox">
                        Selecteer alle
                    </label>
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        @foreach($provinces as $province)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="provinces[]" value="{{ $province }}" class="kt-checkbox discover-province" {{ in_array($province, old('provinces', []), true) ? 'checked' : '' }}>
                                {{ $province }}
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-muted-foreground mb-3">Google Places zoekt bedrijven in de gekozen provincies, of binnen de straal vanaf de plaats. E-mail komt van de bedrijfswebsite (Hunter alleen als fallback). Contactnaam (voornaam, tussenvoegsel, achternaam) halen we uit de website of Hunter, voor de aanhef. Alleen rijen mét e-mail worden opgeslagen. Grote zoekopdrachten kunnen tot twee minuten duren.</p>
                    <div id="discover-progress"
                         class="mb-4 rounded-lg border border-input bg-muted/30 px-4 py-3"
                         data-list-count="{{ $prospects->count() }}"
                         role="status"
                         aria-live="polite">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs text-muted-foreground mb-1" id="discover-progress-label">Bedrijven op de lijst</p>
                                <p class="text-3xl font-semibold text-mono tabular-nums leading-none mb-0" id="discover-found-count">{{ $prospects->count() }}</p>
                            </div>
                            <div id="discover-elapsed" class="hidden shrink-0 text-right" aria-live="polite">
                                <p class="text-xs text-muted-foreground mb-1">Bezig</p>
                                <p class="text-lg font-semibold text-mono tabular-nums leading-none mb-0" id="discover-elapsed-time">0 min 0 sec</p>
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground mt-2 mb-0 hidden" id="discover-progress-detail"></p>
                    </div>
                    <div id="discover-form-error" class="kt-alert kt-alert-danger mb-3 hidden" role="alert">
                        <i class="ki-filled ki-cross-circle" aria-hidden="true"></i>
                        <span id="discover-form-error-text"></span>
                    </div>
                    <button type="submit" id="discover-btn" class="kt-btn kt-btn-primary admin-ajax-action-btn">
                        @include('admin.partials.ajax-action-spinner')
                        <i class="ki-filled ki-magnifier admin-ajax-action-idle-icon me-2"></i>
                        <span>Zoek bedrijven</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header px-5 py-5">
                <h5 class="kt-card-title mb-0">Handmatig toevoegen</h5>
            </div>
            <div class="kt-card-content p-5">
                <form method="POST" action="{{ route('admin.newsletters.prospects.store') }}">
                    @csrf
                    <label class="kt-label mb-1" for="company_name">Bedrijfsnaam *</label>
                    <input type="text" id="company_name" name="company_name" class="kt-input w-full mb-3" required value="{{ old('company_name') }}">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-3">
                        <div>
                            <label class="kt-label mb-1" for="first_name">Voornaam</label>
                            <input type="text" id="first_name" name="first_name" class="kt-input w-full" value="{{ old('first_name') }}" autocomplete="given-name">
                        </div>
                        <div>
                            <label class="kt-label mb-1" for="middle_name">Tussenvoegsel</label>
                            <input type="text" id="middle_name" name="middle_name" class="kt-input w-full" value="{{ old('middle_name') }}">
                        </div>
                        <div>
                            <label class="kt-label mb-1" for="last_name">Achternaam</label>
                            <input type="text" id="last_name" name="last_name" class="kt-input w-full" value="{{ old('last_name') }}" autocomplete="family-name">
                        </div>
                    </div>
                    <label class="kt-label mb-1" for="email">E-mail *</label>
                    <input type="email" id="email" name="email" class="kt-input w-full mb-3" required value="{{ old('email') }}">
                    <label class="kt-label mb-1" for="phone">Telefoon</label>
                    <input type="text" id="phone" name="phone" class="kt-input w-full mb-3" value="{{ old('phone') }}">
                    <label class="kt-label mb-1" for="address">Adres</label>
                    <input type="text" id="address" name="address" class="kt-input w-full mb-3" value="{{ old('address') }}">
                    <label class="kt-label mb-1" for="city">Plaats</label>
                    <input type="text" id="city" name="city" class="kt-input w-full mb-3" value="{{ old('city') }}">
                    <label class="kt-label mb-1" for="province">Provincie</label>
                    <select id="province" name="province" class="kt-select w-full mb-3">
                        <option value="">—</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province }}" @selected(old('province') === $province)>{{ $province }}</option>
                        @endforeach
                    </select>
                    <label class="kt-label mb-1" for="website">Website</label>
                    <input type="text" id="website" name="website" class="kt-input w-full mb-4" value="{{ old('website') }}">
                    <button type="submit" class="kt-btn kt-btn-outline">Toevoegen aan opt-in</button>
                </form>
            </div>
        </div>
    </div>

    <div class="kt-card kt-card-grid w-full min-w-0">
        @if($prospects->isEmpty())
            <div class="kt-card-header px-5 py-5">
                <h5 class="kt-card-title mb-0">{{ $subscribedCount }} adressen klaar om te versturen</h5>
            </div>
            <div class="kt-card-content p-5">
                <p class="text-sm text-muted-foreground mb-0">Nog geen bedrijven. Zoek eerst op branche en provincie.</p>
            </div>
        @else
            @php
                $sourceLegend = [
                    'google_places' => ['label' => 'Google Places', 'icon' => 'ki-google'],
                    'ai_web' => ['label' => 'AI-zoeken', 'icon' => 'ki-technology-4'],
                    'hunter' => ['label' => 'Hunter', 'icon' => 'ki-magnifier'],
                    'manual' => ['label' => 'Handmatig', 'icon' => 'ki-pencil'],
                ];
            @endphp
            <div class="kt-card-header py-5 px-5 flex-wrap gap-2 min-w-0">
                <h3 class="kt-card-title text-sm pb-3 w-full mb-0">
                    <span data-admin-datatable-info="true">Toon 1 tot {{ $prospects->count() }} van {{ $prospects->count() }} adressen</span>
                </h3>
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 w-full -mt-1 pb-2">
                    <p class="text-xs text-muted-foreground mb-0">{{ $subscribedCount }} klaar om te versturen</p>
                    <div class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2 text-xs text-muted-foreground ms-auto" role="note" aria-label="Legenda bronnen">
                        <span class="font-medium text-secondary-foreground">Bronnen</span>
                        @foreach($sourceLegend as $meta)
                            <span class="inline-flex items-center gap-1.5">
                                <i class="ki-filled {{ $meta['icon'] }} text-base text-mono" aria-hidden="true"></i>
                                {{ $meta['label'] }}
                            </span>
                        @endforeach
                    </div>
                </div>
                <div class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2.5 w-full min-w-0 items-stretch sm:items-center sm:justify-end"
                     data-admin-live-filter="off">
                    <label class="kt-input w-full sm:w-64 min-w-0">
                        <i class="ki-filled ki-magnifier"></i>
                        <input placeholder="Zoek op bedrijf, contact, e-mail, telefoon…"
                               type="text"
                               name="search"
                               autocomplete="off"
                               data-admin-datatable-search="#newsletter_prospects_table">
                    </label>
                    <select class="kt-select w-full sm:w-52"
                            name="province"
                            data-admin-datatable-filter="province"
                            data-kt-select="true"
                            data-kt-select-placeholder="Selecteer een provincie"
                            aria-label="Selecteer een provincie">
                        <option value="">Alle provincies</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province }}">{{ $province }}</option>
                        @endforeach
                    </select>
                    <select class="kt-select w-full sm:w-48"
                            name="status"
                            data-admin-datatable-filter="status"
                            data-kt-select="true"
                            data-kt-select-placeholder="Selecteer een status"
                            aria-label="Selecteer een status">
                        <option value="">Alle statussen</option>
                        <option value="subscribed">Opt-in</option>
                        <option value="unsubscribed">Afgemeld</option>
                    </select>
                    <select class="kt-select w-full sm:w-48"
                            name="source"
                            data-admin-datatable-filter="source"
                            data-kt-select="true"
                            data-kt-select-placeholder="Selecteer een bron"
                            aria-label="Selecteer een bron">
                        <option value="">Alle bronnen</option>
                        <option value="google_places">Google Places</option>
                        <option value="ai_web">AI-zoeken</option>
                        <option value="hunter">Hunter</option>
                        <option value="manual">Handmatig</option>
                    </select>
                    <button type="button"
                            data-admin-datatable-reset
                            class="kt-btn kt-btn-outline kt-btn-icon shrink-0 hidden"
                            title="Filters resetten">
                        <i class="ki-filled ki-arrows-circle text-base"></i>
                    </button>
                </div>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="grid w-full min-w-0"
                     data-admin-datatable="true"
                     data-admin-datatable-page-size="25"
                     id="newsletter_prospects_table"
                     data-admin-datatable-label="adressen">
                    <div class="newsletter-prospects-table-wrap min-w-0">
                        <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                            <table id="newsletter-prospects-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                                <thead>
                                    <tr>
                                        <th class="newsletter-prospects-table__company-col text-secondary-foreground font-normal text-left" data-label="Bedrijf">Bedrijf</th>
                                        <th class="text-secondary-foreground font-normal text-left" data-label="E-mail">E-mail</th>
                                        <th class="newsletter-prospects-table__phone-col text-secondary-foreground font-normal text-left" data-label="Telefoon">Telefoon</th>
                                        <th class="newsletter-prospects-table__province-col text-secondary-foreground font-normal text-left" data-label="Provincie">Provincie</th>
                                        <th class="newsletter-prospects-table__source-col text-secondary-foreground font-normal text-center" data-label="Bron">Bron</th>
                                        <th class="newsletter-prospects-table__status-col text-secondary-foreground font-normal text-left" data-label="Status">Status</th>
                                        <th class="newsletter-prospects-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($prospects as $prospect)
                                        @php
                                            $sourceMeta = $sourceLegend[$prospect->source] ?? $sourceLegend['ai_web'];
                                            $sourceLabel = $sourceMeta['label'];
                                            $statusLabel = $prospect->isSubscribed() ? 'Opt-in' : 'Afgemeld';
                                            $searchText = mb_strtolower(implode(' ', array_filter([
                                                $prospect->company_name,
                                                $prospect->contactFullName(),
                                                $prospect->email,
                                                $prospect->phone,
                                                $prospect->address,
                                                $prospect->city,
                                                $prospect->province,
                                                $prospect->website,
                                                $prospect->branch,
                                                $sourceLabel,
                                                $statusLabel,
                                            ])), 'UTF-8');
                                        @endphp
                                        <tr data-province="{{ $prospect->province }}"
                                            data-status="{{ $prospect->status }}"
                                            data-source="{{ $prospect->source }}"
                                            data-search-text="{{ $searchText }}">
                                            <td class="newsletter-prospects-table__company-col">
                                                <div class="font-medium text-mono">{{ $prospect->company_name }}</div>
                                                @if($prospect->contactFullName())
                                                    <div class="text-xs text-mono">{{ $prospect->contactFullName() }}</div>
                                                @endif
                                                @if($prospect->city)
                                                    <div class="text-xs text-muted-foreground">{{ $prospect->city }}</div>
                                                @endif
                                            </td>
                                            <td class="text-secondary-foreground">{{ $prospect->email }}</td>
                                            <td class="newsletter-prospects-table__phone-col text-secondary-foreground">{{ $prospect->phone ?: '—' }}</td>
                                            <td class="newsletter-prospects-table__province-col text-secondary-foreground">{{ $prospect->province ?: '—' }}</td>
                                            <td class="newsletter-prospects-table__source-col text-center">
                                                <i class="ki-filled {{ $sourceMeta['icon'] }} text-lg text-mono"
                                                   title="{{ $sourceLabel }}"
                                                   aria-label="{{ $sourceLabel }}"></i>
                                            </td>
                                            <td class="newsletter-prospects-table__status-col">
                                                <span class="kt-badge {{ $prospect->isSubscribed() ? 'kt-badge-success' : 'kt-badge-secondary' }} kt-badge-sm">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td class="newsletter-prospects-table__actions-col" data-no-row-link onclick="event.stopPropagation();">
                                                @if($prospect->isSubscribed())
                                                    <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                        <div class="kt-menu-item"
                                                             data-kt-menu-item-offset="0, 10px"
                                                             data-kt-menu-item-placement="bottom-end"
                                                             data-kt-menu-item-placement-rtl="bottom-start"
                                                             data-kt-menu-item-toggle="dropdown"
                                                             data-kt-menu-item-trigger="click">
                                                            <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button" aria-label="Acties">
                                                                <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                            </button>
                                                            <div class="kt-menu-dropdown kt-menu-default w-[175px] min-w-[175px]" data-kt-menu-dismiss="true">
                                                                <div class="kt-menu-item">
                                                                    <form method="POST"
                                                                          action="{{ route('admin.newsletters.prospects.unsubscribe', $prospect) }}"
                                                                          onsubmit="return confirm(@json($prospect->company_name.' afmelden van de nieuwsbrief?'));">
                                                                        @csrf
                                                                        <button type="submit" class="kt-menu-link w-full text-left">
                                                                            <span class="kt-menu-icon"><i class="ki-filled ki-cross-circle"></i></span>
                                                                            <span class="kt-menu-title">Afmelden</span>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="kt-card-footer admin-datatable-footer text-secondary-foreground text-sm font-medium pt-5 min-w-0 px-5 pb-5">
                        <div class="admin-datatable-footer__perpage flex flex-wrap items-center gap-2">
                            Toon
                            <select class="kt-select w-24" data-admin-datatable-size="true" data-kt-select="" name="perpage">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            per pagina
                        </div>
                        <div class="admin-datatable-footer__pagination max-w-full overflow-x-auto">
                            <div class="kt-datatable-pagination" data-admin-datatable-pagination="true"></div>
                        </div>
                        <span class="admin-datatable-footer__info" data-admin-datatable-info="true"></span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    #content #newsletter-prospects-table .newsletter-prospects-table__company-col {
        width: 34% !important;
    }
    #content #newsletter-prospects-table .newsletter-prospects-table__phone-col {
        width: 8.75rem !important;
        min-width: 8.75rem !important;
        max-width: 8.75rem !important;
        white-space: nowrap;
        overflow-wrap: normal;
        word-break: normal;
        vertical-align: middle !important;
    }
    #content #newsletter-prospects-table .newsletter-prospects-table__province-col {
        width: 7.75rem !important;
        min-width: 7.75rem !important;
        max-width: 7.75rem !important;
        white-space: nowrap;
        overflow-wrap: normal;
        word-break: normal;
        vertical-align: middle !important;
    }
    #content #newsletter-prospects-table .newsletter-prospects-table__status-col {
        width: 6.25rem !important;
        min-width: 6.25rem !important;
        max-width: 6.25rem !important;
        white-space: nowrap;
        overflow-wrap: normal;
        word-break: normal;
        vertical-align: middle !important;
    }
    #content #newsletter-prospects-table .newsletter-prospects-table__source-col {
        width: 4.25rem !important;
        min-width: 4.25rem !important;
        max-width: 4.25rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
    }
    #content #newsletter-prospects-table .newsletter-prospects-table__actions-col {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
        overflow: visible !important;
    }
    #content #newsletter-prospects-table .newsletter-prospects-table__actions-col .kt-menu {
        display: flex !important;
        justify-content: center !important;
        width: 100%;
        margin-inline: auto;
    }
    .newsletter-prospects-table-wrap td:last-child .kt-menu-dropdown { position: fixed !important; z-index: 99999 !important; }
    .newsletter-prospects-table-wrap .kt-scrollable-x-auto { overflow-x: auto !important; overflow-y: visible !important; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const form = document.getElementById('discover-form');
    const selectAll = document.getElementById('discover-provinces-all');
    const REGION_ERROR = 'Kies minstens één provincie, of vul een plaats in. De straal wordt vanaf die plaats gebruikt.';
    let discoverTimerId = null;

    try {
        const url = new URL(window.location.href);
        if (url.searchParams.has('discover_error') || url.searchParams.has('discovered')) {
            url.searchParams.delete('discover_error');
            url.searchParams.delete('discovered');
            const qs = url.searchParams.toString();
            history.replaceState({}, '', url.pathname + (qs ? '?' + qs : '') + url.hash);
        }
    } catch (e) { /* ignore */ }
    const boxes = function () {
        return form ? form.querySelectorAll('input.discover-province') : [];
    };
    function syncSelectAll() {
        if (!selectAll) return;
        const list = Array.prototype.slice.call(boxes());
        const checked = list.filter(function (box) { return box.checked; }).length;
        selectAll.checked = list.length > 0 && checked === list.length;
        selectAll.indeterminate = checked > 0 && checked < list.length;
    }
    selectAll?.addEventListener('change', function () {
        boxes().forEach(function (box) { box.checked = selectAll.checked; });
        selectAll.indeterminate = false;
    });
    boxes().forEach(function (box) {
        box.addEventListener('change', syncSelectAll);
    });
    syncSelectAll();

    function syncFilterAriaLabels() {
        document.querySelectorAll('.admin-filter-panel [data-kt-select-display]').forEach(function (el) {
            const placeholder = el.querySelector('[data-kt-select-placeholder]');
            const label = (placeholder?.textContent || '').trim();
            if (label && el.getAttribute('aria-label') !== label) {
                el.setAttribute('aria-label', label);
            }
        });
    }
    syncFilterAriaLabels();
    setTimeout(syncFilterAriaLabels, 0);
    setTimeout(syncFilterAriaLabels, 150);
    const filterPanel = document.querySelector('.admin-filter-panel');
    if (filterPanel && window.MutationObserver) {
        new MutationObserver(syncFilterAriaLabels).observe(filterPanel, {
            subtree: true,
            attributes: true,
            attributeFilter: ['aria-label'],
        });
    }

    function formatElapsed(ms) {
        const totalSec = Math.max(0, Math.floor(ms / 1000));
        const minutes = Math.floor(totalSec / 60);
        const seconds = totalSec % 60;
        return minutes + ' min ' + seconds + ' sec';
    }

    function stopDiscoverTimer() {
        if (discoverTimerId) {
            clearInterval(discoverTimerId);
            discoverTimerId = null;
        }
    }

    function startDiscoverTimer() {
        const elapsedWrap = document.getElementById('discover-elapsed');
        const elapsedEl = document.getElementById('discover-elapsed-time');
        stopDiscoverTimer();
        const startedAt = Date.now();
        if (elapsedEl) elapsedEl.textContent = formatElapsed(0);
        if (elapsedWrap) elapsedWrap.classList.remove('hidden');
        discoverTimerId = setInterval(function () {
            if (elapsedEl) elapsedEl.textContent = formatElapsed(Date.now() - startedAt);
        }, 250);
    }

    function discoverHasRegion(formEl) {
        const city = (formEl.querySelector('#discover-city')?.value || '').trim();
        const provinces = formEl.querySelectorAll('input.discover-province:checked').length;
        return city !== '' || provinces > 0;
    }

    function showDiscoverFormError(message, label) {
        const box = document.getElementById('discover-form-error');
        const text = document.getElementById('discover-form-error-text');
        if (text) text.textContent = message;
        if (box) {
            box.classList.remove('hidden');
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        const detailEl = document.getElementById('discover-progress-detail');
        const labelEl = document.getElementById('discover-progress-label');
        if (labelEl) labelEl.textContent = label || 'Zoeken mislukt';
        if (detailEl) {
            detailEl.textContent = message;
            detailEl.classList.remove('hidden');
        }
    }

    function hideDiscoverFormError() {
        const box = document.getElementById('discover-form-error');
        if (box) box.classList.add('hidden');
    }

    function unlockDiscoverForm(formEl, btn) {
        if (btn) {
            btn.classList.remove('is-loading');
            btn.disabled = false;
        }
        formEl.querySelectorAll('input, select, button').forEach(function (el) {
            el.disabled = false;
        });
    }

    function parseDiscoverHttpError(text) {
        try {
            const data = JSON.parse(text);
            if (data && data.message) return String(data.message);
            if (data && data.errors) {
                const first = Object.values(data.errors).flat();
                if (first[0]) return String(first[0]);
            }
        } catch (e) { /* not json */ }
        return 'Zoeken kon niet worden gestart. Controleer of je een plaats of provincie hebt ingevuld.';
    }

    form?.addEventListener('submit', function (event) {
        const btn = document.getElementById('discover-btn');
        if (!discoverHasRegion(form)) {
            event.preventDefault();
            showDiscoverFormError(REGION_ERROR, 'Zoeken niet gestart');
            return;
        }
        hideDiscoverFormError();
        if (!window.fetch || !window.ReadableStream) {
            startDiscoverTimer();
            if (btn) {
                btn.classList.add('is-loading');
                btn.disabled = true;
            }
            return;
        }
        event.preventDefault();
        runDiscoverStream(form, btn);
    });

    function runDiscoverStream(formEl, btn) {
        const progress = document.getElementById('discover-progress');
        const countEl = document.getElementById('discover-found-count');
        const labelEl = document.getElementById('discover-progress-label');
        const detailEl = document.getElementById('discover-progress-detail');
        const listCount = Number(progress?.getAttribute('data-list-count') || '0');
        const csrf = formEl.querySelector('input[name="_token"]')?.value || '';
        const body = new FormData(formEl);

        if (btn) {
            btn.classList.add('is-loading');
            btn.disabled = true;
        }
        formEl.querySelectorAll('input, select, button').forEach(function (el) {
            if (el !== btn && el.type !== 'hidden') {
                el.disabled = true;
            }
        });
        if (labelEl) labelEl.textContent = 'Bezig met zoeken…';
        if (countEl) countEl.textContent = '0';
        if (detailEl) {
            detailEl.textContent = 'Bedrijven worden opgezocht.';
            detailEl.classList.remove('hidden');
        }
        hideDiscoverFormError();
        startDiscoverTimer();

        fetch(formEl.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json, application/x-ndjson',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'X-Discover-Stream': '1',
            },
            body: body,
        }).then(function (response) {
            if (!response.ok) {
                return response.text().then(function (text) {
                    throw new Error(parseDiscoverHttpError(text));
                });
            }
            if (!response.body) {
                throw new Error('Zoeken kon niet worden gestart.');
            }
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let completeEvent = null;

            function applyProgress(event) {
                const found = Number(event.found || 0);
                const saved = Number(event.saved || 0);
                if (countEl) countEl.textContent = String(found);
                if (event.phase === 'starting' || event.phase === 'searching') {
                    if (labelEl) labelEl.textContent = found === 1 ? 'Bedrijf gevonden' : 'Bedrijven gevonden';
                    if (detailEl) detailEl.textContent = 'Google Places zoekt verder…';
                } else if (event.phase === 'enriching') {
                    if (labelEl) labelEl.textContent = found === 1 ? 'Bedrijf gevonden' : 'Bedrijven gevonden';
                    if (detailEl) {
                        detailEl.textContent = saved + ' met e-mail opgeslagen'
                            + (event.skipped ? ', ' + event.skipped + ' overgeslagen' : '')
                            + '.';
                    }
                }
            }

            function pump() {
                return reader.read().then(function (chunk) {
                    if (chunk.done) {
                        const rest = (buffer + decoder.decode()).trim();
                        if (rest) {
                            try {
                                const event = JSON.parse(rest);
                                if (event.type === 'complete') completeEvent = event;
                                else applyProgress(event);
                            } catch (e) { /* ignore */ }
                        }
                        return completeEvent;
                    }
                    buffer += decoder.decode(chunk.value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop() || '';
                    lines.forEach(function (line) {
                        line = line.trim();
                        if (!line) return;
                        try {
                            const event = JSON.parse(line);
                            if (event.type === 'complete') {
                                completeEvent = event;
                                stopDiscoverTimer();
                                if (countEl) countEl.textContent = String(event.found || event.saved || 0);
                                if (labelEl) labelEl.textContent = 'Zoeken afgerond';
                                if (detailEl) {
                                    detailEl.textContent = (event.saved || 0) + ' toegevoegd aan de lijst.';
                                }
                            } else {
                                applyProgress(event);
                            }
                        } catch (e) { /* ignore partial json */ }
                    });
                    return pump();
                });
            }

            return pump();
        }).then(function (event) {
            stopDiscoverTimer();
            if (event && event.redirect) {
                window.location.href = event.redirect;
                return;
            }
            unlockDiscoverForm(formEl, btn);
            if (countEl) countEl.textContent = String(listCount);
            if (event && event.success === false) {
                showDiscoverFormError(event.message || 'Zoeken is mislukt.');
                return;
            }
            showDiscoverFormError('Zoeken kon niet worden afgerond. Controleer of je een plaats of provincie hebt ingevuld.');
        }).catch(function (err) {
            stopDiscoverTimer();
            unlockDiscoverForm(formEl, btn);
            if (countEl) countEl.textContent = String(listCount);
            showDiscoverFormError(err.message || 'Probeer het opnieuw.');
        });
    }
})();
</script>
@endpush
