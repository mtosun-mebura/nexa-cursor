import { AppState } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import {
  fetchDriverInbox,
  inboxOffersFromResponse,
  type DispatchOffer,
} from '../api/driver';
import { normalizeRideAlertTone, playRideAlertTone } from '../audio/rideAlert';
import { notifyNewDriverOffer } from './driverOffers';

const SEEN_KEY = 'nexa_taxi_seen_offer_ids';
const READY_KEY = 'nexa_taxi_offers_seen_ready';
const TONE_KEY = 'nexa_taxi_ride_alert_tone';

let chain: Promise<unknown> = Promise.resolve();
let lastBackgroundCheck = 0;

export function rememberRideAlertTone(tone?: string | null): void {
  SecureStore.setItemAsync(TONE_KEY, normalizeRideAlertTone(tone)).catch(() => undefined);
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
 * Nieuwe ritaanvragen laten horen.
 * Op de voorgrond de gekozen toon; op achtergrond en vergrendelscherm een
 * melding met diezelfde toon. Die melding volgt de stille stand van de telefoon.
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
  const foreground = AppState.currentState === 'active';
  if (foreground) {
    await playRideAlertTone(tone);
  }
  await notifyNewDriverOffer({
    count: fresh.length,
    pickup: priority.ride?.pickup_address,
    dropoff: priority.ride?.dropoff_address,
    playSound: !foreground,
    tone,
  });
  return fresh;
}
