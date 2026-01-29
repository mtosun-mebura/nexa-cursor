@extends('frontend.layouts.app')

@section('title', 'Registreren - NEXA Skillmatching')

@section('content')
<!-- Hero Section with Register Form -->
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900 section-padding relative overflow-hidden min-h-screen flex items-center">
    <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
    
    <div class="container-custom relative z-10">
        <div class="w-full max-w-md mx-auto">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="{{ asset('images/nexa-skillmatching-logo.png') }}" alt="NEXA Skillmatching" class="h-16 w-auto mx-auto mb-6">
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                    Start je carrière!
                </h1>
                <p class="text-lg text-blue-100 dark:text-blue-200">
                    Maak een gratis account aan en vind je droombaan
                </p>
            </div>

            <!-- Register Form -->
            <div class="card p-8 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm">
                <form class="space-y-6" action="{{ route('register.post') }}" method="POST">
                    @csrf
                    
                    <!-- Name Fields -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Voornaam
                            </label>
                            <input id="first_name" name="first_name" type="text" autocomplete="given-name" required 
                                   class="input w-full" 
                                   placeholder="Jan"
                                   value="{{ old('first_name') }}">
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Achternaam
                            </label>
                            <input id="last_name" name="last_name" type="text" autocomplete="family-name" required 
                                   class="input w-full" 
                                   placeholder="Jansen"
                                   value="{{ old('last_name') }}">
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            E-mailadres
                        </label>
                        <input id="email" name="email" type="email" autocomplete="email" required 
                               class="input w-full" 
                               placeholder="jan@email.com"
                               value="{{ old('email') }}">
                    </div>

                    <!-- Password Fields -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Wachtwoord
                        </label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required 
                               class="input w-full" 
                               placeholder="Minimaal 8 karakters">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Wachtwoord bevestigen
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required 
                               class="input w-full" 
                               placeholder="Herhaal je wachtwoord">
                    </div>

                    <!-- Terms & Privacy -->
                    <div class="flex items-start">
                        <input id="terms" name="terms" type="checkbox" required
                               class="h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300 dark:border-gray-600 rounded mt-1">
                        <label for="terms" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            Ik ga akkoord met de 
                            <a href="{{ route('terms') }}" class="font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300" target="_blank" rel="noopener">algemene voorwaarden</a>
                            en 
                            <a href="{{ route('privacy') }}" class="font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300" target="_blank" rel="noopener">privacybeleid</a>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit" class="btn btn-primary w-full btn-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            Gratis account aanmaken
                        </button>
                    </div>
                </form>

                <!-- Divider -->
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400">Of</span>
                        </div>
                    </div>
                </div>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Al een account?
                        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300">
                            Log hier in
                        </a>
                    </p>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="text-center mt-6">
                <a href="{{ route('home') }}" class="inline-flex items-center text-white hover:text-blue-200 dark:hover:text-blue-300 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Terug naar home
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Benefits Section -->
<section class="section-padding bg-white dark:bg-gray-900">
    <div class="container-custom">
        <div class="w-full">
            <div class="text-center mb-12">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    Wat krijg je met een gratis account?
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-300">
                    Alle tools die je nodig hebt voor een succesvolle carrière
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="card-hover p-6 text-center">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">AI-Powered Matching</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">
                        Onze AI vindt de perfecte vacatures die bij jouw vaardigheden passen
                    </p>
                </div>
                
                <div class="card-hover p-6 text-center">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Gratis & Altijd</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">
                        Volledig gratis platform zonder verborgen kosten of abonnementen
                    </p>
                </div>
                
                <div class="card-hover p-6 text-center">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Snelle Resultaten</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">
                        Begin direct met solliciteren en vind binnen dagen je nieuwe baan
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection