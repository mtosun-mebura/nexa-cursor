# Thema / styling – Nexa Skillmatching module

De thema-bestanden van de module staan **in de module** zodat de frontend via eigen classes kan weergeven.

## Locatie

- `Resources/theme/nexa-skillmatching.css` – module-specifieke CSS (eigen classes)

## Gebruik

- In module Blade-views: gebruik classes zoals `nexa-skillmatching-page`, `nexa-skillmatching-card`, `nexa-skillmatching-page-title`.
- In de hoofdlayout van de app kan dit bestand worden geladen wanneer de Skillmatching-module actief is (bijv. via `@push('styles')` of een link-tag naar de gepubliceerde asset).

## Publiceren (optioneel)

Om het thema in `public` beschikbaar te maken:

```bash
php artisan vendor:publish --tag=skillmatching-theme
```

(Vereist een service provider die deze tag registreert.)
