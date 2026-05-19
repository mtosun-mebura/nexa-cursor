@extends('admin.layouts.app')

@section('title', 'Rit #'.$ride->id)

@section('content')
<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }
</style>

<div class="bg-center bg-cover bg-no-repeat hero-bg">
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
            @if($ride->vehicle?->image_url)
                <img
                    src="{{ app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($ride->vehicle->image_url) }}"
                    alt="{{ $ride->vehicle->name ?? 'Voertuig' }}"
                    class="max-w-sm max-h-48 object-contain rounded-lg border border-border bg-white"
                >
            @elseif($ride->vehicle)
                <div class="w-full max-w-sm h-48 rounded-lg border border-border flex items-center justify-center bg-primary/10 text-primary text-3xl font-semibold">
                    <i class="ki-filled ki-car"></i>
                </div>
            @endif

            <div class="text-lg leading-5 font-semibold text-mono text-center">
                Rit #{{ $ride->id }}
            </div>

            <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
                <div class="flex gap-1.25 items-center">
                    <x-heroicon-o-building-office-2 class="w-4 h-4 text-muted-foreground" />
                    <span class="text-secondary-foreground font-medium">{{ $ride->vehicle?->company?->name ?? '—' }}</span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-car text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">{{ $ride->vehicle?->name ?? '—' }}</span>
                </div>
                <div class="flex gap-1.25 items-center">
                    <i class="ki-filled ki-calendar text-muted-foreground text-sm"></i>
                    <span class="text-secondary-foreground font-medium">{{ $ride->pickup_at->format('d-m-Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center gap-5 pb-7.5">
        <a href="{{ route('admin.taxi.ride_requests.index') }}" class="kt-btn kt-btn-outline"><i class="ki-filled ki-arrow-left me-2"></i>Terug</a>
        @can('rides.update')
        <a href="{{ route('admin.taxi.ride_requests.edit', $ride) }}" class="kt-btn kt-btn-outline">Bewerken</a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5"><i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-5">
        <div class="kt-card">
            <div class="kt-card-header"><h3 class="kt-card-title">Route &amp; datum</h3></div>
            <div class="kt-card-content space-y-3 text-sm">
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Ophalen:</span><span class="flex-1">{{ $ride->pickup_address }}</span></p>
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Afzetten:</span><span class="flex-1">{{ $ride->dropoff_address }}</span></p>
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Datum/tijd:</span><span class="flex-1">{{ $ride->pickup_at->format('d-m-Y H:i') }}</span></p>
                @if($ride->distance_km !== null)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Afstand:</span><span class="flex-1">{{ $ride->distance_km }} km</span></p>
                @endif
                @if($ride->duration_minutes !== null)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Rijtijd:</span><span class="flex-1">{{ $ride->duration_minutes }} min</span></p>
                @endif
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Passagiers:</span><span class="flex-1">{{ $ride->passengers }}</span></p>
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Status:</span><span class="flex-1">{{ $ride->status_label }}</span></p>
                @if($ride->quoted_price !== null)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Offerteprijs:</span><span class="flex-1">€ {{ number_format($ride->quoted_price, 2, ',', '.') }}</span></p>
                @endif
            </div>
        </div>
        <div class="kt-card">
            <div class="kt-card-header"><h3 class="kt-card-title">Klant &amp; toewijzing</h3></div>
            <div class="kt-card-content space-y-3 text-sm">
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Naam:</span><span class="flex-1">{{ $ride->customer_name }}</span></p>
                @if($ride->customer_email)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">E-mail:</span><span class="flex-1"><a href="mailto:{{ $ride->customer_email }}">{{ $ride->customer_email }}</a></span></p>
                @endif
                @if($ride->customer_phone)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Telefoon:</span><span class="flex-1"><a href="tel:{{ $ride->customer_phone }}">{{ $ride->customer_phone }}</a></span></p>
                @endif
                @if($ride->customer_note)
                    <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Opmerking:</span><span class="flex-1">{{ $ride->customer_note }}</span></p>
                @endif
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Voertuig:</span><span class="flex-1">{{ $ride->vehicle?->name ?? '—' }}</span></p>
                <p class="flex items-start gap-2"><span class="text-muted-foreground w-28 shrink-0">Chauffeur:</span><span class="flex-1">{{ $ride->driver ? $ride->driver->first_name . ' ' . $ride->driver->last_name : '—' }}</span></p>
            </div>
        </div>
    </div>

    @can('rides.update')
    <div class="kt-card mt-5">
        <div class="kt-card-header"><h3 class="kt-card-title">Toewijzen</h3></div>
        <div class="kt-card-content">
            <form action="{{ route('admin.taxi.ride_requests.assign', $ride) }}" method="POST" class="flex flex-wrap gap-4 items-end">
                @csrf
                <div class="flex flex-col gap-2.5">
                    <label class="kt-form-label">Voertuig</label>
                    <select name="vehicle_id" class="kt-input w-48">
                        <option value="">— Geen —</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" {{ $ride->vehicle_id == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-2.5">
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
            <textarea readonly class="kt-input w-full font-mono text-sm resize-y pt-1" rows="8" id="whatsapp-text" style="min-height: 12rem !important; height: auto !important; box-sizing: border-box;">Rit {{ $ride->pickup_at->format('d-m-Y H:i') }}
Ophalen: {{ $ride->pickup_address }}
Afzetten: {{ $ride->dropoff_address }}
@if($ride->quoted_price)Geschatte prijs: € {{ number_format($ride->quoted_price, 2, ',', '.') }}
@endif
– Nexa Taxi</textarea>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-outline mt-2" onclick="navigator.clipboard.writeText(document.getElementById('whatsapp-text').value); this.textContent='Gekopieerd!'; setTimeout(() => this.textContent='Kopieer WhatsApp-tekst', 2000);">Kopieer WhatsApp-tekst</button>
        </div>
    </div>
    @endcan
</div>
@endsection
