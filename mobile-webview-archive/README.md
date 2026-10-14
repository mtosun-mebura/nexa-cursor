# Gearchiveerd: Capacitor WebView-shell

Deze map is de oude aanpak: een iOS/Android-omhulsel dat de **website** laadt.
Dat geeft een URL-balk/browser-gedrag — niet wat we willen.

Gebruik in plaats daarvan de echte native app:

```bash
cd ../mobile-app
open ios/NexaTaxi.xcworkspace   # Xcode
# of
npx expo run:android
```

Zie `../mobile-app/README.md`.
