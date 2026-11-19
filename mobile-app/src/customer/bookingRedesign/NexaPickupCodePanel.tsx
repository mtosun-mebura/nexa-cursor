import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import React from 'react';
import {
  ImageBackground,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { t } from '../../i18n';
import { AppBrandLogo } from '../../ui/AppBrandLogo';
import { tabBarBottomPadding } from '../../ui/tabBarSafeArea';
import { exactModeAsset, exactOphaalcodePopupBg } from './exactAssets';
import { nexaBookingTheme as theme } from './theme';
import { useTheme } from '../../theme/ThemeContext';

type Props = {
  pin: string;
  onClose: () => void;
};

/**
 * Ophaalcode-paneel onderin de rit (boven footer) — zelfde look als referentie,
 * geen modal. Code alleen tonen na “Ophaalcode bekijken”.
 */
export function NexaPickupCodePanel({ pin, onClose }: Props) {
  const insets = useSafeAreaInsets();
  const { colorScheme } = useTheme();
  const bg = exactModeAsset(exactOphaalcodePopupBg, colorScheme === 'light');
  const bottom = Math.max(72, 56 + tabBarBottomPadding(insets.bottom));

  return (
    <View style={[styles.wrap, { bottom }]} pointerEvents="box-none">
      <View style={styles.panel}>
        <ImageBackground
          source={bg}
          style={styles.bg}
          imageStyle={styles.bgImg}
        >
          <LinearGradient
            colors={['rgba(1,11,24,0.12)', 'rgba(1,11,24,0.55)']}
            style={StyleSheet.absoluteFill}
            pointerEvents="none"
          />

          <View style={styles.chrome}>
            <View style={styles.chromeSide} />
            <AppBrandLogo bare />
            <View style={[styles.chromeSide, styles.chromeRight]}>
              <Pressable
                onPress={onClose}
                hitSlop={10}
                accessibilityRole="button"
                accessibilityLabel={t('Ophaalcode sluiten')}
                style={styles.closeBtn}
              >
                <Ionicons name="close" size={20} color="#FFFFFF" />
              </Pressable>
            </View>
          </View>

          <View style={styles.badge}>
            <Ionicons name="shield-checkmark" size={13} color={theme.color.blueBright} />
            <Text style={styles.badgeText}>{t('Veilige instapcode')}</Text>
          </View>

          <Text style={styles.headline}>{t('Geef deze code aan je chauffeur')}</Text>

          <View style={styles.codeBox}>
            <Text style={styles.code} accessibilityLabel={`Ophaalcode ${pin}`}>
              {pin.split('').join(' ')}
            </Text>
          </View>

          <Text style={styles.hint}>
            {t('De chauffeur vraagt bij het instappen om deze code.')}
          </Text>

          <View style={styles.infoBar}>
            <Ionicons name="information-circle" size={18} color={theme.color.blueBright} />
            <View style={styles.infoDivider} />
            <View style={{ flex: 1 }}>
              <Text style={styles.infoTitle}>{t('Voor jouw veiligheid')}</Text>
              <Text style={styles.infoBody}>
                {t('De rit start pas nadat je de code hebt gedeeld.')}
              </Text>
            </View>
          </View>

          <Pressable
            onPress={onClose}
            style={({ pressed }) => [styles.cta, pressed && { opacity: 0.9 }]}
            accessibilityRole="button"
            accessibilityLabel={t('Begrepen')}
          >
            <Text style={styles.ctaText}>{t('Begrepen')}</Text>
          </Pressable>
        </ImageBackground>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    position: 'absolute',
    left: 12,
    right: 12,
    zIndex: 40,
  },
  panel: {
    borderRadius: 24,
    borderWidth: 1.5,
    borderColor: 'rgba(0, 163, 255, 0.8)',
    overflow: 'hidden',
    backgroundColor: '#010B18',
    shadowColor: '#00A3FF',
    shadowOpacity: 0.4,
    shadowRadius: 16,
    shadowOffset: { width: 0, height: 0 },
    elevation: 8,
  },
  bg: {
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 16,
  },
  bgImg: {
    resizeMode: 'cover',
  },
  chrome: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    minHeight: 40,
    marginBottom: 8,
  },
  chromeSide: {
    width: 36,
  },
  chromeRight: {
    alignItems: 'flex-end',
  },
  closeBtn: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badge: {
    alignSelf: 'center',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: 'rgba(15, 132, 255, 0.75)',
    backgroundColor: 'rgba(6, 19, 41, 0.55)',
    marginBottom: 10,
  },
  badgeText: {
    color: '#FFFFFF',
    fontSize: 11,
    fontWeight: '700',
  },
  headline: {
    color: '#FFFFFF',
    fontSize: 17,
    fontWeight: '800',
    textAlign: 'center',
    marginBottom: 12,
  },
  codeBox: {
    alignSelf: 'center',
    minWidth: '70%',
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: 'rgba(0, 163, 255, 0.95)',
    backgroundColor: 'rgba(8, 24, 48, 0.92)',
    paddingVertical: 14,
    paddingHorizontal: 20,
    shadowColor: '#00A3FF',
    shadowOpacity: 0.55,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 0 },
    elevation: 6,
    marginBottom: 10,
  },
  code: {
    color: '#FFFFFF',
    fontSize: 34,
    fontWeight: '800',
    letterSpacing: 6,
    textAlign: 'center',
    fontVariant: ['tabular-nums'],
  },
  hint: {
    color: 'rgba(255,255,255,0.9)',
    fontSize: 12,
    textAlign: 'center',
    marginBottom: 12,
    lineHeight: 16,
  },
  infoBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderRadius: 12,
    backgroundColor: 'rgba(6, 19, 41, 0.72)',
    borderWidth: 1,
    borderColor: 'rgba(53, 182, 255, 0.35)',
    paddingHorizontal: 10,
    paddingVertical: 10,
    marginBottom: 12,
  },
  infoDivider: {
    width: 1,
    alignSelf: 'stretch',
    backgroundColor: 'rgba(185, 203, 228, 0.35)',
  },
  infoTitle: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '800',
  },
  infoBody: {
    color: 'rgba(185, 203, 228, 0.95)',
    fontSize: 11,
    marginTop: 1,
    lineHeight: 15,
  },
  cta: {
    minHeight: 48,
    borderRadius: 14,
    backgroundColor: theme.color.orangeHot,
    alignItems: 'center',
    justifyContent: 'center',
  },
  ctaText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '800',
  },
});
