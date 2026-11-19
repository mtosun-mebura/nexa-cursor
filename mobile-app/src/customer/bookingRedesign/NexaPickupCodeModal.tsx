import { Ionicons } from '@expo/vector-icons';
import React, { useMemo } from 'react';
import {
  ImageBackground,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { t } from '../../i18n';
import { useTheme } from '../../theme/ThemeContext';
import { AppBrandLogo } from '../../ui/AppBrandLogo';
import { AppModal } from '../../ui/AppModal';
import { exactModeAsset, exactOphaalcodePopupBg } from './exactAssets';
import { nexaBookingTheme as theme } from './theme';

type Props = {
  visible: boolean;
  pin: string;
  onClose: () => void;
};

/** Compacte ophaalcode-modal; achtergrond full-bleed over hele paneel. */
export function NexaPickupCodeModal({ visible, pin, onClose }: Props) {
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  const styles = useMemo(() => makeStyles(light), [light]);
  const bg = exactModeAsset(exactOphaalcodePopupBg, light);

  return (
    <AppModal
      visible={visible}
      onRequestClose={onClose}
      placement="center"
      scrollEnabled={false}
      panelStyle={styles.panel}
    >
      <ImageBackground
        source={bg}
        style={styles.root}
        resizeMode="cover"
        imageStyle={styles.bgImg}
      >
        <View style={styles.content}>
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
                <Ionicons
                  name="close"
                  size={20}
                  color={light ? theme.color.ink : '#FFFFFF'}
                />
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
        </View>
      </ImageBackground>
    </AppModal>
  );
}

function makeStyles(light: boolean) {
  const ink = light ? theme.color.ink : '#FFFFFF';
  const muted = light ? theme.color.inkMuted : 'rgba(255,255,255,0.88)';
  const fill = light ? '#E8F1FF' : '#011258';
  return StyleSheet.create({
    panel: {
      padding: 0,
      paddingTop: 0,
      paddingBottom: 0,
      paddingLeft: 0,
      paddingRight: 0,
      paddingHorizontal: 0,
      paddingVertical: 0,
      margin: 0,
      marginHorizontal: 0,
      overflow: 'hidden',
      backgroundColor: fill,
      borderRadius: 22,
      borderWidth: 1.5,
      borderColor: light ? 'rgba(15, 132, 255, 0.35)' : 'rgba(0, 163, 255, 0.75)',
      maxWidth: 400,
      width: '92%',
      alignSelf: 'center',
    },
    root: {
      width: '100%',
      overflow: 'hidden',
      borderRadius: 20.5,
      backgroundColor: fill,
    },
    /** Asset is al gecropt; lichte zoom eet alleen de afgeronde zwarte hoeken. */
    bgImg: {
      borderRadius: 20.5,
      transform: [{ scale: 1.06 }],
    },
    content: {
      paddingHorizontal: 16,
      paddingTop: 6,
      paddingBottom: 8,
    },
    chrome: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      minHeight: 32,
      marginBottom: 2,
    },
    chromeSide: {
      width: 34,
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
      gap: 5,
      paddingHorizontal: 10,
      paddingVertical: 4,
      borderRadius: 999,
      borderWidth: 1,
      borderColor: light ? 'rgba(15, 132, 255, 0.45)' : 'rgba(15, 132, 255, 0.75)',
      backgroundColor: light ? 'rgba(255,255,255,0.65)' : 'rgba(6, 19, 41, 0.55)',
      marginBottom: 6,
    },
    badgeText: {
      color: ink,
      fontSize: 11,
      fontWeight: '700',
    },
    headline: {
      color: ink,
      fontSize: 16,
      fontWeight: '800',
      textAlign: 'center',
      marginBottom: 8,
    },
    codeBox: {
      alignSelf: 'stretch',
      borderRadius: 14,
      borderWidth: 1.5,
      borderColor: light ? 'rgba(15, 132, 255, 0.55)' : 'rgba(0, 163, 255, 0.9)',
      backgroundColor: light ? 'rgba(255,255,255,0.78)' : 'rgba(8, 24, 48, 0.88)',
      paddingVertical: 11,
      paddingHorizontal: 12,
      shadowColor: '#00A3FF',
      shadowOpacity: light ? 0.18 : 0.45,
      shadowRadius: 10,
      shadowOffset: { width: 0, height: 0 },
      elevation: 4,
      marginBottom: 6,
    },
    code: {
      color: ink,
      fontSize: 32,
      fontWeight: '800',
      letterSpacing: 8,
      textAlign: 'center',
      fontVariant: ['tabular-nums'],
    },
    hint: {
      color: muted,
      fontSize: 12,
      textAlign: 'center',
      marginBottom: 8,
      lineHeight: 16,
    },
    infoBar: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
      borderRadius: 12,
      backgroundColor: light ? 'rgba(255,255,255,0.72)' : 'rgba(6, 19, 41, 0.72)',
      borderWidth: 1,
      borderColor: light ? 'rgba(15, 132, 255, 0.28)' : 'rgba(53, 182, 255, 0.35)',
      paddingHorizontal: 10,
      paddingVertical: 8,
      marginBottom: 8,
    },
    infoDivider: {
      width: 1,
      alignSelf: 'stretch',
      backgroundColor: light ? 'rgba(15,23,42,0.12)' : 'rgba(185, 203, 228, 0.35)',
    },
    infoTitle: {
      color: ink,
      fontSize: 12,
      fontWeight: '800',
    },
    infoBody: {
      color: muted,
      fontSize: 11,
      marginTop: 1,
      lineHeight: 14,
    },
    cta: {
      minHeight: 44,
      height: 44,
      borderRadius: 12,
      backgroundColor: theme.color.orangeHot,
      alignItems: 'center',
      justifyContent: 'center',
      alignSelf: 'stretch',
    },
    ctaText: {
      color: '#FFFFFF',
      fontSize: 15,
      fontWeight: '800',
    },
  });
}
