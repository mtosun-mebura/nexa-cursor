# NEXA Taxi — Exact uitvoeringspakket voor Cursor

## Doel
Dit pakket is bedoeld zodat Cursor de NEXA Taxi-schermen **zo exact mogelijk** kan nabouwen voor zowel **dark mode** als **light mode**.

Dit pakket bevat:
- 6 definitieve **dark-mode referenties**
- 6 definitieve **light-mode referenties**
- losse hero-afbeeldingen **met auto** en **zonder auto**
- losse kaart-/route-assets
- losse live-map assets
- losse automarker-assets voor light en dark
- design tokens voor kleur, spacing, radius en typografie
- uitvoerinstructies voor componenten, layout, scherm-flow en precieze implementatie

> Belangrijk: Cursor mag de screenshots **niet als 1 platte afbeelding in de app plaatsen**. Alle schermen moeten als echte UI-componenten worden opgebouwd. De referenties zijn de visuele waarheid; de losse assets zijn hulpmiddelen.

---

## Mappenstructuur

- `references/dark/` — 6 originele dark mode schermen
- `references/light/` — 6 light mode schermen
- `assets/hero_with_car/` — heroshots met auto
- `assets/hero_without_car/` — kale skyline/waterfront achtergronden zonder auto
- `assets/cards/background_only/` — banner/card achtergronden zonder auto
- `assets/backgrounds/` — algemene app-achtergronden en overlays
- `assets/maps/` — route preview en live tracking map achtergronden
- `assets/markers/` — automarker voor de live kaart
- `tokens/design_tokens.json` — kleur- en spacingtokens

---

## 1. Visuele bron van waarheid

### Definitieve dark-mode schermen
1. `references/dark/01_home_dark.png`
2. `references/dark/02_plan_ride_dark.png`
3. `references/dark/03_choose_vehicle_dark.png`
4. `references/dark/04_review_ride_dark.png`
5. `references/dark/05_driver_assigned_dark.png`
6. `references/dark/06_live_tracking_dark.png`

### Definitieve light-mode schermen
1. `references/light/01_home_light.png`
2. `references/light/02_plan_ride_light.png`
3. `references/light/03_choose_vehicle_light.png`
4. `references/light/04_review_ride_light.png`
5. `references/light/05_driver_assigned_light.png`
6. `references/light/06_live_tracking_light.png`

**Regel:** als tekst, spacing, component-volgorde of hiërarchie afwijkt van deze 12 referenties, dan is de implementatie niet klaar.

---

## 2. Canvas, margins en algemene layout

- Referentieformaat: **941 x 1672 px**
- Verhouding: **9:16**
- Veilige linker/rechter pagina-marge: **34 px**
- Standaard verticale ruimte tussen hoofdblokken: **18–26 px**
- Grote CTA hoogte: **108 px**
- Standaard kaart radius: **26 px**
- Invoerveld radius: **24 px**
- Kleine icon button radius: **22 px**
- Bottom navigation blokhoogte: **140 px**

### Statusbar en header
- Bovenaan iOS-statusbar: tijd links, bereik/wifi/batterij rechts.
- Header heeft gecentreerd `NEXA | TAXI` logo.
- Op scherm 2 t/m 6 linksboven een back button in een afgerond vierkant.
- Rechtsboven altijd een theme toggle (zon/maan-icoon) en Engelse vlag.

### Typografie
Gebruik het projectfont als dat al bestaat. Zo niet: **SF Pro Display** of **Inter**.

- Grote paginatitel: 58 px, weight 700
- Middentitel / sectietitel: 36–46 px, weight 700
- Grote CTA-tekst: 24 px, weight 700
- Standaard body: 18–22 px
- Kleine labels: 14–16 px

---

## 3. Kleuren en semantische tokens

Gebruik `tokens/design_tokens.json` als bron. De belangrijkste semantische kleuren:

