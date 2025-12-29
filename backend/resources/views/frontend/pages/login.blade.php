@extends('frontend.layouts.app')

@section('title', 'Inloggen - NEXA Skillmatching')

@section('content')
<!-- Hero Section with Login Form -->
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900 relative overflow-hidden min-h-screen flex items-center">
    <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
    
    <div class="w-full px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-md mx-auto">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="{{ asset('images/nexa-skillmatching-logo.png') }}" alt="NEXA Skillmatching" class="h-16 w-auto mx-auto mb-6">
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                    Welkom terug!
                </h1>
                <p class="text-lg text-blue-100 dark:text-blue-200">
                    Log in op je account om verder te gaan
                </p>
            </div>

            <!-- Login Form -->
            <div class="card p-8 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm">
                <form class="space-y-6" action="{{ route('login.post') }}" method="POST">
                    @csrf
                    
                    <!-- Error Messages -->
                    @error('email')
                        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                        {{ $message }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @enderror
                    
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            E-mailadres
                        </label>
                        <input id="email" name="email" type="email" autocomplete="email" required 
                               class="input w-full" 
                               placeholder="je@email.com"
                               value="{{ old('email') }}">
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Wachtwoord
                        </label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required 
                               class="input w-full" 
                               placeholder="Je wachtwoord">
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox" 
                                   class="h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300 dark:border-gray-600 rounded">
                            <label for="remember-me" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Onthoud mij
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="#" class="font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300">
                                Wachtwoord vergeten?
                            </a>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit" class="btn btn-primary w-full btn-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            Inloggen
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

                <!-- Register Link -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Nog geen account?
                        <a href="{{ route('register') }}" class="font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300">
                            Registreer je hier
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

<!-- Features Section -->
<section class="py-16 bg-white dark:bg-gray-900">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    Waarom inloggen?
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-300">
                    Toegang tot je persoonlijke dashboard en alle functies
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="card-hover p-6 text-center">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Persoonlijk Dashboard</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">
                        Overzicht van je sollicitaties, matches en carrière voortgang
                    </p>
                </div>
                
                <div class="card-hover p-6 text-center">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">AI Matches</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">
                        Ontvang gepersonaliseerde vacature suggesties op basis van je profiel
                    </p>
                </div>
                
                <div class="card-hover p-6 text-center">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Snelle Sollicitaties</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">
                        Solliciteer direct op interessante vacatures met één klik
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection