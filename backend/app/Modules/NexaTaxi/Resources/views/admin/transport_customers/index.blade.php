@extends('admin.layouts.app')

@section('title', 'Contractklanten')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Contractklanten</h1>
        @if(empty($packageDeniedMessage))
            @can('rides.create')
            <a href="{{ route('admin.taxi.transport_customers.create') }}" class="kt-btn kt-btn-primary shrink-0">
                <svg class="w-4 h-4 me-2 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Nieuwe klant
            </a>
            @endcan
        @endif
    </div>

    @if(!empty($packageDeniedMessage))
        <div class="kt-alert kt-alert-warning mb-5" role="alert">
            <i class="ki-filled ki-information me-2"></i>
            {{ $packageDeniedMessage }}
        </div>
    @else
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid w-full min-w-0" id="transport-customers-card" data-customers-view="contracts">
            <div class="kt-card-header px-5 py-5 flex-wrap gap-2 min-w-0">
                <h3 class="kt-card-title text-sm pb-0 mb-0">
                    <span data-admin-datatable-info="true">Toon 1 tot {{ $customers->count() }} van {{ $customers->count() }} klant{{ $customers->count() !== 1 ? 'en' : '' }}</span>
                </h3>
                <div class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2.5 w-full sm:w-auto min-w-0 items-stretch sm:items-center sm:ms-auto">
                    <div class="transport-customers-view-toggle inline-flex items-center rounded-lg border border-border p-0.5 shrink-0" role="tablist" aria-label="Weergave">
                        <button type="button" class="transport-customers-view-btn" data-customers-view="table" role="tab" aria-selected="false">Lijst</button>
                        <button type="button" class="transport-customers-view-btn is-active" data-customers-view="contracts" role="tab" aria-selected="true">Contracten</button>
                    </div>
                    <label class="kt-input w-full sm:w-64 min-w-0">
                        <i class="ki-filled ki-magnifier"></i>
                        <input type="text"
                               name="search"
                               placeholder="Zoek op naam, contact, type..."
                               autocomplete="off"
                               data-admin-datatable-search="#transport_customers_table">
                    </label>
                    <select class="kt-select admin-field-fit"
                            name="active"
                            data-admin-datatable-filter="active"
                            data-kt-select="true">
                        <option value="">Alle statussen</option>
                        <option value="1">Actief</option>
                        <option value="0">Inactief</option>
                    </select>
                    <button type="button"
                            class="kt-btn kt-btn-outline kt-btn-icon shrink-0 hidden"
                            data-admin-datatable-reset
                            title="Filters resetten">
                        <i class="ki-filled ki-arrows-circle text-base"></i>
                    </button>
                </div>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                @if($customers->count() > 0)
                <div class="grid w-full min-w-0"
                     data-admin-datatable="true"
                     data-admin-datatable-page-size="10"
                     id="transport_customers_table"
                     data-admin-datatable-label="klanten"
                     data-admin-datatable-on-page="initTransportCustomerTablePage">
                    <div class="transport-customers-table-wrap min-w-0" data-customers-pane="table" hidden>
                        <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                            <table id="transport-customers-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                                <thead>
                                    <tr>
                                        <th data-label="Naam">Naam</th>
                                        <th data-label="Contact">Contact</th>
                                        <th data-label="Debiteurnr.">Debiteurnr.</th>
                                        <th class="transport-customers-table__status-col" data-label="Status">Status</th>
                                        <th class="transport-customers-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customers as $customer)
                                    @php
                                        $typeLabel = $customer->organizationTypeLabel();
                                        $searchText = mb_strtolower(implode(' ', array_filter([
                                            $customer->name,
                                            $customer->contact_name,
                                            $customer->contact_email,
                                            $customer->contact_phone,
                                            $customer->debtor_number,
                                            $typeLabel,
                                            $customer->billing_city,
                                            $customer->active ? 'actief' : 'inactief',
                                        ])), 'UTF-8');
                                    @endphp
                                    <tr
                                        data-row-href="{{ route('admin.taxi.transport_customers.show', $customer->id) }}"
                                        data-customer-id="{{ $customer->id }}"
                                        data-active="{{ $customer->active ? '1' : '0' }}"
                                        data-search-text="{{ $searchText }}"
                                        class="transport-customers-table__row cursor-pointer hover:bg-muted/40"
                                        tabindex="0"
                                        role="link"
                                        aria-label="Bekijk {{ $customer->name }}"
                                    >
                                        <td>
                                            <span class="font-medium text-foreground">{{ $customer->name }}</span>
                                            <span class="block text-xs text-muted-foreground mt-0.5">{{ $typeLabel }}</span>
                                        </td>
                                        <td class="text-muted-foreground">
                                            @if($customer->contact_name)
                                                {{ $customer->contact_name }}<br>
                                            @endif
                                            {{ $customer->contact_email ?? '—' }}
                                        </td>
                                        <td class="text-muted-foreground">{{ $customer->debtor_number ?? '—' }}</td>
                                        <td class="transport-customers-table__status-col">
                                            @if($customer->active)
                                                <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                            @else
                                                <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
                                            @endif
                                        </td>
                                        <td class="transport-customers-table__actions-col" data-no-row-link onclick="event.stopPropagation();">
                                            <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                <div class="kt-menu-item"
                                                     data-kt-menu-item-offset="0, 10px"
                                                     data-kt-menu-item-placement="bottom-end"
                                                     data-kt-menu-item-placement-rtl="bottom-start"
                                                     data-kt-menu-item-toggle="dropdown"
                                                     data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button" aria-label="Acties">
                                                        <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"/>
                                                        </svg>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-[175px] min-w-[175px]" data-kt-menu-dismiss="true">
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.taxi.transport_customers.show', $customer->id) }}">
                                                                <span class="kt-menu-icon">
                                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                                                </span>
                                                                <span class="kt-menu-title">Bekijken</span>
                                                            </a>
                                                        </div>
                                                        @can('rides.update')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.taxi.transport_customers.edit', $customer->id) }}">
                                                                <span class="kt-menu-icon">
                                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                                                </span>
                                                                <span class="kt-menu-title">Bewerken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="transport-customers-contracts px-5 py-5" data-customers-pane="contracts">
                        <div class="transport-customers-contracts__grid" id="transport-customers-contracts">
                            @foreach($customers as $customer)
                                @php
                                    $customerContracts = ($contractsByCustomer ?? collect())->get($customer->id)
                                        ?? ($contractsByCustomer ?? collect())->get((string) $customer->id);
                                @endphp
                                @include('taxi::admin.transport_customers.partials.contract-document-card', [
                                    'customer' => $customer,
                                    'carrierCompany' => ($companiesById ?? collect())->get($customer->company_id) ?? ($tenantCompany ?? null),
                                    'latestContract' => $customerContracts instanceof \Illuminate\Support\Collection ? $customerContracts->first() : null,
                                ])
                            @endforeach
                        </div>
                        <p id="transport-customers-contracts-empty" class="py-8 text-center text-muted-foreground text-sm" hidden>
                            Geen contracten voor deze filters.
                        </p>
                    </div>
                    <div class="kt-card-footer admin-datatable-footer text-secondary-foreground text-sm font-medium pt-5 min-w-0 px-5 pb-5">
                        <div class="admin-datatable-footer__perpage flex flex-wrap items-center gap-2">
                            Toon
                            <select class="kt-select w-24" data-admin-datatable-size="true" data-kt-select="" name="perpage">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
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
                @else
                <div class="py-10 px-5 text-center text-muted-foreground text-sm">
                    Geen contractklanten gevonden.
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    #content #transport-customers-table .transport-customers-table__status-col {
        width: 6.5rem !important;
        min-width: 6.5rem !important;
        max-width: 6.5rem !important;
        padding-inline: 0.375rem !important;
        white-space: nowrap;
        vertical-align: middle !important;
    }

    #content #transport-customers-table .transport-customers-table__actions-col {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
        overflow: visible !important;
    }

    #content #transport-customers-table .transport-customers-table__actions-col .kt-menu {
        display: flex !important;
        justify-content: center !important;
        width: 100%;
        margin-inline: auto;
    }

    .transport-customers-table-wrap td:last-child .kt-menu-dropdown { position: fixed !important; z-index: 99999 !important; }
    .transport-customers-table-wrap td:last-child .kt-menu-item.show .kt-menu-dropdown,
    .transport-customers-table-wrap td:last-child .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show .kt-menu-dropdown { display: block !important; visibility: visible !important; opacity: 1 !important; }
    .transport-customers-table-wrap td:last-child .kt-menu-item.show { z-index: 99999 !important; }
    .transport-customers-table-wrap .kt-scrollable-x-auto { overflow-x: auto !important; overflow-y: visible !important; }

    .transport-customers-view-btn {
        appearance: none;
        border: 0;
        background: transparent;
        color: var(--muted-foreground, #64748b);
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1.2;
        padding: 0.4rem 0.75rem;
        border-radius: 0.5rem;
        cursor: pointer;
    }
    .transport-customers-view-btn.is-active {
        background: var(--background, #fff);
        color: var(--foreground, #0f172a);
        box-shadow: 0 1px 2px rgb(15 23 42 / 0.08);
    }
    html.dark .transport-customers-view-btn.is-active {
        background: #111827;
        color: #f8fafc;
        box-shadow: 0 1px 2px rgb(0 0 0 / 0.35);
    }

    .transport-customers-contracts__grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 22.5rem), 1fr));
        gap: 1.5rem;
    }

    .transport-contract-doc {
        position: relative;
        display: flex;
        flex-direction: column;
        min-height: 100%;
        text-decoration: none;
        color: inherit;
        background: #f6f1e4;
        border: 1px solid rgb(120 89 48 / 0.22);
        border-radius: 0.2rem 0.85rem 0.85rem 0.85rem;
        overflow: hidden;
        box-shadow:
            0 18px 36px -24px rgb(15 23 42 / 0.55),
            0 1px 0 rgb(255 255 255 / 0.8) inset;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .transport-contract-doc[hidden] {
        display: none !important;
    }
    .transport-contract-doc:hover,
    .transport-contract-doc:focus-visible {
        transform: translateY(-3px);
        box-shadow: 0 22px 38px -20px rgb(15 23 42 / 0.55);
        text-decoration: none;
        color: inherit;
        outline: none;
    }
    .transport-contract-doc__banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.6rem 1rem;
        color: #fff;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }
    .transport-contract-doc__status {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .transport-contract-doc__status-dot {
        width: 0.45rem;
        height: 0.45rem;
        border-radius: 999px;
        background: #fff;
        box-shadow: 0 0 0 3px rgb(255 255 255 / 0.25);
    }
    .transport-contract-doc.is-active .transport-contract-doc__banner {
        background: linear-gradient(90deg, #065f46, #059669 58%, #34d399);
    }
    .transport-contract-doc.is-inactive .transport-contract-doc__banner {
        background: linear-gradient(90deg, #334155, #64748b);
    }
    .transport-contract-doc__ref {
        opacity: 0.9;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: none;
    }
    .transport-contract-doc__body {
        position: relative;
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 1.15rem 1.2rem 1.2rem;
        color: #1e293b;
        background:
            linear-gradient(135deg, rgb(255 255 255 / 0.42), transparent 38%),
            repeating-linear-gradient(0deg, transparent, transparent 27px, rgb(120 89 48 / 0.07) 28px);
    }
    .transport-contract-doc__body::after {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        border-style: solid;
        border-width: 0 1.15rem 1.15rem 0;
        border-color: transparent #efe8d6 transparent transparent;
        filter: drop-shadow(-1px 1px 0 rgb(120 89 48 / 0.12));
        pointer-events: none;
    }
    .transport-contract-doc__letterhead {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.45rem;
        padding-right: 1rem;
    }
    .transport-contract-doc__kicker {
        margin: 0;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #8a6a3b;
    }
    .transport-contract-doc__type {
        display: inline-flex;
        align-items: center;
        padding: 0.18rem 0.55rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        background: #e2e8f0;
        color: #0f172a;
        white-space: nowrap;
    }
    .transport-contract-doc__type--school { background: #dbeafe; color: #1d4ed8; }
    .transport-contract-doc__type--zorg { background: #d1fae5; color: #047857; }
    .transport-contract-doc__type--ziekenhuis { background: #ffe4e6; color: #be123c; }
    .transport-contract-doc__type--zakelijk { background: #ede9fe; color: #6d28d9; }
    .transport-contract-doc__type--prive { background: #ffedd5; color: #c2410c; }
    .transport-contract-doc__type--shuttle { background: #cffafe; color: #0e7490; }
    .transport-contract-doc__title {
        margin: 0 0 0.2rem;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 1.28rem;
        font-weight: 700;
        line-height: 1.25;
        color: #1c1917;
    }
    .transport-contract-doc__subtitle {
        margin: 0 0 0.9rem;
        font-size: 0.78rem;
        color: #57534e;
    }
    .transport-contract-doc__parties {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin: 0 0 0.95rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid rgb(120 89 48 / 0.18);
    }
    .transport-contract-doc__parties dt {
        font-size: 0.64rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: #8a6a3b;
        margin-bottom: 0.18rem;
    }
    .transport-contract-doc__parties dd {
        margin: 0;
        font-size: 0.82rem;
        font-weight: 650;
        color: #1c1917;
    }
    .transport-contract-doc__address,
    .transport-contract-doc__contact p,
    .transport-contract-doc__meta {
        margin: 0;
        font-size: 0.8rem;
        line-height: 1.45;
        color: #44403c;
    }
    .transport-contract-doc__address { margin-bottom: 0.85rem; }
    .transport-contract-doc__section-label {
        margin: 0 0 0.15rem !important;
        font-size: 0.64rem !important;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: #8a6a3b !important;
    }
    .transport-contract-doc__contact { margin-bottom: 0.8rem; }
    .transport-contract-doc__contact-name {
        font-weight: 650;
        color: #1c1917 !important;
    }
    .transport-contract-doc__meta { margin-bottom: 1rem; }
    .transport-contract-doc__sign {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 0.85rem;
        align-items: end;
        margin-top: auto;
        padding-top: 0.55rem;
        border-top: 1px dashed rgb(120 89 48 / 0.28);
    }
    .transport-contract-doc__sign-col {
        display: flex;
        flex-direction: column;
        min-width: 0;
        font-size: 0.68rem;
        color: #78716c;
    }
    .transport-contract-doc__sign-col strong {
        color: #1c1917;
        font-size: 0.75rem;
        font-weight: 650;
        margin-top: 0.1rem;
    }
    .transport-contract-doc__sign-col--seal {
        align-items: flex-end;
        text-align: right;
    }
    .transport-contract-doc__signature {
        width: 7.6rem;
        height: 1.75rem;
        color: #1e3a8a;
        margin-bottom: 0.15rem;
    }
    .transport-contract-doc__seal {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3.55rem;
        height: 3.55rem;
        margin-bottom: 0.25rem;
        border: 2px solid #b45309;
        border-radius: 999px;
        box-shadow: inset 0 0 0 3px rgb(180 83 9 / 0.25);
        color: #b45309;
        font-size: 0.52rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        transform: rotate(-14deg);
        background: rgb(255 247 237 / 0.7);
    }
    .transport-contract-doc.is-inactive .transport-contract-doc__seal {
        border-color: #64748b;
        box-shadow: inset 0 0 0 3px rgb(100 116 139 / 0.22);
        color: #64748b;
        background: rgb(241 245 249 / 0.7);
    }

    html.dark .transport-contract-doc {
        background: #efe8d6;
        border-color: rgb(248 250 252 / 0.08);
    }
</style>
@endpush

@push('scripts')
<script>
function initTransportCustomerRowLinks() {
    document.querySelectorAll('#transport-customers-table tr[data-row-href]').forEach(function(row) {
        if (row._transportCustomerRowBound) return;
        row._transportCustomerRowBound = true;

        row.addEventListener('click', function(event) {
            if (event.target.closest('[data-no-row-link]')) {
                return;
            }
            window.location.href = row.getAttribute('data-row-href');
        });

        row.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                if (event.target.closest('[data-no-row-link]')) {
                    return;
                }
                event.preventDefault();
                window.location.href = row.getAttribute('data-row-href');
            }
        });
    });
}

window.initTransportCustomerTablePage = function() {
    initTransportCustomerRowLinks();
    syncTransportCustomerContractCards();
};

function syncTransportCustomerContractCards() {
    var visible = 0;
    document.querySelectorAll('[data-contract-card]').forEach(function(card) {
        var id = card.getAttribute('data-customer-id');
        var row = document.querySelector('#transport-customers-table tr[data-customer-id="' + id + '"]');
        var show = Boolean(row) && !row.hidden;
        card.hidden = !show;
        if (show) {
            visible += 1;
        }
    });
    var empty = document.getElementById('transport-customers-contracts-empty');
    if (empty) {
        empty.hidden = visible > 0;
    }
}

function setTransportCustomersView(view) {
    var card = document.getElementById('transport-customers-card');
    if (!card) {
        return;
    }
    var next = view === 'contracts' ? 'contracts' : 'table';
    card.setAttribute('data-customers-view', next);
    card.querySelectorAll('[data-customers-pane]').forEach(function(pane) {
        pane.hidden = pane.getAttribute('data-customers-pane') !== next;
    });
    card.querySelectorAll('[data-customers-view]').forEach(function(btn) {
        if (btn.tagName !== 'BUTTON') {
            return;
        }
        var active = btn.getAttribute('data-customers-view') === next;
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    try {
        window.localStorage.setItem('nexa-contractklanten-view', next);
    } catch (e) {}
    syncTransportCustomerContractCards();
}

document.addEventListener('DOMContentLoaded', function() {
    window.initTransportCustomerTablePage();
    var stored = 'contracts';
    try {
        stored = window.localStorage.getItem('nexa-contractklanten-view') || 'contracts';
    } catch (e) {}
    setTransportCustomersView(stored);

    document.querySelectorAll('.transport-customers-view-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            setTransportCustomersView(btn.getAttribute('data-customers-view'));
        });
    });

    var customersTable = document.getElementById('transport_customers_table');
    if (customersTable) {
        customersTable.addEventListener('admin-datatable:rendered', function() {
            syncTransportCustomerContractCards();
        });
    }

    document.addEventListener('click', function(e) {
        if (e.target.closest('.transport-customers-table-wrap .kt-menu')) return;
        document.querySelectorAll('.transport-customers-table-wrap .kt-menu-item.show').forEach(function(item) {
            item.classList.remove('show');
            var d = item.querySelector('.kt-menu-dropdown');
            if (d) d.style.display = 'none';
        });
    });
});
</script>
@endpush
