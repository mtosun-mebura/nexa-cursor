import { AppState } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import {
  fetchDriverInbox,
  inboxDataFromResponse,
  inboxOffersFromResponse,
  type DispatchOffer,
  type DriverActiveRide,
} from '../api/driver';
import { normalizeRideAlertTone, playRideAlertTone, prepareRideAlertAudio } from '../audio/rideAlert';
import { notifyNewDriverOffer, notifyPickupDenied } from './driverOffers';

const SEEN_KEY = 'nexa_taxi_seen_offer_ids';
const READY_KEY = 'nexa_taxi_offers_seen_ready';
const TONE_KEY = 'nexa_taxi_ride_alert_tone';
const DENIED_KEY = 'nexa_taxi_pickup_denied_notified';

let chain: Promise<unknown> = Promise.resolve();
let lastBackgroundCheck = 0;
let audioPrimed = false;

export function rememberRideAlertTone(tone?: string | null): void {
  SecureStore.setItemAsync(TONE_KEY, normalizeRideAlertTone(tone)).catch(() => undefined);
}

/** Roep aan bij online gaan zodat de eerste achtergrond-toon niet faalt. */
export function primeRideAlertAudio(): void {
  if (audioPrimed) return;
  audioPrimed = true;
  prepareRideAlertAudio().catch(() => {
    audioPrimed = false;
  });
}

async function loadSeen(): Promise<Set<number>> {
  try {
    const raw = await SecureStore.getItemAsync(SEEN_KEY);
    const list = raw ? JSON.parse(raw) : [];
    if (!Array.isArray(list)) return new Set();
    return new Set(list.map((id) => Number(id)).filter((id) => id > 0));
  } catch {
    return new Set();
  }
}

async function saveSeen(seen: Set<number>): Promise<void> {
  const ids = Array.from(seen).slice(-300);
  await SecureStore.setItemAsync(SEEN_KEY, JSON.stringify(ids));
}

/**
 * Nieuwe ritaanvragen:
 * - Voorgrond: app-toon (werkt ook bij stille stand; zelfde pad als profiel-preview).
 * - Standby/achtergrond: eerst lokale melding mét toon (betrouwbaar op vergrendelscherm),
 *   daarna best-effort app-audio. App-audio alleen mag hier niet het meldingsgeluid blokkeren.
 */
export function alertUnseenDriverOffers(input: {
  token?: string;
  offers?: DispatchOffer[];
  online?: boolean;
  minIntervalMs?: number;
}): Promise<DispatchOffer[]> {
  const run = chain.then(() => checkOnce(input));
  chain = run.then(
    () => undefined,
    () => undefined
  );
  return run;
}

async function checkOnce(input: {
  token?: string;
  offers?: DispatchOffer[];
  online?: boolean;
  minIntervalMs?: number;
}): Promise<DispatchOffer[]> {
  const minIntervalMs = input.minIntervalMs ?? 0;
  const now = Date.now();
  if (minIntervalMs > 0 && now - lastBackgroundCheck < minIntervalMs) return [];
  if (minIntervalMs > 0) lastBackgroundCheck = now;

  let offers = input.offers;
  if (!offers) {
    const token = String(input.token || '').trim();
    if (!token) return [];
    const inbox = await fetchDriverInbox(token);
    offers = inboxOffersFromResponse(inbox);
    const data = inboxDataFromResponse(inbox);
    await notifyFreshPickupDenials({
      rides: [data.activeRide, ...data.parkedAssignedRides],
      alert: data.pickupDeniedAlert,
    });
  }

  const ids = offers.map((offer) => Number(offer.id)).filter((id) => id > 0);
  const ready = (await SecureStore.getItemAsync(READY_KEY)) === '1';
  const seen = await loadSeen();
  if (!ready) {
    ids.forEach((id) => seen.add(id));
    await saveSeen(seen);
    await SecureStore.setItemAsync(READY_KEY, '1');
    return [];
  }

  const fresh = offers.filter((offer) => {
    const id = Number(offer.id);
    return id > 0 && !seen.has(id);
  });
  if (!fresh.length || input.online === false) return [];

  fresh.forEach((offer) => seen.add(Number(offer.id)));
  await saveSeen(seen);

  const storedTone = await SecureStore.getItemAsync(TONE_KEY);
  const tone = normalizeRideAlertTone(storedTone);
  const priority = fresh[0];
  const inForeground = AppState.currentState === 'active';

  if (inForeground) {
    await playRideAlertTone(tone);
    await notifyNewDriverOffer({
      count: fresh.length,
      pickup: priority.ride?.pickup_address,
      dropoff: priority.ride?.dropoff_address,
      playSound: false,
      tone,
    });
  } else {
    // Melding+geluid eerst: GPS-wake heeft weinig tijd; app-audio is hier vaak stil.
    await notifyNewDriverOffer({
      count: fresh.length,
      pickup: priority.ride?.pickup_address,
      dropoff: priority.ride?.dropoff_address,
      playSound: true,
      tone,
    });
    void playRideAlertTone(tone);
  }
  return fresh;
}

