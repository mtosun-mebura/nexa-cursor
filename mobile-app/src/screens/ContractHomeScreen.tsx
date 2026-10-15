import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  Alert,
  Animated,
  AppState,
  Linking,
  Pressable,
  RefreshControl,
  Image,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import MapView, { Marker, Polyline, PROVIDER_GOOGLE } from 'react-native-maps';
import { Ionicons } from '@expo/vector-icons';
import * as Location from 'expo-location';
import {
  ContractAbsence,
  ContractDayItem,
  ContractLeg,
  ContractNavStop,
  ContractPassenger,
  ContractToday,
  ContractWeek,
  boardContractPassenger,
  completeContractRide,
  destroyContractAbsence,
  fetchContractAbsences,
  fetchContractMe,
  fetchContractPassengers,
  fetchContractToday,
  fetchContractWeek,
  postContractLocation,
  reachContractDestinations,
  resetContractToday,
  skipContractPassenger,
  startContractRide,
  storeContractAbsence,
  updateContractAccent,
  updateContractPassengerColor,
} from '../api/contract';
import { buildActiveNavigation, buildSimulatorStops } from '../api/contractNavigation';
import { advanceAlong, lineStartingNear, pathAlongDisplayedLines, stopLabel } from '../contract/rideSimulator';
import { DARK_MAP_STYLE, bearingDegrees, fetchDrivingRoute, type LatLng } from '../geo/route';
import { apiErrorMessage, apiErrorStatus } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { playRideAlertTone } from '../audio/rideAlert';
import { API_BASE_URL, ColorPalette } from '../config';
import { AppBrandLogo, APP_LOGO_BAR_MIN_HEIGHT } from '../ui/AppBrandLogo';
import { APP_PAGE_TOP_INSET, APP_PAGE_HEADER_PAD_H } from '../ui/AppPageHeader';
import { AppModal } from '../ui/AppModal';
import { AppScrollView } from '../ui/AppScrollView';
import { Card, ErrorText, GhostButton } from '../ui/components';
import { ContractTabBar, ContractTabKey } from '../ui/ContractTabBar';
import { ScreenHeader } from '../ui/ScreenHeader';
import {
  DRIVER_ACCENT_OPTIONS,
  DriverAccentProvider,
  driverAccentHex,
  hexAlpha,
  normalizeDriverAccent,
} from '../theme/driverAccent';
import { ThemePreference, useTheme, useThemeColors } from '../theme/ThemeContext';
import {
  notifyNewContractRide,
  setupContractNotificationChannel,
} from '../notifications/contractRides';

