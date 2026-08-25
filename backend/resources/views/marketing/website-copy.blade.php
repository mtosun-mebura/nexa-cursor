@extends('marketing.layout')

@section('content')
<div class="badge">Publiceren</div>
<h1>Website-copy voor nexasuite.nl (centraal)</h1>
<p class="lead">Kant-en-klare teksten voor de centrale welkomstpagina. Na review: Admin → Welkom, of sync defaults in code.</p>

<h2>Hero</h2>
<div class="card">
    <p><strong style="color:#fff">Titel:</strong> Mis je ritten aan de telefoon? Laat klanten zelf boeken.</p>
    <p><strong style="color:#fff">Highlight:</strong> zelf boeken</p>
    <p><strong style="color:#fff">Subtitel:</strong> Online boeking, chauffeur-app en contractvervoer in één platform.</p>
    <p><strong style="color:#fff">CTA primair:</strong> Neem contact op → <code>/contact</code></p>
    <p><strong style="color:#fff">CTA secundair:</strong> Bekijk Nexa Taxi → <code>/taxi</code></p>
</div>

<h2>Prijzen (<code>/prijzen</code>)</h2>
<div class="card">
    <p><strong style="color:#fff">Hero:</strong> Duidelijke prijzen. Start bij € 49 per maand.</p>
    <p><strong style="color:#fff">Subtitel:</strong> Kies Start, Pro of Business. De details staan in de pakketten hieronder.</p>
    <p><strong style="color:#fff">Start:</strong> € 49 / maand: ZZP, tot 3 chauffeurs, boeking + merksite.</p>
    <p><strong style="color:#fff">Pro (aanbevolen):</strong> € 99 / maand: dispatch, chauffeur-app, Mollie.</p>
    <p><strong style="color:#fff">Business:</strong> € 179 / maand: plus contractvervoer en contract-app.</p>
    <p><strong style="color:#fff">Website:</strong> vanaf € 750 eenmalig. Uitgebreid vanaf € 1.250.</p>
    <p><strong style="color:#fff">Extra:</strong> AI + € 29 / maand, extra vestiging + € 49 / maand, vloot vanaf € 249 / maand.</p>
</div>

<h2>Waarom NEXA</h2>
<div class="card">
    <p><strong style="color:#fff">Titel:</strong> Waarom ondernemers voor NEXA kiezen</p>
    <p><strong style="color:#fff">Subtitel:</strong> Minder telefoonchaos, meer boekingen. White-label en klaar om te groeien.</p>
</div>

<h2>Modules (volgorde: Taxi eerst)</h2>
<ol style="color:var(--muted);padding-left:1.2rem;">
    <li><strong style="color:#fff">NEXA Taxi</strong>: Online boeking, ritten, chauffeur-app, tarieven en facturatie. Van aanvraag tot rit afgerond.</li>
    <li><strong style="color:#fff">Contractvervoer</strong>: School, zorg, ziekenhuis, zakelijk en privé: planning, portaal en afmeldingen (upsell binnen Taxi).</li>
    <li><strong style="color:#fff">NEXA Garage</strong>: Binnenkort: werkplaats &amp; werkorders.</li>
</ol>

<h2>CTA-blok</h2>
<div class="card">
    <p><strong style="color:#fff">Titel:</strong> Klaar om te starten?</p>
    <p><strong style="color:#fff">Subtitel:</strong> Vertel wat je taxibedrijf nodig heeft. We nemen contact op over onboarding of een voorstel.</p>
    <p><strong style="color:#fff">CTA:</strong> Neem contact op → <code>/contact</code> · secundair Bekijk prijzen → <code>/prijzen</code></p>
</div>

<h2>Hoe publiceren</h2>
<ol style="color:var(--muted);padding-left:1.2rem;">
    <li>Review deze hub op <code>/marketing</code>.</li>
    <li>Code-defaults staan in <code>CentralWelcomePageService</code> (home, taxi, contractvervoer, website, prijzen, contact).</li>
    <li>Admin → <strong>Front-end → Pagina's</strong> (geen tenant) om teksten en plaatjes te beheren.</li>
    <li>Productie-DB: éénmalig <code>php artisan nexa:sync-central-website --force</code> na goedkeuring (overschrijft bestaande centrale pagina's).</li>
</ol>

<div class="note">Let op: <code>--force</code> overschrijft custom content op de centrale website. Alleen doen na expliciete review.</div>
@endsection
