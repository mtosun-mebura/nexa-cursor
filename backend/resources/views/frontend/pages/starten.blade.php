@extends('frontend.layouts.website')

@section('title', 'Starten met NEXA Suite')
@section('description', 'Video: van welkomstmail naar eerste login, handleiding, dashboard, gebruikers en de chauffeur- en contract-app op je telefoon.')

@section('content')
<article class="website-section-inner py-10 md:py-16">
    <div class="w-full max-w-5xl mx-auto">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Voor nieuwe beheerders</p>
        <h1 class="text-3xl md:text-4xl font-semibold text-gray-900 dark:text-white mb-4" style="font-family: var(--theme-font-heading);">Zo start je met NEXA</h1>
        <p class="text-base text-gray-600 dark:text-gray-300 mb-8 leading-relaxed max-w-3xl">
            In deze video zie je de echte schermen: de link in je welkomstmail, de eerste keer een code aanvragen, een wachtwoord instellen, de handleiding, het dashboard, bedrijfsgegevens, nieuwe gebruikers, en daarna de chauffeur-app en de contract-app op je telefoon.
        </p>

        <div class="rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-black shadow-lg mb-8">
            <video
                id="nexa-starten-video"
                class="w-full aspect-video bg-black"
                controls
                controlslist="nodownload"
                playsinline
                preload="auto"
                poster="{{ asset('videos/nexa-starten-poster.jpg') }}?v={{ @filemtime(public_path('videos/nexa-starten-poster.jpg')) ?: time() }}"
            >
                <source src="{{ $videoUrl }}" type="video/mp4">
                Je browser ondersteunt geen video. Open de stappen hieronder, of ga naar de handleiding.
            </video>
        </div>

        <div class="mb-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-1" style="font-family: var(--theme-font-heading);">Direct naar het juiste onderwerp in de video gaan?</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-0">Maak dan je keuze hieronder.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 mb-10">
            @foreach($chapters as $chapter)
                <button
                    type="button"
                    class="text-left rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3 hover:border-blue-500 transition"
                    data-video-seek="{{ $chapter['time'] }}"
                >
                    <span class="block text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">{{ gmdate('i:s', $chapter['time']) }}</span>
                    <span class="block font-semibold text-gray-900 dark:text-white">{{ $chapter['title'] }}</span>
                    <span class="block text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $chapter['summary'] }}</span>
                </button>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-3 mb-12">
            <a href="{{ $adminLoginUrl }}" class="inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-semibold text-white" style="background-color: var(--theme-primary, #2563eb);">
                Open de admin
            </a>
            <a href="{{ $handleidingUrl }}" class="inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-semibold border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white">
                Open de handleiding
            </a>
        </div>

        <div class="space-y-8 text-[0.9375rem] leading-relaxed text-gray-700 dark:text-gray-300">
            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">1. Van de mail naar de admin</h2>
                <p>In de welkomstmail staat je e-mailadres, geen wachtwoord. Klik op <strong>Open de admin</strong>. Je komt op <strong>nexasuite.nl/admin</strong>.</p>
            </section>
            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">2. Eerste keer inloggen</h2>
                <ol class="list-decimal pl-5 space-y-1">
                    <li>Kies <strong>Eerste keer inloggen?</strong></li>
                    <li>Vul hetzelfde e-mailadres in als in de mail.</li>
                    <li>Kies <strong>Inlogcode aanvragen</strong>. De code is 15 minuten geldig.</li>
                    <li>Vul de 6-cijferige code in, kies een wachtwoord en bevestig het.</li>
                    <li>Kies <strong>Wachtwoord instellen en inloggen</strong>.</li>
                </ol>
            </section>
            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">3. Handleiding en menu</h2>
                <p>Na de eerste login land je in de handleiding. Zoek bovenin op een trefwoord, of open een onderwerp en loop de stappen door. Links in het menu vind je daarna Dashboard, Ritten, Gebruikers, Contractvervoer en de rest van jouw pakket.</p>
            </section>
            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">4. Bedrijf en gebruikers</h2>
                <p>Onder <strong>Bedrijf</strong> bekijk je de bedrijfsgegevens. Onder <strong>Gebruikers</strong> maak je collega’s, chauffeurs of planners aan. Zij krijgen zelf een welkomstmail en activeren hun account met een code. Vul bij hen geen wachtwoord in.</p>
            </section>
            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">5. Chauffeur-app (mobiel)</h2>
                <p>Open de app via de knop <strong>Chauffeur-app</strong> in de admin, of via de link in de mail van de chauffeur: <code>/taxi/chauffeur</code>. Na het inloggen sta je standaard <strong>online</strong> en klaar voor ritten; met het schuifje kun je jezelf offline zetten. Onder <strong>Aanvragen</strong> komen nieuwe ritten binnen, onder <strong>Ritten</strong> staan jouw ritten, onder <strong>Navigatie</strong> start je de route in Google Maps.</p>
            </section>
            <section>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">6. Contract-app (mobiel)</h2>
                <p>Ouders en opdrachtgevers openen de app via <strong>Contract-app</strong> of via <code>/taxi/contract</code>. <strong>Vandaag</strong> toont de reizigers van deze dag. <strong>Afmelden</strong> als iemand niet meegaat. <strong>Navigatie</strong> toont de ophaalstops op de kaart.</p>
            </section>
        </div>
    </div>
</article>
<script>
(function () {
    var video = document.getElementById('nexa-starten-video');
    if (!video) return;
    video.addEventListener('loadedmetadata', function () {
        if (!isFinite(video.duration) || video.duration === 0) {
            video.load();
        }
    });
    document.querySelectorAll('[data-video-seek]').forEach(function (button) {
        button.addEventListener('click', function () {
            var seconds = Number(button.getAttribute('data-video-seek') || '0');
            if (!isFinite(video.duration)) {
                video.load();
            }
            var seek = function () {
                video.currentTime = seconds;
                video.play().catch(function () {});
            };
            if (video.readyState >= 1) {
                seek();
            } else {
                video.addEventListener('loadedmetadata', seek, { once: true });
            }
        });
    });
})();
</script>
@endsection
