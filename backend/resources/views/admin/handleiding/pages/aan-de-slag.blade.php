<p>
    Welkom bij NEXA. Deze handleiding hoort bij uw abonnement: u ziet hier alleen de onderdelen
    die voor uw pakket beschikbaar zijn. Na de eerste login komt u hier terecht.
</p>

<h2 id="inloggen">1. Inloggen</h2>
<p>
    Ga naar <strong>nexasuite.nl/admin</strong>. Uw <strong>e-mailadres is de gebruikersnaam</strong>.
    Heeft u al een wachtwoord? Vul e-mail en wachtwoord in en kies <strong>Inloggen</strong>.
    Optioneel: <strong>Onthoud mij</strong>, zodat u niet elke keer opnieuw hoeft in te loggen.
</p>

<x-admin.handleiding-screenshot caption="Het admin-inlogscherm op nexasuite.nl/admin." title="Nexa — Inloggen">
    <x-admin.handleiding-login-card step="login" />
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>Open <strong>https://nexasuite.nl/admin</strong>.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>Vul het e-mailadres in dat bij uw account hoort.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>Heeft u al een wachtwoord? Vul dat in en kies <strong>Inloggen</strong>.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">4</span>
    <div>Is dit uw eerste keer? Kies onderaan <strong>Eerste keer inloggen?</strong> — er staat geen wachtwoord in de welkomstmail.</div>
</div>

<h2 id="eerste-login">2. Eerste keer inloggen: eenmalige code</h2>
<p>
    Nieuwe accounts (beheerder, medewerker) worden <strong>zonder wachtwoord</strong> aangemaakt.
    In de welkomstmail staat alleen uw e-mailadres. U activeert het account met een
    <strong>eenmalige code van 6 cijfers</strong> die naar dat adres gaat.
</p>
<p>
    Probeert u toch met een wachtwoord in te loggen voordat het account actief is? Dan opent Nexa
    automatisch <strong>Eerste keer inloggen</strong> met de melding dat u eerst een code moet aanvragen.
</p>

<x-admin.handleiding-screenshot caption="Eerste login: vraag een eenmalige code aan op het e-mailadres van uw account." title="Nexa — Eerste keer inloggen">
    <x-admin.handleiding-login-card step="request" />
</x-admin.handleiding-screenshot>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>Klik op <strong>Eerste keer inloggen?</strong>.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>Vul hetzelfde e-mailadres in als in de welkomstmail. Dat adres moet al in Nexa staan.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>Kies <strong>Inlogcode aanvragen</strong>. De code is <strong>15 minuten</strong> geldig. Kijk ook in spam of ongewenste mail.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">4</span>
    <div>Vul de 6-cijferige <strong>code uit e-mail</strong> in. Kies daarna zelf een wachtwoord (minimaal 8 tekens, met een hoofdletter, een kleine letter en een cijfer) en herhaal het.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">5</span>
    <div>Kies <strong>Wachtwoord instellen en inloggen</strong>. Daarna bent u ingelogd en opent de handleiding.</div>
</div>

<x-admin.handleiding-screenshot caption="Na de e-mail: code invullen, zelf een wachtwoord kiezen en inloggen." title="Nexa — Code en wachtwoord">
    <x-admin.handleiding-login-card step="verify" />
</x-admin.handleiding-screenshot>

<div class="handleiding-tip">
    <strong class="text-foreground">Code verlopen of niet ontvangen?</strong>
    Kies <strong>Nieuwe code aanvragen</strong>. Er kan ongeveer één minuut tussen twee aanvragen zitten.
    Een gebruikte of verlopen code werkt niet meer.
</div>

<h2 id="wachtwoord-vergeten">3. Wachtwoord vergeten</h2>
<p>
    Bent u uw wachtwoord kwijt (ná de eerste login)? Klik op het inlogscherm op
    <strong>Wachtwoord vergeten?</strong>. Vul uw e-mailadres in en kies <strong>Doorgaan</strong>.
    U ontvangt een link om een nieuw wachtwoord te zetten. Dat is iets anders dan de eenmalige
    inlogcode: die code is alleen voor de <strong>eerste</strong> activatie.
</p>

<h2 id="startscherm">4. Startscherm en navigatie</h2>
<p>
    Na de eerste login landt u op deze <strong>Handleiding</strong>. Daarna is het
    <strong>Dashboard</strong> het startpunt. Links staat het menu met de onderdelen van uw pakket.
    Onder Handleiding vindt u deze uitleg altijd terug.
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

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>Gebruik het menu links om naar ritten, gebruikers of instellingen te gaan.</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>Rechtsboven opent u via uw profielfoto <strong>Mijn Profiel</strong> of <strong>Uitloggen</strong>.</div>
</div>

<div class="handleiding-tip">
    <strong class="text-foreground">Tip:</strong> het menu past zich aan uw pakket aan. Onderdelen zoals dispatch of contractvervoer verschijnen alleen als ze in uw abonnement zitten.
    Chauffeurs en contractouders loggen niet in op /admin, maar in hun eigen app met dezelfde eenmalige code.
</div>
