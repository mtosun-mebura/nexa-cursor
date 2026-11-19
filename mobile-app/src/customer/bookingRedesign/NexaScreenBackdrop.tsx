import { LinearGradient } from 'expo-linear-gradient';
import React, { useMemo } from 'react';
import {
  Image,
  StyleSheet,
  useWindowDimensions,
  View,
} from 'react-native';
import { useTheme } from '../../theme/ThemeContext';
import { nexaBookingTheme as theme } from './theme';

/** Dark map 1536×1024 — route in bovenste helft. */
const mapBackdropDark = require('../../../assets/map-header-background.png');
/** Light map 1024×768 — al wit uitgefade; geen waas nodig. */
const mapBackdropLight = require('../../../assets/map-header-background-light.jpg');

const MAP_ASPECT_DARK = 1536 / 1024;
const MAP_ASPECT_LIGHT = 1024 / 768;

/**
 * Achtergrond Ritten / Meldingen / Profiel.
 * Light: dedicated witte map-asset (geen grijze waas).
 * Dark: navy map + soft fade naar navy page-bg.
 */
export function NexaScreenBackdrop() {
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  const { width: screenW } = useWindowDimensions();
  const aspect = light ? MAP_ASPECT_LIGHT : MAP_ASPECT_DARK;
  const mapH = Math.round(screenW / aspect);
  const styles = useMemo(() => makeStyles(light), [light]);

  return (
    <View style={styles.root} pointerEvents="none">
      <Image
        source={light ? mapBackdropLight : mapBackdropDark}
        style={{
          position: 'absolute',
          left: 0,
          top: 0,
          width: screenW,
          height: mapH,
        }}
        resizeMode="contain"
      />
      {/* Dark: soft overloop naar navy. Light-asset fades al naar wit. */}
      {!light ? (
        <LinearGradient
          colors={[
            'rgba(6,19,41,0)',
            'rgba(6,19,41,0.35)',
            'rgba(6,19,41,0.75)',
            theme.color.navy900,
          ]}
          locations={[0, 0.45, 0.78, 1]}
          style={{
            position: 'absolute',
            left: 0,
            right: 0,
            top: 0,
            height: mapH,
          }}
        />
      ) : null}
      <View
        style={{
          position: 'absolute',
          left: 0,
          right: 0,
          top: mapH - 1,
          bottom: 0,
          backgroundColor: light ? '#FFFFFF' : theme.color.navy900,
        }}
      />
    </View>
  );
}

function makeStyles(light: boolean) {
  return StyleSheet.create({
    root: {
      position: 'absolute',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      zIndex: 0,
      overflow: 'hidden',
      backgroundColor: light ? '#FFFFFF' : theme.color.navy900,
    },
  });
}
