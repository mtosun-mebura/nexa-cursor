import { t } from '../i18n';
import { AppState } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import {
  fetchDriverInbox,
  inboxDataFromResponse,
  inboxOffersFromResponse,
  type DispatchOffer,
  type DriverActiveRide,
} from '../api/driver';
import {
  normalizeRideAlertTone,
  playRideAlertTone,
  prepareRideAlertAudio,
} from '../audio/rideAlert';
import { notifyNewDriverOffer, notifyPickupDenied, setDriverOfferBadge } from './driverOffers';
import { alertNextPickupDeparture } from './pickupDeparture';

const SEEN_KEY = 'nexa_taxi_seen_offer_ids';
const READY_KEY = 'nexa_taxi_offers_seen_ready';
const TONE_KEY = 'nexa_taxi_ride_alert_tone';
const DENIED_KEY = 'nexa_taxi_pickup_denied_notified';
const PAYOUT_KEY = 'nexa_taxi_payout_notified';
const BADGE_KEY = 'nexa_taxi_offer_badge';

let chain: Promise<unknown> = Promise.resolve();
let lastBackgroundCheck = 0;
let audioPrimed = false;
/** Eén inbox-check tegelijk. Extra aanroepen (GPS-burst) vervangen de wachtende input. */
let alertDraining = false;
let queuedAlert: Parameters<typeof checkOnce>[0] | null = null;

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

async function addOfferBadge(extra: number): Promise<number> {
  const add = Math.max(0, Math.round(extra));
  let current = 0;
  try {
    current = Number(await SecureStore.getItemAsync(BADGE_KEY)) || 0;
  } catch {
    current = 0;
  }
  const next = Math.max(0, Math.min(99, current + add));
  await SecureStore.setItemAsync(BADGE_KEY, String(next));
  await setDriverOfferBadge(next);
  return next;
}

/** Cijfer op het icoon weghalen zodra de chauffeur de app weer opent. */
export async function clearDriverOfferBadge(): Promise<void> {
  try {
    await SecureStore.setItemAsync(BADGE_KEY, '0');
  } catch {
    /* ignore */
  }
  await setDriverOfferBadge(0);
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
 * - Voorgrond: gekozen ritgeluid via expo-audio (beltoon/luidspreker als de telefoon niet op stil staat), stille banner.
 * - Standby/achtergrond: lokale melding met dezelfde wav (hoorbaar als de ringer niet stil staat).
 */
export function alertUnseenDriverOffers(input: {
  token?: string;
  offers?: DispatchOffer[];
  online?: boolean;
  minIntervalMs?: number;
  origin?: { lat: number; lng: number } | null;
  fromBackground?: boolean;
}): Promise<DispatchOffer[]> {
  queuedAlert = input;
  if (alertDraining) return Promise.resolve([]);
  alertDraining = true;
  const run = chain.then(async () => {
    let last: DispatchOffer[] = [];
    try {
      while (queuedAlert) {
        const next = queuedAlert;
        queuedAlert = null;
        last = await checkOnce(next);
      }
      return last;
    } finally {
      alertDraining = false;
      if (queuedAlert) {
        void alertUnseenDriverOffers(queuedAlert);
      }
    }
  });
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
  origin?: { lat: number; lng: number } | null;
  fromBackground?: boolean;
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
    // Vertrekmelding mag de ritaanvraag niet tegenhouden (route-lookup kan blijven hangen).
    void alertNextPickupDeparture({
      scheduled: data.scheduledRides,
      activeRide: data.activeRide,
      origin: input.origin,
    }).catch(() => undefined);
  }

  const ids = offers.map((offer) => Number(offer.id)).filter((id) => id > 0);
  const ready = (await SecureStore.getItemAsync(READY_KEY)) === '1';
  const seen = await loadSeen();
  if (!ready) {
    await SecureStore.setItemAsync(READY_KEY, '1');
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
  const inForeground = !input.fromBackground && AppState.currentState === 'active';
  const badge = inForeground ? undefined : await addOfferBadge(fresh.length);

  const offerId = Number(priority.id) || null;
  const rideRequestId = Number(priority.ride_request_id || priority.ride?.id || 0) || null;

  if (inForeground) {
    void playRideAlertTone(tone).then((played) => {
      if (!played) void playRideAlertTone('alert');
    });
    await notifyNewDriverOffer({
      count: fresh.length,
      pickup: priority.ride?.pickup_address,
      dropoff: priority.ride?.dropoff_address,
      playSound: false,
      tone,
      badge,
      offerId,
      rideRequestId,
    });
  } else {
    // Lokale melding met dezelfde wav. Niet wachten op de speler:
    // die hangt de GPS-taak op en de melding is het geluid op de beltoon.
    await notifyNewDriverOffer({
      count: fresh.length,
      pickup: priority.ride?.pickup_address,
      dropoff: priority.ride?.dropoff_address,
      playSound: true,
      tone,
      badge,
      offerId,
      rideRequestId,
    });
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
    (alertMatches ? String(input.alert?.title || '').trim() : '') || t("Klant niet opgehaald");
  const body =
    (alertMatches ? alertBody : '') ||
    `${name || 'De klant'} zegt dat die niet is opgehaald. Tik opnieuw op Klant opgehaald.`;
  const storedTone = await SecureStore.getItemAsync(TONE_KEY);
  const tone = normalizeRideAlertTone(storedTone);
  const inForeground = AppState.currentState === 'active';
  if (inForeground) {
    void playRideAlertTone(tone);
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
  }
  return { title, body, rideId };
}

async function loadPayoutIds(): Promise<Set<number>> {
  try {
    const raw = await SecureStore.getItemAsync(PAYOUT_KEY);
    const list = raw ? JSON.parse(raw) : [];
    if (!Array.isArray(list)) return new Set();
    return new Set(list.map((id) => Number(id)).filter((id) => id > 0));
  } catch {
    return new Set();
  }
}

/** Eén in-app melding per rit nadat de uitbetaling is geaccepteerd. */
export async function takeFreshPayoutNotice(
  notices?: Array<{ ride_request_id?: number; title?: string; message?: string }> | null,
): Promise<{ title: string; body: string; rideId: number } | null> {
  const list = Array.isArray(notices) ? notices : [];
  const stored = await loadPayoutIds();
  for (const notice of list) {
    const rideId = Number(notice?.ride_request_id || 0);
    const body = String(notice?.message || '').trim();
    if (rideId <= 0 || !body || stored.has(rideId)) continue;
    stored.add(rideId);
    const ids = Array.from(stored).slice(-200);
    await SecureStore.setItemAsync(PAYOUT_KEY, JSON.stringify(ids));
    return {
      title: String(notice?.title || '').trim() || t("Bedrag overgemaakt"),
      body,
      rideId,
    };
  }
  return null;
}
