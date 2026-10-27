@extends('admin.layouts.app')

@section('title', __('Betalingen Overzicht'))

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                {{ __('Betalingen Overzicht') }}
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                @if(!empty($tenantId) && count($tenantRows ?? []) === 1)
                    {{ __('Gegevens voor :name', ['name' => $tenantRows[0]['company']->name ?? __('geselecteerde tenant')]) }}
                @else
                    {{ __('Per tenant met actieve betaalmodule (Nexa Taxi, Skillmatching), op basis van betalingsstatus') }}
                @endif
            </div>
        </div>
        <div class="admin-page-actions flex flex-wrap items-center justify-end gap-2.5 w-full min-w-0 lg:w-auto">
            <a class="kt-btn kt-btn-outline min-w-0" href="{{ route('admin.payments.openstaand') }}">
                <i class="ki-filled ki-time text-base me-2"></i>
                {{ __('Openstaande Betalingen') }}
            </a>
            <a class="kt-btn kt-btn-primary min-w-0" href="{{ route('admin.payments.voldaan') }}">
                <i class="ki-filled ki-check-circle text-base me-2"></i>
                {{ __('Voldane Betalingen') }}
            </a>
        </div>
    </div>

    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $paymentStats['pending'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">{{ __('Openstaand') }}</span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $paymentStats['paid'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">{{ __('Voldaan') }}</span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $paymentStats['total'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">{{ __('Totaal') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="kt-card kt-card-grid min-w-full">
        <div class="kt-card-header">
            <h3 class="kt-card-title">{{ __('Betalingen per tenant') }}</h3>
        </div>
        <div class="kt-card-table">
            <div class="kt-scrollable-x-auto">
                <table class="kt-table kt-table-border">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>{{ __('Module(s)') }}</th>
                            <th class="text-end">{{ __('Openstaand') }}</th>
                            <th class="text-end">{{ __('Voldaan') }}</th>
                            <th class="text-end">{{ __('Acties') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenantRows ?? [] as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.companies.show', $row['company']->id) }}" class="font-medium text-sm text-mono hover:text-primary">
                                        {{ $row['company']->name }}
                                    </a>
                                    <div class="text-xs text-secondary-foreground">id {{ $row['company']->id }}</div>
                                </td>
                                <td>
                                    <span class="text-sm text-secondary-foreground">{{ implode(', ', $row['module_labels']) }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="font-semibold text-sm">{{ $row['open_count'] }}</div>
                                    <div class="text-xs text-secondary-foreground">€{{ number_format($row['open_amount'], 2, ',', '.') }}</div>
                                </td>
                                <td class="text-end">
                                    <div class="font-semibold text-sm">{{ $row['paid_count'] }}</div>
                                    <div class="text-xs text-secondary-foreground">€{{ number_format($row['paid_amount'], 2, ',', '.') }}</div>
                                </td>
                                <td class="text-end">
                                    <div class="flex flex-wrap justify-end gap-1.5">
                                        <a href="{{ route('admin.payments.openstaand', ['company_id' => $row['company']->id]) }}" class="kt-btn kt-btn-sm kt-btn-outline">
                                            {{ __('Openstaand') }}
                                        </a>
                                        <a href="{{ route('admin.payments.voldaan', ['company_id' => $row['company']->id]) }}" class="kt-btn kt-btn-sm kt-btn-primary">
                                            {{ __('Voldaan') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-sm text-secondary-foreground">
                                    {{ __('Geen tenants met een actieve betaalmodule gevonden.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
