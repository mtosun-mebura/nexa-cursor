import React, { useMemo } from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import type { DriverActiveRide } from '../api/driver';
import { ColorPalette } from '../config';
import { hexAlpha, useDriverAccent } from '../theme/driverAccent';
import { useThemeColors } from '../theme/ThemeContext';

const carSource = require('../../assets/active-ride-car.png');
const GREEN = '#22C55E';

function shortStreet(address?: string | null): string {
  const text = String(address || '').trim();
  if (!text) return '—';
  const comma = text.indexOf(',');
  return comma > 0 ? text.slice(0, comma).trim() : text;
}

function rideStatus(ride: DriverActiveRide, accentHex: string): { label: string; color: string } {
  const status = String(ride.status || '');
  if (status === 'assigned') return { label: 'Onderweg', color: GREEN };
  if (status === 'accepted') return { label: 'Rit starten', color: accentHex };
  return { label: 'Lopende rit', color: accentHex };
}

export function ActiveRideBar({
  ride,
  onPress,
}: {
  ride: DriverActiveRide;
  onPress: () => void;
}) {
  const colors = useThemeColors();
  const accent = useDriverAccent();
  const styles = useMemo(() => makeStyles(colors, accent.hex), [colors, accent.hex]);
  const status = rideStatus(ride, accent.hex);
  const from = shortStreet(ride.pickup_address);
  const to = shortStreet(ride.dropoff_address);

  return (
    <Pressable
      onPress={onPress}
      style={styles.bar}
      accessibilityRole="button"
      accessibilityLabel={`${status.label}. ${from} naar ${to}. Open rit.`}
    >
      <Image source={carSource} style={styles.car} resizeMode="contain" />
      <View style={styles.text}>
        <Text style={[styles.status, { color: status.color }]}>{status.label}</Text>
        <Text style={styles.route} numberOfLines={1}>
          {from}
          <Text style={styles.arrow}> → </Text>
          {to}
        </Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={colors.muted} />
    </Pressable>
  );
}

function makeStyles(colors: ColorPalette, accentHex: string) {
  return StyleSheet.create({
    bar: {
      marginHorizontal: 16,
      marginBottom: 8,
      paddingHorizontal: 12,
      paddingVertical: 10,
      minHeight: 52,
      borderRadius: 14,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.5),
      backgroundColor: colors.card,
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
    },
    car: {
      width: 44,
      height: 30,
    },
    text: {
      flex: 1,
      minWidth: 0,
      gap: 2,
    },
    status: {
      fontSize: 12,
      fontWeight: '800',
      letterSpacing: 0.2,
      textTransform: 'uppercase',
    },
    route: {
      color: colors.text,
      fontSize: 13,
      fontWeight: '700',
    },
    arrow: {
      color: accentHex,
      fontWeight: '800',
    },
  });
}
