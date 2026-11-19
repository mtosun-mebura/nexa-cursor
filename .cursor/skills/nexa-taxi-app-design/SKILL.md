---
name: nexa-taxi-app-design
description: >-
  Builds and updates the full NEXA Taxi Expo app UX: design map, Exact Pack,
  theme tokens, headers/footers, CTAs, cards, heroes, markers, i18n, new-ride
  alert sounds, archive/restore/delete card motions, notifications. Use for any
  mobile-app change for customer (klant), driver/contractant, or contract parent
  (contractouder), including Ritten/Archief, offers, tones, AppModal, and tab bars.
---

# NEXA Taxi — App Design & Effects

Mandatory for **any** screen or interaction change in `mobile-app/`, for **all roles** that have the feature:

| Role | Screens (typical) |
|------|-------------------|
| Klant | `CustomerHomeScreen`, booking redesign under `bookingRedesign/` |
| Chauffeur / contractant | `DriverHomeScreen`, offers, archive |
| Contractouder / contract | `ContractHomeScreen`, contract ride alerts |

Do **not** invent a second animation or sound system. Reuse the shared modules below.

## Before coding (always)

1. Read **`mobile-app/app-design-map/DESIGN_MAP.md`**.
2. Read Exact Pack **`mobile-app/app-design-map/exact-pack/NEXA_TAXI_CURSOR_EXACT_EXECUTION.md`**.
3. Open matching refs under **`app-design-map/exact-pack/references/{dark|light}/`**.
4. Tokens: **`bookingRedesign/theme.ts`** ↔ Exact Pack `design_tokens.json`.
5. Visual assets via **`exactAssets.ts`** → `assets/nexa-booking/exact/`.
6. Prefer existing shared UI (`ArchiveMotion`, `rideAlert`, `AppModal`, tab bars, glow cards).

Index: [ASSET_INDEX.md](ASSET_INDEX.md).

---

## Motions & alerts (mandatory shared)

### Nieuwe rit / nieuwe aanbieding

| Piece | Source of truth |
|-------|-----------------|
| In-app tone | `mobile-app/src/audio/rideAlert.ts` — `playRideAlertTone` / `playRideAlertToneAsync` |
| Tone set | `classic`, `chime` (default Bel), `alert`, `soft`, `siren` |
| Files | `mobile-app/assets/sounds/*.{wav,caf}` (bundled in `app.json`) |
| Driver offers | `notifications/offerAlertWatch.ts`, `driverOffers.ts`, background task |
| Contract new rides | `notifications/contractRides.ts` — `notifyNewContractRide` (tone + vibrate + notification) |
| Contract pulse | `ContractHomeScreen` — `playRideAlertTone('chime')` + row pulse on new/arrived items |

**Rules**

- Every role that surfaces a “nieuwe rit / nieuwe offer” uses **`rideAlert`** (same catalog). Prefer user-selected tone via `setActiveRideAlertTone` / settings; default `chime`.
- Do not hardcode a parallel `Audio` player or one-off MP3.
- Background / locked-phone: keep existing arming in `rideAlert` + notification channel sound helpers (`rideAlertNotificationSound`).

### Archiveren / terugzetten / verwijderen

| Piece | Source of truth |
|-------|-----------------|
| Card motion | **`mobile-app/src/ui/ArchiveMotion.tsx`** |
| Modes | `archive-out` (pakketje naar archief), `restore-out` (terug naar lijst), `delete-out` (oplossen), `idle` |
| A11y | Honors Reduce Motion → skip animation, still call `onFinished` |
| Accent | `accentColor` (theme primary / blue); archive & restore badges use Ionicons |

**Rules (all roles)**

1. If a list supports **Naar archief** / **Terugzetten uit archief** / permanent delete, wrap the ride/offer **row or card** in `ArchiveMotion`.
2. Flow: set `mode` → play motion → on `onFinished` perform the data mutation (archive/restore/delete). Do not flip list data before the motion finishes (same pattern as `DriverHomeScreen` `archiveRowMotion` / `finishArchiveRowMotion`).
3. **Klant** (`CustomerHomeScreen` `toggleArchiveRide`): must use `ArchiveMotion` the same way — do not only toggle AsyncStorage without the shared motion when building or changing archive UX.
4. **Contractouder / contractant**: if/when archive appears in contract UI, reuse `ArchiveMotion` + same modes; never a custom slide.
5. Reference implementation: `DriverHomeScreen` (completed rides + archive selection restore/delete).

### Other app motion

- Modals: `AppModal` `enterAnimation="zoom"` where product already uses it; blur overlay always (`mobile-app-modal-blur`).
- Live map / ETA shimmer: keep existing Animated patterns in customer live UI; don’t replace archive/new-ride systems with them.

---

## Screen chrome (customer Home + Boeken)

| Element | Rule |
|---------|------|
| Safe area | `SafeAreaView` edges `left/right`; heroes/tabbar own insets |
| Hero | Full-bleed to top; chrome on image |
| Logo + theme/flag | Same row (`nexaHeroLayout.ts`) |
| Home ↔ Boeken | Same crop (`nexaHeroImageStyle`, taxi right); Book scroll `paddingTop: 0` |
| Footer | `NexaCustomerTabBar` color through home-indicator |
| Shell bg | Match footer (no black gap) |

Driver/contract: keep their tab bars (`DriverTabBar`, `ContractTabBar`) with `tabBarBottomPadding`; same “footer color to bottom” idea.

### Map header (Ritten / Meldingen / Profiel)

| Mode | Asset | Chrome |
|------|-------|--------|
| Dark | `assets/map-header-background.png` | `onDark` (white title/icons) |
| Light | `assets/map-header-background-light.jpg` | theme/ink (dark title) — **no grey wash** |

Component: `NexaScreenBackdrop.tsx`. See [ASSET_INDEX.md](ASSET_INDEX.md).

---

## Colors, buttons, cards, map, modals

- Colors/tokens: `theme.ts` — light ink via `nexaSurfaceText` / `nexaSurfaceMuted`
- Primary CTA: `NexaPrimaryButton`; book label **`Boek de rit`**
- Cards: `NexaGlowCard`; destination often white / no outer border when product asks
- Map Book badge: never “Taxi onderweg” (live UI only)
- Marker: `nexa-taxi-marker` density set + HD neon asset; no circle unless asked
- Modals: `AppModal` + blur; ophaalcode full-bleed `popup_bg_*`

---

## i18n

- Every visible NL string → `en.ts`
- Archive copy: `Naar archief`, `Terugzetten uit archief`, `Archief`, etc. already keyed — reuse `t(...)`

---

## Do not

- Screenshot-as-UI / baked chrome in PNGs
- Parallel palettes, sounds, or archive animations per role
- Skip `ArchiveMotion` on customer/contract when archive UX exists
- Black gaps above hero / below tabbar
- Auto-open ophaalcode

---

## After changes

- Light + dark; NL + EN
- Smoke-test: new-ride tone (driver/contract), archive-out + restore-out (any role with archive)
- Device build when user asks (Release → iPad via `with-xcode.sh` / `devicectl`)

## Related

- Rule: `.cursor/rules/nexa-taxi-app-design-map.mdc`
- Rules: `mobile-app-*.mdc`, `mobile-app-modal-blur.mdc`
