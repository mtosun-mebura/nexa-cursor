@extends('admin.layouts.app')

@section('title', 'Pakket '.$package->name)

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                {{ $package->name }}
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                SaaS-abonnementspakket
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.platform-billing.packages.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
            <a href="{{ route('admin.platform-billing.packages.edit', $package) }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-pencil me-2"></i>
                Bewerken
            </a>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Pakketgegevens
                </h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal align-top">Naam</td>
                        <td class="font-medium text-foreground">{{ $package->name }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">Omschrijving</td>
                        <td>{{ $package->description ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">Maandprijs (excl. BTW)</td>
                        <td class="tabular-nums">€ {{ number_format((float) $package->monthly_amount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">Valuta</td>
                        <td>{{ $package->currency }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">Sorteervolgorde</td>
                        <td>{{ $package->sort_order }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal align-top">Status</td>
                        <td>
                            @if($package->is_active)
                                <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
