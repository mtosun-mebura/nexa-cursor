import React, { useMemo } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { useAuth } from '../auth/AuthContext';
import { Card, GhostButton, Screen, Subtitle, Title } from '../ui/components';
import { ColorPalette } from '../config';
import { useThemeColors } from '../theme/ThemeContext';

export function RoleSelectScreen() {
  const { capabilities, setActiveScreen, logout, session } = useAuth();
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const screens = capabilities?.screens || [];
  const modes = capabilities?.modes;

  return (
    <Screen>
      <Title>Kies je scherm</Title>
      <Subtitle>
        Ingelogd als {session?.user?.name || session?.user?.email}.
        {modes?.marketplace ? ' Marktplaats actief.' : ''}
        {modes?.network ? ' Netwerk actief.' : ''}
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

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    badge: {
      color: colors.primary,
      fontSize: 11,
      fontWeight: '700',
      textTransform: 'uppercase',
      marginBottom: 8,
    },
    title: { color: colors.text, fontSize: 17, fontWeight: '700', marginBottom: 6 },
    body: { color: colors.muted, fontSize: 14, lineHeight: 20 },
  });
}
