<p>
    Onder <strong>Front-end</strong> beheert u de hoofdwebsite van NEXA Suite én de websites van tenants:
    thema’s, pagina’s, de visuele builder en AI-generatie. Dit menu is alleen voor super-admins.
    Kies eerst een tenant in de zijbalk om een klantwebsite te bewerken; zonder tenant werkt u aan de centrale site.
</p>

<h2 id="paginas">1. Pagina’s en visuele builder</h2>
<p>
    <strong>Pagina’s</strong> toont de websitepagina’s van de actieve context. Per pagina zet u de pagina live,
    opent u het voorbeeld of bewerkt u de inhoud in de visuele builder (blokken, secties, merkkleuren, boekingsmodule).
</p>

<x-admin.handleiding-screenshot caption="Websitepagina’s: live-schakelaar, voorbeeld en bewerken." title="Nexa — Pagina’s">
    <x-admin.handleiding-mock-screen active="Website" title="Website pagina’s" companyName="NEXA Suite">
        <div class="flex justify-end gap-1 text-[10px] mb-2">
            <span class="rounded-md border border-border px-2 py-0.5">Pagina’s actief</span>
            <span class="rounded-md border border-border px-2 py-0.5">Voorbeeld</span>
        </div>
        <div class="space-y-2 text-[11px]">
            <div class="rounded-lg border border-border px-2 py-2 flex justify-between"><span>Home</span><span class="text-primary">Bewerken</span></div>
            <div class="rounded-lg border border-border px-2 py-2 flex justify-between"><span>Boeken</span><span class="text-primary">Bewerken</span></div>
            <div class="rounded-lg border border-border px-2 py-2 flex justify-between"><span>Contact</span><span class="text-primary">Bewerken</span></div>
        </div>
    </x-admin.handleiding-mock-screen>
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>Selecteer een tenant (of laat “Alle tenants” voor de centrale site) en open <strong>Front-end → Pagina’s</strong>.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>Zet <strong>Pagina’s actief</strong> aan zodat de site publiek zichtbaar is. Gebruik <strong>Website voorbeeld</strong> om te controleren.</div>
</div>

<h2 id="ai">2. Genereer website AI</h2>
<p>
    <strong>Genereer website AI</strong> zet in één keer een tenant-website op: thema, kleuren, geanimeerde componenten
    en de boekingsmodule. Pagina’s komen als concept (niet gepubliceerd). U kunt starten vanaf een lege site of een bestaande URL als bron.
</p>

<x-admin.handleiding-screenshot caption="AI-generator: tenant, bron-URL, thema en kleuren." title="Nexa — Genereer website AI">
    <x-admin.handleiding-mock-screen active="Website" title="Genereer website AI" companyName="NEXA Suite">
        <div class="space-y-2 text-[11px]">
            <div class="rounded-md border border-border px-2 py-1.5 text-muted-foreground">Kies een tenant</div>
            <div class="rounded-md border border-border px-2 py-1.5">Nieuwe website · of URL als bron</div>
            <div class="flex gap-2">
                <div class="size-6 rounded-md bg-primary"></div>
                <div class="size-6 rounded-md bg-zinc-800"></div>
                <span class="self-center text-muted-foreground">Thema + merkkleuren</span>
            </div>
            <div class="h-8 rounded-md bg-primary/90 text-primary-foreground flex items-center justify-center">Genereren</div>
        </div>
    </x-admin.handleiding-mock-screen>
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>Kies het bedrijf, eventueel de oude website-URL, een thema en kleuren. Start <strong>Genereren</strong>.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">4</span>
    <div>Controleer de conceptpagina’s in de builder en publiceer ze pas als de inhoud klopt.</div>
</div>

<h2 id="themas">3. Thema’s</h2>
<p>
    Onder <strong>Thema’s</strong> activeert, publiceert of bekijkt u frontend-thema’s.
    Een actief thema bepaalt de look van tenant-sites die dat thema gebruiken.
    <strong>Componenten</strong> is de bibliotheek van herbruikbare blokken in de builder.
</p>

<div class="handleiding-tip">
    <strong class="text-foreground">Welkom:</strong> de centrale marketingpagina van nexasuite.nl bewerkt u via <strong>Front-end → Welkom</strong>.
    Dat is geen tenant-site.
</div>
