import { Platform, Vibration } from 'react-native';
import {
  normalizeRideAlertTone,
  rideAlertNotificationSound,
  type RideAlertTone,
} from '../audio/rideAlert';

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
const channelReady = new Set<string>();

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

async function ensureSilentOfferChannel(): Promise<string> {
  const channelId = 'driver-offers-silent';
  if (!Notifications || Platform.OS !== 'android') return channelId;
  if (channelReady.has(channelId)) return channelId;
  await Notifications.setNotificationChannelAsync(channelId, {
    name: 'Ritaanvragen',
    importance: Notifications.AndroidImportance.HIGH,
    sound: null,
    enableVibrate: false,
    lockscreenVisibility: Notifications.AndroidNotificationVisibility.PUBLIC,
  });
  channelReady.add(channelId);
  return channelId;
}

async function ensureOfferChannel(tone: RideAlertTone): Promise<string> {
  const channelId = `driver-offers-${tone}`;
  if (!Notifications || Platform.OS !== 'android') return 'driver-offers';
  if (channelReady.has(channelId)) return channelId;
  await Notifications.setNotificationChannelAsync(channelId, {
    name: 'Ritaanvragen',
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 120, 60, 120, 60, 200],
    lightColor: '#EF4444',
    sound: rideAlertNotificationSound(tone),
    enableVibrate: true,
    lockscreenVisibility: Notifications.AndroidNotificationVisibility.PUBLIC,
  });
  channelReady.add(channelId);
  return channelId;
}

export async function notifyNewDriverOffer(opts: {
  count: number;
  pickup?: string | null;
  dropoff?: string | null;
  playSound?: boolean;
  tone?: string | null;
}): Promise<void> {
  const pickup = String(opts.pickup || '').trim() || 'Nieuwe rit';
  const dropoff = String(opts.dropoff || '').trim();
  const body = dropoff ? `${pickup} → ${dropoff}` : pickup;
  const title =
    opts.count > 1 ? `${opts.count} nieuwe ritaanvragen` : 'Nieuwe ritaanvraag';
  const playSound = opts.playSound !== false;
  const tone = normalizeRideAlertTone(opts.tone);

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
  const ok = await ensureDriverNotificationPermission();
  if (!ok) return;

  try {
    const channelId =
      Platform.OS === 'android'
        ? playSound
          ? await ensureOfferChannel(tone)
          : await ensureSilentOfferChannel()
        : undefined;
    await Notifications.scheduleNotificationAsync({
      content: {
        title,
        body,
        sound: playSound ? rideAlertNotificationSound(tone) : undefined,
        data: { type: 'driver_offer', screen: 'requests' },
        ...(Platform.OS === 'ios' ? { interruptionLevel: 'timeSensitive' as const } : {}),
        ...(Platform.OS === 'android'
          ? { channelId: channelId || 'driver-offers', color: '#EF4444' }
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
  // Fallback-kanaal + alle toonvarianten (sound zit vast op channel-creatie)
  await Notifications.setNotificationChannelAsync('driver-offers', {
    name: 'Ritaanvragen',
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 120, 60, 120, 60, 200],
    lightColor: '#EF4444',
    sound: rideAlertNotificationSound('classic'),
    enableVibrate: true,
    lockscreenVisibility: Notifications.AndroidNotificationVisibility.PUBLIC,
  });
  for (const tone of ['classic', 'chime', 'alert', 'soft', 'siren'] as RideAlertTone[]) {
    await ensureOfferChannel(tone);
  }
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
