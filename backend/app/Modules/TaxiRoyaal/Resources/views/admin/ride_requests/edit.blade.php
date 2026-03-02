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
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Status *</td>
                    <td class="min-w-48 w-full">
                        <select name="status" class="kt-input w-full @error('status') border-destructive @enderror" required>
                            @foreach($statusLabels as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $ride->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Ophaaladres *</td>
                    <td class="min-w-48 w-full">
                        <input type="text" name="pickup_address" class="kt-input w-full @error('pickup_address') border-destructive @enderror" value="{{ old('pickup_address', $ride->pickup_address) }}" required readonly>
                        @error('pickup_address')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Afzetadres *</td>
                    <td class="min-w-48 w-full">
                        <input type="text" name="dropoff_address" class="kt-input w-full @error('dropoff_address') border-destructive @enderror" value="{{ old('dropoff_address', $ride->dropoff_address) }}" required readonly>
                        @error('dropoff_address')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Datum en tijd ophalen *</td>
                    <td class="min-w-48 w-full">
                        <input type="datetime-local" name="pickup_at" class="kt-input w-full @error('pickup_at') border-destructive @enderror" value="{{ old('pickup_at', $ride->pickup_at->format('Y-m-d\TH:i')) }}" required readonly>
                        @error('pickup_at')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Passagiers *</td>
                    <td class="min-w-48 w-full">
                        <input type="number" name="passengers" class="kt-input w-full @error('passengers') border-destructive @enderror" value="{{ old('passengers', $ride->passengers) }}" min="1" max="99" required readonly>
                        @error('passengers')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Offerteprijs (€)</td>
                    <td class="min-w-48 w-full">
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">€</span>
                            <input type="number" name="quoted_price" class="kt-input w-full pl-6 @error('quoted_price') border-destructive @enderror" value="{{ old('quoted_price', $ride->quoted_price) }}" step="0.01" min="0" readonly>
                        </div>
                        @error('quoted_price')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Klantnaam *</td>
                    <td class="min-w-48 w-full">
                        <input type="text" name="customer_name" class="kt-input w-full @error('customer_name') border-destructive @enderror" value="{{ old('customer_name', $ride->customer_name) }}" required readonly>
                        @error('customer_name')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">E-mail klant</td>
                    <td class="min-w-48 w-full">
                        <input type="email" name="customer_email" class="kt-input w-full @error('customer_email') border-destructive @enderror" value="{{ old('customer_email', $ride->customer_email) }}" readonly>
                        @error('customer_email')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Telefoon klant</td>
                    <td class="min-w-48 w-full">
                        <input type="text" name="customer_phone" class="kt-input w-full @error('customer_phone') border-destructive @enderror" value="{{ old('customer_phone', $ride->customer_phone) }}" readonly>
                        @error('customer_phone')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Opmerking</td>
                    <td class="min-w-48 w-full">
                        <textarea name="customer_note" class="kt-input w-full @error('customer_note') border-destructive @enderror" rows="2" readonly>{{ old('customer_note', $ride->customer_note) }}</textarea>
                        @error('customer_note')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Voertuig</td>
                    <td class="min-w-48 w-full">
                        <select name="vehicle_id" class="kt-input w-full @error('vehicle_id') border-destructive @enderror">
                            <option value="">— Geen —</option>
                            @foreach($vehicles as $v)
                                <option value="{{ $v->id }}" {{ old('vehicle_id', $ride->vehicle_id) == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_id')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Chauffeur</td>
                    <td class="min-w-48 w-full">
                        <select name="driver_id" class="kt-input w-full @error('driver_id') border-destructive @enderror">
                            <option value="">— Geen —</option>
                            @foreach($drivers as $d)
                                <option value="{{ $d->id }}" {{ old('driver_id', $ride->driver_id) == $d->id ? 'selected' : '' }}>{{ $d->first_name }} {{ $d->last_name }}</option>
                            @endforeach
                        </select>
                        @error('driver_id')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
            </table>
        </div>
        <div class="kt-card-footer flex justify-end gap-2">
            <a href="{{ route('admin.taxiroyaal.ride_requests.show', $ride) }}" class="kt-btn kt-btn-outline">Annuleren</a>
            <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
        </div>
    </form>
</div>
@endsection
