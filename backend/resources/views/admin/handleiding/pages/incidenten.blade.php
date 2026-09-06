<p>
    Onder <strong>Incidenten</strong> meldt u een storing, foutmelding of onverwacht gedrag.
    Voeg een korte titel, toelichting, het pagina-adres en desgewenst schermafbeeldingen toe.
    NEXA pakt de melding op en houdt u op de hoogte.
</p>

<x-admin.handleiding-screenshot caption="Incident melden: titel, toelichting en schermafbeelding." title="Nexa — Incidenten">
    <x-admin.handleiding-mock-screen active="Dashboard" title="Incident melden">
        <div class="space-y-2 text-[11px]">
            <div class="h-8 rounded-md border border-border bg-muted/20 px-2 flex items-center text-muted-foreground">Korte titel</div>
            <div class="h-14 rounded-md border border-border bg-muted/20 px-2 py-1 text-muted-foreground">Wat ging er mis?</div>
            <div class="h-8 rounded-md border border-dashed border-border flex items-center justify-center text-muted-foreground">Schermafbeelding toevoegen</div>
            <div class="h-8 rounded-md bg-primary/90 text-primary-foreground flex items-center justify-center">Versturen</div>
        </div>
    </x-admin.handleiding-mock-screen>
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>Open <strong>Incidenten</strong> in het menu.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>Beschrijf wat u deed en wat er misging. Het huidige pagina-adres wordt meegestuurd.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>Voeg een schermafbeelding toe als dat helpt, en verstuur de melding. U ziet daarna de status (open, in behandeling, opgelost).</div>
</div>

@if(auth()->user()?->isSuperAdmin())
<h2 id="incidenten-super-admin">Voor super-admins</h2>
<p>
    U ziet alle meldingen van alle tenants, plus meldingen zonder bedrijf.
    U wijzigt de status, voegt interne opmerkingen toe, archiveert afgehandelde tickets
    en kunt zelf een incident indienen (desgewenst gekoppeld aan een tenant).
</p>

<x-admin.handleiding-screenshot caption="Super-admin: alle incidenten, status en opmerkingen." title="Nexa — Incidenten (platform)">
    <x-admin.handleiding-mock-screen active="Dashboard" title="Incidenten" companyName="NEXA Suite">
        <div class="rounded-lg border border-border overflow-hidden text-[11px]">
            <div class="grid grid-cols-4 bg-muted/40 px-2 py-1 font-medium"><span>Ref</span><span>Tenant</span><span>Titel</span><span>Status</span></div>
            <div class="grid grid-cols-4 px-2 py-1 border-t border-border"><span>INC-2026-0012</span><span>Taxi Noord</span><span>GPS-kaart wit</span><span class="text-primary">Open</span></div>
            <div class="grid grid-cols-4 px-2 py-1 border-t border-border"><span>INC-2026-0011</span><span>—</span><span>Login traag</span><span>In behandeling</span></div>
        </div>
    </x-admin.handleiding-mock-screen>
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">4</span>
    <div>Open een ticket, zet de status op <strong>In behandeling</strong> of <strong>Opgelost</strong> en laat desgewenst een toelichting achter voor de klant.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">5</span>
    <div>Interne opmerkingen ziet alleen het platformteam. Archiveer afgehandelde tickets zodat de lijst overzichtelijk blijft.</div>
</div>
@endif
