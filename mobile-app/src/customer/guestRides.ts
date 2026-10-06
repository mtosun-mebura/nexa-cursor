import AsyncStorage from '@react-native-async-storage/async-storage';

export const GUEST_RIDES_KEY = 'nexa_taxi_guest_rides';
export const ARCHIVED_RIDES_KEY = 'nexa_taxi_customer_archived_rides';

export type GuestRide = {
  id: number;
  token: string;
  at: number;
  from: string;
  to: string;
  phase: string;
  status_label: string;
  quoted_price?: number | null;
};

export function isActiveRidePhase(phase?: string | null): boolean {
  return (
    phase === 'searching' ||
    phase === 'accepted' ||
    phase === 'awaiting_payment' ||
    phase === 'other'
  );
}

export function isRidesTabBadgePhase(phase?: string | null): boolean {
  return phase === 'searching' || phase === 'accepted';
}

export function rideArchiveKey(ride: { id?: number | null; token?: string | null }): string {
  const id = Number(ride.id) || 0;
  if (id > 0) return `id:${id}`;
  const token = String(ride.token || '').trim();
  return token ? `token:${token}` : '';
}

export function shortAddress(value?: string | null): string {
  const raw = String(value || '').trim();
  if (!raw) return 'Onbekend';
  return raw.split(',')[0].trim() || raw;
}

export async function loadGuestRides(): Promise<GuestRide[]> {
  try {
    const raw = await AsyncStorage.getItem(GUEST_RIDES_KEY);
    const list = raw ? JSON.parse(raw) : [];
    return Array.isArray(list) ? list.filter((r) => r && r.token) : [];
  } catch {
    return [];
  }
}

export async function writeGuestRides(list: GuestRide[]): Promise<void> {
  await AsyncStorage.setItem(GUEST_RIDES_KEY, JSON.stringify(list.slice(0, 50)));
}

export async function saveGuestRide(
  rideId: number,
  trackToken: string,
  meta: Partial<GuestRide>
): Promise<GuestRide[]> {
  const id = Number(rideId) || 0;
  const token = String(trackToken || '').trim();
  if (!token) return loadGuestRides();

  const list = await loadGuestRides();
  const prev = list.find((r) => r.token === token || (id > 0 && Number(r.id) === id));
  const next: GuestRide = {
    id: id || prev?.id || 0,
    token,
    at: Date.now(),
    from: meta.from || prev?.from || '',
    to: meta.to || prev?.to || '',
    phase: meta.phase || prev?.phase || 'searching',
    status_label: meta.status_label || prev?.status_label || 'Zoeken…',
    quoted_price:
      meta.quoted_price !== undefined ? meta.quoted_price : prev?.quoted_price ?? null,
  };
  const filtered = list.filter((r) => r.token !== token && Number(r.id) !== id);
  const out = [next, ...filtered].slice(0, 50);
  await writeGuestRides(out);
  return out;
}

export async function updateGuestRide(
  token: string,
  patch: Partial<GuestRide>
): Promise<GuestRide[]> {
  const list = await loadGuestRides();
  const out = list.map((r) => (r.token === token ? { ...r, ...patch } : r));
  await writeGuestRides(out);
  return out;
}

export async function loadArchivedKeys(): Promise<string[]> {
  try {
    const raw = await AsyncStorage.getItem(ARCHIVED_RIDES_KEY);
    const list = raw ? JSON.parse(raw) : [];
    return Array.isArray(list) ? list.map(String).filter(Boolean) : [];
  } catch {
    return [];
  }
}

export async function setRideArchived(
  ride: { id?: number | null; token?: string | null },
  archived: boolean
): Promise<string[]> {
  const key = rideArchiveKey(ride);
  if (!key) return loadArchivedKeys();
  const keys = (await loadArchivedKeys()).filter((k) => k !== key);
  if (archived) keys.unshift(key);
  const unique = keys.slice(0, 200);
  await AsyncStorage.setItem(ARCHIVED_RIDES_KEY, JSON.stringify(unique));
  return unique;
}

export function formatRideWhen(at?: number | null): string {
  if (at == null || !Number.isFinite(at)) return '';
  try {
    return new Date(at).toLocaleString('nl-NL', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return '';
  }
}

export function phaseLabel(phase?: string | null, fallback?: string | null): string {
  if (phase === 'searching') return 'Zoeken';
  if (phase === 'accepted') return 'Geaccepteerd';
  if (phase === 'awaiting_payment') return 'Betaling';
  if (phase === 'cancelled') return 'Geannuleerd';
  if (phase === 'completed') return 'Afgerond';
  return fallback || phase || '…';
}