### Dark mode
- App background: `#061329`
- Main card: `#102542`
- Raised card: `#142D4E`
- Primary orange: `#FF8A00`
- Accent blue: `#0F84FF`
- Text primary: `#FFFFFF`
- Text secondary: `#B9CBE4`
- Border: `#355A86`

### Light mode
- App background: `#F4F8FF`
- Main card: `#FFFFFF`
- Secondary surface: `#EEF4FB`
- Primary orange: `#FF8A00`
- Accent blue: `#0F84FF`
- Text primary: `#0A1A34`
- Text secondary: `#36506F`
- Border: `#D6E3F2`

### Componentregel
- **Oranje** = primaire actie of actieve booking-CTA
- **Blauw** = secundaire actie, actieve status, route, kaarten en chatknop
- **Wit/licht oppervlak** = inputvelden, cards, light mode surfaces

---

## 4. Welke losse plaatjes waarvoor?

### Hero assets MET auto
Gebruik voor schermkoppen en visuele hero-vlakken:

#### Light mode
- `assets/hero_with_car/light/home_hero_with_car_light.png`
- `assets/hero_with_car/light/taxi_scene_with_car_light.png`

#### Dark mode
- `assets/hero_with_car/dark/home_hero_with_car_dark.png`
- `assets/hero_with_car/dark/taxi_scene_with_car_dark.png`

### Hero assets ZONDER auto
Gebruik als kale achtergrondlagen als je de auto als losse layer wilt plaatsen of wanneer een card leeg moet blijven.

#### Light mode
- `assets/hero_without_car/light/waterfront_no_car_light.png`

#### Dark mode
- `assets/hero_without_car/dark/waterfront_no_car_dark.png`

### Driver/vehicle card achtergrond zonder auto
Gebruik voor kaarten zoals de chauffeur-card achtergrond wanneer auto of content er later bovenop moet komen.

- `assets/cards/background_only/light/driver_card_bg_light.png`
- `assets/cards/background_only/dark/driver_card_bg_dark.png`

### Algemene app-achtergronden
- `assets/backgrounds/light/app_background_light.png`
- `assets/backgrounds/dark/app_background_dark.png`
- `assets/backgrounds/light/top_overlay_light.png`
- `assets/backgrounds/dark/top_overlay_dark.png`

### Kaartassets
#### Route preview kaart (scherm 2)
- `assets/maps/light/route_preview_light.png`
- `assets/maps/dark/route_preview_dark.png`

#### Live tracking kaart (scherm 6)
- `assets/maps/light/live_map_light.png`
- `assets/maps/dark/live_map_dark.png`

### Automarker
- `assets/markers/light/map_car_marker_light.png`
- `assets/markers/dark/map_car_marker_dark.png`

---

## 5. Componenten die Cursor moet bouwen

Cursor moet deze componenten los bouwen en hergebruiken:

1. `NexaStatusBar`
2. `NexaTopHeader`
3. `NexaBackButton`
4. `NexaThemeToggle`
5. `NexaLanguageButton`
6. `NexaHeroSection`
7. `NexaPrimaryTileButton`
8. `NexaSearchBar`
9. `NexaCategoryRow`
10. `NexaLocationField`
11. `NexaRoutePreviewCard`
12. `NexaRecentLocationRow`
13. `NexaVehicleCard`
14. `NexaPriceBadge`
15. `NexaReviewRideCard`
16. `NexaSegmentedTimePicker`
17. `NexaPaymentOptionRow`
18. `NexaExtraOptionRow`
19. `NexaDriverAssignedCard`
20. `NexaContactActionsRow`
21. `NexaRideProgressVertical`
22. `NexaLiveMapSheet`
23. `NexaEtaSummaryCard`
24. `NexaRideProgressHorizontal`
25. `NexaBottomNav`
26. `NexaBottomCTA`

**Belangrijk:** deze componenten mogen niet hardcoded op 1 scherm zitten. Ze moeten thema-aware zijn en data-driven.

---

