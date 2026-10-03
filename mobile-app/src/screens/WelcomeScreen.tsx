import React, { useMemo } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { Card, GhostButton, Screen, Subtitle, Title } from '../ui/components';
import { ColorPalette } from '../config';
import { useTheme } from '../theme/ThemeContext';

export function WelcomeScreen({
  onLogin,
  onCustomer,
}: {
  onLogin: () => void;
  onCustomer: () => void;
}) {
  const { colors, colorScheme } = useTheme();
  const styles = useMemo(() => makeStyles(colors, colorScheme), [colors, colorScheme]);

  return (
    <Screen>
      <Title>Nexa Taxi</Title>
      <Subtitle>
        Echte native app — geen website in een venster. Login, ritten en locatie draaien op je
        telefoon.
      </Subtitle>

      <Pressable onPress={onLogin} style={styles.primaryCard}>
        <Text style={styles.primaryBadge}>Chauffeur · Marketplace · Network · Contract</Text>
        <Text style={styles.primaryTitle}>Inloggen</Text>
        <Text style={styles.primaryBody}>
          We bepalen automatisch welke schermen bij jouw rollen horen.
        </Text>
      </Pressable>

      <Pressable onPress={onCustomer}>
        <Card>
          <Text style={styles.badge}>Klant</Text>
          <Text style={styles.cardTitle}>Taxi boeken</Text>
          <Text style={styles.cardBody}>
            Adres, prijs, boeken en live volgen — alles in de app, zonder browser.
          </Text>
        </Card>
      </Pressable>

      <GhostButton title="Ik ben taxibedrijf (marketplace aanmelden)" onPress={onLogin} />
    </Screen>
  );
}

function makeStyles(colors: ColorPalette, colorScheme: 'light' | 'dark') {
  const isLight = colorScheme === 'light';
  return StyleSheet.create({
    primaryCard: {
      backgroundColor: isLight ? colors.primary : 'rgba(37,99,235,0.28)',
      borderColor: isLight ? colors.primaryPressed : 'rgba(59,130,246,0.55)',
      borderWidth: 1,
      borderRadius: 18,
      padding: 16,
      marginBottom: 12,
    },
    primaryBadge: {
      color: isLight ? 'rgba(255,255,255,0.88)' : '#93C5FD',
      fontSize: 11,
      fontWeight: '700',
      letterSpacing: 0.4,
      textTransform: 'uppercase',
      marginBottom: 8,
    },
    primaryTitle: {
      color: '#FFFFFF',
      fontSize: 18,
      fontWeight: '700',
      marginBottom: 6,
    },
    primaryBody: {
      color: isLight ? 'rgba(255,255,255,0.92)' : '#CBD5E1',
      fontSize: 14,
      lineHeight: 20,
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
