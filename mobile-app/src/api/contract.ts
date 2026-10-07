import { apiRequest } from './client';

export type ContractUser = {
  id: number;
  name: string;
  email?: string | null;
  company_name?: string | null;
  company_logo_url?: string | null;
  company_logo_dark_url?: string | null;
  portal_role?: 'contractant' | 'contractouder' | string | null;
  portal_role_label?: string | null;
  is_contractant?: boolean;
  pwa_accent?: string | null;
};

export type ContractMe = {
  user: ContractUser;
};

export type ContractLeg = {
  ride_stop_id?: number;
  ride_request_id?: number | null;
  ride_status?: string | null;
  leg_key?: string | null;
  leg_label?: string | null;
  pickup_address?: string | null;
  pickup_lat?: number | null;
  pickup_lng?: number | null;
  destination_address?: string | null;
  destination_lat?: number | null;
  destination_lng?: number | null;
  status?: string | null;
  status_key?: string | null;
  picked_up?: boolean;
  destination_reached?: boolean;
  planned_at?: string | null;
  destination_at?: string | null;
  can_cancel?: boolean;
  can_start?: boolean;
  can_complete?: boolean;
};

export type ContractDayItem = {
  passenger_id: number;
  name: string;
  pickup_address?: string | null;
  pickup_lat?: number | null;
  pickup_lng?: number | null;
  destination_address?: string | null;
  status?: string | null;
  status_key?: string | null;
  day_status?: string | null;
  day_status_label?: string | null;
  picked_up?: boolean;
  destination_reached?: boolean;
  planned_at?: string | null;
  destination_at?: string | null;
  can_cancel?: boolean;
  absence_id?: number | null;
  absence_reason?: string | null;
  legs?: ContractLeg[];
};

export type ContractNavStop = {
  name?: string | null;
  address?: string | null;
  lat?: number | null;
  lng?: number | null;
  kind?: string | null;
  label?: string | null;
  leg_label?: string | null;
  planned_at?: string | null;
};

export type ContractToday = {
  date: string;
  customer_name?: string | null;
  destination_summary?: string | null;
  navigation?: {
    leg_key?: string | null;
    leg_label?: string | null;
    hub_label?: string | null;
    hub_address?: string | null;
    stops?: ContractNavStop[];
  } | null;
  items: ContractDayItem[];
};

export type ContractWeekDay = {
  date: string;
  is_today?: boolean;
  ride_count?: number;
  items: ContractDayItem[];
};

export type ContractWeek = {
  from: string;
  to: string;
  customer_name?: string | null;
  days: ContractWeekDay[];
};

export type ContractPassenger = {
  id: number;
  name: string;
  phone?: string | null;
  pickup_address?: string | null;
  absent_today?: boolean;
  absence_reason?: string | null;
};

export type ContractAbsence = {
  id: number;
  passenger_id: number;
  passenger_name: string;
  date: string;
  reason?: string | null;
};

export function fetchContractMe(token: string) {
  return apiRequest<ContractMe>('/api/taxi/v1/contract/me', { token });
}

export function fetchContractToday(token: string) {
  return apiRequest<{ data: ContractToday }>('/api/taxi/v1/contract/today', { token });
}

export function fetchContractWeek(token: string, from?: string) {
  const qs = from ? `?from=${encodeURIComponent(from)}` : '';
  return apiRequest<{ data: ContractWeek }>(`/api/taxi/v1/contract/week${qs}`, { token });
}

export function fetchContractPassengers(token: string) {
  return apiRequest<{ data: { passengers: ContractPassenger[] } }>(
    '/api/taxi/v1/contract/passengers',
    { token }
  );
}

export function fetchContractAbsences(token: string) {
  return apiRequest<{ data: { absences: ContractAbsence[] } }>(
    '/api/taxi/v1/contract/absences',
    { token }
  );
}

export function storeContractAbsence(
  token: string,
  passengerId: number,
  body: { date_from: string; date_to?: string; reason?: string }
) {
  return apiRequest<{ message?: string; data?: unknown }>(
    `/api/taxi/v1/contract/passengers/${passengerId}/absences`,
    { method: 'POST', token, body }
  );
}

export function destroyContractAbsence(token: string, absenceId: number) {
  return apiRequest<{ message?: string }>(`/api/taxi/v1/contract/absences/${absenceId}`, {
    method: 'DELETE',
    token,
  });
}

export function updateContractAccent(token: string, accent: string) {
  return apiRequest<{ pwa_accent?: string }>('/api/taxi/v1/contract/accent', {
    method: 'PUT',
    token,
    body: { accent },
  });
}

export function startContractRide(token: string, rideStopId: number) {
  return apiRequest<{ message?: string; data?: { ride_request_id?: number; status?: string } }>(
    `/api/taxi/v1/contract/stops/${rideStopId}/start`,
    { method: 'POST', token }
  );
}

export function completeContractRide(token: string, rideStopId: number) {
  return apiRequest<{ message?: string; data?: { ride_request_id?: number; status?: string } }>(
    `/api/taxi/v1/contract/stops/${rideStopId}/complete`,
    { method: 'POST', token }
  );
}
