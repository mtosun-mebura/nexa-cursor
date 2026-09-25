@props([
    'step' => 'login',
])
@php
    $title = match ($step) {
        'request' => 'Eerste keer inloggen',
        'verify' => 'Eerste keer inloggen',
        default => 'Inloggen',
    };
@endphp
<div class="flex items-center justify-center rounded-lg bg-muted/20 p-5 sm:p-8">
    <div class="kt-card w-full mx-auto pointer-events-none select-none" style="max-width: 370px" aria-hidden="true">
        <div class="kt-card-content flex flex-col gap-5 p-8 sm:p-10">
            <div class="text-center mb-2.5">
                <div class="mb-4">
                    @include('partials.nexa-brand-logo', ['class' => 'h-10 w-auto mx-auto object-contain'])
                    <div class="mt-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        Administratie paneel
                    </div>
                </div>
                <h3 class="text-lg font-medium text-mono leading-none mb-0">{{ $title }}</h3>
            </div>

            @if($step === 'request')
                <p class="text-sm text-muted-foreground mb-0">
                    Vraag hieronder een eenmalige code aan, die via de e-mail wordt verstuurd. Met deze code kun je zelf een wachtwoord aanmaken.
                </p>
                <div class="flex flex-col gap-2.5">
                    <span class="kt-form-label font-normal text-mono">E-mail</span>
                    <div class="kt-input min-h-10 flex items-center text-muted-foreground">email@email.com</div>
                </div>
                <div class="kt-btn kt-btn-primary flex justify-center grow">Inlogcode aanvragen</div>
                <div class="text-sm link text-primary w-full text-center">Terug naar inloggen</div>
            @elseif($step === 'verify')
                <p class="text-sm text-muted-foreground mb-0">
                    We hebben een eenmalige code naar je e-mail gestuurd. Vul die hieronder in en kies een wachtwoord.
                </p>
                <div class="flex flex-col gap-2.5">
                    <span class="kt-form-label font-normal text-mono">Code uit e-mail</span>
                    <div class="kt-input min-h-10 flex items-center justify-center tracking-widest text-muted-foreground">000000</div>
                </div>
                <div class="flex flex-col gap-2.5">
                    <span class="kt-form-label font-normal text-mono">Nieuw wachtwoord</span>
                    <div class="kt-input min-h-10 flex items-center text-muted-foreground">Min. 8 tekens</div>
                    <p class="text-xs text-muted-foreground mt-0 mb-0">Minimaal 8 tekens, met een hoofdletter, een kleine letter en een cijfer.</p>
                </div>
                <div class="flex flex-col gap-2.5">
                    <span class="kt-form-label font-normal text-mono">Bevestig wachtwoord</span>
                    <div class="kt-input min-h-10 flex items-center text-muted-foreground">Herhaal wachtwoord</div>
                </div>
                <div class="kt-btn kt-btn-primary flex justify-center grow">Wachtwoord instellen en inloggen</div>
                <div class="text-sm link text-primary w-full text-center">Nieuwe code aanvragen</div>
                <div class="text-sm link text-muted-foreground w-full text-center">Terug naar inloggen</div>
            @else
                <div class="flex flex-col gap-2.5">
                    <span class="kt-form-label font-normal text-mono">E-mail</span>
                    <div class="kt-input min-h-10 flex items-center text-muted-foreground">email@email.com</div>
                </div>
                <div class="flex flex-col gap-2.5">
                    <span class="kt-form-label font-normal text-mono">Wachtwoord</span>
                    <div class="kt-input min-h-10 flex items-center justify-between gap-2 text-muted-foreground">
                        <span>Voer wachtwoord in</span>
                        <i class="ki-filled ki-eye text-muted-foreground"></i>
                    </div>
                    <span class="text-sm link text-primary mt-1">Wachtwoord vergeten?</span>
                </div>
                <label class="kt-label">
                    <input class="kt-checkbox kt-checkbox-sm" type="checkbox" tabindex="-1" disabled>
                    <span class="kt-checkbox-label">Onthoud mij</span>
                </label>
                <div class="kt-btn kt-btn-primary flex justify-center grow">Inloggen</div>
                <div class="text-sm link text-primary w-full text-center">Eerste keer inloggen?</div>
            @endif
        </div>
    </div>
</div>
