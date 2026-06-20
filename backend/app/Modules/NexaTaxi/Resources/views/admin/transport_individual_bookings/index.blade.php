@extends('admin.layouts.app')

@section('title', 'Individuele contractritten')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Individuele contractritten</h1>
            <p class="text-sm text-muted-foreground pt-2">{{ $contract->name }} · {{ $customer->name }}</p>
            <div class="pt-3">
                <a href="{{ $backUrl }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug
                </a>
            </div>
        </div>
        @can('rides.create')
        <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_individual_bookings.create', [$customer->id, $contract->id]), url()->current()) }}" class="kt-btn kt-btn-primary shrink-0">
            Nieuwe rit
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <div class="kt-card kt-card-grid w-full min-w-0">
        <div class="kt-card-header py-5 flex-wrap gap-2">
            <form method="GET" class="flex flex-wrap gap-2 w-full sm:w-auto sm:ms-auto">
                <label class="kt-input w-full sm:w-64">
                    <i class="ki-filled ki-magnifier"></i>
                    <input placeholder="Zoek passagier of adres..." type="text" name="search" value="{{ request('search') }}">
                </label>
                <select class="kt-select w-full sm:w-40" name="status" onchange="this.form.submit()">
                    <option value="">Alle statussen</option>
                    @foreach(\App\Modules\NexaTaxi\Models\TransportIndividualBooking::statusLabels() as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm">Zoeken</button>
            </form>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                    <thead>
                        <tr>
                            <th>Passagier</th>
                            <th>Ophalen</th>
                            <th>Route</th>
                            <th>Status</th>
                            @can('rides.update')
                            <th></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                        <tr
                            @can('rides.update')
                            data-row-href="{{ transport_admin_url_with_return(route('admin.taxi.transport_individual_bookings.edit', [$customer->id, $contract->id, $booking->id]), url()->current()) }}"
                            class="cursor-pointer hover:bg-muted/40"
                            tabindex="0"
                            role="link"
                            @endcan
                        >
                            <td class="font-medium">{{ $booking->passenger?->full_name ?? '—' }}</td>
                            <td class="text-muted-foreground whitespace-nowrap">{{ $booking->pickup_at?->format('d-m-Y H:i') ?? '—' }}</td>
                            <td class="text-muted-foreground">
                                {{ Str::limit($booking->pickup_address, 28) }}
                                →
                                {{ Str::limit($booking->dropoff_address, 28) }}
                            </td>
                            <td>
                                @if($booking->status === 'planned')
                                    <span class="kt-badge kt-badge-light kt-badge-sm">Gepland</span>
                                @else
                                    <span class="kt-badge kt-badge-secondary kt-badge-sm">Geannuleerd</span>
                                @endif
                            </td>
                            @can('rides.update')
                            <td class="text-end">
                                @if($booking->status === 'planned')
                                <form method="POST" action="{{ route('admin.taxi.transport_individual_bookings.destroy', [$customer->id, $contract->id, $booking->id]) }}" class="inline" onclick="event.stopPropagation()" onsubmit="return confirm('Rit annuleren?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive" title="Annuleren" aria-label="Annuleren">
                                        <i class="ki-filled ki-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                            @endcan
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->can('rides.update') ? 5 : 4 }}" class="text-center text-muted-foreground py-8">
                                Nog geen individuele contractritten.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($bookings->hasPages())
            <div class="px-3 sm:px-5 py-4 border-t border-input">
                {{ $bookings->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
