@extends('admin.layouts.app')

@section('title', 'Openstaande Betalingen')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Openstaande Betalingen</h1>
            <div class="text-sm text-secondary-foreground">
                Tenants met actieve betaalmodule — status open, pending, mislukt of verlopen
            </div>
        </div>
        <div class="admin-page-actions flex flex-wrap items-center justify-end gap-2.5 w-full min-w-0 lg:w-auto">
            <a class="kt-btn kt-btn-outline min-w-0" href="{{ route('admin.payments.index') }}">
                <i class="ki-filled ki-arrow-left text-base me-2"></i>
                Terug naar Overzicht
            </a>
        </div>
    </div>

    @include('admin.payments.partials.tenant-filter', ['filterRoute' => 'admin.payments.openstaand'])

    <div class="kt-card kt-card-grid min-w-full">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Openstaande betalingen</h3>
            <form method="GET" action="{{ route('admin.payments.openstaand') }}" class="flex flex-wrap items-center gap-2">
                @if(request('company_id'))
                    <input type="hidden" name="company_id" value="{{ request('company_id') }}">
                @endif
                <label class="kt-input max-w-48">
                    <i class="ki-filled ki-magnifier"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Zoeken…" class="min-w-0" autocomplete="off">
                </label>
            </form>
        </div>
        <div class="kt-card-table">
            <div class="kt-scrollable-x-auto">
                <table class="kt-table kt-table-border">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Module</th>
                            <th>Bedrag</th>
                            <th>Referentie</th>
                            <th>Datum</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @include('admin.payments.partials.payment-rows', [
                            'payments' => $payments,
                            'emptyMessage' => 'Geen openstaande betalingen voor deze tenants.',
                            'paymentsReturnUrl' => request()->getRequestUri(),
                        ])
                    </tbody>
                </table>
            </div>
        </div>
        @if($payments->hasPages())
            <div class="kt-card-footer flex items-center justify-between">
                <div class="text-sm text-secondary-foreground">
                    {{ $payments->firstItem() }}–{{ $payments->lastItem() }} van {{ $payments->total() }}
                </div>
                {{ $payments->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
@endpush
@endsection