function rideDeniesPickup(ride: DriverActiveRide | null | undefined): ride is DriverActiveRide {
  if (!ride || Number(ride.id) <= 0) return false;
  return ride.pickup_confirmation?.can_reask === true || ride.pickup_confirmation?.status === 'denied';
}

async function loadDeniedIds(): Promise<Set<number>> {
  try {
    const raw = await SecureStore.getItemAsync(DENIED_KEY);
    const list = raw ? JSON.parse(raw) : [];
    if (!Array.isArray(list)) return new Set();
    return new Set(list.map((id) => Number(id)).filter((id) => id > 0));
  } catch {
    return new Set();
  }
}

async function saveDeniedIds(ids: Set<number>): Promise<void> {
  await SecureStore.setItemAsync(DENIED_KEY, JSON.stringify(Array.from(ids)));
}

export type PickupDeniedNotice = { title: string; body: string; rideId?: number };

/**
 * Eén push per weigering. Zodra de klant opnieuw wordt gevraagd, mag een
 * volgende nee weer een melding geven.
 */
export function alertPickupDenials(input: {
  rides?: Array<DriverActiveRide | null | undefined>;
  alert?: { ride_id?: number; title?: string; message?: string } | null;
}): Promise<PickupDeniedNotice | null> {
  const run = chain.then(() => notifyFreshPickupDenials(input));
  chain = run.then(
    () => undefined,
    () => undefined
  );
  return run;
}

async function notifyFreshPickupDenials(input: {
  rides?: Array<DriverActiveRide | null | undefined>;
  alert?: { ride_id?: number; title?: string; message?: string } | null;
}): Promise<PickupDeniedNotice | null> {
  const denied = (input.rides || []).filter(rideDeniesPickup);
  const current = new Set(denied.map((ride) => Number(ride.id)));
  const alertId = Number(input.alert?.ride_id || 0);
  const alertBody = String(input.alert?.message || '').trim();
  if (alertId > 0 && alertBody) {
    const listed = (input.rides || []).some((ride) => Number(ride?.id) === alertId);
    const stillDenied = denied.some((ride) => Number(ride.id) === alertId);
    if (!listed || stillDenied) current.add(alertId);
  }
  const stored = await loadDeniedIds();
  const fresh = [...current].filter((id) => !stored.has(id));
  await saveDeniedIds(current);
  if (!fresh.length) return null;

  const rideId = fresh[0];
  const ride = denied.find((item) => Number(item.id) === rideId);
  const name = String(ride?.customer_name || '').trim();
  const alertMatches = alertId === rideId;
  const title =
    (alertMatches ? String(input.alert?.title || '').trim() : '') || 'Klant niet opgehaald';
  const body =
    (alertMatches ? alertBody : '') ||
    `${name || 'De klant'} zegt dat die niet is opgehaald. Tik opnieuw op Klant opgehaald.`;
  const storedTone = await SecureStore.getItemAsync(TONE_KEY);
  const tone = normalizeRideAlertTone(storedTone);
  const inForeground = AppState.currentState === 'active';
  if (inForeground) {
    await playRideAlertTone(tone);
    await notifyPickupDenied({
      title,
      body,
      rideId,
      playSound: false,
      tone,
    });
  } else {
    await notifyPickupDenied({
      title,
      body,
      rideId,
      playSound: true,
      tone,
    });
    void playRideAlertTone(tone);
  }
  return { title, body, rideId };
}
