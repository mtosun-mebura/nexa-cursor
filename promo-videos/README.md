# Nexa promo videos (Remotion)

Remotion-project voor promotievideo’s van **Nexa Suite / NexaTaxi**. Wordt aangestuurd vanuit de admin-pagina **Front-end → Promo video’s**.

## Vereisten

- Node.js 18+ (getest met Node 25)
- Chromium-afhankelijkheden voor Remotion render (macOS/Linux: zie [Remotion docs](https://www.remotion.dev/docs/miscellaneous/chrome-headless-shell))

## Installatie

```bash
cd promo-videos
npm install
```

## Preview (Remotion Studio)

```bash
npm run studio
# of: npm run dev
```

Opent Studio met compositions:

| ID | Formaat | Duur |
|----|---------|------|
| `NexaProductPromo` | 1920×1080 | 45s |
| `NexaAppTeaser` | 1080×1920 (verticaal) | 15s |

## Player voor admin (iframe)

Bouwt de embeddable Player naar `backend/public/promo-videos-player/`:

```bash
npm run player:build
```

Dev-player (los van Laravel):

```bash
npm run player:dev
# http://localhost:5179/
```

## Renderen (CLI)

```bash
# Defaults
npm run render:promo
npm run render:teaser
npm run render:all

# Met props JSON (zelfde velden als admin-formulier)
echo '{"companyName":"Demo Taxi","productName":"NexaTaxi","tagline":"...","subtitle":"...","primaryColor":"#2563eb","accentColor":"#f97316","backgroundColor":"#0b1220","ctaText":"Start nu","websiteUrl":"demo.nl","logoUrl":"images/nexa-taxi-logo.png","showAppScreens":true}' > props.json
npm run render:props -- --composition NexaProductPromo --props ./props.json --out ./out/demo.mp4
```

Of via Laravel (na `npm install` in deze map):

```bash
cd backend
php artisan promo-videos:render --composition=NexaProductPromo
```

Output landt standaard in `promo-videos/out/` en wordt gekopieerd naar `backend/storage/app/public/promo-videos/` (symlink via `php artisan storage:link`).

## Props

Bewerkbare props ( gedeeld door Studio, Player en render ):

- `companyName`, `productName`, `tagline`, `subtitle`
- `primaryColor`, `accentColor`, `backgroundColor`
- `ctaText`, `websiteUrl`, `logoUrl`, `showAppScreens`

## Structuur

```
promo-videos/
  src/
    compositions/   # ProductPromo, AppTeaser
    components/     # PhoneFrame, FeatureSlide, …
    player/         # Vite SPA voor admin-iframe
    Root.tsx
  public/images/    # Marketing + app assets
  scripts/render-with-props.mjs
```
