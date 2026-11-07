import { t } from '../i18n';
import { AppState, Platform, Vibration } from 'react-native';
import {
  ensureIosNotificationSoundsInstalled,
  normalizeRideAlertTone,
  playRideAlertTone,
  rideAlertNotificationSound,
  type RideAlertTone,
} from '../audio/rideAlert';

/** Android-kanaal per ritgeluid — id bump als sound wijzigt (OS past kanaal niet meer aan). */
const OFFER_SOUND_CHANNEL_PREFIX = 'driver-offers-alert-v10';
const OFFER_SOUND_CHANNEL = `${OFFER_SOUND_CHANNEL_PREFIX}-chime`;
const OFFER_SILENT_CHANNEL = 'driver-offers-silent';

type NotificationsModule = typeof import('expo-notifications');

let Notifications: NotificationsModule | null = null;
let alertOnReceiveAttached = false;

function offerAlertWatch() {
  // Lazy: voorkomt circulaire import met offerAlertWatch → driverOffers.
  // eslint-disable-next-line @typescript-eslint/no-require-imports
  return require('./offerAlertWatch') as typeof import('./offerAlertWatch');
}

function buzzAndPlaySelectedTone(): void {
  try {
    if (Platform.OS === 'ios') Vibration.vibrate();
    else Vibration.vibrate([0, 120, 60, 120, 60, 200]);
  } catch {
    /* ignore */
  }
  try {
    playRideAlertTone();
  } catch {
    /* ignore */
  }
}

function offerIdFromNotificationData(data: Record<string, unknown> | undefined): number {
  if (!data || typeof data !== 'object') return 0;
  const raw = data.offer_id ?? data.offerId;
  const n = Number(raw);
  return Number.isFinite(n) && n > 0 ? n : 0;
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
  // Nieuwe ritaanvraag én geannuleerd: Bel. Geen tweede play als het id al klonk.
  if (!alertOnReceiveAttached) {
    alertOnReceiveAttached = true;
    Notifications.addNotificationReceivedListener((notification) => {
      try {
        const data = (notification.request.content.data || {}) as Record<string, unknown>;
        const type = String(data.type || '');
        if (type === 'driver_offer_closed') {
          // Lokale Bel-melding speelt al OS-sound; geen extra expo-audio.
          return;
        }
        if (type && type !== 'driver_offer') return;
        const offerId = offerIdFromNotificationData(data);
        const watch = offerAlertWatch();
        if (offerId > 0) {
          // Zichtbare banner (push of lokaal) → geen tweede lokale melding meer.
          watch.markDriverOfferBannerPresented([offerId]);
          if (watch.driverOfferAlreadySounded(offerId)) return;
          watch.playDriverOfferToneForIds([offerId]);
          return;
        }
        // Ook in background/inactive: iOS speelt APNs-sound soms stil (Silent/Focus).
        buzzAndPlaySelectedTone();
      } catch {
        /* never crash from push */
      }
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
      const granted = !!(
        current.granted ||
        current.ios?.status === Notifications!.IosAuthorizationStatus.PROVISIONAL
      );
      // Zonder allowsSound speelt APNs op slot/standby stil — push komt wel binnen.
      if (granted && Platform.OS === 'ios' && current.ios?.allowsSound === false) {
        return false;
      }
      return granted;
    })().catch(() => false);
  }
  return permissionReady;
}

/** true als iOS meldingen mag tonen maar geluid in Instellingen uit staat. */
export async function driverNotificationSoundBlocked(): Promise<boolean> {
  if (!Notifications || Platform.OS !== 'ios') return false;
  try {
    const current = await Notifications.getPermissionsAsync();
    const granted = !!(
      current.granted ||
      current.ios?.status === Notifications.IosAuthorizationStatus.PROVISIONAL
    );
    return granted && current.ios?.allowsSound === false;
  } catch {
    return false;
  }
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

async function ensureSystemOfferChannel(tone?: string | null): Promise<string> {
  const resolved = normalizeRideAlertTone(tone);
  const channelId = `${OFFER_SOUND_CHANNEL_PREFIX}-${resolved}`;
  if (!Notifications || Platform.OS !== 'android') return channelId;
  if (channelReady.has(channelId)) return channelId;
  await Notifications.setNotificationChannelAsync(channelId, {
    name: t("Ritaanvragen"),
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 120, 60, 120, 60, 200],
    lightColor: '#EF4444',
    sound: rideAlertNotificationSound(resolved),
    enableVibrate: true,
    lockscreenVisibility: Notifications.AndroidNotificationVisibility.PUBLIC,
    bypassDnd: false,
    audioAttributes: {
      usage: Notifications.AndroidAudioUsage.NOTIFICATION_EVENT,
      contentType: Notifications.AndroidAudioContentType.SONIFICATION,
      flags: {
        enforceAudibility: true,
        requestHardwareAudioVideoSynchronization: false,
      },
    },
  });
  channelReady.add(channelId);
  return channelId;
}

