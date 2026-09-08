<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>NEXA admin: menuitems per rol</title>
    <style>
        @page { margin: 18mm 14mm 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #0f172a; margin: 0; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 12px; margin: 16px 0 6px; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }
        p, li { line-height: 1.45; }
        .meta { color: #475569; font-size: 9px; margin-bottom: 12px; }
        .note { color: #334155; margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; margin: 0 0 10px; }
        th { background: #0f172a; color: #fff; text-align: left; padding: 5px 6px; font-size: 8.5px; font-weight: bold; }
        td { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; vertical-align: top; }
        tr.section td { background: #e2e8f0; font-weight: bold; font-size: 8.5px; letter-spacing: 0.04em; text-transform: uppercase; color: #334155; border-bottom: none; padding-top: 7px; }
        .c { text-align: center; white-space: nowrap; width: 72px; }
        .yes { color: #166534; font-weight: bold; }
        .no { color: #94a3b8; }
        .cond { color: #9a3412; font-weight: bold; }
        .item { width: 28%; }
        .voor { font-size: 8px; color: #475569; }
        ul { margin: 4px 0 8px 16px; padding: 0; }
        .footer { font-size: 8px; color: #64748b; margin-top: 8px; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 10px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>NEXA admin: welke rol ziet welk menuitem</h1>
    <p class="meta">Stand van de code, 25 augustus 2026. Bron: admin-zijbalk, MenuService, RoleSeeder, NexaTaxi/Skillmatching-modules en pakket-entitlements.</p>

    <div class="box">
        <strong>Alleen deze rollen zien het admin-panel:</strong> super-admin, company-admin, staff, demo.
        Overige rollen (chauffeur, klant, candidate, contractant, contractouder) hebben geen admin-zijbalk; zij gebruiken de chauffeur-app, het contractportaal of de kandidaat-omgeving.
        <br><br>
        <strong>Ja</strong> = zichtbaar bij de standaardrechten van die rol.
        <strong>Nee</strong> = niet in het menu.
        <strong>Voorwaarde</strong> = extra filter (module aan het bedrijf gekoppeld, of tenant-pakket Start/Pro/Business).
    </div>

    <h2>1. Zijbalk: menuitem per rol</h2>
    <p class="note">Module-items (Taxi, Skillmatching) verschijnen alleen als die module aan het bedrijf hangt. Super-admin zonder gekozen tenant ziet alle actieve modules; super-admin met tenant volgt de modules van die tenant.</p>

    <table>
        <thead>
            <tr>
                <th class="item">Menuitem</th>
                <th class="c">Super-admin</th>
                <th class="c">Company-admin</th>
                <th class="c">Staff</th>
                <th class="c">Demo</th>
                <th>Voorwaarde / toelichting</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section"><td colspan="6">Algemeen</td></tr>
            <tr>
                <td>Tenant-switcher (bovenin)</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Alleen super-admin. Wisselt session selected_tenant.</td>
            </tr>
            <tr>
                <td>Dashboard</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="voor">Geen extra check.</td>
            </tr>
            <tr>
                <td>Handleiding (alle pagina's)</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="voor">Geen extra check.</td>
            </tr>

            <tr class="section"><td colspan="6">Beheer</td></tr>
            <tr>
                <td>Bedrijven / Bedrijf</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Rol super-admin of permissie view-companies. Menu-label: Bedrijven voor super-admin, Bedrijf voor andere rollen. Company-admin heeft die permissie standaard; staff en demo niet. Knoppen Nieuw bedrijf / Nieuwe tenant: alleen super-admin.</td>
            </tr>
            <tr>
                <td>Gebruikers</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Rol super-admin of permissie view-users.</td>
            </tr>
            <tr>
                <td>Agenda</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="voor">Rol super-admin of permissie view-agenda. Staff heeft die permissie; demo niet.</td>
            </tr>
            <tr>
                <td>Notificaties</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="voor">Rol super-admin of permissie view-notifications.</td>
            </tr>
            <tr>
                <td>E-mail templates + Formulier velden</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Hard super-admin, ook al heeft company-admin de oude permissie view-email-templates.</td>
            </tr>
            <tr>
                <td>Klantfacturen (los item onder Beheer)</td>
                <td class="c no">Nee*</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Alleen company-admin die geen super-admin is. Super-admin ziet Klantfacturen onder Systeem &gt; Betalingen.</td>
            </tr>
            <tr>
                <td>Abonnementen</td>
                <td class="c no">Nee</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Alleen company-admin die geen super-admin, staff of demo is. Huidig pakket, aanvullende modules, jaarcontract, upgrade/downgrade/opzeggen.</td>
            </tr>
            <tr>
                <td>Email communicatie</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Alle e-mails naar klanten van de tenant (welkomstmail, inlogcode, ritcommunicatie, facturen). Opnieuw versturen mogelijk. Alleen company-admin en super-admin.</td>
            </tr>

            <tr class="section"><td colspan="6">Module Taxi (alleen als module taxi aan het bedrijf hangt)</td></tr>
            <tr>
                <td>Voertuigen</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c no">Nee</td>
                <td class="c cond">Voorwaarde</td>
                <td class="voor">Permissie vehicles.view. Company-admin en demo hebben die; staff niet. Demo: menu-key vehicles.</td>
            </tr>
            <tr>
                <td>Tarieven</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c no">Nee</td>
                <td class="c cond">Voorwaarde</td>
                <td class="voor">rates.view of vehicles.view. Demo: menu-key tarieven.</td>
            </tr>
            <tr>
                <td>Ritten</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c no">Nee</td>
                <td class="c cond">Voorwaarde</td>
                <td class="voor">Permissie rides.view. Demo: menu-key ride_requests.</td>
            </tr>
            <tr>
                <td>Contractvervoer (+ Contractklanten, Planning, Uitzonderingen)</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c no">Nee</td>
                <td class="c cond">Voorwaarde</td>
                <td class="voor">rides.view plus pakket-capability contract_transport. Zie tabel 2. Demo: menu-key transport_customers, daarna hetzelfde pakketfilter.</td>
            </tr>
            <tr>
                <td>GPS-tracker (+ Voertuigen)</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c no">Nee</td>
                <td class="c cond">Voorwaarde</td>
                <td class="voor">vehicles.view of rides.view plus aanvullende module GPS-trackers (package_capability gps_tracking). Menu-key gps_tracking. Niet in de demo-sleutels.</td>
            </tr>
            <tr>
                <td>Chauffeur dispatch</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c no">Nee</td>
                <td class="c cond">Voorwaarde</td>
                <td class="voor">rides.view plus pakket-capability dispatch. Zie tabel 2. Demo: menu-key dispatch_settings.</td>
            </tr>
            <tr>
                <td>AI-chatbot (+ Kennisbank, Instellingen)</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Alleen super-admin, en alleen als taxi-module zichtbaar is. Demo-sleutel ontbreekt; company-admin wordt uitgefilterd via super_admin_only.</td>
            </tr>

            <tr class="section"><td colspan="6">Module Skillmatching (alleen als module skillmatching aan het bedrijf hangt)</td></tr>
            <tr>
                <td>Branches</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Permissie view-branches. Company-admin heeft die standaard; staff niet. Demo heeft geen skillmatching-menu.</td>
            </tr>
            <tr>
                <td>Vacatures</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Menu checkt skillmatching.vacancies.view. Staff heeft alleen view-vacancies; dat is niet genoeg voor dit item.</td>
            </tr>
            <tr>
                <td>Matches</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">skillmatching.matches.view. Staff heeft view-matches, niet deze module-permissie.</td>
            </tr>
            <tr>
                <td>Interviews</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">skillmatching.interviews.view.</td>
            </tr>
            <tr>
                <td>Job Configuraties (+ Configuratie types)</td>
                <td class="c cond">Voorwaarde</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Super-admin en skillmatching-module globaal actief (niet per tenant-koppeling in de zijbalk).</td>
            </tr>

            <tr class="section"><td colspan="6">Systeem (hele blok alleen super-admin)</td></tr>
            <tr>
                <td>Toegang: Rollen en permissies, Permissies, Modules</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">hasRole(super-admin). Blijft zichtbaar ook als een tenant is gekozen.</td>
            </tr>
            <tr>
                <td>Betalingen: Overzicht, Openstaand, Voldaan, Ritfacturen, Klantfacturen, Betalingsproviders, Instellingen</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Hele accordion alleen super-admin.</td>
            </tr>
            <tr>
                <td>NEXA facturatie: NEXA-facturen, Tenant-abonnementen, Factuurregels, Facturatie-instellingen</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Alleen super-admin.</td>
            </tr>
            <tr>
                <td>Paketten (NEXA-prijzen / entitlements)</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">isSuperAdmin().</td>
            </tr>
            <tr>
                <td>Configuraties: Algemeen, Systeem, Upgrade (platform)</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Alleen super-admin.</td>
            </tr>
            <tr>
                <td>Front-end: Coming Soon, Welkom, Pagina's, Thema's, Componenten, Componenten catalogus</td>
                <td class="c yes">Ja</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="voor">Alleen super-admin.</td>
            </tr>
        </tbody>
    </table>

    <h2>2. Pakketfilter bovenop de rol (Taxi)</h2>
    <p class="note">Geldt voor company-admin, demo en super-admin met een tenant gekozen. Super-admin zonder tenant (Alle tenants): geen pakket, dus Contractvervoer en Dispatch blijven zichtbaar. Leeg package_key op een bedrijf = geen extra limiet (legacy), dan beide items ook zichtbaar.</p>
    <table>
        <thead>
            <tr>
                <th>Taxi-menuitem</th>
                <th class="c">Start</th>
                <th class="c">Pro</th>
                <th class="c">Business</th>
                <th class="c">Geen pakket</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Voertuigen, Tarieven, Ritten</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
            </tr>
            <tr>
                <td>Chauffeur dispatch</td>
                <td class="c no">Nee</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
            </tr>
            <tr>
                <td>Contractvervoer</td>
                <td class="c no">Nee</td>
                <td class="c no">Nee</td>
                <td class="c yes">Ja</td>
                <td class="c yes">Ja</td>
            </tr>
            <tr>
                <td>AI-chatbot</td>
                <td class="c no">Nee*</td>
                <td class="c no">Nee*</td>
                <td class="c no">Nee*</td>
                <td class="c no">Nee*</td>
            </tr>
        </tbody>
    </table>
    <p class="note">* AI-chatbot hangt niet aan het pakket, maar aan de rol: alleen super-admin. Een company-admin op Business ziet dit item dus niet.</p>

    <h2>3. Praktische samenvatting per rol</h2>
    <ul>
        <li><strong>Super-admin:</strong> hele zijbalk, inclusief Systeem en Email communicatie. Taxi- en skillmatching-items volgen de gekozen tenant (modules + pakket). Zonder tenant: alle actieve modules, geen pakketfilter. Mag tenants aanmaken.</li>
        <li><strong>Company-admin:</strong> Dashboard, Handleiding, Bedrijf, Gebruikers, Agenda, Notificaties, Abonnementen, Klantfacturen, Email communicatie, plus module-items van het eigen bedrijf. Geen Systeem, geen e-mailtemplates, geen AI-chatbot, geen nieuwe tenant.</li>
        <li><strong>Staff:</strong> Dashboard, Handleiding, Agenda, Notificaties. Geen Bedrijven/Gebruikers, geen taxi-items (mist vehicles.view / rides.view), geen skillmatching-items (mist skillmatching.*.view), geen Systeem.</li>
        <li><strong>Demo:</strong> Dashboard, Handleiding, plus de taxi-keys Voertuigen, Tarieven, Ritten, Contractvervoer en Chauffeur dispatch (daarna nog het pakketfilter van het demobedrijf).</li>
    </ul>

    <p class="footer">Dit overzicht beschrijft wat de zijbalk toont. Achterliggende pagina's kunnen extra 403 of pakketblokkades hebben. Standaardrechten komen uit RoleSeeder; een handmatig extra recht op een gebruiker kan extra items tonen.</p>
</body>
</html>
