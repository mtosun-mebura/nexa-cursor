@extends('admin.layouts.app')

@section('title', 'Rit bewerken')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center gap-5 pb-7.5">
        <a href="{{ route('admin.taxiroyaal.ride_requests.index') }}" class="kt-btn kt-btn-outline"><i class="ki-filled ki-arrow-left me-2"></i>Terug</a>
        <h1 class="text-xl font-medium leading-none text-mono">Rit bewerken #{{ $ride->id }}</h1>
    </div>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('admin.taxiroyaal.ride_requests.update', $ride) }}" method="POST" class="kt-card">
        @csrf
        @method('PUT')
        <div class="kt-card-content">
            <div class="grid gap-5 max-w-2xl">
                <div>
                    <label class="kt-form-label">Status *</label>
                    <select name="status" class="kt-input w-full" required>
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" {{ old('status', $ride->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="kt-form-label">Ophaaladres *</label>
                    <input type="text" name="pickup_address" class="kt-input w-full" value="{{ old('pickup_address', $ride->pickup_address) }}" required>
                </div>
                <div>
                    <label class="kt-form-label">Afzetadres *</label>
                    <input type="text" name="dropoff_address" class="kt-input w-full" value="{{ old('dropoff_address', $ride->dropoff_address) }}" required>
                </div>
                <div>
                    <label class="kt-form-label">Datum en tijd ophalen *</label>
                    <input type="datetime-local" name="pickup_at" class="kt-input w-full" value="{{ old('pickup_at', $ride->pickup_at->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div>
                    <label class="kt-form-label">Passagiers *</label>
                    <input type="number" name="passengers" class="kt-input w-full" value="{{ old('passengers', $ride->passengers) }}" min="1" max="99" required>
                </div>
                <div>
                    <label class="kt-form-label">Offerteprijs (€)</label>
                    <input type="number" name="quoted_price" class="kt-input w-full" value="{{ old('quoted_price', $ride->quoted_price) }}" step="0.01" min="0">
                </div>
                <div>
                    <label class="kt-form-label">Klantnaam *</label>
                    <input type="text" name="customer_name" class="kt-input w-full" value="{{ old('customer_name', $ride->customer_name) }}" required>
                </div>
                <div>
                    <label class="kt-form-label">E-mail klant</label>
                    <input type="email" name="customer_email" class="kt-input w-full" value="{{ old('customer_email', $ride->customer_email) }}">
                </div>
                <div>
                    <label class="kt-form-label">Telefoon klant</label>
                    <input type="text" name="customer_phone" class="kt-input w-full" value="{{ old('customer_phone', $ride->customer_phone) }}">
                </div>
                <div>
                    <label class="kt-form-label">Opmerking klant</label>
                    <textarea name="customer_note" class="kt-input w-full" rows="2">{{ old('customer_note', $ride->customer_note) }}</textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="kt-form-label">Voertuig</label>
                        <select name="vehicle_id" class="kt-input w-full">
                            <option value="">— Geen —</option>
                            @foreach($vehicles as $v)
                                <option value="{{ $v->id }}" {{ old('vehicle_id', $ride->vehicle_id) == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="kt-form-label">Chauffeur</label>
                        <select name="driver_id" class="kt-input w-full">
                            <option value="">— Geen —</option>
                            @foreach($drivers as $d)
                                <option value="{{ $d->id }}" {{ old('driver_id', $ride->driver_id) == $d->id ? 'selected' : '' }}>{{ $d->first_name }} {{ $d->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="kt-card-footer flex justify-end gap-2">
            <a href="{{ route('admin.taxiroyaal.ride_requests.show', $ride) }}" class="kt-btn kt-btn-outline">Annuleren</a>
            <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
        </div>
    </form>
</div>
@endsection
