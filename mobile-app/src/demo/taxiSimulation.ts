import { useSyncExternalStore } from 'react';
import type { NearbyTaxi } from '../api/customer';
import { decodePolyline, type LatLng } from '../geo/route';

/**
 * Demo-simulatie (client-side, superadmin): taxi’s rijden over echte wegen (OSRM)
 * door Twente (Enschede, Hengelo, Almelo, …). Aantal fluctueert ≈5–18.
 * Geclaimde taxi verdwijnt uit de vloot (andere “klanten” zien die niet) en rijdt
 * alleen voor de boekende klant naar het ophaalpunt.
 */

export const DEMO_TAXI_TARGET = 20;
/** Altijd zichtbaar rond de klant / kaartcentrum. */
const NEARBY_COUNT = 6;
/** Extra taxi’s in het stadsbeeld (zichtbaar zonder uitzoomen). */
const MID_COUNT = 10;
const NEARBY_MIN_M = 180;
const NEARBY_MAX_M = 1400;
const MID_MIN_M = 1500;
const MID_MAX_M = 4500;
const MIN_ONLINE = 10;
const MAX_ONLINE = 20;
const TICK_MS = 1000;
/** Iets sneller dan echt verkeer zodat beweging op de kaart zichtbaar is. */
const MIN_SPEED_MPS = 14;
const MAX_SPEED_MPS = 26;
const EVENT_EVERY_MS: [number, number] = [2200, 4800];
const BURST_EXTRA_MS: [number, number] = [400, 1100];
const HEADING_STEP = 10;
const M_PER_DEG_LAT = 111320;
/** Max gelijktijdige OSRM-requests (publieke server vriendelijk houden). */
const ROUTE_CONCURRENCY = 3;

/** Ankerpunten in Twente — taxi’s mogen hiernaartoe (en ertussen) rijden. */
const TWENTE_HUBS: { lat: number; lng: number }[] = [
  { lat: 52.2215, lng: 6.8936 }, // Enschede centrum
  { lat: 52.239, lng: 6.857 }, // Enschede-west / UT-buurt
  { lat: 52.208, lng: 6.895 }, // Enschede-zuid
  { lat: 52.2658, lng: 6.7931 }, // Hengelo
  { lat: 52.278, lng: 6.81 }, // Hengelo-noord
  { lat: 52.357, lng: 6.6626 }, // Almelo
  { lat: 52.34, lng: 6.65 }, // Almelo-zuid
  { lat: 52.3134, lng: 6.9292 }, // Oldenzaal
  { lat: 52.3014, lng: 6.755 }, // Borne
  { lat: 52.1567, lng: 6.7389 }, // Haaksbergen
  { lat: 52.215, lng: 6.97 }, // Glanerbrug
  { lat: 52.2608, lng: 7.0056 }, // Losser
  { lat: 52.25, lng: 6.7 }, // Delden-ish
];

type PathPoint = { lat: number; lng: number };

type DemoTaxi = {
  id: string;
  lat: number;
  lng: number;
  heading: number;
  speed: number;
  path: PathPoint[];
  pathIndex: number;
  routing: boolean;
  /** near = dichtbij klant, mid = stadsbeeld, far = Hengelo/Almelo e.d. */
  band: 'near' | 'mid' | 'far';
};

type AssignedDemoTaxi = DemoTaxi & {
  pickup: PathPoint;
  arrived: boolean;
};

let center: PathPoint | null = null;
let taxis: DemoTaxi[] = [];
let assigned: AssignedDemoTaxi | null = null;
let arriveClearTimer: ReturnType<typeof setTimeout> | null = null;
let desiredOnline = DEMO_TAXI_TARGET;
let seq = 0;
let tickTimer: ReturnType<typeof setInterval> | null = null;
let eventTimer: ReturnType<typeof setTimeout> | null = null;
let running = false;
const burstTimers = new Set<ReturnType<typeof setTimeout>>();
const listeners = new Set<() => void>();

