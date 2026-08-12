@extends('marketing.layout')

@section('content')
<div class="badge">Publiceren</div>
<h1>Website-copy voor nexasuite.nl (centraal)</h1>
<p class="lead">Kant-en-klare teksten voor de centrale welkomstpagina. Na review: Admin → Welkom, of sync defaults in code.</p>

<h2>Hero</h2>
<div class="card">
    <p><strong style="color:#fff">Titel:</strong> Bouw je vervoersbedrijf online — met NEXA Suite</p>
    <p><strong style="color:#fff">Highlight:</strong> NEXA</p>
    <p><strong style="color:#fff">Subtitel:</strong> Modulair SaaS voor taxibedrijven: eigen website met online boeking, chauffeur-app en optioneel contractvervoer. Installeer alleen wat je nodig hebt.</p>
    <p><strong style="color:#fff">CTA primair:</strong> Bekijk modules → <code>#modules-overview</code></p>
    <p><strong style="color:#fff">CTA secundair:</strong> Plan een demo → <code>/marketing/strategie</code> of contact/admin</p>
</div>

<h2>Waarom NEXA</h2>
<div class="card">
    <p><strong style="color:#fff">Titel:</strong> Waarom ondernemers voor NEXA kiezen</p>
    <p><strong style="color:#fff">Subtitel:</strong> Minder telefoonchaos, meer boekingen. White-label, multi-tenant en klaar voor groei — van ZZP tot centrale.</p>
</div>

<h2>Modules (volgorde: Taxi eerst)</h2>
<ol style="color:var(--muted);padding-left:1.2rem;">
    <li><strong style="color:#fff">NEXA Taxi</strong> — Online boeking, ritten, chauffeur-app, tarieven en facturatie. Van aanvraag tot rit afgerond.</li>
    <li><strong style="color:#fff">Contractvervoer</strong> — Schoolroutes, planning, ouderportaal en afmeldingen (upsell binnen Taxi).</li>
    <li><strong style="color:#fff">NEXA Skillmatching</strong> — AI-vacaturematching, pipeline en kandidatenportaal.</li>
    <li><strong style="color:#fff">NEXA Garage</strong> — Binnenkort: werkplaats &amp; werkorders.</li>
</ol>

<h2>CTA-blok</h2>
<div class="card">
    <p><strong style="color:#fff">Titel:</strong> Klaar voor meer online boekingen?</p>
    <p><strong style="color:#fff">Subtitel:</strong> Plan een korte demo. We laten website, dispatch en chauffeur-app zien — met jullie merkkleuren.</p>
    <p><strong style="color:#fff">CTA:</strong> Naar admin / Neem contact op</p>
</div>

<h2>Hoe publiceren</h2>
<ol style="color:var(--muted);padding-left:1.2rem;">
    <li>Review deze hub op <code>/marketing</code>.</li>
    <li>Code-defaults staan in <code>CentralWelcomePageService</code> (bijgewerkt bij implementatie).</li>
    <li>Productie-DB: Admin → <strong>Welkom</strong> teksten overnemen, of éénmalig <code>syncDefaultContent()</code> na goedkeuring (overschrijft bestaande centrale home).</li>
</ol>

<div class="note">Let op: sync overschrijft custom content op de centrale welkomstpagina. Alleen doen na expliciete review.</div>
@endsection
