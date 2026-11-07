import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Location from 'expo-location';
import { AppState, Platform } from 'react-native';
import * as TaskManager from 'expo-task-manager';
import { resolveApiBaseUrl } from '../config';
import { DRIVER_BG } from '../debug/driverBgKillSwitch';
import { t } from '../i18n';
import { secureDelete, secureGet, secureSet } from '../storage/secureStoreSafe';

/** Shown only after iOS/Android will no longer present the system prompt. */
export const LOCATION_SETTINGS_HINT =
  'Locatie staat uit. Zet locatie aan in Instellingen om online te gaan.';

export const LOCATION_REQUIRED_HINT = 'Locatietoestemming is verplicht om online te gaan.';

/**
 * Remember that we already showed the Always / all-the-time system prompt once
 * (or opened Android's location settings). A later Online toggle must not nag.
 * New key: older builds set a flag without ever calling requestBackgroundPermissionsAsync.
 */
const BACKGROUND_UPGRADE_ASKED_KEY = 'nexa.taxi.backgroundLocationUpgradeAsked.v2';
let backgroundUpgradeAsked = false;

export const LOCATION_TASK = 'nexa-taxi-driver-location';
const TOKEN_KEY = 'nexa_taxi_location_token';
const VEHICLE_KEY = 'nexa_taxi_location_vehicle';

/**
 * Eén achtergrond-run tegelijk. De handler zelf is synchroon: een async
 * functie maakt ook bij een vroege return een Promise per GPS-sample, en die
 * wachtrij bevriest de UI zolang de native taak in de voorgrond meeloopt.
 */
let locationTaskBusy = false;

/** Minimaal 15s — sneller → jetsam/SIGKILL (~3GB highwater) binnen ~1 min. */
export function clampGpsRefreshSeconds(value: unknown): number {
  const n = Number(value);
  if (!Number.isFinite(n) || n < 15) return 15;
  return Math.min(30, Math.max(15, Math.round(n)));
}

// De typedefinitie eist een Promise, maar een thenable per sample plant microtasks.
// De runtime accepteert void; alleen de achtergrond start één async post.
TaskManager.defineTask(LOCATION_TASK, (({ data, error }: { data?: unknown; error?: unknown }) => {
  if (error || locationTaskBusy || AppState.currentState !== 'background') {
    return;
  }
  const locations = (data as { locations?: Location.LocationObject[] })?.locations;
  if (!locations?.length) return;
  locationTaskBusy = true;
  void runLocationTask(locations[locations.length - 1]).finally(() => {
    locationTaskBusy = false;
  });
}) as unknown as Parameters<typeof TaskManager.defineTask>[1]);

async function runLocationTask(latest: Location.LocationObject): Promise<void> {
  const token = await secureGet(TOKEN_KEY);
  if (!token) {
    return;
  }
  const vehicleRaw = await secureGet(VEHICLE_KEY);
  const vehicleId = vehicleRaw ? Number(vehicleRaw) : null;
  const heading = latest.coords.heading;
  const speed = latest.coords.speed;

  // GPS posten. Daarna throttled inbox-check: op slotscherm stopt de JS-poll,
  // zonder remote push is dit de wake voor Bel-meldingen.
  try {
    const base = await resolveApiBaseUrl();
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 8000);
    try {
      await fetch(`${base}/api/taxi/v1/driver/availability/location`, {
        method: 'PUT',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          lat: latest.coords.latitude,
          lng: latest.coords.longitude,
          accuracy: latest.coords.accuracy ?? undefined,
          ...(heading != null && heading >= 0 ? { heading } : {}),
          ...(speed != null && speed >= 0 ? { speed } : {}),
          ...(vehicleId ? { vehicle_id: vehicleId } : {}),
        }),
        signal: controller.signal,
      });
    } finally {
      clearTimeout(timer);
    }
  } catch {
    /* ignore network errors in background */
  }
  try {
    // Lazy: voorkomt zware import-cyclus bij native task-load.
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    const { pollDriverOffersFromBackgroundTask } = require('../notifications/offerAlertWatch') as {
      pollDriverOffersFromBackgroundTask: (token?: string | null) => void;
    };
    pollDriverOffersFromBackgroundTask(token);
  } catch {
    /* offer-poll mag GPS nooit breken */
  }
}

export async function prepareBackgroundLocation(token: string, vehicleId?: number | null) {
  await secureSet(TOKEN_KEY, token);
  if (vehicleId) {
    await secureSet(VEHICLE_KEY, String(vehicleId));
  } else {
    await secureDelete(VEHICLE_KEY);
  }
}

