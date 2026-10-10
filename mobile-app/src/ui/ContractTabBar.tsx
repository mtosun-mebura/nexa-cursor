import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useThemeColors } from '../theme/ThemeContext';
import { tabBarBottomPadding } from './tabBarSafeArea';

export type ContractTabKey = 'trips' | 'planning' | 'navigation' | 'absences' | 'profile';

const TABS: {
  key: ContractTabKey;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
}[] = [
  { key: 'trips', label: 'Ritten', icon: 'calendar-outline' },
  { key: 'planning', label: 'Planning', icon: 'calendar-number-outline' },
  { key: 'navigation', label: 'Navigatie', icon: 'navigate-outline' },
  { key: 'absences', label: 'Afmeldingen', icon: 'close-circle-outline' },
  { key: 'profile', label: 'Profiel', icon: 'person-outline' },
];

export function ContractTabBar({
  active,
  onChange,
  tripsBadge,
  accent,
}: {
  active: ContractTabKey;
  onChange: (key: ContractTabKey) => void;
  tripsBadge?: number;
  accent?: string;
}) {
  const insets = useSafeAreaInsets();
  const colors = useThemeColors();

  return (
    <View
      style={[
        styles.bar,
        {
          paddingBottom: tabBarBottomPadding(insets.bottom),
          borderTopColor: colors.border,
          backgroundColor: colors.tabBar,
        },
      ]}
    >
      {TABS.map((tab) => {
        const isActive = active === tab.key;
        const tint = isActive ? accent || colors.amber : colors.muted;
        return (
          <Pressable
            key={tab.key}
            onPress={() => onChange(tab.key)}
            style={[styles.tab, isActive && { backgroundColor: `${accent || colors.amber}24` }]}
            accessibilityRole="button"
            accessibilityState={{ selected: isActive }}
            accessibilityLabel={
              tab.key === 'trips' && tripsBadge && tripsBadge > 0
                ? `Ritten (${tripsBadge})`
                : tab.label
            }
          >
            {tab.key === 'trips' && tripsBadge && tripsBadge > 0 ? (
              <View style={styles.badge}>
                <Text style={styles.badgeText}>
                  {tripsBadge > 9 ? '9+' : String(tripsBadge)}
                </Text>
              </View>
            ) : null}
            <Ionicons name={tab.icon} size={22} color={tint} style={styles.icon} />
            <Text style={[styles.label, { color: tint }]} numberOfLines={1}>
              {tab.label}
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
    paddingTop: 4,
    paddingHorizontal: 2,
    gap: 1,
  },
  tab: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 5,
    borderRadius: 10,
    minHeight: 48,
    position: 'relative',
  },
  icon: {
    marginBottom: 2,
  },
  label: {
    fontSize: 10,
    fontWeight: '700',
  },
  badge: {
    position: 'absolute',
    top: 0,
    right: '16%',
    minWidth: 18,
    height: 18,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
    zIndex: 2,
    backgroundColor: '#EF4444',
  },
  badgeText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#FFFFFF',
  },
});
