import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Location from 'expo-location';
import { Platform } from 'react-native';
import * as TaskManager from 'expo-task-manager';
import * as SecureStore from 'expo-secure-store';
import { API_BASE_URL } from '../config';
import { t } from '../i18n';
import { alertUnseenDriverOffers } from '../notifications/offerAlertWatch';

/** Shown only after iOS/Android will no longer present the system prompt. */
export const LOCATION_SETTINGS_HINT =
  'Locatie staat uit. Zet locatie aan in Instellingen om online te gaan.';

export const LOCATION_REQUIRED_HINT = 'Locatietoestemming is verplicht om online te gaan.';

/**
 * While-using is enough. Remember that the Always / all-the-time upgrade was
 * already offered so a later online toggle does not open Android's location
 * permission page or iOS's Change to Always sheet again.
 */
const BACKGROUND_UPGRADE_OFFERED_KEY = 'nexa.taxi.backgroundLocationUpgradeOffered';
let backgroundUpgradeOffered = false;

export const LOCATION_TASK = 'nexa-taxi-driver-location';
const TOKEN_KEY = 'nexa_taxi_location_token';
const VEHICLE_KEY = 'nexa_taxi_location_vehicle';

export function clampGpsRefreshSeconds(value: unknown): number {
  const n = Number(value);
  if (!Number.isFinite(n) || n < 1) return 1;
  return Math.min(30, Math.max(1, Math.round(n)));
}

TaskManager.defineTask(LOCATION_TASK, async ({ data, error }) => {
  if (error) {
    return;
  }
  const locations = (data as { locations?: Location.LocationObject[] })?.locations;
  if (!locations?.length) {
    return;
  }
  const latest = locations[locations.length - 1];
  const token = await SecureStore.getItemAsync(TOKEN_KEY);
  if (!token) {
    return;
  }
  const vehicleRaw = await SecureStore.getItemAsync(VEHICLE_KEY);
  const vehicleId = vehicleRaw ? Number(vehicleRaw) : null;
  const heading = latest.coords.heading;
  const speed = latest.coords.speed;

  try {
    await fetch(`${API_BASE_URL}/api/taxi/v1/driver/availability/location`, {
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
});

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

async function wasBackgroundUpgradeOffered(): Promise<boolean> {
  if (backgroundUpgradeOffered) return true;
  try {
    if ((await AsyncStorage.getItem(BACKGROUND_UPGRADE_OFFERED_KEY)) === '1') {
      backgroundUpgradeOffered = true;
      return true;
    }
  } catch {
    /* in-memory flag still applies for this session */
  }
  return backgroundUpgradeOffered;
}

function rememberBackgroundUpgradeOffered(): void {
  backgroundUpgradeOffered = true;
  AsyncStorage.setItem(BACKGROUND_UPGRADE_OFFERED_KEY, '1').catch(() => undefined);
}

/**
 * While-using is enough for the map and dispatch. Returns true only when
 * Always / all-the-time is already granted.
 * Does not call requestBackgroundPermissionsAsync once foreground is granted:
 * on Android 11+ that opens the system Location permission page on every call,
 * and on iOS it re-presents Change to Always. The offer is stored so a later
 * online toggle does not open that page. Staying on while-using is not an error.
 */
export async function requestBackgroundLocationUpgrade(): Promise<boolean> {
  try {
    const background = await Location.getBackgroundPermissionsAsync();
    if (background.status === 'granted') return true;
    if (backgroundUpgradeOffered || (await wasBackgroundUpgradeOffered())) return false;

    const foreground = await Location.getForegroundPermissionsAsync();
    if (foreground.status === 'granted' || background.canAskAgain === false) {
      rememberBackgroundUpgradeOffered();
      return false;
    }
  } catch {
    return false;
  }

  // Not asked yet. The one system prompt is the foreground dialog.
  return false;
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
 * Foreground permission is required. While-using is enough to go online and is
 * not a warning. Always / all-the-time is used only when it is already granted.
 * On Android the foreground-service updates run with while-in-use. On iOS the
 * background task starts only with Always.
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

export async function stopBackgroundLocation() {
  await stopLocationTask();
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}
