import { t } from '../i18n';
import { Platform, Vibration } from 'react-native';
import {
  normalizeRideAlertTone,
  playRideAlertTone,
  rideAlertNotificationSound,
  type RideAlertTone,
} from '../audio/rideAlert';

/** Android-kanaal met alert.wav — id bump als sound wijzigt (OS past niet bij). */
const OFFER_SOUND_CHANNEL = 'driver-offers-alert-v6';
const OFFER_SILENT_CHANNEL = 'driver-offers-silent';

type NotificationsModule = typeof import('expo-notifications');

let Notifications: NotificationsModule | null = null;
let alertOnReceiveAttached = false;

function buzzAndPlayAlert(): void {
  try {
    if (Platform.OS === 'ios') Vibration.vibrate();
    else Vibration.vibrate([0, 120, 60, 120, 60, 200]);
  } catch {
    /* ignore */
  }
  try {
    playRideAlertTone('alert');
  } catch {
    /* ignore */
  }
}

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
  // Elke binnengekomen melding (voorgrond/achtergrond in geheugen) → alert.wav.
  // Lockscreen zonder JS vertrouwt op de push `sound: alert.wav`.
  if (!alertOnReceiveAttached) {
    alertOnReceiveAttached = true;
    Notifications.addNotificationReceivedListener(() => {
      buzzAndPlayAlert();
    });
  }
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
      const ask = () =>
        Notifications!.requestPermissionsAsync({
          ios: {
            allowAlert: true,
            allowBadge: true,
            allowSound: true,
            allowDisplayInCarPlay: true,
          },
        });
      let current = await Notifications!.getPermissionsAsync();
      const ok =
        current.granted ||
        current.ios?.status === Notifications!.IosAuthorizationStatus.PROVISIONAL;
      // iOS kan `granted` zijn terwijl Sounds in Instellingen uit staat.
      if (!ok || current.ios?.allowsSound === false) {
        current = await ask();
      }
      return !!(
        current.granted ||
        current.ios?.status === Notifications!.IosAuthorizationStatus.PROVISIONAL
      );
    })().catch(() => false);
  }
  return permissionReady;
}

async function ensureSilentOfferChannel(): Promise<string> {
  const channelId = OFFER_SILENT_CHANNEL;
  if (!Notifications || Platform.OS !== 'android') return channelId;
  if (channelReady.has(channelId)) return channelId;
  await Notifications.setNotificationChannelAsync(channelId, {
    name: t("Ritaanvragen (stil)"),
    importance: Notifications.AndroidImportance.HIGH,
    sound: null,
    enableVibrate: false,
    lockscreenVisibility: Notifications.AndroidNotificationVisibility.PUBLIC,
  });
  channelReady.add(channelId);
  return channelId;
}

async function ensureSystemOfferChannel(): Promise<string> {
  const channelId = OFFER_SOUND_CHANNEL;
  if (!Notifications || Platform.OS !== 'android') return channelId;
  if (channelReady.has(channelId)) return channelId;
  await Notifications.setNotificationChannelAsync(channelId, {
    name: t("Ritaanvragen"),
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 120, 60, 120, 60, 200],
    lightColor: '#EF4444',
    sound: rideAlertNotificationSound(),
    enableVibrate: true,
    lockscreenVisibility: Notifications.AndroidNotificationVisibility.PUBLIC,
    bypassDnd: false,
    audioAttributes: {
      usage: Notifications.AndroidAudioUsage.NOTIFICATION_EVENT,
      contentType: Notifications.AndroidAudioContentType.SONIFICATION,
      flags: { enforceAudibility: true },
    },
  });
  channelReady.add(channelId);
  return channelId;
}

/** Bundelbestand alert.wav — hoorbaar via APNs/FCM op sluimer/achtergrond. */
function offerNotificationSound(play: boolean): string | false {
  return play ? rideAlertNotificationSound() : false;
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
  pickupPlaceName?: string | null;
  dropoffPlaceName?: string | null;
  pickupAt?: string | null;
  quotedPrice?: number | null;
  expiresAt?: string | null;
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

  if (playSound) buzzAndPlayAlert();

  if (!Notifications) return;
  const ok = await ensureDriverNotificationPermission();
  if (!ok) return;

  const shown = await scheduleOfferNotification({
    title,
    body,
    sound: offerNotificationSound(playSound),
    tone,
    badge,
    offerId,
    rideRequestId,
    pickupAddress: opts.pickup,
    pickupPlaceName: opts.pickupPlaceName,
    dropoffAddress: opts.dropoff,
    dropoffPlaceName: opts.dropoffPlaceName,
    pickupAt: opts.pickupAt,
    quotedPrice: opts.quotedPrice,
    expiresAt: opts.expiresAt,
  });
  if (shown && badge != null) await setDriverOfferBadge(badge);
}

