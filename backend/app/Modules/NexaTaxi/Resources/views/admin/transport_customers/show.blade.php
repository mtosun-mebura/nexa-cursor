@extends('admin.layouts.app')

@section('title', $customer->name)

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">{{ $customer->name }}</h1>
            <div class="pt-3">
                <a href="{{ route('admin.taxi.transport_customers.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug
                </a>
            </div>
        </div>
        <div class="flex gap-2 shrink-0">
            @can('rides.update')
            <a href="{{ route('admin.taxi.transport_customers.edit', $customer->id) }}" class="kt-btn kt-btn-outline">
                Bewerken
            </a>
            @endcan
            @can('rides.create')
            <a href="{{ route('admin.taxi.transport_customers.contract_create', $customer->id) }}" class="kt-btn kt-btn-primary">
                <svg class="w-4 h-4 me-2 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Nieuw abonnement
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">

        {{-- Klantdetails --}}
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header">
                <h3 class="kt-card-title mb-0">Klantdetails</h3>
            </div>
            <div class="kt-card-content p-0">
                <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-medium">Status</td>
                            <td>
                                @if($customer->active)
                                    <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                @else
                                    <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Contactpersoon</td>
                            <td>{{ $customer->contact_name ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">E-mail</td>
                            <td>{{ $customer->contact_email ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Telefoon</td>
                            <td>{{ $customer->contact_phone ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Debiteurnummer</td>
                            <td>{{ $customer->debtor_number ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Factuuradres</td>
                            <td>
                                @if($customer->billing_address)
                                    {{ $customer->billing_address }}<br>
                                    {{ $customer->billing_postal_code }} {{ $customer->billing_city }}<br>
                                    {{ $customer->billing_country }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @if($customer->notes)
                        <tr>
                            <td class="text-secondary-foreground font-medium">Notities</td>
                            <td class="whitespace-pre-wrap">{{ $customer->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Abonnementen --}}
        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header">
                <h3 class="kt-card-title mb-0">Abonnementen</h3>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table id="transport-customer-contracts-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Naam">Naam</th>
                                <th data-label="Facturatiemodel">Facturatie</th>
                                <th data-label="Periode">Periode</th>
                                <th data-label="Status">Status</th>
                                <th class="transport-customers-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contracts as $contract)
                            <tr
                                data-row-href="{{ route('admin.taxi.transport_customers.contract_show', [$customer->id, $contract->id]) }}"
                                class="cursor-pointer hover:bg-muted/40"
                                tabindex="0"
                                role="link"
                                aria-label="Bekijk abonnement {{ $contract->name }}"
                            >
                                <td>
                                    <span class="font-medium text-foreground">{{ $contract->name }}</span>
                                </td>
                                <td class="text-muted-foreground">
                                    @if($contract->billing_model === 'fixed_monthly')
                                        Vast maandbedrag
                                    @elseif($contract->billing_model === 'per_ride')
                                        Per rit
                                    @else
                                        Hybride
                                    @endif
                                </td>
                                <td class="text-muted-foreground">
                                    {{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('d-m-Y') : '—' }}
                                    &rarr;
                                    {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('d-m-Y') : 'doorlopend' }}
                                </td>
                                <td>
                                    @if($contract->status === 'active')
                                        <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                    @elseif($contract->status === 'paused')
                                        <span class="kt-badge kt-badge-warning kt-badge-sm">Gepauzeerd</span>
                                    @else
                                        <span class="kt-badge kt-badge-secondary kt-badge-sm">Beëindigd</span>
                                    @endif
                                </td>
                                <td class="transport-customers-table__actions-col" data-no-row-link onclick="event.stopPropagation();">
                                    <a href="{{ route('admin.taxi.transport_customers.contract_show', [$customer->id, $contract->id]) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Bekijken" aria-label="Bekijken">
                                        <i class="ki-filled ki-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted-foreground py-8">
                                    Nog geen abonnementen. <a href="{{ route('admin.taxi.transport_customers.contract_create', $customer->id) }}" class="text-primary hover:underline">Nieuw abonnement aanmaken</a>.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
    #content #transport-customer-contracts-table .transport-customers-table__actions-col {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#transport-customer-contracts-table tr[data-row-href]').forEach(function(row) {
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
});
</script>
@endpush
