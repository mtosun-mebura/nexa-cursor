import type { ContractDayItem, ContractNavStop, ContractToday } from './contract';

const SKIP = new Set(['absent', 'completed', 'skipped', 'none']);
const IN_PROGRESS = new Set(['en_route', 'arrived', 'picked_up']);
const WAVE_GRACE_MS = 90 * 60 * 1000;
const HEEN_FALLBACK_END_HOUR = 12;

type WaveRow = {
  name: string;
  leg_key: string;
  leg_label: string;
  status_key: string;
  planned_at: string;
  picked_up: boolean;
  pickup_address: string;
  pickup_lat?: number | null;
  pickup_lng?: number | null;
  destination_address: string;
  destination_lat?: number | null;
  destination_lng?: number | null;
};

export type ActiveNavigation = NonNullable<ContractToday['navigation']>;

function normalizeLegKey(key?: string | null, label?: string | null): string {
  const k = String(key || '').toLowerCase();
  if (k === 'retour' || /terug|retour/i.test(String(label || ''))) return 'retour';
  if (k === 'heen' || /heen/i.test(String(label || ''))) return 'heen';
  return k || 'heen';
}

function displayLabel(legKey: string): string {
  if (legKey === 'retour') return 'Terugweg';
  if (legKey === 'heen') return 'Heenweg';
  return 'Rit';
}

function parsePlanned(raw?: string | null): Date | null {
  const text = String(raw || '').trim();
  if (!text) return null;
  const d = new Date(text);
  return Number.isNaN(d.getTime()) ? null : d;
}

function collectRows(items: ContractDayItem[]): WaveRow[] {
  const rows: WaveRow[] = [];
  for (const item of items) {
    if (String(item.status_key || '').toLowerCase() === 'absent') continue;
    const name = String(item.name || '').trim();
    const legs = item.legs && item.legs.length > 0 ? item.legs : null;
    if (!legs) continue;

    for (const leg of legs) {
      const status = String(leg.status_key || '').toLowerCase();
      if (SKIP.has(status)) continue;
      const legKey = normalizeLegKey(leg.leg_key, leg.leg_label);
      rows.push({
        name,
        leg_key: legKey,
        leg_label: displayLabel(legKey),
        status_key: status,
        planned_at: String(leg.planned_at || ''),
        picked_up: Boolean(leg.picked_up),
        pickup_address: String(leg.pickup_address || item.pickup_address || '').trim(),
        pickup_lat: leg.pickup_lat ?? item.pickup_lat,
        pickup_lng: leg.pickup_lng ?? item.pickup_lng,
        destination_address: String(leg.destination_address || item.destination_address || '').trim(),
        destination_lat: leg.destination_lat,
        destination_lng: leg.destination_lng,
      });
    }
  }

  rows.sort((a, b) => {
    const cmp = a.planned_at.localeCompare(b.planned_at);
    return cmp !== 0 ? cmp : a.name.localeCompare(b.name);
  });
  return rows;
}

function waveIsInProgress(wave: WaveRow[]): boolean {
  return wave.some((row) => IN_PROGRESS.has(row.status_key));
}

function waveHasOpenStops(wave: WaveRow[]): boolean {
  return wave.some((row) => !row.picked_up && row.pickup_address !== '');
}

function waveEarliest(wave: WaveRow[]): Date | null {
  let best: Date | null = null;
  for (const row of wave) {
    const d = parsePlanned(row.planned_at);
    if (!d) continue;
    if (!best || d.getTime() < best.getTime()) best = d;
  }
  return best;
}

function waveLatest(wave: WaveRow[]): Date | null {
  let best: Date | null = null;
  for (const row of wave) {
    const d = parsePlanned(row.planned_at);
    if (!d) continue;
    if (!best || d.getTime() > best.getTime()) best = d;
  }
  return best;
}

function waveIsEnded(wave: WaveRow[], legKey: string, now: Date): boolean {
  if (waveIsInProgress(wave)) return false;

  let hasActionable = false;
  for (const row of wave) {
    if (row.status_key === 'expired') continue;
    if (row.picked_up) continue;
    if (SKIP.has(row.status_key)) continue;
    hasActionable = true;
  }
  if (!hasActionable) return true;

  const latest = waveLatest(wave);
  if (latest) {
    return latest.getTime() + WAVE_GRACE_MS < now.getTime();
  }

  if (legKey === 'heen' || legKey === '') {
    return now.getHours() >= HEEN_FALLBACK_END_HOUR;
  }
  return false;
}

