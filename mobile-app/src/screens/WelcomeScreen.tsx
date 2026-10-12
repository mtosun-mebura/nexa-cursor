import React, { useMemo } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Card, Screen } from '../ui/components';
import { AppPageHeader, APP_PAGE_TOP_INSET } from '../ui/AppPageHeader';
import { ColorPalette } from '../config';
import { useThemeColors } from '../theme/ThemeContext';

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
  const styles = useMemo(() => makeStyles(colors), [colors]);

  return (
    <Screen style={{ paddingTop: APP_PAGE_TOP_INSET, paddingHorizontal: 0 }}>
      <AppPageHeader title="Maak je keuze" titleMarginBottom={10} />

      <View style={styles.body}>
        <Pressable onPress={onCustomer} accessibilityRole="button">
          <Card>
            <Text style={styles.badge}>Klant</Text>
            <Text style={styles.cardTitle}>Taxi boeken</Text>
            <Text style={styles.cardBody}>
              Adres, prijs, boeken en live volgen, alles in de app, zonder browser.
            </Text>
          </Card>
        </Pressable>

        <Pressable onPress={onLogin} accessibilityRole="button">
          <Card>
            <Text style={styles.badge}>Chauffeur · NEXA-marktplaats · Netwerk · Contract</Text>
            <Text style={styles.cardTitle}>Inloggen</Text>
            <Text style={styles.cardBody}>
              We bepalen automatisch welke schermen bij jouw rollen horen.
            </Text>
          </Card>
        </Pressable>

        <Pressable onPress={onMarketplaceRegister} accessibilityRole="button">
          <Card>
            <Text style={styles.badge}>Taxibedrijf</Text>
            <Text style={styles.cardTitle}>Aanmelden op NEXA Taxi marktplaats</Text>
            <Text style={styles.cardBody}>
              Registreer je taxibedrijf op de NEXA Taxi marktplaats en ontvang ritten uit het
              NEXA-netwerk. Dit is geen aanmelding bij marktplaats.nl.
            </Text>
          </Card>
        </Pressable>
      </View>
    </Screen>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    body: {
      paddingHorizontal: 20,
    },
    badge: {
      color: colors.primary,
      fontSize: 11,
      fontWeight: '700',
      letterSpacing: 0.4,
      textTransform: 'uppercase',
      marginBottom: 8,
    },
    cardTitle: {
      color: colors.text,
      fontSize: 18,
      fontWeight: '700',
      marginBottom: 6,
    },
    cardBody: {
      color: colors.muted,
      fontSize: 14,
      lineHeight: 20,
    },
  });
}
