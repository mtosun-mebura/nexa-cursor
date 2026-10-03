import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Switch,
  Text,
  View,
} from 'react-native';
import {
  acceptOffer,
  declineOffer,
  DispatchOffer,
  fetchDriverInbox,
  fetchDriverMe,
  setDriverOnline,
} from '../api/driver';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { startBackgroundLocation, stopBackgroundLocation } from '../location/background';
import { Card, ErrorText, GhostButton, Screen, Subtitle, Title } from '../ui/components';
import { ColorPalette } from '../config';
import { useThemeColors } from '../theme/ThemeContext';

export function DriverHomeScreen() {
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const { driverToken, logout, capabilities, setActiveScreen } = useAuth();
  const [online, setOnline] = useState(false);
  const [name, setName] = useState('');
  const [company, setCompany] = useState('');
  const [offers, setOffers] = useState<DispatchOffer[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [busyId, setBusyId] = useState<number | null>(null);

  const modes = capabilities?.modes;
  const multi =
    (capabilities?.screens?.length || 0) > 1;

  const refresh = useCallback(async () => {
    if (!driverToken) return;
    setError(null);
    try {
      const me = await fetchDriverMe(driverToken);
      setOnline(!!me.user.is_online);
      setName(me.user.name);
      setCompany(me.user.company_name || '');
      const inbox = await fetchDriverInbox(driverToken);
      const list = inbox.offers || inbox.data || [];
      setOffers(Array.isArray(list) ? list : []);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Kon gegevens niet laden.');
    }
  }, [driverToken]);

  useEffect(() => {
    refresh();
    const t = setInterval(refresh, 5000);
    return () => clearInterval(t);
  }, [refresh]);

  async function toggleOnline(next: boolean) {
    if (!driverToken) return;
    setError(null);
    try {
      if (next) {
        await startBackgroundLocation(driverToken);
        await setDriverOnline(driverToken, true);
      } else {
        await setDriverOnline(driverToken, false);
        await stopBackgroundLocation();
      }
      setOnline(next);
      await refresh();
    } catch (e) {
      setOnline(false);
      setError(e instanceof Error ? e.message : 'Online zetten mislukt.');
      try {
        await stopBackgroundLocation();
      } catch {
        /* ignore */
      }
    }
  }

  async function onAccept(id: number) {
    if (!driverToken) return;
    setBusyId(id);
    try {
      await acceptOffer(driverToken, id);
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Accepteren mislukt.');
    } finally {
      setBusyId(null);
    }
  }

  async function onDecline(id: number) {
    if (!driverToken) return;
    setBusyId(id);
    try {
      await declineOffer(driverToken, id);
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Weigeren mislukt.');
    } finally {
      setBusyId(null);
    }
  }

  return (
    <Screen style={{ paddingHorizontal: 0 }}>
      <View style={styles.header}>
        <View style={{ flex: 1 }}>
          <Title>{name || 'Chauffeur'}</Title>
          <Subtitle>
            {company}
            {modes?.marketplace ? ' · Marketplace' : ''}
            {modes?.network ? ' · Network' : ''}
          </Subtitle>
        </View>
        <View style={styles.onlineBox}>
          <Text style={styles.onlineLabel}>{online ? 'Online' : 'Offline'}</Text>
          <Switch
            value={online}
            onValueChange={toggleOnline}
            trackColor={{ false: colors.border, true: colors.success }}
          />
        </View>
      </View>

      <View style={{ paddingHorizontal: 20 }}>
        <ErrorText>{error}</ErrorText>
        {!online ? (
          <Card>
            <Text style={styles.hint}>
              Zet jezelf online. Locatie blijft actief op de achtergrond (belangrijk voor
              marketplace-matching).
            </Text>
          </Card>
        ) : null}
      </View>

      <FlatList
        data={offers}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 24 }}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={async () => {
            setRefreshing(true);
            await refresh();
            setRefreshing(false);
          }} tintColor={colors.text} />
        }
        ListEmptyComponent={
          <Card>
            <Text style={styles.hint}>
              {online ? 'Nog geen ritten in je inbox.' : 'Ga online om ritten te ontvangen.'}
            </Text>
          </Card>
        }
        renderItem={({ item }) => {
          const ride = item.ride;
          const marketplace = !!ride?.fee_breakdown?.is_marketplace;
          const network = !!ride?.is_network_ride || !!ride?.fee_breakdown?.is_network;
          return (
            <Card>
              <Text style={styles.offerBadge}>
                {[marketplace && 'Marketplace', network && 'Network', !marketplace && !network && 'Rit']
                  .filter(Boolean)
                  .join(' · ')}
              </Text>
              <Text style={styles.offerTitle}>{ride?.pickup_address || 'Ophaaladres onbekend'}</Text>
              <Text style={styles.hint}>→ {ride?.dropoff_address || 'Bestemming onbekend'}</Text>
              {ride?.customer_name ? (
                <Text style={styles.hint}>{ride.customer_name}</Text>
              ) : null}
              <View style={styles.row}>
                <Pressable
                  style={[styles.action, styles.accept]}
                  disabled={busyId === item.id}
                  onPress={() => onAccept(item.id)}
                >
                  <Text style={styles.actionText}>Accepteren</Text>
                </Pressable>
                <Pressable
                  style={[styles.action, styles.decline]}
                  disabled={busyId === item.id}
                  onPress={() => onDecline(item.id)}
                >
                  <Text style={styles.actionText}>Weigeren</Text>
                </Pressable>
              </View>
            </Card>
          );
        }}
      />

      <View style={{ paddingHorizontal: 20 }}>
        {multi ? (
          <GhostButton title="Ander scherm" onPress={() => setActiveScreen(null)} />
        ) : null}
        <GhostButton
          title="Uitloggen"
          onPress={async () => {
            await stopBackgroundLocation();
            await logout();
          }}
        />
      </View>
    </Screen>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    header: {
      paddingHorizontal: 20,
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: 12,
    },
    onlineBox: { alignItems: 'center', paddingTop: 8 },
    onlineLabel: { color: colors.muted, fontSize: 12, marginBottom: 4, fontWeight: '600' },
    hint: { color: colors.muted, fontSize: 14, lineHeight: 20 },
    offerBadge: {
      color: colors.primary,
      fontSize: 11,
      fontWeight: '700',
      textTransform: 'uppercase',
      marginBottom: 6,
    },
    offerTitle: { color: colors.text, fontSize: 16, fontWeight: '700', marginBottom: 4 },
    row: { flexDirection: 'row', gap: 8, marginTop: 12 },
    action: {
      flex: 1,
      borderRadius: 12,
      paddingVertical: 12,
      alignItems: 'center',
    },
    accept: { backgroundColor: colors.success },
    decline: { backgroundColor: colors.muted },
    actionText: { color: '#fff', fontWeight: '700' },
  });
}
