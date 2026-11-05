import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  Alert,
  AppState,
  FlatList,
  Image,
  Keyboard,
  Linking,
  Platform,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as DocumentPicker from 'expo-document-picker';
import * as ImagePicker from 'expo-image-picker';
import {
  acceptOffer,
  cancelAcceptedRide,
  declineOffer,
  DispatchOffer,
  DriverActiveRide,
  DriverCancelReason,
  DriverMedia,
  DriverVehicle,
  fetchDriverInbox,
  fetchDriverMe,
  fetchDriverReviews,
  fetchDriverVehicles,
  fetchDriverFleet,
  createDriverVehicle,
  updateDriverVehicle,
  deleteDriverVehicle,
  deleteDriverProfilePhoto,
  deleteDriverCompanyLogo,
  deleteDriverVehiclePhoto,
  DriverReview,
  fetchDriverPlanningWeek,
  inboxDataFromResponse,
  mergeCompletedRides,
  isMarketplaceRide,
  isCancelledOffer,
  cancelledOfferStillVisible,
  proposePickup,
  releaseAcceptedRide,
  setDriverOnline,
  updateDriverIban,
  markRideEnRoute,
  startRide,
  completeRide,
  fetchRideInvoice,
  isContractRide,
  isRidePaid,
  isRideInvoiceSent,
  rideRequiresPaymentBeforeComplete,
  updateDriverAccent,
  uploadDriverCompanyLogo,
  uploadDriverProfilePhoto,
  uploadDriverVehiclePhoto,
  updateDriverRideAlertTone,
  vehicleDisplayLabel,
  vehicleDisplayName,
} from '../api/driver';
import { ibanFieldError } from '../iban';
import {
  DEFAULT_RIDE_ALERT_TONE,
  RIDE_ALERT_OPTIONS,
  normalizeRideAlertTone,
  playRideAlertTone,
  setActiveRideAlertTone,
  type RideAlertTone,
} from '../audio/rideAlert';
import { fetchContractMe, userIsContractant } from '../api/contract';
import { ApiError, apiErrorStatus } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { DRIVER_BG } from '../debug/driverBgKillSwitch';
import { getApiBaseUrl, resolveApiBaseUrl, ColorPalette } from '../config';
import {
  clampGpsRefreshSeconds,
  publishDriverLocationForMap,
  readLastKnownDriverMapPosition,
  startDriverLocationTracking,
  stopDriverLocationTracking,
  updateDriverLocationTrackingOpts,
} from '../location/tracking';
import { defaultPickupDate, formatPickupAtPayload } from '../geo/route';
import {
  addDriverOfferNotificationResponseListener,
  consumeInitialDriverNotificationResponse,
  driverNotificationSoundBlocked,
  ensureDriverNotificationPermission,
  prepareDriverNotificationChannels,
  setForegroundDriverOfferListener,
  type DriverNotificationOpenTarget,
  type DriverOfferPushPreview,
} from '../notifications/driverOffers';
import { registerDriverPushToken, retryDriverPushTokenIfNeeded } from '../notifications/driverPush';
import {
  alertPickupDenials,
  alertUnseenDriverOffers,
  playDriverOfferToneForIds,
  primeRideAlertAudio,
  rememberRideAlertTone,
  clearDriverOfferBadge,
  takeFreshPayoutNotice,
} from '../notifications/offerAlertWatch';
import { alertNextPickupDeparture } from '../notifications/pickupDeparture';
import { ActiveRideBar } from '../ui/ActiveRideBar';
import { AppBrandLogo, APP_LOGO_BAR_MIN_HEIGHT } from '../ui/AppBrandLogo';
import { AppearanceControls } from '../ui/AppearanceControls';
import { DemoModeRow } from '../ui/DemoModeRow';
import { APP_PAGE_TOP_INSET } from '../ui/AppPageHeader';
import { AppModal } from '../ui/AppModal';
import { LimitedPhotoPickerModal, loadMediaLibrary, PickedLibraryImage } from '../ui/LimitedPhotoPickerModal';
import { useAndroidBackHandler } from '../ui/useAndroidBackHandler';
import { DutchPlateInput } from '../ui/DutchPlateInput';
import { KeyboardDismissAccessory } from '../ui/KeyboardDismissAccessory';
import { formatDutchPlate } from '../dutchPlate';
import { ImageCropModal } from '../ui/ImageCropModal';
import { Card, ErrorText, GhostButton, Screen } from '../ui/components';
import { DriverOfferCard, primeDriverCardExpand, driverOfferExpandKey } from '../ui/DriverOfferCard';
import { DriverPlanningPanel } from '../ui/DriverPlanningPanel';
import { ContractHomeScreen } from './ContractHomeScreen';
import { DriverEarningsPanel } from '../ui/DriverEarningsPanel';
import { ScreenHeader } from '../ui/ScreenHeader';
import { DriverTabBar, DriverTabKey } from '../ui/DriverTabBar';
import { DriverTripCard } from '../ui/DriverTripCard';
import { PickupAtField } from '../ui/PickupAtPicker';
import { DriverSettleRideModal } from '../ui/DriverSettleRideModal';
import { useI18n, t, dateLocale } from '../i18n';
import { ThemePreference, useTheme, useThemeColors } from '../theme/ThemeContext';
import {
  DRIVER_ACCENT_OPTIONS,
  DriverAccentProvider,
  driverAccentHex,
  hexAlpha,
  normalizeDriverAccent,
} from '../theme/driverAccent';
import AsyncStorage from '@react-native-async-storage/async-storage';

const DRIVER_TAB_KEY = 'nexa.driver.tab';
const DRIVER_ARCHIVE_KEY = 'nexa.driver.archive';
const DRIVER_ARCHIVED_RIDES_KEY = 'nexa.driver.archived_completed';
const DRIVER_ARCHIVE_SEEN_KEY = 'nexa.driver.archive.seen';
const DRIVER_ACCENT_KEY = 'nexa.driver.accent';
const DRIVER_RIDE_KIND_KEY = 'nexa.driver.ride_kind';
const DRIVER_RIDE_PERIOD_KEY = 'nexa.driver.ride_period';
const REVIEW_STAR = '#F5B301';

type RideKindFilter = 'all' | 'taxi' | 'contract';
const RIDE_KIND_OPTIONS: { key: RideKindFilter; label: string }[] = [
  { key: 'all', label: t("Alles") },
  { key: 'taxi', label: t("Normaal") },
  { key: 'contract', label: t("Contract") },
];

type RidePeriodFilter = 'today' | 'week';
const RIDE_PERIOD_OPTIONS: { key: RidePeriodFilter; label: string }[] = [
  { key: 'today', label: t("Dag") },
  { key: 'week', label: t("Week") },
];

function reviewDateLabel(iso?: string | null): string {
  if (!iso) return '';
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return '';
  return date.toLocaleDateString(dateLocale(), { day: 'numeric', month: 'short', year: 'numeric' });
}

function isRideKindFilter(value: string | null): value is RideKindFilter {
  return value === 'all' || value === 'taxi' || value === 'contract';
}

function isRidePeriodFilter(value: string | null): value is RidePeriodFilter {
  return value === 'today' || value === 'week';
}

function rideMatchesKindFilter(
  ride: DriverActiveRide | DispatchOffer['ride'] | null | undefined,
  kind: RideKindFilter
): boolean {
  if (kind === 'all') return true;
  if (!ride) return kind === 'taxi';
  const contract = isContractRide(ride);
  if (kind === 'contract') return contract;
  return !contract;
}

function rideIsoDay(ride: DriverActiveRide | null | undefined): string | null {
  if (!ride) return null;
  const raw = String(ride.pickup_at || '').trim();
  if (!raw) return null;
  const m = raw.match(/^(\d{4}-\d{2}-\d{2})/);
  if (m) return m[1];
  const d = new Date(raw);
  if (Number.isNaN(d.getTime())) return null;
  return toIsoDate(d);
}

function rideMatchesPeriodFilter(
  ride: DriverActiveRide | null | undefined,
  period: RidePeriodFilter,
  today: string,
  weekFrom: string,
  weekTo: string
): boolean {
  if (!ride) return true;
  const day = rideIsoDay(ride);
  if (!day) return true;
  if (period === 'today') return day === today;
  return day >= weekFrom && day <= weekTo;
}

/** Open marktplaatsrit waarvan het ophaalmoment voorbij is: altijd tonen, ook buiten Dag/Week. */
function isOutstandingMarketplaceRide(ride: DriverActiveRide | null | undefined): boolean {
  if (!ride || !isMarketplaceRide(ride)) return false;
  return ride.is_scheduled_overdue === true || ride.is_pickup_overdue === true;
}

function persistRideKindFilter(kind: RideKindFilter) {
  AsyncStorage.setItem(DRIVER_RIDE_KIND_KEY, kind).catch(() => undefined);
}

function persistRidePeriodFilter(period: RidePeriodFilter) {
  AsyncStorage.setItem(DRIVER_RIDE_PERIOD_KEY, period).catch(() => undefined);
}

/**
 * Alleen in-memory: tab blijft staan bij een remount in dezelfde sessie.
 * Na uitloggen / nieuwe login altijd weer Ritten (niet Profiel).
 */
let lastDriverTab: DriverTabKey = 'trips';

function persistDriverTab(key: DriverTabKey) {
  lastDriverTab = key;
}

function resetDriverTabToTrips() {
  lastDriverTab = 'trips';
  AsyncStorage.removeItem(DRIVER_TAB_KEY).catch(() => undefined);
}

function persistDriverArchive(open: boolean) {
  AsyncStorage.setItem(DRIVER_ARCHIVE_KEY, open ? '1' : '0').catch(() => undefined);
}

function persistDriverAccent(key: string) {
  AsyncStorage.setItem(DRIVER_ACCENT_KEY, key).catch(() => undefined);
}

function persistArchivedCompletedIds(ids: number[]) {
  AsyncStorage.setItem(DRIVER_ARCHIVED_RIDES_KEY, JSON.stringify(ids)).catch(() => undefined);
}

function persistArchiveSeenKeys(keys: string[]) {
  AsyncStorage.setItem(DRIVER_ARCHIVE_SEEN_KEY, JSON.stringify(keys)).catch(() => undefined);
}

const THEME_OPTIONS: { key: ThemePreference; label: string; hint: string }[] = [
  { key: 'system', label: t("Systeem"), hint: t("Volgt de telefooninstelling") },
  { key: 'light', label: t("Licht"), hint: t("Altijd lichte weergave") },
  { key: 'dark', label: t("Donker"), hint: t("Altijd donkere weergave") },
];

function accentHex(key?: string | null): string {
  return driverAccentHex(key);
}

function accountStatusLabel(active: boolean, onlineNow: boolean): string {
  if (!active) return 'Inactief';
  return onlineNow ? t("Actief · online") : t("Actief · offline");
}

