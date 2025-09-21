@extends('frontend.layouts.dashboard')

@section('title', 'Matches - NEXA Skillmatching')

@section('content')
<section class="flex flex-wrap items-center justify-between gap-3">
  <div>
    <h1 class="text-2xl font-semibold leading-tight">Vacature-matching</h1>
    <p class="text-sm text-muted dark:text-muted-dark">Geselecteerd op jouw profiel en voorkeuren.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="pill"><span class="h-2 w-2 rounded-full bg-brand-500"></span> Nieuwe matches</span>
    <span class="pill">42 resultaten</span>
  </div>
</section>

<section class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
  @foreach (range(1,12) as $i)
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
        <strong>{{ rand(70, 95) }}%</strong>
      </div>
      <div class="match"><span style="width:{{ rand(70, 95) }}%"></span></div>
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
</section>
@endsection
