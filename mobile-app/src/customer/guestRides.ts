import AsyncStorage from '@react-native-async-storage/async-storage';

export const GUEST_RIDES_KEY = 'nexa_taxi_guest_rides';
export const ARCHIVED_RIDES_KEY = 'nexa_taxi_customer_archived_rides';

export type GuestRideRating = {
  rated?: boolean;
  stars?: number | null;
  comment?: string | null;
  can_rate?: boolean;
};

export type GuestRide = {
  id: number;
  token: string;
  at: number;
  from: string;
  to: string;
  phase: string;
  status_label: string;
  quoted_price?: number | null;
  passengers?: number | null;
  baggage_summary?: string | null;
  distance_km?: number | null;
  duration_seconds?: number | null;
  /** Gekozen ophaaltijd (label of ISO), niet het moment van inschieten. */
  pickup_at?: string | null;
  rating?: GuestRideRating | null;
  cancellation_message?: string | null;
  refund_message?: string | null;
  refund_business_days?: number | null;
  payment_status?: string | null;
  pickup_confirmation?: {
    method?: string | null;
    pin?: string | null;
    status?: string | null;
    prompt?: string | null;
    can_answer?: boolean;
  } | null;
};

export function rideNeedsLivePoll(ride: {
  phase?: string | null;
  pickup_confirmation?: { can_answer?: boolean; pin?: string | null } | null;
}): boolean {
  const hasPin = String(ride.pickup_confirmation?.pin || '').replace(/\D/g, '').length === 4;
  return isActiveRidePhase(ride.phase) || ride.pickup_confirmation?.can_answer === true || hasPin;
}

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

const ADDRESS_SKIP_RE =
  /^(nederland|netherlands|the netherlands|belgië|belgie|belgium|duitsland|germany|overijssel|gelderland|noord-holland|zuid-holland|utrecht|limburg|noord-brabant|friesland|fryslân|fryslan|groningen|drenthe|zeeland|flevoland)$/i;

function partHasHouseNumber(part: string): boolean {
  return (
    /\b\d{1,5}[a-zA-Z]?(?:[-/]\d{1,5}[a-zA-Z]?)?\b/.test(part) &&
    !/^\d{4}\s*[A-Za-z]{2}\b/.test(part)
  );
}

/** Straat + huisnummer, en de plaats. Zonder wijk, postcode, provincie of land. */
export function bookingAddressParts(value?: string | null): { street: string; city: string } {
  const text = String(value || '').trim();
  if (!text) return { street: '', city: '' };

  let parts = text
    .split(',')
    .map((part) => part.trim())
    .filter(Boolean);

  if (parts.length >= 2 && /^\d+[a-zA-Z-]*$/.test(parts[0]) && !/^\d/.test(parts[1])) {
    parts = [`${parts[1]} ${parts[0]}`, ...parts.slice(2)];
  } else if (parts.length >= 2 && /^\d+[a-zA-Z-]*$/.test(parts[1]) && !/\d/.test(parts[0])) {
    parts = [`${parts[0]} ${parts[1]}`, ...parts.slice(2)];
  }

  let cityFromPostcode = '';
  const kept: string[] = [];
  parts.forEach((part) => {
    if (ADDRESS_SKIP_RE.test(part)) return;
    if (/^\d{4}\s*[A-Za-z]{2}$/.test(part)) return;
    const withCity = part.match(/^(\d{4}\s*[A-Za-z]{2})\s+(.+)$/);
    if (withCity) {
      cityFromPostcode = withCity[2].trim();
      return;
    }
    kept.push(part);
  });

  const street = kept.find(partHasHouseNumber) || '';
  let city = cityFromPostcode;
  if (!city) {
    const last = kept[kept.length - 1] || '';
    if (last && last !== street) city = last;
  }
  const streetLine =
    street ||
    kept.find((part) => part.toLowerCase() !== city.toLowerCase()) ||
    '';
  const cityLine =
    streetLine && city && streetLine.toLowerCase() === city.toLowerCase() ? '' : city;

  return { street: streetLine, city: cityLine };
}

export function bookingAddressLine(value?: string | null): string {
  const parts = bookingAddressParts(value);
  if (parts.street && parts.city) return `${parts.street}, ${parts.city}`;
  return parts.street || parts.city || '';
}

