import React, { useMemo, useState } from 'react';
import {
  Image,
  Linking,
  Pressable,
  ScrollView,
  Platform,
  StatusBar,
  StyleSheet,
  Text,
  useWindowDimensions,
  View,
} from 'react-native';
import { Ionicons, MaterialCommunityIcons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { AppModal } from '../ui/AppModal';
import { GhostButton } from '../ui/components';
import { useI18n, t } from '../i18n';
import { useThemeColors } from '../theme/ThemeContext';
import { AppearanceControls } from '../ui/AppearanceControls';
import { fitLineFontProps } from '../ui/androidText';

const HELP_EMAIL = 'info@nexasuite.nl';
const HELP_SUBJECT = 'Help bij de NEXA Taxi app';
const HELP_URL = `mailto:${HELP_EMAIL}?subject=${encodeURIComponent(HELP_SUBJECT)}`;

const logo = require('../../assets/nexa-taxi-logo-dark.png');
const mapHeader = require('../../assets/map-header-background.png');
const bookingArt = require('../../assets/taxi-booking-background.png');

export function WelcomeScreen({
  onLogin,
  onCustomer,
  onMarketplaceRegister,
}: {
  onLogin: () => void;
  onCustomer: () => void;
  onMarketplaceRegister: () => void;
}) {
  const colors = useThemeColors();
  useI18n();
  const styles = useMemo(() => makeStyles(), []);
  const { width: windowWidth } = useWindowDimensions();
  const heroWidth = Math.min(windowWidth, 640);
  const mapHeight = heroWidth * (1024 / 1536) * 1.2;
  const routeBottom = mapHeight * 0.4;
  const logoMarginBottom = Math.max(24, Math.round(routeBottom + 12 - 70));
  const [helpOpen, setHelpOpen] = useState(false);

  async function openHelp() {
    try {
      await Linking.openURL(HELP_URL);
    } catch {
      setHelpOpen(true);
    }
  }

  return (
    <View style={styles.screen}>
      {Platform.OS === 'ios' ? <StatusBar barStyle="light-content" /> : null}
      <View style={styles.prefs} pointerEvents="box-none">
        <AppearanceControls tone="onDark" />
      </View>
      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.hero}>
          <Image
            source={mapHeader}
            style={[styles.mapHeader, { width: heroWidth, height: mapHeight, top: 0 }]}
            resizeMode="cover"
            accessible={false}
          />
          <LinearGradient
            colors={['rgba(7,19,35,0)', '#071323']}
            style={styles.mapFade}
            pointerEvents="none"
          />
          <View style={styles.heroCopy}>
            <Image
              source={logo}
              style={[styles.logo, { marginBottom: logoMarginBottom }]}
              resizeMode="contain"
              accessibilityLabel="NEXA Taxi"
            />
            <Text
              style={styles.title}
              accessibilityRole="header"
              numberOfLines={1}
              adjustsFontSizeToFit
              {...fitLineFontProps}
              minimumFontScale={0.7}
            >{t("Welkom bij NEXA Taxi")}</Text>
            <Text style={styles.lead}>{t("Hoe wil je verder?")}</Text>
          </View>
        </View>
        <View style={styles.column}>

          <View style={styles.booking}>
            <Image source={bookingArt} style={styles.bookingArt} resizeMode="cover" accessible={false} />
            <LinearGradient
              colors={['rgba(0,40,115,0.96)', 'rgba(0,42,115,0.82)', 'rgba(0,42,115,0.08)']}
              locations={[0, 0.46, 0.78]}
              start={{ x: 0, y: 0.5 }}
              end={{ x: 1, y: 0.5 }}
              style={StyleSheet.absoluteFill}
              pointerEvents="none"
            />
            <View style={styles.bookingCopy}>
              <MaterialCommunityIcons name="taxi" size={40} color="#ffffff" />
              <Text style={styles.bookingTitle}>{t("Taxi boeken")}</Text>
              <Text
                style={styles.bookingBody}
                numberOfLines={1}
                adjustsFontSizeToFit
              {...fitLineFontProps}
                minimumFontScale={0.75}
              >{t("Plan je rit en volg je taxi live.")}</Text>
              <Pressable
                onPress={onCustomer}
                accessibilityRole="button"
                accessibilityLabel={t("Taxi boeken")}
                style={({ pressed }) => [styles.bookAction, pressed && styles.pressed]}
              >
                <Text style={styles.bookActionText}>{t("Taxi boeken")}</Text>
                <Ionicons name="chevron-forward" size={22} color="#071323" />
              </Pressable>
            </View>
          </View>

          <Pressable
            onPress={onLogin}
            accessibilityRole="button"
            accessibilityLabel={t("Inloggen")}
            style={({ pressed }) => [styles.row, pressed && styles.pressed]}
          >
            <View style={styles.tile}>
              <MaterialCommunityIcons name="steering" size={40} color="#8eb6ff" />
            </View>
            <View style={styles.rowCopy}>
              <Text style={styles.rowTitle}>{t("Inloggen")}</Text>
              <Text style={styles.rowBody}>{t("Voor chauffeurs en zakelijke gebruikers.")}</Text>
            </View>
            <Ionicons name="chevron-forward" size={22} color="#8eb6ff" />
          </Pressable>

          <Pressable
            onPress={onMarketplaceRegister}
            accessibilityRole="button"
            accessibilityLabel={t("Taxibedrijf registreren")}
            style={({ pressed }) => [styles.row, pressed && styles.pressed]}
          >
            <View style={styles.tile}>
              <MaterialCommunityIcons name="office-building" size={40} color="#8eb6ff" />
            </View>
            <View style={styles.rowCopy}>
              <Text style={styles.rowTitle}>{t("Taxibedrijf registreren")}</Text>
              <Text
                style={styles.rowBody}
                numberOfLines={1}
                adjustsFontSizeToFit
              {...fitLineFontProps}
                minimumFontScale={0.75}
              >{t("Sluit je aan bij het NEXA-netwerk.")}</Text>
            </View>
            <Ionicons name="chevron-forward" size={22} color="#8eb6ff" />
          </Pressable>

          <View style={styles.help}>
            <Pressable
              onPress={() => void openHelp()}
              accessibilityRole="link"
              accessibilityLabel={t("Hulp nodig? Stuur een e-mail naar info@nexasuite.nl")}
              style={({ pressed }) => [styles.helpLink, pressed && styles.pressed]}
            >
              <Ionicons name="help-circle-outline" size={28} color="#80b8ff" />
              <Text style={styles.helpText}>{t("Hulp nodig?")}</Text>
            </Pressable>
          </View>
        </View>
      </ScrollView>

      <AppModal visible={helpOpen} onRequestClose={() => setHelpOpen(false)}>
        <Text style={[styles.helpModalTitle, { color: colors.text }]}>{t("Hulp nodig?")}</Text>
        <Text style={[styles.helpModalBody, { color: colors.muted }]}>
          Er is geen mailapp beschikbaar. Stuur zelf een bericht naar {HELP_EMAIL} met als onderwerp
          “{HELP_SUBJECT}”.
        </Text>
        <Text style={[styles.helpModalAddress, { color: colors.text }]} selectable>
          {HELP_EMAIL}
        </Text>
        <GhostButton title={t("Sluiten")} onPress={() => setHelpOpen(false)} />
      </AppModal>
    </View>
  );
}

