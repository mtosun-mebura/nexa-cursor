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

const cardBaseDark = require('../../../assets/nexa-booking/crops/nexa-card-base-dark.png');
/** Light: witte wash (plaatje 3) — geen grijze gradient. */
const cardBaseLight = require('../../../assets/nexa-booking/exact/bg/light/card_base_light.png');

type Props = {
  children: ReactNode;
  style?: StyleProp<ViewStyle>;
  contentStyle?: StyleProp<ViewStyle>;
  /** Geen fill-bg (alleen gloed-rand), bv. als er al een scene-bg is. */
  glowOnly?: boolean;
};

/**
 * Kaart met zelfde opbouw in light én dark.
 * Light: witte card_base_light. Dark: navy texture + wash.
 */
export function NexaGlowCard({ children, style, contentStyle, glowOnly = false }: Props) {
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  const styles = useMemo(() => makeStyles(light), [light]);

  return (
    <View style={[styles.card, style]}>
      {!glowOnly ? (
        light ? (
          <ImageBackground
            source={cardBaseLight}
            style={styles.fillBleed}
            imageStyle={styles.fillImg}
            pointerEvents="none"
          />
        ) : (
          <>
            <LinearGradient
              colors={['#0A1C38', '#102542', '#143056', '#102542', '#0A1C38']}
              locations={[0, 0.22, 0.5, 0.78, 1]}
              start={{ x: 0.5, y: 0 }}
              end={{ x: 0.5, y: 1 }}
              style={styles.fillBleed}
              pointerEvents="none"
            />
            <View style={styles.fillBleed} pointerEvents="none">
              <ImageBackground
                source={cardBaseDark}
                style={StyleSheet.absoluteFill}
                imageStyle={[styles.fillImg, { opacity: 0.32 }]}
              />
            </View>
            <LinearGradient
              colors={[
                'rgba(10,28,56,0.45)',
                'rgba(16,37,66,0.2)',
                'rgba(10,28,56,0.45)',
              ]}
              locations={[0, 0.5, 1]}
              style={styles.fillBleed}
              pointerEvents="none"
            />
          </>
        )
      ) : null}
      <View style={[styles.content, contentStyle]}>{children}</View>
    </View>
  );
}

function makeStyles(light: boolean) {
  return StyleSheet.create({
    card: {
      borderRadius: theme.radius.card,
      borderWidth: 1.5,
      borderColor: light ? 'rgba(8, 125, 255, 0.38)' : 'rgba(110, 190, 255, 0.55)',
      overflow: 'hidden',
      backgroundColor: light ? '#FFFFFF' : theme.color.navy800,
      shadowColor: light ? '#087DFF' : '#4DB2FF',
      shadowOpacity: light ? 0.1 : 0.35,
      shadowRadius: light ? 8 : 12,
      shadowOffset: { width: 0, height: light ? 2 : 0 },
      elevation: light ? 3 : 6,
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
    content: {
      position: 'relative',
      zIndex: 1,
      backgroundColor: 'transparent',
    },
  });
}
