@extends('marketing.layout')

@section('content')
<div class="badge">Go-to-market</div>
<h1>Verkoopstrategie: taxi-led, modulair uitbreiden</h1>
<p class="lead">Win eerst taxibedrijven met website + boeking + chauffeur-app. Upsell daarna contractvervoer.</p>

<h2>ICP (wie bel je eerst)</h2>
<div class="grid">
    <article class="card">
        <h3>Primair: Taxi</h3>
        <ul>
            <li>ZZP tot middelgroot taxibedrijf (1–40 chauffeurs)</li>
            <li>Wil online boekingen / minder telefoon</li>
            <li>Chauffeurs werken nu via WhatsApp/Excel</li>
            <li>Heeft of wil een website met merk</li>
        </ul>
    </article>
    <article class="card">
        <h3>Secundair: Contractvervoer</h3>
        <ul>
            <li>School, zorg, ziekenhuis, zakelijk of privé: vaste routes</li>
            <li>Behoefte aan planning, afmeldingen, facturatie</li>
            <li>Vaak upsell op bestaande Taxi-klant</li>
        </ul>
    </article>
</div>

<h2>Funnel</h2>
<ol style="color:var(--muted);padding-left:1.2rem;">
    <li><strong style="color:#fff">Attract</strong>: lokale SEO / LinkedIn / outbound naar taxibedrijven; landingscopy op nexasuite.nl</li>
    <li><strong style="color:#fff">Qualify</strong>: 15 min discovery: boekingen/dag, chauffeurs, website, contractritten?</li>
    <li><strong style="color:#fff">Demo</strong>: 30 min: website-boeking → dispatch → chauffeur-app (en optioneel contractportaal)</li>
    <li><strong style="color:#fff">Pilot</strong>: 2–4 weken live met 1 vestiging / kernchauffeurs</li>
    <li><strong style="color:#fff">Close</strong>: Pro-pakket (setup + maand) of Business bij multi-user/facturatie</li>
    <li><strong style="color:#fff">Expand</strong>: contractvervoer, AI-chat, extra tenants</li>
</ol>

<h2>Prijsrichting (Taxi)</h2>
<table>
    <thead><tr><th>Pakket</th><th>Maand</th><th>Website</th><th>Voor wie</th></tr></thead>
    <tbody>
        <tr><td>Start</td><td>€49</td><td>vanaf €750 eenmalig</td><td>ZZP / tot 3 chauffeurs</td></tr>
        <tr><td><strong>Pro (aanbevolen)</strong></td><td>€99</td><td>vanaf €750 eenmalig</td><td>Dagelijks ritten + chauffeur-app</td></tr>
        <tr><td>Business</td><td>€179</td><td>vanaf €750 eenmalig</td><td>Taxi + contractvervoer</td></tr>
        <tr><td>Vloot / maatwerk</td><td>vanaf €249</td><td>op maat</td><td>Multi-vestiging / SLA</td></tr>
    </tbody>
</table>
<p>Publieke pagina: <a href="{{ url('/prijzen') }}"><code>/prijzen</code></a> · interne one-pager: <a href="{{ route('marketing.show', 'prijzen') }}">Prijzen</a>. Extra: AI +€29/mnd, extra vestiging +€49/mnd. 12 maanden als standaard.</p>

<h2>Elevator (30 seconden)</h2>
<div class="card">
    <p style="color:#e2e8f0;margin:0;">
        “NEXA Suite geeft taxibedrijven een eigen website met online boeking, een chauffeur-app voor live ritten,
        en, als je vaste contracten rijdt, een portaal waarin school, zorg, bedrijf of privécontact afmeldt en status ziet.
        Alles white-label, multi-tenant, zonder vijf losse tools.”
    </p>
</div>

<h2>Discovery-vragen</h2>
<ul>
    <li>Hoe komen ritten binnen vandaag (telefoon / WhatsApp / site)?</li>
    <li>Hoeveel boekingen per dag en hoeveel chauffeurs online?</li>
    <li>Hebben jullie al een website? Wie beheert die?</li>
    <li>Doen jullie contractvervoer (school, zorg, zakelijk, privé)?</li>
    <li>Wat kost een gemiste boeking jullie per week?</li>
</ul>

<h2>Bezwaren &amp; antwoorden</h2>
<table>
    <thead><tr><th>Bezwaar</th><th>Antwoord</th></tr></thead>
    <tbody>
        <tr><td>“Te duur”</td><td>Reken terug: 2–3 extra ritten/week betalen de maandfee terug.</td></tr>
        <tr><td>“Chauffeurs willen geen app”</td><td>PWA, installeren als icoon, zelfde flow als WhatsApp-inbox.</td></tr>
        <tr><td>“Migratie”</td><td>Pilot met nieuwe boekingen; bestaande ritten blijven parallel.</td></tr>
        <tr><td>“We hebben al een site”</td><td>Boekingsmodule / embed of nieuwe white-label site in dagen.</td></tr>
    </tbody>
</table>

<h2>KPI’s (eerste 90 dagen sales)</h2>
<ul>
    <li>8–12 gekwalificeerde demo’s / maand</li>
    <li>Conversie demo → pilot ≥ 40%</li>
    <li>Conversie pilot → betaald ≥ 60%</li>
    <li>Upsell contractvervoer binnen 6 maanden ≥ 25% van Taxi-klanten</li>
</ul>

<div class="cta-row">
    <a class="btn btn-primary" href="{{ route('marketing.show', 'taxi') }}">Taxi one-pager</a>
    <a class="btn btn-secondary" href="{{ route('marketing.show', 'website-copy') }}">Website-copy</a>
</div>
@endsection
