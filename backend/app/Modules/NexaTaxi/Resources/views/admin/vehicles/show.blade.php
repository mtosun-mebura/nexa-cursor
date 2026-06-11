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
    <div class="kt-container-fixed min-w-0">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10 px-3">
            @if($vehicle->image_url)
                <img src="{{ app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($vehicle->image_url) }}" alt="{{ $vehicle->name }}" class="w-full max-w-sm max-h-48 object-contain rounded-lg border border-border bg-white">
            @else
                <img src="{{ asset('modules/nexa-taxi/vehicle-placeholder.png') }}" alt="" width="384" height="192" class="w-full max-w-sm h-48 rounded-lg object-cover border border-border bg-muted">
            @endif

            <div class="text-lg leading-5 font-semibold text-mono text-center">
                {{ $vehicle->name }}
            </div>

            <div class="flex flex-wrap justify-center gap-2 lg:gap-4.5 text-sm max-w-full">
                <div class="flex gap-1.25 items-center min-w-0">
                    <x-heroicon-o-building-office-2 class="w-4 h-4 text-muted-foreground shrink-0" />
                    <span class="text-secondary-foreground font-medium truncate">{{ $vehicle->company->name ?? '—' }}</span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-car text-muted-foreground text-sm shrink-0"></i>
                    <span class="text-secondary-foreground font-medium">{{ $vehicle->type_label }}</span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-discount text-muted-foreground text-sm shrink-0"></i>
                    <span class="text-secondary-foreground font-medium">{{ $vehicle->license_plate ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed min-w-0">
    <div class="admin-page-actions flex flex-wrap items-center justify-between gap-3 mb-5 lg:mb-10 pt-5 w-full min-w-0">
        <a href="{{ route('admin.taxi.vehicles.index') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('vehicles.update'))
        <a href="{{ route('admin.taxi.vehicles.edit', $vehicle) }}" class="kt-btn kt-btn-primary shrink-0">
            <i class="ki-filled ki-notepad-edit me-2"></i>
            Bewerken
        </a>
        @endif
    </div>

    @php
        $baseFare = $vehicle->base_fare ?? $defaultRates?->base_fare ?? null;
        $minFare = $vehicle->min_fare ?? $defaultRates?->min_fare ?? 0;
        $pricePerKm = $vehicle->price_per_km ?? $defaultRates?->price_per_km ?? 0;
        $pricePerMin = $vehicle->price_per_min ?? $defaultRates?->price_per_min ?? 0;
        $cleaningCosts = $vehicle->cleaning_costs ?? $defaultRates?->cleaning_costs ?? null;
        $personRangeLabel = \App\Modules\NexaTaxi\Models\Vehicle::personRangeLabels()[$vehicle->person_range] ?? $vehicle->person_range ?? '—';
    @endphp

    <div class="flex flex-col gap-5 lg:gap-7.5">
        <div class="kt-card w-full min-w-0 mb-0">
            <div class="kt-card-header">
                <h3 class="kt-card-title mb-0">Gegevens</h3>
            </div>
            <div class="kt-card-content p-0">
                <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">Bedrijf</td>
                            <td class="min-w-48 w-full text-foreground font-normal">{{ $vehicle->company->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Type</td>
                            <td class="text-foreground font-normal">{{ $vehicle->type_label }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Kenteken</td>
                            <td class="text-foreground font-normal">{{ $vehicle->license_plate ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Personenbereik</td>
                            <td class="text-foreground font-normal">{{ $personRangeLabel }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Status</td>
                            <td class="text-foreground font-normal">{{ $vehicle->active ? 'Actief' : 'Inactief' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Foto in frontend</td>
                            <td class="text-foreground font-normal">{{ $vehicle->show_photo ? 'Ja' : 'Nee' }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Instaptarief</td>
                            <td class="text-foreground font-normal">
                                @if($baseFare !== null)
                                    € {{ number_format((float) $baseFare, 2, ',', '.') }}@if($vehicle->base_fare === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Wachttarief vooraf p/u</td>
                            <td class="text-foreground font-normal">
                                € {{ number_format((float) $minFare, 2, ',', '.') }}@if($vehicle->min_fare === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Prijs per km</td>
                            <td class="text-foreground font-normal">
                                € {{ number_format((float) $pricePerKm, 2, ',', '.') }}@if($vehicle->price_per_km === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Prijs per min</td>
                            <td class="text-foreground font-normal">
                                € {{ number_format((float) $pricePerMin, 2, ',', '.') }}@if($vehicle->price_per_min === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Reinigingskosten</td>
                            <td class="text-foreground font-normal">
                                @if($cleaningCosts !== null)
                                    € {{ number_format((float) $cleaningCosts, 2, ',', '.') }}@if($vehicle->cleaning_costs === null && $defaultRates) <span class="text-muted-foreground text-xs">(standaard)</span>@endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @if($vehicle->notes)
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Notities</td>
                            <td class="text-foreground font-normal break-words">{{ $vehicle->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header">
                <h3 class="kt-card-title mb-0">Recente ritten</h3>
            </div>
            <div class="kt-card-content min-w-0 p-0 sm:p-0">
                @if($vehicle->rideRequests->isEmpty())
                    <p class="px-3 sm:px-5 py-4 text-muted-foreground text-sm mb-0">Nog geen ritten met dit voertuig.</p>
                @else
                    <div class="kt-table-responsive kt-scrollable-x-auto admin-table-scroll-wrap px-3 sm:px-5 pb-3">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm w-full min-w-[32rem]">
                            <thead>
                                <tr>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Datum">Datum</th>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Route">Route</th>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Status">Status</th>
                                    <th class="text-secondary-foreground font-normal text-left" data-label="Prijs">Prijs</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vehicle->rideRequests as $r)
                                <tr>
                                    <td>{{ $r->pickup_at->format('d-m-Y H:i') }}</td>
                                    <td class="min-w-0 max-w-xs sm:max-w-none">{{ Str::limit($r->pickup_address, 25) }} → {{ Str::limit($r->dropoff_address, 25) }}</td>
                                    <td>{{ $r->status_label }}</td>
                                    <td>{{ $r->quoted_price !== null ? '€ '.number_format($r->quoted_price, 2, ',', '.') : '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
