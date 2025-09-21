@extends('frontend.layouts.dashboard')

@section('title', 'Dashboard - NEXA Skillmatching')

@section('content')
<section class="flex flex-wrap items-center justify-between gap-3">
  <div>
    <h1 class="text-2xl font-semibold leading-tight">Dashboard</h1>
    <p class="text-sm text-muted dark:text-muted-dark">Welkom terug, {{ Auth::user()->first_name }}!</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="pill"><span class="h-2 w-2 rounded-full bg-brand-500"></span> Nieuwe matches</span>
    <span class="pill">42 resultaten</span>
  </div>
</section>

<!-- Stats Cards -->
<section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">Totaal Matches</p>
        <p class="text-2xl font-semibold">42</p>
      </div>
      <div class="h-8 w-8 bg-brand-100 dark:bg-brand-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
      </div>
    </div>
  </div>
  
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">Actieve Sollicitaties</p>
        <p class="text-2xl font-semibold">8</p>
      </div>
      <div class="h-8 w-8 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
    </div>
  </div>
  
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">Interviews</p>
        <p class="text-2xl font-semibold">3</p>
      </div>
      <div class="h-8 w-8 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
    </div>
  </div>
  
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">Profiel Compleet</p>
        <p class="text-2xl font-semibold">85%</p>
      </div>
      <div class="h-8 w-8 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
      </div>
    </div>
  </div>
</section>

<!-- Recent Matches -->
<section>
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold">Recente Matches</h2>
    <a href="{{ route('matches') }}" class="text-sm text-brand-600 dark:text-brand-400 hover:underline">Bekijk alle</a>
  </div>
  
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @foreach (range(1,6) as $i)
    <article class="card p-3 flex flex-col gap-2">
      <header class="flex items-start justify-between gap-2">
        <div>
          <h3 class="font-semibold leading-tight">Senior Laravel Developer</h3>
          <p class="text-sm text-muted dark:text-muted-dark">NEXA · Amsterdam · Hybride</p>
        </div>
        <span class="badge">€ 5.000–6.000</span>
      </header>

      <p class="text-sm text-muted dark:text-muted-dark line-clamp-3">
        Bouw aan een schaalbaar matching-platform met queues en event-driven architectuur.
      </p>

      <div class="space-y-2">
        <div class="flex items-center justify-between text-sm">
          <span class="text-muted dark:text-muted-dark">Matchscore</span>
          <strong>86%</strong>
        </div>
        <div class="match"><span style="width:86%"></span></div>
        <div class="flex flex-wrap gap-2">
          <span class="pill">Laravel</span><span class="pill">MySQL</span>
          <span class="pill">Docker</span><span class="pill">Tailwind</span>
        </div>
      </div>

      <div class="mt-auto flex items-center gap-2 pt-2">
        <a href="#" class="btn btn-outline">Details</a>
        <button class="btn btn-primary">Solliciteer</button>
      </div>
    </article>
    @endforeach
  </div>
</section>
@endsection
