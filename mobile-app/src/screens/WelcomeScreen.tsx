import React from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { Card, GhostButton, Screen, Subtitle, Title } from '../ui/components';
import { COLORS } from '../config';

export function WelcomeScreen({
  onLogin,
  onCustomer,
}: {
  onLogin: () => void;
  onCustomer: () => void;
}) {
  return (
    <Screen>
      <Title>Nexa Taxi</Title>
      <Subtitle>
        Echte native app — geen website in een venster. Login, ritten en locatie draaien op je
        telefoon.
      </Subtitle>

      <Pressable onPress={onLogin} style={styles.primaryCard}>
        <Text style={styles.badge}>Chauffeur · Marketplace · Network · Contract</Text>
        <Text style={styles.cardTitle}>Inloggen</Text>
        <Text style={styles.cardBody}>
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

const styles = StyleSheet.create({
  primaryCard: {
    backgroundColor: 'rgba(37,99,235,0.22)',
    borderColor: 'rgba(59,130,246,0.45)',
    borderWidth: 1,
    borderRadius: 18,
    padding: 16,
    marginBottom: 12,
  },
  badge: {
    color: '#93C5FD',
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.4,
    textTransform: 'uppercase',
    marginBottom: 8,
  },
  cardTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '700',
    marginBottom: 6,
  },
  cardBody: {
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 20,
  },
});
