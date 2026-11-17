import { LinearGradient } from 'expo-linear-gradient';
import React, { type ReactNode, useMemo } from 'react';
import {
  ImageBackground,
  StyleSheet,
  View,
  type StyleProp,
  type ViewStyle,
} from 'react-native';
import { useTheme } from '../../theme/ThemeContext';
import { nexaBookingTheme as theme } from './theme';

const cardBase = require('../../../assets/nexa-booking/crops/nexa-card-base-dark.png');

type Props = {
  children: ReactNode;
  style?: StyleProp<ViewStyle>;
  contentStyle?: StyleProp<ViewStyle>;
  /** Geen fill-bg (alleen gloed-rand), bv. als er al een scene-bg is. */
  glowOnly?: boolean;
};

/**
 * Kaart met overloop-achtergrond + cyan/blauwe rand.
 * Past zich aan op light/dark.
 */
export function NexaGlowCard({ children, style, contentStyle, glowOnly = false }: Props) {
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  const styles = useMemo(() => makeStyles(light), [light]);

  return (
    <View style={[styles.card, style]}>
      {!glowOnly ? (
        light ? (
          <LinearGradient
            colors={['#FFFFFF', '#F0F6FF', '#E7F0FF', '#F0F6FF', '#FFFFFF']}
            locations={[0, 0.22, 0.5, 0.78, 1]}
            start={{ x: 0.5, y: 0 }}
            end={{ x: 0.5, y: 1 }}
            style={styles.fillBleed}
            pointerEvents="none"
          />
        ) : (
          <>
            <LinearGradient
              colors={['#030914', '#0A1C38', '#1A4578', '#0A1C38', '#030914']}
              locations={[0, 0.22, 0.5, 0.78, 1]}
              start={{ x: 0.5, y: 0 }}
              end={{ x: 0.5, y: 1 }}
              style={styles.fillBleed}
              pointerEvents="none"
            />
            <View style={styles.fillBleed} pointerEvents="none">
              <ImageBackground
                source={cardBase}
                style={StyleSheet.absoluteFill}
                imageStyle={styles.fillImg}
              />
            </View>
          </>
        )
      ) : (
        <View style={[styles.fillBleed, styles.fillFallback]} pointerEvents="none" />
      )}
      <View style={[styles.content, contentStyle]}>{children}</View>
    </View>
  );
}

function makeStyles(light: boolean) {
  return StyleSheet.create({
    card: {
      borderRadius: theme.radius.card,
      borderWidth: 1.5,
      borderColor: light ? 'rgba(8, 125, 255, 0.4)' : 'rgba(110, 190, 255, 0.65)',
      overflow: 'hidden',
      backgroundColor: light ? '#F4F8FF' : '#0A1C38',
      shadowColor: light ? '#087DFF' : '#4DB2FF',
      shadowOpacity: light ? 0.12 : 0.55,
      shadowRadius: light ? 8 : 14,
      shadowOffset: { width: 0, height: light ? 2 : 0 },
      elevation: light ? 3 : 8,
    },
    fillBleed: {
      position: 'absolute',
      top: -2,
      right: -2,
      bottom: -2,
      left: -2,
    },
    fillImg: {
      resizeMode: 'cover',
    },
    fillFallback: {
      backgroundColor: light ? '#E8F1FF' : '#061329',
    },
    content: {
      position: 'relative',
      zIndex: 1,
      backgroundColor: 'transparent',
    },
  });
}
