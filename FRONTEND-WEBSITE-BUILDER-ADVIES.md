# Frontend & Website Builder – Advies en Uitwerking

Dit document beschrijft hoe je de frontend koppelt aan het modulaire backend-systeem, een website builder inricht (basis- en module-pagina’s, logo, templates) en wat er nodig is aan data, services en eventuele plugins.

---

## 1. Uitgangspunten

| Behoefte | Oplossing |
|----------|------------|
| Frontend alleen tonen als er een actieve module is | **Visibiliteitscheck**: bij 0 actieve modules → "Coming soon" landing; bij ≥1 actieve module → volledige site (website builder + module-pagina’s). |
| Website builder voor basispagina’s | **Core pagina’s**: Home, Over Ons, Contact (+ keuze emailtemplate), plus vrije tekstpagina’s. Alles aan/uit en volgorde. |
| Logo en branding | **Site-instellingen**: logo (upload), sitenaam, evt. favicon. |
| Module-specifieke pagina’s | **Pagina’s gekoppeld aan module**: alleen in menu en routing zichtbaar als die module actief is. |
| 3 frontend-templates | **Themes**: 3 vaste templates (bijv. "Modern", "Classic", "Minimal"), elk met eigen Blade layout + instelbare opties in admin. |

De **configuratie** van de website (thema, logo, welke pagina’s, contact-template) zit standaard in de applicatie; per module komen daar alleen **extra pagina’s** bij die automatisch verborgen worden zodra de module wordt gedeactiveerd.

---

## 2. Architectuuroverzicht

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        BEZOEKER OP FRONTEND                             │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  Heeft de applicatie minstens één ACTIEVE module?                        │
│  (ModuleManager::hasAnyActiveModule())                                   │
└─────────────────────────────────────────────────────────────────────────┘
              │ NEE                                    │ JA
              ▼                                        ▼
┌──────────────────────────────┐    ┌──────────────────────────────────────┐
│  "Coming soon" landing page  │    │  Normale frontend                     │
│  - Geen menu, geen modules  │    │  - Actief theme + site-instellingen   │
│  - Tekst/CTA configureerbaar│    │  - Website builder pagina’s (actief)   │
│  - Logo uit site settings    │    │  - Module-pagina’s (alleen van        │
└──────────────────────────────┘    │    actieve modules)                  │
                                    │  - Routes: /, /over-ons, /contact,     │
                                    │    /pagina/{slug}, + module-routes    │
                                    └──────────────────────────────────────┘
