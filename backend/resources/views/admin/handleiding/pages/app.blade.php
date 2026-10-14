<p>
    De native app <strong>Nexa Taxi</strong> opent met <strong>Maak je keuze</strong>.
    Een klant boekt zonder account. Chauffeur, contractant en contractouder loggen in;
    de app kiest daarna het scherm dat bij de rollen hoort. Heeft iemand meer dan één rol,
    dan verschijnt <strong>Kies je scherm</strong>.
</p>

<div class="rounded-lg border border-border bg-muted/20 p-4 mb-6 overflow-x-auto">
    <pre class="text-xs sm:text-sm mb-0 font-mono text-foreground leading-relaxed whitespace-pre">Start
  ├─ Taxi boeken          → Klant (Boeken, Ritten, Profiel)
  ├─ Inloggen             → Chauffeur, contractant of contractouder
  └─ Marktplaats aanmelden → registratie van een taxibedrijf</pre>
</div>

<x-admin.handleiding-screenshot
    class="max-w-xs mx-auto"
    caption="Startscherm van de native app."
    title="Nexa Taxi — Maak je keuze"
    src="{{ asset('images/handleiding/app/welcome.png') }}"
    alt="Startscherm met Taxi boeken, Inloggen en aanmelden op de marktplaats" />

<x-admin.handleiding-screenshot
    class="max-w-xs mx-auto"
    caption="Inloggen met e-mail. De eerste keer vraagt u een code aan en kiest u daarna een wachtwoord."
    title="Nexa Taxi — Inloggen"
    src="{{ asset('images/handleiding/app/login.png') }}"
    alt="Inlogscherm met e-mail, wachtwoord, Inloggen en Code sturen" />

<h2 id="klant">Klant</h2>
<p>
    De klant tikt <strong>Taxi boeken</strong>. Onderaan staan de tabbladen
    <strong>Boeken</strong>, <strong>Ritten</strong> en <strong>Profiel</strong>.
    Een account is niet nodig; naam, telefoon en e-mail horen bij de rit en kunnen in Profiel bewaard worden.
</p>

<x-admin.handleiding-screenshot
    class="max-w-xs mx-auto"
    caption="Boeken: ophaaladres, bestemming, ophaalmoment en de kaart met taxi’s in de buurt."
    title="Nexa Taxi — Boeken"
    src="{{ asset('images/handleiding/app/klant-boeken.png') }}"
    alt="Boekscherm met adresvelden, ophaaltijd en kaart" />

<div class="handleiding-tip">
    Onderstaande stappen volgen de app. Het boekscherm hierboven is een echte opname.
    De statussen daarna (marktplaats, chauffeur, ophaalcode, onderweg) stonden niet open in de simulator
    en hebben daarom geen schermafbeelding.
