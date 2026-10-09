import { API_BASE_URL } from '../config';

export class ApiError extends Error {
  status: number;
  body: unknown;

  constructor(message: string, status: number, body?: unknown) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.body = body;
    // Hermes/Metro: behoud prototype zodat instanceof werkt.
    Object.setPrototypeOf(this, ApiError.prototype);
  }
}

/** Robuuste statuscheck — instanceof faalt soms na bundling. */
export function apiErrorStatus(error: unknown): number | null {
  if (error instanceof ApiError) return error.status;
  if (error && typeof error === 'object' && 'status' in error) {
    const status = Number((error as { status?: unknown }).status);
    return Number.isFinite(status) ? status : null;
  }
  return null;
}

export function apiErrorMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError && error.message) return error.message;
  if (error && typeof error === 'object' && 'message' in error) {
    const message = String((error as { message?: unknown }).message || '').trim();
    if (message) return message;
  }
  return fallback;
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
