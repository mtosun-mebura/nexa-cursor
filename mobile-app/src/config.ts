import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';

/** Productie. De ingelogde keuze in AsyncStorage gaat hier vóór. */
export const API_BASE_DEFAULT = 'https://nexasuite.nl';

export type ApiEnvironment = { id: string; label: string; url: string };

export const API_ENVIRONMENTS: readonly ApiEnvironment[] = [
  { id: 'productie', label: 'Productie', url: 'https://nexasuite.nl' },
  { id: 'test', label: 'Test', url: 'https://nexasuite.online' },
];

const STORAGE_KEY = 'nexa_taxi_api_base_url';

let current = API_BASE_DEFAULT;
let hydratePromise: Promise<void> | null = null;
const listeners = new Set<() => void>();

function emit() {
  listeners.forEach((listener) => listener());
}

export function subscribeApiBase(listener: () => void) {
  listeners.add(listener);
  return () => listeners.delete(listener);
}

/**
 * https-host zonder trailing slash. Een kale domeinnaam krijgt https://.
 * http alleen voor localhost. Pad en query vallen weg.
 */
export function normalizeApiBaseUrl(raw: string | null | undefined): string | null {
  let value = String(raw || '').trim();
  if (!value) return null;
  if (!/^[a-z][a-z0-9+.-]*:\/\//i.test(value)) {
    value = `https://${value}`;
  }
  value = value.replace(/\/+$/, '');
  let url: URL;
  try {
    url = new URL(value);
  } catch {
    return null;
  }
  if (url.username || url.password) return null;
  const host = url.hostname.toLowerCase();
  const protocol = url.protocol.toLowerCase();
  if (protocol === 'http:') {
    if (host !== 'localhost' && host !== '127.0.0.1' && host !== '::1') return null;
  } else if (protocol !== 'https:') {
    return null;
  }
  const local = host === 'localhost' || host === '127.0.0.1' || host === '::1';
  if (!local && !host.includes('.')) return null;
  const port = url.port ? `:${url.port}` : '';
  const hostOut = host.includes(':') ? `[${host}]` : host;
  return `${protocol}//${hostOut}${port}`;
}

export function environmentIdForUrl(url: string, environments: readonly ApiEnvironment[] = API_ENVIRONMENTS): string {
  const normalized = normalizeApiBaseUrl(url);
  const match = environments.find((item) => item.url === normalized);
  return match ? match.id : 'aangepast';
}

/** Lijst van de server waar de app al naartoe wijst. Null als die lijst niet geladen kan worden. */
export async function fetchApiEnvironments(base?: string | null): Promise<ApiEnvironment[] | null> {
  const root = normalizeApiBaseUrl(base) || getApiBaseUrl();
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 4000);
  try {
    const res = await fetch(`${root}/api/taxi/v1/app/environments`, {
      headers: { Accept: 'application/json', 'X-Nexa-Client': 'native' },
      signal: controller.signal,
    });
    if (!res.ok) return null;
    const body = (await res.json()) as { environments?: unknown };
    if (!Array.isArray(body?.environments)) return null;
    const list: ApiEnvironment[] = [];
    const seen = new Set<string>();
    for (const item of body.environments) {
      if (!item || typeof item !== 'object') continue;
      const record = item as { name?: unknown; host?: unknown };
      const url = normalizeApiBaseUrl(typeof record.host === 'string' ? record.host : null);
      const label = typeof record.name === 'string' ? record.name.trim() : '';
      if (!url || !label || seen.has(url)) continue;
      seen.add(url);
      list.push({ id: url, label, url });
    }
    return list.length > 0 ? list : null;
  } catch {
    return null;
  } finally {
    clearTimeout(timer);
  }
}

function builtinFallback(): string {
  const fromEnv = normalizeApiBaseUrl(process.env.EXPO_PUBLIC_API_BASE_URL);
  if (fromEnv) return fromEnv;
  const extra = Constants.expoConfig?.extra?.apiBaseUrl;
  const fromExtra = normalizeApiBaseUrl(typeof extra === 'string' ? extra : null);
  return fromExtra || API_BASE_DEFAULT;
}

/** Huidige API-basis. Na hydrateApiBase() is dit de opgeslagen host. */
export function getApiBaseUrl(): string {
  return current;
}

export function hydrateApiBase(): Promise<void> {
  if (!hydratePromise) {
    hydratePromise = (async () => {
      try {
        const stored = await AsyncStorage.getItem(STORAGE_KEY);
        const normalized = normalizeApiBaseUrl(stored);
        current = normalized || builtinFallback();
      } catch {
        current = builtinFallback();
      }
      emit();
    })();
  }
  return hydratePromise;
}

export async function resolveApiBaseUrl(): Promise<string> {
  await hydrateApiBase();
  return current;
}

/** Slaat een geldige host op en gebruikt die meteen voor alle requests. */
export async function setApiBaseUrl(raw: string): Promise<string | null> {
  const normalized = normalizeApiBaseUrl(raw);
  if (!normalized) return null;
  current = normalized;
  emit();
  try {
    await AsyncStorage.setItem(STORAGE_KEY, normalized);
  } catch {
    /* geheugen blijft gelden voor deze sessie */
  }
  return normalized;
}

export type ColorPalette = {
  bg: string;
  card: string;
  border: string;
  text: string;
  muted: string;
  primary: string;
  primaryPressed: string;
  danger: string;
  success: string;
  amber: string;
  tabBar: string;
  inputBg: string;
};

export const DARK_COLORS: ColorPalette = {
  bg: '#0B1220',
  card: '#121A2B',
  border: 'rgba(148,163,184,0.22)',
  text: '#F8FAFC',
  muted: '#94A3B8',
  primary: '#2563EB',
  primaryPressed: '#1D4ED8',
  danger: '#F87171',
  success: '#22C55E',
  amber: '#F59E0B',
  tabBar: 'rgba(11,18,32,0.96)',
  inputBg: 'rgba(255,255,255,0.03)',
};

export const LIGHT_COLORS: ColorPalette = {
  bg: '#F1F5F9',
  card: '#FFFFFF',
  border: 'rgba(15,23,42,0.14)',
  text: '#0F172A',
  muted: '#475569',
  primary: '#1D4ED8',
  primaryPressed: '#1E40AF',
  danger: '#DC2626',
  success: '#15803D',
  amber: '#B45309',
  tabBar: 'rgba(255,255,255,0.98)',
  inputBg: 'rgba(15,23,42,0.05)',
};

/** Fallback (donker) voor modules buiten ThemeProvider. */
export const COLORS = DARK_COLORS;
