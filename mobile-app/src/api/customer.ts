import { apiRequest } from './client';

export type LatLng = { lat: number; lng: number; address: string };

export type QuoteOffer = {
  id: string;
  price?: number;
  price_label?: string;
  label?: string;
};

export type QuoteResponse = {
  success?: boolean;
  offers: QuoteOffer[];
  marketplace?: {
    radius_km?: number;
    candidate_count?: number;
  };
  payment?: {
    booking?: boolean;
    mollie_configured?: boolean;
  };
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
};

export function fetchQuote(body: {
  distance_meters: number;
  duration_seconds: number;
  passengers: number;
  pickup_lat: number;
  pickup_lng: number;
  pickup_at?: string | null;
}) {
  return apiRequest<QuoteResponse>('/api/taxi/v1/customer/quote', {
    method: 'POST',
    body,
  });
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
