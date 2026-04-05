# Prompt: Nexa SaaS — multi-tenant, modules & domeinen

Gebruik dit document als **vaste instructie** voor uitwerking in deze codebase (Laravel backend, tenant = `company_id`, bestaande `TenantMiddleware` / `TenantFilter`).

---

## Doel

Een **multi-tenant SaaS** waarbij:

1. Een **super-admin** vanuit de backend **bedrijven (tenants)** aanmaakt en elk bedrijf koppelt aan **één of meer modules** (voorbeelden: **Taxi**, **Garage**).
2. Per module verschijnen **specifieke backend-menu’s en configuraties** (bijv. tarieven, voertuigen, agenda — module-afhankelijk). Dit naast de standaard menuitems functionaliteit.
3. Per tenant is er een **eigen frontend** (pagina’s opgebouwd uit componenten / page builder) voor de publieke/marketing-site.
4. Er kan een **domein** aan een tenant worden gekoppeld. Bezoekers en gebruikers die via dat domein binnenkomen krijgen een **afgebakende ervaring**: alleen data en UI van die tenant.
5. Optioneel/parallel: een **klantportaal** waar eindklanten van de tenant bepaalde acties doen, zoals bijvoorbeeld de eerdere taxi ritten inzien bij een Taxi module of bij een Garage module alle onderhoudsbeurten, afspraken en facturen inzien (andere rollen en permissies dan tenant-medewerkers).

**Prioriteit:** veiligheid (tenants mogen **nooit** elkaars data zien) en **eenvoudige, duidelijke beleving** voor tenant en eindklant.

---

## Architectuurkeuzes (vasthouden tenzij expliciet herzien)

- **Database:** start met **één gedeelde database**; isolatie via **`company_id`** (of equivalent) op alle tenant-data, aangevuld met **Policies** en waar passend **Eloquent global scopes**. Geen tenant-data zonder expliciete scope.
- **Tenant-identiteit:** primair `Company` / `company_id` zoals nu in de app; uitbreiden met domein-resolutie en module-koppelingen.
- **Super-admin:** kan alle tenants beheren; gebruik bestaand patroon met **tenant-selectie in sessie** waar nodig, maar **conflict tussen ingelogde user en domein** altijd afwijzen (geen cross-tenant toegang).

---

## Taken voor de agent / ontwikkelaar

1. **Domein → tenant:** model + migratie voor gekoppelde domeinen; middleware die op basis van `Host` de actieve tenant resolve’t en beschikbaar stelt voor routes/views/API.
2. **Modules:** registratie welke module(s) een company heeft; backend-menu’s en routes **conditioneel** tonen op basis van module; voorkeur voor modulaire structuur (bijv. Laravel-modules per domein-Taxi/Garage) i.p.v. losse `if`-slingers overal.
3. **Autorisatie:** naast controller-filters **Policies** (en tests) op kritieke resources; audit van uitzonderingen zoals “globaal zichtbaar” (bijv. branches) — per entiteit bepalen of dat tenant-scoped moet.
4. **Frontend / page builder:** data per `company_id`; preview en publish-flow; cache per host indien nodig.
5. **Klantportaal (fase 2 indien gewenst):** aparte route-groep, rollen (eindklant vs tenant-staff), geen gedeelde “admin”-assumpties.
6. **Rollen en permissies (zie sectie hieronder):** module-specifieke rechten **registreren** in het centrale permissiemodel en **toewijsbaar** maken in dezelfde rollen-UI als bestaande permissies.

---

## Admin UI (backend): consistentie

- **Referentiepatroon:** bestaande schermen zoals **Gebruikers** — zelfde **look & feel**, layout en interactiepatronen aanhouden.
- **Overzichten:** o.a. **actiemenu’s** per regel (of het gangbare patroon uit die referentie), **klik op een regel** opent de **detailpagina**, **paginering**, **filtering** / zoeken waar dat op vergelijkbare lijsten al voorkomt.
- **Nieuwe backend-pagina’s** (ook voor module-specifieke onderdelen): **niet** een eigen stijl of andere UX introduceren; **hergebruik** van dezelfde partials, tabellen, knoppen, lege-staat- en foutfeedback als de referentieschermen.
- Doel: gebruikers en beheerders herkennen overal hetzelfde gedrag; onderhoud blijft voorspelbaar (één plek voor aanpassingen aan lijst/detail-gedrag).
- **Concrete checklist** (nieuwe schermen afvinken, referentie Gebruikers): [`admin-ui-checklist.md`](./admin-ui-checklist.md).
- **Tenant onboarding-wizard** (stappenplan tabs in admin): [`saas-tenant-onboarding-implementatie.md`](./saas-tenant-onboarding-implementatie.md).

---

## Rollen en permissies

