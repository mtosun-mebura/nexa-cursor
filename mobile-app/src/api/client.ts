import { API_BASE_URL } from '../config';

export class ApiError extends Error {
  status: number;
  body: unknown;

  constructor(message: string, status: number, body?: unknown) {
    super(message);
    this.status = status;
    this.body = body;
  }
}

type UnauthorizedHandler = () => void | Promise<void>;

let unauthorizedHandler: UnauthorizedHandler | null = null;
let unauthorizedNotified = false;

/** Wordt aangeroepen bij 401 op een request met Bearer-token (sessie verlopen). */
export function setUnauthorizedHandler(handler: UnauthorizedHandler | null) {
  unauthorizedHandler = handler;
  if (handler) {
    unauthorizedNotified = false;
  }
}

function notifyUnauthorized() {
  if (unauthorizedNotified || !unauthorizedHandler) return;
  unauthorizedNotified = true;
  Promise.resolve(unauthorizedHandler()).finally(() => {
    // Na logout mag een volgende login weer 401's melden.
    setTimeout(() => {
      unauthorizedNotified = false;
    }, 1500);
  });
}

export async function apiRequest<T>(
  path: string,
  options: {
    method?: string;
    token?: string | null;
    body?: unknown;
  } = {}
): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };
  if (options.token) {
    headers.Authorization = `Bearer ${options.token}`;
  }

  const res = await fetch(`${API_BASE_URL}${path}`, {
    method: options.method || 'GET',
    headers,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    if (res.status === 401 && options.token) {
      notifyUnauthorized();
    }
    throw new ApiError(
      (data as { message?: string }).message || `HTTP ${res.status}`,
      res.status,
      data
    );
  }
  return data as T;
}
