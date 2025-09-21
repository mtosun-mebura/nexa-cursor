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
        <div class="w-full">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                    Waarom kiezen voor <span class="text-gradient">Nexa</span>?
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 w-full leading-relaxed">
                    Onze geavanceerde AI-technologie maakt het vinden van de perfecte baan eenvoudiger dan ooit.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="card-hover p-8 text-center">
                    <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">AI-Powered Matching</h3>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        Onze geavanceerde algoritmes analyseren je vaardigheden en vinden de perfecte match met 95% accuracy.
                    </p>
                </div>
                
                <div class="card-hover p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/20 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Snelle Resultaten</h3>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        Vind relevante vacatures in seconden. Ons platform filtert en rangschikt resultaten op basis van jouw profiel.
                    </p>
                </div>
                
                <div class="card-hover p-8 text-center">
                    <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/20 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Betrouwbare Partners</h3>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        Werk samen met geverifieerde bedrijven en toegang tot exclusieve vacatures van topwerkgevers.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Jobs Section -->
<section class="section-padding bg-gray-50 dark:bg-gray-900">
    <div class="container-custom">
        <div class="w-full">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                    Recente <span class="text-gradient">Vacatures</span>
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 w-full leading-relaxed">
                    Ontdek de nieuwste vacatures van topbedrijven
                </p>
            </div>
            
            <!-- Jobs Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                @foreach (range(1, 6) as $i)
                <article class="card p-6 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-lg">
                                    @if($i == 1) N
                                    @elseif($i == 2) T
                                    @elseif($i == 3) A
                                    @elseif($i == 4) M
                                    @elseif($i == 5) S
                                    @else D
                                    @endif
                                </span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg text-gray-900 dark:text-white leading-tight">
                                    @if($i == 1) Senior Laravel Developer
                                    @elseif($i == 2) Frontend React Specialist
                                    @elseif($i == 3) DevOps Engineer
                                    @elseif($i == 4) Product Manager
                                    @elseif($i == 5) UX/UI Designer
                                    @else Data Scientist
                                    @endif
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    @if($i == 1) NEXA
                                    @elseif($i == 2) TechCorp
                                    @elseif($i == 3) CloudSoft
                                    @elseif($i == 4) InnovateLab
                                    @elseif($i == 5) DesignStudio
                                    @else DataFlow
                                    @endif
                                </p>
                            </div>
                        </div>
                        <span class="badge bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200">
                            @if($i == 1) €5.000-6.000
                            @elseif($i == 2) €4.500-5.500
                            @elseif($i == 3) €5.500-7.000
                            @elseif($i == 4) €6.000-8.000
                            @elseif($i == 5) €4.000-5.000
                            @else €5.500-6.500
                            @endif
                        </span>
                    </div>
                    
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-4 overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                        @if($i == 1) Bouw mee aan een schaalbaar matching-platform met moderne Laravel en event-driven architectuur.
                        @elseif($i == 2) Ontwikkel gebruiksvriendelijke interfaces met React en moderne frontend technologieën.
                        @elseif($i == 3) Beheer cloud infrastructuur en implementeer CI/CD pipelines voor optimale performance.
                        @elseif($i == 4) Leid productontwikkeling en werk samen met cross-functionele teams aan innovatieve oplossingen.
                        @elseif($i == 5) Creëer intuïtieve gebruikerservaringen en visueel aantrekkelijke interfaces.
                        @else Analyseer complexe datasets en ontwikkel machine learning modellen voor business insights.
                        @endif
                    </p>
                    
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 text-xs rounded-full">
                            @if($i == 1) Laravel
                            @elseif($i == 2) React
                            @elseif($i == 3) AWS
                            @elseif($i == 4) Product
                            @elseif($i == 5) Figma
                            @else Python
                            @endif
                        </span>
                        <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/20 text-purple-800 dark:text-purple-200 text-xs rounded-full">
                            @if($i == 1) PHP
                            @elseif($i == 2) TypeScript
                            @elseif($i == 3) Docker
                            @elseif($i == 4) Agile
                            @elseif($i == 5) Adobe XD
                            @else Machine Learning
                            @endif
                        </span>
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 text-xs rounded-full">
                            @if($i == 1) MySQL
                            @elseif($i == 2) Next.js
                            @elseif($i == 3) Kubernetes
                            @elseif($i == 4) Scrum
                            @elseif($i == 5) Sketch
                            @else TensorFlow
                            @endif
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            @if($i % 2 == 0) Amsterdam @else Utrecht @endif
                        </div>
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            @if($i == 1) 2 dagen geleden
                            @elseif($i == 2) 1 dag geleden
                            @elseif($i == 3) 3 dagen geleden
                            @elseif($i == 4) 5 dagen geleden
                            @elseif($i == 5) 1 week geleden
                            @else 4 dagen geleden
                            @endif
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                @if($i % 3 == 0) Fulltime
                                @elseif($i % 3 == 1) Parttime
                                @else Hybride
                                @endif
                            </span>
                            <div class="flex items-center space-x-2">
                                <button class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium">
                                    Details
                                </button>
                                <button class="btn btn-primary text-sm px-4 py-2">
                                    Solliciteer
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>
            
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
        <div class="w-full text-center">
            <div class="card-elevated p-12 lg:p-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                    Klaar om je carrière te starten?
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-8 w-full">
                    Sluit je aan bij duizenden professionals die hun droombaan hebben gevonden.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
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