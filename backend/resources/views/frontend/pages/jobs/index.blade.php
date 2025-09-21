@extends('frontend.layouts.dashboard')

@section('title', 'Vacatures - NEXA Skillmatching')

@section('content')
<section class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-semibold leading-tight">Vacatures</h1>
    <p class="text-sm text-muted dark:text-muted-dark">Ontdek de nieuwste vacatures en vind de perfecte baan die bij jou past.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="pill">{{ $jobs->total() }} resultaten</span>
  </div>
</section>

<!-- Search and Filters -->
<div class="card p-4 mb-6">
  <div class="flex flex-col md:flex-row gap-4">
    <div class="flex-1">
      <input type="search" class="input" placeholder="Zoek vacatures, bedrijven of skills…" value="{{ request('q') }}">
    </div>
    <div class="flex gap-2">
      <select class="select w-auto">
        <option value="published_at">Nieuwste eerst</option>
        <option value="title">Titel A-Z</option>
        <option value="salary_min">Salaris (hoog-laag)</option>
        <option value="created_at">Oudste eerst</option>
      </select>
      <button class="btn btn-primary">Zoeken</button>
    </div>
  </div>
</div>

<!-- Jobs Grid -->
<div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
  @forelse($jobs as $job)
  <article class="card p-3 flex flex-col gap-2">
    <header class="flex items-start justify-between gap-2">
      <div>
        <h3 class="font-semibold leading-tight">{{ $job->title }}</h3>
        <p class="text-sm text-muted dark:text-muted-dark">{{ $job->company->name }} · {{ $job->location }}</p>
      </div>
      <span class="badge">{{ $job->salary_min ? '€' . number_format($job->salary_min, 0, ',', '.') . '–' . number_format($job->salary_max, 0, ',', '.') : 'Salaris op aanvraag' }}</span>
    </header>

    <p class="text-sm text-muted dark:text-muted-dark line-clamp-3">
      {{ Str::limit(strip_tags($job->description), 120) }}
    </p>

    <div class="space-y-2">
      <div class="flex items-center justify-between text-sm">
        <span class="text-muted dark:text-muted-dark">Categorie</span>
        <strong>{{ $job->category->name }}</strong>
      </div>
      <div class="flex flex-wrap gap-2">
        <span class="pill">{{ $job->location }}</span>
        <span class="pill">{{ $job->category->name }}</span>
        @if($job->employment_type)
        <span class="pill">{{ $job->employment_type }}</span>
        @endif
      </div>
    </div>

    <div class="mt-auto flex items-center gap-2 pt-2">
      <a href="{{ route('jobs.show', $job) }}" class="btn btn-outline">Details</a>
      <button class="btn btn-primary">Solliciteer</button>
    </div>
  </article>
  @empty
  <div class="col-span-full text-center py-12">
    <div class="w-16 h-16 bg-card dark:bg-card-dark rounded-full flex items-center justify-center mx-auto mb-4">
      <svg class="w-8 h-8 text-muted dark:text-muted-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
      </svg>
    </div>
    <h3 class="text-lg font-medium text-text dark:text-text-dark mb-2">Geen vacatures gevonden</h3>
    <p class="text-muted dark:text-muted-dark">Probeer je zoekcriteria aan te passen of kom later terug voor nieuwe kansen!</p>
  </div>
  @endforelse
</div>

<!-- Pagination -->
@if($jobs->hasPages())
  <div class="mt-8">
    {{ $jobs->links() }}
  </div>
@endif
@endsection