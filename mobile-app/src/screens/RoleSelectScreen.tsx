import React, { useMemo } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useAuth } from '../auth/AuthContext';
import { AppPageHeader, APP_PAGE_TOP_INSET } from '../ui/AppPageHeader';
import { Card, GhostButton, Screen, Subtitle } from '../ui/components';
import { ColorPalette } from '../config';
import { useThemeColors } from '../theme/ThemeContext';

export function RoleSelectScreen() {
  const { capabilities, setActiveScreen, logout, session } = useAuth();
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const screens = capabilities?.screens || [];
  const modes = capabilities?.modes;

  return (
    <Screen style={{ paddingTop: APP_PAGE_TOP_INSET, paddingHorizontal: 0 }}>
      <AppPageHeader title="Kies je scherm" />
      <View style={styles.body}>
        <Subtitle>
          Ingelogd als {session?.user?.name || session?.user?.email}.
          {modes?.marketplace ? ' NEXA Taxi marktplaats actief.' : ''}
          {modes?.network ? ' Netwerk actief.' : ''}
        </Subtitle>

        {screens.map((screen) => (
          <Pressable key={screen.key} onPress={() => setActiveScreen(screen.key)}>
            <Card>
              <Text style={styles.badge}>{screen.badge}</Text>
              <Text style={styles.title}>{screen.title}</Text>
              <Text style={styles.bodyText}>{screen.description}</Text>
            </Card>
          </Pressable>
        ))}

        <GhostButton title="Uitloggen" onPress={() => logout()} />
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
      textTransform: 'uppercase',
      marginBottom: 8,
    },
    title: { color: colors.text, fontSize: 17, fontWeight: '700', marginBottom: 6 },
    bodyText: { color: colors.muted, fontSize: 14, lineHeight: 20 },
  });
}
