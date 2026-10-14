import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  Alert,
  ActivityIndicator,
  Animated,
  Easing,
  Image,
  Keyboard,
  Linking,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import type { TextInput as TextInputType } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import MapView, { Marker, Polyline, PROVIDER_GOOGLE } from 'react-native-maps';
import {
  bookGuest,
  cancelLive,
  confirmPickupLive,
  fetchLive,
  fetchNearbyTaxis,
  fetchQuote,
  LiveRide,
  NearbyTaxi,
  RideStatusUpdate,
  payLive,
  rateLive,
  QuoteOffer,
  QuoteResponse,
  waitLive,
} from '../api/customer';
import { notifyNewRideStatusUpdates } from '../customer/rideStatusNotifications';
import { ApiError } from '../api/client';
import {
  AddressSuggestion,
  DARK_MAP_STYLE,
  DEFAULT_MAP_CENTER,
  defaultPickupDate,
  estimateFareEuro,
  estimateRouteMetrics,
  fetchDrivingRoute,
  formatEuroNl,
  formatPickupAtPayload,
  GeoPoint,
  LatLng,
  peekCurrentCoords,
  resolveCurrentPickup,
  searchAddresses,
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
import {
  Card,
  ErrorText,
  Field,
  GhostButton,
  PrimaryButton,
  Screen,
} from '../ui/components';
import { AppModal } from '../ui/AppModal';
import { CustomerTabBar, CustomerTabKey } from '../ui/CustomerTabBar';
import { AppPageHeader, APP_PAGE_TOP_INSET } from '../ui/AppPageHeader';
import { PickupAtField } from '../ui/PickupAtPicker';
import { API_BASE_URL, ColorPalette } from '../config';
import { ThemePreference, useTheme } from '../theme/ThemeContext';

const TRACK_KEY = 'nexa_taxi_customer_track';
const PROFILE_KEY = 'nexa_taxi_customer_profile_guest';
const TAB_KEY = 'nexa_taxi_customer_tab';
const taxiCarYellow = require('../../assets/taxi-car-yellow.png');
const dropPinGreen = require('../../assets/marker-drop-green.png');

const THEME_OPTIONS: { key: ThemePreference; label: string; hint: string }[] = [
  { key: 'system', label: 'Systeem', hint: 'Volgt de telefooninstelling' },
  { key: 'light', label: 'Licht', hint: 'Altijd lichte weergave' },
  { key: 'dark', label: 'Donker', hint: 'Altijd donkere weergave' },
];

type BaggageKey = 'large' | 'small' | 'hand' | 'wheelchair' | 'pets';

const BAGGAGE_ROWS: {
  key: BaggageKey;
  label: string;
  hint: string;
  special?: boolean;
  max: number;
}[] = [
  { key: 'large', label: 'Grote ruimbagage', hint: '85×55×35 cm', max: 6 },
  { key: 'small', label: 'Kleine ruimbagage', hint: '55×45×25 cm', max: 6 },
  { key: 'hand', label: 'Handbagage', hint: 'Handtas, rugzak, etc.', max: 6 },
  { key: 'wheelchair', label: 'Opvouwbare rolstoel', hint: 'Speciale bagage', special: true, max: 2 },
  { key: 'pets', label: 'Huisdieren', hint: 'Optioneel', special: true, max: 2 },
];

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
      parts.push(`${labels[key] || key} × ${count}`);
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
  const { colors, colorScheme, preference, setPreference } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const [tab, setTab] = useState<CustomerTabKey>('book');
  const [trackToken, setTrackToken] = useState<string | null>(null);
  const [live, setLive] = useState<LiveRide | null>(null);
  const [guestRides, setGuestRides] = useState<GuestRide[]>([]);
  const [archivedKeys, setArchivedKeys] = useState<string[]>([]);
  const [showArchivedRides, setShowArchivedRides] = useState(false);
  const [expandedRideKey, setExpandedRideKey] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [locationError, setLocationError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [pickupAnswerToken, setPickupAnswerToken] = useState<string | null>(null);
  const [profileSaved, setProfileSaved] = useState(false);
  const [cancelConfirmOpen, setCancelConfirmOpen] = useState(false);
  const [photoPreviewUri, setPhotoPreviewUri] = useState<string | null>(null);
  const [hiddenPinKeys, setHiddenPinKeys] = useState<string[]>([]);
  const [rateStars, setRateStars] = useState(0);
  const [rateComment, setRateComment] = useState('');
  const [rateModalToken, setRateModalToken] = useState<string | null>(null);
  const [rateError, setRateError] = useState<string | null>(null);
  const prevLivePhase = useRef<string | null>(null);
  const completedAnnounced = useRef<Set<number>>(new Set());

  const hasActiveRide = !!(live && trackToken && isActiveRidePhase(live.phase));

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
    'pickup' | 'dropoff' | 'firstName' | 'lastName' | 'phone' | 'email' | 'offer' | 'dispatch',
    string
  >>>({});

  const [quote, setQuote] = useState<QuoteResponse | null>(null);
  const [quoteBusy, setQuoteBusy] = useState(false);
  const [nearbyTaxis, setNearbyTaxis] = useState<NearbyTaxi[]>([]);
  const quoteTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const pickupSuggestTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const dropoffSuggestTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const pickupSuggestSeq = useRef(0);
  const dropoffSuggestSeq = useRef(0);
  const mapRef = useRef<MapView | null>(null);
  const bookScrollRef = useRef<ScrollView | null>(null);
  const dropoffInputRef = useRef<TextInputType | null>(null);
  const profileSavedTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const mapSectionY = useRef(0);
  const [bookMountKey, setBookMountKey] = useState(0);

  const offer: QuoteOffer | null = quote?.offers?.[0] || null;
  const activeTaxiLoc = useMemo(() => {
    if (!live || !trackToken || !isActiveRidePhase(live.phase)) return null;
    const loc = live.vehicle_location;
    const lat = loc?.lat != null ? Number(loc.lat) : NaN;
    const lng = loc?.lng != null ? Number(loc.lng) : NaN;
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
    if (lat === 0 && lng === 0) return null;
    return { lat, lng };
  }, [live, trackToken, live?.phase, live?.vehicle_location?.lat, live?.vehicle_location?.lng]);
  const freeTaxis = nearbyTaxis.filter(
    (taxi) => !taxi.busy && !isSameTaxi(taxi, activeTaxiLoc)
  );
  const freeNearby = freeTaxis.length;
  const canDispatch = freeNearby > 0;
  const unavailableMessage =
    quote?.marketplace?.unavailable_message ||
    'Er is momenteel geen taxi beschikbaar in de buurt van deze ophaallocatie. Je krijgt een melding zodra er weer een taxi beschikbaar is.';

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
            ? 'Betaling geannuleerd. Je kunt opnieuw betalen via je openstaande rit.'
            : reden === 'verlopen'
              ? 'Betaling verlopen. Start opnieuw via je openstaande rit.'
              : 'Betaling mislukt. Probeer het opnieuw via je openstaande rit.'
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
        setLocationError(e instanceof Error ? e.message : 'Locatie ophalen mislukt.');
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
          if (p.first_name) setFirstName(String(p.first_name));
          if (p.last_name) setLastName(String(p.last_name));
          if (p.phone) setPhone(String(p.phone));
          if (p.email) setEmail(String(p.email));
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

  function fitMapToRoute() {
    const map = mapRef.current;
    if (!map) return;
    const padding = { top: 48, right: 48, bottom: 48, left: 48 };
    if (routeCoords.length > 1) {
      map.fitToCoordinates(routeCoords, { edgePadding: padding, animated: true });
      return;
    }
    if (pickup && dropoff) {
      map.fitToCoordinates(
        [
          { latitude: pickup.lat, longitude: pickup.lng },
          { latitude: dropoff.lat, longitude: dropoff.lng },
        ],
        { edgePadding: padding, animated: true }
      );
    }
  }

  async function saveProfile() {
    setProfileSaved(false);
    if (profileSavedTimer.current) clearTimeout(profileSavedTimer.current);
    try {
      await AsyncStorage.setItem(
        PROFILE_KEY,
        JSON.stringify({
          first_name: firstName.trim(),
          last_name: lastName.trim(),
          phone: phone.trim(),
          email: email.trim(),
        })
      );
      setProfileSaved(true);
      profileSavedTimer.current = setTimeout(() => setProfileSaved(false), 3000);
    } catch {
      setError('Profiel opslaan mislukt.');
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
    setQuoteBusy(true);
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
      setQuote(data);
    } catch {
      /* houd vorige quote; lokale prijsindicatie blijft zichtbaar */
    } finally {
      setQuoteBusy(false);
    }
  }, [pickup, dropoff, passengers, routeMetrics, baggagePayload, pickupAt]);

  const refreshFleet = useCallback(async () => {
    const origin = pickup
      ? { lat: pickup.lat, lng: pickup.lng }
      : fleetOrigin;
    try {
      const vehicles = await fetchNearbyTaxis({
        lat: origin.lat,
        lng: origin.lng,
        radiusKm: 50,
      });
      setNearbyTaxis(vehicles.filter((taxi) => !taxi.busy && !isSameTaxi(taxi, activeTaxiLoc)));
    } catch {
      /* behoud vorige markers */
    }
  }, [pickup, fleetOrigin, activeTaxiLoc]);

  useEffect(() => {
    refreshFleet();
    const t = setInterval(refreshFleet, 4000);
    return () => clearInterval(t);
  }, [refreshFleet]);

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

  useEffect(() => {
    if (!trackToken) return;
    let cancelled = false;
    let timer: ReturnType<typeof setTimeout> | null = null;

    prevLivePhase.current = null;

    const tick = async () => {
      const tokens = new Set<string>();
      if (trackToken) tokens.add(trackToken);
      guestRidesRef.current.forEach((ride) => {
        if (ride.token && rideNeedsLivePoll(ride)) tokens.add(ride.token);
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
        }
        if (cancelled) return;
        setGuestRides(list);
        const tracked = list.find((ride) => ride.token === trackToken);
        if (tracked && !rideNeedsLivePoll(tracked)) {
          await persistTrack(null);
          if (!cancelled) setTrackToken(null);
        }
        const stillActive = list.some((ride) => ride.token && rideNeedsLivePoll(ride));
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
  }, [trackToken, persistTrack]);

  const idleActiveKey = trackToken
    ? ''
    : guestRides
        .filter((ride) => ride.token && rideNeedsLivePoll(ride))
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
      for (const token of tokens) {
        if (cancelled) return;
        try {
          const data = await fetchLive(token);
          if (cancelled || !data.ride) continue;
          list = await updateGuestRide(token, guestPatchFromLive(data.ride));
        } catch {
          /* volgende rit */
        }
      }
      if (!cancelled) setGuestRides(list);
      if (!cancelled) timer = setTimeout(tick, 2500);
    };
    timer = setTimeout(tick, 400);
    return () => {
      cancelled = true;
      if (timer) clearTimeout(timer);
    };
  }, [trackToken, idleActiveKey]);

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
    setBookMountKey((k) => k + 1);
    setTab('book');
    AsyncStorage.setItem(TAB_KEY, 'book').catch(() => undefined);
    setError(null);
    setProfileSaved(false);
    setShowArchivedRides(false);
  }

  function scrollToMap() {
    Keyboard.dismiss();
    // Iets hoger dan de map-top, zodat de hele kaart onder de header zichtbaar blijft.
    const go = () => {
      const y = Math.max(0, mapSectionY.current - 72);
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
    const point = { lat: item.lat, lng: item.lng, address: label };
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

  function collectBookErrors(): typeof fieldErrors {
    const next: typeof fieldErrors = {};
    if (!pickup) next.pickup = 'Kies een ophaaladres uit de suggesties.';
    if (!dropoff) next.dropoff = 'Kies een bestemming uit de suggesties.';
    if (firstName.trim().length < 2) next.firstName = 'Vul je voornaam in.';
    if (lastName.trim().length < 2) next.lastName = 'Vul je achternaam in.';
    if (phone.trim().length < 8) next.phone = 'Vul een geldig telefoonnummer in.';
    if (!email.trim() || !email.includes('@')) {
      next.email = 'Vul een e-mailadres in voor de betaling.';
    }
    if (pickup && dropoff && !offer && !routeMetrics) {
      next.offer = 'Wacht tot de prijs berekend is.';
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

    if (!canDispatch) {
      const msg =
        'Er zijn geen taxi’s in de buurt beschikbaar. We sturen nu geen rit uit. Je krijgt een melding zodra er weer een taxi beschikbaar is.';
      setFieldErrors({ dispatch: msg });
      setError(msg);
      Alert.alert('Geen taxi’s beschikbaar', msg);
      return;
    }

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
        const msg = 'De prijs kon niet worden opgehaald. Probeer het zo opnieuw.';
        setError(msg);
        Alert.alert('Prijs niet beschikbaar', msg);
        return;
      }
    }

    if (
      paymentQuote?.payment?.deferred_until_taxi ||
      paymentQuote?.marketplace?.taxi_available === false
    ) {
      const msg =
        paymentQuote?.marketplace?.unavailable_message ||
        'Er is momenteel geen taxi beschikbaar in de buurt van deze ophaallocatie.';
      setError(msg);
      Alert.alert('Geen taxi beschikbaar', msg);
      return;
    }

    if (paymentQuote?.payment?.mollie_configured === false) {
      const msg =
        'Online betalen staat uit omdat de Mollie-koppeling van Nexa Suite niet is ingesteld.';
      setError(msg);
      Alert.alert('Betaling niet beschikbaar', msg);
      return;
    }

    if (!paymentQuote?.payment?.booking) {
      const msg = 'Online betalen is momenteel niet beschikbaar. Probeer het later opnieuw.';
      setError(msg);
      Alert.alert('Betaling niet beschikbaar', msg);
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
        phase: data.live?.phase || 'awaiting_payment',
        status_label: data.live?.status_label || 'Betaling',
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

      const checkout = data.checkout_url || data.live?.checkout_url;
      if (checkout) {
        await Linking.openURL(checkout);
      } else if (data.payment_required) {
        setError('Betalingslink kon niet worden geopend. Tik op Volgen en probeer opnieuw te betalen.');
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Boeken mislukt.');
      Alert.alert('Boeken mislukt', e instanceof ApiError ? e.message : 'Boeken mislukt.');
    } finally {
      setBusy(false);
    }
  }

  function requestCancel() {
    if (!trackToken || busy) return;
    setCancelConfirmOpen(true);
  }

  async function confirmCancel() {
    if (!trackToken) return;
    setCancelConfirmOpen(false);
    setBusy(true);
    setError(null);
    try {
      const data = await cancelLive(trackToken);
      if (data.ride) {
        setLive(data.ride);
        const next = await updateGuestRide(trackToken, guestPatchFromLive(data.ride));
        setGuestRides(next);
        setExpandedRideKey(rideArchiveKey({ id: data.ride.id, token: trackToken }));
      }
      await persistTrack(null);
      setTab('rides');
      AsyncStorage.setItem(TAB_KEY, 'rides').catch(() => undefined);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Annuleren mislukt.');
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
      setError(e instanceof ApiError ? e.message : 'Bevestigen mislukt.');
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
      if (data.ride) setLive(data.ride);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Doorgaan mislukt.');
    } finally {
      setBusy(false);
    }
  }

  async function onPay() {
    if (!trackToken) return;
    setBusy(true);
    setError(null);
    try {
      const url = live?.checkout_url;
      if (url) {
        await Linking.openURL(url);
        return;
      }
      const data = await payLive(trackToken, CUSTOMER_PAYMENT_RETURN_URL);
      if (data.ride) setLive(data.ride);
      if (data.checkout_url) {
        await Linking.openURL(data.checkout_url);
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Betaling starten mislukt.');
    } finally {
      setBusy(false);
    }
  }

  function openRateModal(token: string) {
    setRateStars(0);
    setRateComment('');
    setRateError(null);
    setRateModalToken(token);
  }

  async function onDownloadInvoice(token: string) {
    const url = `${API_BASE_URL}/api/taxi/v1/customer/live/invoice?token=${encodeURIComponent(token)}`;
    try {
      await Linking.openURL(url);
    } catch {
      setError('Factuur downloaden mislukt.');
    }
  }

  async function onSubmitRating(token: string) {
    if (rateStars < 1) {
      setRateError('Kies 1 tot 5 sterren.');
      return;
    }
    setBusy(true);
    setRateError(null);
    try {
      const data = await rateLive(token, rateStars, rateComment.trim() || null);
      if (data.ride) {
        setLive(data.ride);
        const next = await updateGuestRide(token, {
          phase: data.ride.phase,
          status_label: data.ride.status_label,
          rating: data.ride.rating || null,
        });
        setGuestRides(next);
      }
      setRateStars(0);
      setRateComment('');
      setRateModalToken(null);
    } catch (e) {
      setRateError(e instanceof ApiError ? e.message : 'Beoordeling opslaan mislukt.');
    } finally {
      setBusy(false);
    }
  }

  function startNewRide() {
    setTrackToken(null);
    setLive(null);
    persistTrack(null);
    setError(null);
    setExpandedRideKey(null);
    goToBookTab();
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

  async function toggleArchiveRide(ride: GuestRide, archived: boolean) {
    const keys = await setRideArchived(ride, archived);
    setArchivedKeys(keys);
  }

  const routeSummary = useMemo(() => {
    const metrics = routeMetrics;
    if (!metrics) return '— km · — min';
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

  const mapBlock = (
    <View style={styles.mapWrap}>
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
      >
        {pickup ? (
          <Marker
            coordinate={{ latitude: pickup.lat, longitude: pickup.lng }}
            title="Ophalen"
            anchor={{ x: 0.5, y: 0.5 }}
            tracksViewChanges={false}
          >
            <View style={styles.pickupDotOuter}>
              <View style={styles.pickupDotInner} />
            </View>
          </Marker>
        ) : null}
        {dropoff ? (
          <Marker
            coordinate={{ latitude: dropoff.lat, longitude: dropoff.lng }}
            title="Bestemming"
            image={dropPinGreen}
            anchor={{ x: 0.5, y: 1 }}
            tracksViewChanges={false}
          />
        ) : null}
        {freeTaxis.map((taxi) => (
              <Marker
                key={taxi.id}
                coordinate={{ latitude: taxi.lat, longitude: taxi.lng }}
                title="Taxi beschikbaar"
                description={
                  taxi.distance_km != null ? `${taxi.distance_km.toFixed(1)} km` : undefined
                }
                anchor={{ x: 0.5, y: 0.5 }}
                tracksViewChanges={false}
              >
                <Image
                  source={taxiCarYellow}
                  style={styles.taxiMarker}
                  resizeMode="contain"
                />
              </Marker>
            ))}
        {routeCoords.length > 1 ? (
          <Polyline coordinates={routeCoords} strokeColor={colors.primary} strokeWidth={4} />
        ) : null}
      </MapView>
      <View style={styles.zoomControls} pointerEvents="box-none">
        <Pressable
          onPress={() => zoomMap(1)}
          style={styles.zoomBtn}
          accessibilityLabel="Inzoomen"
        >
          <Text style={styles.zoomBtnText}>+</Text>
        </Pressable>
        <Pressable
          onPress={() => zoomMap(-1)}
          style={styles.zoomBtn}
          accessibilityLabel="Uitzoomen"
        >
          <Text style={styles.zoomBtnText}>−</Text>
        </Pressable>
        {pickup && dropoff ? (
          <Pressable
            onPress={fitMapToRoute}
            style={styles.zoomBtn}
            accessibilityLabel="Toon route"
            accessibilityHint="Centreert de kaart op de geplande rit"
          >
            <Ionicons name="navigate" size={18} color={colors.text} />
          </Pressable>
        ) : null}
      </View>
      <View style={styles.fleetBadge}>
        <Text style={styles.fleetBadgeText}>
          {`${freeNearby} taxi${freeNearby === 1 ? '' : '’s'} online`}
        </Text>
      </View>
    </View>
  );

  const headerTitle =
    tab === 'rides'
      ? showArchivedRides
        ? 'Archief'
        : 'Ritten'
      : tab === 'profile'
        ? 'Profiel'
        : 'Boeken';

  const appHeader = (
    <AppPageHeader
      title={headerTitle}
      onLogoPress={goToBookTab}
      left={
        <Pressable
          onPress={onBack}
          hitSlop={10}
          accessibilityRole="button"
          accessibilityLabel="Naar startpagina"
        >
          <Ionicons name="home-outline" size={24} color={colors.text} />
        </Pressable>
      }
      right={
        tab === 'rides' && showArchivedRides ? (
          <Pressable
            onPress={() => setShowArchivedRides(false)}
            hitSlop={10}
            accessibilityLabel="Terug naar ritten"
          >
            <Ionicons name="chevron-back" size={24} color={colors.text} />
          </Pressable>
        ) : null
      }
    />
  );

  const liveStatusFromUpdates = (() => {
    const updates = live?.status_updates;
    if (!Array.isArray(updates) || updates.length === 0) return null;
    const latest = updates[updates.length - 1];
    const label = String(latest?.label || '').trim();
    return label || null;
  })();

  const activeBannerRide =
    (live && trackToken && isActiveRidePhase(live.phase)
      ? {
          token: trackToken,
          id: live.id,
          from: live.pickup_address || pickup?.address || '',
          to: live.dropoff_address || dropoff?.address || '',
          phase: live.phase,
          status_label: liveStatusFromUpdates || live.status_label,
          at: Date.now(),
          quoted_price: live.quoted_price,
        }
      : guestRides.find((r) => isActiveRidePhase(r.phase))) || null;

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
      <Text style={[styles.section, styles.sectionCenter]}>Rit & prijs</Text>
      {showQuoteMetrics ? (
        <View style={styles.quoteMetricsBox}>
          <View style={styles.tripMetrics}>
            {quoteDistance ? (
              <View style={styles.tripMetric}>
                <Ionicons name="navigate-outline" size={18} color={colors.muted} />
                <Text style={styles.tripMetricValue}>{quoteDistance}</Text>
                <Text style={styles.tripMetricLabel}>afstand</Text>
              </View>
            ) : null}
            {quoteDistance && (quoteDuration || quotePassengersLabel) ? (
              <View style={styles.tripMetricDivider} />
            ) : null}
            {quoteDuration ? (
              <View style={styles.tripMetric}>
                <Ionicons name="time-outline" size={18} color={colors.muted} />
                <Text style={styles.tripMetricValue}>{quoteDuration}</Text>
                <Text style={styles.tripMetricLabel}>rijtijd</Text>
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
        <Text
          style={quoteBusy ? styles.quotePriceBusy : styles.price}
          numberOfLines={1}
        >
          {quoteBusy ? 'Bezig met berekenen…' : displayPrice}
        </Text>
      </View>
      <Text style={[styles.meta, styles.quoteMetaCenter]}>
        {freeNearby > 0
          ? `${freeNearby} taxi${freeNearby === 1 ? '' : '’s'} in de buurt`
          : 'Geen vrije taxi in de buurt'}
      </Text>

      {pickup && dropoff && !canDispatch ? (
        <Text style={styles.warnText}>
          Geen taxi’s beschikbaar. We sturen geen rit uit. Je krijgt een melding zodra er weer een
          taxi beschikbaar is.
        </Text>
      ) : null}
      {fieldErrors.offer ? <Text style={styles.fieldErrorText}>{fieldErrors.offer}</Text> : null}
      {fieldErrors.dispatch ? (
        <Text style={styles.fieldErrorText}>{fieldErrors.dispatch}</Text>
      ) : null}
    </Card>
  ) : null;

  const bookContent = (
    <>
      {activeBannerRide ? (
        <ActiveRideBanner
          ride={activeBannerRide as GuestRide}
          onPress={() => openRideFollow(activeBannerRide as GuestRide)}
        />
      ) : null}

      <Card>
        <Field
          label="Ophaaladres"
          value={pickupQuery}
          onChangeText={(v) => {
            setPickupQuery(v);
            if (pickup && v.trim() !== pickup.address) setPickup(null);
            setFieldErrors((e) => ({ ...e, pickup: undefined }));
            scheduleSuggestions(v, 'pickup');
          }}
          placeholder="Typ een adres en kies een suggestie"
          autoCapitalize="words"
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
          clearAccessibilityLabel="Ophaaladres wissen"
          rightAccessory={
            <Pressable
              onPress={() => loadPickup({ focusDropoff: true })}
              disabled={pickupLoading}
              hitSlop={8}
              style={styles.locIconBtn}
              accessibilityLabel="Bepaal mijn locatie"
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
          label="Bestemming"
          value={dropoffQuery}
          inputRef={dropoffInputRef}
          onChangeText={(v) => {
            setDropoffQuery(v);
            if (dropoff && v.trim() !== dropoff.address) setDropoff(null);
            setFieldErrors((e) => ({ ...e, dropoff: undefined }));
            scheduleSuggestions(v, 'dropoff');
          }}
          placeholder="Typ een adres en kies een suggestie"
          autoCapitalize="words"
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
          clearAccessibilityLabel="Bestemming wissen"
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

      <Card>
        <PickupAtField value={pickupAt} onChange={setPickupAt} />
      </Card>

      <View
        onLayout={(e) => {
          mapSectionY.current = e.nativeEvent.layout.y;
        }}
      >
        {mapBlock}
      </View>

      {priceBlock}

      <Card>
        <Text style={[styles.section, styles.sectionCenter]}>Passagiers</Text>
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

      <Card>
        <Text style={styles.section}>Bagage</Text>
        {BAGGAGE_ROWS.map((row) => (
          <View key={row.key} style={styles.baggageRow}>
            <View style={{ flex: 1, minWidth: 0 }}>
              <Text style={styles.baggageLabel}>{row.label}</Text>
              <Text style={styles.baggageHint}>{row.hint}</Text>
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

      <Card>
        <View style={styles.nameRow}>
          <View style={{ flex: 1 }}>
            <Field
              label="Voornaam"
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
              label="Achternaam"
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
          label="Telefoon"
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
        <Field
          label="Opmerkingen"
          value={remarks}
          onChangeText={setRemarks}
          placeholder="Bijv. bakfiets, hulp bij instappen…"
          multiline
        />
        <Text style={styles.profileHint}>
          Deze gegevens kun je ook in je profiel invullen, waarna ze hier automatisch worden
          ingevuld.
        </Text>
      </Card>

      {locationError ? <ErrorText>{locationError}</ErrorText> : null}
      {error ? <ErrorText>{error}</ErrorText> : null}
      {fieldErrors.dispatch ? (
        <Text style={styles.fieldErrorText}>{fieldErrors.dispatch}</Text>
      ) : null}
      <PrimaryButton title="Taxi aanvragen" onPress={onBook} loading={busy} />
    </>
  );

  const archivedSet = new Set(archivedKeys);
  const activeRides = guestRides.filter((r) => isActiveRidePhase(r.phase));
  const pastRides = guestRides.filter((r) => !isActiveRidePhase(r.phase));
  const visiblePast = pastRides.filter((r) => !archivedSet.has(rideArchiveKey(r)));
  const archivedPast = pastRides.filter((r) => archivedSet.has(rideArchiveKey(r)));

  function renderRatingResult(stars: number, comment?: string | null) {
    return (
      <View style={styles.statusTimeline}>
        <Text style={styles.rowLabel}>Jouw beoordeling</Text>
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

  function renderTripDetails(input: {
    from?: string | null;
    to?: string | null;
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
              <Text style={styles.tripLabel}>Ophalen</Text>
            </View>
            <View style={styles.tripAddress}>
              <Text style={styles.tripMain}>{pickupStop.main}</Text>
              {pickupStop.sub ? <Text style={styles.tripSub}>{pickupStop.sub}</Text> : null}
            </View>
          </View>
          <View style={[styles.tripStop, styles.tripStopLast]}>
            <View style={styles.tripHead}>
              <View style={[styles.tripDotOuter, styles.tripDotOuterDropoff]}>
                <View style={[styles.tripDot, styles.tripDotDropoff]} />
              </View>
              <Text style={styles.tripLabel}>Afzetten</Text>
            </View>
            <View style={styles.tripAddress}>
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
                    <Text style={styles.tripMetricLabel}>afstand</Text>
                  </View>
                ) : null}
                {distance && (duration || passengersLabel) ? <View style={styles.tripMetricDivider} /> : null}
                {duration ? (
                  <View style={styles.tripMetric}>
                    <Ionicons name="time-outline" size={18} color={colors.muted} />
                    <Text style={styles.tripMetricValue}>{duration}</Text>
                    <Text style={styles.tripMetricLabel}>rijtijd</Text>
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
                <Text style={styles.tripBagsHeading}>Bagage</Text>
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
    const price =
      isLiveMatch && live!.quoted_price != null ? live!.quoted_price : ride.quoted_price;
    const cancelled = phase === 'cancelled';
    const completed = phase === 'completed';
    const active = isActiveRidePhase(phase);
    const headerStatus =
      (isLiveMatch && (liveStatusFromUpdates || live?.status_label)) ||
      ride.status_label ||
      '';
    const canArchive = !active;
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
          ? 'Je rit blijft op de marktplaats tot een taxi accepteert. Je kunt alsnog annuleren.'
          : 'Je rit is aangeboden aan aangesloten taxibedrijven in de buurt.';

      return (
        <View key={key || ride.token} style={styles.rideCard}>
          <View style={styles.rideCardBody}>
            <PhasePill phase="searching" />
            <SearchingTaxiTitle />
            <Text style={styles.searchingDesc}>{desc}</Text>
            <View style={styles.searchingDivider} />
            <View style={styles.searchingMetaRow}>
              <View style={{ flex: 1, minWidth: 0 }}>
                <Text style={styles.metaCaption}>Datum</Text>
                <Text style={styles.rideCardAmount}>{pickupWhen}</Text>
              </View>
              {price != null ? (
                <View style={{ alignItems: 'flex-end' }}>
                  <Text style={styles.metaCaption}>Prijs</Text>
                  <Text style={styles.rideCardAmount}>{formatEuroNl(Number(price))}</Text>
                </View>
              ) : null}
            </View>
            {renderTripDetails({
              from,
              to,
              distanceKm,
              durationSeconds,
              passengers,
              baggage: isLiveMatch
                ? live?.baggage
                : { summary: baggageSummary },
            })}
            {needsDecision ? (
              <View style={{ marginTop: 14, gap: 8 }}>
                <PrimaryButton title="Blijven wachten" onPress={onWait} loading={busy} />
                <GhostButton title="Annuleren" onPress={requestCancel} danger />
              </View>
            ) : showCancel ? (
              <Pressable
                onPress={() => {
                  if (!isLiveMatch) loadRideLive(ride);
                  else if (ride.token !== trackToken) {
                    setTrackToken(ride.token);
                    persistTrack(ride.token);
                  }
                  setCancelConfirmOpen(true);
                }}
                style={styles.cancelRideBtn}
                accessibilityRole="button"
                accessibilityLabel="Rit annuleren"
              >
                <Text style={styles.cancelRideBtnText}>Rit annuleren</Text>
              </Pressable>
            ) : null}
          </View>
        </View>
      );
    }

    return (
      <View
        key={key || ride.token}
        style={[
          styles.rideCard,
          cancelled && styles.rideCardCancelled,
          phase === 'accepted' && styles.rideCardAccepted,
        ]}
      >
        {cancelled ? (
          <View style={styles.rideCardStatusBar}>
            <Text style={styles.rideCardStatusBarText}>Geannuleerd</Text>
          </View>
        ) : null}

        <View style={styles.rideCardBody}>
          {(() => {
            if (!cancelled) return null;
            const cancelMsg = String(
              (isLiveMatch ? live?.cancellation_message : null) || ride.cancellation_message || ''
            ).trim();
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
                  <Text style={styles.cancelReasonNotice}>{cancelMsg}</Text>
                ) : null}
                {refundMsg ? (
                  <View style={styles.refundNotice}>
                    <Text style={styles.refundNoticeTitle}>Terugbetaling</Text>
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
                <PhasePill phase={phase} />
              </Pressable>
              <View style={styles.rideCardTopRight}>
                {canArchive ? (
                  <Pressable
                    onPress={() =>
                      toggleArchiveRide(ride, !showArchivedRides).catch(() => undefined)
                    }
                    hitSlop={8}
                    style={styles.archiveIconBtn}
                    accessibilityLabel={
                      showArchivedRides ? 'Terugzetten uit archief' : 'Archiveren'
                    }
                  >
                    <Ionicons
                      name={showArchivedRides ? 'arrow-up-circle-outline' : 'archive-outline'}
                      size={20}
                      color={colors.muted}
                    />
                  </Pressable>
                ) : null}
                <Pressable
                  onPress={() => toggleRideExpanded(ride)}
                  hitSlop={12}
                  style={styles.rideChevronBtn}
                  accessibilityLabel={expanded ? 'Inklappen' : 'Uitklappen'}
                >
                  <Ionicons
                    name={expanded ? 'chevron-up' : 'chevron-down'}
                    size={26}
                    color={colors.muted}
                  />
                </Pressable>
              </View>
            </View>
          ) : null}

          <Pressable
            onPress={() => toggleRideExpanded(ride)}
            accessibilityRole="button"
            accessibilityState={{ expanded }}
          >
            {!cancelled ? (
              <Text style={styles.liveTitle}>
                {phase === 'awaiting_payment'
                  ? 'Wacht op betaling'
                  : headerStatus || phaseLabel(phase)}
              </Text>
            ) : null}

            <View style={styles.rideRouteRow}>
              <Text
                style={[styles.rideRouteLine, { flex: 1, minWidth: 0, marginBottom: 0 }]}
                numberOfLines={expanded ? 4 : 2}
              >
                {shortAddress(from)} → {shortAddress(to)}
              </Text>
              {cancelled ? (
                <View style={styles.rideCardTopRight}>
                  {canArchive ? (
                    <Pressable
                      onPress={() =>
                        toggleArchiveRide(ride, !showArchivedRides).catch(() => undefined)
                      }
                      hitSlop={8}
                      style={styles.archiveIconBtn}
                      accessibilityLabel={
                        showArchivedRides ? 'Terugzetten uit archief' : 'Archiveren'
                      }
                    >
                      <Ionicons
                        name={showArchivedRides ? 'arrow-up-circle-outline' : 'archive-outline'}
                        size={20}
                        color={colors.muted}
                      />
                    </Pressable>
                  ) : null}
                  <Ionicons
                    name={expanded ? 'chevron-up' : 'chevron-down'}
                    size={26}
                    color={colors.muted}
                  />
                </View>
              ) : null}
            </View>
            <View style={[styles.rideCardFoot, { marginTop: 4 }]}>
              <View style={{ flex: 1, minWidth: 0 }}>
                <Text style={styles.metaCaption}>Datum</Text>
                <Text style={styles.rideCardAmount}>{pickupWhen}</Text>
              </View>
              {price != null ? (
                <View style={{ alignItems: 'flex-end' }}>
                  <Text style={styles.metaCaption}>Prijs</Text>
                  <Text style={styles.rideCardAmount}>{formatEuroNl(Number(price))}</Text>
                </View>
              ) : null}
            </View>
          </Pressable>
          {active && ridePin && !ridePin.started && hiddenPinKeys.includes(ridePin.key) ? (
            <Pressable
              onPress={() => setHiddenPinKeys((keys) => keys.filter((key) => key !== ridePin.key))}
              style={styles.pinShowBtn}
              accessibilityRole="button"
              accessibilityLabel="Ophaalcode tonen"
            >
              <Ionicons name="keypad-outline" size={18} color="#0B1220" />
              <Text style={styles.pinShowBtnText}>Ophaalcode</Text>
            </Pressable>
          ) : null}
        </View>

        {expanded ? (
          <View style={styles.rideCardExpanded}>
            {renderTripDetails({
              from,
              to,
              distanceKm,
              durationSeconds,
              passengers: (isLiveMatch ? live?.passengers : ride.passengers) ?? ride.passengers,
              baggage: isLiveMatch
                ? live?.baggage || { summary: ride.baggage_summary }
                : { summary: ride.baggage_summary },
            })}
            {price != null ? (
              <>
                <Text style={[styles.rowLabel, { marginTop: 10 }]}>Prijs</Text>
                <Text style={styles.rowValue}>{formatEuroNl(Number(price))}</Text>
              </>
            ) : null}

            {isLiveMatch && live ? (
              <>
                {live.eta_label ? (
                  <>
                    <Text style={[styles.rowLabel, { marginTop: 10 }]}>Verwachte aankomsttijd</Text>
                    <Text style={styles.rowValue}>{live.eta_label}</Text>
                  </>
                ) : null}

                {live.driver || live.vehicle ? (
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
                          accessibilityLabel="Profielfoto vergroten"
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
                          accessibilityLabel="Autofoto vergroten"
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
                    <Text style={[styles.rowLabel, { marginTop: 10 }]}>Centrale</Text>
                    <Text style={styles.rowValue}>{live.company.name}</Text>
                  </>
                ) : null}

                {Array.isArray(live.status_updates) && live.status_updates.length > 0 ? (
                  <View style={styles.statusTimeline}>
                    <Text style={styles.rowLabel}>Statusupdates</Text>
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
                              {new Date(u.at).toLocaleString('nl-NL', {
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
                    <PrimaryButton title="Blijven wachten" onPress={onWait} loading={busy} />
                    <GhostButton title="Annuleren" onPress={requestCancel} danger />
                  </View>
                ) : null}

                {live.can_retry_payment ? (
                  <View style={{ marginTop: 12 }}>
                    <PrimaryButton title="Nu betalen" onPress={onPay} loading={busy} />
                  </View>
                ) : null}

                {live.can_cancel && !live.needs_unaccepted_decision ? (
                  <View style={{ marginTop: 8 }}>
                    <GhostButton title="Rit annuleren" onPress={requestCancel} danger />
                  </View>
                ) : null}

                {live.rating?.can_rate ? (
                  <View style={{ marginTop: 14 }}>
                    <GhostButton title="Beoordeel je rit" onPress={() => openRateModal(ride.token)} />
                  </View>
                ) : null}

                {live.rating?.rated && live.rating.stars
                  ? renderRatingResult(live.rating.stars, live.rating.comment)
                  : null}

                {live.can_download_invoice || live.phase === 'completed' ? (
                  <View style={{ marginTop: 12 }}>
                    <GhostButton
                      title="Factuur downloaden"
                      onPress={() => onDownloadInvoice(ride.token)}
                    />
                  </View>
                ) : null}
              </>
            ) : null}

            {!isLiveMatch && completed && ride.rating?.can_rate ? (
              <View style={{ marginTop: 14 }}>
                <GhostButton title="Beoordeel je rit" onPress={() => openRateModal(ride.token)} />
              </View>
            ) : null}

            {!isLiveMatch && ride.rating?.rated && ride.rating.stars
              ? renderRatingResult(ride.rating.stars, ride.rating.comment)
              : null}

            {!isLiveMatch && completed ? (
              <View style={{ marginTop: 12 }}>
                <GhostButton
                  title="Factuur downloaden"
                  onPress={() => onDownloadInvoice(ride.token)}
                />
              </View>
            ) : null}

            {completed && !cancelled ? (
              <View style={{ marginTop: 12 }}>
                <PrimaryButton title="Nieuwe rit" onPress={startNewRide} />
              </View>
            ) : null}
          </View>
        ) : null}
      </View>
    );
  }

  const ridesContent = (
    <ScrollView
      style={styles.formScroll}
      contentContainerStyle={styles.scroll}
      keyboardShouldPersistTaps="handled"
      nestedScrollEnabled
    >
      {showArchivedRides ? (
        archivedPast.length === 0 ? (
          <Card>
            <Text style={styles.meta}>
              Gearchiveerde ritten verschijnen hier. Tik op het archieficoon bij een eerdere rit om
              die te bewaren.
            </Text>
          </Card>
        ) : (
          <>
            <Text style={styles.ridesSection}>Gearchiveerde ritten</Text>
            {archivedPast.map(renderRideCard)}
          </>
        )
      ) : activeRides.length === 0 &&
        visiblePast.length === 0 &&
        archivedPast.length === 0 ? (
        <Card>
          <Text style={styles.meta}>
            Nog geen ritten. Boek een taxi via Boeken; openstaande en eerdere ritten zie je hier.
          </Text>
          <PrimaryButton title="Taxi boeken" onPress={goToBookTab} />
        </Card>
      ) : (
        <>
          {activeRides.length > 0 ? (
            <>
              <Text style={styles.ridesSection}>Openstaand</Text>
              {activeRides.map(renderRideCard)}
            </>
          ) : null}
          {visiblePast.length > 0 ? (
            <>
              <Text style={styles.ridesSection}>
                {activeRides.length ? 'Eerdere ritten' : 'Alle ritten'}
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
              <Text style={styles.archiveLinkCta}>Bekijken →</Text>
            </Pressable>
          ) : null}
        </>
      )}
    </ScrollView>
  );

  const profileContent = (
    <ScrollView
      style={styles.formScroll}
      contentContainerStyle={styles.scroll}
      keyboardShouldPersistTaps="handled"
      nestedScrollEnabled
    >
      <ErrorText>{error}</ErrorText>
      <Card>
        <Field label="Voornaam" value={firstName} onChangeText={setFirstName} autoCapitalize="words" />
        <Field label="Achternaam" value={lastName} onChangeText={setLastName} autoCapitalize="words" />
        <Field
          label="Telefoon"
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
        {profileSaved ? <Text style={[styles.meta, { color: colors.success }]}>Opgeslagen.</Text> : null}
        <PrimaryButton title="Opslaan" onPress={saveProfile} />
      </Card>

      <Card>
        <Text style={styles.section}>Weergave</Text>
        <Text style={styles.themeHint}>
          Standaard volgt de app de light/dark-modus van je telefoon. Kies hieronder alleen als je
          dat wilt overschrijven.
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
                <Text style={styles.themeLabel}>{opt.label}</Text>
                <Text style={styles.themeSub}>{opt.hint}</Text>
              </View>
            </Pressable>
          );
        })}
      </Card>

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
      prompt: confirm?.prompt || 'Geef deze ophaalcode aan de chauffeur om de rit te starten.',
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
    if (!info || info.started || hiddenPinKeys.includes(info.key)) return null;
    return info;
  })();

  return (
    <Screen style={styles.shell}>
      {appHeader}
      <View style={styles.shellBody}>
        {tab === 'rides'
          ? ridesContent
          : tab === 'profile'
            ? profileContent
            : (
              <ScrollView
                key={`book-${bookMountKey}`}
                ref={bookScrollRef}
                style={styles.formScroll}
                contentContainerStyle={styles.scroll}
                keyboardShouldPersistTaps="handled"
                keyboardDismissMode="on-drag"
                automaticallyAdjustKeyboardInsets
                nestedScrollEnabled
                removeClippedSubviews={false}
              >
                {bookContent}
              </ScrollView>
            )}
      </View>
      <CustomerTabBar
        active={tab}
        onChange={(key) => {
          if (key === 'book') {
            goToBookTab();
            return;
          }
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
        ridesBadge={
          guestRides.filter((r) => isRidesTabBadgePhase(r.phase)).length ||
          (hasActiveRide && isRidesTabBadgePhase(live?.phase) ? 1 : 0)
        }
      />

      {pickupPinInfo && tab === 'rides' ? (
        <View style={styles.pickupPinBanner} pointerEvents="box-none">
          <Pressable
            onPress={() =>
              setHiddenPinKeys((keys) =>
                keys.includes(pickupPinInfo.key) ? keys : [...keys, pickupPinInfo.key]
              )
            }
            hitSlop={10}
            style={styles.pickupPinClose}
            accessibilityRole="button"
            accessibilityLabel="Ophaalcode sluiten"
          >
            <Ionicons name="close" size={22} color="#0B1220" />
          </Pressable>
          <Text style={styles.pickupPinBannerLabel}>Ophaalcode voor de chauffeur</Text>
          <Text
            style={styles.pickupPinDigits}
            accessibilityLabel={`Ophaalcode ${pickupPinInfo.pin}`}
          >
            {pickupPinInfo.pin}
          </Text>
          <Text style={styles.pickupPinBannerHint}>{pickupPinInfo.prompt}</Text>
        </View>
      ) : null}

      <AppModal
        visible={cancelConfirmOpen}
        onRequestClose={() => setCancelConfirmOpen(false)}
        panelStyle={styles.confirmBanner}
      >
        <Text style={styles.confirmTitle}>Rit annuleren?</Text>
        <Text style={styles.confirmText}>
          Weet je zeker dat je deze rit wilt annuleren? Als je vooraf hebt betaald, wordt het
          bedrag teruggestort (doorgaans binnen {refundDays} werkdagen).
        </Text>
        <View style={styles.confirmActions}>
          <View style={styles.confirmActionSlot}>
            <Pressable
              onPress={() => setCancelConfirmOpen(false)}
              style={styles.confirmSecondaryBtn}
            >
              <Text style={styles.confirmSecondaryText}>Nee, behouden</Text>
            </Pressable>
          </View>
          <View style={styles.confirmActionSlot}>
            <Pressable onPress={confirmCancel} style={styles.confirmDangerBtn}>
              <Text style={styles.confirmDangerText}>Ja, annuleren</Text>
            </Pressable>
          </View>
        </View>
      </AppModal>

      <AppModal
        visible={!!rateModalToken}
        onRequestClose={() => {
          if (!busy) setRateModalToken(null);
        }}
        dismissDisabled={busy}
        panelStyle={styles.confirmBanner}
      >
        <Text style={styles.confirmTitle}>Beoordeel je rit</Text>
        <Text style={styles.confirmText}>Hoe was de rit?</Text>
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
          placeholder="Optionele opmerking"
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
              <Text style={styles.confirmSecondaryText}>Later</Text>
            </Pressable>
          </View>
          <View style={styles.confirmActionSlot}>
            <PrimaryButton
              title="Versturen"
              onPress={() => {
                if (rateModalToken) onSubmitRating(rateModalToken);
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
      >
        <Pressable
          onPress={() => setPhotoPreviewUri(null)}
          accessibilityRole="button"
          accessibilityLabel="Sluiten"
          hitSlop={8}
          style={styles.photoPreviewClose}
        >
          <Ionicons name="close" size={22} color={colors.text} />
        </Pressable>
        {photoPreviewUri ? (
          <Pressable
            onPress={() => setPhotoPreviewUri(null)}
            accessibilityRole="button"
            accessibilityLabel="Sluiten"
          >
            <Image
              source={{ uri: photoPreviewUri }}
              style={styles.photoPreviewImage}
              resizeMode="contain"
            />
          </Pressable>
        ) : null}
      </AppModal>
    </Screen>
  );
}

function ActiveRideBanner({
  ride,
  onPress,
}: {
  ride: GuestRide;
  onPress: () => void;
}) {
  const { colors } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const shimmer = useRef(new Animated.Value(0)).current;
  const isSearching = ride.phase === 'searching' || ride.phase === 'awaiting_payment';

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

  return (
    <Pressable
      style={[styles.activeBanner, ride.phase === 'accepted' && styles.activeBannerAccepted]}
      onPress={onPress}
      accessibilityRole="button"
      accessibilityLabel="Openstaande rit volgen"
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
            ? ride.status_label || 'Taxi onderweg'
            : 'Openstaande rit'}
        </Text>
        <Text style={styles.activeBannerMeta} numberOfLines={1}>
          {shortAddress(ride.from)} → {shortAddress(ride.to)}
        </Text>
      </View>
      <Text style={styles.activeBannerCta}>Volgen</Text>
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
      ? 'Zoeken…'
      : phase === 'accepted'
        ? 'Geaccepteerd'
        : phase === 'awaiting_payment'
          ? 'Betaling'
          : phase === 'cancelled'
            ? 'Geannuleerd'
            : phase === 'completed'
              ? 'Afgerond'
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
    },
    bookPane: {
      flex: 1,
      minHeight: 0,
    },
    formScroll: {
      flex: 1,
      minHeight: 0,
    },
    scroll: {
      justifyContent: 'flex-start',
      paddingHorizontal: 20,
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
    activeBannerAccepted: {
      backgroundColor: 'rgba(34,197,94,0.2)',
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
    activeBannerMeta: {
      color: colors.muted,
      fontSize: 13,
    },
    activeBannerCta: {
      color: colors.success,
      fontSize: 15,
      fontWeight: '700',
    },
    ridesSection: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '700',
      letterSpacing: 0,
      marginTop: 8,
      marginBottom: 8,
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
    rideCardTopRight: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
      marginLeft: 'auto',
    },
    rideCardAmount: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '700',
      lineHeight: 20,
    },
    archiveIconBtn: {
      padding: 2,
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
      height: 40,
      marginTop: 14,
      alignItems: 'center',
      justifyContent: 'center',
    },
    quotePriceBusy: {
      color: colors.muted,
      fontSize: 15,
      fontWeight: '600',
      lineHeight: 40,
      textAlign: 'center',
    },
    quoteMetaCenter: {
      textAlign: 'center',
      marginTop: 6,
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
      borderWidth: 1,
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
      paddingHorizontal: 12,
      paddingTop: 8,
      paddingBottom: 12,
      maxWidth: 420,
      backgroundColor: colors.card,
    },
    photoPreviewClose: {
      alignSelf: 'flex-end',
      width: 36,
      height: 36,
      alignItems: 'center',
      justifyContent: 'center',
      marginRight: -4,
      marginBottom: 2,
    },
    photoPreviewImage: {
      width: '100%',
      height: 360,
      backgroundColor: 'transparent',
      borderRadius: 12,
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
      backgroundColor: '#F59E0B',
      alignItems: 'center',
      shadowColor: '#000',
      shadowOpacity: 0.28,
      shadowRadius: 12,
      shadowOffset: { width: 0, height: 4 },
      elevation: 6,
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
      color: '#0B1220',
      fontSize: 12,
      fontWeight: '700',
      letterSpacing: 0.3,
      marginBottom: 4,
      textAlign: 'center',
    },
    pickupPinDigits: {
      color: '#0B1220',
      fontSize: 36,
      fontWeight: '800',
      letterSpacing: 8,
      fontVariant: ['tabular-nums'],
    },
    pickupPinBannerHint: {
      color: '#0B1220',
      fontSize: 13,
      lineHeight: 18,
      textAlign: 'center',
      marginTop: 6,
    },
    pinShowBtn: {
      marginTop: 12,
      borderRadius: 12,
      backgroundColor: '#F59E0B',
      paddingVertical: 12,
      paddingHorizontal: 14,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      gap: 8,
    },
    pinShowBtnText: {
      color: '#0B1220',
      fontSize: 15,
      fontWeight: '700',
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
