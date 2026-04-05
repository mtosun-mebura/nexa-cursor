# Checklist: nieuwe admin-schermen (backend)

Gebruik dit bij elke nieuwe **overzichts-/detailpagina** in de admin. Aansluiten op [`saas-multi-tenant-prompt.md`](./saas-multi-tenant-prompt.md).

---

## 1. UI & layout (consistent met referentie)

| # | Controle | Ja / N.v.t. |
|---|----------|-------------|
| 1.1 | Layout: `@extends('admin.layouts.app')`, titel via `@section('title', …)` | |
| 1.2 | Pagina-header: titel links, primaire actie rechts (`kt-btn kt-btn-primary` waar passend) | |
| 1.3 | Overzicht in `kt-card` / `kt-card-grid`, tabel met `kt-table`-patroon zoals bestaande lijsten | |
| 1.4 | **Actiemenu** per regel (`kt-menu`, drie puntjes) met acties (Bekijken, Bewerken, …) — `@can` per actie | |
| 1.5 | **Klik op rij** opent detail (delegation op `tr.*-row`, actiekolom met `stopPropagation` / uitsluitingen voor links en menu) | |
| 1.6 | **Sortering** op relevante kolommen via query (`sort`, `direction`) indien van toepassing | |
| 1.7 | **Filtering / zoeken** via GET-parameters en form die query behoudt (hidden fields voor andere filters) | |
| 1.8 | **Paginering**: zelfde strategie als referentie — bij Gebruikers is dit **client-side** (alle rijen geladen, KTDataTable). Bij grote datasets overwegen **server-side** paginering (`paginate()`) met dezelfde visuele componenten waar mogelijk | |
| 1.9 | Success/error: `kt-alert` + session flash zoals elders | |
| 1.10 | Lege toestand: duidelijke boodschap wanneer geen resultaten | |
| 1.11 | **Formulierlayout:** altijd het patroon uit **§ 1a** (label links, inhoud rechts in `kt-card` + tabel). | |

---

## 1a. Formulierlayout (verplicht)

**Alle** admin-formulieren die in een **`kt-card`** staan en **meerdere velden of blokken** tonen, worden op dezelfde manier opgemaakt:

1. **Geen** losse stapeling van `kt-form-label` boven volle-breedte velden zonder kolomscheiding, tenzij het om een bewuste uitzondering gaat (bijv. alleen een enkele paragraaf of één full-width veld).
2. **Wel** een **twee-koloms** opzet in een tabel binnen de kaart:
   - Buitenste wrapper: `div.kt-card-table.kt-scrollable-x-auto.pb-3`
   - Tabel: `class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table"`
   - **`tbody`** met **`tr`** per logische regel:
     - **Eerste kolom (label):** `td.min-w-56.text-secondary-foreground.font-normal.align-top` — alleen het korte label of de veldnaam (bijv. «Logo», «Hoofdtekst»).
     - **Tweede kolom (inhoud):** `td.min-w-48.w-full.align-top` — uitleg, sublabels, switches, inputs, dropzones, enz.
3. **Volle breedte boven de tabel** alleen voor de **kaarttitel** (`kt-card-header` / `kt-card-title`). Optioneel: een **introductieregel** in de eerste rij met **`colspan="2"`** en padding (bijv. `px-4 sm:px-6 pt-6 pb-2`) als er uitleg nodig is vóór de velden.
4. **Actieknoppen** (bijv. «Opslaan») in een afsluitende rij met **`colspan="2"`**, rechts uitgelijnd (`flex justify-end`), met passende onder-padding (bijv. `pb-6`).
5. **Preview boven dropzone:** waar een afbeelding geüpload wordt (logo, favicon, …), staat het **voorbeeld visueel boven** de dropzone in dezelfde rechterkolom (`flex flex-col gap-3`), niet ernaast — tenzij de specifieke UI expres een andere lay-out vereist.

**CSS voor gelijke rij-padding** (als de pagina geen wizard-layout extend): zelfde regels als in de tenant-wizard — zie `resources/views/admin/companies/wizard/layout.blade.php` of de inline `<style>` bij `resources/views/admin/settings/general.blade.php` voor `.wizard-onboarding-form-table`.

