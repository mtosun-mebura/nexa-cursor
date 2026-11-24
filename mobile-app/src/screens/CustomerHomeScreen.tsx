import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  Alert,
  ActivityIndicator,
  Animated,
  AppState,
  Easing,
  Image,
  ImageBackground,
  Keyboard,
  Linking,
  Platform,
  Pressable,
  ScrollView,
  Share,
  StyleSheet,
  Text,
  TextInput,
  useWindowDimensions,
  View,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import type { TextInput as TextInputType } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import MapView, { Marker, Polyline, PROVIDER_GOOGLE } from 'react-native-maps';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  bookGuest,
  cancelLive,
  confirmPickupLive,
  fetchLive,
  fetchLiveChat,
  fetchNearbyTaxis,
  fetchQuote,
  LiveRide,
  NearbyTaxi,
  RideStatusUpdate,
  payLive,
  rateLive,
  complainLive,
  COMPLAINT_CATEGORIES,
  QuoteOffer,
  QuoteResponse,
  sendLiveChat,
  waitLive,
  type RideChatMessageDto,
} from '../api/customer';
import { RideContactActions } from '../customer/bookingRedesign/RideContactActions';
import {
  RideChatModal,
  type RideChatMessage,
} from '../customer/bookingRedesign/RideChatModal';
import { USE_NEXA_BOOKING_REDESIGN } from '../customer/bookingRedesign/featureFlag';
import { NexaAssignedRidePanel } from '../customer/bookingRedesign/NexaAssignedRidePanel';
import { NexaHeroBanner } from '../customer/bookingRedesign/NexaHeroBanner';
import { NexaHomeScreen } from '../customer/bookingRedesign/NexaHomeScreen';
import { NexaScreenBackdrop } from '../customer/bookingRedesign/NexaScreenBackdrop';
import {
  NexaLocateButton,
  NexaLocationField,
} from '../customer/bookingRedesign/NexaLocationField';
import { NexaGlowCard } from '../customer/bookingRedesign/NexaGlowCard';
import {
  NexaCustomerTabBar,
  type NexaCustomerTabKey,
} from '../customer/bookingRedesign/NexaCustomerTabBar';
import { NexaPrimaryButton } from '../customer/bookingRedesign/NexaPrimaryButton';
import { DriverEnRouteStatusCard } from '../customer/bookingRedesign/DriverEnRouteStatusCard';
import { NexaPickupCodeButton } from '../customer/bookingRedesign/NexaPickupCodeButton';
import { NexaPickupCodeModal } from '../customer/bookingRedesign/NexaPickupCodeModal';
import {
  nexaBookingTheme,
  nexaSurfaceMuted,
  nexaSurfaceText,
} from '../customer/bookingRedesign/theme';
import {
  notifyNewRideChat,
  notifyNewRideStatusUpdates,
} from '../customer/rideStatusNotifications';
import { ApiError } from '../api/client';
import { newUuidV4 } from '../lib/uuid';
import {
  AddressSuggestion,
  DARK_MAP_STYLE,
  DEFAULT_MAP_CENTER,
  defaultPickupDate,
  estimateFareEuro,
  estimateRouteMetrics,
  fetchDrivingRoute,
  followNearestPickupDate,
  formatEuroNl,
  formatPickupAtPayload,
  isPickupAtInPast,
  GeoPoint,
  LatLng,
  peekCurrentCoords,
  peekLastKnownCoords,
  resolveCurrentPickup,
  searchAddresses,
  visiblePlaceName,
} from '../geo/route';
import {
  baggageChipLines,
  customerRefundNoticeText,
  formatPickupWhen,
  formatTripDistance,
  formatTripDuration,
  GuestRide,
  guestPatchFromLive,
  rideNeedsLivePoll,
  rideNeedsStatusRefresh,
  isAutoUnacceptedCancel,
  livePickupAt,
  isActiveRidePhase,
  isRidesTabBadgePhase,
  loadArchivedKeys,
  loadGuestRides,
  phaseLabel,
  rideArchiveKey,
  saveGuestRide,
  setRideArchived,
  bookingAddressLine,
  shortAddress,
  splitRideAddress,
  updateGuestRide,
} from '../customer/guestRides';
import {
  CUSTOMER_PAYMENT_RETURN_URL,
  isCustomerPaymentReturn,
  parseAppDeepLink,
} from '../linking';
import { dismissMollieCheckout, openMollieCheckout } from '../customer/openMollieCheckout';
import {
  Card,
  ErrorText,
  Field,
  GhostButton,
  PrimaryButton,
  Screen,
} from '../ui/components';
import { AppModal } from '../ui/AppModal';
import { ArchiveMotion, type ArchiveMotionMode } from '../ui/ArchiveMotion';
import { CustomerTabBar, CustomerTabKey } from '../ui/CustomerTabBar';
import { AppBrandLogo } from '../ui/AppBrandLogo';
import { AppPageHeader, APP_PAGE_TOP_INSET } from '../ui/AppPageHeader';
import { AppearanceControls } from '../ui/AppearanceControls';
import {
  nexaHeroChromeRow,
  nexaHeroSideRight,
  nexaHeroSideSlot,
} from '../customer/bookingRedesign/nexaHeroLayout';
import { APP_CHROME_HEIGHT } from '../ui/appChrome';
import { useAndroidKeyboardOverlap } from '../ui/androidText';
import { useAndroidBackHandler } from '../ui/useAndroidBackHandler';
import { DemoModeRow } from '../ui/DemoModeRow';
import { useDemo } from '../demo/DemoContext';
import {
  startDemoSimulation,
  stopDemoSimulation,
  updateDemoSimulationCenter,
  useDemoTaxis,
} from '../demo/taxiSimulation';
import { PickupAtField } from '../ui/PickupAtPicker';
import { DutchPlateBadge } from '../ui/DutchPlateInput';
import { getApiBaseUrl, resolveApiBaseUrl, ColorPalette } from '../config';
import { useI18n, t, dateLocale } from '../i18n';
import { ThemePreference, useTheme } from '../theme/ThemeContext';

const TRACK_KEY = 'nexa_taxi_customer_track';
const PROFILE_KEY = 'nexa_taxi_customer_profile_guest';

type ContactSnapshot = {
  firstName: string;
  lastName: string;
  phone: string;
  email: string;
};

function contactDetailsFilled(
  firstName: string,
  lastName: string,
  phone: string,
  email: string
): boolean {
  return (
    firstName.trim().length > 0 &&
    lastName.trim().length > 0 &&
    phone.trim().length > 0 &&
    email.trim().length > 0
  );
}
const TAB_KEY = 'nexa_taxi_customer_tab';
const taxiCarYellow = require('../../assets/taxi-car-yellow.png');
/**
 * Android Marker.image: density-set 26×39 / @2x / @3x.
 * Eén losse @3x-PNG werd als mdpi gedecodeerd → 3× te groot op scherm.
 */
const taxiCarYellowMarker = require('../../assets/taxi-car-yellow-marker.png');
/** Flat illustrated taxi thumb when vehicle photo is missing/fails (not a real car photo). */
const taxiVehiclePlaceholder = require('../../assets/taxi-vehicle-placeholder.png');
const dropPinGreen = require('../../assets/marker-drop-green.png');

const THEME_OPTIONS: { key: ThemePreference; label: string; hint: string }[] = [
  { key: 'system', label: t("Systeem"), hint: t("Volgt de telefooninstelling") },
  { key: 'light', label: t("Licht"), hint: t("Altijd lichte weergave") },
  { key: 'dark', label: t("Donker"), hint: t("Altijd donkere weergave") },
];

type BaggageKey = 'large' | 'small' | 'hand' | 'wheelchair' | 'pets';

const BAGGAGE_ROWS: {
  key: BaggageKey;
  label: string;
  hint: string;
  special?: boolean;
  max: number;
}[] = [
  { key: 'large', label: t("Grote ruimbagage"), hint: '85×55×35 cm', max: 6 },
  { key: 'small', label: t("Kleine ruimbagage"), hint: '55×45×25 cm', max: 6 },
  { key: 'hand', label: t("Handbagage"), hint: t("Handtas, rugzak, etc."), max: 6 },
  { key: 'wheelchair', label: t("Opvouwbare rolstoel"), hint: t("Speciale bagage"), special: true, max: 2 },
  { key: 'pets', label: t("Huisdieren"), hint: t("Optioneel"), special: true, max: 2 },
];

function bearingDegrees(
  from: { lat: number; lng: number },
  to: { lat: number; lng: number }
): number {
  const toRad = (d: number) => (d * Math.PI) / 180;
  const y = Math.sin(toRad(to.lng - from.lng)) * Math.cos(toRad(to.lat));
  const x =
    Math.cos(toRad(from.lat)) * Math.sin(toRad(to.lat)) -
    Math.sin(toRad(from.lat)) * Math.cos(toRad(to.lat)) * Math.cos(toRad(to.lng - from.lng));
  return (Math.atan2(y, x) * 180) / Math.PI;
}

/** Fel neonblauw routepad (geen soft glow-laag). */
const NEON_ROUTE = '#00D4FF';

/**
 * Oude top-down gele taxi (neus = boven). Draait betrouwbaar 0/90/180/270°.
 * De isometrische NEXA-neon marker liet rotatie op iOS/Google Maps falen.
 */
const taxiMarkerFrameStyle = {
  width: 36,
  height: 36,
  alignItems: 'center' as const,
  justifyContent: 'center' as const,
};
const taxiMarkerImageStyle = { width: 22, height: 33 };
const activeTaxiMarkerSource = taxiCarYellowMarker;
const activeTaxiMarkerViewSource = taxiCarYellow;

function taxiMarkerRotationDeg(heading: number): number {
  const h = Number.isFinite(heading) ? heading : 0;
  return ((h % 360) + 360) % 360;
}

function headingDeltaDeg(a: number, b: number): number {
  return Math.abs(((a - b + 540) % 360) - 180);
}

/** Koers van A→B in graden (0 = noord, met de klok mee). */
function headingToward(
  from: { lat: number; lng: number },
  to: { lat: number; lng: number }
): number {
  return (bearingDegrees(from, to) + 360) % 360;
}

/**
 * Android MapView herkent alleen directe <Marker>-children — een wrapper-
 * component (CustomerTaxiMarker) toont vaak maar 1 marker. Daarom: plain
 * factory die een Marker-element teruggeeft (geen component in de boom).
 */
function androidTaxiMarker(
  taxi: NearbyTaxi,
  title: string = t('Taxi beschikbaar'),
  zIndex = 20
) {
  const heading =
    taxi.heading != null && Number.isFinite(taxi.heading) && taxi.heading >= 0
      ? taxi.heading
      : 0;
  return (
    <Marker
      key={taxi.id}
      identifier={`taxi-${taxi.id}`}
      coordinate={{ latitude: taxi.lat, longitude: taxi.lng }}
      title={title}
      description={
        taxi.distance_km != null ? `${taxi.distance_km.toFixed(1)} km` : undefined
      }
      anchor={{ x: 0.5, y: 0.5 }}
      image={activeTaxiMarkerSource}
      rotation={taxiMarkerRotationDeg(heading)}
      flat
      tracksViewChanges={false}
      zIndex={zIndex}
    />
  );
}

function CustomerTaxiMarker({
  taxi,
  title = t("Taxi beschikbaar"),
  smoothMs = 3800,
  stepMs = 100,
}: {
  taxi: NearbyTaxi;
  title?: string;
  /** Duur van de beweging naar een nieuwe positie, en de tijdstap daarvan. */
  smoothMs?: number;
  stepMs?: number;
}) {
  const [pos, setPos] = useState({ lat: taxi.lat, lng: taxi.lng });
  const posRef = useRef(pos);
  const [heading, setHeading] = useState(() =>
    taxi.heading != null && Number.isFinite(taxi.heading) && taxi.heading >= 0
      ? taxi.heading
      : 0
  );
  const [trackView, setTrackView] = useState(true);

  useEffect(() => {
    const from = posRef.current;
    const to = { lat: taxi.lat, lng: taxi.lng };
    const dLat = to.lat - from.lat;
    const dLng = to.lng - from.lng;
    const moved = Math.abs(dLat) > 0.00001 || Math.abs(dLng) > 0.00001;
    if (taxi.heading != null && Number.isFinite(taxi.heading) && taxi.heading >= 0) {
      setHeading(taxi.heading);
    } else if (moved) {
      setHeading(headingToward(from, to));
    }
    setTrackView(true);
    if (!moved) {
      // Heading-only update: iOS custom marker moet opnieuw tekenen.
      const t = setTimeout(() => setTrackView(false), 350);
      return () => clearTimeout(t);
    }
    const started = Date.now();
    const timer = setInterval(() => {
      const t = Math.min(1, (Date.now() - started) / smoothMs);
      const next = { lat: from.lat + dLat * t, lng: from.lng + dLng * t };
      posRef.current = next;
      setPos(next);
      if (t >= 1) clearInterval(timer);
    }, stepMs);
    const off = setTimeout(() => setTrackView(false), smoothMs + 400);
    return () => {
      clearInterval(timer);
      clearTimeout(off);
    };
  }, [taxi.lat, taxi.lng, taxi.heading, smoothMs, stepMs]);

  const rotateDeg = taxiMarkerRotationDeg(heading);

  // iOS: Image-transform (Marker.rotation op custom views is onbetrouwbaar).
  // Android gebruikt androidTaxiMarker (image + rotation).
  return (
    <Marker
      identifier={`taxi-${taxi.id}`}
      coordinate={{ latitude: pos.lat, longitude: pos.lng }}
      title={title}
      description={taxi.distance_km != null ? `${taxi.distance_km.toFixed(1)} km` : undefined}
      anchor={{ x: 0.5, y: 0.5 }}
      tracksViewChanges={trackView}
      flat
      zIndex={20}
    >
      <View style={taxiMarkerFrameStyle} collapsable={false}>
        <Image
          source={activeTaxiMarkerViewSource}
          onLoad={() => {
            setTimeout(() => setTrackView(false), 300);
          }}
          style={[
            taxiMarkerImageStyle,
            { transform: [{ rotate: `${rotateDeg}deg` }] },
          ]}
          resizeMode="contain"
        />
      </View>
    </Marker>
  );
}

function MapPlaceCallout({
  label,
  address,
  tone,
  icon,
}: {
  label: string;
  address: string;
  tone: 'blue' | 'orange';
  icon: keyof typeof Ionicons.glyphMap;
}) {
  const border = tone === 'blue' ? '#3DB4FF' : '#FF8900';
  const glow = tone === 'blue' ? '#3DB4FF' : '#FF8900';
  return (
    <View style={{ alignItems: 'center' }} collapsable={false}>
      <View
        style={{
          maxWidth: 200,
          borderRadius: 12,
          borderWidth: 1.5,
          borderColor: border,
          backgroundColor: 'rgba(6, 19, 41, 0.92)',
          paddingHorizontal: 10,
          paddingVertical: 8,
          flexDirection: 'row',
          alignItems: 'center',
          gap: 8,
          shadowColor: glow,
          shadowOpacity: 0.65,
          shadowRadius: 8,
          shadowOffset: { width: 0, height: 0 },
        }}
      >
        <Ionicons name={icon} size={16} color={border} />
        <View style={{ flexShrink: 1, minWidth: 0 }}>
          <Text style={{ color: '#B7C7DF', fontSize: 10, fontWeight: '700' }} numberOfLines={1}>
            {label}
          </Text>
          <Text style={{ color: '#FFFFFF', fontSize: 12, fontWeight: '700' }} numberOfLines={2}>
            {address}
          </Text>
        </View>
      </View>
      <View
        style={{
          width: 0,
          height: 0,
          marginTop: -1,
          borderLeftWidth: 7,
          borderRightWidth: 7,
          borderTopWidth: 8,
          borderLeftColor: 'transparent',
          borderRightColor: 'transparent',
          borderTopColor: border,
        }}
      />
      <View
        style={{
          width: 18,
          height: 18,
          borderRadius: 9,
          borderWidth: 3,
          borderColor: border,
          backgroundColor: tone === 'blue' ? '#0A1628' : 'rgba(255,137,0,0.25)',
          alignItems: 'center',
          justifyContent: 'center',
          marginTop: 2,
          shadowColor: glow,
          shadowOpacity: 0.8,
          shadowRadius: 6,
          shadowOffset: { width: 0, height: 0 },
        }}
      >
        <View
          style={{
            width: 6,
            height: 6,
            borderRadius: 3,
            backgroundColor: '#FFFFFF',
          }}
        />
      </View>
    </View>
  );
}

const arrivalLine = { alignSelf: 'stretch' as const, width: '100%' as const };

function formatEtaPhrase(minutes: number): string {
  const rounded = Math.max(1, Math.round(minutes));
  if (rounded < 60) {
    return rounded === 1
      ? t('ongeveer 1 minuut')
      : t('ongeveer {n} minuten', { n: rounded });
  }
  const hours = Math.floor(rounded / 60);
  const mins = rounded % 60;
  if (mins === 0) {
    return hours === 1
      ? t('ongeveer 1 uur')
      : t('ongeveer {n} uur', { n: hours });
  }
  if (hours === 1) {
    return t('ongeveer 1 uur en {m} minuten', { m: mins });
  }
  return t('ongeveer {h} uur en {m} minuten', { h: hours, m: mins });
}

/** API stuurt NL-eta_label; lokaliseer voor EN (en hergebruik minutes indien aanwezig). */
function localizeEtaLabel(
  label?: string | null,
  minutes?: number | null
): string {
  if (minutes != null && Number.isFinite(Number(minutes)) && Number(minutes) > 0) {
    return formatEtaPhrase(Number(minutes));
  }
  const raw = String(label || '').trim();
  if (!raw) return '';
  const m = raw.match(/ongeveer\s+(\d+)\s+minu/i);
  if (m) return formatEtaPhrase(Number(m[1]));
  const about = raw.match(/about\s+(\d+)\s+minute/i);
  if (about) return formatEtaPhrase(Number(about[1]));
  return raw;
}

