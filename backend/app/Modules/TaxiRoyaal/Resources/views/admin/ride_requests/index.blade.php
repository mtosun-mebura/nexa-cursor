@extends('admin.layouts.app')

@section('title', 'Ritten')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Ritten</h1>
        @can('rides.create')
        <a href="{{ route('admin.taxiroyaal.ride_requests.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>Nieuwe rit
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5"><i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5"><i class="ki-filled ki-cross-circle me-2"></i> {{ session('error') }}</div>
    @endif

    <div class="kt-card">
        <div class="kt-card-header py-5 flex-wrap gap-2">
            <h3 class="kt-card-title text-sm w-full">Overzicht ritten</h3>
            <form method="GET" action="{{ route('admin.taxiroyaal.ride_requests.index') }}" class="flex flex-wrap gap-2">
                <select name="status" class="kt-input text-sm w-40">
                    <option value="">Alle statussen</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="vehicle_id" class="kt-input text-sm w-48">
                    <option value="">Alle voertuigen</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="kt-input text-sm w-40" placeholder="Van">
                <input type="date" name="to" value="{{ request('to') }}" class="kt-input text-sm w-40" placeholder="Tot">
                <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">Filter</button>
            </form>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto">
            <table class="kt-table kt-table-border-dashed align-middle text-sm">
                <thead>
                    <tr>
                        <th class="text-secondary-foreground font-normal text-left">Datum/tijd</th>
                        <th class="text-secondary-foreground font-normal text-left">Klant</th>
                        <th class="text-secondary-foreground font-normal text-left">Route</th>
                        <th class="text-secondary-foreground font-normal text-left">Voertuig</th>
                        <th class="text-secondary-foreground font-normal text-left">Status</th>
                        <th class="text-secondary-foreground font-normal text-left">Prijs</th>
                        <th class="text-secondary-foreground font-normal text-right">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rideRequests as $r)
                    <tr>
                        <td>{{ $r->pickup_at->format('d-m-Y H:i') }}</td>
                        <td>{{ $r->customer_name }}@if($r->customer_phone)<br><span class="text-muted-foreground text-xs">{{ $r->customer_phone }}</span>@endif</td>
                        <td>{{ Str::limit($r->pickup_address, 20) }} → {{ Str::limit($r->dropoff_address, 20) }}</td>
                        <td>{{ $r->vehicle?->name ?? '—' }}</td>
                        <td>{{ $r->status_label }}</td>
                        <td>{{ $r->quoted_price !== null ? '€ '.number_format($r->quoted_price, 2, ',', '.') : '—' }}</td>
                        <td class="text-right">
                            @can('rides.view')
                            <a href="{{ route('admin.taxiroyaal.ride_requests.show', $r) }}" class="kt-btn kt-btn-sm kt-btn-ghost">Bekijken</a>
                            @endcan
                            @can('rides.update')
                            <a href="{{ route('admin.taxiroyaal.ride_requests.edit', $r) }}" class="kt-btn kt-btn-sm kt-btn-ghost">Bewerken</a>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-muted-foreground">Geen ritten gevonden.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rideRequests->hasPages())
        <div class="kt-card-footer flex justify-end">
            {{ $rideRequests->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
