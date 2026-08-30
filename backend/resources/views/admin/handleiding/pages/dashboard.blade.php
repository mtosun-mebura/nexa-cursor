<p>
    Het dashboard is het startpunt na het inloggen. U ziet hier kerncijfers van uw bedrijf,
    recente activiteit en knoppen naar veelgebruikte schermen.
</p>

<x-admin.handleiding-screenshot caption="Dashboard met statistiekkaarten en recente ritten." title="Nexa — Dashboard">
    <x-admin.handleiding-mock-screen active="Dashboard" title="Dashboard">
        <div class="grid grid-cols-3 gap-2 mb-3">
            @foreach([['Vandaag', '6 ritten'], ['Onderweg', '2'], ['Omzet', '€ 412']] as [$label, $value])
                <div class="rounded-lg border border-border p-2">
                    <div class="text-[10px] text-muted-foreground">{{ $label }}</div>
                    <div class="text-xs font-semibold">{{ $value }}</div>
                </div>
            @endforeach
        </div>
        <div class="rounded-lg border border-border overflow-hidden text-[11px]">
            <div class="bg-muted/40 px-2 py-1 font-medium">Recente ritten</div>
            <div class="px-2 py-1 border-t border-border">Amsterdam CS → Schiphol · 14:20</div>
            <div class="px-2 py-1 border-t border-border">Utrecht → De Uithof · 15:05</div>
        </div>
    </x-admin.handleiding-mock-screen>
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>Open <strong>Dashboard</strong> in het linkermenu om altijd terug te keren.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>Gebruik de kaarten bovenaan om te zien hoeveel ritten openstaan of onderweg zijn.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>Klik op een recente rit om de details te openen.</div>
</div>
