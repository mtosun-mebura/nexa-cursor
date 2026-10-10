import * as Location from 'expo-location';
import { AppState, type AppStateStatus } from 'react-native';
import { sendDriverLocation } from '../api/driver';
import {
  clampGpsRefreshSeconds,
  prepareBackgroundLocation,
  startBackgroundLocation,
  stopBackgroundLocation,
} from './background';

export { clampGpsRefreshSeconds };

type TrackingOpts = {
  token: string;
  vehicleId?: number | null;
  refreshSeconds?: number;
};

let watchSub: Location.LocationSubscription | null = null;
let appStateSub: { remove: () => void } | null = null;
let lastSentAt = 0;
let refreshMs = 1000;
let activeOpts: TrackingOpts | null = null;

async function postFix(
  token: string,
  loc: Location.LocationObject,
  vehicleId?: number | null,
  force = false
) {
  const now = Date.now();
  if (!force && now - lastSentAt < Math.max(250, refreshMs - 75)) {
    return;
  }
  lastSentAt = now;
  const c = loc.coords;
  try {
    await sendDriverLocation(
      token,
      {
        lat: c.latitude,
        lng: c.longitude,
        ...(c.accuracy != null ? { accuracy: c.accuracy } : {}),
        ...(c.heading != null && c.heading >= 0 ? { heading: c.heading } : {}),
        ...(c.speed != null && c.speed >= 0 ? { speed: c.speed } : {}),
      },
      vehicleId
    );
  } catch {
    /* netwerkfouten niet laten knallen in de GPS-loop */
  }
}

async function stopForegroundWatch() {
  if (watchSub) {
    watchSub.remove();
    watchSub = null;
  }
}

async function startForegroundWatch(opts: TrackingOpts) {
  await stopForegroundWatch();
  const permission = await Location.requestForegroundPermissionsAsync();
  if (permission.status !== 'granted') {
    throw new Error('Locatietoestemming is verplicht om online te gaan.');
  }

  watchSub = await Location.watchPositionAsync(
    {
      accuracy: Location.Accuracy.BestForNavigation,
      timeInterval: refreshMs,
      distanceInterval: 0,
      mayShowUserSettingsDialog: true,
    },
    (loc) => {
      void postFix(opts.token, loc, opts.vehicleId);
    }
  );

  try {
    const current = await Location.getCurrentPositionAsync({
      accuracy: Location.Accuracy.High,
    });
    await postFix(opts.token, current, opts.vehicleId, true);
  } catch {
    /* eerste fix mag falen; watch blijft actief */
  }
}

function bindAppState(opts: TrackingOpts) {
  if (appStateSub) {
    appStateSub.remove();
    appStateSub = null;
  }
  appStateSub = AppState.addEventListener('change', (state: AppStateStatus) => {
    if (state === 'active' && activeOpts) {
      void startForegroundWatch(activeOpts).catch(() => undefined);
    }
  });
}

/**
 * Foreground watch + achtergrondupdates op het admin GPS-interval (refresh_seconds).
 */
export async function startDriverLocationTracking(opts: TrackingOpts) {
  const seconds = clampGpsRefreshSeconds(opts.refreshSeconds);
  refreshMs = seconds * 1000;
  activeOpts = { ...opts, refreshSeconds: seconds };

  await prepareBackgroundLocation(opts.token, opts.vehicleId);
  await startForegroundWatch(activeOpts);
  await startBackgroundLocation(opts.token, opts.vehicleId, seconds);
  bindAppState(activeOpts);
}

export async function stopDriverLocationTracking() {
  activeOpts = null;
  if (appStateSub) {
    appStateSub.remove();
    appStateSub = null;
  }
  await stopForegroundWatch();
  await stopBackgroundLocation();
  lastSentAt = 0;
}
