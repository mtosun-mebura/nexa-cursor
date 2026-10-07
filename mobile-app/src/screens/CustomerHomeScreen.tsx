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
  View,
} from 'react-native';
import type { TextInput as TextInputType } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import MapView, { Marker, Polyline, PROVIDER_GOOGLE } from 'react-native-maps';
import {
  bookGuest,
  cancelLive,
  fetchLive,
  fetchNearbyTaxis,
  fetchQuote,
  LiveRide,
  NearbyTaxi,
  payLive,
  QuoteOffer,
  QuoteResponse,
  waitLive,
} from '../api/customer';
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
  formatRideWhen,
  GuestRide,
  isActiveRidePhase,
  isRidesTabBadgePhase,
  loadArchivedKeys,
  loadGuestRides,
  phaseLabel,
  rideArchiveKey,
  saveGuestRide,
  setRideArchived,
  shortAddress,
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
import { PickupAtField } from '../ui/PickupAtPicker';
import { ColorPalette } from '../config';
import { ThemePreference, useTheme } from '../theme/ThemeContext';

const TRACK_KEY = 'nexa_taxi_customer_track';
const PROFILE_KEY = 'nexa_taxi_customer_profile_guest';
const TAB_KEY = 'nexa_taxi_customer_tab';
const logoDark = require('../../assets/nexa-taxi-logo-dark.png');
const logoLight = require('../../assets/nexa-taxi-logo.png');
const taxiCarYellow = require('../../assets/taxi-car-yellow.png');

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

