<p>
    Boekingen via <strong>nexasuite.nl/boek</strong> (en de boekingsmodule op <strong>/taxi</strong>) gaan
    naar het dichtstbijzijnde taxibedrijf dat NEXA Suite-ritten accepteert.
    Onder <strong>Betalingen → NEXA Suite ritten</strong> ziet u die ritten per tenant en factureert u de provisie.
</p>

<x-admin.handleiding-screenshot caption="Overzicht: gereden ritten per tenant, omzet en fee." title="Nexa — NEXA Suite ritten">
    <x-admin.handleiding-mock-screen active="Facturen" title="NEXA Suite ritten" companyName="NEXA Suite">
        <div class="flex gap-2 text-[10px] mb-2">
            <span class="text-primary border-b-2 border-primary pb-0.5">Overzicht</span>
            <span class="text-muted-foreground">Ritten</span>
            <span class="text-muted-foreground">Facturen</span>
            <span class="text-muted-foreground">Instellingen</span>
        </div>
        <div class="rounded-lg border border-border overflow-hidden text-[11px]">
            <div class="grid grid-cols-4 bg-muted/40 px-2 py-1 font-medium"><span>Tenant</span><span>Ritten</span><span>Omzet</span><span>Fee</span></div>
            <div class="grid grid-cols-4 px-2 py-1 border-t border-border"><span>Taxi Noord</span><span>12</span><span>€ 1.240</span><span>€ 124</span></div>
            <div class="grid grid-cols-4 px-2 py-1 border-t border-border"><span>Stadstaxi</span><span>8</span><span>€ 640</span><span>€ 64</span></div>
        </div>
    </x-admin.handleiding-mock-screen>
</x-admin.handleiding-screenshot>

<h2 id="tabs">De vier tabbladen</h2>
<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div><strong>Overzicht</strong> — gereden ritten per tenant in een maand, ritomzet, fee en of er al een factuur is. U kunt per tenant een factuur maken, of de facturatie / aanmaningen nu draaien.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div><strong>Ritten</strong> — alle centrale boekingen (alle statussen), te filteren op maand, tenant en status.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div><strong>Facturen</strong> — maandfacturen met status (concept, verzonden, herinnerd, betaald). U maakt facturen voor de gekozen maand en kunt ze direct mailen.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">4</span>
    <div><strong>Instellingen</strong> — fee in hele procenten, BTW in hele procenten, automatische facturatie (dag/tijd, zoals SaaS-facturatie), betaaltermijn en aanmaningsintervallen.</div>
</div>

<div class="handleiding-tip">
    <strong class="text-foreground">Fee:</strong> bij 10% over €100 ritomzet factureert u €10 excl. BTW.
    Alleen <strong>voltooide</strong> NEXA Suite-ritten van de vorige maand gaan mee.
    Tenants zien dezelfde ritten in hun eigen rittenlijst en chauffeur-app, met het gele label <strong>NEXA Suite</strong>.
</div>
