@extends('admin.layouts.app')

@section('title', 'Werkwijze marktplaats')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Werkwijze marktplaats</h1>
            <div class="text-sm text-secondary-foreground mt-2">Ophaalcode (PIN), actieve rit en uitbetaling</div>
        </div>
    </div>

    @include('admin.nexa-suite-bookings.partials.nav')

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Waar dit voor geldt</h3>
            </div>
            <div class="kt-card-content p-5 text-sm text-muted-foreground space-y-3">
                <p class="mb-0">
                    Dit is de werkwijze voor <strong class="text-foreground">marktplaatsritten</strong> (NEXA Suite),
                    vergelijkbaar met Uber: de klant heeft een ophaalcode, de chauffeur voert die in, pas daarna is de rit actief.
                    Contractvervoer valt hier niet onder.
                </p>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Ophaalcode (PIN)</h3>
            </div>
            <div class="kt-card-content p-5 text-sm text-muted-foreground space-y-3">
                <p class="mb-0">
                    Zodra een chauffeur de rit accepteert, krijgt de klant een
                    <strong class="text-foreground">4-cijferige ophaalcode</strong> in de app.
                </p>
                <p class="mb-0">
                    Bij de klant: de chauffeur tikt <strong class="text-foreground">Klant opgehaald — voer code in</strong>,
                    vraagt de code, en vult die in. Alleen bij een juiste code start de rit (status actief).
                </p>
                <p class="mb-0">
                    Verkeerde code: foutmelding, opnieuw proberen. De rit blijft geaccepteerd, niet actief.
                    GPS blokkeert het starten nooit; een slechte locatie geeft hoogstens een waarschuwing.
                </p>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Uitbetaling</h3>
            </div>
            <div class="kt-card-content p-5 text-sm text-muted-foreground space-y-3">
                <p class="mb-0">
                    Na een geslaagde PIN-start geldt de ophaalbevestiging als
                    <strong class="text-foreground">bevestigd</strong>. Bij afronden zonder andere risico’s
                    volgt snelle uitbetaling (geen lange wachttijd).
                </p>
                <p class="mb-0">
                    Bij risico (te snel afgerond, afstand klopt niet, afgerond ver van de bestemming, contant,
                    of betaling niet rond) gaat de rit
                    <strong class="text-foreground">{{ $reviewHours }} uur</strong> naar handmatige controle —
                    niet meer 72 uur.
                </p>
                <p class="mb-0">
                    Normale ritten zonder bevestigingssignaal (legacy) kunnen
                    <strong class="text-foreground">{{ $holdHours }} uur</strong> worden vastgehouden,
                    daarna automatisch vrijgegeven, tenzij de klant binnen {{ $signalHours }} uur een probleem meldt.
                </p>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Afronden ver van de bestemming</h3>
            </div>
            <div class="kt-card-content p-5 text-sm text-muted-foreground space-y-3">
                <p class="mb-0">
                    De knop om af te ronden blijft werken. Staat de chauffeur meer dan
                    <strong class="text-foreground">{{ $dropoffRadiusMeters }} m</strong> van de bestemming
                    (locatie jonger dan {{ $gpsMaxAgeMinutes }} minuten), dan waarschuwing + controle.
                    De ophaalcode bevestigt alleen het ophalen, niet de hele rit tot bestemming.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
