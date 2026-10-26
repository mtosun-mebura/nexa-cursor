import { t } from '../i18n';
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
      const asked = await Notifications!.requestPermissionsAsync({
        ios: {
          allowAlert: true,
          allowBadge: true,
          allowSound: true,
        },
      });
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
    name: t("Ritaanvragen"),
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
    name: t("Ritaanvragen"),
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

export async function setDriverOfferBadge(count: number): Promise<void> {
  if (!Notifications) return;
  const next = Math.max(0, Math.min(99, Math.round(count)));
  try {
    await Notifications.setBadgeCountAsync(next);
  } catch {
    /* ignore */
  }
}

export async function notifyNewDriverOffer(opts: {
  count: number;
  pickup?: string | null;
  dropoff?: string | null;
  playSound?: boolean;
  tone?: string | null;
  badge?: number;
}): Promise<void> {
  const pickup = String(opts.pickup || '').trim() || t("Nieuwe rit");
  const dropoff = String(opts.dropoff || '').trim();
  const body = dropoff ? `${pickup} → ${dropoff}` : pickup;
  const title =
    opts.count > 1 ? `${opts.count} nieuwe ritaanvragen` : t("Nieuwe ritaanvraag");
  const playSound = opts.playSound !== false;
  const tone = normalizeRideAlertTone(opts.tone);
  const badge =
    opts.badge != null && Number.isFinite(opts.badge) ? Math.max(0, Math.round(opts.badge)) : undefined;

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

  const sounds: Array<string | false> = playSound
    ? [rideAlertNotificationSound(tone), rideAlertNotificationSound('alert'), 'default']
    : [false];
  for (const sound of sounds) {
    try {
      const channelTone =
        sound === rideAlertNotificationSound('alert') || sound === 'default' ? 'alert' : tone;
      const channelId =
        Platform.OS === 'android'
          ? sound
            ? await ensureOfferChannel(channelTone)
            : await ensureSilentOfferChannel()
          : undefined;
      await Notifications.scheduleNotificationAsync({
        content: {
          title,
          body,
          // iOS standby: content.sound is het betrouwbare geluidspad (niet expo-audio).
          sound,
          ...(badge != null ? { badge } : {}),
          data: { type: 'driver_offer', screen: 'requests' },
          ...(Platform.OS === 'ios' ? { interruptionLevel: 'timeSensitive' as const } : {}),
          ...(Platform.OS === 'android'
            ? { channelId: channelId || 'driver-offers', color: '#EF4444' }
            : {}),
        },
        trigger: null,
      });
      if (badge != null) await setDriverOfferBadge(badge);
      return;
    } catch {
      /* volgend geluid: Alert, daarna het systeemgeluid */
    }
  }
}

/** Banner in de app als de klant zegt dat die niet is opgehaald. */
export async function notifyPickupDenied(opts: {
  title: string;
  body: string;
  rideId?: number | null;
  playSound?: boolean;
  tone?: string | null;
}): Promise<void> {
  const title = String(opts.title || '').trim() || t("Klant niet opgehaald");
  const body = String(opts.body || '').trim();
  if (!body) return;
  const playSound = opts.playSound !== false;
  const tone = normalizeRideAlertTone(opts.tone);

  try {
    if (Platform.OS === 'ios') {
      Vibration.vibrate();
    } else {
      Vibration.vibrate([0, 180, 80, 180]);
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
        sound: playSound ? rideAlertNotificationSound(tone) : false,
        data: {
          type: 'pickup_denied',
          screen: 'trips',
          ride_id: opts.rideId ?? null,
        },
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

/** Seintje dat de chauffeur nu moet vertrekken naar de volgende geaccepteerde klant. */
export async function notifyPickupDeparture(opts: {
  title: string;
  body: string;
  rideId?: number | null;
}): Promise<void> {
  const title = String(opts.title || '').trim() || t("Tijd om te vertrekken");
  const body = String(opts.body || '').trim();
  if (!body) return;

  try {
    if (Platform.OS === 'ios') {
      Vibration.vibrate();
    } else {
      Vibration.vibrate([0, 180, 80, 180]);
    }
  } catch {
    /* ignore */
  }

  if (!Notifications) return;
  const ok = await ensureDriverNotificationPermission();
  if (!ok) return;

  try {
    const channelId =
      Platform.OS === 'android' ? await ensureOfferChannel('classic') : undefined;
    await Notifications.scheduleNotificationAsync({
      content: {
        title,
        body,
        sound: rideAlertNotificationSound('classic'),
        data: {
          type: 'pickup_departure',
          screen: 'trips',
          ride_id: opts.rideId ?? null,
        },
        ...(Platform.OS === 'ios' ? { interruptionLevel: 'timeSensitive' as const } : {}),
        ...(Platform.OS === 'android'
          ? { channelId: channelId || 'driver-offers', color: '#F59E0B' }
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
    name: t("Ritaanvragen"),
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
  onOpenRequests: () => void,
  onOpenTrips?: () => void
): () => void {
  if (!Notifications) return () => undefined;
  const sub = Notifications.addNotificationResponseReceivedListener((response) => {
    const type = response.notification.request.content.data?.type;
    if (type === 'driver_offer') onOpenRequests();
    if (type === 'pickup_denied' || type === 'pickup_departure') onOpenTrips?.();
  });
  return () => sub.remove();
}
