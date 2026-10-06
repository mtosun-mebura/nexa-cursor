import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  Alert,
  AppState,
  FlatList,
  Image,
  Linking,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import {
  acceptOffer,
  cancelAcceptedRide,
  declineOffer,
  DispatchOffer,
  DriverActiveRide,
  DriverCancelReason,
  DriverVehicle,
  fetchDriverInbox,
  fetchDriverMe,
  fetchDriverVehicles,
  fetchDriverPlanningWeek,
  inboxDataFromResponse,
  mergeCompletedRides,
  isMarketplaceRide,
  setDriverOnline,
  startRide,
  completeRide,
  updateDriverAccent,
  updateDriverRideAlertTone,
  vehicleDisplayLabel,
  vehicleDisplayName,
} from '../api/driver';
import {
  normalizeRideAlertTone,
  playRideAlertTone,
  RIDE_ALERT_OPTIONS,
  RideAlertTone,
} from '../audio/rideAlert';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { API_BASE_URL, ColorPalette } from '../config';
import { startBackgroundLocation, stopBackgroundLocation } from '../location/background';
import {
  addDriverOfferNotificationResponseListener,
  ensureDriverNotificationPermission,
  notifyNewDriverOffer,
  setupDriverNotificationChannel,
} from '../notifications/driverOffers';
import { ActiveRideBar } from '../ui/ActiveRideBar';
import { Card, ErrorText, GhostButton, Screen } from '../ui/components';
import { DriverOfferCard } from '../ui/DriverOfferCard';
import { DriverPlanningPanel } from '../ui/DriverPlanningPanel';
import { DriverEarningsPanel } from '../ui/DriverEarningsPanel';
import { DriverTabBar, DriverTabKey } from '../ui/DriverTabBar';
import { DriverTripCard } from '../ui/DriverTripCard';
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
const DRIVER_ACCENT_KEY = 'nexa.driver.accent';
const DRIVER_TABS: DriverTabKey[] = [
  'trips',
  'requests',
  'planning',
  'earnings',
  'profile',
];

function isDriverTab(value: string | null): value is DriverTabKey {
  return !!value && (DRIVER_TABS as string[]).includes(value);
}

