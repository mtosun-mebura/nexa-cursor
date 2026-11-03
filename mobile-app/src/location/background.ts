import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Location from 'expo-location';
import { AppState, Platform } from 'react-native';
import * as TaskManager from 'expo-task-manager';
import * as SecureStore from 'expo-secure-store';
import { resolveApiBaseUrl } from '../config';
import { t } from '../i18n';
import { alertUnseenDriverOffers } from '../notifications/offerAlertWatch';

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
 * De taak levert bij distanceInterval 0 een fix per GPS-sample.
 * Zonder deze vlag stapelen SecureStore-, fetch- en inbox-promises zich op
 * de JS-microtaskqueue (geheugen naar meerdere GB, taps komen niet meer aan).
 */
let locationTaskBusy = false;

export function clampGpsRefreshSeconds(value: unknown): number {
  const n = Number(value);
  if (!Number.isFinite(n) || n < 1) return 1;
  return Math.min(30, Math.max(1, Math.round(n)));
}

TaskManager.defineTask(LOCATION_TASK, async ({ data, error }) => {
  if (error || locationTaskBusy) {
    return;
  }
  // Open app: de voorgrond-watch post de locatie en de inbox pollt op een timer.
  // Deze taak hier óók laten lopen blokkeert de JS-thread tot de app crasht.
  if (AppState.currentState === 'active') {
    return;
  }
  const locations = (data as { locations?: Location.LocationObject[] })?.locations;
  if (!locations?.length) {
    return;
  }
  locationTaskBusy = true;
  try {
    await runLocationTask(locations[locations.length - 1]);
  } finally {
    locationTaskBusy = false;
  }
});

async function runLocationTask(latest: Location.LocationObject): Promise<void> {
  const token = await SecureStore.getItemAsync(TOKEN_KEY);
  if (!token) {
    return;
  }
  const vehicleRaw = await SecureStore.getItemAsync(VEHICLE_KEY);
  const vehicleId = vehicleRaw ? Number(vehicleRaw) : null;
  const heading = latest.coords.heading;
  const speed = latest.coords.speed;

  try {
    const base = await resolveApiBaseUrl();
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
    });
  } catch {
    /* ignore network errors in background */
  }

  // GPS-wake is de enige JS die op het vergrendelscherm nog draait.
  try {
    await alertUnseenDriverOffers({
      token,
      minIntervalMs: 4000,
      fromBackground: true,
      origin: {
        lat: latest.coords.latitude,
        lng: latest.coords.longitude,
      },
    });
  } catch {
    /* inbox-check mag de locatiepost niet breken */
  }
}

export async function prepareBackgroundLocation(token: string, vehicleId?: number | null) {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
  if (vehicleId) {
    await SecureStore.setItemAsync(VEHICLE_KEY, String(vehicleId));
  } else {
    await SecureStore.deleteItemAsync(VEHICLE_KEY);
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

async function stopLocationTask() {
  const started = await Location.hasStartedLocationUpdatesAsync(LOCATION_TASK);
  if (started) {
    await Location.stopLocationUpdatesAsync(LOCATION_TASK);
  }
}

async function startLocationTask(refreshSeconds: number) {
  const seconds = clampGpsRefreshSeconds(refreshSeconds);
  const intervalMs = seconds * 1000;

  await stopLocationTask();
  await Location.startLocationUpdatesAsync(LOCATION_TASK, {
    accuracy: Location.Accuracy.BestForNavigation,
    timeInterval: intervalMs,
    // 0 = ook stilstaand een fix. iOS levert een achtergrond-fix alleen aan JS als
    // deferredUpdatesInterval 0 is: één sample heeft tijdsverschil 0 en wordt
    // anders ingeslikt tot de telefoon weer beweegt.
    distanceInterval: 0,
    deferredUpdatesInterval: 0,
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
}

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
  if (!startTask) {
    await stopLocationTask();
    return false;
  }

  try {
    await startLocationTask(refreshSeconds);
  } catch (error) {
    if (backgroundGranted) throw error;
    await stopLocationTask().catch(() => undefined);
    return false;
  }

  return backgroundGranted;
}

/** Start the background task only when Always is already granted. Does not prompt. */
export async function resumeGrantedBackgroundLocation(refreshSeconds: number = 1): Promise<boolean> {
  if (!(await isBackgroundLocationGranted())) return false;
  const started = await Location.hasStartedLocationUpdatesAsync(LOCATION_TASK).catch(() => false);
  if (started) return true;
  await startLocationTask(refreshSeconds);
  return true;
}

/**
 * Keep the native location task running while Online (no permission prompt).
 * iOS needs Always; Android may run the foreground-service task with while-in-use.
 */
export async function ensureBackgroundLocationRunning(refreshSeconds: number = 1): Promise<boolean> {
  const backgroundGranted = await isBackgroundLocationGranted();
  if (!backgroundGranted && Platform.OS !== 'android') return false;
  const started = await Location.hasStartedLocationUpdatesAsync(LOCATION_TASK).catch(() => false);
  if (started) return true;
  try {
    await startLocationTask(refreshSeconds);
    return true;
  } catch {
    return false;
  }
}

/** Stop high-rate updates while the app is open. Keeps the stored token for standby. */
export async function pauseBackgroundLocationUpdates(): Promise<void> {
  await stopLocationTask();
}

export async function stopBackgroundLocation() {
  await stopLocationTask();
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}
