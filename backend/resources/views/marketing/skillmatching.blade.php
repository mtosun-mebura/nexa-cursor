@extends('marketing.layout')

@section('content')
<div class="badge">Tweede vertical</div>
<h1>Nexa Skillmatching — vacatures, AI-matches, interviews</h1>
<p class="lead">Recruitmentmodule voor uitzend en HR: van publicatie tot match en interview in één flow.</p>

<div class="feature-visual" style="margin-bottom:1.25rem;">
    <img src="{{ asset('assets/marketing/images/feature-skillmatching.png') }}" alt="Skillmatching matching">
</div>

<h2>Functionaliteit</h2>
<ul>
    <li>Branches &amp; vacatures</li>
    <li>AI-matching op skills, locatie, ervaring</li>
    <li>Matches-pipeline en interviews</li>
    <li>Kandidatenportaal (dashboard, jobs, matches, agenda)</li>
</ul>

<h2>Wanneer verkopen</h2>
<p>Nadat Taxi-pipeline loopt, of aan recruitmentpartijen die al een NEXA-website willen. Positioneer niet als vervanging van ATS voor enterprise — wél als snelle matching + portaal.</p>

<div class="cta-row">
    <a class="btn btn-secondary" href="{{ route('marketing.index') }}">Terug</a>
</div>
@endsection
