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
      shouldShowAlert: true,
      shouldPlaySound: true,
      shouldSetBadge: true,
    }),
  });
} catch {
  Notifications = null;
}

let permissionReady: Promise<boolean> | null = null;

export function driverNotificationsAvailable(): boolean {
  return Notifications != null;
}

export async function ensureDriverNotificationPermission(): Promise<boolean> {
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

export async function notifyNewDriverOffer(opts: {
  count: number;
  pickup?: string | null;
  dropoff?: string | null;
  playSound?: boolean;
}): Promise<void> {
  const pickup = String(opts.pickup || '').trim() || 'Nieuwe rit';
  const dropoff = String(opts.dropoff || '').trim();
  const body = dropoff ? `${pickup} → ${dropoff}` : pickup;
  const title =
    opts.count > 1 ? `${opts.count} nieuwe ritaanvragen` : 'Nieuwe ritaanvraag';

  try {
    Vibration.vibrate([0, 120, 60, 120, 60, 200]);
  } catch {
    /* ignore */
  }

  if (!Notifications) return;
  const ok = await ensureDriverNotificationPermission();
  if (!ok) return;

  try {
    await Notifications.scheduleNotificationAsync({
      content: {
        title,
        body,
        sound: opts.playSound !== false,
        data: { type: 'driver_offer', screen: 'requests' },
        ...(Platform.OS === 'android'
          ? { channelId: 'driver-offers', color: '#EF4444' }
          : {}),
      },
      trigger: null,
    });
  } catch {
    /* ignore */
  }
}

export async function setupDriverNotificationChannel(): Promise<void> {
  if (!Notifications || Platform.OS !== 'android') return;
  await Notifications.setNotificationChannelAsync('driver-offers', {
    name: 'Ritaanvragen',
    importance: Notifications.AndroidImportance.HIGH,
    vibrationPattern: [0, 120, 60, 120],
    lightColor: '#EF4444',
    sound: 'default',
  });
}

export function addDriverOfferNotificationResponseListener(
  onOpenRequests: () => void
): () => void {
  if (!Notifications) return () => undefined;
  const sub = Notifications.addNotificationResponseReceivedListener((response) => {
    const type = response.notification.request.content.data?.type;
    if (type === 'driver_offer') onOpenRequests();
  });
  return () => sub.remove();
}