function toIsoDate(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function mondayIsoFrom(date = new Date()): string {
  const x = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  const day = x.getDay();
  x.setDate(x.getDate() + (day === 0 ? -6 : 1 - day));
  return toIsoDate(x);
}

function addDaysIso(iso: string, days: number): string {
  const [y, m, d] = iso.split('-').map((n) => Number(n));
  const x = new Date(y, (m || 1) - 1, d || 1);
  x.setDate(x.getDate() + days);
  return toIsoDate(x);
}

function googleMapsDirUrl(address: string) {
  return `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(address)}`;
}

function mapsAddress(ride: DriverActiveRide, dest: 'pickup' | 'dropoff') {
  return String(
    dest === 'dropoff'
      ? ride.dropoff_address || ride.pickup_address
      : ride.pickup_address || ride.dropoff_address || ''
  ).trim();
}

function openMapsForRide(ride: DriverActiveRide, dest: 'pickup' | 'dropoff' = 'pickup') {
  const address = mapsAddress(ride, dest);
  if (!address) return;
  Linking.openURL(googleMapsDirUrl(address)).catch(() => undefined);
}

async function openPickupMapsAndNotify(
  token: string | null | undefined,
  ride: DriverActiveRide
) {
  if (token && ride?.id) {
    try {
      await markRideEnRoute(token, ride.id);
    } catch {
      /* navigatie toch openen */
    }
  }
  openMapsForRide(ride, 'pickup');
}

function resolveMediaUrl(url?: string | null): string | null {
  const raw = String(url || '').trim();
  if (!raw) return null;
  if (raw.startsWith('//')) return `https:${raw}`;
  const apiBase = getApiBaseUrl();
  if (raw.startsWith('/')) return `${apiBase}${raw}`;
  if (!/^https?:\/\//i.test(raw)) return `${apiBase}/${raw}`;
  try {
    const parsed = new URL(raw);
    if (parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1') {
      const base = new URL(apiBase);
      parsed.protocol = base.protocol;
      parsed.host = base.host;
      return parsed.toString();
    }
  } catch {
    /* absolute url laten staan */
  }
  return raw;
}

function personRangeForVehicleType(type: string): string {
  if (type === 'van') return '5-8';
  if (type === 'bus') return '9+';
  return '1-4';
}

function vehicleTypeForPersonRange(range: string): string {
  if (range === '5-8') return 'van';
  if (range === '9+') return 'bus';
  return 'car';
}

function offerMatchesRide(offer: DispatchOffer, rideId: number): boolean {
  return Number(offer.ride_request_id || offer.ride?.id || 0) === rideId;
}

function offerRideRequestId(offer: DispatchOffer): number {
  return Number(offer.ride_request_id || offer.ride?.id || 0);
}

/** Bouw een zichtbare aanvraagkaart uit push-data (vóór inbox-antwoord). */
function dispatchOfferFromPushPreview(preview: DriverOfferPushPreview): DispatchOffer | null {
  const oid = Number(preview.offerId || 0);
  if (oid <= 0) return null;
  const rid = Number(preview.rideId || 0);
  const pickup =
    String(preview.pickupAddress || '').trim() ||
    String(preview.body || '').trim() ||
    undefined;
  const quoted = preview.quotedPrice;
  return {
    id: oid,
    status: 'pending',
    ride_request_id: rid > 0 ? rid : undefined,
    expires_at: preview.expiresAt || undefined,
    ride: {
      id: rid > 0 ? rid : undefined,
      status: 'offered',
      pickup_address: pickup,
      pickup_place_name: preview.pickupPlaceName || null,
      dropoff_address: String(preview.dropoffAddress || '').trim() || undefined,
      dropoff_place_name: preview.dropoffPlaceName || null,
      pickup_at: preview.pickupAt || undefined,
      quoted_price: quoted != null && Number.isFinite(Number(quoted)) ? Number(quoted) : null,
    },
  };
}

/** Zelfde rit staat nog als open aanbod in de inbox: niet als geaccepteerde rit tonen. */
function rideListedAsOpenOffer(ride: DriverActiveRide, openOfferRideIds: Set<number>): boolean {
  const id = Number(ride.id || 0);
  if (id > 0 && openOfferRideIds.has(id)) return true;
  const status = String(ride.status || '').toLowerCase();
  return (
    status === 'offered' ||
    status === 'pending_dispatch' ||
    status === 'pending' ||
    status === 'pending_payment' ||
    status === 'searching'
  );
}

function newestDriverOffer(list: DispatchOffer[]): DispatchOffer | null {
  let best: DispatchOffer | null = null;
  for (const offer of list) {
    if (Number(offer.id) <= 0) continue;
    if (!best) {
      best = offer;
      continue;
    }
    const bestAt = Date.parse(best.offered_at || '') || 0;
    const at = Date.parse(offer.offered_at || '') || 0;
    if (at > bestAt || (at === bestAt && Number(offer.id) > Number(best.id))) {
      best = offer;
    }
  }
  return best;
}

function markOfferCancelled(offer: DispatchOffer, closedAt?: string | null): DispatchOffer {
  return {
    ...offer,
    status: 'superseded',
    ui_cancelled: true,
    closed_at: closedAt || offer.closed_at || new Date().toISOString(),
  };
}

function mergeCancelledOffers(current: DispatchOffer[], incoming: DispatchOffer[]): DispatchOffer[] {
  const byId = new Map<number, DispatchOffer>();
  const now = Date.now();
  for (const offer of [...current, ...incoming]) {
    const id = Number(offer.id);
    if (id <= 0) continue;
    const marked = markOfferCancelled(offer, offer.closed_at);
    if (!cancelledOfferStillVisible(marked, now)) continue;
    const prev = byId.get(id);
    if (!prev) {
      byId.set(id, marked);
      continue;
    }
    const prevAt = Date.parse(String(prev.closed_at || '')) || 0;
    const nextAt = Date.parse(String(marked.closed_at || '')) || 0;
    if (nextAt >= prevAt) byId.set(id, marked);
  }
  return [...byId.values()].sort((a, b) => {
    const aAt = Date.parse(String(a.closed_at || '')) || 0;
    const bAt = Date.parse(String(b.closed_at || '')) || 0;
    return bAt - aAt;
  });
}

function cancelFailureMessage(error: unknown): string {
  const status = apiErrorStatus(error);
  const raw = error instanceof ApiError ? error.message.trim() : '';
  if (status === 429 || /too many attempts/i.test(raw)) {
    return t('Te veel pogingen om deze rit te annuleren. Wacht even en probeer het opnieuw.');
  }
  if (raw && !/^HTTP \d+$/.test(raw)) return raw;
  return t('Annuleren mislukt.');
}

function availabilityFailureMessage(error: unknown): string {
  const status = apiErrorStatus(error);
  const raw = error instanceof ApiError ? error.message.trim() : '';
  if (status === 429 || /too many attempts/i.test(raw)) {
    return t('Te veel pogingen om online te gaan. Wacht even en probeer het opnieuw.');
  }
  if (raw && !/^HTTP \d+$/.test(raw)) return raw;
  return t('Online zetten mislukt.');
}

const VEHICLE_REQUIRED_ERROR = 'Kies eerst een voertuig voordat je online gaat.';
const VEHICLE_FORM_NAME_ACCESSORY_ID = 'vehicle-form-name-keyboard';
const VEHICLE_FORM_PLATE_ACCESSORY_ID = 'vehicle-form-plate-keyboard';

export function DriverHomeScreen() {
  const colors = useThemeColors();
  const { colorScheme, preference, setPreference } = useTheme();
  useI18n();
  const { driverToken, contractToken, logout } = useAuth();
  const [contractantDriver, setContractantDriver] = useState(false);
  const [tab, setTab] = useState<DriverTabKey>(() => lastDriverTab);
  const [showArchived, setShowArchived] = useState(false);
  useAndroidBackHandler(() => {
    if (!showArchived) return false;
    setShowArchived(false);
    persistDriverArchive(false);
    return true;
  });
  const [focusRideId, setFocusRideId] = useState<number | null>(null);
  const [focusOfferId, setFocusOfferId] = useState<number | null>(null);
  const [expandOfferId, setExpandOfferId] = useState<number | null>(null);
  const tripsScrollRef = useRef<ScrollView | null>(null);
  const rideOffsets = useRef<Record<number, number>>({});
  const [online, setOnline] = useState(false);
  const onlineRef = useRef(false);
  onlineRef.current = online;
  const onlineEpochRef = useRef(0);
  const refreshGen = useRef(0);
  const refreshInFlight = useRef(false);
  const inboxInFlight = useRef(false);
  const refreshQueued = useRef(false);
  const refreshFn = useRef<() => Promise<void>>(async () => undefined);
  // Epoch van een lopend online/offline-verzoek (null = geen). Zolang gezet is de tik leidend.
  const onlinePendingEpochRef = useRef<number | null>(null);
  const onlineToggleInFlightRef = useRef(false);
  const [gpsRefreshSeconds, setGpsRefreshSeconds] = useState(15);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [accountActive, setAccountActive] = useState(true);
  const [accent, setAccent] = useState<string>('orange');
  const [rideAlertTone, setRideAlertTone] = useState<RideAlertTone>(DEFAULT_RIDE_ALERT_TONE);
  const [company, setCompany] = useState('');
  const [payoutIbanMasked, setPayoutIbanMasked] = useState<string | null>(null);
  const [ibanDraft, setIbanDraft] = useState('');
  const [ibanEditing, setIbanEditing] = useState(false);
  const [ibanBusy, setIbanBusy] = useState(false);
  const [ibanError, setIbanError] = useState<string | null>(null);
  const [driverMedia, setDriverMedia] = useState<DriverMedia | null>(null);
  const [reviewsOpen, setReviewsOpen] = useState(false);
  const [reviews, setReviews] = useState<DriverReview[]>([]);
  const [reviewsBusy, setReviewsBusy] = useState(false);
  const [reviewsError, setReviewsError] = useState<string | null>(null);
  const [mediaBusy, setMediaBusy] = useState(false);
  const [limitedPickerOpen, setLimitedPickerOpen] = useState(false);
  const limitedPickResolve = useRef<((r: { uri: string; mime: string | null } | null) => void) | null>(null);
  const [cropUri, setCropUri] = useState<string | null>(null);
  const [cropMime, setCropMime] = useState<string | null>(null);
  const [cropKind, setCropKind] = useState<'profile' | 'logo' | 'vehicle' | 'vehicle-draft' | null>(null);
  const [photoPreview, setPhotoPreview] = useState<string | null>(null);
  const [vehiclePhotoZoom, setVehiclePhotoZoom] = useState(false);
  const [logoLight, setLogoLight] = useState<string | null>(null);
  const [logoDark, setLogoDark] = useState<string | null>(null);
  const [canFilterContractRides, setCanFilterContractRides] = useState(false);
  const [rideKindFilter, setRideKindFilter] = useState<RideKindFilter>('taxi');
  const [ridePeriodFilter, setRidePeriodFilter] = useState<RidePeriodFilter>('today');
  const [showEarnings, setShowEarnings] = useState(false);
  const [canViewMonthEarnings, setCanViewMonthEarnings] = useState(false);
  const [offers, setOffers] = useState<DispatchOffer[]>([]);
  const [cancelledOffers, setCancelledOffers] = useState<DispatchOffer[]>([]);
  const [archivedOffers, setArchivedOffers] = useState<DispatchOffer[]>([]);
  const [declinedOffers, setDeclinedOffers] = useState<DispatchOffer[]>([]);
  const [activeRide, setActiveRide] = useState<DriverActiveRide | null>(null);
  const [parkedRides, setParkedRides] = useState<DriverActiveRide[]>([]);
  const [scheduledRides, setScheduledRides] = useState<DriverActiveRide[]>([]);
  const [overdueScheduledRides, setOverdueScheduledRides] = useState<DriverActiveRide[]>([]);
  const [archivedOverdueScheduledRides, setArchivedOverdueScheduledRides] = useState<
    DriverActiveRide[]
  >([]);
  const [completedRides, setCompletedRides] = useState<DriverActiveRide[]>([]);
  const [archivedCompletedIds, setArchivedCompletedIds] = useState<number[]>([]);
  const [archiveSeenKeys, setArchiveSeenKeys] = useState<string[]>([]);
  const [cancelReasons, setCancelReasons] = useState<DriverCancelReason[]>([]);
  const [cancelRideId, setCancelRideId] = useState<number | null>(null);
  const [selectedCancelReason, setSelectedCancelReason] = useState<string | null>(null);
  const [cancelBusy, setCancelBusy] = useState(false);
  const cancelInFlightRef = useRef(false);
  const [proposeRideId, setProposeRideId] = useState<number | null>(null);
  const [rideNotice, setRideNotice] = useState<{
    title: string;
    body: string;
    primaryLabel: string;
    rideId?: number;
  } | null>(null);
  const [proposePickupAt, setProposePickupAt] = useState(() => defaultPickupDate(10));
  const [proposeBusy, setProposeBusy] = useState(false);
  const [settleRideId, setSettleRideId] = useState<number | null>(null);
  const [busyRideId, setBusyRideId] = useState<number | null>(null);
  const [pinRideId, setPinRideId] = useState<number | null>(null);
  const [pickupPin, setPickupPin] = useState('');
  const [pinFormError, setPinFormError] = useState<string | null>(null);
  const [flowMessage, setFlowMessage] = useState<string | null>(null);
  const [pinBusy, setPinBusy] = useState(false);
  const [vehicles, setVehicles] = useState<DriverVehicle[]>([]);
  const [selectedVehicleId, setSelectedVehicleId] = useState<number | null>(null);
  const [vehicleLocked, setVehicleLocked] = useState(false);
  const [assignedUntil, setAssignedUntil] = useState<string | null>(null);
  const [vehiclePickerOpen, setVehiclePickerOpen] = useState(false);
  const [fleet, setFleet] = useState<DriverVehicle[]>([]);
  const [vehicleFormOpen, setVehicleFormOpen] = useState(false);
  const [vehicleFormBusy, setVehicleFormBusy] = useState(false);
  const [vehicleFormError, setVehicleFormError] = useState<string | null>(null);
  const [vehicleConfirmDelete, setVehicleConfirmDelete] = useState(false);
  const [vehicleDraft, setVehicleDraft] = useState({
    id: null as number | null,
    name: '',
    type: 'car',
    license_plate: '',
    person_range: '1-4',
    photoUri: null as string | null,
    photoMime: null as string | null,
    existingPhoto: null as string | null,
  });
  const [vehiclePhotoAsk, setVehiclePhotoAsk] = useState<DriverVehicle | null>(null);
  const [focusVehiclePhoto, setFocusVehiclePhoto] = useState(false);
  const profileScrollRef = useRef<ScrollView>(null);
  const clearedPhotos = useRef({ profile: false, logo: false, vehicles: new Set<number>() });
  const vehiclePhotoRef = useRef<View>(null);
  const [error, setError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [busyId, setBusyId] = useState<number | null>(null);
  /** Aanvragen die deze sessie al naar het Aanvragen-tab zijn gebracht. */
  const surfacedOfferIdsRef = useRef<Set<number>>(new Set());
  /** Aanvragen die al in de lijst stonden; een melding zonder id opent alleen een nieuwe. */
  const knownOfferIdsRef = useRef<Set<number>>(new Set());
  const offersRef = useRef<DispatchOffer[]>([]);
  const pushPreviewRef = useRef<Map<number, DispatchOffer>>(new Map());
  const closedOfferIdsRef = useRef<Set<number>>(new Set());
  /** Eerste poll na een melding mag de gekozen rit niet overschrijven. */
  const notificationExpandLockRef = useRef(false);
  const pendingNotificationExpandRef = useRef<
    | null
    | { kind: 'offer'; offerId: number; rideId: number | null }
    | { kind: 'ride'; rideId: number }
    | { kind: 'newest'; baseline: Set<number> }
  >(null);
  offersRef.current = offers;
  const styles = useMemo(() => makeStyles(colors, accentHex(accent)), [colors, accent]);

  useEffect(() => {
    (async () => {
      try {
        const [
          savedArchive,
          savedAccent,
          savedArchivedRides,
          savedArchiveSeen,
          savedRideKind,
          savedRidePeriod,
        ] = await Promise.all([
          AsyncStorage.getItem(DRIVER_ARCHIVE_KEY),
          AsyncStorage.getItem(DRIVER_ACCENT_KEY),
          AsyncStorage.getItem(DRIVER_ARCHIVED_RIDES_KEY),
          AsyncStorage.getItem(DRIVER_ARCHIVE_SEEN_KEY),
          AsyncStorage.getItem(DRIVER_RIDE_KIND_KEY),
          AsyncStorage.getItem(DRIVER_RIDE_PERIOD_KEY),
        ]);
        AsyncStorage.removeItem(DRIVER_TAB_KEY).catch(() => undefined);
        if (savedArchive === '1') setShowArchived(true);
        if (savedAccent) setAccent(normalizeDriverAccent(savedAccent));
        if (isRideKindFilter(savedRideKind)) setRideKindFilter(savedRideKind);
        if (isRidePeriodFilter(savedRidePeriod)) setRidePeriodFilter(savedRidePeriod);
        if (savedArchivedRides) {
          try {
            const parsed = JSON.parse(savedArchivedRides);
            if (Array.isArray(parsed)) {
              setArchivedCompletedIds(
                parsed.map((id) => Number(id)).filter((id) => Number.isFinite(id) && id > 0)
              );
            }
          } catch {
            /* ignore */
          }
        }
        if (savedArchiveSeen) {
          try {
            const parsed = JSON.parse(savedArchiveSeen);
            if (Array.isArray(parsed)) {
              setArchiveSeenKeys(parsed.filter((key) => typeof key === 'string'));
            }
          } catch {
            /* ignore */
          }
        }
      } catch {
        /* ignore */
      }
    })();
  }, []);

  const logoUri =
    resolveMediaUrl(colorScheme === 'light' ? logoLight || logoDark : logoDark || logoLight) ||
    null;
  const selectedVehicle =
    vehicles.find((v) => v.id === selectedVehicleId) ||
    (selectedVehicleId ? ({ id: selectedVehicleId } as DriverVehicle) : null);
  const needsVehicle = !vehicleLocked && !selectedVehicleId;

  const handleNewOffers = useCallback((offerList: DispatchOffer[], isOnlineNow: boolean) => {
    const ids = offerList.map((offer) => Number(offer.id)).filter((id) => id > 0);
    const unseenOffers = offerList.filter((offer) => {
      const id = Number(offer.id);
      return id > 0 && !surfacedOfferIdsRef.current.has(id);
    });
    ids.forEach((id) => surfacedOfferIdsRef.current.add(id));
    const showNow = isOnlineNow || onlineRef.current;

    const locked = notificationExpandLockRef.current;
    if (locked) notificationExpandLockRef.current = false;
    const pending = pendingNotificationExpandRef.current;
    if (pending && (pending.kind === 'offer' || pending.kind === 'ride')) {
      setShowArchived(false);
      persistDriverArchive(false);
      setTab('requests');
      persistDriverTab('requests');
      return true;
    }
    if (!showNow) return false;
    if (locked) {
      setShowArchived(false);
      persistDriverArchive(false);
      setTab('requests');
      persistDriverTab('requests');
      return true;
    }
    const offerId = Number(newestDriverOffer(unseenOffers)?.id || 0);
    const openNow = showNow && !locked && offerId > 0 && unseenOffers.length > 0;
    if (openNow) {
      primeDriverCardExpand(driverOfferExpandKey(offerId));
      setFocusOfferId(offerId);
      setExpandOfferId(offerId);
      setShowArchived(false);
      persistDriverArchive(false);
      setTab('requests');
      persistDriverTab('requests');
    }
    // Geluid en SecureStore ná de paint. Niet in deze beurt: dat blokkeert de kaart.
    setTimeout(() => {
      void alertUnseenDriverOffers({ offers: offerList, online: showNow });
    }, 0);
    return openNow;
  }, []);

  const refresh = useCallback(async () => {
    if (!driverToken) return;
    if (refreshInFlight.current) {
      refreshQueued.current = true;
      return;
    }
    refreshInFlight.current = true;
    const gen = ++refreshGen.current;
    const refreshEpoch = onlineEpochRef.current;
    const toggleWasPending = onlinePendingEpochRef.current !== null;
    try {
      const [me, vehiclesRes, inbox, fleetRes] = await Promise.all([
        fetchDriverMe(driverToken),
        fetchDriverVehicles(driverToken),
        fetchDriverInbox(driverToken),
        fetchDriverFleet(driverToken),
      ]);
      if (gen !== refreshGen.current) return;
      setError(null);
      const isOnlineNow = !!me.user.is_online;
      // Negeer is_online van een refresh die vóór de tik startte, tijdens een lopend
      // availability-verzoek startte, of daar nog in liep (server kan nog de oude waarde hebben).
      if (
        onlineEpochRef.current === refreshEpoch &&
        !toggleWasPending &&
        onlinePendingEpochRef.current === null
      ) {
        setOnline(isOnlineNow);
      }
      // Alleen updaten bij échte wijziging — anders herstart GPS-effect en crasht native.
      setGpsRefreshSeconds((prev) => {
        const next = clampGpsRefreshSeconds(me.meta?.gps_refresh_seconds ?? 10);
        return prev === next ? prev : next;
      });
      setName(me.user.name);
      setEmail(me.user.email || '');
      setPhone(me.user.phone || '');
      setAccountActive(me.user.is_account_active !== false);
      const nextAccent = normalizeDriverAccent(me.user.pwa_accent);
      setAccent(nextAccent);
      persistDriverAccent(nextAccent);
      const rawTone = String(me.user.ride_alert_tone || '').trim().toLowerCase();
      // Oude standaarden (alert/classic) → Bel (nieuwe standaard).
      const tone =
        rawTone === 'alert' || rawTone === 'classic' || rawTone === ''
          ? DEFAULT_RIDE_ALERT_TONE
          : normalizeRideAlertTone(rawTone);
      setRideAlertTone(tone);
      setActiveRideAlertTone(tone);
      rememberRideAlertTone(tone);
      if ((rawTone === 'alert' || rawTone === 'classic' || rawTone === '') && driverToken) {
        void updateDriverRideAlertTone(driverToken, tone).catch(() => undefined);
      }
      setCompany(me.user.company_name || '');
      setPayoutIbanMasked(me.user.payout_iban_masked || null);
      setLogoLight(me.user.company_logo_url || null);
      setLogoDark(me.user.company_logo_dark_url || null);
      const media = me.user.media || null;
      setDriverMedia(
        media
          ? {
              ...media,
              profile_photo_url: clearedPhotos.current.profile ? null : media.profile_photo_url,
              has_profile_photo: clearedPhotos.current.profile ? false : media.has_profile_photo,
              company_logo_url: clearedPhotos.current.logo ? null : media.company_logo_url,
            }
          : null
      );
      setCanFilterContractRides(!!me.user.can_handle_contract_rides);
      const canSeeEarnings = !!me.permissions?.earnings_view;
      setShowEarnings(canSeeEarnings);
      setCanViewMonthEarnings(!!me.permissions?.earnings_view_month);
      setTab((current) => {
        const next = current === 'earnings' && !canSeeEarnings ? 'trips' : current;
        if (next !== current) persistDriverTab(next);
        return next;
      });

      const locked = !!vehiclesRes.locked;
      const list = Array.isArray(vehiclesRes.data) ? vehiclesRes.data : [];
      const withoutClearedPhoto = (vehicle: DriverVehicle) =>
        clearedPhotos.current.vehicles.has(vehicle.id) ? { ...vehicle, photo_url: null } : vehicle;
      // Altijd server-waarden (kenteken/naam/type) — alleen foto mag lokaal blijven
      // tot de upload-URL binnen is, anders blijft een oud admin-kenteken zichtbaar.
      setVehicles((previous) =>
        list.map((vehicle) => {
          const prior = previous.find((item) => item.id === vehicle.id);
          const next = withoutClearedPhoto(vehicle);
          return {
            ...next,
            license_plate: next.license_plate ?? null,
            name: next.name,
            type: next.type,
            type_label: next.type_label,
            person_range: next.person_range,
            person_range_label: next.person_range_label,
            photo_url: clearedPhotos.current.vehicles.has(vehicle.id)
              ? null
              : next.photo_url || prior?.photo_url || null,
          };
        })
      );
      setFleet(
        (Array.isArray(fleetRes.data) ? fleetRes.data : []).map((vehicle) => withoutClearedPhoto(vehicle))
      );
      setVehicleLocked(locked);
      setAssignedUntil(vehiclesRes.assigned_until || null);
      let resolvedVehicleId: number | null = selectedVehicleId;
      if (locked && vehiclesRes.assigned_vehicle?.id) {
        resolvedVehicleId = Number(vehiclesRes.assigned_vehicle.id);
      } else {
        const fromMe = me.user.vehicle_id ? Number(me.user.vehicle_id) : null;
        if (selectedVehicleId && list.some((v) => v.id === selectedVehicleId)) {
          resolvedVehicleId = selectedVehicleId;
        } else if (fromMe && list.some((v) => v.id === fromMe)) {
          resolvedVehicleId = fromMe;
        } else if (list.length === 1) {
          resolvedVehicleId = list[0].id;
        } else {
          resolvedVehicleId = fromMe;
        }
      }
      setSelectedVehicleId(resolvedVehicleId);

      const inboxData = inboxDataFromResponse(inbox);
      const incomingIds = new Set(inboxData.offers.map((offer) => Number(offer.id)));
      for (const id of [...closedOfferIdsRef.current]) {
        if (!incomingIds.has(id)) closedOfferIdsRef.current.delete(id);
      }
      const liveOffers = inboxData.offers.filter(
        (offer) => !closedOfferIdsRef.current.has(Number(offer.id))
      );
      const openedRequest = handleNewOffers(liveOffers, isOnlineNow);
      for (const id of [...pushPreviewRef.current.keys()]) {
        if (incomingIds.has(id) || closedOfferIdsRef.current.has(id)) {
          pushPreviewRef.current.delete(id);
        }
      }
      const previews = [...pushPreviewRef.current.values()].filter(
        (offer) => !incomingIds.has(Number(offer.id)) && !closedOfferIdsRef.current.has(Number(offer.id))
      );
      setOffers(previews.length ? [...previews, ...liveOffers] : liveOffers);
      setArchivedOffers(inboxData.archivedOffers);
      setDeclinedOffers(inboxData.declinedOffers);
      setCancelledOffers((current) => mergeCancelledOffers(current, inboxData.cancelledOffers));
      setActiveRide(inboxData.activeRide);
      setParkedRides(inboxData.parkedAssignedRides);
      setScheduledRides(inboxData.scheduledRides);
      setOverdueScheduledRides(inboxData.overdueScheduledRides);
      setArchivedOverdueScheduledRides(inboxData.archivedOverdueScheduledRides);
      setCancelReasons(inboxData.cancelReasons);
      // Planning en meldingen mogen de volgende inbox niet tegenhouden.
      // Een trage SecureStore- of notificatie-aanroep hield de poll minuten vast,
      // terwijl de rit al in het antwoord zat.
      const noticeGen = gen;
      const keepRequests = openedRequest || pendingNotificationExpandRef.current != null;
      void (async () => {
        let completedFromPlanning: Array<{ id: number; status?: string }> = [];
        try {
          const monday = mondayIsoFrom();
          const [thisWeek, lastWeek] = await Promise.all([
            fetchDriverPlanningWeek(driverToken, monday, resolvedVehicleId),
            fetchDriverPlanningWeek(driverToken, addDaysIso(monday, -7), resolvedVehicleId),
          ]);
          completedFromPlanning = [
            ...(thisWeek?.data?.days || []).flatMap((day) => day.rides || []),
            ...(lastWeek?.data?.days || []).flatMap((day) => day.rides || []),
          ];
        } catch {
          completedFromPlanning = [];
        }
        if (noticeGen !== refreshGen.current) return;
        setCompletedRides(
          mergeCompletedRides(inboxData.completedRides, completedFromPlanning).slice(0, 40)
        );
        const pickupDenied = await alertPickupDenials({
          rides: [inboxData.activeRide, ...inboxData.parkedAssignedRides],
          alert: inboxData.pickupDeniedAlert,
        });
        if (noticeGen !== refreshGen.current) return;
        let showedPayout = false;
        if (pickupDenied) {
          setRideNotice({
            title: pickupDenied.title,
            body: pickupDenied.body,
            primaryLabel: t("Begrepen"),
            rideId: pickupDenied.rideId,
          });
          if (!keepRequests) {
            setTab('trips');
            persistDriverTab('trips');
          }
        } else {
          const payout = await takeFreshPayoutNotice(inboxData.payoutNotices);
          if (noticeGen !== refreshGen.current) return;
          if (payout) {
            showedPayout = true;
            setRideNotice({
              title: payout.title,
              body: payout.body,
              primaryLabel: t("Begrepen"),
              rideId: payout.rideId,
            });
          }
        }
        if (!pickupDenied && !keepRequests && !showedPayout) {
          try {
            const departure = await alertNextPickupDeparture({
              scheduled: inboxData.scheduledRides,
              activeRide: inboxData.activeRide,
            });
            if (noticeGen !== refreshGen.current) return;
            if (departure) {
              setRideNotice({
                title: departure.title,
                body: departure.body,
                primaryLabel: t("Begrepen"),
                rideId: departure.rideId,
              });
              setTab('trips');
              persistDriverTab('trips');
            }
          } catch {
            /* vertrekmelding mag de inbox niet breken */
          }
        }
      })();
    } catch (e) {
      if (gen !== refreshGen.current) return;
      const status = apiErrorStatus(e);
      const raw = e instanceof ApiError ? e.message : '';
      // Geen retry en geen Engelse 429 op het scherm: de interval pollt vanzelf opnieuw.
      if (status === 429 || /too many attempts/i.test(raw)) return;
      setError(raw || t("Kon gegevens niet laden."));
    } finally {
      refreshInFlight.current = false;
      if (refreshQueued.current) {
        refreshQueued.current = false;
        setTimeout(() => {
          void refreshFn.current();
        }, 0);
      }
    }
  }, [driverToken, handleNewOffers, selectedVehicleId]);
  refreshFn.current = refresh;

  /** Alleen inbox — eigen lock, mag nooit door zware refresh geblokkeerd worden (presence/last_seen). */
  const refreshInboxOnly = useCallback(async () => {
    if (!driverToken || inboxInFlight.current) return;
    inboxInFlight.current = true;
    try {
      const inbox = await fetchDriverInbox(driverToken);
      const inboxData = inboxDataFromResponse(inbox);
      const incomingIds = new Set(inboxData.offers.map((offer) => Number(offer.id)));
      for (const id of [...closedOfferIdsRef.current]) {
        if (!incomingIds.has(id)) closedOfferIdsRef.current.delete(id);
      }
      const liveOffers = inboxData.offers.filter(
        (offer) => !closedOfferIdsRef.current.has(Number(offer.id))
      );
      handleNewOffers(liveOffers, onlineRef.current);
      for (const id of [...pushPreviewRef.current.keys()]) {
        if (incomingIds.has(id) || closedOfferIdsRef.current.has(id)) {
          pushPreviewRef.current.delete(id);
        }
      }
      const previews = [...pushPreviewRef.current.values()].filter(
        (offer) => !incomingIds.has(Number(offer.id)) && !closedOfferIdsRef.current.has(Number(offer.id))
      );
      setOffers(previews.length ? [...previews, ...liveOffers] : liveOffers);
      setArchivedOffers(inboxData.archivedOffers);
      setDeclinedOffers(inboxData.declinedOffers);
      setCancelledOffers((current) => mergeCancelledOffers(current, inboxData.cancelledOffers));
      setActiveRide(inboxData.activeRide);
      setParkedRides(inboxData.parkedAssignedRides);
      setScheduledRides(inboxData.scheduledRides);
      setOverdueScheduledRides(inboxData.overdueScheduledRides);
      setArchivedOverdueScheduledRides(inboxData.archivedOverdueScheduledRides);
      setCancelReasons(inboxData.cancelReasons);
      setCompletedRides((prev) =>
        mergeCompletedRides(inboxData.completedRides, prev).slice(0, 40)
      );
    } catch {
      /* lichte poll: stil falen, volgende tick opnieuw */
    } finally {
      inboxInFlight.current = false;
    }
  }, [driverToken, handleNewOffers]);

  useEffect(() => {
    if (!contractToken) {
      setContractantDriver(false);
      return;
    }
    let cancelled = false;
    fetchContractMe(contractToken)
      .then((me) => {
        if (!cancelled) setContractantDriver(userIsContractant(me.user));
      })
      .catch(() => {
        if (!cancelled) setContractantDriver(false);
      });
    return () => {
      cancelled = true;
    };
  }, [contractToken]);

  // Crash-isolatie: forceer alle periodieke achtergrondwerk uit bij boot.
  useEffect(() => {
    if (!DRIVER_BG.locationTracking) {
      void stopDriverLocationTracking();
    }
  }, []);

  useEffect(() => {
    if (!DRIVER_BG.pushRegister) return;
    prepareDriverNotificationChannels().catch(() => undefined);
    ensureDriverNotificationPermission()
      .then(async (ok) => {
        if (ok || Platform.OS !== 'ios') return;
        const soundOff = await driverNotificationSoundBlocked();
        if (!soundOff) return;
        Alert.alert(
          t('Meldingsgeluid uit'),
          t(
            'iOS mag Nexa Taxi tonen, maar geluid staat uit. Zet Sounds aan in Instellingen, anders hoor je op slot/standby niets bij een nieuwe rit.'
          ),
          [
            { text: t('Later'), style: 'cancel' },
            {
              text: t('Open Instellingen'),
              onPress: () => {
                Linking.openSettings().catch(() => undefined);
              },
            },
          ]
        );
      })
      .catch(() => undefined);
    if (driverToken) {
      registerDriverPushToken(driverToken).catch(() => undefined);
    }
  }, [driverToken]);

  // Inbox en full refresh — kill-switch voor crash-isolatie (driverBgKillSwitch).
  useEffect(() => {
    // Eén keer laden zodat het scherm data heeft; daarna alleen poll als enabled.
    refresh().catch(() => undefined);
    if (!DRIVER_BG.inboxPoll && !DRIVER_BG.fullRefreshPoll) return;
    const inboxMs = online ? 15000 : 20000;
    const fullMs = online ? 60000 : 60000;
    let ticks = 0;
    const t = setInterval(() => {
      ticks += 1;
      const doFull =
        DRIVER_BG.fullRefreshPoll &&
        ticks % Math.max(1, Math.round(fullMs / inboxMs)) === 0;
      if (doFull) {
        void refresh();
      } else if (DRIVER_BG.inboxPoll) {
        void refreshInboxOnly();
      }
    }, inboxMs);
    return () => clearInterval(t);
  }, [refresh, refreshInboxOnly, online]);

  useEffect(() => {
    if (!DRIVER_BG.appStateRefresh && !DRIVER_BG.pushRegister) return;
    let lastAppRefresh = 0;
    const sub = AppState.addEventListener('change', (state) => {
      if (state !== 'active' && state !== 'background') return;
      const now = Date.now();
      if (now - lastAppRefresh < 4000) return;
      lastAppRefresh = now;
      if (state === 'active' && DRIVER_BG.pushRegister) {
        retryDriverPushTokenIfNeeded();
        clearDriverOfferBadge().catch(() => undefined);
      }
      if (DRIVER_BG.appStateRefresh) {
        refresh().catch(() => undefined);
      }
    });
    return () => sub.remove();
  }, [refresh]);

  const mountedRef = useRef(true);
  useEffect(() => {
    mountedRef.current = true;
    return () => {
      mountedRef.current = false;
    };
  }, []);

  const revealNotifiedOffer = useCallback((offerId: number) => {
    const id = Number(offerId);
    if (id <= 0 || !mountedRef.current) return;
    try {
      primeDriverCardExpand(driverOfferExpandKey(id));
      setFocusOfferId(id);
      setExpandOfferId(id);
    } catch {
      /* ignore */
    }
  }, []);

  const applyPushOfferPreview = useCallback(
    (preview: DriverOfferPushPreview | null | undefined) => {
      if (!preview || !mountedRef.current) return 0;
      const built = dispatchOfferFromPushPreview(preview);
      if (!built) return 0;
      const oid = Number(built.id);
      if (closedOfferIdsRef.current.has(oid)) return 0;
      const existing = offersRef.current.find((offer) => Number(offer.id) === oid);
      if (existing?.ride?.pickup_address) {
        revealNotifiedOffer(oid);
        return oid;
      }
      pushPreviewRef.current.set(oid, built);
      setOffers((current) => {
        const idx = current.findIndex((offer) => Number(offer.id) === oid);
        if (idx < 0) return [built, ...current];
        const prior = current[idx];
        const merged: DispatchOffer = {
          ...built,
          ...prior,
          ride: { ...(built.ride || {}), ...(prior.ride || {}) },
        };
        const next = current.slice();
        next[idx] = merged;
        pushPreviewRef.current.set(oid, merged);
        return next;
      });
      revealNotifiedOffer(oid);
      return oid;
    },
    [revealNotifiedOffer]
  );

  /** Inbox direct ophalen tot het aanbod erin zit — melding ⇒ rit moet zichtbaar zijn. */
  const hydrateOfferFromInbox = useCallback(
    async (offerId: number, rideId: number) => {
      const token = driverToken;
      if (!token) return;
      const delays = [0, 200, 500, 1000, 1800];
      for (let i = 0; i < delays.length; i += 1) {
        if (delays[i] > 0) {
          await new Promise((resolve) => setTimeout(resolve, delays[i]));
        }
        if (!mountedRef.current) return;
        try {
          const inbox = await fetchDriverInbox(token);
          if (!mountedRef.current) return;
          const inboxData = inboxDataFromResponse(inbox);
          const incomingIds = new Set(inboxData.offers.map((offer) => Number(offer.id)));
          for (const id of [...closedOfferIdsRef.current]) {
            if (!incomingIds.has(id)) closedOfferIdsRef.current.delete(id);
          }
          const liveOffers = inboxData.offers.filter(
            (offer) => !closedOfferIdsRef.current.has(Number(offer.id))
          );
          handleNewOffers(liveOffers, onlineRef.current);
          for (const id of [...pushPreviewRef.current.keys()]) {
            if (incomingIds.has(id) || closedOfferIdsRef.current.has(id)) {
              pushPreviewRef.current.delete(id);
            }
          }
          const previews = [...pushPreviewRef.current.values()].filter(
            (offer) =>
              !incomingIds.has(Number(offer.id)) && !closedOfferIdsRef.current.has(Number(offer.id))
          );
          setOffers(previews.length ? [...previews, ...liveOffers] : liveOffers);
          setArchivedOffers(inboxData.archivedOffers);
          setDeclinedOffers(inboxData.declinedOffers);
          setCancelledOffers((current) => mergeCancelledOffers(current, inboxData.cancelledOffers));
          setActiveRide(inboxData.activeRide);
          setParkedRides(inboxData.parkedAssignedRides);
          setScheduledRides(inboxData.scheduledRides);
          setOverdueScheduledRides(inboxData.overdueScheduledRides);
          setArchivedOverdueScheduledRides(inboxData.archivedOverdueScheduledRides);
          setCancelReasons(inboxData.cancelReasons);
          const match = liveOffers.find(
            (offer) =>
              (offerId > 0 && Number(offer.id) === offerId) ||
              (rideId > 0 && offerMatchesRide(offer, rideId))
          );
          if (match) {
            pendingNotificationExpandRef.current = null;
            revealNotifiedOffer(Number(match.id));
            return;
          }
        } catch {
          /* volgende poging */
        }
      }
    },
    [driverToken, handleNewOffers, revealNotifiedOffer]
  );

  const openOfferFromNotification = useCallback(
    (target: DriverNotificationOpenTarget | { offerId?: number | null; rideId?: number | null }) => {
      const preview =
        'preview' in target && target.preview
          ? target.preview
          : {
              offerId: target.offerId ?? null,
              rideId: target.rideId ?? null,
            };
      const oid = Number(preview.offerId || target.offerId || 0) || 0;
      const rid = Number(preview.rideId || target.rideId || 0) || 0;
      const fromClosed = 'closed' in target && target.closed === true;
      const run = () => {
        if (!mountedRef.current) return;
        try {
          setShowArchived(false);
          persistDriverArchive(false);
          setTab('requests');
          persistDriverTab('requests');
          if (fromClosed) {
            // Geannuleerde rit: grijs in Aanvragen, niet als open aanbod uitklappen.
            if (oid > 0) closedOfferIdsRef.current.add(oid);
            const closedAt = new Date().toISOString();
            const existing = offersRef.current.find(
              (offer) =>
                (oid > 0 && Number(offer.id) === oid) || (rid > 0 && offerMatchesRide(offer, rid))
            );
            if (existing) {
              setOffers((current) =>
                current.filter(
                  (offer) =>
                    !(oid > 0 && Number(offer.id) === oid) &&
                    !(rid > 0 && offerMatchesRide(offer, rid))
                )
              );
              setCancelledOffers((prev) =>
                mergeCancelledOffers(prev, [markOfferCancelled(existing, closedAt)])
              );
            } else if (oid > 0 || rid > 0) {
              setCancelledOffers((prev) =>
                mergeCancelledOffers(prev, [
                  markOfferCancelled(
                    {
                      id: oid > 0 ? oid : rid,
                      ride_request_id: rid > 0 ? rid : undefined,
                      status: 'superseded',
                      closed_at: closedAt,
                      ui_cancelled: true,
                      ride: {
                        id: rid > 0 ? rid : undefined,
                        pickup_address: String(preview.pickupAddress || ''),
                        pickup_place_name: String(preview.pickupPlaceName || '') || null,
                        dropoff_address: String(preview.dropoffAddress || ''),
                        dropoff_place_name: String(preview.dropoffPlaceName || '') || null,
                        pickup_at: preview.pickupAt || undefined,
                        quoted_price:
                          preview.quotedPrice != null && Number.isFinite(Number(preview.quotedPrice))
                            ? Number(preview.quotedPrice)
                            : null,
                        status: 'cancelled',
                      },
                    },
                    closedAt
                  ),
                ])
              );
            }
            void hydrateOfferFromInbox(oid, rid);
            return;
          }
          notificationExpandLockRef.current = true;
          if (oid > 0 || rid > 0) {
            const applied = applyPushOfferPreview({ ...preview, offerId: oid || preview.offerId });
            if (applied > 0) {
              pendingNotificationExpandRef.current = null;
            } else {
              pendingNotificationExpandRef.current =
                oid > 0
                  ? { kind: 'offer', offerId: oid, rideId: rid > 0 ? rid : null }
                  : { kind: 'ride', rideId: rid };
              const match = offersRef.current.find(
                (offer) =>
                  (oid > 0 && Number(offer.id) === oid) || (rid > 0 && offerMatchesRide(offer, rid))
              );
              if (match) {
                pendingNotificationExpandRef.current = null;
                revealNotifiedOffer(Number(match.id));
              }
            }
            // Directe dispatch: inbox nu ophalen (met retries), niet wachten op de poll.
            void hydrateOfferFromInbox(oid, rid);
            return;
          }
          const baseline = new Set(knownOfferIdsRef.current);
          const list = offersRef.current;
          const arrived = list.filter((offer) => {
            const id = Number(offer.id);
            return id > 0 && !baseline.has(id);
          });
          if (!list.length) {
            pendingNotificationExpandRef.current = { kind: 'newest', baseline };
            void hydrateOfferFromInbox(0, 0);
            return;
          }
          pendingNotificationExpandRef.current = null;
          const pick = newestDriverOffer(arrived.length ? arrived : list);
          if (pick) revealNotifiedOffer(Number(pick.id));
        } catch {
          /* deep-link mag de app niet laten crashen */
        }
      };
      // Direct — geen 250ms-vertraging: melding ⇒ rit moet er al staan.
      run();
    },
    [applyPushOfferPreview, hydrateOfferFromInbox, revealNotifiedOffer]
  );

  useEffect(() => {
    const pending = pendingNotificationExpandRef.current;
    if (pending?.kind === 'offer' || pending?.kind === 'ride') {
      const match = offers.find((offer) => {
        if (pending.kind === 'offer' && Number(offer.id) === pending.offerId) return true;
        const rideId = pending.kind === 'offer' ? pending.rideId : pending.rideId;
        return rideId != null && rideId > 0 && offerMatchesRide(offer, rideId);
      });
      if (match) {
        pendingNotificationExpandRef.current = null;
        setShowArchived(false);
        setTab('requests');
        persistDriverTab('requests');
        revealNotifiedOffer(Number(match.id));
      }
    } else if (pending?.kind === 'newest' && offers.length) {
      const arrived = offers.filter((offer) => {
        const id = Number(offer.id);
        return id > 0 && !pending.baseline.has(id);
      });
      const pick = newestDriverOffer(arrived.length ? arrived : offers);
      pendingNotificationExpandRef.current = null;
      if (pick) revealNotifiedOffer(Number(pick.id));
    }
    if (offers.length) {
      const next = new Set(knownOfferIdsRef.current);
      for (const offer of offers) {
        const id = Number(offer.id);
        if (id > 0) next.add(id);
      }
      knownOfferIdsRef.current = next;
    }
  }, [offers, revealNotifiedOffer]);

  useEffect(() => {
    setForegroundDriverOfferListener((payload) => {
      if (payload.closed) {
        const id = Number(payload.offerId || 0);
        const rid = Number(payload.rideId || 0);
        if (id > 0) closedOfferIdsRef.current.add(id);
        pushPreviewRef.current.delete(id);
        const closedAt = new Date().toISOString();
        const currentOffers = offersRef.current;
        const kept: DispatchOffer[] = [];
        const moved: DispatchOffer[] = [];
        for (const offer of currentOffers) {
          const offerIdNum = Number(offer.id);
          const offerRideId = Number(offer.ride_request_id || offer.ride?.id || 0);
          const match =
            (id > 0 && offerIdNum === id) || (rid > 0 && offerRideId === rid);
          if (match) moved.push(markOfferCancelled(offer, closedAt));
          else kept.push(offer);
        }
        setOffers(kept);
        if (moved.length) {
          setCancelledOffers((prev) => mergeCancelledOffers(prev, moved));
        } else if (id > 0 || rid > 0) {
          // Push kwam binnen vóór de lokale kaart: stub met adres uit de melding.
          setCancelledOffers((prev) =>
            mergeCancelledOffers(prev, [
              markOfferCancelled(
                {
                  id: id > 0 ? id : rid,
                  ride_request_id: rid > 0 ? rid : undefined,
                  status: 'superseded',
                  closed_at: closedAt,
                  ui_cancelled: true,
                  ride: {
                    id: rid > 0 ? rid : undefined,
                    pickup_address: String(payload.pickupAddress || ''),
                    pickup_place_name: String(payload.pickupPlaceName || '') || null,
                    dropoff_address: String(payload.dropoffAddress || ''),
                    dropoff_place_name: String(payload.dropoffPlaceName || '') || null,
                    pickup_at: payload.pickupAt || undefined,
                    quoted_price:
                      payload.quotedPrice != null && Number.isFinite(Number(payload.quotedPrice))
                        ? Number(payload.quotedPrice)
                        : null,
                    status: 'cancelled',
                  },
                },
                closedAt
              ),
            ])
          );
        }
        if (id > 0) {
          setFocusOfferId((current) => (current === id ? null : current));
          setExpandOfferId((current) => (current === id ? null : current));
        }
        setTab('requests');
        persistDriverTab('requests');
        return;
      }
      try {
        const id = Number(payload.offerId || 0);
        const rid = Number(payload.rideId || 0);
        if (id > 0 && closedOfferIdsRef.current.has(id)) return;
        // Sluimer/achtergrond: toch toon + hydrate (push content-available wekt JS kort).
        if (AppState.currentState !== 'active') {
          if (id > 0) {
            applyPushOfferPreview(payload);
            playDriverOfferToneForIds([id]);
          }
          void hydrateOfferFromInbox(id, rid);
          return;
        }
        setShowArchived(false);
        persistDriverArchive(false);
        setTab('requests');
        persistDriverTab('requests');
        if (id > 0) {
          applyPushOfferPreview(payload);
          pendingNotificationExpandRef.current = null;
        } else if (rid > 0) {
          pendingNotificationExpandRef.current = { kind: 'ride', rideId: rid };
        }
        // Geluid + directe inbox-hydrate — rit moet er staan zodra de push binnen is.
        setTimeout(() => {
          if (!mountedRef.current) return;
          if (id > 0) playDriverOfferToneForIds([id]);
          void hydrateOfferFromInbox(id, rid);
        }, 0);
      } catch {
        /* push mag het scherm niet laten crashen */
      }
    });
    return () => setForegroundDriverOfferListener(null);
  }, [applyPushOfferPreview, hydrateOfferFromInbox]);

  useEffect(() => {
    return addDriverOfferNotificationResponseListener(
      (target) => openOfferFromNotification(target),
      () => {
        if (!mountedRef.current) return;
        setTimeout(() => {
          if (!mountedRef.current) return;
          try {
            setTab('trips');
            persistDriverTab('trips');
          } catch {
            /* ignore */
          }
        }, 0);
      }
    );
  }, [openOfferFromNotification]);

  useEffect(() => {
    let cancelled = false;
    void (async () => {
      try {
        const initial = await consumeInitialDriverNotificationResponse();
        if (cancelled || !initial || !mountedRef.current) return;
        if (initial.screen === 'requests') {
          openOfferFromNotification(initial);
        } else {
          setTimeout(() => {
            if (cancelled || !mountedRef.current) return;
            setTab('trips');
            persistDriverTab('trips');
          }, 0);
        }
      } catch {
        /* cold-start deep-link mag niet crashen */
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [openOfferFromNotification]);

  // Houd GPS-posts gelijk met admin refresh_seconds zolang de chauffeur online is
  // (ook na heropenen van de app terwijl status al online was).
  // Wacht tot availability op de server bevestigd is — optimistic online + IBAN-weigering
  // startte anders al location-pings terwijl is_online false bleef (lege boekingskaart).
  useEffect(() => {
    if (online && DRIVER_BG.rideAlertAudio) primeRideAlertAudio();
  }, [online]);

  // Tracking één keer starten bij online. Voertuig/interval alleen updaten — geen herstart
  // (herstart via invalidate crashte/liet presence doodgaan).
  useEffect(() => {
    if (!DRIVER_BG.locationTracking) {
      void stopDriverLocationTracking();
      return;
    }
    if (!driverToken || !online) {
      if (!online) {
        void stopDriverLocationTracking();
      }
      return;
    }
    void startDriverLocationTracking({
      token: driverToken,
      vehicleId: selectedVehicleId,
      refreshSeconds: gpsRefreshSeconds,
    }).catch(() => undefined);
    return () => {
      void stopDriverLocationTracking();
    };
  }, [driverToken, online]);

  useEffect(() => {
    if (!DRIVER_BG.locationTracking || !online || !driverToken) return;
    updateDriverLocationTrackingOpts({
      token: driverToken,
      vehicleId: selectedVehicleId,
      refreshSeconds: gpsRefreshSeconds,
    });
  }, [driverToken, online, selectedVehicleId, gpsRefreshSeconds]);

  useEffect(() => {
    if (!focusVehiclePhoto || tab !== 'profile') return;
    const timer = setTimeout(() => {
      const target = vehiclePhotoRef.current;
      const scroller = profileScrollRef.current;
      if (!target || !scroller) {
        setFocusVehiclePhoto(false);
        return;
      }
      target.measureLayout(
        scroller as unknown as number,
        (_x, y) => {
          scroller.scrollTo({ y: Math.max(0, y - 12), animated: true });
          setFocusVehiclePhoto(false);
        },
        () => setFocusVehiclePhoto(false)
      );
    }, 120);
    return () => clearTimeout(timer);
  }, [focusVehiclePhoto, tab]);

  async function selectVehicle(id: number) {
    const previousId = selectedVehicleId;
    const next = vehicles.find((vehicle) => vehicle.id === id) || null;
    setSelectedVehicleId(id);
    setVehiclePickerOpen(false);
    setError((current) => (current === VEHICLE_REQUIRED_ERROR ? null : current));
    if (previousId != null && previousId !== id && next && !resolveMediaUrl(next.photo_url)) {
      setVehiclePhotoAsk(next);
    }
    if (!driverToken || !online) return;
    try {
      await setDriverOnline(driverToken, true, id);
      if (DRIVER_BG.locationTracking) {
        await startDriverLocationTracking({
          token: driverToken,
          vehicleId: id,
          refreshSeconds: gpsRefreshSeconds,
        });
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t("Voertuig opslaan mislukt."));
    }
  }

  async function savePayoutIban() {
    if (!driverToken || ibanBusy) return;
    const value = ibanDraft.trim();
    const invalid = ibanFieldError(value);
    if (invalid) {
      setIbanError(invalid);
      return;
    }
    setIbanBusy(true);
    setIbanError(null);
    try {
      const res = await updateDriverIban(driverToken, value);
      setPayoutIbanMasked(res.data?.masked_iban || null);
      setIbanDraft('');
      setIbanEditing(false);
    } catch (e) {
      const field =
        e instanceof ApiError && e.body && typeof e.body === 'object' && 'errors' in e.body
          ? (e.body as { errors?: { iban?: string[] } }).errors?.iban?.[0]
          : null;
      setIbanError(field || (e instanceof ApiError ? e.message : t("IBAN opslaan mislukt.")));
    } finally {
      setIbanBusy(false);
    }
  }

  async function toggleOnline(next: boolean) {
    if (!driverToken) return;
    setError(null);
    if (next && !payoutIbanMasked) {
      setError(
        t("Vul eerst een geldig IBAN in bij Bedrijf in je profiel. Dit is nodig voor de uitbetaling van de ritten.")
      );
      goToTab('profile');
      return;
    }
    if (next && !selectedVehicleId) {
      setError(VEHICLE_REQUIRED_ERROR);
      setVehiclePickerOpen(true);
      return;
    }
    // Al in die stand én geen lopende tik → negeren (voorkomt 3× schuiven).
    if (next === online && onlinePendingEpochRef.current === null) return;

    const epoch = ++onlineEpochRef.current;
    onlinePendingEpochRef.current = epoch;
    onlineToggleInFlightRef.current = true;
    // Optimistic: schuif beweegt meteen. Bij fout terugzetten.
    setOnline(next);
    if (!next) {
      void stopDriverLocationTracking();
    }

    try {
      // Snelle last-known (max ~0,4s) mee in online-PUT — zonder lat/lng geen marker op de klantkaart.
      let quickCoords: Awaited<ReturnType<typeof readLastKnownDriverMapPosition>> = null;
      if (next && DRIVER_BG.locationTracking) {
        quickCoords = await Promise.race([
          readLastKnownDriverMapPosition(),
          new Promise<null>((resolve) => setTimeout(() => resolve(null), 400)),
        ]).catch(() => null);
      }
      await Promise.race([
        setDriverOnline(driverToken, next, selectedVehicleId, quickCoords),
        new Promise<never>((_, reject) =>
          setTimeout(() => reject(new Error(t('Online zetten mislukt.'))), 8000)
        ),
      ]);
      if (onlineEpochRef.current !== epoch) return;
      if (next) {
        void (async () => {
          if (DRIVER_BG.rideAlertAudio) {
            try {
              primeRideAlertAudio();
            } catch {
              /* ignore */
            }
          }
          if (!DRIVER_BG.locationTracking) return;
          if (onlineEpochRef.current !== epoch || !onlineRef.current) return;
          const published = await publishDriverLocationForMap(driverToken, selectedVehicleId);
          if (!published && onlineEpochRef.current === epoch && onlineRef.current) {
            setFlowMessage(
              t('Locatie is nodig om op de klantkaart te verschijnen. Controleer of locatie aan staat.')
            );
          }
        })();
      }
    } catch (e) {
      if (onlineEpochRef.current === epoch) {
        setOnline(!next);
        if (next) void stopDriverLocationTracking();
        setError(null);
        setFlowMessage(availabilityFailureMessage(e));
      }
    } finally {
      if (onlinePendingEpochRef.current === epoch) onlinePendingEpochRef.current = null;
      if (onlineEpochRef.current === epoch) onlineToggleInFlightRef.current = false;
    }
  }

  function markArchiveSeen() {
    const keys = [
      ...declinedOffers.map((o) => `declined-${o.id}`),
      ...archivedOffers.map((o) => `offer-${o.id}`),
      ...archivedCompletedRides.map((r) => `done-${r.id}`),
      ...archivedOverdueScheduledRides
        .filter((r) => !isMarketplaceRide(r))
        .map((r) => `overdue-auto-${r.id}`),
    ];
    setArchiveSeenKeys(keys);
    persistArchiveSeenKeys(keys);
  }

  function goToTab(key: DriverTabKey) {
    setTab(key);
    persistDriverTab(key);
  }

  function goToArchive(open: boolean) {
    setShowArchived(open);
    persistDriverArchive(open);
    if (open) {
      markArchiveSeen();
    }
  }

  async function onAccept(id: number) {
    if (!driverToken) return;
    setBusyId(id);
    try {
      const accepted = await acceptOffer(driverToken, id);
      const status = String(accepted.data?.ride?.status || '').toLowerCase();
      if (status && status !== 'accepted' && status !== 'assigned') {
        throw new ApiError(t("Accepteren mislukt."), 409, accepted);
      }
      const rideId = Number(accepted.data?.ride?.id || 0);
      // API OK = geaccepteerd. offersRef na refresh() is nog stale (setState),
      // dus géén stillOffer-check — die gaf vals "Accepteren mislukt".
      closedOfferIdsRef.current.add(id);
      pushPreviewRef.current.delete(id);
      setOffers((current) =>
        current.filter(
          (offer) =>
            Number(offer.id) !== id && !(rideId > 0 && offerMatchesRide(offer, rideId))
        )
      );
      await refresh();
      goToTab('trips');
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : t("Accepteren mislukt.");
      if (!showRideConflictNotice(msg)) {
        setRideNotice({
          title: t("Accepteren mislukt."),
          body: msg,
          primaryLabel: t("Begrepen"),
        });
      }
    } finally {
      setBusyId(null);
    }
  }

  async function onDecline(id: number) {
    if (!driverToken) return;
    setBusyId(id);
    try {
      await declineOffer(driverToken, id);
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t("Weigeren mislukt."));
    } finally {
      setBusyId(null);
    }
  }

  function goToRide(rideId: number) {
    goToArchive(false);
    setFocusRideId(rideId);
    goToTab('trips');
  }

  function blockingAssignedRide(exceptId?: number): DriverActiveRide | null {
    const pool = [activeRide, ...parkedRides].filter((ride): ride is DriverActiveRide => !!ride);
    return (
      pool.find(
        (ride) =>
          String(ride.status || '') === 'assigned' &&
          (exceptId == null || ride.id !== exceptId)
      ) || null
    );
  }

  function showRideConflictNotice(message: string, blocking?: DriverActiveRide | null): boolean {
    if (/gepland rond dit tijdstip|overlapping/i.test(message)) {
      setError(null);
      setRideNotice({
        title: t("Rit overlapt"),
        body: message,
        primaryLabel: t("Naar ritten"),
      });
      return true;
    }
    if (/lopende rit|al een rit actief/i.test(message)) {
      setError(null);
      setRideNotice({
        title: t("Er is al een rit actief"),
        body: message,
        primaryLabel: blocking ? t("Naar actieve rit") : t("Naar ritten"),
        rideId: blocking?.id,
      });
      return true;
    }
    return false;
  }

  function warnRideAlreadyActive(current: DriverActiveRide) {
    setError(null);
    setRideNotice({
      title: t("Er is al een rit actief"),
      body: t("Rond eerst de lopende rit af voordat je een andere start."),
      primaryLabel: t("Naar actieve rit"),
      rideId: current.id,
    });
  }

  function findRideById(rideId: number): DriverActiveRide | null {
    const pool = [activeRide, ...parkedRides, ...scheduledRides, ...overdueScheduledRides];
    return pool.find((r) => r && Number(r.id) === rideId) || null;
  }

  function rideNeedsPickupPin(ride: DriverActiveRide | null): boolean {
    if (!ride || String(ride.status || '') !== 'accepted') return false;
    if (ride.pickup_confirmation?.requires_pin === true) return true;
    if (ride.pickup_confirmation?.method === 'pin') return true;
    return isMarketplaceRide(ride);
  }

  function openPickupPin(rideId: number) {
    setPinRideId(rideId);
    setPickupPin('');
    setPinFormError(null);
    setFlowMessage(null);
  }

  function confirmPickupPin() {
    if (pinRideId == null || pinBusy) return;
    const digits = pickupPin.replace(/\D/g, '');
    if (!digits) {
      setPinFormError(t("Pin is verplicht"));
      return;
    }
    if (digits.length !== 4) {
      setPinFormError(t("Vul de 4-cijferige code in."));
      return;
    }
    setPinFormError(null);
    void submitStartRide(pinRideId, digits);
  }

  async function onStartRide(rideId: number) {
    if (!driverToken) return;
    const blocking = blockingAssignedRide(rideId);
    if (blocking) {
      warnRideAlreadyActive(blocking);
      return;
    }
    const ride = findRideById(rideId);
    if (rideNeedsPickupPin(ride)) {
      openPickupPin(rideId);
      return;
    }
    await submitStartRide(rideId);
  }

  async function submitStartRide(rideId: number, pin?: string) {
    if (!driverToken) return;
    const blocking = blockingAssignedRide(rideId);
    if (blocking) {
      warnRideAlreadyActive(blocking);
      return;
    }
    const usePin = !!pin;
    if (usePin) setPinBusy(true);
    else setBusyRideId(rideId);
    setError(null);
    setFlowMessage(null);
    if (usePin) setPinFormError(null);
    try {
      const started = await startRide(driverToken, rideId, pin ? { pin } : undefined);
      setPinRideId(null);
      setPickupPin('');
      setPinFormError(null);
      await refresh();
      goToTab('trips');
      const pickupWarning = String(
        (started as { pickup_warning?: string | null }).pickup_warning || ''
      ).trim();
      if (pickupWarning) {
        setRideNotice({
          title: t("Controleer het ophalen"),
          body: pickupWarning,
          primaryLabel: t("Begrepen"),
        });
      }
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : t("Rit starten mislukt.");
      if (usePin) {
        // Fout in dezelfde pin-modal houden — geen tweede Modal (iOS blokkeert dan de UI).
        setPinFormError(msg);
      } else {
        const blockingNow = blockingAssignedRide(rideId);
        if (blockingNow && /al een (lopende )?rit/i.test(msg)) {
          warnRideAlreadyActive(blockingNow);
        } else if (!showRideConflictNotice(msg, blockingNow)) {
          setFlowMessage(msg);
        }
      }
    } finally {
      if (usePin) setPinBusy(false);
      else setBusyRideId(null);
    }
  }

  function openProposePickup(ride: DriverActiveRide) {
    setProposePickupAt(defaultPickupDate(10));
    setProposeRideId(ride.id);
  }

  async function confirmProposePickup() {
    if (!driverToken || !proposeRideId) return;
    setProposeBusy(true);
    setError(null);
    try {
      await proposePickup(driverToken, proposeRideId, formatPickupAtPayload(proposePickupAt));
      setProposeRideId(null);
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t("Voorstel versturen mislukt."));
    } finally {
      setProposeBusy(false);
    }
  }

  function requestReleaseRide(rideId: number) {
    Alert.alert(
      'Rit vrijgeven?',
      t("Andere chauffeurs kunnen deze rit daarna overnemen."),
      [
        { text: t("Terug"), style: 'cancel' },
        { text: t("Vrijgeven"), style: 'destructive', onPress: () => onReleaseRide(rideId) },
      ]
    );
  }

  async function onReleaseRide(rideId: number) {
    if (!driverToken) return;
    setBusyRideId(rideId);
    setError(null);
    try {
      await releaseAcceptedRide(driverToken, rideId);
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t("Vrijgeven mislukt."));
    } finally {
      setBusyRideId(null);
    }
  }

  async function requestCompleteRide(ride: DriverActiveRide) {
    if (!driverToken) return;
    if (rideRequiresPaymentBeforeComplete(ride)) {
      setSettleRideId(ride.id);
      return;
    }
    if (isContractRide(ride)) {
      await completeRideNow(ride.id);
      return;
    }
    if (isRidePaid(ride) && isRideInvoiceSent(ride)) {
      await completeRideNow(ride.id);
      return;
    }
    if (isRidePaid(ride)) {
      setBusyRideId(ride.id);
      try {
        const inv = await fetchRideInvoice(driverToken, ride.id);
        if (inv.data?.invoice_sent === true) {
          await completeRideNow(ride.id);
          return;
        }
      } catch {
        /* bij twijfel factuur-popup */
      }
      setBusyRideId(null);
    }
    setSettleRideId(ride.id);
  }

  async function completeRideNow(rideId: number) {
    if (!driverToken) return;
    setBusyRideId(rideId);
    setError(null);
    try {
      const completed = await completeRide(driverToken, rideId);
      await refresh();
      const dropoffWarning = String(
        (completed as { dropoff_warning?: string | null }).dropoff_warning || ''
      ).trim();
      if (dropoffWarning) {
        setRideNotice({
          title: t("Controleer de bestemming"),
          body: dropoffWarning,
          primaryLabel: t("Begrepen"),
        });
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t("Rit afronden mislukt."));
    } finally {
      setBusyRideId(null);
    }
  }

  function patchTripRide(next: DriverActiveRide) {
    if (activeRide?.id === next.id) setActiveRide(next);
    setParkedRides((list) => list.map((ride) => (ride.id === next.id ? next : ride)));
  }

  const settleRide =
    settleRideId == null
      ? null
      : activeRide?.id === settleRideId
        ? activeRide
        : parkedRides.find((ride) => ride.id === settleRideId) ?? null;

  function removeRideFromDriverLists(rideId: number) {
    setActiveRide((current) => (current?.id === rideId ? null : current));
    setParkedRides((list) => list.filter((ride) => ride.id !== rideId));
    setScheduledRides((list) => list.filter((ride) => ride.id !== rideId));
    setOverdueScheduledRides((list) => list.filter((ride) => ride.id !== rideId));
    setArchivedOverdueScheduledRides((list) => list.filter((ride) => ride.id !== rideId));
  }

  async function confirmCancelRide() {
    if (cancelInFlightRef.current) return;
    if (!driverToken || cancelRideId == null || !selectedCancelReason) return;
    const rideId = cancelRideId;
    const reason = selectedCancelReason;
    cancelInFlightRef.current = true;
    setCancelBusy(true);
    setError(null);
    try {
      await cancelAcceptedRide(driverToken, rideId, reason);
      setCancelRideId(null);
      setSelectedCancelReason(null);
      removeRideFromDriverLists(rideId);
      void refresh();
    } catch (e) {
      setCancelRideId(null);
      setSelectedCancelReason(null);
      setFlowMessage(cancelFailureMessage(e));
    } finally {
      cancelInFlightRef.current = false;
      setCancelBusy(false);
    }
  }

  const shownError =
    error === VEHICLE_REQUIRED_ERROR && (vehiclePickerOpen || vehicleFormOpen) ? null : error;

  function renderPlaceholder(title: string, text: string) {
    return (
      <ScrollView
        style={{ flex: 1 }}
        contentContainerStyle={styles.scroll}
        keyboardShouldPersistTaps="handled"
        nestedScrollEnabled
      >
        <ErrorText>{shownError}</ErrorText>
        <ScreenHeader title={title} />
        <Card>
          <Text style={styles.hint}>{text}</Text>
        </Card>
      </ScrollView>
    );
  }

  const openOfferRideIds = new Set<number>();
  for (const offer of offers) {
    const id = offerRideRequestId(offer);
    if (id > 0) openOfferRideIds.add(id);
  }
  const scheduledForTrips = scheduledRides.filter((r) => !rideListedAsOpenOffer(r, openOfferRideIds));
  const overdueForTrips = overdueScheduledRides.filter((r) => !rideListedAsOpenOffer(r, openOfferRideIds));
  const archivedOverdueForTrips = archivedOverdueScheduledRides.filter(
    (r) => !rideListedAsOpenOffer(r, openOfferRideIds)
  );
  const activeForTrips =
    activeRide && !rideListedAsOpenOffer(activeRide, openOfferRideIds) ? activeRide : null;
  const parkedForTrips = parkedRides.filter((r) => !rideListedAsOpenOffer(r, openOfferRideIds));

  // Marketplace-ritten: nooit in "verlopen ophaalmoment" (geen overdue-UI).
  const ownOverdueScheduledRides = overdueForTrips.filter((r) => !isMarketplaceRide(r));
  const overdueContractRides = ownOverdueScheduledRides.filter((r) => isContractRide(r));
  const overdueNonContractRidesAll = ownOverdueScheduledRides.filter((r) => !isContractRide(r));
  const marketplaceOverdueAsScheduled = overdueForTrips.filter((r) => isMarketplaceRide(r));
  const ownArchivedOverdueRides = archivedOverdueForTrips.filter((r) => !isMarketplaceRide(r));
  const marketplaceArchivedOutstanding = archivedOverdueForTrips.filter((r) => isMarketplaceRide(r));
  const plannedRideIds = new Set<number>();
  const plannedRidesAll = [...scheduledForTrips, ...marketplaceOverdueAsScheduled, ...marketplaceArchivedOutstanding].filter(
    (ride) => {
      if (plannedRideIds.has(ride.id)) return false;
      plannedRideIds.add(ride.id);
      return true;
    }
  );
  const archivedCompletedSet = useMemo(
    () => new Set(archivedCompletedIds),
    [archivedCompletedIds]
  );
  const visibleCompletedRidesAll = completedRides.filter((r) => !archivedCompletedSet.has(r.id));
  const archivedCompletedRides = completedRides.filter((r) => archivedCompletedSet.has(r.id));

  const hasAnyContractInTrips =
    (!!activeForTrips && isContractRide(activeForTrips)) ||
    parkedForTrips.some((r) => isContractRide(r)) ||
    plannedRidesAll.some((r) => isContractRide(r)) ||
    overdueNonContractRidesAll.some((r) => isContractRide(r)) ||
    visibleCompletedRidesAll.some((r) => isContractRide(r)) ||
    overdueContractRides.length > 0 ||
    ownArchivedOverdueRides.some((r) => isContractRide(r));
  const showRideKindFilter = canFilterContractRides || hasAnyContractInTrips;
  const effectiveRideKind: RideKindFilter = showRideKindFilter ? rideKindFilter : 'all';
  // Alleen bij filter Contract: passagiersbord. Bij Alles/Normaal één ritlijst —
  // anders liggen Openstaand/Verlopen over de chauffeurfilters heen.
  const showContractPassengerBoard = contractantDriver && effectiveRideKind === 'contract';
  const todayIso = toIsoDate(new Date());
  const weekFromIso = mondayIsoFrom();
  const weekToIso = addDaysIso(weekFromIso, 6);

  function matchesTripFilters(ride: DriverActiveRide | null | undefined): boolean {
    if (!rideMatchesKindFilter(ride, effectiveRideKind)) return false;
    if (isOutstandingMarketplaceRide(ride)) return true;
    return rideMatchesPeriodFilter(ride, ridePeriodFilter, todayIso, weekFromIso, weekToIso);
  }

  function keepDriverCard(ride: DriverActiveRide | null | undefined): boolean {
    if (!ride) return false;
    return !(showContractPassengerBoard && isContractRide(ride));
  }

  // Actieve/geparkeerde ritten altijd tonen (lopend werk), wel soortfilter toepassen.
  const activeRideFiltered =
    activeForTrips && rideMatchesKindFilter(activeForTrips, effectiveRideKind) && keepDriverCard(activeForTrips)
      ? activeForTrips
      : null;
  const parkedRidesFiltered = parkedForTrips.filter(
    (r) => rideMatchesKindFilter(r, effectiveRideKind) && keepDriverCard(r)
  );
  const plannedRides = plannedRidesAll.filter((r) => matchesTripFilters(r) && keepDriverCard(r));
  const overdueNonContractRides = overdueNonContractRidesAll.filter(
    (r) => rideMatchesKindFilter(r, effectiveRideKind) && keepDriverCard(r)
  );
  // Verlopen contractritten van een contractant staan in het passagiersoverzicht.
  const overdueContractRidesOnTrips = showContractPassengerBoard
    ? []
    : overdueContractRides.filter((r) => matchesTripFilters(r));
  const visibleCompletedRides = visibleCompletedRidesAll.filter(
    (r) => matchesTripFilters(r) && keepDriverCard(r)
  );

  const visibleCancelledOffers = useMemo(
    () => cancelledOffers.filter((offer) => cancelledOfferStillVisible(offer)),
    [cancelledOffers]
  );

  const requestOffers = useMemo(() => {
    let live = offers;
    if (focusOfferId) {
      const index = offers.findIndex((o) => Number(o.id) === focusOfferId);
      if (index > 0) {
        live = [offers[index], ...offers.filter((o) => Number(o.id) !== focusOfferId)];
      }
    }
    const liveIds = new Set(live.map((o) => Number(o.id)));
    const cancelledOnly = visibleCancelledOffers.filter((o) => !liveIds.has(Number(o.id)));
    return [...live, ...cancelledOnly];
  }, [offers, focusOfferId, visibleCancelledOffers]);

  // Geannuleerde kaarten na 5 min uit Aanvragen halen.
  useEffect(() => {
    if (cancelledOffers.length === 0) return;
    const timer = setInterval(() => {
      setCancelledOffers((current) => {
        const next = current.filter((offer) => cancelledOfferStillVisible(offer));
        return next.length === current.length ? current : next;
      });
    }, 15_000);
    return () => clearInterval(timer);
  }, [cancelledOffers.length]);

  const hasTripsUnfiltered =
    !!activeForTrips ||
    parkedForTrips.length > 0 ||
    plannedRidesAll.length > 0 ||
    overdueNonContractRidesAll.length > 0 ||
    overdueContractRides.length > 0 ||
    visibleCompletedRidesAll.length > 0;
  const hasDriverTripCards =
    !!activeRideFiltered ||
    parkedRidesFiltered.length > 0 ||
    plannedRides.length > 0 ||
    overdueNonContractRides.length > 0 ||
    overdueContractRidesOnTrips.length > 0 ||
    visibleCompletedRides.length > 0;
  const hasTrips = hasDriverTripCards || showContractPassengerBoard;

  function chooseRideKind(kind: RideKindFilter) {
    setRideKindFilter(kind);
    persistRideKindFilter(kind);
  }

  function chooseRidePeriod(period: RidePeriodFilter) {
    setRidePeriodFilter(period);
    persistRidePeriodFilter(period);
  }

  function toggleCompletedArchive(rideId: number) {
    setArchivedCompletedIds((prev) => {
      const next = prev.includes(rideId) ? prev.filter((id) => id !== rideId) : [...prev, rideId];
      persistArchivedCompletedIds(next);
      return next;
    });
  }

  useEffect(() => {
    if (tab !== 'trips' || focusRideId == null) return;
    const y = rideOffsets.current[focusRideId];
    if (y == null) return;
    const t = setTimeout(() => {
      tripsScrollRef.current?.scrollTo({ y: Math.max(0, y - 12), animated: true });
    }, 80);
    return () => clearTimeout(t);
  }, [tab, focusRideId, hasTrips]);

  function bindRideOffset(rideId: number) {
    return (e: { nativeEvent: { layout: { y: number } } }) => {
      rideOffsets.current[rideId] = e.nativeEvent.layout.y;
      if (focusRideId === rideId && tab === 'trips') {
        tripsScrollRef.current?.scrollTo({
          y: Math.max(0, e.nativeEvent.layout.y - 12),
          animated: true,
        });
      }
    };
  }

  const tripsPanel = (
    <View style={{ flex: 1, minHeight: 0 }}>
    <View style={{ paddingHorizontal: 20, paddingTop: 4 }}>
      <ErrorText>{shownError}</ErrorText>
      <ScreenHeader
        title={t("Ritten")}
        right={
          showContractPassengerBoard ? undefined : (
          <View style={styles.periodToggle}>
            {RIDE_PERIOD_OPTIONS.map((opt) => {
              const active = ridePeriodFilter === opt.key;
              return (
                <Pressable
                  key={opt.key}
                  style={[styles.periodBtn, active && styles.periodBtnActive]}
                  onPress={() => chooseRidePeriod(opt.key)}
                  accessibilityRole="button"
                  accessibilityState={{ selected: active }}
                >
                  <Text style={[styles.periodBtnText, active && styles.periodBtnTextActive]}>
                    {t(opt.label)}
                  </Text>
                </Pressable>
              );
            })}
          </View>
          )
        }
      />
      {showRideKindFilter ? (
        <View style={styles.rideKindRow}>
          {RIDE_KIND_OPTIONS.map((opt) => {
            const active = rideKindFilter === opt.key;
            return (
              <Pressable
                key={opt.key}
                style={[styles.rideKindBtn, active && styles.rideKindBtnActive]}
                onPress={() => chooseRideKind(opt.key)}
                accessibilityRole="button"
                accessibilityState={{ selected: active }}
              >
                <Text style={[styles.rideKindText, active && styles.rideKindTextActive]}>
                  {t(opt.label)}
                </Text>
              </Pressable>
            );
          })}
        </View>
      ) : null}
    </View>
    {showContractPassengerBoard ? null : (
    <ScrollView
      ref={tripsScrollRef}
      style={{ flex: 1 }}
      contentContainerStyle={styles.scroll}
      keyboardShouldPersistTaps="handled"
      nestedScrollEnabled
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={async () => {
            setRefreshing(true);
            await refresh();
            setRefreshing(false);
          }}
          tintColor={colors.text}
        />
      }
    >
      {!hasDriverTripCards ? (
        <Card>
          <Text style={styles.emptyTitle}>
            {ridePeriodFilter === 'week' ? t("Geen ritten deze week.") : t("Geen ritten voor vandaag.")}
          </Text>
          <Text style={styles.hint}>{t("Geaccepteerde ritten verschijnen hier. Afgeronde ritten blijven zichtbaar tot je ze archiveert.")}</Text>
        </Card>
      ) : (
        <>
          {activeRideFiltered ? (
            <>
              <Text style={styles.sectionLabel}>{t("Actief")}</Text>
              <View onLayout={bindRideOffset(activeRideFiltered.id)}>
                <DriverTripCard
                  ride={activeRideFiltered}
                  variant="active"
                  busy={busyRideId === activeRideFiltered.id}
                  highlighted={focusRideId === activeRideFiltered.id}
                  onHighlightEnd={() => setFocusRideId(null)}
                  onStart={() => onStartRide(activeRideFiltered.id)}
                  onOpenMaps={() => openMapsForRide(activeRideFiltered, 'dropoff')}
                  onComplete={() => requestCompleteRide(activeRideFiltered)}
                  onCancel={
                    activeRideFiltered.can_cancel_with_reason
                      ? () => {
                          setSelectedCancelReason(null);
                          setCancelRideId(activeRideFiltered.id);
                        }
                      : undefined
                  }
                />
              </View>
            </>
          ) : null}
          {parkedRidesFiltered.map((ride) => (
            <View key={`parked-${ride.id}`} onLayout={bindRideOffset(ride.id)}>
              <DriverTripCard
                ride={ride}
                variant="active"
                busy={busyRideId === ride.id}
                highlighted={focusRideId === ride.id}
                onHighlightEnd={() => setFocusRideId(null)}
                onStart={() => onStartRide(ride.id)}
                onOpenMaps={() => openMapsForRide(ride, 'dropoff')}
                onComplete={() => requestCompleteRide(ride)}
              />
            </View>
          ))}
          {plannedRides.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>{t("Geaccepteerd / gepland")}</Text>
              {plannedRides.map((ride) => (
                <View key={`scheduled-${ride.id}`} onLayout={bindRideOffset(ride.id)}>
                  <DriverTripCard
                    ride={ride}
                    variant="scheduled"
                    busy={busyRideId === ride.id}
                    highlighted={focusRideId === ride.id}
                    onHighlightEnd={() => setFocusRideId(null)}
                    onStart={() => onStartRide(ride.id)}
                    onOpenMaps={() => void openPickupMapsAndNotify(driverToken, ride)}
                    onCancel={
                      ride.can_cancel_with_reason
                        ? () => {
                            setSelectedCancelReason(null);
                            setCancelRideId(ride.id);
                          }
                        : undefined
                    }
                  />
                </View>
              ))}
            </>
          ) : null}
          {overdueContractRidesOnTrips.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>{t("Verlopen contractritten")}</Text>
              {overdueContractRidesOnTrips.map((ride) => (
                <View key={`overdue-contract-${ride.id}`} onLayout={bindRideOffset(ride.id)}>
                  <DriverTripCard
                    ride={ride}
                    variant="overdue"
                    busy={busyRideId === ride.id}
                    highlighted={focusRideId === ride.id}
                    onHighlightEnd={() => setFocusRideId(null)}
                    onStart={() => onStartRide(ride.id)}
                    onOpenMaps={() => void openPickupMapsAndNotify(driverToken, ride)}
                    onProposePickup={() => openProposePickup(ride)}
                    onRelease={() => requestReleaseRide(ride.id)}
                    onCancel={
                      ride.can_cancel_with_reason
                        ? () => {
                            setSelectedCancelReason(null);
                            setCancelRideId(ride.id);
                          }
                        : undefined
                    }
                  />
                </View>
              ))}
            </>
          ) : null}
          {overdueNonContractRides.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>{t("Verlopen ophaalmoment")}</Text>
              {overdueNonContractRides.map((ride) => (
                <View key={`overdue-${ride.id}`} onLayout={bindRideOffset(ride.id)}>
                  <DriverTripCard
                    ride={ride}
                    variant="overdue"
                    busy={busyRideId === ride.id}
                    highlighted={focusRideId === ride.id}
                    onHighlightEnd={() => setFocusRideId(null)}
                    onStart={() => onStartRide(ride.id)}
                    onOpenMaps={() => void openPickupMapsAndNotify(driverToken, ride)}
                    onProposePickup={() => openProposePickup(ride)}
                    onRelease={() => requestReleaseRide(ride.id)}
                    onCancel={
                      ride.can_cancel_with_reason
                        ? () => {
                            setSelectedCancelReason(null);
                            setCancelRideId(ride.id);
                          }
                        : undefined
                    }
                  />
                </View>
              ))}
            </>
          ) : null}
          {visibleCompletedRides.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>{t("Afgerond")}</Text>
              {visibleCompletedRides.map((ride) => (
                <View key={`done-${ride.id}`} onLayout={bindRideOffset(ride.id)}>
                  <DriverTripCard
                    ride={ride}
                    variant="completed"
                    highlighted={focusRideId === ride.id}
                    onHighlightEnd={() => setFocusRideId(null)}
                    onArchive={() => toggleCompletedArchive(ride.id)}
                  />
                </View>
              ))}
            </>
          ) : null}
        </>
      )}
    </ScrollView>
    )}
    {showContractPassengerBoard ? (
      <View style={{ flex: 1, minHeight: 0 }}>
        <ContractHomeScreen embedded />
      </View>
    ) : null}
    </View>
  );

  const planningPanel = driverToken ? (
    <DriverPlanningPanel
      token={driverToken}
      vehicleId={selectedVehicleId}
    />
  ) : (
    <ScrollView contentContainerStyle={styles.scroll}>
      <Text style={styles.hint}>{t("Niet ingelogd.")}</Text>
    </ScrollView>
  );

  const requestsPanel = (
    <FlatList
      style={{ flex: 1 }}
      data={requestOffers}
      extraData={`${focusOfferId ?? ''}:${expandOfferId ?? ''}:${offers.length}:${visibleCancelledOffers.length}`}
      keyExtractor={(item) => String(item.id)}
      keyboardShouldPersistTaps="handled"
      removeClippedSubviews={false}
      contentContainerStyle={styles.scroll}
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={async () => {
            setRefreshing(true);
            await refresh();
            setRefreshing(false);
          }}
          tintColor={colors.text}
        />
      }
      ListHeaderComponent={
        <>
          <ErrorText>{shownError}</ErrorText>
          <ScreenHeader title={t("Aanvragen")} />
          {!online ? (
            <Card>
              <Text style={styles.hint}>{t("Zet jezelf online. Locatie blijft actief op de achtergrond (belangrijk voor matching op de NEXA Taxi marktplaats).")}</Text>
            </Card>
          ) : null}
        </>
      }
      ListEmptyComponent={
        <Card>
          <Text style={styles.hint}>
            {online ? t("Nog geen ritten in je inbox.") : t("Ga online om ritten te ontvangen.")}
          </Text>
        </Card>
      }
      renderItem={({ item }) => {
        if (!item || Number(item.id) <= 0) {
          return <View />;
        }
        const cancelled = isCancelledOffer(item);
        return (
          <DriverOfferCard
            offer={item}
            cancelled={cancelled}
            busy={!cancelled && busyId === item.id}
            highlighted={!cancelled && focusOfferId === item.id}
            expandOnce={
              !cancelled && expandOfferId != null && Number(item.id) === expandOfferId
            }
            soloOfferId={cancelled ? null : expandOfferId}
            fallbackVehicleLabel={vehicleDisplayLabel(selectedVehicle) || null}
            fallbackVehicleName={vehicleDisplayName(selectedVehicle) || null}
            onAccept={cancelled ? undefined : () => onAccept(item.id)}
            onDecline={cancelled ? undefined : () => onDecline(item.id)}
          />
        );
      }}
    />
  );

  const chooseAccent = async (key: string) => {
    const next = normalizeDriverAccent(key);
    setAccent(next);
    persistDriverAccent(next);
    if (!driverToken) return;
    try {
      await updateDriverAccent(driverToken, next);
    } catch {
      /* keuze blijft lokaal zichtbaar */
    }
  };

  const openHandleiding = () => {
    void resolveApiBaseUrl().then((base) => {
      Linking.openURL(`${base}/taxi/chauffeur/handleiding`).catch(() => undefined);
    });
  };

  const chooseImageSource = (): Promise<'library' | 'files' | null> =>
    new Promise((resolve) => {
      Alert.alert('Afbeelding kiezen', t("Waar wil je de foto vandaan halen?"), [
        { text: t("Annuleren"), style: 'cancel', onPress: () => resolve(null) },
        { text: 'Fotobibliotheek', onPress: () => resolve('library') },
        { text: t("Bestanden"), onPress: () => resolve('files') },
      ]);
    });

  async function openReviews() {
    setReviewsOpen(true);
    if (!driverToken) return;
    setReviewsBusy(true);
    setReviewsError(null);
    try {
      const res = await fetchDriverReviews(driverToken);
      setReviews(Array.isArray(res.reviews) ? res.reviews : []);
    } catch (e) {
      setReviews([]);
      setReviewsError(e instanceof ApiError ? e.message : t("Beoordelingen laden mislukt."));
    } finally {
      setReviewsBusy(false);
    }
  }

  /**
   * Fotobibliotheek. Bij iOS-beperkte toegang tonen we alleen de toegestane foto's
   * (met "Meer foto's toevoegen"); PHPicker (ImagePicker) negeert die selectie.
   */
  const pickFromLibrary = async (
    quality: number
  ): Promise<{ uri: string; mime: string | null } | null> => {
    const perm = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!perm.granted) {
      throw new Error(t("Geen toegang tot foto’s. Sta dit toe in de instellingen."));
    }
    if (Platform.OS === 'ios') {
      const ml = loadMediaLibrary();
      const access = ml ? await ml.getPermissionsAsync().catch(() => null) : null;
      if (access?.accessPrivileges === 'limited') {
        return new Promise((resolve) => {
          limitedPickResolve.current = resolve;
          setLimitedPickerOpen(true);
        });
      }
    }
    const picked = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      quality,
      allowsEditing: false,
    });
    const asset = picked.assets?.[0];
    if (picked.canceled || !asset?.uri) return null;
    return { uri: asset.uri, mime: asset.mimeType || null };
  };

  const finishLimitedPick = (image: PickedLibraryImage | null) => {
    setLimitedPickerOpen(false);
    limitedPickResolve.current?.(image ? { uri: image.uri, mime: image.mimeType } : null);
    limitedPickResolve.current = null;
  };

  const pickAndUploadMedia = async (kind: 'profile' | 'logo' | 'vehicle') => {
    if (!driverToken || mediaBusy || cropUri) return;
    setError(null);
    try {
      const source = await chooseImageSource();
      if (!source) return;

      let uri: string | null = null;
      let mime: string | null = null;

      if (source === 'library') {
        const picked = await pickFromLibrary(1);
        if (!picked) return;
        uri = picked.uri;
        mime = picked.mime;
      } else {
        const picked = await DocumentPicker.getDocumentAsync({
          type: ['image/*'],
          copyToCacheDirectory: true,
          multiple: false,
        });
        if (picked.canceled || !picked.assets?.[0]?.uri) return;
        uri = picked.assets[0].uri;
        mime = picked.assets[0].mimeType || null;
      }

      setCropKind(kind);
      setCropMime(mime);
      setCropUri(uri);
    } catch (e) {
      setError(
        e instanceof Error ? e.message : t("Foto kiezen mislukt.")
      );
    }
  };

  const uploadCroppedMedia = async (cropped: {
    uri: string;
    mimeType: 'image/png' | 'image/jpeg';
  }) => {
    if (!driverToken || !cropKind) return;
    const kind = cropKind;
    const vehicleId = selectedVehicleId;
    setCropUri(null);
    setCropMime(null);
    setCropKind(null);
    if (kind === 'vehicle' && !vehicleId) {
      setError(t("Kies eerst een voertuig."));
      return;
    }
    setMediaBusy(true);
    setError(null);
    try {
      if (kind === 'vehicle') {
        const res = await uploadDriverVehiclePhoto(
          driverToken,
          cropped.uri,
          cropped.mimeType,
          vehicleId
        );
        if (res.media) setDriverMedia(res.media);
        const photoUrl = res.vehicle?.photo_url || res.media?.vehicle_photo_url || null;
        if (vehicleId && photoUrl) {
          clearedPhotos.current.vehicles.delete(vehicleId);
          setVehicles((list) =>
            list.map((vehicle) =>
              vehicle.id === vehicleId ? { ...vehicle, photo_url: photoUrl } : vehicle
            )
          );
          setFleet((list) =>
            list.map((vehicle) =>
              vehicle.id === vehicleId ? { ...vehicle, photo_url: photoUrl } : vehicle
            )
          );
        }
      } else {
        const res =
          kind === 'profile'
            ? await uploadDriverProfilePhoto(driverToken, cropped.uri, cropped.mimeType)
            : await uploadDriverCompanyLogo(driverToken, cropped.uri, cropped.mimeType);
        if (kind === 'profile') clearedPhotos.current.profile = false;
        else clearedPhotos.current.logo = false;
        if (res.media) setDriverMedia(res.media);
      }
    } catch (e) {
      setError(
        e instanceof ApiError
          ? e.message
          : e instanceof Error
            ? e.message
            : t("Foto uploaden mislukt.")
      );
    } finally {
      setMediaBusy(false);
    }
  };

  function openVehicleForm(vehicle?: DriverVehicle) {
    setVehicleFormError(null);
    setVehicleConfirmDelete(false);
    setVehiclePhotoZoom(false);
    setVehicleDraft({
      id: vehicle?.id ?? null,
      name: vehicle?.name || '',
      type: vehicle?.type || 'car',
      license_plate: formatDutchPlate(vehicle?.license_plate || ''),
      person_range: vehicle?.person_range || '1-4',
      photoUri: null,
      photoMime: null,
      existingPhoto: vehicle?.photo_url || null,
    });
    setVehiclePickerOpen(false);
    setVehicleFormOpen(true);
  }

  async function pickVehicleDraftPhoto() {
    setVehicleFormError(null);
    try {
      const source = await chooseImageSource();
      if (!source) return;

      let uri: string | null = null;
      let mime: string | null = null;

      if (source === 'library') {
        const picked = await pickFromLibrary(0.85);
        if (!picked) return;
        uri = picked.uri;
        mime = picked.mime;
      } else {
        const picked = await DocumentPicker.getDocumentAsync({
          type: ['image/*'],
          copyToCacheDirectory: true,
          multiple: false,
        });
        const asset = picked.assets?.[0];
        if (picked.canceled || !asset?.uri) return;
        uri = asset.uri;
        mime = asset.mimeType || null;
      }

      setCropKind('vehicle-draft');
      setCropMime(mime);
      setCropUri(uri);
    } catch (e) {
      setVehicleFormError(e instanceof Error ? e.message : t("Foto kiezen mislukt."));
    }
  }

  function forgetVehiclePhoto(vehicleId: number) {
    const clear = (list: DriverVehicle[]) =>
      list.map((vehicle) => (vehicle.id === vehicleId ? { ...vehicle, photo_url: null } : vehicle));
    setVehicles(clear);
    setFleet(clear);
    setVehicleDraft((current) =>
      current.id === vehicleId
        ? { ...current, photoUri: null, photoMime: null, existingPhoto: null }
        : current
    );
  }

  function openPhotoPreview(uri: string | null) {
    if (!uri) return;
    setPhotoPreview(uri);
  }

  function confirmRemovePhoto(message: string, onYes: () => void) {
    Alert.alert('Plaatje verwijderen', message, [
      { text: t("Annuleren"), style: 'cancel' },
      { text: t("Verwijderen"), style: 'destructive', onPress: onYes },
    ]);
  }

  async function removeProfilePhoto() {
    if (!driverToken || mediaBusy) return;
    clearedPhotos.current.profile = true;
    setDriverMedia((current) =>
      current ? { ...current, profile_photo_url: null, has_profile_photo: false } : current
    );
    setMediaBusy(true);
    setError(null);
    try {
      const res = await deleteDriverProfilePhoto(driverToken);
      if (res.media) {
        setDriverMedia({
          ...res.media,
          profile_photo_url: null,
          has_profile_photo: false,
        });
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t("Profielfoto verwijderen mislukt."));
    } finally {
      setMediaBusy(false);
    }
  }

  async function removeCompanyLogo() {
    if (!driverToken || mediaBusy) return;
    clearedPhotos.current.logo = true;
    setDriverMedia((current) => (current ? { ...current, company_logo_url: null } : current));
    setMediaBusy(true);
    setError(null);
    try {
      const res = await deleteDriverCompanyLogo(driverToken);
      if (res.media) {
        setDriverMedia({ ...res.media, company_logo_url: null });
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t("Bedrijfslogo verwijderen mislukt."));
    } finally {
      setMediaBusy(false);
    }
  }

  async function removeVehiclePhoto(vehicleId: number) {
    if (!driverToken || vehicleId <= 0) return;
    clearedPhotos.current.vehicles.add(vehicleId);
    forgetVehiclePhoto(vehicleId);
    setMediaBusy(true);
    setVehicleFormError(null);
    setError(null);
    try {
      await deleteDriverVehiclePhoto(driverToken, vehicleId);
    } catch (e) {
      const message = e instanceof ApiError ? e.message : t("Auto-foto verwijderen mislukt.");
      if (vehicleFormOpen) setVehicleFormError(message);
      else setError(message);
    } finally {
      setMediaBusy(false);
    }
  }

  function removeVehicleDraftPhoto() {
    const id = vehicleDraft.id;
    setVehiclePhotoZoom(false);
    setVehicleDraft((current) => ({
      ...current,
      photoUri: null,
      photoMime: null,
      existingPhoto: null,
    }));
    if (id && (vehicleDraft.existingPhoto || vehicleDraft.photoUri)) {
      void removeVehiclePhoto(id);
    }
  }

  async function saveVehicleDraft() {
    if (!driverToken || vehicleFormBusy) return;
    const name = vehicleDraft.name.trim();
    const plate = vehicleDraft.license_plate.trim();
    if (name.length < 1) {
      setVehicleFormError(t("Vul een naam in."));
      return;
    }
    if (plate.length < 1) {
      setVehicleFormError(t("Vul een kenteken in."));
      return;
    }
    setVehicleFormBusy(true);
    setVehicleFormError(null);
    try {
      const input = {
        name,
        type: vehicleDraft.type,
        license_plate: plate,
        person_range: vehicleDraft.person_range,
      };
      const saved = vehicleDraft.id
        ? await updateDriverVehicle(driverToken, vehicleDraft.id, input)
        : await createDriverVehicle(driverToken, input);
      const savedId = Number(saved.data?.id || vehicleDraft.id || 0);
      if (vehicleDraft.photoUri && savedId > 0) {
        clearedPhotos.current.vehicles.delete(savedId);
        await uploadDriverVehiclePhoto(
          driverToken,
          vehicleDraft.photoUri,
          vehicleDraft.photoMime,
          savedId
        );
      }
      setVehicleFormOpen(false);
      await refresh();
    } catch (e) {
      setVehicleFormError(e instanceof ApiError ? e.message : t("Voertuig opslaan mislukt."));
    } finally {
      setVehicleFormBusy(false);
    }
  }

  async function confirmDeleteVehicle() {
    if (!driverToken || !vehicleDraft.id || vehicleFormBusy) return;
    const vehicleId = vehicleDraft.id;
    setVehicleFormBusy(true);
    setVehicleFormError(null);
    try {
      await deleteDriverVehicle(driverToken, vehicleId);
      if (selectedVehicleId === vehicleId) {
        setSelectedVehicleId(null);
      }
      setVehicleConfirmDelete(false);
      setVehicleFormOpen(false);
      await refresh();
    } catch (e) {
      setVehicleConfirmDelete(false);
      setVehicleFormError(e instanceof ApiError ? e.message : t("Voertuig verwijderen mislukt."));
    } finally {
      setVehicleFormBusy(false);
    }
  }

  const profilePanel = (
    <ScrollView
      ref={profileScrollRef}
      style={{ flex: 1 }}
      contentContainerStyle={styles.scroll}
      keyboardShouldPersistTaps="handled"
      nestedScrollEnabled
    >
      <ErrorText>{shownError}</ErrorText>
      <ScreenHeader title={t("Profiel")} />
      <Card>
        <Text style={styles.profileName}>{name || t("Chauffeur")}</Text>
        <View style={styles.profileRow}>
          <Text style={styles.profileLabel}>{t("E-mailadres")}</Text>
          <Text style={styles.profileValue}>{email || '—'}</Text>
        </View>
        <View style={styles.profileRow}>
          <Text style={styles.profileLabel}>{t("Telefoonnummer")}</Text>
          <Text style={[styles.profileValue, !phone && styles.profileValueMuted]}>
            {phone || t("Niet beschikbaar")}
          </Text>
        </View>
        <View style={styles.profileRow}>
          <Text style={styles.profileLabel}>{t("Bedrijf")}</Text>
          <Text style={styles.profileValue}>{company || '—'}</Text>
          <Text style={[styles.profileLabel, { marginTop: 14 }]}>IBAN</Text>
          {!payoutIbanMasked || ibanEditing ? (
            <>
              <TextInput
                value={ibanDraft}
                onChangeText={(value) => {
                  const next = value.toUpperCase();
                  setIbanDraft(next);
                  setIbanError(ibanFieldError(next, true));
                }}
                autoCapitalize="characters"
                autoCorrect={false}
                autoComplete="off"
                placeholder="NL00 BANK 0000 0000 00"
                placeholderTextColor={colors.muted}
                style={[styles.fleetInput, ibanError ? { borderColor: colors.danger } : null]}
                editable={!ibanBusy}
                accessibilityLabel="IBAN"
              />
              <Text style={styles.profileIbanHint}>{t("Dit is nodig voor de uitbetaling van de ritten.")}</Text>
              {ibanError ? <Text style={styles.reviewsError}>{ibanError}</Text> : null}
              <Pressable
                onPress={() => void savePayoutIban()}
                disabled={ibanBusy}
                accessibilityRole="button"
                accessibilityLabel={t("IBAN opslaan")}
                style={[styles.profileIbanSave, ibanBusy && styles.cancelActionDisabled]}
              >
                <Text style={styles.profileIbanSaveText}>{ibanBusy ? t("Opslaan…") : t("IBAN opslaan")}</Text>
              </Pressable>
              {ibanEditing && payoutIbanMasked ? (
                <Pressable
                  onPress={() => {
                    setIbanEditing(false);
                    setIbanDraft('');
                    setIbanError(null);
                  }}
                  accessibilityRole="button"
                  accessibilityLabel={t("IBAN wijzigen annuleren")}
                >
                  <Text style={styles.profileIbanAction}>{t("Annuleren")}</Text>
                </Pressable>
              ) : null}
            </>
          ) : (
            <>
              <Text style={styles.profileValue}>{payoutIbanMasked}</Text>
              <Text style={styles.profileIbanHint}>{t("Dit is nodig voor de uitbetaling van de ritten.")}</Text>
              <Pressable
                onPress={() => {
                  setIbanEditing(true);
                  setIbanDraft('');
                  setIbanError(null);
                }}
                accessibilityRole="button"
                accessibilityLabel={t("IBAN wijzigen")}
              >
                <Text style={styles.profileIbanAction}>{t("Wijzigen")}</Text>
              </Pressable>
            </>
          )}
        </View>
        <View style={styles.profileRow}>
          <Text style={styles.profileLabel}>{t("Accountstatus")}</Text>
          <Text style={styles.profileValue}>{accountStatusLabel(accountActive, online)}</Text>
        </View>
        <Pressable
          style={styles.profileRow}
          onPress={() => void openReviews()}
          accessibilityRole="button"
          accessibilityLabel={t("Beoordelingen bekijken")}
        >
          <Text style={styles.profileLabel}>{t("Beoordeling")}</Text>
          <View style={styles.ratingValue}>
            <Ionicons name="star" size={16} color={REVIEW_STAR} />
            <Text style={styles.profileValue}>
              {driverMedia?.rating_avg != null
                ? Number(driverMedia.rating_avg).toFixed(1)
                : '—'}
              {driverMedia?.rating_count ? ` (${driverMedia.rating_count})` : ''}
            </Text>
          </View>
        </Pressable>

        <View style={styles.profileDivider} />
        <Text style={styles.profileSection}>{t("Foto’s voor de klant")}</Text>
        <Text style={styles.themeHint}>{t("Profielfoto is verplicht voordat je een rit kunt accepteren. Logo en auto-foto zijn optioneel en worden alleen getoond als je ze uploadt.")}</Text>
        <View style={styles.mediaUploadRow}>
          <View style={styles.mediaUploadCard}>
            <Pressable
              style={styles.mediaUploadPress}
              onPress={() => pickAndUploadMedia('profile')}
              disabled={mediaBusy}
            >
              {driverMedia?.profile_photo_url ? (
                <Image
                  source={{ uri: driverMedia.profile_photo_url }}
                  style={styles.mediaPreview}
                  resizeMode="contain"
                />
              ) : (
                <View style={[styles.mediaPreview, styles.mediaPreviewEmpty]}>
                  <Ionicons name="person" size={28} color={colors.muted} />
                </View>
              )}
            </Pressable>
            {driverMedia?.profile_photo_url ? (
              <View style={styles.photoActions}>
                <Pressable
                  onPress={() => openPhotoPreview(driverMedia.profile_photo_url || null)}
                  accessibilityRole="button"
                  accessibilityLabel={t("Profielfoto vergroten")}
                  hitSlop={8}
                >
                  <Ionicons name="search-outline" size={20} color={colors.text} />
                </Pressable>
                <Pressable
                  onPress={() =>
                    confirmRemovePhoto(t("De profielfoto wordt verwijderd."), () => void removeProfilePhoto())
                  }
                  disabled={mediaBusy}
                  accessibilityRole="button"
                  accessibilityLabel={t("Profielfoto verwijderen")}
                  hitSlop={8}
                >
                  <Ionicons name="trash-outline" size={20} color={colors.danger} />
                </Pressable>
              </View>
            ) : null}
            <Text style={styles.mediaUploadLabel}>
              {driverMedia?.has_profile_photo ? t("Profielfoto wijzigen") : t("Profielfoto (verplicht)")}
            </Text>
          </View>
          <View style={styles.mediaUploadCard}>
            <Pressable
              style={styles.mediaUploadPress}
              onPress={() => pickAndUploadMedia('logo')}
              disabled={mediaBusy}
            >
              {driverMedia?.company_logo_url ? (
                <Image
                  source={{ uri: driverMedia.company_logo_url }}
                  style={[styles.mediaPreview, styles.mediaPreviewTransparent]}
                  resizeMode="contain"
                />
              ) : (
                <View style={[styles.mediaPreview, styles.mediaPreviewEmpty]}>
                  <Ionicons name="business" size={28} color={colors.muted} />
                </View>
              )}
            </Pressable>
            {driverMedia?.company_logo_url ? (
              <View style={styles.photoActions}>
                <Pressable
                  onPress={() => openPhotoPreview(driverMedia.company_logo_url || null)}
                  accessibilityRole="button"
                  accessibilityLabel={t("Bedrijfslogo vergroten")}
                  hitSlop={8}
                >
                  <Ionicons name="search-outline" size={20} color={colors.text} />
                </Pressable>
                <Pressable
                  onPress={() =>
                    confirmRemovePhoto(t("Het bedrijfslogo wordt verwijderd."), () => void removeCompanyLogo())
                  }
                  disabled={mediaBusy}
                  accessibilityRole="button"
                  accessibilityLabel={t("Bedrijfslogo verwijderen")}
                  hitSlop={8}
                >
                  <Ionicons name="trash-outline" size={20} color={colors.danger} />
                </Pressable>
              </View>
            ) : null}
            <Text style={styles.mediaUploadLabel}>{t("Bedrijfslogo")}</Text>
          </View>
          <View ref={vehiclePhotoRef} collapsable={false} style={styles.mediaUploadCard}>
            <Pressable
              style={styles.mediaUploadPress}
              onPress={() => pickAndUploadMedia('vehicle')}
              disabled={mediaBusy}
            >
              {resolveMediaUrl(selectedVehicle?.photo_url) ? (
                <Image
                  source={{ uri: resolveMediaUrl(selectedVehicle?.photo_url) || '' }}
                  style={styles.mediaPreview}
                  resizeMode="contain"
                />
              ) : (
                <View style={[styles.mediaPreview, styles.mediaPreviewEmpty]}>
                  <Ionicons name="car" size={28} color={colors.muted} />
                </View>
              )}
            </Pressable>
            {resolveMediaUrl(selectedVehicle?.photo_url) ? (
              <View style={styles.photoActions}>
                <Pressable
                  onPress={() => openPhotoPreview(resolveMediaUrl(selectedVehicle?.photo_url))}
                  accessibilityRole="button"
                  accessibilityLabel={t("Auto-foto vergroten")}
                  hitSlop={8}
                >
                  <Ionicons name="search-outline" size={20} color={colors.text} />
                </Pressable>
                <Pressable
                  onPress={() =>
                    selectedVehicleId
                      ? confirmRemovePhoto(t("De auto-foto wordt verwijderd."), () =>
                          void removeVehiclePhoto(selectedVehicleId)
                        )
                      : undefined
                  }
                  disabled={mediaBusy || !selectedVehicleId}
                  accessibilityRole="button"
                  accessibilityLabel={t("Auto-foto verwijderen")}
                  hitSlop={8}
                >
                  <Ionicons name="trash-outline" size={20} color={colors.danger} />
                </Pressable>
              </View>
            ) : null}
            <Text style={styles.mediaUploadLabel}>
              {selectedVehicle ? `Auto · ${vehicleDisplayLabel(selectedVehicle)}` : t("Auto")}
            </Text>
          </View>
        </View>

        <View style={styles.profileDivider} />
        <View style={styles.fleetHeader}>
          <Text style={[styles.profileSection, styles.fleetHeaderTitle]}>{t("Voertuigen")}</Text>
          <Pressable
            onPress={() => openVehicleForm()}
            accessibilityRole="button"
            accessibilityLabel={t("Voertuig toevoegen")}
            hitSlop={8}
            style={styles.fleetAddPress}
          >
            <Text style={[styles.fleetAdd, { color: accentHex(accent) }]}>{t("Toevoegen")}</Text>
          </Pressable>
        </View>
        <Text style={styles.themeHint}>{t("Naam, type, kenteken en personenbereik. Een rit met meer dan 4 personen gaat alleen naar een voertuig van 5 t/m 8.")}</Text>
        {fleet.length === 0 ? (
          <Text style={styles.hint}>{t("Nog geen voertuigen.")}</Text>
        ) : (
          fleet.map((vehicle) => (
            <Pressable
              key={vehicle.id}
              style={styles.fleetRow}
              onPress={() => openVehicleForm(vehicle)}
              accessibilityRole="button"
              accessibilityLabel={`${vehicle.name || 'Voertuig'} bewerken`}
            >
              {resolveMediaUrl(vehicle.photo_url) ? (
                <Image
                  source={{ uri: resolveMediaUrl(vehicle.photo_url) || '' }}
                  style={styles.fleetPhoto}
                  resizeMode="contain"
                />
              ) : (
                <View style={[styles.fleetPhoto, styles.mediaPreviewEmpty]}>
                  <Ionicons name="car" size={18} color={colors.muted} />
                </View>
              )}
              <View style={{ flex: 1, minWidth: 0 }}>
                <Text style={styles.vehiclePlate} numberOfLines={1}>
                  {vehicle.name || vehicleDisplayLabel(vehicle)}
                </Text>
                <Text style={styles.themeHint} numberOfLines={1}>
                  {[vehicle.license_plate, vehicle.type_label || t("Auto"), vehicle.person_range_label || t("t/m 4 personen")]
                    .filter(Boolean)
                    .join(' · ')}
                </Text>
              </View>
              <Ionicons name="create-outline" size={18} color={colors.muted} />
            </Pressable>
          ))
        )}

        <View style={styles.profileDivider} />
        <Text style={styles.profileSection}>{t("Themakleur")}</Text>
        <View style={styles.accentRow}>
          {DRIVER_ACCENT_OPTIONS.map((item) => {
            const selected = accent === item.key;
            return (
              <Pressable
                key={item.key}
                onPress={() => chooseAccent(item.key)}
                accessibilityRole="button"
                accessibilityLabel={item.label}
                accessibilityState={{ selected }}
                style={[
                  styles.accentSwatch,
                  { backgroundColor: item.hex },
                  selected && styles.accentSwatchOn,
                ]}
              />
            );
          })}
        </View>

        <View style={styles.profileDivider} />
        <Text style={styles.profileSection}>{t("Weergave")}</Text>
        <Text style={styles.themeHint}>{t("Standaard volgt de app de light/dark-modus van je telefoon. Kies hieronder alleen als je dat wilt overschrijven.")}</Text>
        {THEME_OPTIONS.map((opt) => {
          const selected = preference === opt.key;
          return (
            <Pressable
              key={opt.key}
              onPress={() => setPreference(opt.key)}
              style={styles.themeRow}
              accessibilityRole="radio"
              accessibilityState={{ selected }}
            >
              <View style={[styles.themeRadio, selected && styles.themeRadioOn]} />
              <View style={{ flex: 1, minWidth: 0 }}>
                <Text style={styles.themeLabel}>{t(opt.label)}</Text>
                <Text style={styles.themeSub}>{t(opt.hint)}</Text>
              </View>
            </Pressable>
          );
        })}

        <View style={styles.profileDivider} />
        <Text style={styles.profileSection}>{t("Ritgeluid")}</Text>
        <Text style={styles.themeHint}>
          {t("Kies het geluid bij een nieuwe rit. Tik om te beluisteren.")}
        </Text>
        <View style={styles.toneRow}>
          {RIDE_ALERT_OPTIONS.map((opt) => {
            const selected = rideAlertTone === opt.key;
            return (
              <Pressable
                key={opt.key}
                onPress={() => {
                  setRideAlertTone(opt.key);
                  setActiveRideAlertTone(opt.key);
                  rememberRideAlertTone(opt.key);
                  playRideAlertTone(opt.key);
                  if (driverToken) {
                    void updateDriverRideAlertTone(driverToken, opt.key).catch(() => undefined);
                  }
                }}
                style={[styles.toneChip, selected && styles.toneChipOn]}
                accessibilityRole="radio"
                accessibilityState={{ selected }}
              >
                <Text style={[styles.toneChipText, selected && styles.toneChipTextOn]}>
                  {t(opt.label)}
                </Text>
              </Pressable>
            );
          })}
        </View>

        <DemoModeRow />
        <Pressable
          onPress={openHandleiding}
          style={[styles.guideLink, { borderColor: `${accentHex(accent)}66` }]}
        >
          <Text style={[styles.guideLinkTitle, { color: accentHex(accent) }]}>{t("Handleiding")}</Text>
          <Text style={[styles.guideLinkAction, { color: accentHex(accent) }]}>{t("Openen →")}</Text>
        </Pressable>
        <Pressable
          style={styles.logoutBtn}
          onPress={() => {
            resetDriverTabToTrips();
            void logout();
          }}
        >
          <Text style={styles.logoutBtnText}>{t("Uitloggen")}</Text>
        </Pressable>
      </Card>
    </ScrollView>
  );

  // Alleen een lopende (gestarte) rit bovenaan — geen geplande/verlopen ritten.
  const jumpRideTarget =
    activeForTrips && String(activeForTrips.status || '') === 'assigned' ? activeForTrips : null;
  const showActiveRideBar = !!jumpRideTarget && (showArchived || tab !== 'trips');
  const declinedOffersFiltered = declinedOffers.filter((o) =>
    rideMatchesKindFilter(o.ride, rideKindFilter)
  );
  const archivedOffersFiltered = archivedOffers.filter((o) =>
    rideMatchesKindFilter(o.ride, rideKindFilter)
  );
  const archivedCompletedRidesFiltered = archivedCompletedRides.filter((r) =>
    rideMatchesKindFilter(r, rideKindFilter)
  );
  const ownArchivedOverdueRidesFiltered = ownArchivedOverdueRides.filter((r) =>
    rideMatchesKindFilter(r, rideKindFilter)
  );
  const archiveKeys = [
    ...declinedOffers.map((o) => `declined-${o.id}`),
    ...archivedOffers.map((o) => `offer-${o.id}`),
    ...archivedCompletedRides.map((r) => `done-${r.id}`),
    ...ownArchivedOverdueRides.map((r) => `overdue-auto-${r.id}`),
  ];
  const archiveHasItems =
    declinedOffersFiltered.length > 0 ||
    archivedOffersFiltered.length > 0 ||
    archivedCompletedRidesFiltered.length > 0 ||
    ownArchivedOverdueRidesFiltered.length > 0;
  const archiveSeenSet = useMemo(() => new Set(archiveSeenKeys), [archiveSeenKeys]);
  const archiveBadgeCount = archiveKeys.filter((key) => !archiveSeenSet.has(key)).length;

  const archivePanel = (
    <ScrollView
      style={{ flex: 1 }}
      contentContainerStyle={styles.scroll}
      keyboardShouldPersistTaps="handled"
      nestedScrollEnabled
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={async () => {
            setRefreshing(true);
            await refresh();
            setRefreshing(false);
          }}
          tintColor={colors.text}
        />
      }
    >
      <ErrorText>{shownError}</ErrorText>
      <ScreenHeader title={t("Archief")} />
      <View style={styles.archiveSectionRow}>
        <Text style={styles.archiveSectionTitle}>{t("Gearchiveerde ritten")}</Text>
        <Pressable
          onPress={() => goToArchive(false)}
          hitSlop={8}
          accessibilityRole="button"
          accessibilityLabel={t("Terug naar ritten")}
          style={styles.archiveBackLinkPress}
        >
          <Text style={styles.archiveBackLink}>{t("Ritten →")}</Text>
        </Pressable>
      </View>
      <View style={styles.rideKindRow}>
        {RIDE_KIND_OPTIONS.map((opt) => {
          const active = rideKindFilter === opt.key;
          return (
            <Pressable
              key={opt.key}
              style={[styles.rideKindBtn, active && styles.rideKindBtnActive]}
              onPress={() => chooseRideKind(opt.key)}
              accessibilityRole="button"
              accessibilityState={{ selected: active }}
            >
              <Text style={[styles.rideKindText, active && styles.rideKindTextActive]}>
                {t(opt.label)}
              </Text>
            </Pressable>
          );
        })}
      </View>
      {archiveHasItems ? (
        <>
          {declinedOffersFiltered.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>{t("Afgewezen / geannuleerd")}</Text>
              {declinedOffersFiltered.map((offer) => (
                <DriverOfferCard
                  key={`declined-${offer.id}`}
                  offer={offer}
                  busy={busyId === offer.id}
                  onAccept={() => onAccept(offer.id)}
                  onDecline={() => onDecline(offer.id)}
                />
              ))}
            </>
          ) : null}
          {ownArchivedOverdueRidesFiltered.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>{t("Verlopen ritten")}</Text>
              {ownArchivedOverdueRidesFiltered.map((ride) => (
                <View key={`archived-overdue-auto-${ride.id}`}>
                  <DriverTripCard
                    ride={ride}
                    variant="overdue"
                    busy={busyRideId === ride.id}
                    onStart={() => onStartRide(ride.id)}
                    onOpenMaps={() => void openPickupMapsAndNotify(driverToken, ride)}
                    onProposePickup={() => openProposePickup(ride)}
                    onRelease={() => requestReleaseRide(ride.id)}
                    onCancel={
                      ride.can_cancel_with_reason
                        ? () => {
                            setSelectedCancelReason(null);
                            setCancelRideId(ride.id);
                          }
                        : undefined
                    }
                  />
                </View>
              ))}
            </>
          ) : null}
          {archivedCompletedRidesFiltered.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>{t("Afgeronde ritten")}</Text>
              {archivedCompletedRidesFiltered.map((ride) => (
                <DriverTripCard
                  key={`archived-done-${ride.id}`}
                  ride={ride}
                  variant="completed"
                  archived
                  onArchive={() => toggleCompletedArchive(ride.id)}
                />
              ))}
            </>
          ) : null}
          {archivedOffersFiltered.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>{t("Archief")}</Text>
              {archivedOffersFiltered.map((offer) => {
                const ride = offer.ride;
                const pickup = String(ride?.pickup_address || '').split(',')[0] || '—';
                const dropoff = String(ride?.dropoff_address || '').split(',')[0] || '—';
                return (
                  <Card key={`archived-${offer.id}`}>
                    <Text style={styles.archivedMeta}>
                      {ride?.pickup_at
                        ? new Date(ride.pickup_at).toLocaleString(dateLocale(), {
                            weekday: 'short',
                            day: 'numeric',
                            month: 'short',
                            hour: '2-digit',
                            minute: '2-digit',
                          })
                        : 'Gearchiveerd'}
                    </Text>
                    <Text style={styles.hint}>
                      {pickup} → {dropoff}
                    </Text>
                    {ride?.customer_name ? (
                      <Text style={styles.hint}>{ride.customer_name}</Text>
                    ) : null}
                  </Card>
                );
              })}
            </>
          ) : null}
        </>
      ) : (
        <Card>
          <Text style={styles.hint}>{t("Geen gearchiveerde of afgewezen ritten.")}</Text>
        </Card>
      )}
    </ScrollView>
  );

  let body: React.ReactNode = tripsPanel;
  if (showArchived) body = archivePanel;
  else if (tab === 'requests') body = requestsPanel;
  else if (tab === 'planning') body = planningPanel;
  else if (tab === 'earnings') {
    body = driverToken ? (
      <DriverEarningsPanel
        token={driverToken}
        canViewMonth={canViewMonthEarnings}
        completedRides={completedRides}
      />
    ) : (
      renderPlaceholder(t("Inkomsten"), t("Niet ingelogd."))
    );
  } else if (tab === 'profile') body = profilePanel;

  return (
    <DriverAccentProvider accent={accent}>
    <Screen style={{ paddingHorizontal: 0, paddingTop: APP_PAGE_TOP_INSET, paddingBottom: 0 }}>
      <ImageCropModal
        visible={!!cropUri && cropKind !== 'vehicle-draft'}
        uri={cropUri}
        sourceMimeType={cropMime}
        preferPng={cropKind === 'logo'}
        aspectRatio={cropKind === 'profile' ? 1 : 4 / 3}
        title={
          cropKind === 'profile'
            ? t("Profielfoto bijsnijden")
            : cropKind === 'logo'
              ? t("Logo bijsnijden")
              : t("Voertuigfoto bijsnijden")
        }
        onCancel={() => {
          setCropUri(null);
          setCropMime(null);
          setCropKind(null);
        }}
        onConfirm={(result) => {
          if (cropKind === 'vehicle-draft') {
            setVehicleDraft((current) => ({
              ...current,
              photoUri: result.uri,
              photoMime: result.mimeType,
            }));
            setCropUri(null);
            setCropMime(null);
            setCropKind(null);
            return;
          }
          void uploadCroppedMedia(result);
        }}
      />
      <AppModal
        visible={!!photoPreview}
        onRequestClose={() => setPhotoPreview(null)}
        panelStyle={styles.photoLightbox}
      >
        {photoPreview ? (
          <Image source={{ uri: photoPreview }} style={styles.photoLightboxImage} resizeMode="contain" />
        ) : null}
        <Pressable onPress={() => setPhotoPreview(null)} style={styles.vehicleModalCloseBtn}>
          <Text style={styles.vehicleModalCloseText}>{t("Sluiten")}</Text>
        </Pressable>
      </AppModal>
      <View style={styles.topBar}>
        <View style={styles.onlineBox}>
          <Text style={styles.onlineLabel}>{online ? t("Online") : t("Offline")}</Text>
          <Switch
            value={online}
            onValueChange={toggleOnline}
            trackColor={{ false: colors.border, true: colors.success }}
            style={styles.onlineSwitch}
          />
        </View>

        <View style={styles.logoCenter} pointerEvents="none">
          <AppBrandLogo
            bare
            uri={logoUri}
            fallbackLabel={company || t("Chauffeur")}
            accessibilityLabel={company || t("Bedrijfslogo")}
          />
        </View>

        <View style={styles.headerIcons}>
          <Pressable
            style={[styles.headerIconBtn, showArchived && styles.headerIconBtnActive]}
            onPress={() => goToArchive(!showArchived)}
            accessibilityLabel={
              archiveBadgeCount > 0 ? `Archief (${archiveBadgeCount})` : t("Archief")
            }
          >
            {archiveBadgeCount > 0 ? (
              <View style={styles.headerBadge}>
                <Text style={styles.headerBadgeText}>
                  {archiveBadgeCount > 9 ? '9+' : String(archiveBadgeCount)}
                </Text>
              </View>
            ) : null}
            <Ionicons
              name="archive-outline"
              size={22}
              color={showArchived ? accentHex(accent) : colors.muted}
            />
          </Pressable>
        </View>
      </View>
      <View style={styles.prefsRow}>
        <AppearanceControls />
      </View>

      <Pressable
        onPress={() => {
          if (!vehicleLocked) setVehiclePickerOpen(true);
        }}
        disabled={vehicleLocked}
        style={[styles.vehicleRow, needsVehicle && styles.vehicleRowNeeded]}
        accessibilityRole={vehicleLocked ? 'text' : 'button'}
        accessibilityLabel={
          vehicleLocked ? t("Toegewezen voertuig") : t("Voertuig wisselen")
        }
      >
        <Ionicons name="car-outline" size={20} color={colors.muted} />
        <Text style={styles.vehicleLine} numberOfLines={1}>
          {selectedVehicle ? (
            <Text style={styles.vehiclePlate}>
              {vehicleDisplayLabel(selectedVehicle)}
              {vehicleDisplayName(selectedVehicle) ? (
                <Text style={styles.vehicleBrand}>
                  {'  '}
                  {vehicleDisplayName(selectedVehicle)}
                </Text>
              ) : null}
              {vehicleLocked && assignedUntil ? (
                <Text style={styles.vehicleBrand}>{`  · tot ${assignedUntil}`}</Text>
              ) : null}
            </Text>
          ) : (
            <Text style={styles.vehicleBrand}>{t("Kies een voertuig…")}</Text>
          )}
        </Text>
        {!vehicleLocked ? (
          <Ionicons name="swap-horizontal" size={20} color={colors.muted} />
        ) : null}
      </Pressable>

      {showActiveRideBar && jumpRideTarget ? (
        <ActiveRideBar
          ride={jumpRideTarget}
          onPress={() => goToRide(jumpRideTarget.id)}
        />
      ) : null}

      <View style={styles.body}>{body}</View>

      <DriverTabBar
        active={tab}
        onChange={(key) => {
          goToArchive(false);
          goToTab(key);
        }}
        showEarnings={showEarnings}
        requestsBadge={offers.length}
        tripsBadge={jumpRideTarget && tab !== 'trips' ? 1 : 0}
        accent={accentHex(accent)}
      />

      <AppModal
        visible={reviewsOpen}
        onRequestClose={() => setReviewsOpen(false)}
        panelStyle={styles.reviewsModal}
      >
        <Text style={styles.pickerTitle}>{t("Beoordelingen")}</Text>
        {reviewsBusy ? <Text style={styles.hint}>{t("Laden…")}</Text> : null}
        {reviewsError ? <Text style={styles.reviewsError}>{reviewsError}</Text> : null}
        {!reviewsBusy && !reviewsError && reviews.length === 0 ? (
          <Text style={styles.hint}>{t("Nog geen beoordelingen.")}</Text>
        ) : null}
        <ScrollView
          style={styles.reviewsScroll}
          contentContainerStyle={styles.reviewsScrollContent}
          nestedScrollEnabled
          keyboardShouldPersistTaps="handled"
        >
          {reviews.map((review) => {
            const stars = Math.max(0, Math.min(5, Number(review.stars) || 0));
            const route = [review.pickup_address, review.dropoff_address].filter(Boolean).join(' → ');
            return (
              <View key={review.id} style={styles.reviewItem}>
                <View style={styles.reviewHead}>
                  <View style={styles.reviewStars}>
                    {[1, 2, 3, 4, 5].map((n) => (
                      <Ionicons
                        key={n}
                        name={n <= stars ? 'star' : 'star-outline'}
                        size={16}
                        color={REVIEW_STAR}
                      />
                    ))}
                  </View>
                  <Text style={styles.reviewDate}>{reviewDateLabel(review.created_at)}</Text>
                </View>
                {review.customer_name ? (
                  <Text style={styles.reviewName}>{review.customer_name}</Text>
                ) : null}
                {route ? <Text style={styles.reviewRoute}>{route}</Text> : null}
                {review.comment ? <Text style={styles.reviewComment}>{review.comment}</Text> : null}
              </View>
            );
          })}
        </ScrollView>
        <GhostButton title={t("Sluiten")} onPress={() => setReviewsOpen(false)} />
      </AppModal>

      <LimitedPhotoPickerModal
        visible={limitedPickerOpen && !vehicleFormOpen}
        onClose={() => finishLimitedPick(null)}
        onPick={finishLimitedPick}
      />

      <AppModal
        visible={vehiclePhotoAsk != null}
        onRequestClose={() => setVehiclePhotoAsk(null)}
        panelStyle={styles.centeredSheet}
      >
        <Text style={styles.pickerTitle}>{t("Foto van dit voertuig")}</Text>
        <Text style={styles.noticeBody}>
          Wil je een andere foto uploaden voor{' '}
          {vehiclePhotoAsk ? vehicleDisplayLabel(vehiclePhotoAsk) : t("dit voertuig")}?
        </Text>
        <View style={styles.cancelActions}>
          <Pressable
            style={[styles.cancelActionBtn, styles.cancelActionGhost]}
            onPress={() => setVehiclePhotoAsk(null)}
          >
            <Text style={styles.cancelActionGhostText}>{t("Nee")}</Text>
          </Pressable>
          <Pressable
            style={[styles.cancelActionBtn, styles.cancelActionPrimary]}
            onPress={() => {
              setVehiclePhotoAsk(null);
              goToTab('profile');
              setFocusVehiclePhoto(true);
            }}
          >
            <Text style={styles.cancelActionPrimaryText}>{t("Ja")}</Text>
          </Pressable>
        </View>
      </AppModal>

      <AppModal
        visible={vehiclePickerOpen}
        onRequestClose={() => setVehiclePickerOpen(false)}
        panelStyle={styles.vehicleModalCard}
      >
        <Text style={styles.pickerTitle}>{t("Voertuig kiezen")}</Text>
        {vehicles.length === 0 ? (
          fleet.length === 0 ? (
            <>
              <Text style={styles.hint}>{t("Nog geen voertuig. Voeg er een toe om online te gaan.")}</Text>
              <Pressable
                onPress={() => openVehicleForm()}
                style={[styles.vehicleModalAdminBtn, { backgroundColor: accentHex(accent) }]}
                accessibilityRole="button"
                accessibilityLabel={t("Voertuig toevoegen")}
              >
                <Text style={styles.vehicleModalAdminText}>{t("Voertuig toevoegen")}</Text>
              </Pressable>
            </>
          ) : (
            <Text style={styles.hint}>{t("Alle voertuigen zijn nu in gebruik.")}</Text>
          )
        ) : (
          vehicles.map((v) => {
            const active = v.id === selectedVehicleId;
            return (
              <Pressable
                key={v.id}
                onPress={() => selectVehicle(v.id)}
                style={[styles.pickerItem, active && styles.pickerItemActive]}
              >
                <Ionicons name="car-outline" size={18} color={colors.muted} />
                <View style={{ flex: 1, minWidth: 0 }}>
                  <Text style={styles.vehiclePlate} numberOfLines={1}>
                    {vehicleDisplayLabel(v)}
                    {vehicleDisplayName(v) ? (
                      <Text style={styles.vehicleBrand}>
                        {'  '}
                        {vehicleDisplayName(v)}
                      </Text>
                    ) : null}
                  </Text>
                </View>
                {active ? (
                  <Ionicons name="checkmark-circle" size={20} color={colors.success} />
                ) : null}
              </Pressable>
            );
          })
        )}
        {vehicles.length > 0 || fleet.length > 0 ? (
          <Pressable
            onPress={() => openVehicleForm()}
            style={styles.vehicleModalCloseBtn}
            accessibilityRole="button"
            accessibilityLabel={t("Voertuig toevoegen")}
          >
            <Text style={[styles.vehicleModalCloseText, { color: accentHex(accent) }]}>{t("Voertuig toevoegen")}</Text>
          </Pressable>
        ) : null}
        <Pressable
          onPress={() => setVehiclePickerOpen(false)}
          style={styles.vehicleModalCloseBtn}
          accessibilityRole="button"
          accessibilityLabel={t("Sluiten")}
        >
          <Text style={styles.vehicleModalCloseText}>{t("Sluiten")}</Text>
        </Pressable>
      </AppModal>

      <AppModal
        visible={vehicleFormOpen}
        scrollEnabled={!(cropKind === 'vehicle-draft' && cropUri)}
        dismissOnBackdrop={false}
        showsVerticalScrollIndicator={false}
        keyboardAccessory={
          vehicleFormOpen ? (
            <KeyboardDismissAccessory
              nativeIDs={[VEHICLE_FORM_NAME_ACCESSORY_ID, VEHICLE_FORM_PLATE_ACCESSORY_ID]}
            />
          ) : null
        }
        onRequestClose={() => {
          if (vehicleFormBusy) return;
          if (cropKind === 'vehicle-draft') {
            setCropUri(null);
            setCropMime(null);
            setCropKind(null);
            return;
          }
          if (vehiclePhotoZoom) {
            setVehiclePhotoZoom(false);
            return;
          }
          if (vehicleConfirmDelete) {
            setVehicleConfirmDelete(false);
            return;
          }
          Keyboard.dismiss();
        }}
        dismissDisabled={vehicleFormBusy}
        panelStyle={styles.centeredSheet}
      >
        {limitedPickerOpen ? (
          <LimitedPhotoPickerModal
            embedded
            visible
            onClose={() => finishLimitedPick(null)}
            onPick={finishLimitedPick}
          />
        ) : cropKind === 'vehicle-draft' && cropUri ? (
          <ImageCropModal
            embedded
            visible
            uri={cropUri}
            sourceMimeType={cropMime}
            aspectRatio={4 / 3}
            maxFrameHeight={180}
            title={t("Voertuigfoto bijsnijden")}
            onCancel={() => {
              setCropUri(null);
              setCropMime(null);
              setCropKind(null);
            }}
            onConfirm={(result) => {
              setVehicleDraft((current) => ({
                ...current,
                photoUri: result.uri,
                photoMime: result.mimeType,
              }));
              setCropUri(null);
              setCropMime(null);
              setCropKind(null);
            }}
          />
        ) : vehiclePhotoZoom && (vehicleDraft.photoUri || resolveMediaUrl(vehicleDraft.existingPhoto)) ? (
          <>
            <Text style={styles.pickerTitle}>{t("Voertuigfoto")}</Text>
            <Image
              source={{ uri: vehicleDraft.photoUri || resolveMediaUrl(vehicleDraft.existingPhoto) || '' }}
              style={styles.photoLightboxImage}
              resizeMode="contain"
            />
            <Pressable
              onPress={() => setVehiclePhotoZoom(false)}
              style={styles.vehicleModalCloseBtn}
              accessibilityRole="button"
              accessibilityLabel={t("Sluiten")}
            >
              <Text style={styles.vehicleModalCloseText}>{t("Sluiten")}</Text>
            </Pressable>
          </>
        ) : vehicleConfirmDelete ? (
          <>
            <Text style={styles.pickerTitle}>{t("Voertuig verwijderen")}</Text>
            <Text style={styles.hint}>
              Weet je zeker dat je {vehicleDraft.name.trim() || t("dit voertuig")} wilt verwijderen?
            </Text>
            <View style={styles.cancelActions}>
              <Pressable
                style={[styles.cancelActionBtn, styles.cancelActionGhost]}
                onPress={() => setVehicleConfirmDelete(false)}
                disabled={vehicleFormBusy}
              >
                <Text style={styles.cancelActionGhostText}>{t("Annuleren")}</Text>
              </Pressable>
              <Pressable
                style={[styles.cancelActionBtn, styles.cancelActionPrimary]}
                onPress={() => void confirmDeleteVehicle()}
                disabled={vehicleFormBusy}
              >
                <Text style={styles.cancelActionPrimaryText}>
                  {vehicleFormBusy ? t("Verwijderen…") : t("Verwijderen")}
                </Text>
              </Pressable>
            </View>
          </>
        ) : (
          <>
        <Text style={styles.pickerTitle}>{vehicleDraft.id ? t("Voertuig bewerken") : t("Voertuig toevoegen")}</Text>
        <View style={styles.fleetPhotoPick}>
          <Pressable
            onPress={() => void pickVehicleDraftPhoto()}
            style={styles.fleetPhotoPress}
            accessibilityRole="button"
          >
            {vehicleDraft.photoUri || resolveMediaUrl(vehicleDraft.existingPhoto) ? (
              <Image
                key={vehicleDraft.photoUri || vehicleDraft.existingPhoto || 'vehicle-photo'}
                source={{ uri: vehicleDraft.photoUri || resolveMediaUrl(vehicleDraft.existingPhoto) || '' }}
                style={styles.fleetPhotoLarge}
                resizeMode="contain"
              />
            ) : (
              <View style={[styles.fleetPhotoLarge, styles.mediaPreviewEmpty]}>
                <Ionicons name="image-outline" size={28} color={colors.muted} />
                <Text style={styles.fleetPhotoEmptyLabel}>{t("Foto, optioneel")}</Text>
              </View>
            )}
          </Pressable>
          {vehicleDraft.photoUri || resolveMediaUrl(vehicleDraft.existingPhoto) ? (
            <View style={styles.photoActions}>
              <Pressable
                onPress={() => setVehiclePhotoZoom(true)}
                accessibilityRole="button"
                accessibilityLabel={t("Voertuigfoto vergroten")}
                hitSlop={8}
              >
                <Ionicons name="search-outline" size={20} color={colors.text} />
              </Pressable>
              <Pressable
                onPress={() =>
                  confirmRemovePhoto(t("De voertuigfoto wordt verwijderd."), removeVehicleDraftPhoto)
                }
                disabled={vehicleFormBusy || mediaBusy}
                accessibilityRole="button"
                accessibilityLabel={t("Voertuigfoto verwijderen")}
                hitSlop={8}
              >
                <Ionicons name="trash-outline" size={20} color={colors.danger} />
              </Pressable>
            </View>
          ) : null}
        </View>
        <Text style={styles.fleetFieldLabel}>{t("Naam")}</Text>
        <TextInput
          value={vehicleDraft.name}
          onChangeText={(name) => setVehicleDraft((current) => ({ ...current, name }))}
          placeholder={t("bijv. Auto 1")}
          placeholderTextColor={colors.muted}
          style={styles.fleetInput}
          inputAccessoryViewID={VEHICLE_FORM_NAME_ACCESSORY_ID}
        />
        <Text style={styles.fleetFieldLabel}>{t("Type")}</Text>
        <View style={styles.fleetChips}>
          {[
            { key: 'car', label: t("Auto") },
            { key: 'van', label: 'Busje' },
            { key: 'bus', label: 'Bus' },
          ].map((item) => {
            const selected = vehicleDraft.type === item.key;
            return (
              <Pressable
                key={item.key}
                onPress={() =>
                  setVehicleDraft((current) => ({
                    ...current,
                    type: item.key,
                    person_range: personRangeForVehicleType(item.key),
                  }))
                }
                style={[styles.fleetChip, selected && { backgroundColor: accentHex(accent), borderColor: accentHex(accent) }]}
              >
                <Text style={[styles.fleetChipText, selected && styles.fleetChipTextOn]}>{item.label}</Text>
              </Pressable>
            );
          })}
        </View>
        <Text style={styles.fleetFieldLabel}>{t("Kenteken")}</Text>
        <DutchPlateInput
          value={vehicleDraft.license_plate}
          onChangeText={(license_plate) => setVehicleDraft((current) => ({ ...current, license_plate }))}
          inputAccessoryViewID={VEHICLE_FORM_PLATE_ACCESSORY_ID}
        />
        <Text style={styles.fleetFieldLabel}>{t("Personenbereik")}</Text>
        <View style={styles.fleetChips}>
          {[
            { key: '1-4', label: t("t/m 4 personen") },
            { key: '5-8', label: t("5 t/m 8 personen") },
            { key: '9+', label: t("meer dan 8 personen") },
          ].map((item) => {
            const selected = vehicleDraft.person_range === item.key;
            return (
              <Pressable
                key={item.key}
                onPress={() =>
                  setVehicleDraft((current) => ({
                    ...current,
                    person_range: item.key,
                    type: vehicleTypeForPersonRange(item.key),
                  }))
                }
                style={[styles.fleetChip, selected && { backgroundColor: accentHex(accent), borderColor: accentHex(accent) }]}
              >
                <Text style={[styles.fleetChipText, selected && styles.fleetChipTextOn]}>{item.label}</Text>
              </Pressable>
            );
          })}
        </View>
        <Text style={styles.themeHint}>{t("Bepaalt welke standaardtarievenset wordt gebruikt.")}</Text>
        {vehicleFormError ? <Text style={styles.fleetError}>{vehicleFormError}</Text> : null}
        <Pressable
          onPress={() => void saveVehicleDraft()}
          disabled={vehicleFormBusy}
          style={[styles.vehicleModalAdminBtn, { backgroundColor: accentHex(accent) }, vehicleFormBusy && { opacity: 0.6 }]}
        >
          <Text style={styles.vehicleModalAdminText}>{vehicleFormBusy ? t("Opslaan…") : t("Opslaan")}</Text>
        </Pressable>
        {vehicleDraft.id ? (
          <Pressable
            onPress={() => setVehicleConfirmDelete(true)}
            disabled={vehicleFormBusy}
            style={styles.vehicleModalCloseBtn}
          >
            <Text style={[styles.vehicleModalCloseText, { color: colors.danger }]}>{t("Verwijderen")}</Text>
          </Pressable>
        ) : null}
        <Pressable
          onPress={() => {
            setVehicleConfirmDelete(false);
            setVehicleFormOpen(false);
          }}
          disabled={vehicleFormBusy}
          style={styles.vehicleModalCloseBtn}
        >
          <Text style={styles.vehicleModalCloseText}>{t("Annuleren")}</Text>
        </Pressable>
          </>
        )}
      </AppModal>

      <AppModal
        visible={cancelRideId != null}
        onRequestClose={() => {
          if (!cancelBusy) {
            setCancelRideId(null);
            setSelectedCancelReason(null);
          }
        }}
        dismissDisabled={cancelBusy}
        panelStyle={styles.centeredSheet}
      >
        <Text style={styles.pickerTitle}>{t("Rit annuleren")}</Text>
        <Text style={styles.hint}>{t("Kies een reden. Deze melding wordt getoond bij de klant.")}</Text>
        <ScrollView style={styles.cancelReasonList} keyboardShouldPersistTaps="handled">
          {cancelReasons.map((reason) => {
            const active = selectedCancelReason === reason.code;
            return (
              <Pressable
                key={reason.code}
                onPress={() => setSelectedCancelReason(reason.code)}
                style={[styles.cancelReasonItem, active && styles.cancelReasonItemActive]}
                disabled={cancelBusy}
              >
                <View style={{ flex: 1, minWidth: 0 }}>
                  <Text style={styles.cancelReasonLabel}>{reason.label}</Text>
                  <Text style={styles.cancelReasonMessage}>{reason.message}</Text>
                </View>
                {active ? (
                  <Ionicons name="checkmark-circle" size={20} color={accentHex(accent)} />
                ) : null}
              </Pressable>
            );
          })}
        </ScrollView>
        <View style={styles.cancelActions}>
          <Pressable
            style={[styles.cancelActionBtn, styles.cancelActionGhost]}
            disabled={cancelBusy}
            onPress={() => {
              setCancelRideId(null);
              setSelectedCancelReason(null);
            }}
          >
            <Text style={styles.cancelActionGhostText}>{t("Terug")}</Text>
          </Pressable>
          <Pressable
            style={[
              styles.cancelActionBtn,
              styles.cancelActionPrimary,
              !selectedCancelReason && styles.cancelActionDisabled,
            ]}
            disabled={cancelBusy || !selectedCancelReason}
            onPress={() => {
              if (cancelInFlightRef.current) return;
              void confirmCancelRide();
            }}
          >
            <Text style={styles.cancelActionPrimaryText}>
              {cancelBusy ? t("Bezig…") : t("Bevestigen")}
            </Text>
          </Pressable>
        </View>
      </AppModal>

      <AppModal
        visible={proposeRideId != null}
        onRequestClose={() => {
          if (!proposeBusy) setProposeRideId(null);
        }}
        dismissDisabled={proposeBusy}
        panelStyle={styles.centeredSheet}
      >
        <Text style={styles.pickerTitle}>{t("Nieuw ophaalmoment")}</Text>
        <Text style={styles.hint}>{t("De klant krijgt dit tijdstip via WhatsApp ter goedkeuring.")}</Text>
        <PickupAtField value={proposePickupAt} onChange={setProposePickupAt} embedded />
        <View style={styles.cancelActions}>
          <Pressable
            style={[styles.cancelActionBtn, styles.cancelActionGhost]}
            disabled={proposeBusy}
            onPress={() => setProposeRideId(null)}
          >
            <Text style={styles.cancelActionGhostText}>{t("Terug")}</Text>
          </Pressable>
          <Pressable
            style={[styles.cancelActionBtn, styles.cancelActionPrimary]}
            disabled={proposeBusy}
            onPress={() => confirmProposePickup()}
          >
            <Text style={styles.cancelActionPrimaryText}>
              {proposeBusy ? t("Versturen…") : t("Voorstellen")}
            </Text>
          </Pressable>
        </View>
      </AppModal>

      <AppModal
        visible={pinRideId != null}
        onRequestClose={() => {
          if (pinBusy) return;
          setPinRideId(null);
          setPickupPin('');
          setPinFormError(null);
        }}
        dismissDisabled={pinBusy}
        panelStyle={styles.centeredSheet}
      >
        <Text style={styles.pickerTitle}>{t("Ophaalcode")}</Text>
        <Text style={styles.noticeBody}>{t("Vraag de 4-cijferige code aan de klant en vul die hier in. Pas daarna start de rit.")}</Text>
        <TextInput
          value={pickupPin}
          onChangeText={(v) => {
            setPickupPin(v.replace(/\D/g, '').slice(0, 4));
            if (pinFormError) setPinFormError(null);
          }}
          keyboardType="number-pad"
          maxLength={4}
          placeholder="••••"
          placeholderTextColor={colors.muted}
          style={styles.pinInput}
          editable={!pinBusy}
          autoFocus
        />
        {pinFormError ? <Text style={[styles.noticeBody, { color: colors.danger }]}>{pinFormError}</Text> : null}
        <View style={styles.cancelActions}>
          <Pressable
            style={[styles.cancelActionBtn, styles.cancelActionGhost]}
            disabled={pinBusy}
            onPress={() => {
              setPinRideId(null);
              setPickupPin('');
              setPinFormError(null);
            }}
          >
            <Text style={styles.cancelActionGhostText}>{t("Annuleren")}</Text>
          </Pressable>
          <Pressable
            style={[styles.cancelActionBtn, styles.cancelActionPrimary, pinBusy && { opacity: 0.55 }]}
            disabled={pinBusy || pinRideId == null}
            onPress={confirmPickupPin}
          >
            <Text style={styles.cancelActionPrimaryText}>
              {pinBusy ? t("Bezig…") : t("Start rit")}
            </Text>
          </Pressable>
        </View>
      </AppModal>

      <AppModal
        visible={flowMessage != null && pinRideId == null}
        onRequestClose={() => setFlowMessage(null)}
        panelStyle={styles.centeredSheet}
      >
        <Text style={styles.noticeBody}>{flowMessage}</Text>
        <View style={styles.cancelActions}>
          <Pressable
            style={[styles.cancelActionBtn, styles.cancelActionGhost]}
            onPress={() => setFlowMessage(null)}
          >
            <Text style={styles.cancelActionGhostText}>{t("Sluiten")}</Text>
          </Pressable>
        </View>
      </AppModal>

      <AppModal
        visible={rideNotice != null}
        onRequestClose={() => setRideNotice(null)}
        panelStyle={styles.centeredSheet}
      >
        <Text style={styles.pickerTitle}>{rideNotice?.title}</Text>
        <Text style={styles.noticeBody}>{rideNotice?.body}</Text>
        <View style={styles.cancelActions}>
          <Pressable
            style={[styles.cancelActionBtn, styles.cancelActionGhost]}
            onPress={() => setRideNotice(null)}
          >
            <Text style={styles.cancelActionGhostText}>{t("Sluiten")}</Text>
          </Pressable>
          <Pressable
            style={[styles.cancelActionBtn, styles.cancelActionPrimary]}
            onPress={() => {
              const notice = rideNotice;
              setRideNotice(null);
              if (!notice) return;
              if (notice.rideId) goToRide(notice.rideId);
              else goToTab('trips');
            }}
          >
            <Text style={styles.cancelActionPrimaryText}>{rideNotice?.primaryLabel}</Text>
          </Pressable>
        </View>
      </AppModal>
      {driverToken && settleRide ? (
        <DriverSettleRideModal
          visible
          ride={settleRide}
          token={driverToken}
          onClose={() => setSettleRideId(null)}
          onCompleted={async () => {
            setSettleRideId(null);
            await refresh();
          }}
          onRideUpdated={patchTripRide}
        />
      ) : null}
    </Screen>
    </DriverAccentProvider>
  );
}

