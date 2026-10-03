import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  Alert,
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

type Step = 'book' | 'live';

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
  const [step, setStep] = useState<Step>('book');
  const [trackToken, setTrackToken] = useState<string | null>(null);
  const [live, setLive] = useState<LiveRide | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [profileSaved, setProfileSaved] = useState(false);

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
            setStep('live');
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
      setError(null);
      try {
        const point = await resolveCurrentPickup();
        setPickup(point);
        setPickupQuery(point.address);
        setPickupSuggestions([]);
        if (opts?.focusDropoff) {
          focusDropoffField();
        }
      } catch (e) {
        setError(e instanceof Error ? e.message : 'Locatie ophalen mislukt.');
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
        if (data.ride && !['cancelled', 'completed'].includes(data.ride.phase)) {
          setTrackToken(saved);
          setLive(data.ride);
          setStep('live');
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
    if (step !== 'live' || !trackToken) return;
    let cancelled = false;
    let timer: ReturnType<typeof setTimeout> | null = null;

    const tick = async () => {
      try {
        const data = await fetchLive(trackToken);
        if (cancelled) return;
        setLive(data.ride);
        if (['cancelled', 'completed'].includes(data.ride.phase)) {
          await persistTrack(null);
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
  }, [step, trackToken, persistTrack]);

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
      setStep('live');

      const checkout = data.checkout_url || data.live?.checkout_url;
      if (checkout) {
        await Linking.openURL(checkout);
      } else if (data.payment_required) {
        setError('Betalingslink kon niet worden geopend. Open betalen via Live rit.');
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Boeken mislukt.');
      Alert.alert('Boeken mislukt', e instanceof ApiError ? e.message : 'Boeken mislukt.');
    } finally {
      setBusy(false);
    }
  }

  async function onCancel() {
    if (!trackToken) return;
    setBusy(true);
    setError(null);
    try {
      const data = await cancelLive(trackToken);
      if (data.ride) setLive(data.ride);
      await persistTrack(null);
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
    setStep('book');
    setTrackToken(null);
    setLive(null);
    persistTrack(null);
    setError(null);
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
          {freeNearby} taxi{freeNearby === 1 ? '' : '’s'} online
        </Text>
      </View>
    </View>
  );

  const headerTitle =
    tab === 'rides' ? 'Ritten' : tab === 'profile' ? 'Profiel' : step === 'live' ? 'Live rit' : 'Boeken';

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
      <Text style={styles.headerTitle} numberOfLines={1}>
        {headerTitle}
      </Text>
    </View>
  );

  const priceBlock = (
    <Card>
      <Text style={styles.section}>Rit & prijs</Text>
      <Text style={styles.routeSummary}>{routeSummary}</Text>
      {quoteBusy && !displayPrice ? (
        <Text style={styles.meta}>Bezig met berekenen…</Text>
      ) : displayPrice ? (
        <Text style={styles.price}>{displayPrice}</Text>
      ) : (
        <Text style={styles.meta}>Vul ophalen + bestemming in voor een prijs.</Text>
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
  );

  const liveContent = (
    <>
      <View
        onLayout={(e) => {
          mapSectionY.current = e.nativeEvent.layout.y;
        }}
      >
        {mapBlock}
      </View>
      <ErrorText>{error}</ErrorText>

      <Card>
        <PhasePill phase={live?.phase} />
        <Text style={styles.liveTitle}>
          {live?.phase === 'searching'
            ? 'We zoeken een taxi in de buurt…'
            : live?.phase === 'accepted'
              ? 'Taxi onderweg'
              : live?.phase === 'awaiting_payment'
                ? 'Wacht op betaling'
                : live?.status_label || 'Status'}
        </Text>
        {live?.eta_label ? <Text style={styles.meta}>ETA: {live.eta_label}</Text> : null}
        {live?.driver?.name ? <Text style={styles.meta}>Chauffeur: {live.driver.name}</Text> : null}
        {live?.vehicle?.label || live?.vehicle?.license_plate ? (
          <Text style={styles.meta}>
            Voertuig: {live.vehicle.label || live.vehicle.license_plate}
          </Text>
        ) : null}
        {live?.company?.name ? <Text style={styles.meta}>Centrale: {live.company.name}</Text> : null}
        {live?.payment_error ? (
          <Text style={[styles.meta, { color: colors.danger }]}>{live.payment_error}</Text>
        ) : null}
      </Card>

      <Card>
        <Text style={styles.rowLabel}>Van</Text>
        <Text style={styles.rowValue}>{live?.pickup_address || pickup?.address || '—'}</Text>
        <Text style={[styles.rowLabel, { marginTop: 12 }]}>Naar</Text>
        <Text style={styles.rowValue}>{live?.dropoff_address || dropoff?.address || '—'}</Text>
        {live?.quoted_price != null ? (
          <>
            <Text style={[styles.rowLabel, { marginTop: 12 }]}>Prijs</Text>
            <Text style={styles.rowValue}>
              € {Number(live.quoted_price).toFixed(2).replace('.', ',')}
            </Text>
          </>
        ) : null}
      </Card>

      {live?.needs_unaccepted_decision ? (
        <Card>
          <Text style={styles.liveTitle}>Nog geen taxi gevonden</Text>
          <Text style={styles.meta}>
            Wil je blijven wachten
            {live.decision_minutes ? ` (tot ca. ${live.decision_deadline_label || '—'})` : ''} of
            annuleren?
          </Text>
          <PrimaryButton title="Blijven wachten" onPress={onWait} loading={busy} />
          <GhostButton title="Annuleren" onPress={onCancel} />
        </Card>
      ) : null}

      {live?.can_retry_payment ? (
        <PrimaryButton title="Nu betalen" onPress={onPay} loading={busy} />
      ) : null}

      {live?.can_cancel && !live?.needs_unaccepted_decision ? (
        <GhostButton title="Rit annuleren" onPress={onCancel} />
      ) : null}

      {live?.phase === 'cancelled' || live?.phase === 'completed' ? (
        <PrimaryButton title="Nieuwe rit" onPress={startNewRide} />
      ) : null}
    </>
  );

  const bookContent = (
    <>
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
                <Text style={styles.locIcon}>…</Text>
              ) : (
                <Text style={styles.locIcon}>📍</Text>
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
      </Card>

      {error ? <ErrorText>{error}</ErrorText> : null}
      {fieldErrors.dispatch ? (
        <Text style={styles.fieldErrorText}>{fieldErrors.dispatch}</Text>
      ) : null}
      <PrimaryButton title="Taxi aanvragen" onPress={onBook} loading={busy} />
    </>
  );

  const ridesContent = (
    <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
      <ErrorText>{error}</ErrorText>
      {live && step === 'live' ? (
        <Card>
          <PhasePill phase={live.phase} />
          <Text style={styles.liveTitle}>{live.status_label || 'Actieve rit'}</Text>
          <Text style={styles.meta}>{live.pickup_address || pickup?.address || '—'}</Text>
          <Text style={styles.meta}>→ {live.dropoff_address || dropoff?.address || '—'}</Text>
          <PrimaryButton title="Open live rit" onPress={() => setTab('book')} />
        </Card>
      ) : (
        <Card>
          <Text style={styles.meta}>
            Geen actieve rit. Boek een taxi via het tabblad Boeken. Eerdere ritten zie je na
            inloggen.
          </Text>
          <PrimaryButton title="Taxi boeken" onPress={() => setTab('book')} />
        </Card>
      )}
    </ScrollView>
  );

  const profileContent = (
    <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
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

      <GhostButton title="Terug naar start" onPress={onBack} />
    </ScrollView>
  );

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
                ref={bookScrollRef}
                style={styles.formScroll}
                contentContainerStyle={styles.scroll}
                keyboardShouldPersistTaps="handled"
                keyboardDismissMode="on-drag"
                automaticallyAdjustKeyboardInsets
                nestedScrollEnabled
                removeClippedSubviews={false}
              >
                {step === 'live' ? liveContent : bookContent}
              </ScrollView>
            )}
      </View>
      <CustomerTabBar
        active={tab}
        onChange={(key) => {
          setTab(key);
          AsyncStorage.setItem(TAB_KEY, key).catch(() => undefined);
          setError(null);
          setProfileSaved(false);
          Keyboard.dismiss();
        }}
        ridesBadge={live && step === 'live' ? 1 : 0}
      />
    </Screen>
  );
}

function PhasePill({ phase }: { phase?: string }) {
  const { colors } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
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
  return (
    <View style={[styles.pill, { backgroundColor: color + '33', borderColor: color }]}>
      <Text style={[styles.pillText, { color }]}>{label}</Text>
    </View>
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
    headerTitle: {
      textAlign: 'center',
      color: colors.text,
      fontSize: 18,
      fontWeight: '700',
      paddingHorizontal: 20,
      paddingTop: 4,
      paddingBottom: 6,
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
    },
    pill: {
      alignSelf: 'flex-start',
      borderWidth: 1,
      borderRadius: 999,
      paddingHorizontal: 10,
      paddingVertical: 4,
    },
    pillText: {
      fontSize: 12,
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
    locIcon: {
      fontSize: 18,
    },
  });
}
