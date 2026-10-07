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
  DriverActiveRide,
  DriverEarningsPayload,
  DriverEarningsRide,
  driverPriceDisplay,
  fetchDriverEarnings,
} from '../api/driver';
import { ApiError } from '../api/client';
import { ColorPalette } from '../config';
import { formatEuroNl } from '../geo/route';
import { ErrorText } from './components';
import { ScreenHeader } from './ScreenHeader';
import { hexAlpha, useDriverAccent } from '../theme/driverAccent';
import { useThemeColors } from '../theme/ThemeContext';

type Period = 'day' | 'week' | 'month';

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

function addDaysIso(iso: string, days: number): string {
  const d = parseIsoDate(iso);
  d.setDate(d.getDate() + days);
  return toIsoDate(d);
}

function mondayIso(iso: string): string {
  const d = parseIsoDate(iso);
  const day = d.getDay();
  const diff = day === 0 ? -6 : 1 - day;
  d.setDate(d.getDate() + diff);
  return toIsoDate(d);
}

function periodBounds(period: Period, date: string, today: string): { from: string; to: string } {
  if (period === 'week') {
    const from = mondayIso(date);
    const weekEnd = addDaysIso(from, 6);
    return { from, to: weekEnd > today ? today : weekEnd };
  }
  if (period === 'month') {
    const d = parseIsoDate(date);
    const from = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`;
    const last = toIsoDate(new Date(d.getFullYear(), d.getMonth() + 1, 0));
    return { from, to: last > today ? today : last };
  }
  return { from: date, to: date };
}

function dayInBounds(day: string | null, from: string, to: string): boolean {
  const start = isoDay(from) || from.slice(0, 10);
  const end = isoDay(to) || to.slice(0, 10);
  if (!day) return true;
  return day >= start && day <= end;
}

function shortAddress(address?: string | null): string {
  const text = String(address || '').trim();
  if (!text) return '—';
  const comma = text.indexOf(',');
  return comma > 0 ? text.slice(0, comma).trim() : text;
}

function isoDay(value?: string | null): string | null {
  if (!value) return null;
  const m = String(value).match(/^(\d{4}-\d{2}-\d{2})/);
  if (m) return m[1];
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return null;
  const y = d.getFullYear();
  const mo = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${mo}-${day}`;
}

function asEarningsPayload(raw: unknown): DriverEarningsPayload | null {
  if (!raw || typeof raw !== 'object') return null;
  const obj = raw as Record<string, unknown>;
  const inner =
    obj.data && typeof obj.data === 'object' && !Array.isArray(obj.data)
      ? (obj.data as Record<string, unknown>)
      : obj;
  if (
    Array.isArray(inner.rides) ||
    inner.period_total != null ||
    inner.day_total != null ||
    typeof inner.period === 'string'
  ) {
    return inner as DriverEarningsPayload;
  }
  return null;
}

function rideAmountLines(
  ride: DriverEarningsRide,
  fallback?: DriverActiveRide | null
): { primary: number | null; customerPays: number | null } {
  const quoted =
    ride.quoted_price ?? fallback?.quoted_price ?? null;
  const fee = fallback?.fee_breakdown;
  const fromFee = driverPriceDisplay({ quotedPrice: quoted, fee });
  const apiAmt = Number(ride.amount);
  const hasApi = Number.isFinite(apiAmt) && apiAmt > 0;
  if (fromFee.primary != null) {
    return fromFee;
  }
  if (hasApi) {
    return {
      primary: apiAmt,
      customerPays:
        quoted != null && Number(quoted) !== apiAmt ? Number(quoted) : null,
    };
  }
  if (quoted != null && Number.isFinite(Number(quoted))) {
    return { primary: Number(quoted), customerPays: null };
  }
  return { primary: null, customerPays: null };
}

export function DriverEarningsPanel({
  token,
  canViewMonth,
  completedRides = [],
}: {
  token: string;
  canViewMonth?: boolean;
  completedRides?: DriverActiveRide[];
}) {
  const colors = useThemeColors();
  const accent = useDriverAccent();
  const styles = useMemo(() => makeStyles(colors, accent.hex), [colors, accent.hex]);
  const [period, setPeriod] = useState<Period>('day');
  const [date, setDate] = useState(() => toIsoDate(new Date()));
  const [payload, setPayload] = useState<DriverEarningsPayload | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(
    async (soft = false) => {
      if (!soft) setLoading(true);
      setError(null);
      try {
        const res = await fetchDriverEarnings(token, date, period);
        const next = asEarningsPayload(res);
        setPayload(next);
        if (next?.date) setDate(next.date);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : 'Inkomsten laden mislukt.');
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [token, date, period]
  );

  useEffect(() => {
    load();
  }, [load]);

  const todayIso = toIsoDate(new Date());
  const bounds = periodBounds(period, date, todayIso);
  const fallbackById = useMemo(() => {
    const map = new Map<number, DriverActiveRide>();
    for (const ride of completedRides) {
      map.set(Number(ride.id), ride);
    }
    return map;
  }, [completedRides]);

  const displayRides = useMemo(() => {
    type Row = {
      id: number;
      time: string | null;
      pickup?: string | null;
      dropoff?: string | null;
      primary: number | null;
      customerPays: number | null;
    };
    const byId = new Map<number, Row>();

    const put = (row: Row) => {
      const prev = byId.get(row.id);
      if (!prev) {
        byId.set(row.id, row);
        return;
      }
      byId.set(row.id, {
        ...prev,
        ...row,
        primary: row.primary ?? prev.primary,
        customerPays: row.customerPays ?? prev.customerPays,
        time: row.time || prev.time,
        pickup: row.pickup || prev.pickup,
        dropoff: row.dropoff || prev.dropoff,
      });
    };

    for (const ride of payload?.rides || []) {
      const id = Number(ride.id);
      if (!id) continue;
      const fallback = fallbackById.get(id) || null;
      const amounts = rideAmountLines(ride, fallback);
      put({
        id,
        time: ride.completed_time || null,
        pickup: ride.pickup_address || fallback?.pickup_address,
        dropoff: ride.dropoff_address || fallback?.dropoff_address,
        primary: amounts.primary,
        customerPays: amounts.customerPays,
      });
    }

    for (const ride of completedRides) {
      const id = Number(ride.id);
      if (!id) continue;
      const day = isoDay(ride.pickup_at);
      if (!dayInBounds(day, bounds.from, bounds.to)) continue;
      const amounts = rideAmountLines(
        { id, quoted_price: ride.quoted_price, amount: undefined },
        ride
      );
      const time = ride.pickup_at
        ? new Date(ride.pickup_at).toLocaleTimeString('nl-NL', {
            hour: '2-digit',
            minute: '2-digit',
          })
        : null;
      put({
        id,
        time,
        pickup: ride.pickup_address,
        dropoff: ride.dropoff_address,
        primary: amounts.primary,
        customerPays: amounts.customerPays,
      });
    }

    return [...byId.values()].sort((a, b) => String(b.time || '').localeCompare(String(a.time || '')));
  }, [payload, completedRides, fallbackById, bounds.from, bounds.to]);

  const totalFromRides = displayRides.reduce(
    (sum, ride) => sum + (ride.primary != null ? ride.primary : 0),
    0
  );
  const apiTotal = Number(payload?.period_total ?? payload?.day_total ?? 0);
  const total = totalFromRides > 0 ? totalFromRides : apiTotal;
  const count = displayRides.length || Number(payload?.ride_count ?? 0);

  function step(delta: number) {
    if (period === 'week') setDate(addDaysIso(date, delta * 7));
    else if (period === 'month') {
      const d = parseIsoDate(date);
      d.setMonth(d.getMonth() + delta);
      setDate(toIsoDate(d));
    } else setDate(addDaysIso(date, delta));
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
            load(true);
          }}
          tintColor={colors.text}
        />
      }
    >
      <ErrorText>{error}</ErrorText>
      <ScreenHeader title="Inkomsten" />
      <View style={styles.periodRow}>
        {(['day', 'week', ...(canViewMonth ? (['month'] as const) : [])] as Period[]).map(
          (key) => {
            const active = period === key;
            const label = key === 'day' ? 'Dag' : key === 'week' ? 'Week' : 'Maand';
            return (
              <Pressable
                key={key}
                style={[styles.periodBtn, active && styles.periodBtnActive]}
                onPress={() => setPeriod(key)}
              >
                <Text style={[styles.periodText, active && styles.periodTextActive]}>{label}</Text>
              </Pressable>
            );
          }
        )}
      </View>

      <View style={styles.navRow}>
        <Pressable style={styles.navBtn} onPress={() => step(-1)}>
          <Ionicons name="chevron-back" size={20} color={colors.text} />
        </Pressable>
        <View style={styles.navMeta}>
          <Text style={styles.navLabel}>{payload?.label || '—'}</Text>
          {payload?.sub_label ? <Text style={styles.navSub}>{payload.sub_label}</Text> : null}
        </View>
        <Pressable
          style={[styles.navBtn, payload?.is_current && styles.navBtnDisabled]}
          disabled={!!payload?.is_current}
          onPress={() => step(1)}
        >
          <Ionicons name="chevron-forward" size={20} color={colors.text} />
        </Pressable>
      </View>

      {loading && !payload ? (
        <ActivityIndicator color={accent.hex} style={{ marginTop: 24 }} />
      ) : (
        <>
          <View style={styles.summary}>
            <Text style={styles.summaryLabel}>{payload?.total_label || 'Totaal'}</Text>
            <Text style={styles.summaryValue}>{formatEuroNl(total)}</Text>
            <Text style={styles.summaryHint}>
              {count === 1 ? '1 afgeronde rit' : `${count} afgeronde ritten`}
            </Text>
          </View>

          {displayRides.length === 0 ? (
            <Text style={styles.empty}>
              {payload?.empty_message || 'Geen afgeronde ritten in deze periode.'}
            </Text>
          ) : (
            <View style={styles.list}>
              {displayRides.map((ride) => (
                <View key={ride.id} style={styles.ride}>
                  <View style={styles.rideTop}>
                    <Text style={styles.rideTime}>{ride.time || '—'}</Text>
                    <View style={styles.rideAmountCol}>
                      <Text style={styles.rideAmount}>
                        {ride.primary != null ? formatEuroNl(ride.primary) : '—'}
                      </Text>
                      {ride.customerPays != null ? (
                        <Text style={styles.rideCustomerPays}>
                          klant {formatEuroNl(ride.customerPays)}
                        </Text>
                      ) : null}
                    </View>
                  </View>
                  <Text style={styles.rideRoute}>
                    {shortAddress(ride.pickup)}
                    <Text style={styles.rideArrow}> → </Text>
                    {shortAddress(ride.dropoff)}
                  </Text>
                </View>
              ))}
            </View>
          )}
        </>
      )}
    </ScrollView>
  );
}