```

- **Geen actieve module** → één route (bijv. `/`) die altijd de coming-soon view toont.
- **Wel actieve module(s)** → gewone frontend: theme, logo, core-pagina’s + alleen pagina’s van actieve modules.

---

## 3. Gegevensmodel (database)

### 3.1 Bestaand

- `modules` (naam, active, installed, …) – gebruik voor `hasAnyActiveModule()` en voor koppeling pagina ↔ module.
- `email_templates` – voor keuze contact-emailtemplate in website builder.

### 3.2 Nieuwe/uitgebreide tabellen

**`site_settings`** (of uitbreiden van `general_settings` indien die al bestaat)

- Doel: globale frontend-configuratie, ongeacht theme.
- Velden (voorbeeld):  
  `logo_path`, `logo_dark_path`, `site_name`, `favicon_path`, `contact_email`, `contact_email_template_id` (FK naar `email_templates.id`), `coming_soon_title`, `coming_soon_text`, `coming_soon_show_email`.

**`frontend_themes`**

- Doel: de 3 templates; alleen metadata en instelbare opties.
- Velden:  
  `id`, `slug` (bijv. `modern`, `classic`, `minimal`), `name`, `description`, `is_active` (één actief theme), `settings` (JSON: primary_color, font_heading, font_body, footer_text, …).

**`website_pages`**

- Doel: alle door de website builder beheerde pagina’s (core + custom + module).
- Velden:  
  `id`, `slug` (uniek), `title`, `content` (HTML/longtext), `meta_description`, `page_type` (enum: `home`, `about`, `contact`, `custom`, `module`), `module_name` (nullable, FK-concept naar `modules.name`; alleen bij `page_type = module`), `frontend_theme_id` (nullable; indien leeg = geldt voor alle themes), `is_active`, `sort_order`, `created_at`, `updated_at`.
- Regels:
  - Er is maximaal één actieve pagina per `page_type` voor `home`, `about`, `contact` (of je maakt `page_type` + `module_name` uniek waar relevant).
  - Pagina’s met `module_name` alleen tonen als die module actief is; anders niet in menu en niet in routing.

**`website_menu_items`** (optioneel, voor expliciete volgorde/plek)

- Of: menu volgorde afleiden uit `website_pages.sort_order` + filter op `is_active` en actieve modules.

Een minimale aanpak: geen aparte menutabel; menu = gesorteerde lijst actieve pagina’s (core + custom + actieve module-pagina’s). Later uitbreidbaar met custom menu-items of drag-and-drop.

---

## 4. Website builder – functionaliteit in admin

### 4.1 Algemeen (buiten modules)

- **Site-instellingen**
  - Logo (en evt. donkere variant), sitenaam, favicon.
  - Coming-soon teksten en of e-mailadres getoond wordt (voor als er geen actieve module is).
- **Themes**
  - Lijst van 3 themes; één actief.
  - Per theme: instellingen (kleuren, fonts, footertekst, etc.) in een formulier → opslaan in `frontend_themes.settings`.
- **Pagina’s**
  - CRUD voor `website_pages`.
  - Velden: slug, titel, content (rich text), meta_description, page_type (home / about / contact / custom / module), bij type "module" een dropdown met geïnstalleerde modules, is_active, sort_order.
  - Bij Contact-pagina: keuze **emailtemplate** (dropdown uit `email_templates`, gefilterd op type “contact” of algemeen); dit sla je op in `site_settings.contact_email_template_id` of per-page als je contact meerdere “pagina’s” zou willen (meestal volstaat één contactpagina + één gekozen template).

### 4.2 Koppeling met modules

- Pagina’s met `page_type = module` en `module_name = X` alleen tonen in admin als module X geïnstalleerd is; in frontend alleen als module X **actief** is.
- In admin bij modules: optioneel een sectie “Frontend-pagina’s” die filtert op `module_name = deze module`.

Geen extra plugins nodig voor deze logica; alleen `ModuleManager::getActiveModules()` en filter op `module_name`.

---

## 5. Frontend-routing en -weergave

### 5.1 Coming soon

- Route: `/` (of alle niet-admin routes als je alles wilt afvangen).
- Logica: `if (!ModuleManager::hasAnyActiveModule()) return view('frontend.coming-soon', $siteSettings);`
- View: één Blade-view met titel, tekst, evt. e-mail, logo uit `site_settings`. Geen menu, geen module-specifieke content.

### 5.2 Normale frontend (minstens één module actief)

- **Theme**: actief theme uit `frontend_themes`; layout kiezen op basis van `theme.slug` (bijv. `layouts.themes.modern`, `layouts.themes.classic`, `layouts.themes.minimal`).
- **Routes**:
  - `/` → homepage (eerste actieve pagina met `page_type = home`).
  - `/over-ons` (of slug uit page) → about.
  - `/contact` → contact (formulier + bij submit: email via gekozen `contact_email_template_id`).
  - `/pagina/{slug}` of `/{slug}` voor overige actieve custom-pagina’s.
  - Module-routes (bijv. `/vacatures`, `/jobs`) blijven zoals nu geregistreerd door de module, **alleen** als die module actief is (dat doe je al via ModuleServiceProvider / route registration).

Een centrale **FrontendRouter** of **WebsitePageController** kan voor alle “website builder”-pagina’s de juiste page ophalen (op slug of type), theme toepassen en de bijbehorende Blade view renderen (bijv. één generieke `page.show` view die `$page->content` en theme gebruikt).

### 5.3 Contactformulier en emailtemplate

- Contactpagina toont het bestaande contactformulier.
- Bij submit: niet hardcoded `emails.contact`, maar template ophalen via `site_settings.contact_email_template_id` (of gekozen template op de contact-pagina) en met `EmailTemplateService` (of bestaande mail-logica) de mail versturen met die template. Variabelen (naam, email, bericht) blijven hetzelfde; alleen de template is configureerbaar.

---

## 6. Drie templates – technische invulling

- **Drie vaste themes** in code:
  - Bijv. `resources/views/frontend/themes/modern/`, `classic/`, `minimal/` met elk een `layout.blade.php` en evt. subviews (header, footer, hero).
- **Eén actief theme** in DB; in die layout lees je `theme.settings` (kleuren, fonts) en geef je die door aan de view (of een kleine Blade/JS-helper voor CSS variables).
- Geen aparte “theme builder” nodig: de **configuratie** (kleur, font, logo, footer) is wat je in admin aanpast; de **structuur** (plaats van logo, menu, content) zit in de Blade-templates.

Optioneel: per theme een **preview-afbeelding** in admin zodat beheerders kunnen kiezen op basis van uiterlijk.

---

## 7. Benodigde onderdelen in de applicatie

| Onderdeel | Toelichting |
|-----------|-------------|
| **ModuleManager::hasAnyActiveModule()** | Nieuwe methode: `count(getActiveModules()) > 0`. |
| **SiteSettings** (model + repo of service) | Ophalen/opslaan logo, sitenaam, contact template, coming-soon teksten. |
| **FrontendTheme** model | Met `getActive()` en `getSettings()`. |
| **WebsitePage** model | Met scopes `active()`, `forModule($name)`, `corePages()`. |
| **WebsiteBuilderService** (of PageService) | `getHomePage()`, `getPageBySlug($slug)`, `getActiveMenuPages()`, `getContactEmailTemplateId()`. |
| **Middleware of basis-controller** | Bovenop frontend-routes: als geen actieve module → redirect/coming-soon; anders doorlaten. |
| **Admin controllers + views** | Site-instellingen, themes (edit settings), website-pagina’s CRUD. |
| **Frontend controllers** | Coming-soon view; één controller voor “website builder”-pagina’s (home, about, contact, custom slug). Contact submit blijft bestaande ContactController, maar mail-template uit settings. |

Geen verplichte **externe** CMS- of page-builder plugins; alles kan met Laravel + Blade + bestaande module-structuur.

---

## 8. Optionele plugins en tools

| Behoefte | Optie | Opmerking |
|----------|--------|-----------|
| **Logo/bestanden opslaan** | Laravel storage + `Storage::url()` | Eenvoudig; geen package nodig. |
| **Mediamanagement** | `spatie/laravel-medialibrary` | Handig als je meerdere afbeeldingen per pagina of per site wilt. |
| **Rich text in admin** | Tiptap, CKEditor, Quill, of Filament/Backpack form | Alleen voor `website_pages.content`; geen volledige page-builder. |
| **Settings (key-value)** | `spatie/laravel-settings` of eigen `site_settings` tabel | Spatie als je veel losse keys wilt; anders één rij in `site_settings` met JSON of kolommen. |
| **Sitemap/SEO** | Zelf genereren of `spatie/laravel-sitemap` | Optioneel; handig als je veel pagina’s hebt. |

Aanbeveling: start **zonder** extra packages; voeg Spatie Media Library of Settings alleen toe als je merkt dat je meerdere logo’s, varianten of veel losse instellingen wilt.

---

## 9. Uitbreiding Base Module (frontend)

In `App\Modules\Base\Module` kun je optioneel definiëren:

- `getFrontendPages()`: retourneert een lijst van “standaard” pagina-definities voor deze module (slug, titel, page_type `module`), zodat bij installatie van de module automatisch een of meer `website_pages`-rijen kunnen worden aangemaakt.
- `getFrontendRoutes()`: heb je al; gebruik dit om module-specifieke URLs (bijv. `/vacatures`) te koppelen aan dezelfde module-activatie.

De website builder blijft de bron van waarheid voor **content** (titels, body); de module levert alleen de **definitie** (welke pagina’s bij welke module horen) en de **routes**.

---

## 10. Implementatievolgorde (fases)

1. **Fase 1 – Coming soon + visibiliteit**
   - `ModuleManager::hasAnyActiveModule()`.
   - `site_settings` (of gelijkwaardig) met coming-soon velden + logo.
   - Eén coming-soon Blade-view en route `/` die deze toont als er geen actieve module is.
   - Middleware of check in bootstrap: voor `/` (en evt. alle publieke routes) eerst deze check.

2. **Fase 2 – Themes en site-instellingen**
   - Migratie `frontend_themes` + seed voor 3 themes.
   - Model `FrontendTheme`; actief theme kiezen in admin.
   - Site-instellingen (logo, sitenaam, contact template) in admin bewerkbaar.
   - Frontend: layout kiezen op basis van actief theme; theme settings (kleur, font) doorgeven.

3. **Fase 3 – Website pagina’s (core)**
   - Migratie `website_pages`.
   - Model `WebsitePage` + `WebsiteBuilderService` (of `PageService`).
   - Admin: CRUD pagina’s met types home, about, contact, custom; is_active, sort_order.
   - Contact: koppeling met `email_templates` (dropdown in admin); ContactController aanpassen om gekozen template te gebruiken.
   - Frontend: routing voor `/`, `/over-ons`, `/contact`, `/pagina/{slug}`; generieke page-view met actief theme.

4. **Fase 4 – Module-pagina’s en menu**
   - `website_pages.module_name` en type `module`; in admin bij pagina’s module kiezen (alleen geïnstalleerde modules).
   - Menu: lijst van actieve pagina’s (waarvan module-pagina’s alleen als die module actief is).
   - Optioneel: `getFrontendPages()` op Base Module en seeden bij module-installatie.

5. **Fase 5 – Drie templates uitwerken**
   - Drie theme-mappen met layout, header, footer; styling (CSS variables) uit `theme.settings`.
   - Admin: per theme instellingenformulier (kleuren, fonts, footertekst).
   - Testen dat wisselen van theme direct zichtbaar is op de frontend.

6. **Fase 6 – Afronding**
   - Logo-upload in site-instellingen.
   - Rich-text editor voor pagina-content in admin.
   - Permissions voor website builder (bijv. `manage-site-settings`, `manage-website-pages`).

---

## 11. Samenvatting

- **Geen actieve module** → alleen coming-soon landing (configureerbaar, met logo).
- **Wel actieve module(s)** → volledige frontend: 3 themes (configureerbaar), website builder-pagina’s (Home, Over Ons, Contact met keuze emailtemplate, extra tekstpagina’s), plus module-pagina’s die automatisch verborgen worden als de module uitstaat.
- **Technisch**: bestaande Laravel/Blade-stack; nieuwe tabellen `site_settings`, `frontend_themes`, `website_pages`; één WebsiteBuilderService en uitbreiding ModuleManager; geen verplichte externe page-builder of CMS.
- **Optioneel**: Spatie Media Library of Settings, en een rich-text editor voor pagina-content.

Als je wilt, kan de volgende stap zijn: concrete migraties en methodesignatures (ModuleManager, WebsiteBuilderService, SiteSettings) in code uitwerken in deze repo.
