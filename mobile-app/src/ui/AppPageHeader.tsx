import React, { ReactNode, useMemo } from 'react';
import { Pressable, StyleSheet, Text, View, ViewStyle } from 'react-native';
import { ColorPalette } from '../config';
import { t, useI18n } from '../i18n';
import { useThemeColors } from '../theme/ThemeContext';
import {
  AppBrandLogo,
  APP_LOGO_BAR_MIN_HEIGHT,
} from './AppBrandLogo';
import {
  SCREEN_TITLE_FONT_SIZE,
  SCREEN_TITLE_LINE_HEIGHT,
} from './ScreenHeader';
import { compactFontProps } from './androidText';

/** Zelfde top-inset als Taxi boeken (CustomerHomeScreen). */
export const APP_PAGE_TOP_INSET = 0;

/** Zijslot naast logo (home / spacer) — zelfde breedte als klantscherm. */
export const APP_PAGE_SIDE_SLOT = 40;

/** Horizontale padding van de kop (logo-rij + titel). */
export const APP_PAGE_HEADER_PAD_H = 12;

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
 * Zelfde hoogte/lettertype op start, boeken, login, marktplaats, enz.
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
  useI18n();
  const onDark = chrome === 'onDark';
  const styles = useMemo(() => makeStyles(colors, onDark), [colors, onDark]);

  const logo = (
    <AppBrandLogo bare uri={uri} fallbackLabel={fallbackLabel} />
  );

  return (
    <View style={[styles.header, style]}>
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
      paddingHorizontal: APP_PAGE_HEADER_PAD_H,
      paddingBottom: 0,
    },
    logoBar: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      minHeight: APP_LOGO_BAR_MIN_HEIGHT,
      paddingTop: 2,
      paddingBottom: 2,
    },
    sideSlot: {
      minWidth: APP_PAGE_SIDE_SLOT,
      minHeight: APP_PAGE_SIDE_SLOT,
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
      // Ruimte boven de titel zodat de map-routelijn in de header-bg zichtbaar blijft
      paddingTop: 18,
      paddingBottom: 8,
      marginBottom: 0,
    },
  });
}