type RouteJob = {
  taxiId: string;
  from: PathPoint;
  to: PathPoint;
  forAssigned: boolean;
};
const routeQueue: RouteJob[] = [];
let routeInFlight = 0;

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
const randInt = (min: number, max: number) =>
  Math.floor(min + Math.random() * (max - min + 1));
const norm = (deg: number) => ((deg % 360) + 360) % 360;
const quantize = (deg: number) => norm(Math.round(deg / HEADING_STEP) * HEADING_STEP);

function distanceMeters(a: PathPoint, b: PathPoint) {
  const dLat = (b.lat - a.lat) * M_PER_DEG_LAT;
  const dLng = (b.lng - a.lng) * M_PER_DEG_LAT * Math.cos((a.lat * Math.PI) / 180);
  return Math.hypot(dLat, dLng);
}

function bearingTo(from: PathPoint, to: PathPoint) {
  const dLat = (to.lat - from.lat) * M_PER_DEG_LAT;
  const dLng = (to.lng - from.lng) * M_PER_DEG_LAT * Math.cos((from.lat * Math.PI) / 180);
  return norm((Math.atan2(dLng, dLat) * 180) / Math.PI);
}

function jitterAround(hub: PathPoint, meters = 450): PathPoint {
  const bearing = rand(0, 360);
  const dist = rand(40, meters);
  const rad = (bearing * Math.PI) / 180;
  return {
    lat: hub.lat + (dist * Math.cos(rad)) / M_PER_DEG_LAT,
    lng: hub.lng + (dist * Math.sin(rad)) / (M_PER_DEG_LAT * Math.cos((hub.lat * Math.PI) / 180)),
  };
}

function pickHub(avoid?: PathPoint | null): PathPoint {
  if (!avoid) return TWENTE_HUBS[randInt(0, TWENTE_HUBS.length - 1)];
  let best = TWENTE_HUBS[0];
  let tries = 0;
  while (tries < 8) {
    const hub = TWENTE_HUBS[randInt(0, TWENTE_HUBS.length - 1)];
    if (distanceMeters(hub, avoid) > 2500) return hub;
    best = hub;
    tries += 1;
  }
  return best;
}

function jitterRing(hub: PathPoint, minM: number, maxM: number): PathPoint {
  const bearing = rand(0, 360);
  const dist = rand(minM, maxM);
  const rad = (bearing * Math.PI) / 180;
  return {
    lat: hub.lat + (dist * Math.cos(rad)) / M_PER_DEG_LAT,
    lng: hub.lng + (dist * Math.sin(rad)) / (M_PER_DEG_LAT * Math.cos((hub.lat * Math.PI) / 180)),
  };
}

function pickDestination(from: PathPoint, band: DemoTaxi['band']): PathPoint {
  const anchor = center ?? from;
  if (band === 'near') {
    if (Math.random() < 0.9) return jitterRing(anchor, NEARBY_MIN_M, NEARBY_MAX_M + 300);
    return jitterRing(anchor, MID_MIN_M, MID_MAX_M);
  }
  if (band === 'mid') {
    if (Math.random() < 0.85) return jitterRing(anchor, MID_MIN_M, MID_MAX_M);
    return jitterRing(anchor, NEARBY_MIN_M, NEARBY_MAX_M);
  }
  // far: Hengelo / Almelo / …, soms terug naar de stad.
  if (Math.random() < 0.75) return jitterAround(pickHub(from), 700);
  return jitterRing(anchor, MID_MIN_M, MID_MAX_M + 1200);
}

async function fetchRoadPath(from: PathPoint, to: PathPoint): Promise<PathPoint[]> {
  const ctrl = typeof AbortController !== 'undefined' ? new AbortController() : null;
  const timer = ctrl ? setTimeout(() => ctrl.abort(), 6000) : null;
  try {
    const url =
      `https://router.project-osrm.org/route/v1/driving/` +
      `${from.lng},${from.lat};${to.lng},${to.lat}` +
      `?overview=full&geometries=polyline&steps=false`;
    const res = await fetch(url, ctrl ? { signal: ctrl.signal } : undefined);
    if (!res.ok) return [from, to];
    const data = await res.json();
    const geometry = data?.routes?.[0]?.geometry;
    if (!geometry) return [from, to];
    const coords = decodePolyline(String(geometry));
    if (coords.length < 2) return [from, to];
    return coords.map((c) => ({ lat: c.latitude, lng: c.longitude }));
  } catch {
    return [from, to];
  } finally {
    if (timer) clearTimeout(timer);
  }
}

