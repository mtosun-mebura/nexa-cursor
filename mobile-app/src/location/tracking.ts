import * as Location from 'expo-location';
import { AppState, type AppStateStatus } from 'react-native';
import { sendDriverLocation } from '../api/driver';
import { DRIVER_BG } from '../debug/driverBgKillSwitch';
import {
  clampGpsRefreshSeconds,
  ensureBackgroundLocationRunning,
  ensureForegroundLocationPermission,
  pauseBackgroundLocationUpdates,
  prepareBackgroundLocation,
  stopBackgroundLocation,
} from './background';

export { clampGpsRefreshSeconds };

type TrackingOpts = {
  token: string;
  vehicleId?: number | null;
  refreshSeconds?: number;
};

let appStateSub: { remove: () => void } | null = null;
let foregroundTicker: ReturnType<typeof setInterval> | null = null;
let postInFlight = false;
let postStartedAt = 0;
let postToken = 0;
let appliedAppState: AppStateStatus | null = null;
let refreshMs = 15000;
let activeOpts: TrackingOpts | null = null;
let trackGeneration = 0;
/** Laatst geslaagde coords — presence blijft online zonder native GPS-spam. */
let lastGoodCoords: DriverMapPosition | null = null;
let lastNativeReadAt = 0;

const POST_WATCHDOG_MS = 8000;
/** Minimale tick — jetsam highwater (~3GB) bij vaker native GPS. */
const TICK_MS = 15000;
/** Native last-known hoogstens 1× per dit interval (JS-cache tussendoor). */
const NATIVE_READ_MIN_MS = 30000;

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

/** Last-known only. No getCurrentPosition — that leaked CLLocationManagers (jetsam). */
export async function readLastKnownDriverMapPosition(): Promise<DriverMapPosition | null> {
  const now = Date.now();
  if (lastGoodCoords && now - lastNativeReadAt < NATIVE_READ_MIN_MS) {
    return lastGoodCoords;
  }
  try {
    const last = await Promise.race([
      Location.getLastKnownPositionAsync({
        maxAge: 1000 * 60 * 30,
        requiredAccuracy: 5000,
      }),
      new Promise<null>((resolve) => setTimeout(() => resolve(null), 1200)),
    ]);
    lastNativeReadAt = Date.now();
    const pos = positionFromLocation(last);
    if (pos) lastGoodCoords = pos;
    return pos || lastGoodCoords;
  } catch {
    return lastGoodCoords;
  }
}

/** Presence/kaart: alleen cache + last-known. Nooit verse GPS-fix (memory). */
export async function readDriverMapPosition(): Promise<DriverMapPosition | null> {
  if (lastGoodCoords) return lastGoodCoords;
  return readLastKnownDriverMapPosition();
}

async function postCoords(
  token: string,
  coords: DriverMapPosition,
  vehicleId?: number | null
): Promise<boolean> {
  try {
    await sendDriverLocation(token, coords, vehicleId);
    lastGoodCoords = coords;
    return true;
  } catch {
    return false;
  }
}

function clearPostLockIfStuck(): void {
  if (!postInFlight) return;
  if (Date.now() - postStartedAt < POST_WATCHDOG_MS) return;
  postInFlight = false;
  postToken += 1;
}

function kickForegroundPost(): void {
  if (!activeOpts || AppState.currentState !== 'active') return;
  clearPostLockIfStuck();
  if (postInFlight) return;

  postInFlight = true;
  postStartedAt = Date.now();
  const tokenId = ++postToken;
  const token = activeOpts.token;
  const vehicleId = activeOpts.vehicleId;

  void (async () => {
    try {
      // Liever cache herposten dan native thrashen — last_seen blijft vers.
      let coords = lastGoodCoords;
      if (!coords || Date.now() - lastNativeReadAt >= NATIVE_READ_MIN_MS) {
        coords = (await readLastKnownDriverMapPosition()) || lastGoodCoords;
      }
      if (tokenId !== postToken || activeOpts?.token !== token) return;
      if (!coords) return;
      await postCoords(token, coords, vehicleId);
    } catch {
      /* nooit laten knallen */
    } finally {
      if (tokenId === postToken) {
        postInFlight = false;
      }
    }
  })();
}

function stopForegroundTicker(): void {
  if (foregroundTicker) {
    clearInterval(foregroundTicker);
    foregroundTicker = null;
  }
}

function startForegroundTicker(generation: number): void {
  stopForegroundTicker();
  if (generation !== trackGeneration || AppState.currentState !== 'active') return;

  const every = Math.max(TICK_MS, refreshMs);
  foregroundTicker = setInterval(() => {
    if (generation !== trackGeneration || AppState.currentState !== 'active') {
      stopForegroundTicker();
      return;
    }
    kickForegroundPost();
  }, every);
  kickForegroundPost();
}