export function CustomerHomeScreen({ onBack }: { onBack: () => void }) {
  const { colors, colorScheme, preference, setPreference } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const logoSource = colorScheme === 'light' ? logoLight : logoDark;
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
  const [profileSaved, setProfileSaved] = useState(false);
  const [cancelConfirmOpen, setCancelConfirmOpen] = useState(false);

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
  const freeTaxis = nearbyTaxis.filter((t) => !t.busy);
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
            const next = await saveGuestRide(data.ride.id, token, {
              from: data.ride.pickup_address || '',
              to: data.ride.dropoff_address || '',
              phase: data.ride.phase,
              status_label: data.ride.status_label,
              quoted_price: data.ride.quoted_price,
            });
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
    (async () => {
      try {
        const savedTab = await AsyncStorage.getItem(TAB_KEY);
        if (savedTab === 'book' || savedTab === 'rides' || savedTab === 'profile') {
          setTab(savedTab);
        }
      } catch {
        /* ignore */
      }
    })();
  }, []);

  useEffect(() => {
    loadPickup();
    (async () => {
      try {
        const [rides, archived] = await Promise.all([loadGuestRides(), loadArchivedKeys()]);
        setGuestRides(rides);
        setArchivedKeys(archived);
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
          const next = await saveGuestRide(data.ride.id, saved, {
            from: data.ride.pickup_address || '',
            to: data.ride.dropoff_address || '',
            phase: data.ride.phase,
            status_label: data.ride.status_label,
            quoted_price: data.ride.quoted_price,
          });
          setGuestRides(next);
          if (!isActiveRidePhase(data.ride.phase)) {
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
  }, [pickup?.lat, pickup?.lng, dropoff?.lat, dropoff?.lng, fleetOrigin.lat, fleetOrigin.lng]);

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
      setNearbyTaxis(vehicles.filter((t) => !t.busy));
    } catch {
      /* behoud vorige markers */
    }
  }, [pickup, fleetOrigin]);

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

  useEffect(() => {
    if (!trackToken) return;
    let cancelled = false;
    let timer: ReturnType<typeof setTimeout> | null = null;

    const tick = async () => {
      try {
        const data = await fetchLive(trackToken);
        if (cancelled) return;
        setLive(data.ride);
        const next = await updateGuestRide(trackToken, {
          id: data.ride.id,
          from: data.ride.pickup_address || undefined,
          to: data.ride.dropoff_address || undefined,
          phase: data.ride.phase,
          status_label: data.ride.status_label,
          quoted_price: data.ride.quoted_price,
        });
        setGuestRides(next);
        if (!isActiveRidePhase(data.ride.phase)) {
          await persistTrack(null);
          return;
        }
        const ms = data.ride.poll_interval_ms || 3000;
        timer = setTimeout(tick, ms);
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
    const point = { lat: item.lat, lng: item.lng, address: item.label };
    if (kind === 'pickup') {
      setPickup(point);
      setPickupQuery(item.label);
      setPickupSuggestions([]);
      // Alleen focus naar bestemming — scroll naar map pas na bestemming.
      focusDropoffField();
      return;
    }
    setDropoff(point);
    setDropoffQuery(item.label);
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

    if (!quote?.payment?.booking || !quote?.payment?.mollie_configured) {
      const msg = 'Online betalen is momenteel niet beschikbaar. Probeer het later opnieuw.';
      setError(msg);
      Alert.alert('Betaling niet beschikbaar', msg);
      return;
    }

    if (!pickup || !dropoff || !offer) return;

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
        selected_offer_id: offer.id || null,
        payment_method: 'booking',
        return_url: CUSTOMER_PAYMENT_RETURN_URL,
        marketplace_radius_km: 50,
        ...baggagePayload,
      });

      setTrackToken(data.track_token);
      await persistTrack(data.track_token);
      if (data.live) setLive(data.live);
      const next = await saveGuestRide(data.ride_request_id, data.track_token, {
        from: pickup.address,
        to: dropoff.address,
        phase: data.live?.phase || 'awaiting_payment',
        status_label: data.live?.status_label || 'Betaling',
        quoted_price: data.live?.quoted_price ?? offer.price ?? null,
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
        const next = await updateGuestRide(trackToken, {
          phase: data.ride.phase,
          status_label: data.ride.status_label,
          from: data.ride.pickup_address || undefined,
          to: data.ride.dropoff_address || undefined,
          quoted_price: data.ride.quoted_price,
        });
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

  function startNewRide() {
    setTrackToken(null);
    setLive(null);
    persistTrack(null);
    setError(null);
    setExpandedRideKey(null);
    goToBookTab();
  }

  function openRideFollow(ride: GuestRide) {
    setTrackToken(ride.token);
    persistTrack(isActiveRidePhase(ride.phase) ? ride.token : null);
    setExpandedRideKey(rideArchiveKey(ride));
    setShowArchivedRides(false);
    setTab('rides');
    AsyncStorage.setItem(TAB_KEY, 'rides').catch(() => undefined);
    setError(null);
    fetchLive(ride.token)
      .then((data) => {
        if (!data.ride) return;
        setLive(data.ride);
        return updateGuestRide(ride.token, {
          id: data.ride.id,
          from: data.ride.pickup_address || undefined,
          to: data.ride.dropoff_address || undefined,
          phase: data.ride.phase,
          status_label: data.ride.status_label,
          quoted_price: data.ride.quoted_price,
        }).then(setGuestRides);
      })
      .catch(() => undefined);
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
        customMapStyle={colorScheme === 'dark' ? DARK_MAP_STYLE : []}
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
            anchor={{ x: 0.5, y: 1 }}
            tracksViewChanges={false}
          >
            <View style={styles.dropPin}>
              <View style={styles.dropPinHead}>
                <View style={styles.dropPinHole} />
              </View>
              <View style={styles.dropPinTip} />
            </View>
          </Marker>
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
      <View style={[styles.zoomControls, { pointerEvents: 'box-none' }]}>
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
          {freeNearby} taxi{freeNearby === 1 ? '' : '’s'} online
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
    <View style={styles.header}>
      <View style={styles.logoBar}>
        <Image
          source={logoSource}
          style={styles.logo}
          resizeMode="contain"
          accessibilityLabel="NEXA | taxi"
        />
      </View>
      <View style={styles.headerTitleRow}>
        {tab === 'rides' && showArchivedRides ? (
          <Pressable
            onPress={() => setShowArchivedRides(false)}
            hitSlop={8}
            style={styles.headerBackBtn}
            accessibilityLabel="Terug naar ritten"
          >
            <Ionicons name="chevron-back" size={22} color={colors.text} />
          </Pressable>
        ) : (
          <View style={styles.headerBackBtn} />
        )}
        <Text style={styles.headerTitle} numberOfLines={1}>
          {headerTitle}
        </Text>
        <View style={styles.headerBackBtn} />
      </View>
    </View>
  );

  const activeBannerRide =
    (live && trackToken && isActiveRidePhase(live.phase)
      ? {
          token: trackToken,
          id: live.id,
          from: live.pickup_address || pickup?.address || '',
          to: live.dropoff_address || dropoff?.address || '',
          phase: live.phase,
          status_label: live.status_label,
          at: Date.now(),
          quoted_price: live.quoted_price,
        }
      : guestRides.find((r) => isActiveRidePhase(r.phase))) || null;

  const priceBlock = displayPrice ? (
    <Card>
      <Text style={styles.section}>Rit & prijs</Text>
      <Text style={styles.routeSummary}>{routeSummary}</Text>
      {quoteBusy ? (
        <Text style={styles.meta}>Bezig met berekenen…</Text>
      ) : (
        <Text style={styles.price}>{displayPrice}</Text>
      )}
      <Text style={styles.meta}>
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
        <Text style={styles.section}>Passagiers</Text>
        <View style={styles.qtyWrap}>
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
    const canArchive = !active;

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
          {cancelled && live?.cancellation_message ? (
            <Text style={styles.cancelReasonNotice}>{live.cancellation_message}</Text>
          ) : null}

          {!cancelled ? (
            <View style={styles.rideCardTop}>
              <Pressable
                onPress={() => setExpandedRideKey(expanded ? null : key)}
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
                  onPress={() => setExpandedRideKey(expanded ? null : key)}
                  hitSlop={8}
                  accessibilityLabel={expanded ? 'Inklappen' : 'Uitklappen'}
                >
                  <Ionicons
                    name={expanded ? 'chevron-up' : 'chevron-down'}
                    size={18}
                    color={colors.muted}
                  />
                </Pressable>
              </View>
            </View>
          ) : null}

          <Pressable
            onPress={() => setExpandedRideKey(expanded ? null : key)}
            accessibilityRole="button"
            accessibilityState={{ expanded }}
          >
            {!cancelled ? (
              phase === 'searching' ? (
                <SearchingTaxiTitle />
              ) : (
                <Text style={styles.liveTitle}>
                  {phase === 'accepted'
                    ? 'Taxi onderweg'
                    : phase === 'awaiting_payment'
                      ? 'Wacht op betaling'
                      : phaseLabel(phase, ride.status_label)}
                </Text>
              )
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
                    size={18}
                    color={colors.muted}
                  />
                </View>
              ) : null}
            </View>
            <View style={[styles.rideCardFoot, { marginTop: 4 }]}>
              <Text style={styles.meta}>{formatRideWhen(ride.at) || '—'}</Text>
              {price != null ? (
                <Text style={styles.rideCardAmount}>{formatEuroNl(Number(price))}</Text>
              ) : null}
            </View>
          </Pressable>
        </View>

        {expanded ? (
          <View style={styles.rideCardExpanded}>
            <Text style={styles.rowLabel}>Van</Text>
            <Text style={styles.rowValue}>{from || '—'}</Text>
            <Text style={[styles.rowLabel, { marginTop: 10 }]}>Naar</Text>
            <Text style={styles.rowValue}>{to || '—'}</Text>
            {price != null ? (
              <>
                <Text style={[styles.rowLabel, { marginTop: 10 }]}>Prijs</Text>
                <Text style={styles.rowValue}>{formatEuroNl(Number(price))}</Text>
              </>
            ) : null}

            {isLiveMatch && live ? (
              <>
                {live.eta_label ? <Text style={styles.meta}>ETA: {live.eta_label}</Text> : null}
                {live.driver?.name ? (
                  <Text style={styles.meta}>Chauffeur: {live.driver.name}</Text>
                ) : null}
                {live.vehicle?.label || live.vehicle?.license_plate ? (
                  <Text style={styles.meta}>
                    Voertuig: {live.vehicle.label || live.vehicle.license_plate}
                  </Text>
                ) : null}
                {live.company?.name ? (
                  <Text style={styles.meta}>Centrale: {live.company.name}</Text>
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
              </>
            ) : null}

            {(cancelled || completed) && (
              <View style={{ marginTop: 12 }}>
                <PrimaryButton title="Nieuwe rit" onPress={startNewRide} />
              </View>
            )}
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

      <PrimaryButton title="Rit boeken" onPress={goToBookTab} />
      <GhostButton title="Terug naar start" onPress={onBack} />
    </ScrollView>
  );

  const refundDays = Math.max(1, Number(live?.refund_business_days) || 10);

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
          <Pressable
            onPress={() => setCancelConfirmOpen(false)}
            style={styles.confirmSecondaryBtn}
          >
            <Text style={styles.confirmSecondaryText}>Nee, behouden</Text>
          </Pressable>
          <Pressable onPress={confirmCancel} style={styles.confirmDangerBtn}>
            <Text style={styles.confirmDangerText}>Ja, annuleren</Text>
          </Pressable>
        </View>
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
          style={[styles.activeBannerWash, { opacity: washOpacity, pointerEvents: 'none' }]}
        />
      ) : null}
      <View style={{ flex: 1, minWidth: 0 }}>
        <Text style={styles.activeBannerTitle}>
          {ride.phase === 'accepted' ? 'Taxi onderweg' : 'Openstaande rit'}
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
      ? 'Zoeken'
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
      paddingTop: 0,
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
    header: {
      paddingBottom: 2,
    },
    logoBar: {
      alignItems: 'center',
      paddingTop: 4,
      paddingBottom: 2,
      paddingHorizontal: 20,
    },
    logo: {
      width: 160,
      height: 40,
    },
    headerTitleRow: {
      flexDirection: 'row',
      alignItems: 'center',
      paddingHorizontal: 12,
      paddingTop: 4,
      paddingBottom: 6,
    },
    headerBackBtn: {
      width: 32,
      height: 32,
      alignItems: 'center',
      justifyContent: 'center',
    },
    headerTitle: {
      flex: 1,
      textAlign: 'center',
      color: colors.text,
      fontSize: 20,
      lineHeight: 24,
      fontWeight: '700',
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
      fontSize: 12,
      fontWeight: '700',
      textTransform: 'uppercase',
      letterSpacing: 0.4,
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
    },
    archiveIconBtn: {
      padding: 2,
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
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: 8,
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
    dropPin: {
      alignItems: 'center',
      width: 28,
      height: 36,
    },
    dropPinHead: {
      width: 26,
      height: 26,
      borderRadius: 13,
      backgroundColor: '#22C55E',
      borderWidth: 2,
      borderColor: '#FFFFFF',
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 2,
    },
    dropPinHole: {
      width: 8,
      height: 8,
      borderRadius: 4,
      backgroundColor: '#FFFFFF',
    },
    dropPinTip: {
      marginTop: -6,
      width: 0,
      height: 0,
      borderLeftWidth: 8,
      borderRightWidth: 8,
      borderTopWidth: 12,
      borderLeftColor: 'transparent',
      borderRightColor: 'transparent',
      borderTopColor: '#22C55E',
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
      fontSize: 12,
      fontWeight: '700',
      textTransform: 'uppercase',
      letterSpacing: 0.4,
      marginBottom: 8,
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
    },
    meta: {
      color: colors.muted,
      fontSize: 14,
      lineHeight: 20,
      marginTop: 6,
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
      color: colors.text,
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
    confirmActions: {
      flexDirection: 'row',
      gap: 10,
    },
    confirmSecondaryBtn: {
      flex: 1,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: colors.border,
      paddingVertical: 12,
      alignItems: 'center',
      backgroundColor: 'transparent',
    },
    confirmSecondaryText: {
      color: colors.text,
      fontSize: 14,
      fontWeight: '600',
    },
    confirmDangerBtn: {
      flex: 1,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: colors.danger,
      paddingVertical: 12,
      alignItems: 'center',
      backgroundColor: 'transparent',
    },
    confirmDangerText: {
      color: colors.danger,
      fontSize: 14,
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
      marginBottom: 12,
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