function makeStyles(colors: ColorPalette, accentHex: string) {
  return StyleSheet.create({
    topBar: {
      flexDirection: 'row',
      alignItems: 'center',
      paddingHorizontal: 16,
      paddingTop: 2,
      paddingBottom: 2,
      minHeight: APP_LOGO_BAR_MIN_HEIGHT,
    },
    onlineBox: {
      width: 72,
      alignItems: 'flex-start',
      zIndex: 2,
    },
    onlineLabel: {
      color: colors.muted,
      fontSize: 11,
      marginBottom: 2,
      fontWeight: '600',
    },
    onlineSwitch: {
      transform: [{ scaleX: 0.78 }, { scaleY: 0.78 }],
      marginLeft: -2,
    },
    logoCenter: {
      position: 'absolute',
      left: 72,
      right: 72,
      top: 0,
      bottom: 0,
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 1,
    },
    prefsRow: {
      flexDirection: 'row',
      justifyContent: 'flex-end',
      paddingHorizontal: 12,
      paddingBottom: 2,
    },
    headerIcons: {
      marginLeft: 'auto',
      minWidth: 72,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'flex-end',
      gap: 2,
      zIndex: 3,
    },
    headerIconBtn: {
      width: 36,
      height: 32,
      alignItems: 'center',
      justifyContent: 'center',
      borderRadius: 10,
      position: 'relative',
      overflow: 'visible',
    },
    headerIconBtnActive: {
      backgroundColor: hexAlpha(accentHex, 0.16),
    },
    headerBadge: {
      position: 'absolute',
      top: 0,
      right: 0,
      minWidth: 16,
      height: 16,
      borderRadius: 999,
      alignItems: 'center',
      justifyContent: 'center',
      paddingHorizontal: 3,
      zIndex: 2,
      backgroundColor: '#EF4444',
    },
    headerBadgeText: {
      fontSize: 9,
      fontWeight: '800',
      color: '#FFFFFF',
    },
    archiveSectionRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      alignItems: 'center',
      columnGap: 12,
      rowGap: 2,
      marginTop: 0,
      marginBottom: 8,
    },
    archiveSectionTitle: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '700',
      letterSpacing: 0,
      flexShrink: 1,
    },
    archiveBackLinkPress: {
      marginLeft: 'auto',
    },
    archiveBackLink: {
      color: colors.primary,
      fontSize: 13,
      fontWeight: '700',
    },
    archivedMeta: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '600',
      marginBottom: 4,
    },
    rideKindRow: {
      flexDirection: 'row',
      borderRadius: 999,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      overflow: 'hidden',
      marginBottom: 12,
      backgroundColor: colors.card,
    },
    rideKindBtn: { flex: 1, paddingVertical: 8, alignItems: 'center' },
    rideKindBtnActive: { backgroundColor: accentHex },
    rideKindText: { color: colors.muted, fontSize: 13, fontWeight: '700' },
    rideKindTextActive: { color: '#fff' },
    vehicleRow: {
      marginHorizontal: 20,
      marginBottom: 8,
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 12,
      paddingHorizontal: 12,
      paddingVertical: 10,
      backgroundColor: colors.card,
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
    },
    vehicleRowNeeded: {
      borderColor: accentHex,
      borderWidth: 1.5,
    },
    vehicleLine: {
      flex: 1,
      minWidth: 0,
    },
    vehiclePlate: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '700',
    },
    vehicleBrand: {
      color: colors.muted,
      fontSize: 14,
      fontWeight: '500',
    },
    body: {
      flex: 1,
      minHeight: 0,
    },
    scroll: {
      paddingHorizontal: 20,
      paddingBottom: 32,
      paddingTop: 4,
    },
    periodToggle: {
      flexDirection: 'row',
      borderRadius: 999,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      overflow: 'hidden',
      backgroundColor: colors.card,
    },
    periodBtn: {
      paddingHorizontal: 14,
      paddingVertical: 8,
      minWidth: 64,
      alignItems: 'center',
    },
    periodBtnActive: {
      backgroundColor: accentHex,
    },
    periodBtnText: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '700',
    },
    periodBtnTextActive: {
      color: '#fff',
    },
    sectionLabel: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '700',
      textTransform: 'uppercase',
      letterSpacing: 0.4,
      marginBottom: 8,
      marginTop: 4,
    },
    emptyTitle: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '700',
      marginBottom: 6,
    },
    hint: { color: colors.muted, fontSize: 14, lineHeight: 20 },
    offerTitle: { color: colors.text, fontSize: 16, fontWeight: '700', marginBottom: 4 },
    pickerOverlay: {
      flex: 1,
      justifyContent: 'flex-end',
    },
    pickerBackdrop: {
      ...StyleSheet.absoluteFill,
      backgroundColor: 'rgba(15, 23, 42, 0.72)',
    },
    pickerSheet: {
      backgroundColor: colors.card,
      borderTopLeftRadius: 18,
      borderTopRightRadius: 18,
      borderWidth: 1,
      borderColor: colors.border,
      paddingHorizontal: 16,
      paddingTop: 16,
      paddingBottom: 28,
      maxHeight: '70%',
    },
    vehicleModalCard: {
      maxHeight: '70%',
    },
    centeredSheet: {
      maxHeight: '80%',
    },
    vehicleModalAdminBtn: {
      marginTop: 14,
      borderRadius: 12,
      paddingVertical: 12,
      alignItems: 'center',
    },
    vehicleModalAdminText: {
      color: '#fff',
      fontSize: 15,
      fontWeight: '700',
    },
    vehicleModalCloseBtn: {
      marginTop: 10,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: colors.border,
      paddingVertical: 12,
      alignItems: 'center',
    },
    vehicleModalCloseText: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '600',
    },
    fleetHeader: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      marginBottom: 10,
      gap: 12,
    },
    fleetHeaderTitle: {
      marginBottom: 0,
      flexShrink: 1,
    },
    fleetAddPress: {
      justifyContent: 'center',
      paddingVertical: 0,
    },
    fleetAdd: {
      fontSize: 12,
      fontWeight: '700',
      letterSpacing: 0.5,
      lineHeight: 16,
    },
    fleetRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
      paddingVertical: 8,
    },
    fleetPhoto: {
      width: 44,
      height: 44,
      borderRadius: 10,
      alignItems: 'center',
      justifyContent: 'center',
    },
    fleetPhotoPick: {
      alignSelf: 'stretch',
      width: '100%',
      marginBottom: 8,
    },
    fleetPhotoPress: {
      width: '100%',
    },
    fleetPhotoLarge: {
      width: '100%',
      height: 96,
      borderRadius: 12,
      backgroundColor: colors.inputBg,
      alignItems: 'center',
      justifyContent: 'center',
    },
    fleetPhotoEmptyLabel: {
      color: colors.muted,
      fontSize: 13,
      textAlign: 'center',
      paddingHorizontal: 22,
      marginTop: 4,
    },
    photoActions: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      gap: 18,
      marginTop: 8,
    },
    photoLightbox: {
      width: '100%',
      maxWidth: 420,
    },
    photoLightboxImage: {
      width: '100%',
      height: 360,
      borderRadius: 12,
      backgroundColor: colors.inputBg,
    },
    fleetFieldLabel: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '700',
      marginTop: 8,
      marginBottom: 4,
    },
    fleetInput: {
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 10,
      color: colors.text,
      backgroundColor: colors.inputBg,
      paddingHorizontal: 12,
      paddingVertical: 10,
      fontSize: 15,
    },
    fleetChips: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 8,
    },
    fleetChip: {
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 999,
      paddingHorizontal: 12,
      paddingVertical: 8,
    },
    fleetChipText: {
      color: colors.text,
      fontSize: 13,
      fontWeight: '600',
    },
    fleetChipTextOn: {
      color: '#fff',
    },
    fleetError: {
      color: colors.danger,
      fontSize: 13,
      marginTop: 8,
    },
    pickerTitle: {
      color: colors.text,
      fontSize: 17,
      fontWeight: '700',
      marginBottom: 12,
    },
    noticeBody: {
      color: colors.text,
      fontSize: 16,
      lineHeight: 23,
      marginBottom: 16,
    },
    pinInput: {
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 12,
      backgroundColor: colors.inputBg,
      color: colors.text,
      fontSize: 28,
      fontWeight: '700',
      letterSpacing: 10,
      textAlign: 'center',
      paddingVertical: 14,
      marginBottom: 10,
    },
    pickerItem: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
      paddingVertical: 12,
      paddingHorizontal: 10,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      marginBottom: 8,
    },
    pickerItemActive: {
      borderColor: colors.success,
      backgroundColor: 'rgba(34,197,94,0.08)',
    },
    cancelRideBtn: {
      marginTop: 14,
      borderRadius: 12,
      borderWidth: 1.5,
      borderColor: colors.danger,
      paddingVertical: 12,
      alignItems: 'center',
    },
    cancelRideBtnText: {
      color: colors.danger,
      fontWeight: '800',
      fontSize: 13,
      textTransform: 'uppercase',
    },
    cancelReasonList: {
      maxHeight: 320,
      marginTop: 8,
    },
    cancelReasonItem: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: 10,
      paddingVertical: 12,
      paddingHorizontal: 12,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      marginBottom: 8,
    },
    cancelReasonItemActive: {
      borderColor: accentHex,
      backgroundColor: hexAlpha(accentHex, 0.1),
    },
    cancelReasonLabel: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '700',
      marginBottom: 2,
    },
    cancelReasonMessage: {
      color: colors.muted,
      fontSize: 12,
      lineHeight: 17,
    },
    cancelActions: {
      flexDirection: 'row',
      gap: 8,
      marginTop: 12,
    },
    cancelActionBtn: {
      flex: 1,
      minHeight: 46,
      borderRadius: 12,
      alignItems: 'center',
      justifyContent: 'center',
    },
    cancelActionGhost: {
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
    },
    cancelActionGhostText: {
      color: colors.text,
      fontWeight: '700',
    },
    cancelActionPrimary: {
      backgroundColor: accentHex,
    },
    cancelActionPrimaryText: {
      color: '#fff',
      fontWeight: '800',
    },
    cancelActionDisabled: {
      opacity: 0.45,
    },
    profileName: {
      color: colors.text,
      fontSize: 18,
      fontWeight: '700',
      marginBottom: 16,
    },
    profileRow: {
      marginBottom: 14,
    },
    profileLabel: {
      color: colors.muted,
      fontSize: 11,
      fontWeight: '700',
      letterSpacing: 0.6,
      textTransform: 'uppercase',
      marginBottom: 3,
    },
    ratingValue: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 6,
      flexShrink: 1,
    },
    reviewsModal: {
      maxHeight: '80%',
      paddingHorizontal: 18,
      paddingTop: 8,
      paddingBottom: 12,
    },
    reviewsScroll: {
      maxHeight: 420,
    },
    reviewsScrollContent: {
      paddingBottom: 8,
      gap: 12,
    },
    reviewsError: {
      color: '#F87171',
      fontSize: 14,
      marginBottom: 8,
    },
    reviewItem: {
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: colors.border,
      paddingTop: 10,
      gap: 4,
    },
    reviewHead: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: 8,
    },
    reviewStars: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 2,
    },
    reviewDate: {
      color: colors.muted,
      fontSize: 12,
    },
    reviewName: {
      color: colors.text,
      fontSize: 14,
      fontWeight: '700',
    },
    reviewRoute: {
      color: colors.muted,
      fontSize: 13,
      lineHeight: 18,
    },
    reviewComment: {
      color: colors.text,
      fontSize: 14,
      lineHeight: 20,
    },
    profileValue: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '600',
    },
    profileValueMuted: {
      color: colors.muted,
      fontStyle: 'italic',
      fontWeight: '500',
    },
    profileIbanHint: {
      color: colors.muted,
      fontSize: 13,
      lineHeight: 18,
      marginTop: 6,
    },
    profileIbanAction: {
      color: colors.primary,
      fontSize: 14,
      fontWeight: '700',
      marginTop: 8,
    },
    profileIbanSave: {
      marginTop: 10,
      minHeight: 44,
      borderRadius: 12,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: colors.primary,
    },
    profileIbanSaveText: {
      color: '#fff',
      fontWeight: '800',
    },
    profileDivider: {
      height: StyleSheet.hairlineWidth,
      backgroundColor: hexAlpha(accentHex, 0.35),
      marginVertical: 14,
    },
    profileSection: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '700',
      letterSpacing: 0.5,
      textTransform: 'uppercase',
      marginBottom: 10,
    },
    mediaUploadRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 10,
      marginBottom: 4,
    },
    mediaUploadCard: {
      width: '30%',
      minWidth: 96,
      flexGrow: 1,
      alignItems: 'center',
      gap: 6,
    },
    mediaUploadPress: {
      width: '100%',
      alignItems: 'center',
      gap: 6,
    },
    mediaPreview: {
      width: '100%',
      aspectRatio: 1,
      borderRadius: 12,
      backgroundColor: colors.inputBg,
      borderWidth: 1,
      borderColor: colors.border,
    },
    mediaPreviewTransparent: {
      backgroundColor: 'transparent',
    },
    mediaPreviewEmpty: {
      alignItems: 'center',
      justifyContent: 'center',
    },
    mediaUploadLabel: {
      color: colors.text,
      fontSize: 11,
      fontWeight: '600',
      textAlign: 'center',
    },
    accentRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 12,
      flexWrap: 'wrap',
    },
    accentSwatch: {
      width: 34,
      height: 34,
      borderRadius: 999,
      borderWidth: 3,
      borderColor: 'transparent',
    },
    accentSwatchOn: {
      borderColor: colors.text,
      shadowColor: '#000',
      shadowOpacity: 0.35,
      shadowRadius: 4,
      shadowOffset: { width: 0, height: 1 },
      elevation: 3,
    },
    themeHint: {
      color: colors.muted,
      fontSize: 13,
      lineHeight: 19,
      marginBottom: 8,
    },
    themeRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 12,
      paddingVertical: 10,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: hexAlpha(accentHex, 0.35),
    },
    themeRadio: {
      width: 20,
      height: 20,
      borderRadius: 999,
      borderWidth: 2,
      borderColor: hexAlpha(accentHex, 0.45),
    },
    themeRadioOn: {
      borderColor: accentHex,
      backgroundColor: accentHex,
    },
    themeLabel: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '600',
    },
    themeSub: {
      color: colors.muted,
      fontSize: 12,
      marginTop: 2,
    },
    toneRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 8,
    },
    toneChip: {
      borderRadius: 999,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      paddingVertical: 8,
      paddingHorizontal: 14,
      backgroundColor: colors.card,
    },
    toneChipOn: {
      backgroundColor: accentHex,
      borderColor: accentHex,
    },
    toneChipText: {
      color: colors.text,
      fontSize: 13,
      fontWeight: '700',
    },
    toneChipTextOn: {
      color: '#fff',
    },
    profileNote: {
      color: colors.muted,
      fontSize: 13,
      marginTop: 16,
      marginBottom: 12,
    },
    guideLink: {
      marginTop: 20,
      borderWidth: 1.5,
      borderRadius: 14,
      minHeight: 52,
      paddingHorizontal: 16,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
    },
    guideLinkTitle: {
      fontSize: 16,
      fontWeight: '800',
    },
    guideLinkAction: {
      fontSize: 14,
      fontWeight: '700',
    },
    logoutBtn: {
      marginTop: 12,
      borderRadius: 14,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      minHeight: 48,
      alignItems: 'center',
      justifyContent: 'center',
    },
    logoutBtnText: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '700',
    },
  });
}