</div>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>
        Op <strong>Boeken</strong> vult de klant het ophaaladres in (of tikt de locatieknop), de bestemming
        en het ophaalmoment. Een kaart toont de buurt en hoeveel taxi’s online zijn.
        Daarna volgen passagiers, bagage (grote en kleine ruimbagage, handbagage, opvouwbare rolstoel, huisdieren),
        voornaam, achternaam, telefoon, e-mail en een opmerking.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>
        Zodra beide adressen gekozen zijn, verschijnt <strong>Rit &amp; prijs</strong> met afstand, rijtijd en prijs.
        Zijn er geen taxi’s beschikbaar, dan stuurt de app de rit niet uit.
        Met <strong>Taxi aanvragen</strong> gaat de rit de deur uit.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>
        Op <strong>Ritten</strong> staat de nieuwe rit in de zoekfase. De app wisselt de titel
        <strong>We zoeken een taxi</strong> en <strong>Wacht op bevestiging</strong> af, met het label <strong>Zoeken…</strong>.
        De tekst is: <em>Je rit is aangeboden aan aangesloten taxibedrijven in de buurt.</em>
        Blijft de klant expres wachten, dan wordt dat: <em>Je rit blijft op de marktplaats tot een taxi accepteert.</em>
        De klant kan de rit annuleren. Blijft een rit te lang open, dan vraagt de app om te blijven wachten of te annuleren.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">4</span>
    <div>
        Heeft een chauffeur bevestigd, dan is de fase <strong>Geaccepteerd</strong>.
        De klant ziet de naam van de chauffeur, een sterwaardering als die er is, het kenteken en de autonaam,
        plus foto’s van chauffeur en auto als die zijn geüpload. Daarbij staan de centrale en, als de locatie bekend is,
        de verwachte aankomsttijd. Een tijdlijn toont de statusupdates.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">5</span>
    <div>
        Bij een marktplaatsrit (geen contractrit) verschijnt een balk <strong>Ophaalcode voor de chauffeur</strong>
        met een code van vier cijfers. De tekst is: <em>Geef deze ophaalcode aan de chauffeur om de rit te starten.</em>
        De chauffeur ziet de code niet. Die tikt <strong>Klant opgehaald — voer code in</strong>, vult de code in en tikt <strong>Start rit</strong>.
        Alleen een juiste code maakt de rit actief. Een foute code geeft een foutmelding; de rit blijft geaccepteerd.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">6</span>
    <div>
        Na de start verdwijnt de ophaalcode. De klant blijft de rit volgen via de statusupdates en de verwachte aankomst.
        De chauffeur navigeert met <strong>Ophalen</strong> en daarna <strong>Afzetten</strong>, en rondt af met
        <strong>Rit afronden</strong> (of eerst <strong>Betalen</strong> als de rit nog betaald moet worden).
        Is de rit afgerond, dan kan de klant sterren geven, een opmerking plaatsen en de factuur downloaden.
        Openstaande en eerdere ritten blijven onder <strong>Ritten</strong>; afgeronde ritten kunnen naar het archief.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">7</span>
    <div>
        Wacht de rit op betaling, dan staat er <strong>Wacht op betaling</strong> en de knop <strong>Nu betalen</strong>.
        Onder <strong>Profiel</strong> bewaart de klant naam, telefoon en e-mail, en de weergave
        (systeem, licht of donker). Die gegevens vullen het boekscherm de volgende keer in.
    </div>
</div>

<h2 id="chauffeur">Chauffeur</h2>
<p>
    De chauffeur kiest <strong>Inloggen</strong>, vult het e-mailadres in en tikt de eerste keer
    <strong>Code sturen</strong>. Na de code uit de e-mail kiest hij een wachtwoord.
    Daarna is inloggen e-mail plus wachtwoord. De tabbladen zijn
    <strong>Ritten</strong>, <strong>Aanvragen</strong>, <strong>Planning</strong>,
    <strong>Inkomsten</strong> (alleen met het recht om inkomsten te zien) en <strong>Profiel</strong>.
</p>

<div class="rounded-lg border border-border bg-muted/20 p-4 mb-4 overflow-x-auto">
    <pre class="text-xs sm:text-sm mb-0 font-mono text-foreground leading-relaxed whitespace-pre">Online + voertuig
  → Aanvragen: Accepteren of Weigeren
  → Ritten: code vragen, Start rit
  → Ophalen / Afzetten
  → Rit afronden (of eerst Betalen)
  → Archief</pre>
</div>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>
        Op <strong>Aanvragen</strong> zet de chauffeur zich <strong>Online</strong>.
        Zonder gekozen voertuig lukt dat niet: de app vraagt eerst een auto te kiezen.
        Offline komt er geen inbox binnen. Online blijft de locatie beschikbaar voor matching.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>
        Een aanbod heeft <strong>Accepteren</strong> en <strong>Weigeren</strong>.
        Ritten vanaf de NEXA-marktplaats dragen dat label. Er kan maar één rit tegelijk actief zijn;
        overlapt een rit, dan blokkeert de app het accepteren.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>
        Geaccepteerde ritten staan onder <strong>Ritten</strong>, te filteren op <strong>Dag</strong> of <strong>Week</strong>
        en op <strong>Alles</strong>, <strong>Normaal</strong> of <strong>Contract</strong>.
        Bij een marktplaatsrit is de startknop <strong>Klant opgehaald — voer code in</strong>.
        De chauffeur vraagt de vier cijfers aan de klant. In het venster <strong>Ophaalcode</strong> tikt hij <strong>Start rit</strong>.
        Zonder ophaalcode (geen marktplaatsrit) is de knop <strong>Klant opgehaald rit starten</strong>.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">4</span>
    <div>
        Tijdens de rit opent <strong>Ophalen</strong> de route naar de klant en <strong>Afzetten</strong> de route naar de bestemming, in Google Maps.
        <strong>Vrijgeven</strong> zet de rit terug in de pool. <strong>Nieuw tijdstip voorstellen</strong> stuurt een ander ophaalmoment ter goedkeuring.
        Annuleren vraagt een reden; die tekst ziet de klant.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">5</span>
    <div>
        Afronden gaat met <strong>Rit afronden</strong>. Moet er nog betaald worden, dan staat er eerst <strong>Betalen</strong>.
        Afgeronde ritten blijven staan tot <strong>Naar archief</strong>.
        <strong>Planning</strong> toont de week. <strong>Inkomsten</strong> toont dag, week en — met extra recht — maand.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">6</span>
    <div>
        <strong>Profiel</strong> toont naam, e-mail, telefoon, bedrijf en of het account online is.
        Daar staan ook beoordelingen, het voertuig, de themakleur en uitloggen.
        Met meerdere rollen is er <strong>Ander scherm</strong>.
    </div>
