@extends('frontend.layouts.website')

@section('title', 'Algemene voorwaarden - NEXA Suite')
@section('description', 'Algemene voorwaarden voor het gebruik van NEXA Suite, het SaaS-platform voor taxibedrijven.')

@section('content')
<article class="website-section-inner py-10 md:py-16">
    <div class="w-full max-w-3xl mx-auto">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Laatst bijgewerkt: 26 september 2026</p>
        <h1 class="text-3xl md:text-4xl font-semibold text-gray-900 dark:text-white mb-4" style="font-family: var(--theme-font-heading);">Algemene voorwaarden</h1>
        <p class="text-base text-gray-600 dark:text-gray-300 mb-10 leading-relaxed">
            Deze voorwaarden gelden voor het gebruik van NEXA Suite, het SaaS-platform van NEXA voor taxibedrijven (website, online boeking, chauffeur-app, contractvervoer en gerelateerde modules). Door een account aan te maken, een abonnement af te nemen of het platform te gebruiken, ga je hiermee akkoord.
        </p>

        <div class="space-y-8 text-[0.9375rem] leading-relaxed text-gray-700 dark:text-gray-300">
            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">1. Definities</h2>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>NEXA / wij:</strong> de aanbieder van NEXA Suite.</li>
                    <li><strong>Klant / jij:</strong> het bedrijf of de persoon die NEXA Suite afneemt of gebruikt.</li>
                    <li><strong>Platform:</strong> de software, websites, apps en API’s van NEXA Suite, inclusief white-label klantomgevingen.</li>
                    <li><strong>Gebruiker:</strong> iedereen die met jouw account inlogt (beheerders, chauffeurs, planners, contractpartijen).</li>
                    <li><strong>Abonnement:</strong> de overeengekomen module(s) en het pakket, inclusief eventuele proefperiode, voor het gebruik van het platform op jouw eigen website en in jouw omgeving.</li>
                    <li><strong>Eigen website:</strong> de white-label website van jouw taxibedrijf (jouw domein), niet de algemene marketingwebsite nexasuite.nl.</li>
                    <li><strong>NEXA Suite-rit:</strong> een rit die een reiziger boekt via de algemene website nexasuite.nl (onder meer /boek of /taxi) en die naar het dichtstbijzijnde aangesloten taxibedrijf gaat.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">2. Het platform</h2>
                <p>NEXA Suite is een modulair softwareplatform voor taxibedrijven. Afhankelijk van je pakket kan dit onder meer omvatten: een bedrijfswebsite, online ritboeking, dispatch, chauffeur-app, contractvervoer, facturatie en gerelateerde ondersteuning. Wij mogen het platform verbeteren, modules aanpassen of tijdelijk onderhoud uitvoeren. We streven naar een stabiele dienst, maar kunnen 100% ononderbroken beschikbaarheid niet garanderen.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">3. Account en toegang</h2>
                <p>Je bent verantwoordelijk voor de juistheid van bedrijfs- en accountgegevens en voor het geheim houden van inlogcodes en wachtwoorden. Handelingen van gebruikers binnen jouw omgeving zijn voor jouw rekening. Meld misbruik of een beveiligingsincident zo snel mogelijk via <a href="mailto:info@nexasuite.nl" class="text-blue-600 dark:text-blue-400 hover:underline">info@nexasuite.nl</a>. Wij mogen toegang blokkeren bij misbruik, wanbetaling of een redelijk vermoeden van een beveiligingsrisico.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">4. Abonnement, proefperiode en betaling</h2>
                <p>Prijzen, modules en looptijd volgen uit je bestelling, offerte of het actuele pakketoverzicht. Een proefperiode eindigt automatisch volgens de afgesproken datum, tenzij je opzegt of een betaald abonnement ingaat. Facturen zijn opeisbaar binnen de op de factuur vermelde termijn. Bij te late betaling mogen wij toegang tot (delen van) het platform opschorten na herinnering. Betaalde bedragen worden niet gerestitueerd, tenzij dwingend recht dat voorschrijft of wij dat schriftelijk anders overeenkomen.</p>
                <p class="mt-3">Het maandabonnement dekt het gebruik van NEXA Suite op jouw eigen website en in jouw omgeving. Over ritten die klanten daar boeken, betaal je geen provisie per rit.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">5. Ritten via nexasuite.nl (provisie)</h2>
                <p>Dit artikel geldt <strong>alleen</strong> voor NEXA Suite-ritten: boekingen via de algemene website nexasuite.nl die naar het dichtstbijzijnde aangesloten taxibedrijf gaan. Het geldt <strong>niet</strong> voor ritten via jouw eigen website. Die vallen onder het maandabonnement uit artikel 4.</p>
                <p class="mt-3">Over voltooide NEXA Suite-ritten betaal je een provisie van <strong>{{ \App\Support\NexaMarketplaceFeeCopy::percent() }}%</strong> over de ritomzet, exclusief btw. Dit percentage kan wijzigen; de actuele waarde staat op deze pagina en op de prijzenpagina. Wij factureren die provisie periodiek, los van het maandabonnement.</p>
                <p class="mt-3">NEXA is geen vervoerder op deze ritten. De vervoersovereenkomst is tussen de reiziger en het taxibedrijf dat de rit uitvoert.</p>
                <p class="mt-3">Ritprijzen en tariefindicaties in NEXA Suite zijn bepaald op basis van <a href="https://www.rijksoverheid.nl/vraag-en-antwoord/taxi/wat-zijn-de-kosten-voor-een-taxi" class="text-blue-600 dark:text-blue-400 hover:underline" target="_blank" rel="noopener noreferrer">de wettelijke maximum taxitarieven in Nederland</a>.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">6. Jouw verantwoordelijkheden</h2>
                <p>Je gebruikt het platform alleen voor rechtmatig taxivervoer en bedrijfsvoering, en houdt je aan wet- en regelgeving (onder meer taxivergunning, privacy, consumentenrecht en arbeidsrecht). Je zorgt dat ritten, tarieven, planning, klantcommunicatie en facturen kloppen. NEXA is geen vervoerder en geen partij in ritten tussen jou en jouw klanten of chauffeurs.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">7. Gegevens en privacy</h2>
                <p>Voor de gegevens van jouw klanten, chauffeurs en ritten ben jij verwerkingsverantwoordelijke; NEXA verwerkt die gegevens als verwerker om het platform te leveren. Onze eigen verwerking van gegevens (accounts, facturatie, support, de marketingwebsite) staat in het <a href="{{ route('privacy') }}" class="text-blue-600 dark:text-blue-400 hover:underline">privacybeleid</a>. Je mag geen inbreuk maken op rechten van derden en geen malware of onrechtmatige content plaatsen.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">8. Intellectuele eigendom</h2>
                <p>Het platform, de merken, designs, broncode en documentatie blijven eigendom van NEXA of haar licentiegevers. Je krijgt een niet-exclusief, niet-overdraagbaar gebruiksrecht voor de looptijd van het abonnement. Je eigen merkmateriaal, klantdata en content blijven van jou. Na einde van de overeenkomst kun je binnen een redelijke termijn export van je data vragen, voor zover technisch beschikbaar.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">9. Aansprakelijkheid</h2>
                <p>NEXA is niet aansprakelijk voor schade door onjuiste ritgegevens, planning, tarieven, GPS, netwerkstoringen, derden (betaalproviders, kaartdiensten, hosting) of keuzes van gebruikers. Onze totale aansprakelijkheid is beperkt tot het bedrag dat je in de twaalf maanden voor de schadeveroorzakende gebeurtenis aan ons hebt betaald voor het betreffende abonnement, en nooit tot gevolgschade, gederfde winst of omzetderving. Deze beperking geldt niet bij opzet of bewuste roekeloosheid van NEXA.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">10. Duur en opzegging</h2>
                <p>Het abonnement loopt voor de overeengekomen periode en wordt verlengd volgens de afspraken in je pakket of factuur, tenzij tijdig opgezegd. Opzeggen kan via de admin of schriftelijk via <a href="mailto:info@nexasuite.nl" class="text-blue-600 dark:text-blue-400 hover:underline">info@nexasuite.nl</a>. Wij mogen de overeenkomst beëindigen bij ernstige tekortkoming, faillissement of langdurige wanbetaling. Na beëindiging vervalt de toegang tot het platform.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">11. Wijzigingen</h2>
                <p>Wij mogen deze voorwaarden wijzigen. De actuele versie staat op deze pagina. Bij een wezenlijke wijziging informeren wij je redelijkerwijs (bijvoorbeeld per e-mail of in de admin). Als je het platform daarna blijft gebruiken, geldt de nieuwe versie.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">12. Toepasselijk recht</h2>
                <p>Op deze voorwaarden en het gebruik van NEXA Suite is Nederlands recht van toepassing. Geschillen worden voorgelegd aan de bevoegde rechter in Nederland, tenzij dwingend recht een andere bevoegdheid voorschrijft.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">13. Contact</h2>
                <p>Vragen over deze voorwaarden? Mail <a href="mailto:info@nexasuite.nl" class="text-blue-600 dark:text-blue-400 hover:underline">info@nexasuite.nl</a> of gebruik het <a href="{{ url('/contact') }}" class="text-blue-600 dark:text-blue-400 hover:underline">contactformulier</a>. Zie ook de <a href="{{ route('disclaimer') }}" class="text-blue-600 dark:text-blue-400 hover:underline">disclaimer</a>.</p>
            </section>
        </div>
    </div>
</article>
@endsection