async function scheduleOfferNotification(opts: {
  title: string;
  body: string;
  sound: string | false;
  tone: RideAlertTone;
  badge?: number;
  offerId?: number | null;
  rideRequestId?: number | null;
  pickupAddress?: string | null;
  pickupPlaceName?: string | null;
  dropoffAddress?: string | null;
  dropoffPlaceName?: string | null;
  pickupAt?: string | null;
  quotedPrice?: number | null;
  expiresAt?: string | null;
}): Promise<boolean> {
  if (!Notifications) return false;
  const channelId =
    Platform.OS === 'android'
      ? opts.sound
        ? await ensureSystemOfferChannel()
        : await ensureSilentOfferChannel()
      : undefined;
  const content = {
    title: opts.title,
    body: opts.body,
    sound: opts.sound === false ? false : opts.sound,
    ...(opts.badge != null ? { badge: opts.badge } : {}),
    data: {
      type: 'driver_offer',
      screen: 'requests',
      offer_id: opts.offerId ?? null,
      ride_request_id: opts.rideRequestId ?? null,
      pickup_address: String(opts.pickupAddress || '').trim() || null,
      pickup_place_name: String(opts.pickupPlaceName || '').trim() || null,
      dropoff_address: String(opts.dropoffAddress || '').trim() || null,
      dropoff_place_name: String(opts.dropoffPlaceName || '').trim() || null,
      pickup_at: String(opts.pickupAt || '').trim() || null,
      quoted_price: opts.quotedPrice ?? null,
      expires_at: String(opts.expiresAt || '').trim() || null,
    },
    ...(Platform.OS === 'android'
      ? { channelId: channelId || OFFER_SOUND_CHANNEL, color: '#EF4444' }
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
          ? await ensureSystemOfferChannel()
          : await ensureSilentOfferChannel()
        : undefined;
    await Notifications.scheduleNotificationAsync({
      content: {
        title,
        body,
        sound: offerNotificationSound(playSound),
        data: {
          type: 'pickup_denied',
          screen: 'trips',
          ride_id: opts.rideId ?? null,
        },
        ...(Platform.OS === 'ios' ? { interruptionLevel: 'timeSensitive' as const } : {}),
        ...(Platform.OS === 'android'
          ? { channelId: channelId || OFFER_SOUND_CHANNEL, color: '#EF4444' }
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
    const channelId = Platform.OS === 'android' ? await ensureSystemOfferChannel() : undefined;
    await Notifications.scheduleNotificationAsync({
      content: {
        title,
        body,
        sound: offerNotificationSound(true),
        data: {
          type: 'pickup_departure',
          screen: 'trips',
          ride_id: opts.rideId ?? null,
        },
        ...(Platform.OS === 'ios' ? { interruptionLevel: 'timeSensitive' as const } : {}),
        ...(Platform.OS === 'android'
          ? { channelId: channelId || OFFER_SOUND_CHANNEL, color: '#F59E0B' }
          : {}),
      },
      trigger: null,
    });
  } catch {
    /* ignore */
  }
}

/** Kanalen moeten bestaan vóór een remote push, dus bij elke app-start. */
export async function prepareDriverNotificationChannels(): Promise<void> {
  await ensureSystemOfferChannel();
  await ensureSilentOfferChannel();
  await setupDriverNotificationChannel();
}

export async function setupDriverNotificationChannel(): Promise<void> {
  if (!Notifications || Platform.OS !== 'android') return;
  await ensureSystemOfferChannel();
  // Legacy defaultChannel uit app.json — zelfde alert.wav als de push.
  await Notifications.setNotificationChannelAsync('driver-offers', {
    name: t("Ritaanvragen"),
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 120, 60, 120, 60, 200],
    lightColor: '#EF4444',
    sound: rideAlertNotificationSound(),
    enableVibrate: true,
    lockscreenVisibility: Notifications.AndroidNotificationVisibility.PUBLIC,
  });
}

/** Rit-snapshot uit push/local melding — genoeg om de kaart te tonen vóór inbox. */
export type DriverOfferPushPreview = {
  offerId?: number | null;
  rideId?: number | null;
  pickupAddress?: string;
  pickupPlaceName?: string;
  dropoffAddress?: string;
  dropoffPlaceName?: string;
  pickupAt?: string;
  quotedPrice?: number | null;
  expiresAt?: string;
  title?: string;
  body?: string;
};

export type DriverNotificationOpenTarget = {
  screen: 'requests' | 'trips';
  offerId?: number | null;
  rideId?: number | null;
  preview?: DriverOfferPushPreview;
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

function stringField(payload: Record<string, unknown>, ...keys: string[]): string {
  for (const key of keys) {
    const raw = payload[key];
    if (raw == null) continue;
    const text = String(raw).trim();
    if (text) return text;
  }
  return '';
}

function numberField(payload: Record<string, unknown>, ...keys: string[]): number | null {
  for (const key of keys) {
    const n = Number(payload[key]);
    if (Number.isFinite(n)) return n;
  }
  return null;
}

export function offerPreviewFromNotificationData(
  data: Record<string, unknown> | undefined | null
): DriverOfferPushPreview | null {
  const payload = unwrapNotificationData(data);
  if (!payload) return null;
  const offerId = positiveId(payload.offer_id) ?? positiveId(payload.offerId);
  const rideId =
    positiveId(payload.ride_request_id) ??
    positiveId(payload.rideRequestId) ??
    positiveId(payload.ride_id) ??
    positiveId(payload.rideId);
  if (offerId == null && rideId == null) return null;
  return {
    offerId,
    rideId,
    pickupAddress: stringField(payload, 'pickup_address', 'pickupAddress') || undefined,
    pickupPlaceName: stringField(payload, 'pickup_place_name', 'pickupPlaceName') || undefined,
    dropoffAddress: stringField(payload, 'dropoff_address', 'dropoffAddress') || undefined,
    dropoffPlaceName: stringField(payload, 'dropoff_place_name', 'dropoffPlaceName') || undefined,
    pickupAt: stringField(payload, 'pickup_at', 'pickupAt') || undefined,
    quotedPrice: numberField(payload, 'quoted_price', 'quotedPrice'),
    expiresAt: stringField(payload, 'expires_at', 'expiresAt') || undefined,
  };
}

function targetFromNotificationData(data: Record<string, unknown> | undefined | null): DriverNotificationOpenTarget | null {
  const payload = unwrapNotificationData(data);
  if (!payload) return null;
  const type = String(payload.type || '');
  const preview = offerPreviewFromNotificationData(payload);
  const offerId = preview?.offerId ?? null;
  const rideId = preview?.rideId ?? null;
  if (type === 'driver_offer_closed') return null;
  if (type === 'driver_offer' || offerId != null) {
    return {
      screen: 'requests',
      offerId,
      rideId,
      preview: preview ?? undefined,
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

type ForegroundOfferListener = (offer: DriverOfferPushPreview & {
  closed?: boolean;
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
        const content = notification.request.content;
        const payload = unwrapNotificationData(
          content.data as Record<string, unknown> | undefined
        );
        if (String(payload?.type || '') === 'driver_offer_closed') {
          foregroundOfferListener?.({
            offerId: positiveId(payload?.offer_id) ?? positiveId(payload?.offerId),
            rideId:
              positiveId(payload?.ride_request_id) ??
              positiveId(payload?.rideRequestId) ??
              positiveId(payload?.ride_id),
            closed: true,
          });
          return;
        }
        const target = targetFromNotificationData(
          content.data as Record<string, unknown> | undefined
        );
        if (!target || target.screen !== 'requests') return;
        foregroundOfferListener?.({
          ...(target.preview || {
            offerId: target.offerId ?? null,
            rideId: target.rideId ?? null,
          }),
          title: String(content.title || ''),
          body: String(content.body || ''),
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
  onOpenRequests: (target: DriverNotificationOpenTarget) => void,
  onOpenTrips?: (rideId?: number | null) => void
): () => void {
  if (!Notifications) return () => undefined;
  const sub = Notifications.addNotificationResponseReceivedListener((response) => {
    try {
      const target = takeNotificationOpenTarget(response);
      if (!target) return;
      if (target.screen === 'requests') {
        onOpenRequests(target);
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
