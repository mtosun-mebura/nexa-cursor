import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useThemeColors } from '../theme/ThemeContext';

export type ContractTabKey = 'today' | 'week' | 'navigation' | 'absences' | 'profile';

const TABS: {
  key: ContractTabKey;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
}[] = [
  { key: 'today', label: 'Vandaag', icon: 'calendar-outline' },
  { key: 'week', label: 'Planning', icon: 'list-outline' },
  { key: 'navigation', label: 'Navigatie', icon: 'navigate-outline' },
  { key: 'absences', label: 'Afmeldingen', icon: 'close-circle-outline' },
  { key: 'profile', label: 'Profiel', icon: 'person-outline' },
];

export function ContractTabBar({
  active,
  onChange,
}: {
  active: ContractTabKey;
  onChange: (key: ContractTabKey) => void;
}) {
  const insets = useSafeAreaInsets();
  const colors = useThemeColors();

  return (
    <View
      style={[
        styles.bar,
        {
          paddingBottom: Math.max(2, insets.bottom > 0 ? insets.bottom - 10 : 2),
          borderTopColor: colors.border,
          backgroundColor: colors.tabBar,
        },
      ]}
    >
      {TABS.map((tab) => {
        const isActive = active === tab.key;
        const tint = isActive ? colors.amber : colors.muted;
        return (
          <Pressable
            key={tab.key}
            onPress={() => onChange(tab.key)}
            style={[styles.tab, isActive && { backgroundColor: colors.amber + '24' }]}
            accessibilityRole="button"
            accessibilityState={{ selected: isActive }}
            accessibilityLabel={tab.label}
          >
            <Ionicons name={tab.icon} size={18} color={tint} style={styles.icon} />
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
    paddingTop: 2,
    paddingHorizontal: 2,
    gap: 1,
  },
  tab: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 3,
    borderRadius: 10,
    minHeight: 42,
  },
  icon: {
    marginBottom: 1,
  },
  label: {
    fontSize: 9,
    fontWeight: '700',
  },
});