## 6. Schermspecs per scherm

### Scherm 1 — Home
Volg exact `references/dark/01_home_dark.png` en `references/light/01_home_light.png`.

#### Blokken
1. statusbar
2. decoratieve route-header met NEXA-logo
3. welkomsttekst met kleine oranje underline
4. hero-taxi visual
5. 3 grote action tiles: `Rit boeken`, `Mijn ritten`, `Account`
6. grote zoekbalk `Waar wil je naartoe?`
7. categoriekaart met `Luchthaven`, `Zakelijk`, `Groepsvervoer`
8. bottom navigation

#### Assetgebruik
- achtergrond: `assets/backgrounds/{mode}/app_background_{mode}.png`
- top fade: `assets/backgrounds/{mode}/top_overlay_{mode}.png`
- hero met auto: `assets/hero_with_car/{mode}/home_hero_with_car_{mode}.png` (of beste alternatief in de map)

### Scherm 2 — Plan je rit
Volg `references/*/02_plan_ride_*`.

#### Blokken
1. top header met back button en NEXA-logo
2. titel `Plan je rit`
3. begeleidende tekstregel
4. ophaallocatie veld
5. bestemming veld
6. route-preview kaart
7. `Recente locaties`
8. recente locatie rows
9. `Voeg tussenstop toe`
10. onderste oranje CTA `Bekijk ritopties`

#### Assetgebruik
- hero/header met auto: `assets/hero_with_car/{mode}/taxi_scene_with_car_{mode}.png`
- route-preview: `assets/maps/{mode}/route_preview_{mode}.png`

### Scherm 3 — Kies je rit
Volg `references/*/03_choose_vehicle_*`.

#### Blokken
1. top header
2. samenvattingskaart met pickup/destination
3. titel `Kies je rit`
4. 3 voertuigkaarten: `Standaard`, `Comfort`, `Busje`
5. onderste CTA `Ga verder`

#### Voertuigkaart-layout
- links afbeeldingvlak
- midden: titel en kenmerken
- rechts: wachttijd + prijs
- rechtsboven selectie-indicator

### Scherm 4 — Controleer je rit
Volg `references/*/04_review_ride_*`.

#### Blokken
1. top header
2. titel `Controleer je rit`
3. route strip bovenaan
4. grote review card met route en voertuig
5. `Ophaaltijd` card met `Nu` en `Later`
6. `Betaalmethode` card met `iDEAL` en `Creditcard`
7. `Extra wensen` card
8. `Zakelijke factuur?` toggle card
9. oranje CTA `Rit bevestigen`

### Scherm 5 — Je rit is bevestigd
Volg `references/*/05_driver_assigned_*`.

#### Blokken
1. top header
2. groene check en titel
3. grote chauffeurkaart met profielfoto, naam, rating, voertuig, kenteken, ETA en auto-visual
4. route-adreskaart
5. contactknoppen: `Bel chauffeur`, `Chat`
6. verticale ritstatuslijst
7. CTA `Live volgen`

#### Achtergrond chauffeurkaart
Voor de grote card kan Cursor `assets/cards/background_only/{mode}/driver_card_bg_{mode}.png` als achtergrondlaag gebruiken en daarboven de content renderen.

### Scherm 6 — Chauffeur onderweg / live volgen
Volg `references/*/06_live_tracking_*`.

#### Blokken
1. top header
2. titel `Chauffeur onderweg`
3. live map card
4. onderste bottom sheet / info-panel
5. chauffeurinfo, auto, kenteken
6. ETA card
7. horizontale ride progress
8. actierow `Bel`, `Chat`, `Deel rit`
9. bestemming kaart
10. betalingsschatting kaart

#### Assetgebruik
- live map background: `assets/maps/{mode}/live_map_{mode}.png`
- automarker: `assets/markers/{mode}/map_car_marker_{mode}.png`

---

## 7. Positierichtlijnen

Gebruik de screenshots als hoofdbron, maar hanteer deze richtlijnen als startwaarden:

### Home-screen hoofdblokken (941 px breed referentie)
- logo gecentreerd rond y = 140
- welkomsttekst start x = 59, y = 295
- hero-image start x ≈ 246, y ≈ 470
- tile buttons start y ≈ 760
- zoekkaart start y ≈ 1015
- categoriekaart start y ≈ 1190
- bottom nav start y ≈ 1512

### Ronde hoeken
- grote section card: 26 px
- tile cards: 24 px
- inputvelden: 24 px
- pill CTA / chips: 18–24 px

### Schaduwen
#### Dark mode
- subtiele outer glow of blauwe edge
- kaartschaduw zacht en donker

#### Light mode
- zachte grijze/blauwe shadow: `0 12 24 rgba(22,49,90,0.10)`

---

## 8. Functioneel gedrag voor Cursor

Cursor moet niet alleen de visuals nabouwen, maar ook de flow ondersteunen:

1. Home → Plan je rit
2. Plan je rit → Kies je rit
3. Kies je rit → Controleer je rit
4. Controleer je rit → rit aanvragen
5. bij geslaagde koppeling → `Je rit is bevestigd`
6. daarna → `Chauffeur onderweg`

### Themaregels
- handmatige toggle en system theme ondersteunen
- elk component moet kleuren uit tokens halen
- geen harde dark kleuren gebruiken in light mode

### Dataregels
- teksten, ETA, prijzen, chauffeur en kenteken moeten uit echte data kunnen komen
- design mag met demo-data starten, maar componenten moeten data-driven zijn

---

## 9. Wat Cursor NIET mag doen

- Geen hele screenshot als volledige pagina-afbeelding inladen
- Geen tekst in achtergrondbakken bak-en-fix plakken
- Geen verschillende fonts door elkaar gebruiken
- Geen nieuwe layout verzinnen “omdat het mooier lijkt”
- Geen extra knoppen toevoegen die niet in de referentie bestaan
- Geen afwijkende spacing of andere componentvolgorde gebruiken
- Geen light mode improviseren buiten de meegeleverde light referenties

---

## 10. Uitvoerinstructie voor Cursor (copy/paste)

```text
Lees eerst dit hele pakket en open daarna alle bestanden in references/dark en references/light. Gebruik die 12 schermen als de definitieve visuele waarheid. Bouw de NEXA Taxi UI als echte React Native / Expo componenten op, niet als screenshotlagen. Gebruik tokens/design_tokens.json voor theming. Gebruik de assets in assets/hero_with_car, assets/hero_without_car, assets/cards/background_only, assets/backgrounds, assets/maps en assets/markers als losse beeldlagen waar nodig. Maak gedeelde herbruikbare componenten voor header, kaarten, CTA’s, bottom navigation, locatievelden, voertuigkaarten, chauffeurkaart, progress componenten en live tracking. Zorg dat dark mode en light mode exact dezelfde layout gebruiken, met alleen correcte semantische kleur- en beeldwissels. Vergelijk na implementatie elk scherm visueel met de referenties en lever een verschillenrapport op. Meld ontbrekende originele transparante product-assets expliciet in ASSET_GAPS.md.
```

---

## 11. Aanbevolen oplevervolgorde

1. design tokens + theme provider
2. top header + bottom nav
3. home-screen
4. plan-je-rit screen
5. kies-je-rit screen
6. controleer-je-rit screen
7. rit bevestigd screen
8. chauffeur onderweg screen
9. screenshotvergelijking dark + light
10. asset gaps rapport

---

## 12. Samenvatting

Als Cursor dit pakket volgt, dan heeft het:
- alle 12 hoofdscreens
- losse achtergronden met en zonder auto
- losse live-map en route-map plaatjes
- losse automarker
- kleuren, lettertype-richtlijnen, spacing, radii en component-hiërarchie

Dat is de basis om de schermen **feilloos en consistent** uit te werken.
