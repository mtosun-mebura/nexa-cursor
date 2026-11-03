#!/usr/bin/env bash
# Reststappen voor remote push. Maakt geen Apple-, Google- of Expo-account aan.
# Draai dit zelf, vanuit een terminal: bash scripts/setup-push.sh
set -euo pipefail
cd "$(dirname "$0")/.."

echo "1. Inloggen bij Expo. Het account moet al bestaan."
npx eas-cli login

echo "2. Project koppelen. Dit maakt alleen een Expo-projectrecord, geen betaalplan."
npx eas-cli init

echo "3. Android: google-services.json van de Firebase-app nl.nexasuite.taxi hier neerzetten."
echo "   Dat clientbestand mag in git. De FCM service-account JSON en .p8-sleutels niet."
if [[ -f google-services.json ]]; then
  echo "   Gevonden: google-services.json"
else
  echo "   Ontbreekt nog: google-services.json"
fi

echo "4. FCM v1 service account uploaden (bestand niet committen):"
echo "   npx eas-cli credentials -p android"

echo "5. iOS APNs .p8-sleutel uploaden naar het Expo-project (geen sleutel in git):"
echo "   npx eas-cli credentials -p ios"
echo "   Device-installaties gebruiken aps-environment=development."
echo "   Alleen een App Store-archief: EXPO_PUBLIC_IOS_PUSH_ENV=production"

echo "6. Opnieuw bouwen."
echo "   Android, nadat google-services.json en FCM er zijn:"
echo "   npx expo prebuild --platform android && cd android && ./gradlew assembleDebug"
echo "   iOS Release:"
echo "   bash scripts/with-xcode.sh xcodebuild -workspace ios/NexaTaxi.xcworkspace -scheme NexaTaxi -configuration Release -destination 'generic/platform=iOS' -derivedDataPath \"\$HOME/Library/Developer/Xcode/DerivedData/NexaTaxi-bstwfyjebbophecmoheknijexamr\" -allowProvisioningUpdates build"
echo "Klaar. Test daarna op de server: php artisan taxi:push-test chauffeur@example.com"