function bindAppState(): void {
  if (appStateSub) {
    appStateSub.remove();
    appStateSub = null;
  }
  appStateSub = AppState.addEventListener('change', (state: AppStateStatus) => {
    if (!activeOpts) return;
    if (state === appliedAppState && !(state === 'active' && !foregroundTicker)) return;
    appliedAppState = state;
    const current = activeOpts;
    if (state === 'background') {
      stopForegroundTicker();
      postToken += 1;
      postInFlight = false;
      if (DRIVER_BG.backgroundLocation) {
        void ensureBackgroundLocationRunning(current.refreshSeconds).catch(() => undefined);
      }
      return;
    }
    if (state !== 'active') {
      stopForegroundTicker();
      postToken += 1;
      postInFlight = false;
      void pauseBackgroundLocationUpdates().catch(() => undefined);
      return;
    }
    void pauseBackgroundLocationUpdates().catch(() => undefined);
    startForegroundTicker(trackGeneration);
  });
}

/**
 * Opties bijwerken zonder tracking te herstarten (voorkomt native thrash/crash).
 */
export function updateDriverLocationTrackingOpts(
  opts: Partial<TrackingOpts> & { token?: string }
): void {
  if (!activeOpts) return;
  const prevToken = activeOpts.token;
  const prevVehicle = activeOpts.vehicleId;
  if (opts.token) activeOpts.token = opts.token;
  if (opts.vehicleId !== undefined) activeOpts.vehicleId = opts.vehicleId;
  if (opts.refreshSeconds != null) {
    const seconds = clampGpsRefreshSeconds(opts.refreshSeconds);
    activeOpts.refreshSeconds = seconds;
    refreshMs = seconds * 1000;
  }
  // SecureStore alleen bij echte wijziging — disk-writes watchdog killde de app eerder.
  if (activeOpts.token !== prevToken || activeOpts.vehicleId !== prevVehicle) {
    void prepareBackgroundLocation(activeOpts.token, activeOpts.vehicleId).catch(() => undefined);
  }
}

/**
 * Start presence-tracking. Native start/stop is fire-and-forget met timeouts —
 * de JS-draad mag hier nooit op blijven hangen.
 */
export async function startDriverLocationTracking(opts: TrackingOpts): Promise<void> {
  if (!DRIVER_BG.locationTracking) {
    await stopDriverLocationTracking();
    return;
  }
  if (activeOpts?.token === opts.token && AppState.currentState === 'active' && foregroundTicker) {
    updateDriverLocationTrackingOpts(opts);
    kickForegroundPost();
    return;
  }

  const generation = ++trackGeneration;
  const seconds = clampGpsRefreshSeconds(opts.refreshSeconds);
  refreshMs = seconds * 1000;
  activeOpts = { ...opts, refreshSeconds: seconds };
  postToken += 1;
  postInFlight = false;

  void Promise.race([
    (async () => {
      try {
        await ensureForegroundLocationPermission();
      } catch {
        /* online mag zonder Always */
      }
      if (generation !== trackGeneration) return;
      await prepareBackgroundLocation(opts.token, opts.vehicleId);
    })(),
    new Promise<void>((resolve) => setTimeout(resolve, 2500)),
  ]).catch(() => undefined);

  if (AppState.currentState !== 'background' || !DRIVER_BG.backgroundLocation) {
    void pauseBackgroundLocationUpdates().catch(() => undefined);
  } else {
    void ensureBackgroundLocationRunning(seconds).catch(() => undefined);
  }

  if (generation !== trackGeneration) return;
  bindAppState();
  if (AppState.currentState === 'active') {
    startForegroundTicker(generation);
  }

  void publishDriverLocationForMap(opts.token, opts.vehicleId).catch(() => undefined);
}

export function invalidateDriverLocationTracking(): void {
  trackGeneration += 1;
  stopForegroundTicker();
  postToken += 1;
  postInFlight = false;
}

export async function stopDriverLocationTracking(): Promise<void> {
  trackGeneration += 1;
  activeOpts = null;
  if (appStateSub) {
    appStateSub.remove();
    appStateSub = null;
  }
  stopForegroundTicker();
  postToken += 1;
  postInFlight = false;
  void stopBackgroundLocation().catch(() => undefined);
}

/**
 * Directe locatie voor de klantkaart. Gebruikt cache / last-known — nooit getCurrent.
 */
export async function publishDriverLocationForMap(
  token: string,
  vehicleId?: number | null
): Promise<boolean> {
  if (!DRIVER_BG.locationTracking) return false;
  try {
    await Promise.race([
      ensureForegroundLocationPermission(),
      new Promise<void>((_, reject) => setTimeout(() => reject(new Error('perm-timeout')), 2000)),
    ]);
  } catch {
    /* probeer toch met cache */
  }
  const coords = await readDriverMapPosition();
  if (!coords) return false;
  return postCoords(token, coords, vehicleId);
}
