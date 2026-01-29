@extends('frontend.layouts.dashboard')

@section('title', 'Mijn Sollicitaties - NEXA Skillmatching')

@php
  $statusLabel = function ($status) {
    return match($status) {
      'initiated' => 'In behandeling',
      'submitted' => 'In behandeling',
      'interview' => 'Interview',
      'offer' => 'Aanbod',
      'rejected' => 'Afgewezen',
      default => $status,
    };
  };
  $statusBadgeClass = function ($status) {
    return match($status) {
      'initiated', 'submitted' => 'bg-blue-50 text-blue-700 dark:bg-blue-700/20 dark:text-blue-200',
      'interview' => 'bg-green-50 text-green-700 dark:bg-green-700/20 dark:text-green-200',
      'offer' => 'bg-purple-50 text-purple-700 dark:bg-purple-700/20 dark:text-purple-200',
      'rejected' => 'bg-red-50 text-red-700 dark:bg-red-700/20 dark:text-red-200',
      default => 'bg-gray-50 text-gray-700 dark:bg-gray-700/20 dark:text-gray-200',
    };
  };
@endphp

@section('content')
<section class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-semibold leading-tight">Mijn Sollicitaties</h1>
    <p class="text-sm text-muted dark:text-muted-dark">Overzicht van al je sollicitaties en hun status.</p>
  </div>
  @if($applications->isNotEmpty())
  <div class="flex items-center gap-2">
    <span class="pill"><span class="h-2 w-2 rounded-full bg-green-500"></span> {{ $stats['in_progress'] + $stats['interview'] + $stats['offer'] }} actieve sollicitaties</span>
    @if($stats['interview'] > 0)
    <span class="pill">{{ $stats['interview'] }} interview(s) gepland</span>
    @endif
  </div>
  @endif
</section>

<!-- Quick Stats -->
<section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">Totaal Sollicitaties</p>
        <p class="text-2xl font-semibold">{{ $stats['total'] }}</p>
      </div>
      <div class="h-8 w-8 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
      </div>
    </div>
  </div>
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">In Behandeling</p>
        <p class="text-2xl font-semibold">{{ $stats['in_progress'] }}</p>
      </div>
      <div class="h-8 w-8 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
    </div>
  </div>
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">Interviews</p>
        <p class="text-2xl font-semibold">{{ $stats['interview'] }}</p>
      </div>
      <div class="h-8 w-8 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
    </div>
  </div>
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">Aanbod / Afgewezen</p>
        <p class="text-2xl font-semibold">{{ $stats['offer'] + $stats['rejected'] }}</p>
      </div>
      <div class="h-8 w-8 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
    </div>
  </div>
</section>

@if($applications->isNotEmpty())
<!-- Application Status Tabs (informational) -->
<div class="flex flex-wrap gap-1 bg-card dark:bg-card-dark p-1 rounded-xl mb-6">
  <span class="px-3 py-2 text-sm font-medium rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark">
    Alle ({{ $stats['total'] }})
  </span>
  <span class="px-3 py-2 text-sm font-medium rounded-lg text-muted dark:text-muted-dark">
    In behandeling ({{ $stats['in_progress'] }})
  </span>
  <span class="px-3 py-2 text-sm font-medium rounded-lg text-muted dark:text-muted-dark">
    Interview ({{ $stats['interview'] }})
  </span>
  <span class="px-3 py-2 text-sm font-medium rounded-lg text-muted dark:text-muted-dark">
    Afgewezen ({{ $stats['rejected'] }})
  </span>
</div>

<!-- Applications List -->
<section class="space-y-4" id="applications_list">
  @foreach ($applications as $application)
  @php $vacancy = $application->vacancy; $company = $vacancy->company ?? null; @endphp
  <article class="card p-4 hover:shadow-md transition-shadow duration-200 cursor-pointer" data-href="{{ route('applications.show', $application) }}">
    <div class="flex items-start justify-between gap-4">
      <div class="flex-1">
        <div class="flex items-center gap-3 mb-2">
          <h3 class="font-semibold text-lg hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
            {{ $vacancy->title }}
          </h3>
          <span class="badge {{ $statusBadgeClass($application->status) }}">
            {{ $statusLabel($application->status) }}
          </span>
        </div>
        <p class="text-sm text-muted dark:text-muted-dark mb-2">
          @if($company){{ $company->name }}@endif
          @if($vacancy->location) · {{ $vacancy->location }}@endif
          @if($vacancy->remote_work) · Remote @endif
          @if($vacancy->employment_type) · {{ $vacancy->employment_type }}@endif
        </p>
        <p class="text-sm text-muted dark:text-muted-dark mb-3">
          Sollicitatie ingediend op {{ $application->created_at->translatedFormat('d M Y') }}
        </p>

        @if(in_array($application->status, ['initiated', 'submitted']))
        <div class="flex items-center gap-4 text-sm mb-3">
          <div class="flex items-center gap-2">
            <div class="h-2 w-2 rounded-full bg-green-500"></div>
            <span class="text-muted dark:text-muted-dark">Sollicitatie ontvangen</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="h-2 w-2 rounded-full bg-yellow-500"></div>
            <span class="text-muted dark:text-muted-dark">In behandeling</span>
          </div>
        </div>
        @endif

        @if($application->status === 'interview')
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 mb-3">
          <div class="flex items-center gap-2 mb-1">
            <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Interview gepland</span>
          </div>
          <p class="text-sm text-blue-700 dark:text-blue-300">Bekijk de statuspagina voor datum en tijd.</p>
        </div>
        @endif

        @if($application->status === 'offer')
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 mb-3">
          <div class="flex items-center gap-2 mb-1">
            <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-sm font-medium text-green-900 dark:text-green-100">Aanbod ontvangen</span>
          </div>
          <p class="text-sm text-green-700 dark:text-green-300">De werkgever heeft je een aanbod gedaan. Bekijk de details op de statuspagina.</p>
        </div>
        @endif
      </div>

      <div class="flex flex-col gap-2" data-actions-cell>
        @if(in_array($application->status, ['initiated', 'submitted', 'interview', 'offer']))
        <a href="{{ route('applications.status', $application) }}" class="btn btn-primary text-sm">Bekijk status</a>
        @endif
        <a href="{{ route('applications.show', $application) }}" class="btn btn-outline text-sm">Details</a>
        <a href="{{ route('jobs.index') }}" class="btn btn-outline text-sm">Nieuwe sollicitatie</a>
      </div>
    </div>
  </article>
  @endforeach
</section>
@else
<!-- Empty State -->
<div class="text-center py-12">
  <div class="w-16 h-16 bg-card dark:bg-card-dark rounded-full flex items-center justify-center mx-auto mb-4">
    <svg class="w-8 h-8 text-muted dark:text-muted-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
  </div>
  <h3 class="text-lg font-medium text-text dark:text-text-dark mb-2">Nog geen sollicitaties</h3>
  <p class="text-muted dark:text-muted-dark mb-4">Begin met solliciteren op interessante vacatures!</p>
  <a href="{{ route('jobs.index') }}" class="btn btn-primary">Bekijk vacatures</a>
</div>
@endif

@if($applications->isNotEmpty())
<script>
document.getElementById('applications_list').addEventListener('click', function(e) {
  const card = e.target.closest('article[data-href]');
  if (!card) return;
  if (e.target.closest('[data-actions-cell]') || e.target.closest('a') || e.target.closest('button')) return;
  window.location.href = card.dataset.href;
});
</script>
@endif
@endsection
