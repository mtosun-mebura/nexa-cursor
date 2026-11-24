import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  ImageBackground,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  useWindowDimensions,
  View,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { t } from '../../i18n';
import { useTheme } from '../../theme/ThemeContext';
import { AppBrandLogo } from '../../ui/AppBrandLogo';
import { AppearanceControls } from '../../ui/AppearanceControls';
import { APP_CHROME_HEIGHT } from '../../ui/appChrome';
import { exactHeroHome, exactModeAsset } from './exactAssets';
import { NexaGlowCard } from './NexaGlowCard';
import {
  nexaHeroChromeRow,
  nexaHeroContentHeight,
  nexaHeroImageStyle,
  nexaHeroReadableScrim,
  nexaHeroSideRight,
  nexaHeroSideSlot,
} from './nexaHeroLayout';
import { nexaBookingTheme as theme, nexaSurfaceText } from './theme';

const VISITED_KEY = 'nexa_customer_has_visited';

type Props = {
  greetingName?: string | null;
  /** Terugkerende klant (naam / rittenhistorie). */
  isReturning?: boolean;
  onBack?: () => void;
  onBook: () => void;
  onRides: () => void;
  onAccount: () => void;
  onDestinationFocus: () => void;
  onAirport?: () => void;
  onBusiness?: () => void;
  onGroup?: () => void;
  /** Actieve-rit banner — tussen hero en tiles. */
  topBanner?: React.ReactNode;
};

