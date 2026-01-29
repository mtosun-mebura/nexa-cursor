@extends('frontend.layouts.app')

@section('title', 'Help & FAQ - NEXA Skillmatching')
@section('description', 'Veelgestelde vragen en hulp bij het gebruik van het NEXA Skillmatching platform.')

@section('content')
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900 section-padding relative overflow-hidden">
    <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
    <div class="container-custom relative z-10">
        <div class="w-full text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
                Help & <span class="text-blue-200 dark:text-blue-300">FAQ</span>
            </h1>
            <p class="text-xl text-blue-100 dark:text-blue-200 mb-8 w-full leading-relaxed max-w-3xl mx-auto">
                Veelgestelde vragen en hulp bij het gebruik van ons platform.
            </p>
        </div>
    </div>
</section>

<section class="section-padding bg-white dark:bg-gray-900">
    <div class="container-custom">
        <div class="!max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Veelgestelde vragen</h2>
            <div class="space-y-6 text-gray-600 dark:text-gray-300">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Hoe maak ik een account aan?</h3>
                    <p>Klik op "Registreren" in het menu en vul je gegevens in. Na registratie kun je direct je profiel invullen en vacatures bekijken.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Hoe werkt de AI-matching?</h3>
                    <p>Ons platform analyseert je vaardigheden en ervaring en matcht deze automatisch met vacatures die bij jou passen. Vul je profiel zo volledig mogelijk in voor de beste resultaten.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Hoe solliciteer ik op een vacature?</h3>
                    <p>Ga naar de vacature en klik op "Solliciteren". Controleer of je CV en profiel up-to-date zijn voordat je solliciteert.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Nog meer vragen?</h3>
                    <p>Neem gerust <a href="{{ route('contact') }}" class="text-blue-600 dark:text-blue-400 hover:underline">contact</a> met ons op.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