function makeStyles(colors: ColorPalette, accentHex: string) {
  return StyleSheet.create({
    scroll: { paddingHorizontal: 20, paddingTop: 4, paddingBottom: 24 },
    periodRow: {
      flexDirection: 'row',
      borderRadius: 999,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      overflow: 'hidden',
      marginBottom: 12,
      backgroundColor: colors.card,
    },
    periodBtn: { flex: 1, paddingVertical: 8, alignItems: 'center' },
    periodBtnActive: { backgroundColor: accentHex },
    periodText: { color: colors.muted, fontSize: 13, fontWeight: '700' },
    periodTextActive: { color: '#fff' },
    navRow: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 14 },
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
    navBtnDisabled: { opacity: 0.35 },
    navMeta: { flex: 1, alignItems: 'center' },
    navLabel: { color: colors.text, fontSize: 15, fontWeight: '800' },
    navSub: { color: colors.muted, fontSize: 12, marginTop: 2 },
    summary: {
      borderRadius: 16,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      backgroundColor: colors.card,
      padding: 16,
      marginBottom: 16,
    },
    summaryLabel: { color: colors.muted, fontSize: 12, fontWeight: '700' },
    summaryValue: { color: colors.text, fontSize: 28, fontWeight: '800', marginTop: 4 },
    summaryHint: { color: colors.muted, fontSize: 13, marginTop: 4 },
    list: { gap: 10 },
    ride: {
      borderRadius: 14,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.35),
      backgroundColor: colors.card,
      paddingHorizontal: 14,
      paddingVertical: 12,
      gap: 4,
    },
    rideTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
    rideTime: { color: colors.muted, fontSize: 13, fontWeight: '700' },
    rideAmountCol: { alignItems: 'flex-end', gap: 1 },
    rideAmount: { color: colors.text, fontSize: 16, fontWeight: '800' },
    rideCustomerPays: { color: colors.muted, fontSize: 12, fontWeight: '600' },
    rideRoute: { color: colors.text, fontSize: 14, fontWeight: '700' },
    rideArrow: { color: accentHex, fontWeight: '800' },
    empty: { color: colors.muted, fontSize: 14, textAlign: 'center', marginTop: 12 },
  });
}
