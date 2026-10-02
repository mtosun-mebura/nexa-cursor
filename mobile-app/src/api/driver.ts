import { apiRequest } from './client';
import type { AppModes } from './auth';

export type DriverUser = {
  id: number;
  name: string;
  email: string;
  company_id: number;
  company_name?: string | null;
  is_online: boolean;
  is_account_active: boolean;
  vehicle_id?: number | null;
  app_modes?: AppModes;
};

export type DispatchOffer = {
  id: number;
  ride_request_id?: number;
  status?: string;
  ride?: {
    id: number;
    pickup_address?: string;
    dropoff_address?: string;
    pickup_at?: string;
    customer_name?: string;
    is_network_ride?: boolean;
    fee_breakdown?: { is_marketplace?: boolean; is_network?: boolean };
  };
};

export function fetchDriverMe(token: string) {
  return apiRequest<{ user: DriverUser }>('/api/taxi/v1/driver/me', { token });
}

export function setDriverOnline(token: string, isOnline: boolean, vehicleId?: number | null) {
  return apiRequest('/api/taxi/v1/driver/availability', {
    method: 'PUT',
    token,
    body: {
      is_online: isOnline,
      ...(vehicleId ? { vehicle_id: vehicleId } : {}),
    },
  });
}

export function sendDriverLocation(
  token: string,
  coords: { lat: number; lng: number; accuracy?: number; heading?: number; speed?: number },
  vehicleId?: number | null
) {
  return apiRequest('/api/taxi/v1/driver/availability/location', {
    method: 'PUT',
    token,
    body: {
      lat: coords.lat,
      lng: coords.lng,
      ...(coords.accuracy != null ? { accuracy: coords.accuracy } : {}),
      ...(coords.heading != null ? { heading: coords.heading } : {}),
      ...(coords.speed != null ? { speed: coords.speed } : {}),
      ...(vehicleId ? { vehicle_id: vehicleId } : {}),
    },
  });
}

export function fetchDriverInbox(token: string) {
  return apiRequest<{ offers?: DispatchOffer[]; data?: DispatchOffer[]; meta?: unknown }>(
    '/api/taxi/v1/driver/dispatch/inbox',
    { token }
  );
}

export function acceptOffer(token: string, offerId: number) {
  return apiRequest(`/api/taxi/v1/driver/dispatch/offers/${offerId}/accept`, {
    method: 'POST',
    token,
  });
}

export function declineOffer(token: string, offerId: number) {
  return apiRequest(`/api/taxi/v1/driver/dispatch/offers/${offerId}/decline`, {
    method: 'POST',
    token,
  });
}
