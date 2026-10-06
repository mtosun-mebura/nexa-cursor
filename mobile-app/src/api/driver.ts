import { apiRequest } from './client';
import type { AppModes } from './auth';

export type DriverUser = {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
  company_id: number;
  company_name?: string | null;
  company_logo_url?: string | null;
  company_logo_dark_url?: string | null;
  is_online: boolean;
  is_account_active: boolean;
  vehicle_id?: number | null;
  vehicle_locked?: boolean;
  pwa_accent?: string | null;
  ride_alert_tone?: string | null;
  app_modes?: AppModes;
};

export type DriverPermissions = {
  earnings_view?: boolean;
  earnings_view_month?: boolean;
};

export type DriverVehicle = {
  id: number;
  name?: string;
  license_plate?: string | null;
  type?: string;
  label?: string;
};

export type DriverVehiclesResponse = {
  data?: DriverVehicle[];
  locked?: boolean;
  assigned_vehicle?: DriverVehicle | null;
  assigned_until?: string | null;
};

export type DispatchOfferRide = {
  id?: number;
  status?: string;
  pickup_address?: string;
  dropoff_address?: string;
  pickup_at?: string;
  customer_name?: string;
  customer_phone?: string | null;
  customer_note?: string | null;
  quoted_price?: number | null;
  passengers?: number | null;
  distance_km?: number | null;
  duration_seconds?: number | null;
  duration_minutes?: number | null;
  is_network_ride?: boolean;
  is_nexa_suite?: boolean;
  is_pickup_overdue?: boolean;
  is_scheduled_overdue?: boolean;
  can_cancel_with_reason?: boolean;
  vehicle_label?: string | null;
  vehicle_plate?: string | null;
  vehicle_name?: string | null;
  baggage?: {
    summary?: string | null;
    items?: Array<{ key: string; label: string; qty: number }>;
  };
  fee_breakdown?: {
    is_marketplace?: boolean;
    is_network?: boolean;
    owner_name?: string;
  } | null;
};

export type DispatchOffer = {
  id: number;
  ride_request_id?: number;
  status?: string;
  is_waiting?: boolean;
  is_pickup_overdue?: boolean;
  urgency?: string;
  offered_at?: string | null;
  expires_at?: string | null;
  seconds_remaining?: number;
  ride?: DispatchOfferRide;
};

export type DriverCancelReason = {
  code: string;
  label: string;
  message: string;
};

export type DriverActiveRide = DispatchOfferRide & {
  id: number;
};

export type DriverInboxData = {
  offers: DispatchOffer[];
  archivedOffers: DispatchOffer[];
  declinedOffers: DispatchOffer[];
  activeRide: DriverActiveRide | null;
  parkedAssignedRides: DriverActiveRide[];
  scheduledRides: DriverActiveRide[];
  overdueScheduledRides: DriverActiveRide[];
  cancelReasons: DriverCancelReason[];
};

function asRideList(raw: unknown): DriverActiveRide[] {
  if (!Array.isArray(raw)) return [];
  return raw.filter(
    (ride): ride is DriverActiveRide =>
      !!ride && typeof ride === 'object' && Number((ride as DriverActiveRide).id) > 0
  );
}

function asOfferList(raw: unknown): DispatchOffer[] {
  if (!Array.isArray(raw)) return [];
  return raw.filter(
    (offer): offer is DispatchOffer =>
      !!offer && typeof offer === 'object' && Number((offer as DispatchOffer).id) > 0
  );
}

export function fetchDriverMe(token: string) {
  return apiRequest<{ user: DriverUser; permissions?: DriverPermissions }>(
    '/api/taxi/v1/driver/me',
    { token }
  );
}

export function updateDriverAccent(token: string, accent: string) {
  return apiRequest<{ pwa_accent?: string }>('/api/taxi/v1/driver/accent', {
    method: 'PUT',
    token,
    body: { accent },
  });
}

export function updateDriverRideAlertTone(token: string, tone: string) {
  return apiRequest<{ ride_alert_tone?: string }>('/api/taxi/v1/driver/ride-alert-tone', {
    method: 'PUT',
    token,
    body: { tone },
  });
}

export function fetchDriverVehicles(token: string) {
  return apiRequest<DriverVehiclesResponse>('/api/taxi/v1/driver/vehicles', { token });
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
  return apiRequest<{
    data?: {
      offers?: DispatchOffer[];
      pending_approval_offers?: DispatchOffer[];
      declined_offers?: DispatchOffer[];
      archived_offers?: DispatchOffer[];
      active_ride?: DriverActiveRide | null;
      parked_assigned_rides?: DriverActiveRide[];
      scheduled_rides?: DriverActiveRide[];
      overdue_scheduled_rides?: DriverActiveRide[];
    };
    offers?: DispatchOffer[];
    meta?: {
      driver_cancel_reasons?: DriverCancelReason[];
    };
  }>('/api/taxi/v1/driver/dispatch/inbox', { token });
}