function applyPath(taxiId: string, path: PathPoint[], forAssigned: boolean) {
  if (!running || path.length < 2) return;
  const heading = bearingTo(path[0], path[1]);
  if (forAssigned) {
    if (!assigned || assigned.id !== taxiId) return;
    // Huidige positie behouden — niet terugspringen naar path[0].
    assigned = {
      ...assigned,
      heading,
      path,
      pathIndex: 0,
      routing: false,
      arrived: false,
    };
    emit();
    return;
  }
  const idx = taxis.findIndex((taxi) => taxi.id === taxiId);
  if (idx < 0) return;
  const taxi = taxis[idx];
  taxis = [
    ...taxis.slice(0, idx),
    {
      ...taxi,
      heading,
      path,
      pathIndex: 0,
      routing: false,
    },
    ...taxis.slice(idx + 1),
  ];
  emit();
}

function pumpRouteQueue() {
  while (running && routeInFlight < ROUTE_CONCURRENCY && routeQueue.length > 0) {
    const job = routeQueue.shift()!;
    routeInFlight += 1;
    void (async () => {
      const path = await fetchRoadPath(job.from, job.to);
      routeInFlight -= 1;
      applyPath(job.taxiId, path, job.forAssigned);
      pumpRouteQueue();
    })();
  }
}

function enqueueRoute(
  taxiId: string,
  from: PathPoint,
  to: PathPoint,
  forAssigned: boolean,
  priority = false
) {
  // Vervang openstaande job voor dezelfde taxi.
  for (let i = routeQueue.length - 1; i >= 0; i -= 1) {
    if (routeQueue[i].taxiId === taxiId && routeQueue[i].forAssigned === forAssigned) {
      routeQueue.splice(i, 1);
    }
  }
  const job: RouteJob = { taxiId, from, to, forAssigned };
  if (priority || forAssigned) routeQueue.unshift(job);
  else routeQueue.push(job);
  pumpRouteQueue();
}

/** Eenvoudige rechte-lijn fallback zodat markers meteen bewegen zonder OSRM. */
function buildStraightPath(from: PathPoint, to: PathPoint): PathPoint[] {
  const steps = 12;
  const path: PathPoint[] = [from];
  for (let i = 1; i <= steps; i += 1) {
    const f = i / steps;
    path.push({
      lat: from.lat + (to.lat - from.lat) * f,
      lng: from.lng + (to.lng - from.lng) * f,
    });
  }
  return path;
}

function requestNextCruise(taxi: DemoTaxi) {
  if (taxi.routing) return;
  const from = { lat: taxi.lat, lng: taxi.lng };
  const to = pickDestination(taxi, taxi.band);
  const straight = buildStraightPath(from, to);
  // Mutatie op het object: werkt ook vóór de taxi in `taxis` staat (makeTaxi).
  taxi.routing = true;
  taxi.path = straight;
  taxi.pathIndex = 0;
  taxi.heading = bearingTo(straight[0], straight[1]);
  const idx = taxis.findIndex((entry) => entry.id === taxi.id);
  if (idx >= 0) {
    taxis = [
      ...taxis.slice(0, idx),
      {
        ...taxis[idx],
        routing: true,
        path: straight,
        pathIndex: 0,
        heading: taxi.heading,
      },
      ...taxis.slice(idx + 1),
    ];
  }
  // OSRM vervangt het pad over de weg; applyPath zet routing weer false.
  enqueueRoute(taxi.id, from, to, false, taxi.band !== 'far');
}

