import React, { useMemo } from 'react';
import { StyleSheet, Text, View, ViewStyle } from 'react-native';
import { useThemeColors } from '../theme/ThemeContext';

/** Canonieke paginatitel — overal hetzelfde (Ritten, Planning, Navigatie, Profiel, …). */
export const SCREEN_TITLE_FONT_SIZE = 20;
export const SCREEN_TITLE_LINE_HEIGHT = 24;
export const SCREEN_HEADER_MARGIN_BOTTOM = 12;

type Props = {
  title: string;
  right?: React.ReactNode;
  style?: ViewStyle;
};

/**
 * Paginakop met vaste titelhoogte. Right-slot (datum, Dag/Week) uitlijnen op
 * flex-start zodat de titel niet omlaag schuift t.o.v. andere tabs.
 */
export function ScreenHeader({ title, right, style }: Props) {
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors.text), [colors.text]);

  return (
    <View style={[styles.row, style]}>
      <Text style={styles.title} numberOfLines={1}>
        {title}
      </Text>
      {right ? <View style={styles.right}>{right}</View> : null}
    </View>
  );
}

function makeStyles(textColor: string) {
  return StyleSheet.create({
    row: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      marginBottom: SCREEN_HEADER_MARGIN_BOTTOM,
      gap: 12,
      minHeight: SCREEN_TITLE_LINE_HEIGHT,
    },
    title: {
      flexShrink: 1,
      color: textColor,
      fontSize: SCREEN_TITLE_FONT_SIZE,
      lineHeight: SCREEN_TITLE_LINE_HEIGHT,
      fontWeight: '700',
      marginBottom: 0,
      paddingTop: 0,
    },
    right: {
      flexShrink: 0,
      maxWidth: '55%',
      alignItems: 'flex-end',
      // Optisch op één lijn met titel-cap height (niet verticaal centreren).
      paddingTop: 0,
    },
  });
}
