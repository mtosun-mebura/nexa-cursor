@extends('frontend.layouts.dashboard')

@section('title', 'Mijn Profiel - NEXA Skillmatching')

@section('content')
<section class="flex flex-wrap items-center justify-between gap-3">
  <div>
    <h1 class="text-2xl font-semibold leading-tight">Mijn Profiel</h1>
    <p class="text-sm text-muted dark:text-muted-dark">Beheer je persoonlijke informatie en vaardigheden.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="pill">85% compleet</span>
  </div>
</section>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- Profile Overview -->
  <div class="lg:col-span-1">
    <div class="card p-6 text-center">
      <div class="w-20 h-20 bg-brand-100 dark:bg-brand-900/20 rounded-full mx-auto mb-4 flex items-center justify-center">
        <svg class="w-10 h-10 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
      </div>
      <h3 class="font-semibold text-lg mb-1">{{ Auth::user()->first_name ?? 'Gebruiker' }} {{ Auth::user()->last_name ?? '' }}</h3>
      <p class="text-sm text-muted dark:text-muted-dark mb-4">Senior Developer</p>
      
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-muted dark:text-muted-dark">Profiel compleet</span>
          <span class="font-medium">85%</span>
        </div>
        <div class="w-full bg-border dark:bg-border-dark rounded-full h-2">
          <div class="bg-brand-500 h-2 rounded-full" style="width: 85%"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Profile Form -->
  <div class="lg:col-span-2 space-y-6">
    <!-- Personal Information -->
    <div class="card p-6">
      <h3 class="font-semibold text-lg mb-4">Persoonlijke Informatie</h3>
      <form class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-muted dark:text-muted-dark">Voornaam</label>
            <input type="text" class="input mt-1" value="{{ Auth::user()->first_name ?? '' }}" placeholder="Voornaam">
          </div>
          <div>
            <label class="text-sm font-medium text-muted dark:text-muted-dark">Achternaam</label>
            <input type="text" class="input mt-1" value="{{ Auth::user()->last_name ?? '' }}" placeholder="Achternaam">
          </div>
        </div>
        
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">E-mailadres</label>
          <input type="email" class="input mt-1" value="{{ Auth::user()->email ?? '' }}" placeholder="E-mailadres">
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-muted dark:text-muted-dark">Telefoonnummer</label>
            <input type="tel" class="input mt-1" placeholder="+31 6 12345678">
          </div>
          <div>
            <label class="text-sm font-medium text-muted dark:text-muted-dark">Locatie</label>
            <input type="text" class="input mt-1" placeholder="Amsterdam, Nederland">
          </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Opslaan</button>
      </form>
    </div>

    <!-- Skills -->
    <div class="card p-6">
      <h3 class="font-semibold text-lg mb-4">Vaardigheden</h3>
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Technische Vaardigheden</label>
          <div class="flex flex-wrap gap-2 mt-2">
            <span class="pill">Laravel</span>
            <span class="pill">PHP</span>
            <span class="pill">MySQL</span>
            <span class="pill">JavaScript</span>
            <span class="pill">Vue.js</span>
            <span class="pill">Docker</span>
            <button class="pill border-dashed border-2 border-border dark:border-border-dark text-muted dark:text-muted-dark">
              + Toevoegen
            </button>
          </div>
        </div>
        
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Soft Skills</label>
          <div class="flex flex-wrap gap-2 mt-2">
            <span class="pill">Teamwork</span>
            <span class="pill">Leiderschap</span>
            <span class="pill">Probleemoplossing</span>
            <button class="pill border-dashed border-2 border-border dark:border-border-dark text-muted dark:text-muted-dark">
              + Toevoegen
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Experience -->
    <div class="card p-6">
      <h3 class="font-semibold text-lg mb-4">Werkervaring</h3>
      <div class="space-y-4">
        <div class="border-l-2 border-brand-500 pl-4">
          <h4 class="font-medium">Senior Developer</h4>
          <p class="text-sm text-muted dark:text-muted-dark">TechCorp · 2020 - Heden</p>
          <p class="text-sm mt-1">Ontwikkeling van schaalbare webapplicaties met Laravel en Vue.js.</p>
        </div>
        <div class="border-l-2 border-border dark:border-border-dark pl-4">
          <h4 class="font-medium">Full Stack Developer</h4>
          <p class="text-sm text-muted dark:text-muted-dark">StartupXYZ · 2018 - 2020</p>
          <p class="text-sm mt-1">Bouw van complete webapplicaties van frontend tot backend.</p>
        </div>
        <button class="btn btn-outline w-full">Werkervaring toevoegen</button>
      </div>
    </div>
  </div>
</div>
@endsection
