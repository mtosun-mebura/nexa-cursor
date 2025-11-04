@extends('frontend.layouts.dashboard')

@section('title', 'Sollicitatie Details - NEXA Skillmatching')

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
            <span class="text-primary dark:text-primary-dark">Details</span>
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
        1 => ['title' => 'Senior Laravel Developer', 'company' => 'NEXA', 'location' => 'Utrecht', 'type' => 'Hybride', 'date' => now()->subDays(15), 'status' => 'In behandeling'],
        2 => ['title' => 'Frontend React Developer', 'company' => 'TechCorp', 'location' => 'Amsterdam', 'type' => 'Remote', 'date' => now()->subDays(12), 'status' => 'In behandeling'],
        3 => ['title' => 'DevOps Engineer', 'company' => 'CloudSoft', 'location' => 'Utrecht', 'type' => 'Hybride', 'date' => now()->subDays(10), 'status' => 'In behandeling'],
        4 => ['title' => 'Product Manager', 'company' => 'InnovateLab', 'location' => 'Amsterdam', 'type' => 'Remote', 'date' => now()->subDays(8), 'status' => 'Interview'],
        5 => ['title' => 'UX Designer', 'company' => 'DesignStudio', 'location' => 'Utrecht', 'type' => 'Hybride', 'date' => now()->subDays(6), 'status' => 'Interview'],
        6 => ['title' => 'Backend Python Developer', 'company' => 'DataFlow', 'location' => 'Amsterdam', 'type' => 'Remote', 'date' => now()->subDays(4), 'status' => 'Interview'],
        7 => ['title' => 'Full Stack Developer', 'company' => 'StartupXYZ', 'location' => 'Utrecht', 'type' => 'Hybride', 'date' => now()->subDays(20), 'status' => 'Aangenomen'],
        8 => ['title' => 'Data Scientist', 'company' => 'BigTech', 'location' => 'Amsterdam', 'type' => 'Remote', 'date' => now()->subDays(35), 'status' => 'Afgewezen'],
        9 => ['title' => 'Mobile App Developer', 'company' => 'MobileFirst', 'location' => 'Utrecht', 'type' => 'Hybride', 'date' => now()->subDays(42), 'status' => 'Afgewezen'],
        10 => ['title' => 'Cloud Architect', 'company' => 'CloudGiant', 'location' => 'Amsterdam', 'type' => 'Remote', 'date' => now()->subDays(38), 'status' => 'Afgewezen'],
        11 => ['title' => 'QA Engineer', 'company' => 'QualityAssure', 'location' => 'Utrecht', 'type' => 'Hybride', 'date' => now()->subDays(30), 'status' => 'Afgewezen'],
        12 => ['title' => 'Technical Lead', 'company' => 'TechLeader', 'location' => 'Amsterdam', 'type' => 'Remote', 'date' => now()->subDays(25), 'status' => 'Afgewezen'],
    ];
    
    $application = $applications[$applicationId] ?? $applications[1];
@endphp

<!-- Application Header -->
<div class="card p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $application['title'] }}</h1>
            <p class="text-lg text-muted dark:text-muted-dark mb-4">{{ $application['company'] }}</p>
            
            <div class="flex flex-wrap gap-4 text-sm text-muted dark:text-muted-dark mb-4">
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ $application['location'] }}
                </div>
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    {{ $application['type'] }}
                </div>
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                    </svg>
                    Sollicitatie ingediend op {{ $application['date']->format('d M Y') }}
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <span class="badge {{ $application['status'] == 'In behandeling' ? 'bg-blue-50 text-blue-700 dark:bg-blue-700/20 dark:text-blue-200' : ($application['status'] == 'Interview' ? 'bg-green-50 text-green-700 dark:bg-green-700/20 dark:text-green-200' : ($application['status'] == 'Aangenomen' ? 'bg-purple-50 text-purple-700 dark:bg-purple-700/20 dark:text-purple-200' : 'bg-red-50 text-red-700 dark:bg-red-700/20 dark:text-red-200')) }}">
                    {{ $application['status'] }}
                </span>
            </div>
        </div>
        
        <div class="flex flex-col gap-3 lg:min-w-[200px]">
            <a href="{{ route('applications.status', $applicationId) }}" class="btn btn-primary w-full">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Bekijk status
            </a>
        </div>
    </div>
