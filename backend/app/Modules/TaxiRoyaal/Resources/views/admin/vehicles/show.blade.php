@extends('admin.layouts.app')

@section('title', $vehicle->name)

@section('content')
<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @if($vehicle->image_url)
                <img src="{{ asset(ltrim($vehicle->image_url, '/')) }}" alt="{{ $vehicle->name }}" class="max-w-sm max-h-48 object-contain rounded-lg border border-border bg-white">
            @else
                <div class="w-full max-w-sm h-48 rounded-lg border border-border flex items-center justify-center bg-primary/10 text-primary text-3xl font-semibold">
                    <i class="ki-filled ki-car"></i>
                </div>
            @endif

            <div class="text-lg leading-5 font-semibold text-mono text-center">
                {{ $vehicle->name }}
            </div>

            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <x-heroicon-o-building-office-2 class="w-4 h-4 text-muted-foreground" />
                    <span class="text-secondary-foreground font-medium">{{ $vehicle->company->name ?? '—' }}</span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-car text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">{{ $vehicle->type_label }}</span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-discount text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">{{ $vehicle->license_plate ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10 pt-5">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.taxiroyaal.vehicles.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('vehicles.update'))
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.taxiroyaal.vehicles.edit', $vehicle) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-notepad-edit me-2"></i>
                Bewerken
            </a>
        </div>
        @endif
    </div>

    <div class="kt-card mb-5">
        <div class="kt-card-header"><h3 class="kt-card-title">Gegevens</h3></div>
        <div class="kt-card-content">
            <dl class="grid gap-x-4 text-base items-center" style="grid-template-columns: 16rem minmax(0, 1fr); row-gap: 1rem;">
                <dt class="text-muted-foreground whitespace-nowrap">Bedrijf</dt><dd>{{ $vehicle->company->name ?? '—' }}</dd>
                <dt class="text-muted-foreground whitespace-nowrap">Type</dt><dd>{{ $vehicle->type_label }}</dd>
                <dt class="text-muted-foreground whitespace-nowrap">Kenteken</dt><dd>{{ $vehicle->license_plate ?? '—' }}</dd>
                <dt class="text-muted-foreground whitespace-nowrap">Status</dt><dd>{{ $vehicle->active ? 'Actief' : 'Inactief' }}</dd>
                @php
                    $minFare = $vehicle->min_fare ?? $defaultRates?->min_fare ?? 0;
                    $pricePerKm = $vehicle->price_per_km ?? $defaultRates?->price_per_km ?? 0;
                    $pricePerMin = $vehicle->price_per_min ?? $defaultRates?->price_per_min ?? 0;
                    $cleaningCosts = $vehicle->cleaning_costs ?? $defaultRates?->cleaning_costs ?? null;
                @endphp
                <dt class="text-muted-foreground whitespace-nowrap">Wachttarief vooraf p/u</dt><dd>€ {{ number_format((float) $minFare, 2, ',', '.') }}@if($vehicle->min_fare === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif</dd>
                <dt class="text-muted-foreground whitespace-nowrap">Prijs per km</dt><dd>€ {{ number_format((float) $pricePerKm, 2, ',', '.') }}@if($vehicle->price_per_km === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif</dd>
                <dt class="text-muted-foreground whitespace-nowrap">Prijs per min</dt><dd>€ {{ number_format((float) $pricePerMin, 2, ',', '.') }}@if($vehicle->price_per_min === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif</dd>
                <dt class="text-muted-foreground whitespace-nowrap">Reinigingskosten</dt><dd>@if($cleaningCosts !== null)€ {{ number_format((float) $cleaningCosts, 2, ',', '.') }}@if($vehicle->cleaning_costs === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif @else—@endif</dd>
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