function advanceAlongPath<T extends DemoTaxi>(taxi: T, dtSec: number): T {
  if (taxi.path.length < 2 || taxi.pathIndex >= taxi.path.length - 1) {
    return taxi;
  }
  let remaining = Math.max(taxi.speed, MIN_SPEED_MPS) * dtSec;
  let index = taxi.pathIndex;
  let lat = taxi.lat;
  let lng = taxi.lng;
  let heading = taxi.heading;

  while (remaining > 0 && index < taxi.path.length - 1) {
    const next = taxi.path[index + 1];
    const dist = distanceMeters({ lat, lng }, next);
    if (dist < 0.5) {
      index += 1;
      lat = next.lat;
      lng = next.lng;
      continue;
    }
    heading = bearingTo({ lat, lng }, next);
    if (dist <= remaining) {
      remaining -= dist;
      lat = next.lat;
      lng = next.lng;
      index += 1;
    } else {
      const f = remaining / dist;
      lat += (next.lat - lat) * f;
      lng += (next.lng - lng) * f;
      remaining = 0;
    }
  }

  return { ...taxi, lat, lng, heading, pathIndex: index };
}

function makeTaxi(
  at: PathPoint | null | undefined,
  band: DemoTaxi['band']
): DemoTaxi {
  const anchor = at ?? center ?? TWENTE_HUBS[0];
  const hub =
    band === 'near'
      ? jitterRing(anchor, NEARBY_MIN_M, NEARBY_MAX_M)
      : band === 'mid'
        ? jitterRing(anchor, MID_MIN_M, MID_MAX_M)
        : jitterAround(pickHub(anchor), 700);
  seq += 1;
  const taxi: DemoTaxi = {
    id: `demo-${seq}`,
    ...hub,
    heading: rand(0, 360),
    speed: rand(MIN_SPEED_MPS, MAX_SPEED_MPS),
    path: [],
    pathIndex: 0,
    routing: false,
    band,
  };
  requestNextCruise(taxi);
  return taxi;
}

function countBand(band: DemoTaxi['band']) {
  return taxis.filter((taxi) => taxi.band === band).length;
}

function clearAssignedInternal() {
  if (arriveClearTimer) {
    clearTimeout(arriveClearTimer);
    arriveClearTimer = null;
  }
  if (!assigned) return false;
  assigned = null;
  return true;
}

function stepAssigned(taxi: AssignedDemoTaxi): AssignedDemoTaxi {
  if (taxi.arrived) return taxi;

  if (taxi.path.length >= 2 && taxi.pathIndex < taxi.path.length - 1) {
    const next = advanceAlongPath(taxi, TICK_MS / 1000);
    const nearEnd = next.pathIndex >= next.path.length - 1;
    const distPickup = distanceMeters(next, next.pickup);
    if (nearEnd || distPickup < 35) {
      if (!arriveClearTimer) {
        arriveClearTimer = setTimeout(() => {
          arriveClearTimer = null;
          if (clearAssignedInternal()) emit();
        }, 8000);
      }
      return {
        ...next,
        lat: next.pickup.lat,
        lng: next.pickup.lng,
        speed: 0,
        arrived: true,
        pathIndex: Math.max(0, next.path.length - 1),
      };
    }
    return { ...next, arrived: false };
  }

  if (!taxi.routing) {
    taxi.routing = true;
    enqueueRoute(taxi.id, { lat: taxi.lat, lng: taxi.lng }, taxi.pickup, true);
  }

  // Wachten op route: blijf staan (niet door weilanden zweven).
  return taxi;
}

function pickNewDesired(): number {
  const roll = Math.random();
  if (roll < 0.25) return randInt(MIN_ONLINE, MIN_ONLINE + 3); // ~10–13
  if (roll < 0.55) return randInt(14, 17);
  if (roll < 0.85) return MAX_ONLINE; // druk
  return randInt(MIN_ONLINE + 1, MAX_ONLINE - 1);
}

function removeOneTaxi() {
  if (taxis.length <= MIN_ONLINE) return false;
  // Haal bij voorkeur far/mid weg, houd near in beeld.
  const far = taxis.filter((taxi) => taxi.band === 'far');
  const mid = taxis.filter((taxi) => taxi.band === 'mid');
  const pool =
    far.length > 0
      ? far
      : mid.length > 0 && countBand('near') >= NEARBY_COUNT
        ? mid
        : taxis;
  const gone = pool[Math.floor(Math.random() * pool.length)];
  taxis = taxis.filter((taxi) => taxi.id !== gone.id);
  return true;
}

