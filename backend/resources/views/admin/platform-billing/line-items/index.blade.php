@extends('admin.layouts.app')

@section('title', 'NEXA-factuurregels')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">NEXA-factuurregels</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Generieke regels voor NEXA-facturen
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.platform-billing.line-items.create') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuwe regel
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header py-5 flex-wrap gap-3 justify-between items-center">
            <h3 class="kt-card-title text-sm mb-0">Factuurregels</h3>
            <form method="GET" action="{{ route('admin.platform-billing.line-items.index') }}" class="admin-filter-panel flex flex-wrap items-center gap-2">
                <label class="kt-input w-full sm:w-56 min-w-0">
                    <i class="ki-filled ki-magnifier"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Zoeken…" class="min-w-0" autocomplete="off">
                </label>
                <select class="kt-select w-full sm:w-40" name="is_active">
                    <option value="" @selected(request('is_active', '') === '')>Alle statussen</option>
                    <option value="1" @selected(request('is_active') === '1')>Actief</option>
                    <option value="0" @selected(request('is_active') === '0')>Inactief</option>
                </select>
            </form>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap admin-desktop-table-wrap min-w-0">
                <table id="platform-billing-line-items-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                    <colgroup>
                        <col class="platform-billing-line-items-col-name">
                        <col class="platform-billing-line-items-col-description">
                        <col class="platform-billing-line-items-col-price">
                        <col class="platform-billing-line-items-col-active">
                        <col class="admin-table__actions-col">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-secondary-foreground font-normal text-left">Naam</th>
                            <th class="text-secondary-foreground font-normal text-left">Omschrijving</th>
                            <th class="platform-billing-line-items__price-col text-secondary-foreground font-normal text-left">
                                <span class="block leading-tight">Prijs</span>
                                <span class="block text-xs leading-tight">excl. BTW</span>
                            </th>
                            <th class="text-secondary-foreground font-normal text-left">Actief</th>
                            <th class="admin-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($lineItems as $lineItem)
                        <tr data-row-href="{{ route('admin.platform-billing.line-items.edit', $lineItem) }}">
                            <td class="platform-billing-line-items__name font-medium text-mono">{{ $lineItem->name }}</td>
                            <td class="platform-billing-line-items__description text-secondary-foreground" @if($lineItem->description) title="{{ $lineItem->description }}" @endif>
                                {{ $lineItem->description ?: '—' }}
                            </td>
                            <td class="platform-billing-line-items__price whitespace-nowrap tabular-nums">€ {{ number_format((float) $lineItem->unit_price, 2, ',', '.') }}</td>
                            <td class="platform-billing-line-items__active whitespace-nowrap">{{ $lineItem->is_active ? 'Ja' : 'Nee' }}</td>
                            <td class="admin-table__actions-col" data-no-row-link>
                                <div class="kt-menu flex justify-center" data-kt-menu="true">
                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                        <button type="button" class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Acties">
                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                        </button>
                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.platform-billing.line-items.edit', $lineItem) }}">
                                                    <span class="kt-menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                    <span class="kt-menu-title">Bewerken</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <form action="{{ route('admin.platform-billing.line-items.toggle-status', $lineItem) }}"
                                                      method="POST"
                                                      class="block"
                                                      onsubmit="return confirm('Weet je zeker dat je de status wilt wijzigen?')">
                                                    @csrf
                                                    <button type="submit" class="kt-menu-link w-full text-left">
                                                        <span class="kt-menu-icon"><i class="ki-filled {{ $lineItem->is_active ? 'ki-pause' : 'ki-play' }}"></i></span>
                                                        <span class="kt-menu-title">{{ $lineItem->is_active ? 'Deactiveren' : 'Activeren' }}</span>
                                                    </button>
                                                </form>
                                            </div>
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <form action="{{ route('admin.platform-billing.line-items.destroy', $lineItem) }}"
                                                      method="POST"
                                                      class="block"
                                                      onsubmit="return confirm('Weet je zeker dat je deze factuurregel wilt verwijderen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="kt-menu-link w-full text-left text-danger">
                                                        <span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span>
                                                        <span class="kt-menu-title">Verwijderen</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-5 text-secondary-foreground">Geen factuurregels gevonden.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @include('admin.partials.pagination-footer', ['paginator' => $lineItems])
    </div>
</div>
@endsection

@push('styles')
<style>
    #content #platform-billing-line-items-table col.platform-billing-line-items-col-name {
        width: 11rem;
    }

    #content #platform-billing-line-items-table col.platform-billing-line-items-col-price {
        width: 6.5rem;
    }

    #content #platform-billing-line-items-table .platform-billing-line-items__price-col,
    #content #platform-billing-line-items-table .platform-billing-line-items__price {
        width: 6.5rem !important;
        min-width: 6.5rem !important;
        max-width: 6.5rem !important;
        vertical-align: middle;
        padding-inline-end: 0.5rem !important;
    }

    #content #platform-billing-line-items-table .platform-billing-line-items__price {
        white-space: nowrap;
    }

    #content #platform-billing-line-items-table col.platform-billing-line-items-col-active {
        width: 3.75rem;
    }

    #content #platform-billing-line-items-table .platform-billing-line-items__name,
    #content #platform-billing-line-items-table .platform-billing-line-items__description {
        max-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
@endpush
