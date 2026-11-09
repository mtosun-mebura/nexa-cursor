import { resolveApiBaseUrl } from '../config';
import { getLocale } from '../i18n';

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
    /** Hard cut-off — voorkomt oneindige hangs (GPS-loop / inbox). */
    timeoutMs?: number;
  } = {}
): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-Nexa-Client': 'native',
    'X-App-Locale': getLocale(),
    'Accept-Language': getLocale() === 'en' ? 'en' : 'nl',
  };
  if (options.token) {
    headers.Authorization = `Bearer ${options.token}`;
  }

  const base = await resolveApiBaseUrl();
  const timeoutMs = options.timeoutMs && options.timeoutMs > 0 ? options.timeoutMs : 0;
  const controller = timeoutMs > 0 ? new AbortController() : null;
  const timer =
    controller && timeoutMs > 0
      ? setTimeout(() => {
          try {
            controller.abort();
          } catch {
            /* ignore */
          }
        }, timeoutMs)
      : null;
  let res: Response;
  try {
    res = await fetch(`${base}${path}`, {
      method: options.method || 'GET',
      headers,
      body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
      ...(controller ? { signal: controller.signal } : {}),
    });
  } catch (e) {
    if (controller?.signal.aborted) {
      throw new ApiError('Request timeout', 408);
    }
    throw e;
  } finally {
    if (timer) clearTimeout(timer);
  }

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

function mimeFromUri(uri: string): string | null {
  const path = uri.split('?')[0]?.toLowerCase() || '';
  if (path.endsWith('.png')) return 'image/png';
  if (path.endsWith('.webp')) return 'image/webp';
  if (path.endsWith('.gif')) return 'image/gif';
  if (path.endsWith('.heic') || path.endsWith('.heif')) return 'image/heic';
  if (path.endsWith('.jpg') || path.endsWith('.jpeg')) return 'image/jpeg';
  return null;
}

/**
 * Multipart upload (foto’s).
 * Expo fetch ondersteunt géén RN `{ uri, name, type }`-parts — lokale file → Blob.
 */
export async function apiUpload<T>(
  path: string,
  options: {
    token: string;
    fieldName?: string;
    uri: string;
    fileName?: string;
    mimeType?: string;
    fields?: Record<string, string>;
  }
): Promise<T> {
  const name = options.fileName || 'photo.jpg';
  const type =
    options.mimeType || mimeFromUri(options.uri) || mimeFromUri(name) || 'image/jpeg';

  const local = await fetch(options.uri);
  if (!local.ok) {
    throw new ApiError('Lokale foto kon niet worden gelezen.', local.status);
  }
  const rawBlob = await local.blob();
  const blob =
    rawBlob.type && rawBlob.type !== 'application/octet-stream'
      ? rawBlob
      : new Blob([rawBlob], { type });

  const form = new FormData();
  form.append(options.fieldName || 'photo', blob, name);
  for (const [key, value] of Object.entries(options.fields || {})) {
    if (value !== '') form.append(key, value);
  }

  const base = await resolveApiBaseUrl();
  const res = await fetch(`${base}${path}`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${options.token}`,
      'X-Requested-With': 'XMLHttpRequest',
      'X-Nexa-Client': 'native',
    },
    body: form,
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    if (res.status === 401) {
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
