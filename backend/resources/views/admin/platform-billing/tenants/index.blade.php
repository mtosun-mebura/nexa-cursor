@extends('admin.layouts.app')

@section('title', 'Tenant-abonnementen')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Tenant-abonnementen
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                SaaS-abonnementen en incasso per tenant
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-destructive mb-5" role="alert">
            <i class="ki-filled ki-information-2 me-2"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header py-5 flex-wrap gap-3 justify-between items-center">
            <h3 class="kt-card-title text-sm mb-0">
                Tenants
            </h3>
            <form method="GET" action="{{ route('admin.platform-billing.tenants.index') }}" class="admin-filter-panel flex flex-wrap items-center gap-2">
                <label class="kt-input w-full sm:w-56 min-w-0">
                    <i class="ki-filled ki-magnifier"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Zoek tenant…" class="min-w-0" autocomplete="off">
                </label>
                <select class="kt-select w-full sm:w-44" name="billing_mode">
                    <option value="">Alle modi</option>
                    <option value="package" @selected(request('billing_mode') === 'package')>Pakket</option>
                    <option value="custom" @selected(request('billing_mode') === 'custom')>Custom</option>
                    <option value="free" @selected(request('billing_mode') === 'free')>Gratis</option>
                    <option value="none" @selected(request('billing_mode') === 'none')>Niet geconfigureerd</option>
                </select>
                <select class="kt-select w-full sm:w-44" name="mandate_status">
                    <option value="">Alle mandaten</option>
                    <option value="none" @selected(request('mandate_status') === 'none')>Geen mandaat</option>
                    <option value="active" @selected(request('mandate_status') === 'active')>Actief</option>
                    <option value="pending" @selected(request('mandate_status') === 'pending')>In afwachting</option>
                </select>
            </form>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap admin-desktop-table-wrap min-w-0">
                <table id="platform-billing-tenants-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                    <colgroup>
                        <col>
                        <col class="platform-billing-tenants-col-mode">
                        <col class="platform-billing-tenants-col-amount">
                        <col class="platform-billing-tenants-col-mandate">
                        <col class="admin-table__actions-col">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-secondary-foreground font-normal text-left">Tenant</th>
                            <th class="text-secondary-foreground font-normal text-left">Modus</th>
                            <th class="text-secondary-foreground font-normal text-left">Bedrag/maand</th>
                            <th class="text-secondary-foreground font-normal text-left">Mandaat</th>
                            <th class="admin-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($companies as $company)
                        @php
                            $profile = $profiles->get($company->id);
                            $mandate = $mandates->get($company->id);
                        @endphp
                        <tr data-row-href="{{ route('admin.platform-billing.tenants.edit', $company) }}">
                            <td class="platform-billing-tenants__name font-medium">{{ $company->name }}</td>
                            <td class="platform-billing-tenants__mode whitespace-nowrap">{{ $profile?->billing_mode ?? '—' }}</td>
                            <td class="platform-billing-tenants__amount whitespace-nowrap tabular-nums">@if($profile) € {{ number_format($profile->resolveMonthlyAmount(), 2, ',', '.') }} @else — @endif</td>
                            <td class="platform-billing-tenants__mandate whitespace-nowrap">{{ $mandate?->status ?? 'geen' }}</td>
                            <td class="admin-table__actions-col" data-no-row-link>
                                <div class="kt-menu flex justify-center" data-kt-menu="true">
                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                        <button type="button" class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Acties">
                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                        </button>
                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.platform-billing.tenants.edit', $company) }}">
                                                    <span class="kt-menu-icon"><i class="ki-filled ki-setting-2"></i></span>
                                                    <span class="kt-menu-title">Configureren</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-5 text-secondary-foreground">Geen tenants gevonden.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @include('admin.partials.pagination-footer', ['paginator' => $companies])
    </div>
</div>
@endsection

@push('styles')
<style>
    #content #platform-billing-tenants-table col.platform-billing-tenants-col-mode {
        width: 7rem;
    }

    #content #platform-billing-tenants-table col.platform-billing-tenants-col-amount {
        width: 7.5rem;
    }

    #content #platform-billing-tenants-table col.platform-billing-tenants-col-mandate {
        width: 7rem;
    }

    #content #platform-billing-tenants-table .platform-billing-tenants__name {
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
