@extends('admin.layouts.app')

@section('title', 'Rit #'.$ride->id)

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center gap-5 pb-7.5">
        <a href="{{ route('admin.taxiroyaal.ride_requests.index') }}" class="kt-btn kt-btn-outline"><i class="ki-filled ki-arrow-left me-2"></i>Terug</a>
        <h1 class="text-xl font-medium leading-none text-mono">Rit #{{ $ride->id }}</h1>
        @can('rides.update')
        <a href="{{ route('admin.taxiroyaal.ride_requests.edit', $ride) }}" class="kt-btn kt-btn-outline">Bewerken</a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5"><i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}</div>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <div class="kt-card">
            <div class="kt-card-header"><h3 class="kt-card-title">Route &amp; datum</h3></div>
            <div class="kt-card-content space-y-3 text-sm">
                <p><span class="text-muted-foreground">Ophalen:</span> {{ $ride->pickup_address }}</p>
                <p><span class="text-muted-foreground">Afzetten:</span> {{ $ride->dropoff_address }}</p>
                <p><span class="text-muted-foreground">Datum/tijd:</span> {{ $ride->pickup_at->format('d-m-Y H:i') }}</p>
                @if($ride->distance_km !== null)<p><span class="text-muted-foreground">Afstand:</span> {{ $ride->distance_km }} km</p>@endif
                @if($ride->duration_minutes !== null)<p><span class="text-muted-foreground">Rijtijd:</span> {{ $ride->duration_minutes }} min</p>@endif
                <p><span class="text-muted-foreground">Passagiers:</span> {{ $ride->passengers }}</p>
                <p><span class="text-muted-foreground">Status:</span> {{ $ride->status_label }}</p>
                @if($ride->quoted_price !== null)<p><span class="text-muted-foreground">Offerteprijs:</span> € {{ number_format($ride->quoted_price, 2, ',', '.') }}</p>@endif
            </div>
        </div>
        <div class="kt-card">
            <div class="kt-card-header"><h3 class="kt-card-title">Klant &amp; toewijzing</h3></div>
            <div class="kt-card-content space-y-3 text-sm">
                <p><span class="text-muted-foreground">Naam:</span> {{ $ride->customer_name }}</p>
                @if($ride->customer_email)<p><span class="text-muted-foreground">E-mail:</span> <a href="mailto:{{ $ride->customer_email }}">{{ $ride->customer_email }}</a></p>@endif
                @if($ride->customer_phone)<p><span class="text-muted-foreground">Telefoon:</span> <a href="tel:{{ $ride->customer_phone }}">{{ $ride->customer_phone }}</a></p>@endif
                @if($ride->customer_note)<p><span class="text-muted-foreground">Opmerking:</span> {{ $ride->customer_note }}</p>@endif
                <p><span class="text-muted-foreground">Voertuig:</span> {{ $ride->vehicle?->name ?? '—' }}</p>
                <p><span class="text-muted-foreground">Chauffeur:</span> {{ $ride->driver ? $ride->driver->first_name . ' ' . $ride->driver->last_name : '—' }}</p>
            </div>
        </div>
    </div>

    @can('rides.update')
    <div class="kt-card mt-5">
        <div class="kt-card-header"><h3 class="kt-card-title">Toewijzen</h3></div>
        <div class="kt-card-content">
            <form action="{{ route('admin.taxiroyaal.ride_requests.assign', $ride) }}" method="POST" class="flex flex-wrap gap-4 items-end">
                @csrf
                <div>
                    <label class="kt-form-label">Voertuig</label>
                    <select name="vehicle_id" class="kt-input w-48">
                        <option value="">— Geen —</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" {{ $ride->vehicle_id == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="kt-form-label">Chauffeur</label>
                    <select name="driver_id" class="kt-input w-48">
                        <option value="">— Geen —</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}" {{ $ride->driver_id == $d->id ? 'selected' : '' }}>{{ $d->first_name }} {{ $d->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="kt-btn kt-btn-primary">Toewijzing opslaan</button>
            </form>
        </div>
    </div>
    <div class="kt-card mt-5">
        <div class="kt-card-header"><h3 class="kt-card-title">Communicatie</h3></div>
        <div class="kt-card-content">
            <p class="text-sm text-muted-foreground mb-2">Kopieer onderstaande tekst voor WhatsApp of e-mail naar de klant.</p>
            <textarea readonly class="kt-input w-full font-mono text-sm" rows="6" id="whatsapp-text">Rit {{ $ride->pickup_at->format('d-m-Y H:i') }}
Ophalen: {{ $ride->pickup_address }}
Afzetten: {{ $ride->dropoff_address }}
@if($ride->quoted_price)Geschatte prijs: € {{ number_format($ride->quoted_price, 2, ',', '.') }}
@endif
– Taxi Royaal</textarea>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-outline mt-2" onclick="navigator.clipboard.writeText(document.getElementById('whatsapp-text').value); this.textContent='Gekopieerd!'; setTimeout(() => this.textContent='Kopieer WhatsApp-tekst', 2000);">Kopieer WhatsApp-tekst</button>
        </div>
    </div>
    @endcan
</div>
@endsection
