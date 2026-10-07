# Nexa Taxi — echte native app (iOS + Android)

Dit is **geen website in een WebView**. React Native / Expo met eigen schermen:

- Native login (e-mail + wachtwoord of code) — geen browser, geen URL-balk
- Rollen uit de API → chauffeur / contract / marktplaats / netwerk
- Chauffeur: online-toggle, inbox, accepteren/weigeren
- Achtergrondlocatie via iOS/Android Location services (marktplaats-matching)

De oude Capacitor-WebView ligt in `../mobile-webview-archive` (niet gebruiken).

## Vereisten

- Node 20+
- Xcode 16+ (fysiek iPhone voor locatie/achtergrond)
- Android Studio
- CocoaPods

## Installeren

```bash
cd mobile-app
npm install
```

API-server (productie standaard):

```bash
# of lokaal, zelfde Wi-Fi als je telefoon:
export EXPO_PUBLIC_API_BASE_URL=http://192.168.x.x:8085
```

Of pas `extra.apiBaseUrl` aan in `app.json`.

## Native projecten genereren (Xcode / Android Studio)

```bash
cd mobile-app
npx expo prebuild --clean
```

Dit maakt echte mappen `ios/` en `android/`.

### iOS → TestFlight

```bash
npx expo run:ios
# of:
open ios/NexaTaxi.xcworkspace
```

In Xcode: Team kiezen → Run op iPhone → Product → Archive → TestFlight.

Locatie: kies **Altijd** bij de systeemvraag (nodig voor marktplaats op de achtergrond).

### Android → APK/AAB

```bash
npx expo run:android
# of open android/ in Android Studio → Build Bundle/APK
```

## Development

```bash
npx expo start
```

Voor achtergrondlocatie heb je een **development build** of `prebuild` + native run nodig (Expo Go ondersteunt background location beperkt).

## API

Gebruikt:

- `POST /api/taxi/v1/app/login`
- `POST /api/taxi/v1/app/login-code/request|verify`
- `GET/PUT /api/taxi/v1/driver/*`
