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
  can_handle_contract_rides?: boolean;
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
  pickup_lat?: number | null;
  pickup_lng?: number | null;
  dropoff_lat?: number | null;
  dropoff_lng?: number | null;
  pickup_at?: string;
  customer_name?: string;
  customer_email?: string | null;
  customer_phone?: string | null;
  customer_note?: string | null;
  quoted_price?: number | null;
  payment_status?: string | null;
  payment_method?: string | null;
  payment_paid?: boolean;
  payment?: RidePaymentSummary | null;
  invoice?: RideInvoiceSummary | null;
  passengers?: number | null;
  distance_km?: number | null;
  duration_seconds?: number | null;
  duration_minutes?: number | null;
  is_network_ride?: boolean;
  is_nexa_suite?: boolean;
  is_contract?: boolean;
  nexa_suite_label?: string | null;
  owner_company_name?: string | null;
  source?: string | null;
  ride_type?: string | null;
  transport_contract_id?: number | null;
  is_pickup_overdue?: boolean;
  is_scheduled_overdue?: boolean;
  can_cancel_with_reason?: boolean;
  pickup_proposal?: {
    status?: string | null;
    proposed_at?: string | null;
    customer_remark?: string | null;
  } | null;
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
    executor_name?: string;
    customer_pays?: number;
    nexa_fee?: number;
    nexa_fee_percent?: number;
    driver_share?: number;
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

export type RidePaymentSummary = {
  method?: string | null;
  status?: string | null;
  amount_due?: number | null;
  quoted_price?: number | null;
  can_complete?: boolean;
  requires_payment_before_complete?: boolean;
  cash_payment_enabled?: boolean;
  driver_payment_enabled?: boolean;
  payment_error?: string | null;
  payment_leg_label?: string | null;
};

export type RideInvoiceSummary = {
  can_send?: boolean;
  customer_email?: string | null;
  invoice_number?: string | null;
  invoice_sent?: boolean;
  invoice_leg_label?: string | null;
  has_invoice?: boolean;
};