/**
 * When-in-use first. Throws only when that level is missing.
 * Already-denied (canAskAgain false) uses LOCATION_SETTINGS_HINT; the caller opens Settings.
 * A fresh denial keeps LOCATION_REQUIRED_HINT so the system sheet the user just saw is not replaced.
 */
let foregroundPermissionTask: Promise<void> | null = null;

async function requestForegroundLocationPermission(): Promise<void> {
  const existing = await Location.getForegroundPermissionsAsync();
  if (existing.status === 'granted') return;
  if (existing.canAskAgain === false) {
    throw new Error(t(LOCATION_SETTINGS_HINT));
  }

  const asked = await Location.requestForegroundPermissionsAsync();
  if (asked.status === 'granted') return;
  throw new Error(t(LOCATION_REQUIRED_HINT));
}

/** While-using prompt. Concurrent callers share one system dialog. */
export function ensureForegroundLocationPermission(): Promise<void> {
  if (!foregroundPermissionTask) {
    foregroundPermissionTask = requestForegroundLocationPermission().finally(() => {
      foregroundPermissionTask = null;
    });
  }
  return foregroundPermissionTask;
}

async function wasBackgroundUpgradeAsked(): Promise<boolean> {
  if (backgroundUpgradeAsked) return true;
  try {
    if ((await AsyncStorage.getItem(BACKGROUND_UPGRADE_ASKED_KEY)) === '1') {
      backgroundUpgradeAsked = true;
      return true;
    }
  } catch {
    /* in-memory flag still applies for this session */
  }
  return backgroundUpgradeAsked;
}

function rememberBackgroundUpgradeAsked(): void {
  backgroundUpgradeAsked = true;
  AsyncStorage.setItem(BACKGROUND_UPGRADE_ASKED_KEY, '1').catch(() => undefined);
}

/**
 * Ensures Always / all-the-time when possible so online drivers stay on booking
 * maps and receive offer checks while the app is backgrounded.
 * Asks at most once per install. Denial is not fatal: Online still works in
 * foreground; Android keeps the foreground-service task with while-in-use.
 */
export async function requestBackgroundLocationUpgrade(): Promise<boolean> {
  try {
    const background = await Location.getBackgroundPermissionsAsync();
    if (background.status === 'granted') return true;

    const foreground = await Location.getForegroundPermissionsAsync();
    if (foreground.status !== 'granted') return false;

    if (background.canAskAgain === false) {
      rememberBackgroundUpgradeAsked();
      return false;
    }
    if (backgroundUpgradeAsked || (await wasBackgroundUpgradeAsked())) {
      return false;
    }

    rememberBackgroundUpgradeAsked();
    const asked = await Location.requestBackgroundPermissionsAsync();
    return asked.status === 'granted';
  } catch {
    return false;
  }
}

export async function isBackgroundLocationGranted(): Promise<boolean> {
  const background = await Location.getBackgroundPermissionsAsync();
  return background.status === 'granted';
}

let locationOps: Promise<void> = Promise.resolve();
let wantBackgroundTask = false;
const LOCATION_OP_TIMEOUT_MS = 2500;

function runLocationOp(work: () => Promise<void>): Promise<void> {
  // Elke native start/stop mag de wachtrij max. kort vasthouden. Een hangende
  // stopLocationUpdatesAsync blokkeerde anders online/offline en alle taps.
  const timed = async () => {
    try {
      await Promise.race([
        work(),
        new Promise<void>((resolve) => setTimeout(resolve, LOCATION_OP_TIMEOUT_MS)),
      ]);
    } catch {
      /* native kan al gestopt/gestart zijn */
    }
  };
  const run = locationOps.then(timed, timed);
  locationOps = run.then(
    () => undefined,
    () => undefined
  );
  return run;
}

async function stopLocationTaskNow(): Promise<void> {
  // Altijd stoppen. hasStarted is soms false terwijl CLLocationManager nog
  // elke fix naar JS stuurt; die check liet de taak in de voorgrond doorlopen.
  try {
    await Location.stopLocationUpdatesAsync(LOCATION_TASK);
  } catch {
    /* taak kan al gestopt zijn */
  }
}

