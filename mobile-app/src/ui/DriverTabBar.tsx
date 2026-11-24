import { t } from '../i18n';
import { Ionicons } from '@expo/vector-icons';
import React, { useMemo } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme, useThemeColors } from '../theme/ThemeContext';
import { compactFontProps } from './androidText';
import { tabBarBottomPadding } from './tabBarSafeArea';

export type DriverTabKey =
  | 'trips'
  | 'requests'
  | 'planning'
  | 'earnings'
  | 'profile';

const ALL_TABS: {
  key: DriverTabKey;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
}[] = [
  { key: 'trips', label: t('Ritten'), icon: 'calendar-outline' },
  { key: 'requests', label: t('Aanvragen'), icon: 'car-outline' },
  { key: 'planning', label: t('Planning'), icon: 'calendar-number-outline' },
  { key: 'earnings', label: t('Inkomsten'), icon: 'cash-outline' },
  { key: 'profile', label: t('Profiel'), icon: 'person-outline' },
];

const ORANGE = '#FF8A00';
const NAVY = '#0B1F3A';

export function DriverTabBar({
  active,
  onChange,
  showEarnings,
  requestsBadge,
  tripsBadge,
  accent,
}: {
  active: DriverTabKey;
  onChange: (key: DriverTabKey) => void;
  showEarnings?: boolean;
  requestsBadge?: number;
  tripsBadge?: number;
  accent?: string;
}) {
  const insets = useSafeAreaInsets();
  const colors = useThemeColors();
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  const tabs = useMemo(
    () => ALL_TABS.filter((tab) => tab.key !== 'earnings' || showEarnings),
    [showEarnings]
  );
  const activeTint = accent || ORANGE;
  const barBg = light ? '#FFFFFF' : NAVY;

  return (
    <View
      style={[
        styles.bar,
        {
          paddingBottom: Math.max(tabBarBottomPadding(insets.bottom), insets.bottom),
          borderTopColor: light ? 'rgba(8, 125, 255, 0.2)' : 'rgba(110, 190, 255, 0.28)',
          backgroundColor: barBg,
        },
      ]}
    >
      {tabs.map((tab) => {
        const isActive = active === tab.key;
        const tint = isActive ? activeTint : light ? '#64748B' : colors.muted;
        return (
          <Pressable
            key={tab.key}
            onPress={() => onChange(tab.key)}
            style={styles.tab}
            accessibilityRole="button"
            accessibilityState={{ selected: isActive }}
            accessibilityLabel={
              tab.key === 'requests' && requestsBadge && requestsBadge > 0
                ? t('Aanvragen ({count})', { count: requestsBadge })
                : tab.key === 'trips' && tripsBadge && tripsBadge > 0
                  ? t('Ritten ({count})', { count: tripsBadge })
                  : t(tab.label)
            }
          >
            {tab.key === 'requests' && requestsBadge && requestsBadge > 0 ? (
              <View style={styles.badge}>
                <Text style={styles.badgeText} {...compactFontProps}>
                  {requestsBadge > 9 ? '9+' : String(requestsBadge)}
                </Text>
              </View>
            ) : null}
            {tab.key === 'trips' && tripsBadge && tripsBadge > 0 ? (
              <View style={styles.badge}>
                <Text style={styles.badgeText} {...compactFontProps}>
                  {tripsBadge > 9 ? '9+' : String(tripsBadge)}
                </Text>
              </View>
            ) : null}
            <Ionicons name={tab.icon} size={22} color={tint} style={styles.icon} />
            <Text style={[styles.label, { color: tint }]} numberOfLines={1} {...compactFontProps}>
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
  icon: {
    marginBottom: 2,
  },
  label: {
    fontSize: 10,
    fontWeight: '700',
  },
  badge: {
    position: 'absolute',
    top: 2,
    right: '18%',
    minWidth: 16,
    height: 16,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
    zIndex: 2,
    backgroundColor: ORANGE,
  },
  badgeText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#FFFFFF',
  },
});
