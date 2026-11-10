import AsyncStorage from '@react-native-async-storage/async-storage';

/**
 * Test. De app heeft een host nodig vóór ze de admin-lijst kan lezen, en die
 * lijst bepaalt alleen de publieke URL voor Mollie. Daarom staat de API vast.
 */
export const API_BASE_DEFAULT = 'https://nexasuite.online';

/** Eerder gekozen host/omgeving. Bij start wissen, zodat Productie niet blijft hangen. */
const STORED_HOST_KEYS = ['nexa_taxi_api_base_url'] as const;

let current = API_BASE_DEFAULT;
let hydratePromise: Promise<void> | null = null;

/** Huidige API-basis. Altijd de Test-host. */
export function getApiBaseUrl(): string {
  return current;
}

export function hydrateApiBase(): Promise<void> {
  if (!hydratePromise) {
    hydratePromise = (async () => {
      current = API_BASE_DEFAULT;
      try {
        await AsyncStorage.multiRemove([...STORED_HOST_KEYS]);
      } catch {
        /* vaste host blijft gelden */
      }
    })();
  }
  return hydratePromise;
}

export async function resolveApiBaseUrl(): Promise<string> {
  await hydrateApiBase();
  return current;
}

/**
 * Superadmin-demo (gesimuleerde taxi’s / boeken) alleen op lokaal + test.
 * Productie (o.a. nexasuite.nl) blijft uit — ook als iemand de schakelaar aanzet.
 */
export function isDemoModeEnvironmentAllowed(apiBase: string = getApiBaseUrl()): boolean {
  try {
    const host = new URL(apiBase).hostname.toLowerCase();
    if (host === 'localhost' || host === '127.0.0.1' || host === '::1') return true;
    if (host.endsWith('.local') || host.endsWith('.test') || host.endsWith('.localhost')) {
      return true;
    }
    // Test-omgeving
    if (host === 'nexasuite.online' || host.endsWith('.nexasuite.online')) return true;
    // Privé-LAN (lokale backend)
    if (/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)/.test(host)) return true;
    return false;
  } catch {
    return false;
  }
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
