@extends('admin.layouts.app')

@section('title', 'Nieuwe individuele contractrit')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Nieuwe individuele contractrit</h1>
        <p class="text-sm text-muted-foreground pt-2">{{ $contract->name }} · {{ $customer->name }}</p>
        <div class="pt-3">
            <a href="{{ $backUrl }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($passengers->isEmpty())
        <div class="kt-alert kt-alert-warning mb-5" role="alert">
            Voeg eerst een <a href="{{ route('admin.taxi.transport_passengers.create', [$customer->id, $contract->id]) }}" class="text-primary hover:underline">passagier</a> toe aan dit abonnement.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.taxi.transport_individual_bookings.store', [$customer->id, $contract->id]) }}">
        @csrf
        <input type="hidden" name="return" value="{{ old('return', $backUrl) }}">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header"><h3 class="kt-card-title mb-0">Ritgegevens</h3></div>
            <div class="kt-card-content p-0">
                <div class="px-3 sm:px-5 pb-3 min-w-0">
                    @include('taxi::admin.transport_individual_bookings.partials.form-fields', ['booking' => null])
                </div>
            </div>
        </div>
        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5">
            <a href="{{ $backUrl }}" class="kt-btn kt-btn-outline">Annuleren</a>
            <button type="submit" class="kt-btn kt-btn-primary" @disabled($passengers->isEmpty())>Opslaan</button>
        </div>
    </form>
</div>
@endsection
