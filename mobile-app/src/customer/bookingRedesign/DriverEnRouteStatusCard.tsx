import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import React, { useMemo } from 'react';
import {
  Pressable,
  StyleSheet,
  Text,
  useWindowDimensions,
  View,
} from 'react-native';
import { t } from '../../i18n';
import { useTheme } from '../../theme/ThemeContext';

type Props = {
  title: string;
  etaText?: string | null;
  routeText?: string | null;
  isLive?: boolean;
  onFollow: () => void;
  testID?: string;
};

/**
 * Live-statuskaart “Chauffeur onderweg” — CURSOR_NEXA_LIVE_STATUSKAART.md
 */
export function DriverEnRouteStatusCard({
  title,
  etaText,
  routeText,
  isLive = false,
  onFollow,
  testID,
}: Props) {
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  const { width } = useWindowDimensions();
  const stackFollow = width < 360;
  const styles = useMemo(() => makeStyles(light, stackFollow), [light, stackFollow]);

  return (
    <Pressable
      testID={testID}
      onPress={onFollow}
      accessibilityRole="button"
      accessibilityLabel={t('Volg chauffeur naar ophaallocatie')}
      style={({ pressed }) => [styles.card, pressed && styles.pressed]}
    >
      <LinearGradient
        colors={
          light ? ['#F7FFF9', '#EEFBF3', '#F7FFF9'] : ['#0B203B', '#0A1C34', '#08192F']
        }
        locations={light ? [0, 0.5, 1] : [0, 0.55, 1]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={StyleSheet.absoluteFill}
        pointerEvents="none"
      />

      <View style={styles.row}>
        <View style={styles.iconGlowOuter}>
          <View style={styles.iconCircle}>
            <Ionicons
              name="car-sport"
              size={22}
              color={light ? '#15803D' : '#FFFFFF'}
            />
          </View>
        </View>
        <View style={styles.separator} />
        <View style={styles.content}>
          <View style={styles.titleRow}>
            <Text style={styles.title} numberOfLines={2}>
              {title}
            </Text>
            {isLive ? (
              <View style={styles.liveBadge}>
                <Text style={styles.liveBadgeText}>{t('LIVE')}</Text>
              </View>
            ) : null}
          </View>
          {etaText ? (
            <Text style={styles.eta} numberOfLines={2}>
              {etaText}
            </Text>
          ) : null}
          {routeText ? (
            <Text style={styles.route} numberOfLines={1}>
              {routeText}
            </Text>
          ) : null}
          {stackFollow ? (
            <Pressable
              onPress={onFollow}
              style={({ pressed }) => [styles.followBtn, pressed && { opacity: 0.9 }]}
              accessibilityRole="button"
              accessibilityLabel={t('Volg chauffeur naar ophaallocatie')}
            >
              <Text style={styles.followText}>{t('Volgen')}</Text>
            </Pressable>
          ) : null}
        </View>
        {!stackFollow ? (
          <Pressable
            onPress={onFollow}
            style={({ pressed }) => [styles.followBtn, pressed && { opacity: 0.9 }]}
            accessibilityRole="button"
            accessibilityLabel={t('Volg chauffeur naar ophaallocatie')}
            hitSlop={4}
          >
            <Text style={styles.followText}>{t('Volgen')}</Text>
          </Pressable>
        ) : null}
      </View>
    </Pressable>
  );
}

function makeStyles(light: boolean, stackFollow: boolean) {
  return StyleSheet.create({
    card: {
      minHeight: 94,
      borderRadius: 22,
      borderWidth: 2,
      borderColor: light ? '#38B86B' : '#44E568',
      backgroundColor: light ? '#F7FFF9' : '#0B203B',
      paddingHorizontal: 14,
      paddingVertical: 14,
      overflow: 'hidden',
      shadowColor: light ? 'rgba(11,45,33,1)' : 'rgba(35,225,98,1)',
      shadowOpacity: light ? 0.1 : 0.15,
      shadowRadius: light ? 8 : 12,
      shadowOffset: { width: 0, height: light ? 2 : 0 },
      elevation: 4,
    },
    pressed: {
      opacity: 0.96,
    },
    row: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
    },
    iconGlowOuter: {
      width: 50,
      height: 50,
      borderRadius: 25,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: light ? 'rgba(56,184,107,0.1)' : 'rgba(68,229,104,0.1)',
      shadowColor: '#44E568',
      shadowOpacity: light ? 0.12 : 0.28,
      shadowRadius: light ? 4 : 6,
      shadowOffset: { width: 0, height: 0 },
      elevation: 2,
    },
    iconCircle: {
      width: 44,
      height: 44,
      borderRadius: 22,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: light ? '#DCFCE7' : '#143F2B',
      borderWidth: 2,
      borderColor: light ? '#38B86B' : '#44E568',
      shadowColor: '#44E568',
      shadowOpacity: light ? 0.12 : 0.28,
      shadowRadius: light ? 3 : 5,
      shadowOffset: { width: 0, height: 0 },
    },
    separator: {
      width: StyleSheet.hairlineWidth,
      alignSelf: 'stretch',
      backgroundColor: light ? 'rgba(56,184,107,0.35)' : 'rgba(80,195,128,0.4)',
      marginVertical: 2,
    },
    content: {
      flex: 1,
      minWidth: 0,
      gap: 3,
    },
    titleRow: {
      flexDirection: 'row',
      alignItems: 'center',
      flexWrap: 'wrap',
      gap: 6,
    },
    title: {
      color: light ? '#0B253B' : '#FFFFFF',
      fontSize: 15,
      fontWeight: '700',
      flexShrink: 1,
    },
    liveBadge: {
      backgroundColor: light ? '#D1FAE5' : '#4CF56E',
      borderRadius: 8,
      paddingHorizontal: 6,
      paddingVertical: 2,
    },
    liveBadgeText: {
      color: light ? '#166534' : '#07331B',
      fontSize: 9,
      fontWeight: '700',
      letterSpacing: 0.4,
    },
    eta: {
      color: light ? '#13763E' : '#A3FAB4',
      fontSize: 12.5,
      fontWeight: '600',
      lineHeight: 16,
    },
    route: {
      color: light ? '#5F7183' : '#AEBFD5',
      fontSize: 11,
      fontWeight: '400',
    },
    followBtn: {
      minWidth: 68,
      minHeight: 36,
      borderRadius: 16,
      paddingHorizontal: 12,
      paddingVertical: 8,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: light ? '#1FAE59' : '#4CF56E',
      alignSelf: stackFollow ? 'flex-end' : 'center',
      marginTop: stackFollow ? 6 : 0,
    },
    followText: {
      color: light ? '#FFFFFF' : '#082C19',
      fontSize: 12.5,
      fontWeight: '700',
    },
  });
}
