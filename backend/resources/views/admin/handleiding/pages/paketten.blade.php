<p>
    Onder <strong>Paketten</strong> beheert u de maandpakketten die op nexasuite.nl én in de NEXA-facturatie zichtbaar zijn:
    prijs, kenmerken, welke functies de software afdwingt, en aanvullende modules (GPS, extra contractklanten, Vloot).
</p>

<x-admin.handleiding-screenshot caption="Paketten: Start, Pro en Business met prijs en functies." title="Nexa — Paketten">
    <x-admin.handleiding-mock-screen active="Abonnementen" title="Paketten" companyName="NEXA Suite">
        <div class="grid grid-cols-3 gap-2 text-[11px]">
            @foreach([['Start', '€ 49'], ['Pro', '€ 99'], ['Business', '€ 149']] as [$name, $price])
                <div class="rounded-lg border border-border p-2">
                    <div class="font-semibold">{{ $name }}</div>
                    <div class="text-muted-foreground">{{ $price }} / mnd</div>
                </div>
            @endforeach
        </div>
    </x-admin.handleiding-mock-screen>
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>Open <strong>Paketten</strong> in het menu. Pas prijs, badge, CTA en de lijst met functies per pakket aan.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>Zet per functie aan of uit wat de software mag afdwingen (dispatch, chauffeur-app, contractvervoer, Mollie, enzovoort).</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>Vul de aanvullende modules in (naam, prijs, beschrijving). Die verschijnen bij het bedrijf, in Tenant-abonnementen en als aparte regels op de maandfactuur.</div>
</div>

<div class="handleiding-tip">
    <strong class="text-foreground">Website:</strong> de publieke prijzenpagina opent u via de knop naar <strong>/prijzen</strong>.
    Wijzigingen hier sturen zowel de marketingpagina als wat een tenant in de software mag.
</div>
