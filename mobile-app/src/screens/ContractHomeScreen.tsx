import React from 'react';
import { Text } from 'react-native';
import { useAuth } from '../auth/AuthContext';
import { Card, GhostButton, Screen, Subtitle, Title } from '../ui/components';
import { COLORS } from '../config';

export function ContractHomeScreen() {
  const { session, logout, setActiveScreen, capabilities } = useAuth();
  const multi = (capabilities?.screens?.length || 0) > 1;

  return (
    <Screen>
      <Title>Contract</Title>
      <Subtitle>
        Welkom {session?.user?.name}. Planning en afwezigheid openen hier native (zonder browser).
      </Subtitle>
      <Card>
        <Text style={{ color: COLORS.muted, lineHeight: 20 }}>
          Contract-schermen (vandaag / week / afwezigheid) worden hier verder uitgebouwd. Je bent
          al correct doorgestuurd op basis van je contract-rol.
        </Text>
      </Card>
      {multi ? <GhostButton title="Ander scherm" onPress={() => setActiveScreen(null)} /> : null}
      <GhostButton title="Uitloggen" onPress={() => logout()} />
    </Screen>
  );
}
