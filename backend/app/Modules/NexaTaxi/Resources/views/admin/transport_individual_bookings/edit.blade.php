@extends('admin.layouts.app')

@section('title', 'Individuele contractrit bewerken')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Individuele contractrit bewerken</h1>
        <p class="text-sm text-muted-foreground pt-2">{{ $booking->passenger?->full_name ?? 'Rit' }} · {{ $contract->name }}</p>
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

    @if($hasOccurrence)
        <div class="kt-alert kt-alert-light mb-5" role="status">
            Deze rit is al gegenereerd naar een chauffeursrit. Wijzigingen gelden alleen voor toekomstige planning, niet voor de bestaande rit in de app.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.taxi.transport_individual_bookings.update', [$customer->id, $contract->id, $booking->id]) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="return" value="{{ old('return', $backUrl) }}">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header"><h3 class="kt-card-title mb-0">Ritgegevens</h3></div>
            <div class="kt-card-content p-0">
                <div class="px-3 sm:px-5 pb-3 min-w-0">
                    @include('taxi::admin.transport_individual_bookings.partials.form-fields')
                </div>
            </div>
        </div>
        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5">
            <a href="{{ $backUrl }}" class="kt-btn kt-btn-outline">Annuleren</a>
            <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
        </div>
    </form>
</div>
@endsection
