# Native app — Debug vs standalone

## Twee soorten builds

| | **Debug** (ontwikkelen) | **Release / App Store** (standalone) |
|--|-------------------------|--------------------------------------|
| JS-code | Via Metro op je Mac | **Ingebakken** in de app |
| Wi‑Fi Mac nodig? | Ja | **Nee** |
| Werkt op 5G? | Alleen als Mac bereikbaar is | **Ja** |
| Backend | `EXPO_PUBLIC_API_BASE_URL` (nu productie HTTPS) | Zelfde — alleen internet naar de API |

App Store-gebruikers krijgen altijd een **Release**-build. Die heeft **geen** Metro, geen LAN-IP, geen Mac.

## Standalone op je iPhone (zoals App Store)

Het Xcode-scheme **Run** staat op **Release** (ingebakken JS). ▶ Run op je iPhone overschrijft zo niet opnieuw met Debug.

```bash
cd /Users/tosun/Projecten/nexa-saas/nexa-cursor/mobile-app
npx expo run:ios --device --configuration Release
```

Alleen voor hot-reload/debug: in Xcode Product → Scheme → Edit Scheme → Run → **Debug** (dan wél Metro nodig).

Daarna mag Metro uit; de app praat alleen met de HTTPS-API (bijv. `nexasuite.online`).

## Debug (hot reload, alleen tijdens ontwikkelen)

```bash
npm run start:ios          # simulator (zet DEVELOPER_DIR naar Xcode)
# of
npm run start -- --lan     # fysieke iPhone + Mac op zelfde Wi‑Fi
npm run ios                # native rebuild + simulator
```

Als je `unable to find utility "simctl"` ziet: Xcode Command Line Tools wijzen naar alleen CLT.
Eenmalig (wachtwoord nodig):

```bash
sudo xcode-select -s /Applications/Xcode.app/Contents/Developer
```

`npm run ios` / `npm run start:ios` zetten `DEVELOPER_DIR` al automatisch goed.

Debug is **niet** wat eindgebruikers krijgen.

## Android-emulator (deze Mac)

Zelfde Expo-app als iOS (`nl.nexasuite.taxi`). Geen tweede UI. Android Studio staat hier nog niet; installeer die eenmalig (enkele GB).

1. Installeer [Android Studio](https://developer.android.com/studio) (Apple Silicon).
2. Open Android Studio → **SDK Manager**:
   - Android SDK Platform **36** (of de versie die `npx expo run:android` vraagt)
   - Android SDK Build-Tools
   - Android SDK Platform-Tools
   - Android Emulator
3. **Device Manager** → Create Virtual Device → Phone (bijv. Pixel 8) → system image **Google Play** (niet “Google APIs” alleen) → Finish. Play is nodig voor Google Maps.
4. Zet in `~/.zshrc` (pad is de standaard SDK-locatie):

```bash
export ANDROID_HOME="$HOME/Library/Android/sdk"
export PATH="$ANDROID_HOME/emulator:$ANDROID_HOME/platform-tools:$PATH"
```

5. Start de emulator in Device Manager, of: `emulator -avd <naam>`.
6. Vanuit de app:

```bash
cd /Users/tosun/Projecten/nexa-saas/nexa-cursor/mobile-app
npm run android
```

Dat is `npx expo run:android`: prebuild van `android/` indien nodig, daarna installatie op de draaiende emulator. `mobile-app/.env.local` levert de API- en Maps-sleutel al aan (niet committen).

Kaarten blijven leeg zonder `EXPO_PUBLIC_GOOGLE_MAPS_API_KEY`. Achtergrondlocatie: in het systeemvenster **Altijd toestaan**. Weergave licht/donker wisselt het launcher-icoon via een Android activity-alias (wit of `#0B1220`); iOS doet dat met één icoon met twee appearances.

## App Store / TestFlight

In Xcode: **Product → Archive** → Distribute (TestFlight of App Store). Dat is ook Release met ingebakken JS.
