import { t } from '../i18n';
import { AppState, Platform, Vibration } from 'react-native';
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
    handleNotification: async () => {
      // Zichtbare app: iOS speelt de push niet. De in-app toon doet dat,
      // zonder dat deze handler op de aanbod-beurt op geluid wacht.
      const visible = AppState.currentState === 'active';
      return {
        shouldShowBanner: true,
        shouldShowList: true,
        shouldPlaySound: !visible,
        shouldSetBadge: !visible,
      };
    },
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

async function ensureSystemOfferChannel(): Promise<string> {
  const channelId = 'driver-offers-system';
  if (!Notifications || Platform.OS !== 'android') return channelId;
  if (channelReady.has(channelId)) return channelId;
  await Notifications.setNotificationChannelAsync(channelId, {
    name: t("Ritaanvragen"),
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 120, 60, 120, 60, 200],
    lightColor: '#EF4444',
    // Echt geluidsbestand. De string 'default' is alleen geldig in de Expo-serverpayload.
    sound: rideAlertNotificationSound(null),
    enableVibrate: true,
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
  /**
   * Alleen als laatste redmiddel: standaard systeemgeluid i.p.v. gekozen wav.
   * Prefer altijd custom tone (ritgeluid); Silent Mode dempt beide op iOS.
   */
  systemSound?: boolean;
  tone?: string | null;
  badge?: number;
  offerId?: number | null;
  rideRequestId?: number | null;
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
  const offerId = Number(opts.offerId || 0) || null;
  const rideRequestId = Number(opts.rideRequestId || 0) || null;

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

  // Gekozen ritgeluid eerst; Alert-wav; dan pas systeemgeluid.
  const sounds: Array<string | boolean> = !playSound
    ? [false]
    : opts.systemSound
      ? [true]
      : [rideAlertNotificationSound(tone), rideAlertNotificationSound('alert'), true];
  for (const sound of sounds) {
    const shown = await scheduleOfferNotification({
      title,
      body,
      sound,
      tone,
      badge,
      offerId,
      rideRequestId,
    });
    if (shown) {
      if (badge != null) await setDriverOfferBadge(badge);
      return;
    }
  }
}

async function scheduleOfferNotification(opts: {
  title: string;
  body: string;
  sound: string | boolean;
  tone: RideAlertTone;
  badge?: number;
  offerId?: number | null;
  rideRequestId?: number | null;
}): Promise<boolean> {
  if (!Notifications) return false;
  const channelId =
    Platform.OS === 'android'
      ? opts.sound === true
        ? await ensureSystemOfferChannel()
        : opts.sound
          ? await ensureOfferChannel(
              opts.sound === rideAlertNotificationSound('alert') ? 'alert' : opts.tone
            )
          : await ensureSilentOfferChannel()
      : undefined;
  const content = {
    title: opts.title,
    body: opts.body,
    // iOS standby: `true` is het systeemgeluid. De string 'default' is een bestandsnaam.
    sound: opts.sound,
    ...(opts.badge != null ? { badge: opts.badge } : {}),
    data: {
      type: 'driver_offer',
      screen: 'requests',
      offer_id: opts.offerId ?? null,
      ride_request_id: opts.rideRequestId ?? null,
    },
    ...(Platform.OS === 'android'
      ? { channelId: channelId || 'driver-offers', color: '#EF4444' }
      : {}),
  };
  try {
    await Notifications.scheduleNotificationAsync({
      content: {
        ...content,
        ...(Platform.OS === 'ios' ? { interruptionLevel: 'timeSensitive' as const } : {}),
      },
      trigger: null,
    });
    return true;
  } catch {
    /* timeSensitive kan ontbreken op dit team; zonder dat niveau alsnog tonen */
  }
  try {
    await Notifications.scheduleNotificationAsync({
      content,
      trigger: null,
    });
    return true;
  } catch {
    return false;
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
      Platform.OS === 'android' ? await ensureOfferChannel(normalizeRideAlertTone(null)) : undefined;
    await Notifications.scheduleNotificationAsync({
      content: {
        title,
        body,
        sound: rideAlertNotificationSound(null),
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

/** Kanaal moet bestaan vóór een remote push, dus bij elke app-start. */
export async function prepareDriverNotificationChannels(): Promise<void> {
  await ensureSystemOfferChannel();
  await setupDriverNotificationChannel();
}

export async function setupDriverNotificationChannel(): Promise<void> {
  if (!Notifications || Platform.OS !== 'android') return;
  // Fallback-kanaal + alle toonvarianten (sound zit vast op channel-creatie)
  await Notifications.setNotificationChannelAsync('driver-offers', {
    name: t("Ritaanvragen"),
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 120, 60, 120, 60, 200],
    lightColor: '#EF4444',
    sound: rideAlertNotificationSound(null),
    enableVibrate: true,
    lockscreenVisibility: Notifications.AndroidNotificationVisibility.PUBLIC,
  });
  for (const tone of ['classic', 'chime', 'alert', 'soft', 'siren'] as RideAlertTone[]) {
    await ensureOfferChannel(tone);
  }
}

export type DriverNotificationOpenTarget = {
  screen: 'requests' | 'trips';
  offerId?: number | null;
  rideId?: number | null;
};

function positiveId(value: unknown): number | null {
  if (value == null || value === '') return null;
  const n = typeof value === 'number' ? value : Number(String(value).trim());
  if (!Number.isFinite(n) || n <= 0) return null;
  return Math.trunc(n);
}

function asRecord(value: unknown): Record<string, unknown> | null {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return null;
  return value as Record<string, unknown>;
}

/** Expo zet de payload soms in `body` of `data`, soms als JSON-string. */
function unwrapNotificationData(data: Record<string, unknown> | undefined | null): Record<string, unknown> | null {
  const root = asRecord(data);
  if (!root) return null;
  const nested: Record<string, unknown>[] = [];
  const body = root.body;
  if (typeof body === 'string') {
    try {
      const parsed = asRecord(JSON.parse(body));
      if (parsed) nested.push(parsed);
    } catch {
      /* body is de meldingstekst */
    }
  } else {
    const bodyObj = asRecord(body);
    if (bodyObj) nested.push(bodyObj);
  }
  const dataObj = asRecord(root.data);
  if (dataObj) nested.push(dataObj);
  return nested.reduce<Record<string, unknown>>((acc, item) => ({ ...acc, ...item }), { ...root });
}

function targetFromNotificationData(data: Record<string, unknown> | undefined | null): DriverNotificationOpenTarget | null {
  const payload = unwrapNotificationData(data);
  if (!payload) return null;
  const type = String(payload.type || '');
  const offerId = positiveId(payload.offer_id) ?? positiveId(payload.offerId);
  const rideId =
    positiveId(payload.ride_request_id) ??
    positiveId(payload.rideRequestId) ??
    positiveId(payload.ride_id) ??
    positiveId(payload.rideId);
  if (type === 'driver_offer' || offerId != null) {
    return {
      screen: 'requests',
      offerId,
      rideId,
    };
  }
  if (type === 'pickup_denied' || type === 'pickup_departure') {
    return {
      screen: 'trips',
      rideId,
    };
  }
  return null;
}

function responseDedupeKey(response: {
  actionIdentifier?: string;
  notification?: { date?: number; request?: { identifier?: string } };
}): string {
  const id = String(response.notification?.request?.identifier || '');
  const date = Number(response.notification?.date || 0);
  const action = String(response.actionIdentifier || '');
  return `${id}|${date}|${action}`;
}

let lastHandledResponseKey: string | null = null;

function takeNotificationOpenTarget(response: {
  actionIdentifier?: string;
  notification?: {
    date?: number;
    request?: { identifier?: string; content?: { data?: Record<string, unknown> } };
  };
}): DriverNotificationOpenTarget | null {
  const key = responseDedupeKey(response);
  if (lastHandledResponseKey === key) return null;
  const data = response.notification?.request?.content?.data;
  const target = targetFromNotificationData(data);
  if (!target) return null;
  lastHandledResponseKey = key;
  return target;
}

type ForegroundOfferListener = (offer: {
  offerId: number | null;
  rideId: number | null;
  title?: string;
  body?: string;
  pickupAddress?: string;
  placeName?: string;
}) => void;

let foregroundOfferListener: ForegroundOfferListener | null = null;
let foregroundOfferSub: { remove: () => void } | null = null;

/** Push terwijl de app open is: iOS speelt dan geen geluid. De luisteraar toont het aanbod. */
export function setForegroundDriverOfferListener(listener: ForegroundOfferListener | null): void {
  foregroundOfferListener = listener;
  if (!Notifications) return;
  if (listener && !foregroundOfferSub) {
    foregroundOfferSub = Notifications.addNotificationReceivedListener((notification) => {
      try {
        const target = targetFromNotificationData(
          notification.request.content.data as Record<string, unknown> | undefined
        );
        if (!target || target.screen !== 'requests') return;
        const content = notification.request.content;
        const payload = unwrapNotificationData(
          content.data as Record<string, unknown> | undefined
        );
        foregroundOfferListener?.({
          offerId: target.offerId ?? null,
          rideId: target.rideId ?? null,
          title: String(content.title || ''),
          body: String(content.body || ''),
          pickupAddress: String(payload?.pickup_address || ''),
          placeName: String(payload?.pickup_place_name || ''),
        });
      } catch {
        /* nooit crashen vanuit een push */
      }
    });
  }
  if (!listener && foregroundOfferSub) {
    foregroundOfferSub.remove();
    foregroundOfferSub = null;
  }
}

export function addDriverOfferNotificationResponseListener(
  onOpenRequests: (target: { offerId?: number | null; rideId?: number | null }) => void,
  onOpenTrips?: (rideId?: number | null) => void
): () => void {
  if (!Notifications) return () => undefined;
  const sub = Notifications.addNotificationResponseReceivedListener((response) => {
    try {
      const target = takeNotificationOpenTarget(response);
      if (!target) return;
      if (target.screen === 'requests') {
        onOpenRequests({ offerId: target.offerId ?? null, rideId: target.rideId ?? null });
      } else {
        onOpenTrips?.(target.rideId ?? null);
      }
    } catch {
      /* nooit crashen vanuit native notification callback */
    }
  });
  return () => sub.remove();
}

let initialNotificationConsumed = false;

/** Cold start: gebruiker opende de app via een melding terwijl JS nog niet luisterde. */
export async function consumeInitialDriverNotificationResponse(): Promise<DriverNotificationOpenTarget | null> {
  if (!Notifications || initialNotificationConsumed) return null;
  initialNotificationConsumed = true;
  try {
    const response = await Notifications.getLastNotificationResponseAsync();
    if (!response) return null;
    const target = takeNotificationOpenTarget(response);
    try {
      await Notifications.clearLastNotificationResponseAsync();
    } catch {
      try {
        Notifications.clearLastNotificationResponse();
      } catch {
        /* ignore */
      }
    }
    return target;
  } catch {
    return null;
  }
}
