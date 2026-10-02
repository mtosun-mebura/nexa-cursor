import React from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { useAuth } from '../auth/AuthContext';
import { Card, GhostButton, Screen, Subtitle, Title } from '../ui/components';
import { COLORS } from '../config';

export function RoleSelectScreen() {
  const { capabilities, setActiveScreen, logout, session } = useAuth();
  const screens = capabilities?.screens || [];
  const modes = capabilities?.modes;

  return (
    <Screen>
      <Title>Kies je scherm</Title>
      <Subtitle>
        Ingelogd als {session?.user?.name || session?.user?.email}.
        {modes?.marketplace ? ' Marketplace actief.' : ''}
        {modes?.network ? ' Network actief.' : ''}
      </Subtitle>

      {screens.map((screen) => (
        <Pressable key={screen.key} onPress={() => setActiveScreen(screen.key)}>
          <Card>
            <Text style={styles.badge}>{screen.badge}</Text>
            <Text style={styles.title}>{screen.title}</Text>
            <Text style={styles.body}>{screen.description}</Text>
          </Card>
        </Pressable>
      ))}

      <GhostButton title="Uitloggen" onPress={() => logout()} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  badge: {
    color: '#93C5FD',
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
    marginBottom: 8,
  },
  title: { color: COLORS.text, fontSize: 17, fontWeight: '700', marginBottom: 6 },
  body: { color: COLORS.muted, fontSize: 14, lineHeight: 20 },
});
