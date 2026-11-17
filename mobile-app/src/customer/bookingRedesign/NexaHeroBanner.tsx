import { LinearGradient } from 'expo-linear-gradient';
import React from 'react';
import { ImageBackground, StyleSheet, Text, View } from 'react-native';
import { t } from '../../i18n';
import { nexaBookingTheme as theme } from './theme';

const planHero = require('../../../assets/nexa-booking/crops/planning_header_scene_reference.png');

/** Hero voor Plan je rit (scherm 02). */
export function NexaHeroBanner({ greetingName }: { greetingName?: string | null }) {
  void greetingName;
  return (
    <View style={styles.wrap}>
      <ImageBackground source={planHero} style={styles.hero} imageStyle={styles.heroImg}>
        <LinearGradient
          colors={['rgba(6,19,41,0.2)', 'rgba(6,19,41,0.88)']}
          style={StyleSheet.absoluteFill}
        />
        <Text style={styles.headline}>{t('Plan je rit')}</Text>
        <View style={styles.underline} />
        <Text style={styles.sub}>
          {t(
            'Geef je ophaallocatie en bestemming op, en bekijk direct je ritopties.'
          )}
        </Text>
      </ImageBackground>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    marginBottom: 14,
    marginHorizontal: -12,
    overflow: 'hidden',
  },
  hero: {
    minHeight: 168,
    justifyContent: 'flex-end',
    paddingHorizontal: 12,
    paddingBottom: 16,
    paddingTop: 36,
  },
  heroImg: {
    resizeMode: 'cover',
  },
  headline: {
    color: theme.color.text,
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
    color: theme.color.textMuted,
    fontSize: 14,
    lineHeight: 20,
  },
});
