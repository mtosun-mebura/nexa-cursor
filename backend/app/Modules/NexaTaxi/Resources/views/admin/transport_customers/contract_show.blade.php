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
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground contract-detail-table w-full">
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Naam</td>
                            <td>{{ $contract->name }}</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Planningkleur</td>
                            <td>
                                <span class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-block h-4 w-4 rounded border"
                                        style="background-color: {{ $contract->planningColorHex() }}; border-color: {{ $contract->planningColorHex() }};"
                                    ></span>
                                    {{ $contract->planningColorHex() }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Status</td>
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
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Periode</td>
                            <td>
                                {{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('d-m-Y') : '—' }}
                                &rarr;
                                {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('d-m-Y') : 'doorlopend' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Facturatiemodel</td>
                            <td>
                                @if($contract->billing_model === 'fixed_monthly') Vast maandbedrag
                                @elseif($contract->billing_model === 'per_ride') Per rit
                                @else Hybride @endif
                            </td>
                        </tr>
                        @if(!is_null($contract->monthly_amount) && $contract->billing_model !== 'per_ride')
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Maandbedrag</td>
                            <td>&euro; {{ number_format($contract->monthly_amount, 2, ',', '.') }} excl. BTW</td>
                        </tr>
                        @endif
                        @if(!is_null($contract->price_per_ride) && $contract->billing_model !== 'fixed_monthly')
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Prijs per rit</td>
                            <td>&euro; {{ number_format($contract->price_per_ride, 2, ',', '.') }} excl. BTW</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">BTW</td>
                            <td>{{ $contract->tax_rate }}%</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Factuurdag</td>
                            <td>{{ $contract->invoice_day }}e van de maand</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Betalingstermijn</td>
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
                                <th class="transport-contract-passengers-table__pickup-col" data-label="Ophaaladres">Ophaaladres</th>
                                <th class="transport-contract-passengers-table__status-col" data-label="Status">Status</th>
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
                                <td class="text-muted-foreground transport-contract-passengers-table__pickup-col">{{ Str::limit($passenger->pickup_address, 50) }}</td>
                                <td class="transport-contract-passengers-table__status-col">
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

        {{-- Facturen --}}
        <div id="transport-contract-invoices-card" class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-2">
                <h3 class="kt-card-title mb-0">Facturen ({{ $contractInvoices->count() }})</h3>
                <div class="flex gap-2 shrink-0">
                    @if($contractInvoices->isNotEmpty())
                    <a href="{{ route('admin.taxi.transport_contract_invoices.export', [$customer->id, $contract->id]) }}" class="kt-btn kt-btn-sm kt-btn-outline">
                        Export CSV
                    </a>
                    @endif
                </div>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                @can('rides.update')
                @php
                    $invoicePeriodValue = old('period', $defaultInvoicePeriod);
                    if (! is_string($invoicePeriodValue) || ! preg_match('/^\d{4}-\d{2}$/', $invoicePeriodValue)) {
                        $invoicePeriodValue = $defaultInvoicePeriod;
                    }
                    [$invoicePeriodYear, $invoicePeriodMonth] = array_map('intval', explode('-', $invoicePeriodValue));
                    $invoicePeriodYearOptions = range(now()->year - 3, now()->year + 1);
                @endphp
                <form method="POST"
                      action="{{ route('admin.taxi.transport_contract_invoices.generate', [$customer->id, $contract->id]) }}"
                      id="transport-contract-invoice-generate-form"
                      class="px-3 sm:px-5 py-4 border-b border-border flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label class="text-sm text-secondary-foreground block mb-1" for="contract-invoice-period-month">Periode</label>
                        <div class="flex items-center gap-2">
                            <select id="contract-invoice-period-month" class="kt-select w-40" required aria-label="Maand">
                                @foreach(range(1, 12) as $monthNum)
                                <option value="{{ sprintf('%02d', $monthNum) }}" @selected($monthNum === $invoicePeriodMonth)>
                                    {{ \Carbon\Carbon::create(2000, $monthNum, 1)->locale('nl')->translatedFormat('F') }}
                                </option>
                                @endforeach
                            </select>
                            <select id="contract-invoice-period-year" class="kt-select w-28" required aria-label="Jaar">
                                @foreach($invoicePeriodYearOptions as $yearOption)
                                <option value="{{ $yearOption }}" @selected($yearOption === $invoicePeriodYear)>{{ $yearOption }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="period" id="contract-invoice-period" value="{{ $invoicePeriodValue }}">
                        </div>
                    </div>
                    <label class="kt-label flex items-center gap-2 text-sm shrink-0 mb-0" for="contract-invoice-send-email">
                        <input type="checkbox" name="send_email" id="contract-invoice-send-email" value="1" class="kt-switch kt-switch-sm shrink-0" @checked(old('send_email'))>
                        <span>Direct verzenden per e-mail</span>
                    </label>
                    <button type="submit" class="kt-btn kt-btn-primary ml-auto shrink-0">Maandfactuur genereren</button>
                </form>
                @endcan
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap contract-invoices-table-wrap">
                    <table id="transport-contract-invoices-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Nummer">Nummer</th>
                                <th data-label="Periode">Periode</th>
                                <th data-label="Datum">Datum</th>
                                <th data-label="Totaal">Totaal</th>
                                <th data-label="Status">Status</th>
                                <th class="contract-invoices-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contractInvoices as $invoice)
                            <tr>
                                <td><span class="font-medium text-foreground">{{ $invoice->invoice_number }}</span></td>
                                <td class="text-muted-foreground">{{ $invoice->billing_period }}</td>
                                <td class="text-muted-foreground whitespace-nowrap">{{ $invoice->invoice_date?->format('d-m-Y') }}</td>
                                <td class="text-muted-foreground">&euro; {{ number_format($invoice->total_amount, 2, ',', '.') }}</td>
                                <td>
                                    @if($invoice->status === 'paid')
                                        <span class="kt-badge kt-badge-success kt-badge-sm">Betaald</span>
                                    @elseif($invoice->status === 'sent')
                                        <span class="kt-badge kt-badge-light kt-badge-sm">Verzonden</span>
                                    @elseif($invoice->status === 'draft')
                                        <span class="kt-badge kt-badge-secondary kt-badge-sm">Concept</span>
                                    @else
                                        <span class="kt-badge kt-badge-secondary kt-badge-sm">{{ ucfirst($invoice->status) }}</span>
                                    @endif
                                </td>
                                <td class="contract-invoices-table__actions-col" data-no-row-link onclick="event.stopPropagation();">
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
                                                    <a class="kt-menu-link" href="{{ route('admin.taxi.transport_contract_invoices.pdf', [$customer->id, $contract->id, $invoice->id]) }}" target="_blank" rel="noopener">
                                                        <span class="kt-menu-icon">
                                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                                        </span>
                                                        <span class="kt-menu-title">PDF</span>
                                                    </a>
                                                </div>
                                                @can('rides.update')
                                                @if($invoice->status !== 'paid')
                                                <div class="kt-menu-item">
                                                    <form method="POST" action="{{ route('admin.taxi.transport_contract_invoices.mark_paid', [$customer->id, $contract->id, $invoice->id]) }}" class="contents">
                                                        @csrf
                                                        <button type="submit" class="kt-menu-link w-full text-left border-0 bg-transparent">
                                                            <span class="kt-menu-icon">
                                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                                            </span>
                                                            <span class="kt-menu-title">Betaald</span>
                                                        </button>
                                                    </form>
                                                </div>
                                                @endif
                                                @if($invoice->status !== 'sent' && $invoice->status !== 'paid')
                                                <div class="kt-menu-item">
                                                    <form method="POST" action="{{ route('admin.taxi.transport_contract_invoices.send', [$customer->id, $contract->id, $invoice->id]) }}" class="contents">
                                                        @csrf
                                                        <button type="submit" class="kt-menu-link w-full text-left border-0 bg-transparent">
                                                            <span class="kt-menu-icon">
                                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                                                            </span>
                                                            <span class="kt-menu-title">Verzenden</span>
                                                        </button>
                                                    </form>
                                                </div>
                                                @endif
                                                @if($invoice->status === 'draft')
                                                <div class="kt-menu-separator"></div>
                                                <div class="kt-menu-item">
                                                    <form method="POST" action="{{ route('admin.taxi.transport_contract_invoices.destroy', [$customer->id, $contract->id, $invoice->id]) }}" class="contents" onsubmit="return confirm('Conceptfactuur verwijderen? Daarna kun je opnieuw genereren met de bijgewerkte prijs.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="kt-menu-link w-full text-left text-danger border-0 bg-transparent">
                                                            <span class="kt-menu-icon">
                                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                                            </span>
                                                            <span class="kt-menu-title">Verwijderen</span>
                                                        </button>
                                                    </form>
                                                </div>
                                                @endif
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted-foreground py-6">Nog geen facturen voor dit abonnement.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- SEPA-mandaat --}}
        @php
            $mandateSaved = $mandate && filled($mandate->iban);
            $mandateEditMode = ! $mandateSaved || $errors->hasAny([
                'account_holder', 'iban', 'bic', 'mandate_reference', 'status', 'signed_at',
            ]);
            $mandateStatusLabels = [
                'pending' => 'In behandeling',
                'active' => 'Actief',
                'revoked' => 'Ingetrokken',
            ];
        @endphp
        <div class="kt-card w-full min-w-0" id="transport-contract-mandate-card">
            <div class="kt-card-header flex items-center justify-between gap-2">
                <h3 class="kt-card-title mb-0">SEPA-mandaat (automatisch incasso)</h3>
                @can('rides.update')
                @if($mandateSaved)
                <button type="button"
                        id="transport-contract-mandate-edit-btn"
                        class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost {{ $mandateEditMode ? 'hidden' : '' }}"
                        aria-label="SEPA-mandaat bewerken">
                    <i class="ki-filled ki-pencil"></i>
                </button>
                @endif
                @endcan
            </div>
            <div class="kt-card-content p-0">
                @if($mandateSaved)
                <div id="transport-contract-mandate-view" class="px-3 sm:px-5 pb-3 min-w-0 {{ $mandateEditMode ? 'hidden' : '' }}">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground contract-detail-table w-full">
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Rekeninghouder</td>
                            <td>{{ $mandate->account_holder }}</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">IBAN</td>
                            <td>{{ $mandate->iban }}</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">BIC</td>
                            <td>{{ $mandate->bic ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Mandaatreferentie</td>
                            <td>{{ $mandate->mandate_reference ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Status mandaat</td>
                            <td>
                                @if(($mandate->status ?? 'pending') === 'active')
                                    <span class="kt-badge kt-badge-success kt-badge-sm">{{ $mandateStatusLabels['active'] }}</span>
                                @elseif(($mandate->status ?? 'pending') === 'revoked')
                                    <span class="kt-badge kt-badge-secondary kt-badge-sm">{{ $mandateStatusLabels['revoked'] }}</span>
                                @else
                                    <span class="kt-badge kt-badge-warning kt-badge-sm">{{ $mandateStatusLabels['pending'] }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="contract-detail-table__label text-secondary-foreground font-medium">Ondertekend op</td>
                            <td>{{ $mandate->signed_at?->format('d-m-Y') ?: '—' }}</td>
                        </tr>
                    </table>
                </div>
                @endif

                @can('rides.update')
                <form method="POST"
                      action="{{ route('admin.taxi.transport_customers.mandate_save', [$customer->id, $contract->id]) }}"
                      id="transport-contract-mandate-form"
                      class="{{ $mandateSaved && ! $mandateEditMode ? 'hidden' : '' }}">
                    @csrf
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground contract-detail-table wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="contract-detail-table__label text-secondary-foreground font-normal">Rekeninghouder</td>
                                <td class="min-w-48 w-full">
                                    <input type="text" name="account_holder" value="{{ old('account_holder', optional($mandate)->account_holder) }}" class="kt-input w-full" maxlength="200" required>
                                </td>
                            </tr>
                            <tr>
                                <td class="contract-detail-table__label text-secondary-foreground font-normal">IBAN</td>
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
                                <td class="contract-detail-table__label text-secondary-foreground font-normal">BIC</td>
                                <td>
                                    <input type="text" name="bic" value="{{ old('bic', optional($mandate)->bic) }}" class="kt-input w-full" maxlength="64">
                                </td>
                            </tr>
                            <tr>
                                <td class="contract-detail-table__label text-secondary-foreground font-normal">Mandaatreferentie</td>
                                <td>
                                    <input type="text" name="mandate_reference" value="{{ old('mandate_reference', optional($mandate)->mandate_reference) }}" class="kt-input w-full" maxlength="64">
                                </td>
                            </tr>
                            <tr>
                                <td class="contract-detail-table__label text-secondary-foreground font-normal">Status mandaat</td>
                                <td>
                                    <select name="status" class="kt-select w-full">
                                        <option value="pending" @selected(old('status', optional($mandate)->status ?? 'pending') === 'pending')>In behandeling</option>
                                        <option value="active" @selected(old('status', optional($mandate)->status) === 'active')>Actief</option>
                                        <option value="revoked" @selected(old('status', optional($mandate)->status) === 'revoked')>Ingetrokken</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="contract-detail-table__label text-secondary-foreground font-normal">Ondertekend op</td>
                                <td>
                                    @include('taxi::admin.transport_customers.partials.date-picker-input', [
                                        'name' => 'signed_at',
                                        'value' => old('signed_at', optional($mandate)->signed_at?->format('Y-m-d') ?? ''),
                                    ])
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="px-3 sm:px-5 pb-5 flex justify-end gap-2">
                        @if($mandateSaved)
                        <button type="button" id="transport-contract-mandate-cancel-btn" class="kt-btn kt-btn-outline">Annuleren</button>
                        @endif
                        <button type="submit" class="kt-btn kt-btn-primary">Mandaat opslaan</button>
                    </div>
                </form>
                @elseif(! $mandateSaved)
                <div class="px-3 sm:px-5 pb-5 text-sm text-muted-foreground">
                    Nog geen SEPA-mandaat ingesteld.
                </div>
                @endcan
            </div>
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
    #content .contract-detail-table {
        width: 100%;
        table-layout: fixed;
    }

    #content .contract-detail-table td.contract-detail-table__label {
        width: 14rem;
        min-width: 14rem;
        max-width: 14rem;
        vertical-align: top;
    }

    #content .contract-detail-table td:nth-child(2) {
        min-width: 0;
        overflow-wrap: break-word;
    }

    #content #transport-contract-passengers-table .transport-contract-passengers-table__status-col {
        width: 6.5rem !important;
        min-width: 6.5rem !important;
        max-width: 6.5rem !important;
        padding-inline: 0.375rem !important;
        white-space: nowrap;
        vertical-align: middle !important;
    }

    #content #transport-contract-passengers-table .transport-contract-passengers-table__pickup-col {
        width: 55% !important;
        min-width: 12rem !important;
    }

    #content #transport-contract-invoices-table .contract-invoices-table__actions-col {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
        padding-inline: 0.25rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
        overflow: visible !important;
        font-size: 0.8125rem;
    }

    #content #transport-contract-invoices-table .contract-invoices-table__actions-col .kt-menu {
        display: flex !important;
        justify-content: center !important;
        width: 100%;
        margin-inline: auto;
    }

    #transport-contract-invoices-card,
    #transport-contract-invoices-card .kt-card-content {
        overflow: visible !important;
    }

    #transport-contract-invoices-card.transport-contract-invoices-card--menu-open,
    #transport-contract-invoices-card:has(.kt-menu-item.show) {
        position: relative;
        z-index: 120;
    }

    .contract-invoices-table-wrap .contract-invoices-table__actions-col .kt-menu-dropdown {
        position: fixed !important;
        z-index: 200000 !important;
    }

    .contract-invoices-table-wrap .contract-invoices-table__actions-col .kt-menu-item.show .kt-menu-dropdown,
    .contract-invoices-table-wrap .contract-invoices-table__actions-col .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show .kt-menu-dropdown {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    .contract-invoices-table-wrap .contract-invoices-table__actions-col .kt-menu-item.show {
        z-index: 200000 !important;
    }

    .contract-invoices-table-wrap .kt-scrollable-x-auto {
        overflow-x: auto !important;
        overflow-y: visible !important;
    }

    .contract-invoices-table-wrap .kt-menu-default.kt-menu-dropdown {
        overflow: hidden;
        box-sizing: border-box;
        padding: 0.25rem;
    }

    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item {
        width: 100%;
        min-width: 0;
    }

    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item > form.contents {
        display: contents;
    }

    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link {
        display: flex;
        align-items: center;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        margin-inline: 0 !important;
        border: 0;
        background: transparent;
        font: inherit;
        color: inherit;
        text-decoration: none;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        border-radius: calc(var(--radius) - 2px);
        padding-inline: calc(var(--spacing) * 2);
        padding-block: calc(var(--spacing) * 2);
    }

    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link:hover,
    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link:focus-visible {
        background-color: var(--accent);
    }

    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link:hover .kt-menu-title,
    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link:focus-visible .kt-menu-title {
        color: var(--mono);
    }

    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link:hover .kt-menu-icon svg,
    .contract-invoices-table-wrap .kt-menu-default .kt-menu-item .kt-menu-link:focus-visible .kt-menu-icon svg {
        color: var(--primary);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var invoicePeriodMonth = document.getElementById('contract-invoice-period-month');
    var invoicePeriodYear = document.getElementById('contract-invoice-period-year');
    var invoicePeriodHidden = document.getElementById('contract-invoice-period');
    var invoiceGenerateForm = document.getElementById('transport-contract-invoice-generate-form');

    function syncContractInvoicePeriod() {
        if (!invoicePeriodMonth || !invoicePeriodYear || !invoicePeriodHidden) return;
        invoicePeriodHidden.value = invoicePeriodYear.value + '-' + invoicePeriodMonth.value;
    }

    if (invoicePeriodMonth && invoicePeriodYear) {
        invoicePeriodMonth.addEventListener('change', syncContractInvoicePeriod);
        invoicePeriodYear.addEventListener('change', syncContractInvoicePeriod);
        syncContractInvoicePeriod();
    }

    if (invoiceGenerateForm) {
        invoiceGenerateForm.addEventListener('submit', syncContractInvoicePeriod);
    }

    var mandateEditBtn = document.getElementById('transport-contract-mandate-edit-btn');
    var mandateCancelBtn = document.getElementById('transport-contract-mandate-cancel-btn');
    var mandateView = document.getElementById('transport-contract-mandate-view');
    var mandateForm = document.getElementById('transport-contract-mandate-form');

    function showMandateEditMode() {
        if (!mandateView || !mandateForm) return;
        mandateView.classList.add('hidden');
        mandateForm.classList.remove('hidden');
        if (mandateEditBtn) mandateEditBtn.classList.add('hidden');
        var firstInput = mandateForm.querySelector('input, select, textarea');
        if (firstInput) firstInput.focus();
    }

    function showMandateViewMode() {
        if (!mandateView || !mandateForm) return;
        mandateForm.reset();
        mandateForm.classList.add('hidden');
        mandateView.classList.remove('hidden');
        if (mandateEditBtn) mandateEditBtn.classList.remove('hidden');
    }

    if (mandateEditBtn && mandateView && mandateForm) {
        mandateEditBtn.addEventListener('click', showMandateEditMode);
    }

    if (mandateCancelBtn && mandateView && mandateForm) {
        mandateCancelBtn.addEventListener('click', showMandateViewMode);
    }

    function resetContractInvoiceDropdown(dropdown) {
        if (!dropdown) return;
        dropdown.style.position = '';
        dropdown.style.left = '';
        dropdown.style.top = '';
        dropdown.style.minWidth = '';
        dropdown.style.width = '';
        dropdown.style.zIndex = '';
        dropdown.style.display = '';
        dropdown.style.visibility = '';
        dropdown.style.opacity = '';
    }

    function positionContractInvoiceDropdown(toggle, dropdown) {
        var rect = toggle.getBoundingClientRect();
        dropdown.style.position = 'fixed';
        dropdown.style.left = Math.max(8, rect.right - 175) + 'px';
        dropdown.style.top = (rect.bottom + 5) + 'px';
        dropdown.style.minWidth = '175px';
        dropdown.style.width = '175px';
        dropdown.style.zIndex = '200000';
        dropdown.style.display = 'flex';
        dropdown.style.visibility = 'visible';
        dropdown.style.opacity = '1';
    }

    var transportContractInvoicesCard = document.getElementById('transport-contract-invoices-card');

    function syncTransportContractInvoicesCardMenuState() {
        if (!transportContractInvoicesCard) return;
        transportContractInvoicesCard.classList.toggle(
            'transport-contract-invoices-card--menu-open',
            !!transportContractInvoicesCard.querySelector('.kt-menu-item[data-kt-menu-item-toggle="dropdown"].show')
        );
    }

    function repositionOpenContractInvoiceMenus() {
        document.querySelectorAll('.contract-invoices-table-wrap .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show').forEach(function(menuItem) {
            var toggle = menuItem.querySelector('.kt-menu-toggle');
            var dropdown = menuItem.querySelector('.kt-menu-dropdown');
            if (toggle && dropdown) {
                positionContractInvoiceDropdown(toggle, dropdown);
            }
        });
    }

    if (!window._contractInvoiceMenuListenersBound) {
        window._contractInvoiceMenuListenersBound = true;
        window.addEventListener('scroll', repositionOpenContractInvoiceMenus, true);
        document.addEventListener('scroll', repositionOpenContractInvoiceMenus, true);
        window.addEventListener('resize', repositionOpenContractInvoiceMenus);
    }

    function initContractInvoiceMenus() {
        document.querySelectorAll('.contract-invoices-table-wrap .kt-menu-toggle').forEach(function(toggle) {
            if (toggle._contractInvoiceMenuBound) return;
            toggle._contractInvoiceMenuBound = true;
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                var menuItem = toggle.closest('.kt-menu-item[data-kt-menu-item-toggle="dropdown"]');
                if (!menuItem) return;
                var dropdown = menuItem.querySelector('.kt-menu-dropdown');
                if (!dropdown) return;
                var isShowing = menuItem.classList.contains('show');
                document.querySelectorAll('.contract-invoices-table-wrap .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show').forEach(function(item) {
                    if (item === menuItem) return;
                    item.classList.remove('show');
                    resetContractInvoiceDropdown(item.querySelector('.kt-menu-dropdown'));
                });
                if (!isShowing) {
                    menuItem.classList.add('show');
                    positionContractInvoiceDropdown(toggle, dropdown);
                } else {
                    menuItem.classList.remove('show');
                    resetContractInvoiceDropdown(dropdown);
                }
                syncTransportContractInvoicesCardMenuState();
            });
        });
    }
    initContractInvoiceMenus();
    setTimeout(initContractInvoiceMenus, 300);
    setTimeout(initContractInvoiceMenus, 1200);

    document.addEventListener('click', function(e) {
        if (e.target.closest('.contract-invoices-table-wrap .kt-menu')) return;
        document.querySelectorAll('.contract-invoices-table-wrap .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show').forEach(function(item) {
            item.classList.remove('show');
            resetContractInvoiceDropdown(item.querySelector('.kt-menu-dropdown'));
        });
        syncTransportContractInvoicesCardMenuState();
    });

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
