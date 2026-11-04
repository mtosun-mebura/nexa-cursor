/**
 * Tijdelijke isolatie van chauffeur-achtergrondprocessen (spontane jetsam/SIGKILL).
 *
 * Alles op `false` = uit. Zet er één op `true` per build om de boosdoener te vinden.
 * Verwijder dit bestand weer zodra de oorzaak bekend is.
 */
export const DRIVER_BG = {
  /** Foreground GPS ticker + presence POST (tracking.ts). */
  locationTracking: true,
  /** expo-location background task / startLocationUpdatesAsync. */
  backgroundLocation: true,
  /** Inbox setInterval-poll. */
  inboxPoll: true,
  /** Zware me/vehicles/fleet/planning setInterval — nodig voor kenteken/voertuig-sync. */
  fullRefreshPoll: true,
  /** Refresh bij AppState active/background — kenteken meteen na admin-wijziging. */
  appStateRefresh: true,
  /** expo-audio prepare / rit-alarm laden. */
  rideAlertAudio: true,
  /** expo-keep-awake (scherm aan). */
  keepAwake: true,
  /** Push-token register + AppState retry. */
  pushRegister: true,
} as const;

export type DriverBgFlag = keyof typeof DRIVER_BG;
