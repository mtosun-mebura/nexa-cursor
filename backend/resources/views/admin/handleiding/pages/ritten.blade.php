<p>
    Onder <strong>Ritten</strong> staan alle boekingen: van website-aanvraag tot afgeronde rit.
    U filtert op datum, status of voertuig en opent een rit voor adressen, prijs en chauffeur.
    Boekingen vanaf nexasuite.nl herkent u aan het gele label <strong>NEXA Suite</strong>.
</p>

<x-admin.handleiding-screenshot caption="Rittenlijst met status, klant en NEXA Suite-label." title="Nexa — Ritten">
    <x-admin.handleiding-mock-screen active="Ritten" title="Ritten">
        <x-slot:toolbar>
            <span class="rounded-md border border-border px-2 py-1 text-[10px]">Nieuwe rit</span>
        </x-slot:toolbar>
        <div class="rounded-lg border border-border overflow-hidden text-[11px]">
            <div class="grid grid-cols-4 bg-muted/40 px-2 py-1 font-medium">
                <span>Status</span><span>Ophalen</span><span>Klant</span><span>Prijs</span>
            </div>
            <div class="grid grid-cols-4 px-2 py-1 border-t border-border">
                <span class="text-primary">Gepland</span><span>02-09 09:15</span>
                <span>De Vries<br><span class="inline-block mt-0.5 rounded-full border border-amber-400 bg-amber-100 px-1.5 text-[9px] text-amber-800">NEXA Suite</span></span>
                <span>€ 42,50</span>
            </div>
            <div class="grid grid-cols-4 px-2 py-1 border-t border-border">
                <span>Onderweg</span><span>02-09 10:00</span><span>Jansen</span><span>€ 28,00</span>
            </div>
        </div>
    </x-admin.handleiding-mock-screen>
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>Ga naar <strong>Ritten</strong> in het menu.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>Filter op status (nieuw, gepland, onderweg, afgerond) of voertuig als u een specifieke rit zoekt.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>Open een rit voor ophaal- en afzetadres, prijsindicatie en (indien van toepassing) de chauffeur. Bij een NEXA Suite-rit staat de bron vermeld: algemene boeking, niet via uw eigen website.</div>
</div>

<div class="handleiding-tip">
    <strong class="text-foreground">NEXA Suite:</strong> deze ritten komen van nexasuite.nl (dichtstbijzijnde taxibedrijf).
    Behandel ze hetzelfde als eigen boekingen: toewijzen, rijden, afronden. De chauffeur ziet hetzelfde gele label in de app.
</div>
