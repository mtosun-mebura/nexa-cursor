@extends('admin.layouts.app')

@section('title', 'Nieuwe rit')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Nieuwe rit</h1>
        <a href="{{ route('admin.taxi.ride_requests.index') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>Terug
        </a>
    </div>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('admin.taxi.ride_requests.store') }}" method="POST" class="kt-card w-full min-w-0">
        @csrf
        <div class="kt-card-header">
            <h3 class="kt-card-title mb-0">Ritgegevens</h3>
        </div>
        <div class="kt-card-content p-0">
            <div class="px-3 sm:px-5 pb-3 min-w-0">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Status *</td>
                        <td class="min-w-48 w-full">
                            <select name="status" class="kt-input w-full @error('status') border-destructive @enderror" required>
                                @foreach($statusLabels as $value => $label)
                                    <option value="{{ $value }}" {{ old('status', 'draft') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Ophaaladres *</td>
                        <td class="min-w-48 w-full">
                            @include('admin.partials.google-address-input', [
                                'name' => 'pickup_address',
                                'value' => old('pickup_address'),
                                'latName' => 'pickup_lat',
                                'lngName' => 'pickup_lng',
                                'latValue' => old('pickup_lat'),
                                'lngValue' => old('pickup_lng'),
                                'required' => true,
                                'placeholder' => 'Zoek ophaaladres...',
                            ])
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Afzetadres *</td>
                        <td class="min-w-48 w-full">
                            @include('admin.partials.google-address-input', [
                                'name' => 'dropoff_address',
                                'value' => old('dropoff_address'),
                                'latName' => 'dropoff_lat',
                                'lngName' => 'dropoff_lng',
                                'latValue' => old('dropoff_lat'),
                                'lngValue' => old('dropoff_lng'),
                                'required' => true,
                                'placeholder' => 'Zoek afzetadres...',
                            ])
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Datum en tijd ophalen *</td>
                        <td class="min-w-48 w-full">
                            <input type="datetime-local" name="pickup_at" class="kt-input w-full @error('pickup_at') border-destructive @enderror" value="{{ old('pickup_at') }}" required>
                            @error('pickup_at')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Passagiers *</td>
                        <td class="min-w-48 w-full">
                            <input type="number" name="passengers" class="kt-input w-full @error('passengers') border-destructive @enderror" value="{{ old('passengers', 1) }}" min="1" max="99" required>
                            @error('passengers')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Offerteprijs (€)</td>
                        <td class="min-w-48 w-full">
                            <div class="flex items-center gap-2 w-full max-w-md">
                                <span class="shrink-0 text-sm font-medium text-foreground" aria-hidden="true">€</span>
                                <input type="number" name="quoted_price" class="kt-input w-full min-w-0 @error('quoted_price') border-destructive @enderror" value="{{ old('quoted_price') }}" step="0.01" min="0">
                            </div>
                            @error('quoted_price')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Klantnaam *</td>
                        <td class="min-w-48 w-full">
                            <input type="text" name="customer_name" class="kt-input w-full @error('customer_name') border-destructive @enderror" value="{{ old('customer_name') }}" required>
                            @error('customer_name')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">E-mail klant</td>
                        <td class="min-w-48 w-full">
                            <input type="email" name="customer_email" class="kt-input w-full @error('customer_email') border-destructive @enderror" value="{{ old('customer_email') }}">
                            @error('customer_email')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Telefoon klant</td>
                        <td class="min-w-48 w-full">
                            <input type="text" name="customer_phone" class="kt-input w-full @error('customer_phone') border-destructive @enderror" value="{{ old('customer_phone') }}">
                            @error('customer_phone')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Opmerking klant</td>
                        <td class="min-w-48 w-full">
                            <textarea name="customer_note" class="kt-input w-full @error('customer_note') border-destructive @enderror" rows="2">{{ old('customer_note') }}</textarea>
                            @error('customer_note')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Voertuig</td>
                        <td class="min-w-48 w-full">
                            <select name="vehicle_id" class="kt-input w-full @error('vehicle_id') border-destructive @enderror">
                                <option value="">— Nog niet toegewezen —</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}" {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                                @endforeach
                            </select>
                            @error('vehicle_id')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Chauffeur</td>
                        <td class="min-w-48 w-full">
                            <select name="driver_id" class="kt-input w-full @error('driver_id') border-destructive @enderror">
                                <option value="">— Nog niet toegewezen —</option>
                                @foreach($drivers as $d)
                                    <option value="{{ $d->id }}" {{ old('driver_id') == $d->id ? 'selected' : '' }}>{{ $d->first_name }} {{ $d->last_name }}</option>
                                @endforeach
                            </select>
                            @error('driver_id')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 px-3 sm:px-5 pb-5 w-full min-w-0">
            <a href="{{ route('admin.taxi.ride_requests.index') }}" class="kt-btn kt-btn-outline">Annuleren</a>
            <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
        </div>
    </form>
</div>
@endsection
