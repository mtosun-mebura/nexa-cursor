import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  Alert,
  Image,
  Linking,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import {
  ContractAbsence,
  ContractDayItem,
  ContractLeg,
  ContractNavStop,
  ContractPassenger,
  ContractToday,
  ContractWeek,
  completeContractRide,
  destroyContractAbsence,
  fetchContractAbsences,
  fetchContractMe,
  fetchContractPassengers,
  fetchContractToday,
  fetchContractWeek,
  startContractRide,
  storeContractAbsence,
  updateContractAccent,
} from '../api/contract';
import { buildActiveNavigation } from '../api/contractNavigation';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { API_BASE_URL, ColorPalette } from '../config';
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

function ritLabel(legKey?: string | null, legLabel?: string | null): string {
  const key = String(legKey || '').toLowerCase();
  if (key === 'retour' || /terug|retour/i.test(String(legLabel || ''))) return 'Terugweg';
  if (key === 'heen' || /heen/i.test(String(legLabel || ''))) return 'Heenweg';
  const label = String(legLabel || '').trim();
  return label || 'Rit';
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

const OVERDUE_RED = '#EF4444';
const DEST_GREEN = '#22C55E';
const ABSENT_GREY = '#94A3B8';

function openMapsAddress(address?: string | null) {
  const text = String(address || '').trim();
  if (!text) return;
  const url = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(text)}`;
  Linking.openURL(url).catch(() => undefined);
}

function openMapsStops(stops: ContractNavStop[]) {
  const withAddr = stops
    .map((s) => String(s.address || '').trim())
    .filter(Boolean);
  if (withAddr.length === 0) return;
  if (withAddr.length === 1) {
    openMapsAddress(withAddr[0]);
    return;
  }
  const destination = withAddr[withAddr.length - 1];
  const waypoints = withAddr.slice(0, -1).map(encodeURIComponent).join('|');
  const url =
    `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination)}` +
    (waypoints ? `&waypoints=${waypoints}` : '');
  Linking.openURL(url).catch(() => undefined);
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

function legIsExpired(leg: ContractLeg, item: ContractDayItem): boolean {
  return (
    isExpiredStatus(leg.status, leg.status_key) ||
    isExpiredStatus(item.status, item.status_key) ||
    isExpiredStatus(item.day_status_label, item.day_status)
  );
}

function legIsTerminal(leg: ContractLeg, item: ContractDayItem): boolean {
  const key = String(leg.status_key || item.status_key || item.day_status || '').toLowerCase();
  return key === 'completed' || key === 'absent';
}

function legMatchesStatusFilter(
  leg: ContractLeg,
  item: ContractDayItem,
  filter: TripStatusFilter
): boolean {
  if (filter === 'all') return true;
  const expired = legIsExpired(leg, item);
  const terminal = legIsTerminal(leg, item);
  if (filter === 'expired') return expired && !terminal;
  return !expired && !terminal;
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
  const multi = (capabilities?.screens?.length || 0) > 1;
  const canOperateRides = isContractant || isContractantRole(portalRole);
  const accentHex = driverAccentHex(accent);
  const styles = useMemo(() => makeStyles(colors, accentHex), [colors, accentHex]);
  const logoUri =
    resolveMediaUrl(colorScheme === 'light' ? logoLight || logoDark : logoDark || logoLight) ||
    null;

  const refresh = useCallback(async (fromOverride?: string) => {
    if (!contractToken) return;
    setError(null);
    const from = fromOverride || weekFrom;
    try {
      const [me, todayRes, weekRes, passengersRes, absencesRes] = await Promise.all([
        fetchContractMe(contractToken),
        fetchContractToday(contractToken),
        fetchContractWeek(contractToken, from),
        fetchContractPassengers(contractToken),
        fetchContractAbsences(contractToken),
      ]);
      setName(me.user?.name || session?.user?.name || '');
      setEmail(me.user?.email || session?.user?.email || '');
      setCustomerName(me.user?.company_name || todayRes.data?.customer_name || '');
      setLogoLight(me.user?.company_logo_url || null);
      setLogoDark(me.user?.company_logo_dark_url || null);
      setPortalRole(me.user?.portal_role || null);
      setIsContractant(
        isContractantRole(me.user?.portal_role, me.user?.is_contractant)
      );
      if (me.user?.pwa_accent) setAccent(normalizeDriverAccent(me.user.pwa_accent));
      setToday(todayRes.data || null);
      setWeek(weekRes.data || null);
      if (weekRes.data?.from) setWeekFrom(weekRes.data.from);
      setPassengers(passengersRes.data?.passengers || []);
      setAbsences(absencesRes.data?.absences || []);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Kon contractgegevens niet laden.');
    }
  }, [contractToken, session?.user?.name, weekFrom]);

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
    void refresh();
    // Alleen bij login/token; weeknavigatie via loadWeek.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [contractToken]);

  async function loadWeek(from: string) {
    setWeekFrom(from);
    await refresh(from);
  }

  async function onRefresh() {
    setRefreshing(true);
    await refresh();
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
      setError(e instanceof ApiError ? e.message : 'Afmelden mislukt.');
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
            setError(e instanceof ApiError ? e.message : 'Intrekken mislukt.');
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
      setError(e instanceof ApiError ? e.message : 'Rit starten mislukt.');
    } finally {
      setRideActionStopId(null);
    }
  }

  async function onCompleteLeg(rideStopId?: number) {
    if (!contractToken || !rideStopId) return;
    Alert.alert('Rit afronden', 'Weet je zeker dat je deze rit wilt afronden?', [
      { text: 'Nee', style: 'cancel' },
      {
        text: 'Afronden',
        style: 'default',
        onPress: async () => {
          setRideActionStopId(rideStopId);
          setError(null);
          try {
            await completeContractRide(contractToken, rideStopId);
            await refresh();
          } catch (e) {
            setError(e instanceof ApiError ? e.message : 'Rit afronden mislukt.');
          } finally {
            setRideActionStopId(null);
          }
        },
      },
    ]);
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

  const activeNav = useMemo(
    () => buildActiveNavigation(today?.items || []),
    [today?.items]
  );
  const navStops = activeNav.stops || [];
  const hasActiveNav = Boolean(activeNav.leg_key) || navStops.length > 0;
  const navRit = hasActiveNav
    ? ritLabel(activeNav.leg_key, activeNav.leg_label)
    : 'Geen actieve rit';
  const navHubLabel = activeNav.hub_label;
  const navHubAddress = activeNav.hub_address;
  const tripsBadge = useMemo(() => {
    if (tab === 'trips') return 0;
    const seen = new Set(tripsSeenKeys);
    return actionableTripKeys.filter((key) => !seen.has(key)).length;
  }, [tab, tripsSeenKeys, actionableTripKeys]);

  function renderRideCard(item: ContractDayItem, dateLabel?: string) {
    const legs = itemLegs(item);

    return (
      <View key={`${dateLabel || 'd'}-${item.passenger_id}`}>
        {legs.map((leg, index) => {
          const pickupRaw = leg.pickup_address || item.pickup_address;
          const dropoffRaw = leg.destination_address || item.destination_address;
          const pickup = splitAddress(pickupRaw);
          const dropoff = splitAddress(dropoffRaw);
          const absent =
            isAbsentStatus(leg.status, leg.status_key) ||
            isAbsentStatus(item.status, item.status_key) ||
            isAbsentStatus(item.day_status_label, item.day_status) ||
            !!item.absence_id;
          const expired =
            !absent &&
            (isExpiredStatus(leg.status, leg.status_key) ||
              isExpiredStatus(item.status, item.status_key) ||
              isExpiredStatus(item.day_status_label, item.day_status));
          const statusText = absent
            ? 'Afgemeld'
            : expired
              ? 'Verlopen'
              : leg.status || item.day_status_label || item.status || 'Gepland';
          const time = formatTime(leg.planned_at || item.planned_at);
          const label = legDisplayLabel(leg);
          const showStart = !absent && legCanStart(leg, item, canOperateRides);
          const showComplete = !absent && legCanComplete(leg, item, canOperateRides);
          const cardBorder = absent
            ? hexAlpha(ABSENT_GREY, 0.55)
            : expired
              ? OVERDUE_RED
              : hexAlpha(accentHex, 0.55);
          const cardAccent = absent ? ABSENT_GREY : expired ? OVERDUE_RED : accentHex;

          return (
            <View
              key={`${item.passenger_id}-leg-${leg.leg_key || index}-${leg.ride_stop_id || index}`}
              style={[
                styles.rideCard,
                { borderColor: cardBorder },
                expired && styles.rideCardOverdue,
                absent && styles.rideCardAbsent,
              ]}
            >
              <View
                style={[
                  styles.rideAccentBar,
                  { backgroundColor: cardAccent },
                ]}
              />
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
                ) : expired ? (
                  <View style={[styles.badge, styles.badgeDanger]}>
                    <Text style={styles.badgeDangerText}>Ophaalmoment verlopen</Text>
                  </View>
                ) : (
                  <View style={[styles.badge, styles.badgeNeutral]}>
                    <Text style={styles.badgeNeutralText}>{statusText}</Text>
                  </View>
                )}
              </View>

              <Text style={styles.rideName}>{item.name}</Text>
              <Text style={styles.rideMeta}>
                {time}
                {dateLabel ? ` · ${formatDayLong(dateLabel)}` : ''}
              </Text>

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
                  style={styles.rideIconTab}
                  onPress={() => openMapsAddress(pickupRaw)}
                  accessibilityRole="button"
                  accessibilityLabel="Ophalen"
                >
                  <Ionicons name="navigate-outline" size={22} color={colors.muted} />
                  <Text style={styles.rideIconLabel} numberOfLines={1} adjustsFontSizeToFit minimumFontScale={0.7}>
                    Ophalen
                  </Text>
                </Pressable>
                <Pressable
                  style={styles.rideIconTab}
                  onPress={() => openMapsAddress(dropoffRaw)}
                  accessibilityRole="button"
                  accessibilityLabel="Bestemming"
                >
                  <Ionicons name="flag-outline" size={22} color={colors.muted} />
                  <Text style={styles.rideIconLabel} numberOfLines={1} adjustsFontSizeToFit minimumFontScale={0.7}>
                    Bestemming
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
                    <Text style={styles.rideIconLabel} numberOfLines={1} adjustsFontSizeToFit minimumFontScale={0.7}>
                      {rideActionStopId === leg.ride_stop_id ? 'Bezig…' : 'Starten'}
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
                    onPress={() => void onCompleteLeg(leg.ride_stop_id)}
                    accessibilityRole="button"
                    accessibilityLabel="Afronden"
                  >
                    <Ionicons name="checkmark-circle-outline" size={22} color={colors.muted} />
                    <Text style={styles.rideIconLabel} numberOfLines={1} adjustsFontSizeToFit minimumFontScale={0.7}>
                      {rideActionStopId === leg.ride_stop_id && !showStart ? 'Bezig…' : 'Afronden'}
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
                    <Text style={styles.rideIconLabel} numberOfLines={1} adjustsFontSizeToFit minimumFontScale={0.7}>
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
                    <Text style={styles.rideIconLabel} numberOfLines={1} adjustsFontSizeToFit minimumFontScale={0.7}>
                      Intrekken
                    </Text>
                  </Pressable>
                ) : null}
              </View>
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
      {today?.destination_summary ? (
        <View style={styles.destBanner}>
          <Text style={styles.destLabel}>Bestemming</Text>
          <Text style={styles.destValue}>{today.destination_summary}</Text>
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

  const navigationPanel = (
    <AppScrollView
      persistKey="contract-navigation"
      contentContainerStyle={styles.scroll}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.text} />
      }
    >
      <ErrorText>{error}</ErrorText>
      <ScreenHeader
        title="Navigatie"
        right={<Text style={styles.navRitLabel}>Rit: {navRit}</Text>}
      />

      {navHubAddress ? (
        <View style={styles.navHubBanner}>
          <Text style={styles.navHubLabel}>{navHubLabel || 'Doel'}</Text>
          <Text style={styles.navHubValue}>{navHubAddress}</Text>
        </View>
      ) : null}

      {navStops.length === 0 ? (
        <Card>
          <Text style={styles.emptyHint}>Geen openstaande stops voor navigatie vandaag.</Text>
        </Card>
      ) : (
        <>
          <Text style={styles.sectionLabel}>Route ({navStops.length} stops)</Text>
          <View style={styles.navStopList}>
            {navStops.map((stop, index) => {
              const isPickup = String(stop.kind || '').toLowerCase() === 'pickup';
              const title =
                stop.name ||
                stop.label ||
                (isPickup ? 'Ophalen' : 'Afzetten') ||
                `Stop ${index + 1}`;
              const addr = splitAddress(stop.address);
              return (
                <Pressable
                  key={`nav-${index}-${stop.address || index}`}
                  style={styles.navStopChip}
                  onPress={() => openMapsAddress(stop.address)}
                  accessibilityRole="button"
                  accessibilityLabel={`${title}: ${stop.address || ''}`}
                >
                  <View
                    style={[
                      styles.navStopIndex,
                      { backgroundColor: isPickup ? hexAlpha(DEST_GREEN, 0.2) : hexAlpha(accentHex, 0.2) },
                    ]}
                  >
                    <Text
                      style={[
                        styles.navStopIndexText,
                        { color: isPickup ? DEST_GREEN : accentHex },
                      ]}
                    >
                      {index + 1}
                    </Text>
                  </View>
                  <View style={styles.navStopBody}>
                    <Text style={styles.navStopTitle} numberOfLines={1}>
                      {title}
                    </Text>
                    <Text style={styles.navStopAddr} numberOfLines={2}>
                      {addr.main}
                      {addr.sub ? `, ${addr.sub}` : ''}
                    </Text>
                  </View>
                  <Ionicons name="navigate-outline" size={18} color={colors.muted} />
                </Pressable>
              );
            })}
          </View>
          <Pressable style={styles.primaryBtn} onPress={() => openMapsStops(navStops)}>
            <Text style={styles.primaryBtnText}>Start navigatie</Text>
          </Pressable>
        </>
      )}
    </AppScrollView>
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

      {multi ? <GhostButton title="Ander scherm" onPress={() => setActiveScreen(null)} /> : null}
      <Pressable
        style={styles.logoutBtn}
        onPress={() => {
          resetContractTabToTrips();
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
  else if (tab === 'navigation') body = navigationPanel;
  else if (tab === 'absences') body = absencesPanel;
  else if (tab === 'profile') body = profilePanel;

  const absencePassenger = passengers.find((p) => p.id === absencePassengerId);

  return (
    <DriverAccentProvider accent={accent}>
      <View style={[styles.shell, { backgroundColor: colors.bg }]}>
        <View style={styles.topBar}>
          {logoUri ? (
            <Image
              source={{ uri: logoUri }}
              style={styles.logo}
              resizeMode="contain"
              accessibilityLabel={customerName || 'Bedrijfslogo'}
            />
          ) : (
            <Text style={styles.logoFallback} numberOfLines={1}>
              {customerName || 'Contract'}
            </Text>
          )}
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
      paddingHorizontal: 20,
      paddingTop: 4,
      paddingBottom: 8,
      alignItems: 'center',
      minHeight: 62,
    },
    logo: {
      width: 220,
      height: 60,
    },
    logoFallback: {
      color: colors.text,
      fontSize: 20,
      fontWeight: '700',
      maxWidth: 240,
      textAlign: 'center',
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
    badgeRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 6,
      marginBottom: 8,
      paddingLeft: 4,
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