function addOneTaxi() {
  if (!center || taxis.length >= MAX_ONLINE) return false;
  const band: DemoTaxi['band'] =
    countBand('near') < NEARBY_COUNT
      ? 'near'
      : countBand('mid') < MID_COUNT
        ? 'mid'
        : 'far';
  taxis = [...taxis, makeTaxi(center, band)];
  return true;
}

function runFleetEvent() {
  if (!center || !running) return;

  if (Math.random() < 0.22 || Math.abs(taxis.length - desiredOnline) < 1) {
    desiredOnline = pickNewDesired();
  }

  const deficit = desiredOnline - taxis.length;
  if (deficit > 0) {
    if (addOneTaxi()) emit();
  } else if (deficit < 0) {
    const gap = Math.abs(deficit);
    const burst =
      gap >= 4 && Math.random() < 0.45 ? Math.min(3, gap, taxis.length - MIN_ONLINE) : 1;
    if (removeOneTaxi()) emit();
    for (let i = 1; i < burst; i += 1) {
      const delay = rand(...BURST_EXTRA_MS) * i;
      const timer = setTimeout(() => {
        burstTimers.delete(timer);
        if (!running || !center) return;
        if (taxis.length > Math.max(MIN_ONLINE, desiredOnline)) {
          if (removeOneTaxi()) emit();
        }
      }, delay);
      burstTimers.add(timer);
    }
  }

  scheduleFleetEvent();
}

function scheduleFleetEvent() {
  if (eventTimer) clearTimeout(eventTimer);
  eventTimer = setTimeout(runFleetEvent, rand(...EVENT_EVERY_MS));
}

export function startDemoSimulation(at: { lat: number; lng: number }) {
  stopDemoSimulation();
  running = true;
  center = { lat: at.lat, lng: at.lng };
  desiredOnline = DEMO_TAXI_TARGET;
  const farCount = Math.max(0, DEMO_TAXI_TARGET - NEARBY_COUNT - MID_COUNT);
  // near + mid eerst (zichtbaar in stadszoom), far mag verder weg.
  taxis = [
    ...Array.from({ length: NEARBY_COUNT }, () => makeTaxi(center!, 'near')),
    ...Array.from({ length: MID_COUNT }, () => makeTaxi(center!, 'mid')),
    ...Array.from({ length: farCount }, () => makeTaxi(center!, 'far')),
  ];
  emit();
  tickTimer = setInterval(() => {
    if (!running) return;
    taxis = taxis.map((taxi) => {
      let next = advanceAlongPath(taxi, TICK_MS / 1000);
      const pathDone = next.path.length < 2 || next.pathIndex >= next.path.length - 1;
      if (pathDone && !next.routing) {
        requestNextCruise(next);
      }
      return next;
    });
    if (assigned) assigned = stepAssigned(assigned);
    // Houd near+mid op peil zodat de kaart nooit “leeg” oogt.
    if (countBand('near') < NEARBY_COUNT && taxis.length < MAX_ONLINE) {
      taxis = [...taxis, makeTaxi(center!, 'near')];
    } else if (countBand('mid') < MID_COUNT && taxis.length < MAX_ONLINE) {
      taxis = [...taxis, makeTaxi(center!, 'mid')];
    }
    emit();
  }, TICK_MS);
  scheduleFleetEvent();
}

/**
 * Verschuif het “rondom klant”-centrum (bijv. na GPS). Herplaatst near/mid
 * taxi’s als het centrum significant is verplaatst.
 */
export function updateDemoSimulationCenter(at: { lat: number; lng: number }) {
  if (!running) return;
  const next = { lat: at.lat, lng: at.lng };
  if (center && distanceMeters(center, next) < 350) {
    center = next;
    return;
  }
  center = next;
  const keepFar = taxis.filter((taxi) => taxi.band === 'far');
  taxis = [
    ...Array.from({ length: NEARBY_COUNT }, () => makeTaxi(center, 'near')),
    ...Array.from({ length: MID_COUNT }, () => makeTaxi(center, 'mid')),
    ...keepFar,
  ].slice(0, MAX_ONLINE);
  emit();
}