/** Normaliseer inbox-payload (API: data.offers). */
export function inboxOffersFromResponse(inbox: {
  data?: { offers?: DispatchOffer[] };
  offers?: DispatchOffer[];
}): DispatchOffer[] {
  const raw = inbox?.data?.offers ?? inbox?.offers ?? [];
  return Array.isArray(raw) ? raw : [];
}

export function inboxDataFromResponse(inbox: {
  data?: {
    offers?: DispatchOffer[];
    declined_offers?: DispatchOffer[];
    archived_offers?: DispatchOffer[];
    active_ride?: DriverActiveRide | null;
    parked_assigned_rides?: DriverActiveRide[];
    scheduled_rides?: DriverActiveRide[];
    overdue_scheduled_rides?: DriverActiveRide[];
  };
  offers?: DispatchOffer[];
  meta?: { driver_cancel_reasons?: DriverCancelReason[] };
}): DriverInboxData {
  const active = inbox?.data?.active_ride;
  return {
    offers: inboxOffersFromResponse(inbox),
    archivedOffers: asOfferList(inbox?.data?.archived_offers),
    declinedOffers: asOfferList(inbox?.data?.declined_offers),
    activeRide:
      active && typeof active === 'object' && Number(active.id) > 0 ? active : null,
    parkedAssignedRides: asRideList(inbox?.data?.parked_assigned_rides),
    scheduledRides: asRideList(inbox?.data?.scheduled_rides),
    overdueScheduledRides: asRideList(inbox?.data?.overdue_scheduled_rides),
    cancelReasons: Array.isArray(inbox?.meta?.driver_cancel_reasons)
      ? inbox.meta.driver_cancel_reasons
      : [],
  };
}

export function inboxActiveRideFromResponse(inbox: {
  data?: { active_ride?: DriverActiveRide | null };
}): DriverActiveRide | null {
  return inboxDataFromResponse(inbox).activeRide;
}

export function inboxCancelReasonsFromResponse(inbox: {
  meta?: { driver_cancel_reasons?: DriverCancelReason[] };
}): DriverCancelReason[] {
  return inboxDataFromResponse(inbox).cancelReasons;
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

export function startRide(token: string, rideId: number) {
  return apiRequest(`/api/taxi/v1/driver/dispatch/rides/${rideId}/start`, {
    method: 'POST',
    token,
  });
}

export function cancelAcceptedRide(token: string, rideId: number, reasonCode: string) {
  return apiRequest(`/api/taxi/v1/driver/dispatch/rides/${rideId}/cancel`, {
    method: 'POST',
    token,
    body: { reason_code: reasonCode },
  });
}

export function isMarketplaceOffer(offer: DispatchOffer): boolean {
  const ride = offer.ride;
  return !!(ride?.fee_breakdown?.is_marketplace || ride?.is_nexa_suite);
}

export function isMarketplaceRide(ride?: DriverActiveRide | null): boolean {
  return !!(ride?.fee_breakdown?.is_marketplace || ride?.is_nexa_suite);
}

export function vehicleDisplayLabel(v?: DriverVehicle | null): string {
  if (!v) return '';
  const plate = String(v.license_plate || v.label || '').trim();
  if (plate) return plate;
  const name = String(v.name || '').trim();
  return name || (v.id ? `Voertuig ${v.id}` : '');
}

export function vehicleDisplayName(v?: DriverVehicle | null): string {
  if (!v) return '';
  return String(v.name || '').trim();
}

export type PlanningRide = {
  id: number;
  status?: string;
  status_label?: string;
  is_contract?: boolean;
  is_nexa_suite?: boolean;
  nexa_suite_label?: string | null;
  is_network_ride?: boolean;
  network_label?: string | null;
  pickup_address?: string;
  dropoff_address?: string;
  pickup_at?: string | null;
  planning_date?: string | null;
  customer_name?: string | null;
  passengers?: number | null;
  quoted_price?: number | null;
  fee_breakdown?: {
    is_marketplace?: boolean;
    is_network?: boolean;
    owner_name?: string;
  } | null;
};

export type PlanningDay = {
  date: string;
  is_today?: boolean;
  ride_count?: number;
  rides?: PlanningRide[];
  shift_count?: number;
  shifts?: Array<{
    starts_at?: string;
    ends_at?: string;
    vehicle_label?: string | null;
    notes?: string | null;
  }>;
};

export type DriverPlanningWeek = {
  from: string;
  to: string;
  today: string;
  days: PlanningDay[];
};

export function fetchDriverPlanningWeek(
  token: string,
  from?: string | null,
  vehicleId?: number | null
) {
  const params = new URLSearchParams();
  if (from) params.set('from', from);
  if (vehicleId) params.set('vehicle_id', String(vehicleId));
  const qs = params.toString();
  return apiRequest<{ data?: DriverPlanningWeek }>(
    `/api/taxi/v1/driver/planning${qs ? `?${qs}` : ''}`,
    { token }
  );
}
