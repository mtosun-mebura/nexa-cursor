@extends('admin.layouts.app')

@section('title', 'SaaS-pakketten')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                SaaS-pakketten
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Vaste abonnementspakketten voor tenants
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.platform-billing.packages.create') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuw pakket
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
            <h3 class="kt-card-title text-sm mb-0">
                Pakketten
            </h3>
            <form method="GET" action="{{ route('admin.platform-billing.packages.index') }}" class="admin-filter-panel flex flex-wrap items-center gap-2">
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
                <table id="platform-billing-packages-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full platform-billing-packages-table">
                    <colgroup>
                        <col class="platform-billing-packages-col-name">
                        <col class="platform-billing-packages-col-description">
                        <col class="platform-billing-packages-col-price">
                        <col class="platform-billing-packages-col-active">
                        <col class="admin-table__actions-col">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-secondary-foreground font-normal text-left">Naam</th>
                            <th class="text-secondary-foreground font-normal text-left">Omschrijving</th>
                            <th class="text-secondary-foreground font-normal text-left">Maandprijs</th>
                            <th class="text-secondary-foreground font-normal text-left">Actief</th>
                            <th class="admin-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($packages as $package)
                        <tr class="platform-billing-package-row" data-row-href="{{ route('admin.platform-billing.packages.show', $package) }}">
                            <td class="platform-billing-packages__name font-medium text-mono">{{ $package->name }}</td>
                            <td class="platform-billing-packages__description text-secondary-foreground" @if($package->description) title="{{ $package->description }}" @endif>
                                {{ $package->description ?: '—' }}
                            </td>
                            <td class="platform-billing-packages__price whitespace-nowrap tabular-nums">€ {{ number_format((float) $package->monthly_amount, 2, ',', '.') }}</td>
                            <td class="platform-billing-packages__active whitespace-nowrap">{{ $package->is_active ? 'Ja' : 'Nee' }}</td>
                            <td class="admin-table__actions-col" data-no-row-link>
                                <div class="kt-menu flex justify-center" data-kt-menu="true">
                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                        <button type="button" class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Acties">
                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                        </button>
                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.platform-billing.packages.show', $package) }}">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-eye"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Bekijken</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.platform-billing.packages.edit', $package) }}">
                                                    <span class="kt-menu-icon">
                                                        <i class="ki-filled ki-pencil"></i>
                                                    </span>
                                                    <span class="kt-menu-title">Bewerken</span>
                                                </a>
                                            </div>
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <form action="{{ route('admin.platform-billing.packages.toggle-status', $package) }}"
                                                      method="POST"
                                                      class="block"
                                                      onsubmit="return confirm('Weet je zeker dat je de status wilt wijzigen?')">
                                                    @csrf
                                                    <button type="submit" class="kt-menu-link w-full text-left">
                                                        <span class="kt-menu-icon">
                                                            <i class="ki-filled {{ $package->is_active ? 'ki-pause' : 'ki-play' }}"></i>
                                                        </span>
                                                        <span class="kt-menu-title">{{ $package->is_active ? 'Deactiveren' : 'Activeren' }}</span>
                                                    </button>
                                                </form>
                                            </div>
                                            <div class="kt-menu-separator"></div>
                                            <div class="kt-menu-item">
                                                <form action="{{ route('admin.platform-billing.packages.destroy', $package) }}"
                                                      method="POST"
                                                      class="block"
                                                      onsubmit="return confirm('Weet je zeker dat je dit pakket wilt verwijderen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="kt-menu-link w-full text-left text-danger">
                                                        <span class="kt-menu-icon">
                                                            <i class="ki-filled ki-trash"></i>
                                                        </span>
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
                            <td colspan="5" class="p-5 text-secondary-foreground">Nog geen pakketten.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @include('admin.partials.pagination-footer', ['paginator' => $packages])
    </div>
</div>
@endsection

@push('styles')
<style>
    #content #platform-billing-packages-table col.platform-billing-packages-col-name {
        width: 11rem;
    }

    #content #platform-billing-packages-table col.platform-billing-packages-col-price {
        width: 6.5rem;
    }

    #content #platform-billing-packages-table col.platform-billing-packages-col-active {
        width: 3.75rem;
    }

    #content #platform-billing-packages-table .platform-billing-packages__name,
    #content #platform-billing-packages-table .platform-billing-packages__description {
        max-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        word-break: normal !important;
        overflow-wrap: normal !important;
        vertical-align: middle;
    }

    #content #platform-billing-packages-table .platform-billing-packages__price,
    #content #platform-billing-packages-table .platform-billing-packages__active {
        width: auto !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
        vertical-align: middle;
    }

    #content #platform-billing-packages-table .platform-billing-packages__active {
        padding-inline-end: 0.5rem !important;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
@endpush
