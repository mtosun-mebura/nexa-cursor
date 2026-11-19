import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { t } from '../../i18n';
import { useTheme } from '../../theme/ThemeContext';
import { tabBarBottomPadding } from '../../ui/tabBarSafeArea';
import { nexaBookingTheme as theme } from './theme';

export type NexaCustomerTabKey = 'home' | 'book' | 'rides' | 'notifications' | 'more';

const TABS: {
  key: NexaCustomerTabKey;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
}[] = [
  { key: 'home', label: 'Home', icon: 'home' },
  { key: 'book', label: 'Boeken', icon: 'car-sport' },
  { key: 'rides', label: 'Ritten', icon: 'calendar-outline' },
  { key: 'notifications', label: 'Meldingen', icon: 'notifications-outline' },
  { key: 'more', label: 'Profiel', icon: 'person-outline' },
];

export function NexaCustomerTabBar({
  active,
  onChange,
  ridesBadge,
  notificationsBadge,
}: {
  active: NexaCustomerTabKey;
  onChange: (key: NexaCustomerTabKey) => void;
  ridesBadge?: number;
  notificationsBadge?: number;
}) {
  const insets = useSafeAreaInsets();
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';

  const barBg = light ? '#FFFFFF' : theme.color.navy800;

  return (
    <View
      style={[
        styles.bar,
        {
          // Footer-kleur tot onder de home-indicator (geen zwarte spleet).
          paddingBottom: Math.max(tabBarBottomPadding(insets.bottom), insets.bottom),
          borderTopColor: light ? 'rgba(8, 125, 255, 0.2)' : theme.color.border,
          backgroundColor: barBg,
        },
      ]}
    >
      {TABS.map((tab) => {
        const isActive = active === tab.key;
        const tint = isActive
          ? theme.color.orangeHot
          : light
            ? '#64748B'
            : theme.color.textMuted;
        const badge =
          tab.key === 'rides'
            ? ridesBadge
            : tab.key === 'notifications'
              ? notificationsBadge
              : 0;
        return (
          <Pressable
            key={tab.key}
            onPress={() => onChange(tab.key)}
            style={styles.tab}
            accessibilityRole="button"
            accessibilityState={{ selected: isActive }}
            accessibilityLabel={t(tab.label)}
          >
            {badge && badge > 0 ? (
              <View style={styles.badge}>
                <Text style={styles.badgeText}>{badge > 9 ? '9+' : String(badge)}</Text>
              </View>
            ) : null}
            <Ionicons name={tab.icon} size={22} color={tint} />
            <Text style={[styles.label, { color: tint }]} numberOfLines={1}>
              {t(tab.label)}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  bar: {
    flexDirection: 'row',
    borderTopWidth: 1,
    paddingTop: 8,
    paddingHorizontal: 4,
  },
  tab: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 6,
    minHeight: 52,
    position: 'relative',
  },
  label: {
    marginTop: 3,
    fontSize: 10,
    fontWeight: '700',
  },
  badge: {
    position: 'absolute',
    top: 2,
    right: '22%',
    minWidth: 16,
    height: 16,
    borderRadius: 999,
    backgroundColor: theme.color.orangeHot,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
    zIndex: 2,
  },
  badgeText: {
    color: '#fff',
    fontSize: 10,
    fontWeight: '800',
  },
});
