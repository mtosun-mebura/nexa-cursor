@extends('admin.layouts.app')

@section('title', 'Notificatielog rit #'.$ride->id)

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div class="min-w-0">
            <h1 class="text-xl font-medium leading-none text-mono">Notificatielog</h1>
            <p class="text-sm text-muted-foreground mt-2 mb-0 leading-relaxed">
                Overzicht van WhatsApp- en e-mailmeldingen voor rit #{{ $ride->id }}
                ({{ $ride->customer_name }} · {{ $ride->pickup_at->format('d-m-Y H:i') }}).
            </p>
        </div>
    </div>
    <div class="admin-page-actions flex flex-wrap gap-2.5 mb-5 w-full min-w-0">
        <a href="{{ route('admin.taxi.ride_requests.show', $ride) }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug naar rit
        </a>
        <a href="{{ route('admin.taxi.ride_requests.index') }}" class="kt-btn kt-btn-outline shrink-0">Alle ritten</a>
    </div>

    @if($tableMissing)
        <div class="kt-alert kt-alert-warning mb-5">
            De notificatielog-tabel ontbreekt nog. Voer de taximodule-migraties uit om logging te activeren.
        </div>
    @endif

    <div class="kt-card w-full min-w-0">
        <div class="kt-card-header">
            <h3 class="kt-card-title mb-0">Verzonden meldingen</h3>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            @if($logs->isEmpty())
                <p class="px-3 sm:px-5 py-6 text-sm text-muted-foreground mb-0">
                    @if($tableMissing)
                        Geen loggegevens beschikbaar.
                    @else
                        Er zijn nog geen notificaties gelogd voor deze rit.
                    @endif
                </p>
            @else
            <div class="kt-table-responsive kt-scrollable-x-auto admin-table-scroll-wrap px-3 sm:px-5 pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full min-w-[40rem]">
                <thead>
                    <tr>
                        <th class="text-secondary-foreground font-normal text-left" data-label="Tijdstip">Tijdstip</th>
                        <th class="text-secondary-foreground font-normal text-left" data-label="Kanaal">Kanaal</th>
                        <th class="text-secondary-foreground font-normal text-left" data-label="Status">Status</th>
                        <th class="text-secondary-foreground font-normal text-left" data-label="Ontvanger">Ontvanger</th>
                        <th class="text-secondary-foreground font-normal text-left" data-label="Adres">Adres</th>
                        <th class="text-secondary-foreground font-normal text-left" data-label="Toelichting">Toelichting</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap">{{ $log->created_at?->format('d-m-Y H:i:s') ?? '—' }}</td>
                        <td>{{ $log->channel_label }}</td>
                        <td>
                            @if($log->status === \App\Modules\NexaTaxi\Models\RideRequestNotificationLog::STATUS_SENT)
                                <span class="text-success font-medium">{{ $log->status_label }}</span>
                            @elseif($log->status === \App\Modules\NexaTaxi\Models\RideRequestNotificationLog::STATUS_FAILED)
                                <span class="text-destructive font-medium">{{ $log->status_label }}</span>
                            @else
                                <span class="text-muted-foreground">{{ $log->status_label }}</span>
                            @endif
                        </td>
                        <td>{{ $log->recipient_name ?? '—' }}</td>
                        <td class="font-mono text-xs break-all">{{ $log->recipient_address ?? '—' }}</td>
                        <td class="min-w-0 max-w-xs sm:max-w-md break-words">{{ $log->detail ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
