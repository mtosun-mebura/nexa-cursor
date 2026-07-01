<p>
    Welkom bij Nexa. Deze eerste handleiding helpt u snel op weg: u leert waar u terechtkunt na het inloggen,
    hoe u tussen bedrijven (tenants) wisselt en hoe het linkermenu is opgebouwd. Dat is de basis voor alle
    verdere taken — van websitebeheer tot ritten en e-mailtemplates.
</p>

<h2 id="inloggen">1. Inloggen en startscherm</h2>
<p>
    Na het inloggen op <strong>/admin</strong> komt u op het <strong>Dashboard</strong>. Dat is uw startpunt:
    hier ziet u in één oogopslag belangrijke cijfers en recente activiteit, afhankelijk van de actieve modules
    van uw organisatie (bijvoorbeeld taxi of skillmatching).
</p>

<x-admin.handleiding-screenshot caption="Het dashboard toont kernstatistieken en een welkomstblok." title="Nexa — Dashboard">
    <div class="rounded-lg bg-gradient-to-r from-slate-50 via-slate-100 to-slate-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 p-4 ring-1 ring-border">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <div class="text-sm font-semibold text-foreground">Nexa overzicht</div>
                <div class="text-xs text-muted-foreground">Direct inzicht in gebruikers, modules en inkomsten.</div>
            </div>
            <span class="kt-badge kt-badge-light text-[10px]">{{ now()->translatedFormat('d M Y') }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            @foreach([['Gebruikers', 'ki-people', '128'], ['Ritten', 'ki-delivery', '1.240'], ['Omzet', 'ki-dollar', '€ 42k'], ['Bedrijven', 'ki-abstract-26', '12']] as [$label, $icon, $value])
                <div class="rounded-lg bg-background/70 dark:bg-white/5 ring-1 ring-border p-2.5">
                    <div class="text-[10px] text-muted-foreground">{{ $label }}</div>
                    <div class="text-sm font-semibold text-foreground">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>
        <strong class="text-foreground">Open het menu Dashboard</strong> in de linkerzijbalk om altijd terug te keren naar dit overzicht.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>
        <strong class="text-foreground">Bekijk de statistiekkaarten</strong> bovenaan: gebruikers, module-specifieke cijfers (ritten, vacatures, …) en financieel overzicht.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>
        <strong class="text-foreground">Scroll verder</strong> voor grafieken, recente activiteit en snelle links naar veelgebruikte onderdelen.
    </div>
</div>

<h2 id="tenant">2. Bedrijf / tenant kiezen</h2>
<p>
    Bent u <strong>super-admin</strong>, dan ziet u bovenaan het menu een <strong>tenant-kiezer</strong>.
    Nexa is multi-tenant: meerdere bedrijven delen het platform, maar data blijft gescheiden.
</p>

<x-admin.handleiding-screenshot caption="Kies één bedrijf om alleen die data te zien, of 'Alle Tenants' voor het totaaloverzicht." title="Nexa — Tenant-kiezer">
    <div class="flex gap-3">
        <div class="handleiding-mock-sidebar rounded-lg overflow-hidden ring-1 ring-border">
            <div class="px-2 py-2 border-b border-border">
                <div class="flex items-center justify-between gap-1 rounded-md border border-border bg-muted/30 px-2 py-1.5 text-[10px]">
                    <span class="flex items-center gap-1 truncate"><i class="ki-filled ki-abstract-26 text-[10px]"></i> Taxi Demo</span>
                    <i class="ki-filled ki-down text-[8px] text-muted-foreground"></i>
                </div>
            </div>
            <div class="handleiding-mock-menu-item is-active"><i class="ki-filled ki-element-11"></i> Dashboard</div>
            <div class="handleiding-mock-menu-item"><i class="ki-filled ki-book-open"></i> Handleiding</div>
            <div class="handleiding-mock-menu-item"><i class="ki-filled ki-abstract-26"></i> Bedrijven</div>
        </div>
        <div class="flex-1 min-w-0 rounded-lg bg-muted/20 p-3 ring-1 ring-border text-xs text-muted-foreground">
            <p class="mb-2 text-foreground font-medium text-sm">Wat verandert er?</p>
            <ul class="space-y-1.5 m-0 p-0 list-none">
                <li class="flex gap-2"><i class="ki-filled ki-check text-primary text-xs mt-0.5"></i> Lijsten tonen alleen records van het gekozen bedrijf</li>
                <li class="flex gap-2"><i class="ki-filled ki-check text-primary text-xs mt-0.5"></i> Dashboardstatistieken worden gefilterd</li>
                <li class="flex gap-2"><i class="ki-filled ki-check text-primary text-xs mt-0.5"></i> Website- en e-mailinstellingen gelden per tenant</li>
            </ul>
        </div>
    </div>
</x-admin.handleiding-screenshot>

<div class="handleiding-tip">
    <strong class="text-foreground">Tip:</strong> werkt u aan één klant? Selecteer dat bedrijf vóór u gegevens bewerkt — zo voorkomt u dat u per ongeluk in de verkeerde omgeving werkt.
</div>

<h2 id="navigatie">3. Navigatie in het menu</h2>
<p>
    Het linkermenu is ingedeeld in logische groepen. Onder <strong>Beheer</strong> vindt u bedrijven, gebruikers en
    instellingen. Actieve modules (zoals <strong>Nexa Taxi</strong>) krijgen eigen menu-items met subpagina's.
</p>

<x-admin.handleiding-screenshot caption="Voorbeeld van de zijbalkstructuur — uw menu kan afwijken per rol en geactiveerde modules." title="Nexa — Zijbalk">
    <div class="grid sm:grid-cols-2 gap-3 text-[11px]">
        <div class="rounded-lg ring-1 ring-border p-3">
            <div class="text-[10px] uppercase tracking-wide text-muted-foreground mb-2">Algemeen</div>
            <ul class="space-y-1 m-0 p-0 list-none text-secondary-foreground">
                <li class="flex items-center gap-2 font-medium text-primary"><i class="ki-filled ki-element-11"></i> Dashboard</li>
                <li class="flex items-center gap-2"><i class="ki-filled ki-book-open"></i> Handleiding</li>
            </ul>
            <div class="text-[10px] uppercase tracking-wide text-muted-foreground mt-3 mb-2">Beheer</div>
            <ul class="space-y-1 m-0 p-0 list-none text-secondary-foreground">
                <li class="flex items-center gap-2"><i class="ki-filled ki-abstract-26"></i> Bedrijven</li>
                <li class="flex items-center gap-2"><i class="ki-filled ki-people"></i> Gebruikers</li>
                <li class="flex items-center gap-2"><i class="ki-filled ki-setting-2"></i> Instellingen</li>
            </ul>
        </div>
        <div class="rounded-lg ring-1 ring-border p-3">
            <div class="text-[10px] uppercase tracking-wide text-muted-foreground mb-2">Modules (voorbeeld)</div>
            <ul class="space-y-1 m-0 p-0 list-none text-secondary-foreground">
                <li class="flex items-center gap-2"><i class="ki-filled ki-delivery"></i> Ritten &amp; boekingen</li>
                <li class="flex items-center gap-2"><i class="ki-filled ki-sms"></i> E-mail Templates</li>
                <li class="flex items-center gap-2"><i class="ki-filled ki-screen"></i> Website pagina's</li>
                <li class="flex items-center gap-2"><i class="ki-filled ki-message-text-2"></i> AI-chatbot</li>
            </ul>
        </div>
    </div>
</x-admin.handleiding-screenshot>

<h3>Wat ziet u wel of niet?</h3>
<ul>
    <li><strong>Rollen en rechten</strong> — niet elke gebruiker ziet alle menu-items. Een medewerker ziet alleen wat nodig is voor zijn of haar taken.</li>
    <li><strong>Modules</strong> — menu-items van een module verschijnen pas als die module voor uw bedrijf is geactiveerd.</li>
    <li><strong>Inklapbaar menu</strong> — via het pijltje bovenaan de zijbalk maakt u het menu smaller voor meer werkruimte.</li>
</ul>

<h2 id="hulp">4. Hulp en vervolgstappen</h2>
<p>Naast deze handleiding kunt u in Nexa op de volgende manieren hulp krijgen:</p>
<ul>
    <li><strong>AI-assistent</strong> — via het chat-icoon in de header kunt u vragen stellen over het gebruik van het platform (indien ingeschakeld).</li>
    <li><strong>Handleiding</strong> — onder Dashboard in het menu; nieuwe onderwerpen worden hier stap voor stap toegevoegd.</li>
    <li><strong>Instellingen</strong> — algemene configuratie zoals logo, e-mail en integraties vindt u onder Beheer → Instellingen.</li>
</ul>

<div class="handleiding-tip">
    <strong class="text-foreground">Volgende handleidingen (binnenkort):</strong> website &amp; contactformulier, e-mailtemplates, taxi-boekingen en gebruikersbeheer.
    We breiden deze sectie uit zodat elke belangrijke functie met schermvoorbeelden wordt uitgelegd.
</div>

<p class="mb-0">
    Klaar om verder te gaan? Ga terug naar het <a href="{{ route('admin.dashboard') }}" class="text-primary underline">dashboard</a>
    of bekijk het <a href="{{ route('admin.handleiding.index') }}" class="text-primary underline">handleidingoverzicht</a> zodra er nieuwe onderwerpen beschikbaar zijn.
</p>
