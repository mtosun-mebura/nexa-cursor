import * as Location from 'expo-location';
import { AppState, type AppStateStatus } from 'react-native';
import { sendDriverLocation } from '../api/driver';
import {
  clampGpsRefreshSeconds,
  ensureBackgroundLocationRunning,
  ensureForegroundLocationPermission,
  pauseBackgroundLocationUpdates,
  prepareBackgroundLocation,
  requestBackgroundLocationUpgrade,
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
let tickerStartPending = false;
let postInFlight = false;
let postToken = 0;
let lastSentAt = 0;
let appliedAppState: AppStateStatus | null = null;
let refreshMs = 8000;
let activeOpts: TrackingOpts | null = null;
let trackGeneration = 0;
/** Eerste fix na online: forceer verse GPS als last-known leeg is (anders geen marker op klantkaart). */
let needsMapFix = false;

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

function stopForegroundTicker() {
  if (foregroundTicker) {
    clearInterval(foregroundTicker);
    foregroundTicker = null;
  }
}

/**
 * Eén last-known fix per interval. Geen getCurrentPosition en geen watch:
 * die maken per GPS-sample een Promise en die stapelt zolang het scherm open is.
 * postInFlight wordt synchroon gezet, vóór de eerste await.
 */
function kickForegroundPost(): void {
  if (!activeOpts || postInFlight || AppState.currentState !== 'active') return;
  postInFlight = true;
  const tokenId = ++postToken;
  const token = activeOpts.token;
  const vehicleId = activeOpts.vehicleId;
  // Alleen last-known in de interval — getCurrentPosition in een loop crashte iOS na ~2–3 min.
  // Verse fix gebeurt éénmalig via publishDriverLocationForMap bij online.
  void Promise.race([
    Location.getLastKnownPositionAsync({
      maxAge: 1000 * 60 * 5,
      requiredAccuracy: 2500,
    }),
    new Promise<null>((resolve) => setTimeout(() => resolve(null), 2000)),
  ])
    .then((loc) => {
      if (
        tokenId !== postToken ||
        !loc ||
        activeOpts?.token !== token ||
        AppState.currentState !== 'active'
      ) {
        return;
      }
      return postFix(token, loc, vehicleId, true).then(() => {
        needsMapFix = false;
      });
    })
    .catch(() => undefined)
    .finally(() => {
      if (tokenId === postToken) postInFlight = false;
    });
}

/**
 * Interval pas zetten ná de await, en meteen na stopForegroundTicker.
 * Een await ertussen liet een tweede setInterval de eerste overschrijven
 * zonder clear — elke herstart (tab/focus/online) hield de vorige timer.
 */
async function startForegroundTicker(generation: number) {
  if (tickerStartPending || foregroundTicker) return;
  if (generation !== trackGeneration || AppState.currentState !== 'active') return;
  tickerStartPending = true;
  try {
    await ensureForegroundLocationPermission();
    if (generation !== trackGeneration || AppState.currentState !== 'active' || foregroundTicker) return;
    const every = Math.max(10000, refreshMs);
    const timer = setInterval(() => {
      if (foregroundTicker !== timer) return;
      if (generation !== trackGeneration || AppState.currentState !== 'active') {
        if (foregroundTicker === timer) stopForegroundTicker();
        return;
      }
      kickForegroundPost();
    }, every);
    if (generation !== trackGeneration || AppState.currentState !== 'active' || foregroundTicker) {
      clearInterval(timer);
      return;
    }
    foregroundTicker = timer;
    kickForegroundPost();
  } finally {
    tickerStartPending = false;
  }
}

function bindAppState() {
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
      void ensureBackgroundLocationRunning(current.refreshSeconds).catch(() => undefined);
      return;
    }
    if (state !== 'active') {
      stopForegroundTicker();
      postToken += 1;
      void pauseBackgroundLocationUpdates().catch(() => undefined);
      return;
    }
    void startForegroundTicker(trackGeneration).catch(() => undefined);
  });
}

/**
 * Foreground watch + background location task while Online.
 * Always-prompt gebeurt deferred — niet in deze call, anders hangt/crasht de online-schuif.
 */
export async function startDriverLocationTracking(opts: TrackingOpts): Promise<void> {
  const generation = ++trackGeneration;
  const seconds = clampGpsRefreshSeconds(opts.refreshSeconds);
  refreshMs = seconds * 1000;
  activeOpts = { ...opts, refreshSeconds: seconds };
  needsMapFix = true;

  // Eerst de native taak uit. Die levert bij distanceInterval 0 een sample per
  // GPS-fix en blokkeert de UI als hij meeloopt terwijl het scherm open is.
  if (AppState.currentState !== 'background') {
    await pauseBackgroundLocationUpdates();
  }
  if (generation !== trackGeneration) return;

  await prepareBackgroundLocation(opts.token, opts.vehicleId);
  if (generation !== trackGeneration) return;
  if (AppState.currentState === 'background') {
    await ensureBackgroundLocationRunning(seconds);
  } else {
    await startForegroundTicker(generation);
    await pauseBackgroundLocationUpdates();
  }
  if (generation !== trackGeneration) return;
  bindAppState();

  // Always-prompt zelden en laat — systeembalk tijdens actief gebruik liet de app crashen.
  setTimeout(() => {
    if (generation !== trackGeneration || !activeOpts) return;
    if (AppState.currentState !== 'active') return;
    void requestBackgroundLocationUpgrade().catch(() => false);
  }, 45000);
}

/** Stopt een lopende start zonder het opgeslagen token te wissen. */
export function invalidateDriverLocationTracking(): void {
  trackGeneration += 1;
  stopForegroundTicker();
  postToken += 1;
  postInFlight = false;
}

export async function stopDriverLocationTracking() {
  trackGeneration += 1;
  activeOpts = null;
  needsMapFix = false;
  if (appStateSub) {
    appStateSub.remove();
    appStateSub = null;
  }
  stopForegroundTicker();
  postToken += 1;
  postInFlight = false;
  await stopBackgroundLocation();
  lastSentAt = 0;
}

/**
 * Directe locatie voor de klantkaart bij online gaan (blokkeert de schuif niet
 * als je ’m fire-and-forget aanroept). Vraagt zo nodig while-using toestemming.
 */
export async function publishDriverLocationForMap(
  token: string,
  vehicleId?: number | null
): Promise<boolean> {
  try {
    await ensureForegroundLocationPermission();
  } catch {
    return false;
  }
  const coords = await readDriverMapPosition();
  if (!coords) return false;
  try {
    await sendDriverLocation(token, coords, vehicleId);
    needsMapFix = false;
    lastSentAt = Date.now();
    return true;
  } catch {
    return false;
  }
}
