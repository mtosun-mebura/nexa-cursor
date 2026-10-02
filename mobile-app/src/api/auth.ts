import { apiRequest } from './client';

export type AppModes = {
  chauffeur: boolean;
  contract: boolean;
  marketplace: boolean;
  network: boolean;
};

export type AppScreen = {
  key: string;
  title: string;
  description: string;
  badge: string;
  url: string;
  primary?: boolean;
};

export type Capabilities = {
  screens: AppScreen[];
  modes: AppModes;
  default_screen: string | null;
  company: {
    id: number | null;
    name: string | null;
    package_key: string | null;
  };
};

export type BootstrapSession = {
  user: { id: number; email: string; name: string };
  capabilities: Capabilities;
  tokens: {
    driver?: { token: string; token_type: string; expires_at?: string | null };
    contract?: { token: string; token_type: string; expires_at?: string | null };
  };
};

export function loginWithPassword(email: string, password: string) {
  return apiRequest<BootstrapSession>('/api/taxi/v1/app/login', {
    method: 'POST',
    body: { email, password },
  });
}

export function requestLoginCode(email: string) {
  return apiRequest<{ message: string; channel?: string; retry_after?: number }>(
    '/api/taxi/v1/app/login-code/request',
    { method: 'POST', body: { email } }
  );
}

export function verifyLoginCode(input: {
  email: string;
  code: string;
  password?: string;
  skip_password?: boolean;
  channel?: string;
}) {
  return apiRequest<BootstrapSession>('/api/taxi/v1/app/login-code/verify', {
    method: 'POST',
    body: input,
  });
}
