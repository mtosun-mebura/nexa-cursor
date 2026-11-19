# Rollback Taxi-boeken UI

Als de NEXA pixel-spec redesign niet bevalt:

1. Zet in `src/customer/bookingRedesign/featureFlag.ts` → `USE_NEXA_BOOKING_REDESIGN = false`
   OF
2. Vervang `src/screens/CustomerHomeScreen.tsx` door:
   `src/screens/legacy/CustomerHomeScreen.pre-nexa-redesign.tsx`

Pixel-spec bron: `design-refs/pixel-spec/NEXA_TAXI_CURSOR_PIXEL_SPEC/`.
Voertuigkeuze (scherm 03) blijft uit (`NEXA_VEHICLE_PICKER_ENABLED = false`).
