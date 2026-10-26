import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import { Platform } from 'react-native';
import { apiRequest } from '../api/client';
import {
  ensureDriverNotificationPermission,
  prepareDriverNotificationChannels,
} from './driverOffers';

const STORAGE_KEY = 'nexa_taxi_expo_push_token';

let latestAuthToken: string | null = null;
let tokenListenerAttached = false;

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
  await prepareDriverNotificationChannels();
  const allowed = await ensureDriverNotificationPermission();
  if (!allowed) return;

  const projectId = expoProjectId();
  const Notifications = notificationsModule();
  if (!projectId || !Notifications) return;

  watchPushTokenChanges();

  try {
    const expo = await Notifications.getExpoPushTokenAsync({ projectId });
    const token = expo.data?.trim();
    if (!token) return;
    await apiRequest('/api/taxi/v1/driver/push-token', {
      method: 'PUT',
      token: authToken,
      body: {
        token,
        platform: Platform.OS === 'ios' ? 'ios' : 'android',
      },
    });
    await AsyncStorage.setItem(STORAGE_KEY, token);
  } catch {
    // Geen projectId/FCM of netwerk: lokaal aanbod blijft, remote push komt later.
  }
}

export async function unregisterDriverPushToken(authToken: string | null | undefined): Promise<void> {
  latestAuthToken = null;
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
