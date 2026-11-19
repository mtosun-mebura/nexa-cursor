# NEXA Taxi — App Design Map (bron van waarheid)

> **Altijd raadplegen** bij UI-wijzigingen in `mobile-app/` (klant én chauffeur-schermen die dezelfde brand gebruiken).  
> **Exact Pack**: `exact-pack/` + `NEXA_TAXI_CURSOR_EXACT_EXECUTION.md` (12 referenties dark/light).  
> Assets in app: `assets/nexa-booking/exact/` (pure heroes/BGs — **geen** screenshot-slices met gebakken tekst).  
> Tokens: Exact Pack `design_tokens.json` ↔ `src/customer/bookingRedesign/theme.ts`.

## Doel

Alle klantpagina’s delen dezelfde opmaak: header-scene, gloedkaarten, oranje CTA’s, full-bleed kaart, light én dark — **1:1** met Exact Pack-referenties.

## Schermen (referentie)

| Bestand | Scherm | Kern |
|---------|--------|------|
| `exact-pack/references/*/01_home_*` | Home | Pure hero + dynamische welkomsttekst, tiles, witte zoekbalk |
| `exact-pack/references/*/02_plan_ride_*` | Plan je rit | Hero taxi_scene, ophaal/bestemming (bestemming wit), route-map |
| `exact-pack/references/*/05_driver_assigned_*` | Chauffeur | Pure cityscape BG op chauffeurkaart |
| `references/02_plan_rit.jpg` | Plan je rit (legacy) | Fallback mockup |

## Assets (pure)

- Home hero: `exact/hero/{mode}/home_hero_with_car_{mode}.png`
- Plan hero: `exact/hero/{mode}/taxi_scene_with_car_{mode}.png`
- Chauffeurkaart BG: `exact/cards/{mode}/driver_card_bg_{mode}.jpg` (user cityscapes)
- Map marker: `exact/markers/nexa-map-car-marker-neon.png` (geel + neon)

## Welkomsttekst (Home)

- Alleen als **RN Text** over pure hero — nooit gebakken in de PNG.
- Terugkerend: bold “Welkom terug,” + “Goed je weer te zien…”.
- Eerste bezoek: bold “Welkom bij NEXA Taxi”.
- Groene actieve-rit banner: **onder** hero, **boven** tiles Rit boeken / Mijn ritten / Account.

## Kleur & contrast

### Dark
- Achtergrond `#061329`, surface `#102542` / `#142D4E`
- Tekst `#FFFFFF`, muted `#B9CBE4`
- Accent blauw `#0F84FF`, oranje CTA `#FF8A00`
- Kaartrand / gloed: cyan-blauw `#35B6FF`

### Light
- Achtergrond `#F4F8FF`, surface `#FFFFFF` / `#EEF4FB`
- Tekst (ink) `#0A1A34`, muted `#36506F` — **nooit** dark-mode wit op light surfaces
- Zelfde oranje/blauwe accenten; scene-foto’s met lichte wash

## Header (elke klantpagina)

- Cinematische scene (stad / route / taxi) **edge-to-edge** onder de logo-rij.
- Boeken: Exact Pack `taxi_scene_with_car` (pure).
- Titel groot + korte oranje underline; subtitel muted.
- Logo midden; appearance + taal rechts; terug links waar nodig.
- Light: zelfde asset, lichtere overlay — niet weglaten.

## Locatievelden (Plan je rit)

- Twee aparte glow-kaarten (niet platte `Card`).
- Ophaal: pin blauw, label muted, adres bold; GPS-kruis rechts.
- Bestemming: **wit vlak**, oranje pin, donkere tekst, placeholder “Waar wil je naartoe?”.
- Suggesties onder het veld, binnen dezelfde visuele familie.

## Ophaaltijd (Controleer / Boeken)

- Sectiekop met oranje calendar-icoon.
- Segment **Nu** | **Later**: actief = blauwe gloed-gradient + icoon; inactief = donkere/transparante outline.
- “Later” opent datum/tijd-picker.

## Kaart

- **Full-bleed** (volledige schermbreedte): geen smalle kaart in een padded wit/navy blok met dikke outer card-radius die “los” voelt.
- Optioneel: floating pill met afstand/tijd rechtsonder op de kaart.
- Dark map style in dark én redesign.
- Light header (Ritten/Profiel): `assets/map-header-background-light.jpg` via `NexaScreenBackdrop` — **geen grijze waas**; dark: `map-header-background.png`.

## Knoppen (primair)

- Oranje **gradient**, witte tekst, optioneel auto-icoon + chevron.
- Zachte oranje outer glow / shadow.
- Min. touch 44; `fontWeight` 800; light mode zelfde levendigheid.

## Kaarten / secties

- `NexaGlowCard` (of equivalent): texture/gradient + blauwe border glow.
- Sectiekoppen: oranje icoon + titel.
- Actieve keuzes (iDEAL, Nu, toggles): blauwe glow-border.

## Footer / tabbar

- Vast Nexa-tabbar-patroon; actief = oranje; light = witte bar met blauwe top-rand.

## Checklist voor agents

1. Open deze map + juiste `references/*.jpg` vóór UI-werk.
2. Hergebruik `theme.ts` / tokens — geen losse hex zonder token.
3. Light: `nexaSurfaceText` / `nexaSurfaceMuted` op surfaces.
4. Geen screenshot-as-UI; echte native componenten.
5. Kaarten full-bleed waar de mockup dat toont.
6. Primairy CTA altijd oranje gradient + glow, dark én light.
