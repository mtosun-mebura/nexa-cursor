import * as Location from 'expo-location';
import { API_BASE_URL } from '../config';

export type GeoPoint = {
  lat: number;
  lng: number;
  address: string;
};

export type LatLng = { latitude: number; longitude: number };

export type RouteResult = {
  coordinates: LatLng[];
  distance_meters: number;
  duration_seconds: number;
};

export const DEFAULT_MAP_CENTER = { lat: 52.2215, lng: 6.8936 }; // Enschede (dev/simulator fallback)

/** Dark Google Maps style — past bij Nexa Taxi UI. */
export const DARK_MAP_STYLE = [
  { elementType: 'geometry', stylers: [{ color: '#1d2c4d' }] },
  { elementType: 'labels.text.fill', stylers: [{ color: '#8ec3b9' }] },
  { elementType: 'labels.text.stroke', stylers: [{ color: '#1a3646' }] },
  { featureType: 'administrative.country', elementType: 'geometry.stroke', stylers: [{ color: '#4b6878' }] },
  { featureType: 'administrative.land_parcel', elementType: 'labels.text.fill', stylers: [{ color: '#64779e' }] },
  { featureType: 'administrative.province', elementType: 'geometry.stroke', stylers: [{ color: '#4b6878' }] },
  { featureType: 'landscape.man_made', elementType: 'geometry.stroke', stylers: [{ color: '#334e87' }] },
  { featureType: 'landscape.natural', elementType: 'geometry', stylers: [{ color: '#023e58' }] },
  { featureType: 'poi', elementType: 'geometry', stylers: [{ color: '#283d6a' }] },
  { featureType: 'poi', elementType: 'labels.text.fill', stylers: [{ color: '#6f9ba5' }] },
  { featureType: 'poi', elementType: 'labels.text.stroke', stylers: [{ color: '#1d2c4d' }] },
  { featureType: 'poi.park', elementType: 'geometry.fill', stylers: [{ color: '#023e58' }] },
  { featureType: 'poi.park', elementType: 'labels.text.fill', stylers: [{ color: '#3C7680' }] },
  { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#304a7d' }] },
  { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#98a5be' }] },
  { featureType: 'road', elementType: 'labels.text.stroke', stylers: [{ color: '#1d2c4d' }] },
  { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#2c6675' }] },
  { featureType: 'road.highway', elementType: 'geometry.stroke', stylers: [{ color: '#255763' }] },
  { featureType: 'road.highway', elementType: 'labels.text.fill', stylers: [{ color: '#b0d5ce' }] },
  { featureType: 'road.highway', elementType: 'labels.text.stroke', stylers: [{ color: '#023e58' }] },
  { featureType: 'transit', elementType: 'labels.text.fill', stylers: [{ color: '#98a5be' }] },
  { featureType: 'transit', elementType: 'labels.text.stroke', stylers: [{ color: '#1d2c4d' }] },
  { featureType: 'transit.line', elementType: 'geometry.fill', stylers: [{ color: '#283d6a' }] },
  { featureType: 'transit.station', elementType: 'geometry', stylers: [{ color: '#3a4762' }] },
  { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0e1626' }] },
  { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#4e6d70' }] },
];

type NominatimAddress = {
  road?: string;
  pedestrian?: string;
  house_number?: string;
  city?: string;
  town?: string;
  village?: string;
  municipality?: string;
  suburb?: string;
  neighbourhood?: string;
  postcode?: string;
  hamlet?: string;
};

/** Alleen straat (+ huisnr) en stad — geen provincie/land/postcode. */
export function compactAddressLabel(
  displayName: string | null | undefined,
  address?: NominatimAddress | null,
  poiName?: string | null
): string {
  if (address && typeof address === 'object') {
    const street = String(address.road || address.pedestrian || '').trim();
    const number = String(address.house_number || '').trim();
    const city = String(
      address.city || address.town || address.village || address.municipality || address.suburb || ''
    ).trim();
    const streetPart = [street, number].filter(Boolean).join(' ').trim();
    const lead = streetPart || String(poiName || '').trim();
    if (lead && city) return `${lead}, ${city}`;
    if (lead) return lead;
    if (city) return city;
  }

  const parts = String(displayName || '')
    .split(',')
    .map((p) => p.trim())
    .filter(Boolean);
  if (!parts.length) return '';

  // "155-20, De Posten, …" → straat + huisnummer
  if (/^\d+[a-zA-Z\-]*$/.test(parts[0]) && parts[1] && !/^\d/.test(parts[1])) {
    const streetWithNr = `${parts[1]} ${parts[0]}`;
    const city =
      parts.find((p, i) => i > 1 && !/^\d{4}\s*[A-Z]{2}$/i.test(p) && !/nederland|overijssel|gelderland|noord|zuid|utrecht|limburg|brabant|friesland|groningen|drenthe|zeeland|flevoland/i.test(p)) ||
      parts[2] ||
      '';
    return city ? `${streetWithNr}, ${city}` : streetWithNr;
  }

  const streetPart = parts[0];
  const city =
    parts.find((p, i) => i > 0 && !/^\d{4}\s*[A-Z]{2}$/i.test(p) && !/nederland|overijssel|gelderland|noord-holland|zuid-holland|utrecht|limburg|noord-brabant|friesland|groningen|drenthe|zeeland|flevoland/i.test(p)) ||
    parts[1] ||
    '';
  return city && city !== streetPart ? `${streetPart}, ${city}` : streetPart;
}

export function formatAddress(parts: Location.LocationGeocodedAddress): string {
  const street = [parts.street, parts.streetNumber].filter(Boolean).join(' ').trim();
  const city = String(parts.city || parts.subregion || '').trim();
  if (street && city) return `${street}, ${city}`;
  return street || city || 'Onbekend adres';
}

async function reverseViaBackend(lat: number, lng: number): Promise<string | null> {
  try {
    const url =
      `${API_BASE_URL}/nexa-taxi/booking/address-search?` +
      `lat=${encodeURIComponent(String(lat))}&lon=${encodeURIComponent(String(lng))}`;
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!res.ok) return null;
    const data = await res.json();
    const label = compactAddressLabel(data?.display_name, data?.address, data?.name);
    return label || null;
  } catch {
    return null;
  }
}

export type AddressSuggestion = {
  label: string;
  lat: number;
  lng: number;
};

/** Uniekere labels voor zoeksuggesties (huisnr / postcode / buurt / POI). */
function suggestionLabel(
  displayName: string | null | undefined,
  address?: NominatimAddress | null,
  poiName?: string | null
): string {
  const street = String(address?.road || address?.pedestrian || '').trim();
  const number = String(address?.house_number || '').trim();
  const city = String(
    address?.city || address?.town || address?.village || address?.municipality || ''
  ).trim();
  const area = String(address?.suburb || address?.neighbourhood || address?.hamlet || '').trim();
  const postcode = String(address?.postcode || '').trim();
  const poi = String(poiName || '').trim();
  const streetPart = [street, number].filter(Boolean).join(' ').trim();

  // Postcode uit display_name als address die mist (onderscheidt segmenten van dezelfde straat)
  const postcodeFromDisplay =
    postcode ||
    (String(displayName || '').match(/\b(\d{4}\s*[A-Z]{2})\b/i)?.[1] || '').replace(/\s+/, ' ').trim();

  if (streetPart && city) {
    if (
      poi &&
      poi.toLowerCase() !== street.toLowerCase() &&
      !streetPart.toLowerCase().includes(poi.toLowerCase())
    ) {
      return number
        ? `${poi}, ${streetPart}, ${city}`
        : `${poi}, ${streetPart}${postcodeFromDisplay ? `, ${postcodeFromDisplay}` : ''}, ${city}`;
    }
    if (number) return `${streetPart}, ${city}`;
    // Zonder huisnummer: postcode of buurt zodat Nominatim-segmenten niet identiek lijken
    if (postcodeFromDisplay) return `${streetPart}, ${postcodeFromDisplay}, ${city}`;
    if (area && area.toLowerCase() !== city.toLowerCase()) {
      return `${streetPart}, ${area}, ${city}`;
    }
    return `${streetPart}, ${city}`;
  }

  if (poi && city) {
    return postcodeFromDisplay ? `${poi}, ${postcodeFromDisplay}, ${city}` : `${poi}, ${city}`;
  }

  const compact = compactAddressLabel(displayName, address, poiName);
  if (compact && postcodeFromDisplay && !compact.includes(postcodeFromDisplay)) {
    // compact mist postcode → alsnog onderscheid
    return compact.replace(/,([^,]+)$/, `, ${postcodeFromDisplay},$1`);
  }
  if (compact) return compact;

  const parts = String(displayName || '')
    .split(',')
    .map((p) => p.trim())
    .filter((p) => p && !/nederland|netherlands|overijssel|gelderland/i.test(p));
  return parts.slice(0, 3).join(', ');
}

export async function searchAddresses(query: string): Promise<AddressSuggestion[]> {
  const q = query.trim();
  if (q.length < 3) return [];
  const url =
    `${API_BASE_URL}/nexa-taxi/booking/address-search?` +
    `q=${encodeURIComponent(q)}&countrycodes=nl&limit=8&accept-language=nl&addressdetails=1`;
  const res = await fetch(url, { headers: { Accept: 'application/json' } });
  if (!res.ok) return [];
  const data = await res.json();
  if (!Array.isArray(data)) return [];

  const seenCoords = new Set<string>();
  const seenLabels = new Set<string>();
  const out: AddressSuggestion[] = [];

  for (const item of data as {
    display_name?: string;
    name?: string;
    lat?: string | number;
    lon?: string | number;
    address?: NominatimAddress;
  }[]) {
    const lat = Number(item.lat);
    const lng = Number(item.lon);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) continue;

    const coordKey = `${lat.toFixed(5)},${lng.toFixed(5)}`;
    if (seenCoords.has(coordKey)) continue;

    let label = suggestionLabel(item.display_name, item.address, item.name);
    if (!label) continue;

    const labelKey = label.toLowerCase();
    if (seenLabels.has(labelKey)) {
      // Zelfde tekst, ander punt: voeg huisnr of korte coördinaat-hint toe
      const nr = String(item.address?.house_number || '').trim();
      if (nr && !label.includes(nr)) {
        label = label.replace(/,/, ` ${nr},`);
      } else {
        continue;
      }
      if (seenLabels.has(label.toLowerCase())) continue;
    }

    seenCoords.add(coordKey);
    seenLabels.add(label.toLowerCase());
    out.push({ label, lat, lng });
    if (out.length >= 6) break;
  }

  return out;
}

function waitForPosition(timeoutMs: number): Promise<Location.LocationObject | null> {
  return new Promise((resolve) => {
    let settled = false;
    let sub: Location.LocationSubscription | null = null;
    const timer = setTimeout(() => {
      if (settled) return;
      settled = true;
      sub?.remove();
      resolve(null);
    }, timeoutMs);

    Location.watchPositionAsync(
      {
        accuracy: Location.Accuracy.Balanced,
        distanceInterval: 0,
        timeInterval: 500,
      },
      (pos) => {
        if (settled) return;
        settled = true;
        clearTimeout(timer);
        sub?.remove();
        resolve(pos);
      }
    )
      .then((s) => {
        sub = s;
      })
      .catch(() => {
        if (settled) return;
        settled = true;
        clearTimeout(timer);
        resolve(null);
      });
  });
}

export async function resolveCurrentPickup(): Promise<GeoPoint> {
  const { status } = await Location.requestForegroundPermissionsAsync();
  if (status !== 'granted') {
    throw new Error('Geef locatietoegang of vul handmatig een ophaaladres in.');
  }

  let lat: number | null = null;
  let lng: number | null = null;

  // Eerst echte GPS — geen IP-schatting (die wijst vaak naar de ISP-stad, bv. Eindhoven).
  try {
    const pos = await Location.getCurrentPositionAsync({
      accuracy: Location.Accuracy.Balanced,
    });
    lat = pos.coords.latitude;
    lng = pos.coords.longitude;
  } catch {
    const watched = await waitForPosition(8000);
    if (watched?.coords) {
      lat = watched.coords.latitude;
      lng = watched.coords.longitude;
    }
  }

  // Alleen recente last-known (max 2 min), anders riskeren we een oude simulator-locatie.
  if (lat == null || lng == null) {
    try {
      const last = await Location.getLastKnownPositionAsync({
        maxAge: 1000 * 60 * 2,
        requiredAccuracy: 500,
      });
      if (last?.coords) {
        lat = last.coords.latitude;
        lng = last.coords.longitude;
      }
    } catch {
      /* ignore */
    }
  }

  if (lat == null || lng == null) {
    throw new Error(
      'Geen GPS-fixatie. Op de simulator: Features → Location → Custom Location (Enschede). Of typ je ophaaladres.'
    );
  }

  const backendName = await reverseViaBackend(lat, lng);
  if (backendName) {
    return { lat, lng, address: backendName };
  }

  try {
    const results = await Location.reverseGeocodeAsync({ latitude: lat, longitude: lng });
    const address = results[0] ? formatAddress(results[0]) : `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    return { lat, lng, address };
  } catch {
    return { lat, lng, address: `${lat.toFixed(5)}, ${lng.toFixed(5)}` };
  }
}

export async function geocodeAddress(query: string): Promise<GeoPoint> {
  const suggestions = await searchAddresses(query);
  if (suggestions.length) {
    const first = suggestions[0];
    return { lat: first.lat, lng: first.lng, address: first.label };
  }
  throw new Error('Adres niet gevonden. Kies een suggestie uit de lijst.');
}

/** Decode Google/OSRM encoded polyline → coordinates. */
export function decodePolyline(encoded: string): LatLng[] {
  const coordinates: LatLng[] = [];
  let index = 0;
  let lat = 0;
  let lng = 0;

  while (index < encoded.length) {
    let result = 0;
    let shift = 0;
    let b: number;
    do {
      b = encoded.charCodeAt(index++) - 63;
      result |= (b & 0x1f) << shift;
      shift += 5;
    } while (b >= 0x20);
    const dlat = result & 1 ? ~(result >> 1) : result >> 1;
    lat += dlat;

    result = 0;
    shift = 0;
    do {
      b = encoded.charCodeAt(index++) - 63;
      result |= (b & 0x1f) << shift;
      shift += 5;
    } while (b >= 0x20);
    const dlng = result & 1 ? ~(result >> 1) : result >> 1;
    lng += dlng;

    coordinates.push({ latitude: lat / 1e5, longitude: lng / 1e5 });
  }
  return coordinates;
}

/** Route over wegen (OSRM). Fallback: rechte lijn-schatting. */
export async function fetchDrivingRoute(
  from: { lat: number; lng: number },
  to: { lat: number; lng: number }
): Promise<RouteResult> {
  try {
    const url =
      `https://router.project-osrm.org/route/v1/driving/` +
      `${from.lng},${from.lat};${to.lng},${to.lat}` +
      `?overview=full&geometries=polyline&steps=false`;
    const res = await fetch(url);
    if (res.ok) {
      const data = await res.json();
      const route = data?.routes?.[0];
      if (route?.geometry) {
        return {
          coordinates: decodePolyline(String(route.geometry)),
          distance_meters: Math.max(50, Math.round(Number(route.distance) || 0)),
          duration_seconds: Math.max(60, Math.round(Number(route.duration) || 0)),
        };
      }
    }
  } catch {
    /* fall through */
  }

  const metrics = estimateRouteMetrics(from, to);
  return {
    coordinates: [
      { latitude: from.lat, longitude: from.lng },
      { latitude: to.lat, longitude: to.lng },
    ],
    ...metrics,
  };
}

/** Zelfde schatting als web-fallback: haversine × 1.25 @ ~30 km/u. */
export function estimateRouteMetrics(from: { lat: number; lng: number }, to: { lat: number; lng: number }) {
  const R = 6371000;
  const toRad = (d: number) => (d * Math.PI) / 180;
  const dLat = toRad(to.lat - from.lat);
  const dLng = toRad(to.lng - from.lng);
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(toRad(from.lat)) * Math.cos(toRad(to.lat)) * Math.sin(dLng / 2) ** 2;
  const meters = 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  const distance_meters = Math.max(50, Math.round(meters * 1.25));
  const duration_seconds = Math.max(60, Math.round((distance_meters / 1000 / 30) * 3600));
  return { distance_meters, duration_seconds };
}

/** Indicatieve prijs als de quote-API geen offer teruggeeft. */
export function estimateFareEuro(distanceMeters: number, durationSeconds: number): number {
  const km = Math.max(0, distanceMeters / 1000);
  const minutes = Math.max(1, durationSeconds / 60);
  const raw = 3.8 + km * 2.2 + minutes * 0.35;
  return Math.round(raw * 100) / 100;
}

export function formatEuroNl(amount: number): string {
  return `€ ${amount.toFixed(2).replace('.', ',')}`;
}

/** GPS zonder adres — voor vloot op de kaart voordat er een ophaaladres is. */
export async function peekCurrentCoords(): Promise<{ lat: number; lng: number } | null> {
  try {
    const { status } = await Location.getForegroundPermissionsAsync();
    if (status !== 'granted') {
      const asked = await Location.requestForegroundPermissionsAsync();
      if (asked.status !== 'granted') return null;
    }
  } catch {
    return null;
  }

  try {
    const pos = await Location.getCurrentPositionAsync({
      accuracy: Location.Accuracy.Balanced,
    });
    return { lat: pos.coords.latitude, lng: pos.coords.longitude };
  } catch {
    /* fall through */
  }

  try {
    const last = await Location.getLastKnownPositionAsync({
      maxAge: 1000 * 60 * 5,
      requiredAccuracy: 1500,
    });
    if (last?.coords) {
      return { lat: last.coords.latitude, lng: last.coords.longitude };
    }
  } catch {
    /* ignore */
  }
  return null;
}

const pad2 = (n: number) => String(n).padStart(2, '0');

/** Rond af naar volgende 5-minuten (minstens `minutesFromNow` vanaf nu). */
export function defaultPickupDate(minutesFromNow = 10): Date {
  const d = new Date(Date.now() + minutesFromNow * 60_000);
  d.setSeconds(0, 0);
  const rem = d.getMinutes() % 5;
  if (rem !== 0) d.setMinutes(d.getMinutes() + (5 - rem));
  return d;
}

/** Europe/Amsterdam wall-clock string zonder timezone (backend verwacht dit). */
export function formatPickupAtPayload(d: Date): string {
  return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}T${pad2(d.getHours())}:${pad2(d.getMinutes())}:00`;
}

export function defaultPickupAt(minutesFromNow = 10): string {
  return formatPickupAtPayload(defaultPickupDate(minutesFromNow));
}

export function formatPickupAtLabel(d: Date): string {
  const today = new Date();
  const start = (x: Date) => new Date(x.getFullYear(), x.getMonth(), x.getDate()).getTime();
  const diff = Math.round((start(d) - start(today)) / 86_400_000);
  const time = `${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
  if (diff === 0) return `Vandaag ${time}`;
  if (diff === 1) return `Morgen ${time}`;
  const day = d.toLocaleDateString('nl-NL', { weekday: 'short', day: 'numeric', month: 'short' });
  return `${day} ${time}`;
}
