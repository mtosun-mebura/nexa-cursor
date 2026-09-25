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
        @php
            $archivedCustomers = $archivedCustomers ?? collect();
            $hasActiveCustomers = $customers->count() > 0;
            $hasArchivedCustomers = $archivedCustomers->count() > 0;
        @endphp
        <div class="kt-card kt-card-grid w-full min-w-0" id="transport-customers-card" data-customers-view="contracts" data-has-active="{{ $hasActiveCustomers ? '1' : '0' }}" data-has-archived="{{ $hasArchivedCustomers ? '1' : '0' }}">
            <div class="kt-card-header px-5 py-5 flex-wrap gap-2 min-w-0">
                <h3 class="kt-card-title text-sm pb-0 mb-0">
                    <span data-admin-datatable-info="true">Toon 1 tot {{ $customers->count() }} van {{ $customers->count() }} klant{{ $customers->count() !== 1 ? 'en' : '' }}</span>
                </h3>
                <div class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2.5 w-full sm:w-auto min-w-0 items-stretch sm:items-center sm:ms-auto">
                    <div class="transport-customers-view-toggle inline-flex items-center rounded-lg border border-border p-0.5 shrink-0" role="tablist" aria-label="Weergave">
                        <button type="button" class="transport-customers-view-btn" data-customers-view="table" role="tab" aria-selected="false">Lijst</button>
                        <button type="button" class="transport-customers-view-btn is-active" data-customers-view="contracts" role="tab" aria-selected="true">Contracten</button>
                        <button type="button" class="transport-customers-view-btn" data-customers-view="archive" role="tab" aria-selected="false">Archief</button>
                    </div>
                    <div class="transport-customers-active-filters flex flex-col sm:flex-row flex-wrap gap-2.5 w-full sm:w-auto min-w-0 items-stretch sm:items-center" data-customers-active-filters>
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
            </div>
            <div class="kt-card-content p-0 min-w-0">
                @if($hasActiveCustomers || $hasArchivedCustomers)
                <div class="grid w-full min-w-0"
                     data-admin-datatable="true"
                     data-admin-datatable-page-size="10"
                     id="transport_customers_table"
                     data-admin-datatable-label="klanten"
                     data-admin-datatable-on-page="initTransportCustomerTablePage">
                    <div class="transport-customers-table-wrap min-w-0" data-customers-pane="table" @if(! $hasActiveCustomers) hidden @endif>
                        @if($hasActiveCustomers)
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
                                                        @can('rides.delete')
                                                        <div class="kt-menu-item">
                                                            <button type="button"
                                                                    class="kt-menu-link w-full text-left"
                                                                    data-transport-customer-delete
                                                                    data-delete-mode="archive"
                                                                    data-action="{{ route('admin.taxi.transport_customers.destroy', $customer->id) }}"
                                                                    data-label="{{ $customer->name }}">
                                                                <span class="kt-menu-icon text-destructive">
                                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                                                </span>
                                                                <span class="kt-menu-title text-destructive">Naar archief</span>
                                                            </button>
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
                        @else
                        <div class="px-5 py-5">
                            <p class="py-8 text-center text-muted-foreground text-sm mb-0" data-customers-table-none>
                                Geen contracten.
                            </p>
                        </div>
                        @endif
                    </div>
                    <div class="transport-customers-contracts px-5 py-5" data-customers-pane="contracts">
                        @if($hasActiveCustomers)
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
                                    'forceDelete' => false,
                                ])
                            @endforeach
                        </div>
                        <p id="transport-customers-contracts-empty" class="py-8 text-center text-muted-foreground text-sm" hidden>
                            Geen contracten voor deze filters.
                        </p>
                        @else
                        <p class="py-8 text-center text-muted-foreground text-sm mb-0" data-customers-contracts-none>
                            Geen contracten.
                        </p>
                        @endif
                    </div>
                    <div class="kt-card-footer admin-datatable-footer text-secondary-foreground text-sm font-medium pt-5 min-w-0 px-5 pb-5" data-customers-active-footer>
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
                <div class="transport-customers-archive px-5 py-5" data-customers-pane="archive" hidden>
                    @if($hasArchivedCustomers)
                        <p class="text-sm text-muted-foreground mb-4">
                            Gearchiveerde contracten blijven bewaard voor facturatiehistorie. Definitief verwijderen wist alle gekoppelde gegevens, inclusief abonnementen en passagiers.
                        </p>
                        <div class="transport-customers-contracts__grid" id="transport-customers-archive">
                            @foreach($archivedCustomers as $customer)
                                @php
                                    $customerContracts = ($contractsByCustomer ?? collect())->get($customer->id)
                                        ?? ($contractsByCustomer ?? collect())->get((string) $customer->id);
                                @endphp
                                @include('taxi::admin.transport_customers.partials.contract-document-card', [
                                    'customer' => $customer,
                                    'carrierCompany' => ($companiesById ?? collect())->get($customer->company_id) ?? ($tenantCompany ?? null),
                                    'latestContract' => $customerContracts instanceof \Illuminate\Support\Collection ? $customerContracts->first() : null,
                                    'forceDelete' => true,
                                ])
                            @endforeach
                        </div>
                    @else
                        <p class="py-8 text-center text-muted-foreground text-sm mb-0">
                            Geen gearchiveerde contracten.
                        </p>
                    @endif
                </div>
                @else
                <div class="transport-customers-table-wrap min-w-0 px-5 py-5" data-customers-pane="table" hidden>
                    <p class="py-8 text-center text-muted-foreground text-sm mb-0">
                        Geen contracten.
                    </p>
                </div>
                <div class="transport-customers-contracts px-5 py-5" data-customers-pane="contracts">
                    <p class="py-8 text-center text-muted-foreground text-sm mb-0">
                        Geen contracten.
                    </p>
                </div>
                <div class="transport-customers-archive px-5 py-5" data-customers-pane="archive" hidden>
                    <p class="py-8 text-center text-muted-foreground text-sm mb-0">
                        Geen gearchiveerde contracten.
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
    @can('rides.delete')
    <form id="transport-customer-delete-form" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
    @endcan
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
        align-items: start;
    }

    /* Acties staan onder de kaart; card-content mag ze niet clippen. */
    #transport-customers-card > .kt-card-content {
        overflow: visible;
    }
    .transport-contract-doc-wrap {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
        height: auto;
        min-height: 0;
    }
    .transport-contract-doc-wrap[hidden] {
        display: none !important;
    }
    .transport-contract-doc__actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: start;
        justify-items: center;
        gap: 0.35rem 0.5rem;
        flex: 0 0 auto;
        width: 100%;
        max-width: 22rem;
        margin-inline: auto;
        padding: 0.35rem 0.15rem 0.55rem;
        position: relative;
        z-index: 2;
    }
    .transport-contract-doc__actions:has(> .transport-contract-doc__action-col:only-child) {
        grid-template-columns: 1fr;
        max-width: 8rem;
    }
    .transport-contract-doc__action-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        gap: 0.35rem;
        min-width: 0;
        width: 100%;
        text-align: center;
    }
    .transport-contract-doc__keep-past {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
        cursor: pointer;
        min-height: 2.125rem;
    }
    .transport-contract-doc__action-label {
        font-size: 0.68rem;
        font-weight: 600;
        line-height: 1.2;
        color: var(--muted-foreground, #64748b);
        white-space: nowrap;
    }
    .transport-contract-doc__restore-form {
        margin: 0;
        line-height: 1;
    }
    .transport-contract-doc__action-btn {
        appearance: none;
        border: 0;
        background: transparent !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.125rem;
        height: 2.125rem;
        margin: 0;
        padding: 0;
        cursor: pointer;
        box-shadow: none !important;
        transition: color 0.15s ease, transform 0.15s ease;
    }
    .transport-contract-doc__action-btn i {
        font-size: 1.15rem;
        line-height: 1;
        color: inherit;
    }
    .transport-contract-doc__restore {
        color: #0f766e !important;
    }
    .transport-contract-doc__restore:hover,
    .transport-contract-doc__restore:focus-visible {
        color: #0d9488 !important;
        outline: none;
        transform: scale(1.1);
    }
    .transport-contract-doc__delete {
        position: static;
        z-index: 1;
        color: #dc2626 !important;
    }
    .transport-contract-doc-wrap:hover .transport-contract-doc__delete,
    .transport-contract-doc-wrap:focus-within .transport-contract-doc__delete {
        background: transparent !important;
        color: #b91c1c !important;
    }
    .transport-contract-doc__delete:hover,
    .transport-contract-doc__delete:focus-visible {
        background: transparent !important;
        color: #991b1b !important;
        outline: none;
        transform: scale(1.1);
    }
    html.dark .transport-contract-doc__restore {
        color: #2dd4bf !important;
    }
    html.dark .transport-contract-doc__delete {
        color: #f87171 !important;
        background: transparent !important;
    }
    html.dark .transport-contract-doc__delete:hover,
    html.dark .transport-contract-doc__delete:focus-visible {
        color: #ef4444 !important;
    }
    html.dark .transport-contract-doc__action-label {
        color: #94a3b8;
    }

    .transport-contract-doc {
        position: relative;
        display: flex;
        flex-direction: column;
        flex: 0 0 auto;
        width: 100%;
        min-height: 0;
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
    .transport-contract-doc-wrap:hover .transport-contract-doc,
    .transport-contract-doc-wrap:focus-within .transport-contract-doc,
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
    .transport-contract-doc.is-archived .transport-contract-doc__banner {
        background: linear-gradient(90deg, #57534e, #78716c 58%, #a8a29e);
    }
    .transport-contract-doc.is-archived .transport-contract-doc__seal {
        border-color: #78716c;
        box-shadow: inset 0 0 0 3px rgb(120 113 108 / 0.22);
        color: #78716c;
        background: rgb(245 245 244 / 0.7);
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
    document.querySelectorAll('#transport-customers-contracts [data-contract-card]').forEach(function(card) {
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
    var next = view === 'archive' ? 'archive' : (view === 'table' ? 'table' : 'contracts');
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
    var activeFilters = card.querySelector('[data-customers-active-filters]');
    if (activeFilters) {
        activeFilters.hidden = next === 'archive' || card.getAttribute('data-has-active') === '0';
    }
    var activeFooter = card.querySelector('[data-customers-active-footer]');
    if (activeFooter) {
        activeFooter.hidden = next === 'archive' || card.getAttribute('data-has-active') === '0';
    }
    try {
        window.localStorage.setItem('nexa-contractklanten-view', next);
    } catch (e) {}
    syncTransportCustomerContractCards();
}

document.addEventListener('DOMContentLoaded', function() {
    window.initTransportCustomerTablePage();
    // Standaard Contracten; ?view=archive na archief-acties.
    var initialView = 'contracts';
    try {
        var params = new URLSearchParams(window.location.search);
        if (params.get('view') === 'archive') {
            initialView = 'archive';
        }
    } catch (e) {}
    setTransportCustomersView(initialView);
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

    var deleteForm = document.getElementById('transport-customer-delete-form');
    if (deleteForm) {
        document.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-transport-customer-delete]');
            if (!btn) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            var label = btn.getAttribute('data-label') || 'dit contract';
            var action = btn.getAttribute('data-action') || '';
            var mode = btn.getAttribute('data-delete-mode') || 'archive';
            var isForce = mode === 'force';
            var title = isForce ? 'Contract definitief verwijderen' : 'Contract naar archief';
            var confirmLabel = isForce ? 'Definitief verwijderen' : 'Naar archief';
            var message = isForce
                ? ('Weet je zeker dat je het contract van ' + label + ' definitief wilt verwijderen?\n\n'
                    + 'Alles onder dit contract wordt permanent gewist, inclusief abonnementen, passagiers en ritten in planning/agenda. Facturatiehistorie en gekoppelde gegevens gaan verloren. Dit kan niet ongedaan worden gemaakt.')
                : ('Weet je zeker dat je het contract van ' + label + ' naar het archief wilt verplaatsen?\n\n'
                    + 'Het contract verdwijnt uit de actieve lijst. Ritten verdwijnen uit planning en agenda, tenzij je hieronder kiest om verleden ritten te bewaren.');
            var extraHtml = isForce ? '' : (
                '<label class="kt-label flex items-start gap-2 cursor-pointer mb-0">'
                + '<input type="checkbox" class="kt-checkbox mt-0.5" id="admin-confirm-keep-past-rides" value="1">'
                + '<span class="text-sm text-foreground">Verleden ritten zichtbaar houden in planning en agenda '
                + '<span class="text-muted-foreground font-normal">(geen ritten vanaf vandaag)</span></span>'
                + '</label>'
            );
            var runDelete = function (keepPast) {
                deleteForm.action = action;
                var existing = deleteForm.querySelector('input[name="keep_past_rides"]');
                if (existing) {
                    existing.remove();
                }
                if (!isForce) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'keep_past_rides';
                    input.value = keepPast ? '1' : '0';
                    deleteForm.appendChild(input);
                }
                deleteForm.submit();
            };
            if (typeof window.showAdminConfirm === 'function') {
                window.showAdminConfirm({
                    title: title,
                    message: message,
                    confirmLabel: confirmLabel,
                    destructive: true,
                    extraHtml: extraHtml
                }).then(function (ok) {
                    if (ok) {
                        var keepPast = !isForce && typeof window.adminConfirmExtraChecked === 'function'
                            && window.adminConfirmExtraChecked('#admin-confirm-keep-past-rides');
                        runDelete(keepPast);
                    }
                });
                return;
            }
            if (window.confirm(message)) {
                runDelete(false);
            }
        });
    }

    document.addEventListener('change', function (event) {
        var input = event.target.closest('[data-archive-keep-past]');
        if (!input) {
            return;
        }
        event.preventDefault();
        var action = input.getAttribute('data-action') || '';
        if (!action) {
            return;
        }
        var previous = !input.checked;
        input.disabled = true;
        var token = document.querySelector('meta[name="csrf-token"]');
        var csrf = token ? token.getAttribute('content') : '';
        var body = new URLSearchParams();
        body.set('_method', 'PUT');
        body.set('keep_past_rides', input.checked ? '1' : '0');
        if (csrf) {
            body.set('_token', csrf);
        }
        fetch(action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            credentials: 'same-origin',
            body: body.toString()
        }).then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok && data && data.ok !== false, data: data };
            }).catch(function () {
                return { ok: false, data: null };
            });
        }).then(function (result) {
            if (!result.ok) {
                input.checked = previous;
            } else if (result.data && typeof result.data.keep_past_rides === 'boolean') {
                input.checked = result.data.keep_past_rides;
            }
        }).catch(function () {
            input.checked = previous;
        }).finally(function () {
            input.disabled = false;
        });
    });
});
</script>
@endpush