export type RideOpenPayment = {
  id?: number;
  status?: string | null;
  amount?: number | null;
  checkout_url?: string | null;
  qr_url?: string | null;
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
  completedRides: DriverActiveRide[];
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
      completed_rides?: DriverActiveRide[];
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
    completed_rides?: DriverActiveRide[];
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
    completedRides: asRideList(inbox?.data?.completed_rides),
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

export function completeRide(token: string, rideId: number) {
  return apiRequest(`/api/taxi/v1/driver/dispatch/rides/${rideId}/complete`, {
    method: 'POST',
    token,
  });
}

export function fetchRidePayment(token: string, rideId: number) {
  return apiRequest<{
    data?: {
      ride?: DriverActiveRide;
      payment?: RidePaymentSummary | null;
      open_payment?: RideOpenPayment | null;
    };
  }>(`/api/taxi/v1/driver/dispatch/rides/${rideId}/payment`, { token });
}

export function createRideQrPayment(token: string, rideId: number, amount: number) {
  return apiRequest<{
    message?: string;
    data?: {
      ride?: DriverActiveRide;
      open_payment?: RideOpenPayment | null;
    };
  }>(`/api/taxi/v1/driver/dispatch/rides/${rideId}/payment`, {
    method: 'POST',
    token,
    body: { amount },
  });
}

export function markRideCashPaid(token: string, rideId: number, amount?: number) {
  return apiRequest<{
    message?: string;
    data?: { ride?: DriverActiveRide };
  }>(`/api/taxi/v1/driver/dispatch/rides/${rideId}/payment/cash`, {
    method: 'POST',
    token,
    body: amount != null ? { amount } : {},
  });
}

export function fetchRideInvoice(token: string, rideId: number) {
  return apiRequest<{ data?: RideInvoiceSummary }>(
    `/api/taxi/v1/driver/dispatch/rides/${rideId}/invoice`,
    { token }
  );
}

export function sendRideInvoice(
  token: string,
  rideId: number,
  email: string,
  invoiceNumber?: string
) {
  return apiRequest<{
    message?: string;
    data?: { invoice?: RideInvoiceSummary; ride?: DriverActiveRide };
  }>(`/api/taxi/v1/driver/dispatch/rides/${rideId}/invoice/send`, {
    method: 'POST',
    token,
    body: {
      email,
      invoice_number: invoiceNumber || undefined,
    },
  });
}

export function proposePickup(token: string, rideId: number, pickupAt: string) {
  return apiRequest(`/api/taxi/v1/driver/dispatch/rides/${rideId}/propose-pickup`, {
    method: 'POST',
    token,
    body: { pickup_at: pickupAt },
  });
}

export function releaseAcceptedRide(token: string, rideId: number) {
  return apiRequest(`/api/taxi/v1/driver/dispatch/rides/${rideId}/release`, {
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

export function isRidePaid(ride?: {
  payment_paid?: boolean;
  payment_status?: string | null;
  payment?: { status?: string | null } | null;
} | null): boolean {
  if (!ride) return false;
  if (ride.payment_paid === true) return true;
  if (ride.payment?.status === 'paid') return true;
  return ride.payment_status === 'paid';
}

export function rideRequiresPaymentBeforeComplete(ride?: DispatchOfferRide | null): boolean {
  if (!ride || isContractRide(ride)) return false;
  if (isRidePaid(ride)) return false;
  if (typeof ride.payment?.requires_payment_before_complete === 'boolean') {
    return ride.payment.requires_payment_before_complete;
  }
  if (typeof ride.payment?.can_complete === 'boolean') {
    return !ride.payment.can_complete;
  }
  const due = ride.payment?.amount_due ?? ride.quoted_price;
  return due != null && Number(due) >= 0.01;
}

export function isRideInvoiceSent(ride?: DispatchOfferRide | null): boolean {
  if (!ride) return false;
  return ride.invoice?.invoice_sent === true;
}

export function isContractRide(ride?: {
  is_contract?: boolean;
  transport_contract_id?: number | null;
  source?: string | null;
  ride_type?: string | null;
} | null): boolean {
  if (!ride) return false;
  if (ride.is_contract) return true;
  if (Number(ride.transport_contract_id) > 0) return true;
  if (ride.source === 'contract') return true;
  return ride.ride_type === 'contract_group' || ride.ride_type === 'contract_individual';
}

export type RideChannelKind = 'contract' | 'network' | 'marketplace' | 'taxi';

export function rideChannelKind(ride?: {
  is_contract?: boolean;
  transport_contract_id?: number | null;
  source?: string | null;
  ride_type?: string | null;
  is_network_ride?: boolean;
  is_nexa_suite?: boolean;
  fee_breakdown?: { is_network?: boolean; is_marketplace?: boolean } | null;
} | null): RideChannelKind {
  if (isContractRide(ride)) return 'contract';
  if (ride?.is_network_ride || ride?.fee_breakdown?.is_network) return 'network';
  if (ride?.is_nexa_suite || ride?.fee_breakdown?.is_marketplace) return 'marketplace';
  return 'taxi';
}

export function rideChannelLabel(ride?: {
  is_contract?: boolean;
  transport_contract_id?: number | null;
  source?: string | null;
  ride_type?: string | null;
  is_network_ride?: boolean;
  is_nexa_suite?: boolean;
  owner_company_name?: string | null;
  fee_breakdown?: { is_network?: boolean; is_marketplace?: boolean; owner_name?: string } | null;
} | null): string {
  const kind = rideChannelKind(ride);
  if (kind === 'contract') return 'Contract';
  if (kind === 'network') {
    const owner = String(ride?.owner_company_name || ride?.fee_breakdown?.owner_name || '').trim();
    if (owner && owner !== '—') return `Netwerk van ${owner}`;
    return 'Netwerk';
  }
  if (kind === 'marketplace') return 'Marktplaats';
  return 'Taxi';
}

export function driverShareFromFee(
  fee?: {
    driver_share?: number;
    customer_pays?: number;
    nexa_fee?: number;
  } | null
): number | null {
  if (!fee) return null;
  if (fee.driver_share != null && Number.isFinite(Number(fee.driver_share))) {
    return Number(fee.driver_share);
  }
  if (fee.customer_pays != null && fee.nexa_fee != null) {
    return Math.max(0, Number(fee.customer_pays) - Number(fee.nexa_fee));
  }
  return null;
}

/** Groot: chauffeur. Grijs: klant, alleen als dat afwijkt (marktplaats/network). */
export function driverPriceDisplay(opts: {
  quotedPrice?: number | null;
  fee?: Parameters<typeof driverShareFromFee>[0];
}): { primary: number | null; customerPays: number | null } {
  const quoted =
    opts.quotedPrice != null && Number.isFinite(Number(opts.quotedPrice))
      ? Number(opts.quotedPrice)
      : null;
  const share = driverShareFromFee(opts.fee);
  const fromFee =
    opts.fee?.customer_pays != null && Number.isFinite(Number(opts.fee.customer_pays))
      ? Number(opts.fee.customer_pays)
      : null;
  const customerPays = fromFee ?? quoted;
  const primary = share != null ? share : quoted;
  if (primary == null) return { primary: null, customerPays: null };
  if (customerPays == null || customerPays === primary) {
    return { primary, customerPays: null };
  }
  return { primary, customerPays };
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
  owner_company_name?: string | null;
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
    executor_name?: string;
    customer_pays?: number;
    nexa_fee?: number;
    nexa_fee_percent?: number;
    driver_share?: number;
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

export function mergeCompletedRides(
  ...lists: Array<Array<PlanningRide | DriverActiveRide | null | undefined>>
): DriverActiveRide[] {
  const byId = new Map<number, DriverActiveRide>();
  for (const list of lists) {
    for (const item of list) {
      if (!item || String(item.status || '') !== 'completed') continue;
      const id = Number(item.id);
      if (!id) continue;
      byId.set(id, {
        ...item,
        id,
        pickup_at: item.pickup_at ?? undefined,
      } as DriverActiveRide);
    }
  }
  return [...byId.values()].sort((a, b) => {
    const ta = Date.parse(String(a.pickup_at || '')) || 0;
    const tb = Date.parse(String(b.pickup_at || '')) || 0;
    return tb - ta;
  });
}

export type DriverEarningsRide = {
  id: number;
  completed_at?: string | null;
  completed_time?: string | null;
  pickup_address?: string;
  dropoff_address?: string;
  amount?: number;
  quoted_price?: number | null;
  currency?: string;
  customer_name?: string | null;
};

export type DriverEarningsPayload = {
  period?: string;
  date?: string;
  from?: string;
  to?: string;
  label?: string;
  sub_label?: string;
  total_label?: string;
  empty_message?: string;
  is_today?: boolean;
  is_current?: boolean;
  currency?: string;
  day_total?: number;
  period_total?: number;
  ride_count?: number;
  rides?: DriverEarningsRide[];
  month?: { label?: string; total?: number; ride_count?: number } | null;
};

export function fetchDriverEarnings(
  token: string,
  date?: string | null,
  period?: 'day' | 'week' | 'month'
) {
  const params = new URLSearchParams();
  if (date) params.set('date', date);
  if (period) params.set('period', period);
  const qs = params.toString();
  return apiRequest<{ data?: DriverEarningsPayload }>(
    `/api/taxi/v1/driver/earnings${qs ? `?${qs}` : ''}`,
    { token }
  );
}
