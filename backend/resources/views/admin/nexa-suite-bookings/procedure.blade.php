@extends('admin.layouts.app')

@section('title', __('Werkwijze marktplaats'))

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('Werkwijze marktplaats') }}</h1>
            <div class="text-sm text-secondary-foreground mt-2">{{ __('Ophaalcode (PIN), actieve rit en uitbetaling') }}</div>
        </div>
    </div>

    @include('admin.nexa-suite-bookings.partials.nav')

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">{{ __('Waar dit voor geldt') }}</h3>
            </div>
            <div class="kt-card-content p-5 text-sm text-muted-foreground space-y-3">
                <p class="mb-0">
                    {{ __('Dit is de werkwijze voor') }} <strong class="text-foreground">{{ __('marktplaatsritten') }}</strong> (NEXA Suite),
                    {{ __('vergelijkbaar met Uber: de klant heeft een ophaalcode, de chauffeur voert die in, pas daarna is de rit actief.') }}
                    {{ __('Contractvervoer valt hier niet onder.') }}
                </p>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">{{ __('Ophaalcode (PIN)') }}</h3>
            </div>
            <div class="kt-card-content p-5 text-sm text-muted-foreground space-y-3">
                <p class="mb-0">
                    {{ __('Zodra een chauffeur de rit accepteert, krijgt de klant een') }}
                    <strong class="text-foreground">{{ __('4-cijferige ophaalcode') }}</strong> {{ __('in de app.') }}
                </p>
                <p class="mb-0">
                    {{ __('Bij de klant: de chauffeur tikt') }} <strong class="text-foreground">{{ __('Klant opgehaald — voer code in') }}</strong>,
                    {{ __('vraagt de code, en vult die in. Alleen bij een juiste code start de rit (status actief).') }}
                </p>
                <p class="mb-0">
                    {{ __('Verkeerde code: foutmelding, opnieuw proberen. De rit blijft geaccepteerd, niet actief.') }}
                    {{ __('GPS blokkeert het starten nooit; een slechte locatie geeft hoogstens een waarschuwing.') }}
                </p>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">{{ __('Uitbetaling') }}</h3>
            </div>
            <div class="kt-card-content p-5 text-sm text-muted-foreground space-y-3">
                <p class="mb-0">
                    {{ __('Na een geslaagde PIN-start geldt de ophaalbevestiging als') }}
                    <strong class="text-foreground">{{ __('bevestigd') }}</strong>{{ __('. Bij afronden zonder andere risico’s') }}
                    {{ __('volgt snelle uitbetaling (geen lange wachttijd).') }}
                </p>
                <p class="mb-0">
                    {{ __('Bij risico (te snel afgerond, afstand klopt niet, afgerond ver van de bestemming, contant,') }}
                    {{ __('of betaling niet rond) gaat de rit') }}
                    <strong class="text-foreground">{{ __(':hours uur', ['hours' => $reviewHours]) }}</strong> {{ __('naar handmatige controle —') }}
                    {{ __('niet meer 72 uur.') }}
                </p>
                <p class="mb-0">
                    {{ __('Normale ritten zonder bevestigingssignaal (legacy) kunnen') }}
                    <strong class="text-foreground">{{ __(':hours uur', ['hours' => $holdHours]) }}</strong> {{ __('worden vastgehouden,') }}
                    {{ __('daarna automatisch vrijgegeven, tenzij de klant binnen :hours uur een probleem meldt.', ['hours' => $signalHours]) }}
                </p>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">{{ __('Afronden ver van de bestemming') }}</h3>
            </div>
            <div class="kt-card-content p-5 text-sm text-muted-foreground space-y-3">
                <p class="mb-0">
                    {{ __('De knop om af te ronden blijft werken. Staat de chauffeur meer dan') }}
                    <strong class="text-foreground">{{ __(':meters m', ['meters' => $dropoffRadiusMeters]) }}</strong> {{ __('van de bestemming') }}
                    {{ __('(locatie jonger dan :minutes minuten), dan waarschuwing + controle.', ['minutes' => $gpsMaxAgeMinutes]) }}
                    {{ __('De ophaalcode bevestigt alleen het ophalen, niet de hele rit tot bestemming.') }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
