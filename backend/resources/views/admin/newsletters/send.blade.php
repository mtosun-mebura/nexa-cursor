@extends('admin.layouts.app')

@section('title', __('Nieuwsbrief versturen'))

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">{{ __('Versturen') }}</h1>
        <p class="text-sm text-muted-foreground mt-2 mb-0">
            {{ __('Alleen opt-inadressen. Afgemelde bedrijven en eerdere ontvangers van dezelfde campagne worden overgeslagen.') }}
        </p>
        <div class="pt-3 flex w-full flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.newsletters.index') }}" class="kt-btn kt-btn-outline shrink-0">
                <i class="ki-filled ki-arrow-left me-2"></i>
                {{ __('Terug') }}
            </a>
            <a href="{{ route('admin.newsletters.prospects') }}" class="kt-btn kt-btn-primary shrink-0 ms-auto">{{ __('Klantenlijst') }}</a>
        </div>
    </div>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="kt-card w-full min-w-0">
        <div class="kt-card-header px-5 py-5">
            <h5 class="kt-card-title mb-0">{{ __(':count adressen in de opt-inlijst', ['count' => $subscribedCount]) }}</h5>
        </div>
        <div class="kt-card-content p-5 min-w-0">
            @if($campaigns->isEmpty())
                <p class="text-sm text-muted-foreground mb-0">{{ __('Maak eerst een nieuwsbrief in de designer.') }}</p>
            @else
                <form method="POST" action="{{ route('admin.newsletters.send.store') }}" id="send-form" class="min-w-0">
                    @csrf
                    <label class="kt-label mb-1" for="campaign_id">{{ __('Nieuwsbrief *') }}</label>
                    <select id="campaign_id" name="campaign_id" class="kt-select admin-field-fit mb-4" data-kt-select="true" required>
                        @foreach($campaigns as $campaign)
                            <option value="{{ $campaign->id }}">{{ $campaign->name }} — {{ $campaign->subject }}</option>
                        @endforeach
                    </select>
                    <p class="kt-label mb-2">{{ __('Alleen deze provincies (leeg = heel Nederland)') }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 mb-5">
                        @foreach($provinces as $province)
                            <label class="flex items-center gap-2 text-sm min-w-0">
                                <input type="checkbox" name="provinces[]" value="{{ $province }}" class="kt-checkbox shrink-0">
                                <span class="truncate">{{ $province }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button type="button" id="open-send-modal" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-send me-2"></i>
                        {{ __('Verstuur nieuwsbrief') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

@if($campaigns->isNotEmpty())
<div id="send-modal" class="fixed inset-0 z-[100000] hidden items-center justify-center bg-slate-900/45 p-4 backdrop-blur-md" role="dialog" aria-modal="true" aria-labelledby="send-modal-title">
    <div class="w-full max-w-lg rounded-2xl border border-border bg-background shadow-2xl">
        <div class="border-b border-border px-6 py-5">
            <h2 id="send-modal-title" class="text-lg font-semibold text-foreground mb-1">{{ __('Nieuwsbrief versturen?') }}</h2>
            <p class="text-sm text-muted-foreground mb-0">
                {{ __('De mail gaat naar de opt-inlijst via het NEXA Suite-mailaccount. Elk bericht heeft een afmeldlink. Wie zich afmeldt, krijgt de volgende ronde niet.') }}
            </p>
        </div>
        <div class="px-6 py-5 flex justify-end gap-2">
            <button type="button" id="cancel-send-modal" class="kt-btn kt-btn-outline">{{ __('Annuleren') }}</button>
            <button type="button" id="confirm-send-modal" class="kt-btn kt-btn-primary">{{ __('Versturen') }}</button>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if($campaigns->isNotEmpty())
<script>
(function () {
    const modal = document.getElementById('send-modal');
    const form = document.getElementById('send-form');
    document.getElementById('open-send-modal')?.addEventListener('click', function () {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });
    document.getElementById('cancel-send-modal')?.addEventListener('click', function () {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });
    document.getElementById('confirm-send-modal')?.addEventListener('click', function () {
        this.disabled = true;
        form.submit();
    });
})();
</script>
@endif
@endpush
