import * as Location from 'expo-location';
import { AppState, type AppStateStatus } from 'react-native';
import { sendDriverLocation } from '../api/driver';
import {
  clampGpsRefreshSeconds,
  ensureForegroundLocationPermission,
  prepareBackgroundLocation,
  resumeGrantedBackgroundLocation,
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
let trackGeneration = 0;

export type DriverMapPosition = {
  lat: number;
  lng: number;
  accuracy?: number;
  heading?: number;
  speed?: number;
};

export function positionFromLocation(loc: Location.LocationObject | null | undefined): DriverMapPosition | null {
  const c = loc?.coords;
  if (!c || !Number.isFinite(c.latitude) || !Number.isFinite(c.longitude)) return null;
  if (c.latitude === 0 && c.longitude === 0) return null;
  return {
    lat: c.latitude,
    lng: c.longitude,
    ...(c.accuracy != null && Number.isFinite(c.accuracy) ? { accuracy: c.accuracy } : {}),
    ...(c.heading != null && c.heading >= 0 ? { heading: c.heading } : {}),
    ...(c.speed != null && c.speed >= 0 ? { speed: c.speed } : {}),
  };
}

/** Last-known fix only. Does not wait for a fresh GPS reading. */
export async function readLastKnownDriverMapPosition(): Promise<DriverMapPosition | null> {
  try {
    const last = await Location.getLastKnownPositionAsync({
      maxAge: 1000 * 60 * 10,
      requiredAccuracy: 1500,
    });
    return positionFromLocation(last);
  } catch {
    return null;
  }
}

/**
 * Echte GPS voor de boekingskaart. Eerst een recente last-known fix (iOS levert die
 * meteen), daarna een verse meting. Geen vast adres.
 */
export async function readDriverMapPosition(): Promise<DriverMapPosition | null> {
  const fromLast = await readLastKnownDriverMapPosition();
  if (fromLast) return fromLast;

  try {
    const current = await Promise.race([
      Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced }),
      new Promise<null>((resolve) => setTimeout(() => resolve(null), 6000)),
    ]);
    return positionFromLocation(current);
  } catch {
    return null;
  }
}

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
  await ensureForegroundLocationPermission();

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
    const last = await Location.getLastKnownPositionAsync({
      maxAge: 1000 * 60 * 10,
      requiredAccuracy: 1500,
    });
    if (last) await postFix(opts.token, last, opts.vehicleId, true);
  } catch {
    /* last-known mag ontbreken */
  }

  // Verse meting volgt. Niet awaiten: de online-schakelaar mag hier niet op wachten.
  void (async () => {
    try {
      const current = await Promise.race([
        Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced }),
        new Promise<null>((resolve) => setTimeout(() => resolve(null), 6000)),
      ]);
      if (!current || activeOpts?.token !== opts.token) return;
      await postFix(opts.token, current, activeOpts.vehicleId, true);
    } catch {
      /* eerste fix mag falen; watch blijft actief */
    }
  })();
}

function bindAppState(opts: TrackingOpts) {
  if (appStateSub) {
    appStateSub.remove();
    appStateSub = null;
  }
  appStateSub = AppState.addEventListener('change', (state: AppStateStatus) => {
    if (state === 'active' && activeOpts) {
      const opts = activeOpts;
      void startForegroundWatch(opts).catch(() => undefined);
      void resumeGrantedBackgroundLocation(opts.refreshSeconds).catch(() => undefined);
    }
  });
}

/**
 * Foreground watch posts to the driver location API while the app is in use.
 * While-using is enough. Always is not requested again after that choice.
 */
export async function startDriverLocationTracking(opts: TrackingOpts): Promise<void> {
  const generation = ++trackGeneration;
  const seconds = clampGpsRefreshSeconds(opts.refreshSeconds);
  refreshMs = seconds * 1000;
  activeOpts = { ...opts, refreshSeconds: seconds };

  await prepareBackgroundLocation(opts.token, opts.vehicleId);
  if (generation !== trackGeneration) return;
  await startForegroundWatch(activeOpts);
  if (generation !== trackGeneration) {
    await stopForegroundWatch();
    return;
  }
  await startBackgroundLocation(opts.token, opts.vehicleId, seconds);
  if (generation !== trackGeneration) {
    await stopForegroundWatch();
    await stopBackgroundLocation();
    return;
  }
  bindAppState(activeOpts);
}

export async function stopDriverLocationTracking() {
  trackGeneration += 1;
  activeOpts = null;
  if (appStateSub) {
    appStateSub.remove();
    appStateSub = null;
  }
  await stopForegroundWatch();
  await stopBackgroundLocation();
  lastSentAt = 0;
}