function waveIsInWindow(wave: WaveRow[], now: Date): boolean {
  const earliest = waveEarliest(wave);
  const latest = waveLatest(wave);
  if (!earliest || !latest) return false;
  const start = earliest.getTime() - 30 * 60 * 1000;
  const end = latest.getTime() + WAVE_GRACE_MS;
  const t = now.getTime();
  return t >= start && t <= end;
}

function selectActiveWave(rows: WaveRow[], now: Date): WaveRow[] {
  const byKey = new Map<string, WaveRow[]>();
  for (const row of rows) {
    const list = byKey.get(row.leg_key) || [];
    list.push(row);
    byKey.set(row.leg_key, list);
  }

  const order: string[] = [];
  for (const preferred of ['heen', 'retour']) {
    if (byKey.has(preferred)) order.push(preferred);
  }
  for (const key of byKey.keys()) {
    if (!order.includes(key)) order.push(key);
  }

  for (const key of order) {
    const wave = byKey.get(key)!;
    if (waveIsInProgress(wave)) return wave;
  }

  const alive = new Map<string, WaveRow[]>();
  for (const key of order) {
    const wave = byKey.get(key)!;
    if (waveIsEnded(wave, key, now)) continue;
    if (!waveHasOpenStops(wave)) continue;
    alive.set(key, wave);
  }

  if (alive.size === 0) return [];

  for (const key of order) {
    const wave = alive.get(key);
    if (wave && waveIsInWindow(wave, now)) return wave;
  }

  let nextKey: string | null = null;
  let nextAt: number | null = null;
  for (const [key, wave] of alive) {
    const start = waveEarliest(wave);
    if (!start) {
      if (nextKey === null) nextKey = key;
      continue;
    }
    if (nextAt === null || start.getTime() < nextAt) {
      nextAt = start.getTime();
      nextKey = key;
    }
  }

  return nextKey ? alive.get(nextKey) || [] : [];
}

function normalizeAddress(address: string): string {
  return address.trim().replace(/\s+/g, ' ').toLowerCase();
}

function pushStop(stops: ContractNavStop[], stop: ContractNavStop): void {
  const addr = String(stop.address || '').trim();
  if (!addr) return;
  const last = stops[stops.length - 1];
  if (last && normalizeAddress(String(last.address || '')) === normalizeAddress(addr)) return;
  stops.push(stop);
}

function withHub(route: ActiveNavigation, wave: WaveRow[]): ActiveNavigation {
  const key = String(route.leg_key || '');
  let hubLabel: string | null = null;
  let hubAddress: string | null = null;

  if (key === 'retour') {
    hubLabel = 'Ophalen vanaf';
    for (const row of wave) {
      if (row.pickup_address) {
        hubAddress = row.pickup_address;
        break;
      }
    }
  } else {
    hubLabel = 'Doel';
    for (const row of wave) {
      if (row.destination_address) {
        hubAddress = row.destination_address;
        break;
      }
    }
  }

  return {
    ...route,
    hub_label: hubAddress ? hubLabel : null,
    hub_address: hubAddress,
  };
}

/**
 * Bouwt de actieve navigatieroute lokaal uit dagitems.
 * Voorbije heenweg (na grace) verdwijnt; terugweg neemt over — ook als de API nog oud is.
 */
export function buildActiveNavigation(
  items: ContractDayItem[],
  now: Date = new Date()
): ActiveNavigation {
  const rows = collectRows(items);
  if (rows.length === 0) {
    return { leg_key: null, leg_label: null, hub_label: null, hub_address: null, stops: [] };
  }

  const wave = selectActiveWave(rows, now);
  if (wave.length === 0) {
    return { leg_key: null, leg_label: null, hub_label: null, hub_address: null, stops: [] };
  }

  const stops: ContractNavStop[] = [];
  for (const row of wave) {
    if (row.picked_up || row.pickup_address === '') continue;
    pushStop(stops, {
      kind: 'pickup',
      label: 'Ophalen',
      name: row.name || null,
      address: row.pickup_address,
      lat: row.pickup_lat,
      lng: row.pickup_lng,
      planned_at: row.planned_at || null,
      leg_label: row.leg_label,
    });
  }
  for (const row of wave) {
    if (row.destination_address === '') continue;
    pushStop(stops, {
      kind: 'dropoff',
      label: 'Afzetten',
      name: null,
      address: row.destination_address,
      lat: row.destination_lat,
      lng: row.destination_lng,
      planned_at: null,
      leg_label: row.leg_label,
    });
  }

  const waveKey = wave[0]?.leg_key || '';
  return withHub(
    {
      leg_key: waveKey || null,
      leg_label: displayLabel(waveKey),
      stops,
    },
    wave
  );
}
