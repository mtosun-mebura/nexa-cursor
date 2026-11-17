import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import React from 'react';
import {
  Image,
  ImageBackground,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { t } from '../../i18n';
import { AppBrandLogo } from '../../ui/AppBrandLogo';
import { AppearanceControls } from '../../ui/AppearanceControls';
import { NexaGlowCard } from './NexaGlowCard';
import { nexaBookingTheme as theme } from './theme';

const homeHero = require('../../../assets/nexa-booking/crops/home_hero_scene_reference.png');

type Props = {
  greetingName?: string | null;
  onBook: () => void;
  onRides: () => void;
  onAccount: () => void;
  onDestinationFocus: () => void;
  onAirport?: () => void;
  onBusiness?: () => void;
  onGroup?: () => void;
  /** Optionele actieve-rit banner (boven de hero). */
  topBanner?: React.ReactNode;
};

export function NexaHomeScreen({
  greetingName,
  onBook,
  onRides,
  onAccount,
  onDestinationFocus,
  onAirport,
  onBusiness,
  onGroup,
  topBanner,
}: Props) {
  const name = String(greetingName || '').trim();

  return (
    <ScrollView
      style={styles.scroll}
      contentContainerStyle={styles.content}
      showsVerticalScrollIndicator={false}
    >
      <View style={styles.headerRow}>
        <View style={styles.sideSlot} />
        <AppBrandLogo bare />
        <View style={[styles.sideSlot, styles.sideRight]}>
          <AppearanceControls />
        </View>
      </View>

      {topBanner}

      <View style={styles.heroBlock}>
        <ImageBackground source={homeHero} style={styles.heroImage} imageStyle={styles.heroImageInner}>
          <LinearGradient
            colors={['rgba(6,19,41,0.15)', 'rgba(6,19,41,0.92)']}
            style={StyleSheet.absoluteFill}
          />
          <Text style={styles.welcome}>
            {name ? t('Welkom terug,') : t('Welkom bij NEXA Taxi')}
          </Text>
          <Text style={styles.headline}>
            {name
              ? t('Goed je weer te zien, {name}!', { name })
              : t('Goed je weer te zien!')}
          </Text>
          <View style={styles.underline} />
        </ImageBackground>
      </View>

      <View style={styles.tileRow}>
        <Pressable
          style={({ pressed }) => [styles.tile, styles.tileOrange, pressed && styles.pressed]}
          onPress={onBook}
          accessibilityRole="button"
          accessibilityLabel={t('Rit boeken')}
        >
          <Ionicons name="car-sport" size={28} color="#fff" />
          <Text style={styles.tileText}>{t('Rit boeken')}</Text>
        </Pressable>
        <Pressable
          style={({ pressed }) => [styles.tile, styles.tileBlue, pressed && styles.pressed]}
          onPress={onRides}
          accessibilityRole="button"
          accessibilityLabel={t('Mijn ritten')}
        >
          <Ionicons name="calendar" size={26} color="#fff" />
          <Text style={styles.tileText}>{t('Mijn ritten')}</Text>
        </Pressable>
        <Pressable
          style={({ pressed }) => [styles.tile, styles.tileLight, pressed && styles.pressed]}
          onPress={onAccount}
          accessibilityRole="button"
          accessibilityLabel={t('Account')}
        >
          <Ionicons name="person" size={26} color={theme.color.navy900} />
          <Text style={[styles.tileText, styles.tileTextDark]}>{t('Account')}</Text>
        </Pressable>
      </View>

      <NexaGlowCard contentStyle={styles.searchCard}>
        <Pressable
          style={styles.searchBar}
          onPress={onDestinationFocus}
          accessibilityRole="button"
          accessibilityLabel={t('Waar wil je naartoe?')}
        >
          <Ionicons name="location" size={22} color={theme.color.orangeHot} />
          <Text style={styles.searchText}>{t('Waar wil je naartoe?')}</Text>
          <Ionicons name="chevron-forward" size={20} color={theme.color.navy800} />
        </Pressable>

        <View style={styles.shortcutRow}>
          <Shortcut
            icon="airplane"
            label={t('Luchthaven')}
            onPress={onAirport || onBook}
          />
          <View style={styles.shortcutDivider} />
          <Shortcut
            icon="briefcase"
            label={t('Zakelijk')}
            onPress={onBusiness || onBook}
          />
          <View style={styles.shortcutDivider} />
          <Shortcut
            icon="people"
            label={t('Groepsvervoer')}
            onPress={onGroup || onBook}
          />
        </View>
      </NexaGlowCard>
    </ScrollView>
  );
}

function Shortcut({
  icon,
  label,
  onPress,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  onPress: () => void;
}) {
  return (
    <Pressable style={styles.shortcut} onPress={onPress} accessibilityRole="button">
      <View style={styles.shortcutIcon}>
        <Ionicons name={icon} size={22} color={theme.color.lightBlue} />
      </View>
      <Text style={styles.shortcutLabel}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  scroll: {
    flex: 1,
    backgroundColor: theme.color.navy900,
  },
  content: {
    paddingHorizontal: 12,
    paddingBottom: 28,
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    minHeight: 48,
    marginBottom: 8,
  },
  sideSlot: {
    minWidth: 72,
  },
  sideRight: {
    alignItems: 'flex-end',
  },
  heroBlock: {
    overflow: 'hidden',
    marginHorizontal: -12,
    marginBottom: 14,
  },
  heroImage: {
    minHeight: 210,
    justifyContent: 'flex-end',
    paddingHorizontal: 12,
    paddingBottom: 18,
    paddingTop: 48,
  },
  heroImageInner: {
    resizeMode: 'cover',
  },
  welcome: {
    color: theme.color.lightBlue,
    fontSize: 16,
    fontWeight: '500',
  },
  headline: {
    color: theme.color.text,
    fontSize: theme.font.title,
    fontWeight: '800',
    marginTop: 2,
  },
  underline: {
    marginTop: 8,
    width: 78,
    height: 3,
    borderRadius: 2,
    backgroundColor: theme.color.orangeHot,
  },
  tileRow: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 14,
  },
  tile: {
    flex: 1,
    minHeight: 96,
    borderRadius: theme.radius.card,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingHorizontal: 6,
  },
  tileOrange: {
    backgroundColor: theme.color.orangeHot,
  },
  tileBlue: {
    backgroundColor: theme.color.blueBright,
  },
  tileLight: {
    backgroundColor: '#E8EEF7',
  },
  tileText: {
    color: '#fff',
    fontSize: 13,
    fontWeight: '800',
    textAlign: 'center',
  },
  tileTextDark: {
    color: theme.color.navy900,
  },
  pressed: {
    opacity: 0.88,
  },
  searchCard: {
    padding: 14,
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: '#fff',
    borderRadius: theme.radius.md,
    minHeight: 52,
    paddingHorizontal: 14,
  },
  searchText: {
    flex: 1,
    color: theme.color.navy900,
    fontSize: 16,
    fontWeight: '700',
  },
  shortcutRow: {
    flexDirection: 'row',
    alignItems: 'stretch',
    marginTop: 16,
  },
  shortcut: {
    flex: 1,
    alignItems: 'center',
    gap: 8,
    paddingVertical: 4,
  },
  shortcutIcon: {
    width: 52,
    height: 52,
    borderRadius: 26,
    borderWidth: 1.5,
    borderColor: theme.color.blueBright,
    backgroundColor: theme.color.navy700,
    alignItems: 'center',
    justifyContent: 'center',
  },
  shortcutLabel: {
    color: theme.color.textMuted,
    fontSize: 12,
    fontWeight: '600',
    textAlign: 'center',
  },
  shortcutDivider: {
    width: 1,
    backgroundColor: theme.color.border,
    marginVertical: 8,
  },
});