- **Eén systeem:** rechten en rollen moeten **soepel en eenvoudig** te beheren blijven — geen verspreide “losse” checks alleen in views; **server-side** blijft leidend (Policies / middleware), maar de **administratie** van wat wie mag gebeurt op **één plek** (rollen & permissies in de backend).
- **Module-configuratie ↔ permissies:** wanneer in de **moduleconfiguratie** rechten of **pagina’s** worden gedefinieerd die **alleen voor die module** gelden (bijv. Taxi-tarieven, Garage-onderhoud), moeten die als **eersteklas permissies** in het systeem landen — dezelfde naamgevings- en registratiestructuur als bestaande permissies.
- **Toewijzen aan rollen:** alle module-specifieke permissies moeten **automatisch zichtbaar en selecteerbaar** zijn in de **rollen- en permissiebeheer**-schermen (aanmaken/bewerken van rollen), zodat ze net als globale acties aan gebruikersrollen gekoppeld kunnen worden. Geen “alleen via module-instellingen verborgen superuser-rechten” zonder spoor in rollenbeheer.
- **Onderhoud:** bij nieuwe module-pagina’s of acties: **registratie** van de bijbehorende permissies (seed of module service provider) zodat ze in de UI verschijnen; documenteer kort de naming convention (bijv. `module.taxi.rates.view`).

---

## Veiligheid (niet onderhandelbaar)

- Elke read/write van tenant-data: **tenant-context** moet kloppen (user `company_id` én/of resolved tenant uit domein).
- **Feature tests:** minstens scenario’s “tenant A kan geen record van tenant B lezen/wijzigen” op kern-endpoints.
- Geen vertrouwen op alleen frontend-verbergen van menu’s; **server-side** altijd enforce.
- **URL’s en route-parameters (`/resource/{id}` e.d.):** personen mogen door **wijziging van een id** (of andere sleutel in URL, query of body) **niet** bij data van een andere tenant komen. Nooit alleen op “record bestaat” vertrouwen: na ophalen altijd controleren dat het record **bij de actieve tenant** hoort (policy + `company_id` / equivalent). Bij **toegang geweigerd** (cross-tenant of onrechtmatige id): **geen** gedeeltelijke of gelekte data; **HTTP 403** (of een bewust gekozen **404** om enumeratie te bemoeilijken — één strategie voor de hele app) en voor **web** een **vaste blokkade-/toegang-geweigerd-pagina** met begrijpelijke tekst (geen stack traces). Voor **API’s:** gestructureerde fout, geen details die tenants of records verraden.
- **Uploads (afbeeldingen, documenten, alle binaire bestanden):** altijd **versleuteld at rest** opslaan; fysieke/logische opslag uitsluitend onder een **eigen tenant-map** (bijv. voorvoegsel op basis van `company_id` of een unieke tenant-key), zodat bestanden **nooit tussen tenants** door elkaar lopen. Geen gedeelde platte upload-map zonder tenant-segmentatie. Uitserveren/download alleen na tenant-check en decryptie in applicatielaag (geen directe publieke URL naar ruwe opslag die tenant overschrijdt).

### Voorkeur: hoe opslag en encryptie in te richten

- **Productie (aanbevolen):** **S3-compatible object storage** met **server-side encryption** — bij voorkeur **SSE-KMS** (eigen KMS-key per omgeving of per tenant als compliance dat eist), anders minimaal **SSE-S3**. Object keys altijd met vaste **tenant-prefix**, bijv. `tenants/{company_id}/...`, zodat policies en audits eenvoudig blijven. Bucket: **geen publieke listing**; toegang via applicatie-rol (IAM/IAM-achtig) en waar nodig **signed URLs** met korte geldigheid voor downloads.
- **Applicatie:** Laravel `Storage`-disk(s) naar die bucket; **paden nooit blind uit user-input** overnemen; elke read/write valideert tenant-context. Geen statische publieke URL naar een pad dat tenant niet afdwingt.
- **Kleinere of lokale omgeving:** Laravel **encrypted** disk (of bestand eerst encrypten vóór `put`) **plus** dezelfde tenant-mapstructuur — acceptabel als de onderliggende schijf óók versleuteld is; voor productie en schaal blijft **managed object storage + SSE** de betere optie (backups, lifecycle, compliance).
- **Vermijden:** alleen “aparte map per tenant” **zonder** at-rest-encryptie op de storage-laag als die schijf/bucket onversleuteld is; security door verborgen URL’s i.p.v. echte autorisatie.

---

## Stijl van werken in deze repo

- Bestaande patronen (Laravel, `TenantFilter`, rollen) **respecteren en uitbreiden**; geen grote refactor zonder noodzaak.
- Nieuwe admin-schermen: **Admin UI: consistentie** (hierboven); nieuwe acties: **Rollen en permissies** (registreren + toewijsbaar in rollen-UI).
- Wijzigingen **gericht** houden; geen losse documentatie of bestanden toevoegen tenzij gevraagd.
- Na wijzigingen: relevante tests en linter waar van toepassing.

---

## Referentie: huidige code (indicatief)

- `App\Http\Middleware\TenantMiddleware` — `tenant_id` uit `user->company_id`
- `App\Http\Controllers\Admin\Traits\TenantFilter` — query-filtering voor admin
- Super-admin + `session('selected_tenant')` voor tenant-switch in backend
- **Admin UI-patroon:** o.a. **Gebruikers**-overzicht en -detail (`AdminUserController`, bijbehorende Blade-views) als referentie voor lijsten, acties, navigatie detail.
- **Permissies:** o.a. `AdminPermissionController`, rollen-views — uitbreiden i.p.v. parallel systeem voor module-rechten.

---

*Versie: 2026-03-25 — basis voor vervolgstappen in implementatie (o.a. admin-UI-consistentie, rollen/permissies).*
