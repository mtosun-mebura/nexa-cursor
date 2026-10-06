import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import {
  DriverPlanningWeek,
  PlanningDay,
  PlanningRide,
  fetchDriverPlanningWeek,
} from '../api/driver';
import { ApiError } from '../api/client';
import { ColorPalette } from '../config';
import { ErrorText } from './components';
import { hexAlpha, useDriverAccent } from '../theme/driverAccent';
import { useThemeColors } from '../theme/ThemeContext';

const WEEKDAYS_SHORT = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

function parseIsoDate(iso: string): Date {
  const [y, m, d] = iso.split('-').map((n) => Number(n));
  return new Date(y, (m || 1) - 1, d || 1);
}

function toIsoDate(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function addDaysIso(iso: string, days: number): string {
  const d = parseIsoDate(iso);
  d.setDate(d.getDate() + days);
  return toIsoDate(d);
}

function mondayIso(iso: string): string {
  const d = parseIsoDate(iso);
  const day = d.getDay(); // 0=zo … 6=za
  const diff = day === 0 ? -6 : 1 - day;
  d.setDate(d.getDate() + diff);
  return toIsoDate(d);
}

function formatRangeLabel(from: string, to: string): string {
  const a = parseIsoDate(from);
  const b = parseIsoDate(to);
  const opts: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short' };
  return `${a.toLocaleDateString('nl-NL', opts)} – ${b.toLocaleDateString('nl-NL', opts)}`;
}

function formatDayLong(iso: string): string {
  return parseIsoDate(iso).toLocaleDateString('nl-NL', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  });
}

function formatTime(iso?: string | null): string {
  if (!iso) return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '—';
  return d.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' });
}

function shortAddress(address?: string | null): string {
  const text = String(address || '').trim();
  if (!text) return '—';
  const comma = text.indexOf(',');
  return comma > 0 ? text.slice(0, comma).trim() : text;
}

function rideCountLabel(count: number): string {
  return count === 1 ? '1 rit' : `${count} ritten`;
}

function rideMeta(ride: PlanningRide): string {
  const parts: string[] = [];
  const name = String(ride.customer_name || '').trim();
  if (name) parts.push(name);
  if (ride.is_contract) parts.push('Contract');
  else if (ride.is_nexa_suite) parts.push(ride.nexa_suite_label || 'NEXA Suite');
  else parts.push('Taxi');
  const pax = Number(ride.passengers || 0);
  if (pax > 0) parts.push(pax === 1 ? '1 passagier' : `${pax} passagiers`);
  return parts.join(' · ');
}

function canOpenRide(ride: PlanningRide): boolean {
  return ride.status === 'accepted' || ride.status === 'assigned';
}

