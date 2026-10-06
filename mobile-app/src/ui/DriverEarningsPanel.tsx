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
  DriverEarningsPayload,
  fetchDriverEarnings,
} from '../api/driver';
import { ApiError } from '../api/client';
import { ColorPalette } from '../config';
import { formatEuroNl } from '../geo/route';
import { ErrorText } from './components';
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

function shortAddress(address?: string | null): string {
  const text = String(address || '').trim();
  if (!text) return '—';
  const comma = text.indexOf(',');
  return comma > 0 ? text.slice(0, comma).trim() : text;
}

export function DriverEarningsPanel({
  token,
  canViewMonth,
}: {
  token: string;
  canViewMonth?: boolean;
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
        setPayload(res?.data || null);
        if (res?.data?.date) setDate(res.data.date);
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

  const total = Number(payload?.period_total ?? payload?.day_total ?? 0);
  const rides = payload?.rides || [];
  const count = Number(payload?.ride_count ?? rides.length);

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
      <Text style={styles.title}>Inkomsten</Text>
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

      <ErrorText>{error}</ErrorText>

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

          {rides.length === 0 ? (
            <Text style={styles.empty}>
              {payload?.empty_message || 'Geen afgeronde ritten in deze periode.'}
            </Text>
          ) : (
            <View style={styles.list}>
              {rides.map((ride) => (
                <View key={ride.id} style={styles.ride}>
                  <View style={styles.rideTop}>
                    <Text style={styles.rideTime}>{ride.completed_time || '—'}</Text>
                    <Text style={styles.rideAmount}>{formatEuroNl(Number(ride.amount || 0))}</Text>
                  </View>
                  <Text style={styles.rideRoute}>
                    {shortAddress(ride.pickup_address)}
                    <Text style={styles.rideArrow}> → </Text>
                    {shortAddress(ride.dropoff_address)}
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
    scroll: { paddingHorizontal: 16, paddingTop: 8, paddingBottom: 28 },
    title: { color: colors.text, fontSize: 22, fontWeight: '800', marginBottom: 14 },
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
    rideTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
    rideTime: { color: colors.muted, fontSize: 13, fontWeight: '700' },
    rideAmount: { color: colors.text, fontSize: 16, fontWeight: '800' },
    rideRoute: { color: colors.text, fontSize: 14, fontWeight: '700' },
    rideArrow: { color: accentHex, fontWeight: '800' },
    empty: { color: colors.muted, fontSize: 14, textAlign: 'center', marginTop: 12 },
  });
}