/**
 * Lokale melding-geluid.
 * - Android / voorgrond: Bel (chime.wav).
 * - iOS slot/achtergrond: 'default' — custom wav blijft tijdens wake vaak stil;
 *   Bel speelt dan via expo-audio (achtergrondtaak / armRideAlert).
 */
function offerNotificationSound(
  play: boolean,
  opts?: { tone?: string | null; systemSound?: boolean; lockReliable?: boolean }
): string | boolean {
  if (!play) return false;
  if (opts?.systemSound === true || opts?.lockReliable === true) {
    return 'default';
  }
  return rideAlertNotificationSound(opts?.tone);
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

  const screenActive = AppState.currentState === 'active';
  // Voorgrond: Bel via expo-audio. Slot: Bel ook proberen (stille schakelaar);
  // OS-melding gebruikt dan betrouwbaar 'default' als backup.
  if (playSound) {
    if (offerId && offerId > 0) offerAlertWatch().playDriverOfferToneForIds([offerId]);
    else buzzAndPlaySelectedTone();
  }

  if (!Notifications) return;
  // Zelfde aanbod al als banner gezien (push of eerdere lokale) → geen tweede melding.
  if (offerId && offerAlertWatch().driverOfferBannerAlreadyPresented(offerId)) {
    return;
  }
  const ok = await ensureDriverNotificationPermission();
  if (!ok) return;
  await ensureIosNotificationSoundsInstalled();

  if (offerId) offerAlertWatch().markDriverOfferBannerPresented([offerId]);

  const lockReliable = Platform.OS === 'ios' && !screenActive;
  const shown = await scheduleOfferNotification({
    title,
    body,
    sound: offerNotificationSound(playSound, {
      tone,
      systemSound: opts.systemSound === true,
      lockReliable,
    }),
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
    delayForLockSound: lockReliable,
  });
  if (shown && badge != null) await setDriverOfferBadge(badge);
}

