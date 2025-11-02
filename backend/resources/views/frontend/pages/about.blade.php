@extends('frontend.layouts.app')

@section('title', 'Over Ons - NEXA Skillmatching')
@section('description', 'Ontdek wie we zijn en hoe we jou helpen de perfecte baan te vinden met geavanceerde AI-technologie.')

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900 section-padding relative overflow-hidden">
    <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
    <div class="container-custom relative z-10">
        <div class="w-full text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
                Over <span class="text-blue-200 dark:text-blue-300">NEXA</span>
            </h1>
            <p class="text-xl text-blue-100 dark:text-blue-200 mb-8 w-full leading-relaxed max-w-3xl mx-auto">
                Wij geloven dat iedereen de perfecte baan verdient. Met geavanceerde AI-technologie maken we de match tussen kandidaten en vacatures slimmer en sneller.
            </p>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="section-padding bg-white dark:bg-gray-900">
    <div class="container-custom">
        <div class="max-w-4xl mx-auto">
            <div class="mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    Onze Missie
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-300 leading-relaxed mb-6">
                    Bij NEXA Skillmatching zijn we gepassioneerd over het verbinden van talent met kansen. Onze missie is om het zoeken naar een baan te transformeren van een tijdrovend proces naar een efficiënte, gepersonaliseerde ervaring met behulp van geavanceerde AI-technologie.
                </p>
                <p class="text-lg text-gray-600 dark:text-gray-300 leading-relaxed">
                    We maken gebruik van machine learning en data-analyse om de perfecte match te vinden tussen jouw vaardigheden, ervaring en ambities en de beste vacatures op de markt. Dit bespaart je tijd en helpt je om sneller de baan te vinden die echt bij jou past.
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                            AI-Powered Matching
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Onze geavanceerde AI-algoritmes analyseren jouw profiel en matcht het automatisch met relevante vacatures voor de beste resultaten.
                        </p>
                    </div>

                    <div class="card p-6 hover:shadow-lg transition-all duration-300">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                            Snel & Efficiënt
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Bespaar uren aan zoeken door automatisch de meest relevante vacatures te vinden die perfect bij jouw profiel passen.
                        </p>
                    </div>

                    <div class="card p-6 hover:shadow-lg transition-all duration-300">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                            Gepersonaliseerde Ervaring
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Elke match is uniek en afgestemd op jouw specifieke vaardigheden, ervaring en carrièredoelen.
                        </p>
                    </div>

                    <div class="card p-6 hover:shadow-lg transition-all duration-300">
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/20 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                            Betrouwbaar & Veilig
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Je gegevens zijn veilig bij ons. We respecteren je privacy en beschermen je persoonlijke informatie met de hoogste standaarden.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    Waarom NEXA?
                </h2>
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Geavanceerde AI-Technologie
                            </h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                We gebruiken de nieuwste ontwikkelingen in machine learning en AI om de beste matches te maken.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Breed Aanbod van Vacatures
                            </h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Samenwerking met honderden bedrijven betekent dat je toegang hebt tot duizenden vacatures in verschillende sectoren.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-purple-100 dark:bg-purple-900/20 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Gebruiksvriendelijke Interface
                            </h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Ons platform is ontworpen met de gebruiker in gedachten - eenvoudig, intuïtief en toegankelijk voor iedereen.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-8 h-8 bg-orange-100 dark:bg-orange-900/20 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Continue Verbetering
                            </h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                We luisteren naar feedback en verbeteren voortdurend ons platform om je de beste ervaring te bieden.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                    Klaar om te Beginnen?
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
                    Sluit je aan bij duizenden anderen die hun droombaan hebben gevonden via NEXA Skillmatching.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
                        Gratis Account Aanmaken
                    </a>
                    <a href="{{ route('jobs.index') }}" class="btn btn-outline btn-lg">
                        Bekijk Vacatures
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