export function shortAddress(value?: string | null): string {
  return bookingAddressLine(value) || 'Onbekend';
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
    passengers: meta.passengers !== undefined ? meta.passengers : prev?.passengers ?? null,
    baggage_summary:
      meta.baggage_summary !== undefined ? meta.baggage_summary : prev?.baggage_summary ?? null,
    distance_km: meta.distance_km !== undefined ? meta.distance_km : prev?.distance_km ?? null,
    duration_seconds:
      meta.duration_seconds !== undefined ? meta.duration_seconds : prev?.duration_seconds ?? null,
    pickup_at: pickupAtOrPrevious(meta.pickup_at, prev?.pickup_at),
    rating: meta.rating !== undefined ? meta.rating : prev?.rating ?? null,
    cancellation_message:
      meta.cancellation_message !== undefined
        ? meta.cancellation_message
        : prev?.cancellation_message ?? null,
    refund_message:
      meta.refund_message !== undefined ? meta.refund_message : prev?.refund_message ?? null,
    refund_business_days:
      meta.refund_business_days !== undefined
        ? meta.refund_business_days
        : prev?.refund_business_days ?? null,
    payment_status:
      meta.payment_status !== undefined ? meta.payment_status : prev?.payment_status ?? null,
    pickup_confirmation:
      meta.pickup_confirmation !== undefined
        ? meta.pickup_confirmation
        : prev?.pickup_confirmation ?? null,
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
  const clean = Object.fromEntries(
    Object.entries(patch).filter(([, value]) => value !== undefined)
  ) as Partial<GuestRide>;
  const out = list.map((r) => (r.token === token ? { ...r, ...clean } : r));
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

export function livePickupAt(
  ride?: { pickup_at_label?: string | null; pickup_at?: string | null } | null
): string | null {
  const label = String(ride?.pickup_at_label || '').trim();
  if (label) return label;
  const iso = String(ride?.pickup_at || '').trim();
  return iso || null;
}

export function guestPatchFromLive(ride: {
  id?: number;
  pickup_address?: string | null;
  dropoff_address?: string | null;
  phase?: string | null;
  status_label?: string | null;
  quoted_price?: number | null;
  passengers?: number | null;
  baggage?: { summary?: string | null } | null;
  pickup_at?: string | null;
  pickup_at_label?: string | null;
  rating?: GuestRideRating | null;
  distance_km?: number | null;
  duration_seconds?: number | null;
  cancellation_message?: string | null;
  refund_message?: string | null;
  refund_business_days?: number | null;
  payment_status?: string | null;
  pickup_confirmation?: GuestRide['pickup_confirmation'];
}): Partial<GuestRide> {
  return {
    id: ride.id,
    from: ride.pickup_address || undefined,
    to: ride.dropoff_address || undefined,
    phase: ride.phase || undefined,
    status_label: ride.status_label || undefined,
    quoted_price: ride.quoted_price,
    passengers: ride.passengers ?? undefined,
    baggage_summary: ride.baggage?.summary ?? undefined,
    pickup_at: livePickupAt(ride) || undefined,
    rating: ride.rating ?? undefined,
    distance_km: ride.distance_km ?? undefined,
    duration_seconds: ride.duration_seconds ?? undefined,
    cancellation_message: ride.cancellation_message ?? undefined,
    refund_message: ride.refund_message ?? undefined,
    refund_business_days: ride.refund_business_days ?? undefined,
    payment_status: ride.payment_status ?? undefined,
    pickup_confirmation: ride.pickup_confirmation ?? undefined,
  };
}

/** Fallback-tekst als de API geen refund_message stuurt maar er wel betaald is. */
export function customerRefundNoticeText(input?: {
  refund_message?: string | null;
  payment_status?: string | null;
  payment_paid?: boolean | null;
  /** Klant-app boekt vooruit online; bij geannuleerde rit met prijs toon terugbetaling. */
  assume_prepaid?: boolean | null;
  refund_business_days?: number | null;
} | null): string | null {
  const fromApi = String(input?.refund_message || '').trim();
  if (fromApi) return fromApi;
  const status = String(input?.payment_status || '').trim();
  const wasPaid =
    !!input?.payment_paid ||
    !!input?.assume_prepaid ||
    ['paid', 'refunded', 'refund_pending', 'refund_failed'].includes(status);
  if (!wasPaid) return null;
  if (status === 'refund_failed') {
    return 'De automatische terugbetaling is nog niet afgerond. Neem contact op met de taxi zodat we dit voor u kunnen afronden.';
  }
  const days = Math.max(1, Number(input?.refund_business_days) || 10);
  return (
    'Het betaalde bedrag wordt teruggestort op de rekening/methode waarmee u heeft betaald. '
    + `Reken op zichtbaarheid binnen circa ${days} werkdagen (afhankelijk van uw bank).`
  );
}

function pickupAtOrPrevious(next?: string | null, prev?: string | null): string | null {
  const value = String(next || '').trim();
  if (value) return value;
  const previous = String(prev || '').trim();
  return previous || null;
}

const WEEKDAYS = ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'] as const;
const MONTHS = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'] as const;

/** Straat en huisnummer op de eerste regel, de plaats eronder. */
export function splitRideAddress(address?: string | null): { main: string; sub: string } {
  const parts = bookingAddressParts(address);
  if (!parts.street && !parts.city) return { main: '—', sub: '' };
  if (!parts.street) return { main: parts.city, sub: '' };
  return { main: parts.street, sub: parts.city };
}

function formatDutchPickup(day: number, month: number, year: number, hh: string, mm: string): string {
  const date = new Date(year, month - 1, day);
  const weekday = WEEKDAYS[date.getDay()] || '';
  const monthName = MONTHS[month - 1] || '';
  return `${weekday} ${day} ${monthName} ${year}, ${hh}:${mm}`;
}

function formatInstantPickup(at: number): string {
  try {
    const parts = new Intl.DateTimeFormat('nl-NL', {
      timeZone: 'Europe/Amsterdam',
      weekday: 'short',
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hourCycle: 'h23',
    }).formatToParts(new Date(at));
    const get = (type: Intl.DateTimeFormatPartTypes) =>
      parts.find((part) => part.type === type)?.value || '';
    const weekday = get('weekday').replace(/\./g, '').slice(0, 2).toLowerCase();
    const day = Number(get('day'));
    const month = get('month').replace(/\./g, '').toLowerCase();
    const year = get('year');
    const hour = get('hour').padStart(2, '0');
    const minute = get('minute').padStart(2, '0');
    if (!day || !year) return '';
    return `${weekday} ${day} ${month} ${year}, ${hour}:${minute}`;
  } catch {
    return '';
  }
}

/** Ophaaltijd als “ma 12 okt 2026, 16:35”. */
export function formatPickupWhen(value?: string | number | null): string {
  if (value == null || value === '') return '';
  if (typeof value === 'number') return formatRideWhen(value);
  const raw = String(value).trim();
  const labeled = raw.match(/^(\d{2})-(\d{2})-(\d{4})[,\s]+(\d{2}):(\d{2})/);
  if (labeled) {
    return formatDutchPickup(
      Number(labeled[1]),
      Number(labeled[2]),
      Number(labeled[3]),
      labeled[4],
      labeled[5]
    );
  }
  const parsed = Date.parse(raw);
  if (Number.isFinite(parsed)) return formatRideWhen(parsed);
  return raw;
}

export function formatRideWhen(at?: number | null): string {
  if (at == null || !Number.isFinite(at)) return '';
  return formatInstantPickup(at);
}

export function formatTripDistance(km?: number | null): string | null {
  const n = Number(km);
  if (!Number.isFinite(n) || n <= 0) return null;
  return `${n.toFixed(1).replace('.', ',')} km`;
}

export function formatTripDuration(seconds?: number | null): string | null {
  const total = Number(seconds);
  if (!Number.isFinite(total) || total < 0) return null;
  const mins = Math.max(0, Math.round(total / 60));
  const hours = Math.floor(mins / 60);
  const rest = mins % 60;
  if (hours <= 0) return `${rest} min`;
  if (rest <= 0) return `${hours} u`;
  return `${hours} u ${rest} min`;
}

export function baggageChipLines(input?: {
  items?: Array<{ label?: string | null; qty?: number | null }> | null;
  summary?: string | null;
} | null): string[] {
  const items = input?.items || [];
  if (items.length) {
    return items
      .map((item) => {
        const label = String(item.label || '').trim();
        const qty = Number(item.qty || 0);
        if (!label || qty <= 0) return '';
        return `${label} × ${qty}`;
      })
      .filter(Boolean);
  }
  const summary = String(input?.summary || '').trim();
  if (!summary || /^geen$/i.test(summary)) return [];
  return summary
    .split(',')
    .map((part) => part.trim())
    .filter(Boolean);
}

export function phaseLabel(phase?: string | null, fallback?: string | null): string {
  if (phase === 'searching') return 'Zoeken';
  if (phase === 'accepted') return 'Geaccepteerd';
  if (phase === 'awaiting_payment') return 'Betaling';
  if (phase === 'cancelled') return 'Geannuleerd';
  if (phase === 'completed') return 'Afgerond';
  return fallback || phase || '…';
}
