import { apiRequest } from './client';
import { API_BASE_URL } from '../config';

export type QuoteOffer = {
  id: string;
  price?: number;
  price_label?: string;
  label?: string;
  title?: string;
};

export type QuoteResponse = {
  success?: boolean;
  offers: QuoteOffer[];
  marketplace?: {
    radius_km?: number;
    candidate_count?: number;
    taxi_available?: boolean;
    unavailable_message?: string | null;
    candidates?: Array<{ company_id: number; company_name: string; distance_km: number }>;
  };
  payment?: {
    booking?: boolean;
    mollie_configured?: boolean;
    deferred_until_taxi?: boolean;
  };
  route?: {
    distance_meters?: number;
    duration_seconds?: number;
  };
};

export type NearbyTaxi = {
  id: string;
  lat: number;
  lng: number;
  car_style?: string;
  distance_km?: number | null;
  busy?: boolean;
};

export type LiveRide = {
  id: number;
  status: string;
  status_label: string;
  phase: string;
  accepted?: boolean;
  can_cancel?: boolean;
  needs_unaccepted_decision?: boolean;
  waiting_for_taxi?: boolean;
  auto_cancel_label?: string | null;
  decision_deadline_label?: string | null;
  decision_minutes?: number | null;
  payment_error?: string | null;
  can_retry_payment?: boolean;
  checkout_url?: string | null;
  pickup_address?: string;
  dropoff_address?: string;
  pickup_at_label?: string | null;
  passengers?: number;
  quoted_price?: number | null;
  company?: { id: number; name: string; phone?: string } | null;
  vehicle?: { label?: string; license_plate?: string | null } | null;
  driver?: { id: number; name: string } | null;
  eta_minutes?: number | null;
  eta_label?: string | null;
  poll_interval_ms?: number;
};

export type BookResponse = {
  success: boolean;
  message?: string;
  ride_request_id: number;
  track_token: string;
  payment_required?: boolean;
  checkout_url?: string | null;
  live?: LiveRide;
  offer?: QuoteOffer;
};

export type BookPayload = {
  distance_meters: number;
  duration_seconds: number;
  passengers: number;
  pickup_address: string;
  dropoff_address: string;
  pickup_at: string;
  pickup_lat: number;
  pickup_lng: number;
  dropoff_lat: number;
  dropoff_lng: number;
  first_name: string;
  last_name: string;
  phone: string;
  email?: string | null;
  remarks?: string | null;
  selected_offer_id?: string | null;
  payment_method?: 'booking' | null;
  return_url?: string;
  marketplace_radius_km?: number;
  baggage?: Record<string, number>;
  special_baggage?: Record<string, number>;
};

const MARKETPLACE_SECTION = 'component:taxi.algemene_boekingsmodule';

export function fetchQuote(body: {
  distance_meters: number;
  duration_seconds: number;
  passengers: number;
  pickup_lat: number;
  pickup_lng: number;
  pickup_at?: string | null;
  marketplace_radius_km?: number;
  baggage?: Record<string, number>;
  special_baggage?: Record<string, number>;
}) {
  return apiRequest<QuoteResponse>('/api/taxi/v1/customer/quote', {
    method: 'POST',
    body,
  });
}

export async function fetchNearbyTaxis(input: {
  lat: number;
  lng: number;
  radiusKm?: number;
}): Promise<NearbyTaxi[]> {
  const params = new URLSearchParams({
    lat: String(input.lat),
    lng: String(input.lng),
    radius_km: String(input.radiusKm ?? 50),
    section_key: MARKETPLACE_SECTION,
  });
  const res = await fetch(`${API_BASE_URL}/nexa-taxi/booking/nearby-taxis?${params}`, {
    headers: { Accept: 'application/json' },
  });
  if (!res.ok) return [];
  const data = await res.json();
  const list = Array.isArray(data?.vehicles) ? data.vehicles : [];
  return list
    .map((v: NearbyTaxi) => ({
      id: String(v.id),
      lat: Number(v.lat),
      lng: Number(v.lng),
      car_style: v.car_style,
      distance_km: v.distance_km == null ? null : Number(v.distance_km),
      busy: !!v.busy,
    }))
    .filter((v: NearbyTaxi) => Number.isFinite(v.lat) && Number.isFinite(v.lng));
}

export function bookGuest(body: BookPayload) {
  return apiRequest<BookResponse>('/api/taxi/v1/customer/book/guest', {
    method: 'POST',
    body,
  });
}

export function fetchLive(token: string) {
  return apiRequest<{ success: boolean; ride: LiveRide }>(
    `/api/taxi/v1/customer/live?token=${encodeURIComponent(token)}`
  );
}

export function cancelLive(token: string) {
  return apiRequest<{ success?: boolean; message?: string; ride?: LiveRide }>(
    '/api/taxi/v1/customer/live/cancel',
    { method: 'POST', body: { token } }
  );
}

export function waitLive(token: string) {
  return apiRequest<{ success?: boolean; message?: string; ride?: LiveRide }>(
    '/api/taxi/v1/customer/live/wait',
    { method: 'POST', body: { token } }
  );
}

export function payLive(token: string, returnUrl?: string) {
  return apiRequest<{
    success?: boolean;
    checkout_url?: string | null;
    ride?: LiveRide;
    message?: string;
  }>('/api/taxi/v1/customer/live/pay', {
    method: 'POST',
    body: { token, return_url: returnUrl },
  });
}
