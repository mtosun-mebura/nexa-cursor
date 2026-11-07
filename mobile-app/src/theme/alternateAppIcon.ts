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
 * iOS "system" stays on the primary icon (light/dark appearances in the asset catalog).
 * Android "system" also stays on the default MainActivity — switching Light/Dark
 * aliases on every boot disabled MainActivity and broke the launcher.
 * Explicit Licht/Donker mag wél de alias zetten (alleen bij syncIcon: true).
 */
export function alternateIconNameForPreference(
  preference: ThemePreference,
  _systemScheme: ColorSchemeName | null | undefined
): string | null {
  if (preference === 'light') return LIGHT_ICON;
  if (preference === 'dark') return DARK_ICON;
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
  let current: string | null = null;
  try {
    current = api.getAppIconName() ?? null;
  } catch {
    current = null;
  }
  if (current === target) {
    settledFor = target;
    return;
  }
  try {
    // Android: null = default MainActivity launcher. Nooit half-switchen.
    await api.setAlternateAppIcon(target);
    settledFor = target;
  } catch (error) {
    if (/cancel/i.test(errorText(error))) {
      settledFor = target;
      return;
    }
    // Alias-wissel mislukt: probeer default launcher te herstellen.
    if (Platform.OS === 'android' && target !== null) {
      try {
        await api.setAlternateAppIcon(null);
        settledFor = null;
      } catch {
        /* ignore */
      }
    }
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
  if (options?.force) settledFor = undefined;
  queued = alternateIconNameForPreference(preference, systemScheme);
  pending = pending.then(drain, drain);
}
