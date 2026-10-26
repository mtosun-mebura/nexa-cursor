import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import { AppState, Platform } from 'react-native';
import { apiRequest } from '../api/client';
import {
  ensureDriverNotificationPermission,
  prepareDriverNotificationChannels,
} from './driverOffers';

const STORAGE_KEY = 'nexa_taxi_expo_push_token';

let latestAuthToken: string | null = null;
let tokenListenerAttached = false;
let foregroundRetryAttached = false;
let lastRegistrationFailed = false;

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

/**
 * Registreert het Expo-pushtoken van deze chauffeur.
 * Zonder EAS projectId (en op Android zonder FCM) slaat dit stil over: de app blijft werken.
 */
export async function registerDriverPushToken(authToken: string): Promise<void> {
  if (!authToken) return;
  latestAuthToken = authToken;
  watchForegroundRetry();
  await prepareDriverNotificationChannels();
  const allowed = await ensureDriverNotificationPermission();
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

  watchPushTokenChanges();

  try {
    const expo = await Notifications.getExpoPushTokenAsync({ projectId });
    const token = expo.data?.trim();
    if (!token) {
      lastRegistrationFailed = true;
      return;
    }
    await apiRequest('/api/taxi/v1/driver/push-token', {
      method: 'PUT',
      token: authToken,
      body: {
        token,
        platform: Platform.OS === 'ios' ? 'ios' : 'android',
      },
    });
    await AsyncStorage.setItem(STORAGE_KEY, token);
    lastRegistrationFailed = false;
  } catch {
    // Geen projectId/FCM of netwerk: geen fouttekst in de app. Opnieuw bij de volgende voorgrond.
    lastRegistrationFailed = true;
  }
}

/** Stille herkansing nadat token ophalen eerder mislukte. */
export function retryDriverPushTokenIfNeeded(): void {
  if (latestAuthToken && lastRegistrationFailed) {
    void registerDriverPushToken(latestAuthToken);
  }
}

export async function unregisterDriverPushToken(authToken: string | null | undefined): Promise<void> {
  latestAuthToken = null;
  lastRegistrationFailed = false;
  const stored = await AsyncStorage.getItem(STORAGE_KEY).catch(() => null);
  if (authToken) {
    try {
      await apiRequest('/api/taxi/v1/driver/push-token', {
        method: 'DELETE',
        token: authToken,
        body: stored ? { token: stored } : {},
      });
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
  Notifications.addPushTokenListener(() => {
    if (latestAuthToken) {
      void registerDriverPushToken(latestAuthToken);
    }
  });
}