</div>

<!-- Application Details -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Application Information -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Sollicitatie Informatie</h2>
        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-medium text-muted dark:text-muted-dark mb-1">Status</h3>
                <p class="text-base text-gray-900 dark:text-white">{{ $application['status'] }}</p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-muted dark:text-muted-dark mb-1">Ingediend op</h3>
                <p class="text-base text-gray-900 dark:text-white">{{ $application['date']->format('d M Y') }}</p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-muted dark:text-muted-dark mb-1">Bedrijf</h3>
                <p class="text-base text-gray-900 dark:text-white">{{ $application['company'] }}</p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-muted dark:text-muted-dark mb-1">Functie</h3>
                <p class="text-base text-gray-900 dark:text-white">{{ $application['title'] }}</p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-muted dark:text-muted-dark mb-1">Locatie</h3>
                <p class="text-base text-gray-900 dark:text-white">{{ $application['location'] }}</p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-muted dark:text-muted-dark mb-1">Werktype</h3>
                <p class="text-base text-gray-900 dark:text-white">{{ $application['type'] }}</p>
            </div>
        </div>
    </div>

    <!-- Application Timeline -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Status Geschiedenis</h2>
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <div class="flex flex-col items-center">
                    <div class="h-3 w-3 rounded-full bg-green-500"></div>
                    <div class="w-0.5 h-8 bg-gray-200 dark:bg-gray-700 mt-1"></div>
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900 dark:text-white">Sollicitatie ingediend</h3>
                    <p class="text-sm text-muted dark:text-muted-dark">{{ $application['date']->format('d M Y') }}</p>
                </div>
            </div>
            
            @if($application['status'] != 'Afgewezen')
            <div class="flex items-start gap-3">
                <div class="flex flex-col items-center">
                    <div class="h-3 w-3 rounded-full bg-green-500"></div>
                    <div class="w-0.5 h-8 bg-gray-200 dark:bg-gray-700 mt-1"></div>
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900 dark:text-white">CV bekeken</h3>
                    <p class="text-sm text-muted dark:text-muted-dark">{{ $application['date']->addDays(2)->format('d M Y') }}</p>
                </div>
            </div>
            @endif
            
            @if($application['status'] == 'Interview' || $application['status'] == 'Aangenomen')
            <div class="flex items-start gap-3">
                <div class="flex flex-col items-center">
                    <div class="h-3 w-3 rounded-full bg-blue-500"></div>
                    @if($application['status'] == 'Aangenomen')
                    <div class="w-0.5 h-8 bg-gray-200 dark:bg-gray-700 mt-1"></div>
                    @endif
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900 dark:text-white">Interview gepland</h3>
                    <p class="text-sm text-muted dark:text-muted-dark">{{ $application['date']->addDays(5)->format('d M Y') }}</p>
                </div>
            </div>
            @endif
            
            @if($application['status'] == 'Aangenomen')
            <div class="flex items-start gap-3">
                <div class="flex flex-col items-center">
                    <div class="h-3 w-3 rounded-full bg-purple-500"></div>
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900 dark:text-white">Aangenomen</h3>
                    <p class="text-sm text-muted dark:text-muted-dark">{{ $application['date']->addDays(8)->format('d M Y') }}</p>
                </div>
            </div>
            @endif
            
            @if($application['status'] == 'Afgewezen')
            <div class="flex items-start gap-3">
                <div class="flex flex-col items-center">
                    <div class="h-3 w-3 rounded-full bg-red-500"></div>
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900 dark:text-white">Afgewezen</h3>
                    <p class="text-sm text-muted dark:text-muted-dark">{{ $application['date']->addDays(5)->format('d M Y') }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Contact Information -->
@if($application['status'] == 'Aangenomen')
<div class="card p-6 mt-6">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Contact Informatie</h2>
    <div class="space-y-3">
        <div class="flex items-center gap-2 text-sm text-muted dark:text-muted-dark">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
            </svg>
            contact@{{ strtolower(str_replace(' ', '', $application['company'])) }}.nl
        </div>
        
        <div class="flex items-center gap-2 text-sm text-muted dark:text-muted-dark">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
            </svg>
            +31 20 123 4567
        </div>
    </div>
</div>
@endif
@endsection


