import React, { ReactNode, useMemo } from 'react';
import { Pressable, StyleSheet, Text, View, ViewStyle } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { ColorPalette } from '../config';
import { t, useI18n } from '../i18n';
import { useThemeColors } from '../theme/ThemeContext';
import {
  AppBrandLogo,
} from './AppBrandLogo';
import {
  APP_CHROME_HEIGHT,
  APP_CHROME_PAD_H,
  APP_CHROME_SIDE_SLOT,
} from './appChrome';
import {
  SCREEN_TITLE_FONT_SIZE,
  SCREEN_TITLE_LINE_HEIGHT,
} from './ScreenHeader';
import { compactFontProps } from './androidText';

/** Zelfde top-inset als Taxi boeken (CustomerHomeScreen). */
export const APP_PAGE_TOP_INSET = 0;

/** Zijslot naast logo — gelijk aan Home-hero chrome. */
export const APP_PAGE_SIDE_SLOT = APP_CHROME_SIDE_SLOT;

/** Horizontale padding van de kop (logo-rij + titel). */
export const APP_PAGE_HEADER_PAD_H = APP_CHROME_PAD_H;

type Props = {
  title?: string | null;
  left?: ReactNode;
  right?: ReactNode;
  uri?: string | null;
  fallbackLabel?: string | null;
  style?: ViewStyle;
  /** Tap op het logo (bv. naar Boeken). */
  onLogoPress?: () => void;
  /** Extra ruimte onder de titel (startpagina kaarten). Default = titel-padding. */
  titleMarginBottom?: number;
  /** onDark = witte titel op donkere map-backdrop (ook in light mode). */
  chrome?: 'theme' | 'onDark';
};

/**
 * Canonieke kop: logo + optionele paginatitel.
 * Zelfde chrome-hoogte/slots als Home / Boeken hero.
 */
export function AppPageHeader({
  title,
  left,
  right,
  uri,
  fallbackLabel,
  style,
  onLogoPress,
  titleMarginBottom,
  chrome = 'theme',
}: Props) {
  const colors = useThemeColors();
  const insets = useSafeAreaInsets();
  useI18n();
  const onDark = chrome === 'onDark';
  const styles = useMemo(() => makeStyles(colors, onDark), [colors, onDark]);

  const logo = (
    <AppBrandLogo bare uri={uri} fallbackLabel={fallbackLabel} />
  );

  return (
    <View style={[styles.header, { paddingTop: insets.top }, style]}>
      <View style={styles.logoBar}>
        <View style={[styles.sideSlot, styles.sideSlotLeft]}>{left ?? null}</View>
        {onLogoPress ? (
          <Pressable
            onPress={onLogoPress}
            hitSlop={8}
            style={styles.logoCenter}
            accessibilityRole="button"
            accessibilityLabel={t('Naar Boeken')}
          >
            {logo}
          </Pressable>
        ) : (
          <View style={styles.logoCenter}>{logo}</View>
        )}
        <View style={[styles.sideSlot, styles.sideSlotRight]}>{right ?? null}</View>
      </View>
      {title ? (
        <Text
          style={[
            styles.title,
            titleMarginBottom != null ? { marginBottom: titleMarginBottom } : null,
          ]}
          numberOfLines={1}
          {...compactFontProps}
        >
          {title}
        </Text>
      ) : null}
    </View>
  );
}

function makeStyles(colors: ColorPalette, onDark: boolean) {
  return StyleSheet.create({
    header: {
      paddingHorizontal: APP_CHROME_PAD_H,
      paddingBottom: 0,
    },
    logoBar: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      minHeight: APP_CHROME_HEIGHT,
      height: APP_CHROME_HEIGHT,
      paddingTop: 0,
      paddingBottom: 0,
    },
    sideSlot: {
      minWidth: APP_CHROME_SIDE_SLOT,
      height: APP_CHROME_HEIGHT,
      flexDirection: 'row',
      alignItems: 'center',
      flexShrink: 0,
    },
    sideSlotLeft: {
      justifyContent: 'flex-start',
    },
    sideSlotRight: {
      justifyContent: 'flex-end',
    },
    logoCenter: {
      flex: 1,
      minWidth: 0,
      alignItems: 'center',
      justifyContent: 'center',
    },
    title: {
      textAlign: 'center',
      color: onDark ? '#FFFFFF' : colors.text,
      fontSize: SCREEN_TITLE_FONT_SIZE,
      lineHeight: SCREEN_TITLE_LINE_HEIGHT,
      fontWeight: '700',
      // Onder de map-header / chrome-lijn; extra lucht onder de titel.
      paddingTop: 34,
      paddingBottom: 18,
      marginBottom: 4,
    },
  });
}