async function startLocationTask(refreshSeconds: number): Promise<void> {
  if (!DRIVER_BG.backgroundLocation) {
    wantBackgroundTask = false;
    await runLocationOp(() => stopLocationTaskNow());
    return;
  }
  wantBackgroundTask = true;
  const seconds = clampGpsRefreshSeconds(refreshSeconds);
  await runLocationOp(async () => {
    if (!wantBackgroundTask || AppState.currentState !== 'background') {
      wantBackgroundTask = false;
      await stopLocationTaskNow();
      return;
    }
    await stopLocationTaskNow();
    if (!wantBackgroundTask || AppState.currentState !== 'background') return;

    // Minimaal 4s. deferredUpdatesInterval 0 laat iOS elke sample naar JS sturen;
    // in de voorgrond negeert iOS die interval zelfs en is BestForNavigation een vloed.
    const intervalMs = Math.max(seconds * 1000, 4000);
    await Location.startLocationUpdatesAsync(LOCATION_TASK, {
      accuracy: Location.Accuracy.Balanced,
      timeInterval: intervalMs,
      // 0 zodat een stilstaande chauffeur op het slotscherm nog fixes krijgt.
      // In de voorgrond draait deze taak niet; deferred interval bundelt de rest.
      distanceInterval: 0,
      deferredUpdatesInterval: intervalMs,
      deferredUpdatesDistance: 0,
      showsBackgroundLocationIndicator: true,
      pausesUpdatesAutomatically: false,
      activityType: Location.ActivityType.AutomotiveNavigation,
      foregroundService: {
        notificationTitle: 'Nexa Taxi',
        notificationBody: 'Locatie actief zodat klanten je taxi kunnen vinden.',
        notificationColor: '#2563EB',
      },
    });
    if (!wantBackgroundTask || AppState.currentState !== 'background') {
      await stopLocationTaskNow();
    }
  });
}

/** Eén stop per voorgrond-periode. Elke AppState-tik opnieuw stoppen stapelde native promises. */
let foregroundPauseSent = false;

function stopHighRateTaskUnlessBackground(): void {
  if (AppState.currentState === 'background') {
    foregroundPauseSent = false;
    return;
  }
  if (foregroundPauseSent) return;
  foregroundPauseSent = true;
  void pauseBackgroundLocationUpdates().catch(() => undefined);
}

// Niet synchroon bij import: stopLocationUpdatesAsync bij boot blokkeerde het
// eerste frame (zwart splash-scherm). Eerst paint, dan native ops.
setTimeout(() => {
  stopHighRateTaskUnlessBackground();
  AppState.addEventListener('change', () => {
    stopHighRateTaskUnlessBackground();
  });
}, 0);

/**
 * Foreground permission is required to go Online.
 * iOS: background task needs Always (requested once via upgrade).
 * Android: foreground-service updates run with while-in-use; Always is preferred
 * so presence survives longer after leaving the app.
 */
export async function startBackgroundLocation(
  token: string,
  vehicleId?: number | null,
  refreshSeconds: number = 1
): Promise<boolean> {
  await prepareBackgroundLocation(token, vehicleId);
  await ensureForegroundLocationPermission();
  const backgroundGranted = await requestBackgroundLocationUpgrade();

  const startTask = backgroundGranted || Platform.OS === 'android';
  if (!startTask || AppState.currentState !== 'background') {
    await pauseBackgroundLocationUpdates();
    return false;
  }

  try {
    await startLocationTask(refreshSeconds);
  } catch (error) {
    if (backgroundGranted) throw error;
    await pauseBackgroundLocationUpdates().catch(() => undefined);
    return false;
  }

  return backgroundGranted;
}

/** Start the background task only when Always is already granted. Does not prompt. */
export async function resumeGrantedBackgroundLocation(refreshSeconds: number = 1): Promise<boolean> {
  if (AppState.currentState !== 'background') {
    await pauseBackgroundLocationUpdates();
    return false;
  }
  if (!(await isBackgroundLocationGranted())) return false;
  await startLocationTask(refreshSeconds);
  return true;
}

/**
 * Keep the native location task running while Online (no permission prompt).
 * iOS needs Always; Android may run the foreground-service task with while-in-use.
 */
export async function ensureBackgroundLocationRunning(refreshSeconds: number = 1): Promise<boolean> {
  if (AppState.currentState !== 'background') {
    await pauseBackgroundLocationUpdates();
    return false;
  }
  const backgroundGranted = await isBackgroundLocationGranted();
  if (!backgroundGranted && Platform.OS !== 'android') return false;
  try {
    await startLocationTask(refreshSeconds);
    return AppState.currentState === 'background';
  } catch {
    return false;
  }
}

/** Stop high-rate updates while the app is open. Keeps the stored token for standby. */
export async function pauseBackgroundLocationUpdates(): Promise<void> {
  wantBackgroundTask = false;
  await runLocationOp(() => stopLocationTaskNow());
}

export async function stopBackgroundLocation() {
  wantBackgroundTask = false;
  await runLocationOp(() => stopLocationTaskNow());
  await secureDelete(TOKEN_KEY);
}
