{{-- Nexa Taxi dashboard-onderdelen (alleen tonen wanneer $showTaxi) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
    <div class="kt-card w-full">
        <div class="kt-card-body flex items-start justify-between gap-4 p-6">
            <div class="space-y-1 flex-1 min-w-0">
                <div class="text-sm text-secondary-foreground">Ritten totaal</div>
                <div class="text-2xl font-semibold text-mono">{{ $taxiStats['total_rides'] ?? 0 }}</div>
            </div>
            <span class="size-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                <i class="ki-filled ki-calendar text-primary text-xl"></i>
            </span>
        </div>
    </div>
    <div class="kt-card w-full">
        <div class="kt-card-body flex items-start justify-between gap-4 p-6">
            <div class="space-y-1 flex-1 min-w-0">
                <div class="text-sm text-secondary-foreground">Open / wachtend</div>
                <div class="text-2xl font-semibold text-mono">{{ $taxiStats['pending_rides'] ?? 0 }}</div>
            </div>
            <span class="size-12 rounded-full bg-warning/10 flex items-center justify-center shrink-0">
                <i class="ki-filled ki-time text-warning text-xl"></i>
            </span>
        </div>
    </div>
    <div class="kt-card w-full">
        <div class="kt-card-body flex items-start justify-between gap-4 p-6">
            <div class="space-y-1 flex-1 min-w-0">
                <div class="text-sm text-secondary-foreground">Actieve ritten</div>
                <div class="text-2xl font-semibold text-mono">{{ $taxiStats['active_rides'] ?? 0 }}</div>
            </div>
            <span class="size-12 rounded-full bg-info/10 flex items-center justify-center shrink-0">
                <i class="ki-filled ki-delivery-3 text-info text-xl"></i>
            </span>
        </div>
    </div>
    <div class="kt-card w-full">
        <div class="kt-card-body flex items-start justify-between gap-4 p-6">
            <div class="space-y-1 flex-1 min-w-0">
                <div class="text-sm text-secondary-foreground">Afgerond</div>
                <div class="text-2xl font-semibold text-mono">{{ $taxiStats['completed_rides'] ?? 0 }}</div>
            </div>
            <span class="size-12 rounded-full bg-success/10 flex items-center justify-center shrink-0">
                <i class="ki-filled ki-check-circle text-success text-xl"></i>
            </span>
        </div>
    </div>
</div>

<div class="kt-card w-full min-w-0">
    <div class="kt-card-header flex-wrap gap-3">
        <h3 class="kt-card-title">
            <i class="ki-filled ki-calendar me-2"></i>
            Recente ritten
        </h3>
        @if(Route::has('admin.taxi.ride_requests.index'))
            <a href="{{ route('admin.taxi.ride_requests.index') }}" class="kt-btn kt-btn-sm kt-btn-outline">Alle ritten</a>
        @endif
    </div>
    <div class="kt-card-content p-0 min-w-0">
        <div class="kt-scrollable-x-auto admin-dashboard-table-scroll">
            <table class="kt-table align-middle">
                <thead>
                    <tr>
                        <th>Route</th>
                        <th class="whitespace-nowrap">Status</th>
                        <th class="whitespace-nowrap">Datum</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent_rides as $ride)
                        @php
                            $statusLabels = \App\Modules\NexaTaxi\Models\RideRequest::statusLabels();
                        @endphp
                        <tr>
                            <td>
                                <div class="font-medium text-foreground text-sm">
                                    {{ Str::limit($ride->pickup_address ?? '—', 40) }}
                                </div>
                                <div class="text-xs text-secondary-foreground">
                                    → {{ Str::limit($ride->dropoff_address ?? '—', 40) }}
                                </div>
                            </td>
                            <td>
                                <span class="kt-badge kt-badge-sm kt-badge-outline">
                                    {{ $statusLabels[$ride->status] ?? $ride->status }}
                                </span>
                            </td>
                            <td class="text-sm text-muted-foreground">
                                {{ optional($ride->created_at)->format('d-m-Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted-foreground py-5">
                                Geen recente ritten
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
