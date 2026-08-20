@extends('marketing.layout')

@section('content')
<div class="badge">Conversie-engine</div>
<h1>Website builder: merk + boeking zonder apart CMS</h1>
<p class="lead">Elke tenant krijgt een eigen site met thema’s, secties, SEO en inzetbare modules (boeking, reviews, vacatures).</p>

<div class="feature-visual" style="margin-bottom:1.25rem;">
    <img src="{{ asset('assets/marketing/images/feature-website-builder.png') }}" alt="Website builder">
</div>

<h2>Waarom dit verkoopt</h2>
<ul>
    <li>Taxibedrijf wil “online zichtbaar”: website is de haak</li>
    <li>Boekingsmodule op dezelfde site = korte weg naar omzet</li>
    <li>White-label past bij merk; jij levert platform</li>
</ul>

<h2>Componenten die tellen</h2>
<ul>
    <li>Taxi boekingsmodule (v1/v2 met kaart)</li>
    <li>Google Reviews</li>
    <li>NEXA modules-overzicht (platform-home)</li>
</ul>

<div class="cta-row">
    <a class="btn btn-primary" href="{{ route('marketing.show', 'website-copy') }}">Copy voor nexasuite.nl</a>
</div>
@endsection
