import { useSyncExternalStore } from 'react';
import type { NearbyTaxi } from '../api/customer';

/**
 * Demo-simulatie (alleen client-side, superadmin): gesimuleerde taxi's rond een
 * punt. Niets hiervan gaat naar de backend of naar echte chauffeurs.
 */

export const DEMO_TAXI_TARGET = 20;
const TICK_MS = 1500;
const MIN_RADIUS_KM = 0.3;
const MAX_RADIUS_KM = 4;
/** Versneld t.o.v. echt verkeer, anders beweegt een taxi op kaartzoom nauwelijks. */
const MIN_SPEED_MPS = 20;
const MAX_SPEED_MPS = 40;
const ACCEPT_EVERY_MS: [number, number] = [3000, 6000];
const RESPAWN_AFTER_MS: [number, number] = [2000, 5000];
/** Beperkt het aantal gedraaide Android-bitmaps (zie androidTaxiUri). */
const HEADING_STEP = 15;
const M_PER_DEG_LAT = 111320;

type DemoTaxi = { id: string; lat: number; lng: number; heading: number; speed: number };

let center: { lat: number; lng: number } | null = null;
let taxis: DemoTaxi[] = [];
let seq = 0;
let pendingSpawns = 0;
let tickTimer: ReturnType<typeof setInterval> | null = null;
let acceptTimer: ReturnType<typeof setTimeout> | null = null;
const spawnTimers = new Set<ReturnType<typeof setTimeout>>();
const listeners = new Set<() => void>();

function emit() {
  listeners.forEach((listener) => listener());
}

function subscribe(listener: () => void) {
  listeners.add(listener);
  return () => {
    listeners.delete(listener);
  };
}

const rand = (min: number, max: number) => min + Math.random() * (max - min);
const norm = (deg: number) => ((deg % 360) + 360) % 360;
const quantize = (deg: number) => norm(Math.round(deg / HEADING_STEP) * HEADING_STEP);

function offsetFromCenter(c: { lat: number; lng: number }, meters: number, bearing: number) {
  const rad = (bearing * Math.PI) / 180;
  return {
    lat: c.lat + (meters * Math.cos(rad)) / M_PER_DEG_LAT,
    lng: c.lng + (meters * Math.sin(rad)) / (M_PER_DEG_LAT * Math.cos((c.lat * Math.PI) / 180)),
  };
}

function distanceMeters(a: { lat: number; lng: number }, b: { lat: number; lng: number }) {
  const dLat = (b.lat - a.lat) * M_PER_DEG_LAT;
  const dLng = (b.lng - a.lng) * M_PER_DEG_LAT * Math.cos((a.lat * Math.PI) / 180);
  return Math.hypot(dLat, dLng);
}

function bearingTo(from: { lat: number; lng: number }, to: { lat: number; lng: number }) {
  const dLat = (to.lat - from.lat) * M_PER_DEG_LAT;
  const dLng = (to.lng - from.lng) * M_PER_DEG_LAT * Math.cos((from.lat * Math.PI) / 180);
  return norm((Math.atan2(dLng, dLat) * 180) / Math.PI);
}

function makeTaxi(c: { lat: number; lng: number }): DemoTaxi {
  // sqrt: gelijkmatige spreiding over de oppervlakte, niet geklonterd in het midden.
  const km = Math.sqrt(rand(MIN_RADIUS_KM ** 2, MAX_RADIUS_KM ** 2));
  const pos = offsetFromCenter(c, km * 1000, rand(0, 360));
  seq += 1;
  return {
    id: `demo-${seq}`,
    ...pos,
    heading: rand(0, 360),
    speed: rand(MIN_SPEED_MPS, MAX_SPEED_MPS),
  };
}