</div>

<h2 id="contractant">Contractant</h2>
<p>
    Een contractant logt in met het contractaccount. De app toont in Profiel de rol <strong>Contractant</strong>.
    De tabbladen zijn <strong>Ritten</strong>, <strong>Planning</strong>, <strong>Navigatie</strong>,
    <strong>Afmeldingen</strong> en <strong>Profiel</strong>.
    Alleen de contractant start en rondt ritten af; een contractouder ziet die knoppen niet.
</p>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>
        <strong>Ritten</strong> toont de contractritten van vandaag, met filters
        <strong>Alles</strong>, <strong>Openstaand</strong> en <strong>Verlopen</strong>.
        Per rit staan ophalen en afzetten. De contractant markeert <strong>Opgehaald</strong>,
        tikt <strong>Starten</strong> en later <strong>Afronden</strong>.
        Een banner <strong>Bestemming bereikt</strong> sluit de rit op de bestemming.
        Afwezige passagiers kunnen hier of via Afmeldingen worden afgemeld; <strong>Intrekken</strong> haalt een afmelding weg zolang de dag openstaat.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>
        <strong>Planning</strong> schakelt tussen <strong>Dag</strong> en <strong>Week</strong>.
        In de weekweergave kiest u een dag in de strip; de lijst eronder blijft de ritten van die dag.
        Vorige en volgende week zitten naast de datum.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>
        <strong>Navigatie</strong> toont de kaart met de route van elk kind in een eigen kleur, en de taxi als die live is.
        <strong>Start navigatie</strong> opent de route langs de haltes. Tik op een naam om de kleur van dat kind te wijzigen.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">4</span>
    <div>
        <strong>Afmeldingen</strong> meldt een passagier af voor één of meer dagen, met een reden.
        Bestaande afmeldingen kunnen worden ingetrokken.
        <strong>Profiel</strong> toont naam, e-mail, bedrijf en rol, plus thema (systeem, licht, donker) en accentkleur.
    </div>
</div>

<h2 id="contractouder">Contractouder</h2>
<p>
    Een contractouder gebruikt dezelfde app en dezelfde tabbladen. In Profiel staat de rol <strong>Contractouder</strong>.
    Het verschil met de contractant: de ouder volgt en meldt af, en start of rondt de rit niet af.
</p>

<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>
        <strong>Ritten</strong> en <strong>Planning</strong> tonen dezelfde dag- en weeklijst, met ophalen, afzetten en de filters
        Alles, Openstaand en Verlopen. De knoppen <strong>Starten</strong>, <strong>Afronden</strong> en
        <strong>Bestemming bereikt</strong> ontbreken.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>
        <strong>Navigatie</strong> volgt de taxi en de route van het kind op de kaart.
        Zonder open rit staat er <em>Geen open rit om te volgen.</em>
        <strong>Start navigatie</strong> is er niet. De kleur van een kind is wel aan te passen.
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>
        Onder <strong>Afmeldingen</strong> meldt de ouder een passagier af voor één of meer dagen en kan die afmelding intrekken
        zolang de dag openstaat. <strong>Profiel</strong> is gelijk aan dat van de contractant, met de rol Contractouder.
    </div>
</div>
