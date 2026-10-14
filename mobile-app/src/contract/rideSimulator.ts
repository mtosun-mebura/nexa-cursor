import type { SimulatorStop } from '../api/contractNavigation';
import type { LatLng } from '../geo/route';

export type SimCursor = {
  segment: number;
  meters: number;
};

function haversineMeters(a: LatLng, b: LatLng): number {
  const r = 6371000;
  const dLat = ((b.latitude - a.latitude) * Math.PI) / 180;
  const dLng = ((b.longitude - a.longitude) * Math.PI) / 180;
  const lat1 = (a.latitude * Math.PI) / 180;
  const lat2 = (b.latitude * Math.PI) / 180;
  const h =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
  return 2 * r * Math.asin(Math.min(1, Math.sqrt(h)));
}

export function lineLength(line: LatLng[]): number {
  let total = 0;
  for (let i = 1; i < line.length; i += 1) {
    total += haversineMeters(line[i - 1], line[i]);
  }
  return total;
}

/** Schuif `stepMeters` over de lijn. `done` zodra het einde is bereikt. */
export function advanceAlong(
  line: LatLng[],
  fromMeters: number,
  stepMeters: number
): { point: LatLng; meters: number; done: boolean } {
  if (line.length === 0) {
    return { point: { latitude: 0, longitude: 0 }, meters: fromMeters, done: true };
  }
  if (line.length === 1) {
    return { point: line[0], meters: 0, done: true };
  }

  const target = Math.max(0, fromMeters + Math.max(0, stepMeters));
  let walked = 0;
  for (let i = 1; i < line.length; i += 1) {
    const span = haversineMeters(line[i - 1], line[i]);
    if (walked + span >= target || i === line.length - 1) {
      const into = span <= 0 ? 1 : Math.min(1, (target - walked) / span);
      const point = {
        latitude: line[i - 1].latitude + (line[i].latitude - line[i - 1].latitude) * into,
        longitude: line[i - 1].longitude + (line[i].longitude - line[i - 1].longitude) * into,
      };
      const done = into >= 1 && i === line.length - 1;
      return { point, meters: done ? walked + span : target, done };
    }
    walked += span;
  }

  const last = line[line.length - 1];
  return { point: last, meters: target, done: true };
}

export function stopLabel(stop: SimulatorStop | undefined): string {
  if (!stop) return 'Rit klaar';
  if (stop.kind === 'pickup') return `Ophalen ${stop.name}`;
  return 'Naar de bestemming';
}
