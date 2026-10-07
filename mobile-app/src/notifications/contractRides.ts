import { Platform, Vibration } from 'react-native';

type NotificationsModule = typeof import('expo-notifications');

let Notifications: NotificationsModule | null = null;
try {
  // Native module ontbreekt tot een rebuild met expo-notifications — crash dan niet.
  // eslint-disable-next-line @typescript-eslint/no-require-imports
  Notifications = require('expo-notifications') as NotificationsModule;
  Notifications.setNotificationHandler({
    handleNotification: async () => ({
      shouldShowBanner: true,
      shouldShowList: true,
      shouldPlaySound: true,
      shouldSetBadge: true,
    }),
  });
} catch {
  Notifications = null;
}

let permissionReady: Promise<boolean> | null = null;

export function contractNotificationsAvailable(): boolean {
  return Notifications != null;
}

export async function ensureContractNotificationPermission(): Promise<boolean> {
  if (!Notifications) return false;
  if (!permissionReady) {
    permissionReady = (async () => {
      const current = await Notifications!.getPermissionsAsync();
      if (
        current.granted ||
        current.ios?.status === Notifications!.IosAuthorizationStatus.PROVISIONAL
      ) {
        return true;
      }
      const asked = await Notifications!.requestPermissionsAsync();
      return !!(
        asked.granted ||
        asked.ios?.status === Notifications!.IosAuthorizationStatus.PROVISIONAL
      );
    })().catch(() => false);
  }
  return permissionReady;
}

export async function notifyNewContractRide(opts: {
  count: number;
  pickup?: string | null;
  dropoff?: string | null;
  playSound?: boolean;
}): Promise<void> {
  const pickup = String(opts.pickup || '').trim() || 'Nieuwe rit';
  const dropoff = String(opts.dropoff || '').trim();
  const body = dropoff ? `${pickup} → ${dropoff}` : pickup;
  const title = opts.count > 1 ? `${opts.count} nieuwe ritten` : 'Nieuwe rit';

  try {
    if (Platform.OS === 'ios') {
      Vibration.vibrate();
    } else {
      Vibration.vibrate([0, 120, 60, 120, 60, 200]);
    }
  } catch {
    /* ignore */
  }

  if (!Notifications) return;
  const ok = await ensureContractNotificationPermission();
  if (!ok) return;

  try {
    await Notifications.scheduleNotificationAsync({
      content: {
        title,
        body,
        sound: opts.playSound !== false,
        data: { type: 'contract_ride', screen: 'trips' },
        ...(Platform.OS === 'android'
          ? { channelId: 'contract-rides', color: '#2563EB' }
          : {}),
      },
      trigger: null,
    });
  } catch {
    /* ignore */
  }
}

export async function setupContractNotificationChannel(): Promise<void> {
  if (!Notifications || Platform.OS !== 'android') return;
  await Notifications.setNotificationChannelAsync('contract-rides', {
    name: 'Contractritten',
    importance: Notifications.AndroidImportance.HIGH,
    vibrationPattern: [0, 120, 60, 120],
    lightColor: '#2563EB',
    sound: 'default',
  });
}