function persistDriverTab(key: DriverTabKey) {
  AsyncStorage.setItem(DRIVER_TAB_KEY, key).catch(() => undefined);
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

const THEME_OPTIONS: { key: ThemePreference; label: string; hint: string }[] = [
  { key: 'system', label: 'Systeem', hint: 'Volgt de telefooninstelling' },
  { key: 'light', label: 'Licht', hint: 'Altijd lichte weergave' },
  { key: 'dark', label: 'Donker', hint: 'Altijd donkere weergave' },
];

function accentHex(key?: string | null): string {
  return driverAccentHex(key);
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

function openMapsForRide(ride: DriverActiveRide, dest: 'pickup' | 'dropoff' = 'pickup') {
  const address = String(
    dest === 'dropoff'
      ? ride.dropoff_address || ride.pickup_address
      : ride.pickup_address || ride.dropoff_address || ''
  ).trim();
  if (!address) return;
  const url = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(address)}`;
  Linking.openURL(url).catch(() => undefined);
}

function resolveMediaUrl(url?: string | null): string | null {
  const raw = String(url || '').trim();
  if (!raw) return null;
  if (/^https?:\/\//i.test(raw)) return raw;
  if (raw.startsWith('//')) return `https:${raw}`;
  if (raw.startsWith('/')) return `${API_BASE_URL}${raw}`;
  return `${API_BASE_URL}/${raw}`;
}

export function DriverHomeScreen() {
  const colors = useThemeColors();
  const { colorScheme, preference, setPreference } = useTheme();
  const { driverToken, logout, capabilities, setActiveScreen } = useAuth();
  const [tab, setTab] = useState<DriverTabKey>('trips');
  const [showArchived, setShowArchived] = useState(false);
  const [focusRideId, setFocusRideId] = useState<number | null>(null);
  const tripsScrollRef = useRef<ScrollView | null>(null);
  const rideOffsets = useRef<Record<number, number>>({});
  const [online, setOnline] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [accountActive, setAccountActive] = useState(true);
  const [accent, setAccent] = useState<string>('orange');
  const [rideTone, setRideTone] = useState<RideAlertTone>('classic');
  const [company, setCompany] = useState('');
  const [logoLight, setLogoLight] = useState<string | null>(null);
  const [logoDark, setLogoDark] = useState<string | null>(null);
  const [showEarnings, setShowEarnings] = useState(false);
  const [canViewMonthEarnings, setCanViewMonthEarnings] = useState(false);
  const [offers, setOffers] = useState<DispatchOffer[]>([]);
  const [archivedOffers, setArchivedOffers] = useState<DispatchOffer[]>([]);
  const [declinedOffers, setDeclinedOffers] = useState<DispatchOffer[]>([]);
  const [activeRide, setActiveRide] = useState<DriverActiveRide | null>(null);
  const [parkedRides, setParkedRides] = useState<DriverActiveRide[]>([]);
  const [scheduledRides, setScheduledRides] = useState<DriverActiveRide[]>([]);
  const [overdueScheduledRides, setOverdueScheduledRides] = useState<DriverActiveRide[]>([]);
  const [completedRides, setCompletedRides] = useState<DriverActiveRide[]>([]);
  const [archivedCompletedIds, setArchivedCompletedIds] = useState<number[]>([]);
  const [cancelReasons, setCancelReasons] = useState<DriverCancelReason[]>([]);
  const [cancelRideId, setCancelRideId] = useState<number | null>(null);
  const [selectedCancelReason, setSelectedCancelReason] = useState<string | null>(null);
  const [cancelBusy, setCancelBusy] = useState(false);
  const [busyRideId, setBusyRideId] = useState<number | null>(null);
  const [vehicles, setVehicles] = useState<DriverVehicle[]>([]);
  const [selectedVehicleId, setSelectedVehicleId] = useState<number | null>(null);
  const [vehicleLocked, setVehicleLocked] = useState(false);
  const [assignedUntil, setAssignedUntil] = useState<string | null>(null);
  const [vehiclePickerOpen, setVehiclePickerOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [busyId, setBusyId] = useState<number | null>(null);
  const seenOfferIds = useRef<Set<number>>(new Set());
  const offersBootstrapped = useRef(false);
  const rideAlertToneRef = useRef<RideAlertTone>('classic');
  const styles = useMemo(() => makeStyles(colors, accentHex(accent)), [colors, accent]);

  useEffect(() => {
    (async () => {
      try {
        const [savedTab, savedArchive, savedAccent, savedArchivedRides] = await Promise.all([
          AsyncStorage.getItem(DRIVER_TAB_KEY),
          AsyncStorage.getItem(DRIVER_ARCHIVE_KEY),
          AsyncStorage.getItem(DRIVER_ACCENT_KEY),
          AsyncStorage.getItem(DRIVER_ARCHIVED_RIDES_KEY),
        ]);
        if (savedTab === 'navigation') persistDriverTab('trips');
        else if (isDriverTab(savedTab)) setTab(savedTab);
        if (savedArchive === '1') setShowArchived(true);
        if (savedAccent) setAccent(normalizeDriverAccent(savedAccent));
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
      } catch {
        /* ignore */
      }
    })();
  }, []);

  const modes = capabilities?.modes;
  const multi = (capabilities?.screens?.length || 0) > 1;
  const logoUri =
    resolveMediaUrl(colorScheme === 'light' ? logoLight || logoDark : logoDark || logoLight) ||
    null;
  const selectedVehicle =
    vehicles.find((v) => v.id === selectedVehicleId) ||
    (selectedVehicleId ? ({ id: selectedVehicleId } as DriverVehicle) : null);
  const needsVehicle = !vehicleLocked && !selectedVehicleId;

  const handleNewOffers = useCallback(async (offerList: DispatchOffer[], isOnlineNow: boolean) => {
    const ids = offerList.map((o) => Number(o.id)).filter((id) => id > 0);
    if (!offersBootstrapped.current) {
      ids.forEach((id) => seenOfferIds.current.add(id));
      offersBootstrapped.current = true;
      return;
    }
    const fresh = offerList.filter((o) => {
      const id = Number(o.id);
      return id > 0 && !seenOfferIds.current.has(id);
    });
    if (!fresh.length || !isOnlineNow) return;

    fresh.forEach((o) => seenOfferIds.current.add(Number(o.id)));
    const priority = fresh[0];
    const foreground = AppState.currentState === 'active';
    if (foreground) {
      await playRideAlertTone(rideAlertToneRef.current);
    }
    await notifyNewDriverOffer({
      count: fresh.length,
      pickup: priority.ride?.pickup_address,
      dropoff: priority.ride?.dropoff_address,
      playSound: !foreground,
    });
  }, []);

  const refresh = useCallback(async () => {
    if (!driverToken) return;
    setError(null);
    try {
      const [me, vehiclesRes, inbox] = await Promise.all([
        fetchDriverMe(driverToken),
        fetchDriverVehicles(driverToken),
        fetchDriverInbox(driverToken),
      ]);
      const isOnlineNow = !!me.user.is_online;
      setOnline(isOnlineNow);
      setName(me.user.name);
      setEmail(me.user.email || '');
      setPhone(me.user.phone || '');
      setAccountActive(me.user.is_account_active !== false);
      const nextAccent = normalizeDriverAccent(me.user.pwa_accent);
      const nextTone = normalizeRideAlertTone(me.user.ride_alert_tone);
      setAccent(nextAccent);
      persistDriverAccent(nextAccent);
      setRideTone(nextTone);
      rideAlertToneRef.current = nextTone;
      setCompany(me.user.company_name || '');
      setLogoLight(me.user.company_logo_url || null);
      setLogoDark(me.user.company_logo_dark_url || null);
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
      setVehicles(list);
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
      setOffers(inboxData.offers);
      setArchivedOffers(inboxData.archivedOffers);
      setDeclinedOffers(inboxData.declinedOffers);
      setActiveRide(inboxData.activeRide);
      setParkedRides(inboxData.parkedAssignedRides);
      setScheduledRides(inboxData.scheduledRides);
      setOverdueScheduledRides(inboxData.overdueScheduledRides);
      let completedFromPlanning: DriverActiveRide[] = [];
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
      setCompletedRides(mergeCompletedRides(inboxData.completedRides, completedFromPlanning));
      setCancelReasons(inboxData.cancelReasons);
      await handleNewOffers(inboxData.offers, isOnlineNow);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Kon gegevens niet laden.');
    }
  }, [driverToken, handleNewOffers, selectedVehicleId]);

  useEffect(() => {
    setupDriverNotificationChannel().catch(() => undefined);
    ensureDriverNotificationPermission().catch(() => undefined);
  }, []);

  useEffect(() => {
    refresh();
    const intervalMs = online ? 2500 : 5000;
    const t = setInterval(refresh, intervalMs);
    return () => clearInterval(t);
  }, [refresh, online]);

  useEffect(() => {
    const sub = AppState.addEventListener('change', (state) => {
      if (state === 'active') {
        refresh().catch(() => undefined);
      }
    });
    return () => sub.remove();
  }, [refresh]);

  useEffect(() => {
    return addDriverOfferNotificationResponseListener(() => goToTab('requests'));
  }, []);

  async function selectVehicle(id: number) {
    setSelectedVehicleId(id);
    setVehiclePickerOpen(false);
    if (!driverToken || !online) return;
    try {
      await setDriverOnline(driverToken, true, id);
      await startBackgroundLocation(driverToken, id);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Voertuig opslaan mislukt.');
    }
  }

  async function toggleOnline(next: boolean) {
    if (!driverToken) return;
    setError(null);
    if (next && !selectedVehicleId) {
      setError('Kies eerst een voertuig voordat je online gaat.');
      setVehiclePickerOpen(true);
      return;
    }
    try {
      if (next) {
        await ensureDriverNotificationPermission();
        await startBackgroundLocation(driverToken, selectedVehicleId);
        await setDriverOnline(driverToken, true, selectedVehicleId);
      } else {
        await setDriverOnline(driverToken, false, selectedVehicleId);
        await stopBackgroundLocation();
      }
      setOnline(next);
      await refresh();
    } catch (e) {
      setOnline(false);
      setError(e instanceof Error ? e.message : 'Online zetten mislukt.');
      try {
        await stopBackgroundLocation();
      } catch {
        /* ignore */
      }
    }
  }

  function goToTab(key: DriverTabKey) {
    setTab(key);
    persistDriverTab(key);
  }

  function goToArchive(open: boolean) {
    setShowArchived(open);
    persistDriverArchive(open);
  }

  async function onAccept(id: number) {
    if (!driverToken) return;
    setBusyId(id);
    try {
      await acceptOffer(driverToken, id);
      await refresh();
      goToTab('trips');
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Accepteren mislukt.');
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
      setError(e instanceof ApiError ? e.message : 'Weigeren mislukt.');
    } finally {
      setBusyId(null);
    }
  }

  async function onStartRide(rideId: number) {
    if (!driverToken) return;
    setBusyRideId(rideId);
    setError(null);
    try {
      await startRide(driverToken, rideId);
      await refresh();
      goToTab('trips');
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Rit starten mislukt.');
    } finally {
      setBusyRideId(null);
    }
  }

  function requestCompleteRide(rideId: number) {
    Alert.alert('Rit afronden?', 'Weet je zeker dat je deze rit wilt afronden?', [
      { text: 'Terug', style: 'cancel' },
      { text: 'Afronden', onPress: () => onCompleteRide(rideId) },
    ]);
  }

  async function onCompleteRide(rideId: number) {
    if (!driverToken) return;
    setBusyRideId(rideId);
    setError(null);
    try {
      await completeRide(driverToken, rideId);
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Rit afronden mislukt.');
    } finally {
      setBusyRideId(null);
    }
  }

  async function confirmCancelRide() {
    if (!driverToken || !cancelRideId || !selectedCancelReason) return;
    setCancelBusy(true);
    setError(null);
    try {
      await cancelAcceptedRide(driverToken, cancelRideId, selectedCancelReason);
      setCancelRideId(null);
      setSelectedCancelReason(null);
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Annuleren mislukt.');
    } finally {
      setCancelBusy(false);
    }
  }

  function renderPlaceholder(title: string, text: string) {
    return (
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <ErrorText>{error}</ErrorText>
        <Text style={styles.panelTitle}>{title}</Text>
        <Card>
          <Text style={styles.hint}>{text}</Text>
        </Card>
      </ScrollView>
    );
  }

  // Marketplace-ritten: nooit in "verlopen ophaalmoment" (geen overdue-UI).
  const ownOverdueScheduledRides = overdueScheduledRides.filter((r) => !isMarketplaceRide(r));
  const marketplaceOverdueAsScheduled = overdueScheduledRides.filter((r) => isMarketplaceRide(r));
  const plannedRides = [...scheduledRides, ...marketplaceOverdueAsScheduled];
  const archivedCompletedSet = useMemo(
    () => new Set(archivedCompletedIds),
    [archivedCompletedIds]
  );
  const visibleCompletedRides = completedRides.filter((r) => !archivedCompletedSet.has(r.id));
  const archivedCompletedRides = completedRides.filter((r) => archivedCompletedSet.has(r.id));

  const hasTrips =
    !!activeRide ||
    parkedRides.length > 0 ||
    plannedRides.length > 0 ||
    ownOverdueScheduledRides.length > 0 ||
    visibleCompletedRides.length > 0;

  function toggleCompletedArchive(rideId: number) {
    setArchivedCompletedIds((prev) => {
      const next = prev.includes(rideId) ? prev.filter((id) => id !== rideId) : [...prev, rideId];
      persistArchivedCompletedIds(next);
      return next;
    });
  }

  function openRideFromPlanning(rideId: number) {
    setFocusRideId(rideId);
    goToTab('trips');
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
    <ScrollView
      ref={tripsScrollRef}
      contentContainerStyle={styles.scroll}
      keyboardShouldPersistTaps="handled"
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
      <ErrorText>{error}</ErrorText>
      <Text style={styles.panelTitle}>Ritten</Text>
      {!hasTrips ? (
        <Card>
          <Text style={styles.emptyTitle}>Geen actieve of geplande ritten.</Text>
          <Text style={styles.hint}>
            Geaccepteerde ritten verschijnen hier. Afgeronde ritten blijven zichtbaar tot je ze archiveert.
          </Text>
        </Card>
      ) : (
        <>
          {activeRide ? (
            <>
              <Text style={styles.sectionLabel}>Actief</Text>
              <View onLayout={bindRideOffset(activeRide.id)}>
                <DriverTripCard
                  ride={activeRide}
                  variant="active"
                  busy={busyRideId === activeRide.id}
                  highlighted={focusRideId === activeRide.id}
                  onHighlightEnd={() => setFocusRideId(null)}
                  onOpenMaps={() => openMapsForRide(activeRide, 'dropoff')}
                  onComplete={() => requestCompleteRide(activeRide.id)}
                  onCancel={
                    activeRide.can_cancel_with_reason
                      ? () => {
                          setSelectedCancelReason(null);
                          setCancelRideId(activeRide.id);
                        }
                      : undefined
                  }
                />
              </View>
            </>
          ) : null}
          {parkedRides.map((ride) => (
            <View key={`parked-${ride.id}`} onLayout={bindRideOffset(ride.id)}>
              <DriverTripCard
                ride={ride}
                variant="active"
                busy={busyRideId === ride.id}
                highlighted={focusRideId === ride.id}
                onHighlightEnd={() => setFocusRideId(null)}
                onOpenMaps={() => openMapsForRide(ride, 'dropoff')}
                onComplete={() => requestCompleteRide(ride.id)}
              />
            </View>
          ))}
          {plannedRides.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>Geaccepteerd / gepland</Text>
              {plannedRides.map((ride) => (
                <View key={`scheduled-${ride.id}`} onLayout={bindRideOffset(ride.id)}>
                  <DriverTripCard
                    ride={ride}
                    variant="scheduled"
                    busy={busyRideId === ride.id}
                    highlighted={focusRideId === ride.id}
                    onHighlightEnd={() => setFocusRideId(null)}
                    onStart={() => onStartRide(ride.id)}
                    onOpenMaps={() => openMapsForRide(ride, 'pickup')}
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
          {ownOverdueScheduledRides.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>Verlopen ophaalmoment</Text>
              {ownOverdueScheduledRides.map((ride) => (
                <View key={`overdue-${ride.id}`} onLayout={bindRideOffset(ride.id)}>
                  <DriverTripCard
                    ride={ride}
                    variant="overdue"
                    busy={busyRideId === ride.id}
                    highlighted={focusRideId === ride.id}
                    onHighlightEnd={() => setFocusRideId(null)}
                    onStart={() => onStartRide(ride.id)}
                    onOpenMaps={() => openMapsForRide(ride, 'pickup')}
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
              <Text style={styles.sectionLabel}>Afgerond</Text>
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
  );

  const planningPanel = driverToken ? (
    <DriverPlanningPanel
      token={driverToken}
      vehicleId={selectedVehicleId}
      onOpenRide={openRideFromPlanning}
    />
  ) : (
    <ScrollView contentContainerStyle={styles.scroll}>
      <Text style={styles.hint}>Niet ingelogd.</Text>
    </ScrollView>
  );

  const requestsPanel = (
    <FlatList
      data={offers}
      keyExtractor={(item) => String(item.id)}
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
          <ErrorText>{error}</ErrorText>
          <Text style={styles.panelTitle}>Aanvragen</Text>
          {!online ? (
            <Card>
              <Text style={styles.hint}>
                Zet jezelf online. Locatie blijft actief op de achtergrond (belangrijk voor
                marketplace-matching).
              </Text>
            </Card>
          ) : null}
        </>
      }
      ListEmptyComponent={
        <Card>
          <Text style={styles.hint}>
            {online ? 'Nog geen ritten in je inbox.' : 'Ga online om ritten te ontvangen.'}
          </Text>
        </Card>
      }
      renderItem={({ item }) => (
        <DriverOfferCard
          offer={item}
          busy={busyId === item.id}
          fallbackVehicleLabel={vehicleDisplayLabel(selectedVehicle) || null}
          fallbackVehicleName={vehicleDisplayName(selectedVehicle) || null}
          onAccept={() => onAccept(item.id)}
          onDecline={() => onDecline(item.id)}
        />
      )}
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

  const chooseTone = async (key: RideAlertTone) => {
    setRideTone(key);
    rideAlertToneRef.current = key;
    await playRideAlertTone(key);
    if (!driverToken) return;
    try {
      await updateDriverRideAlertTone(driverToken, key);
    } catch {
      /* keuze blijft lokaal hoorbaar */
    }
  };

  const openHandleiding = () => {
    const url = `${API_BASE_URL}/taxi/chauffeur/handleiding`;
    Linking.openURL(url).catch(() => undefined);
  };

  const profilePanel = (
    <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
      <ErrorText>{error}</ErrorText>
      <Text style={styles.panelTitle}>Profiel</Text>
      <Card>
        <Text style={styles.profileName}>{name || 'Chauffeur'}</Text>
        <View style={styles.profileRow}>
          <Text style={styles.profileLabel}>E-mailadres</Text>
          <Text style={styles.profileValue}>{email || '—'}</Text>
        </View>
        <View style={styles.profileRow}>
          <Text style={styles.profileLabel}>Telefoonnummer</Text>
          <Text style={[styles.profileValue, !phone && styles.profileValueMuted]}>
            {phone || 'Niet beschikbaar'}
          </Text>
        </View>
        <View style={styles.profileRow}>
          <Text style={styles.profileLabel}>Bedrijf</Text>
          <Text style={styles.profileValue}>{company || '—'}</Text>
        </View>
        <View style={styles.profileRow}>
          <Text style={styles.profileLabel}>Accountstatus</Text>
          <Text style={styles.profileValue}>{accountStatusLabel(accountActive, online)}</Text>
        </View>

        <View style={styles.profileDivider} />
        <Text style={styles.profileSection}>Themakleur</Text>
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
        <Text style={styles.profileSection}>Weergave</Text>
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
              style={styles.themeRow}
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

        <View style={styles.profileDivider} />
        <Text style={styles.profileSection}>Ritgeluid</Text>
        <Text style={styles.themeHint}>
          Kies het geluid bij een nieuwe rit. Tik om te beluisteren.
        </Text>
        <View style={styles.toneRow}>
          {RIDE_ALERT_OPTIONS.map((item) => {
            const selected = rideTone === item.key;
            return (
              <Pressable
                key={item.key}
                onPress={() => chooseTone(item.key)}
                style={[
                  styles.toneChip,
                  selected && { backgroundColor: accentHex(accent), borderColor: accentHex(accent) },
                ]}
                accessibilityRole="button"
                accessibilityState={{ selected }}
              >
                <Text style={[styles.toneChipText, selected && styles.toneChipTextOn]}>
                  {item.label}
                </Text>
              </Pressable>
            );
          })}
        </View>

        <Text style={styles.profileNote}>Gegevens zijn alleen ter inzage.</Text>
        <Pressable
          onPress={openHandleiding}
          style={[styles.guideLink, { borderColor: `${accentHex(accent)}66` }]}
        >
          <Text style={[styles.guideLinkTitle, { color: accentHex(accent) }]}>Handleiding</Text>
          <Text style={[styles.guideLinkAction, { color: accentHex(accent) }]}>Openen →</Text>
        </Pressable>
        <Pressable
          style={styles.logoutBtn}
          onPress={async () => {
            await stopBackgroundLocation();
            await logout();
          }}
        >
          <Text style={styles.logoutBtnText}>Uitloggen</Text>
        </Pressable>
      </Card>
      {multi ? <GhostButton title="Ander scherm" onPress={() => setActiveScreen(null)} /> : null}
    </ScrollView>
  );

  const jumpRideTarget =
    (activeRide && String(activeRide.status || '') === 'assigned' ? activeRide : null) ||
    parkedRides[0] ||
    plannedRides[0] ||
    ownOverdueScheduledRides[0] ||
    activeRide ||
    null;
  const showActiveRideBar = !!jumpRideTarget && (showArchived || tab !== 'trips');
  const archiveBadgeCount =
    archivedOffers.length + declinedOffers.length + archivedCompletedRides.length;

  const archivePanel = (
    <ScrollView
      contentContainerStyle={styles.scroll}
      keyboardShouldPersistTaps="handled"
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
      <ErrorText>{error}</ErrorText>
      <View style={styles.archiveHead}>
        <Text style={styles.panelTitle}>Archief</Text>
        <Pressable onPress={() => goToArchive(false)} hitSlop={8}>
          <Text style={styles.archiveBack}>Terug</Text>
        </Pressable>
      </View>
      {archiveBadgeCount === 0 ? (
        <Card>
          <Text style={styles.hint}>Geen gearchiveerde of afgewezen ritten.</Text>
        </Card>
      ) : (
        <>
          {declinedOffers.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>Afgewezen / geannuleerd</Text>
              {declinedOffers.map((offer) => (
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
          {archivedCompletedRides.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>Afgeronde ritten</Text>
              {archivedCompletedRides.map((ride) => (
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
          {archivedOffers.length > 0 ? (
            <>
              <Text style={styles.sectionLabel}>Archief</Text>
              {archivedOffers.map((offer) => {
                const ride = offer.ride;
                const pickup = String(ride?.pickup_address || '').split(',')[0] || '—';
                const dropoff = String(ride?.dropoff_address || '').split(',')[0] || '—';
                return (
                  <Card key={`archived-${offer.id}`}>
                    <Text style={styles.archivedMeta}>
                      {ride?.pickup_at
                        ? new Date(ride.pickup_at).toLocaleString('nl-NL', {
                            weekday: 'short',
                            day: 'numeric',
                            month: 'short',
                            hour: '2-digit',
                            minute: '2-digit',
                          })
                        : 'Gearchiveerd'}
                    </Text>
                    <Text style={styles.offerTitle}>
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
      )}
    </ScrollView>
  );

  let body: React.ReactNode = tripsPanel;
  if (showArchived) body = archivePanel;
  else if (tab === 'requests') body = requestsPanel;
  else if (tab === 'planning') body = planningPanel;
  else if (tab === 'earnings') {
    body = driverToken ? (
      <DriverEarningsPanel token={driverToken} canViewMonth={canViewMonthEarnings} />
    ) : (
      renderPlaceholder('Inkomsten', 'Niet ingelogd.')
    );
  } else if (tab === 'profile') body = profilePanel;

  return (
    <DriverAccentProvider accent={accent}>
    <Screen style={{ paddingHorizontal: 0, paddingBottom: 0 }}>
      <View style={styles.topBar}>
        <View style={styles.onlineBox}>
          <Text style={styles.onlineLabel}>{online ? 'Online' : 'Offline'}</Text>
          <Switch
            value={online}
            onValueChange={toggleOnline}
            trackColor={{ false: colors.border, true: colors.success }}
            style={styles.onlineSwitch}
          />
        </View>

        <View style={styles.logoCenter} pointerEvents="none">
          {logoUri ? (
            <Image
              source={{ uri: logoUri }}
              style={styles.logo}
              resizeMode="contain"
              accessibilityLabel={company || 'Bedrijfslogo'}
            />
          ) : (
            <Text style={styles.logoFallback} numberOfLines={1}>
              {company || 'Chauffeur'}
            </Text>
          )}
        </View>

        <View style={styles.headerIcons}>
          <Pressable
            style={[styles.headerIconBtn, showArchived && styles.headerIconBtnActive]}
            onPress={() => goToArchive(!showArchived)}
            accessibilityLabel={
              archiveBadgeCount > 0 ? `Archief (${archiveBadgeCount})` : 'Archief'
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

      <Pressable
        onPress={() => {
          if (!vehicleLocked) setVehiclePickerOpen(true);
        }}
        disabled={vehicleLocked}
        style={[styles.vehicleRow, needsVehicle && styles.vehicleRowNeeded]}
        accessibilityRole={vehicleLocked ? 'text' : 'button'}
        accessibilityLabel={
          vehicleLocked ? 'Toegewezen voertuig' : 'Voertuig wisselen'
        }
      >
        <Ionicons name="car-outline" size={20} color={colors.muted} />
        <Text style={styles.vehicleLine} numberOfLines={1}>
          {selectedVehicle ? (
            <>
              <Text style={styles.vehiclePlate}>{vehicleDisplayLabel(selectedVehicle)}</Text>
              {vehicleDisplayName(selectedVehicle) ? (
                <Text style={styles.vehicleBrand}>
                  {'  '}
                  {vehicleDisplayName(selectedVehicle)}
                </Text>
              ) : null}
              {vehicleLocked && assignedUntil ? (
                <Text style={styles.vehicleBrand}>{`  · tot ${assignedUntil}`}</Text>
              ) : null}
            </>
          ) : (
            <Text style={styles.vehicleBrand}>Kies een voertuig…</Text>
          )}
        </Text>
        {!vehicleLocked ? (
          <Ionicons name="swap-horizontal" size={20} color={colors.muted} />
        ) : null}
      </Pressable>

      {showActiveRideBar && jumpRideTarget ? (
        <ActiveRideBar
          ride={jumpRideTarget}
          onPress={() => {
            goToArchive(false);
            setFocusRideId(jumpRideTarget.id);
            goToTab('trips');
          }}
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

      <Modal
        visible={vehiclePickerOpen}
        transparent
        animationType="fade"
        onRequestClose={() => setVehiclePickerOpen(false)}
      >
        <View style={styles.pickerOverlay}>
          <Pressable style={styles.pickerBackdrop} onPress={() => setVehiclePickerOpen(false)} />
          <View style={styles.pickerSheet}>
            <Text style={styles.pickerTitle}>Voertuig kiezen</Text>
            {vehicles.length === 0 ? (
              <Text style={styles.hint}>Geen beschikbare voertuigen.</Text>
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
          </View>
        </View>
      </Modal>

      <Modal
        visible={cancelRideId != null}
        transparent
        animationType="fade"
        onRequestClose={() => {
          if (!cancelBusy) {
            setCancelRideId(null);
            setSelectedCancelReason(null);
          }
        }}
      >
        <View style={styles.pickerOverlay}>
          <Pressable
            style={styles.pickerBackdrop}
            disabled={cancelBusy}
            onPress={() => {
              setCancelRideId(null);
              setSelectedCancelReason(null);
            }}
          />
          <View style={styles.pickerSheet}>
            <Text style={styles.pickerTitle}>Rit annuleren</Text>
            <Text style={styles.hint}>
              Kies een reden. Deze melding wordt getoond bij de klant.
            </Text>
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
                <Text style={styles.cancelActionGhostText}>Terug</Text>
              </Pressable>
              <Pressable
                style={[
                  styles.cancelActionBtn,
                  styles.cancelActionPrimary,
                  !selectedCancelReason && styles.cancelActionDisabled,
                ]}
                disabled={cancelBusy || !selectedCancelReason}
                onPress={() => confirmCancelRide()}
              >
                <Text style={styles.cancelActionPrimaryText}>
                  {cancelBusy ? 'Bezig…' : 'Bevestigen'}
                </Text>
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>
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
      paddingBottom: 6,
      minHeight: 48,
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
      left: 0,
      right: 0,
      top: 0,
      bottom: 0,
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 1,
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
      width: 40,
      height: 40,
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
    archiveHead: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      marginBottom: 4,
    },
    archiveBack: {
      color: colors.primary,
      fontSize: 14,
      fontWeight: '700',
    },
    archivedMeta: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '600',
      marginBottom: 4,
    },
    logo: {
      width: 156,
      height: 42,
    },
    logoFallback: {
      color: colors.text,
      fontSize: 20,
      fontWeight: '700',
      maxWidth: 168,
      textAlign: 'center',
    },
    vehicleRow: {
      marginHorizontal: 20,
      marginBottom: 8,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
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
      paddingBottom: 24,
      paddingTop: 4,
    },
    panelTitle: {
      color: colors.text,
      fontSize: 20,
      fontWeight: '700',
      marginBottom: 12,
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
      borderColor: hexAlpha(accentHex, 0.45),
      paddingHorizontal: 16,
      paddingTop: 16,
      paddingBottom: 28,
      maxHeight: '70%',
    },
    pickerTitle: {
      color: colors.text,
      fontSize: 17,
      fontWeight: '700',
      marginBottom: 12,
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
      fontSize: 22,
      fontWeight: '800',
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
