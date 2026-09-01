# NEXA marketing / sales hub

Interne preview van verkoopcontent en one-pagers.

## Preview

- Hub: http://localhost:8085/marketing
- Alleen op centrale hosts (`localhost`, `nexasuite.nl`, …) — niet op tenant-subdomeinen.

## Assets

Afbeeldingen staan in `backend/public/assets/marketing/images/` (niet in `public/marketing/` — die mapnaam botst met de Laravel-route bij `php artisan serve`).

## 404-preview

- UI-preview (HTTP 200): http://localhost:8085/test-404
- Echte 404: willekeurige onbekende URL

## Publiceren naar nexasuite.nl

Defaults voor de centrale welkomstpagina: `CentralWelcomePageService`. Extra copy-hints: `/marketing/website-copy`.
