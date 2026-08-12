@extends('marketing.layout')

@section('content')
<section class="hero">
    <div>
        <div class="badge">Product + verkoop</div>
        <h1>NEXA Suite: modulair SaaS dat taxibedrijven en recruitmentteams online laat groeien</h1>
        <p class="lead">
            Eén multi-tenant platform met website builder, rollen &amp; rechten, SaaS-facturatie en AI-chat.
            Activeer alleen de modules die je nodig hebt — start met <strong style="color:#fff">Nexa Taxi</strong> (hoogste saleskans).
        </p>
        <div class="cta-row">
            <a class="btn btn-primary" href="{{ route('marketing.show', 'strategie') }}">Bekijk verkoopstrategie</a>
            <a class="btn btn-secondary" href="{{ route('marketing.show', 'taxi') }}">Taxi one-pager</a>
        </div>
    </div>
    <div class="hero-visual">
        <img src="{{ asset('assets/marketing/images/hero-nexa-suite.png') }}" alt="NEXA Suite platform overzicht" width="640" height="400">
    </div>
</section>

<div class="note">
    Preview-URL: <code>{{ url('/marketing') }}</code> · 404-preview: <a href="{{ url('/test-404') }}">/test-404</a>.
    Deze hub is bedoeld om salescontent te reviewen vóór publicatie op nexasuite.nl.
</div>

<h2>Wat de SaaS vandaag kan</h2>
<div class="grid">
    <article class="card">
        <div class="badge">Platform</div>
        <h3>Multi-tenant basis</h3>
        <p>Bedrijven, modules, gebruikers, Spatie-rollen, website builder, e-mailtemplates, agenda, SaaS-facturatie (Mollie) en AI-chatlaag.</p>
    </article>
    <article class="card">
        <div class="badge">Hoogste saleskans</div>
        <h3>Nexa Taxi</h3>
        <p>Website-boeking, tarieven, ritten, chauffeur-dispatch + PWA, klantportaal, contractvervoer met school/ouder-app, AI-assistent.</p>
        <p><a href="{{ route('marketing.show', 'taxi') }}">Lees meer →</a></p>
    </article>
    <article class="card">
        <div class="badge">B2B upsell</div>
        <h3>Contractvervoer</h3>
        <p>Vaste routes, groepen, planning, uitzonderingen, afmeldingen (van–tot), verstoringenbanners, maandfacturatie.</p>
        <p><a href="{{ route('marketing.show', 'contractvervoer') }}">Lees meer →</a></p>
    </article>
    <article class="card">
        <div class="badge">Tweede vertical</div>
        <h3>Skillmatching</h3>
        <p>Vacatures, AI-matches, interviews, kandidatenportaal — recruitment zonder losse tools.</p>
        <p><a href="{{ route('marketing.show', 'skillmatching') }}">Lees meer →</a></p>
    </article>
    <article class="card">
        <div class="badge">Conversie</div>
        <h3>Website builder</h3>
        <p>Merkbare tenant-sites met boekingsmodule, reviews, SEO — zonder apart CMS.</p>
        <p><a href="{{ route('marketing.show', 'website') }}">Lees meer →</a></p>
    </article>
    <article class="card">
        <div class="badge warn">Roadmap</div>
        <h3>NEXA Garage</h3>
        <p>Werkplaats / werkorders — positioneer als “binnenkort”, niet als live product.</p>
    </article>
</div>

<h2>Waarom klanten kopen</h2>
<table>
    <thead>
        <tr><th>Pijnpunt</th><th>NEXA-antwoord</th></tr>
    </thead>
    <tbody>
        <tr><td>Geen online boekingen / verloren calls</td><td>Boekingsmodule op eigen website + tarieven</td></tr>
        <tr><td>Chauffeurs via WhatsApp/Excel</td><td>Dispatch + chauffeur-PWA met inbox</td></tr>
        <tr><td>Schoolvervoer handmatig afmelden</td><td>Contractportaal met status &amp; afmeldingen</td></tr>
        <tr><td>Losse website + losse app</td><td>Alles in één SaaS, white-label per tenant</td></tr>
    </tbody>
</table>

<h2>Feature-visuals (sales)</h2>
<div class="grid">
    <div class="feature-visual"><img src="{{ asset('assets/marketing/images/feature-taxi-booking.png') }}" alt="Online taxi boeking"></div>
    <div class="feature-visual"><img src="{{ asset('assets/marketing/images/feature-chauffeur-app.png') }}" alt="Chauffeur app"></div>
    <div class="feature-visual"><img src="{{ asset('assets/marketing/images/feature-contract-portal.png') }}" alt="Contractportaal"></div>
</div>
@endsection
