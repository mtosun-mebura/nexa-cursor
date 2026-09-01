@extends('marketing.layout')

@section('content')
<div class="badge">B2B upsell</div>
<h1>Contractvervoer: school, zorg, zakelijk en privé zonder Excel</h1>
<p class="lead">Vaste ritten voor taxibedrijven: leerlingenvervoer, zorg- en ziekenhuisritten, zakelijke routes, privécontracten en shuttles. Planning, afmeldingen, status en facturatie in één systeem.</p>

<div class="screenshot-grid screenshot-grid--portal">
    <figure>
        <div class="feature-visual">
            <img src="{{ asset('assets/marketing/images/feature-contract-portal.png') }}" alt="Contractportaal: vandaag">
        </div>
        <figcaption>Vandaag</figcaption>
    </figure>
    <figure>
        <div class="feature-visual">
            <img src="{{ asset('assets/marketing/images/feature-contract-planning.png') }}" alt="Contractportaal: weekplanning">
        </div>
        <figcaption>Planning</figcaption>
    </figure>
</div>

<h2>Welke contracten je ermee rijdt</h2>
<div class="grid">
    <article class="card">
        <h3>Leerlingenvervoer</h3>
        <p>School- en dagopvangritten. Ouders of de school melden af; de chauffeur rijdt geen loze stop.</p>
    </article>
    <article class="card">
        <h3>Zorgcontracten</h3>
        <p>Zorginstellingen, dagbesteding en woonzorg. Vaste groepen, vaste tijden, één opdrachtgever.</p>
    </article>
    <article class="card">
        <h3>Ziekenhuisvervoer</h3>
        <p>Zittend ziekenvervoer, dialyse, polikliniek. Terugkerende ritten met status en uitzonderingen.</p>
    </article>
    <article class="card">
        <h3>Zakelijk vervoer</h3>
        <p>Woon-werk, shuttles tussen vestigingen, vaste relaties. Maandfactuur naar het bedrijf.</p>
    </article>
    <article class="card">
        <h3>Privévervoer</h3>
        <p>Vaste privéchauffeur of terugkerende privéritten. Abonnement of per rit, zonder telefoonchaos.</p>
    </article>
    <article class="card">
        <h3>Wmo, shuttles en overig</h3>
        <p>Doelgroepenvervoer, luchthaven- en hotelshuttles, evenementen. Elke vaste route past in hetzelfde contract.</p>
    </article>
</div>

<h2>Kerncapaciteiten</h2>
<ul>
    <li>Contractklanten, abonnementen (vast / per rit / hybride), SEPA-mandaat</li>
    <li>Passagiers, groepen, routeplanner, vaste chauffeur/voertuig</li>
    <li>Planning 14 dagen vooruit + uitzonderingen (vakantie, behandeling, thuiswerken)</li>
    <li><strong style="color:#fff">Contractportaal (PWA)</strong>: vandaag, weekplanning, heen/retour-status, afmelden van–tot, verstoringenbanners, voor school, zorgcoördinator of bedrijfscontact</li>
    <li>Chauffeur ziet afmeldingen; route wordt herberekend (skipped stops)</li>
    <li>Maandfacturatie PDF / e-mail / CSV</li>
</ul>

<h2>Saleshoek</h2>
<div class="card">
    <p style="color:#e2e8f0;margin:0;">
        “De school, de zorginstelling of het bedrijf meldt een week in één keer af.
        Contactpersonen zien of de rit is gereden. Jullie chauffeurs rijden geen loze kilometers.”
    </p>
</div>

<div class="cta-row">
    <a class="btn btn-primary" href="{{ route('marketing.show', 'taxi') }}">Basis: Nexa Taxi</a>
    <a class="btn btn-secondary" href="{{ route('marketing.index') }}">Overzicht</a>
</div>
@endsection
