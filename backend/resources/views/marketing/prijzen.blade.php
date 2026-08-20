@extends('marketing.layout')

@section('content')
<div class="badge">Verkoop</div>
<h1>Prijzen: instap laag, groeien met extra’s</h1>
<p class="lead">Publieke pagina: <a href="{{ url('/prijzen') }}"><code>/prijzen</code></a>. Maandabonnement vanaf €&nbsp;49. Website live zetten vanaf €&nbsp;750 eenmalig. Prijzen excl.&nbsp;btw, standaard 12 maanden.</p>

<h2>Maandpakketten</h2>
<div class="grid">
    <article class="card">
        <div class="badge">Instap</div>
        <h3>Start — € 49 / maand</h3>
        <p>ZZP en tot 3 chauffeurs. Website met boeking, drie chauffeur-accounts, merkkleuren, e-mailsupport.</p>
    </article>
    <article class="card">
        <div class="badge">Aanbevolen</div>
        <h3>Pro — € 99 / maand</h3>
        <p>Dagelijks ritten. Onbeperkt chauffeurs, dispatch, chauffeur-app, Mollie-betaling, factuur naar de klant.</p>
    </article>
    <article class="card">
        <div class="badge">Contract erbij</div>
        <h3>Business — € 179 / maand</h3>
        <p>Alles van Pro plus contractvervoer (school, zorg, zakelijk, privé), contract-app, maandfactuur en SEPA.</p>
    </article>
</div>

<h2>Website (eenmalig)</h2>
<div class="card">
    <p><strong style="color:#fff">Vanaf € 750</strong> — home, contact, boekingspagina, website builder met configureerbare componenten, SEO per pagina, Google Maps, logo en kleuren, live op jouw domein. Daarna alleen het maandabonnement.</p>
    <p>Uitgebreider (meer pagina’s of copy): vanaf € 1.250.</p>
</div>

<h2>Extra opties</h2>
<table>
    <thead><tr><th>Optie</th><th>Prijs</th><th>Wanneer</th></tr></thead>
    <tbody>
        <tr><td>Extra vestiging</td><td>+ € 49 / maand</td><td>Tweede merk of standplaats</td></tr>
        <tr><td>AI-assistent</td><td>+ € 29 / maand</td><td>Chat op de website (offerte, status, FAQ)</td></tr>
        <tr><td>Vloot / maatwerk</td><td>vanaf € 249 / maand</td><td>Meerdere vestigingen, SLA</td></tr>
    </tbody>
</table>

<h2>Waarom deze niveaus</h2>
<ul>
    <li><strong style="color:#fff">€ 49</strong> is laag genoeg voor ZZP: één extra rit per week dekt het.</li>
    <li><strong style="color:#fff">€ 99 Pro</strong> is het verkoopanker: dispatch + chauffeur-app, twee à drie extra ritten per week.</li>
    <li><strong style="color:#fff">€ 179 Business</strong> is de upsell zodra er school, zorg of zakelijk vast werk bij komt.</li>
    <li><strong style="color:#fff">€ 750 website</strong> ligt onder een bureau (€ 2.000–5.000) maar is geen “gratis site”.</li>
</ul>

<div class="note">Bron in code: <code>config/nexa_pricing.php</code>. Publiceren: Admin → Front-end → Pagina’s (Prijzen), of <code>php artisan nexa:sync-central-website --force</code> na review.</div>

<div class="cta-row">
    <a class="btn btn-primary" href="{{ url('/prijzen') }}">Open /prijzen</a>
    <a class="btn btn-secondary" href="{{ route('marketing.show', 'strategie') }}">Terug naar strategie</a>
</div>
@endsection
