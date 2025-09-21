@extends('frontend.layouts.app')

@section('title', $job->title . ' - ' . $job->company->name . ' | Nexa Skillmatching')
@section('description', Str::limit(strip_tags($job->description), 160))

@section('content')
<!-- Job Header -->
<section class="bg-bg border-b border-border">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between">
            <!-- Job Info -->
            <div class="flex-1">
                <div class="flex items-start space-x-4">
                    <!-- Company Logo -->
                    <div class="flex-shrink-0">
                        @if($job->company->logo)
                            <img src="{{ Storage::url($job->company->logo) }}" 
                                 alt="{{ $job->company->name }}" 
                                 class="w-16 h-16 rounded-lg object-cover">
                        @else
                            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                <span class="text-blue-600 dark:text-blue-400 font-semibold text-2xl">
                                    {{ substr($job->company->name, 0, 1) }}
                                </span>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Job Details -->
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ $job->title }}
                        </h1>
                        <p class="text-xl text-gray-600 dark:text-gray-400 mb-4">
                            {{ $job->company->name }}
                        </p>
                        
                        <!-- Job Meta -->
                        <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ $job->location }}
                            </div>
                            
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                {{ $job->salary_min ? '€' . number_format($job->salary_min, 0, ',', '.') . ' - €' . number_format($job->salary_max, 0, ',', '.') : 'Salaris op aanvraag' }}
                            </div>
                            
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                {{ $job->category->name }}
                            </div>
                            
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $job->published_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Apply Button -->
            <div class="mt-6 lg:mt-0 lg:ml-8">
                <a href="#apply" 
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Solliciteren
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Job Content -->
<section class="py-12 bg-bg">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Job Description -->
                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Functieomschrijving</h2>
                    <div class="prose dark:prose-invert w-full">
                        {!! $job->description !!}
                    </div>
                </div>
                
                <!-- Requirements -->
                @if($job->requirements)
                    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Wat we zoeken</h2>
                        <div class="prose dark:prose-invert w-full">
                            {!! $job->requirements !!}
                        </div>
                    </div>
                @endif
                
                <!-- Benefits -->
                @if($job->benefits)
                    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Wat we bieden</h2>
                        <div class="prose dark:prose-invert w-full">
                            {!! $job->benefits !!}
                        </div>
                    </div>
                @endif
            </div>
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Company Info -->
                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Over {{ $job->company->name }}</h3>
                    @if($job->company->description)
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            {{ Str::limit(strip_tags($job->company->description), 200) }}
                        </p>
                    @endif
                </div>
                
                <!-- Job Details -->
                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Vacature details</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Locatie</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $job->location }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Salaris</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">
                                {{ $job->salary_min ? '€' . number_format($job->salary_min, 0, ',', '.') . ' - €' . number_format($job->salary_max, 0, ',', '.') : 'Op aanvraag' }}
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Categorie</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $job->category->name }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ ucfirst($job->employment_type) }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ervaring</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ ucfirst($job->experience_level) }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Geplaatst</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $job->published_at->format('d-m-Y') }}</dd>
                        </div>
                    </dl>
                </div>
                
                <!-- Apply Button -->
                <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Interesse?</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Solliciteer nu en maak kans op deze geweldige baan!
                    </p>
                    <a href="#apply" 
                       class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        Solliciteren
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Jobs -->
@if($relatedJobs->count() > 0)
    <section class="py-12 bg-white dark:bg-gray-900">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-8">Gerelateerde vacatures</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedJobs as $relatedJob)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200 group">
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-200 mb-2">
                                <a href="{{ route('jobs.show', $relatedJob) }}" class="hover:underline">
                                    {{ $relatedJob->title }}
                                </a>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                {{ $relatedJob->company->name }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-500">
                                {{ $relatedJob->location }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection
