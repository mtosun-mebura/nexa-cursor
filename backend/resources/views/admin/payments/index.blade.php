@extends('admin.layouts.app')

@section('title', 'Betalingen Overzicht')

@section('content')
<div class="kt-container-fixed">
    <!-- Page Title -->
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Betalingen Overzicht
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Overzicht van alle betalingen en inkomsten
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('admin.payments.openstaand') }}">
                <i class="ki-filled ki-time text-base me-2"></i>
                Openstaande Betalingen
            </a>
            <a class="kt-btn kt-btn-primary" href="{{ route('admin.payments.voldaan') }}">
                <i class="ki-filled ki-check-circle text-base me-2"></i>
                Voldane Betalingen
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $paymentStats['pending'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Openstaand
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1">
                </span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $paymentStats['paid'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Betaald
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1">
                </span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $paymentStats['total'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Earnings Card -->
    <div class="kt-card">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                Inkomsten
            </h3>
            <div class="flex gap-5">
                <label class="flex items-center gap-2">
                    <input class="kt-switch" name="check" type="checkbox" value="1"/>
                    <span class="kt-label">
                        Alleen betaald
                    </span>
                </label>
                <select class="kt-select w-36" data-kt-select="true" data-kt-select-placeholder="Selecteer periode" name="kt-select">
                    <option value="">Geen</option>
                    <option value="1">1 maand</option>
                    <option value="2">3 maanden</option>
                    <option value="3">6 maanden</option>
                    <option value="4">12 maanden</option>
                </select>
            </div>
        </div>
        <div class="kt-card-content flex flex-col justify-end items-stretch grow px-3 py-1">
            <div class="flex items-center justify-center py-10 text-secondary-foreground">
                <div class="text-center">
                    <i class="ki-filled ki-chart-line-up-2 text-4xl mb-3"></i>
                    <p>Grafiek wordt geladen...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection




