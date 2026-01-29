@extends('frontend.layouts.app')

@section('title', 'Algemene Voorwaarden - NEXA Skillmatching')
@section('description', 'Algemene voorwaarden van NEXA Skillmatching.')

@section('content')
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900 section-padding relative overflow-hidden">
    <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
    <div class="container-custom relative z-10">
        <div class="w-full text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
                Algemene <span class="text-blue-200 dark:text-blue-300">Voorwaarden</span>
            </h1>
            <p class="text-xl text-blue-100 dark:text-blue-200 mb-8 w-full leading-relaxed max-w-3xl mx-auto">
                Gebruiksvoorwaarden van het NEXA Skillmatching platform.
            </p>
        </div>
    </div>
</section>

<section class="section-padding bg-white dark:bg-gray-900">
    <div class="container-custom">
        <div class="!max-w-4xl mx-auto prose dark:prose-invert max-w-none">
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                Door gebruik te maken van NEXA Skillmatching ga je akkoord met onderstaande algemene voorwaarden.
            </p>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-8 mb-4">Gebruik van het platform</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                Je gebruikt het platform voor het zoeken naar vacatures en het aanbieden van je profiel aan werkgevers. Je zorgt ervoor dat de door jou verstrekte gegevens correct en actueel zijn.
            </p>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-8 mb-4">Account</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                Je bent verantwoordelijk voor het geheim houden van je inloggegevens. Meld het ons direct als je vermoedt dat je account is misbruikt.
            </p>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-8 mb-4">Contact</h2>
            <p class="text-gray-600 dark:text-gray-300">
                Voor vragen over deze voorwaarden kun je <a href="{{ route('contact') }}" class="text-blue-600 dark:text-blue-400 hover:underline">contact</a> met ons opnemen.
            </p>
        </div>
    </div>
</section>
@endsection
