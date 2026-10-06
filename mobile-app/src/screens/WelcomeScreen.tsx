import React, { useMemo } from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { Card, Screen } from '../ui/components';
import { ColorPalette } from '../config';
import { useTheme } from '../theme/ThemeContext';

const logoLight = require('../../assets/nexa-taxi-logo.png');
const logoDark = require('../../assets/nexa-taxi-logo-dark.png');

export function WelcomeScreen({
  onLogin,
  onCustomer,
  onMarketplaceRegister,
}: {
  onLogin: () => void;
  onCustomer: () => void;
  onMarketplaceRegister: () => void;
}) {
  const { colors, colorScheme } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const logoSource = colorScheme === 'light' ? logoLight : logoDark;

  return (
    <Screen>
      <View style={styles.logoBar}>
        <Image
          source={logoSource}
          style={styles.logo}
          resizeMode="contain"
          accessibilityLabel="NEXA | taxi"
        />
      </View>

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
          <Text style={styles.badge}>Chauffeur · Marktplaats · Netwerk · Contract</Text>
          <Text style={styles.cardTitle}>Inloggen</Text>
          <Text style={styles.cardBody}>
            We bepalen automatisch welke schermen bij jouw rollen horen.
          </Text>
        </Card>
      </Pressable>

      <Pressable onPress={onMarketplaceRegister} accessibilityRole="button">
        <Card>
          <Text style={styles.badge}>Taxibedrijf</Text>
          <Text style={styles.cardTitle}>Marktplaats aanmelden</Text>
          <Text style={styles.cardBody}>
            Registreer je taxibedrijf en start met ritten via de Nexa marktplaats.
          </Text>
        </Card>
      </Pressable>
    </Screen>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    logoBar: {
      alignItems: 'center',
      justifyContent: 'center',
      paddingTop: 8,
      paddingBottom: 16,
    },
    logo: {
      width: 180,
      height: 46,
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