export function DriverPlanningPanel({
  token,
  vehicleId,
  onOpenRide,
}: {
  token: string;
  vehicleId?: number | null;
  onOpenRide: (rideId: number) => void;
}) {
  const colors = useThemeColors();
  const accent = useDriverAccent();
  const styles = useMemo(() => makeStyles(colors, accent.hex), [colors, accent.hex]);
  const [view, setView] = useState<'day' | 'week'>('week');
  const [weekFrom, setWeekFrom] = useState(() => mondayIso(toIsoDate(new Date())));
  const [selectedDate, setSelectedDate] = useState(() => toIsoDate(new Date()));
  const [payload, setPayload] = useState<DriverPlanningWeek | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(
    async (from: string, soft = false) => {
      if (!soft) setLoading(true);
      setError(null);
      try {
        const res = await fetchDriverPlanningWeek(token, from, vehicleId);
        const data = res?.data;
        if (!data?.days?.length) {
          setPayload(null);
          return;
        }
        setPayload({
          from: data.from,
          to: data.to,
          today: data.today,
          days: data.days,
        });
        setWeekFrom(data.from);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : 'Planning laden mislukt.');
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [token, vehicleId]
  );

  useEffect(() => {
    load(weekFrom);
  }, [load, weekFrom]);

  const days = payload?.days || [];
  const today = payload?.today || toIsoDate(new Date());
  const selected: PlanningDay | null =
    days.find((d) => d.date === selectedDate) || days.find((d) => d.is_today) || days[0] || null;

  useEffect(() => {
    if (!days.length) return;
    if (!days.some((d) => d.date === selectedDate)) {
      const t = days.find((d) => d.is_today) || days[0];
      if (t) setSelectedDate(t.date);
    }
  }, [days, selectedDate]);

  const weekTotal = days.reduce((sum, d) => sum + Number(d.ride_count || d.rides?.length || 0), 0);
  const selectedCount = Number(selected?.ride_count || selected?.rides?.length || 0);
  const selectedRides = selected?.rides || [];

  function goToday() {
    const monday = mondayIso(today);
    setSelectedDate(today);
    if (monday !== weekFrom) setWeekFrom(monday);
  }

  function goPrev() {
    if (view === 'week') {
      setWeekFrom(addDaysIso(weekFrom, -7));
      setSelectedDate(addDaysIso(selectedDate, -7));
      return;
    }
    const next = addDaysIso(selectedDate, -1);
    setSelectedDate(next);
    const monday = mondayIso(next);
    if (monday !== weekFrom) setWeekFrom(monday);
  }

  function goNext() {
    if (view === 'week') {
      setWeekFrom(addDaysIso(weekFrom, 7));
      setSelectedDate(addDaysIso(selectedDate, 7));
      return;
    }
    const next = addDaysIso(selectedDate, 1);
    setSelectedDate(next);
    const monday = mondayIso(next);
    if (monday !== weekFrom) setWeekFrom(monday);
  }

  return (
    <ScrollView
      contentContainerStyle={styles.scroll}
      keyboardShouldPersistTaps="handled"
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={() => {
            setRefreshing(true);
            load(weekFrom, true);
          }}
          tintColor={colors.text}
        />
      }
    >
      <View style={styles.headerRow}>
        <Text style={styles.title}>Planning</Text>
        <View style={styles.viewToggle}>
          <Pressable
            style={[styles.viewBtn, view === 'day' && styles.viewBtnActive]}
            onPress={() => setView('day')}
          >
            <Text style={[styles.viewBtnText, view === 'day' && styles.viewBtnTextActive]}>Dag</Text>
          </Pressable>
          <Pressable
            style={[styles.viewBtn, view === 'week' && styles.viewBtnActive]}
            onPress={() => setView('week')}
          >
            <Text style={[styles.viewBtnText, view === 'week' && styles.viewBtnTextActive]}>
              Week
            </Text>
          </Pressable>
        </View>
      </View>

      <ErrorText>{error}</ErrorText>

      <View style={styles.navRow}>
        <Pressable style={styles.navBtn} onPress={goPrev} accessibilityLabel="Vorige">
          <Ionicons name="chevron-back" size={20} color={colors.text} />
        </Pressable>
        <Pressable style={styles.todayBtn} onPress={goToday}>
          <Text style={styles.todayBtnText}>Vandaag</Text>
        </Pressable>
        <View style={styles.navSpacer} />
        <Pressable style={styles.navBtn} onPress={goNext} accessibilityLabel="Volgende">
          <Ionicons name="chevron-forward" size={20} color={colors.text} />
        </Pressable>
      </View>

      {loading && !payload ? (
        <ActivityIndicator color={accent.hex} style={{ marginTop: 24 }} />
      ) : !payload ? (
        <Text style={styles.empty}>Geen planningsdata.</Text>
      ) : (
        <>
          <View style={[styles.heading, view === 'day' && styles.headingDay]}>
            <Text style={[styles.headingLabel, view === 'day' && styles.headingLabelDay]}>
              {view === 'week'
                ? formatRangeLabel(payload.from, payload.to)
                : formatDayLong(selected?.date || selectedDate)}
            </Text>
            <View style={styles.countRow}>
              <Ionicons name="car-outline" size={16} color={accent.hex} />
              <Text style={styles.countText}>
                {view === 'week' ? weekTotal : selectedCount}
              </Text>
            </View>
          </View>

          {view === 'week' ? (
            <>
              <View style={styles.weekDays}>
                {days.map((day, index) => {
                  const count = Number(day.ride_count || day.rides?.length || 0);
                  const active = day.date === (selected?.date || selectedDate);
                  const hasRides = count > 0;
                  const dayNum = parseIsoDate(day.date).getDate();
                  return (
                    <Pressable
                      key={day.date}
                      style={[
                        styles.weekDay,
                        day.is_today && styles.weekDayToday,
                        active && styles.weekDayActive,
                      ]}
                      onPress={() => setSelectedDate(day.date)}
                    >
                      <Text style={[styles.wdName, active && styles.wdNameActive]}>
                        {WEEKDAYS_SHORT[index] || '—'}
                      </Text>
                      <Text style={[styles.wdNum, active && styles.wdNumActive]}>{dayNum}</Text>
                      <View style={styles.wdRides}>
                        <Ionicons
                          name="car-outline"
                          size={12}
                          color={hasRides || active ? accent.hex : colors.muted}
                        />
                        <Text
                          style={[
                            styles.wdRidesText,
                            !(hasRides || active) && styles.wdRidesMuted,
                          ]}
                        >
                          {count}
                        </Text>
                      </View>
                    </Pressable>
                  );
                })}
              </View>

              {selected ? (
                <View style={styles.daySection}>
                  <View style={styles.daySectionHead}>
                    <Text style={styles.daySectionTitle}>{formatDayLong(selected.date)}</Text>
                    <Text style={styles.daySectionCount}>{rideCountLabel(selectedCount)}</Text>
                  </View>
                  <DayRides rides={selectedRides} styles={styles} onOpenRide={onOpenRide} />
                </View>
              ) : null}
            </>
          ) : (
            <DayRides rides={selectedRides} styles={styles} onOpenRide={onOpenRide} />
          )}
        </>
      )}
    </ScrollView>
  );
}

