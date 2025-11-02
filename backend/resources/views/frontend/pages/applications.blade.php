@extends('frontend.layouts.dashboard')

@section('title', 'Mijn Sollicitaties - NEXA Skillmatching')

@section('content')
<section class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-semibold leading-tight">Mijn Sollicitaties</h1>
    <p class="text-sm text-muted dark:text-muted-dark">Overzicht van al je sollicitaties en hun status.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="pill"><span class="h-2 w-2 rounded-full bg-green-500"></span> 8 actieve sollicitaties</span>
    <span class="pill">3 interviews gepland</span>
  </div>
</section>

<!-- Quick Stats -->
<section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">Totaal Sollicitaties</p>
        <p class="text-2xl font-semibold">12</p>
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
        <p class="text-2xl font-semibold">3</p>
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
        <p class="text-2xl font-semibold">3</p>
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
        <p class="text-sm text-muted dark:text-muted-dark">Succesvol</p>
        <p class="text-2xl font-semibold">1</p>
      </div>
      <div class="h-8 w-8 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
    </div>
  </div>
</section>

<!-- Application Status Tabs -->
<div class="flex space-x-1 bg-card dark:bg-card-dark p-1 rounded-xl mb-6">
  <button class="flex-1 px-3 py-2 text-sm font-medium rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark">
    Alle (12)
  </button>
  <button class="flex-1 px-3 py-2 text-sm font-medium rounded-lg text-muted dark:text-muted-dark hover:bg-surface dark:hover:bg-surface-dark">
    In behandeling (3)
  </button>
  <button class="flex-1 px-3 py-2 text-sm font-medium rounded-lg text-muted dark:text-muted-dark hover:bg-surface dark:hover:bg-surface-dark">
    Interview (3)
  </button>
  <button class="flex-1 px-3 py-2 text-sm font-medium rounded-lg text-muted dark:text-muted-dark hover:bg-surface dark:hover:bg-surface-dark">
    Afgewezen (5)
  </button>
</div>

<!-- Applications List -->
<section class="space-y-4">
  @foreach (range(1,12) as $i)
  <article class="card p-4 hover:shadow-md transition-shadow duration-200">
    <div class="flex items-start justify-between gap-4">
      <div class="flex-1">
        <div class="flex items-center gap-3 mb-2">
          <h3 class="font-semibold text-lg hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
            {{ $i == 1 ? 'Senior Laravel Developer' : ($i == 2 ? 'Frontend React Developer' : ($i == 3 ? 'DevOps Engineer' : ($i == 4 ? 'Product Manager' : ($i == 5 ? 'UX Designer' : ($i == 6 ? 'Backend Python Developer' : ($i == 7 ? 'Full Stack Developer' : ($i == 8 ? 'Data Scientist' : ($i == 9 ? 'Mobile App Developer' : ($i == 10 ? 'Cloud Architect' : ($i == 11 ? 'QA Engineer' : 'Technical Lead')))))))))) }}
          </h3>
          <span class="badge {{ $i <= 3 ? 'bg-blue-50 text-blue-700 dark:bg-blue-700/20 dark:text-blue-200' : ($i <= 6 ? 'bg-green-50 text-green-700 dark:bg-green-700/20 dark:text-green-200' : ($i == 7 ? 'bg-purple-50 text-purple-700 dark:bg-purple-700/20 dark:text-purple-200' : 'bg-red-50 text-red-700 dark:bg-red-700/20 dark:text-red-200')) }}">
            {{ $i <= 3 ? 'In behandeling' : ($i <= 6 ? 'Interview' : ($i == 7 ? 'Aangenomen' : 'Afgewezen')) }}
          </span>
        </div>
        <p class="text-sm text-muted dark:text-muted-dark mb-2">
          {{ $i == 1 ? 'NEXA' : ($i == 2 ? 'TechCorp' : ($i == 3 ? 'CloudSoft' : ($i == 4 ? 'InnovateLab' : ($i == 5 ? 'DesignStudio' : ($i == 6 ? 'DataFlow' : ($i == 7 ? 'StartupXYZ' : ($i == 8 ? 'BigTech' : ($i == 9 ? 'MobileFirst' : ($i == 10 ? 'CloudGiant' : ($i == 11 ? 'QualityAssure' : 'TechLeader')))))))))) }} · 
          {{ $i % 2 == 0 ? 'Amsterdam' : 'Utrecht' }} · 
          {{ $i % 3 == 0 ? 'Remote' : 'Hybride' }}
        </p>
        <p class="text-sm text-muted dark:text-muted-dark mb-3">
          Sollicitatie ingediend op {{ now()->subDays(rand(1, 45))->format('d M Y') }}
        </p>
        
        @if($i <= 6)
        <div class="flex items-center gap-4 text-sm mb-3">
          <div class="flex items-center gap-2">
            <div class="h-2 w-2 rounded-full bg-green-500"></div>
            <span class="text-muted dark:text-muted-dark">CV bekeken</span>
          </div>
          @if($i <= 3)
          <div class="flex items-center gap-2">
            <div class="h-2 w-2 rounded-full bg-yellow-500"></div>
            <span class="text-muted dark:text-muted-dark">In behandeling</span>
          </div>
          @elseif($i <= 6)
          <div class="flex items-center gap-2">
            <div class="h-2 w-2 rounded-full bg-blue-500"></div>
            <span class="text-muted dark:text-muted-dark">Interview gepland</span>
          </div>
          @endif
        </div>
        @endif

        @if($i <= 6 && $i > 3)
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 mb-3">
          <div class="flex items-center gap-2 mb-1">
            <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Interview gepland</span>
          </div>
          <p class="text-sm text-blue-700 dark:text-blue-300">
            {{ now()->addDays(rand(1, 7))->format('d M Y') }} om {{ rand(9, 17) }}:00
          </p>
        </div>
        @endif

        @if($i == 7)
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 mb-3">
          <div class="flex items-center gap-2 mb-1">
            <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-sm font-medium text-green-900 dark:text-green-100">Gefeliciteerd!</span>
          </div>
          <p class="text-sm text-green-700 dark:text-green-300">
            Je bent aangenomen voor deze functie. We nemen binnenkort contact met je op.
          </p>
        </div>
        @endif
      </div>
      
      <div class="flex flex-col gap-2">
        <a href="{{ route('applications.show', $i) }}" class="btn btn-outline text-sm">Details</a>
        @if($i <= 6)
        <a href="{{ route('applications.status', $i) }}" class="btn btn-primary text-sm">Bekijk status</a>
        @elseif($i == 7)
        <a href="{{ route('applications.show', $i) }}" class="btn btn-primary text-sm">Contact opnemen</a>
        @else
        <a href="{{ route('jobs.index') }}" class="btn btn-outline text-sm">Nieuwe sollicitatie</a>
        @endif
      </div>
    </div>
  </article>
  @endforeach
</section>

<!-- Empty State (hidden when applications exist) -->
<div class="hidden text-center py-12">
  <div class="w-16 h-16 bg-card dark:bg-card-dark rounded-full flex items-center justify-center mx-auto mb-4">
    <svg class="w-8 h-8 text-muted dark:text-muted-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
  </div>
  <h3 class="text-lg font-medium text-text dark:text-text-dark mb-2">Nog geen sollicitaties</h3>
  <p class="text-muted dark:text-muted-dark mb-4">Begin met solliciteren op interessante vacatures!</p>
  <a href="{{ route('jobs.index') }}" class="btn btn-primary">Bekijk vacatures</a>
</div>
@endsection
