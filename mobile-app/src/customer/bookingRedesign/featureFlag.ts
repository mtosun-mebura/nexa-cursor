/**
 * NEXA Taxi boeken redesign (design-refs Nov 2026).
 * Zet op `false` om terug te vallen op de UI van vóór de redesign
 * (zie ook `src/screens/legacy/CustomerHomeScreen.pre-nexa-redesign.tsx`).
 */
export const USE_NEXA_BOOKING_REDESIGN = true;

/**
 * Marketplace quote forceert `offer_display_mode = person_range` → één prijsaanbod.
 * Scherm 03_kies_voertuig (meerdere klassen met eigen prijzen) is daarmee niet
 * betrouwbaar zonder backend-wijziging. UI toont dus géén aparte voertuigkeuze.
 */
export const NEXA_VEHICLE_PICKER_ENABLED = false;