**Referenties (conform dit patroon):**

| Bestand | Opmerking |
|---------|-----------|
| `resources/views/admin/companies/wizard/step1.blade.php` | Tenant-onboarding, o.a. logo + bedrijfsvelden |
| `resources/views/admin/companies/create.blade.php` / `edit.blade.php` | Nieuw/bewerken bedrijf |
| `resources/views/admin/settings/general.blade.php` | Logo & Favicon, Algemene opties, Formulier succesbericht |

---

## 1b. Afbeelding uploaden (admin) — vast patroon

Gebruik **niet** een kale `<input type="file" class="kt-input">` voor logo’s/afbeeldingen. Hanteer hetzelfde patroon als de **website-builder** (Home-secties → Footer-logo) en **Algemene instellingen** (Logo & Favicon):

- Container: `flex flex-wrap items-start gap-2`
- Optioneel: thumbnail + verwijderknop (`image-remove-btn`, `ki-picture`-icoon)
- Dropzone: `border border-input rounded-xl border-dashed bg-muted/30`, min. hoogte ~130px, tekst **“Klik of Sleep & Drop”** en **“SVG, PNG, JPG (max. 2MB)”** (of vergelijkbaar; bij favicon andere formaten)
- Echte upload: `<input type="file" … class="hidden">` + JavaScript: klik op zone/link opent dialoog, **dragover / drop**, client-side check op type en **2MB**
- Servervalidatie: o.a. `max:2048` (KB) voor 2MB, `mimes:` passend bij `accept`

**Herbruikbare partial:** `resources/views/admin/partials/image-upload-dropzone-inline.blade.php` (gebruikt o.a. op tenant-wizard stap 1). Referentie-implementaties: `resources/views/admin/website-pages/partials/home-sections.blade.php` (footer-logo uploadblok + JS), `resources/views/admin/settings/general.blade.php` (branding).

---

## 1c. Formuliervalidatie (UX) — verplicht patroon

| # | Regel | Toelichting |
|---|--------|-------------|
| 1c.1 | **Geen** browser-`alert()`, `confirm()` of vergelijkbare popups voor **veldvalidatie** of uploadfouten (type/grootte). Toon **inline** rood (`text-destructive`) onder het veld. Zet op het `<form>` het attribuut **`novalidate`**, anders blokkeert de browser submit met een native tooltip (bijv. “Vul dit veld in”) en bereikt **Laravel-validatie + `@error` niet** — de gebruiker ziet dan geen rode tekst onder het veld. | Gebruikers moeten fouten in context zien; popups onderbreken de flow. |
| 1c.2 | **Serverfouten (Laravel):** `@error('veld')` direct **onder** het veld, met `text-xs text-destructive mt-1`. Voeg `border-destructive` toe op het input- of select-element bij dezelfde fout. Markeer het foutblok met `data-validation-error="1"` en **`data-validation-error-for="veldnaam"`** (zelfde als `name` op het input/select, of `logo` voor bestand) zodat scroll-scripts en client-side opruimen het veld herkennen. Optioneel mag bovenaan het formulier nog een **samenvatting** staan (bijv. `x-error-card`); dat is geen popup en vervangt de veldfouten niet. | Eén consistente “rode regel” per fout. |
| 1c.2b | **Direct opruimen na typen:** zodra de gebruiker een veld **wijzigt** waar een serverfout op stond (`border-destructive` / `data-server-error`), verdwijnt de rode rand en de foutregel **onmiddellijk** (geen nieuwe submit nodig). Dit staat **inline** in `admin/layouts/app.blade.php` (werkt altijd) en vult aan met `resources/js/admin-field-hints.js` waar nodig. | Standaardgedrag in de admin. |
| 1c.3 | **Scroll na redirect met fouten:** bij `$errors->any()` scrollt het admin-layout naar het **eerste** element met `[data-validation-error]`, anders naar `.border-destructive` / `[data-server-error]` (zie `admin/layouts/app.blade.php`). | Gebruiker ziet direct welk veld ongeldig is. |
| 1c.4 | **Live validatie (algemeen):** voor schermen zonder verplichte e-mail/telefoon per § **1e** kan dezelfde opzet als gebruiker aanmaken worden gebruikt: `data-validate="true"` + `form-validation.js`. Zie ook § **1e** — **e-mail en telefoon zijn daarvan geen uitzondering.** |
| 1c.5 | **Server blijft leidend:** HTML5-`pattern`/`minlength` alleen als extra hulp; **bron van waarheid** is Laravel `validate()` in de controller of Form Request. Houd `AdminFieldValidationPatterns::evaluateWizardStep1Field()` en de JS-logica synchroon; test met `tests/Unit/AdminFieldValidationPatternsTest.php`. | Voorkomt drift tussen client en server. |
| 1c.6 | **Uitrol:** nieuwe en gewijzigde admin-formulieren moeten dit patroon volgen; bestaande schermen kunnen gefaseerd worden bijgewerkt. | |

