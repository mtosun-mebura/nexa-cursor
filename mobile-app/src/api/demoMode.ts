import { apiRequest } from './client';

export type DemoModeStatus = {
  enabled: boolean;
  environment_allowed: boolean;
  fleet_size: number;
};

export async function fetchDemoModeStatus(): Promise<DemoModeStatus> {
  const res = await apiRequest<{ success?: boolean; data?: DemoModeStatus }>(
    '/api/taxi/v1/app/demo-mode',
    { method: 'GET', timeoutMs: 8000 }
  );
  return {
    enabled: !!res?.data?.enabled,
    environment_allowed: res?.data?.environment_allowed !== false,
    fleet_size: Number(res?.data?.fleet_size) || 18,
  };
}

export async function setDemoModeEnabled(
  token: string,
  enabled: boolean
): Promise<DemoModeStatus> {
  const res = await apiRequest<{ success?: boolean; data?: DemoModeStatus }>(
    '/api/taxi/v1/app/demo-mode',
    {
      method: 'PUT',
      token,
      body: { enabled },
      timeoutMs: 15000,
    }
  );
  return {
    enabled: !!res?.data?.enabled,
    environment_allowed: res?.data?.environment_allowed !== false,
    fleet_size: Number(res?.data?.fleet_size) || 18,
  };
}
