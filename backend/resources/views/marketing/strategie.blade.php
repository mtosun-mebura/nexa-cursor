@extends('marketing.layout')

@section('content')
<div class="badge">Go-to-market</div>
<h1>Verkoopstrategie: taxi-led, modulair uitbreiden</h1>
<p class="lead">Win eerst taxibedrijven met website + boeking + chauffeur-app. Upsell daarna contractvervoer. Skillmatching als tweede vertical.</p>

<h2>ICP (wie bel je eerst)</h2>
<div class="grid">
    <article class="card">
        <h3>Primair — Taxi</h3>
        <ul>
            <li>ZZP tot middelgroot taxibedrijf (1–40 chauffeurs)</li>
            <li>Wil online boekingen / minder telefoon</li>
            <li>Chauffeurs werken nu via WhatsApp/Excel</li>
            <li>Heeft of wil een website met merk</li>
        </ul>
    </article>
    <article class="card">
        <h3>Secundair — Contractvervoer</h3>
        <ul>
            <li>School-/leerlingenvervoer of vaste B2B-routes</li>
            <li>Behoefte aan planning, afmeldingen, facturatie</li>
            <li>Vaak upsell op bestaande Taxi-klant</li>
        </ul>
    </article>
    <article class="card">
        <h3>Tertiair — Skillmatching</h3>
        <ul>
            <li>Uitzend / recruitment / HR met vacaturevolume</li>
            <li>Wil AI-matching i.p.v. handmatig filteren</li>
        </ul>
    </article>
</div>

<h2>Funnel</h2>
<ol style="color:var(--muted);padding-left:1.2rem;">
    <li><strong style="color:#fff">Attract</strong> — lokale SEO / LinkedIn / outbound naar taxibedrijven; landingscopy op nexasuite.nl</li>
    <li><strong style="color:#fff">Qualify</strong> — 15 min discovery: boekingen/dag, chauffeurs, website, contractritten?</li>
    <li><strong style="color:#fff">Demo</strong> — 30 min: website-boeking → dispatch → chauffeur-app (en optioneel contractportaal)</li>
    <li><strong style="color:#fff">Pilot</strong> — 2–4 weken live met 1 vestiging / kernchauffeurs</li>
    <li><strong style="color:#fff">Close</strong> — Pro-pakket (setup + maand) of Business bij multi-user/facturatie</li>
    <li><strong style="color:#fff">Expand</strong> — contractvervoer, AI-chat, extra tenants, Skillmatching</li>
</ol>

<h2>Prijsrichting (Taxi — intern)</h2>
<table>
    <thead><tr><th>Pakket</th><th>Setup</th><th>Maand</th><th>Voor wie</th></tr></thead>
    <tbody>
        <tr><td>Start</td><td>€995</td><td>€49</td><td>ZZP / starter</td></tr>
        <tr><td><strong>Pro (aanbevolen)</strong></td><td>€1.750</td><td>€99</td><td>Dagelijks ritten + app</td></tr>
        <tr><td>Business</td><td>€2.500</td><td>€179</td><td>Dispatch, multi-user, facturatie</td></tr>
        <tr><td>Fleet / maatwerk</td><td>vanaf €3.500</td><td>vanaf €249</td><td>Multi-vestiging / white-label</td></tr>
    </tbody>
</table>
<p>Website-eenmalig €750–2.500; 12 maanden contract als standaard.</p>

<h2>Elevator (30 seconden)</h2>
<div class="card">
    <p style="color:#e2e8f0;margin:0;">
        “NEXA Suite geeft taxibedrijven een eigen website met online boeking, een chauffeur-app voor live ritten,
        en — als je schoolvervoer doet — een portaal waarin ouders afmelden en status zien.
        Alles white-label, multi-tenant, zonder vijf losse tools.”
    </p>
</div>

<h2>Discovery-vragen</h2>
<ul>
    <li>Hoe komen ritten binnen vandaag (telefoon / WhatsApp / site)?</li>
    <li>Hoeveel boekingen per dag en hoeveel chauffeurs online?</li>
    <li>Hebben jullie al een website? Wie beheert die?</li>
    <li>Doen jullie contract-/schoolvervoer?</li>
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