function makeStyles() {
  return StyleSheet.create({
    screen: {
      flex: 1,
      backgroundColor: '#071323',
    },
    prefs: {
      position: 'absolute',
      top: 8,
      right: 12,
      zIndex: 5,
    },
    scroll: {
      flex: 1,
    },
    content: {
      paddingBottom: 28,
    },
    hero: {
      width: '100%',
      maxWidth: 640,
      alignSelf: 'center',
      overflow: 'hidden',
    },
    mapHeader: {
      position: 'absolute',
      left: 0,
    },
    mapFade: {
      position: 'absolute',
      left: 0,
      right: 0,
      bottom: 0,
      height: 40,
    },
    heroCopy: {
      paddingTop: 28,
      paddingHorizontal: 20,
      paddingBottom: 28,
    },
    column: {
      width: '100%',
      maxWidth: 520,
      alignSelf: 'center',
      marginTop: 0,
      paddingHorizontal: 20,
    },
    logo: {
      width: 168,
      height: 42,
      alignSelf: 'center',
      marginBottom: 24,
    },
    title: {
      color: '#ffffff',
      fontSize: 28,
      lineHeight: 34,
      fontWeight: '700',
      marginBottom: 6,
    },
    lead: {
      color: '#bfcee2',
      fontSize: 18,
      lineHeight: 24,
    },
    booking: {
      overflow: 'hidden',
      backgroundColor: '#064ab7',
      borderWidth: 1,
      borderColor: '#388bff',
      borderRadius: 24,
      marginBottom: 0,
    },
    bookingArt: {
      position: 'absolute',
      top: 0,
      right: 0,
      bottom: 0,
      width: '100%',
      height: '100%',
    },
    bookingCopy: {
      paddingHorizontal: 20,
      paddingTop: 16,
      paddingBottom: 16,
      maxWidth: '72%',
    },
    bookingTitle: {
      color: '#ffffff',
      fontSize: 24,
      lineHeight: 28,
      fontWeight: '700',
      marginTop: 8,
      marginBottom: 4,
    },
    bookingBody: {
      color: '#ffffff',
      fontSize: 14,
      lineHeight: 18,
      marginBottom: 14,
    },
    bookAction: {
      alignSelf: 'flex-start',
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: 20,
      minHeight: 52,
      paddingHorizontal: 20,
      paddingVertical: 14,
      borderRadius: 14,
      backgroundColor: '#ffffff',
    },
    bookActionText: {
      color: '#071323',
      fontSize: 16,
      fontWeight: '700',
    },
    row: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 14,
      minHeight: 116,
      marginTop: 24,
      padding: 20,
      borderRadius: 22,
      borderWidth: 1,
      borderColor: '#2a3c56',
      backgroundColor: '#101d30',
    },
    tile: {
      width: 58,
      height: 58,
      borderRadius: 29,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: '#162c4c',
    },
    rowCopy: {
      flex: 1,
      minWidth: 0,
    },
    rowTitle: {
      color: '#ffffff',
      fontSize: 20,
      lineHeight: 25,
      fontWeight: '700',
      marginBottom: 7,
    },
    rowBody: {
      color: '#bfcee2',
      fontSize: 15,
      lineHeight: 20,
    },
    help: {
      marginTop: 28,
      alignItems: 'center',
    },
    helpLink: {
      minHeight: 48,
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
    },
    helpText: {
      color: '#80b8ff',
      fontSize: 17,
      fontWeight: '600',
    },
    helpModalTitle: {
      fontSize: 20,
      fontWeight: '700',
      marginBottom: 8,
    },
    helpModalBody: {
      fontSize: 15,
      lineHeight: 22,
      marginBottom: 12,
    },
    helpModalAddress: {
      fontSize: 16,
      fontWeight: '700',
      marginBottom: 16,
    },
    pressed: {
      opacity: 0.86,
    },
  });
}
