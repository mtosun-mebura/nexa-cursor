import React, { useMemo } from 'react';
import {
  Image,
  ImageSourcePropType,
  ImageStyle,
  StyleSheet,
  Text,
  View,
  ViewStyle,
} from 'react-native';
import { ColorPalette } from '../config';
import { useTheme } from '../theme/ThemeContext';

/** Canonieke logo-maat — zelfde als Taxi boeken / klantscherm. */
export const APP_LOGO_WIDTH = 168;
export const APP_LOGO_HEIGHT = 42;
export const APP_LOGO_BAR_MIN_HEIGHT = 44;

const logoLight = require('../../assets/nexa-taxi-logo.png');
const logoDark = require('../../assets/nexa-taxi-logo-dark.png');

type Props = {
  /** Extern logo (bedrijf); leeg = Nexa Taxi-merklogo. */
  uri?: string | null;
  /** Fallbacktekst als er geen uri is en fallback gewenst is. */
  fallbackLabel?: string | null;
  accessibilityLabel?: string;
  style?: ImageStyle;
  /** Alleen het Image (zonder centrerende balk), bv. in een header-rij. */
  bare?: boolean;
  barStyle?: ViewStyle;
  /** Extra ruimte onder de logo-balk (startscherm t.o.v. eerste kaart). */
  marginBottom?: number;
};

/**
 * Merk-/bedrijfslogo met vaste hoogte en breedte over alle schermen.
 */
export function AppBrandLogo({
  uri,
  fallbackLabel,
  accessibilityLabel,
  style,
  bare = false,
  barStyle,
  marginBottom = 0,
}: Props) {
  const { colors, colorScheme } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const brandSource: ImageSourcePropType =
    colorScheme === 'light' ? logoLight : logoDark;

  const image = uri ? (
    <Image
      source={{ uri }}
      style={[styles.logo, style]}
      resizeMode="contain"
      accessibilityLabel={accessibilityLabel || fallbackLabel || 'Logo'}
    />
  ) : fallbackLabel ? (
    <Text style={styles.fallback} numberOfLines={1}>
      {fallbackLabel}
    </Text>
  ) : (
    <Image
      source={brandSource}
      style={[styles.logo, style]}
      resizeMode="contain"
      accessibilityLabel={accessibilityLabel || 'NEXA | taxi'}
    />
  );

  if (bare) return image;

  return (
    <View style={[styles.bar, barStyle, marginBottom ? { marginBottom } : null]}>
      {image}
    </View>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    bar: {
      alignItems: 'center',
      justifyContent: 'center',
      // Zelfde rijhoogte als AppPageHeader / Taxi boeken.
      minHeight: APP_LOGO_BAR_MIN_HEIGHT,
      paddingTop: 2,
      paddingBottom: 2,
    },
    logo: {
      width: APP_LOGO_WIDTH,
      height: APP_LOGO_HEIGHT,
    },
    fallback: {
      color: colors.text,
      fontSize: 18,
      fontWeight: '700',
      maxWidth: APP_LOGO_WIDTH + 24,
      textAlign: 'center',
      lineHeight: APP_LOGO_HEIGHT,
    },
  });
}
