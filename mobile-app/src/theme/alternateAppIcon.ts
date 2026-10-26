import { ColorSchemeName, Platform } from 'react-native';
import type { ThemePreference } from './ThemeContext';

const LIGHT_ICON = 'Light';
const DARK_ICON = 'Dark';

type AlternateIconsModule = {
  supportsAlternateIcons: boolean;
  getAppIconName: () => string | null;
  setAlternateAppIcon: (name: string | null) => Promise<string | null>;
};

let cached: AlternateIconsModule | null | undefined;
let pending: Promise<void> = Promise.resolve();
let queued: string | null | undefined;
/** Last target that is already active, or that the user dismissed in the iOS alert. */
let settledFor: string | null | undefined;

/**
 * Icon name for the stored Weergave choice.
 * iOS "system" stays on the primary icon, which already has light and dark appearances.
 * Android has one launcher icon, so system follows the current scheme via an alias.
 */
export function alternateIconNameForPreference(
  preference: ThemePreference,
  systemScheme: ColorSchemeName | null | undefined
): string | null {
  if (preference === 'light') return LIGHT_ICON;
  if (preference === 'dark') return DARK_ICON;
  if (Platform.OS === 'android') {
    if (systemScheme === 'light') return LIGHT_ICON;
    if (systemScheme === 'dark') return DARK_ICON;
    return null;
  }
  return null;
}

function loadModule(): AlternateIconsModule | null {
  if (cached !== undefined) return cached;
  try {
    cached = require('expo-alternate-app-icons') as AlternateIconsModule;
  } catch {
    cached = null;
  }
  return cached;
}

function errorText(error: unknown): string {
  if (error instanceof Error) return error.message;
  if (error && typeof error === 'object' && 'message' in error) {
    return String((error as { message: unknown }).message);
  }
  return String(error);
}

async function applyIcon(target: string | null): Promise<void> {
  if (settledFor === target) return;
  const api = loadModule();
  if (!api?.supportsAlternateIcons) return;
  const current = api.getAppIconName() ?? null;
  if (current === target) {
    settledFor = target;
    return;
  }
  try {
    await api.setAlternateAppIcon(target);
    settledFor = target;
  } catch (error) {
    if (/cancel/i.test(errorText(error))) settledFor = target;
    else throw error;
  }
}

function drain(): Promise<void> {
  const target = queued;
  queued = undefined;
  if (target === undefined) return Promise.resolve();
  return applyIcon(target)
    .catch(() => {
      /* Expo Go, or the user dismissed the iOS icon alert. */
    })
    .then(() => (queued === undefined ? undefined : drain()));
}

/** Apply the home-screen icon for this preference. Skips the native call when it is already active. */
export function syncAlternateAppIcon(
  preference: ThemePreference,
  systemScheme: ColorSchemeName | null | undefined,
  options?: { force?: boolean }
): void {
  // Android has no adaptive primary icon. Wait until the phone scheme is known
  // instead of falling through to the dark alias.
  if (
    preference === 'system' &&
    Platform.OS === 'android' &&
    systemScheme !== 'light' &&
    systemScheme !== 'dark'
  ) {
    return;
  }
  if (options?.force) settledFor = undefined;
  queued = alternateIconNameForPreference(preference, systemScheme);
  pending = pending.then(drain, drain);
}
