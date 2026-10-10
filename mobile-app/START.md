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
npx expo start --lan   # fysieke iPhone + Mac op zelfde Wi‑Fi
# of
npx expo start --localhost   # alleen simulator
```

Debug is **niet** wat eindgebruikers krijgen.

## App Store / TestFlight

In Xcode: **Product → Archive** → Distribute (TestFlight of App Store). Dat is ook Release met ingebakken JS.
