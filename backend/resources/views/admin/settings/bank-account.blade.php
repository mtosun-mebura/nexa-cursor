@extends('admin.layouts.app')

@section('title', 'Bankrekening')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono mb-1">Bankrekening</h1>
            <p class="text-sm text-muted-foreground mb-0">
                Rekeningnummer voor uitbetaling van Nexa Suite-ritten (na aftrek van de marketplace-fee).
            </p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    </div>

    <form method="POST" action="{{ route('admin.settings.bank-account.update') }}" data-validate="true" novalidate id="bank-account-form">
        @csrf
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5">
                    <h3 class="kt-card-title mb-0">{{ $company->name }}</h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Huidige rekening</td>
                                <td class="min-w-48 w-full font-medium text-foreground">
                                    {{ $payload['masked_destination'] ?: 'Nog niet ingevuld' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">IBAN</td>
                                <td class="min-w-48 w-full">
                                    <input type="text"
                                           name="iban"
                                           id="iban"
                                           class="kt-input @error('iban') border-destructive @enderror"
                                           placeholder="NL91 ABNA 0417 1643 00"
                                           value="{{ old('iban') }}"
                                           autocomplete="off"
                                           data-validate-as="iban"
                                           maxlength="42"
                                           @error('iban') aria-invalid="true" @enderror
                                           required>
                                    <p class="text-xs text-muted-foreground mt-1 mb-0">
                                        Verplicht voor uitbetaling. We bewaren alleen *** + laatste 4 cijfers (bijv. ***4300).
                                    </p>
                                    <div class="field-feedback text-sm mt-1" data-field="iban" aria-live="polite"></div>
                                    @error('iban')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="iban" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            @if(filled($identity?->masked_destination))
                            @unless($authViaCode ?? false)
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal">Huidig wachtwoord</td>
                                <td class="min-w-48 w-full">
                                    <input type="password"
                                           name="password"
                                           id="bank_password"
                                           class="kt-input @error('password') border-destructive @enderror"
                                           autocomplete="current-password"
                                           @error('password') aria-invalid="true" @enderror>
                                    <p class="text-xs text-muted-foreground mt-1 mb-0">
                                        Optioneel als je een wachtwoord hebt. Anders: e-mailcode hieronder.
                                    </p>
                                    <div class="field-feedback text-sm mt-1" data-field="password" aria-live="polite"></div>
                                    @error('password')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="password" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            @endunless
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">
                                    {{ ($authViaCode ?? false) ? 'Bevestigingscode' : 'Of e-mailcode' }}
                                </td>
                                <td class="min-w-48 w-full">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input type="text"
                                               name="confirmation_code"
                                               id="confirmation_code"
                                               class="kt-input admin-field-fit tracking-widest text-center @error('confirmation_code') border-destructive @enderror"
                                               inputmode="numeric"
                                               maxlength="6"
                                               autocomplete="one-time-code"
                                               placeholder="000000"
                                               value="{{ old('confirmation_code') }}"
                                               @error('confirmation_code') aria-invalid="true" @enderror
                                               {{ ($authViaCode ?? false) ? 'required' : '' }}>
                                        <button type="button"
                                                id="bank-send-code-btn"
                                                class="kt-btn kt-btn-outline shrink-0"
                                                data-url="{{ route('admin.settings.bank-account.send-code') }}">
                                            Code sturen
                                        </button>
                                    </div>
                                    <p class="text-xs text-muted-foreground mt-1 mb-0">
                                        Verplicht bij wijziging. Het nieuwe rekeningnummer wordt pas na {{ $coolingOffHours }} uur gebruikt voor uitbetalingen.
                                        @if($authViaCode ?? false)
                                            Je bent ingelogd met een e-mailcode — bevestig de wijziging met een nieuwe code.
                                        @else
                                            Geen wachtwoord (alleen e-mailcode gebruikt)? Stuur dan een bevestigingscode.
                                        @endif
                                    </p>
                                    <p id="bank-send-code-status" class="text-xs mt-1 mb-0 hidden" aria-live="polite"></p>
                                    <div class="field-feedback text-sm mt-1" data-field="confirmation_code" aria-live="polite"></div>
                                    @error('confirmation_code')
                                        <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error" data-laravel-field="confirmation_code" data-laravel-message="{{ $message }}">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            @if(!empty($payload['pending_destination_change']))
                <div class="kt-card w-full min-w-0">
                    <div class="kt-card-content p-5 text-sm text-amber-600 dark:text-amber-400">
                        Rekeningwijziging in afwachting
                        @if(!empty($identity->pending_masked_destination))
                            (nieuw: {{ $identity->pending_masked_destination }})
                        @endif
                        @if(!empty($payload['destination_change_eligible_at']))
                            — het nieuwe nummer wordt gebruikt vanaf {{ \Illuminate\Support\Carbon::parse($payload['destination_change_eligible_at'])->timezone(config('app.timezone'))->format('d-m-Y H:i') }}.
                        @endif
                    </div>
                </div>
            @endif

            <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-0 w-full min-w-0">
                <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script>
(function () {
    const btn = document.getElementById('bank-send-code-btn');
    const statusEl = document.getElementById('bank-send-code-status');
    if (!btn || !statusEl) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';

    btn.addEventListener('click', async function () {
        btn.disabled = true;
        statusEl.classList.remove('hidden', 'text-destructive');
        statusEl.classList.add('text-muted-foreground');
        statusEl.textContent = 'Code versturen…';

        try {
            const res = await fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({}),
            });
            const data = await res.json().catch(() => ({}));
            const msg = data.message || (res.ok ? 'Code verstuurd.' : 'Versturen mislukt.');
            statusEl.textContent = msg;
            if (!res.ok) {
                statusEl.classList.remove('text-muted-foreground');
                statusEl.classList.add('text-destructive');
            }
            if (res.ok) {
                document.getElementById('confirmation_code')?.focus();
            }
            if (data.retry_after) {
                setTimeout(() => { btn.disabled = false; }, Math.max(1000, Number(data.retry_after) * 1000));
            } else {
                btn.disabled = false;
            }
        } catch (e) {
            statusEl.textContent = 'Versturen mislukt. Probeer het opnieuw.';
            statusEl.classList.remove('text-muted-foreground');
            statusEl.classList.add('text-destructive');
            btn.disabled = false;
        }
    });
})();
</script>
@endpush
