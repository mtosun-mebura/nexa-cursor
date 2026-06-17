@extends('admin.layouts.app')

@section('title', $contract->name)

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">{{ $contract->name }}</h1>
            <p class="text-sm text-muted-foreground pt-2">Klant: {{ $customer->name }}</p>
            <div class="pt-3">
                <a href="{{ route('admin.taxi.transport_customers.show', $customer->id) }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug
                </a>
            </div>
        </div>
        @can('rides.update')
        <a href="{{ route('admin.taxi.transport_customers.contract_edit', [$customer->id, $contract->id]) }}" class="kt-btn kt-btn-outline shrink-0">
            Bewerken
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">
        @php
            $contractShowUrl = route('admin.taxi.transport_customers.contract_show', [$customer->id, $contract->id]);
        @endphp

        {{-- Abonnementsdetails --}}
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header">
                <h3 class="kt-card-title mb-0">Abonnement</h3>
            </div>
            <div class="kt-card-content p-0">
                <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-medium">Status</td>
                            <td>
                                @if($contract->status === 'active')
                                    <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                @elseif($contract->status === 'paused')
                                    <span class="kt-badge kt-badge-warning kt-badge-sm">Gepauzeerd</span>
                                @else
                                    <span class="kt-badge kt-badge-secondary kt-badge-sm">Beëindigd</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Periode</td>
                            <td>
                                {{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('d-m-Y') : '—' }}
                                &rarr;
                                {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('d-m-Y') : 'doorlopend' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Facturatiemodel</td>
                            <td>
                                @if($contract->billing_model === 'fixed_monthly') Vast maandbedrag
                                @elseif($contract->billing_model === 'per_ride') Per rit
                                @else Hybride @endif
                            </td>
                        </tr>
                        @if(!is_null($contract->monthly_amount) && $contract->billing_model !== 'per_ride')
                        <tr>
                            <td class="text-secondary-foreground font-medium">Maandbedrag</td>
                            <td>&euro; {{ number_format($contract->monthly_amount, 2, ',', '.') }} excl. BTW</td>
                        </tr>
                        @endif
                        @if(!is_null($contract->price_per_ride) && $contract->billing_model !== 'fixed_monthly')
                        <tr>
                            <td class="text-secondary-foreground font-medium">Prijs per rit</td>
                            <td>&euro; {{ number_format($contract->price_per_ride, 2, ',', '.') }} excl. BTW</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-secondary-foreground font-medium">BTW</td>
                            <td>{{ $contract->tax_rate }}%</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Factuurdag</td>
                            <td>{{ $contract->invoice_day }}e van de maand</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-medium">Betalingstermijn</td>
                            <td>{{ $contract->payment_terms_days }} dagen</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Passagiers --}}
        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-2">
                <h3 class="kt-card-title mb-0">Passagiers ({{ $passengerCount }})</h3>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_passengers.index', [$customer->id, $contract->id]), $contractShowUrl) }}" class="kt-btn kt-btn-sm kt-btn-outline">
                        Alle passagiers
                    </a>
                    @can('rides.create')
                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_passengers.create', [$customer->id, $contract->id]), $contractShowUrl) }}" class="kt-btn kt-btn-sm kt-btn-primary">
                        Nieuwe passagier
                    </a>
                    @endcan
                </div>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table id="transport-contract-passengers-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Naam">Naam</th>
                                <th data-label="Ophaaladres">Ophaaladres</th>
                                <th data-label="Status">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPassengers as $passenger)
                            <tr
                                @can('rides.update')
                                data-row-href="{{ transport_admin_url_with_return(route('admin.taxi.transport_passengers.edit', [$customer->id, $contract->id, $passenger->id]), $contractShowUrl) }}"
                                class="cursor-pointer hover:bg-muted/40"
                                tabindex="0"
                                role="link"
                                aria-label="Bewerk passagier {{ $passenger->full_name }}"
                                @endcan
                            >
                                <td>
                                    <span class="font-medium text-foreground">{{ $passenger->full_name }}</span>
                                </td>
                                <td class="text-muted-foreground">{{ Str::limit($passenger->pickup_address, 50) }}</td>
                                <td>
                                    @if($passenger->active)
                                        <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                    @else
                                        <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted-foreground py-6">
                                    Nog geen passagiers.
                                    @can('rides.create')
                                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_passengers.create', [$customer->id, $contract->id]), $contractShowUrl) }}" class="text-primary hover:underline">Eerste passagier toevoegen</a>.
                                    @endcan
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Groepen --}}
        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-2">
                <h3 class="kt-card-title mb-0">Groepen ({{ $groupCount }})</h3>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.index', [$customer->id, $contract->id]), $contractShowUrl) }}" class="kt-btn kt-btn-sm kt-btn-outline">
                        Alle groepen
                    </a>
                    @can('rides.create')
                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.create', [$customer->id, $contract->id]), $contractShowUrl) }}" class="kt-btn kt-btn-sm kt-btn-primary">
                        Nieuwe groep
                    </a>
                    @endcan
                </div>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table id="transport-contract-groups-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Naam">Naam</th>
                                <th data-label="Eindlocatie">Eindlocatie</th>
                                <th data-label="Aankomst">Aankomst</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentGroups as $group)
                            <tr
                                data-row-href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.show', [$customer->id, $contract->id, $group->id]), $contractShowUrl) }}"
                                class="cursor-pointer hover:bg-muted/40"
                                tabindex="0"
                                role="link"
                                aria-label="Bekijk groep {{ $group->name }}"
                            >
                                <td>
                                    <span class="font-medium text-foreground">{{ $group->name }}</span>
                                </td>
                                <td class="text-muted-foreground">{{ Str::limit($group->destination_address, 50) }}</td>
                                <td class="text-muted-foreground">{{ substr($group->destination_arrival_time, 0, 5) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted-foreground py-6">
                                    Nog geen groepen.
                                    @can('rides.create')
                                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.create', [$customer->id, $contract->id]), $contractShowUrl) }}" class="text-primary hover:underline">Eerste groep aanmaken</a>.
                                    @endcan
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Individuele contractritten --}}
        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-2">
                <h3 class="kt-card-title mb-0">Individuele ritten ({{ $individualBookingCount }})</h3>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_individual_bookings.index', [$customer->id, $contract->id]), $contractShowUrl) }}" class="kt-btn kt-btn-sm kt-btn-outline">
                        Alle ritten
                    </a>
                    @can('rides.create')
                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_individual_bookings.create', [$customer->id, $contract->id]), $contractShowUrl) }}" class="kt-btn kt-btn-sm kt-btn-primary">
                        Nieuwe rit
                    </a>
                    @endcan
                </div>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Passagier">Passagier</th>
                                <th data-label="Ophalen">Ophalen</th>
                                <th data-label="Route">Route</th>
                                <th data-label="Status">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentIndividualBookings as $booking)
                            <tr
                                @can('rides.update')
                                data-row-href="{{ transport_admin_url_with_return(route('admin.taxi.transport_individual_bookings.edit', [$customer->id, $contract->id, $booking->id]), $contractShowUrl) }}"
                                class="cursor-pointer hover:bg-muted/40"
                                tabindex="0"
                                role="link"
                                aria-label="Bewerk rit {{ $booking->passenger?->full_name ?? '' }}"
                                @endcan
                            >
                                <td><span class="font-medium text-foreground">{{ $booking->passenger?->full_name ?? '—' }}</span></td>
                                <td class="text-muted-foreground whitespace-nowrap">{{ $booking->pickup_at?->format('d-m-Y H:i') ?? '—' }}</td>
                                <td class="text-muted-foreground">{{ Str::limit($booking->pickup_address, 24) }} → {{ Str::limit($booking->dropoff_address, 24) }}</td>
                                <td>
                                    @if($booking->status === 'planned')
                                        <span class="kt-badge kt-badge-light kt-badge-sm">Gepland</span>
                                    @else
                                        <span class="kt-badge kt-badge-secondary kt-badge-sm">Geannuleerd</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted-foreground py-6">
                                    Nog geen individuele ritten.
                                    @can('rides.create')
                                    <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_individual_bookings.create', [$customer->id, $contract->id]), $contractShowUrl) }}" class="text-primary hover:underline">Eerste rit plannen</a>.
                                    @endcan
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- SEPA-mandaat --}}
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header flex items-center justify-between">
                <h3 class="kt-card-title mb-0">SEPA-mandaat (automatisch incasso)</h3>
            </div>
            <div class="kt-card-content p-0">
                <form method="POST" action="{{ route('admin.taxi.transport_customers.mandate_save', [$customer->id, $contract->id]) }}">
                    @csrf
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Rekeninghouder</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="account_holder" value="{{ old('account_holder', optional($mandate)->account_holder) }}" class="kt-input w-full" maxlength="200">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">IBAN</td>
                                <td class="min-w-48 w-full">
                                    <input type="text"
                                           name="iban"
                                           value="{{ old('iban', optional($mandate)->iban) }}"
                                           class="kt-input w-full @error('iban') border-destructive @enderror"
                                           maxlength="64"
                                           placeholder="NL00 BANK 0000 0000 00"
                                           required>
                                    <div class="text-xs text-muted-foreground mt-1">Nederlands IBAN: 18 tekens (bijv. NL91 ABNA 0417 1643 00).</div>
                                    @error('iban')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">BIC</td>
                                <td>
                                    <input type="text" name="bic" value="{{ old('bic', optional($mandate)->bic) }}" class="kt-input w-full" maxlength="64">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Mandaatreferentie</td>
                                <td>
                                    <input type="text" name="mandate_reference" value="{{ old('mandate_reference', optional($mandate)->mandate_reference) }}" class="kt-input w-full" maxlength="64">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Status mandaat</td>
                                <td>
                                    <select name="status" class="kt-select w-full">
                                        <option value="pending" @selected(old('status', optional($mandate)->status ?? 'pending') === 'pending')>In behandeling</option>
                                        <option value="active" @selected(old('status', optional($mandate)->status) === 'active')>Actief</option>
                                        <option value="revoked" @selected(old('status', optional($mandate)->status) === 'revoked')>Ingetrokken</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-normal">Ondertekend op</td>
                                <td>
                                    @include('taxi::admin.transport_customers.partials.date-picker-input', [
                                        'name' => 'signed_at',
                                        'value' => old('signed_at', optional($mandate)->signed_at?->format('Y-m-d') ?? ''),
                                    ])
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="px-3 sm:px-5 pb-5 flex justify-end">
                        <button type="submit" class="kt-btn kt-btn-primary">Mandaat opslaan</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#transport-contract-passengers-table tr[data-row-href], #transport-contract-groups-table tr[data-row-href]').forEach(function(row) {
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