---

## 1d. Adres invoer (Nederland) — postcode-lookup verplicht

Waar in de admin (of elders in de applicatie) een **adres** ingevuld moet worden, geldt dit vaste patroon:

| # | Regel | Toelichting |
|---|--------|-------------|
| 1d.1 | **Altijd** de **postcode-check** gebruiken op basis van **postcode** en **huisnummer** (zelfde bron als bestaande admin-postcode-lookup: `POST admin/postcode/lookup`, route `admin.postcode.lookup`). | Eén consistente adresbron; geen vrije tekst alleen voor straat/plaats tenzij de lookup faalt. |
| 1d.2 | **Volgorde van velden:** eerst **postcode** en **huisnummer**, daaronder **straat**, **plaats** en **land**. | Gebruiker vult eerst wat nodig is voor de lookup. |
| 1d.3 | Straat, plaats en land staan standaard op **`readonly`** en worden **automatisch** ingevuld na een geslaagde lookup (bijv. op `blur` van postcode/huisnummer of na debounced request). | Voorkomt inconsistente combinaties van postcode en straat/plaats. |
| 1d.4 | **Geen adres gevonden** (lookup zonder resultaat of fout): **verwijder `readonly`** van straat, plaats en land zodat de gebruiker **handmatig** kan aanvullen. Bij een latere geslaagde lookup kunnen de velden weer readonly worden gezet en ingevuld. | Fallback zonder de gebruiker te blokkeren. |
| 1d.5 | Korte **hulptekst** onder de velden (zoals bij de tenant-wizard) die uitlegt dat de lookup automatisch gaat en dat handmatig invullen mogelijk is als er geen resultaat is. | |

**Referentie:** tenant-wizard stap 1 — `resources/views/admin/companies/wizard/step1.blade.php` (bedrijfsadres in eerste kaart; contactpersoon in tweede kaart). Vergelijkbaar patroon: nieuw bedrijf aanmaken in de admin (`admin/companies/create`) waar hetzelfde postcode-/huisnummer-gedrag geldt.

---

## 1e. E-mail en telefoon (admin) — altijd `form-validation.js`

**Elk** `<input type="email">` en **elk** `<input type="tel">` (of veld bedoeld als Nederlands telefoonnummer, herkenbaar aan `name`/`pattern` zoals elders) in de **admin** moet zich hetzelfde gedragen als op **Gebruiker aanmaken**:

| # | Regel | Toelichting |
|---|--------|-------------|
| 1e.1 | Het **formulier** waarin het veld staat, moet **`data-validate="true"`** hebben (en bij voorkeur **`novalidate`**, zie §1c.1). | Anders start `FormValidator` niet. |
| 1e.2 | De pagina laadt **`public/assets/js/form-validation.js`** (bijv. `@push('scripts')` met `asset('assets/js/form-validation.js')`, zelfde als `resources/views/admin/users/create.blade.php`). | Eén gedeelde module; geen eigen e-mail/telefoon-JS per scherm. |
| 1e.3 | **Gedrag tijdens typen:** live feedback via **`.field-feedback`** (rode fouttekst onder het veld, o.a. minimaal 5 tekens voor e-mail; NL-telefoon volgens patroon in het script), plus **vink/kruis** in het veld (`validation-icon-wrapper`). **Hulptekst** (`text-muted-foreground`) blijft mogelijk boven/naast de dynamische feedback, zoals bij telefoon op gebruiker aanmaken. | Zie `validationRules.email` en `validationRules.phone` in `form-validation.js`. |
| 1e.4 | **Server** blijft leidend: Laravel `validate()` (bijv. `email`, `regex` telefoon) is de waarheid; de client is alleen UX. | Geen conflict: `novalidate` + client-submitvalidatie in het script. |
| 1e.5 | Formulieren met **bestandsupload** in hetzelfde `<form>`: het script slaat **`type="file"`** over (zoals geïmplementeerd in `form-validation.js`); andere velden blijven gevalideerd. | Voorkomt onnodige validatie op het file-input. |

