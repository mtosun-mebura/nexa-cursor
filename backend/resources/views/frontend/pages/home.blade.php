@extends('frontend.layouts.app')

@section('title', 'Nexa Skillmatching - Vind je droombaan')
@section('description', 'Ontdek de perfecte match tussen jouw vaardigheden en vacatures. Ons AI-platform helpt je de ideale baan te vinden.')

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900 section-padding relative overflow-hidden">
    <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
    <div class="container-custom relative z-10">
        <div class="w-full text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
                Vind je <span class="text-blue-200 dark:text-blue-300">droombaan</span> met AI
            </h1>
            <p class="text-xl text-blue-100 dark:text-blue-200 mb-8 w-full leading-relaxed max-w-3xl mx-auto">
                Ons geavanceerde AI-platform matcht jouw vaardigheden met de perfecte vacatures van topbedrijven. Start vandaag nog je carrière.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg hover-lift bg-white text-blue-600 hover:bg-blue-50 dark:bg-blue-600 dark:text-white dark:hover:bg-blue-700">
                    Gratis account aanmaken
                </a>
                <a href="{{ route('jobs.index') }}" class="inline-flex items-center justify-center px-8 py-4 bg-transparent hover:bg-white text-white hover:text-blue-600 dark:hover:text-blue-700 font-semibold rounded-lg border-2 border-white hover:border-white shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                    Vacatures bekijken
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="container-custom">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="stat-card text-center p-6">
                <div class="stat-number text-3xl font-bold text-blue-600 dark:text-blue-400 mb-2">10,000+</div>
                <div class="stat-label text-gray-600 dark:text-gray-300">Actieve vacatures</div>
            </div>
            <div class="stat-card text-center p-6">
                <div class="stat-number text-3xl font-bold text-green-600 dark:text-green-400 mb-2">5,000+</div>
                <div class="stat-label text-gray-600 dark:text-gray-300">Succesvolle matches</div>
            </div>
            <div class="stat-card text-center p-6">
                <div class="stat-number text-3xl font-bold text-purple-600 dark:text-purple-400 mb-2">500+</div>
                <div class="stat-label text-gray-600 dark:text-gray-300">Partner bedrijven</div>
            </div>
            <div class="stat-card text-center p-6">
                <div class="stat-number text-3xl font-bold text-orange-600 dark:text-orange-400 mb-2">95%</div>
                <div class="stat-label text-gray-600 dark:text-gray-300">Match accuracy</div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="section-padding bg-white dark:bg-gray-900">
    <div class="container-custom">
        <div class="!max-w-7xl mx-auto" style="max-width: 80rem !important;">
            <div class="mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    Waarom kiezen voor <span class="text-gradient">Nexa</span>?
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 leading-relaxed text-center">
                    Onze geavanceerde AI-technologie maakt het vinden van de perfecte baan eenvoudiger dan ooit.
                </p>
            </div>
            
            <div class="mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    Wat Wij Bieden
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="card p-6 hover:shadow-lg transition-all duration-300">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                            AI-Powered Matching
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Onze geavanceerde algoritmes analyseren je vaardigheden en vinden de perfecte match met 95% accuracy.
                        </p>
                    </div>
                    
                    <div class="card p-6 hover:shadow-lg transition-all duration-300">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                            Snelle Resultaten
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Vind relevante vacatures in seconden. Ons platform filtert en rangschikt resultaten op basis van jouw profiel.
                        </p>
                    </div>
                    
                    <div class="card p-6 hover:shadow-lg transition-all duration-300">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                            Betrouwbare Partners
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Werk samen met geverifieerde bedrijven en toegang tot exclusieve vacatures van topwerkgevers.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Jobs Section -->
<section class="section-padding bg-gray-50 dark:bg-gray-900">
    <div class="container-custom">
        <div class="!max-w-7xl mx-auto" style="max-width: 80rem !important;">
            <div class="mb-12 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                    Recente <span class="text-gradient">Vacatures</span>
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 leading-relaxed">
                    Ontdek de nieuwste vacatures van topbedrijven
                </p>
            </div>
            
            <!-- Jobs Grid -->
            @if($jobs->isEmpty())
            <div class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Geen vacatures beschikbaar</h3>
                <p class="text-gray-500 dark:text-gray-400">Kom later terug voor nieuwe vacatures!</p>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                @foreach($jobs as $job)
                @php
                    $companyName = $job->company->name ?? 'Directe werkgever';
                    $companyInitial = Str::upper(Str::substr($companyName, 0, 1));
                    $publishedLabel = $job->publication_date ? $job->publication_date->diffForHumans() : ($job->published_at ? $job->published_at->diffForHumans() : 'Onlangs');
                @endphp
                <article class="card p-6 hover:shadow-lg transition-all duration-300 hover:-translate-y-1 flex flex-col h-full">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-lg">{{ $companyInitial }}</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg text-gray-900 dark:text-white leading-tight">
                                    {{ $job->title }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $companyName }}
                                </p>
                            </div>
                        </div>
                        <span class="badge bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200">
                            @if($job->salary_min && $job->salary_max)
                                €{{ number_format($job->salary_min, 0, ',', '.') }}-{{ number_format($job->salary_max, 0, ',', '.') }}
                            @else
                                Salaris n.o.t.k.
                            @endif
                        </span>
                    </div>
                    
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-4 overflow-hidden flex-grow" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                        {{ Str::limit(strip_tags($job->description), 120) }}
                    </p>
                    
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if($job->category)
                        <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 text-xs rounded-full">
                            {{ $job->category->name }}
                        </span>
                        @endif
                        @if($job->remote_work)
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-100 text-xs rounded-full">
                            Remote
                        </span>
                        @endif
                        @if($job->travel_expenses)
                        <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/20 text-purple-800 dark:text-purple-200 text-xs rounded-full">
                            Reiskosten
                        </span>
                        @endif
                    </div>
                    
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ $job->location ?? 'Locatie onbekend' }}
                        </div>
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $publishedLabel }}
                        </div>
                    </div>
                    
                    <div class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $job->employment_type ?? 'Niet opgegeven' }}
                            </span>
                            <div class="flex items-center">
                                <a href="{{ route('jobs.show', $job) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium">
                                    Details
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>
            @endif
            
            <div class="text-center">
                <a href="{{ route('jobs.index') }}" class="btn btn-primary btn-lg hover-lift inline-flex items-center">
                    Bekijk alle vacatures
                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section-padding bg-white dark:bg-gray-900">
    <div class="container-custom">
        <div class="!max-w-7xl mx-auto" style="max-width: 80rem !important;">
            <div class="text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                    Klaar om je carrière te starten?
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
                    Sluit je aan bij duizenden professionals die hun droombaan hebben gevonden.
                </p>
                <div class="flex flex-col items-center gap-4">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg hover-lift">
                        Gratis account aanmaken
                    </a>
                    <a href="{{ route('jobs.index') }}" class="btn btn-outline btn-lg hover-lift">
                        Vacatures bekijken
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection