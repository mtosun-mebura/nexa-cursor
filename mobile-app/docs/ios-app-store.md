# Nexa Taxi → Apple App Store (iOS)

How-to voor publiceren in de Apple App Store (oktober 2026). Google Play is een aparte gids.

**Huidige app-status (uit de repo)**  
- Naam: **Nexa Taxi** · slug `nexa-taxi` · scheme `nexataxi`  
- Bundle ID: **`nl.nexasuite.taxi`**  
- Versie: **`1.0.0`** · iOS buildNumber: **`1`**  
- Expo SDK: **57** (`expo ~57.0.27`)  
- **EAS:** `eas.json` staat klaar; **geen** `projectId` / `ascAppId` tot na `eas init` + App Store Connect-app  
- Geen vaste `ios/`-map in git: native project komt via `expo prebuild` / EAS  
- Locatie-permissionsteksten staan al in `app.json` (o.a. When In Use + Always + background)  
- Betalingen lopen via **Mollie** (browser/deeplink), niet via Apple IAP  
- Push op iOS staat in config uit tot je betaald Developer-account + `EXPO_PUBLIC_IOS_PUSH=1` hebt  

Alle commands hieronder vanaf:

```bash
cd /Users/tosun/Projecten/nexa-saas/nexa-cursor/mobile-app
```

---

## 1. Vereisten (eenmalig + per release)

### Eenmalig

1. **Apple ID** (persoonlijk of zakelijk e-mailadres).  
2. **Apple Developer Program** — ~**€99/jaar**. Zonder dit: geen App Store, geen TestFlight voor externe testers, geen betrouwbare productie-signing/push.  
   - Besluit: **Individual** (jij als persoon) of **Organization** (bedrijfsnaam, D-U-N-S, juridische entiteit). Voor Nexa Suite/commercieel gebruik is **Organization** meestal beter (listing onder bedrijfsnaam).  
3. **Mac + Xcode** (jij hebt dit al) — nodig voor lokale Archive-route; bij EAS Build optioneel.  
4. **Expo-account** op [expo.dev](https://expo.dev) (gratis tier volstaat om te starten).  
5. **Privacy policy-URL** (verplicht in App Store Connect) — publieke HTTPS-pagina. In de app/repo staat nog **geen** privacybeleid-URL.  
6. **Support-URL** — pagina of e-mailadres waar gebruikers terechtkunnen (ook verplicht).  
7. **Juridische/bedrijfsnaam** voor de listing, copyright, contact.  
8. **App-icoon** — staat al (`assets/icon.png` / dark). Check of 1024×1024 zonder alpha goed is voor App Store Connect.  
9. **Screenshots** (zie onder) — zelf maken op simulator/device.  
10. **Age rating** + **App Privacy** (“nutrition labels”) invullen in Connect.  
11. **Demo-login** voor App Review (chauffeur én/of klant), werkend tegen productie-API (`https://nexasuite.online`).

### Per release

- Versie en/of **buildNumber** verhogen  
- Nieuwe binary bouwen + uploaden  
- Release notes (NL, optioneel EN)  
- Eventueel TestFlight-ronde  
- Opnieuw indienen voor review bij inhoudelijke wijzigingen  

### Screenshots (kort)

Apple vraagt o.a. iPhone 6.7" en/of 6.5" (en vaak 5.5"). Maak realistische schermen: login, klant-rit/kaart, chauffeur-inbox, offline/online. Geen marketing-frames die de UI verbergen. Minimaal 1 set iPhone; tablet niet nodig (`supportsTablet: false`).

### App Privacy

In Connect geef je aan welke data je verzamelt (locatie, account, foto’s, apparaat-ID’s voor push, etc.). Wees eerlijk: deze app gebruikt **locatie (ook achtergrond)**, login, foto’s (profiel/auto), notificaties.

---

## 2. App Store Connect — app-record aanmaken

1. Ga naar [App Store Connect](https://appstoreconnect.apple.com) → **Mijn apps** → **+** → Nieuwe app.  
2. Platform: **iOS**.  
3. Naam: **Nexa Taxi** (of de definitieve winkenaam; max. lengte let op).  
4. Primaire taal: **Nederlands**.  
5. Bundle ID: kies of maak **`nl.nexasuite.taxi`** (Identifiers in [developer.apple.com](https://developer.apple.com) → Certificates, Identifiers & Profiles → Identifiers, als die nog niet bestaat).  
6. SKU: intern, bijv. `nexa-taxi-ios`.  
7. Gebruikersrechten: Full Access (tenzij je rollen wilt splitsen).

### Metadata (NL, optioneel EN)

- **Omschrijving**, keywords, promotekst, “wat is nieuw”.  
- **Categorieën**: bijv. Reis / Navigatie (of Lifestyle) — kies wat het best past.  
- **Prijs**: waarschijnlijk **Gratis**. In-app aankopen alleen als je later digitale goederen/abonnementen via Apple verkoopt (nu: Mollie voor ritten — zie §7).  
- **Privacy policy-URL** + **Support-URL**.  
- **Copyright**, contact-e-mail.  
- **Age rating**-vragenlijst invullen.  
- **App Privacy**-labels.

**Besluit:** listing eerst alleen NL, of NL + EN vanaf dag 1.

---

## 3. Signing & build-pipeline

Twee routes. **Aanbevolen: EAS** (past bij Expo; signing in de cloud). Alternatief: lokale Xcode Archive (zoals in `START.md` / `README.md`).

### Route A — EAS Build + EAS Submit (aanbevolen)

`eas.json` staat klaar; Expo-projectkoppeling (`projectId`) nog niet. Eenmalig:

```bash
cd /Users/tosun/Projecten/nexa-saas/nexa-cursor/mobile-app
npx eas-cli@latest login
npx eas-cli@latest init
```

`eas init` koppelt het Expo-project en schrijft `extra.eas.projectId` in de config (of je zet `EXPO_PUBLIC_EAS_PROJECT_ID` — `app.config.js` leest die al). **Vul geen fake projectId in.**

**`eas.json`** staat al in de repo met:

| Profile | Doel |
| --- | --- |
| `development` | Dev client, internal, iOS Simulator |
| `preview` | Internal device builds (ad-hoc / interne testers) |
| `production` | Store builds; `autoIncrement` + `cli.appVersionSource: remote` |

`submit.production.ios` heeft **nog geen** `ascAppId` (JSON heeft geen comments). Na aanmaken van de app in App Store Connect, zet in `eas.json`:

```json
"submit": {
  "production": {
    "ios": {
      "ascAppId": "1234567890"
    },
    "android": {
      "track": "internal"
    }
  }
}
```

Het numerieke App-ID staat in App Store Connect → App-informatie. Android `track: internal` is klaar voor Play later; nog niet submitten.

Credentials (certificaat + provisioning):

```bash
npx eas-cli@latest credentials
```

Kies iOS → laat EAS credentials beheren (eenvoudigste), of lever eigen certificaten aan. Hiervoor moet je **Apple Developer Program**-lid zijn en inloggen met het Apple-team.

**Push (productie):** met betaald team kun je push aanzetten. Voor store-builds met APNs:

```bash
export EXPO_PUBLIC_IOS_PUSH=1
```

(zonder dit blijft `aps-environment` weg — handig voor gratis/personal team, **niet** ideaal voor productie-notificaties).

### Route B — Xcode Archive (zonder EAS)

```bash
cd /Users/tosun/Projecten/nexa-saas/nexa-cursor/mobile-app
npm install
npx expo prebuild --platform ios --clean
open ios/*.xcworkspace
```

In Xcode: Team = je Apple Developer-team · Signing Automatic · Bundle ID `nl.nexasuite.taxi` · **Product → Archive** → Distribute App → App Store Connect.

Zorg dat Release-configuratie JS inbakkt (geen Metro) — zie `START.md`.

---

## 4. Productie-binary bouwen (versie / buildNumber)

Huidig in `app.json`:

- `version`: **`1.0.0`** (gebruikerszichtbare versie)  
- `ios.buildNumber`: **`1`** (moet uniek omhoog bij elke upload)

**Bump vóór elke nieuwe upload** (handmatig als je geen `autoIncrement` gebruikt):

- Zelfde feature-release: alleen `buildNumber` → `"2"`, `"3"`, …  
- Nieuwe store-versie: `version` → `"1.0.1"` / `"1.1.0"` én nieuw buildNumber  

Met EAS production + `autoIncrement: true` kan buildNumber remote omhooggaan; `version` bump je zelf in `app.json` wanneer je een nieuwe marketingversie wilt.

### EAS productie-build

```bash
cd /Users/tosun/Projecten/nexa-saas/nexa-cursor/mobile-app
# Zorg dat productie-API klopt (standaard in app.json):
# extra.apiBaseUrl = https://nexasuite.online
# Of: export EXPO_PUBLIC_API_BASE_URL=https://nexasuite.online

export EXPO_PUBLIC_IOS_PUSH=1   # als push in productie moet werken
npx eas-cli@latest build --platform ios --profile production
```

Wacht tot de build klaar is in het Expo-dashboard. Download de IPA of submit direct (stap 5).

### Lokale Release-check op device (optioneel, vóór store)

```bash
npx expo run:ios --device --configuration Release
```

---

## 5. Uploaden naar App Store Connect

**Via EAS Submit** (na geslaagde production-build):

```bash
npx eas-cli@latest submit --platform ios --profile production --latest
```

**Via Transporter:** download IPA van EAS → open Transporter → upload.  

**Via Xcode Organizer:** na Archive → Distribute → App Store Connect.

Na upload: in Connect onder de build even wachten tot verwerking klaar is (minuten tot ~uur).

---

## 6. TestFlight (vóór review)

1. Connect → je app → **TestFlight**.  
2. Koppel de verwerkte build.  
3. **Interne testers** (tot 100, zelfde team) — snel, geen Beta App Review.  
4. **Externe testers** — eerste keer vaak korte Beta App Review; daarna sneller.  
5. Test kritisch: login, locatie (When In Use + Always), achtergrond voor chauffeurs/marktplaats, Mollie-betaalflow + return via `nexataxi://`, push (als aanzet), dark/light icoon.

Pas indienen voor App Review als TestFlight stabiel is.

---

## 7. Indienen voor App Review

In Connect: versie aanmaken → build selecteren → metadata compleet → **Ter beoordeling indienen**.

### Wat Apple vaak vraagt bij taxi / marktplaats

| Onderwerp | Wat jij doet |
|-----------|----------------|
| **Demo-account** | In “Opmerkingen voor de reviewer”: e-mail + wachtwoord (of login-code-flow) voor **klant** én **chauffeur**, klaar op productie. |
| **Locatie** | Purpose strings staan al in `app.json` / Info.plist. In review-notes kort uitleggen: ophaaladres, ritten in de buurt, matching. |
| **Achtergrondlocatie** | Aan (`UIBackgroundModes` + Always). Apple is streng: leg uit **waarom Always nodig is** (marktplaats-matching / chauffeur-tracking terwijl app op achtergrond). Alleen “nice to have” = afwijzing-risico. |
| **Foto/camera** | Strings aanwezig; leg kort uit (profiel, logo, auto). |
| **Betalingen (Mollie)** | Ritten = **dienst in de echte wereld** (vervoer). Zulke marketplaces vallen vaak onder uitzonderingen t.o.v. verplichte IAP (reader / physical goods & services). **Verifieer dit zelf** tegen de actuele [App Store Review Guidelines](https://developer.apple.com/app-store/review/guidelines/) (§3.1.x); dit is **geen juridisch advies**. Digitale abonnementen/credits in de app zouden wél IAP kunnen vereisen. |
| **Login verplicht** | Account voor kernfunctionaliteit is normaal; geef werkende demo-credentials. |
| **Encryptie** | `ITSAppUsesNonExemptEncryption: false` staat al — bij exportvragen meestal “standard encryption only”. |

Zet in review-notes ook: deep link-scheme `nexataxi`, dat betalen via Mollie in de browser/terugkeer in de app gebeurt, en hoe de reviewer een test-rit kan starten.

---

## 8. Na goedkeuring & volgende update

1. Bij goedkeuring: **handmatig vrijgeven** of **automatisch** na goedkeuring (keuze in Connect).  
2. Check live: download uit de Store, login, één rit-flow.  
3. **Volgende update:**  
   - Bump `version` en/of `buildNumber` in `app.json`  
   - `eas build --platform ios --profile production`  
   - `eas submit …` of Transporter  
   - TestFlight → Submit for Review  
   - Release notes bijwerken  

Kleine JS-only fixes kun je later met **EAS Update** overwegen; native wijzigingen (permissions, SDK, plugins) vereisen altijd een nieuwe binary.

---

## Store-readiness checklist (blockers — nu niet gefixt in code)

- [ ] Apple Developer Program ingeschreven (Individual vs Organization)  
- [ ] Bundle ID `nl.nexasuite.taxi` in Apple Developer + app-record in Connect  
- [ ] Privacy policy-URL + support-URL live  
- [ ] Screenshots + beschrijving (NL)  
- [ ] App Privacy + age rating  
- [ ] `eas login` + `eas init` (+ `ascAppId` in `eas.json` submit.production.ios)  
- [ ] Demo-accounts voor review  
- [ ] Besluit: push in productie (`EXPO_PUBLIC_IOS_PUSH=1` + APNs)  
- [ ] Google Maps-sleutel voor store-builds (`EXPO_PUBLIC_GOOGLE_MAPS_API_KEY`) indien kaarten leeg blijven zonder  
- [ ] Achtergrondlocatie-justificatie scherp geformuleerd voor review  
- [ ] Mollie/IAP-uitzondering zelf tegen Apple-guidelines gecheckt  

**Al OK in config:** display name, bundle id, version/buildNumber startwaarden, locatie-/foto-/camera-usage descriptions, non-exempt encryption flag.

---

### Volgende keer: Google Play

Zelfde Expo-app (`nl.nexasuite.taxi`, `versionCode: 1`). Dan: Google Play Console-account, privacy, AAB via `eas build --platform android --profile production`, Data safety, en eventueel achtergrondlocatie-declaratie op Android. Vraag ernaar als iOS live of in review staat.