async function scheduleOfferNotification(opts: {
  title: string;
  body: string;
  sound: string | boolean;
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
  /** iOS slot: korte delay zodat OS-geluid niet stilvalt midden in een wake. */
  delayForLockSound?: boolean;
}): Promise<boolean> {
  if (!Notifications) return false;
  const channelId =
    Platform.OS === 'android'
      ? opts.sound
        ? await ensureSystemOfferChannel(opts.tone)
        : await ensureSilentOfferChannel()
      : undefined;
  const sound =
    opts.sound === false
      ? false
      : typeof opts.sound === 'string'
        ? opts.sound
        : rideAlertNotificationSound(opts.tone);
  const content = {
    title: opts.title,
    body: opts.body,
    sound,
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
  const trigger =
    opts.delayForLockSound && Notifications
      ? {
          type: Notifications.SchedulableTriggerInputTypes.TIME_INTERVAL,
          seconds: 1,
          repeats: false as const,
        }
      : null;
  // Zelfde offerId → vervang i.p.v. stapelen (GPS-poll + inbox-poll).
  const identifier =
    opts.offerId && opts.offerId > 0 ? `driver-offer-${opts.offerId}` : undefined;
  try {
    await Notifications.scheduleNotificationAsync({
      ...(identifier ? { identifier } : {}),
      content: {
        ...content,
        ...(Platform.OS === 'ios' ? { interruptionLevel: 'timeSensitive' as const } : {}),
      },
      trigger,
    });
    return true;
  } catch {
    /* timeSensitive kan ontbreken op dit team; zonder dat niveau alsnog tonen */
  }
  try {
    await Notifications.scheduleNotificationAsync({
      ...(identifier ? { identifier } : {}),
      content,
      trigger,
    });
    return true;
  } catch {
    try {
      await Notifications.scheduleNotificationAsync({
        ...(identifier ? { identifier } : {}),
        content,
        trigger: null,
      });
      return true;
    } catch {
      return false;
    }
  }
}

const closedBannerIds = new Set<number>();

/**
 * “Rit geannuleerd” met Bel (chime) — iOS plant dit lokaal na data-only wake,
 * zodat niet de systeemtriangel van een remote push speelt.
 */
export async function notifyDriverOfferClosed(opts: {
  offerId?: number | null;
  rideRequestId?: number | null;
  pickupAddress?: string | null;
  pickupAt?: string | null;
  quotedPrice?: number | null;
  tone?: string | null;
}): Promise<void> {
  const offerId = Number(opts.offerId || 0) || 0;
  if (offerId > 0) {
    if (closedBannerIds.has(offerId)) return;
    closedBannerIds.add(offerId);
  }

  const pickup = String(opts.pickupAddress || '').trim();
  let when = '';
  const rawWhen = String(opts.pickupAt || '').trim();
  if (rawWhen) {
    const ms = Date.parse(rawWhen);
    if (Number.isFinite(ms)) {
      const d = new Date(ms);
      const dd = String(d.getDate()).padStart(2, '0');
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const hh = String(d.getHours()).padStart(2, '0');
      const mi = String(d.getMinutes()).padStart(2, '0');
      when = `${dd}-${mm} ${hh}:${mi}`;
    }
  }
  const price =
    opts.quotedPrice != null && Number.isFinite(Number(opts.quotedPrice))
      ? `€ ${Number(opts.quotedPrice).toFixed(2).replace('.', ',')}`
      : '';
  const shortPickup = pickup.includes(',')
    ? pickup.slice(0, pickup.indexOf(',')).trim()
    : pickup;
  const parts = [shortPickup || null, when || null, price || null].filter(Boolean);
  const body = parts.length ? parts.join(' · ') : t('De rit is geannuleerd.');
  const tone = normalizeRideAlertTone(opts.tone);

  if (!Notifications) return;
  const ok = await ensureDriverNotificationPermission();
  if (!ok) return;
  await ensureIosNotificationSoundsInstalled();

  const lockReliable = Platform.OS === 'ios' && AppState.currentState !== 'active';
  const sound = lockReliable ? 'default' : rideAlertNotificationSound(tone);
  const channelId =
    Platform.OS === 'android' ? await ensureSystemOfferChannel(tone) : undefined;
  const identifier = offerId > 0 ? `driver-offer-closed-${offerId}` : undefined;
  const content = {
    title: t('Rit geannuleerd'),
    body,
    sound,
    data: {
      type: 'driver_offer_closed',
      screen: 'requests',
      offer_id: offerId > 0 ? offerId : null,
      ride_request_id: Number(opts.rideRequestId || 0) || null,
      pickup_address: pickup || null,
      pickup_at: rawWhen || null,
      quoted_price: opts.quotedPrice ?? null,
    },
    ...(Platform.OS === 'android'
      ? { channelId: channelId || OFFER_SOUND_CHANNEL, color: '#EF4444' }
      : {}),
  };
  const trigger =
    lockReliable && Notifications
      ? {
          type: Notifications.SchedulableTriggerInputTypes.TIME_INTERVAL,
          seconds: 1,
          repeats: false as const,
        }
      : null;
  try {
    await Notifications.scheduleNotificationAsync({
      ...(identifier ? { identifier } : {}),
      content: {
        ...content,
        ...(Platform.OS === 'ios' ? { interruptionLevel: 'timeSensitive' as const } : {}),
      },
      trigger,
    });
  } catch {
    try {
      await Notifications.scheduleNotificationAsync({
        ...(identifier ? { identifier } : {}),
        content,
        trigger,
      });
    } catch {
      /* ignore */
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
          ? await ensureSystemOfferChannel(opts.tone)
          : await ensureSilentOfferChannel()
        : undefined;
    await Notifications.scheduleNotificationAsync({
      content: {
        title,
        body,
        sound: offerNotificationSound(playSound, { tone: opts.tone }),
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
  await ensureIosNotificationSoundsInstalled();
  await ensureSystemOfferChannel();
  await ensureSilentOfferChannel();
  await setupDriverNotificationChannel();
}

export async function setupDriverNotificationChannel(): Promise<void> {
  if (!Notifications || Platform.OS !== 'android') return;
  await ensureSystemOfferChannel();
  // Legacy defaultChannel uit app.json — Bel (chime).
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
  /** Tik op “Rit geannuleerd”: toon grijze kaart, niet als open aanbod. */
  closed?: boolean;
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
  if (type === 'driver_offer_closed') {
    // Tik op “Rit geannuleerd”: open Aanvragen zodat de grijze kaart zichtbaar is.
    return {
      screen: 'requests',
      offerId,
      rideId,
      preview: preview ?? undefined,
      closed: true,
    };
  }
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
          const preview = offerPreviewFromNotificationData(payload);
          foregroundOfferListener?.({
            offerId:
              preview?.offerId ??
              positiveId(payload?.offer_id) ??
              positiveId(payload?.offerId),
            rideId:
              preview?.rideId ??
              positiveId(payload?.ride_request_id) ??
              positiveId(payload?.rideRequestId) ??
              positiveId(payload?.ride_id),
            pickupAddress: preview?.pickupAddress,
            pickupPlaceName: preview?.pickupPlaceName,
            dropoffAddress: preview?.dropoffAddress,
            dropoffPlaceName: preview?.dropoffPlaceName,
            pickupAt: preview?.pickupAt,
            quotedPrice: preview?.quotedPrice,
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
      // Tik op melding: OS heeft al geluid afgespeeld — niet opnieuw.
      const oid = Number(target.offerId || target.preview?.offerId || 0);
      if (oid > 0) offerAlertWatch().rememberDriverOfferSounded([oid]);
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
    const oid = Number(target?.offerId || target?.preview?.offerId || 0);
    if (oid > 0) offerAlertWatch().rememberDriverOfferSounded([oid]);
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
