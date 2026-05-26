@extends('admin.layouts.app')

@section('title', 'Notificatielog rit #'.$ride->id)

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Notificatielog</h1>
        <p class="text-sm text-muted-foreground">
            Overzicht van WhatsApp- en e-mailmeldingen voor rit #{{ $ride->id }}
            ({{ $ride->customer_name }} · {{ $ride->pickup_at->format('d-m-Y H:i') }}).
        </p>
        <div class="pt-3 flex flex-wrap gap-2.5">
            <a href="{{ route('admin.taxi.ride_requests.show', $ride) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug naar rit
            </a>
            <a href="{{ route('admin.taxi.ride_requests.index') }}" class="kt-btn kt-btn-outline">Alle ritten</a>
        </div>
    </div>

    @if($tableMissing)
        <div class="kt-alert kt-alert-warning mb-5">
            De notificatielog-tabel ontbreekt nog. Voer de taximodule-migraties uit om logging te activeren.
        </div>
    @endif

    <div class="kt-card min-w-full">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Verzonden meldingen</h3>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            @if($logs->isEmpty())
                <p class="px-5 py-6 text-sm text-muted-foreground">
                    @if($tableMissing)
                        Geen loggegevens beschikbaar.
                    @else
                        Er zijn nog geen notificaties gelogd voor deze rit.
                    @endif
                </p>
            @else
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                <thead>
                    <tr>
                        <th class="text-secondary-foreground font-normal text-left">Tijdstip</th>
                        <th class="text-secondary-foreground font-normal text-left">Kanaal</th>
                        <th class="text-secondary-foreground font-normal text-left">Status</th>
                        <th class="text-secondary-foreground font-normal text-left">Ontvanger</th>
                        <th class="text-secondary-foreground font-normal text-left">Adres</th>
                        <th class="text-secondary-foreground font-normal text-left">Toelichting</th>
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
                        <td class="font-mono text-xs">{{ $log->recipient_address ?? '—' }}</td>
                        <td class="max-w-md">{{ $log->detail ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>
@endsection
