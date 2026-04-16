# Nexa Skillmatching module

Deze module bevat vacature matching, interviews en de bijbehorende **frontend-pagina’s** en **thema-bestanden** binnen de module, zodat alles afzonderlijk kan werken en via eigen classes kan worden weergegeven.

## Waar staat wat?

### Thema (eigen classes / styling)

- **Pad:** `Resources/theme/`
- **Bestand:** `nexa-skillmatching.css` – module-specifieke CSS (o.a. `.nexa-skillmatching-page`, `.nexa-skillmatching-card`, `.nexa-skillmatching-page-title`).
- Zie `Resources/theme/README.md` voor gebruik en eventueel publiceren.

### Frontend-pagina’s (dashboard, vacatures, matches, agenda)

- **Pad:** `Resources/views/frontend/pages/`
  - `dashboard.blade.php` – Dashboard
  - `matches.blade.php` – Matches / vacature-matching
  - `agenda.blade.php` – Agenda
  - `jobs/index.blade.php` – Vacaturesoverzicht
  - `jobs/show.blade.php` – Vacature detail

Deze views worden door de app **alleen gebruikt als ze bestaan**: de frontend controllers doen `view()->first(['skillmatching::frontend.pages.*', 'frontend.pages.*'], $data)`. Eerst wordt de module-view geladen (`skillmatching::...`), anders de view uit de hoofdapp (`resources/views/frontend/pages/`). De layout blijft die van de app (`frontend.layouts.dashboard`).

### Admin-views

- **Pad:** `Resources/views/admin/` – bestaande admin-views (vacancies, matches, interviews).

## Afzonderlijk laten werken

- **Thema:** Eigen CSS in de module; in de hoofdlayout kan dit bestand worden geladen wanneer de Skillmatching-module actief is (bijv. via `@push('styles')` of een link naar een gepubliceerde asset).
- **Frontend-pagina’s:** Zolang de module actief is en deze views in de module staan, gebruiken de routes `/dashboard`, `/jobs`, `/matches` en `/agenda` automatisch de module-views. Routes blijven in de hoofdapp; alleen de view-naam wordt via `view()->first()` bepaald.

## Namespace views

Views in deze module hebben de namespace **`skillmatching`**, bijvoorbeeld:

- `skillmatching::frontend.pages.dashboard`
- `skillmatching::frontend.pages.jobs.index`
- `skillmatching::admin.vacancies.index`

De namespace wordt geregistreerd door `ModuleServiceProvider::registerModuleViews()` (pad: `Resources/views`).
