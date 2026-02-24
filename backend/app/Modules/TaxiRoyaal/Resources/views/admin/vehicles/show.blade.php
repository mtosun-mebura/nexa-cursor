@extends('admin.layouts.app')

@section('title', $vehicle->name)

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center gap-5 pb-7.5">
        <a href="{{ route('admin.taxiroyaal.vehicles.index') }}" class="kt-btn kt-btn-outline"><i class="ki-filled ki-arrow-left me-2"></i>Terug</a>
        <h1 class="text-xl font-medium leading-none text-mono">{{ $vehicle->name }}</h1>
        @can('vehicles.update')
        <a href="{{ route('admin.taxiroyaal.vehicles.edit', $vehicle) }}" class="kt-btn kt-btn-outline">Bewerken</a>
        @endcan
    </div>

    @if($vehicle->image_url)
    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <img src="{{ asset(ltrim($vehicle->image_url, '/')) }}" alt="{{ $vehicle->name }}" class="max-w-sm max-h-48 object-contain rounded-lg border border-border">
        </div>
    </div>
    @endif
    <div class="kt-card mb-5">
        <div class="kt-card-header"><h3 class="kt-card-title">Gegevens</h3></div>
        <div class="kt-card-content">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <dt class="text-muted-foreground">Bedrijf</dt><dd>{{ $vehicle->company->name ?? '—' }}</dd>
                <dt class="text-muted-foreground">Type</dt><dd>{{ $vehicle->type_label }}</dd>
                <dt class="text-muted-foreground">Kenteken</dt><dd>{{ $vehicle->license_plate ?? '—' }}</dd>
                <dt class="text-muted-foreground">Status</dt><dd>{{ $vehicle->active ? 'Actief' : 'Inactief' }}</dd>
                @php
                    $minFare = $vehicle->min_fare ?? $defaultRates?->min_fare ?? 0;
                    $pricePerKm = $vehicle->price_per_km ?? $defaultRates?->price_per_km ?? 0;
                    $pricePerMin = $vehicle->price_per_min ?? $defaultRates?->price_per_min ?? 0;
                    $cleaningCosts = $vehicle->cleaning_costs ?? $defaultRates?->cleaning_costs ?? null;
                @endphp
                <dt class="text-muted-foreground">Wachttarief vooraf p/u</dt><dd>€ {{ number_format((float) $minFare, 2, ',', '.') }}@if($vehicle->min_fare === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif</dd>
                <dt class="text-muted-foreground">Prijs per km</dt><dd>€ {{ number_format((float) $pricePerKm, 2, ',', '.') }}@if($vehicle->price_per_km === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif</dd>
                <dt class="text-muted-foreground">Prijs per min</dt><dd>€ {{ number_format((float) $pricePerMin, 2, ',', '.') }}@if($vehicle->price_per_min === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif</dd>
                <dt class="text-muted-foreground">Reinigingskosten</dt><dd>@if($cleaningCosts !== null)€ {{ number_format((float) $cleaningCosts, 2, ',', '.') }}@if($vehicle->cleaning_costs === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif @else—@endif</dd>
                @if($vehicle->notes)
                <dt class="text-muted-foreground sm:col-span-2">Notities</dt><dd class="sm:col-span-2">{{ $vehicle->notes }}</dd>
                @endif
            </dl>
        </div>
    </div>

    <div class="kt-card">
        <div class="kt-card-header"><h3 class="kt-card-title">Recente ritten</h3></div>
        <div class="kt-card-table kt-scrollable-x-auto">
            @if($vehicle->rideRequests->isEmpty())
                <p class="p-5 text-muted-foreground text-sm">Nog geen ritten met dit voertuig.</p>
            @else
            <table class="kt-table kt-table-border-dashed align-middle text-sm">
                <thead>
                    <tr>
                        <th class="text-secondary-foreground font-normal text-left">Datum</th>
                        <th class="text-secondary-foreground font-normal text-left">Route</th>
                        <th class="text-secondary-foreground font-normal text-left">Status</th>
                        <th class="text-secondary-foreground font-normal text-left">Prijs</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicle->rideRequests as $r)
                    <tr>
                        <td>{{ $r->pickup_at->format('d-m-Y H:i') }}</td>
                        <td>{{ Str::limit($r->pickup_address, 25) }} → {{ Str::limit($r->dropoff_address, 25) }}</td>
                        <td>{{ $r->status_label }}</td>
                        <td>{{ $r->quoted_price !== null ? '€ '.number_format($r->quoted_price, 2, ',', '.') : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>
@endsection