function resolveMediaUrl(url?: string | null): string | null {
  const raw = String(url || '').trim();
  if (!raw) return null;
  if (/^https?:\/\//i.test(raw)) return raw;
  if (raw.startsWith('//')) return `https:${raw}`;
  if (raw.startsWith('/')) return `${API_BASE_URL}${raw}`;
  return `${API_BASE_URL}/${raw}`;
}

const ROLE_LABEL: Record<string, string> = {
  contractant: 'Contractant',
  contractouder: 'Contractouder',
};

const THEME_OPTIONS: { key: ThemePreference; label: string; hint: string }[] = [
  { key: 'system', label: 'Systeem', hint: 'Volgt de telefooninstelling' },
  { key: 'light', label: 'Licht', hint: 'Altijd lichte weergave' },
  { key: 'dark', label: 'Donker', hint: 'Altijd donkere weergave' },
];

const WEEKDAYS_SHORT = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

function toIsoDate(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function parseIsoDate(iso: string): Date {
  const [y, m, d] = iso.split('-').map((n) => Number(n));
  return new Date(y, (m || 1) - 1, d || 1);
}

function mondayIso(iso: string): string {
  const d = parseIsoDate(iso);
  const day = d.getDay();
  const diff = day === 0 ? -6 : 1 - day;
  d.setDate(d.getDate() + diff);
  return toIsoDate(d);
}

function addDaysIso(iso: string, days: number): string {
  const d = parseIsoDate(iso);
  d.setDate(d.getDate() + days);
  return toIsoDate(d);
}

function formatTime(iso?: string | null): string {
  if (!iso) return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '—';
  return d.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' });
}

function formatDayLong(iso: string): string {
  return parseIsoDate(iso).toLocaleDateString('nl-NL', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  });
}

function formatRangeLabel(from: string, to: string): string {
  const a = parseIsoDate(from);
  const b = parseIsoDate(to);
  const opts: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short' };
  return `${a.toLocaleDateString('nl-NL', opts)} – ${b.toLocaleDateString('nl-NL', opts)}`;
}

function shortAddress(address?: string | null): string {
  const text = String(address || '').trim();
  if (!text) return '—';
  const comma = text.indexOf(',');
  return comma > 0 ? text.slice(0, comma).trim() : text;
}

function splitAddress(address?: string | null): { main: string; sub: string } {
  const text = String(address || '').trim();
  if (!text) return { main: '—', sub: '' };
  const comma = text.indexOf(',');
  if (comma <= 0) return { main: text, sub: '' };
  return { main: text.slice(0, comma).trim(), sub: text.slice(comma + 1).trim() };
}

function legDisplayLabel(leg?: ContractLeg | null): string {
  const key = String(leg?.leg_key || '').toLowerCase();
  if (key === 'retour' || /terug|retour/i.test(String(leg?.leg_label || ''))) return 'Terugweg';
  if (key === 'heen' || /heen/i.test(String(leg?.leg_label || ''))) return 'Heenweg';
  return String(leg?.leg_label || 'Rit').trim() || 'Rit';
}

function isExpiredStatus(status?: string | null, statusKey?: string | null): boolean {
  const key = String(statusKey || '').toLowerCase();
  if (key === 'expired') return true;
  return /verlop/i.test(String(status || ''));
}

function isAbsentStatus(status?: string | null, statusKey?: string | null): boolean {
  const key = String(statusKey || '').toLowerCase();
  if (key === 'absent') return true;
  return /afwezig|afgemeld/i.test(String(status || ''));
}

/** Adres als stoptekst. Coördinaten toont Google Maps als “Met speld”. */
function mapsStopLabel(stop: ContractNavStop): string {
  return String(stop.address || '').trim();
}

/** Opent de route in de Google Maps-app. Adressen, geen coördinaten, anders staat er “Met speld”. */
async function openPassengerRoute(stops: ContractNavStop[]) {
  const points: string[] = [];
  for (const stop of stops) {
    const label = mapsStopLabel(stop);
    if (!label) continue;
    if (points[points.length - 1]?.toLowerCase() === label.toLowerCase()) continue;
    points.push(label);
  }
  if (points.length === 0) return;

  const segments = ['Current+Location', ...points.map((point) => encodeURIComponent(point))];
  await Linking.openURL(`https://www.google.com/maps/dir/${segments.join('/')}`);
}

const OVERDUE_RED = '#EF4444';
const DEST_GREEN = '#22C55E';
const ABSENT_GREY = '#94A3B8';

function placeTaxiOnLines(raw: LatLng, lines: LatLng[][]): LatLng {
  let best: LatLng | null = null;
  let bestDistance = Infinity;
  for (const line of lines) {
    const snapped = snapToRoute(raw, line);
    if (snapped === raw) continue;
    const distance = haversineMeters(raw, snapped);
    if (distance < bestDistance) {
      bestDistance = distance;
      best = snapped;
    }
  }
  return best ?? raw;
}

function itemHasVisibleRide(item: ContractDayItem): boolean {
  const key = item.day_status || item.status_key || '';
  if (key === 'none') return false;
  const legs = item.legs || [];
  if (legs.length > 0) return true;
  return key !== 'none';
}

const CONTRACT_TRIP_STATUS_KEY = 'nexa.contract.trip_status';
const CONTRACT_TRIPS_SEEN_KEY = 'nexa.contract.trips_seen';

/**
 * Alleen in-memory: tab blijft staan bij refresh/remount in dezelfde sessie.
 * Na uitloggen / nieuwe login altijd weer Ritten (niet Profiel).
 */
let lastContractTab: ContractTabKey = 'trips';

function persistContractTab(key: ContractTabKey) {
  lastContractTab = key;
}

function resetContractTabToTrips() {
  lastContractTab = 'trips';
  // Oude AsyncStorage-tab wissen zodat login nooit op Profiel opent.
  AsyncStorage.removeItem('nexa.contract.tab').catch(() => undefined);
}

type TripStatusFilter = 'all' | 'open' | 'expired';
const TRIP_STATUS_OPTIONS: { key: TripStatusFilter; label: string }[] = [
  { key: 'all', label: 'Alles' },
  { key: 'open', label: 'Openstaand' },
  { key: 'expired', label: 'Verlopen' },
];

function isTripStatusFilter(value: string | null): value is TripStatusFilter {
  return value === 'all' || value === 'open' || value === 'expired';
}

function itemLegs(item: ContractDayItem): ContractLeg[] {
  if (item.legs && item.legs.length > 0) return item.legs;
  return [
    {
      pickup_address: item.pickup_address,
      destination_address: item.destination_address,
      planned_at: item.planned_at,
      status: item.status,
      status_key: item.status_key,
      leg_key: 'heen',
      leg_label: 'Heen',
      can_cancel: item.can_cancel,
    },
  ];
}

function legIsExpired(leg: ContractLeg, _item?: ContractDayItem): boolean {
  // Alleen de leg zelf — day_status van een andere (verlopen) rit mag deze niet verbergen.
  return isExpiredStatus(leg.status, leg.status_key);
}

function legIsTerminal(leg: ContractLeg, item: ContractDayItem): boolean {
  const key = String(leg.status_key || item.status_key || item.day_status || '').toLowerCase();
  return key === 'completed' || key === 'absent' || key === 'not_taken';
}

function legStatusKey(leg: ContractLeg, item: ContractDayItem): string {
  return String(leg.status_key || item.status_key || item.day_status || '').toLowerCase();
}

const DEST_REACH_RADIUS_M = 250;

function distanceMeters(lat1: number, lng1: number, lat2: number, lng2: number): number {
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLng = ((lng2 - lng1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) * Math.sin(dLng / 2) ** 2;
  return 2 * 6371000 * Math.asin(Math.min(1, Math.sqrt(a)));
}

/** Open bestemmingen met coördinaten, zodat bevestigen lokaal kan zien of de auto er staat. */
function openDestinationCoords(items: ContractDayItem[]): Array<{ lat: number; lng: number }> {
  const closed = new Set(['completed', 'absent', 'skipped', 'not_taken', 'none', 'expired']);
  const points: Array<{ lat: number; lng: number }> = [];
  for (const item of items) {
    for (const leg of itemLegs(item)) {
      const key = String(leg.status_key || '').toLowerCase();
      if (closed.has(key) || legIsExpired(leg) || legIsTerminal(leg, item)) continue;
      const lat = Number(leg.destination_lat);
      const lng = Number(leg.destination_lng);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) continue;
      points.push({ lat, lng });
    }
  }
  return points;
}

const CHILD_MAP_COLORS = [
  '#2563EB',
  '#F59E0B',
  '#10B981',
  '#EC4899',
  '#7C3AED',
  '#EF4444',
  '#06B6D4',
  '#F97316',
] as const;

const CHILD_COLOR_LABELS = ['Blauw', 'Amber', 'Groen', 'Roze', 'Paars', 'Rood', 'Cyaan', 'Oranje'];

const CHILD_RIDE_SKIP = new Set(['absent', 'not_taken', 'expired', 'none', 'skipped']);
const CHILD_RIDE_LIVE = new Set(['picked_up', 'en_route', 'arrived']);

function childMapColor(passengerId: number, stored?: string | null): string {
  const raw = String(stored || '').trim();
  if (/^#[0-9A-Fa-f]{6}$/.test(raw)) return raw.toUpperCase();
  return CHILD_MAP_COLORS[Math.abs(passengerId) % CHILD_MAP_COLORS.length];
}

function mapPoint(lat?: number | null, lng?: number | null): LatLng | null {
  const latitude = Number(lat);
  const longitude = Number(lng);
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null;
  if (latitude === 0 && longitude === 0) return null;
  return { latitude, longitude };
}

function passengerGivenName(name?: string | null, firstName?: string | null): string {
  const given = String(firstName || '').trim();
  if (given) return given;
  const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
  if (parts.length < 2) return parts[0] || 'Kind';
  return parts.slice(0, -1).join(' ');
}

type ChildMapRide = {
  passengerId: number;
  name: string;
  givenName: string;
  color: string;
  inTaxi: boolean;
  pickedUp: boolean;
  arrived: boolean;
  pickup: LatLng | null;
  dropoff: LatLng | null;
  vehicle: LatLng | null;
};

function childMapRides(items: ContractDayItem[], includeExpired = false): ChildMapRide[] {
  const skip = includeExpired
    ? new Set([...CHILD_RIDE_SKIP].filter((key) => key !== 'expired'))
    : CHILD_RIDE_SKIP;
  const rides: ChildMapRide[] = [];
  for (const item of items) {
    const legs = itemLegs(item).filter((leg) => {
      if (!skip.has(legStatusKey(leg, item))) return true;
      const lat = Number(leg.vehicle_location?.lat);
      const lng = Number(leg.vehicle_location?.lng);
      return Number.isFinite(lat) && Number.isFinite(lng);
    });
    if (legs.length === 0) continue;
    const leg = legs.find((row) => CHILD_RIDE_LIVE.has(legStatusKey(row, item))) || legs[0];
    const key = legStatusKey(leg, item);
    const arrived = key === 'completed' || Boolean(leg.destination_reached);
    rides.push({
      passengerId: item.passenger_id,
      name: item.name || 'Kind',
      givenName: passengerGivenName(item.name, item.first_name),
      color: childMapColor(item.passenger_id, item.map_color),
      inTaxi: key === 'picked_up',
      pickedUp: key === 'picked_up' || arrived,
      arrived,
      pickup: mapPoint(leg.pickup_lat ?? item.pickup_lat, leg.pickup_lng ?? item.pickup_lng),
      dropoff: mapPoint(leg.destination_lat, leg.destination_lng),
      vehicle: mapPoint(leg.vehicle_location?.lat, leg.vehicle_location?.lng),
    });
  }
  return rides;
}

function haversineMeters(a: LatLng, b: LatLng): number {
  const dLat = ((b.latitude - a.latitude) * Math.PI) / 180;
  const dLng = ((b.longitude - a.longitude) * Math.PI) / 180;
  const lat1 = (a.latitude * Math.PI) / 180;
  const lat2 = (b.latitude * Math.PI) / 180;
  const h =
    Math.sin(dLat / 2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
  return 2 * 6371000 * Math.asin(Math.min(1, Math.sqrt(h)));
}

function projectSegment(point: LatLng, start: LatLng, end: LatLng): LatLng {
  const abLat = end.latitude - start.latitude;
  const abLng = end.longitude - start.longitude;
  const denom = abLat * abLat + abLng * abLng;
  if (denom === 0) return start;
  const t = Math.max(
    0,
    Math.min(1, ((point.latitude - start.latitude) * abLat + (point.longitude - start.longitude) * abLng) / denom)
  );
  return { latitude: start.latitude + t * abLat, longitude: start.longitude + t * abLng };
}

/** Zet de taxi op de ritlijn als de gps daar dichtbij ligt. */
function snapToRoute(point: LatLng, line: LatLng[], maxMeters = 400): LatLng {
  if (line.length < 2) return point;
  let best = line[0];
  let bestDistance = Infinity;
  for (let i = 0; i < line.length - 1; i += 1) {
    const projected = projectSegment(point, line[i], line[i + 1]);
    const distance = haversineMeters(point, projected);
    if (distance < bestDistance) {
      bestDistance = distance;
      best = projected;
    }
  }
  return bestDistance <= maxMeters ? best : point;
}

function FluidTaxiMarker({
  target,
  mapRef,
  imageStyle,
  followRef,
}: {
  target: LatLng;
  mapRef: React.RefObject<MapView | null>;
  imageStyle: React.ComponentProps<typeof Image>['style'];
  followRef: React.RefObject<boolean>;
}) {
  const [coord, setCoord] = useState(target);
  const coordRef = useRef(target);
  const hasTarget = useRef(false);
  const lastAt = useRef(0);
  const [trackView, setTrackView] = useState(true);
  const [heading, setHeading] = useState(0);

  useEffect(() => {
    setTrackView(true);
    const timer = setTimeout(() => setTrackView(false), 250);
    return () => clearTimeout(timer);
  }, [heading]);

  useEffect(() => {
    const from = coordRef.current;
    const now = Date.now();
    const gap = lastAt.current ? now - lastAt.current : 0;
    lastAt.current = now;
    const dist = haversineMeters(from, target);
    if (dist >= 4) {
      const deg = bearingDegrees(
        { lat: from.latitude, lng: from.longitude },
        { lat: target.latitude, lng: target.longitude }
      );
      setHeading((deg + 360) % 360);
    }
    const snap = !hasTarget.current || dist < 3 || dist > 800 || gap <= 0;
    hasTarget.current = true;
    if (snap) {
      coordRef.current = target;
      setCoord(target);
      if (dist > 800 && followRef.current) {
        mapRef.current?.animateCamera({ center: target, zoom: 15 }, { duration: 500 });
      }
      return;
    }
    const duration = Math.max(200, gap);
    const start = Date.now();
    let frame = 0;
    const tick = () => {
      const t = Math.min(1, (Date.now() - start) / duration);
      const next = {
        latitude: from.latitude + (target.latitude - from.latitude) * t,
        longitude: from.longitude + (target.longitude - from.longitude) * t,
      };
      coordRef.current = next;
      setCoord(next);
      if (t < 1) frame = requestAnimationFrame(tick);
    };
    if (followRef.current) {
      mapRef.current?.animateCamera({ center: target, zoom: 15 }, { duration });
    }
    frame = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(frame);
  }, [target.latitude, target.longitude, mapRef, followRef]);

  return (
    <Marker
      coordinate={coord}
      title="Taxi"
      anchor={{ x: 0.5, y: 0.5 }}
      tracksViewChanges={trackView}
      zIndex={50}
    >
      <View style={taxiMarkerFrame} collapsable={false}>
        <Image
          source={taxiCarYellow}
          style={[imageStyle, { transform: [{ rotate: `${heading}deg` }] }]}
          resizeMode="contain"
        />
      </View>
    </Marker>
  );
}

function moveMeters(point: LatLng, northMeters: number, eastMeters: number): LatLng {
  const latRad = (point.latitude * Math.PI) / 180;
  return {
    latitude: point.latitude + northMeters / 111320,
    longitude: point.longitude + eastMeters / (111320 * Math.cos(latRad)),
  };
}

/**
 * Namen in een kolom. De tussenruimte blijft op het scherm ongeveer gelijk,
 * maar wordt nooit groter dan de afstand die bij uitzoomen goed leest.
 */
function stackPoint(point: LatLng, index: number, metersPerPixel: number): LatLng {
  const gap = Math.min(280, 34 * metersPerPixel);
  const start = Math.min(150, 28 * metersPerPixel);
  const side = Math.min(90, 16 * metersPerPixel);
  return moveMeters(point, start + index * gap, side);
}

const taxiCarYellow = require('../../assets/taxi-car-yellow.png');
/** Vierkant ruim genoeg voor de auto in elke draai, anders knipt de marker neus en kont af. */
const taxiMarkerFrame = {
  width: 56,
  height: 56,
  alignItems: 'center' as const,
  justifyContent: 'center' as const,
};

/** Banner voor ouders/contractant — gebruikt API-tekst of lokale fallback. */
function legStatusBanner(leg: ContractLeg, item: ContractDayItem): string {
  const api = String(leg.status_banner || '').trim();
  if (api) return api;
  const key = legStatusKey(leg, item);
  if (key === 'absent') return 'Afgemeld';
  if (key === 'not_taken') return 'Niet meegenomen';
  if (key === 'picked_up') return 'Opgehaald · onderweg';
  if (key === 'completed') return 'Gearriveerd';
  if (key === 'arrived') return 'Chauffeur ter plaatse';
  if (key === 'en_route') return 'Chauffeur onderweg';
  if (key === 'expired') return 'Ophaalmoment verlopen';
  if (key === 'planned') return 'Nog niet opgehaald';
  return String(leg.status || item.status || 'Gepland');
}

function PulsingIcon({
  name,
  color,
  size,
  pulseToken,
}: {
  name: React.ComponentProps<typeof Ionicons>['name'];
  color: string;
  size: number;
  pulseToken: number;
}) {
  const scale = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    if (!pulseToken) return;
    scale.setValue(1);
    const beat = Animated.sequence([
      Animated.timing(scale, { toValue: 1.45, duration: 180, useNativeDriver: true }),
      Animated.timing(scale, { toValue: 1, duration: 220, useNativeDriver: true }),
    ]);
    const anim = Animated.loop(beat, { iterations: 5 });
    anim.start();
    return () => anim.stop();
  }, [pulseToken, scale]);

  return (
    <Animated.View style={{ transform: [{ scale }] }}>
      <Ionicons name={name} size={size} color={color} />
    </Animated.View>
  );
}

function bannerTone(
  key: string
): 'neutral' | 'info' | 'success' | 'warn' | 'danger' | 'muted' {
  if (key === 'completed' || key === 'picked_up') return 'success';
  if (key === 'en_route' || key === 'arrived') return 'info';
  if (key === 'expired') return 'warn';
  if (key === 'absent' || key === 'not_taken') return 'muted';
  return 'neutral';
}

function legCanBoard(leg: ContractLeg, item: ContractDayItem, canOperate: boolean): boolean {
  if (!canOperate || !leg.ride_stop_id || legIsTerminal(leg, item)) return false;
  if (leg.can_board === true) return true;
  const key = legStatusKey(leg, item);
  if (key === 'picked_up' || key === 'completed' || key === 'not_taken') return false;
  const rideStatus = String(leg.ride_status || '').toLowerCase();
  if (rideStatus === 'completed' || rideStatus === 'cancelled') return false;
  return (
    rideStatus === '' ||
    rideStatus === 'assigned' ||
    rideStatus === 'accepted' ||
    rideStatus === 'offered' ||
    rideStatus === 'pending_dispatch'
  );
}

function legMatchesStatusFilter(
  leg: ContractLeg,
  item: ContractDayItem,
  filter: TripStatusFilter
): boolean {
  if (filter === 'all') return true;
  const key = legStatusKey(leg, item);
  const arrived = key === 'completed' || Boolean(leg.destination_reached);
  const expired = legIsExpired(leg, item);
  const terminal = legIsTerminal(leg, item);
  if (key === 'absent' || key === 'not_taken') return false;
  if (filter === 'expired') return expired || arrived;
  return !expired && !terminal && !arrived;
}

function actionableLegKey(item: ContractDayItem, leg: ContractLeg, date?: string): string {
  const stop = leg.ride_stop_id != null ? String(leg.ride_stop_id) : String(leg.leg_key || 'heen');
  return `${date || ''}:${item.passenger_id}:${stop}`;
}

function isActionableLeg(leg: ContractLeg, item: ContractDayItem): boolean {
  return !legIsTerminal(leg, item);
}

function isContractantRole(role?: string | null, flag?: boolean | null): boolean {
  if (flag === true) return true;
  return String(role || '').toLowerCase() === 'contractant';
}

/** Toon start/afronden voor contractant op openstaande legs (API-flags + lokale fallback). */
function legCanStart(
  leg: ContractLeg,
  item: ContractDayItem,
  canOperate: boolean
): boolean {
  if (!canOperate || !leg.ride_stop_id || legIsTerminal(leg, item)) return false;
  if (leg.can_start === true) return true;
  const rideStatus = String(leg.ride_status || '').toLowerCase();
  if (rideStatus === 'assigned' || rideStatus === 'completed' || rideStatus === 'cancelled') {
    return false;
  }
  // accepted / offered / pending of onbekend (oudere API): starten tonen
  return (
    rideStatus === '' ||
    rideStatus === 'accepted' ||
    rideStatus === 'offered' ||
    rideStatus === 'pending_dispatch'
  );
}

function legCanComplete(
  leg: ContractLeg,
  item: ContractDayItem,
  canOperate: boolean
): boolean {
  if (!canOperate || !leg.ride_stop_id || legIsTerminal(leg, item)) return false;
  if (leg.can_complete === true) return true;
  const rideStatus = String(leg.ride_status || '').toLowerCase();
  if (rideStatus === 'completed' || rideStatus === 'cancelled') return false;
  return (
    rideStatus === '' ||
    rideStatus === 'assigned' ||
    rideStatus === 'accepted' ||
    rideStatus === 'offered' ||
    rideStatus === 'pending_dispatch'
  );
}

function anyPassengerPickedUp(items: ContractDayItem[]): boolean {
  for (const item of items) {
    if (item.picked_up) return true;
    for (const leg of item.legs || []) {
      if (leg.picked_up) return true;
    }
  }
  return false;
}

export function ContractHomeScreen() {
  const colors = useThemeColors();
  const { colorScheme, preference, setPreference } = useTheme();
  const { session, contractToken, logout, setActiveScreen, capabilities } = useAuth();
  const [tab, setTab] = useState<ContractTabKey>(() => lastContractTab);
  const [planningView, setPlanningView] = useState<'day' | 'week'>('day');
  const [tripStatusFilter, setTripStatusFilter] = useState<TripStatusFilter>('all');
  const [tripsSeenKeys, setTripsSeenKeys] = useState<string[]>([]);
  const [name, setName] = useState(session?.user?.name || '');
  const [email, setEmail] = useState(session?.user?.email || '');
  const [customerName, setCustomerName] = useState('');
  const [logoLight, setLogoLight] = useState<string | null>(null);
  const [logoDark, setLogoDark] = useState<string | null>(null);
  const [portalRole, setPortalRole] = useState<string | null>(null);
  const [isContractant, setIsContractant] = useState(false);
  const [isDemo, setIsDemo] = useState(false);
  const [simRunning, setSimRunning] = useState(false);
  const [simResetOpen, setSimResetOpen] = useState(false);
  const [simResetBusy, setSimResetBusy] = useState(false);
  const [simResetError, setSimResetError] = useState<string | null>(null);
  const [simSpeed, setSimSpeed] = useState(60);
  const [simLabel, setSimLabel] = useState('De chauffeur rijdt de haltes af.');
  const [simExtraLines, setSimExtraLines] = useState<LatLng[][]>([]);
  const simActiveRef = useRef(false);
  const simSpeedRef = useRef(60);
  const simTrackWidth = useRef(1);
  const simSourceRef = useRef<ContractDayItem[]>([]);
  const childRouteLinesRef = useRef<Record<number, LatLng[]>>({});
  const [accent, setAccent] = useState('orange');
  const [error, setError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [today, setToday] = useState<ContractToday | null>(null);
  const [week, setWeek] = useState<ContractWeek | null>(null);
  const [weekFrom, setWeekFrom] = useState(() => mondayIso(toIsoDate(new Date())));
  const [selectedDate, setSelectedDate] = useState(() => toIsoDate(new Date()));
  const [passengers, setPassengers] = useState<ContractPassenger[]>([]);
  const [absences, setAbsences] = useState<ContractAbsence[]>([]);
  const [absenceOpen, setAbsenceOpen] = useState(false);
  const [absencePassengerId, setAbsencePassengerId] = useState<number | null>(null);
  const [absenceFrom, setAbsenceFrom] = useState(() => toIsoDate(new Date()));
  const [absenceTo, setAbsenceTo] = useState(() => toIsoDate(new Date()));
  const [absenceReason, setAbsenceReason] = useState('');
  const [absenceBusy, setAbsenceBusy] = useState(false);
  const [rideActionStopId, setRideActionStopId] = useState<number | null>(null);
  const [destReachOpen, setDestReachOpen] = useState(false);
  const [destReachBusy, setDestReachBusy] = useState(false);
  const [destReachError, setDestReachError] = useState<string | null>(null);
  const ignoreDestOpenUntil = useRef(0);
  const [childRouteLines, setChildRouteLines] = useState<Record<number, LatLng[]>>({});
  const [colorPassengerId, setColorPassengerId] = useState<number | null>(null);
  const [colorOverrides, setColorOverrides] = useState<Record<number, string>>({});
  const [colorError, setColorError] = useState<string | null>(null);
  const [expandedRideKeys, setExpandedRideKeys] = useState<Record<string, true>>({});
  const [iconPulse, setIconPulse] = useState<Record<string, number>>({});
  const [destBtnPulse, setDestBtnPulse] = useState(0);
  const destBtnScale = useRef(new Animated.Value(1)).current;
  const statusSeenRef = useRef<Map<string, string> | null>(null);
  const multi = (capabilities?.screens?.length || 0) > 1;
  const canOperateRides = isContractant || isContractantRole(portalRole);
  const accentHex = driverAccentHex(accent);
  const styles = useMemo(() => makeStyles(colors, accentHex), [colors, accentHex]);
  const logoUri =
    resolveMediaUrl(colorScheme === 'light' ? logoLight || logoDark : logoDark || logoLight) ||
    null;

  const refreshBusyRef = useRef(false);
  const todayPollBusyRef = useRef(false);
  const weekFromRef = useRef(weekFrom);
  weekFromRef.current = weekFrom;
  const seenRideIds = useRef<Set<number>>(new Set());
  const ridesBootstrapped = useRef(false);
  const hasLoadedOnceRef = useRef(false);
  const lastFixRef = useRef<{ lat: number; lng: number } | null>(null);
  const driverGpsRef = useRef<{ lat: number; lng: number } | null>(null);
  const [driverFix, setDriverFix] = useState<LatLng | null>(null);
  const [followTaxi, setFollowTaxi] = useState(true);
  const followTaxiRef = useRef(true);
  const [pinScale, setPinScale] = useState({ latitudeDelta: 0.08, height: 320 });
  const prevTaxiRef = useRef<LatLng | null>(null);
  const [taxiHeading, setTaxiHeading] = useState(0);
  const [trackTaxiHeading, setTrackTaxiHeading] = useState(true);
  const lastLocationPostRef = useRef(0);
  const parentMapRef = useRef<MapView | null>(null);
  const parentMapFittedKey = useRef('');

  const handleNewContractRides = useCallback(async (todayData: ContractToday | null) => {
    const openLegs: ContractLeg[] = [];
    for (const item of todayData?.items || []) {
      for (const leg of itemLegs(item)) {
        if (legIsTerminal(leg, item)) continue;
        openLegs.push(leg);
      }
    }
    const ids = openLegs
      .map((leg) => Number(leg.ride_request_id || leg.ride_stop_id || 0))
      .filter((id) => id > 0);

    if (!ridesBootstrapped.current) {
      ids.forEach((id) => seenRideIds.current.add(id));
      ridesBootstrapped.current = true;
      return;
    }

    const fresh = openLegs.filter((leg) => {
      const id = Number(leg.ride_request_id || leg.ride_stop_id || 0);
      return id > 0 && !seenRideIds.current.has(id);
    });
    if (!fresh.length) return;

    fresh.forEach((leg) => {
      const id = Number(leg.ride_request_id || leg.ride_stop_id || 0);
      if (id > 0) seenRideIds.current.add(id);
    });

    setTab('trips');
    persistContractTab('trips');
    const priority = fresh[0];
    const foreground = AppState.currentState === 'active';
    await notifyNewContractRide({
      count: fresh.length,
      pickup: priority.pickup_address,
      dropoff: priority.destination_address,
      playSound: !foreground,
    });
  }, []);

  const applyTodayData = useCallback(
    async (todayData: ContractToday | null) => {
      setToday(todayData);
      try {
        await handleNewContractRides(todayData);
      } catch {
        /* push mag refresh niet breken */
      }
    },
    [handleNewContractRides]
  );

  /** Achtergrondpoll: alleen today — stil bij netwerk/429. */
  const pollToday = useCallback(async () => {
    if (!contractToken || todayPollBusyRef.current || refreshBusyRef.current) return;
    todayPollBusyRef.current = true;
    try {
      const todayRes = await fetchContractToday(contractToken);
      await applyTodayData(todayRes.data || null);
      if (hasLoadedOnceRef.current) setError(null);
    } catch (e) {
      const status = apiErrorStatus(e);
      if (status === 429 || status === 401) return;
      // Geen rode banner tijdens achtergrondpoll — pull-to-refresh toont wél fouten.
    } finally {
      todayPollBusyRef.current = false;
    }
  }, [contractToken, applyTodayData]);

  const refresh = useCallback(
    async (fromOverride?: string, opts?: { silent?: boolean }) => {
      if (!contractToken || refreshBusyRef.current) return;
      refreshBusyRef.current = true;
      const silent = opts?.silent === true;
      const from = fromOverride || weekFromRef.current;
      try {
        const results = await Promise.allSettled([
          fetchContractMe(contractToken),
          fetchContractToday(contractToken),
          fetchContractWeek(contractToken, from),
          fetchContractPassengers(contractToken),
          fetchContractAbsences(contractToken),
        ]);

        const [meR, todayR, weekR, passengersR, absencesR] = results;
        const rejected = results.find((r) => r.status === 'rejected') as
          | PromiseRejectedResult
          | undefined;
        const softFail =
          rejected &&
          (apiErrorStatus(rejected.reason) === 429 || apiErrorStatus(rejected.reason) === 401);

        const me = meR.status === 'fulfilled' ? meR.value : null;
        const todayData =
          todayR.status === 'fulfilled' ? todayR.value.data || null : null;

        if (me) {
          setName(me.user?.name || session?.user?.name || '');
          setEmail(me.user?.email || session?.user?.email || '');
          setLogoLight(me.user?.company_logo_url || null);
          setLogoDark(me.user?.company_logo_dark_url || null);
          setPortalRole(me.user?.portal_role || null);
          setIsContractant(
            isContractantRole(me.user?.portal_role, me.user?.is_contractant)
          );
          setIsDemo(me.user?.is_demo === true);
          if (me.user?.pwa_accent) setAccent(normalizeDriverAccent(me.user.pwa_accent));
        }

        if (me || todayData) {
          setCustomerName(
            me?.user?.company_name || todayData?.customer_name || ''
          );
        }

        if (todayR.status === 'fulfilled') {
          await applyTodayData(todayData);
        }

        if (weekR.status === 'fulfilled') {
          setWeek(weekR.value.data || null);
          if (weekR.value.data?.from && weekR.value.data.from !== weekFromRef.current) {
            setWeekFrom(weekR.value.data.from);
          }
        }

        if (passengersR.status === 'fulfilled') {
          setPassengers(passengersR.value.data?.passengers || []);
        }
        if (absencesR.status === 'fulfilled') {
          setAbsences(absencesR.value.data?.absences || []);
        }

        const anyOk = results.some((r) => r.status === 'fulfilled');
        if (anyOk) {
          hasLoadedOnceRef.current = true;
          setError(null);
        }

        if (rejected && !softFail && !silent && !anyOk) {
          setError(
            apiErrorMessage(rejected.reason, 'Kon contractgegevens niet laden.')
          );
        } else if (rejected && !softFail && !silent && !hasLoadedOnceRef.current) {
          setError(
            apiErrorMessage(rejected.reason, 'Kon contractgegevens niet laden.')
          );
        }
      } catch (e) {
        const status = apiErrorStatus(e);
        if (status === 429 || status === 401 || silent) return;
        if (!hasLoadedOnceRef.current) {
          setError(apiErrorMessage(e, 'Kon contractgegevens niet laden.'));
        }
      } finally {
        refreshBusyRef.current = false;
      }
    },
    [contractToken, session?.user?.name, session?.user?.email, applyTodayData]
  );

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const [savedFilter, savedSeen] = await Promise.all([
          AsyncStorage.getItem(CONTRACT_TRIP_STATUS_KEY),
          AsyncStorage.getItem(CONTRACT_TRIPS_SEEN_KEY),
        ]);
        if (cancelled) return;
        if (isTripStatusFilter(savedFilter)) setTripStatusFilter(savedFilter);
        if (savedSeen) {
          try {
            const parsed = JSON.parse(savedSeen) as unknown;
            if (Array.isArray(parsed)) {
              setTripsSeenKeys(parsed.filter((k): k is string => typeof k === 'string'));
            }
          } catch {
            /* ignore */
          }
        }
      } catch {
        /* ignore */
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    setupContractNotificationChannel().catch(() => undefined);
  }, []);

  useEffect(() => {
    seenRideIds.current.clear();
    ridesBootstrapped.current = false;
    hasLoadedOnceRef.current = false;
  }, [contractToken]);

  // Today snel; rest minder vaak — voorkomt rode fouten door concurrente poll-fails.
  useEffect(() => {
    if (!contractToken) return;
    void refresh(undefined, { silent: false });
    const todayTimer = setInterval(() => {
      if (AppState.currentState !== 'active') return;
      void pollToday();
    }, 2500);
    const fullTimer = setInterval(() => {
      if (AppState.currentState !== 'active') return;
      void refresh(undefined, { silent: true });
    }, 20000);
    return () => {
      clearInterval(todayTimer);
      clearInterval(fullTimer);
    };
  }, [contractToken, refresh, pollToday]);

  // Ouder op de kaart: vaker de taxi-positie ophalen, zodat de auto kan doorrijden.
  useEffect(() => {
    if (!contractToken || canOperateRides || tab !== 'navigation') return;
    const timer = setInterval(() => {
      if (AppState.currentState !== 'active') return;
      void pollToday();
    }, 600);
    return () => clearInterval(timer);
  }, [contractToken, canOperateRides, tab, pollToday]);

  useEffect(() => {
    if (!canOperateRides || !contractToken) return;
    let stopped = false;
    let sub: Location.LocationSubscription | null = null;

    const remember = (lat: number, lng: number, post: boolean) => {
      if (stopped || !Number.isFinite(lat) || !Number.isFinite(lng)) return;
      driverGpsRef.current = { lat, lng };
      if (simActiveRef.current) return;
      lastFixRef.current = { lat, lng };
      setDriverFix({ latitude: lat, longitude: lng });
      if (!post) return;
      const now = Date.now();
      if (now - lastLocationPostRef.current < 15000) return;
      lastLocationPostRef.current = now;
      void postContractLocation(contractToken, lat, lng)
        .then((res) => {
          if ((res.data?.updated || 0) > 0) void pollToday();
        })
        .catch(() => undefined);
    };

    (async () => {
      const perm = await Location.requestForegroundPermissionsAsync();
      if (stopped || perm.status !== 'granted') return;

      const last = await Location.getLastKnownPositionAsync();
      if (last) remember(last.coords.latitude, last.coords.longitude, false);

      sub = await Location.watchPositionAsync(
        {
          accuracy: Location.Accuracy.Balanced,
          distanceInterval: 8,
          timeInterval: 3000,
        },
        (pos) => remember(pos.coords.latitude, pos.coords.longitude, true)
      );

      Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced })
        .then((current) => remember(current.coords.latitude, current.coords.longitude, true))
        .catch(() => undefined);
    })().catch(() => undefined);

    return () => {
      stopped = true;
      sub?.remove();
    };
  }, [canOperateRides, contractToken, pollToday]);

  useEffect(() => {
    const sub = AppState.addEventListener('change', (state) => {
      if (state === 'active') void refresh(undefined, { silent: true });
    });
    return () => sub.remove();
  }, [refresh]);

  async function loadWeek(from: string) {
    setWeekFrom(from);
    await refresh(from, { silent: false });
  }

  async function onRefresh() {
    setRefreshing(true);
    await refresh(undefined, { silent: false });
    setRefreshing(false);
  }

  function openAbsence(passengerId: number, date?: string) {
    const day = date || toIsoDate(new Date());
    setAbsencePassengerId(passengerId);
    setAbsenceFrom(day);
    setAbsenceTo(day);
    setAbsenceReason('');
    setAbsenceOpen(true);
  }

  async function submitAbsence() {
    if (!contractToken || !absencePassengerId) return;
    setAbsenceBusy(true);
    setError(null);
    try {
      await storeContractAbsence(contractToken, absencePassengerId, {
        date_from: absenceFrom,
        date_to: absenceTo || absenceFrom,
        reason: absenceReason.trim() || undefined,
      });
      setAbsenceOpen(false);
      await refresh();
    } catch (e) {
      setError(apiErrorMessage(e, 'Afmelden mislukt.'));
    } finally {
      setAbsenceBusy(false);
    }
  }

  async function cancelAbsence(id: number) {
    if (!contractToken) return;
    Alert.alert('Afmelding intrekken', 'Weet je zeker dat je deze afmelding wilt intrekken?', [
      { text: 'Nee', style: 'cancel' },
      {
        text: 'Intrekken',
        style: 'destructive',
        onPress: async () => {
          try {
            await destroyContractAbsence(contractToken, id);
            await refresh();
          } catch (e) {
            setError(apiErrorMessage(e, 'Intrekken mislukt.'));
          }
        },
      },
    ]);
  }

  async function onStartLeg(rideStopId?: number) {
    if (!contractToken || !rideStopId) return;
    setRideActionStopId(rideStopId);
    setError(null);
    try {
      await startContractRide(contractToken, rideStopId);
      await refresh();
    } catch (e) {
      setError(apiErrorMessage(e, 'Rit starten mislukt.'));
    } finally {
      setRideActionStopId(null);
    }
  }

  async function runRideAction(
    rideStopId: number,
    action: () => Promise<unknown>,
    failMessage: string
  ) {
    setRideActionStopId(rideStopId);
    setError(null);
    try {
      await action();
      await refresh();
    } catch (e) {
      setError(apiErrorMessage(e, failMessage));
    } finally {
      setRideActionStopId(null);
    }
  }

  async function onBoardLeg(rideStopId?: number) {
    if (!contractToken || !rideStopId) return;
    await runRideAction(
      rideStopId,
      () => boardContractPassenger(contractToken, rideStopId),
      'Ophalen bevestigen mislukt.'
    );
  }

  function onSkipLeg(rideStopId?: number, passengerName?: string) {
    if (!contractToken || !rideStopId) return;
    Alert.alert(
      'Niet meegenomen',
      `${passengerName || 'Deze passagier'} is niet meegenomen. Doorgaan?`,
      [
        { text: 'Annuleren', style: 'cancel' },
        {
          text: 'Bevestigen',
          style: 'destructive',
          onPress: () => {
            void runRideAction(
              rideStopId,
              () => skipContractPassenger(contractToken, rideStopId),
              'Overslaan mislukt.'
            );
          },
        },
      ]
    );
  }

  function onCompleteLeg(rideStopId?: number, leg?: ContractLeg, item?: ContractDayItem) {
    if (!contractToken || !rideStopId) return;
    const needsBoard =
      !!leg &&
      !!item &&
      legCanBoard(leg, item, canOperateRides) &&
      !['picked_up', 'completed', 'not_taken'].includes(legStatusKey(leg, item));

    if (needsBoard) {
      Alert.alert(
        'Bevestig ophalen',
        `${item?.name || 'Passagier'} is nog niet gemarkeerd. Is deze persoon opgehaald?`,
        [
          { text: 'Annuleren', style: 'cancel' },
          {
            text: 'Niet meegenomen',
            style: 'destructive',
            onPress: () => onSkipLeg(rideStopId, item?.name),
          },
          {
            text: 'Opgehaald → afronden',
            style: 'default',
            onPress: () => {
              void (async () => {
                setRideActionStopId(rideStopId);
                setError(null);
                try {
                  await boardContractPassenger(contractToken, rideStopId);
                  await completeContractRide(contractToken, rideStopId);
                  await refresh();
                } catch (e) {
                  setError(apiErrorMessage(e, 'Afronden mislukt.'));
                } finally {
                  setRideActionStopId(null);
                }
              })();
            },
          },
        ]
      );
      return;
    }

    Alert.alert('Bestemming bereikt', 'Bevestig dat de passagier is afgezet.', [
      { text: 'Nee', style: 'cancel' },
      {
        text: 'Afronden',
        style: 'default',
        onPress: () => {
          void runRideAction(
            rideStopId,
            () => completeContractRide(contractToken, rideStopId),
            'Rit afronden mislukt.'
          );
        },
      },
    ]);
  }

  function openDestReach() {
    if (Date.now() < ignoreDestOpenUntil.current) return;
    setDestReachError(null);
    bumpPulses(destinationPulseKeys());
    setDestBtnPulse((n) => n + 1);
    setDestReachOpen(true);
  }

  function closeDestReach() {
    if (destReachBusy) return;
    ignoreDestOpenUntil.current = Date.now() + 700;
    setDestReachOpen(false);
  }

  async function confirmReachAllDestinations() {
    if (!contractToken || destReachBusy) return;
    setDestReachBusy(true);
    setDestReachError(null);
    try {
      const fix = lastFixRef.current;
      if (!fix) {
        setDestReachError(
          'Je locatie is niet bekend. De bestemming kan niet op bereikt gezet worden.'
        );
        return;
      }
      const points = openDestinationCoords(todayItems);
      const near = points.some(
        (point) => distanceMeters(fix.lat, fix.lng, point.lat, point.lng) <= DEST_REACH_RADIUS_M
      );
      if (points.length > 0 && !near) {
        setDestReachError(
          'Je staat niet bij de bestemming. De bestemming kan niet op bereikt gezet worden.'
        );
        return;
      }
      const res = await reachContractDestinations(contractToken, fix);
      if ((res.data?.updated || 0) < 1) {
        setDestReachError(res.message || 'De bestemming kan niet op bereikt gezet worden.');
        return;
      }
      bumpPulses(destinationPulseKeys());
      setDestBtnPulse((n) => n + 1);
      ignoreDestOpenUntil.current = Date.now() + 700;
      setDestReachOpen(false);
      await refresh();
    } catch (e) {
      setDestReachError(apiErrorMessage(e, 'Bestemming bijwerken mislukt.'));
    } finally {
      setDestReachBusy(false);
    }
  }

  async function chooseAccent(key: string) {
    const next = normalizeDriverAccent(key);
    setAccent(next);
    if (!contractToken) return;
    try {
      await updateContractAccent(contractToken, next);
    } catch {
      /* lokaal blijven */
    }
  }

  function chooseTripStatusFilter(next: TripStatusFilter) {
    setTripStatusFilter(next);
    AsyncStorage.setItem(CONTRACT_TRIP_STATUS_KEY, next).catch(() => undefined);
  }

  const todayItems = useMemo(
    () => (today?.items || []).filter(itemHasVisibleRide),
    [today]
  );
  const navStops = useMemo(
    () => buildActiveNavigation(today?.items || []).stops || [],
    [today?.items]
  );
  const simStops = useMemo(
    () => buildSimulatorStops(today?.items || []),
    [today?.items]
  );
  simSourceRef.current = today?.items || [];
  childRouteLinesRef.current = childRouteLines;
  const showSimulator = canOperateRides || isDemo;

  useEffect(() => {
    if (!simRunning || !contractToken) return;
    let cancelled = false;
    simActiveRef.current = true;
    const cursor = { segment: 0, meters: 0 };
    let lines: LatLng[][] = [];
    let stops = buildSimulatorStops(simSourceRef.current);
    let ready = false;
    let busy = false;
    let lastPost = 0;

    const fail = (message: string) => {
      if (cancelled) return;
      setSimLabel(message);
      setSimRunning(false);
    };

    void (async () => {
      if (stops.length === 0) {
        fail('Geen open haltes om te rijden.');
        return;
      }
      setSimLabel(stopLabel(stops[0]));
      const driver = driverGpsRef.current;
      const startAtDriver = !anyPassengerPickedUp(simSourceRef.current) && driver;
      const origin = startAtDriver
        ? { latitude: driver.lat, longitude: driver.lng }
        : { latitude: stops[0].lat, longitude: stops[0].lng };
      lastFixRef.current = { lat: origin.latitude, lng: origin.longitude };
      setDriverFix(origin);
      const waitStarted = Date.now();
      while (!cancelled && Date.now() - waitStarted < 2500) {
        const readyLines = Object.values(childRouteLinesRef.current).some((line) => line.length > 1);
        if (readyLines) break;
        await new Promise((resolve) => setTimeout(resolve, 150));
      }
      if (cancelled) return;
      const displayed = Object.values(childRouteLinesRef.current).filter((line) => line.length > 1);
      let from = origin;
      const built: LatLng[][] = [];
      const extras: LatLng[][] = [];
      for (const stop of stops) {
        const to = { latitude: stop.lat, longitude: stop.lng };
        const along = pathAlongDisplayedLines(displayed, from, to);
        if (along && along.length > 1) {
          built.push(along);
          from = along[along.length - 1];
        } else {
          const host = lineStartingNear(displayed, to);
          const target = host ? host[0] : to;
          let coords: LatLng[];
          try {
            const route = await fetchDrivingRoute(
              { lat: from.latitude, lng: from.longitude },
              { lat: target.latitude, lng: target.longitude }
            );
            coords = route.coordinates.length > 1 ? route.coordinates : [from, target];
          } catch {
            coords = [from, target];
          }
          if (host && haversineMeters(coords[coords.length - 1], host[0]) > 15) {
            coords = [...coords, host[0]];
          }
          built.push(coords);
          extras.push(coords);
          from = coords[coords.length - 1];
        }
        if (cancelled) return;
      }
      if (cancelled) return;
      lines = built;
      setSimExtraLines(extras);
      ready = true;
    })();

    const timer = setInterval(() => {
      if (cancelled || !ready || busy) return;
      const stop = stops[cursor.segment];
      const line = lines[cursor.segment];
      if (!stop || !line) {
        setSimLabel('Rit gesimuleerd.');
        setSimRunning(false);
        return;
      }
      const step = advanceAlong(line, cursor.meters, (Math.max(20, simSpeedRef.current) / 3.6) * 0.25);
      cursor.meters = step.meters;
      lastFixRef.current = { lat: step.point.latitude, lng: step.point.longitude };
      setDriverFix(step.point);
      const now = Date.now();
      if (now - lastPost > 250) {
        lastPost = now;
        void postContractLocation(contractToken, step.point.latitude, step.point.longitude, true).catch(
          () => undefined
        );
      }
      if (!step.done) return;
      busy = true;
      void (async () => {
        try {
          await postContractLocation(contractToken, stop.lat, stop.lng, true);
          lastFixRef.current = { lat: stop.lat, lng: stop.lng };
          setDriverFix({ latitude: stop.lat, longitude: stop.lng });
          if (stop.kind === 'pickup' && stop.rideStopId) {
            try {
              await startContractRide(contractToken, stop.rideStopId);
            } catch {
              /* rit kan al gestart zijn */
            }
            try {
              await boardContractPassenger(contractToken, stop.rideStopId);
            } catch (e) {
              const msg = apiErrorMessage(e, '');
              if (!/al afgehandeld/i.test(msg)) throw e;
            }
          } else {
            const res = await reachContractDestinations(contractToken, {
              lat: stop.lat,
              lng: stop.lng,
            });
            if ((res.data?.updated || 0) < 1) {
              throw new Error(res.message || 'De bestemming kan niet op bereikt gezet worden.');
            }
          }
          await pollToday();
          cursor.segment += 1;
          cursor.meters = 0;
          const next = stops[cursor.segment];
          setSimLabel(next ? stopLabel(next) : 'Rit gesimuleerd.');
          if (!next) setSimRunning(false);
        } catch (e) {
          fail(apiErrorMessage(e, 'Status bijwerken mislukt.'));
        } finally {
          busy = false;
        }
      })();
    }, 250);

    return () => {
      cancelled = true;
      simActiveRef.current = false;
      setSimExtraLines([]);
      clearInterval(timer);
    };
  }, [simRunning, contractToken, pollToday]);

  function openSimReset() {
    if (simResetBusy) return;
    setSimResetError(null);
    setSimResetOpen(true);
  }

  function closeSimReset() {
    if (simResetBusy) return;
    setSimResetOpen(false);
  }

  async function confirmSimReset() {
    if (!contractToken || simResetBusy) return;
    setSimResetBusy(true);
    setSimResetError(null);
    setSimRunning(false);
    setSimLabel('Ritten resetten…');
    try {
      await resetContractToday(contractToken);
      setSimLabel('Ritten van vandaag zijn gereset.');
      setSimResetOpen(false);
      await refresh();
    } catch (e) {
      setSimResetError(apiErrorMessage(e, 'Resetten mislukt.'));
      setSimLabel('Resetten mislukt.');
    } finally {
      setSimResetBusy(false);
    }
  }
  const childRides = useMemo(
    () =>
      childMapRides(todayItems, showSimulator).map((ride) => ({
        ...ride,
        color: colorOverrides[ride.passengerId] || ride.color,
      })),
    [todayItems, colorOverrides, showSimulator]
  );
  const childRouteKey = useMemo(
    () =>
      childRides
        .map(
          (ride) =>
            `${ride.passengerId}:${ride.pickup?.latitude ?? ''},${ride.pickup?.longitude ?? ''}:${ride.dropoff?.latitude ?? ''},${ride.dropoff?.longitude ?? ''}`
        )
        .join('|'),
    [childRides]
  );
  const taxiOnMap = useMemo(() => {
    const fromRide = [...childRides]
      .sort((a, b) => Number(b.inTaxi) - Number(a.inTaxi))
      .find((ride) => ride.vehicle)?.vehicle;
    const raw = canOperateRides ? driverFix : fromRide;
    if (!raw) return null;
    if (simRunning) return raw;
    const lines = childRides
      .map((ride) => childRouteLines[ride.passengerId])
      .filter((line): line is LatLng[] => !!line && line.length > 1);
    return placeTaxiOnLines(raw, lines);
  }, [canOperateRides, driverFix, childRides, childRouteLines, simRunning]);

  useEffect(() => {
    if (!canOperateRides || !taxiOnMap) return;
    const prev = prevTaxiRef.current;
    prevTaxiRef.current = taxiOnMap;
    if (!prev || haversineMeters(prev, taxiOnMap) < 4) return;
    const deg = bearingDegrees(
      { lat: prev.latitude, lng: prev.longitude },
      { lat: taxiOnMap.latitude, lng: taxiOnMap.longitude }
    );
    setTaxiHeading((deg + 360) % 360);
  }, [canOperateRides, taxiOnMap]);

  useEffect(() => {
    setTrackTaxiHeading(true);
    const timer = setTimeout(() => setTrackTaxiHeading(false), 250);
    return () => clearTimeout(timer);
  }, [taxiHeading]);
  const passengerPins = useMemo(() => {
    const pins: Array<{
      key: string;
      coordinate: LatLng;
      color: string;
      label: string;
      kind: 'pickup' | 'destination';
      pickedUp: boolean;
      arrivedNames: Array<{ label: string; color: string }>;
    }> = [];
    const seenDest = new Set<string>();
    const arrivedByDest = new Map<string, Array<{ label: string; color: string }>>();
    childRides.forEach((ride) => {
      const label = ride.givenName || ride.name;
      if (ride.arrived && ride.dropoff) {
        const destKey = `${ride.dropoff.latitude.toFixed(4)},${ride.dropoff.longitude.toFixed(4)}`;
        const names = arrivedByDest.get(destKey) ?? [];
        names.push({ label, color: ride.color });
        arrivedByDest.set(destKey, names);
        return;
      }
      const pickup = ride.inTaxi && taxiOnMap ? taxiOnMap : ride.pickup;
      if (pickup) {
        pins.push({
          key: `pick-${ride.passengerId}`,
          coordinate: pickup,
          color: ride.color,
          label,
          kind: 'pickup',
          pickedUp: ride.pickedUp,
          arrivedNames: [],
        });
      }
    });
    childRides.forEach((ride) => {
      if (!ride.dropoff) return;
      const destKey = `${ride.dropoff.latitude.toFixed(4)},${ride.dropoff.longitude.toFixed(4)}`;
      if (seenDest.has(destKey)) return;
      seenDest.add(destKey);
      pins.push({
        key: `dest-${destKey}`,
        coordinate: ride.dropoff,
        color: DEST_GREEN,
        label: '',
        kind: 'destination',
        pickedUp: false,
        arrivedNames: arrivedByDest.get(destKey) ?? [],
      });
    });
    const metersPerPixel =
      (pinScale.latitudeDelta * 111320) / Math.max(pinScale.height, 120);
    const clusterMeters = Math.min(200, 48 * metersPerPixel);
    const named = pins.filter((pin) => pin.kind === 'pickup' && pin.label);
    const used = new Set<string>();
    const moved = new Map<string, LatLng>();
    for (const pin of named) {
      if (used.has(pin.key)) continue;
      const group = [pin];
      used.add(pin.key);
      for (const other of named) {
        if (used.has(other.key)) continue;
        if (haversineMeters(pin.coordinate, other.coordinate) < clusterMeters) {
          group.push(other);
          used.add(other.key);
        }
      }
      if (group.length < 2) continue;
      group.forEach((item, index) => {
        moved.set(item.key, stackPoint(group[0].coordinate, index, metersPerPixel));
      });
    }
    if (moved.size === 0) return pins;
    return pins.map((pin) => {
      const coordinate = moved.get(pin.key);
      return coordinate ? { ...pin, coordinate } : pin;
    });
  }, [childRides, taxiOnMap, pinScale]);

  useEffect(() => {
    if (!childRouteKey) {
      setChildRouteLines({});
      return;
    }
    let cancelled = false;
    const jobs = childRides.filter((ride) => ride.pickup && ride.dropoff);
    void (async () => {
      const next: Record<number, LatLng[]> = {};
      await Promise.all(
        jobs.map(async (ride) => {
          const route = await fetchDrivingRoute(
            { lat: ride.pickup!.latitude, lng: ride.pickup!.longitude },
            { lat: ride.dropoff!.latitude, lng: ride.dropoff!.longitude }
          );
          next[ride.passengerId] = route.coordinates;
        })
      );
      if (!cancelled) setChildRouteLines(next);
    })();
    return () => {
      cancelled = true;
    };
  }, [childRouteKey]);

  useEffect(() => {
    if (!canOperateRides || !followTaxi || !taxiOnMap || !parentMapRef.current) return;
    parentMapRef.current.animateCamera(
      {
        center: { latitude: taxiOnMap.latitude, longitude: taxiOnMap.longitude },
        zoom: 15,
      },
      { duration: 700 }
    );
  }, [canOperateRides, followTaxi, taxiOnMap?.latitude, taxiOnMap?.longitude]);

  useEffect(() => {
    if (taxiOnMap || !parentMapRef.current) return;
    const points: LatLng[] = [];
    for (const ride of childRides) {
      const line = childRouteLines[ride.passengerId];
      if (line && line.length > 0) points.push(...line);
      else {
        if (ride.pickup) points.push(ride.pickup);
        if (ride.dropoff) points.push(ride.dropoff);
      }
    }
    if (points.length === 0) return;
    const key = points.map((point) => `${point.latitude.toFixed(4)},${point.longitude.toFixed(4)}`).join('|');
    if (key === parentMapFittedKey.current) return;
    parentMapFittedKey.current = key;
    parentMapRef.current.fitToCoordinates(points, {
      edgePadding: { top: 48, right: 48, bottom: 48, left: 48 },
      animated: true,
    });
  }, [taxiOnMap, childRides, childRouteLines]);

  const actionableTripKeys = useMemo(() => {
    const date = today?.date || '';
    const keys: string[] = [];
    for (const item of todayItems) {
      for (const leg of itemLegs(item)) {
        if (isActionableLeg(leg, item)) {
          keys.push(actionableLegKey(item, leg, date));
        }
      }
    }
    return keys;
  }, [todayItems, today?.date]);

  const markTripsSeen = useCallback(() => {
    if (actionableTripKeys.length === 0) return;
    setTripsSeenKeys((prev) => {
      const set = new Set(prev);
      let changed = false;
      for (const key of actionableTripKeys) {
        if (!set.has(key)) {
          set.add(key);
          changed = true;
        }
      }
      if (!changed) return prev;
      const next = Array.from(set);
      AsyncStorage.setItem(CONTRACT_TRIPS_SEEN_KEY, JSON.stringify(next)).catch(() => undefined);
      return next;
    });
  }, [actionableTripKeys]);

  useEffect(() => {
    if (tab === 'trips') markTripsSeen();
  }, [tab, markTripsSeen]);

  function selectTab(key: ContractTabKey) {
    setTab(key);
    persistContractTab(key);
    if (key === 'trips') markTripsSeen();
  }

  const filteredTodayItems = useMemo(() => {
    const rows: ContractDayItem[] = [];
    for (const item of todayItems) {
      const legs = itemLegs(item).filter((leg) =>
        legMatchesStatusFilter(leg, item, tripStatusFilter)
      );
      if (legs.length === 0) continue;
      rows.push({ ...item, legs });
    }
    return rows;
  }, [todayItems, tripStatusFilter]);

  /** Alleen bestemming van open/actieve legs — nooit tonen als er geen openstaande rit is. */
  const openDestinationSummary = useMemo(() => {
    const closed = new Set([
      'completed',
      'absent',
      'skipped',
      'not_taken',
      'none',
      'expired',
    ]);
    const active = new Set(['en_route', 'arrived', 'picked_up']);
    const openLegs: ContractLeg[] = [];
    for (const item of todayItems) {
      for (const leg of itemLegs(item)) {
        const key = String(leg.status_key || '').toLowerCase();
        if (closed.has(key) || legIsExpired(leg) || legIsTerminal(leg, item)) continue;
        openLegs.push(leg);
      }
    }
    if (openLegs.length === 0) return null;
    const activeLegs = openLegs.filter((leg) =>
      active.has(String(leg.status_key || '').toLowerCase())
    );
    const use = activeLegs.length > 0 ? activeLegs : openLegs;
    const addresses = [
      ...new Set(
        use
          .map((leg) => String(leg.destination_address || '').trim())
          .filter(Boolean)
      ),
    ];
    if (addresses.length === 1) return addresses[0];
    if (addresses.length > 1) return 'Meerdere bestemmingen';
    return null;
  }, [todayItems]);

  const selectedWeekDay = useMemo(() => {
    const days = week?.days || [];
    return days.find((d) => d.date === selectedDate) || days.find((d) => d.is_today) || days[0] || null;
  }, [week, selectedDate]);

  const planningItems = useMemo(() => {
    // Week én Dag: lijst = ritten van de gekozen dag; weekstrip blijft zichtbaar in Week.
    return (selectedWeekDay?.items || [])
      .filter(itemHasVisibleRide)
      .map((item) => ({ ...item, _date: selectedWeekDay?.date }));
  }, [selectedWeekDay]);

  const tripsBadge = useMemo(() => {
    if (tab === 'trips') return 0;
    const seen = new Set(tripsSeenKeys);
    return actionableTripKeys.filter((key) => !seen.has(key)).length;
  }, [tab, tripsSeenKeys, actionableTripKeys]);

  function rideExpandKey(
    item: ContractDayItem,
    leg: ContractLeg,
    index: number,
    dateLabel?: string
  ): string {
    return `${dateLabel || 'd'}:${item.passenger_id}:${leg.ride_stop_id ?? leg.leg_key ?? index}`;
  }

  const bumpPulses = useCallback((keys: string[]) => {
    if (keys.length === 0) return;
    setIconPulse((prev) => {
      const next = { ...prev };
      for (const key of keys) next[key] = (next[key] || 0) + 1;
      return next;
    });
  }, []);

  const destinationPulseKeys = useCallback(() => {
    const keys: string[] = [];
    todayItems.forEach((item) => {
      itemLegs(item).forEach((leg, index) => {
        const status = legStatusKey(leg, item);
        if (status === 'absent' || status === 'not_taken') return;
        keys.push(`dest:${rideExpandKey(item, leg, index, today?.date)}`);
      });
    });
    return keys;
  }, [todayItems, today?.date]);

  useEffect(() => {
    const next = new Map<string, string>();
    todayItems.forEach((item) => {
      itemLegs(item).forEach((leg, index) => {
        next.set(rideExpandKey(item, leg, index, today?.date), legStatusKey(leg, item));
      });
    });
    const prev = statusSeenRef.current;
    statusSeenRef.current = next;
    if (!prev) return;
    const keys: string[] = [];
    const arrivedKeys: string[] = [];
    for (const [id, status] of next) {
      const before = prev.get(id);
      if (!before || before === status) continue;
      if (status === 'completed') arrivedKeys.push(id);
      if (canOperateRides) continue;
      const wasBoarded = before === 'picked_up' || before === 'completed';
      if ((status === 'picked_up' || status === 'completed') && !wasBoarded) {
        keys.push(`pickup:${id}`);
      }
      if (status === 'completed') keys.push(`dest:${id}`);
    }
    if (arrivedKeys.length > 0) {
      selectTab('trips');
      setTripStatusFilter('expired');
      AsyncStorage.setItem(CONTRACT_TRIP_STATUS_KEY, 'expired').catch(() => undefined);
      setExpandedRideKeys((open) => {
        const nextOpen = { ...open };
        for (const key of arrivedKeys) nextOpen[key] = true;
        return nextOpen;
      });
    }
    if (keys.length === 0) return;
    bumpPulses(keys);
    void playRideAlertTone('chime');
  }, [todayItems, today?.date, canOperateRides, bumpPulses]);

  useEffect(() => {
    if (!destBtnPulse) return;
    destBtnScale.setValue(1);
    const beat = Animated.sequence([
      Animated.timing(destBtnScale, { toValue: 1.06, duration: 160, useNativeDriver: true }),
      Animated.timing(destBtnScale, { toValue: 1, duration: 180, useNativeDriver: true }),
    ]);
    const anim = Animated.loop(beat, { iterations: 4 });
    anim.start();
    return () => anim.stop();
  }, [destBtnPulse, destBtnScale]);

  function toggleRideExpanded(key: string) {
    setExpandedRideKeys((prev) => {
      if (prev[key]) {
        const next = { ...prev };
        delete next[key];
        return next;
      }
      return { ...prev, [key]: true };
    });
  }

  function renderRideCard(item: ContractDayItem, dateLabel?: string) {
    const legs = itemLegs(item);

    return (
      <View key={`${dateLabel || 'd'}-${item.passenger_id}`}>
        {legs.map((leg, index) => {
          const pickupRaw = leg.pickup_address || item.pickup_address;
          const dropoffRaw = leg.destination_address || item.destination_address;
          const pickup = splitAddress(pickupRaw);
          const dropoff = splitAddress(dropoffRaw);
          const statusKey = legStatusKey(leg, item);
          const absent =
            isAbsentStatus(leg.status, leg.status_key) ||
            isAbsentStatus(item.status, item.status_key) ||
            isAbsentStatus(item.day_status_label, item.day_status) ||
            !!item.absence_id ||
            statusKey === 'absent';
          const notTaken = statusKey === 'not_taken';
          const expired =
            !absent &&
            !notTaken &&
            (isExpiredStatus(leg.status, leg.status_key) ||
              isExpiredStatus(item.status, item.status_key) ||
              isExpiredStatus(item.day_status_label, item.day_status) ||
              statusKey === 'expired');
          const bannerText = legStatusBanner(leg, item);
          const tone = bannerTone(absent ? 'absent' : notTaken ? 'not_taken' : statusKey);
          const time = formatTime(leg.planned_at || item.planned_at);
          const label = legDisplayLabel(leg);
          const expandKey = rideExpandKey(item, leg, index, dateLabel);
          const expanded = !!expandedRideKeys[expandKey];
          const showStart = !absent && !notTaken && legCanStart(leg, item, canOperateRides);
          const showBoard = !absent && !notTaken && legCanBoard(leg, item, canOperateRides);
          const showComplete =
            !absent &&
            !notTaken &&
            legCanComplete(leg, item, canOperateRides) &&
            (statusKey === 'picked_up' || !showBoard);
          const isPickedUp =
            statusKey === 'picked_up' || statusKey === 'completed';
          const isAtDestination = statusKey === 'completed';
          // Grijs tot relevant; groen = opgehaald; rood = chauffeur was er, niet opgehaald.
          const chauffeurHasBeen =
            statusKey === 'arrived' ||
            statusKey === 'expired' ||
            statusKey === 'not_taken';
          const boardedColor = isPickedUp
            ? DEST_GREEN
            : chauffeurHasBeen
              ? OVERDUE_RED
              : colors.muted;
          const destinationColor = isAtDestination
            ? DEST_GREEN
            : absent
              ? ABSENT_GREY
              : colors.muted;
          const destinationLabel = canOperateRides ? 'Bestemming' : 'Op bestemming';
          const cardBorder = absent || notTaken
            ? hexAlpha(ABSENT_GREY, 0.55)
            : expired
              ? OVERDUE_RED
              : hexAlpha(accentHex, 0.55);
          const cardAccent = absent || notTaken ? ABSENT_GREY : expired ? OVERDUE_RED : accentHex;

          return (
            <View
              key={`${item.passenger_id}-leg-${leg.leg_key || index}-${leg.ride_stop_id || index}`}
              style={[
                styles.rideCard,
                { borderColor: cardBorder },
                expired && styles.rideCardOverdue,
                absent && styles.rideCardAbsent,
                !expanded && styles.rideCardCollapsed,
              ]}
            >
              <View
                style={[
                  styles.rideAccentBar,
                  { backgroundColor: cardAccent },
                ]}
              />
              <Pressable
                onPress={() => toggleRideExpanded(expandKey)}
                accessibilityRole="button"
                accessibilityState={{ expanded }}
                accessibilityLabel={
                  expanded ? `Rit van ${item.name} inklappen` : `Rit van ${item.name} uitklappen`
                }
              >
                <View style={styles.rideCardHeader}>
                  <View style={styles.badgeRow}>
                    <View
                      style={[
                        styles.badge,
                        {
                          backgroundColor: absent
                            ? hexAlpha(ABSENT_GREY, 0.2)
                            : hexAlpha(accentHex, 0.2),
                        },
                      ]}
                    >
                      <Text
                        style={[
                          styles.badgeText,
                          { color: absent ? ABSENT_GREY : accentHex },
                        ]}
                      >
                        Contract
                      </Text>
                    </View>
                    <View style={[styles.badge, styles.badgeLeg]}>
                      <Text style={styles.badgeLegText}>{label}</Text>
                    </View>
                    {absent ? (
                      <View style={[styles.badge, styles.badgeAbsent]}>
                        <Text style={styles.badgeAbsentText}>Afgemeld</Text>
                      </View>
                    ) : notTaken ? (
                      <View style={[styles.badge, styles.badgeAbsent]}>
                        <Text style={styles.badgeAbsentText}>Niet meegenomen</Text>
                      </View>
                    ) : expired ? (
                      <View style={[styles.badge, styles.badgeDanger]}>
                        <Text style={styles.badgeDangerText}>Ophaalmoment verlopen</Text>
                      </View>
                    ) : null}
                  </View>
                  <Ionicons
                    name={expanded ? 'chevron-up' : 'chevron-down'}
                    size={20}
                    color={colors.muted}
                    style={styles.rideExpandIcon}
                  />
                </View>

                <Text style={styles.rideName}>{item.name}</Text>
                <Text style={[styles.rideMeta, !expanded && styles.rideMetaCollapsed]}>
                  {time}
                  {dateLabel ? ` · ${formatDayLong(dateLabel)}` : ''}
                </Text>

                {!expanded ? (
                  <View style={styles.rideCollapsedSummary}>
                    <Text
                      style={[
                        styles.rideCollapsedStatus,
                        tone === 'success' && styles.statusBannerTextSuccess,
                        tone === 'info' && styles.statusBannerTextInfo,
                        tone === 'warn' && styles.statusBannerTextWarn,
                        tone === 'muted' && styles.statusBannerTextMuted,
                        tone === 'neutral' && { color: accentHex },
                        tone === 'danger' && styles.statusBannerTextWarn,
                      ]}
                      numberOfLines={1}
                    >
                      {bannerText}
                    </Text>
                    <Text style={styles.rideCollapsedRoute} numberOfLines={1}>
                      {pickup.main}
                      {dropoff.main ? ` → ${dropoff.main}` : ''}
                    </Text>
                    <View style={styles.rideCollapsedIcons}>
                      <View style={styles.rideCollapsedIcon}>
                        <PulsingIcon
                          name="navigate-outline"
                          size={18}
                          color={absent ? ABSENT_GREY : boardedColor}
                          pulseToken={iconPulse[`pickup:${expandKey}`] || 0}
                        />
                        <Text style={[styles.rideCollapsedIconLabel, { color: absent ? ABSENT_GREY : boardedColor }]}>
                          Opgehaald
                        </Text>
                      </View>
                      <View style={styles.rideCollapsedIcon}>
                        <PulsingIcon
                          name="flag-outline"
                          size={18}
                          color={destinationColor}
                          pulseToken={iconPulse[`dest:${expandKey}`] || 0}
                        />
                        <Text style={[styles.rideCollapsedIconLabel, { color: destinationColor }]}>
                          {destinationLabel}
                        </Text>
                      </View>
                    </View>
                  </View>
                ) : null}
              </Pressable>

              {expanded ? (
                <>
              <View
                style={[
                  styles.statusBanner,
                  tone === 'success' && styles.statusBannerSuccess,
                  tone === 'info' && styles.statusBannerInfo,
                  tone === 'warn' && styles.statusBannerWarn,
                  tone === 'danger' && styles.statusBannerDanger,
                  tone === 'muted' && styles.statusBannerMuted,
                ]}
              >
                <Text
                  style={[
                    styles.statusBannerText,
                    tone === 'success' && styles.statusBannerTextSuccess,
                    tone === 'info' && styles.statusBannerTextInfo,
                    tone === 'warn' && styles.statusBannerTextWarn,
                    tone === 'muted' && styles.statusBannerTextMuted,
                  ]}
                >
                  {bannerText}
                </Text>
                {canOperateRides && showBoard ? (
                  <Text style={styles.statusBannerHint}>
                    Markeer Opgehaald of Overslaan.{'\n'}
                    Zonder keuze: automatisch opgehaald
                    {typeof today?.auto_board_grace_minutes === 'number'
                      ? ` ${today.auto_board_grace_minutes} min`
                      : ' enkele minuten'}{' '}
                    na aankomst bestemming (of bij afronden door de chauffeur).
                  </Text>
                ) : null}
              </View>

              <View style={styles.route}>
                <View style={styles.routeRail} />
                <View style={[styles.routeStop, styles.routeStopFirst]}>
                  <View style={styles.routeHead}>
                    <View style={[styles.dotOuter, styles.dotOuterPickup]}>
                      <View style={[styles.dot, styles.dotPickup]} />
                    </View>
                    <Text style={styles.routeLabel}>Ophalen</Text>
                  </View>
                  <View style={styles.routeAddress}>
                    <Text style={styles.routeMain}>{pickup.main}</Text>
                    {pickup.sub ? <Text style={styles.routeSub}>{pickup.sub}</Text> : null}
                  </View>
                </View>
                <View style={[styles.routeStop, styles.routeStopLast]}>
                  <View style={styles.routeHead}>
                    <View style={[styles.dotOuter, styles.dotOuterDropoff]}>
                      <View style={[styles.dot, styles.dotDropoff]} />
                    </View>
                    <Text style={styles.routeLabel}>Afzetten</Text>
                  </View>
                  <View style={styles.routeAddress}>
                    <Text style={styles.routeMain}>{dropoff.main}</Text>
                    {dropoff.sub ? <Text style={styles.routeSub}>{dropoff.sub}</Text> : null}
                  </View>
                </View>
              </View>

              <View style={styles.rideIconBar}>
                <Pressable
                  style={[
                    styles.rideIconTab,
                    rideActionStopId === leg.ride_stop_id && showBoard && { opacity: 0.55 },
                  ]}
                  disabled={!showBoard || rideActionStopId != null}
                  onPress={() => {
                    if (!showBoard) return;
                    if (canOperateRides) bumpPulses([`pickup:${expandKey}`]);
                    void onBoardLeg(leg.ride_stop_id);
                  }}
                  accessibilityRole="button"
                  accessibilityLabel={isPickedUp ? 'Opgehaald' : 'Nog niet opgehaald'}
                  accessibilityState={{ disabled: !showBoard }}
                >
                  <PulsingIcon
                    name="navigate-outline"
                    size={22}
                    color={absent ? ABSENT_GREY : boardedColor}
                    pulseToken={iconPulse[`pickup:${expandKey}`] || 0}
                  />
                  <Text
                    style={[
                      styles.rideIconLabel,
                      { color: absent ? ABSENT_GREY : boardedColor },
                    ]}
                    numberOfLines={1}
                    adjustsFontSizeToFit
                    minimumFontScale={0.7}
                  >
                    Opgehaald
                  </Text>
                </Pressable>
                <Pressable
                  style={styles.rideIconTab}
                  disabled={!canOperateRides || isAtDestination}
                  onPress={openDestReach}
                  accessibilityRole="button"
                  accessibilityLabel={
                    isAtDestination ? `${destinationLabel}: ja` : `${destinationLabel}: nog niet`
                  }
                  accessibilityState={{ disabled: !canOperateRides || isAtDestination }}
                >
                  <PulsingIcon
                    name="flag-outline"
                    size={22}
                    color={destinationColor}
                    pulseToken={iconPulse[`dest:${expandKey}`] || 0}
                  />
                  <Text
                    style={[styles.rideIconLabel, { color: destinationColor }]}
                    numberOfLines={1}
                    adjustsFontSizeToFit
                    minimumFontScale={0.7}
                  >
                    {destinationLabel}
                  </Text>
                </Pressable>
                {showStart ? (
                  <Pressable
                    style={[
                      styles.rideIconTab,
                      rideActionStopId === leg.ride_stop_id && { opacity: 0.55 },
                    ]}
                    disabled={rideActionStopId != null}
                    onPress={() => void onStartLeg(leg.ride_stop_id)}
                    accessibilityRole="button"
                    accessibilityLabel="Rit starten"
                  >
                    <Ionicons name="play-outline" size={22} color={colors.muted} />
                    <Text
                      style={styles.rideIconLabel}
                      numberOfLines={1}
                      adjustsFontSizeToFit
                      minimumFontScale={0.7}
                    >
                      {rideActionStopId === leg.ride_stop_id ? 'Bezig…' : 'Starten'}
                    </Text>
                  </Pressable>
                ) : null}
                {showBoard ? (
                  <Pressable
                    style={[
                      styles.rideIconTab,
                      rideActionStopId === leg.ride_stop_id && { opacity: 0.55 },
                    ]}
                    disabled={rideActionStopId != null}
                    onPress={() => onSkipLeg(leg.ride_stop_id, item.name)}
                    accessibilityRole="button"
                    accessibilityLabel="Niet meegenomen"
                  >
                    <Ionicons name="person-remove-outline" size={22} color={colors.muted} />
                    <Text
                      style={styles.rideIconLabel}
                      numberOfLines={1}
                      adjustsFontSizeToFit
                      minimumFontScale={0.7}
                    >
                      Overslaan
                    </Text>
                  </Pressable>
                ) : null}
                {showComplete ? (
                  <Pressable
                    style={[
                      styles.rideIconTab,
                      rideActionStopId === leg.ride_stop_id && { opacity: 0.55 },
                    ]}
                    disabled={rideActionStopId != null}
                      onPress={() => onCompleteLeg(leg.ride_stop_id, leg, item)}
                      accessibilityRole="button"
                      accessibilityLabel="Afronden"
                    >
                      <Ionicons name="checkmark-circle-outline" size={22} color={colors.muted} />
                      <Text
                        style={styles.rideIconLabel}
                        numberOfLines={1}
                        adjustsFontSizeToFit
                        minimumFontScale={0.7}
                      >
                        {rideActionStopId === leg.ride_stop_id && !showStart && !showBoard
                          ? 'Bezig…'
                          : 'Afronden'}
                      </Text>
                    </Pressable>
                  ) : null}
                {item.can_cancel && !item.absence_id ? (
                  <Pressable
                    style={styles.rideIconTab}
                    onPress={() => openAbsence(item.passenger_id, dateLabel)}
                    accessibilityRole="button"
                    accessibilityLabel="Afmelden"
                  >
                    <Ionicons name="close-circle-outline" size={22} color={colors.muted} />
                    <Text
                      style={styles.rideIconLabel}
                      numberOfLines={1}
                      adjustsFontSizeToFit
                      minimumFontScale={0.7}
                    >
                      Afmelden
                    </Text>
                  </Pressable>
                ) : null}
                {item.absence_id ? (
                  <Pressable
                    style={styles.rideIconTab}
                    onPress={() => cancelAbsence(item.absence_id!)}
                    accessibilityRole="button"
                    accessibilityLabel="Afmelding intrekken"
                  >
                    <Ionicons name="arrow-undo-outline" size={22} color={colors.muted} />
                    <Text
                      style={styles.rideIconLabel}
                      numberOfLines={1}
                      adjustsFontSizeToFit
                      minimumFontScale={0.7}
                    >
                      Intrekken
                    </Text>
                  </Pressable>
                ) : null}
              </View>
                </>
              ) : null}
            </View>
          );
        })}
      </View>
    );
  }

  const tripsPanel = (
    <AppScrollView
      persistKey="contract-trips"
      contentContainerStyle={styles.scroll}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.text} />
      }
    >
      <ErrorText>{error}</ErrorText>
      <ScreenHeader
        title="Ritten"
        right={
          <Text style={styles.headerDate}>
            {today?.date ? formatDayLong(today.date) : 'Vandaag'}
          </Text>
        }
      />
      <View style={styles.statusFilterRow}>
        {TRIP_STATUS_OPTIONS.map((opt) => {
          const active = tripStatusFilter === opt.key;
          return (
            <Pressable
              key={opt.key}
              style={[styles.statusFilterBtn, active && styles.statusFilterBtnActive]}
              onPress={() => chooseTripStatusFilter(opt.key)}
            >
              <Text style={[styles.statusFilterText, active && styles.statusFilterTextActive]}>
                {opt.label}
              </Text>
            </Pressable>
          );
        })}
      </View>
      {openDestinationSummary ? (
        <View style={styles.destBanner}>
          <Text style={styles.destLabel}>Bestemming</Text>
          <Text style={styles.destValue}>{openDestinationSummary}</Text>
          {canOperateRides ? (
            <Animated.View style={{ transform: [{ scale: destBtnScale }] }}>
              <Pressable
                style={styles.primaryBtn}
                onPress={openDestReach}
              >
                <Text style={styles.primaryBtnText}>Bestemming bereikt</Text>
              </Pressable>
            </Animated.View>
          ) : null}
        </View>
      ) : null}
      {filteredTodayItems.length === 0 ? (
        <Card>
          <Text style={styles.emptyTitle}>
            {todayItems.length === 0
              ? 'Geen ritten vandaag.'
              : tripStatusFilter === 'expired'
                ? 'Geen verlopen ritten.'
                : tripStatusFilter === 'open'
                  ? 'Geen openstaande ritten.'
                  : 'Geen ritten vandaag.'}
          </Text>
          <Text style={styles.emptyHint}>
            {todayItems.length === 0
              ? 'Geplande contractritten voor vandaag verschijnen hier.'
              : 'Pas het filter aan om andere ritten te zien.'}
          </Text>
        </Card>
      ) : (
        filteredTodayItems.map((item) => renderRideCard(item, today?.date))
      )}
    </AppScrollView>
  );

  const planningPanel = (
    <AppScrollView
      persistKey="contract-planning"
      contentContainerStyle={styles.scroll}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.text} />
      }
    >
      <ErrorText>{error}</ErrorText>
      <ScreenHeader
        title="Planning"
        right={
          <View style={styles.periodToggle}>
            {(['day', 'week'] as const).map((key) => {
              const active = planningView === key;
              return (
                <Pressable
                  key={key}
                  style={[styles.periodBtn, active && styles.periodBtnActive]}
                  onPress={() => setPlanningView(key)}
                >
                  <Text style={[styles.periodBtnText, active && styles.periodBtnTextActive]}>
                    {key === 'day' ? 'Dag' : 'Week'}
                  </Text>
                </Pressable>
              );
            })}
          </View>
        }
      />

      <View style={styles.navRow}>
        <Pressable
          style={styles.navBtn}
          onPress={() => loadWeek(addDaysIso(weekFrom, -7))}
          accessibilityLabel="Vorige week"
        >
          <Ionicons name="chevron-back" size={20} color={colors.text} />
        </Pressable>
        <Pressable
          style={styles.todayBtn}
          onPress={() => {
            const todayIso = toIsoDate(new Date());
            setSelectedDate(todayIso);
            loadWeek(mondayIso(todayIso));
          }}
        >
          <Text style={styles.todayBtnText}>Vandaag</Text>
        </Pressable>
        <View style={styles.navSpacer} />
        <Pressable
          style={styles.navBtn}
          onPress={() => loadWeek(addDaysIso(weekFrom, 7))}
          accessibilityLabel="Volgende week"
        >
          <Ionicons name="chevron-forward" size={20} color={colors.text} />
        </Pressable>
      </View>

      <Text style={styles.headingLabel}>
        {planningView === 'week' && week
          ? `${formatRangeLabel(week.from, week.to)} · ${formatDayLong(selectedWeekDay?.date || selectedDate)}`
          : formatDayLong(selectedWeekDay?.date || selectedDate)}
      </Text>

      {planningView === 'week' ? (
        <View style={styles.weekDays}>
          {(week?.days || []).map((day, index) => {
            const active = day.date === (selectedWeekDay?.date || selectedDate);
            const count = Number(day.ride_count || 0);
            return (
              <Pressable
                key={day.date}
                style={[styles.weekDay, active && styles.weekDayActive]}
                onPress={() => setSelectedDate(day.date)}
              >
                <Text style={[styles.weekDayName, active && styles.weekDayNameActive]}>
                  {WEEKDAYS_SHORT[index] || '—'}
                </Text>
                <Text style={[styles.weekDayNum, active && styles.weekDayNumActive]}>
                  {parseIsoDate(day.date).getDate()}
                </Text>
                <Text style={[styles.weekDayCount, active && styles.weekDayCountActive]}>
                  {count}
                </Text>
              </Pressable>
            );
          })}
        </View>
      ) : null}

      {planningItems.length === 0 ? (
        <Card>
          <Text style={styles.emptyHint}>Geen ritten in deze periode.</Text>
        </Card>
      ) : (
        planningItems.map((item) =>
          renderRideCard(item, (item as ContractDayItem & { _date?: string })._date)
        )
      )}
    </AppScrollView>
  );

  async function saveChildColor(passengerId: number, mapColor: string) {
    const color = mapColor.toUpperCase();
    setColorOverrides((prev) => ({ ...prev, [passengerId]: color }));
    setPassengers((rows) =>
      rows.map((row) => (row.id === passengerId ? { ...row, map_color: color } : row))
    );
    setToday((current) =>
      current
        ? {
            ...current,
            items: current.items.map((item) =>
              item.passenger_id === passengerId ? { ...item, map_color: color } : item
            ),
          }
        : current
    );
    setColorPassengerId(null);
    if (!contractToken) return;
    try {
      await updateContractPassengerColor(contractToken, passengerId, color);
    } catch (e) {
      setError(apiErrorMessage(e, 'Kleur opslaan mislukt.'));
    }
  }

  const colorPassengerName =
    passengers.find((row) => row.id === colorPassengerId)?.name ||
    childRides.find((ride) => ride.passengerId === colorPassengerId)?.name ||
    'Kind';
  const colorPassengerStored =
    passengers.find((row) => row.id === colorPassengerId)?.map_color ||
    todayItems.find((item) => item.passenger_id === colorPassengerId)?.map_color ||
    null;

  function releaseTaxiFollow() {
    if (!followTaxiRef.current) return;
    followTaxiRef.current = false;
    setFollowTaxi(false);
  }

  function focusTaxi() {
    if (!taxiOnMap) return;
    followTaxiRef.current = true;
    setFollowTaxi(true);
    parentMapRef.current?.animateCamera(
      {
        center: { latitude: taxiOnMap.latitude, longitude: taxiOnMap.longitude },
        zoom: 15,
      },
      { duration: 450 }
    );
  }

  const parentNavigation = (
    <View style={styles.parentNav}>
      <View style={styles.parentNavHeader}>
        <ScreenHeader title="Navigatie" />
      </View>
      <View
        style={styles.parentNavMapWrap}
        onLayout={(event) => {
          const height = event.nativeEvent.layout.height;
          if (height < 80) return;
          setPinScale((prev) => (Math.abs(prev.height - height) < 8 ? prev : { ...prev, height }));
        }}
      >
      <MapView
        key={colorScheme}
        ref={parentMapRef}
        style={styles.parentNavMap}
        provider={PROVIDER_GOOGLE}
        {...(colorScheme === 'dark' ? { customMapStyle: DARK_MAP_STYLE } : {})}
        initialRegion={{
          latitude: taxiOnMap?.latitude || childRides[0]?.pickup?.latitude || 52.22,
          longitude: taxiOnMap?.longitude || childRides[0]?.pickup?.longitude || 6.89,
          latitudeDelta: 0.08,
          longitudeDelta: 0.08,
        }}
        showsUserLocation={false}
        showsMyLocationButton={false}
        pitchEnabled={false}
        rotateEnabled={false}
        scrollEnabled
        zoomEnabled
        toolbarEnabled={false}
        onPanDrag={releaseTaxiFollow}
        onRegionChange={(_region, details) => {
          if (details?.isGesture) releaseTaxiFollow();
        }}
        onRegionChangeComplete={(region) => {
          const latitudeDelta = region.latitudeDelta;
          if (!Number.isFinite(latitudeDelta) || latitudeDelta <= 0) return;
          setPinScale((prev) =>
            Math.abs(prev.latitudeDelta - latitudeDelta) < prev.latitudeDelta * 0.08
              ? prev
              : { ...prev, latitudeDelta }
          );
        }}
      >
        {simExtraLines.map((line, index) =>
          line.length > 1 ? (
            <Polyline
              key={`sim-line-${index}`}
              coordinates={line}
              strokeColor="#E2E8F0"
              strokeWidth={5}
            />
          ) : null
        )}
        {childRides.map((ride) => {
          const line =
            childRouteLines[ride.passengerId] ||
            (ride.pickup && ride.dropoff ? [ride.pickup, ride.dropoff] : []);
          if (line.length < 2) return null;
          return (
            <Polyline
              key={`route-${ride.passengerId}`}
              coordinates={line}
              strokeColor={ride.color}
              strokeWidth={5}
            />
          );
        })}
        {passengerPins.map((pin) => (
          <Marker
            key={pin.key}
            coordinate={pin.coordinate}
            title={pin.kind === 'destination' ? 'Bestemming' : pin.label}
            anchor={{ x: 0.5, y: 1 }}
            tracksViewChanges
            zIndex={pin.kind === 'destination' ? 40 : 30}
          >
            <View style={styles.childMarker}>
              {pin.arrivedNames.length > 0 ? (
                <View style={styles.destinationNames}>
                  <Text style={styles.destinationCaption}>Bestemming</Text>
                  {pin.arrivedNames.map((person) => (
                    <View
                      key={person.label}
                      style={[styles.destinationNameRow, { backgroundColor: person.color }]}
                    >
                      <Text style={styles.destinationNameText}>{person.label}</Text>
                      <View style={styles.passengerPinCheck}>
                        <Ionicons name="checkmark" size={13} color="#16A34A" />
                      </View>
                    </View>
                  ))}
                </View>
              ) : pin.kind === 'pickup' && pin.label ? (
                <Text style={[styles.childMarkerName, styles.passengerPinLabel, { backgroundColor: pin.color }]}>
                  {pin.label}
                </Text>
              ) : null}
              <View
                style={[
                  pin.kind === 'destination' ? styles.destinationPinHead : styles.passengerPinHead,
                  { backgroundColor: pin.color },
                ]}
              >
                {pin.kind === 'pickup' && pin.pickedUp ? (
                  <View style={styles.passengerPinCheck}>
                    <Ionicons name="checkmark" size={13} color="#16A34A" />
                  </View>
                ) : null}
              </View>
              <View
                style={[
                  pin.kind === 'destination' ? styles.destinationPinTail : styles.passengerPinTail,
                  { borderTopColor: pin.color },
                ]}
              />
            </View>
          </Marker>
        ))}
        {taxiOnMap && !canOperateRides ? (
          <FluidTaxiMarker
            target={taxiOnMap}
            mapRef={parentMapRef}
            imageStyle={styles.taxiMarker}
            followRef={followTaxiRef}
          />
        ) : taxiOnMap ? (
          <Marker
            coordinate={taxiOnMap}
            title="Taxi"
            anchor={{ x: 0.5, y: 0.5 }}
            tracksViewChanges={trackTaxiHeading}
          >
            <View style={taxiMarkerFrame} collapsable={false}>
              <Image
                source={taxiCarYellow}
                style={[styles.taxiMarker, { transform: [{ rotate: `${taxiHeading}deg` }] }]}
                resizeMode="contain"
              />
            </View>
          </Marker>
        ) : null}
      </MapView>
      {taxiOnMap && !followTaxi ? (
        <Pressable
          style={styles.mapFocusBtn}
          onPress={focusTaxi}
          accessibilityRole="button"
          accessibilityLabel="Focus op de taxi"
        >
          <Ionicons name="locate" size={22} color={colors.text} />
        </Pressable>
      ) : null}
      </View>
      {childRides.length === 0 && !taxiOnMap ? (
        <View style={styles.parentNavEmpty} pointerEvents="none">
          <Text style={styles.parentNavEmptyText}>Geen open rit om te volgen.</Text>
        </View>
      ) : null}
      {canOperateRides ? (
        <Pressable
          style={[styles.primaryBtn, styles.navStartBtn, navStops.length === 0 && { opacity: 0.5 }]}
          disabled={navStops.length === 0}
          onPress={() => {
            void openPassengerRoute(navStops).catch(() => undefined);
          }}
          accessibilityRole="button"
          accessibilityLabel="Start navigatie"
        >
          <Text style={styles.primaryBtnText}>Start navigatie</Text>
        </Pressable>
      ) : null}
      {showSimulator ? (
        <View style={styles.simCard}>
          <Text style={styles.simTitle}>Ritsimulator</Text>
          <Text style={styles.simHint}>
            {simRunning
              ? simLabel
              : 'Laat de chauffeur de haltes afrijden. Bij elke stop wordt de status echt gezet.'}
          </Text>
          <View style={styles.simSpeedRow}>
            <Text style={styles.simSpeedLabel}>Snelheid</Text>
            <Text style={styles.simSpeedValue}>{simSpeed} km/u</Text>
          </View>
          <View
            style={styles.simTrack}
            onLayout={(event) => {
              simTrackWidth.current = event.nativeEvent.layout.width;
            }}
            onStartShouldSetResponder={() => true}
            onMoveShouldSetResponder={() => true}
            onResponderGrant={(event) => {
              const ratio = Math.min(
                1,
                Math.max(0, event.nativeEvent.locationX / Math.max(1, simTrackWidth.current))
              );
              const next = Math.round(20 + ratio * 140);
              simSpeedRef.current = next;
              setSimSpeed(next);
            }}
            onResponderMove={(event) => {
              const ratio = Math.min(
                1,
                Math.max(0, event.nativeEvent.locationX / Math.max(1, simTrackWidth.current))
              );
              const next = Math.round(20 + ratio * 140);
              simSpeedRef.current = next;
              setSimSpeed(next);
            }}
            accessibilityRole="adjustable"
            accessibilityLabel="Snelheid van de rit"
          >
            <View
              style={[
                styles.simFill,
                { width: `${((simSpeed - 20) / 140) * 100}%` },
              ]}
            />
            <View
              style={[
                styles.simThumb,
                { left: `${((simSpeed - 20) / 140) * 100}%` },
              ]}
            />
          </View>
          <Pressable
            style={[
              styles.primaryBtn,
              styles.simStartBtn,
              simStops.length === 0 && !simRunning && { opacity: 0.5 },
            ]}
            disabled={simStops.length === 0 && !simRunning}
            onPress={() => {
              if (simRunning) {
                setSimRunning(false);
                setSimLabel('Simulator gestopt.');
                return;
              }
              setSimLabel('Route laden…');
              setSimRunning(true);
            }}
            accessibilityRole="button"
            accessibilityLabel={simRunning ? 'Stop simulator' : 'Start simulator'}
          >
            <Text style={styles.primaryBtnText}>{simRunning ? 'Stop simulator' : 'Start simulator'}</Text>
          </Pressable>
          {isDemo ? (
            <Pressable
              style={[styles.secondaryBtn, styles.simResetBtn, simResetBusy && { opacity: 0.6 }]}
              disabled={simResetBusy}
              onPress={openSimReset}
              accessibilityRole="button"
              accessibilityLabel="Ritten resetten"
            >
              <Text style={styles.secondaryBtnText}>Ritten resetten</Text>
            </Pressable>
          ) : null}
        </View>
      ) : null}
      {passengers.length > 0 || childRides.length > 0 ? (
        <Text style={styles.childLegendHint}>
          Tik op de naam van een kind om de kleur aan te passen.
        </Text>
      ) : null}
      <View style={styles.childLegend}>
        {(passengers.length > 0 ? passengers : childRides.map((ride) => ({
          id: ride.passengerId,
          name: ride.name,
          map_color: ride.color,
        }))).map((child) => {
          const rideColor = childRides.find((ride) => ride.passengerId === child.id)?.color;
          const color = rideColor || childMapColor(child.id, child.map_color);
          return (
            <Pressable
              key={`legend-${child.id}`}
              style={styles.childLegendItem}
              onPress={() => setColorPassengerId(child.id)}
              accessibilityRole="button"
              accessibilityLabel={`Kleur van ${child.name}`}
            >
              <View style={[styles.childLegendDot, { backgroundColor: color }]} />
              <Text style={styles.childLegendName} numberOfLines={1}>
                {child.name}
              </Text>
            </Pressable>
          );
        })}
      </View>
    </View>
  );

  const absencesPanel = (
    <AppScrollView
      persistKey="contract-absences"
      contentContainerStyle={styles.scroll}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.text} />
      }
    >
      <ErrorText>{error}</ErrorText>
      <ScreenHeader title="Afmeldingen" />
      <Text style={styles.hint}>
        Meld een passagier af voor één of meer dagen. Intrekken kan zolang de dag nog openstaat.
      </Text>

      <Text style={styles.sectionLabel}>Passagiers</Text>
      {passengers.length === 0 ? (
        <Card>
          <Text style={styles.emptyHint}>Geen passagiers zichtbaar.</Text>
        </Card>
      ) : (
        passengers.map((p) => (
          <Card key={`p-${p.id}`}>
            <Text style={styles.rideName}>{p.name}</Text>
            {p.pickup_address ? (
              <Text style={styles.hint}>{shortAddress(p.pickup_address)}</Text>
            ) : null}
            {p.absent_today ? (
              <Text style={styles.rideMeta}>Vandaag afgemeld{p.absence_reason ? `: ${p.absence_reason}` : ''}</Text>
            ) : null}
            <Pressable style={styles.secondaryBtn} onPress={() => openAbsence(p.id)}>
              <Text style={styles.secondaryBtnText}>Afmelden</Text>
            </Pressable>
          </Card>
        ))
      )}

      <Text style={styles.sectionLabel}>Geplande afmeldingen</Text>
      {absences.length === 0 ? (
        <Card>
          <Text style={styles.emptyHint}>Geen openstaande afmeldingen.</Text>
        </Card>
      ) : (
        absences.map((a) => (
          <Card key={`a-${a.id}`}>
            <Text style={styles.rideName}>{a.passenger_name}</Text>
            <Text style={styles.rideMeta}>{formatDayLong(a.date)}</Text>
            {a.reason ? <Text style={styles.hint}>{a.reason}</Text> : null}
            <Pressable style={styles.secondaryBtn} onPress={() => cancelAbsence(a.id)}>
              <Text style={styles.secondaryBtnText}>Intrekken</Text>
            </Pressable>
          </Card>
        ))
      )}
    </AppScrollView>
  );

  const profilePanel = (
    <AppScrollView persistKey="contract-profile" contentContainerStyle={styles.scroll}>
      <ErrorText>{error}</ErrorText>
      <ScreenHeader title="Profiel" style={styles.profileHeader} />
      <View style={styles.profileCardWrap}>
        <Card>
          <Text style={styles.profileName}>{name || 'Contract'}</Text>
          <View style={styles.profileRow}>
            <Text style={styles.profileLabel}>E-mailadres</Text>
            <Text style={styles.profileValue}>{email || '—'}</Text>
          </View>
          <View style={styles.profileRow}>
            <Text style={styles.profileLabel}>Bedrijf</Text>
            <Text style={styles.profileValue}>{customerName || '—'}</Text>
          </View>
          <View style={styles.profileRowLast}>
            <Text style={styles.profileLabel}>Rol</Text>
            <Text style={styles.profileValue}>
              {portalRole ? ROLE_LABEL[portalRole] || portalRole : 'Contractant / contractouder'}
            </Text>
          </View>
        </Card>
      </View>

      <Text style={styles.sectionLabel}>Thema</Text>
      <View style={styles.optionList}>
        {THEME_OPTIONS.map((opt) => {
          const active = preference === opt.key;
          return (
            <Pressable
              key={opt.key}
              style={[styles.optionRow, active && styles.optionRowActive]}
              onPress={() => setPreference(opt.key)}
            >
              <View>
                <Text style={styles.optionTitle}>{opt.label}</Text>
                <Text style={styles.themeSub}>{opt.hint}</Text>
              </View>
              {active ? <Ionicons name="checkmark-circle" size={20} color={accentHex} /> : null}
            </Pressable>
          );
        })}
      </View>

      <Text style={styles.sectionLabel}>Kleur</Text>
      <View style={styles.accentRow}>
        {DRIVER_ACCENT_OPTIONS.map((opt) => {
          const active = accent === opt.key;
          return (
            <Pressable
              key={opt.key}
              style={[
                styles.accentDot,
                { backgroundColor: opt.hex },
                active && styles.accentDotActive,
              ]}
              onPress={() => chooseAccent(opt.key)}
              accessibilityLabel={opt.label}
            />
          );
        })}
      </View>

      {multi ? <GhostButton title="Ander scherm" onPress={() => setActiveScreen('driver')} /> : null}
      <Pressable
        style={styles.logoutBtn}
        onPress={() => {
          resetContractTabToTrips();
          seenRideIds.current.clear();
          ridesBootstrapped.current = false;
          setTab('trips');
          void logout();
        }}
      >
        <Text style={styles.logoutBtnText}>Uitloggen</Text>
      </Pressable>
    </AppScrollView>
  );

  let body: React.ReactNode = tripsPanel;
  if (tab === 'planning') body = planningPanel;
  else if (tab === 'navigation') body = parentNavigation;
  else if (tab === 'absences') body = absencesPanel;
  else if (tab === 'profile') body = profilePanel;

  const absencePassenger = passengers.find((p) => p.id === absencePassengerId);

  return (
    <DriverAccentProvider accent={accent}>
      <View style={[styles.shell, { backgroundColor: colors.bg }]}>
        <View style={styles.topBar}>
          <AppBrandLogo
            bare
            uri={logoUri}
            fallbackLabel={customerName || 'Contract'}
            accessibilityLabel={customerName || 'Bedrijfslogo'}
          />
        </View>

        <View style={styles.body}>{body}</View>
        <ContractTabBar
          active={tab}
          onChange={selectTab}
          tripsBadge={tripsBadge}
          accent={accentHex}
        />

        <AppModal
          visible={absenceOpen}
          onRequestClose={() => setAbsenceOpen(false)}
          dismissDisabled={absenceBusy}
        >
          <ScreenHeader title="Afmelden" />
          <Text style={styles.hint}>{absencePassenger?.name || 'Passagier'}</Text>
          <Text style={styles.fieldLabel}>Van (jjjj-mm-dd)</Text>
          <TextInput
            style={styles.input}
            value={absenceFrom}
            onChangeText={setAbsenceFrom}
            autoCapitalize="none"
            placeholder="2026-10-07"
            placeholderTextColor={colors.muted}
          />
          <Text style={styles.fieldLabel}>Tot (jjjj-mm-dd)</Text>
          <TextInput
            style={styles.input}
            value={absenceTo}
            onChangeText={setAbsenceTo}
            autoCapitalize="none"
            placeholder="2026-10-07"
            placeholderTextColor={colors.muted}
          />
          <Text style={styles.fieldLabel}>Reden (optioneel)</Text>
          <TextInput
            style={styles.input}
            value={absenceReason}
            onChangeText={setAbsenceReason}
            placeholder="Bijv. ziek"
            placeholderTextColor={colors.muted}
          />
          <ErrorText>{error}</ErrorText>
          <Pressable
            style={[styles.primaryBtn, absenceBusy && { opacity: 0.6 }]}
            disabled={absenceBusy}
            onPress={() => void submitAbsence()}
          >
            <Text style={styles.primaryBtnText}>
              {absenceBusy ? 'Bezig…' : 'Afmelden'}
            </Text>
          </Pressable>
          <GhostButton title="Annuleren" onPress={() => setAbsenceOpen(false)} />
        </AppModal>

        <AppModal
          visible={destReachOpen}
          onRequestClose={closeDestReach}
          dismissDisabled={destReachBusy}
        >
          <ScreenHeader title="Bestemming bereikt" />
          <Text style={styles.hint}>
            Dit zet de bestemming op bereikt voor de passagiers bij de bestemming waar de auto staat.
          </Text>
          <ErrorText>{destReachError}</ErrorText>
          <Pressable
            style={[styles.primaryBtn, destReachBusy && { opacity: 0.6 }]}
            disabled={destReachBusy}
            onPress={() => void confirmReachAllDestinations()}
          >
            <Text style={styles.primaryBtnText}>
              {destReachBusy ? 'Bezig…' : 'Bevestigen'}
            </Text>
          </Pressable>
          <GhostButton title="Annuleren" onPress={closeDestReach} />
        </AppModal>

        <AppModal
          visible={simResetOpen}
          onRequestClose={closeSimReset}
          dismissDisabled={simResetBusy}
        >
          <ScreenHeader title="Ritten resetten" />
          <Text style={styles.hint}>
            Dit zet de ritten van vandaag terug, zodat je de simulator opnieuw kunt testen.
          </Text>
          <ErrorText>{simResetError}</ErrorText>
          <Pressable
            style={[styles.primaryBtn, simResetBusy && { opacity: 0.6 }]}
            disabled={simResetBusy}
            onPress={() => {
              void confirmSimReset();
            }}
          >
            <Text style={styles.primaryBtnText}>{simResetBusy ? 'Bezig…' : 'Resetten'}</Text>
          </Pressable>
          <GhostButton title="Annuleren" onPress={closeSimReset} />
        </AppModal>

        <AppModal
          visible={colorPassengerId != null}
          onRequestClose={() => setColorPassengerId(null)}
        >
          <ScreenHeader title="Kleur van het kind" />
          <Text style={styles.hint}>{colorPassengerName}</Text>
          <View style={styles.colorGrid}>
            {CHILD_MAP_COLORS.map((swatch, index) => {
              const current =
                (colorPassengerId != null ? colorOverrides[colorPassengerId] : null) ||
                childMapColor(colorPassengerId || 0, colorPassengerStored);
              const selected = current.toUpperCase() === swatch.toUpperCase();
              return (
                <Pressable
                  key={swatch}
                  hitSlop={6}
                  style={[
                    styles.colorSwatch,
                    { backgroundColor: swatch },
                    selected && styles.colorSwatchSelected,
                  ]}
                  onPress={() => {
                    if (colorPassengerId != null) void saveChildColor(colorPassengerId, swatch);
                  }}
                  accessibilityRole="button"
                  accessibilityLabel={CHILD_COLOR_LABELS[index] || 'Kleur'}
                />
              );
            })}
          </View>
          <GhostButton title="Annuleren" onPress={() => setColorPassengerId(null)} />
        </AppModal>
      </View>
    </DriverAccentProvider>
  );
}

