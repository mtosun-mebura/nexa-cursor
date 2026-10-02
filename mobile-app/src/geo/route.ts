import * as Location from 'expo-location';

export type GeoPoint = {
  lat: number;
  lng: number;
  address: string;
};

export function formatAddress(parts: Location.LocationGeocodedAddress): string {
  const street = [parts.street, parts.streetNumber].filter(Boolean).join(' ').trim();
  const cityLine = [parts.postalCode, parts.city].filter(Boolean).join(' ').trim();
  const bits = [street, cityLine, parts.region, parts.country].filter((v) => !!v && String(v).trim());
  return bits.join(', ') || 'Onbekend adres';
}

export async function resolveCurrentPickup(): Promise<GeoPoint> {
  const { status } = await Location.requestForegroundPermissionsAsync();
  if (status !== 'granted') {
    throw new Error('Locatietoegang is nodig om je ophaalpunt te bepalen.');
  }

  const pos = await Location.getCurrentPositionAsync({
    accuracy: Location.Accuracy.Balanced,
  });
  const lat = pos.coords.latitude;
  const lng = pos.coords.longitude;
  const results = await Location.reverseGeocodeAsync({ latitude: lat, longitude: lng });
  const address = results[0] ? formatAddress(results[0]) : `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
  return { lat, lng, address };
}

export async function geocodeAddress(query: string): Promise<GeoPoint> {
  const q = query.trim();
  if (q.length < 3) {
    throw new Error('Vul een duidelijker adres in.');
  }
  const results = await Location.geocodeAsync(q);
  if (!results.length) {
    throw new Error('Adres niet gevonden. Probeer straat + plaats.');
  }
  const first = results[0];
  const lat = first.latitude;
  const lng = first.longitude;
  const reverse = await Location.reverseGeocodeAsync({ latitude: lat, longitude: lng });
  const address = reverse[0] ? formatAddress(reverse[0]) : q;
  return { lat, lng, address };
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

/** Europe/Amsterdam wall-clock string zonder timezone (backend verwacht dit). */
export function defaultPickupAt(minutesFromNow = 10): string {
  const d = new Date(Date.now() + minutesFromNow * 60_000);
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
}
