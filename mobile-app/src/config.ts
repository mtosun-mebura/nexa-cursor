import Constants from 'expo-constants';

/** Backend zonder trailing slash. Override via app.json extra.apiBaseUrl of EXPO_PUBLIC_API_BASE_URL. */
export const API_BASE_URL = (
  process.env.EXPO_PUBLIC_API_BASE_URL ||
  (Constants.expoConfig?.extra?.apiBaseUrl as string | undefined) ||
  'https://nexasuite.online'
).replace(/\/$/, '');

export const COLORS = {
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
};