export function NexaHomeScreen({
  greetingName,
  isReturning = false,
  onBack,
  onBook,
  onRides,
  onAccount,
  onDestinationFocus,
  onAirport,
  onBusiness,
  onGroup,
  topBanner,
}: Props) {
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  const insets = useSafeAreaInsets();
  const { width } = useWindowDimensions();
  const name = String(greetingName || '').trim();
  const [visitedBefore, setVisitedBefore] = useState(false);
  const returning = isReturning || name.length > 0 || visitedBefore;
  const [chromeOpacity, setChromeOpacity] = useState(0);
  const chromeOpacityRef = useRef(0);
  const heroSource = exactModeAsset(exactHeroHome, light);
  const heroHeight = useMemo(
    () => nexaHeroContentHeight(width, insets.top, 'home'),
    [width, insets.top]
  );
  const imgStyle = useMemo(
    () => nexaHeroImageStyle(width, heroHeight, light),
    [width, heroHeight, light]
  );
  const scrim = useMemo(() => nexaHeroReadableScrim(light), [light]);
  const fadeTo = light ? '#FFFFFF' : theme.color.navy900;

  useEffect(() => {
    let cancelled = false;
    AsyncStorage.getItem(VISITED_KEY)
      .then((v) => {
        if (cancelled) return;
        if (v === '1') {
          setVisitedBefore(true);
        } else {
          AsyncStorage.setItem(VISITED_KEY, '1').catch(() => undefined);
        }
      })
      .catch(() => undefined);
    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <View style={[styles.wrap, light && { backgroundColor: '#FFFFFF' }]}>
    <ScrollView
      style={[styles.scroll, light && { backgroundColor: '#FFFFFF' }]}
      contentContainerStyle={styles.content}
      showsVerticalScrollIndicator={false}
      scrollEventThrottle={8}
      onScroll={(e) => {
        const y = e.nativeEvent.contentOffset.y;
        const next = y <= 2 ? 0 : Math.min(1, (y - 2) / 12);
        if (Math.abs(next - chromeOpacityRef.current) < 0.03 && next > 0 && next < 1) {
          return;
        }
        chromeOpacityRef.current = next;
        setChromeOpacity(next);
      }}
    >
      <View style={[styles.heroBlock, light && { backgroundColor: '#FFFFFF' }]}>
        <ImageBackground
          source={heroSource}
          style={[
            styles.heroImage,
            {
              height: heroHeight,
              paddingTop: insets.top,
              backgroundColor: light ? '#FFFFFF' : theme.color.navy900,
            },
          ]}
          imageStyle={imgStyle}
        >
          <LinearGradient
            colors={scrim.horizontal}
            locations={scrim.horizontalLoc}
            start={{ x: 0, y: 0.5 }}
            end={{ x: 1, y: 0.5 }}
            style={StyleSheet.absoluteFill}
          />
          <LinearGradient
            colors={scrim.bottom}
            locations={scrim.bottomLoc}
            style={styles.heroBottomScrim}
            pointerEvents="none"
          />

          {/* Ruimte voor sticky chrome — logo/icons zitten erboven. */}
          <View style={nexaHeroChromeRow} pointerEvents="none" />

          <View style={[styles.copy, styles.copyLower]}>
            {returning ? (
              <>
                <Text style={styles.welcomeBold}>{t('Welkom terug,')}</Text>
                <Text style={[styles.headline, light && styles.headlineLight]}>
                  {name
                    ? t('Goed je weer te zien, {name}!', { name })
                    : t('Goed je weer te zien!')}
                </Text>
              </>
            ) : (
              <Text style={[styles.headline, light && styles.headlineLight]}>
                {t('Welkom bij NEXA Taxi')}
              </Text>
            )}
            <View style={styles.underline} />
          </View>
        </ImageBackground>
        <LinearGradient
          colors={
            light
              ? [
                  'rgba(255,255,255,0)',
                  'rgba(255,255,255,0.55)',
                  'rgba(255,255,255,0.92)',
                  '#FFFFFF',
                ]
              : [
                  'rgba(6,19,41,0)',
                  'rgba(6,19,41,0.55)',
                  'rgba(6,19,41,0.92)',
                  fadeTo,
                ]
          }
          locations={[0, 0.35, 0.7, 1]}
          style={styles.heroBottomFade}
          pointerEvents="none"
        />
      </View>

      {topBanner ? <View style={styles.bannerSlot}>{topBanner}</View> : null}

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
          accessibilityLabel={t('Profiel')}
        >
          <Ionicons name="person" size={26} color={theme.color.navy900} />
          <Text style={[styles.tileText, styles.tileTextDark]}>{t('Profiel')}</Text>
        </Pressable>
      </View>

      <NexaGlowCard contentStyle={styles.searchCard}>
        <Pressable
          style={[styles.searchBar, light && styles.searchBarLight]}
          onPress={onDestinationFocus}
          accessibilityRole="button"
          accessibilityLabel={t('Waar wil je naartoe?')}
        >
          <Ionicons name="location" size={22} color={theme.color.orangeHot} />
          <Text style={[styles.searchText, light && { color: nexaSurfaceText(true) }]}>
            {t('Waar wil je naartoe?')}
          </Text>
          <Ionicons
            name="chevron-forward"
            size={20}
            color={light ? theme.color.inkMuted : theme.color.navy800}
          />
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
      <View
        collapsable={false}
        style={[
          styles.stickyChrome,
          {
            height: insets.top + APP_CHROME_HEIGHT,
            backgroundColor: light
              ? `rgba(255,255,255,${chromeOpacity})`
              : `rgba(6,19,41,${chromeOpacity})`,
            borderBottomWidth: chromeOpacity > 0.2 ? StyleSheet.hairlineWidth : 0,
            borderBottomColor: light
              ? 'rgba(15,23,42,0.12)'
              : 'rgba(255,255,255,0.12)',
          },
        ]}
        pointerEvents="box-none"
      >
        <View
          style={{ paddingTop: insets.top, zIndex: 2, height: insets.top + APP_CHROME_HEIGHT }}
          pointerEvents="box-none"
        >
          <View style={nexaHeroChromeRow}>
            <View style={nexaHeroSideSlot}>
              {onBack ? (
                <Pressable
                  onPress={onBack}
                  hitSlop={10}
                  accessibilityRole="button"
                  accessibilityLabel={t('Naar startpagina')}
                  style={[styles.homeBtn, light && styles.homeBtnLight]}
                >
                  <Ionicons
                    name="home-outline"
                    size={24}
                    color={light ? theme.color.ink : '#FFFFFF'}
                  />
                </Pressable>
              ) : null}
            </View>
            <View style={styles.logoCenter}>
              <AppBrandLogo bare />
            </View>
            <View style={[nexaHeroSideSlot, nexaHeroSideRight]}>
              <AppearanceControls tone={light ? 'theme' : 'onDark'} />
            </View>
          </View>
        </View>
      </View>
    </View>
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
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  return (
    <Pressable style={styles.shortcut} onPress={onPress} accessibilityRole="button">
      <View style={[styles.shortcutIcon, light && styles.shortcutIconLight]}>
        <Ionicons
          name={icon}
          size={22}
          color={light ? theme.color.blueBright : theme.color.lightBlue}
        />
      </View>
      <Text style={[styles.shortcutLabel, light && { color: nexaSurfaceText(true) }]}>
        {label}
      </Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  wrap: {
    flex: 1,
    backgroundColor: theme.color.navy900,
  },
  scroll: {
    flex: 1,
    backgroundColor: theme.color.navy900,
    zIndex: 0,
  },
  content: {
    paddingHorizontal: 12,
    paddingBottom: 28,
  },
  stickyChrome: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    zIndex: 100,
    elevation: 24,
    overflow: 'hidden',
  },
  stickyChromeBg: {
    ...StyleSheet.absoluteFillObject,
    zIndex: 0,
  },
  logoCenter: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  homeBtn: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'transparent',
  },
  homeBtnLight: {
    backgroundColor: 'transparent',
  },
  heroBlock: {
    marginHorizontal: -12,
    marginBottom: 6,
    overflow: 'visible',
  },
  heroImage: {
    justifyContent: 'space-between',
    paddingHorizontal: 0,
    paddingBottom: 22,
    overflow: 'hidden',
  },
  heroBottomScrim: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    height: '48%',
  },
  heroBottomFade: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: -48,
    height: 96,
  },
  copy: {
    paddingHorizontal: 16,
    maxWidth: '72%',
    paddingBottom: 8,
  },
  /** Halve regel omhoog t.o.v. vorige spot — tekst/oranje lijn boven de overloop. */
  copyLower: {
    transform: [{ translateY: 11 }],
  },
  welcomeBold: {
    color: theme.color.orangeHot,
    fontSize: 16,
    fontWeight: '800',
  },
  headline: {
    color: '#FFFFFF',
    fontSize: theme.font.section,
    fontWeight: '800',
    marginTop: 2,
  },
  headlineLight: {
    color: theme.color.ink,
  },
  underline: {
    marginTop: 8,
    width: 78,
    height: 3,
    borderRadius: 2,
    backgroundColor: theme.color.orangeHot,
  },
  bannerSlot: {
    marginBottom: 16,
  },
  tileRow: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 16,
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
    backgroundColor: '#FFFFFF',
    borderRadius: theme.radius.md,
    minHeight: 52,
    paddingHorizontal: 14,
  },
  searchBarLight: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: 'rgba(8, 125, 255, 0.28)',
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
  shortcutIconLight: {
    backgroundColor: 'rgba(8, 125, 255, 0.1)',
    borderColor: 'rgba(8, 125, 255, 0.45)',
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
