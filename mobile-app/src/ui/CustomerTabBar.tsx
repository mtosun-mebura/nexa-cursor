import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useThemeColors } from '../theme/ThemeContext';

export type CustomerTabKey = 'book' | 'rides' | 'profile';

const TABS: {
  key: CustomerTabKey;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
}[] = [
  { key: 'book', label: 'Boeken', icon: 'book' },
  { key: 'rides', label: 'Ritten', icon: 'car-sport' },
  { key: 'profile', label: 'Profiel', icon: 'person' },
];

export function CustomerTabBar({
  active,
  onChange,
  ridesBadge,
}: {
  active: CustomerTabKey;
  onChange: (key: CustomerTabKey) => void;
  ridesBadge?: number;
}) {
  const insets = useSafeAreaInsets();
  const colors = useThemeColors();

  return (
    <View
      style={[
        styles.bar,
        {
          paddingBottom: Math.max(6, insets.bottom),
          borderTopColor: colors.border,
          backgroundColor: colors.tabBar,
        },
      ]}
    >
      {TABS.map((tab) => {
        const isActive = active === tab.key;
        const tint = isActive ? colors.primary : colors.muted;
        return (
          <Pressable
            key={tab.key}
            onPress={() => onChange(tab.key)}
            style={[
              styles.tab,
              isActive && { backgroundColor: colors.primary + '24' },
            ]}
            accessibilityRole="button"
            accessibilityState={{ selected: isActive }}
            accessibilityLabel={tab.label}
          >
            {tab.key === 'rides' && ridesBadge && ridesBadge > 0 ? (
              <View style={[styles.badge, { backgroundColor: colors.amber }]}>
                <Text style={[styles.badgeText, { color: colors.bg }]}>
                  {ridesBadge > 9 ? '9+' : String(ridesBadge)}
                </Text>
              </View>
            ) : null}
            <Ionicons name={tab.icon} size={22} color={tint} style={styles.icon} />
            <Text style={[styles.label, { color: tint }]}>{tab.label}</Text>
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
    paddingTop: 6,
    paddingHorizontal: 8,
    gap: 4,
  },
  tab: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 8,
    borderRadius: 12,
    minHeight: 52,
    position: 'relative',
  },
  icon: {
    marginBottom: 3,
  },
  label: {
    fontSize: 11,
    fontWeight: '600',
  },
  badge: {
    position: 'absolute',
    top: 4,
    right: '28%',
    minWidth: 16,
    height: 16,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
    zIndex: 2,
  },
  badgeText: {
    fontSize: 10,
    fontWeight: '800',
  },
});