function makeStyles(colors: ColorPalette, accentHex: string) {
  return StyleSheet.create({
    shell: {
      flex: 1,
      minHeight: 0,
    },
    topBar: {
      paddingHorizontal: APP_PAGE_HEADER_PAD_H,
      paddingTop: APP_PAGE_TOP_INSET + 2,
      paddingBottom: 2,
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: APP_LOGO_BAR_MIN_HEIGHT,
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
    statusFilterRow: {
      flexDirection: 'row',
      borderRadius: 999,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      overflow: 'hidden',
      backgroundColor: colors.card,
      marginBottom: 12,
    },
    statusFilterBtn: {
      flex: 1,
      paddingVertical: 8,
      alignItems: 'center',
    },
    statusFilterBtnActive: {
      backgroundColor: accentHex,
    },
    statusFilterText: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '700',
    },
    statusFilterTextActive: {
      color: '#fff',
    },
    headerDate: {
      color: colors.text,
      fontSize: 14,
      fontWeight: '700',
      lineHeight: 24,
      textAlign: 'right',
      textTransform: 'capitalize',
    },
    navRitLabel: {
      color: colors.muted,
      fontSize: 14,
      fontWeight: '700',
      lineHeight: 24,
      textAlign: 'right',
    },
    profileHeader: {
      marginBottom: 16,
    },
    profileCardWrap: {
      marginBottom: 8,
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
    profileRowLast: {
      marginBottom: 0,
    },
    profileLabel: {
      color: colors.muted,
      fontSize: 11,
      fontWeight: '700',
      letterSpacing: 0.6,
      textTransform: 'uppercase',
      marginBottom: 3,
    },
    profileValue: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '600',
    },
    navHubBanner: {
      borderWidth: 1.5,
      borderColor: hexAlpha(accentHex, 0.55),
      backgroundColor: hexAlpha(accentHex, 0.12),
      borderRadius: 14,
      paddingHorizontal: 14,
      paddingVertical: 12,
      marginBottom: 14,
    },
    navHubLabel: {
      color: accentHex,
      fontSize: 11,
      fontWeight: '800',
      textTransform: 'uppercase',
      letterSpacing: 0.4,
      marginBottom: 4,
    },
    navHubValue: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '700',
      lineHeight: 21,
    },
    navStopList: {
      gap: 8,
      marginBottom: 16,
    },
    navStopChip: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
      borderWidth: 1,
      borderColor: colors.border,
      backgroundColor: colors.card,
      borderRadius: 12,
      paddingHorizontal: 10,
      paddingVertical: 10,
    },
    navStopIndex: {
      width: 28,
      height: 28,
      borderRadius: 8,
      alignItems: 'center',
      justifyContent: 'center',
    },
    navStopIndexText: {
      fontSize: 13,
      fontWeight: '800',
    },
    navStopBody: {
      flex: 1,
      minWidth: 0,
    },
    navStopTitle: {
      color: colors.text,
      fontSize: 14,
      fontWeight: '700',
      marginBottom: 2,
    },
    navStopAddr: {
      color: colors.muted,
      fontSize: 12,
      lineHeight: 16,
    },
    destBanner: {
      borderWidth: 1.5,
      borderColor: hexAlpha(accentHex, 0.55),
      backgroundColor: hexAlpha(accentHex, 0.12),
      borderRadius: 14,
      paddingHorizontal: 14,
      paddingVertical: 12,
      marginBottom: 14,
    },
    destLabel: {
      color: accentHex,
      fontSize: 11,
      fontWeight: '800',
      textTransform: 'uppercase',
      letterSpacing: 0.4,
      marginBottom: 4,
    },
    destValue: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '700',
      lineHeight: 21,
    },
    parentNav: {
      flex: 1,
      minHeight: 0,
    },
    parentNavHeader: {
      paddingHorizontal: 20,
      paddingTop: 4,
    },
    parentNavMapWrap: {
      flex: 1,
      minHeight: 280,
    },
    parentNavMap: {
      flex: 1,
      minHeight: 280,
    },
    mapFocusBtn: {
      position: 'absolute',
      right: 16,
      bottom: 16,
      width: 44,
      height: 44,
      borderRadius: 22,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: colors.card,
      borderWidth: 1,
      borderColor: colors.border,
    },
    navStartBtn: {
      marginHorizontal: 16,
      marginTop: 10,
      marginBottom: 0,
    },
    simCard: {
      marginHorizontal: 16,
      marginTop: 12,
      gap: 8,
    },
    simTitle: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '700',
    },
    simHint: {
      color: colors.muted,
      fontSize: 13,
      lineHeight: 18,
    },
    simSpeedRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
    },
    simSpeedLabel: {
      color: colors.text,
      fontSize: 14,
      fontWeight: '600',
    },
    simSpeedValue: {
      color: colors.text,
      fontSize: 14,
      fontWeight: '700',
    },
    simTrack: {
      height: 28,
      borderRadius: 14,
      backgroundColor: colors.card,
      justifyContent: 'center',
    },
    simFill: {
      position: 'absolute',
      left: 0,
      top: 0,
      bottom: 0,
      borderRadius: 14,
      backgroundColor: accentHex,
      opacity: 0.45,
    },
    simThumb: {
      position: 'absolute',
      width: 22,
      height: 22,
      marginLeft: -11,
      borderRadius: 11,
      backgroundColor: accentHex,
    },
    simStartBtn: {
      marginHorizontal: 0,
      marginTop: 4,
    },
    simResetBtn: {
      marginTop: 0,
    },
    parentNavEmpty: {
      position: 'absolute',
      left: 24,
      right: 24,
      top: '46%',
      alignItems: 'center',
    },
    parentNavEmptyText: {
      color: colors.text,
      backgroundColor: colors.card,
      overflow: 'hidden',
      borderRadius: 10,
      paddingHorizontal: 12,
      paddingVertical: 8,
      fontSize: 13,
      fontWeight: '700',
    },
    childMarker: {
      alignItems: 'center',
      paddingHorizontal: 6,
      paddingTop: 2,
    },
    passengerPinLabel: {
      marginTop: 0,
      marginBottom: 2,
    },
    passengerPinHead: {
      width: 22,
      height: 22,
      borderRadius: 11,
      borderWidth: 2,
      borderColor: '#fff',
      alignItems: 'center',
      justifyContent: 'center',
    },
    passengerPinCheck: {
      width: 14,
      height: 14,
      borderRadius: 7,
      backgroundColor: '#fff',
      alignItems: 'center',
      justifyContent: 'center',
    },
    passengerPinTail: {
      width: 0,
      height: 0,
      marginTop: -1,
      borderLeftWidth: 6,
      borderRightWidth: 6,
      borderTopWidth: 8,
      borderLeftColor: 'transparent',
      borderRightColor: 'transparent',
    },
    destinationNames: {
      alignItems: 'center',
      marginBottom: 4,
      gap: 3,
    },
    destinationCaption: {
      color: '#fff',
      backgroundColor: '#15803D',
      overflow: 'hidden',
      fontSize: 10,
      fontWeight: '800',
      paddingHorizontal: 6,
      paddingVertical: 2,
      borderRadius: 6,
    },
    destinationNameRow: {
      flexDirection: 'row',
      alignItems: 'center',
      borderRadius: 6,
      paddingLeft: 6,
      paddingRight: 3,
      paddingVertical: 2,
      gap: 4,
    },
    destinationNameText: {
      color: '#fff',
      fontSize: 12,
      fontWeight: '800',
    },
    destinationPinHead: {
      width: 30,
      height: 30,
      borderRadius: 15,
      borderWidth: 3,
      borderColor: '#fff',
    },
    destinationPinTail: {
      width: 0,
      height: 0,
      marginTop: -1,
      borderLeftWidth: 10,
      borderRightWidth: 10,
      borderTopWidth: 14,
      borderLeftColor: 'transparent',
      borderRightColor: 'transparent',
    },
    childMarkerName: {
      marginTop: 2,
      color: '#fff',
      fontSize: 10,
      fontWeight: '800',
      paddingHorizontal: 6,
      paddingVertical: 2,
      borderRadius: 6,
    },
    childLegendHint: {
      color: colors.muted,
      fontSize: 12,
      lineHeight: 16,
      paddingHorizontal: 16,
      paddingTop: 10,
    },
    childLegend: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 8,
      paddingHorizontal: 16,
      paddingTop: 8,
      paddingBottom: 12,
    },
    childLegendItem: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 6,
      paddingVertical: 6,
      paddingHorizontal: 8,
      borderRadius: 999,
      backgroundColor: colors.card,
    },
    childLegendDot: {
      width: 12,
      height: 12,
      borderRadius: 6,
    },
    childLegendName: {
      color: colors.text,
      fontSize: 13,
      fontWeight: '700',
      maxWidth: 140,
    },
    colorGrid: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 12,
      marginTop: 8,
      marginBottom: 8,
    },
    colorSwatch: {
      width: 44,
      height: 44,
      borderRadius: 22,
    },
    colorSwatchSelected: {
      borderWidth: 3,
      borderColor: colors.text,
    },
    taxiMarker: {
      width: 26,
      height: 39,
    },
    sectionLabel: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '700',
      textTransform: 'uppercase',
      letterSpacing: 0.4,
      marginBottom: 8,
      marginTop: 12,
    },
    emptyTitle: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '700',
      marginBottom: 6,
    },
    hint: { color: colors.muted, fontSize: 14, lineHeight: 20, marginBottom: 10 },
    emptyHint: {
      color: colors.muted,
      fontSize: 14,
      lineHeight: 20,
      marginTop: 0,
      marginBottom: 0,
    },
    rideCard: {
      borderWidth: 1.5,
      borderRadius: 16,
      backgroundColor: colors.card,
      marginBottom: 14,
      paddingTop: 14,
      paddingHorizontal: 14,
      paddingBottom: 14,
      overflow: 'hidden',
      position: 'relative',
    },
    rideCardCollapsed: {
      paddingBottom: 12,
    },
    rideCardOverdue: {
      borderWidth: 1.5,
    },
    rideCardAbsent: {
      borderWidth: 1.5,
      opacity: 0.88,
    },
    rideAccentBar: {
      position: 'absolute',
      left: 0,
      top: 0,
      bottom: 0,
      width: 3,
    },
    rideCardHeader: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: 8,
    },
    rideExpandIcon: {
      marginTop: 2,
    },
    badgeRow: {
      flex: 1,
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 6,
      marginBottom: 8,
      paddingLeft: 4,
    },
    rideMetaCollapsed: {
      marginBottom: 6,
    },
    rideCollapsedSummary: {
      paddingLeft: 4,
      gap: 4,
    },
    rideCollapsedStatus: {
      fontSize: 13,
      fontWeight: '800',
    },
    rideCollapsedRoute: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '600',
      lineHeight: 18,
    },
    rideCollapsedIcons: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 16,
      marginTop: 6,
    },
    rideCollapsedIcon: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 4,
    },
    rideCollapsedIconLabel: {
      fontSize: 11,
      fontWeight: '700',
    },
    badge: {
      alignSelf: 'flex-start',
      flexDirection: 'row',
      alignItems: 'center',
      borderRadius: 999,
      paddingHorizontal: 10,
      paddingVertical: 4,
    },
    badgeText: {
      fontSize: 10,
      fontWeight: '800',
      textTransform: 'uppercase',
      letterSpacing: 0.35,
    },
    badgeLeg: {
      backgroundColor: 'rgba(148,163,184,0.16)',
    },
    badgeLegText: {
      color: colors.text,
      fontSize: 10,
      fontWeight: '800',
      textTransform: 'uppercase',
      letterSpacing: 0.35,
    },
    badgeDanger: {
      backgroundColor: 'rgba(239,68,68,0.18)',
    },
    badgeDangerText: {
      color: '#FCA5A5',
      fontSize: 10,
      fontWeight: '800',
      textTransform: 'uppercase',
      letterSpacing: 0.35,
    },
    badgeNeutral: {
      backgroundColor: 'rgba(148,163,184,0.14)',
    },
    badgeNeutralText: {
      color: colors.muted,
      fontSize: 10,
      fontWeight: '800',
      textTransform: 'uppercase',
      letterSpacing: 0.35,
    },
    badgeAbsent: {
      backgroundColor: 'rgba(148,163,184,0.28)',
      borderWidth: 1,
      borderColor: 'rgba(148,163,184,0.55)',
    },
    badgeAbsentText: {
      color: ABSENT_GREY,
      fontSize: 10,
      fontWeight: '800',
      textTransform: 'uppercase',
      letterSpacing: 0.35,
    },
    rideName: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '800',
      marginBottom: 4,
      paddingLeft: 4,
    },
    rideMeta: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '600',
      marginBottom: 10,
      paddingLeft: 4,
    },
    route: {
      position: 'relative',
      marginBottom: 12,
      paddingLeft: 4,
    },
    routeRail: {
      position: 'absolute',
      left: 13,
      top: 22,
      bottom: 22,
      width: 2,
      backgroundColor: hexAlpha(accentHex, 0.35),
      borderRadius: 2,
    },
    routeStop: {
      marginBottom: 10,
    },
    routeStopFirst: {},
    routeStopLast: { marginBottom: 0 },
    routeHead: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
      marginBottom: 4,
    },
    routeLabel: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '600',
    },
    routeAddress: {
      paddingLeft: 30,
    },
    routeMain: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '800',
      lineHeight: 21,
    },
    routeSub: {
      color: colors.muted,
      fontSize: 12,
      marginTop: 2,
      lineHeight: 16,
    },
    dotOuter: {
      width: 20,
      height: 20,
      borderRadius: 999,
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 1,
    },
    dotOuterPickup: {
      backgroundColor: 'rgba(34,197,94,0.18)',
    },
    dotOuterDropoff: {
      backgroundColor: hexAlpha(accentHex, 0.18),
    },
    dot: {
      width: 10,
      height: 10,
      borderRadius: 999,
    },
    dotPickup: {
      backgroundColor: DEST_GREEN,
    },
    dotDropoff: {
      backgroundColor: accentHex,
    },
    statusBanner: {
      marginTop: 10,
      marginBottom: 16,
      borderRadius: 10,
      paddingVertical: 10,
      paddingHorizontal: 12,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.35),
      backgroundColor: hexAlpha(accentHex, 0.1),
    },
    statusBannerSuccess: {
      borderColor: hexAlpha(DEST_GREEN, 0.45),
      backgroundColor: hexAlpha(DEST_GREEN, 0.14),
    },
    statusBannerInfo: {
      borderColor: hexAlpha('#3B82F6', 0.45),
      backgroundColor: hexAlpha('#3B82F6', 0.14),
    },
    statusBannerWarn: {
      borderColor: hexAlpha(OVERDUE_RED, 0.45),
      backgroundColor: hexAlpha(OVERDUE_RED, 0.12),
    },
    statusBannerDanger: {
      borderColor: hexAlpha(OVERDUE_RED, 0.5),
      backgroundColor: hexAlpha(OVERDUE_RED, 0.16),
    },
    statusBannerMuted: {
      borderColor: hexAlpha(ABSENT_GREY, 0.45),
      backgroundColor: hexAlpha(ABSENT_GREY, 0.14),
    },
    statusBannerText: {
      color: accentHex,
      fontSize: 13,
      fontWeight: '800',
      textAlign: 'left',
    },
    statusBannerTextSuccess: { color: DEST_GREEN },
    statusBannerTextInfo: { color: '#60A5FA' },
    statusBannerTextWarn: { color: OVERDUE_RED },
    statusBannerTextMuted: { color: ABSENT_GREY },
    statusBannerHint: {
      marginTop: 6,
      color: colors.muted,
      fontSize: 11,
      lineHeight: 15,
      fontWeight: '500',
      textAlign: 'left',
    },
    rideIconBar: {
      flexDirection: 'row',
      alignItems: 'stretch',
      justifyContent: 'space-between',
      gap: 2,
      marginTop: 4,
      paddingTop: 10,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: colors.border,
    },
    rideIconTab: {
      flex: 1,
      minWidth: 0,
      alignItems: 'center',
      justifyContent: 'center',
      paddingVertical: 6,
      paddingHorizontal: 1,
      borderRadius: 10,
      minHeight: 52,
      overflow: 'hidden',
    },
    rideIconLabel: {
      marginTop: 3,
      fontSize: 10,
      fontWeight: '700',
      textAlign: 'center',
      width: '100%',
      maxWidth: '100%',
      color: colors.muted,
    },
    actionBtn: {
      marginTop: 10,
      flexDirection: 'row',
      alignItems: 'center',
      gap: 6,
    },
    actionText: {
      color: accentHex,
      fontSize: 14,
      fontWeight: '700',
    },
    secondaryBtn: {
      marginTop: 8,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      borderRadius: 12,
      paddingVertical: 10,
      alignItems: 'center',
    },
    secondaryBtnText: {
      color: colors.text,
      fontSize: 14,
      fontWeight: '700',
    },
    primaryBtn: {
      marginTop: 8,
      backgroundColor: accentHex,
      borderRadius: 12,
      paddingVertical: 14,
      alignItems: 'center',
    },
    primaryBtnText: {
      color: '#fff',
      fontSize: 15,
      fontWeight: '800',
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
    periodBtnActive: { backgroundColor: accentHex },
    periodBtnText: { color: colors.muted, fontSize: 13, fontWeight: '700' },
    periodBtnTextActive: { color: '#fff' },
    navRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
      marginBottom: 12,
    },
    navBtn: {
      width: 40,
      height: 40,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      backgroundColor: colors.card,
      alignItems: 'center',
      justifyContent: 'center',
    },
    todayBtn: {
      borderRadius: 12,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      backgroundColor: colors.card,
      paddingHorizontal: 14,
      paddingVertical: 10,
    },
    todayBtnText: { color: colors.text, fontSize: 13, fontWeight: '700' },
    navSpacer: { flex: 1 },
    headingLabel: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '800',
      marginBottom: 12,
      textTransform: 'capitalize',
    },
    weekDays: {
      flexDirection: 'row',
      gap: 6,
      marginBottom: 14,
    },
    weekDay: {
      flex: 1,
      alignItems: 'center',
      borderRadius: 12,
      borderWidth: 1,
      borderColor: colors.border,
      backgroundColor: colors.card,
      paddingVertical: 8,
    },
    weekDayActive: {
      borderColor: accentHex,
      backgroundColor: hexAlpha(accentHex, 0.16),
    },
    weekDayName: { color: colors.muted, fontSize: 11, fontWeight: '700' },
    weekDayNameActive: { color: accentHex },
    weekDayNum: { color: colors.text, fontSize: 16, fontWeight: '800', marginTop: 2 },
    weekDayNumActive: { color: accentHex },
    weekDayCount: { color: colors.muted, fontSize: 11, marginTop: 2 },
    weekDayCountActive: { color: accentHex },
    optionList: { gap: 8, marginBottom: 12 },
    optionRow: {
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 12,
      padding: 12,
      backgroundColor: colors.card,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
    },
    optionRowActive: {
      borderColor: accentHex,
      backgroundColor: hexAlpha(accentHex, 0.12),
    },
    optionTitle: { color: colors.text, fontSize: 15, fontWeight: '700' },
    themeSub: { color: colors.muted, fontSize: 12, marginTop: 2 },
    accentRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 10,
      marginBottom: 16,
    },
    accentDot: {
      width: 34,
      height: 34,
      borderRadius: 999,
      borderWidth: 2,
      borderColor: 'transparent',
    },
    accentDotActive: {
      borderColor: colors.text,
    },
    logoutBtn: {
      marginTop: 12,
      borderRadius: 14,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      minHeight: 48,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: 'transparent',
    },
    logoutBtnText: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '700',
    },
    fieldLabel: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '700',
      marginBottom: 6,
      marginTop: 8,
      textTransform: 'uppercase',
    },
    input: {
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 12,
      paddingHorizontal: 14,
      paddingVertical: 12,
      color: colors.text,
      backgroundColor: colors.bg,
      fontSize: 15,
    },
  });
}
