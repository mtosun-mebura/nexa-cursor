@extends('frontend.layouts.website')

@section('title', 'Disclaimer - NEXA Suite')
@section('description', 'Disclaimer van de NEXA Suite website en software. Informatie, aansprakelijkheid en gebruik van het platform.')

@section('content')
<article class="website-section-inner py-10 md:py-16">
    <div class="w-full max-w-3xl mx-auto">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Laatst bijgewerkt: 16 september 2026</p>
        <h1 class="text-3xl md:text-4xl font-semibold text-gray-900 dark:text-white mb-4" style="font-family: var(--theme-font-heading);">Disclaimer</h1>
        <p class="text-base text-gray-600 dark:text-gray-300 mb-10 leading-relaxed">
            Deze disclaimer geldt voor de publieke website van NEXA Suite (nexasuite.nl) en voor informatie die wij via het platform, e-mail of demo’s delen. Voor het gebruik van de software gelden daarnaast de <a href="{{ route('terms') }}" class="text-blue-600 dark:text-blue-400 hover:underline">algemene voorwaarden</a>.
        </p>

        <div class="space-y-8 text-[0.9375rem] leading-relaxed text-gray-700 dark:text-gray-300">
            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Informatie op de website</h2>
                <p>De inhoud op deze website is met zorg samengesteld, maar bedoeld als algemene informatie over NEXA Suite. Prijzen, modules, schermafbeeldingen en voorbeelden kunnen wijzigen en vormen geen aanbod, tenzij dat uitdrukkelijk schriftelijk is bevestigd. Aan de website kunnen geen rechten worden ontleend.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Geen advies</h2>
                <p>Niets op deze website of in het platform is juridisch, fiscaal, boekhoudkundig of vervoersrechtelijk advies. Controleer zelf of NEXA Suite past bij jouw vergunningen, administratie en werkwijze. Twijfel je, win dan advies in bij een deskundige.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Software en ritten</h2>
                <p>NEXA Suite ondersteunt taxibedrijven bij website, boeking, planning en contractvervoer. Ritprijzen, ETA’s, beschikbaarheid van voertuigen en kaartgegevens zijn indicatief en afhankelijk van invoer, verkeer, GPS en derden. NEXA is geen taxionderneming en sluit geen vervoersovereenkomst met eindklanten van het taxibedrijf.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Boekingen via nexasuite.nl</h2>
                <p>Reizigers kunnen op de algemene website nexasuite.nl een rit aanvragen. Die aanvraag gaat naar het dichtstbijzijnde aangesloten taxibedrijf. NEXA is geen vervoerder; de rit is tussen reiziger en taxibedrijf. Over die ritten betaalt het taxibedrijf een provisie (zie de <a href="{{ route('terms') }}" class="text-blue-600 dark:text-blue-400 hover:underline">algemene voorwaarden</a>). Ritten via de eigen website van het taxibedrijf vallen onder het maandabonnement, zonder provisie per rit.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Derden</h2>
                <p>De website en het platform kunnen verwijzen naar of gebruikmaken van diensten van derden (onder meer kaarten, betalingen, hosting, berichten). Voor die diensten gelden de voorwaarden van die partijen. NEXA is niet verantwoordelijk voor de inhoud of beschikbaarheid daarvan.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Aansprakelijkheid</h2>
                <p>Wij zijn niet aansprakelijk voor schade door het gebruik van of het vertrouwen op informatie op deze website, of door storingen, onvolledige gegevens of typefouten, voor zover de wet dat toelaat. De aansprakelijkheidsregeling in de algemene voorwaarden is van toepassing op het gebruik van het platform.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Intellectuele eigendom</h2>
                <p>Teksten, beelden, logo’s en merken op deze website zijn van NEXA of van rechthebbenden. Overname is niet toegestaan zonder voorafgaande toestemming, behalve waar de wet dat toestaat (bijvoorbeeld citaatrecht).</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Contact</h2>
                <p>Vragen over deze disclaimer? Mail <a href="mailto:info@nexasuite.nl" class="text-blue-600 dark:text-blue-400 hover:underline">info@nexasuite.nl</a> of ga naar <a href="{{ url('/contact') }}" class="text-blue-600 dark:text-blue-400 hover:underline">contact</a>.</p>
            </section>
        </div>
    </div>
</article>
@endsection