function step(taxi: DemoTaxi, c: { lat: number; lng: number }): DemoTaxi {
  let heading = taxi.heading;
  if (distanceMeters(c, taxi) > MAX_RADIUS_KM * 1000) {
    heading = bearingTo(taxi, c) + rand(-25, 25);
  } else if (Math.random() < 0.25) {
    heading += (Math.random() < 0.5 ? -1 : 1) * rand(20, 90);
  } else {
    heading += rand(-8, 8);
  }
  heading = norm(heading);
  const next = offsetFromCenter(taxi, taxi.speed * (TICK_MS / 1000), heading);
  return { ...taxi, ...next, heading };
}

function scheduleSpawn() {
  pendingSpawns += 1;
  const timer = setTimeout(() => {
    spawnTimers.delete(timer);
    pendingSpawns -= 1;
    if (!center) return;
    taxis = [...taxis, makeTaxi(center)];
    emit();
  }, rand(...RESPAWN_AFTER_MS));
  spawnTimers.add(timer);
}

function scheduleAccept() {
  acceptTimer = setTimeout(() => {
    // Een willekeurige taxi "accepteert een rit" en verdwijnt; er komt later een nieuwe.
    if (taxis.length > 0) {
      const gone = taxis[Math.floor(Math.random() * taxis.length)];
      taxis = taxis.filter((taxi) => taxi.id !== gone.id);
      scheduleSpawn();
      emit();
    }
    scheduleAccept();
  }, rand(...ACCEPT_EVERY_MS));
}

export function startDemoSimulation(at: { lat: number; lng: number }) {
  stopDemoSimulation();
  center = at;
  taxis = Array.from({ length: DEMO_TAXI_TARGET }, () => makeTaxi(at));
  emit();
  tickTimer = setInterval(() => {
    if (!center) return;
    const c = center;
    taxis = taxis.map((taxi) => step(taxi, c));
    emit();
  }, TICK_MS);
  scheduleAccept();
}

export function stopDemoSimulation() {
  if (tickTimer) clearInterval(tickTimer);
  if (acceptTimer) clearTimeout(acceptTimer);
  spawnTimers.forEach((timer) => clearTimeout(timer));
  spawnTimers.clear();
  tickTimer = null;
  acceptTimer = null;
  pendingSpawns = 0;
  center = null;
  if (taxis.length > 0) {
    taxis = [];
    emit();
  }
}

/**
 * Demo-boeking: de dichtstbijzijnde taxi "accepteert" en verdwijnt.
 * Geeft null als er (nog) geen taxi's zijn.
 */
export function claimNearestDemoTaxi(at: { lat: number; lng: number }) {
  if (taxis.length === 0) return null;
  let best = taxis[0];
  let bestMeters = distanceMeters(at, best);
  for (const taxi of taxis) {
    const meters = distanceMeters(at, taxi);
    if (meters < bestMeters) {
      best = taxi;
      bestMeters = meters;
    }
  }
  taxis = taxis.filter((taxi) => taxi.id !== best.id);
  scheduleSpawn();
  emit();
  return { distanceKm: bestMeters / 1000 };
}

const toNearby = (taxi: DemoTaxi): NearbyTaxi => ({
  id: taxi.id,
  lat: taxi.lat,
  lng: taxi.lng,
  heading: quantize(taxi.heading),
  busy: false,
  distance_km: null,
});

let nearbyCache: { source: DemoTaxi[]; value: NearbyTaxi[] } = { source: [], value: [] };
function getNearbySnapshot(): NearbyTaxi[] {
  if (nearbyCache.source !== taxis) nearbyCache = { source: taxis, value: taxis.map(toNearby) };
  return nearbyCache.value;
}

/** Positie-updates; alleen de component die dit aanroept rendert elke tick opnieuw. */
export function useDemoTaxis(): NearbyTaxi[] {
  return useSyncExternalStore(subscribe, getNearbySnapshot, getNearbySnapshot);
}

/** Alleen het aantal: rendert opnieuw bij accepteren/verschijnen, niet bij elke beweging. */
export function useDemoTaxiCount(): number {
  return useSyncExternalStore(
    subscribe,
    () => taxis.length,
    () => taxis.length
  );
}