**Referenties:** `resources/views/admin/users/create.blade.php` (e-mail + telefoonrijen + `@push` script), `resources/views/admin/companies/wizard/step1.blade.php` (Contactpersoon-kaart). **Geen** afwijkende aanpak voor alleen e-mail/telefoon (bijv. geen losse `admin-field-hints`-only variant voor deze velden tenzij het hele formulier bewust geen `data-validate` gebruikt — voor **nieuwe** schermen: **altijd** dit patroon voor e-mail en telefoon).

---

## 2. Veiligheid & tenant

| # | Controle | Ja / N.v.t. |
|---|----------|-------------|
| 2.1 | Controller gebruikt `TenantFilter` / `applyTenantFilter` waar tenant-data wordt getoond | |
| 2.2 | `show` / `edit` / `update` / `destroy`: na route-model binding **`canAccessResource`** of policy-equivalent — **403** bij cross-tenant of ongeldige id | |
| 2.3 | Geen gevoelige details in 403-boodschap die enumeratie vergemakkelijken (consistent met prompt) | |
| 2.4 | API/json: zelfde checks, geen stack trace naar client | |

---

## 3. Rechten (Spatie permissies)

| # | Controle | Ja / N.v.t. |
|---|----------|-------------|
| 3.1 | Nieuwe permissies geregistreerd (seeder of module provider) en zichtbaar in rollenbeheer | |
| 3.2 | `index`: `@can('view-…')` of `abort(403)` aan begin controller | |
| 3.3 | `create`/`store`: `create-…` | |
| 3.4 | `edit`/`update`: `edit-…` | |
| 3.5 | `destroy`: `delete-…` (of passende naam) | |
| 3.6 | Module-specifieke acties: naamconventie `module.{module}.{resource}.{action}` (afspraak met team) | |

---

## 4. Tests (minimaal)

| # | Controle | Ja / N.v.t. |
|---|----------|-------------|
| 4.1 | Feature test: gebruiker **zonder** permissie krijgt **403** op index/show | |
| 4.2 | Feature test: tenant A kan **geen** record van tenant B openen (id in URL) | |
| 4.3 | Unit test: waar `AdminFieldValidationPatterns` wordt gebruikt, evaluatie **in lijn** met Laravel `Validator`-regels voor hetzelfde scherm (zie `tests/Unit/AdminFieldValidationPatternsTest.php`) | |

---

## Referentie-implementatie: Gebruikers

Onderstaand is geen verplichte code-aanpassing, maar een **verificatie** dat het huidige **Gebruikers**-scherm het referentiepatroon dekt.

| Onderwerp | Waar in de codebase |
|-----------|---------------------|
| Controller + tenant | `app/Http/Controllers/Admin/AdminUserController.php` — `TenantFilter`, `applyTenantFilter` op index; `canAccessResource($user)` op show/edit/update |
| Rechten | o.a. `view-users`, `create-users`, `edit-users` + `@can` in views |
| Overzicht | `resources/views/admin/users/index.blade.php` — stats card, filters, sort links, tabel, actiemenu, rij-klik JS |
| Detail | `resources/views/admin/users/show.blade.php` |
| Paginering | Client-side: alle gebruikers in één query (`get()`), tabel + KTDataTable; kopieertekst “Toon 1 tot X van X” |

**Kanttekening:** voor lijsten met **veel** rijen is server-side paginering vaak beter; houd dan **dezelfde** filter- en sort-querypatronen aan.

---

*Bestand sluit aan op `docs/saas-multi-tenant-prompt.md` (Admin UI + rollen/permissies + veiligheid).*