function haversineKm(fromLat: number, fromLng: number, toLat: number, toLng: number): number {
  const toRad = (deg: number) => (deg * Math.PI) / 180;
  const dLat = toRad(toLat - fromLat);
  const dLng = toRad(toLng - fromLng);
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(toRad(fromLat)) * Math.cos(toRad(toLat)) * Math.sin(dLng / 2) ** 2;
  return 2 * 6371 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function etaFromStatus(label?: string | null): string | null {
  const match = String(label || '').match(/Aankomst over\s+(.+?)\.?\s*$/i);
  if (!match) return null;
  const value = match[1].replace(/\.+$/, '').trim();
  if (!value || value === '...') return null;
  return value;
}

function arrivalEtaText(live?: LiveRide | null): string | null {
  const label = String(live?.eta_label || '').trim();
  if (label) return label;
  const minutes = Number(live?.eta_minutes);
  if (Number.isFinite(minutes) && minutes > 0) return formatEtaPhrase(minutes);
  const fromLat = Number(live?.vehicle_location?.lat);
  const fromLng = Number(live?.vehicle_location?.lng);
  const toLat = Number(live?.pickup_lat);
  const toLng = Number(live?.pickup_lng);
  if (![fromLat, fromLng, toLat, toLng].every((n) => Number.isFinite(n))) return null;
  if (fromLat === 0 && fromLng === 0) return null;
  const km = haversineKm(fromLat, fromLng, toLat, toLng);
  return formatEtaPhrase(Math.max(1, Math.ceil((km / 28) * 60)));
}

function resolveCustomerMediaUrl(url?: string | null): string | null {
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

const ACCEPTED_CAR_WIDTH = 92;
const ACCEPTED_CAR_HEIGHT = 56;

async function callViaCentralRelay(companyPhone?: string | null) {
  const raw = String(companyPhone || '').trim();
  const digits = raw.replace(/[^\d+]/g, '');
  if (!digits) {
    Alert.alert(
      t('Bellen niet beschikbaar'),
      t('Er is nog geen belverbinding via de centrale. Probeer chat of bel later opnieuw.')
    );
    return;
  }
  // Privénummer chauffeur blijft verborgen: bel loopt via centrale.
  // iPad: canOpenURL('tel:') is vaak false — toch openURL proberen (FaceTime/prompt).
  const url = `tel:${digits}`;
  try {
    await Linking.openURL(url);
  } catch {
    Alert.alert(
      t('Bellen niet beschikbaar'),
      t('Kon belverbinding niet openen. Centrale: {phone}', { phone: digits })
    );
  }
}

async function shareLiveRide(live: LiveRide) {
  const from = live.pickup_address || t('Ophaalpunt');
  const to = live.dropoff_address || t('Bestemming');
  const eta = live.eta_label ? `\n${t('ETA')}: ${live.eta_label}` : '';
  const driver = live.driver?.name ? `\n${t('Chauffeur')}: ${live.driver.name}` : '';
  try {
    await Share.share({
      message: `${t('NEXA Taxi rit')}\n${from} → ${to}${driver}${eta}`,
    });
  } catch {
    /* ignore */
  }
}

function AcceptedRideIdentity({
  live,
  onPreview,
  onCall,
  onChat,
  onShare,
  chatAttention,
}: {
  live: LiveRide;
  onPreview: (uri: string) => void;
  onCall?: () => void;
  onChat?: () => void;
  onShare?: () => void;
  chatAttention?: boolean;
}) {
  const { colors, colorScheme } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const photo = resolveCustomerMediaUrl(live.driver?.photo_url);
  const carPhoto = resolveCustomerMediaUrl(live.vehicle?.photo_url);
  const logo = resolveCustomerMediaUrl(live.driver?.company_logo_url);
  const plate = String(live.vehicle?.license_plate || '').trim();
  const [carPhotoFailed, setCarPhotoFailed] = useState(false);
  const showCarPhoto = Boolean(carPhoto) && !carPhotoFailed;
  const redesign = USE_NEXA_BOOKING_REDESIGN;
  const canCommunicate = !!(live.driver?.id || live.accepted);

  useEffect(() => {
    setCarPhotoFailed(false);
  }, [carPhoto]);

  return (
    <View
      style={[
        styles.driverCard,
        styles.acceptedDriverCard,
        redesign && {
          backgroundColor: nexaBookingTheme.color.surface,
          borderColor: nexaBookingTheme.color.border,
          borderWidth: 1,
          borderRadius: nexaBookingTheme.radius.lg,
          padding: 14,
        },
      ]}
    >
      {logo ? (
        <Image source={{ uri: logo }} style={styles.acceptedDriverLogo} resizeMode="contain" />
      ) : null}
      <View style={styles.acceptedAlign}>
        <View style={styles.acceptedAlignRow}>
          <View style={styles.acceptedCarColumn}>
            <Pressable
              disabled={!photo}
              onPress={() => {
                if (photo) onPreview(photo);
              }}
              accessibilityRole={photo ? 'button' : 'image'}
              accessibilityLabel={photo ? t("Profielfoto vergroten") : t("Chauffeur")}
            >
              {photo ? (
                <Image source={{ uri: photo }} style={styles.acceptedDriverAvatar} />
              ) : (
                <View style={[styles.acceptedDriverAvatar, styles.driverAvatarFallback]}>
                  <Ionicons name="person" size={34} color={colors.muted} />
                </View>
              )}
            </Pressable>
          </View>
          <View style={styles.acceptedDriverMeta}>
            {live.driver?.name ? (
              <Text
                style={[
                  styles.driverName,
                  redesign && {
                    color: nexaSurfaceText(colorScheme === 'light'),
                  },
                ]}
              >
                {live.driver.name}
              </Text>
            ) : (
              <Text style={styles.driverName}>{t("Chauffeur")}</Text>
            )}
            {live.driver?.rating_avg != null ? (
              <Text style={styles.acceptedDriverRating}>
                <Text style={styles.driverStar}>★</Text>
                {' '}
                {Number(live.driver.rating_avg).toFixed(1)}
                {live.driver.rating_count ? ` (${live.driver.rating_count})` : ''}
              </Text>
            ) : null}
            {live.vehicle?.name || live.vehicle?.label ? (
              <Text style={styles.meta}>{live.vehicle?.name || live.vehicle?.label}</Text>
            ) : null}
            {live.eta_label || live.eta_minutes != null ? (
              <Text style={[styles.meta, redesign && { color: nexaBookingTheme.color.blueBright }]}>
                {t('Aankomst over {eta}', {
                  eta: localizeEtaLabel(live.eta_label, live.eta_minutes),
                })}
              </Text>
            ) : null}
          </View>
        </View>
        <View style={styles.acceptedAlignRow}>
          <Pressable
            disabled={!showCarPhoto}
            onPress={() => {
              if (showCarPhoto && carPhoto) onPreview(carPhoto);
            }}
            accessibilityRole={showCarPhoto ? 'button' : 'image'}
            accessibilityLabel={
              showCarPhoto ? t("Autofoto vergroten") : t("Geen foto van dit voertuig")
            }
          >
            {showCarPhoto ? (
              <Image
                source={{ uri: carPhoto! }}
                style={styles.acceptedVehicleThumb}
                resizeMode="contain"
                onError={() => setCarPhotoFailed(true)}
              />
            ) : (
              <View style={styles.acceptedVehiclePlaceholderWrap}>
                <View style={styles.acceptedVehiclePlaceholderFrame}>
                  <Image
                    source={
                      redesign
                        ? require('../../assets/nexa-booking/crops/accepted_vehicle_reference.png')
                        : taxiVehiclePlaceholder
                    }
                    style={[
                      styles.acceptedVehiclePlaceholderThumb,
                      redesign && { width: 140, height: 80 },
                    ]}
                    resizeMode="contain"
                  />
                </View>
                {!redesign ? (
                  <Text style={styles.acceptedVehiclePlaceholderLabel}>{t("Geen foto")}</Text>
                ) : null}
              </View>
            )}
          </Pressable>
          <DutchPlateBadge plate={plate} accessibilityLabel={t("Kenteken")} />
        </View>
      </View>
      {redesign && onCall && onChat ? (
        <RideContactActions
          canCommunicate={canCommunicate}
          onCall={onCall}
          onChat={onChat}
          onShare={onShare}
          chatAttention={chatAttention}
        />
      ) : null}
    </View>
  );
}

function DriverComingText({
  eta,
  headlineStyle,
  detailStyle,
}: {
  eta?: string | null;
  headlineStyle?: object;
  detailStyle?: object;
}) {
  const headline = t("De chauffeur is onderweg naar jou");
  const detail = eta ? t("Aankomst over {eta}.", { eta }) : t("Aankomst over...");
  const showFull = Platform.OS === 'android';
  const [headlineCount, setHeadlineCount] = useState(showFull ? headline.length : 0);
  const [detailCount, setDetailCount] = useState(showFull ? detail.length : 0);

  useEffect(() => {
    if (showFull) {
      setHeadlineCount(headline.length);
      setDetailCount(detail.length);
      return;
    }
    setHeadlineCount(0);
    setDetailCount(0);
    let headlineIndex = 0;
    let detailIndex = 0;
    const timer = setInterval(() => {
      if (headlineIndex < headline.length) {
        headlineIndex += 1;
        setHeadlineCount(headlineIndex);
        return;
      }
      if (detailIndex < detail.length) {
        detailIndex += 1;
        setDetailCount(detailIndex);
        return;
      }
      clearInterval(timer);
    }, 28);
    return () => clearInterval(timer);
  }, [detail, headline, showFull]);

  const headlineShown = headline.slice(0, headlineCount);
  const detailShown = detail.slice(0, detailCount);
  const headlineRest = headline.slice(headlineCount);
  const detailRest = detail.slice(detailCount);

  return (
    <View style={arrivalLine}>
      <Text style={[headlineStyle, arrivalLine]}>
        {headlineShown}
        {headlineRest ? <Text style={{ color: 'transparent' }}>{headlineRest}</Text> : null}
      </Text>
      <Text style={[detailStyle, arrivalLine]}>
        {detailShown}
        {detailRest ? <Text style={{ color: 'transparent' }}>{detailRest}</Text> : null}
      </Text>
    </View>
  );
}

const ROUTE_FIT_PADDING = { top: 56, right: 56, bottom: 56, left: 56 };
/** Extra top-ruimte zodat ophaal-/bestemmingsballonnen niet worden afgekapt. */
/** Ruime L/R: ballonnen (~200px) mogen niet tegen de kaartrand knippen. */
/** Genoeg ruimte voor ballonnen, zonder de route te ver weg te zoomen. */
const TRIP_ROUTE_FIT_PADDING = { top: 130, right: 120, bottom: 72, left: 120 };

function fitMapToPoints(
  map: MapView | null,
  points: LatLng[],
  edgePadding: { top: number; right: number; bottom: number; left: number } = ROUTE_FIT_PADDING
) {
  if (!map || points.length === 0) return;
  if (points.length === 1) {
    map.animateToRegion(
      {
        latitude: points[0].latitude,
        longitude: points[0].longitude,
        latitudeDelta: 0.02,
        longitudeDelta: 0.02,
      },
      400
    );
    return;
  }
  map.fitToCoordinates(points, { edgePadding, animated: true });
}

function tripRoutePoints(
  live: LiveRide | null | undefined,
  line: LatLng[],
  taxi: { lat: number; lng: number } | null
): LatLng[] {
  const points: LatLng[] = line.length > 0 ? [...line] : [];
  if (live?.pickup_lat != null && live.pickup_lng != null) {
    points.push({ latitude: Number(live.pickup_lat), longitude: Number(live.pickup_lng) });
  }
  if (live?.dropoff_lat != null && live.dropoff_lng != null) {
    points.push({ latitude: Number(live.dropoff_lat), longitude: Number(live.dropoff_lng) });
  }
  if (taxi) points.push({ latitude: taxi.lat, longitude: taxi.lng });
  return points;
}

function isSameTaxi(
  taxi: { lat: number; lng: number },
  loc: { lat: number; lng: number } | null
): boolean {
  if (!loc) return false;
  const toRad = (d: number) => (d * Math.PI) / 180;
  const dLat = toRad(taxi.lat - loc.lat);
  const dLng = toRad(taxi.lng - loc.lng);
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(toRad(loc.lat)) * Math.cos(toRad(taxi.lat)) * Math.sin(dLng / 2) ** 2;
  const meters = 2 * 6371000 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return meters < 200;
}

function formatBaggageSummary(
  baggage?: Record<string, number> | null,
  special?: Record<string, number> | null
): string | null {
  const labels: Record<string, string> = Object.fromEntries(
    BAGGAGE_ROWS.map((row) => [row.key, row.label])
  );
  const parts: string[] = [];
  for (const map of [baggage, special]) {
    if (!map) continue;
    for (const [key, qty] of Object.entries(map)) {
      const count = Number(qty) || 0;
      if (count <= 0) continue;
      parts.push(`${t(labels[key] || key)} × ${count}`);
    }
  }
  return parts.length ? parts.join(', ') : null;
}

/** Nieuwste status bovenaan. Bron blijft oudste-eerst (API orderBy id). */
function statusUpdatesNewestFirst(updates: RideStatusUpdate[]): RideStatusUpdate[] {
  return updates
    .map((update, index) => ({ update, index }))
    .sort((a, b) => {
      const aTime = Date.parse(a.update.at || '');
      const bTime = Date.parse(b.update.at || '');
      const aMs = Number.isFinite(aTime) ? aTime : 0;
      const bMs = Number.isFinite(bTime) ? bTime : 0;
      if (bMs !== aMs) return bMs - aMs;
      return b.index - a.index;
    })
    .map(({ update }) => update);
}

export function CustomerHomeScreen({ onBack }: { onBack: () => void }) {
  const { height: windowHeight } = useWindowDimensions();
  const insets = useSafeAreaInsets();
  const { colors, colorScheme, preference, setPreference } = useTheme();
  useI18n();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  /** Sticky header-bg: 0 = hero doorzichtig, 1 = solide vlak achter logo/icons. */
  const [bookChromeOpacity, setBookChromeOpacity] = useState(0);
  const bookChromeOpacityRef = useRef(0);
  /** Open ritten waarvan de Ritten-badge al is gezien (klik op tab). */
  const [ridesBadgeSeenKeys, setRidesBadgeSeenKeys] = useState<string[]>([]);
  const [tab, setTab] = useState<CustomerTabKey | NexaCustomerTabKey>(
    USE_NEXA_BOOKING_REDESIGN ? 'home' : 'book'
  );
  const [trackToken, setTrackToken] = useState<string | null>(null);
  const [live, setLive] = useState<LiveRide | null>(null);
  const [guestRides, setGuestRides] = useState<GuestRide[]>([]);
  const [archivedKeys, setArchivedKeys] = useState<string[]>([]);
  const [showArchivedRides, setShowArchivedRides] = useState(false);
  const [rideArchiveMotion, setRideArchiveMotion] = useState<{
    key: string;
    mode: Exclude<ArchiveMotionMode, 'idle' | 'delete-out'>;
  } | null>(null);
  const pendingRideArchiveRef = useRef<{ ride: GuestRide; archived: boolean } | null>(
    null
  );
  const keyboardOverlap = useAndroidKeyboardOverlap();
  useAndroidBackHandler(() => {
    if (showArchivedRides) {
      setShowArchivedRides(false);
      return true;
    }
    onBack();
    return true;
  });
  const [expandedRideKey, setExpandedRideKey] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [locationError, setLocationError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [pickupAnswerToken, setPickupAnswerToken] = useState<string | null>(null);
  const [profileSaved, setProfileSaved] = useState(false);
  const [profileEditing, setProfileEditing] = useState(false);
  const [bookContactEditing, setBookContactEditing] = useState(false);
  /** Read-only only after hydrate/save with all fields filled — not while first-time typing. */
  const [contactConfirmed, setContactConfirmed] = useState(false);
  const contactSnapshot = useRef<ContactSnapshot | null>(null);
  const [cancelConfirmOpen, setCancelConfirmOpen] = useState(false);
  /** Token van de rit die geannuleerd wordt (ook als trackToken even leeg is). */
  const [cancelTargetToken, setCancelTargetToken] = useState<string | null>(null);
  /** Auto-annulering na lang wachten: alleen OK, daarna uit de lijst. */
  const [longWaitCancelRide, setLongWaitCancelRide] = useState<GuestRide | null>(null);
  const longWaitCancelShownRef = useRef<Set<string>>(new Set());
  /** Keuze blijven wachten / annuleren na zoektijd. */
  const waitDecisionNotifiedRef = useRef<Set<string>>(new Set());
  const [photoPreviewUri, setPhotoPreviewUri] = useState<string | null>(null);
  const [chatOpen, setChatOpen] = useState(false);
  const [chatMessages, setChatMessages] = useState<RideChatMessage[]>([]);
  const [chatSending, setChatSending] = useState(false);
  const [chatAllowed, setChatAllowed] = useState(true);
  /** Knipperende chatknop tot de klant de chat opent. */
  const [chatAttention, setChatAttention] = useState(false);
  const chatOpenRef = useRef(false);
  chatOpenRef.current = chatOpen;
  /** Live-volgen scherm (kaart uitvergroot + overlays) na "ophalen" door chauffeur. */
  const [liveFollowUi, setLiveFollowUi] = useState(false);
  /** Ophaalcode standaard verborgen; pas tonen na “Ophaalcode bekijken”. */
  const [revealedPinKeys, setRevealedPinKeys] = useState<string[]>([]);
  const [rateStars, setRateStars] = useState(0);
  const [complaintModalToken, setComplaintModalToken] = useState<string | null>(null);
  const [complaintCategory, setComplaintCategory] = useState('other');
  const [complaintSubject, setComplaintSubject] = useState('');
  const [complaintDescription, setComplaintDescription] = useState('');
  const [complaintPhone, setComplaintPhone] = useState('');
  const [complaintEmail, setComplaintEmail] = useState('');
  const [complaintError, setComplaintError] = useState<string | null>(null);
  const [rateComment, setRateComment] = useState('');
  const [rateModalToken, setRateModalToken] = useState<string | null>(null);
  const [rateError, setRateError] = useState<string | null>(null);
  const prevLivePhase = useRef<string | null>(null);
  const completedAnnounced = useRef<Set<number>>(new Set());

  const hasActiveRide = !!(live && trackToken && isActiveRidePhase(live.phase));
  const tripStarted = !!(
    hasActiveRide &&
    (live?.trip_started === true || live?.status === 'assigned')
  );
  const latestStatusEvent =
    live?.customer_event ||
    (Array.isArray(live?.status_updates) && live.status_updates.length
      ? live.status_updates[live.status_updates.length - 1]?.event
      : null);
  const driverComing = !!(hasActiveRide && !tripStarted && latestStatusEvent === 'en_route');
  const rideMapOpen = !!(hasActiveRide && live?.phase === 'accepted');
  const tripMapHeight = Math.max(
    220,
    Math.round(windowHeight * (liveFollowUi && driverComing ? 0.38 : 0.32))
  );

  useEffect(() => {
    if (!driverComing) setLiveFollowUi(false);
  }, [driverComing]);

  const [pickup, setPickup] = useState<GeoPoint | null>(null);
  const [pickupQuery, setPickupQuery] = useState('');
  const [pickupLoading, setPickupLoading] = useState(false);
  const [pickupSuggestions, setPickupSuggestions] = useState<AddressSuggestion[]>([]);
  const [fleetOrigin, setFleetOrigin] = useState<{ lat: number; lng: number }>(DEFAULT_MAP_CENTER);

  const [dropoffQuery, setDropoffQuery] = useState('');
  const [dropoff, setDropoff] = useState<GeoPoint | null>(null);
  const [dropoffSuggestions, setDropoffSuggestions] = useState<AddressSuggestion[]>([]);

  const [routeCoords, setRouteCoords] = useState<LatLng[]>([]);
  const [routeMetrics, setRouteMetrics] = useState<{
    distance_meters: number;
    duration_seconds: number;
  } | null>(null);

  const [passengers, setPassengers] = useState('1');
  const [baggage, setBaggage] = useState<Record<BaggageKey, number>>({
    large: 0,
    small: 0,
    hand: 0,
    wheelchair: 0,
    pets: 0,
  });
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [remarks, setRemarks] = useState('');
  const [pickupAt, setPickupAt] = useState(() => defaultPickupDate(10));
  const [fieldErrors, setFieldErrors] = useState<Partial<Record<
    'pickup' | 'dropoff' | 'pickupAt' | 'firstName' | 'lastName' | 'phone' | 'email' | 'offer' | 'dispatch',
    string
  >>>({});

  // Houd ophaaltijd bij de dichtstbijzijnde boekbare slot als het scherm open blijft.
  useEffect(() => {
    const bump = () => {
      setPickupAt((prev) => followNearestPickupDate(prev, 10));
      setFieldErrors((e) => (e.pickupAt ? { ...e, pickupAt: undefined } : e));
    };
    bump();
    const id = setInterval(bump, 30_000);
    const sub = AppState.addEventListener('change', (state) => {
      if (state === 'active') bump();
    });
    return () => {
      clearInterval(id);
      sub.remove();
    };
  }, []);

  const [quote, setQuote] = useState<QuoteResponse | null>(null);
  const [nearbyTaxis, setNearbyTaxis] = useState<NearbyTaxi[]>([]);
  const demo = useDemo();
  const demoActive = demo.active;
  /** Los van MapView-fragment: hook hier zodat markers directe children kunnen zijn. */
  const demoTaxis = useDemoTaxis();
  const quoteTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const pickupSuggestTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const dropoffSuggestTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const pickupSuggestSeq = useRef(0);
  const dropoffSuggestSeq = useRef(0);
  const dropoffRef = useRef<GeoPoint | null>(null);
  const mapRef = useRef<MapView | null>(null);
  const tripMapRef = useRef<MapView | null>(null);
  const ridesScrollRef = useRef<ScrollView | null>(null);
  const tripStatusY = useRef(0);
  const followTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const bookIdleTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const ignoreMapGestureUntil = useRef(0);
  const ignoreBookGestureUntil = useRef(0);
  const bookUserMoving = useRef(false);
  const bookFingerDown = useRef(false);
  const bookFingerAt = useRef(0);
  const bookSawGesture = useRef(false);
  const bookGpsSeq = useRef(0);
  const tripFitted = useRef(false);
  const [followTaxi, setFollowTaxi] = useState(false);
  const [tripRoute, setTripRoute] = useState<LatLng[]>([]);
  const bookScrollRef = useRef<ScrollView | null>(null);
  const dropoffInputRef = useRef<TextInputType | null>(null);
  const profileSavedTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const mapSectionY = useRef(0);
  const pickupAtSectionY = useRef(0);
  const [bookMountKey, setBookMountKey] = useState(0);

  dropoffRef.current = dropoff;
  const offer: QuoteOffer | null = quote?.offers?.[0] || null;
  const activeTaxiLoc = useMemo(() => {
    if (!live || !trackToken || !isActiveRidePhase(live.phase)) return null;
    const loc = live.vehicle_location;
    const lat = loc?.lat != null ? Number(loc.lat) : NaN;
    const lng = loc?.lng != null ? Number(loc.lng) : NaN;
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
    if (lat === 0 && lng === 0) return null;
    const headingRaw = loc?.heading != null ? Number(loc.heading) : NaN;
    let heading: number | null =
      Number.isFinite(headingRaw) && headingRaw >= 0 ? headingRaw : null;
    // Voorkant (= bovenkant auto) richting reis: bij ontbrekende/suspect GPS-heading
    // afleiden uit koers naar ophaalpunt (en-route) of bestemming.
    const pickupLat = live.pickup_lat != null ? Number(live.pickup_lat) : NaN;
    const pickupLng = live.pickup_lng != null ? Number(live.pickup_lng) : NaN;
    const dropLat = live.dropoff_lat != null ? Number(live.dropoff_lat) : NaN;
    const dropLng = live.dropoff_lng != null ? Number(live.dropoff_lng) : NaN;
    const towardPickup =
      Number.isFinite(pickupLat) && Number.isFinite(pickupLng)
        ? { lat: pickupLat, lng: pickupLng }
        : null;
    const towardDrop =
      Number.isFinite(dropLat) && Number.isFinite(dropLng)
        ? { lat: dropLat, lng: dropLng }
        : null;
    const phase = String(live.phase || '');
    const toward =
      phase === 'in_progress' || phase === 'arrived_dropoff'
        ? towardDrop
        : towardPickup;
    // Alleen koers afleiden als GPS-heading ontbreekt — anders blijft de marker “op de kop”.
    if ((heading == null || heading < 0) && toward) {
      heading = headingToward({ lat, lng }, toward);
    }
    return {
      lat,
      lng,
      heading,
    };
  }, [
    live,
    trackToken,
    live?.phase,
    live?.vehicle_location?.lat,
    live?.vehicle_location?.lng,
    live?.vehicle_location?.heading,
  ]);
  // Demo aan: client-simulatie toont taxi’s; echte vloot blijft meelopen.
  // Bij ophalen alleen de toegewezen taxi. Range = admin Marktplaats kaart-radius.
  const [fleetRangeKm, setFleetRangeKm] = useState(25);
  const fleetRangeOrigin = pickup
    ? { lat: pickup.lat, lng: pickup.lng }
    : fleetOrigin;
  const taxiInFleetRange = useCallback(
    (taxi: NearbyTaxi) =>
      haversineKm(fleetRangeOrigin.lat, fleetRangeOrigin.lng, taxi.lat, taxi.lng) <=
      fleetRangeKm,
    [fleetRangeOrigin.lat, fleetRangeOrigin.lng, fleetRangeKm]
  );
  const freeTaxis = nearbyTaxis.filter(
    (taxi) => !taxi.busy && !isSameTaxi(taxi, activeTaxiLoc) && taxiInFleetRange(taxi)
  );
  const pickupTracking = !!(hasActiveRide && activeTaxiLoc && (driverComing || rideMapOpen));
  const visibleFleetTaxis = (() => {
    if (pickupTracking) return [] as NearbyTaxi[];
    const demoInRange = demoTaxis.filter(taxiInFleetRange);
    if (!demoActive || demoInRange.length === 0) return freeTaxis;
    const demoIds = new Set(demoInRange.map((taxi) => String(taxi.id)));
    return [...demoInRange, ...freeTaxis.filter((taxi) => !demoIds.has(String(taxi.id)))];
  })();
  const freeNearby = pickupTracking ? 1 : visibleFleetTaxis.length;
  const marketplaceBlocksDispatch =
    quote?.marketplace?.taxi_available === false ||
    !!quote?.payment?.deferred_until_taxi;
  const canDispatch = freeNearby > 0 && !marketplaceBlocksDispatch;
  const showNoTaxiWarn = !!(pickup && dropoff && !canDispatch);

  // Chauffeur heeft "ophalen" geklikt (customer_event en_route, status nog accepted).
  const followTaxiMode = !!(driverComing && activeTaxiLoc);
  const followTaxiModeRef = useRef(followTaxiMode);
  followTaxiModeRef.current = followTaxiMode;

  const tripFitPointsRef = useRef<LatLng[]>([]);
  tripFitPointsRef.current = tripRoutePoints(live, tripRoute, activeTaxiLoc);

  const pauseFollow = useCallback(() => {
    if (Date.now() < ignoreMapGestureUntil.current) return;
    setFollowTaxi(false);
    if (followTimer.current) clearTimeout(followTimer.current);
    followTimer.current = setTimeout(() => {
      ignoreMapGestureUntil.current = Date.now() + 1200;
      if (followTaxiModeRef.current) {
        setFollowTaxi(true);
      } else {
        fitMapToPoints(tripMapRef.current, tripFitPointsRef.current, TRIP_ROUTE_FIT_PADDING);
      }
    }, 3000);
  }, []);

  const cancelBookLocationReturn = useCallback(() => {
    if (bookIdleTimer.current) {
      clearTimeout(bookIdleTimer.current);
      bookIdleTimer.current = null;
    }
  }, []);

  const holdBookMapForProgrammaticMove = useCallback(
    (ms = 1600) => {
      bookUserMoving.current = false;
      bookFingerDown.current = false;
      bookSawGesture.current = false;
      bookGpsSeq.current += 1;
      ignoreBookGestureUntil.current = Date.now() + ms;
      cancelBookLocationReturn();
    },
    [cancelBookLocationReturn]
  );

  const moveBookMapToCurrentLocation = useCallback(async (fresh = false) => {
    const seq = ++bookGpsSeq.current;
    cancelBookLocationReturn();
    const coords = fresh ? await peekCurrentCoords() : await peekLastKnownCoords();
    if (seq !== bookGpsSeq.current) return;
    if (bookUserMoving.current || bookFingerDown.current) return;
    if (!coords) return;
    const map = mapRef.current;
    if (!map) return;
    ignoreBookGestureUntil.current = Date.now() + 1600;
    try {
      const cam = await map.getCamera();
      if (seq !== bookGpsSeq.current || bookUserMoving.current || bookFingerDown.current) return;
      const zoom = cam.zoom && cam.zoom > 0 ? cam.zoom : 15;
      map.animateCamera(
        {
          center: { latitude: coords.lat, longitude: coords.lng },
          heading: cam.heading ?? 0,
          pitch: 0,
          zoom,
        },
        { duration: 450 }
      );
    } catch {
      if (seq !== bookGpsSeq.current || bookUserMoving.current || bookFingerDown.current) return;
      map.animateToRegion(
        {
          latitude: coords.lat,
          longitude: coords.lng,
          latitudeDelta: 0.02,
          longitudeDelta: 0.02,
        },
        450
      );
    }
  }, [cancelBookLocationReturn]);

  const scheduleBookLocationReturn = useCallback(() => {
    if (Date.now() < ignoreBookGestureUntil.current) return;
    if (bookUserMoving.current || bookFingerDown.current) return;
    cancelBookLocationReturn();
    bookIdleTimer.current = setTimeout(function tick() {
      bookIdleTimer.current = null;
      if (Date.now() < ignoreBookGestureUntil.current) return;
      const fingerFresh = bookFingerDown.current && Date.now() - bookFingerAt.current < 2500;
      if (bookUserMoving.current || fingerFresh) {
        bookIdleTimer.current = setTimeout(tick, 400);
        return;
      }
      bookFingerDown.current = false;
      bookUserMoving.current = false;
      void moveBookMapToCurrentLocation();
    }, 3000);
  }, [cancelBookLocationReturn, moveBookMapToCurrentLocation]);

  const noteBookMapGesture = useCallback(() => {
    if (Date.now() < ignoreBookGestureUntil.current) return;
    bookSawGesture.current = true;
    bookUserMoving.current = true;
    bookGpsSeq.current += 1;
    cancelBookLocationReturn();
  }, [cancelBookLocationReturn]);

  const noteBookMapGestureEnd = useCallback(() => {
    if (Date.now() < ignoreBookGestureUntil.current) return;
    bookUserMoving.current = false;
    bookFingerDown.current = false;
    if (!bookSawGesture.current) return;
    bookSawGesture.current = false;
    scheduleBookLocationReturn();
  }, [scheduleBookLocationReturn]);

  const noteBookMapTouchStart = useCallback(() => {
    bookFingerDown.current = true;
    bookFingerAt.current = Date.now();
    bookUserMoving.current = true;
  }, []);

  const noteBookMapTouchEnd = useCallback(() => {
    bookFingerDown.current = false;
    bookUserMoving.current = false;
    if (!bookSawGesture.current) return;
    if (Date.now() < ignoreBookGestureUntil.current) return;
    bookSawGesture.current = false;
    scheduleBookLocationReturn();
  }, [scheduleBookLocationReturn]);

  useEffect(() => {
    if (!rideMapOpen) {
      setFollowTaxi(false);
      tripFitted.current = false;
      if (followTimer.current) clearTimeout(followTimer.current);
      return;
    }
    // Pixel-spec Home: blijf op Home; Ritten alleen forceren in legacy-flow.
    if (!USE_NEXA_BOOKING_REDESIGN) {
      setTab('rides');
      AsyncStorage.setItem(TAB_KEY, 'rides').catch(() => undefined);
    }
    if (followTaxiMode) {
      if (followTimer.current) clearTimeout(followTimer.current);
      setFollowTaxi(true);
    } else {
      setFollowTaxi(false);
      // Na ophalen → rit gestart: opnieuw volledige route in beeld.
      tripFitted.current = false;
    }
    return () => {
      if (followTimer.current) clearTimeout(followTimer.current);
    };
  }, [rideMapOpen, followTaxiMode]);

  useEffect(() => {
    if (!rideMapOpen) return;
    setShowArchivedRides(false);
  }, [rideMapOpen, trackToken]);

  useEffect(() => {
    if (!rideMapOpen || tab !== 'rides') return;
    // Bij "ophalen" (en_route) hoort de kaart bovenaan — niet scrollen naar de statuskaart.
    if (driverComing) return;
    const scrollToStatus = () => {
      const y =
        tripStatusY.current > 0 ? tripStatusY.current : Math.max(0, tripMapHeight + 12);
      ridesScrollRef.current?.scrollTo({ y, animated: false });
    };
    const timers = [0, 80, 280, 500].map((ms) => setTimeout(scrollToStatus, ms));
    return () => timers.forEach(clearTimeout);
  }, [rideMapOpen, tab, trackToken, tripMapHeight, driverComing]);

  // Chauffeur klikt "ophalen": klant meteen naar de kaart (status staat eronder).
  useEffect(() => {
    if (!driverComing || !rideMapOpen) return;
    if (!USE_NEXA_BOOKING_REDESIGN) {
      setTab('rides');
      AsyncStorage.setItem(TAB_KEY, 'rides').catch(() => undefined);
    }
    setShowArchivedRides(false);
    const reveal = () => ridesScrollRef.current?.scrollTo({ y: 0, animated: true });
    requestAnimationFrame(reveal);
    const timers = [80, 280, 500].map((ms) => setTimeout(reveal, ms));
    return () => timers.forEach(clearTimeout);
  }, [driverComing, rideMapOpen, trackToken]);

  useEffect(() => {
    if (!rideMapOpen || live?.pickup_lat == null || live?.pickup_lng == null || live?.dropoff_lat == null || live?.dropoff_lng == null) {
      setTripRoute([]);
      return;
    }
    let cancelled = false;
    fetchDrivingRoute(
      { lat: Number(live.pickup_lat), lng: Number(live.pickup_lng) },
      { lat: Number(live.dropoff_lat), lng: Number(live.dropoff_lng) }
    )
      .then((route) => {
        if (!cancelled) setTripRoute(route.coordinates);
      })
      .catch(() => undefined);
    return () => {
      cancelled = true;
    };
  }, [rideMapOpen, live?.pickup_lat, live?.pickup_lng, live?.dropoff_lat, live?.dropoff_lng]);

  useEffect(() => {
    if (!rideMapOpen || tab !== 'rides' || followTaxiMode) return;
    const timer = setTimeout(() => {
      if (tripFitted.current) return;
      const map = tripMapRef.current;
      if (!map) return;
      const points = tripRoutePoints(live, tripRoute, activeTaxiLoc);
      if (points.length === 0) return;
      tripFitted.current = true;
      ignoreMapGestureUntil.current = Date.now() + 1200;
      fitMapToPoints(map, points, TRIP_ROUTE_FIT_PADDING);
    }, 350);
    return () => clearTimeout(timer);
  }, [rideMapOpen, tab, followTaxiMode, tripRoute, activeTaxiLoc, live?.pickup_lat, live?.pickup_lng, live?.dropoff_lat, live?.dropoff_lng]);

  useEffect(() => {
    if (!followTaxi || !activeTaxiLoc) return;
    ignoreMapGestureUntil.current = Date.now() + 1200;
    tripMapRef.current?.animateCamera(
      { center: { latitude: activeTaxiLoc.lat, longitude: activeTaxiLoc.lng } },
      { duration: 900 }
    );
  }, [followTaxi, activeTaxiLoc?.lat, activeTaxiLoc?.lng]);

  useEffect(() => {
    return () => {
      if (bookIdleTimer.current) clearTimeout(bookIdleTimer.current);
    };
  }, []);
  const unavailableMessage =
    quote?.marketplace?.unavailable_message ||
    t("Er is momenteel geen taxi beschikbaar in de buurt van deze ophaallocatie. Je krijgt een melding zodra er weer een taxi beschikbaar is.");

  const baggagePayload = useMemo(() => {
    const normal: Record<string, number> = {};
    const special: Record<string, number> = {};
    for (const row of BAGGAGE_ROWS) {
      const qty = baggage[row.key] || 0;
      if (qty <= 0) continue;
      if (row.special) special[row.key] = qty;
      else normal[row.key] = qty;
    }
    return { baggage: normal, special_baggage: special };
  }, [baggage]);

  const mapRegion = useMemo(() => {
    const points = [pickup, dropoff].filter(Boolean) as GeoPoint[];
    if (points.length === 0) {
      return {
        latitude: fleetOrigin.lat,
        longitude: fleetOrigin.lng,
        latitudeDelta: 0.06,
        longitudeDelta: 0.06,
      };
    }
    if (points.length === 1) {
      return {
        latitude: points[0].lat,
        longitude: points[0].lng,
        latitudeDelta: 0.02,
        longitudeDelta: 0.02,
      };
    }
    const lats = points.map((p) => p.lat);
    const lngs = points.map((p) => p.lng);
    const minLat = Math.min(...lats);
    const maxLat = Math.max(...lats);
    const minLng = Math.min(...lngs);
    const maxLng = Math.max(...lngs);
    return {
      latitude: (minLat + maxLat) / 2,
      longitude: (minLng + maxLng) / 2,
      latitudeDelta: Math.max(0.025, (maxLat - minLat) * 1.6),
      longitudeDelta: Math.max(0.025, (maxLng - minLng) * 1.6),
    };
  }, [pickup, dropoff, fleetOrigin]);

  useEffect(() => {
    if (routeCoords.length > 1 && mapRef.current) {
      holdBookMapForProgrammaticMove();
      mapRef.current.fitToCoordinates(routeCoords, {
        edgePadding: { top: 40, right: 40, bottom: 40, left: 40 },
        animated: true,
      });
    }
  }, [routeCoords]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const coords = await peekCurrentCoords();
      if (cancelled || !coords) return;
      setFleetOrigin(coords);
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const persistTrack = useCallback(async (token: string | null) => {
    if (!token) {
      await AsyncStorage.removeItem(TRACK_KEY);
      return;
    }
    await AsyncStorage.setItem(TRACK_KEY, token);
  }, []);

  const applyPaymentReturnUrl = useCallback(
    async (url: string | null) => {
      const link = parseAppDeepLink(url);
      if (!isCustomerPaymentReturn(link) || !link) return;

      dismissMollieCheckout();

      const token = String(link.params.token || '').trim();
      const boeking = String(link.params.boeking || '');

      if (token) {
        setTrackToken(token);
        await persistTrack(token);
        try {
          const data = await fetchLive(token);
          if (data.ride) {
            setLive(data.ride);
            const next = await saveGuestRide(data.ride.id, token, guestPatchFromLive(data.ride));
            setGuestRides(next);
            setExpandedRideKey(rideArchiveKey({ id: data.ride.id, token }));
          }
        } catch {
          /* live sync may still be catching up after Mollie */
        }
      }

      if (boeking === 'betaald' || boeking === 'betaling-bezig') {
        setTab('rides');
        AsyncStorage.setItem(TAB_KEY, 'rides').catch(() => undefined);
        setError(null);
      } else if (boeking === 'betaling-mislukt') {
        const reden = String(link.params.reden || 'mislukt');
        setTab('rides');
        AsyncStorage.setItem(TAB_KEY, 'rides').catch(() => undefined);
        setError(
          reden === 'geannuleerd'
            ? t("Betaling geannuleerd. Je kunt opnieuw betalen via je openstaande rit.")
            : reden === 'verlopen'
              ? t("Betaling verlopen. Start opnieuw via je openstaande rit.")
              : t("Betaling mislukt. Probeer het opnieuw via je openstaande rit.")
        );
      }
    },
    [persistTrack]
  );

  useEffect(() => {
    Linking.getInitialURL()
      .then((url) => applyPaymentReturnUrl(url))
      .catch(() => undefined);
    const sub = Linking.addEventListener('url', ({ url }) => {
      applyPaymentReturnUrl(url).catch(() => undefined);
    });
    return () => sub.remove();
  }, [applyPaymentReturnUrl]);

  const focusDropoffField = useCallback(() => {
    requestAnimationFrame(() => {
      dropoffInputRef.current?.focus();
    });
    setTimeout(() => dropoffInputRef.current?.focus(), 80);
  }, []);

  const loadPickup = useCallback(
    async (opts?: { focusDropoff?: boolean }) => {
      setPickupLoading(true);
      setLocationError(null);
      try {
        const point = await resolveCurrentPickup();
        setPickup(point);
        setPickupQuery(point.address);
        setPickupSuggestions([]);
        if (opts?.focusDropoff) {
          focusDropoffField();
        }
      } catch (e) {
        setLocationError(e instanceof Error ? e.message : t("Locatie ophalen mislukt."));
      } finally {
        setPickupLoading(false);
      }
    },
    [focusDropoffField]
  );

  useEffect(() => {
    // Bij openen vanaf start: altijd Boeken (niet een oude Profiel-tab).
    // Betaling-return mag wel naar Ritten blijven sturen.
    (async () => {
      try {
        const url = await Linking.getInitialURL();
        if (isCustomerPaymentReturn(parseAppDeepLink(url))) return;
      } catch {
        /* ignore */
      }
      const savedTrack = await AsyncStorage.getItem(TRACK_KEY);
      // Pixel-spec: altijd Home als start; actieve rit blijft via banner/Ritten bereikbaar.
      if (USE_NEXA_BOOKING_REDESIGN) {
        setTab('home');
        AsyncStorage.setItem(TAB_KEY, 'home').catch(() => undefined);
        return;
      }
      if (savedTrack) return;
      setTab('book');
      AsyncStorage.setItem(TAB_KEY, 'book').catch(() => undefined);
    })();
  }, []);

  async function hydratePickupTimes(list: GuestRide[]) {
    const missing = list.filter((ride) => ride.token && !String(ride.pickup_at || '').trim());
    for (const ride of missing) {
      try {
        const data = await fetchLive(ride.token);
        const pickup = livePickupAt(data.ride);
        if (!pickup) continue;
        const next = await updateGuestRide(ride.token, { pickup_at: pickup });
        setGuestRides(next);
      } catch {
        /* rit blijft zonder ophaaltijd tot de volgende sync */
      }
    }
  }

  useEffect(() => {
    loadPickup();
    (async () => {
      try {
        const [rides, archived] = await Promise.all([loadGuestRides(), loadArchivedKeys()]);
        setGuestRides(rides);
        setArchivedKeys(archived);
        void hydratePickupTimes(rides);
      } catch {
        /* ignore */
      }
      try {
        const raw = await AsyncStorage.getItem(PROFILE_KEY);
        if (raw) {
          const p = JSON.parse(raw) as {
            first_name?: string;
            last_name?: string;
            phone?: string;
            email?: string;
          };
          const loadedFirst = p.first_name ? String(p.first_name) : '';
          const loadedLast = p.last_name ? String(p.last_name) : '';
          const loadedPhone = p.phone ? String(p.phone) : '';
          const loadedEmail = p.email ? String(p.email) : '';
          if (loadedFirst) setFirstName(loadedFirst);
          if (loadedLast) setLastName(loadedLast);
          if (loadedPhone) setPhone(loadedPhone);
          if (loadedEmail) setEmail(loadedEmail);
          if (contactDetailsFilled(loadedFirst, loadedLast, loadedPhone, loadedEmail)) {
            setContactConfirmed(true);
          }
        }
      } catch {
        /* ignore */
      }
      const saved = await AsyncStorage.getItem(TRACK_KEY);
      if (!saved) return;
      try {
        const data = await fetchLive(saved);
        if (data.ride) {
          setTrackToken(saved);
          setLive(data.ride);
          const next = await saveGuestRide(data.ride.id, saved, guestPatchFromLive(data.ride));
          setGuestRides(next);
          if (!rideNeedsLivePoll(data.ride)) {
            await AsyncStorage.removeItem(TRACK_KEY);
            if (data.ride.phase === 'cancelled' || data.ride.phase === 'completed') {
              setTrackToken(null);
            }
          }
        } else {
          await AsyncStorage.removeItem(TRACK_KEY);
        }
      } catch {
        await AsyncStorage.removeItem(TRACK_KEY);
      }
    })();
  }, [loadPickup]);

  useEffect(() => {
    if (pickup) {
      setFleetOrigin({ lat: pickup.lat, lng: pickup.lng });
    }
  }, [pickup?.lat, pickup?.lng]);

  useEffect(() => {
    const map = mapRef.current;
    if (!map) return;
    holdBookMapForProgrammaticMove();
    const coords: LatLng[] = [];
    if (pickup) coords.push({ latitude: pickup.lat, longitude: pickup.lng });
    if (dropoff) coords.push({ latitude: dropoff.lat, longitude: dropoff.lng });
    if (coords.length === 0) {
      map.animateToRegion(
        {
          latitude: fleetOrigin.lat,
          longitude: fleetOrigin.lng,
          latitudeDelta: 0.06,
          longitudeDelta: 0.06,
        },
        350
      );
      return;
    }
    if (coords.length === 1) {
      map.animateToRegion(
        {
          latitude: coords[0].latitude,
          longitude: coords[0].longitude,
          latitudeDelta: 0.02,
          longitudeDelta: 0.02,
        },
        350
      );
      return;
    }
    map.fitToCoordinates(coords, {
      edgePadding: { top: 48, right: 48, bottom: 48, left: 48 },
      animated: true,
    });
  }, [
    pickup?.lat,
    pickup?.lng,
    dropoff?.lat,
    dropoff?.lng,
    fleetOrigin.lat,
    fleetOrigin.lng,
  ]);

  function zoomMap(delta: number) {
    bookUserMoving.current = false;
    bookFingerDown.current = false;
    scheduleBookLocationReturn();
    ignoreBookGestureUntil.current = Date.now() + 700;
    const map = mapRef.current;
    if (!map) return;
    map
      .getCamera()
      .then((cam) => {
        const nextZoom = Math.max(3, Math.min(20, (cam.zoom ?? 14) + delta));
        return map.animateCamera(
          {
            center: cam.center,
            heading: cam.heading,
            pitch: cam.pitch,
            zoom: nextZoom,
          },
          { duration: 180 }
        );
      })
      .catch(() => {
        /* ignore */
      });
  }

  function zoomTripMap(delta: number) {
    pauseFollow();
    ignoreMapGestureUntil.current = Date.now() + 600;
    const map = tripMapRef.current;
    if (!map) return;
    map
      .getCamera()
      .then((cam) => {
        const nextZoom = Math.max(3, Math.min(20, (cam.zoom ?? 14) + delta));
        return map.animateCamera(
          {
            center: cam.center,
            heading: cam.heading,
            pitch: cam.pitch,
            zoom: nextZoom,
          },
          { duration: 180 }
        );
      })
      .catch(() => {
        /* ignore */
      });
  }

  function focusTripTaxi() {
    if (!activeTaxiLoc) return;
    if (followTimer.current) clearTimeout(followTimer.current);
    ignoreMapGestureUntil.current = Date.now() + 1200;
    setFollowTaxi(true);
    tripMapRef.current?.animateCamera(
      { center: { latitude: activeTaxiLoc.lat, longitude: activeTaxiLoc.lng } },
      { duration: 450 }
    );
  }

  function focusAcceptedMap() {
    if (!driverComing) return;
    setLiveFollowUi(true);
    const reveal = () => ridesScrollRef.current?.scrollTo({ y: 0, animated: true });
    requestAnimationFrame(reveal);
    setTimeout(reveal, 80);
    setTimeout(reveal, 280);
    setTimeout(() => {
      if (activeTaxiLoc) focusTripTaxi();
      else fitTripRoute();
    }, 360);
  }

  function fitTripRoute() {
    setFollowTaxi(false);
    if (followTimer.current) clearTimeout(followTimer.current);
    ignoreMapGestureUntil.current = Date.now() + 1200;
    fitMapToPoints(
      tripMapRef.current,
      tripRoutePoints(live, tripRoute, activeTaxiLoc),
      TRIP_ROUTE_FIT_PADDING
    );
  }

  function fitMapToRoute() {
    holdBookMapForProgrammaticMove();
    const points: LatLng[] = [];
    if (routeCoords.length > 1) points.push(...routeCoords);
    if (pickup) points.push({ latitude: pickup.lat, longitude: pickup.lng });
    if (dropoff) points.push({ latitude: dropoff.lat, longitude: dropoff.lng });
    if (activeTaxiLoc) points.push({ latitude: activeTaxiLoc.lat, longitude: activeTaxiLoc.lng });
    fitMapToPoints(mapRef.current, points);
  }

  const showProfileInputs = !contactConfirmed || profileEditing;
  const showBookContactInputs = !contactConfirmed || bookContactEditing;

  function beginContactEdit(scope: 'profile' | 'book') {
    contactSnapshot.current = { firstName, lastName, phone, email };
    setProfileSaved(false);
    if (scope === 'profile') setProfileEditing(true);
    else setBookContactEditing(true);
  }

  function restoreContactSnapshot() {
    const snap = contactSnapshot.current;
    if (!snap) return;
    setFirstName(snap.firstName);
    setLastName(snap.lastName);
    setPhone(snap.phone);
    setEmail(snap.email);
    contactSnapshot.current = null;
    setFieldErrors((e) => ({
      ...e,
      firstName: undefined,
      lastName: undefined,
      phone: undefined,
      email: undefined,
    }));
  }

  function cancelContactEdit(scope: 'profile' | 'book') {
    restoreContactSnapshot();
    if (scope === 'profile') setProfileEditing(false);
    else setBookContactEditing(false);
  }

  function leaveContactEditModes() {
    if (profileEditing || bookContactEditing) restoreContactSnapshot();
    setProfileEditing(false);
    setBookContactEditing(false);
  }

  async function saveProfile() {
    setProfileSaved(false);
    if (profileSavedTimer.current) clearTimeout(profileSavedTimer.current);
    try {
      const nextFirst = firstName.trim();
      const nextLast = lastName.trim();
      const nextPhone = phone.trim();
      const nextEmail = email.trim();
      await AsyncStorage.setItem(
        PROFILE_KEY,
        JSON.stringify({
          first_name: nextFirst,
          last_name: nextLast,
          phone: nextPhone,
          email: nextEmail,
        })
      );
      contactSnapshot.current = null;
      setProfileEditing(false);
      setBookContactEditing(false);
      setContactConfirmed(contactDetailsFilled(nextFirst, nextLast, nextPhone, nextEmail));
      setProfileSaved(true);
      profileSavedTimer.current = setTimeout(() => setProfileSaved(false), 3000);
    } catch {
      setError(t("Profiel opslaan mislukt."));
    }
  }

  useEffect(() => {
    let cancelled = false;
    (async () => {
      if (!pickup || !dropoff) {
        setRouteCoords([]);
        setRouteMetrics(null);
        return;
      }
      // Direct schatting zodat prijs niet op OSRM hoeft te wachten.
      const fallback = estimateRouteMetrics(pickup, dropoff);
      setRouteMetrics(fallback);
      setRouteCoords([
        { latitude: pickup.lat, longitude: pickup.lng },
        { latitude: dropoff.lat, longitude: dropoff.lng },
      ]);
      try {
        const route = await fetchDrivingRoute(pickup, dropoff);
        if (cancelled) return;
        setRouteCoords(route.coordinates);
        setRouteMetrics({
          distance_meters: route.distance_meters,
          duration_seconds: route.duration_seconds,
        });
      } catch {
        /* fallback blijft staan */
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [pickup, dropoff]);

  const refreshQuote = useCallback(async () => {
    if (!pickup || !dropoff) {
      return;
    }
    const metrics = routeMetrics || estimateRouteMetrics(pickup, dropoff);
    try {
      const data = await fetchQuote({
        ...metrics,
        passengers: Math.max(1, Math.min(20, parseInt(passengers, 10) || 1)),
        pickup_lat: pickup.lat,
        pickup_lng: pickup.lng,
        pickup_at: formatPickupAtPayload(pickupAt),
        marketplace_radius_km: 50,
        ...baggagePayload,
      });
      if (dropoffRef.current) setQuote(data);
    } catch {
      /* houd vorige quote; lokale prijsindicatie blijft zichtbaar */
    }
  }, [pickup, dropoff, passengers, routeMetrics, baggagePayload, pickupAt]);

  const refreshFleet = useCallback(async () => {
    // Ook tijdens demo: echte rijdende taxi’s van deze omgeving blijven zichtbaar.
    const origin = pickup
      ? { lat: pickup.lat, lng: pickup.lng }
      : fleetOrigin;
    try {
      const { vehicles, radiusKm } = await fetchNearbyTaxis({
        lat: origin.lat,
        lng: origin.lng,
      });
      const range = Number.isFinite(radiusKm) && radiusKm > 0 ? radiusKm : 25;
      setFleetRangeKm(range);
      setNearbyTaxis(
        vehicles.filter(
          (taxi) =>
            !taxi.busy &&
            !isSameTaxi(taxi, activeTaxiLoc) &&
            haversineKm(origin.lat, origin.lng, taxi.lat, taxi.lng) <= range
        )
      );
    } catch {
      /* behoud vorige markers */
    }
  }, [pickup, fleetOrigin, activeTaxiLoc]);

  useEffect(() => {
    if (!demoActive) {
      stopDemoSimulation();
      return;
    }
    startDemoSimulation(fleetOrigin);
    let cancelled = false;
    (async () => {
      const coords = await peekCurrentCoords();
      if (cancelled || !coords) return;
      setFleetOrigin(coords);
      updateDemoSimulationCenter(coords);
    })();
    return () => {
      cancelled = true;
      stopDemoSimulation();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [demoActive]);

  useEffect(() => {
    if (!demoActive) return;
    updateDemoSimulationCenter(fleetOrigin);
  }, [demoActive, fleetOrigin.lat, fleetOrigin.lng]);

  useEffect(() => {
    refreshFleet();
    const t = setInterval(refreshFleet, demoActive ? 2500 : 4000);
    return () => clearInterval(t);
  }, [refreshFleet, demoActive]);

  useEffect(() => {
    if (!pickup || !dropoff) {
      return;
    }
    if (quoteTimer.current) clearTimeout(quoteTimer.current);
    quoteTimer.current = setTimeout(() => {
      refreshQuote();
    }, 350);
    return () => {
      if (quoteTimer.current) clearTimeout(quoteTimer.current);
    };
  }, [pickup, dropoff, passengers, routeMetrics, baggagePayload, pickupAt, refreshQuote]);

  const guestRidesRef = useRef(guestRides);
  guestRidesRef.current = guestRides;
  const trackTokenRef = useRef(trackToken);
  trackTokenRef.current = trackToken;

  const presentLongWaitCancel = useCallback(
    (
      rideLike: Partial<GuestRide> & {
        id?: number | null;
        token?: string | null;
        phase?: string | null;
        cancellation_reason?: string | null;
        cancellation_message?: string | null;
        pickup_address?: string | null;
        dropoff_address?: string | null;
        pickup_place_name?: string | null;
        dropoff_place_name?: string | null;
        pickup_at?: string | null;
        pickup_at_label?: string | null;
        from?: string | null;
        to?: string | null;
      },
      tokenHint?: string | null
    ) => {
      if (!isAutoUnacceptedCancel(rideLike)) return;
      const token =
        String(tokenHint || rideLike.token || '').trim() ||
        guestRidesRef.current.find((r) => Number(r.id) === Number(rideLike.id))?.token ||
        '';
      const key = rideArchiveKey({ id: rideLike.id, token }) || `id:${Number(rideLike.id) || 0}`;
      if (!key || key === 'id:0') return;
      if (longWaitCancelShownRef.current.has(key)) return;
      if (archivedKeys.includes(key)) {
        longWaitCancelShownRef.current.add(key);
        return;
      }
      const existing =
        guestRidesRef.current.find(
          (r) => (token && r.token === token) || (Number(r.id) > 0 && Number(r.id) === Number(rideLike.id))
        ) || null;
      const from =
        String(
          rideLike.from ||
            rideLike.pickup_address ||
            existing?.from ||
            ''
        ).trim() ||
        String(rideLike.pickup_place_name || existing?.from_place || '').trim();
      const to =
        String(
          rideLike.to ||
            rideLike.dropoff_address ||
            existing?.to ||
            ''
        ).trim() ||
        String(rideLike.dropoff_place_name || existing?.to_place || '').trim();
      const next: GuestRide = {
        id: Number(rideLike.id) || existing?.id || 0,
        token: token || existing?.token || '',
        at: existing?.at || Date.now(),
        from: from || existing?.from || '',
        to: to || existing?.to || '',
        from_place:
          rideLike.pickup_place_name !== undefined
            ? rideLike.pickup_place_name
            : existing?.from_place ?? null,
        to_place:
          rideLike.dropoff_place_name !== undefined
            ? rideLike.dropoff_place_name
            : existing?.to_place ?? null,
        phase: 'cancelled',
        status_label: existing?.status_label || t('Geannuleerd'),
        quoted_price: existing?.quoted_price ?? null,
        passengers: existing?.passengers ?? null,
        baggage_summary: existing?.baggage_summary ?? null,
        distance_km: existing?.distance_km ?? null,
        duration_seconds: existing?.duration_seconds ?? null,
        pickup_at:
          livePickupAt({
            pickup_at_label: rideLike.pickup_at_label,
            pickup_at: rideLike.pickup_at,
          }) ||
          existing?.pickup_at ||
          null,
        rating: existing?.rating ?? null,
        cancellation_message:
          rideLike.cancellation_message ||
          existing?.cancellation_message ||
          'De rit is geannuleerd omdat het wachten te lang duurde. Er is geen chauffeur gevonden.',
        cancellation_reason: 'auto_unaccepted',
        refund_message: existing?.refund_message ?? null,
        refund_business_days: existing?.refund_business_days ?? null,
        payment_status: existing?.payment_status ?? null,
        pickup_confirmation: existing?.pickup_confirmation ?? null,
      };
      longWaitCancelShownRef.current.add(key);
      setLongWaitCancelRide(next);
      setTab('rides');
    },
    [archivedKeys]
  );

  useEffect(() => {
    if (longWaitCancelRide) return;
    const archived = new Set(archivedKeys);
    const candidate =
      guestRides.find(
        (r) => isAutoUnacceptedCancel(r) && !archived.has(rideArchiveKey(r))
      ) || null;
    if (candidate) {
      presentLongWaitCancel(candidate);
      return;
    }
    if (isAutoUnacceptedCancel(live || {})) {
      presentLongWaitCancel(live as LiveRide, trackToken);
    }
  }, [guestRides, archivedKeys, longWaitCancelRide, live, trackToken, presentLongWaitCancel]);

  useEffect(() => {
    if (!trackToken) return;
    let cancelled = false;
    let timer: ReturnType<typeof setTimeout> | null = null;

    prevLivePhase.current = null;

    const tick = async () => {
      const tokens = new Set<string>();
      if (trackToken) tokens.add(trackToken);
      guestRidesRef.current.forEach((ride) => {
        if (ride.token && rideNeedsStatusRefresh(ride)) tokens.add(ride.token);
      });

      try {
        let list = guestRidesRef.current;
        for (const token of tokens) {
          if (cancelled) return;
          let data;
          try {
            data = await fetchLive(token);
          } catch {
            continue;
          }
          if (cancelled || !data.ride) continue;
          const ride = data.ride;
          if (token === trackToken) {
            const previousPhase = prevLivePhase.current;
            prevLivePhase.current = ride.phase;
            setLive(ride);
            notifyNewRideStatusUpdates(ride).catch(() => undefined);
            if (!chatOpenRef.current) {
              if (Number(ride.chat_unread_count || 0) > 0) {
                setChatAttention(true);
                notifyNewRideChat(ride, token).catch(() => undefined);
              } else {
                setChatAttention(false);
              }
            }
            const rideId = Number(ride.id) || 0;
            if (
              ride.phase === 'completed' &&
              previousPhase &&
              previousPhase !== 'completed' &&
              rideId > 0 &&
              !completedAnnounced.current.has(rideId)
            ) {
              completedAnnounced.current.add(rideId);
              if (ride.rating?.can_rate) {
                setRateStars(0);
                setRateComment('');
                setRateError(null);
                setRateModalToken(trackToken);
              }
            }
          }
          list = await updateGuestRide(token, guestPatchFromLive(ride));
          if (isAutoUnacceptedCancel(ride)) {
            presentLongWaitCancel({ ...ride, token }, token);
          }
        }
        if (cancelled) return;
        setGuestRides(list);
        const tracked = list.find((ride) => ride.token === trackToken);
        if (tracked && !rideNeedsLivePoll(tracked)) {
          await persistTrack(null);
          if (!cancelled) setTrackToken(null);
        }
        const stillActive = list.some((ride) => ride.token && rideNeedsStatusRefresh(ride));
        if (!stillActive) return;
        timer = setTimeout(tick, 2500);
      } catch {
        if (!cancelled) timer = setTimeout(tick, 2500);
      }
    };
    tick();
    return () => {
      cancelled = true;
      if (timer) clearTimeout(timer);
    };
  }, [trackToken, persistTrack, presentLongWaitCancel]);

  const idleActiveKey = trackToken
    ? ''
    : guestRides
        .filter((ride) => ride.token && rideNeedsStatusRefresh(ride))
        .map((ride) => ride.token)
        .sort()
        .join('|');

  useEffect(() => {
    if (trackToken || !idleActiveKey) return;
    const tokens = idleActiveKey.split('|').filter(Boolean);
    if (!tokens.length) return;
    let cancelled = false;
    let timer: ReturnType<typeof setTimeout> | null = null;

    const tick = async () => {
      let list = guestRidesRef.current;
      const stillNeed: string[] = [];
      for (const token of tokens) {
        if (cancelled) return;
        try {
          const data = await fetchLive(token);
          if (cancelled || !data.ride) continue;
          list = await updateGuestRide(token, guestPatchFromLive(data.ride));
          if (isAutoUnacceptedCancel(data.ride)) {
            presentLongWaitCancel({ ...data.ride, token }, token);
          } else if (rideNeedsStatusRefresh({ ...data.ride, token })) {
            stillNeed.push(token);
          }
        } catch {
          stillNeed.push(token);
        }
      }
      if (!cancelled) setGuestRides(list);
      if (!cancelled && stillNeed.length) timer = setTimeout(tick, 2500);
    };
    timer = setTimeout(tick, 400);
    return () => {
      cancelled = true;
      if (timer) clearTimeout(timer);
    };
  }, [trackToken, idleActiveKey, presentLongWaitCancel]);

  // Na standby/achtergrond: zoekritten opnieuw syncen (auto-cancel popup).
  useEffect(() => {
    const sub = AppState.addEventListener('change', (state) => {
      if (state !== 'active') return;
      const tokens = guestRidesRef.current
        .filter((ride) => ride.token && rideNeedsStatusRefresh(ride))
        .map((ride) => ride.token as string);
      if (!tokens.length) return;
      void (async () => {
        let list = guestRidesRef.current;
        for (const token of tokens) {
          try {
            const data = await fetchLive(token);
            if (!data.ride) continue;
            list = await updateGuestRide(token, guestPatchFromLive(data.ride));
            if (isAutoUnacceptedCancel(data.ride)) {
              presentLongWaitCancel({ ...data.ride, token }, token);
            }
          } catch {
            /* volgende rit */
          }
        }
        setGuestRides(list);
      })();
    });
    return () => sub.remove();
  }, [presentLongWaitCancel]);

  function scheduleSuggestions(query: string, kind: 'pickup' | 'dropoff') {
    const timerRef = kind === 'pickup' ? pickupSuggestTimer : dropoffSuggestTimer;
    const seqRef = kind === 'pickup' ? pickupSuggestSeq : dropoffSuggestSeq;
    const setter = kind === 'pickup' ? setPickupSuggestions : setDropoffSuggestions;
    if (timerRef.current) clearTimeout(timerRef.current);
    if (query.trim().length < 3) {
      setter([]);
      return;
    }
    const seq = ++seqRef.current;
    timerRef.current = setTimeout(async () => {
      try {
        const rows = await searchAddresses(query);
        if (seqRef.current !== seq) return;
        setter(rows);
      } catch {
        if (seqRef.current !== seq) return;
        setter([]);
      }
    }, 280);
  }

  function scrollBookToTop() {
    Keyboard.dismiss();
    const go = () => bookScrollRef.current?.scrollTo({ y: 0, animated: false });
    go();
    requestAnimationFrame(go);
    setTimeout(go, 50);
    setTimeout(go, 200);
    setTimeout(go, 450);
  }

  function goToBookTab() {
    Keyboard.dismiss();
    leaveContactEditModes();
    setBookMountKey((k) => k + 1);
    bookChromeOpacityRef.current = 0;
    setBookChromeOpacity(0);
    setTab('book');
    AsyncStorage.setItem(TAB_KEY, 'book').catch(() => undefined);
    setError(null);
    setProfileSaved(false);
    setShowArchivedRides(false);
  }

  function updateBookChromeFromScrollY(y: number) {
    // Snel volle bg: na ~12px scroll al solid — moet zichtbaar “klikken”.
    const next = y <= 2 ? 0 : Math.min(1, (y - 2) / 12);
    if (Math.abs(next - bookChromeOpacityRef.current) < 0.03 && next > 0 && next < 1) {
      return;
    }
    bookChromeOpacityRef.current = next;
    setBookChromeOpacity(next);
  }

  const openRidesBadgeKeys = useMemo(() => {
    const keys = new Set<string>();
    for (const ride of guestRides) {
      if (!isRidesTabBadgePhase(ride.phase)) continue;
      const key = rideArchiveKey(ride);
      if (key) keys.add(key);
    }
    if (hasActiveRide && live && isRidesTabBadgePhase(live.phase)) {
      const key = rideArchiveKey({ id: live.id, token: trackToken });
      if (key) keys.add(key);
    }
    return keys;
  }, [guestRides, hasActiveRide, live, trackToken]);

  const ridesBadgeSeenSet = useMemo(() => new Set(ridesBadgeSeenKeys), [ridesBadgeSeenKeys]);
  const ridesBadgeCount = useMemo(() => {
    let n = 0;
    for (const key of openRidesBadgeKeys) {
      if (!ridesBadgeSeenSet.has(key)) n += 1;
    }
    return n;
  }, [openRidesBadgeKeys, ridesBadgeSeenSet]);

  function acknowledgeRidesBadge() {
    if (openRidesBadgeKeys.size === 0) return;
    setRidesBadgeSeenKeys((prev) => {
      const next = new Set(prev);
      for (const key of openRidesBadgeKeys) next.add(key);
      return Array.from(next);
    });
  }

  function scrollToMap() {
    Keyboard.dismiss();
    // Net boven ophaaltijd: veld volledig zichtbaar + kiesbaar, map eronder.
    const go = () => {
      const anchor =
        pickupAtSectionY.current > 0
          ? pickupAtSectionY.current
          : Math.max(0, mapSectionY.current - 120);
      const y = Math.max(0, anchor - 16);
      bookScrollRef.current?.scrollTo({ y, animated: true });
    };
    requestAnimationFrame(go);
    setTimeout(go, 120);
    setTimeout(go, 320);
  }

  useEffect(() => {
    if (tab !== 'book') return;
    scrollBookToTop();
  }, [tab, bookMountKey]);

  function applySuggestion(item: AddressSuggestion, kind: 'pickup' | 'dropoff') {
    const label = bookingAddressLine(item.label) || item.label;
    const placeName = visiblePlaceName(item.placeName, label) || null;
    const point = { lat: item.lat, lng: item.lng, address: label, placeName };
    if (kind === 'pickup') {
      setPickup(point);
      setPickupQuery(label);
      setPickupSuggestions([]);
      // Alleen focus naar bestemming — scroll naar map pas na bestemming.
      focusDropoffField();
      return;
    }
    setDropoff(point);
    setDropoffQuery(label);
    setDropoffSuggestions([]);
    // Pas hier naar de map navigeren (na gekozen bestemming).
    scrollToMap();
  }

  function bumpBaggage(key: BaggageKey, delta: number, max: number) {
    setBaggage((prev) => ({
      ...prev,
      [key]: Math.max(0, Math.min(max, (prev[key] || 0) + delta)),
    }));
  }

  const passengerCount = Math.max(1, Math.min(20, parseInt(passengers, 10) || 1));

  function bumpPassengers(delta: number) {
    setPassengers(String(Math.max(1, Math.min(20, passengerCount + delta))));
  }

  function clearBookingDropoff() {
    if (dropoffSuggestTimer.current) clearTimeout(dropoffSuggestTimer.current);
    dropoffSuggestSeq.current += 1;
    setDropoffQuery('');
    setDropoff(null);
    setDropoffSuggestions([]);
    setQuote(null);
    setFieldErrors((prev) => ({ ...prev, dropoff: undefined, offer: undefined }));
  }

  function collectBookErrors(): typeof fieldErrors {
    const next: typeof fieldErrors = {};
    if (!pickup) next.pickup = t("Kies een ophaaladres uit de suggesties.");
    if (!dropoff) next.dropoff = t("Kies een bestemming uit de suggesties.");
    if (isPickupAtInPast(pickupAt)) {
      next.pickupAt = t("Kies een ophaalmoment in de toekomst.");
    }
    if (firstName.trim().length < 2) next.firstName = t("Vul je voornaam in.");
    if (lastName.trim().length < 2) next.lastName = t("Vul je achternaam in.");
    if (phone.trim().length < 8) next.phone = t("Vul een geldig telefoonnummer in.");
    if (!email.trim() || !email.includes('@')) {
      next.email = t("Vul een e-mailadres in voor de betaling.");
    }
    if (pickup && dropoff && !offer && !routeMetrics) {
      next.offer = t("Wacht tot de prijs berekend is.");
    }
    return next;
  }

  async function onBook() {
    Keyboard.dismiss();
    const next = collectBookErrors();
    setFieldErrors(next);

    if (Object.keys(next).length > 0) {
      setError(null);
      bookScrollRef.current?.scrollTo({ y: 0, animated: true });
      return;
    }

    // Button is disabled when !canDispatch; yellow warn above covers UX — no red/alert duplicate.
    if (!canDispatch) return;

    // Ook in demo-modus: echte quote/boeking/dispatch, zodat virtuele én echte
    // chauffeurs het aanbod ontvangen.
    let paymentQuote = quote;
    if (!paymentQuote?.payment && pickup && dropoff) {
      try {
        const metrics = routeMetrics || estimateRouteMetrics(pickup, dropoff);
        paymentQuote = await fetchQuote({
          ...metrics,
          passengers: Math.max(1, Math.min(20, parseInt(passengers, 10) || 1)),
          pickup_lat: pickup.lat,
          pickup_lng: pickup.lng,
          pickup_at: formatPickupAtPayload(pickupAt),
          marketplace_radius_km: 50,
          ...baggagePayload,
        });
        setQuote(paymentQuote);
      } catch {
        const msg = t("De prijs kon niet worden opgehaald. Probeer het zo opnieuw.");
        setError(msg);
        Alert.alert(t('Prijs niet beschikbaar'), msg);
        return;
      }
    }

    if (
      paymentQuote?.payment?.deferred_until_taxi ||
      paymentQuote?.marketplace?.taxi_available === false
    ) {
      const msg =
        paymentQuote?.marketplace?.unavailable_message ||
        t("Er is momenteel geen taxi beschikbaar in de buurt van deze ophaallocatie.");
      setError(msg);
      Alert.alert(t('Geen taxi beschikbaar'), msg);
      return;
    }

    if (paymentQuote?.payment?.mollie_configured === false) {
      const msg =
        t("Online betalen staat uit omdat de Mollie-koppeling van Nexa Suite niet is ingesteld.");
      setError(msg);
      Alert.alert(t('Betaling niet beschikbaar'), msg);
      return;
    }

    if (!paymentQuote?.payment?.booking) {
      const msg = t("Online betalen is momenteel niet beschikbaar. Probeer het later opnieuw.");
      setError(msg);
      Alert.alert(t('Betaling niet beschikbaar'), msg);
      return;
    }

    const bookedOffer = paymentQuote?.offers?.[0] || offer;
    if (!pickup || !dropoff || !bookedOffer) return;

    setBusy(true);
    setError(null);
    setFieldErrors({});
    try {
      const metrics = routeMetrics || (await fetchDrivingRoute(pickup, dropoff));
      const data = await bookGuest({
        distance_meters: metrics.distance_meters,
        duration_seconds: metrics.duration_seconds,
        passengers: Math.max(1, Math.min(20, parseInt(passengers, 10) || 1)),
        pickup_address: pickup.address,
        dropoff_address: dropoff.address,
        pickup_place_name: pickup.placeName || null,
        dropoff_place_name: dropoff.placeName || null,
        pickup_lat: pickup.lat,
        pickup_lng: pickup.lng,
        dropoff_lat: dropoff.lat,
        dropoff_lng: dropoff.lng,
        pickup_at: formatPickupAtPayload(pickupAt),
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        phone: phone.trim(),
        email: email.trim(),
        remarks: remarks.trim() || null,
        selected_offer_id: bookedOffer.id || null,
        payment_method: 'booking',
        return_url: CUSTOMER_PAYMENT_RETURN_URL,
        marketplace_radius_km: 50,
        ...baggagePayload,
      });

      setTrackToken(data.track_token);
      await persistTrack(data.track_token);
      if (data.live) setLive(data.live);
      const next = await saveGuestRide(data.ride_request_id, data.track_token, {
        ...guestPatchFromLive(data.live || {}),
        from: pickup.address,
        to: dropoff.address,
        from_place:
          data.live && 'pickup_place_name' in data.live
            ? data.live.pickup_place_name || null
            : pickup.placeName || null,
        to_place:
          data.live && 'dropoff_place_name' in data.live
            ? data.live.dropoff_place_name || null
            : dropoff.placeName || null,
        phase: data.live?.phase || 'awaiting_payment',
        status_label: data.live?.status_label || t("Betaling"),
        quoted_price: data.live?.quoted_price ?? bookedOffer.price ?? null,
        passengers: data.live?.passengers ?? passengerCount,
        baggage_summary:
          data.live?.baggage?.summary ||
          formatBaggageSummary(baggagePayload.baggage, baggagePayload.special_baggage),
        pickup_at: livePickupAt(data.live) || formatPickupAtPayload(pickupAt),
        distance_km:
          data.live?.distance_km ??
          (metrics.distance_meters ? Math.round(metrics.distance_meters / 100) / 10 : null),
        duration_seconds: data.live?.duration_seconds ?? metrics.duration_seconds,
      });
      setGuestRides(next);
      setExpandedRideKey(rideArchiveKey({ id: data.ride_request_id, token: data.track_token }));
      await saveProfile();

      // Open Mollie before MapView/dropoff churn — Android Linking.openURL after
      // those commits often never shows (or flashes and returns).
      const checkout = data.checkout_url || data.live?.checkout_url;
      if (checkout) {
        try {
          const opened = await openMollieCheckout(checkout);
          if (opened.returnUrl) {
            await applyPaymentReturnUrl(opened.returnUrl);
          }
        } catch {
          setError(
            t("Betalingslink kon niet worden geopend. Tik op Volgen en probeer opnieuw te betalen.")
          );
        }
      } else if (data.payment_required) {
        setError(t("Betalingslink kon niet worden geopend. Tik op Volgen en probeer opnieuw te betalen."));
      }

      clearBookingDropoff();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t("Boeken mislukt."));
      Alert.alert(t('Boeken mislukt'), e instanceof ApiError ? e.message : t("Boeken mislukt."));
    } finally {
      setBusy(false);
    }
  }

  function requestCancel(rideToken?: string | null) {
    const token = String(rideToken || trackToken || '').trim();
    if (!token || busy) return;
    if (token !== trackToken) {
      setTrackToken(token);
      void persistTrack(token);
    }
    setCancelTargetToken(token);
    setCancelConfirmOpen(true);
  }

  async function confirmCancel() {
    const token = String(cancelTargetToken || trackToken || '').trim();
    if (!token) return;
    setCancelConfirmOpen(false);
    setCancelTargetToken(null);
    setBusy(true);
    setError(null);
    try {
      const data = await cancelLive(token);
      if (data.ride) {
        setLive(data.ride);
        const next = await updateGuestRide(token, guestPatchFromLive(data.ride));
        setGuestRides(next);
        setExpandedRideKey(rideArchiveKey({ id: data.ride.id, token }));
      }
      await persistTrack(null);
      setTrackToken(null);
      setTab('rides');
      AsyncStorage.setItem(TAB_KEY, 'rides').catch(() => undefined);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t("Annuleren mislukt."));
    } finally {
      setBusy(false);
    }
  }

  async function answerPickup(token: string, answer: 'yes' | 'no') {
    const track = String(token || '').trim();
    if (!track || pickupAnswerToken) return;
    setPickupAnswerToken(track);
    setError(null);
    try {
      const data = await confirmPickupLive(track, answer);
      if (data.ride) {
        if (track === trackToken) setLive(data.ride);
        const next = await updateGuestRide(track, guestPatchFromLive(data.ride));
        setGuestRides(next);
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t("Bevestigen mislukt."));
    } finally {
      setPickupAnswerToken(null);
    }
  }

  async function onWait() {
    if (!trackToken) return;
    setBusy(true);
    setError(null);
    try {
      const data = await waitLive(trackToken);
      if (data.ride) {
        setLive(data.ride);
        const next = await updateGuestRide(trackToken, guestPatchFromLive(data.ride));
        setGuestRides(next);
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t("Doorgaan mislukt."));
    } finally {
      setBusy(false);
    }
  }

  const showWaitDecisionModal = !!(
    live &&
    trackToken &&
    live.needs_unaccepted_decision &&
    !longWaitCancelRide &&
    !cancelConfirmOpen
  );

  useEffect(() => {
    if (!showWaitDecisionModal || !live) return;
    const key = `decision:${Number(live.id) || trackToken || ''}`;
    if (!key || key === 'decision:0' || waitDecisionNotifiedRef.current.has(key)) return;
    waitDecisionNotifiedRef.current.add(key);
    setTab('rides');
    AsyncStorage.setItem(TAB_KEY, 'rides').catch(() => undefined);
    void (async () => {
      try {
        const Notifications = await import('expo-notifications');
        const perm = await Notifications.getPermissionsAsync();
        if (!perm.granted) {
          const asked = await Notifications.requestPermissionsAsync();
          if (!asked.granted) return;
        }
        await Notifications.scheduleNotificationAsync({
          content: {
            title: t('Nog geen taxi gevonden'),
            body: t('Wil je blijven wachten of de rit annuleren?'),
            sound: true,
            data: { type: 'wait_decision', ride_id: live.id, token: trackToken },
            ...(Platform.OS === 'ios' ? { interruptionLevel: 'timeSensitive' as const } : {}),
          },
          trigger: null,
        });
      } catch {
        /* melding is best-effort; modal blijft zichtbaar */
      }
    })();
  }, [showWaitDecisionModal, live, trackToken]);

  async function onPay() {
    if (!trackToken) return;
    setBusy(true);
    setError(null);
    try {
      // Always mint/sync a fresh Mollie link — a cached live.checkout_url may be
      // canceled; Mollie then redirects instantly and Android looks like "no page".
      const data = await payLive(trackToken, CUSTOMER_PAYMENT_RETURN_URL);
      if (data.ride) setLive(data.ride);
      const checkout = data.checkout_url || live?.checkout_url;
      if (!checkout) {
        setError(t("Betalingslink kon niet worden geopend. Tik op Volgen en probeer opnieuw te betalen."));
        return;
      }
      const opened = await openMollieCheckout(checkout);
      if (opened.returnUrl) {
        await applyPaymentReturnUrl(opened.returnUrl);
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t("Betaling starten mislukt."));
    } finally {
      setBusy(false);
    }
  }

  const refreshChat = useCallback(async (token: string) => {
    try {
      const res = await fetchLiveChat(token);
      const msgs = (res.data?.messages || []) as RideChatMessageDto[];
      setChatMessages(
        msgs.map((m) => ({
          id: m.id,
          from: m.from,
          body: m.body,
          at: m.at,
        }))
      );
      if (typeof res.data?.allowed === 'boolean') {
        setChatAllowed(res.data.allowed);
      }
    } catch {
      /* chat poll best-effort */
    }
  }, []);

  useEffect(() => {
    if (!chatOpen || !trackToken) return;
    void refreshChat(trackToken);
    const id = setInterval(() => {
      void refreshChat(trackToken);
    }, 4000);
    return () => clearInterval(id);
  }, [chatOpen, trackToken, refreshChat]);

  function openRideChat() {
    if (!trackToken) return;
    setChatAttention(false);
    setChatOpen(true);
    void refreshChat(trackToken);
  }

  /**
   * Tik op chat-melding: open chat én Ritten-tab met die rit eronder,
   * zodat sluiten van de popup de rit toont.
   */
  function openRideChatFromNotification(data: Record<string, unknown>) {
    const rideId = Number(data.rideId || data.ride_id || 0);
    const tokenFromPush = String(data.track_token || data.token || '').trim();
    const guests = guestRidesRef.current;
    const guest =
      (tokenFromPush
        ? guests.find((r) => r.token === tokenFromPush)
        : undefined) ||
      (rideId > 0 ? guests.find((r) => Number(r.id) === rideId) : undefined) ||
      (trackTokenRef.current
        ? guests.find((r) => r.token === trackTokenRef.current)
        : undefined) ||
      guests.find((r) => isActiveRidePhase(r.phase)) ||
      null;

    setShowArchivedRides(false);
    setTab('rides');
    AsyncStorage.setItem(TAB_KEY, 'rides').catch(() => undefined);

    if (guest) {
      setExpandedRideKey(rideArchiveKey(guest));
      if (guest.token !== trackTokenRef.current) {
        loadRideLive(guest);
      } else {
        setTrackToken(guest.token);
        void persistTrack(
          isActiveRidePhase(guest.phase) ? guest.token : null
        );
      }
      setChatAttention(false);
      setChatOpen(true);
      void refreshChat(guest.token);
      return;
    }

    const fallback = tokenFromPush || trackTokenRef.current;
    if (!fallback) return;
    setTrackToken(fallback);
    void persistTrack(fallback);
    setChatAttention(false);
    setChatOpen(true);
    void refreshChat(fallback);
  }

  const openRideChatFromNotificationRef = useRef(openRideChatFromNotification);
  openRideChatFromNotificationRef.current = openRideChatFromNotification;

  // Tik op chat-push → open chat + rit op Ritten.
  useEffect(() => {
    let sub: { remove: () => void } | null = null;
    let cancelled = false;
    let lastKey: string | null = null;
    const handleResponse = (response: {
      notification?: {
        date?: number;
        request?: {
          identifier?: string;
          content?: { data?: Record<string, unknown> };
        };
      };
    }) => {
      const data = (response.notification?.request?.content?.data || {}) as Record<
        string,
        unknown
      >;
      if (String(data.type || '') !== 'customer_ride_chat') return;
      if (!data.open_chat) return;
      const key = `${String(response.notification?.request?.identifier || '')}|${Number(response.notification?.date || 0)}`;
      if (lastKey === key) return;
      lastKey = key;
      openRideChatFromNotificationRef.current(data);
    };
    void (async () => {
      try {
        const Notifications = await import('expo-notifications');
        if (cancelled) return;
        const last = await Notifications.getLastNotificationResponseAsync();
        if (!cancelled && last) handleResponse(last);
        sub = Notifications.addNotificationResponseReceivedListener((response) => {
          try {
            handleResponse(response);
          } catch {
            /* ignore */
          }
        });
      } catch {
        /* Expo Go / geen native module */
      }
    })();
    return () => {
      cancelled = true;
      sub?.remove();
    };
  }, []);

  function callAssignedDriver() {
    void callViaCentralRelay(live?.company?.phone);
  }

  async function sendRideChatMessage(body: string) {
    if (!trackToken) return;
    setChatSending(true);
    try {
      const res = await sendLiveChat(trackToken, body, newUuidV4());
      const msgs = (res.data?.messages || []) as RideChatMessageDto[];
      setChatMessages(
        msgs.map((m) => ({
          id: m.id,
          from: m.from,
          body: m.body,
          at: m.at,
        }))
      );
      if (typeof res.data?.allowed === 'boolean') {
        setChatAllowed(res.data.allowed);
      }
    } catch (e) {
      Alert.alert(
        t('Chat'),
        e instanceof ApiError ? e.message : t('Bericht versturen mislukt.')
      );
    } finally {
      setChatSending(false);
    }
  }

  function openRateModal(token: string) {
    setRateStars(0);
    setRateComment('');
    setRateError(null);
    setRateModalToken(token);
  }

  async function onDownloadInvoice(token: string) {
    const url = `${await resolveApiBaseUrl()}/api/taxi/v1/customer/live/invoice?token=${encodeURIComponent(token)}`;
    try {
      await Linking.openURL(url);
    } catch {
      setError(t("Factuur downloaden mislukt."));
    }
  }

  async function onSubmitRating(token: string) {
    if (rateStars < 1) {
      setRateError(t("Kies 1 tot 5 sterren."));
      return;
    }
    setBusy(true);
    setRateError(null);
    try {
      const data = await rateLive(token, rateStars, rateComment.trim() || null);
      const rating =
        data.ride?.rating ||
        data.rating || {
          rated: true,
          stars: rateStars,
          comment: rateComment.trim() || null,
          can_rate: false,
        };
      if (data.ride) {
        setLive({ ...data.ride, rating });
      } else if (live && trackToken === token) {
        setLive({ ...live, rating });
      }
      const next = await updateGuestRide(token, {
        ...(data.ride ? guestPatchFromLive(data.ride) : {}),
        rating,
      });
      setGuestRides(next);
      setRateStars(0);
      setRateComment('');
      setRateModalToken(null);
    } catch (e) {
      setRateError(e instanceof ApiError ? e.message : t("Beoordeling opslaan mislukt."));
    } finally {
      setBusy(false);
    }
  }

  function openComplaintModal(token: string) {
    const guest = guestRides.find((r) => r.token === token);
    const liveMatch = live && trackToken === token ? live : null;
    const rideId = Number(liveMatch?.id || guest?.id || 0) || 0;
    const companyName = String(
      liveMatch?.company?.name || guest?.company_name || ''
    ).trim();
    const subject =
      rideId > 0
        ? companyName
          ? `Klacht over rit #${rideId} bij ${companyName}`
          : `Klacht over rit #${rideId}`
        : companyName
          ? `Klacht bij ${companyName}`
          : '';
    setComplaintCategory('other');
    setComplaintSubject(subject);
    setComplaintDescription('');
    setComplaintPhone(phone.trim());
    setComplaintEmail(email.trim());
    setComplaintError(null);
    setComplaintModalToken(token);
  }

  async function onSubmitComplaint(token: string) {
    if (complaintDescription.trim().length < 10) {
      setComplaintError(t('Omschrijf de klacht in minimaal 10 tekens.'));
      return;
    }
    setBusy(true);
    setComplaintError(null);
    try {
      const data = await complainLive(token, {
        category: complaintCategory,
        description: complaintDescription.trim(),
        subject: complaintSubject.trim() || null,
        customer_phone: complaintPhone.trim() || null,
        customer_email: complaintEmail.trim() || null,
        preferred_contact: 'email',
      });
      const complaint =
        data.ride?.complaint ||
        data.complaint || {
          filed: true,
          can_file: false,
          complaint_id: data.complaint_id ?? null,
        };
      if (data.ride) {
        setLive({ ...data.ride, complaint });
      } else if (live && trackToken === token) {
        setLive({ ...live, complaint });
      }
      const next = await updateGuestRide(token, {
        ...(data.ride ? guestPatchFromLive(data.ride) : {}),
        complaint,
      });
      setGuestRides(next);
      setComplaintModalToken(null);
      Alert.alert(t('Klacht ontvangen'), data.message || t('We nemen je melding in behandeling.'));
    } catch (e) {
      setComplaintError(e instanceof ApiError ? e.message : t('Klacht indienen mislukt.'));
    } finally {
      setBusy(false);
    }
  }

  function loadRideLive(ride: GuestRide) {
    setTrackToken(ride.token);
    persistTrack(isActiveRidePhase(ride.phase) ? ride.token : null);
    setError(null);
    fetchLive(ride.token)
      .then((data) => {
        if (!data.ride) return;
        setLive(data.ride);
        notifyNewRideStatusUpdates(data.ride).catch(() => undefined);
        return updateGuestRide(ride.token, guestPatchFromLive(data.ride)).then(setGuestRides);
      })
      .catch(() => undefined);
  }

  function openRideFollow(ride: GuestRide) {
    setExpandedRideKey(rideArchiveKey(ride));
    setShowArchivedRides(false);
    setTab('rides');
    AsyncStorage.setItem(TAB_KEY, 'rides').catch(() => undefined);
    loadRideLive(ride);
  }

  function toggleRideExpanded(ride: GuestRide) {
    const key = rideArchiveKey(ride);
    if (expandedRideKey === key) {
      setExpandedRideKey(null);
      return;
    }
    setExpandedRideKey(key);
    loadRideLive(ride);
  }

  function toggleArchiveRide(ride: GuestRide, archived: boolean) {
    if (rideArchiveMotion) return;
    const key = rideArchiveKey(ride);
    pendingRideArchiveRef.current = { ride, archived };
    setRideArchiveMotion({
      key,
      mode: archived ? 'archive-out' : 'restore-out',
    });
  }

  const finishRideArchiveMotion = useCallback(() => {
    const pending = pendingRideArchiveRef.current;
    pendingRideArchiveRef.current = null;
    setRideArchiveMotion(null);
    if (!pending) return;
    void setRideArchived(pending.ride, pending.archived).then((keys) => {
      setArchivedKeys(keys);
    });
  }, []);

  const routeSummary = useMemo(() => {
    const metrics = routeMetrics;
    if (!metrics) return t("— km · — min");
    const km = (metrics.distance_meters / 1000).toFixed(1).replace('.', ',');
    const mins = Math.max(1, Math.round(metrics.duration_seconds / 60));
    return `${km} km · ca. ${mins} min`;
  }, [routeMetrics]);

  const displayPrice = useMemo(() => {
    if (offer?.price_label) return offer.price_label;
    if (offer?.price != null) return formatEuroNl(Number(offer.price));
    if (routeMetrics) {
      return formatEuroNl(
        estimateFareEuro(routeMetrics.distance_meters, routeMetrics.duration_seconds)
      );
    }
    return null;
  }, [offer, routeMetrics]);

  const mapBleed = USE_NEXA_BOOKING_REDESIGN;
  const mapBlock = (
    <View style={[styles.mapWrap, mapBleed && styles.mapWrapRounded]}>
      <MapView
        ref={mapRef}
        style={styles.map}
        provider={PROVIDER_GOOGLE}
        {...(colorScheme === 'dark' ? { customMapStyle: DARK_MAP_STYLE } : {})}
        initialRegion={mapRegion}
        showsUserLocation
        showsMyLocationButton={false}
        scrollEnabled
        zoomEnabled
        pitchEnabled={false}
        rotateEnabled={false}
        moveOnMarkerPress={false}
        onTouchStart={noteBookMapTouchStart}
        onTouchEnd={noteBookMapTouchEnd}
        onTouchCancel={noteBookMapTouchEnd}
        onPanDrag={noteBookMapGesture}
        onRegionChangeStart={(_region, details) => {
          if (details?.isGesture) noteBookMapGesture();
        }}
        onRegionChange={(_region, details) => {
          if (details?.isGesture) noteBookMapGesture();
        }}
        onRegionChangeComplete={(_region, details) => {
          if (details?.isGesture) noteBookMapGestureEnd();
        }}
      >
        {pickup ? (
          <Marker
            coordinate={{ latitude: pickup.lat, longitude: pickup.lng }}
            title={t("Ophalen")}
            anchor={{ x: 0.5, y: 1 }}
            tracksViewChanges={false}
            zIndex={12}
          >
            {USE_NEXA_BOOKING_REDESIGN ? (
              <MapPlaceCallout
                label={t('Ophaalpunt')}
                address={String(pickup.placeName || pickup.address || t('Ophalen'))}
                tone="blue"
                icon="person"
              />
            ) : (
              <View style={styles.pickupDotOuter}>
                <View style={styles.pickupDotInner} />
              </View>
            )}
          </Marker>
        ) : null}
        {dropoff ? (
          USE_NEXA_BOOKING_REDESIGN ? (
            <Marker
              coordinate={{ latitude: dropoff.lat, longitude: dropoff.lng }}
              title={t("Bestemming")}
              anchor={{ x: 0.5, y: 1 }}
              tracksViewChanges={false}
              zIndex={12}
            >
              <MapPlaceCallout
                label={t('Bestemming')}
                address={String(dropoff.placeName || dropoff.address || t('Bestemming'))}
                tone="orange"
                icon="flag"
              />
            </Marker>
          ) : (
            <Marker
              coordinate={{ latitude: dropoff.lat, longitude: dropoff.lng }}
              title={t("Bestemming")}
              image={dropPinGreen}
              anchor={{ x: 0.5, y: 1 }}
              tracksViewChanges={false}
            />
          )
        ) : null}
        {activeTaxiLoc
          ? Platform.OS === 'android'
            ? androidTaxiMarker(
                {
                  id: 'active-ride',
                  lat: activeTaxiLoc.lat,
                  lng: activeTaxiLoc.lng,
                  heading: activeTaxiLoc.heading,
                },
                t('Je taxi'),
                30
              )
            : (
                <CustomerTaxiMarker
                  key="active-ride-taxi"
                  title={t('Je taxi')}
                  taxi={{
                    id: 'active-ride',
                    lat: activeTaxiLoc.lat,
                    lng: activeTaxiLoc.lng,
                    heading: activeTaxiLoc.heading,
                  }}
                />
              )
          : null}
        {/* Android: Marker rechtstreeks (geen wrapper-component) — anders 1 marker. */}
        {Platform.OS === 'android'
          ? visibleFleetTaxis.map((taxi) => androidTaxiMarker(taxi))
          : visibleFleetTaxis.map((taxi) => (
              <CustomerTaxiMarker
                key={taxi.id}
                taxi={taxi}
                smoothMs={demoActive ? 1500 : 3800}
                stepMs={demoActive ? 200 : 100}
              />
            ))}
        {routeCoords.length > 1 ? (
          <Polyline
            coordinates={routeCoords}
            strokeColor={USE_NEXA_BOOKING_REDESIGN ? NEON_ROUTE : colors.primary}
            strokeWidth={USE_NEXA_BOOKING_REDESIGN ? 5 : 4}
            lineCap="round"
            lineJoin="round"
          />
        ) : null}
      </MapView>
      <View style={styles.zoomControls} pointerEvents="box-none">
        <Pressable
          onPress={() => zoomMap(1)}
          style={styles.zoomBtn}
          accessibilityLabel={t("Inzoomen")}
        >
          <Text style={styles.zoomBtnText}>+</Text>
        </Pressable>
        <Pressable
          onPress={() => zoomMap(-1)}
          style={styles.zoomBtn}
          accessibilityLabel={t("Uitzoomen")}
        >
          <Text style={styles.zoomBtnText}>−</Text>
        </Pressable>
      </View>
      <View style={styles.mapBottomRightControls} pointerEvents="box-none">
        {pickup && dropoff ? (
          <Pressable
            onPress={fitMapToRoute}
            style={styles.zoomBtn}
            accessibilityLabel={t("Toon route")}
            accessibilityHint={t("Centreert de kaart op de geplande rit")}
          >
            <Ionicons name="navigate" size={18} color={colors.text} />
          </Pressable>
        ) : null}
        <Pressable
          onPress={() => {
            bookUserMoving.current = false;
            bookFingerDown.current = false;
            void moveBookMapToCurrentLocation(true);
          }}
          style={styles.zoomBtn}
          accessibilityRole="button"
          accessibilityLabel={t("Huidige locatie")}
        >
          <Ionicons name="locate" size={18} color={colors.text} />
        </Pressable>
      </View>
      {demoActive ? (
        <View style={styles.demoBadge} pointerEvents="none">
          <Text style={styles.demoBadgeText}>DEMO</Text>
        </View>
      ) : null}
      <View style={[styles.fleetBadge, mapBleed && styles.fleetBadgeNexa]}>
        {mapBleed ? (
          <View style={styles.routePill}>
            <Ionicons name="car-sport" size={14} color="#fff" />
            <Text style={[styles.fleetBadgeText, { color: '#FFFFFF' }]}>
              {routeMetrics
                ? `Ca. ${routeSummary}`
                : freeNearby === 1
                  ? t('{n} taxi online', { n: freeNearby })
                  : t('{n} taxi’s online', { n: freeNearby })}
            </Text>
          </View>
        ) : (
          <Text style={styles.fleetBadgeText}>
            {freeNearby === 1
              ? t('{n} taxi online', { n: freeNearby })
              : t('{n} taxi’s online', { n: freeNearby })}
          </Text>
        )}
      </View>
    </View>
  );

  const headerTitle =
    tab === 'rides'
      ? showArchivedRides
        ? t("Archief")
        : rideMapOpen
          ? t("Rit")
          : t("Ritten")
      : tab === 'profile' || tab === 'more'
        ? t("Profiel")
        : tab === 'home' || (USE_NEXA_BOOKING_REDESIGN && tab === 'book')
          ? null
          : t("Boeken");

  // Dark map → witte chrome. Light map-asset is bleek → dark ink chrome.
  const headerOnDark = USE_NEXA_BOOKING_REDESIGN && colorScheme === 'dark';
  // Home + Boeken: chrome zit in de hero-scene (mockup), geen dubbele AppPageHeader.
  const appHeader =
    USE_NEXA_BOOKING_REDESIGN && (tab === 'home' || tab === 'book') ? null : (
    <AppPageHeader
      title={headerTitle}
      chrome={headerOnDark ? 'onDark' : 'theme'}
      onLogoPress={() => {
        if (USE_NEXA_BOOKING_REDESIGN) {
          setTab('home');
          AsyncStorage.setItem(TAB_KEY, 'home').catch(() => undefined);
          return;
        }
        goToBookTab();
      }}
      left={
        <Pressable
          onPress={onBack}
          hitSlop={10}
          accessibilityRole="button"
          accessibilityLabel={t("Naar startpagina")}
          style={[
            styles.bookStickyBack,
            !headerOnDark && colorScheme === 'light' && styles.bookStickyBackLight,
          ]}
        >
          <Ionicons
            name="home-outline"
            size={24}
            color={
              headerOnDark
                ? '#FFFFFF'
                : colorScheme === 'light'
                  ? nexaBookingTheme.color.ink
                  : colors.text
            }
          />
        </Pressable>
      }
      right={<AppearanceControls tone={headerOnDark ? 'onDark' : 'theme'} />}
    />
  );

  const liveStatusFromUpdates = (() => {
    const updates = live?.status_updates;
    if (!Array.isArray(updates) || updates.length === 0) return null;
    const latest = updates[updates.length - 1];
    const label = String(latest?.label || '').trim();
    return label || null;
  })();

  const invoiceSentLabel = (() => {
    const updates = live?.status_updates;
    if (!Array.isArray(updates)) return null;
    for (let i = updates.length - 1; i >= 0; i -= 1) {
      const update = updates[i];
      if (update?.event !== 'invoice_sent') continue;
      const label = String(update.label || '').trim();
      return label || 'De factuur is naar je verstuurd.';
    }
    return null;
  })();

  const activeGuestRide = guestRides.find((r) => isActiveRidePhase(r.phase)) || null;
  const activeBannerRide =
    (live && trackToken && isActiveRidePhase(live.phase)
      ? {
          token: trackToken,
          id: live.id,
          from: live.pickup_address || pickup?.address || '',
          to: live.dropoff_address || dropoff?.address || '',
          // Bedrijfs-/plaatsnaam heeft voorrang op straatadres (zoals e-mails / map-callouts).
          from_place:
            live.pickup_place_name ||
            pickup?.placeName ||
            activeGuestRide?.from_place ||
            null,
          to_place:
            live.dropoff_place_name ||
            dropoff?.placeName ||
            activeGuestRide?.to_place ||
            null,
          phase: live.phase,
          status_label: liveStatusFromUpdates || live.status_label,
          at: Date.now(),
          quoted_price: live.quoted_price,
        }
      : activeGuestRide) || null;

  const quoteDistance = routeMetrics
    ? formatTripDistance(routeMetrics.distance_meters / 1000)
    : null;
  const quoteDuration = routeMetrics
    ? formatTripDuration(routeMetrics.duration_seconds)
    : null;
  const quotePassengersLabel = String(passengerCount);
  const showQuoteMetrics = !!(quoteDistance || quoteDuration || quotePassengersLabel);

  const priceBlock = displayPrice ? (
    <Card>
      <Text style={[styles.section, styles.sectionCenter]}>{t("Rit & prijs")}</Text>
      {USE_NEXA_BOOKING_REDESIGN ? (
        <View
          style={{
            width: '100%',
            height: 148,
            marginBottom: 8,
            borderRadius: 16,
            overflow: 'hidden',
            backgroundColor: colorScheme === 'light' ? '#0B1220' : '#060F1F',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Image
            source={require('../../assets/nexa-booking/crops/vehicle_standard_reference.png')}
            style={{ width: '100%', height: '100%' }}
            resizeMode="contain"
          />
        </View>
      ) : null}
      {showQuoteMetrics ? (
        <View style={styles.quoteMetricsBox}>
          <View style={styles.tripMetrics}>
            {quoteDistance ? (
              <View style={styles.tripMetric}>
                <Ionicons name="navigate-outline" size={18} color={colors.muted} />
                <Text style={styles.tripMetricValue}>{quoteDistance}</Text>
                <Text style={styles.tripMetricLabel}>{t("afstand")}</Text>
              </View>
            ) : null}
            {quoteDistance && (quoteDuration || quotePassengersLabel) ? (
              <View style={styles.tripMetricDivider} />
            ) : null}
            {quoteDuration ? (
              <View style={styles.tripMetric}>
                <Ionicons name="time-outline" size={18} color={colors.muted} />
                <Text style={styles.tripMetricValue}>{quoteDuration}</Text>
                <Text style={styles.tripMetricLabel}>{t("rijtijd")}</Text>
              </View>
            ) : null}
            {quoteDuration && quotePassengersLabel ? (
              <View style={styles.tripMetricDivider} />
            ) : null}
            {quotePassengersLabel ? (
              <View style={styles.tripMetric}>
                <Ionicons name="people-outline" size={18} color={colors.muted} />
                <Text style={styles.tripMetricValue}>{quotePassengersLabel}</Text>
                <Text style={styles.tripMetricLabel}>
                  {passengerCount === 1 ? 'persoon' : 'personen'}
                </Text>
              </View>
            ) : null}
          </View>
        </View>
      ) : (
        <Text style={styles.routeSummary}>{routeSummary}</Text>
      )}
      <View style={styles.quotePriceSlot}>
        <Text style={styles.price} numberOfLines={1}>
          {displayPrice}
        </Text>
      </View>
      <Text
        style={[
          styles.meta,
          styles.quoteMetaCenter,
          freeNearby > 0 ? null : styles.quoteMetaNoTaxi,
        ]}
      >
        {freeNearby > 0
          ? freeNearby === 1
            ? t('{n} taxi in de buurt', { n: freeNearby })
            : t('{n} taxi’s in de buurt', { n: freeNearby })
          : t("Geen vrije taxi in de buurt")}
      </Text>

      {fieldErrors.offer ? <Text style={styles.fieldErrorText}>{fieldErrors.offer}</Text> : null}
    </Card>
  ) : null;

  const bookContent = (
    <>
      {USE_NEXA_BOOKING_REDESIGN ? (
        <NexaHeroBanner
          greetingName={firstName}
          showChrome={false}
          onBack={onBack}
          onLogoPress={() => {
            setTab('home');
            AsyncStorage.setItem(TAB_KEY, 'home').catch(() => undefined);
          }}
        />
      ) : null}
      {activeBannerRide ? (
        <View style={{ marginBottom: 14 }}>
          <ActiveRideBanner
            ride={activeBannerRide as GuestRide}
            etaLabel={
              driverComing
                ? localizeEtaLabel(live?.eta_label, live?.eta_minutes)
                : null
            }
            isLive={driverComing}
            onPress={() => openRideFollow(activeBannerRide as GuestRide)}
          />
        </View>
      ) : null}

      {USE_NEXA_BOOKING_REDESIGN ? (
        <>
          <NexaLocationField
            variant="pickup"
            label={t('Ophaallocatie')}
            value={pickupQuery}
            onChangeText={(v) => {
              setPickupQuery(v);
              if (pickup && v.trim() !== pickup.address) setPickup(null);
              setFieldErrors((e) => ({ ...e, pickup: undefined }));
              scheduleSuggestions(v, 'pickup');
            }}
            placeholder={t('Typ een adres en kies een suggestie')}
            autoCapitalize="words"
            autoComplete="off"
            confirmed={!!pickup}
            error={fieldErrors.pickup}
            returnKeyType="next"
            onSubmitEditing={focusDropoffField}
            onClear={() => {
              if (pickupSuggestTimer.current) clearTimeout(pickupSuggestTimer.current);
              pickupSuggestSeq.current += 1;
              setPickupQuery('');
              setPickup(null);
              setPickupSuggestions([]);
              setFieldErrors((e) => ({ ...e, pickup: undefined }));
            }}
            clearAccessibilityLabel={t('Ophaaladres wissen')}
            rightAccessory={
              <NexaLocateButton
                loading={pickupLoading}
                onPress={() => loadPickup({ focusDropoff: true })}
                accessibilityLabel={t('Bepaal mijn locatie')}
              />
            }
            suggestions={pickupSuggestions.map((s, i) => ({
              key: `p-${i}-${s.lat.toFixed(5)}-${s.lng.toFixed(5)}-${s.label}`,
              label: s.label,
              onPress: () => {
                applySuggestion(s, 'pickup');
                setFieldErrors((e) => ({ ...e, pickup: undefined }));
              },
            }))}
          />
          <NexaLocationField
            variant="dropoff"
            label={t('Bestemming')}
            value={dropoffQuery}
            inputRef={dropoffInputRef}
            onChangeText={(v) => {
              setDropoffQuery(v);
              if (dropoff && v.trim() !== dropoff.address) setDropoff(null);
              setFieldErrors((e) => ({ ...e, dropoff: undefined }));
              scheduleSuggestions(v, 'dropoff');
            }}
            placeholder={t('Waar wil je naartoe?')}
            autoCapitalize="words"
            autoComplete="off"
            confirmed={!!dropoff}
            error={fieldErrors.dropoff}
            returnKeyType="done"
            onSubmitEditing={() => Keyboard.dismiss()}
            onClear={() => {
              if (dropoffSuggestTimer.current) clearTimeout(dropoffSuggestTimer.current);
              dropoffSuggestSeq.current += 1;
              setDropoffQuery('');
              setDropoff(null);
              setDropoffSuggestions([]);
              setFieldErrors((e) => ({ ...e, dropoff: undefined }));
            }}
            clearAccessibilityLabel={t('Bestemming wissen')}
            suggestions={dropoffSuggestions.map((s, i) => ({
              key: `d-${i}-${s.lat.toFixed(5)}-${s.lng.toFixed(5)}-${s.label}`,
              label: s.label,
              onPress: () => {
                applySuggestion(s, 'dropoff');
                setFieldErrors((e) => ({ ...e, dropoff: undefined }));
              },
            }))}
          />
          <View
            style={{ marginBottom: 10 }}
            onLayout={(e) => {
              pickupAtSectionY.current = e.nativeEvent.layout.y;
            }}
          >
            <PickupAtField
              variant="nexa"
              value={pickupAt}
              error={fieldErrors.pickupAt}
              onChange={(next) => {
                setPickupAt(next);
                setFieldErrors((e) => ({ ...e, pickupAt: undefined }));
              }}
            />
          </View>
        </>
      ) : (
        <>
          <Card>
            <Field
              label={t('Ophaaladres')}
              value={pickupQuery}
              onChangeText={(v) => {
                setPickupQuery(v);
                if (pickup && v.trim() !== pickup.address) setPickup(null);
                setFieldErrors((e) => ({ ...e, pickup: undefined }));
                scheduleSuggestions(v, 'pickup');
              }}
              placeholder={t('Typ een adres en kies een suggestie')}
              autoCapitalize="words"
              autoComplete="off"
              confirmed={!!pickup}
              error={fieldErrors.pickup}
              returnKeyType="next"
              onSubmitEditing={focusDropoffField}
              onClear={() => {
                if (pickupSuggestTimer.current) clearTimeout(pickupSuggestTimer.current);
                pickupSuggestSeq.current += 1;
                setPickupQuery('');
                setPickup(null);
                setPickupSuggestions([]);
                setFieldErrors((e) => ({ ...e, pickup: undefined }));
              }}
              clearAccessibilityLabel={t('Ophaaladres wissen')}
              rightAccessory={
                <Pressable
                  onPress={() => loadPickup({ focusDropoff: true })}
                  disabled={pickupLoading}
                  hitSlop={8}
                  style={styles.locIconBtn}
                  accessibilityLabel={t('Bepaal mijn locatie')}
                >
                  {pickupLoading ? (
                    <ActivityIndicator size="small" color={colors.primary} />
                  ) : (
                    <Ionicons name="location" size={22} color={colors.primary} />
                  )}
                </Pressable>
              }
            />
            {pickupSuggestions.map((s, i) => (
              <Pressable
                key={`p-${i}-${s.lat.toFixed(5)}-${s.lng.toFixed(5)}-${s.label}`}
                onPress={() => {
                  applySuggestion(s, 'pickup');
                  setFieldErrors((e) => ({ ...e, pickup: undefined }));
                }}
                style={[styles.suggestRow, i === 0 && styles.suggestRowFirst]}
              >
                <Text style={styles.suggestText}>{s.label}</Text>
              </Pressable>
            ))}
          </Card>

          <Card>
            <Field
              label={t('Bestemming')}
              value={dropoffQuery}
              inputRef={dropoffInputRef}
              onChangeText={(v) => {
                setDropoffQuery(v);
                if (dropoff && v.trim() !== dropoff.address) setDropoff(null);
                setFieldErrors((e) => ({ ...e, dropoff: undefined }));
                scheduleSuggestions(v, 'dropoff');
              }}
              placeholder={t('Typ een adres en kies een suggestie')}
              autoCapitalize="words"
              autoComplete="off"
              confirmed={!!dropoff}
              error={fieldErrors.dropoff}
              returnKeyType="done"
              onSubmitEditing={() => Keyboard.dismiss()}
              onClear={() => {
                if (dropoffSuggestTimer.current) clearTimeout(dropoffSuggestTimer.current);
                dropoffSuggestSeq.current += 1;
                setDropoffQuery('');
                setDropoff(null);
                setDropoffSuggestions([]);
                setFieldErrors((e) => ({ ...e, dropoff: undefined }));
              }}
              clearAccessibilityLabel={t('Bestemming wissen')}
            />
            {dropoffSuggestions.map((s, i) => (
              <Pressable
                key={`d-${i}-${s.lat.toFixed(5)}-${s.lng.toFixed(5)}-${s.label}`}
                onPress={() => {
                  applySuggestion(s, 'dropoff');
                  setFieldErrors((e) => ({ ...e, dropoff: undefined }));
                }}
                style={[styles.suggestRow, i === 0 && styles.suggestRowFirst]}
              >
                <Text style={styles.suggestText}>{s.label}</Text>
              </Pressable>
            ))}
          </Card>

          <View
            onLayout={(e) => {
              pickupAtSectionY.current = e.nativeEvent.layout.y;
            }}
          >
            <Card>
              <PickupAtField
                value={pickupAt}
                error={fieldErrors.pickupAt}
                onChange={(next) => {
                  setPickupAt(next);
                  setFieldErrors((e) => ({ ...e, pickupAt: undefined }));
                }}
              />
            </Card>
          </View>
        </>
      )}

      <View
        onLayout={(e) => {
          mapSectionY.current = e.nativeEvent.layout.y;
        }}
      >
        {mapBlock}
      </View>

      {priceBlock}

      {USE_NEXA_BOOKING_REDESIGN ? (
        <NexaGlowCard contentStyle={{ padding: 14 }} style={{ marginBottom: 10 }}>
          <Text
            style={[
              styles.section,
              styles.sectionCenter,
              { color: nexaSurfaceText(colorScheme === 'light') },
            ]}
          >
            {t('Passagiers')}
          </Text>
          <View style={[styles.qtyWrap, styles.qtyWrapCenter]}>
            <Pressable
              onPress={() => bumpPassengers(-1)}
              disabled={passengerCount <= 1}
              style={[styles.qtyBtn, passengerCount <= 1 && styles.qtyBtnDisabled]}
            >
              <Text
                style={[styles.qtyBtnText, passengerCount <= 1 && styles.qtyBtnTextDisabled]}
              >
                −
              </Text>
            </Pressable>
            <Text
              style={[styles.qtyValue, { color: nexaSurfaceText(colorScheme === 'light') }]}
            >
              {passengerCount}
            </Text>
            <Pressable
              onPress={() => bumpPassengers(1)}
              disabled={passengerCount >= 20}
              style={[styles.qtyBtn, passengerCount >= 20 && styles.qtyBtnDisabled]}
            >
              <Text
                style={[styles.qtyBtnText, passengerCount >= 20 && styles.qtyBtnTextDisabled]}
              >
                +
              </Text>
            </Pressable>
          </View>
        </NexaGlowCard>
      ) : (
        <Card>
          <Text style={[styles.section, styles.sectionCenter]}>{t('Passagiers')}</Text>
          <View style={[styles.qtyWrap, styles.qtyWrapCenter]}>
            <Pressable
              onPress={() => bumpPassengers(-1)}
              disabled={passengerCount <= 1}
              style={[styles.qtyBtn, passengerCount <= 1 && styles.qtyBtnDisabled]}
            >
              <Text
                style={[styles.qtyBtnText, passengerCount <= 1 && styles.qtyBtnTextDisabled]}
              >
                −
              </Text>
            </Pressable>
            <Text style={styles.qtyValue}>{passengerCount}</Text>
            <Pressable
              onPress={() => bumpPassengers(1)}
              disabled={passengerCount >= 20}
              style={[styles.qtyBtn, passengerCount >= 20 && styles.qtyBtnDisabled]}
            >
              <Text
                style={[styles.qtyBtnText, passengerCount >= 20 && styles.qtyBtnTextDisabled]}
              >
                +
              </Text>
            </Pressable>
          </View>
        </Card>
      )}

      {USE_NEXA_BOOKING_REDESIGN ? (
        <NexaGlowCard contentStyle={{ padding: 14 }} style={{ marginBottom: 10 }}>
          <Text style={[styles.section, { color: nexaSurfaceText(colorScheme === 'light') }]}>
            {t('Bagage')}
          </Text>
          {BAGGAGE_ROWS.map((row) => (
            <View key={row.key} style={styles.baggageRow}>
              <View style={{ flex: 1, minWidth: 0 }}>
                <Text
                  style={[
                    styles.baggageLabel,
                    { color: nexaSurfaceText(colorScheme === 'light') },
                  ]}
                >
                  {t(row.label)}
                </Text>
                <Text
                  style={[
                    styles.baggageHint,
                    { color: nexaSurfaceMuted(colorScheme === 'light') },
                  ]}
                >
                  {t(row.hint)}
                </Text>
              </View>
              <View style={styles.qtyWrap}>
                <Pressable
                  onPress={() => bumpBaggage(row.key, -1, row.max)}
                  style={styles.qtyBtn}
                >
                  <Text style={styles.qtyBtnText}>−</Text>
                </Pressable>
                <Text
                  style={[
                    styles.qtyValue,
                    { color: nexaSurfaceText(colorScheme === 'light') },
                  ]}
                >
                  {baggage[row.key]}
                </Text>
                <Pressable
                  onPress={() => bumpBaggage(row.key, 1, row.max)}
                  style={styles.qtyBtn}
                >
                  <Text style={styles.qtyBtnText}>+</Text>
                </Pressable>
              </View>
            </View>
          ))}
        </NexaGlowCard>
      ) : (
        <Card>
          <Text style={styles.section}>{t('Bagage')}</Text>
          {BAGGAGE_ROWS.map((row) => (
            <View key={row.key} style={styles.baggageRow}>
              <View style={{ flex: 1, minWidth: 0 }}>
                <Text style={styles.baggageLabel}>{t(row.label)}</Text>
                <Text style={styles.baggageHint}>{t(row.hint)}</Text>
              </View>
              <View style={styles.qtyWrap}>
                <Pressable
                  onPress={() => bumpBaggage(row.key, -1, row.max)}
                  style={styles.qtyBtn}
                >
                  <Text style={styles.qtyBtnText}>−</Text>
                </Pressable>
                <Text style={styles.qtyValue}>{baggage[row.key]}</Text>
                <Pressable
                  onPress={() => bumpBaggage(row.key, 1, row.max)}
                  style={styles.qtyBtn}
                >
                  <Text style={styles.qtyBtnText}>+</Text>
                </Pressable>
              </View>
            </View>
          ))}
        </Card>
      )}

      {USE_NEXA_BOOKING_REDESIGN ? (
        <NexaGlowCard contentStyle={{ padding: 14 }} style={{ marginBottom: 10 }}>
          <Field
            label="Opmerkingen"
            value={remarks}
            onChangeText={setRemarks}
            placeholder={t('Bijv. bakfiets, hulp bij instappen…')}
            multiline
          />
        </NexaGlowCard>
      ) : (
        <Card>
          <Field
            label="Opmerkingen"
            value={remarks}
            onChangeText={setRemarks}
            placeholder={t('Bijv. bakfiets, hulp bij instappen…')}
            multiline
          />
        </Card>
      )}

      {USE_NEXA_BOOKING_REDESIGN ? (
        <NexaGlowCard contentStyle={{ padding: 14 }} style={{ marginBottom: 10 }}>
          {showBookContactInputs ? (
            <>
              <View style={styles.nameRow}>
                <View style={{ flex: 1 }}>
                  <Field
                    label={t("Voornaam")}
                    value={firstName}
                    onChangeText={(v) => {
                      setFirstName(v);
                      setFieldErrors((e) => ({ ...e, firstName: undefined }));
                    }}
                    autoCapitalize="words"
                    error={fieldErrors.firstName}
                  />
                </View>
                <View style={{ width: 10 }} />
                <View style={{ flex: 1 }}>
                  <Field
                    label={t("Achternaam")}
                    value={lastName}
                    onChangeText={(v) => {
                      setLastName(v);
                      setFieldErrors((e) => ({ ...e, lastName: undefined }));
                    }}
                    autoCapitalize="words"
                    error={fieldErrors.lastName}
                  />
                </View>
              </View>
              <Field
                label={t("Telefoon")}
                value={phone}
                onChangeText={(v) => {
                  setPhone(v);
                  setFieldErrors((e) => ({ ...e, phone: undefined }));
                }}
                keyboardType="phone-pad"
                placeholder="06…"
                error={fieldErrors.phone}
              />
              <Field
                label="E-mail"
                value={email}
                onChangeText={(v) => {
                  setEmail(v);
                  setFieldErrors((e) => ({ ...e, email: undefined }));
                }}
                keyboardType="email-address"
                autoCapitalize="none"
                placeholder="naam@email.nl"
                error={fieldErrors.email}
              />
              {bookContactEditing ? (
                <>
                  {profileSaved ? (
                    <Text style={[styles.meta, { color: colors.success }]}>{t("Opgeslagen.")}</Text>
                  ) : null}
                  <PrimaryButton title={t("Opslaan")} onPress={() => void saveProfile()} />
                  <View style={{ height: 10 }} />
                  <GhostButton title={t("Annuleren")} onPress={() => cancelContactEdit('book')} />
                </>
              ) : null}
            </>
          ) : (
            <>
              <View style={styles.nameRow}>
                <View style={[styles.contactReadonlyField, { flex: 1 }]}>
                  <Text style={styles.rowLabel}>{t("Voornaam")}</Text>
                  <Text style={styles.rowValue}>{firstName}</Text>
                </View>
                <View style={{ width: 10 }} />
                <View style={[styles.contactReadonlyField, { flex: 1 }]}>
                  <Text style={styles.rowLabel}>{t("Achternaam")}</Text>
                  <Text style={styles.rowValue}>{lastName}</Text>
                </View>
              </View>
              <View style={styles.contactReadonlyField}>
                <Text style={styles.rowLabel}>{t("Telefoon")}</Text>
                <Text style={styles.rowValue}>{phone}</Text>
              </View>
              <View style={styles.contactReadonlyField}>
                <Text style={styles.rowLabel}>E-mail</Text>
                <Text style={styles.rowValue}>{email}</Text>
              </View>
              <Pressable
                onPress={() => beginContactEdit('book')}
                style={({ pressed }) => [
                  styles.contactEditBtn,
                  pressed && styles.contactEditBtnPressed,
                ]}
                accessibilityRole="button"
              >
                <Text style={styles.contactEditBtnText}>{t("Bewerk")}</Text>
              </Pressable>
            </>
          )}
          <Text style={styles.profileHint}>{t("Deze gegevens kun je ook in je profiel invullen, waarna ze hier automatisch worden ingevuld.")}</Text>
        </NexaGlowCard>
      ) : (
        <Card>
          {showBookContactInputs ? (
            <>
              <View style={styles.nameRow}>
                <View style={{ flex: 1 }}>
                  <Field
                    label={t("Voornaam")}
                    value={firstName}
                    onChangeText={(v) => {
                      setFirstName(v);
                      setFieldErrors((e) => ({ ...e, firstName: undefined }));
                    }}
                    autoCapitalize="words"
                    error={fieldErrors.firstName}
                  />
                </View>
                <View style={{ width: 10 }} />
                <View style={{ flex: 1 }}>
                  <Field
                    label={t("Achternaam")}
                    value={lastName}
                    onChangeText={(v) => {
                      setLastName(v);
                      setFieldErrors((e) => ({ ...e, lastName: undefined }));
                    }}
                    autoCapitalize="words"
                    error={fieldErrors.lastName}
                  />
                </View>
              </View>
              <Field
                label={t("Telefoon")}
                value={phone}
                onChangeText={(v) => {
                  setPhone(v);
                  setFieldErrors((e) => ({ ...e, phone: undefined }));
                }}
                keyboardType="phone-pad"
                placeholder="06…"
                error={fieldErrors.phone}
              />
              <Field
                label="E-mail"
                value={email}
                onChangeText={(v) => {
                  setEmail(v);
                  setFieldErrors((e) => ({ ...e, email: undefined }));
                }}
                keyboardType="email-address"
                autoCapitalize="none"
                placeholder="naam@email.nl"
                error={fieldErrors.email}
              />
              {bookContactEditing ? (
                <>
                  {profileSaved ? (
                    <Text style={[styles.meta, { color: colors.success }]}>{t("Opgeslagen.")}</Text>
                  ) : null}
                  <PrimaryButton title={t("Opslaan")} onPress={() => void saveProfile()} />
                  <View style={{ height: 10 }} />
                  <GhostButton title={t("Annuleren")} onPress={() => cancelContactEdit('book')} />
                </>
              ) : null}
            </>
          ) : (
            <>
              <View style={styles.nameRow}>
                <View style={[styles.contactReadonlyField, { flex: 1 }]}>
                  <Text style={styles.rowLabel}>{t("Voornaam")}</Text>
                  <Text style={styles.rowValue}>{firstName}</Text>
                </View>
                <View style={{ width: 10 }} />
                <View style={[styles.contactReadonlyField, { flex: 1 }]}>
                  <Text style={styles.rowLabel}>{t("Achternaam")}</Text>
                  <Text style={styles.rowValue}>{lastName}</Text>
                </View>
              </View>
              <View style={styles.contactReadonlyField}>
                <Text style={styles.rowLabel}>{t("Telefoon")}</Text>
                <Text style={styles.rowValue}>{phone}</Text>
              </View>
              <View style={styles.contactReadonlyField}>
                <Text style={styles.rowLabel}>E-mail</Text>
                <Text style={styles.rowValue}>{email}</Text>
              </View>
              <Pressable
                onPress={() => beginContactEdit('book')}
                style={({ pressed }) => [
                  styles.contactEditBtn,
                  pressed && styles.contactEditBtnPressed,
                ]}
                accessibilityRole="button"
              >
                <Text style={styles.contactEditBtnText}>{t("Bewerk")}</Text>
              </Pressable>
            </>
          )}
          <Text style={styles.profileHint}>{t("Deze gegevens kun je ook in je profiel invullen, waarna ze hier automatisch worden ingevuld.")}</Text>
        </Card>
      )}

      {locationError ? <ErrorText>{locationError}</ErrorText> : null}
      {error && !showNoTaxiWarn ? <ErrorText>{error}</ErrorText> : null}
      {showNoTaxiWarn ? (
        <Text style={[styles.warnText, { marginBottom: 10 }]}>
          {t("Geen taxi’s beschikbaar. We sturen geen rit uit. Je krijgt een melding zodra er weer een taxi beschikbaar is.")}
        </Text>
      ) : null}
      {/* Marketplace-boekingen vereisen altijd eerst Mollie-betaling. */}
      {canDispatch ? (
        <Text
          style={[
            styles.meta,
            {
              marginTop: 0,
              marginBottom: 10,
              color:
                colorScheme === 'light'
                  ? nexaBookingTheme.color.inkMuted
                  : colors.muted,
            },
          ]}
        >
          {t(
            'Om te boeken betaal je eerst. Je wordt doorgestuurd naar een betaalpagina.'
          )}
        </Text>
      ) : null}
      {USE_NEXA_BOOKING_REDESIGN ? (
        <NexaPrimaryButton
          title={t('Boek de rit')}
          onPress={onBook}
          loading={busy}
          disabled={!canDispatch}
        />
      ) : (
        <PrimaryButton
          title={t("Taxi aanvragen")}
          onPress={onBook}
          loading={busy}
          disabled={!canDispatch}
        />
      )}
    </>
  );

  const homeContent = USE_NEXA_BOOKING_REDESIGN ? (
    <NexaHomeScreen
      greetingName={firstName}
      isReturning={guestRides.length > 0 || firstName.trim().length > 0}
      onBack={onBack}
      onBook={goToBookTab}
      onRides={() => {
        leaveContactEditModes();
        setTab('rides');
        AsyncStorage.setItem(TAB_KEY, 'rides').catch(() => undefined);
      }}
      onAccount={() => {
        leaveContactEditModes();
        setTab('more');
        AsyncStorage.setItem(TAB_KEY, 'more').catch(() => undefined);
      }}
      onDestinationFocus={() => {
        goToBookTab();
        setTimeout(() => focusDropoffField(), 350);
      }}
      topBanner={
        activeBannerRide ? (
          <ActiveRideBanner
            ride={activeBannerRide as GuestRide}
            etaLabel={
              driverComing
                ? localizeEtaLabel(live?.eta_label, live?.eta_minutes)
                : null
            }
            isLive={driverComing}
            onPress={() => openRideFollow(activeBannerRide as GuestRide)}
          />
        ) : null
      }
    />
  ) : null;

  const archivedSet = new Set(archivedKeys);
  const activeRides = guestRides.filter((r) => isActiveRidePhase(r.phase));
  const pastRides = guestRides.filter((r) => !isActiveRidePhase(r.phase));
  const visiblePast = pastRides.filter((r) => !archivedSet.has(rideArchiveKey(r)));
  const archivedPast = pastRides.filter((r) => archivedSet.has(rideArchiveKey(r)));

  function renderRatingResult(stars: number, comment?: string | null) {
    return (
      <View style={styles.statusTimeline}>
        <Text style={styles.rowLabel}>{t("Jouw beoordeling")}</Text>
        <View style={styles.starsRow}>
          {[1, 2, 3, 4, 5].map((n) => (
            <Ionicons
              key={n}
              name={n <= stars ? 'star' : 'star-outline'}
              size={22}
              color={n <= stars ? '#F5B301' : colors.muted}
            />
          ))}
        </View>
        {comment ? <Text style={styles.ratingComment}>{comment}</Text> : null}
      </View>
    );
  }

  function renderWaitStyleRoute(input: {
    from?: string | null;
    to?: string | null;
    fromPlace?: string | null;
    toPlace?: string | null;
  }) {
    const fromLine = bookingAddressLine(input.from);
    const toLine = bookingAddressLine(input.to);
    const fromPlace = visiblePlaceName(input.fromPlace, input.from);
    const toPlace = visiblePlaceName(input.toPlace, input.to);
    if (!fromLine && !toLine && !fromPlace && !toPlace) return null;
    return (
      <View style={styles.waitRouteBlock}>
        {fromPlace ? <Text style={styles.waitRoutePlace}>{fromPlace}</Text> : null}
        {fromLine ? <Text style={styles.waitRouteAddress}>{fromLine}</Text> : null}
        {(fromLine || fromPlace) && (toLine || toPlace) ? (
          <View style={styles.waitRouteArrow}>
            <Ionicons name="arrow-down" size={22} color={colors.text} />
          </View>
        ) : null}
        {toPlace ? <Text style={styles.waitRoutePlace}>{toPlace}</Text> : null}
        {toLine ? <Text style={styles.waitRouteAddress}>{toLine}</Text> : null}
      </View>
    );
  }

  function renderTripDetails(input: {
    from?: string | null;
    to?: string | null;
    fromPlace?: string | null;
    toPlace?: string | null;
    distanceKm?: number | null;
    durationSeconds?: number | null;
    passengers?: number | null;
    baggage?: {
      items?: Array<{ label?: string | null; qty?: number | null }> | null;
      summary?: string | null;
    } | null;
  }) {
    const pickupStop = splitRideAddress(input.from);
    const dropoffStop = splitRideAddress(input.to);
    const pickupPlace = visiblePlaceName(input.fromPlace, input.from);
    const dropoffPlace = visiblePlaceName(input.toPlace, input.to);
    const distance = formatTripDistance(input.distanceKm);
    const duration = formatTripDuration(input.durationSeconds);
    const passengerCount = Number(input.passengers || 0);
    const passengersLabel = passengerCount > 0 ? String(passengerCount) : null;
    const chips = baggageChipLines(input.baggage);
    const showMetrics = !!(distance || duration || passengersLabel);

    return (
      <View>
        <View style={styles.tripRoute}>
          <View style={styles.tripRail} />
          <View style={styles.tripStop}>
            <View style={styles.tripHead}>
              <View style={[styles.tripDotOuter, styles.tripDotOuterPickup]}>
                <View style={[styles.tripDot, styles.tripDotPickup]} />
              </View>
              <Text style={styles.tripLabel}>{t("Ophalen")}</Text>
            </View>
            <View style={styles.tripAddress}>
              {pickupPlace ? <Text style={styles.tripPlace}>{pickupPlace}</Text> : null}
              <Text style={styles.tripMain}>{pickupStop.main}</Text>
              {pickupStop.sub ? <Text style={styles.tripSub}>{pickupStop.sub}</Text> : null}
            </View>
          </View>
          <View style={[styles.tripStop, styles.tripStopLast]}>
            <View style={styles.tripHead}>
              <View style={[styles.tripDotOuter, styles.tripDotOuterDropoff]}>
                <View style={[styles.tripDot, styles.tripDotDropoff]} />
              </View>
              <Text style={styles.tripLabel}>{t("Afzetten")}</Text>
            </View>
            <View style={styles.tripAddress}>
              {dropoffPlace ? <Text style={styles.tripPlace}>{dropoffPlace}</Text> : null}
              <Text style={styles.tripMain}>{dropoffStop.main}</Text>
              {dropoffStop.sub ? <Text style={styles.tripSub}>{dropoffStop.sub}</Text> : null}
            </View>
          </View>
        </View>
        {showMetrics || chips.length ? (
          <View style={styles.tripStats}>
            {showMetrics ? (
              <View style={styles.tripMetrics}>
                {distance ? (
                  <View style={styles.tripMetric}>
                    <Ionicons name="navigate-outline" size={18} color={colors.muted} />
                    <Text style={styles.tripMetricValue}>{distance}</Text>
                    <Text style={styles.tripMetricLabel}>{t("afstand")}</Text>
                  </View>
                ) : null}
                {distance && (duration || passengersLabel) ? <View style={styles.tripMetricDivider} /> : null}
                {duration ? (
                  <View style={styles.tripMetric}>
                    <Ionicons name="time-outline" size={18} color={colors.muted} />
                    <Text style={styles.tripMetricValue}>{duration}</Text>
                    <Text style={styles.tripMetricLabel}>{t("rijtijd")}</Text>
                  </View>
                ) : null}
                {duration && passengersLabel ? <View style={styles.tripMetricDivider} /> : null}
                {passengersLabel ? (
                  <View style={styles.tripMetric}>
                    <Ionicons name="people-outline" size={18} color={colors.muted} />
                    <Text style={styles.tripMetricValue}>{passengersLabel}</Text>
                    <Text style={styles.tripMetricLabel}>
                      {passengerCount === 1 ? 'persoon' : 'personen'}
                    </Text>
                  </View>
                ) : null}
              </View>
            ) : null}
            {chips.length ? (
              <View style={[styles.tripBags, showMetrics && styles.tripBagsSpaced]}>
                <Text style={styles.tripBagsHeading}>{t("Bagage")}</Text>
                <View style={styles.tripChips}>
                  {chips.map((line) => (
                    <View key={line} style={styles.tripChip}>
                      <Text style={styles.tripChipText}>{line}</Text>
                    </View>
                  ))}
                </View>
              </View>
            ) : null}
          </View>
        ) : null}
      </View>
    );
  }

  function renderRideCard(ride: GuestRide) {
    const key = rideArchiveKey(ride);
    const expanded = expandedRideKey === key;
    const isLiveMatch = !!(live && trackToken === ride.token);
    const phase = isLiveMatch ? live!.phase : ride.phase;
    const from = isLiveMatch ? live!.pickup_address || ride.from : ride.from;
    const to = isLiveMatch ? live!.dropoff_address || ride.to : ride.to;
    const fromPlace = (isLiveMatch ? live?.pickup_place_name : null) || ride.from_place;
    const toPlace = (isLiveMatch ? live?.dropoff_place_name : null) || ride.to_place;
    const price =
      isLiveMatch && live!.quoted_price != null ? live!.quoted_price : ride.quoted_price;
    const cancelled = phase === 'cancelled';
    const completed = phase === 'completed';
    const complaintFiled = !!(
      (isLiveMatch ? live?.complaint?.filed : null) || ride.complaint?.filed
    );
    const active = isActiveRidePhase(phase);
    const approaching = isLiveMatch && driverComing;
    const headerStatus =
      (isLiveMatch && (liveStatusFromUpdates || live?.status_label)) ||
      ride.status_label ||
      '';
    const canArchive = !active;
    const cancelMsg = String(
      (isLiveMatch ? live?.cancellation_message : null) || ride.cancellation_message || ''
    ).trim();
    const pickupWhen =
      formatPickupWhen(isLiveMatch ? livePickupAt(live) || ride.pickup_at : ride.pickup_at) ||
      '—';
    const storedKm = (isLiveMatch ? live?.distance_km : null) ?? ride.distance_km ?? null;
    const storedSeconds =
      (isLiveMatch ? live?.duration_seconds : null) ?? ride.duration_seconds ?? null;
    const pickupLat = isLiveMatch ? live?.pickup_lat : null;
    const pickupLng = isLiveMatch ? live?.pickup_lng : null;
    const dropoffLat = isLiveMatch ? live?.dropoff_lat : null;
    const dropoffLng = isLiveMatch ? live?.dropoff_lng : null;
    const estimated =
      storedKm == null || storedSeconds == null
        ? pickupLat != null &&
          pickupLng != null &&
          dropoffLat != null &&
          dropoffLng != null
          ? estimateRouteMetrics(
              { lat: pickupLat, lng: pickupLng },
              { lat: dropoffLat, lng: dropoffLng }
            )
          : null
        : null;
    const distanceKm =
      storedKm ??
      (estimated ? Math.round(estimated.distance_meters / 100) / 10 : null);
    const durationSeconds = storedSeconds ?? estimated?.duration_seconds ?? null;
    const ridePin = phase === 'searching' ? null : pickupPinForRide(ride);
    // Alle zoekritten (ook “blijven wachten”) gebruiken dezelfde box: geen status_label “Aangeboden”
    if (phase === 'searching') {
      const needsDecision = !!(isLiveMatch && live?.needs_unaccepted_decision);
      const waitingOn = !!(isLiveMatch && live?.waiting_for_taxi);
      const showCancel = isLiveMatch ? !!live?.can_cancel : true;
      const passengers =
        (isLiveMatch ? live?.passengers : ride.passengers) ?? null;
      const baggageSummary =
        (isLiveMatch ? live?.baggage?.summary : ride.baggage_summary) || null;
      const desc = needsDecision
        ? `Wil je blijven wachten${
            live?.decision_minutes
              ? ` (tot ca. ${live.decision_deadline_label || '—'})`
              : ''
          } of annuleren?`
        : waitingOn
          ? t(
              "Je rit blijft op de marktplaats tot een taxi accepteert. Duurt het te lang, dan wordt de rit automatisch geannuleerd. Je kunt alsnog zelf annuleren."
            )
          : t("Je rit is aangeboden aan aangesloten taxibedrijven in de buurt.");

      return (
        <ArchiveMotion
          key={key || ride.token}
          mode={
            rideArchiveMotion?.key === key ? rideArchiveMotion.mode : 'idle'
          }
          accentColor={nexaBookingTheme.color.blueBright}
          onFinished={
            rideArchiveMotion?.key === key ? finishRideArchiveMotion : undefined
          }
        >
        <View style={styles.rideCard}>
          <View style={styles.rideCardBody}>
            <PhasePill phase="searching" />
            <SearchingTaxiTitle />
            <Text style={styles.searchingDesc}>{desc}</Text>
            <View style={styles.searchingDivider} />
            <View style={styles.searchingMetaRow}>
              <View style={{ flex: 1, minWidth: 0 }}>
                <Text style={styles.metaCaption}>{t("Datum")}</Text>
                <Text style={styles.rideCardAmount}>{pickupWhen}</Text>
              </View>
              {price != null ? (
                <View style={{ alignItems: 'flex-end' }}>
                  <Text style={styles.metaCaption}>{t("Prijs")}</Text>
                  <Text style={styles.rideCardAmount}>{formatEuroNl(Number(price))}</Text>
                </View>
              ) : null}
            </View>
            {renderTripDetails({
              from,
              to,
              fromPlace,
              toPlace,
              distanceKm,
              durationSeconds,
              passengers,
              baggage: isLiveMatch
                ? live?.baggage
                : { summary: baggageSummary },
            })}
            {needsDecision ? (
              <View style={{ marginTop: 14, gap: 8 }}>
                <PrimaryButton title={t("Blijven wachten")} onPress={onWait} loading={busy} />
                <GhostButton
                  title={t("Annuleren")}
                  onPress={() => requestCancel(ride.token)}
                  danger
                />
              </View>
            ) : showCancel ? (
              <Pressable
                onPress={() => requestCancel(ride.token)}
                style={styles.cancelRideBtn}
                accessibilityRole="button"
                accessibilityLabel={t("Rit annuleren")}
              >
                <Text style={styles.cancelRideBtnText}>{t("Rit annuleren")}</Text>
              </Pressable>
            ) : null}
          </View>
        </View>
        </ArchiveMotion>
      );
    }

    return (
      <ArchiveMotion
        key={key || ride.token}
        mode={rideArchiveMotion?.key === key ? rideArchiveMotion.mode : 'idle'}
        accentColor={nexaBookingTheme.color.blueBright}
        onFinished={
          rideArchiveMotion?.key === key ? finishRideArchiveMotion : undefined
        }
      >
      <View
        style={[
          styles.rideCard,
          cancelled && styles.rideCardCancelled,
          phase === 'accepted' && styles.rideCardAccepted,
        ]}
      >
        {cancelled ? (
          <View style={styles.rideCardStatusBar}>
            <Text style={styles.rideCardStatusBarText}>{t("Geannuleerd")}</Text>
          </View>
        ) : null}

        <View style={styles.rideCardBody}>
          {(() => {
            if (!cancelled) return null;
            const refundMsg = customerRefundNoticeText(
              isLiveMatch && live
                ? {
                    refund_message: live.refund_message,
                    payment_status: live.payment_status,
                    payment_paid: live.payment_paid,
                    assume_prepaid: price != null,
                    refund_business_days: live.refund_business_days,
                  }
                : {
                    refund_message: ride.refund_message,
                    payment_status: ride.payment_status,
                    assume_prepaid: price != null,
                    refund_business_days: ride.refund_business_days,
                  }
            );
            return (
              <>
                {cancelMsg ? (
                  <Text style={styles.cancelReasonNotice}>{t(cancelMsg)}</Text>
                ) : null}
                {refundMsg ? (
                  <View style={styles.refundNotice}>
                    <Text style={styles.refundNoticeTitle}>{t("Terugbetaling")}</Text>
                    <Text style={styles.refundNoticeText}>{refundMsg}</Text>
                  </View>
                ) : null}
              </>
            );
          })()}

          {!cancelled ? (
            <View style={styles.rideCardTop}>
              <Pressable
                onPress={() => toggleRideExpanded(ride)}
                style={{ flex: 1, minWidth: 0 }}
                accessibilityRole="button"
                accessibilityState={{ expanded }}
              >
                <View style={styles.rideStatusPills}>
                  <PhasePill phase={phase} />
                  {complaintFiled ? (
                    <View
                      style={[
                        styles.pill,
                        {
                          backgroundColor: colors.danger + '33',
                          borderColor: colors.danger,
                        },
                      ]}
                    >
                      <Text style={[styles.pillText, { color: colors.danger }]}>
                        {t('Klacht ingediend')}
                      </Text>
                    </View>
                  ) : null}
                </View>
              </Pressable>
              <Pressable
                onPress={() => toggleRideExpanded(ride)}
                hitSlop={12}
                style={styles.rideChevronBtn}
                accessibilityLabel={expanded ? 'Inklappen' : t("Uitklappen")}
              >
                <Ionicons
                  name={expanded ? 'chevron-up' : 'chevron-down'}
                  size={26}
                  color={colors.muted}
                />
              </Pressable>
            </View>
          ) : null}

          <Pressable
            onPress={() => toggleRideExpanded(ride)}
            accessibilityRole="button"
            accessibilityState={{ expanded }}
          >
            {!cancelled ? (
              approaching || (headerStatus || '').includes(t("Aankomst over")) ? (
                <DriverComingText
                  eta={
                    approaching
                      ? arrivalEtaText(live) || etaFromStatus(headerStatus)
                      : etaFromStatus(headerStatus)
                  }
                  headlineStyle={styles.arrivalHeadline}
                  detailStyle={styles.arrivalType}
                />
              ) : (
                <Text style={styles.liveTitle}>
                  {phase === 'awaiting_payment'
                    ? t("Wacht op betaling")
                    : headerStatus || phaseLabel(phase)}
                </Text>
              )
            ) : null}

            {cancelled ||
            !(
              completed ||
              (isLiveMatch && tripStarted) ||
              approaching ||
              (headerStatus || '').includes(t("Aankomst over"))
            ) ? (
            <View style={styles.rideRouteRow}>
              <Text
                style={[styles.rideRouteLine, { flex: 1, minWidth: 0, marginBottom: 0 }]}
                numberOfLines={expanded ? 4 : 2}
              >
                {shortAddress(from)} → {shortAddress(to)}
              </Text>
              {cancelled ? (
                <Ionicons
                  name={expanded ? 'chevron-up' : 'chevron-down'}
                  size={26}
                  color={colors.muted}
                />
              ) : null}
            </View>
            ) : null}
            <View style={[styles.rideCardFoot, { marginTop: 4 }]}>
              <View style={{ flex: 1, minWidth: 0 }}>
                <Text style={styles.metaCaption}>{t("Datum")}</Text>
                <Text style={styles.rideCardAmount}>{pickupWhen}</Text>
              </View>
              {price != null ? (
                <View style={{ alignItems: 'flex-end' }}>
                  <Text style={styles.metaCaption}>{t("Prijs")}</Text>
                  <Text style={styles.rideCardAmount}>{formatEuroNl(Number(price))}</Text>
                </View>
              ) : null}
            </View>
          </Pressable>
          {active &&
          ridePin &&
          !ridePin.started &&
          !revealedPinKeys.includes(ridePin.key) ? (
            <NexaPickupCodeButton
              onPress={() =>
                setRevealedPinKeys((keys) =>
                  keys.includes(ridePin.key) ? keys : [...keys, ridePin.key]
                )
              }
            />
          ) : null}
        </View>

        {expanded ? (
          <View style={styles.rideCardExpanded}>
            {renderTripDetails({
              from,
              to,
              fromPlace,
              toPlace,
              distanceKm,
              durationSeconds,
              passengers: (isLiveMatch ? live?.passengers : ride.passengers) ?? ride.passengers,
              baggage: isLiveMatch
                ? live?.baggage || { summary: ride.baggage_summary }
                : { summary: ride.baggage_summary },
            })}

            {isLiveMatch && live ? (
              <>
                {live.eta_label || live.eta_minutes != null ? (
                  <>
                    <Text style={[styles.rowLabel, { marginTop: 10 }]}>
                      {live.customer_event === 'en_route' && !live.trip_started
                        ? t("Aankomst bij jou")
                        : t("Verwachte aankomsttijd")}
                    </Text>
                    <Text style={styles.rowValue}>
                      {localizeEtaLabel(live.eta_label, live.eta_minutes)}
                    </Text>
                  </>
                ) : null}

                {phase === 'accepted' ? (
                  <NexaAssignedRidePanel
                    live={live}
                    driverComing={
                      live.customer_event === 'en_route' && !live.trip_started
                    }
                    tripStarted={
                      live.trip_started === true || live.status === 'assigned'
                    }
                    onPreview={setPhotoPreviewUri}
                    onCall={callAssignedDriver}
                    onChat={openRideChat}
                    chatAttention={chatAttention}
                    onLiveFollow={() => openRideFollow(ride)}
                    embedInScreenBackdrop
                  />
                ) : live.driver || live.vehicle ? (
                  <View style={styles.driverCard}>
                    {live.driver?.company_logo_url ? (
                      <Image
                        source={{ uri: live.driver.company_logo_url }}
                        style={styles.driverLogo}
                        resizeMode="contain"
                      />
                    ) : null}
                    <View style={styles.driverRow}>
                      {live.driver?.photo_url ? (
                        <Pressable
                          onPress={() => setPhotoPreviewUri(live.driver!.photo_url!)}
                          accessibilityRole="button"
                          accessibilityLabel={t("Profielfoto vergroten")}
                        >
                          <Image
                            source={{ uri: live.driver.photo_url }}
                            style={styles.driverAvatar}
                          />
                        </Pressable>
                      ) : null}
                      <View style={{ flex: 1 }}>
                        {live.driver?.name ? (
                          <Text style={styles.driverName}>{live.driver.name}</Text>
                        ) : null}
                        {live.driver?.rating_avg != null ? (
                          <Text style={styles.meta}>
                            <Text style={styles.driverStar}>★</Text>
                            {' '}
                            {Number(live.driver.rating_avg).toFixed(1)}
                            {live.driver.rating_count
                              ? ` (${live.driver.rating_count})`
                              : ''}
                          </Text>
                        ) : null}
                        {live.vehicle?.license_plate ? (
                          <Text style={styles.meta}>{live.vehicle.license_plate}</Text>
                        ) : null}
                        {live.vehicle?.name ? (
                          <Text style={styles.meta}>{live.vehicle.name}</Text>
                        ) : null}
                      </View>
                      {live.vehicle?.photo_url ? (
                        <Pressable
                          onPress={() => setPhotoPreviewUri(live.vehicle!.photo_url!)}
                          accessibilityRole="button"
                          accessibilityLabel={t("Autofoto vergroten")}
                        >
                          <Image
                            source={{ uri: live.vehicle.photo_url }}
                            style={styles.vehicleThumb}
                            resizeMode="contain"
                          />
                        </Pressable>
                      ) : null}
                    </View>
                  </View>
                ) : null}

                {live.company?.name ? (
                  <>
                    <Text style={[styles.rowLabel, { marginTop: 10 }]}>{t("Centrale")}</Text>
                    <Text style={styles.rowValue}>{live.company.name}</Text>
                  </>
                ) : null}

                {Array.isArray(live.status_updates) && live.status_updates.length > 0 ? (
                  <View style={styles.statusTimeline}>
                    <Text style={styles.rowLabel}>{t("Statusupdates")}</Text>
                    {statusUpdatesNewestFirst(live.status_updates).map((u, idx) => {
                      const isCurrent =
                        idx === 0 && u.event !== 'cancelled' && u.event !== 'declined';
                      return (
                      <View key={`${u.event}-${u.at || idx}`} style={styles.statusRow}>
                        <View
                          style={[
                            styles.statusDot,
                            isCurrent && { backgroundColor: colors.success },
                          ]}
                        />
                        <View style={{ flex: 1 }}>
                          <Text
                            style={[
                              styles.statusLabel,
                              isCurrent && { color: colors.success },
                            ]}
                          >
                            {u.label}
                          </Text>
                          {u.at ? (
                            <Text style={styles.statusAt}>
                              {new Date(u.at).toLocaleString(dateLocale(), {
                                day: '2-digit',
                                month: 'short',
                                hour: '2-digit',
                                minute: '2-digit',
                              })}
                            </Text>
                          ) : null}
                        </View>
                      </View>
                      );
                    })}
                  </View>
                ) : null}

                {live.payment_error ? (
                  <Text style={[styles.meta, { color: colors.danger }]}>{live.payment_error}</Text>
                ) : null}

                {live.needs_unaccepted_decision ? (
                  <View style={{ marginTop: 12, gap: 8 }}>
                    <Text style={styles.meta}>
                      Wil je blijven wachten
                      {live.decision_minutes
                        ? ` (tot ca. ${live.decision_deadline_label || '—'})`
                        : ''}{' '}
                      of annuleren?
                    </Text>
                    <PrimaryButton title={t("Blijven wachten")} onPress={onWait} loading={busy} />
                    <GhostButton
                      title={t("Annuleren")}
                      onPress={() => requestCancel(ride.token)}
                      danger
                    />
                  </View>
                ) : null}

                {live.can_retry_payment ? (
                  <View style={{ marginTop: 12 }}>
                    <PrimaryButton title={t("Nu betalen")} onPress={onPay} loading={busy} />
                  </View>
                ) : null}

                {live.can_cancel && !live.needs_unaccepted_decision ? (
                  <View style={{ marginTop: 8 }}>
                    <GhostButton
                      title={t("Rit annuleren")}
                      onPress={() => requestCancel(ride.token)}
                      danger
                    />
                  </View>
                ) : null}

                {live.rating?.can_rate ? (
                  <View style={{ marginTop: 14 }}>
                    <GhostButton title={t("Beoordeel je rit")} onPress={() => openRateModal(ride.token)} />
                  </View>
                ) : null}

                {live.rating?.rated
                  ? renderRatingResult(Number(live.rating.stars) || 0, live.rating.comment)
                  : null}

                {live.can_download_invoice || live.phase === 'completed' ? (
                  <View style={{ marginTop: 12 }}>
                    <GhostButton
                      title={t("Factuur downloaden")}
                      onPress={() => onDownloadInvoice(ride.token)}
                    />
                  </View>
                ) : null}
              </>
            ) : null}

            {!isLiveMatch && completed && ride.rating?.can_rate ? (
              <View style={{ marginTop: 14 }}>
                <GhostButton title={t("Beoordeel je rit")} onPress={() => openRateModal(ride.token)} />
              </View>
            ) : null}

            {!isLiveMatch && ride.rating?.rated
              ? renderRatingResult(Number(ride.rating.stars) || 0, ride.rating.comment)
              : null}

            {!isLiveMatch && completed ? (
              <View style={{ marginTop: 12 }}>
                <GhostButton
                  title={t("Factuur downloaden")}
                  onPress={() => onDownloadInvoice(ride.token)}
                />
              </View>
            ) : null}

            {canArchive ? (
              <View style={{ marginTop: 12 }}>
                <GhostButton
                  title={showArchivedRides ? t('Terugzetten uit archief') : t('Naar archief')}
                  onPress={() => toggleArchiveRide(ride, !showArchivedRides)}
                />
              </View>
            ) : null}

            {(isLiveMatch ? live?.complaint?.can_file : completed && ride.complaint?.can_file) ? (
              <Pressable
                onPress={() => openComplaintModal(ride.token)}
                accessibilityRole="button"
                style={styles.complaintLinkWrap}
              >
                <Text style={styles.complaintLinkText}>
                  {t('Heeft u een klacht over deze rit?')}
                </Text>
              </Pressable>
            ) : null}
            {(isLiveMatch ? live?.complaint?.filed : ride.complaint?.filed) ? (
              <Text style={[styles.ratingComment, { marginTop: 10 }]}>
                {t('Je klacht is ontvangen en wordt behandeld.')}
              </Text>
            ) : null}
          </View>
        ) : (
          <View style={styles.rideArchiveBtnWrap}>
            {canArchive ? (
              <GhostButton
                title={showArchivedRides ? t('Terugzetten uit archief') : t('Naar archief')}
                onPress={() => toggleArchiveRide(ride, !showArchivedRides)}
              />
            ) : null}
            {completed && ride.complaint?.can_file ? (
              <Pressable
                onPress={() => openComplaintModal(ride.token)}
                accessibilityRole="button"
                style={styles.complaintLinkWrap}
              >
                <Text style={styles.complaintLinkText}>
                  {t('Heeft u een klacht over deze rit?')}
                </Text>
              </Pressable>
            ) : null}
            {ride.complaint?.filed ? (
              <Text style={[styles.ratingComment, { marginTop: 10 }]}>
                {t('Je klacht is ontvangen en wordt behandeld.')}
              </Text>
            ) : null}
          </View>
        )}
      </View>
      </ArchiveMotion>
    );
  }

  const listedActive = rideMapOpen
    ? activeRides.filter((ride) => ride.token !== trackToken)
    : activeRides;

  const openGuestRide = trackToken
    ? guestRides.find((ride) => ride.token === trackToken)
    : undefined;
  const tripPanel =
    rideMapOpen && live ? (
      <View>
        <View style={styles.liveTitleRow}>
          <View style={{ flex: 1, minWidth: 0 }}>
            <Text
              style={[
                styles.liveTitleEyebrow,
                { color: nexaBookingTheme.color.orangeHot },
              ]}
              numberOfLines={1}
            >
              {t('Je rit is bevestigd')}
            </Text>
            <Text
              style={[
                styles.liveTitleMain,
                { color: nexaSurfaceText(colorScheme === 'light') },
              ]}
              numberOfLines={1}
            >
              {tripStarted ? t('Rit onderweg') : t('Chauffeur onderweg')}
            </Text>
            <View style={styles.liveTitleUnderline} />
          </View>
          {driverComing || liveFollowUi || tripStarted ? (
            <View style={styles.liveTrackingPillHd}>
              <Ionicons
                name="radio"
                size={15}
                color={colorScheme === 'light' ? '#0D9488' : '#69E0B1'}
              />
              <Text
                style={[
                  styles.liveTrackingPillHdText,
                  {
                    color: colorScheme === 'light' ? '#0F766E' : '#D0EDE8',
                  },
                ]}
              >
                {t('Live tracking')}
              </Text>
            </View>
          ) : null}
        </View>
        <View
          style={[
            styles.mapWrap,
            USE_NEXA_BOOKING_REDESIGN && styles.mapWrapRounded,
            { height: tripMapHeight },
          ]}
        >
          <MapView
            ref={tripMapRef}
            style={styles.map}
            provider={PROVIDER_GOOGLE}
            {...(colorScheme === 'dark' ? { customMapStyle: DARK_MAP_STYLE } : {})}
            initialRegion={{
              latitude: (activeTaxiLoc?.lat ?? Number(live.pickup_lat)) || 52.22,
              longitude: (activeTaxiLoc?.lng ?? Number(live.pickup_lng)) || 6.89,
              latitudeDelta: 0.05,
              longitudeDelta: 0.05,
            }}
            showsUserLocation
            showsMyLocationButton={false}
            scrollEnabled
            zoomEnabled
            pitchEnabled={false}
            rotateEnabled={false}
            moveOnMarkerPress={false}
            onPanDrag={pauseFollow}
            onRegionChange={(_region, details) => {
              if (details?.isGesture) pauseFollow();
            }}
          >
            {live.pickup_lat != null && live.pickup_lng != null ? (
              <Marker
                coordinate={{
                  latitude: Number(live.pickup_lat),
                  longitude: Number(live.pickup_lng),
                }}
                title={t("Ophalen")}
                anchor={{ x: 0.5, y: 1 }}
                tracksViewChanges={false}
                zIndex={12}
              >
                <MapPlaceCallout
                  label={t('Ophaalpunt')}
                  address={String(live.pickup_place_name || live.pickup_address || t('Ophalen'))}
                  tone="blue"
                  icon="person"
                />
              </Marker>
            ) : null}
            {live.dropoff_lat != null && live.dropoff_lng != null ? (
              <Marker
                coordinate={{
                  latitude: Number(live.dropoff_lat),
                  longitude: Number(live.dropoff_lng),
                }}
                title={t("Afzetten")}
                anchor={{ x: 0.5, y: 1 }}
                tracksViewChanges={false}
                zIndex={12}
              >
                <MapPlaceCallout
                  label={t('Bestemming')}
                  address={String(
                    live.dropoff_place_name || live.dropoff_address || t('Bestemming')
                  )}
                  tone="orange"
                  icon="flag"
                />
              </Marker>
            ) : null}
            {activeTaxiLoc
              ? Platform.OS === 'android'
                ? androidTaxiMarker(
                    {
                      id: 'trip-taxi',
                      lat: activeTaxiLoc.lat,
                      lng: activeTaxiLoc.lng,
                      heading: activeTaxiLoc.heading,
                    },
                    t('Je taxi'),
                    30
                  )
                : (
                    <CustomerTaxiMarker
                      title={t('Je taxi')}
                      taxi={{
                        id: 'trip-taxi',
                        lat: activeTaxiLoc.lat,
                        lng: activeTaxiLoc.lng,
                        heading: activeTaxiLoc.heading,
                      }}
                    />
                  )
              : null}
            {tripRoute.length > 1 ? (
              <Polyline
                coordinates={tripRoute}
                strokeColor={NEON_ROUTE}
                strokeWidth={5}
                lineCap="round"
                lineJoin="round"
              />
            ) : null}
          </MapView>
          {/* Geen ETA-chip op de kaart — aankomst staat al bij chauffeurgegevens. */}
          <View style={styles.zoomControls} pointerEvents="box-none">
            <Pressable
              onPress={() => zoomTripMap(1)}
              style={styles.zoomBtn}
              accessibilityLabel={t("Inzoomen")}
            >
              <Text style={styles.zoomBtnText}>+</Text>
            </Pressable>
            <Pressable
              onPress={() => zoomTripMap(-1)}
              style={styles.zoomBtn}
              accessibilityLabel={t("Uitzoomen")}
            >
              <Text style={styles.zoomBtnText}>−</Text>
            </Pressable>
          </View>
          <View style={styles.mapBottomRightControls} pointerEvents="box-none">
            <Pressable
              onPress={fitTripRoute}
              style={styles.zoomBtn}
              accessibilityLabel={t("Toon route")}
              accessibilityHint={t("Centreert de kaart op de geplande rit")}
            >
              <Ionicons name="navigate" size={18} color={colors.text} />
            </Pressable>
            <Pressable
              onPress={focusTripTaxi}
              style={styles.zoomBtn}
              accessibilityLabel={t("Focus op taxi")}
              accessibilityHint={t("Centreert de kaart op je taxi en volgt die")}
            >
              <Ionicons name="locate" size={18} color={colors.text} />
            </Pressable>
          </View>
        </View>
        <View
          onLayout={(e) => {
            tripStatusY.current = e.nativeEvent.layout.y;
          }}
        >
        <View style={{ gap: 12 }}>
          {invoiceSentLabel ? (
            <Text
              style={[
                styles.liveTitle,
                { color: nexaSurfaceText(colorScheme === 'light') },
              ]}
            >
              {invoiceSentLabel}
            </Text>
          ) : (
            <NexaAssignedRidePanel
              live={live}
              driverComing={driverComing}
              tripStarted={tripStarted}
              onPreview={setPhotoPreviewUri}
              onCall={callAssignedDriver}
              onChat={openRideChat}
              chatAttention={chatAttention}
              onLiveFollow={focusAcceptedMap}
              embedInScreenBackdrop
            />
          )}
          {(() => {
            const livePin = pickupPinForRide({
              token: trackToken || '',
              pickup_confirmation: live.pickup_confirmation,
            } as GuestRide);
            if (!livePin || livePin.started || revealedPinKeys.includes(livePin.key)) {
              return null;
            }
            return (
              <NexaPickupCodeButton
                onPress={() =>
                  setRevealedPinKeys((keys) =>
                    keys.includes(livePin.key) ? keys : [...keys, livePin.key]
                  )
                }
              />
            );
          })()}
          {live.quoted_price != null ? (
            <View style={styles.rideCardFoot}>
              <View style={{ flex: 1, minWidth: 0 }}>
                <Text
                  style={[
                    styles.metaCaption,
                    { color: nexaSurfaceMuted(colorScheme === 'light') },
                  ]}
                >
                  {t("Datum")}
                </Text>
                <Text
                  style={[
                    styles.rideCardAmount,
                    { color: nexaSurfaceText(colorScheme === 'light') },
                  ]}
                >
                  {formatPickupWhen(livePickupAt(live)) || '—'}
                </Text>
              </View>
              <View style={{ alignItems: 'flex-end' }}>
                <Text
                  style={[
                    styles.metaCaption,
                    { color: nexaSurfaceMuted(colorScheme === 'light') },
                  ]}
                >
                  {t("Prijs")}
                </Text>
                <Text
                  style={[
                    styles.rideCardAmount,
                    { color: nexaSurfaceText(colorScheme === 'light') },
                  ]}
                >
                  {formatEuroNl(Number(live.quoted_price))}
                </Text>
              </View>
            </View>
          ) : null}
        </View>
        </View>
      </View>
    ) : null;

  const ridesContent = (
    <ScrollView
      ref={ridesScrollRef}
      style={styles.formScroll}
      contentContainerStyle={[
        styles.scroll,
        { paddingBottom: 16 },
        rideMapOpen ? { paddingBottom: 20 } : null,
      ]}
      keyboardShouldPersistTaps="handled"
      nestedScrollEnabled
    >
      {showArchivedRides ? (
        <>
          <View style={styles.archiveSectionRow}>
            <Text style={styles.archiveSectionTitle}>{t("Gearchiveerde ritten")}</Text>
            <Pressable
              onPress={() => setShowArchivedRides(false)}
              hitSlop={8}
              accessibilityRole="button"
              accessibilityLabel={t("Terug naar ritten")}
              style={styles.archiveBackLinkPress}
            >
              <Text style={styles.archiveBackLink}>{t("Ritten →")}</Text>
            </Pressable>
          </View>
          {archivedPast.length === 0 ? (
            <Card>
              <Text style={styles.meta}>
                {t(
                  'Gearchiveerde ritten verschijnen hier. Tik op Naar archief bij een eerdere rit om die te bewaren.'
                )}
              </Text>
            </Card>
          ) : (
            archivedPast.map(renderRideCard)
          )}
        </>
      ) : !rideMapOpen &&
        listedActive.length === 0 &&
        visiblePast.length === 0 &&
        archivedPast.length === 0 ? (
        <Card>
          <Text style={styles.meta}>{t("Nog geen ritten. Boek een taxi via Boeken; openstaande en eerdere ritten zie je hier.")}</Text>
          <PrimaryButton title={t("Taxi boeken")} onPress={goToBookTab} />
        </Card>
      ) : (
        <>
          {tripPanel}
          {listedActive.length > 0 ? (
            <>
              <Text style={styles.ridesSection}>{t("Openstaand")}</Text>
              {listedActive.map(renderRideCard)}
            </>
          ) : null}
          {visiblePast.length > 0 ? (
            <>
              <Text style={styles.ridesSection}>
                {listedActive.length || rideMapOpen ? t("Eerdere ritten") : t("Alle ritten")}
              </Text>
              {visiblePast.map(renderRideCard)}
            </>
          ) : null}
          {archivedPast.length > 0 ? (
            <Pressable
              style={styles.archiveLink}
              onPress={() => setShowArchivedRides(true)}
              accessibilityRole="button"
              accessibilityLabel={`Archief, ${archivedPast.length} ${
                archivedPast.length === 1 ? 'rit' : 'ritten'
              }. Bekijken`}
            >
              <Text style={styles.archiveLinkMeta}>
                Archief • {archivedPast.length}{' '}
                {archivedPast.length === 1 ? 'rit' : 'ritten'}
              </Text>
              <Text style={styles.archiveLinkCta}>{t("Bekijken →")}</Text>
            </Pressable>
          ) : null}
        </>
      )}
    </ScrollView>
  );

  const profileContent = (
    <ScrollView
      style={styles.formScroll}
      contentContainerStyle={[styles.scroll, { paddingBottom: 24 + keyboardOverlap }]}
      keyboardShouldPersistTaps="handled"
      nestedScrollEnabled
    >
      <ErrorText>{error}</ErrorText>
      {USE_NEXA_BOOKING_REDESIGN ? (
        <NexaGlowCard contentStyle={styles.profileGlowInner} style={styles.profileGlowCard}>
          {showProfileInputs ? (
            <>
              <Field label={t("Voornaam")} value={firstName} onChangeText={setFirstName} autoCapitalize="words" />
              <Field label={t("Achternaam")} value={lastName} onChangeText={setLastName} autoCapitalize="words" />
              <Field
                label={t("Telefoon")}
                value={phone}
                onChangeText={setPhone}
                keyboardType="phone-pad"
                placeholder="06…"
              />
              <Field
                label="E-mail"
                value={email}
                onChangeText={setEmail}
                keyboardType="email-address"
                autoCapitalize="none"
                placeholder="naam@email.nl"
              />
              {profileSaved ? (
                <Text style={[styles.meta, { color: colors.success }]}>{t("Opgeslagen.")}</Text>
              ) : null}
              <PrimaryButton title={t("Opslaan")} onPress={() => void saveProfile()} />
              {profileEditing ? (
                <>
                  <View style={{ height: 10 }} />
                  <GhostButton title={t("Annuleren")} onPress={() => cancelContactEdit('profile')} />
                </>
              ) : null}
            </>
          ) : (
            <>
              <View style={styles.contactReadonlyField}>
                <Text style={styles.rowLabel}>{t("Voornaam")}</Text>
                <Text style={styles.rowValue}>{firstName}</Text>
              </View>
              <View style={styles.contactReadonlyField}>
                <Text style={styles.rowLabel}>{t("Achternaam")}</Text>
                <Text style={styles.rowValue}>{lastName}</Text>
              </View>
              <View style={styles.contactReadonlyField}>
                <Text style={styles.rowLabel}>{t("Telefoon")}</Text>
                <Text style={styles.rowValue}>{phone}</Text>
              </View>
              <View style={styles.contactReadonlyField}>
                <Text style={styles.rowLabel}>E-mail</Text>
                <Text style={styles.rowValue}>{email}</Text>
              </View>
              {profileSaved ? (
                <Text style={[styles.meta, { color: colors.success }]}>{t("Opgeslagen.")}</Text>
              ) : null}
              <PrimaryButton title={t("Bewerk")} onPress={() => beginContactEdit('profile')} />
            </>
          )}
        </NexaGlowCard>
      ) : (
        <Card>
          {showProfileInputs ? (
            <>
              <Field label={t("Voornaam")} value={firstName} onChangeText={setFirstName} autoCapitalize="words" />
              <Field label={t("Achternaam")} value={lastName} onChangeText={setLastName} autoCapitalize="words" />
              <Field
                label={t("Telefoon")}
                value={phone}
                onChangeText={setPhone}
                keyboardType="phone-pad"
                placeholder="06…"
              />
              <Field
                label="E-mail"
                value={email}
                onChangeText={setEmail}
                keyboardType="email-address"
                autoCapitalize="none"
                placeholder="naam@email.nl"
              />
              {profileSaved ? <Text style={[styles.meta, { color: colors.success }]}>{t("Opgeslagen.")}</Text> : null}
              <PrimaryButton title={t("Opslaan")} onPress={() => void saveProfile()} />
              {profileEditing ? (
                <>
                  <View style={{ height: 10 }} />
                  <GhostButton title={t("Annuleren")} onPress={() => cancelContactEdit('profile')} />
                </>
              ) : null}
            </>
          ) : (
            <>
              <View style={styles.contactReadonlyField}>
                <Text style={styles.rowLabel}>{t("Voornaam")}</Text>
                <Text style={styles.rowValue}>{firstName}</Text>
              </View>
              <View style={styles.contactReadonlyField}>
                <Text style={styles.rowLabel}>{t("Achternaam")}</Text>
                <Text style={styles.rowValue}>{lastName}</Text>
              </View>
              <View style={styles.contactReadonlyField}>
                <Text style={styles.rowLabel}>{t("Telefoon")}</Text>
                <Text style={styles.rowValue}>{phone}</Text>
              </View>
              <View style={styles.contactReadonlyField}>
                <Text style={styles.rowLabel}>E-mail</Text>
                <Text style={styles.rowValue}>{email}</Text>
              </View>
              {profileSaved ? <Text style={[styles.meta, { color: colors.success }]}>{t("Opgeslagen.")}</Text> : null}
              <PrimaryButton title={t("Bewerk")} onPress={() => beginContactEdit('profile')} />
            </>
          )}
        </Card>
      )}

      {USE_NEXA_BOOKING_REDESIGN ? (
        <NexaGlowCard contentStyle={styles.profileGlowInner} style={styles.profileGlowCard}>
          <Text style={styles.section}>{t("Weergave")}</Text>
          <Text style={styles.themeHint}>
            {t(
              'Standaard volgt de app de light/dark-modus van je telefoon. Kies hieronder alleen als je dat wilt overschrijven.'
            )}
          </Text>
          {THEME_OPTIONS.map((opt) => {
            const selected = preference === opt.key;
            return (
              <Pressable
                key={opt.key}
                onPress={() => setPreference(opt.key)}
                style={[styles.themeRow, selected && styles.themeRowActive]}
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
        </NexaGlowCard>
      ) : (
        <Card>
          <Text style={styles.section}>{t("Weergave")}</Text>
          <Text style={styles.themeHint}>
            {t(
              'Standaard volgt de app de light/dark-modus van je telefoon. Kies hieronder alleen als je dat wilt overschrijven.'
            )}
          </Text>
          {THEME_OPTIONS.map((opt) => {
            const selected = preference === opt.key;
            return (
              <Pressable
                key={opt.key}
                onPress={() => setPreference(opt.key)}
                style={[styles.themeRow, selected && styles.themeRowActive]}
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
        </Card>
      )}

      <DemoModeRow showOpenBooking={false} />
    </ScrollView>
  );

  const refundDays = Math.max(1, Number(live?.refund_business_days) || 10);

  function pickupPinForRide(ride: GuestRide): { pin: string; prompt: string; started: boolean; key: string } | null {
    const confirm =
      (ride.token === trackToken ? live?.pickup_confirmation : null) ?? ride.pickup_confirmation ?? null;
    const pin = String(confirm?.pin || '').replace(/\D/g, '');
    if (pin.length !== 4) return null;
    const rideStatus = ride.token === trackToken ? String(live?.status || '') : '';
    return {
      pin,
      // Altijd via i18n — API-prompt is NL en blijft anders zichtbaar in EN.
      prompt: t("Geef deze ophaalcode aan de chauffeur om de rit te starten."),
      started: rideStatus === 'assigned' || confirm?.status === 'confirmed',
      key: `${ride.token}:${pin}`,
    };
  }

  const pickupPinInfo = (() => {
    const ride =
      (trackToken
        ? guestRides.find((item) => item.token === trackToken && isActiveRidePhase(item.phase))
        : null) ||
      guestRides.find((item) => isActiveRidePhase(item.phase)) ||
      null;
    if (!ride) return null;
    const info = pickupPinForRide(ride);
    if (!info || info.started) return null;
    return info;
  })();
  const pickupPinRevealed = !!(
    pickupPinInfo && revealedPinKeys.includes(pickupPinInfo.key)
  );

  // Home/Boeken hebben eigen map→hero overloop; geen tweede screen-backdrop.
  const showMapBackdrop =
    USE_NEXA_BOOKING_REDESIGN &&
    (tab === 'rides' || tab === 'profile' || tab === 'more');
  const lightMode = colorScheme === 'light';

  return (
    <Screen
      style={[
        styles.shell,
        {
          // Onderkant = footer-kleur zodat er geen zwarte spleet onder de tabbar is.
          backgroundColor: lightMode
            ? '#FFFFFF'
            : showMapBackdrop
              ? 'transparent'
              : nexaBookingTheme.color.navy800,
        },
      ]}
    >
      {showMapBackdrop ? <NexaScreenBackdrop /> : null}
      {appHeader}
      {demoActive && demo.isSuperAdmin ? (
        <View style={styles.demoModeBanner} accessibilityRole="text">
          <Text style={styles.demoModeBannerTitle}>{t('Demo-modus')}</Text>
          <Text style={styles.demoModeBannerText}>
            {t('Demo-modus actief op deze omgeving — virtuele taxi’s zichtbaar voor alle klanten.')}
          </Text>
        </View>
      ) : null}
      <View style={styles.shellBody}>
        {USE_NEXA_BOOKING_REDESIGN && tab === 'home'
          ? homeContent
          : tab === 'rides'
            ? ridesContent
            : tab === 'profile' || tab === 'more'
              ? profileContent
              : (
                  <View style={styles.bookPane}>
                    <ScrollView
                      key={`book-${bookMountKey}`}
                      ref={bookScrollRef}
                      style={styles.formScroll}
                      contentContainerStyle={[
                        styles.scroll,
                        // Zelfde top als Home: geen lege strip boven de hero.
                        USE_NEXA_BOOKING_REDESIGN ? { paddingTop: 0 } : null,
                        { paddingBottom: 24 + keyboardOverlap },
                      ]}
                      keyboardShouldPersistTaps="handled"
                      keyboardDismissMode="on-drag"
                      automaticallyAdjustKeyboardInsets
                      nestedScrollEnabled
                      removeClippedSubviews={false}
                      scrollEventThrottle={8}
                      onScroll={(e) => {
                        if (!USE_NEXA_BOOKING_REDESIGN) return;
                        updateBookChromeFromScrollY(e.nativeEvent.contentOffset.y);
                      }}
                    >
                      {bookContent}
                    </ScrollView>
                    {USE_NEXA_BOOKING_REDESIGN ? (
                      <View
                        collapsable={false}
                        style={[
                          styles.bookStickyChrome,
                          {
                            height: insets.top + APP_CHROME_HEIGHT,
                            backgroundColor:
                              colorScheme === 'light'
                                ? `rgba(255,255,255,${bookChromeOpacity})`
                                : `rgba(6,19,41,${bookChromeOpacity})`,
                            borderBottomWidth: bookChromeOpacity > 0.2 ? StyleSheet.hairlineWidth : 0,
                            borderBottomColor:
                              colorScheme === 'light'
                                ? 'rgba(15,23,42,0.12)'
                                : 'rgba(255,255,255,0.12)',
                          },
                        ]}
                        pointerEvents="box-none"
                      >
                        <View
                          style={{
                            paddingTop: insets.top,
                            zIndex: 2,
                            height: insets.top + APP_CHROME_HEIGHT,
                          }}
                          pointerEvents="box-none"
                        >
                          <View style={nexaHeroChromeRow}>
                            <View style={nexaHeroSideSlot}>
                              <Pressable
                                onPress={onBack}
                                hitSlop={10}
                                accessibilityRole="button"
                                accessibilityLabel={t('Naar startpagina')}
                                style={[
                                  styles.bookStickyBack,
                                  colorScheme === 'light' && styles.bookStickyBackLight,
                                ]}
                              >
                                <Ionicons
                                  name="home-outline"
                                  size={24}
                                  color={
                                    colorScheme === 'light'
                                      ? nexaBookingTheme.color.ink
                                      : '#FFFFFF'
                                  }
                                />
                              </Pressable>
                            </View>
                            <Pressable
                              onPress={() => {
                                setTab('home');
                                AsyncStorage.setItem(TAB_KEY, 'home').catch(() => undefined);
                              }}
                              style={styles.bookStickyLogo}
                              accessibilityRole="button"
                              accessibilityLabel={t('Naar startpagina')}
                            >
                              <AppBrandLogo bare />
                            </Pressable>
                            <View style={[nexaHeroSideSlot, nexaHeroSideRight]}>
                              <AppearanceControls
                                tone={colorScheme === 'light' ? 'theme' : 'onDark'}
                              />
                            </View>
                          </View>
                        </View>
                      </View>
                    ) : null}
                  </View>
                )}
      </View>
      {USE_NEXA_BOOKING_REDESIGN ? (
        <NexaCustomerTabBar
          active={
            tab === 'profile'
              ? 'more'
              : (tab as NexaCustomerTabKey)
          }
          onChange={(key) => {
            if (key === 'book') {
              goToBookTab();
              return;
            }
            if (key === 'home') {
              leaveContactEditModes();
              setTab('home');
              AsyncStorage.setItem(TAB_KEY, 'home').catch(() => undefined);
              setError(null);
              Keyboard.dismiss();
              return;
            }
            leaveContactEditModes();
            if (key === 'rides') acknowledgeRidesBadge();
            setTab(key);
            AsyncStorage.setItem(TAB_KEY, key).catch(() => undefined);
            setError(null);
            setProfileSaved(false);
            setShowArchivedRides(false);
            Keyboard.dismiss();
            if (key === 'rides' && activeBannerRide) {
              setExpandedRideKey(rideArchiveKey(activeBannerRide));
            }
          }}
          ridesBadge={ridesBadgeCount}
        />
      ) : (
        <CustomerTabBar
          active={tab === 'book' || tab === 'rides' || tab === 'profile' ? tab : 'book'}
          onChange={(key) => {
            if (key === 'book') {
              goToBookTab();
              return;
            }
            leaveContactEditModes();
            if (key === 'rides') acknowledgeRidesBadge();
            setTab(key);
            AsyncStorage.setItem(TAB_KEY, key).catch(() => undefined);
            setError(null);
            setProfileSaved(false);
            setShowArchivedRides(false);
            Keyboard.dismiss();
            if (key === 'rides' && activeBannerRide) {
              setExpandedRideKey(rideArchiveKey(activeBannerRide));
            }
          }}
          ridesBadge={ridesBadgeCount}
        />
      )}

      {pickupPinInfo && tab === 'rides' ? (
        <NexaPickupCodeModal
          visible={pickupPinRevealed}
          pin={pickupPinInfo.pin}
          onClose={() =>
            setRevealedPinKeys((keys) => keys.filter((key) => key !== pickupPinInfo.key))
          }
        />
      ) : null}

      <AppModal
        visible={cancelConfirmOpen}
        onRequestClose={() => {
          setCancelConfirmOpen(false);
          setCancelTargetToken(null);
        }}
        panelStyle={styles.confirmBanner}
      >
        <Text style={styles.confirmTitle}>{t("Rit annuleren?")}</Text>
        <Text style={styles.confirmText}>
          {t(
            'Weet je zeker dat je deze rit wilt annuleren? Als je vooraf hebt betaald, wordt het bedrag teruggestort (doorgaans binnen {days} werkdagen).',
            { days: refundDays }
          )}
        </Text>
        <View style={styles.confirmActions}>
          <View style={styles.confirmActionSlot}>
            <Pressable
              onPress={() => {
                setCancelConfirmOpen(false);
                setCancelTargetToken(null);
              }}
              style={styles.confirmSecondaryBtn}
            >
              <Text style={styles.confirmSecondaryText}>{t("Nee, behouden")}</Text>
            </Pressable>
          </View>
          <View style={styles.confirmActionSlot}>
            <Pressable onPress={() => void confirmCancel()} style={styles.confirmDangerBtn}>
              <Text style={styles.confirmDangerText}>{t("Ja, annuleren")}</Text>
            </Pressable>
          </View>
        </View>
      </AppModal>

      <AppModal
        visible={showWaitDecisionModal}
        onRequestClose={() => undefined}
        dismissOnBackdrop={false}
        dismissDisabled
        panelStyle={styles.confirmBanner}
      >
        <Text style={styles.confirmTitle}>{t('Nog geen taxi gevonden')}</Text>
        <Text style={styles.confirmText}>
          {live?.decision_minutes
            ? t('Wil je blijven wachten (tot ca. {time}) of de rit annuleren?', {
                time: String(live.decision_deadline_label || '—'),
              })
            : t('Wil je blijven wachten of de rit annuleren?')}
        </Text>
        {renderWaitStyleRoute({
          from: live?.pickup_address,
          to: live?.dropoff_address,
          toPlace: live?.dropoff_place_name || openGuestRide?.to_place,
        })}
        <View style={{ marginTop: 14, gap: 10 }}>
          <PrimaryButton title={t('Blijven wachten')} onPress={() => void onWait()} loading={busy} />
          <GhostButton
            title={t('Annuleren')}
            danger
            onPress={() => requestCancel(trackToken)}
          />
        </View>
      </AppModal>

      <AppModal
        visible={!!longWaitCancelRide}
        onRequestClose={() => undefined}
        placement="center"
        dismissOnBackdrop={false}
        dismissDisabled
        scrollEnabled={false}
        panelStyle={{
          ...styles.cancelAckPanel,
          borderColor:
            colorScheme === 'light'
              ? 'rgba(15, 132, 255, 0.35)'
              : 'rgba(0, 163, 255, 0.75)',
        }}
      >
        <Text style={styles.cancelAckTitle}>{t("Rit geannuleerd")}</Text>
        {longWaitCancelRide ? (
          <>
            {renderWaitStyleRoute({
              from: longWaitCancelRide.from,
              to: longWaitCancelRide.to,
              fromPlace: longWaitCancelRide.from_place,
              toPlace: longWaitCancelRide.to_place,
            })}
            {formatPickupWhen(longWaitCancelRide.pickup_at) ? (
              <Text style={styles.cancelAckMeta}>
                {t('Ophalen')}: {formatPickupWhen(longWaitCancelRide.pickup_at)}
              </Text>
            ) : null}
          </>
        ) : null}
        <Text style={styles.cancelAckBody}>
          {t(
            String(
              longWaitCancelRide?.cancellation_message ||
                live?.cancellation_message ||
                'De rit is geannuleerd omdat het wachten te lang duurde. Er is geen chauffeur gevonden.'
            ).trim()
          )}
        </Text>
        <Pressable
          onPress={() => {
            const ride = longWaitCancelRide;
            setLongWaitCancelRide(null);
            if (!ride) return;
            const key = rideArchiveKey(ride);
            if (key) longWaitCancelShownRef.current.add(key);
            void toggleArchiveRide(ride, true);
            if (trackToken && (ride.token === trackToken || Number(live?.id) === Number(ride.id))) {
              setTrackToken(null);
              setLive(null);
              void AsyncStorage.removeItem(TRACK_KEY);
            }
          }}
          style={({ pressed }) => [styles.cancelAckCta, pressed && { opacity: 0.9 }]}
          accessibilityRole="button"
          accessibilityLabel={t('Sluiten')}
        >
          <Text style={styles.cancelAckCtaText}>{t('Sluiten')}</Text>
        </Pressable>
      </AppModal>

      <AppModal
        visible={!!rateModalToken}
        onRequestClose={() => {
          if (!busy) setRateModalToken(null);
        }}
        dismissDisabled={busy}
        panelStyle={styles.confirmBanner}
      >
        <Text style={styles.confirmTitle}>{t("Beoordeel je rit")}</Text>
        <Text style={styles.confirmText}>{t("Hoe was de rit?")}</Text>
        <View style={[styles.starsRow, { marginBottom: 12 }]}>
          {[1, 2, 3, 4, 5].map((n) => (
            <Pressable key={n} onPress={() => setRateStars(n)} hitSlop={8}>
              <Ionicons
                name={n <= rateStars ? 'star' : 'star-outline'}
                size={32}
                color={n <= rateStars ? '#F5B301' : colors.muted}
              />
            </Pressable>
          ))}
        </View>
        <TextInput
          style={[styles.rateInput, { marginBottom: 12 }]}
          value={rateComment}
          onChangeText={setRateComment}
          placeholder={t("Optionele opmerking")}
          placeholderTextColor={colors.muted}
          multiline
        />
        {rateError ? (
          <Text style={[styles.ratingComment, { color: colors.danger, marginBottom: 12 }]}>
            {rateError}
          </Text>
        ) : null}
        <View style={styles.confirmActions}>
          <View style={styles.confirmActionSlot}>
            <Pressable
              onPress={() => setRateModalToken(null)}
              disabled={busy}
              style={[styles.confirmSecondaryBtn, busy && { opacity: 0.55 }]}
            >
              <Text style={styles.confirmSecondaryText}>{t("Later")}</Text>
            </Pressable>
          </View>
          <View style={styles.confirmActionSlot}>
            <PrimaryButton
              title={t("Versturen")}
              onPress={() => {
                if (rateModalToken) onSubmitRating(rateModalToken);
              }}
              loading={busy}
            />
          </View>
        </View>
      </AppModal>

      <AppModal
        visible={!!complaintModalToken}
        onRequestClose={() => {
          if (!busy) setComplaintModalToken(null);
        }}
        dismissDisabled={busy}
        showsVerticalScrollIndicator={false}
        panelStyle={styles.confirmBanner}
      >
        <Text style={styles.confirmTitle}>{t('Klacht indienen')}</Text>
        <Text style={styles.confirmText}>
          {t('Meld een probleem over deze rit. We behandelen je klacht via NEXA Suite.')}
        </Text>
        <Text style={[styles.rowLabel, { marginBottom: 6 }]}>{t('Categorie')}</Text>
        <View style={styles.complaintCategoryWrap}>
          {COMPLAINT_CATEGORIES.map((item) => {
            const selected = complaintCategory === item.value;
            return (
              <Pressable
                key={item.value}
                onPress={() => setComplaintCategory(item.value)}
                style={[
                  styles.complaintChip,
                  selected ? styles.complaintChipSelected : null,
                ]}
              >
                <Text
                  style={[
                    styles.complaintChipText,
                    selected ? styles.complaintChipTextSelected : null,
                  ]}
                  numberOfLines={2}
                >
                  {t(item.label)}
                </Text>
              </Pressable>
            );
          })}
        </View>
        <TextInput
          style={[styles.rateInput, { marginBottom: 10, minHeight: 40 }]}
          value={complaintSubject}
          onChangeText={setComplaintSubject}
          placeholder={t('Onderwerp')}
          placeholderTextColor={colors.muted}
        />
        <TextInput
          style={[styles.rateInput, { marginBottom: 10, minHeight: 90 }]}
          value={complaintDescription}
          onChangeText={setComplaintDescription}
          placeholder={t('Omschrijving van de klacht')}
          placeholderTextColor={colors.muted}
          multiline
        />
        <TextInput
          style={[styles.rateInput, { marginBottom: 10, minHeight: 40 }]}
          value={complaintEmail}
          onChangeText={setComplaintEmail}
          placeholder={t('E-mail voor terugkoppeling (optioneel)')}
          placeholderTextColor={colors.muted}
          keyboardType="email-address"
          autoCapitalize="none"
        />
        <TextInput
          style={[styles.rateInput, { marginBottom: 10, minHeight: 40 }]}
          value={complaintPhone}
          onChangeText={setComplaintPhone}
          placeholder={t('Telefoon (optioneel)')}
          placeholderTextColor={colors.muted}
          keyboardType="phone-pad"
        />
        {complaintError ? (
          <Text style={[styles.ratingComment, { color: colors.danger, marginBottom: 12 }]}>
            {complaintError}
          </Text>
        ) : null}
        <View style={styles.confirmActions}>
          <View style={styles.confirmActionSlot}>
            <Pressable
              onPress={() => setComplaintModalToken(null)}
              disabled={busy}
              style={[styles.confirmSecondaryBtn, busy && { opacity: 0.55 }]}
            >
              <Text style={styles.confirmSecondaryText}>{t('Annuleren')}</Text>
            </Pressable>
          </View>
          <View style={styles.confirmActionSlot}>
            <PrimaryButton
              title={t('Indienen')}
              onPress={() => {
                if (complaintModalToken) onSubmitComplaint(complaintModalToken);
              }}
              loading={busy}
            />
          </View>
        </View>
      </AppModal>

      <AppModal
        visible={!!photoPreviewUri}
        onRequestClose={() => setPhotoPreviewUri(null)}
        panelStyle={styles.photoPreviewPanel}
        avoidKeyboard={false}
        scrollEnabled={false}
        enterAnimation="zoom"
      >
        <Pressable
          onPress={() => setPhotoPreviewUri(null)}
          accessibilityRole="button"
          accessibilityLabel={t("Sluiten")}
          hitSlop={8}
          style={styles.photoPreviewClose}
        >
          <Ionicons name="close" size={22} color={colors.text} />
        </Pressable>
        {photoPreviewUri ? (
          <Pressable
            onPress={() => setPhotoPreviewUri(null)}
            accessibilityRole="button"
            accessibilityLabel={t("Sluiten")}
          >
            <Image
              source={{ uri: photoPreviewUri }}
              style={styles.photoPreviewImage}
              resizeMode="contain"
            />
          </Pressable>
        ) : null}
      </AppModal>

      <RideChatModal
        visible={chatOpen}
        onClose={() => setChatOpen(false)}
        driverName={live?.driver?.name}
        peerPhotoUrl={resolveCustomerMediaUrl(live?.driver?.photo_url)}
        messages={chatMessages}
        sending={chatSending}
        readOnly={!chatAllowed}
        onSend={sendRideChatMessage}
      />
    </Screen>
  );
}

function ActiveRideBanner({
  ride,
  etaLabel,
  onPress,
  isLive = false,
}: {
  ride: GuestRide;
  etaLabel?: string | null;
  onPress: () => void;
  /** Live tracking actief (chauffeur onderweg). */
  isLive?: boolean;
}) {
  const { colors } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const shimmer = useRef(new Animated.Value(0)).current;
  const isSearching = ride.phase === 'searching' || ride.phase === 'awaiting_payment';
  const nexa = USE_NEXA_BOOKING_REDESIGN;

  useEffect(() => {
    if (!isSearching) {
      shimmer.setValue(0);
      return;
    }
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(shimmer, {
          toValue: 1,
          duration: 2200,
          easing: Easing.inOut(Easing.quad),
          useNativeDriver: true,
        }),
        Animated.timing(shimmer, {
          toValue: 0,
          duration: 2200,
          easing: Easing.inOut(Easing.quad),
          useNativeDriver: true,
        }),
      ])
    );
    loop.start();
    return () => loop.stop();
  }, [isSearching, shimmer]);

  const washOpacity = shimmer.interpolate({
    inputRange: [0, 1],
    outputRange: [0.08, 0.22],
  });

  if (nexa) {
    // Prioriteit: bedrijfs-/plaatsnaam → pas daarna straatadres.
    const placeName = String(ride.to_place || '').trim();
    const destination =
      placeName ||
      shortAddress(ride.to) ||
      String(ride.to || '').trim() ||
      null;
    const routeText = destination
      ? t('Bestemming: {destination}', { destination })
      : null;
    const enRoute = ride.phase === 'accepted' && (!!etaLabel || isLive);
    const title = enRoute
      ? t('Chauffeur onderweg')
      : ride.phase === 'accepted'
        ? t('De chauffeur heeft je rit geaccepteerd.')
        : t('Openstaande rit');
    const etaText = enRoute
      ? etaLabel
        ? t('Je chauffeur arriveert over {eta}.', { eta: etaLabel })
        : t('De aankomsttijd wordt bijgewerkt.')
      : null;

    return (
      <DriverEnRouteStatusCard
        title={title}
        etaText={etaText}
        routeText={routeText}
        isLive={enRoute && isLive}
        onFollow={onPress}
      />
    );
  }

  const placeName = String(ride.to_place || '').trim();
  const destination =
    placeName ||
    shortAddress(ride.to) ||
    String(ride.to || '').trim() ||
    null;

  return (
    <Pressable
      style={[
        styles.activeBanner,
        ride.phase === 'accepted' && styles.activeBannerAccepted,
      ]}
      onPress={onPress}
      accessibilityRole="button"
      accessibilityLabel={t("Openstaande rit volgen")}
    >
      {isSearching ? (
        <Animated.View
          pointerEvents="none"
          style={[styles.activeBannerWash, { opacity: washOpacity }]}
        />
      ) : null}
      <View style={{ flex: 1, minWidth: 0 }}>
        <Text style={styles.activeBannerTitle} numberOfLines={2}>
          {ride.phase === 'accepted'
            ? etaLabel
              ? t("De chauffeur is onderweg naar jou.")
              : t("De chauffeur heeft je rit geaccepteerd.")
            : t("Openstaande rit")}
        </Text>
        {etaLabel ? (
          <Text style={styles.tripEta}>Chauffeur arriveert over {etaLabel}.</Text>
        ) : null}
        {destination ? (
          <Text style={styles.activeBannerMeta} numberOfLines={1}>
            {t('Bestemming: {destination}', { destination })}
          </Text>
        ) : null}
      </View>
      <Text style={styles.activeBannerCta}>{t("Volgen")}</Text>
    </Pressable>
  );
}

const SEARCHING_MESSAGES = [
  'We zoeken een taxi',
  'Wacht op bevestiging',
] as const;

/** AI-zoekknop: blauw → turkoois → groen → terug */
const AI_SEARCH_COLORS = ['#2563EB', '#06B6D4', '#14B8A6', '#22C55E', '#14B8A6', '#06B6D4', '#2563EB'] as const;

function StatusDot({ color }: { color: string }) {
  return (
    <View
      style={{
        width: 8,
        height: 8,
        borderRadius: 999,
        backgroundColor: color,
        marginRight: 8,
      }}
      accessibilityElementsHidden
    />
  );
}

function SearchingTaxiTitle() {
  const { colors } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const [messageIndex, setMessageIndex] = useState(0);
  const [typed, setTyped] = useState('');
  const [dots, setDots] = useState(0);
  const [holding, setHolding] = useState(false);

  useEffect(() => {
    const full = SEARCHING_MESSAGES[messageIndex];
    setTyped('');
    setDots(0);
    setHolding(false);

    let i = 0;
    let holdTimer: ReturnType<typeof setTimeout> | null = null;
    let dotsTimer: ReturnType<typeof setInterval> | null = null;
    const typeTimer = setInterval(() => {
      i += 1;
      setTyped(full.slice(0, i));
      if (i >= full.length) {
        clearInterval(typeTimer);
        setHolding(true);
        setDots(1);
        dotsTimer = setInterval(() => {
          setDots((n) => (n >= 3 ? 1 : n + 1));
        }, 420);
        holdTimer = setTimeout(() => {
          if (dotsTimer) clearInterval(dotsTimer);
          setHolding(false);
          setDots(0);
          setMessageIndex((n) => (n + 1) % SEARCHING_MESSAGES.length);
        }, 3000);
      }
    }, 38);

    return () => {
      clearInterval(typeTimer);
      if (dotsTimer) clearInterval(dotsTimer);
      if (holdTimer) clearTimeout(holdTimer);
    };
  }, [messageIndex]);

  return (
    <View style={styles.searchingTitleRow}>
      <StatusDot color={colors.amber} />
      <Text
        style={styles.searchingTitle}
        accessibilityLabel={`${SEARCHING_MESSAGES[0]}. ${SEARCHING_MESSAGES[1]}.`}
      >
        {typed}
        {holding ? (
          <Text style={styles.searchingDots}>
            {'.'.repeat(dots)}
            {'\u00A0'.repeat(Math.max(0, 3 - dots))}
          </Text>
        ) : null}
      </Text>
    </View>
  );
}

function PhasePill({ phase }: { phase?: string }) {
  const { colors } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const hue = useRef(new Animated.Value(0)).current;
  const searching = phase === 'searching';

  useEffect(() => {
    if (!searching) {
      hue.setValue(0);
      return;
    }
    const loop = Animated.loop(
      Animated.timing(hue, {
        toValue: 1,
        duration: 3600,
        easing: Easing.linear,
        useNativeDriver: false,
      })
    );
    loop.start();
    return () => loop.stop();
  }, [searching, hue]);

  const label =
    phase === 'searching'
      ? t("Zoeken…")
      : phase === 'accepted'
        ? t("Geaccepteerd")
        : phase === 'awaiting_payment'
          ? t("Betaling")
          : phase === 'cancelled'
            ? t("Geannuleerd")
            : phase === 'completed'
              ? t("Afgerond")
              : phase || '…';
  const color =
    phase === 'accepted'
      ? colors.success
      : phase === 'cancelled'
        ? colors.danger
        : phase === 'awaiting_payment'
          ? colors.amber
          : colors.primary;

  if (!searching) {
    return (
      <View style={[styles.pill, { backgroundColor: color + '33', borderColor: color }]}>
        <Text style={[styles.pillText, { color }]}>{label}</Text>
      </View>
    );
  }

  const aiColor = hue.interpolate({
    inputRange: [0, 1 / 6, 2 / 6, 3 / 6, 4 / 6, 5 / 6, 1],
    outputRange: [...AI_SEARCH_COLORS],
  });

  return (
    <Animated.View
      style={[
        styles.pill,
        {
          borderColor: aiColor,
          backgroundColor: 'rgba(37, 99, 235, 0.12)',
        },
      ]}
    >
      <Animated.Text style={[styles.pillText, { color: aiColor }]}>{label}</Animated.Text>
    </Animated.View>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    shell: {
      flex: 1,
      paddingHorizontal: 0,
      paddingTop: APP_PAGE_TOP_INSET,
      paddingBottom: 0,
    },
    shellBody: {
      flex: 1,
      minHeight: 0,
      zIndex: 1,
    },
    bookPane: {
      flex: 1,
      minHeight: 0,
      position: 'relative',
      overflow: 'visible',
    },
    bookStickyChrome: {
      position: 'absolute',
      top: 0,
      left: 0,
      right: 0,
      zIndex: 100,
      elevation: 24,
      overflow: 'hidden',
    },
    bookStickyChromeBg: {
      ...StyleSheet.absoluteFillObject,
      zIndex: 0,
    },
    bookStickyBack: {
      width: 40,
      height: 40,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: 'transparent',
    },
    bookStickyBackLight: {
      backgroundColor: 'transparent',
    },
    bookStickyLogo: {
      flex: 1,
      alignItems: 'center',
      justifyContent: 'center',
    },
    formScroll: {
      flex: 1,
      minHeight: 0,
      zIndex: 0,
    },
    scroll: {
      justifyContent: 'flex-start',
      paddingHorizontal: 12,
      paddingTop: 4,
      paddingBottom: 24,
    },
    activeBanner: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 12,
      backgroundColor: 'rgba(34,197,94,0.14)',
      borderWidth: 1,
      borderColor: 'rgba(34,197,94,0.45)',
      borderRadius: 14,
      paddingHorizontal: 14,
      paddingVertical: 12,
      marginBottom: 12,
      overflow: 'hidden',
    },
    activeBannerNexa: {
      backgroundColor: 'rgba(8, 36, 24, 0.94)',
      borderWidth: 1.5,
      borderColor: 'rgba(74, 222, 128, 0.7)',
      borderRadius: 20,
      paddingHorizontal: 16,
      paddingVertical: 14,
      marginBottom: 14,
      shadowColor: '#22C55E',
      shadowOpacity: 0.4,
      shadowRadius: 14,
      shadowOffset: { width: 0, height: 0 },
      elevation: 6,
    },
    activeBannerNexaLight: {
      backgroundColor: 'rgba(220, 252, 231, 0.96)',
      borderColor: 'rgba(22, 163, 74, 0.5)',
      shadowOpacity: 0.16,
    },
    activeBannerAccepted: {
      backgroundColor: 'rgba(34,197,94,0.2)',
    },
    activeBannerNexaAccepted: {
      backgroundColor: 'rgba(6, 42, 28, 0.96)',
    },
    activeBannerWash: {
      position: 'absolute',
      top: 0,
      right: 0,
      bottom: 0,
      left: 0,
      backgroundColor: 'rgba(74,222,128,0.55)',
    },
    activeBannerTitle: {
      color: colors.success,
      fontSize: 15,
      fontWeight: '700',
      marginBottom: 2,
    },
    activeBannerTitleNexa: {
      color: '#4ADE80',
      fontSize: 15,
      fontWeight: '800',
    },
    activeBannerEtaNexa: {
      color: '#35B6FF',
      fontSize: 13,
      fontWeight: '700',
      marginTop: 2,
      marginBottom: 2,
    },
    activeBannerMeta: {
      color: colors.muted,
      fontSize: 13,
    },
    activeBannerMetaNexa: {
      color: 'rgba(185, 203, 228, 0.85)',
      fontSize: 12,
    },
    activeBannerCta: {
      color: colors.success,
      fontSize: 15,
      fontWeight: '700',
    },
    activeBannerCtaNexa: {
      color: '#4ADE80',
      fontSize: 15,
      fontWeight: '800',
    },
    ridesSection: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '700',
      letterSpacing: 0,
      marginTop: 8,
      marginBottom: 8,
    },
    archiveSectionRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      alignItems: 'center',
      columnGap: 12,
      rowGap: 2,
      marginTop: 8,
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
    rideCard: {
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 16,
      backgroundColor: colors.card,
      marginBottom: 12,
      overflow: 'hidden',
    },
    rideCardCancelled: {
      borderColor: 'rgba(248,113,113,0.45)',
    },
    rideCardAccepted: {
      borderColor: 'rgba(34,197,94,0.4)',
    },
    rideCardStatusBar: {
      backgroundColor: colors.danger,
      paddingHorizontal: 14,
      paddingVertical: 8,
    },
    rideCardStatusBarText: {
      color: '#fff',
      fontSize: 13,
      fontWeight: '700',
    },
    cancelReasonNotice: {
      color: colors.text,
      fontSize: 14,
      fontWeight: '600',
      lineHeight: 20,
      marginBottom: 10,
      paddingHorizontal: 10,
      paddingVertical: 8,
      borderRadius: 10,
      backgroundColor: 'rgba(248,113,113,0.12)',
      overflow: 'hidden',
    },
    refundNotice: {
      marginBottom: 10,
      paddingHorizontal: 12,
      paddingVertical: 10,
      borderRadius: 12,
      backgroundColor: 'rgba(6, 78, 59, 0.55)',
      borderWidth: 1,
      borderColor: 'rgba(110, 231, 183, 0.4)',
    },
    refundNoticeTitle: {
      color: '#6EE7B7',
      fontSize: 13,
      fontWeight: '700',
      letterSpacing: 0,
      marginBottom: 4,
    },
    refundNoticeText: {
      color: '#A7F3D0',
      fontSize: 14,
      lineHeight: 20,
    },
    rideCardBody: {
      paddingHorizontal: 14,
      paddingTop: 12,
      paddingBottom: 12,
    },
    rideCardTop: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: 8,
      marginBottom: 8,
    },
    rideStatusPills: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      alignItems: 'center',
      gap: 8,
    },
    rideCardAmount: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '700',
      lineHeight: 20,
    },
    rideChevronBtn: {
      padding: 4,
      margin: -4,
    },
    rideRouteRow: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: 8,
      marginBottom: 4,
    },
    rideRouteLine: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '600',
      marginBottom: 4,
    },
    rideCardFoot: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: 8,
    },
    metaCaption: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '600',
      letterSpacing: 0,
      marginBottom: 2,
    },
    tripRoute: {
      marginTop: 14,
      position: 'relative',
    },
    tripRail: {
      position: 'absolute',
      left: 9,
      top: 18,
      bottom: 18,
      width: 2,
      backgroundColor: colors.border,
    },
    tripStop: {
      paddingBottom: 16,
    },
    tripStopLast: {
      paddingBottom: 0,
    },
    tripHead: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
      marginBottom: 4,
    },
    tripDotOuter: {
      width: 20,
      height: 20,
      borderRadius: 999,
      alignItems: 'center',
      justifyContent: 'center',
    },
    tripDotOuterPickup: {
      backgroundColor: 'rgba(34,197,94,0.18)',
    },
    tripDotOuterDropoff: {
      backgroundColor: 'rgba(37,99,235,0.18)',
    },
    tripDot: {
      width: 10,
      height: 10,
      borderRadius: 999,
    },
    tripDotPickup: {
      backgroundColor: '#22C55E',
    },
    tripDotDropoff: {
      backgroundColor: colors.primary,
    },
    tripLabel: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '600',
    },
    tripAddress: {
      paddingLeft: 30,
    },
    tripPlace: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '800',
      lineHeight: 21,
      marginBottom: 2,
    },
    tripMain: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '800',
      lineHeight: 21,
    },
    tripSub: {
      color: colors.muted,
      fontSize: 13,
      marginTop: 2,
      lineHeight: 18,
    },
    tripStats: {
      marginTop: 14,
      borderRadius: 14,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: colors.border,
      backgroundColor: 'rgba(148,163,184,0.05)',
      paddingHorizontal: 10,
      paddingVertical: 12,
    },
    quoteMetricsBox: {
      borderRadius: 14,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: colors.border,
      backgroundColor: 'rgba(148,163,184,0.05)',
      paddingHorizontal: 10,
      paddingVertical: 12,
      marginBottom: 4,
    },
    quotePriceSlot: {
      minHeight: 40,
      marginTop: 14,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
    },
    quoteMetaCenter: {
      textAlign: 'center',
      marginTop: 6,
    },
    quoteMetaNoTaxi: {
      // Duidelijke oranje waarschuwing (licht #EA580C / donker #FB923C)
      color: colors.text === '#F8FAFC' ? '#FB923C' : '#EA580C',
      fontWeight: '600',
    },
    tripMetrics: {
      flexDirection: 'row',
      alignItems: 'stretch',
    },
    tripMetric: {
      flex: 1,
      alignItems: 'center',
      gap: 3,
    },
    tripMetricValue: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '800',
    },
    tripMetricLabel: {
      color: colors.muted,
      fontSize: 11,
      fontWeight: '500',
    },
    tripMetricDivider: {
      width: StyleSheet.hairlineWidth,
      backgroundColor: colors.border,
      marginVertical: 4,
    },
    tripBags: {
      gap: 8,
    },
    tripBagsSpaced: {
      marginTop: 12,
      paddingTop: 12,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: colors.border,
    },
    tripBagsHeading: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '600',
      letterSpacing: 0,
    },
    tripChips: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 6,
    },
    tripChip: {
      borderRadius: 999,
      paddingHorizontal: 10,
      paddingVertical: 6,
      backgroundColor: 'rgba(148,163,184,0.12)',
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: colors.border,
    },
    tripChipText: {
      color: colors.text,
      fontSize: 13,
      fontWeight: '700',
    },
    rideCardExpanded: {
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: colors.border,
      paddingHorizontal: 14,
      paddingTop: 12,
      paddingBottom: 14,
    },
    rideArchiveBtnWrap: {
      paddingHorizontal: 14,
      paddingBottom: 14,
      paddingTop: 4,
    },
    archiveLink: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: 10,
      borderWidth: 1,
      borderStyle: 'dashed',
      borderColor: colors.border,
      borderRadius: 14,
      paddingHorizontal: 14,
      paddingVertical: 12,
      marginTop: 6,
      marginBottom: 4,
      backgroundColor: 'transparent',
    },
    archiveLinkMeta: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '700',
      flexShrink: 1,
    },
    archiveLinkCta: {
      color: colors.primary,
      fontSize: 13,
      fontWeight: '700',
    },
    mapWrap: {
      height: 240,
      borderRadius: 16,
      overflow: 'hidden',
      borderWidth: 1,
      borderColor: colors.border,
      marginBottom: 12,
      backgroundColor: colors.card,
    },
    mapWrapRounded: {
      borderRadius: 22,
      borderWidth: 0,
      borderColor: 'transparent',
      height: 240,
      marginBottom: 16,
      backgroundColor: colors.card,
      overflow: 'hidden',
    },
    liveTitleRow: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: 12,
      marginBottom: 14,
      paddingHorizontal: 2,
    },
    liveTitleEyebrow: {
      fontSize: 13,
      fontWeight: '800',
      marginBottom: 4,
    },
    liveTitleMain: {
      fontSize: 26,
      fontWeight: '800',
      letterSpacing: -0.3,
    },
    liveTitleUnderline: {
      marginTop: 8,
      width: 78,
      height: 4,
      borderRadius: 3,
      backgroundColor: nexaBookingTheme.color.orangeHot,
    },
    liveTrackingPillHd: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 6,
      marginTop: 2,
      paddingHorizontal: 10,
      paddingVertical: 4,
      borderRadius: 12,
      borderWidth: 1.5,
      borderColor: '#5AB19D',
      backgroundColor: 'transparent',
    },
    liveTrackingPillHdText: {
      color: '#D0EDE8',
      fontSize: 13,
      fontWeight: '600',
      letterSpacing: 0.1,
    },
    mapBottomRightControls: {
      position: 'absolute',
      right: 10,
      bottom: 10,
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
      zIndex: 5,
    },
    profileGlowCard: {
      marginBottom: 14,
    },
    profileGlowInner: {
      padding: 14,
    },
    fleetBadgeNexa: {
      left: 12,
      right: undefined,
      bottom: 12,
      backgroundColor: 'transparent',
      borderWidth: 0,
      paddingHorizontal: 0,
      paddingVertical: 0,
    },
    routePill: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 6,
      backgroundColor: 'rgba(6,19,41,0.88)',
      borderRadius: 999,
      paddingHorizontal: 12,
      paddingVertical: 8,
      borderWidth: 1,
      borderColor: 'rgba(110,190,255,0.45)',
    },
    followMapBlock: {
      marginTop: 2,
      marginBottom: 8,
      gap: 4,
    },
    followMapLinkPress: {
      alignSelf: 'flex-start',
      flexDirection: 'row',
      alignItems: 'center',
      gap: 6,
      paddingVertical: 2,
    },
    followMapLink: {
      color: colors.primary,
      fontSize: 15,
      fontWeight: '700',
    },
    map: {
      width: '100%',
      height: '100%',
    },
    zoomControls: {
      position: 'absolute',
      right: 10,
      top: 10,
      gap: 6,
    },
    zoomBtn: {
      width: 36,
      height: 36,
      borderRadius: 10,
      backgroundColor: colors.card,
      borderWidth: 2,
      borderColor: colors.border,
      alignItems: 'center',
      justifyContent: 'center',
    },
    zoomBtnText: {
      color: colors.text,
      fontSize: 22,
      fontWeight: '600',
      lineHeight: 24,
    },
    locateBtn: {
      position: 'absolute',
      right: 10,
      bottom: 10,
      width: 36,
      height: 36,
      borderRadius: 10,
      backgroundColor: colors.card,
      borderWidth: 1,
      borderColor: colors.border,
      alignItems: 'center',
      justifyContent: 'center',
    },
    fleetBadge: {
      position: 'absolute',
      left: 10,
      bottom: 10,
      backgroundColor: colors.card,
      borderRadius: 999,
      paddingHorizontal: 10,
      paddingVertical: 5,
      borderWidth: 1,
      borderColor: colors.border,
      opacity: 0.95,
    },
    fleetBadgeText: {
      color: colors.text,
      fontSize: 12,
      fontWeight: '600',
    },
    demoBadge: {
      position: 'absolute',
      left: 10,
      top: 10,
      backgroundColor: colors.amber,
      borderRadius: 999,
      paddingHorizontal: 10,
      paddingVertical: 4,
    },
    demoBadgeText: {
      color: '#ffffff',
      fontSize: 12,
      fontWeight: '800',
      letterSpacing: 1,
    },
    demoModeBanner: {
      marginHorizontal: 16,
      marginBottom: 8,
      paddingHorizontal: 12,
      paddingVertical: 10,
      borderRadius: 12,
      backgroundColor: colors.amber,
    },
    demoModeBannerTitle: {
      color: '#0B1220',
      fontSize: 13,
      fontWeight: '800',
      letterSpacing: 0.3,
    },
    demoModeBannerText: {
      color: '#0B1220',
      fontSize: 12,
      fontWeight: '500',
      marginTop: 2,
      opacity: 0.9,
    },
    taxiMarker: {
      width: 26,
      height: 39,
    },
    taxiMarkerBusy: {
      opacity: 0.45,
    },
    pickupDotOuter: {
      width: 22,
      height: 22,
      borderRadius: 11,
      backgroundColor: 'rgba(249,115,22,0.28)',
      alignItems: 'center',
      justifyContent: 'center',
    },
    pickupDotInner: {
      width: 12,
      height: 12,
      borderRadius: 6,
      backgroundColor: '#F97316',
      borderWidth: 2,
      borderColor: '#FFFFFF',
    },
    routeSummary: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '600',
      marginBottom: 6,
    },
    warnText: {
      color: colors.amber,
      fontSize: 14,
      lineHeight: 20,
      marginTop: 10,
    },
    fieldErrorText: {
      color: colors.danger,
      fontSize: 13,
      lineHeight: 18,
      marginTop: 10,
    },
    section: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '600',
      letterSpacing: 0,
      marginBottom: 8,
    },
    sectionCenter: {
      textAlign: 'center',
    },
    qtyWrapCenter: {
      justifyContent: 'center',
    },
    themeHint: {
      color: colors.muted,
      fontSize: 13,
      lineHeight: 19,
      marginBottom: 10,
    },
    themeRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 12,
      paddingVertical: 12,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: colors.border,
    },
    themeRowActive: {
      // selected state uses radio fill
    },
    themeRadio: {
      width: 20,
      height: 20,
      borderRadius: 999,
      borderWidth: 2,
      borderColor: colors.border,
    },
    themeRadioOn: {
      borderColor: colors.primary,
      backgroundColor: colors.primary,
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
    rowLabel: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '600',
      marginBottom: 4,
    },
    rowValue: {
      color: colors.text,
      fontSize: 15,
      lineHeight: 21,
      fontWeight: '700',
    },
    meta: {
      color: colors.muted,
      fontSize: 14,
      lineHeight: 20,
      marginTop: 6,
    },
    driverCard: {
      marginTop: 12,
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 12,
      padding: 12,
      gap: 10,
    },
    driverLogo: {
      width: 120,
      height: 40,
      alignSelf: 'flex-start',
      backgroundColor: 'transparent',
    },
    driverRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 12,
    },
    driverAvatar: {
      width: 56,
      height: 56,
      borderRadius: 28,
      backgroundColor: colors.inputBg,
    },
    driverAvatarFallback: {
      alignItems: 'center',
      justifyContent: 'center',
    },
    acceptedDriverCard: {
      marginBottom: 20,
      padding: 16,
      overflow: 'hidden',
    },
    acceptedDriverLogo: {
      width: 120,
      height: 40,
      alignSelf: 'center',
      backgroundColor: 'transparent',
    },
    acceptedAlign: {
      alignSelf: 'center',
      alignItems: 'flex-start',
      maxWidth: '100%',
      gap: 10,
    },
    acceptedAlignRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 12,
      maxWidth: '100%',
    },
    acceptedCarColumn: {
      width: ACCEPTED_CAR_WIDTH,
      alignItems: 'center',
    },
    acceptedDriverMeta: {
      flexShrink: 1,
      minWidth: 0,
    },
    acceptedDriverAvatar: {
      width: 70,
      height: 70,
      borderRadius: 35,
      backgroundColor: colors.inputBg,
    },
    acceptedDriverRating: {
      color: colors.muted,
      fontSize: 14,
      lineHeight: 18,
      marginTop: 2,
    },
    acceptedVehicleThumb: {
      width: ACCEPTED_CAR_WIDTH,
      height: ACCEPTED_CAR_HEIGHT,
      borderRadius: 6,
      backgroundColor: 'transparent',
    },
    acceptedVehiclePlaceholderWrap: {
      width: ACCEPTED_CAR_WIDTH,
      alignItems: 'center',
      gap: 4,
    },
    acceptedVehiclePlaceholderFrame: {
      width: ACCEPTED_CAR_WIDTH,
      height: ACCEPTED_CAR_HEIGHT,
      borderRadius: 6,
      borderWidth: 1.5,
      // Dashed + radius is unreliable on Android (drawPath crash / yellowbox).
      borderStyle: Platform.OS === 'android' ? 'solid' : 'dashed',
      borderColor: colors.border,
      backgroundColor: colors.inputBg,
      alignItems: 'center',
      justifyContent: 'center',
      overflow: 'hidden',
    },
    acceptedVehiclePlaceholderThumb: {
      width: ACCEPTED_CAR_WIDTH - 8,
      height: ACCEPTED_CAR_HEIGHT - 10,
      opacity: 0.85,
    },
    acceptedVehiclePlaceholderLabel: {
      color: colors.muted,
      fontSize: 10,
      fontWeight: '600',
      letterSpacing: 0.2,
      textAlign: 'center',
    },
    driverName: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '700',
    },
    driverStar: {
      color: '#F5B301',
    },
    vehicleThumb: {
      width: 72,
      height: 52,
      borderRadius: 8,
      backgroundColor: 'transparent',
    },
    photoPreviewPanel: {
      paddingHorizontal: 10,
      paddingTop: 6,
      paddingBottom: 10,
      maxWidth: 440,
      backgroundColor: colors.card,
      overflow: 'hidden',
    },
    photoPreviewClose: {
      alignSelf: 'flex-end',
      width: 36,
      height: 36,
      alignItems: 'center',
      justifyContent: 'center',
      marginRight: -4,
      marginBottom: 2,
      zIndex: 2,
    },
    photoPreviewImage: {
      width: '100%',
      height: 380,
      backgroundColor: 'transparent',
      borderRadius: 14,
    },
    statusTimeline: {
      marginTop: 14,
      gap: 8,
    },
    statusRow: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: 10,
    },
    statusDot: {
      width: 8,
      height: 8,
      borderRadius: 4,
      marginTop: 6,
      backgroundColor: colors.primary,
    },
    statusLabel: {
      color: colors.text,
      fontSize: 14,
      fontWeight: '600',
    },
    statusAt: {
      color: colors.muted,
      fontSize: 12,
      marginTop: 2,
    },
    ratingComment: {
      color: colors.muted,
      fontSize: 14,
      lineHeight: 20,
    },
    starsRow: {
      flexDirection: 'row',
      gap: 6,
    },
    rateInput: {
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 12,
      backgroundColor: colors.inputBg,
      color: colors.text,
      paddingHorizontal: 12,
      paddingVertical: 10,
      minHeight: 72,
      textAlignVertical: 'top',
    },
    profileHint: {
      color: colors.muted,
      fontSize: 12,
      lineHeight: 17,
      marginTop: 10,
    },
    contactReadonlyField: {
      marginBottom: 14,
    },
    contactEditBtn: {
      alignSelf: 'flex-start',
      marginTop: 2,
      paddingVertical: 7,
      paddingHorizontal: 14,
      borderRadius: 10,
      borderWidth: 1,
      borderColor: colors.border,
      backgroundColor: 'transparent',
    },
    contactEditBtnPressed: {
      backgroundColor: colors.inputBg,
    },
    contactEditBtnText: {
      color: colors.text,
      fontSize: 14,
      fontWeight: '600',
    },
    price: {
      color: colors.text,
      fontSize: 28,
      fontWeight: '700',
      lineHeight: 40,
      textAlign: 'center',
    },
    nameRow: {
      flexDirection: 'row',
      alignItems: 'flex-start',
    },
    qtyBtnDisabled: {
      opacity: 0.35,
    },
    qtyBtnTextDisabled: {
      color: colors.muted,
    },
    baggageRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 12,
      paddingVertical: 10,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: colors.border,
    },
    baggageLabel: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '600',
    },
    baggageHint: {
      color: colors.muted,
      fontSize: 12,
      marginTop: 2,
    },
    qtyWrap: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
    },
    qtyBtn: {
      width: 34,
      height: 34,
      borderRadius: 10,
      borderWidth: 1,
      borderColor: colors.border,
      alignItems: 'center',
      justifyContent: 'center',
    },
    qtyBtnText: {
      color: colors.text,
      fontSize: 18,
      fontWeight: '600',
      lineHeight: 20,
    },
    qtyValue: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '700',
      minWidth: 18,
      textAlign: 'center',
    },
    arrivalHeadline: {
      color: colors.success,
      fontSize: 17,
      fontWeight: '700',
      lineHeight: 22,
      marginTop: 8,
    },
    arrivalType: {
      color: colors.amber,
      fontSize: 15,
      fontWeight: '600',
      lineHeight: 21,
      marginTop: 2,
      marginBottom: 6,
    },
    tripEta: {
      color: colors.primary,
      fontSize: 15,
      fontWeight: '700',
      marginTop: 6,
    },
    liveTitle: {
      color: colors.success,
      fontSize: 18,
      fontWeight: '700',
      marginTop: 10,
      marginBottom: 8,
    },
    searchingTitle: {
      color: colors.amber,
      fontSize: 15,
      fontWeight: '700',
      flexShrink: 1,
    },
    searchingDots: {
      color: colors.amber,
      fontSize: 15,
      fontWeight: '700',
      letterSpacing: 1,
    },
    confirmBanner: {
      borderRadius: 18,
      paddingHorizontal: 18,
    },
    confirmTitle: {
      color: colors.text,
      fontSize: 18,
      fontWeight: '700',
      marginBottom: 8,
    },
    confirmText: {
      color: colors.muted,
      fontSize: 14,
      lineHeight: 21,
      marginBottom: 16,
    },
    /** Zelfde layout-ritme als NexaPickupCodeModal (titel/body gecentreerd, oranje CTA). */
    cancelAckPanel: {
      borderRadius: 22,
      borderWidth: 1.5,
      paddingHorizontal: 16,
      paddingTop: 16,
      paddingBottom: 14,
      maxWidth: 400,
      width: '92%',
      alignSelf: 'center',
    },
    cancelAckTitle: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '800',
      textAlign: 'center',
      marginBottom: 8,
    },
    cancelAckMeta: {
      color: colors.muted,
      fontSize: 13,
      lineHeight: 18,
      textAlign: 'center',
      marginTop: 10,
      marginBottom: 0,
    },
    cancelAckBody: {
      color: colors.muted,
      fontSize: 13,
      lineHeight: 18,
      textAlign: 'center',
      marginTop: 10,
      marginBottom: 12,
    },
    cancelAckCta: {
      minHeight: 44,
      height: 44,
      borderRadius: 12,
      backgroundColor: nexaBookingTheme.color.orangeHot,
      alignItems: 'center',
      justifyContent: 'center',
      alignSelf: 'stretch',
    },
    cancelAckCtaText: {
      color: '#FFFFFF',
      fontSize: 15,
      fontWeight: '800',
    },
    waitRouteBlock: {
      marginTop: 10,
      marginBottom: 4,
      gap: 0,
      width: '100%',
      alignItems: 'center',
    },
    waitRouteAddress: {
      color: colors.text,
      fontSize: 14,
      lineHeight: 20,
      fontWeight: '700',
      textAlign: 'center',
    },
    waitRoutePlace: {
      color: colors.amber,
      fontSize: 14,
      lineHeight: 18,
      marginBottom: 1,
      fontWeight: '700',
      textAlign: 'center',
    },
    waitRouteArrow: {
      alignItems: 'center',
      justifyContent: 'center',
      paddingVertical: 6,
    },
    pickupPinBanner: {
      position: 'absolute',
      left: 16,
      right: 16,
      bottom: 88,
      zIndex: 40,
      borderRadius: 16,
      paddingTop: 16,
      paddingBottom: 14,
      paddingHorizontal: 36,
      backgroundColor: '#DC2626',
      alignItems: 'center',
      elevation: 4,
    },
    pickupPinClose: {
      position: 'absolute',
      top: 8,
      right: 8,
      width: 32,
      height: 32,
      alignItems: 'center',
      justifyContent: 'center',
    },
    pickupPinBannerLabel: {
      color: '#FFFFFF',
      fontSize: 12,
      fontWeight: '700',
      letterSpacing: 0.3,
      marginBottom: 4,
      textAlign: 'center',
    },
    pickupPinDigits: {
      color: '#FFFFFF',
      fontSize: 36,
      fontWeight: '800',
      letterSpacing: 8,
      fontVariant: ['tabular-nums'],
    },
    pickupPinBannerHint: {
      color: 'rgba(255,255,255,0.92)',
      fontSize: 13,
      lineHeight: 18,
      textAlign: 'center',
      marginTop: 6,
    },
    pinShowBtn: {
      marginTop: 12,
      minHeight: 48,
      borderRadius: nexaBookingTheme.radius.md,
      backgroundColor: '#DC2626',
      paddingVertical: 12,
      paddingHorizontal: 16,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      gap: 8,
      elevation: 2,
    },
    pinShowBtnText: {
      color: '#FFFFFF',
      fontSize: 15,
      fontWeight: '800',
    },
    liveMapTopBar: {
      position: 'absolute',
      top: 12,
      right: 12,
      left: 12,
      flexDirection: 'row',
      justifyContent: 'flex-end',
      zIndex: 4,
    },
    liveTrackingPill: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 6,
      paddingHorizontal: 10,
      paddingVertical: 4,
      borderRadius: 12,
      borderWidth: 1.5,
      borderColor: '#5AB19D',
      backgroundColor: 'transparent',
    },
    liveTrackingPillText: {
      color: '#D0EDE8',
      fontSize: 12,
      fontWeight: '600',
    },
    liveEtaChip: {
      position: 'absolute',
      left: 12,
      bottom: 12,
      zIndex: 4,
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
      paddingHorizontal: 14,
      paddingVertical: 12,
      borderRadius: 18,
      borderWidth: 1.5,
      borderColor: 'rgba(61, 180, 255, 0.85)',
      backgroundColor: 'rgba(6, 19, 41, 0.92)',
      shadowColor: '#3DB4FF',
      shadowOpacity: 0.5,
      shadowRadius: 10,
      shadowOffset: { width: 0, height: 0 },
    },
    liveEtaChipLabel: {
      color: '#B7C7DF',
      fontSize: 11,
      fontWeight: '600',
    },
    liveEtaChipValue: {
      color: '#FFFFFF',
      fontSize: 16,
      fontWeight: '800',
      lineHeight: 20,
    },
    confirmActions: {
      flexDirection: 'row',
      alignItems: 'stretch',
      gap: 10,
    },
    confirmActionSlot: {
      flex: 1,
      minWidth: 0,
    },
    confirmSecondaryBtn: {
      borderRadius: 14,
      borderWidth: 1,
      borderColor: colors.border,
      paddingVertical: 14,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: 'transparent',
      marginTop: 8,
      width: '100%',
    },
    confirmSecondaryText: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '700',
    },
    complaintCategoryWrap: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 8,
      marginBottom: 12,
      width: '100%',
    },
    complaintChip: {
      borderRadius: 12,
      borderWidth: 1,
      borderColor: colors.border,
      paddingVertical: 8,
      paddingHorizontal: 12,
      backgroundColor: 'transparent',
      maxWidth: '100%',
    },
    complaintChipSelected: {
      borderColor: colors.primary,
      backgroundColor: colors.primary + '22',
    },
    complaintChipText: {
      color: colors.text,
      fontSize: 13,
      fontWeight: '600',
    },
    complaintChipTextSelected: {
      color: colors.primary,
    },
    complaintLinkWrap: {
      marginTop: 12,
      alignSelf: 'stretch',
      alignItems: 'center',
      paddingVertical: 2,
    },
    complaintLinkText: {
      color: colors.primary,
      fontSize: 14,
      fontWeight: '600',
      textAlign: 'center',
      textDecorationLine: 'underline',
    },
    confirmDangerBtn: {
      borderRadius: 14,
      borderWidth: 1,
      borderColor: colors.danger,
      paddingVertical: 14,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: 'transparent',
      marginTop: 8,
      width: '100%',
    },
    confirmDangerText: {
      color: colors.danger,
      fontSize: 16,
      fontWeight: '700',
    },
    confirmPrimaryBtn: {
      borderRadius: 14,
      paddingVertical: 14,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: colors.primary,
      marginTop: 8,
      width: '100%',
    },
    confirmPrimaryText: {
      color: '#fff',
      fontSize: 16,
      fontWeight: '700',
    },
    pill: {
      alignSelf: 'flex-start',
      flexDirection: 'row',
      alignItems: 'center',
      gap: 6,
      borderWidth: 1,
      borderRadius: 999,
      paddingHorizontal: 10,
      paddingVertical: 4,
    },
    pillText: {
      fontSize: 12,
      fontWeight: '700',
    },
    searchingTitleRow: {
      flexDirection: 'row',
      alignItems: 'center',
      marginTop: 10,
      marginBottom: 8,
    },
    searchingDesc: {
      color: colors.muted,
      fontSize: 14,
      lineHeight: 20,
      marginBottom: 0,
    },
    searchingDivider: {
      height: StyleSheet.hairlineWidth,
      backgroundColor: colors.border,
      marginTop: 14,
      marginBottom: 12,
    },
    searchingMetaRow: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: 12,
    },
    cancelRideBtn: {
      marginTop: 14,
      backgroundColor: colors.danger,
      borderRadius: 12,
      paddingVertical: 14,
      alignItems: 'center',
    },
    cancelRideBtnText: {
      color: '#fff',
      fontSize: 15,
      fontWeight: '700',
    },
    payToggle: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
      marginTop: 14,
    },
    checkbox: {
      width: 22,
      height: 22,
      borderRadius: 6,
      borderWidth: 1.5,
      borderColor: colors.border,
      backgroundColor: 'transparent',
    },
    checkboxOn: {
      backgroundColor: colors.primary,
      borderColor: colors.primary,
    },
    payToggleText: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '600',
    },
    suggestRow: {
      paddingVertical: 10,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: colors.border,
    },
    suggestRowFirst: {
      marginTop: 4,
    },
    suggestText: {
      color: colors.text,
      fontSize: 14,
      lineHeight: 19,
    },
    locIconBtn: {
      width: 36,
      height: 36,
      borderRadius: 10,
      alignItems: 'center',
      justifyContent: 'center',
    },
  });
}