function DayRides({
  rides,
  styles,
  onOpenRide,
}: {
  rides: PlanningRide[];
  styles: ReturnType<typeof makeStyles>;
  onOpenRide: (rideId: number) => void;
}) {
  if (!rides.length) {
    return <Text style={styles.empty}>Geen ritten op deze dag.</Text>;
  }
  return (
    <View style={styles.ridesList}>
      {rides.map((ride) => {
        const openable = canOpenRide(ride);
        const isAssigned = ride.status === 'assigned';
        const isCompleted = ride.status === 'completed';
        const isContract = !!ride.is_contract;
        return (
          <Pressable
            key={ride.id}
            disabled={!openable}
            onPress={() => openable && onOpenRide(ride.id)}
            style={[
              styles.rideCard,
              isContract ? styles.rideCardContract : styles.rideCardTaxi,
              isAssigned && styles.rideCardAssigned,
              isCompleted && styles.rideCardCompleted,
              !openable && styles.rideCardDisabled,
            ]}
          >
            <View style={styles.rideTop}>
              <Text
                style={[
                  styles.rideTime,
                  isContract && styles.rideTimeContract,
                  isAssigned && styles.rideTimeAssigned,
                  isCompleted && styles.rideTimeCompleted,
                ]}
              >
                {formatTime(ride.pickup_at)}
              </Text>
              <Text style={styles.rideStatus}>
                {(ride.status_label || ride.status || '').toUpperCase()}
              </Text>
            </View>
            <Text style={styles.rideRoute}>
              {shortAddress(ride.pickup_address)}
              <Text style={styles.rideArrow}> → </Text>
              {shortAddress(ride.dropoff_address)}
            </Text>
            {rideMeta(ride) ? <Text style={styles.rideMeta}>{rideMeta(ride)}</Text> : null}
          </Pressable>
        );
      })}
    </View>
  );
}

