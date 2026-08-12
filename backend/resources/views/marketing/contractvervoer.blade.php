@extends('marketing.layout')

@section('content')
<div class="badge">B2B upsell</div>
<h1>Contractvervoer — school &amp; vaste routes zonder Excel</h1>
<p class="lead">Voor taxibedrijven met leerlingenvervoer of vaste B2B-ritten: planning, afmeldingen, status en facturatie.</p>

<div class="feature-visual" style="margin-bottom:1.25rem;">
    <img src="{{ asset('assets/marketing/images/feature-contract-portal.png') }}" alt="Contractportaal ouders">
</div>

<h2>Kerncapaciteiten</h2>
<ul>
    <li>Contractklanten, abonnementen (vast / per rit / hybride), SEPA-mandaat</li>
    <li>Passagiers, groepen, routeplanner, vaste chauffeur/voertuig</li>
    <li>Planning 14 dagen vooruit + uitzonderingen (vakantie)</li>
    <li><strong style="color:#fff">Contractportaal (PWA)</strong>: vandaag, weekplanning, heen/retour-status, afmelden van–tot, verstoringenbanners</li>
    <li>Chauffeur ziet afmeldingen; route wordt herberekend (skipped stops)</li>
    <li>Maandfacturatie PDF / e-mail / CSV</li>
</ul>

<h2>Saleshoek</h2>
<div class="card">
    <p style="color:#e2e8f0;margin:0;">
        “Ouders weten of hun kind is opgehaald. De school meldt af voor een week in één keer.
        Jullie chauffeurs rijden geen loze kilometers.”
    </p>
</div>

<div class="cta-row">
    <a class="btn btn-primary" href="{{ route('marketing.show', 'taxi') }}">Basis: Nexa Taxi</a>
    <a class="btn btn-secondary" href="{{ route('marketing.index') }}">Overzicht</a>
</div>
@endsection
