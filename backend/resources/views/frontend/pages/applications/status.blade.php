@extends('frontend.layouts.dashboard')

@section('title', 'Sollicitatie Status - NEXA Skillmatching')

@section('content')
<!-- Breadcrumb -->
<nav class="mb-6">
    <ol class="flex items-center space-x-2 text-sm text-muted dark:text-muted-dark">
        <li><a href="{{ route('dashboard') }}" class="hover:text-primary dark:hover:text-primary-dark">Dashboard</a></li>
        <li class="flex items-center">
            <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
            <a href="{{ route('applications') }}" class="hover:text-primary dark:hover:text-primary-dark">Mijn Sollicitaties</a>
        </li>
        <li class="flex items-center">
            <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
            <span class="text-primary dark:text-primary-dark">Status</span>
        </li>
    </ol>
</nav>

<!-- Back Button -->
<div class="mb-6">
    <a href="{{ route('applications') }}" class="inline-flex items-center text-sm text-muted dark:text-muted-dark hover:text-primary dark:hover:text-primary-dark transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Terug naar sollicitaties
    </a>
</div>

@php
    // Mock data gebaseerd op application ID
    $applications = [
        1 => ['title' => 'Senior Laravel Developer', 'company' => 'NEXA', 'location' => 'Utrecht', 'type' => 'Hybride', 'date' => now()->subDays(15), 'status' => 'In behandeling', 'statusDesc' => 'Je sollicitatie wordt momenteel beoordeeld door het HR team.'],
        2 => ['title' => 'Frontend React Developer', 'company' => 'TechCorp', 'location' => 'Amsterdam', 'type' => 'Remote', 'date' => now()->subDays(12), 'status' => 'In behandeling', 'statusDesc' => 'Je sollicitatie wordt momenteel beoordeeld door het HR team.'],
        3 => ['title' => 'DevOps Engineer', 'company' => 'CloudSoft', 'location' => 'Utrecht', 'type' => 'Hybride', 'date' => now()->subDays(10), 'status' => 'In behandeling', 'statusDesc' => 'Je sollicitatie wordt momenteel beoordeeld door het HR team.'],
        4 => ['title' => 'Product Manager', 'company' => 'InnovateLab', 'location' => 'Amsterdam', 'type' => 'Remote', 'date' => now()->subDays(8), 'status' => 'Interview', 'statusDesc' => 'Gefeliciteerd! Je bent geselecteerd voor een interview.', 'interviewDate' => now()->addDays(3)],
        5 => ['title' => 'UX Designer', 'company' => 'DesignStudio', 'location' => 'Utrecht', 'type' => 'Hybride', 'date' => now()->subDays(6), 'status' => 'Interview', 'statusDesc' => 'Gefeliciteerd! Je bent geselecteerd voor een interview.', 'interviewDate' => now()->addDays(2)],
        6 => ['title' => 'Backend Python Developer', 'company' => 'DataFlow', 'location' => 'Amsterdam', 'type' => 'Remote', 'date' => now()->subDays(4), 'status' => 'Interview', 'statusDesc' => 'Gefeliciteerd! Je bent geselecteerd voor een interview.', 'interviewDate' => now()->addDays(1)],
    ];
    
    $application = $applications[$applicationId] ?? $applications[1];
@endphp

<!-- Status Header -->
<div class="card p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $application['title'] }}</h1>
            <p class="text-lg text-muted dark:text-muted-dark mb-4">{{ $application['company'] }}</p>
            
            <div class="flex items-center gap-3 mb-4">
                <span class="badge {{ $application['status'] == 'In behandeling' ? 'bg-blue-50 text-blue-700 dark:bg-blue-700/20 dark:text-blue-200' : 'bg-green-50 text-green-700 dark:bg-green-700/20 dark:text-green-200' }}">
                    {{ $application['status'] }}
                </span>
            </div>
            
            <p class="text-base text-gray-700 dark:text-gray-300">
                {{ $application['statusDesc'] }}
            </p>
        </div>
        
        <div class="flex flex-col gap-3 lg:min-w-[200px]">
            <a href="{{ route('applications.show', $applicationId) }}" class="btn btn-outline w-full">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Bekijk details
            </a>
        </div>
    </div>
</div>