function makeStyles(colors: ColorPalette, accentHex: string) {
  return StyleSheet.create({
    scroll: {
      paddingHorizontal: 16,
      paddingTop: 8,
      paddingBottom: 28,
    },
    headerRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      marginBottom: 14,
      gap: 12,
    },
    title: {
      color: colors.text,
      fontSize: 22,
      fontWeight: '800',
    },
    viewToggle: {
      flexDirection: 'row',
      borderRadius: 999,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      overflow: 'hidden',
      backgroundColor: colors.card,
    },
    viewBtn: {
      paddingHorizontal: 14,
      paddingVertical: 8,
      minWidth: 64,
      alignItems: 'center',
    },
    viewBtnActive: {
      backgroundColor: accentHex,
    },
    viewBtnText: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '700',
    },
    viewBtnTextActive: {
      color: '#fff',
    },
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
      height: 40,
      paddingHorizontal: 14,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      backgroundColor: colors.card,
      alignItems: 'center',
      justifyContent: 'center',
    },
    todayBtnText: {
      color: colors.text,
      fontSize: 13,
      fontWeight: '700',
    },
    navSpacer: {
      flex: 1,
    },
    heading: {
      alignItems: 'center',
      gap: 4,
      marginBottom: 12,
    },
    headingDay: {
      marginBottom: 8,
    },
    headingLabel: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '700',
      textAlign: 'center',
    },
    headingLabelDay: {
      fontSize: 18,
      fontWeight: '800',
    },
    countRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 5,
    },
    countText: {
      color: accentHex,
      fontSize: 14,
      fontWeight: '700',
    },
    weekDays: {
      flexDirection: 'row',
      gap: 5,
      marginBottom: 14,
    },
    weekDay: {
      flex: 1,
      minWidth: 0,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      borderRadius: 10,
      paddingTop: 7,
      paddingBottom: 6,
      paddingHorizontal: 2,
      alignItems: 'center',
      backgroundColor: 'transparent',
    },
    weekDayToday: {
      borderColor: hexAlpha(accentHex, 0.5),
    },
    weekDayActive: {
      borderColor: accentHex,
      backgroundColor: hexAlpha(accentHex, 0.16),
    },
    wdName: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '600',
      textTransform: 'lowercase',
    },
    wdNameActive: {
      color: accentHex,
    },
    wdNum: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '800',
      marginTop: 2,
    },
    wdNumActive: {
      color: colors.text,
    },
    wdRides: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 2,
      marginTop: 3,
    },
    wdRidesText: {
      color: accentHex,
      fontSize: 11,
      fontWeight: '700',
    },
    wdRidesMuted: {
      color: colors.muted,
      opacity: 0.55,
    },
    daySection: {
      gap: 10,
    },
    daySectionHead: {
      flexDirection: 'row',
      alignItems: 'baseline',
      justifyContent: 'space-between',
      gap: 8,
    },
    daySectionTitle: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '800',
      flex: 1,
      textTransform: 'lowercase',
    },
    daySectionCount: {
      color: accentHex,
      fontSize: 13,
      fontWeight: '700',
    },
    ridesList: {
      gap: 10,
      width: '100%',
    },
    empty: {
      color: colors.muted,
      fontSize: 14,
      textAlign: 'center',
      marginTop: 8,
      paddingVertical: 12,
    },
    rideCard: {
      borderRadius: 14,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      borderTopWidth: 3,
      backgroundColor: colors.card,
      paddingHorizontal: 14,
      paddingVertical: 12,
      gap: 6,
    },
    rideCardTaxi: {
      borderTopColor: accentHex,
    },
    rideCardContract: {
      borderTopColor: '#3B82F6',
    },
    rideCardAssigned: {
      borderTopColor: '#22C55E',
    },
    rideCardCompleted: {
      opacity: 0.72,
      borderTopColor: colors.muted,
    },
    rideCardDisabled: {
      opacity: 0.7,
    },
    rideTop: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: 8,
    },
    rideTime: {
      color: accentHex,
      fontSize: 18,
      fontWeight: '800',
    },
    rideTimeContract: {
      color: '#60A5FA',
    },
    rideTimeAssigned: {
      color: '#4ADE80',
    },
    rideTimeCompleted: {
      color: colors.muted,
    },
    rideStatus: {
      color: '#FDBA74',
      fontSize: 10,
      fontWeight: '800',
      letterSpacing: 0.3,
      backgroundColor: hexAlpha(accentHex, 0.14),
      overflow: 'hidden',
      borderRadius: 999,
      paddingHorizontal: 9,
      paddingVertical: 4,
    },
    rideRoute: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '800',
      lineHeight: 20,
    },
    rideArrow: {
      color: accentHex,
      fontWeight: '800',
    },
    rideMeta: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '500',
      marginTop: 2,
    },
  });
}
