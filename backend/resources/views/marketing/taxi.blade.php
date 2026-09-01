@extends('marketing.layout')

@section('content')
<div class="badge">Hoogste saleskans</div>
<h1>Nexa Taxi: van telefoon naar online boeking &amp; chauffeur-app</h1>
<p class="lead">Het product waarmee je de meeste taxibedrijven binnenhaalt: website, boeking, dispatch, betaling en chauffeur-PWA in één stack.</p>

<div class="feature-visual" style="margin-bottom:1.25rem;">
    <img src="{{ asset('assets/marketing/images/feature-taxi-booking.png') }}" alt="Online boekingsmodule">
</div>

<h2>Wat je verkoopt</h2>
<div class="grid">
    <article class="card">
        <h3>Online boeking</h3>
        <p>Meerstapsflow op de klantwebsite: route, voertuig, offerte, gegevens. Minder gemiste calls.</p>
    </article>
    <article class="card">
        <h3>Dispatch</h3>
        <p>Ritten toewijzen, waves, accept/decline, redispatch: overzicht voor de centrale.</p>
    </article>
    <article class="card">
        <h3>Chauffeur-PWA</h3>
        <p>Online/offline, inbox, rit starten/afronden, stops, betaling: <code>/taxi/chauffeur</code>.</p>
    </article>
    <article class="card">
        <h3>Jouw merk, jouw tarieven</h3>
        <p>Logo, kleuren en tarieven per voertuig. De klant boekt bij jou, niet bij een marktplaats.</p>
    </article>
</div>

<div class="screenshot-grid">
    <figure>
        <div class="feature-visual">
            <img src="{{ asset('assets/marketing/images/feature-chauffeur-inbox.png') }}" alt="Chauffeur-app: nieuwe ritaanvraag">
        </div>
        <figcaption>Nieuwe ritaanvraag</figcaption>
    </figure>
    <figure>
        <div class="feature-visual">
            <img src="{{ asset('assets/marketing/images/feature-chauffeur-active.png') }}" alt="Chauffeur-app: actieve rit">
        </div>
        <figcaption>Actieve rit</figcaption>
    </figure>
    <figure>
        <div class="feature-visual">
            <img src="{{ asset('assets/marketing/images/feature-chauffeur-app.png') }}" alt="Chauffeur-app: geplande ritten in de auto">
        </div>
        <figcaption>Geplande ritten</figcaption>
    </figure>
</div>

<h2>Waarom dit scoort in sales</h2>
<ul>
    <li>Directe ROI: zichtbare online boekingen</li>
    <li>Chauffeurs zien iets concreets (app), niet alleen “admin”</li>
    <li>Upsellpad naar contractvervoer en AI</li>
    <li>White-label past bij merk van de taxicentrale</li>
</ul>

<h2>Demo-script (15 min)</h2>
<ol style="color:var(--muted);padding-left:1.2rem;">
    <li>Homepage tenant → boekingsmodule → offerte</li>
    <li>Admin: rit binnen → dispatch naar chauffeur</li>
    <li>Chauffeur-app: accepteren, start, complete</li>
    <li>Optioneel: contractportaal afmelden tonen</li>
</ol>

<div class="cta-row">
    <a class="btn btn-primary" href="{{ route('marketing.show', 'contractvervoer') }}">Upsell: contractvervoer</a>
    <a class="btn btn-secondary" href="{{ route('marketing.show', 'strategie') }}">Terug naar strategie</a>
</div>
@endsection
