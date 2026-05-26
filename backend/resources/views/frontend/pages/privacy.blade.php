@extends('frontend.layouts.app')

@section('title', 'Privacybeleid - NEXA Skillmatching')
@section('description', 'Privacybeleid van NEXA Skillmatching.')

@section('content')
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900 section-padding relative overflow-hidden">
    <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
    <div class="container-custom relative z-10">
        <div class="w-full text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
                <span class="text-blue-200 dark:text-blue-300">Privacy</span>beleid
            </h1>
            <p class="text-xl text-blue-100 dark:text-blue-200 mb-8 w-full leading-relaxed max-w-3xl mx-auto">
                Hoe wij omgaan met je persoonsgegevens.
            </p>
        </div>
    </div>
</section>

<section class="section-padding bg-white dark:bg-gray-900">
    <div class="container-custom">
        <div class="!max-w-4xl mx-auto prose dark:prose-invert max-w-none">
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                NEXA Skillmatching hecht groot belang aan de bescherming van je persoonsgegevens. In dit privacybeleid leggen we uit welke gegevens we verzamelen, waarom we dat doen en hoe we ze beveiligen.
            </p>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-8 mb-4">Welke gegevens verzamelen we?</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                We verzamelen gegevens die je zelf aan ons verstrekt, zoals naam, e-mailadres, CV en profielinformatie. Daarnaast verzamelen we technische gegevens zoals je IP-adres en cookiegegevens voor het goed functioneren van het platform.
            </p>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-8 mb-4">Gebruik van gegevens</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                Je gegevens worden gebruikt om je te matchen met vacatures, om werkgevers je profiel te tonen (met je toestemming), en om het platform te verbeteren. We verkopen je gegevens nooit aan derden.
            </p>
            <h2 id="cookies" class="text-xl font-bold text-gray-900 dark:text-white mt-8 mb-4 scroll-mt-20">Cookies</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                We gebruiken cookies en vergelijkbare technieken om het platform goed te laten functioneren, om in te loggen en om gebruik te analyseren. Je kunt je cookievoorkeuren beheren in je accountinstellingen.
            </p>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-8 mb-4">Contact</h2>
            <p class="text-gray-600 dark:text-gray-300">
                Voor vragen over privacy kun je <a href="{{ route('contact') }}" class="text-blue-600 dark:text-blue-400 hover:underline">contact</a> met ons opnemen.
            </p>
        </div>
    </div>
</section>
@endsection
