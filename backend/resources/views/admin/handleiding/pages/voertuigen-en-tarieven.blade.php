<p>
    Stel uw wagenpark en tarieven in voordat klanten online boeken. Voertuigen bepalen wat er
    in de boekingsmodule te kiezen is. Tarieven sturen de prijsindicatie.
</p>

<x-admin.handleiding-screenshot caption="Voertuigenlijst met type en kenteken." title="Nexa — Voertuigen">
    <x-admin.handleiding-mock-screen active="Voertuigen" title="Voertuigen">
        <div class="rounded-lg border border-border overflow-hidden text-[11px]">
            <div class="grid grid-cols-3 bg-muted/40 px-2 py-1 font-medium">
                <span>Naam</span><span>Type</span><span>Kenteken</span>
            </div>
            <div class="grid grid-cols-3 px-2 py-1 border-t border-border"><span>Comfort 1</span><span>Personenauto</span><span>X-123-YZ</span></div>
            <div class="grid grid-cols-3 px-2 py-1 border-t border-border"><span>Rolstoelbus</span><span>Bus</span><span>B-456-CD</span></div>
        </div>
    </x-admin.handleiding-mock-screen>
</x-admin.handleiding-screenshot>

<x-admin.handleiding-screenshot caption="Tarieven: vaste ritprijs of berekening op afstand." title="Nexa — Tarieven">
    <x-admin.handleiding-mock-screen active="Tarieven" title="Tarieven">
        <div class="space-y-2 text-[11px]">
            <div class="rounded-lg border border-border p-2 flex justify-between"><span>Instaptarief</span><span class="font-medium">€ 3,20</span></div>
            <div class="rounded-lg border border-border p-2 flex justify-between"><span>Per kilometer</span><span class="font-medium">€ 2,45</span></div>
        </div>
    </x-admin.handleiding-mock-screen>
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>Voeg minstens één voertuig toe onder <strong>Voertuigen</strong>.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>Controleer de tarieven zodat de prijsindicatie op de website klopt.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>
        Stel bij voorkeur een eigen <strong>mailserver</strong> in. Zonder eigen SMTP gaan klantmails via NEXA Suite
        (dat zien klanten als afzender).
    </div>
</div>
<p class="text-sm text-muted-foreground mt-4 mb-0">
    Na het inloggen krijg je een stappenplan-popup als voertuigen of tarieven nog ontbreken.
    Dat stappenplan blijft ook beschikbaar via je <strong>notificaties</strong>.
    Zonder voertuig kun je de boekingsmodule niet op de website plaatsen.
</p>
