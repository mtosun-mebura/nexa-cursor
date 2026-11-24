# NEXA Taxi — design assets & docs index

## Markdown (source of truth)

| Path | Role |
|------|------|
| `mobile-app/app-design-map/DESIGN_MAP.md` | Primary design map |
| `mobile-app/app-design-map/exact-pack/NEXA_TAXI_CURSOR_EXACT_EXECUTION.md` | Exact Pack execution |
| `mobile-app/app-design-map/exact-pack/README.md` | Pack overview |
| `mobile-app/app-design-map/exact-pack/tokens/design_tokens.json` | Design tokens |
| `mobile-app/app-design-map/tokens.json` | Legacy/map tokens |
| `mobile-app/src/customer/bookingRedesign/DESIGN_SPEC.md` | Implementation contract (flows) |
| `mobile-app/src/customer/bookingRedesign/ROLLBACK.md` | Feature-flag rollback |
| `mobile-app/design-refs/pixel-spec/NEXA_TAXI_CURSOR_PIXEL_SPEC/` | Pixel-spec refs (optional) |

## Screen references (compare before coding)

- Dark: `mobile-app/app-design-map/exact-pack/references/dark/01_home_dark.png` … `06_live_tracking_dark.png`
- Light: `…/references/light/01_home_light.png` … `06_live_tracking_light.png`
- Legacy JPG: `mobile-app/app-design-map/references/02_plan_rit.jpg`, `04_controleer_rit.jpg`

## Screen map header (Ritten / Meldingen / Profiel)

Bound in `mobile-app/src/customer/bookingRedesign/NexaScreenBackdrop.tsx` — **no grey wash in light**.

| Asset | Mode | Notes |
|-------|------|-------|
| `mobile-app/assets/map-header-background.png` | dark | 1536×1024 navy map |
| `mobile-app/assets/map-header-background-light.jpg` | light | 1024×768 white map, fades to `#FFFFFF` |
| Ref copy | light | `app-design-map/exact-pack/references/light/map-header-background-light.jpg` |

Light chrome on these tabs: dark ink (not `onDark`) — asset is pale. Dark mode: `chrome="onDark"`.

## Runtime assets (`mobile-app/assets/nexa-booking/exact/`)

Bind only through `mobile-app/src/customer/bookingRedesign/exactAssets.ts`.

| Folder | Use |
|--------|-----|
| `hero/{dark\|light}/home_hero_with_car_*.jpg` | Home hero (1024×769 waterfront+taxi) |
| `hero/{dark\|light}/taxi_scene_with_car_*.jpg` | Boeken / Plan hero (zelfde crop als Home) |
| Ref light | `references/light/home_hero_with_car_light.jpg` |
| Ref dark | `references/dark/home_hero_with_car_dark.jpg` |
| `hero/{dark\|light}/waterfront_no_car_*.png` | Optional scene without car |
| `cards/{dark\|light}/driver_card_bg_*.jpg` | Chauffeurkaart background |
| `markers/nexa-map-car-marker-neon.png` | HD map car (custom Marker view) |
| `markers/map_car_marker_{dark\|light}.png` | Mode-specific markers |
| `maps/{dark\|light}/live_map_*.png`, `route_preview_*.png` | Decorative map strips |
| `bg/{dark\|light}/app_background_*.png`, `top_overlay_*.png` | Screen backdrops |
| `bg/light/card_base_light.png` | Light `NexaGlowCard` fill (wit, geen grijs) |
| `ophaalcode/popup_bg_{dark\|light}.png` | Ophaalcode modal BG |

Density set for Android `Marker.image`: `mobile-app/assets/nexa-taxi-marker.png` (+ `@2x` / `@3x`).

## Shared UI modules

| Module | Role |
|--------|------|
| `theme.ts` | Colors, radius, spacing, fonts |
| `nexaHeroLayout.ts` | Shared hero chrome + taxi-right crop |
| `NexaHomeScreen.tsx` | Home |
| `NexaHeroBanner.tsx` | Boeken hero |
| `NexaCustomerTabBar.tsx` | Footer tabs |
| `NexaPrimaryButton.tsx` | Orange CTA |
| `NexaGlowCard.tsx` | Glow section cards |
| `NexaLocationField.tsx` | Pickup / destination |
| `DriverEnRouteStatusCard.tsx` | Green live status |
| `NexaPickupCodeModal.tsx` / `Button` / `Panel` | Ophaalcode |
| `exactAssets.ts` | Asset requires |
| `featureFlag.ts` | `USE_NEXA_BOOKING_REDESIGN` |

## Icons

- Prefer `@expo/vector-icons` / Ionicons in code
- Pixel-spec SVGs (reference only): `mobile-app/design-refs/pixel-spec/.../assets/icons/`
- Do not ship low-res PNG icons when a vector exists

## Sounds & motion (all roles)

| Path / module | Role |
|---------------|------|
| `mobile-app/src/ui/ArchiveMotion.tsx` | Archive / restore / delete card motion |
| `mobile-app/src/audio/rideAlert.ts` | New-ride / offer in-app tones |
| `mobile-app/assets/sounds/classic.wav` (+ chime, alert, soft, siren; `.caf` on iOS) | Tone files |
| `mobile-app/app.json` → `expo-notifications` `sounds` | Bundled notification sounds |
| `mobile-app/src/notifications/offerAlertWatch.ts` | Driver offer watch + tone |
| `mobile-app/src/notifications/driverOffers.ts` | Driver offer presentation |
| `mobile-app/src/notifications/driverOfferBackgroundTask.ts` | Background offer tone |
| `mobile-app/src/notifications/contractRides.ts` | Contractouder new-ride notify + vibrate + tone |
| `mobile-app/src/customer/rideStatusNotifications.ts` | Customer ride status pushes |
| `DriverHomeScreen` | Reference: `ArchiveMotion` + tone settings |
| `CustomerHomeScreen` | Archive keys + UI; **must wrap with `ArchiveMotion`** when changing archive UX |
| `ContractHomeScreen` | New-ride chime + pulse; reuse `ArchiveMotion` if archive is added |

## Roles checklist

| Feature | Klant | Chauffeur / contractant | Contractouder |
|---------|-------|-------------------------|---------------|
| Design tokens / brand | yes | yes (shared) | yes (shared) |
| New-ride / offer sound (`rideAlert`) | status notifs | offers + settings | `contractRides` + screen chime |
| Archive → `archive-out` | required when archive exists | implemented | required if archive exists |
| Restore → `restore-out` | required when archive exists | implemented | required if archive exists |
| Delete → `delete-out` | if permanent delete exists | implemented | if exists |
