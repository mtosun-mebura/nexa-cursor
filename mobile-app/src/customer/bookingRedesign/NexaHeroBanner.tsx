import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import React, { useMemo } from 'react';
import {
  ImageBackground,
  Pressable,
  StyleSheet,
  Text,
  useWindowDimensions,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { t } from '../../i18n';
import { useTheme } from '../../theme/ThemeContext';
import { AppBrandLogo } from '../../ui/AppBrandLogo';
import { AppearanceControls } from '../../ui/AppearanceControls';
import { exactHeroHome, exactModeAsset } from './exactAssets';
import {
  nexaHeroChromeRow,
  nexaHeroContentHeight,
  nexaHeroImageStyle,
  nexaHeroReadableScrim,
  nexaHeroSideRight,
  nexaHeroSideSlot,
} from './nexaHeroLayout';
import { nexaBookingTheme as theme } from './theme';

/**
 * Hero Plan je rit — exact dezelfde asset/crop/scrim als Home.
 */
export function NexaHeroBanner({
  greetingName,
  title,
  subtitle,
  onBack,
  onLogoPress,
  showChrome = true,
}: {
  greetingName?: string | null;
  title?: string;
  subtitle?: string;
  onBack?: () => void;
  onLogoPress?: () => void;
  showChrome?: boolean;
}) {
  void greetingName;
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  const insets = useSafeAreaInsets();
  const { width } = useWindowDimensions();
  const planHero = exactModeAsset(exactHeroHome, light);
  const headline = title || t('Plan je rit');
  const sub =
    subtitle ||
    t('Geef je ophaallocatie en bestemming op, en bekijk direct je ritopties.');

  const heroHeight = useMemo(
    () => nexaHeroContentHeight(width, insets.top, 'plan'),
    [width, insets.top]
  );
  const imgStyle = useMemo(
    () => nexaHeroImageStyle(width, heroHeight, light),
    [width, heroHeight, light]
  );
  const scrim = useMemo(() => nexaHeroReadableScrim(light), [light]);
  const fadeTo = light ? '#FFFFFF' : theme.color.navy900;
  // Light: witte waas → donkere tekst. Dark: navy waas → witte tekst.
  const titleColor = light ? theme.color.ink : '#FFFFFF';
  const iconColor = light ? theme.color.ink : '#FFFFFF';

  return (
    <View style={[styles.wrap, { backgroundColor: fadeTo }]}>
      <ImageBackground
        source={planHero}
        style={[
          styles.hero,
          {
            height: heroHeight,
            paddingTop: insets.top,
            backgroundColor: fadeTo,
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

        {showChrome ? (
          <View style={nexaHeroChromeRow}>
            <View style={nexaHeroSideSlot}>
              {onBack ? (
                <Pressable
                  onPress={onBack}
                  hitSlop={10}
                  accessibilityRole="button"
                  accessibilityLabel={t('Naar startpagina')}
                  style={[styles.backBtn, light && styles.backBtnLight]}
                >
                  <Ionicons name="chevron-back" size={24} color={iconColor} />
                </Pressable>
              ) : null}
            </View>
            <Pressable
              onPress={onLogoPress}
              disabled={!onLogoPress}
              style={styles.logoCenter}
              accessibilityRole={onLogoPress ? 'button' : undefined}
            >
              <AppBrandLogo bare />
            </Pressable>
            <View style={[nexaHeroSideSlot, nexaHeroSideRight]}>
              <AppearanceControls tone={light ? 'theme' : 'onDark'} />
            </View>
          </View>
        ) : (
          /* Ruimte voor sticky chrome erboven — layout gelijk houden. */
          <View style={nexaHeroChromeRow} pointerEvents="none" />
        )}

        <View style={[styles.copy, width >= 700 && styles.copyWide]}>
          <Text style={[styles.headline, { color: titleColor }]}>{headline}</Text>
          <View style={styles.underline} />
          <Text style={[styles.sub, light ? styles.subLight : null]}>{sub}</Text>
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
  );
}

const styles = StyleSheet.create({
  wrap: {
    marginBottom: 6,
    marginHorizontal: -12,
    overflow: 'visible',
  },
  hero: {
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
  backBtn: {
    width: 40,
    height: 40,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(6,19,41,0.45)',
  },
  backBtnLight: {
    backgroundColor: 'rgba(255,255,255,0.72)',
  },
  logoCenter: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  copy: {
    maxWidth: '72%',
    paddingHorizontal: 16,
    paddingBottom: 8,
  },
  copyWide: {
    maxWidth: 480,
  },
  headline: {
    color: '#FFFFFF',
    fontSize: theme.font.section,
    fontWeight: '800',
  },
  underline: {
    marginTop: 6,
    width: 72,
    height: 3,
    borderRadius: 2,
    backgroundColor: theme.color.orangeHot,
  },
  sub: {
    marginTop: 8,
    color: 'rgba(255,255,255,0.92)',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '700',
  },
  subLight: {
    color: theme.color.ink,
    fontSize: 12.5,
    lineHeight: 17,
    fontWeight: '700',
  },
});
