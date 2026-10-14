import * as Location from 'expo-location';
import * as SecureStore from 'expo-secure-store';
import type { DriverActiveRide } from '../api/driver';
import { fetchDrivingRoute } from '../geo/route';
import { notifyPickupDeparture } from './driverOffers';

const NOTIFIED_KEY = 'nexa_taxi_depart_notified';
/** Iets eerder aankomen dan het ophaalmoment. */
const EARLY_MS = 5 * 60 * 1000;
const CHECK_EVERY_MS = 60_000;
const ROUTE_CACHE_MS = 3 * 60 * 1000;
/** Niet blijven melden lang nadat het ophaalmoment voorbij is. */
const LATE_MS = 20 * 60 * 1000;

let lastCheck = 0;
const routeCache = new Map<string, { at: number; seconds: number }>();

export type PickupDepartureNotice = {
  title: string;
  body: string;
  rideId: number;
};

function pickupMs(ride: DriverActiveRide): number | null {
  const ms = Date.parse(String(ride.pickup_at || ''));
  return Number.isFinite(ms) ? ms : null;
}

function coord(lat?: number | null, lng?: number | null): { lat: number; lng: number } | null {
  const la = Number(lat);
  const ln = Number(lng);
  if (!Number.isFinite(la) || !Number.isFinite(ln)) return null;
  if (la === 0 && ln === 0) return null;
  if (Math.abs(la) > 90 || Math.abs(ln) > 180) return null;
  return { lat: la, lng: ln };
}

function formatTravel(seconds: number): string {
  const mins = Math.max(1, Math.round(seconds / 60));
  if (mins < 60) return mins === 1 ? '1 minuut' : `${mins} minuten`;
  const hours = Math.floor(mins / 60);
  const rest = mins % 60;
  const hourLabel = hours === 1 ? '1 uur' : `${hours} uur`;
  if (rest === 0) return hourLabel;
  return `${hourLabel} en ${rest} minuten`;
}

function formatPickupClock(ms: number): string {
  const when = new Date(ms);
  const time = when.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' });
  const today = new Date();
  const sameDay =
    when.getFullYear() === today.getFullYear() &&
    when.getMonth() === today.getMonth() &&
    when.getDate() === today.getDate();
  if (sameDay) return time;
  const day = when.toLocaleDateString('nl-NL', { weekday: 'short', day: 'numeric', month: 'short' });
  return `${day} ${time}`;
}

function nextAcceptedRide(rides: DriverActiveRide[]): DriverActiveRide | null {
  const now = Date.now();
  let best: DriverActiveRide | null = null;
  let bestMs = Infinity;
  for (const ride of rides) {
    const ms = pickupMs(ride);
    if (ms == null || ms < now - LATE_MS) continue;
    if (!coord(ride.pickup_lat, ride.pickup_lng)) continue;
    if (ms < bestMs) {
      best = ride;
      bestMs = ms;
    }
  }
  return best;
}

async function travelSeconds(
  from: { lat: number; lng: number },
  to: { lat: number; lng: number }
): Promise<number> {
  const key = [
    from.lat.toFixed(2),
    from.lng.toFixed(2),
    to.lat.toFixed(3),
    to.lng.toFixed(3),
  ].join(':');
  const cached = routeCache.get(key);
  if (cached && Date.now() - cached.at < ROUTE_CACHE_MS) return cached.seconds;
  const route = await fetchDrivingRoute(from, to);
  const seconds = Math.max(60, Math.round(route.duration_seconds));
  routeCache.set(key, { at: Date.now(), seconds });
  return seconds;
}

async function loadNotified(): Promise<Set<string>> {
  try {
    const raw = await SecureStore.getItemAsync(NOTIFIED_KEY);
    const list = raw ? JSON.parse(raw) : [];
    if (!Array.isArray(list)) return new Set();
    return new Set(list.map((item) => String(item)));
  } catch {
    return new Set();
  }
}

async function saveNotified(keys: Set<string>): Promise<void> {
  await SecureStore.setItemAsync(NOTIFIED_KEY, JSON.stringify(Array.from(keys).slice(-80)));
}

/**
 * Meld de chauffeur zodra die moet vertrekken om de volgende geaccepteerde klant
 * op tijd op te halen. Reistijd vanaf de huidige locatie; bij een lopende rit
 * ook via de bestemming van die rit.
 */
export async function alertNextPickupDeparture(input: {
  scheduled: DriverActiveRide[];
  activeRide?: DriverActiveRide | null;
  origin?: { lat: number; lng: number } | null;
}): Promise<PickupDepartureNotice | null> {
  const now = Date.now();
  if (now - lastCheck < CHECK_EVERY_MS) return null;
  const next = nextAcceptedRide(input.scheduled || []);
  if (!next) return null;
  const when = pickupMs(next);
  const pickup = coord(next.pickup_lat, next.pickup_lng);
  if (when == null || !pickup) return null;

  lastCheck = now;

  let origin = input.origin && coord(input.origin.lat, input.origin.lng);
  if (!origin) {
    try {
      const last = await Location.getLastKnownPositionAsync();
      origin = last ? coord(last.coords.latitude, last.coords.longitude) : null;
    } catch {
      origin = null;
    }
  }
  if (!origin) return null;

  const active = input.activeRide;
  const dropoff =
    active && Number(active.id) !== Number(next.id)
      ? coord(active.dropoff_lat, active.dropoff_lng)
      : null;
  let seconds = await travelSeconds(origin, dropoff || pickup);
  let viaCurrentRide = false;
  if (dropoff) {
    seconds += await travelSeconds(dropoff, pickup);
    viaCurrentRide = true;
  }

  const leaveAt = when - seconds * 1000 - EARLY_MS;
  if (now < leaveAt || now > when + LATE_MS) return null;

  const dedupe = `${next.id}:${String(next.pickup_at || '')}`;
  const notified = await loadNotified();
  if (notified.has(dedupe)) return null;
  notified.add(dedupe);
  await saveNotified(notified);

  const name = String(next.customer_name || '').trim() || 'de klant';
  const address = String(next.pickup_address || '').trim();
  const where = address ? ` op ${address}` : '';
  const via = viaCurrentRide ? ', inclusief je huidige rit,' : '';
  const notice: PickupDepartureNotice = {
    title: 'Tijd om te vertrekken',
    body: `${name} ophalen om ${formatPickupClock(when)}${where}. Je bent${via} ongeveer ${formatTravel(seconds)} onderweg.`,
    rideId: Number(next.id),
  };
  await notifyPickupDeparture(notice);
  return notice;
}
