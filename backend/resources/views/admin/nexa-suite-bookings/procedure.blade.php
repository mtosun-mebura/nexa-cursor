@extends('admin.layouts.app')

@section('title', 'Werkwijze marktplaats')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Werkwijze marktplaats</h1>
            <div class="text-sm text-secondary-foreground mt-2">Ophalen, klantbevestiging en uitbetaling aan de chauffeur</div>
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
                    Dit is de werkwijze voor <strong class="text-foreground">marktplaatsritten</strong> (NEXA Suite):
                    de klant betaalt NEXA, de chauffeur krijgt zijn deel pas nadat de rit is gecontroleerd.
                    Contractvervoer valt hier niet onder.
                </p>
                <p class="mb-0">
                    De knop <strong class="text-foreground">Klant opgehaald, rit starten</strong> blijft altijd te gebruiken.
                    GPS blokkeert die knop nooit. Een slechte locatie mag de klant niet laten staan.
                </p>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Bij het starten</h3>
            </div>
            <div class="kt-card-content p-5 text-sm text-muted-foreground">
                <p class="mb-3">
                    We kijken naar de laatste chauffeur-locatie. Die telt alleen mee als die jonger is dan
                    <strong class="text-foreground">{{ $gpsMaxAgeMinutes }} minuten</strong>.
                </p>
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                    <thead>
                        <tr>
                            <th class="text-secondary-foreground font-normal">Situatie</th>
                            <th class="text-secondary-foreground font-normal">Wat er gebeurt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground align-top">Binnen {{ $pickupRadiusMeters }} m van het ophaalpunt</td>
                            <td>Geen waarschuwing. De rit start. De klant krijgt wel de vraag hieronder.</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground align-top">Verder dan {{ $pickupRadiusMeters }} m</td>
                            <td>Waarschuwing aan de chauffeur. De rit start wel. Zonder Ja van de klant gaat de uitbetaling {{ $reviewHours }} uur naar controle.</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground align-top">Geen bruikbare locatie</td>
                            <td>De rit start wel en krijgt de vlag <strong class="text-foreground">locatie onbekend</strong>. Zonder Ja van de klant: {{ $reviewHours }} uur controle.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Vraag aan de klant</h3>
            </div>
            <div class="kt-card-content p-5 text-sm text-muted-foreground space-y-3">
                <p class="mb-0">
                    Direct nadat de chauffeur start, ziet de klant in de app en op de ritpagina:
                </p>
                <p class="mb-0 text-foreground font-medium">De chauffeur zegt dat je bent opgehaald. Klopt dat?</p>
                <p class="mb-0">Ziet de klant de vraag niet, dan loopt de rit gewoon door. De chauffeur kan ophalen en afronden.</p>
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                    <thead>
                        <tr>
                            <th class="text-secondary-foreground font-normal">Antwoord</th>
                            <th class="text-secondary-foreground font-normal">Uitbetaling</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground align-top">Ja</td>
                            <td>
                                Bij afronden wordt meteen uitbetaald, tenzij er een ander risico is:
                                te snel afgerond, gereden afstand klopt niet bij de offerte, afgerond ver van de bestemming,
                                contant, of de betaling is niet rond.
                                Een Ja heft de GPS-waarschuwing en “locatie onbekend” op.
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground align-top">Nee</td>
                            <td>
                                Geen automatische uitbetaling. De rit gaat {{ $reviewHours }} uur in handmatige controle.
                                De rit zelf wordt niet stopgezet.
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground align-top">Geen reactie</td>
                            <td>
                                Normale rit (gestart bij het ophaalpunt, niet te snel, afstand past bij de offerte):
                                {{ $holdHours }} uur vasthouden, daarna automatisch vrijgeven,
                                tenzij de klant binnen {{ $signalHours }} uur een probleem meldt.
                                Verdacht (te snel, afstand klopt niet, gestart ver van het ophaalpunt, locatie onbekend,
                                of afgerond ver van de bestemming): {{ $reviewHours }} uur handmatige controle, geen automatische vrijgave.
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="mb-0">
                    Ontbreekt alleen de GPS-track van de gereden route, zonder die andere signalen,
                    dan blijft de bestaande regel: {{ $holdHours }} uur vasthouden, daarna vrijgeven.
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
                    <strong class="text-foreground">{{ $dropoffRadiusMeters }} m</strong> van de bestemming,
                    met een locatie jonger dan {{ $gpsMaxAgeMinutes }} minuten, dan is er een waarschuwing
                    en gaat de uitbetaling naar controle. Ook als de klant Ja heeft gezegd:
                    Ja bevestigt alleen het ophalen, niet dat de rit tot de bestemming is gereden.
                </p>
                <p class="mb-0">
                    Is de locatie op dat moment niet bruikbaar, dan blokkeren we het afronden niet
                    en zetten we alleen om die reden geen extra vlag.
                </p>
            </div>
        </div>

        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Al vrijgegeven</h3>
            </div>
            <div class="kt-card-content p-5 text-sm text-muted-foreground">
                <p class="mb-0">
                    Nee of een probleem ná een al vrijgegeven uitbetaling zet de rit op controle.
                    Een bedrag dat al is uitgekeerd wordt niet automatisch teruggedraaid; dat doet een beheerder.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
