<p>
    Welkom bij NEXA. Deze handleiding hoort bij uw abonnement: u ziet hier alleen de onderdelen
    die voor uw pakket beschikbaar zijn. Na de eerste login wijzigt u het tijdelijke wachtwoord
    en komt u hier terecht.
</p>

<h2 id="inloggen">1. Inloggen</h2>
<p>
    Ga naar <strong>nexasuite.nl/admin</strong>. Gebruik uw <strong>e-mailadres als gebruikersnaam</strong>
    en het tijdelijke wachtwoord uit de welkomstmail.
</p>

<x-admin.handleiding-screenshot caption="De admin-login op nexasuite.nl/admin." title="Nexa — Inloggen">
    <x-admin.handleiding-mock-screen active="Dashboard" title="Inloggen">
        <div class="max-w-sm mx-auto rounded-xl border border-border bg-background p-4 space-y-3">
            <div class="text-sm font-semibold text-foreground">Welkom terug</div>
            <div class="h-8 rounded-md border border-border bg-muted/30 px-2 text-[11px] text-muted-foreground flex items-center">e-mailadres</div>
            <div class="h-8 rounded-md border border-border bg-muted/30 px-2 text-[11px] text-muted-foreground flex items-center">wachtwoord</div>
            <div class="h-8 rounded-md bg-primary/90 text-primary-foreground text-[11px] font-medium flex items-center justify-center">Inloggen</div>
        </div>
    </x-admin.handleiding-mock-screen>
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>Open <strong>https://nexasuite.nl/admin</strong>.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>Vul het e-mailadres in dat in de welkomstmail staat — dat is uw gebruikersnaam.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>Vul het tijdelijke wachtwoord in. Direct daarna moet u een eigen wachtwoord kiezen.</div>
</div>

<h2 id="wachtwoord">2. Tijdelijk wachtwoord wijzigen</h2>
<p>
    Het tijdelijke wachtwoord mag <strong>één keer</strong> worden gebruikt om in te loggen.
    Daarna verschijnt een scherm dat u niet kunt sluiten totdat u een eigen wachtwoord heeft opgeslagen
    (minimaal 8 tekens, met hoofdletter, kleine letter en cijfer).
</p>

<x-admin.handleiding-screenshot caption="Verplicht scherm na de eerste login: het tijdelijke wachtwoord moet worden vervangen." title="Nexa — Wachtwoord wijzigen">
    <div class="rounded-xl border border-border bg-zinc-950/40 p-6">
        <div class="max-w-md mx-auto rounded-xl border border-border bg-background p-4">
            <div class="text-sm font-semibold text-foreground mb-1">Wachtwoord wijzigen</div>
            <p class="text-[11px] text-muted-foreground mb-3">Kies een eigen wachtwoord voordat u verdergaat.</p>
            <div class="h-8 rounded-md border border-border bg-muted/20 mb-2"></div>
            <div class="h-8 rounded-md border border-border bg-muted/20 mb-3"></div>
            <div class="h-8 rounded-md bg-primary/90 text-[11px] text-primary-foreground flex items-center justify-center">Wachtwoord opslaan</div>
        </div>
    </div>
</x-admin.handleiding-screenshot>

<h2 id="startscherm">3. Startscherm</h2>
<p>
    Na het inloggen landt u op het <strong>Dashboard</strong>. Links staat het menu met de onderdelen van uw pakket.
    Onder <strong>Handleiding</strong> vindt u deze uitleg altijd terug.
</p>

<x-admin.handleiding-screenshot caption="Na het inloggen opent het dashboard. Het menu links toont de onderdelen van uw pakket." title="Nexa — Dashboard">
    <div class="rounded-lg bg-gradient-to-r from-slate-50 via-slate-100 to-slate-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 p-4 ring-1 ring-border">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <div class="text-sm font-semibold text-foreground">Nexa overzicht</div>
                <div class="text-xs text-muted-foreground">Direct inzicht in ritten, gebruikers en omzet.</div>
            </div>
            <span class="kt-badge kt-badge-light text-[10px]">{{ now()->translatedFormat('d M Y') }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            @foreach([['Ritten', '18'], ['Chauffeurs', '3'], ['Omzet', '€ 2,4k'], ['Openstaand', '4']] as [$label, $value])
                <div class="rounded-lg bg-background/70 dark:bg-white/5 ring-1 ring-border p-2.5">
                    <div class="text-[10px] text-muted-foreground">{{ $label }}</div>
                    <div class="text-sm font-semibold text-foreground">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>
</x-admin.handleiding-screenshot>

<div class="handleiding-tip">
    <strong class="text-foreground">Tip:</strong> het menu past zich aan uw pakket aan. Onderdelen zoals dispatch of contractvervoer verschijnen alleen als ze in uw abonnement zitten.
</div>
