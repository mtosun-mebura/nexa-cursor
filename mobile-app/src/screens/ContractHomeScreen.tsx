import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { fetchContractMe } from '../api/contract';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { ColorPalette } from '../config';
import { Card, ErrorText, GhostButton, Screen, Subtitle, Title } from '../ui/components';
import { ContractTabBar, ContractTabKey } from '../ui/ContractTabBar';
import { useThemeColors } from '../theme/ThemeContext';

const ROLE_LABEL: Record<string, string> = {
  contractant: 'Contractant',
  contractouder: 'Contractouder',
};

export function ContractHomeScreen() {
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const { session, contractToken, logout, setActiveScreen, capabilities } = useAuth();
  const [tab, setTab] = useState<ContractTabKey>('today');
  const [name, setName] = useState(session?.user?.name || '');
  const [customerName, setCustomerName] = useState('');
  const [portalRole, setPortalRole] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const multi = (capabilities?.screens?.length || 0) > 1;

  const refresh = useCallback(async () => {
    if (!contractToken) return;
    setError(null);
    try {
      const me = await fetchContractMe(contractToken);
      setName(me.user?.name || session?.user?.name || '');
      setCustomerName(me.user?.company_name || '');
      setPortalRole(me.user?.portal_role || null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Kon contractgegevens niet laden.');
    }
  }, [contractToken, session?.user?.name]);

  useEffect(() => {
    refresh();
  }, [refresh]);

  function renderPanel(title: string, text: string) {
    return (
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <ErrorText>{error}</ErrorText>
        <Text style={styles.panelTitle}>{title}</Text>
        <Card>
          <Text style={styles.hint}>{text}</Text>
        </Card>
      </ScrollView>
    );
  }

  let body: React.ReactNode;
  if (tab === 'today') {
    body = renderPanel(
      'Vandaag',
      'Ritten van vandaag verschijnen hier, net als in het contractportaal op /taxi/contract.'
    );
  } else if (tab === 'week') {
    body = renderPanel(
      'Planning',
      'Weekplanning voor contractritten verschijnt hier, gelijk aan de web-app.'
    );
  } else if (tab === 'navigation') {
    body = renderPanel(
      'Navigatie',
      'Navigatie naar ophaal- en bestemming wordt hier geopend.'
    );
  } else if (tab === 'absences') {
    body = renderPanel(
      'Afmeldingen',
      'Afwezigheid en afmeldingen beheer je hier, zoals in het contractportaal.'
    );
  } else {
    body = (
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <ErrorText>{error}</ErrorText>
        <Text style={styles.panelTitle}>Profiel</Text>
        <Card>
          <Text style={styles.name}>{name || 'Contract'}</Text>
          {customerName ? <Text style={styles.hint}>{customerName}</Text> : null}
          {portalRole ? (
            <Text style={styles.hint}>
              Rol: {ROLE_LABEL[portalRole] || portalRole}
            </Text>
          ) : (
            <Text style={styles.hint}>
              Toegang via rol contractant of contractouder.
            </Text>
          )}
        </Card>
        {multi ? <GhostButton title="Ander scherm" onPress={() => setActiveScreen(null)} /> : null}
        <GhostButton title="Uitloggen" onPress={() => logout()} />
      </ScrollView>
    );
  }

  return (
    <Screen style={{ paddingHorizontal: 0, paddingBottom: 0 }}>
      <View style={styles.header}>
        <Title>{customerName || 'Contract'}</Title>
        <Subtitle>
          {name}
          {portalRole ? ` · ${ROLE_LABEL[portalRole] || portalRole}` : ''}
        </Subtitle>
      </View>
      <View style={styles.body}>{body}</View>
      <ContractTabBar active={tab} onChange={setTab} />
    </Screen>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    header: {
      paddingHorizontal: 20,
      paddingBottom: 4,
    },
    body: {
      flex: 1,
      minHeight: 0,
    },
    scroll: {
      paddingHorizontal: 20,
      paddingBottom: 24,
      paddingTop: 4,
    },
    panelTitle: {
      color: colors.text,
      fontSize: 20,
      fontWeight: '700',
      marginBottom: 12,
    },
    name: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '700',
      marginBottom: 4,
    },
    hint: {
      color: colors.muted,
      fontSize: 14,
      lineHeight: 20,
    },
  });
}