<!-- Status Details -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Current Status -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Huidige Status</h2>
        
        @if($application['status'] == 'In behandeling')
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0">
                    <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white">In behandeling</h3>
                    <p class="text-sm text-muted dark:text-muted-dark">Je sollicitatie wordt momenteel beoordeeld.</p>
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm text-blue-900 dark:text-blue-100">
                    <strong>Wat betekent dit?</strong><br>
                    Het HR team heeft je sollicitatie ontvangen en is deze aan het beoordelen. Dit proces kan enkele dagen tot weken duren. We houden je op de hoogte zodra er updates zijn.
                </p>
            </div>
        </div>
        @elseif($application['status'] == 'Interview')
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <div class="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center flex-shrink-0">
                    <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900 dark:text-white">Interview gepland</h3>
                    <p class="text-sm text-muted dark:text-muted-dark">Je bent geselecteerd voor een interview!</p>
                </div>
            </div>
            
            @if(isset($application['interviewDate']))
            <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-medium text-green-900 dark:text-green-100">Interview datum</span>
                </div>
                <p class="text-base text-green-800 dark:text-green-200">
                    {{ $application['interviewDate']->format('d M Y') }} om {{ $application['interviewDate']->format('H:i') }}:00
                </p>
                <p class="text-sm text-green-700 dark:text-green-300 mt-2">
                    Locatie: {{ $application['location'] }}
                </p>
            </div>
            @endif
        </div>
        @endif
    </div>

    <!-- Next Steps -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Volgende Stappen</h2>
        
        @if($application['status'] == 'In behandeling')
        <div class="space-y-4">
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <h3 class="font-medium text-gray-900 dark:text-white mb-2">Wat kun je doen?</h3>
                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Houd je e-mail in de gaten voor updates</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Bereid je voor op mogelijke vragen over je ervaring</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Blijf actief solliciteren op andere vacatures</span>
                    </li>
                </ul>
            </div>
        </div>
        @elseif($application['status'] == 'Interview')
        <div class="space-y-4">
            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <h3 class="font-medium text-green-900 dark:text-green-100 mb-2">Voorbereiding tips</h3>
                <ul class="space-y-2 text-sm text-green-800 dark:text-green-200">
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Lees je CV en motivatiebrief nog eens door</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Bereid vragen voor die je aan het bedrijf wilt stellen</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Bereid voorbeelden voor uit je werkervaring</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Zorg dat je op tijd aanwezig bent</span>
                    </li>
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Timeline -->
<div class="card p-6 mt-6">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Status Geschiedenis</h2>
    <div class="space-y-6">
        <div class="flex items-start gap-4">
            <div class="flex flex-col items-center">
                <div class="h-4 w-4 rounded-full bg-green-500 border-2 border-white dark:border-gray-800"></div>
                <div class="w-0.5 h-12 bg-gray-200 dark:bg-gray-700 mt-2"></div>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900 dark:text-white">Sollicitatie ingediend</h3>
                <p class="text-sm text-muted dark:text-muted-dark mb-1">{{ $application['date']->format('d M Y om H:i') }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Je sollicitatie is succesvol ingediend en ontvangen door het bedrijf.</p>
            </div>
        </div>
        
        <div class="flex items-start gap-4">
            <div class="flex flex-col items-center">
                <div class="h-4 w-4 rounded-full bg-green-500 border-2 border-white dark:border-gray-800"></div>
                <div class="w-0.5 h-12 bg-gray-200 dark:bg-gray-700 mt-2"></div>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900 dark:text-white">CV bekeken</h3>
                <p class="text-sm text-muted dark:text-muted-dark mb-1">{{ $application['date']->addDays(2)->format('d M Y om H:i') }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Je CV is bekeken door het recruitement team.</p>
            </div>
        </div>
        
        @if($application['status'] == 'Interview')
        <div class="flex items-start gap-4">
            <div class="flex flex-col items-center">
                <div class="h-4 w-4 rounded-full bg-blue-500 border-2 border-white dark:border-gray-800"></div>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900 dark:text-white">Interview gepland</h3>
                <p class="text-sm text-muted dark:text-muted-dark mb-1">
                    @if(isset($application['interviewDate']))
                        {{ $application['interviewDate']->format('d M Y om H:i') }}
                    @else
                        {{ now()->addDays(3)->format('d M Y om H:i') }}
                    @endif
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Je bent geselecteerd voor een interview. Succes met de voorbereiding!</p>
            </div>
        </div>
        @else
        <div class="flex items-start gap-4">
            <div class="flex flex-col items-center">
                <div class="h-4 w-4 rounded-full bg-blue-500 border-2 border-white dark:border-gray-800"></div>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900 dark:text-white">In behandeling</h3>
                <p class="text-sm text-muted dark:text-muted-dark mb-1">{{ $application['date']->addDays(3)->format('d M Y om H:i') }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Je sollicitatie wordt momenteel beoordeeld. We houden je op de hoogte van verdere ontwikkelingen.</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

