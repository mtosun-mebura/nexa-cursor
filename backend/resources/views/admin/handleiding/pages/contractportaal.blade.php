<p>
    Het contractportaal is de app voor ouders of opdrachtgevers: ritten van vandaag en de week,
    afmelden van–tot, en de ophaalroute op de kaart. Alleen in Business.
</p>

<x-admin.handleiding-screenshot caption="Contractportaal: weekoverzicht, afmelden en navigatie." title="Nexa — Contractportaal">
    <x-admin.handleiding-mock-screen active="Ritten" title="Contractportaal">
        <div class="rounded-lg border border-border p-3 text-[11px]">
            <div class="font-medium mb-2">Week 35</div>
            <div class="grid grid-cols-5 gap-1 mb-3">
                @foreach(['Ma', 'Di', 'Wo', 'Do', 'Vr'] as $day)
                    <div class="rounded-md bg-primary/10 text-center py-2">{{ $day }}</div>
                @endforeach
            </div>
            <div class="h-8 rounded-md border border-border flex items-center justify-center mb-2">Afmelden van–tot</div>
            <div class="h-8 rounded-md bg-primary/10 flex items-center justify-center">Navigatie · 3 ophaalstops</div>
        </div>
    </x-admin.handleiding-mock-screen>
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>Koppel een ouder of opdrachtgever aan de contractklant.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>Zij loggen in op het portaal om ritten in te zien of af te melden.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>Onder <strong>Navigatie</strong> zien ze de ophaalstops van vandaag op de kaart. De naam van de reiziger staat boven Ophalen; Afzetten heeft geen naam erachter. <strong>Start navigatie</strong> opent Google Maps met alle tussenstops en de bestemming, in de juiste volgorde. Wie is opgehaald of afgemeld valt van de route af.</div>
</div>