export function stopDemoSimulation() {
  running = false;
  if (tickTimer) clearInterval(tickTimer);
  if (eventTimer) clearTimeout(eventTimer);
  burstTimers.forEach((timer) => clearTimeout(timer));
  burstTimers.clear();
  routeQueue.length = 0;
  clearAssignedInternal();
  tickTimer = null;
  eventTimer = null;
  center = null;
  desiredOnline = DEMO_TAXI_TARGET;
  taxis = [];
  emit();
}

/**
 * Demo-boeking: dichtstbijzijnde vrije taxi verdwijnt uit de vloot (andere klanten
 * zien die niet meer) en rijdt over de weg naar het ophaalpunt van deze klant.
 */
export function claimNearestDemoTaxi(at: { lat: number; lng: number }) {
  if (assigned) {
    return {
      distanceKm: distanceMeters(assigned, at) / 1000,
      taxiId: assigned.id,
    };
  }
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
  const pickup = { lat: at.lat, lng: at.lng };
  assigned = {
    ...best,
    speed: Math.max(best.speed, MIN_SPEED_MPS),
    pickup,
    arrived: false,
    path: [],
    pathIndex: 0,
    routing: true,
  };
  enqueueRoute(assigned.id, { lat: best.lat, lng: best.lng }, pickup, true);
  desiredOnline = Math.max(MIN_ONLINE, Math.min(desiredOnline, taxis.length + randInt(0, 2)));
  emit();
  return { distanceKm: bestMeters / 1000, taxiId: best.id };
}

export function clearDemoAssignedTaxi() {
  if (clearAssignedInternal()) emit();
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

let assignedCache: { source: AssignedDemoTaxi | null; value: NearbyTaxi | null } = {
  source: null,
  value: null,
};
function getAssignedSnapshot(): NearbyTaxi | null {
  if (assignedCache.source !== assigned) {
    assignedCache = {
      source: assigned,
      value: assigned ? toNearby(assigned) : null,
    };
  }
  return assignedCache.value;
}

function remainingAssignedRoute(): LatLng[] {
  if (!assigned || assigned.path.length < 2) return [];
  const pts: LatLng[] = [{ latitude: assigned.lat, longitude: assigned.lng }];
  for (let i = assigned.pathIndex + 1; i < assigned.path.length; i += 1) {
    const p = assigned.path[i];
    pts.push({ latitude: p.lat, longitude: p.lng });
  }
  return pts;
}

let routeCache: { source: AssignedDemoTaxi | null; pathIndex: number; value: LatLng[] } = {
  source: null,
  pathIndex: -1,
  value: [],
};
function getAssignedRouteSnapshot(): LatLng[] {
  if (!assigned) {
    if (routeCache.value.length) routeCache = { source: null, pathIndex: -1, value: [] };
    return routeCache.value;
  }
  if (routeCache.source !== assigned || routeCache.pathIndex !== assigned.pathIndex) {
    routeCache = {
      source: assigned,
      pathIndex: assigned.pathIndex,
      value: remainingAssignedRoute(),
    };
  }
  return routeCache.value;
}

export function useDemoTaxis(): NearbyTaxi[] {
  return useSyncExternalStore(subscribe, getNearbySnapshot, getNearbySnapshot);
}

export function useDemoAssignedTaxi(): NearbyTaxi | null {
  return useSyncExternalStore(subscribe, getAssignedSnapshot, getAssignedSnapshot);
}

/** Resterende wegroute van de toegewezen demo-taxi naar ophalen. */
export function useDemoAssignedRoute(): LatLng[] {
  return useSyncExternalStore(subscribe, getAssignedRouteSnapshot, getAssignedRouteSnapshot);
}

export function useDemoTaxiCount(): number {
  return useSyncExternalStore(
    subscribe,
    () => taxis.length,
    () => taxis.length
  );
}
