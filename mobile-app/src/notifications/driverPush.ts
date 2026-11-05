import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import { AppState, Platform } from 'react-native';
import { apiRequest } from '../api/client';
import { DRIVER_BG } from '../debug/driverBgKillSwitch';
import {
  ensureDriverNotificationPermission,
  prepareDriverNotificationChannels,
} from './driverOffers';

const STORAGE_KEY = 'nexa_taxi_expo_push_token';
const GET_TOKEN_TIMEOUT_MS = 8000;
const API_TIMEOUT_MS = 8000;
/** Na falen niet meteen opnieuw spammem (jetsam/freeze). */
const FAIL_BACKOFF_MS = 120_000;
/** Succes: hoogstens 1× per dit interval opnieuw getExpoPushTokenAsync. */
const OK_COOLDOWN_MS = 300_000;

let latestAuthToken: string | null = null;
let tokenListenerAttached = false;
let foregroundRetryAttached = false;
let lastRegistrationFailed = false;
let lastAttemptAt = 0;
let lastRegisteredToken: string | null = null;
let registerInFlight: Promise<void> | null = null;
/** Native getExpoPushTokenAsync hoogstens één keer per cold start (tenzij listener nieuw token geeft). */
let nativeTokenFetchedThisSession = false;

function expoProjectId(): string | null {
  const extra = Constants.expoConfig?.extra as { eas?: { projectId?: string } } | undefined;
  const fromConfig = extra?.eas?.projectId?.trim() || '';
  const fromEnv = (process.env.EXPO_PUBLIC_EAS_PROJECT_ID || '').trim();
  return fromConfig || fromEnv || null;
}

function notificationsModule(): typeof import('expo-notifications') | null {
  try {
    return require('expo-notifications') as typeof import('expo-notifications');
  } catch {
    return null;
  }
}

function withTimeout<T>(promise: Promise<T>, ms: number, label: string): Promise<T> {
  return new Promise<T>((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error(`${label}-timeout`)), ms);
    promise.then(
      (value) => {
        clearTimeout(timer);
        resolve(value);
      },
      (error) => {
        clearTimeout(timer);
        reject(error);
      }
    );
  });
}

async function putTokenOnServer(authToken: string, pushToken: string): Promise<void> {
  await apiRequest('/api/taxi/v1/driver/push-token', {
    method: 'PUT',
    token: authToken,
    timeoutMs: API_TIMEOUT_MS,
    body: {
      token: pushToken,
      platform: Platform.OS === 'ios' ? 'ios' : 'android',
    },
  });
  await AsyncStorage.setItem(STORAGE_KEY, pushToken);
  lastRegisteredToken = pushToken;
  lastRegistrationFailed = false;
}

/**
 * Registreert het Expo-pushtoken van deze chauffeur.
 * Single-flight, timeouts, cooldown — mag UI nooit blokkeren of native thrashen.
 */
export async function registerDriverPushToken(authToken: string): Promise<void> {
  if (!DRIVER_BG.pushRegister) return;
  if (!authToken) return;
  latestAuthToken = authToken;
  watchForegroundRetry();

  if (registerInFlight) return registerInFlight;

  const now = Date.now();
  if (lastRegisteredToken && now - lastAttemptAt < OK_COOLDOWN_MS) return;
  if (lastRegistrationFailed && now - lastAttemptAt < FAIL_BACKOFF_MS) return;

  registerInFlight = (async () => {
    lastAttemptAt = Date.now();
    try {
      // Eerst paint: native push-init op de eerste frame liet iPhone/iPad vastlopen.
      await new Promise<void>((resolve) => setTimeout(resolve, 1500));
      if (latestAuthToken !== authToken) return;

      await withTimeout(prepareDriverNotificationChannels(), 4000, 'channels').catch(() => undefined);
      const allowed = await withTimeout(ensureDriverNotificationPermission(), 6000, 'perm').catch(
        () => false
      );
      if (!allowed) {
        lastRegistrationFailed = true;
        return;
      }

      const projectId = expoProjectId();
      const Notifications = notificationsModule();
      if (!projectId || !Notifications) {
        lastRegistrationFailed = true;
        return;
      }

      let token =
        lastRegisteredToken ||
        (await AsyncStorage.getItem(STORAGE_KEY).catch(() => null))?.trim() ||
        null;

      // Native token-fetch is de crash-verdachte: max 1× per cold start, harde timeout.
      if (!nativeTokenFetchedThisSession) {
        nativeTokenFetchedThisSession = true;
        try {
          const expo = await withTimeout(
            Notifications.getExpoPushTokenAsync({ projectId }),
            GET_TOKEN_TIMEOUT_MS,
            'expo-push-token'
          );
          const fresh = expo.data?.trim() || null;
          if (fresh) token = fresh;
        } catch {
          /* cache hieronder; geen herhaalde native calls deze sessie */
        }
      }

      if (!token) {
        lastRegistrationFailed = true;
        return;
      }

      if (token === lastRegisteredToken) {
        lastRegistrationFailed = false;
        watchPushTokenChanges();
        return;
      }

      await putTokenOnServer(authToken, token);
      watchPushTokenChanges();
    } catch {
      lastRegistrationFailed = true;
    } finally {
      registerInFlight = null;
    }
  })();

  return registerInFlight;
}

/** Stille herkansing — respecteer backoff; nooit parallel. */
export function retryDriverPushTokenIfNeeded(): void {
  if (!DRIVER_BG.pushRegister) return;
  if (!latestAuthToken || !lastRegistrationFailed) return;
  if (registerInFlight) return;
  if (Date.now() - lastAttemptAt < FAIL_BACKOFF_MS) return;
  void registerDriverPushToken(latestAuthToken);
}

export async function unregisterDriverPushToken(authToken: string | null | undefined): Promise<void> {
  latestAuthToken = null;
  lastRegistrationFailed = false;
  lastRegisteredToken = null;
  const stored = await AsyncStorage.getItem(STORAGE_KEY).catch(() => null);
  if (authToken) {
    try {
      await Promise.race([
        apiRequest('/api/taxi/v1/driver/push-token', {
          method: 'DELETE',
          token: authToken,
          timeoutMs: 2000,
          body: stored ? { token: stored } : {},
        }),
        new Promise<void>((resolve) => setTimeout(resolve, 2000)),
      ]);
    } catch {
      /* logout mag niet blijven hangen */
    }
  }
  await AsyncStorage.removeItem(STORAGE_KEY).catch(() => undefined);
}

function watchForegroundRetry(): void {
  if (foregroundRetryAttached) return;
  foregroundRetryAttached = true;
  AppState.addEventListener('change', (state) => {
    if (state === 'active') retryDriverPushTokenIfNeeded();
  });
}

function watchPushTokenChanges(): void {
  if (tokenListenerAttached) return;
  const Notifications = notificationsModule();
  if (!Notifications) return;
  tokenListenerAttached = true;
  Notifications.addPushTokenListener((event) => {
    const next = String((event as { data?: string })?.data || '').trim();
    if (!next || !latestAuthToken) return;
    if (next === lastRegisteredToken) return;
    if (registerInFlight) return;
    // Alleen server bijwerken — géén getExpoPushTokenAsync (voorkomt listener-loop/crash).
    void putTokenOnServer(latestAuthToken, next).catch(() => {
      lastRegistrationFailed = true;
    });
  });
}
