import Constants from 'expo-constants';

/** Backend zonder trailing slash. Override via app.json extra.apiBaseUrl of EXPO_PUBLIC_API_BASE_URL. */
export const API_BASE_URL = (
  process.env.EXPO_PUBLIC_API_BASE_URL ||
  (Constants.expoConfig?.extra?.apiBaseUrl as string | undefined) ||
  'https://nexasuite.online'
).replace(/\/$/, '');

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
