import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  Linking,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import {
  bookGuest,
  cancelLive,
  fetchLive,
  fetchQuote,
  LiveRide,
  payLive,
  QuoteOffer,
  QuoteResponse,
  waitLive,
} from '../api/customer';
import { ApiError } from '../api/client';
import {
  defaultPickupAt,
  estimateRouteMetrics,
  geocodeAddress,
  GeoPoint,
  resolveCurrentPickup,
} from '../geo/route';
import {
  Card,
  ErrorText,
  Field,
  GhostButton,
  PrimaryButton,
  Screen,
  Subtitle,
  Title,
} from '../ui/components';
import { COLORS } from '../config';

const TRACK_KEY = 'nexa_taxi_customer_track';

type Step = 'book' | 'live';

export function CustomerHomeScreen({ onBack }: { onBack: () => void }) {
  const [step, setStep] = useState<Step>('book');
  const [trackToken, setTrackToken] = useState<string | null>(null);
  const [live, setLive] = useState<LiveRide | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const [pickup, setPickup] = useState<GeoPoint | null>(null);
  const [pickupLoading, setPickupLoading] = useState(false);
  const [dropoffQuery, setDropoffQuery] = useState('');
  const [dropoff, setDropoff] = useState<GeoPoint | null>(null);
  const [dropoffBusy, setDropoffBusy] = useState(false);

  const [passengers, setPassengers] = useState('1');
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [remarks, setRemarks] = useState('');
  const [payOnline, setPayOnline] = useState(false);

  const [quote, setQuote] = useState<QuoteResponse | null>(null);
  const [quoteBusy, setQuoteBusy] = useState(false);
  const quoteTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const offer: QuoteOffer | null = quote?.offers?.[0] || null;

  const persistTrack = useCallback(async (token: string | null) => {
    if (!token) {
      await AsyncStorage.removeItem(TRACK_KEY);
      return;
    }
    await AsyncStorage.setItem(TRACK_KEY, token);
  }, []);

  const loadPickup = useCallback(async () => {
    setPickupLoading(true);
    setError(null);
    try {
      const point = await resolveCurrentPickup();
      setPickup(point);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Locatie ophalen mislukt.');
    } finally {
      setPickupLoading(false);
    }
  }, []);

  useEffect(() => {
    loadPickup();
    (async () => {
      const saved = await AsyncStorage.getItem(TRACK_KEY);
      if (!saved) return;
      try {
        const data = await fetchLive(saved);
        if (data.ride && !['cancelled', 'completed'].includes(data.ride.phase)) {
          setTrackToken(saved);
          setLive(data.ride);
          setStep('live');
        } else {
          await AsyncStorage.removeItem(TRACK_KEY);
        }
      } catch {
        await AsyncStorage.removeItem(TRACK_KEY);
      }
    })();
  }, [loadPickup]);

  const refreshQuote = useCallback(async () => {
    if (!pickup || !dropoff) {
      setQuote(null);
      return;
    }
    setQuoteBusy(true);
    setError(null);
    try {
      const metrics = estimateRouteMetrics(pickup, dropoff);
      const data = await fetchQuote({
        ...metrics,
        passengers: Math.max(1, parseInt(passengers, 10) || 1),
        pickup_lat: pickup.lat,
        pickup_lng: pickup.lng,
        pickup_at: defaultPickupAt(),
      });
      setQuote(data);
      if (data.payment?.booking && data.payment?.mollie_configured) {
        /* keep user choice */
      } else {
        setPayOnline(false);
      }
    } catch (e) {
      setQuote(null);
      setError(e instanceof ApiError ? e.message : 'Kon geen prijs ophalen.');
    } finally {
      setQuoteBusy(false);
    }
  }, [pickup, dropoff, passengers]);

  useEffect(() => {
    if (!pickup || !dropoff) {
      setQuote(null);
      return;
    }
    if (quoteTimer.current) clearTimeout(quoteTimer.current);
    quoteTimer.current = setTimeout(() => {
      refreshQuote();
    }, 400);
    return () => {
      if (quoteTimer.current) clearTimeout(quoteTimer.current);
    };
  }, [pickup, dropoff, passengers, refreshQuote]);

  useEffect(() => {
    if (step !== 'live' || !trackToken) return;
    let cancelled = false;
    let timer: ReturnType<typeof setTimeout> | null = null;

    const tick = async () => {
      try {
        const data = await fetchLive(trackToken);
        if (cancelled) return;
        setLive(data.ride);
        if (['cancelled', 'completed'].includes(data.ride.phase)) {
          await persistTrack(null);
        }
        const ms = data.ride.poll_interval_ms || 3000;
        timer = setTimeout(tick, ms);
      } catch {
        if (!cancelled) timer = setTimeout(tick, 2500);
      }
    };
    tick();
    return () => {
      cancelled = true;
      if (timer) clearTimeout(timer);
    };
  }, [step, trackToken, persistTrack]);

  async function resolveDropoff() {
    setDropoffBusy(true);
    setError(null);
    try {
      const point = await geocodeAddress(dropoffQuery);
      setDropoff(point);
      setDropoffQuery(point.address);
    } catch (e) {
      setDropoff(null);
      setError(e instanceof Error ? e.message : 'Bestemming niet gevonden.');
    } finally {
      setDropoffBusy(false);
    }
  }

  async function onBook() {
    if (!pickup) {
      setError('Bepaal eerst je ophaallocatie.');
      return;
    }
    if (!dropoff) {
      setError('Zoek eerst een bestemming.');
      return;
    }
    if (firstName.trim().length < 2 || lastName.trim().length < 2) {
      setError('Vul voor- en achternaam in.');
      return;
    }
    if (phone.trim().length < 8) {
      setError('Vul een geldig telefoonnummer in.');
      return;
    }
    if (payOnline && (!email.trim() || !email.includes('@'))) {
      setError('Vul een e-mailadres in voor online betaling.');
      return;
    }

    setBusy(true);
    setError(null);
    try {
      const metrics = estimateRouteMetrics(pickup, dropoff);
      const data = await bookGuest({
        ...metrics,
        passengers: Math.max(1, parseInt(passengers, 10) || 1),
        pickup_address: pickup.address,
        dropoff_address: dropoff.address,
        pickup_lat: pickup.lat,
        pickup_lng: pickup.lng,
        dropoff_lat: dropoff.lat,
        dropoff_lng: dropoff.lng,
        pickup_at: defaultPickupAt(),
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        phone: phone.trim(),
        email: email.trim() || null,
        remarks: remarks.trim() || null,
        selected_offer_id: offer?.id || null,
        payment_method: payOnline ? 'booking' : null,
        return_url: 'nexataxi://customer',
      });

      setTrackToken(data.track_token);
      await persistTrack(data.track_token);
      if (data.live) setLive(data.live);

      if (data.payment_required && data.checkout_url) {
        await Linking.openURL(data.checkout_url);
      }
      setStep('live');
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Boeken mislukt.');
    } finally {
      setBusy(false);
    }
  }

  async function onCancel() {
    if (!trackToken) return;
    setBusy(true);
    setError(null);
    try {
      const data = await cancelLive(trackToken);
      if (data.ride) setLive(data.ride);
      await persistTrack(null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Annuleren mislukt.');
    } finally {
      setBusy(false);
    }
  }

  async function onWait() {
    if (!trackToken) return;
    setBusy(true);
    setError(null);
    try {
      const data = await waitLive(trackToken);
      if (data.ride) setLive(data.ride);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Doorgaan mislukt.');
    } finally {
      setBusy(false);
    }
  }

  async function onPay() {
    if (!trackToken) return;
    setBusy(true);
    setError(null);
    try {
      const url = live?.checkout_url;
      if (url) {
        await Linking.openURL(url);
        return;
      }
      const data = await payLive(trackToken, 'nexataxi://customer');
      if (data.ride) setLive(data.ride);
      if (data.checkout_url) {
        await Linking.openURL(data.checkout_url);
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Betaling starten mislukt.');
    } finally {
      setBusy(false);
    }
  }

  function startNewRide() {
    setStep('book');
    setTrackToken(null);
    setLive(null);
    persistTrack(null);
    setError(null);
  }

  if (step === 'live') {
    return (
      <Screen style={{ paddingHorizontal: 0 }}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <Title>Live rit</Title>
          <Subtitle>{live?.status_label || 'Bezig…'}</Subtitle>
          <ErrorText>{error}</ErrorText>

          <Card>
            <PhasePill phase={live?.phase} />
            <Text style={styles.liveTitle}>
              {live?.phase === 'searching'
                ? 'We zoeken een taxi in de buurt…'
                : live?.phase === 'accepted'
                  ? 'Taxi onderweg'
                  : live?.phase === 'awaiting_payment'
                    ? 'Wacht op betaling'
                    : live?.status_label || 'Status'}
            </Text>
            {live?.eta_label ? (
              <Text style={styles.meta}>ETA: {live.eta_label}</Text>
            ) : null}
            {live?.driver?.name ? (
              <Text style={styles.meta}>Chauffeur: {live.driver.name}</Text>
            ) : null}
            {live?.vehicle?.label || live?.vehicle?.license_plate ? (
              <Text style={styles.meta}>
                Voertuig: {live.vehicle.label || live.vehicle.license_plate}
              </Text>
            ) : null}
            {live?.company?.name ? (
              <Text style={styles.meta}>Centrale: {live.company.name}</Text>
            ) : null}
            {live?.payment_error ? (
              <Text style={[styles.meta, { color: COLORS.danger }]}>{live.payment_error}</Text>
            ) : null}
          </Card>

          <Card>
            <Text style={styles.rowLabel}>Van</Text>
            <Text style={styles.rowValue}>{live?.pickup_address || pickup?.address || '—'}</Text>
            <Text style={[styles.rowLabel, { marginTop: 12 }]}>Naar</Text>
            <Text style={styles.rowValue}>{live?.dropoff_address || dropoff?.address || '—'}</Text>
            {live?.quoted_price != null ? (
              <>
                <Text style={[styles.rowLabel, { marginTop: 12 }]}>Prijs</Text>
                <Text style={styles.rowValue}>
                  € {Number(live.quoted_price).toFixed(2).replace('.', ',')}
                </Text>
              </>
            ) : null}
          </Card>

          {live?.needs_unaccepted_decision ? (
            <Card>
              <Text style={styles.liveTitle}>Nog geen taxi gevonden</Text>
              <Text style={styles.meta}>
                Wil je blijven wachten
                {live.decision_minutes ? ` (tot ca. ${live.decision_deadline_label || '—'})` : ''} of
                annuleren?
              </Text>
              <PrimaryButton title="Blijven wachten" onPress={onWait} loading={busy} />
              <GhostButton title="Annuleren" onPress={onCancel} />
            </Card>
          ) : null}

          {live?.can_retry_payment ? (
            <PrimaryButton title="Nu betalen" onPress={onPay} loading={busy} />
          ) : null}

          {live?.can_cancel && !live?.needs_unaccepted_decision ? (
            <GhostButton title="Rit annuleren" onPress={onCancel} />
          ) : null}

          {live?.phase === 'cancelled' || live?.phase === 'completed' ? (
            <PrimaryButton title="Nieuwe rit" onPress={startNewRide} />
          ) : null}

          <GhostButton title="Terug naar start" onPress={onBack} />
        </ScrollView>
      </Screen>
    );
  }

  const bookingEnabled = quote?.payment?.booking && quote?.payment?.mollie_configured;

  return (
    <Screen style={{ paddingHorizontal: 0 }}>
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <Title>Taxi boeken</Title>
        <Subtitle>Volledig native — ophalen, prijs, boeken en live volgen in de app.</Subtitle>
        <ErrorText>{error}</ErrorText>

        <Card>
          <Text style={styles.section}>Ophalen</Text>
          <Text style={styles.rowValue}>{pickup?.address || 'Locatie bepalen…'}</Text>
          <GhostButton
            title={pickupLoading ? 'Bezig…' : 'Locatie vernieuwen'}
            onPress={loadPickup}
          />
        </Card>

        <Card>
          <Field
            label="Bestemming"
            value={dropoffQuery}
            onChangeText={(v) => {
              setDropoffQuery(v);
              setDropoff(null);
            }}
            placeholder="Straat, huisnr, plaats"
            autoCapitalize="words"
          />
          {dropoff ? (
            <Text style={[styles.meta, { color: COLORS.success }]}>Gevonden: {dropoff.address}</Text>
          ) : null}
          <PrimaryButton
            title="Bestemming zoeken"
            onPress={resolveDropoff}
            loading={dropoffBusy}
            disabled={dropoffQuery.trim().length < 3}
          />
        </Card>

        <Card>
          <Field
            label="Passagiers"
            value={passengers}
            onChangeText={setPassengers}
            keyboardType="number-pad"
            placeholder="1"
          />
          <View style={styles.nameRow}>
            <View style={{ flex: 1 }}>
              <Field label="Voornaam" value={firstName} onChangeText={setFirstName} autoCapitalize="words" />
            </View>
            <View style={{ width: 10 }} />
            <View style={{ flex: 1 }}>
              <Field label="Achternaam" value={lastName} onChangeText={setLastName} autoCapitalize="words" />
            </View>
          </View>
          <Field
            label="Telefoon"
            value={phone}
            onChangeText={setPhone}
            keyboardType="phone-pad"
            placeholder="06…"
          />
          <Field
            label="E-mail (optioneel)"
            value={email}
            onChangeText={setEmail}
            keyboardType="email-address"
            autoCapitalize="none"
            placeholder="naam@email.nl"
          />
          <Field
            label="Opmerkingen"
            value={remarks}
            onChangeText={setRemarks}
            placeholder="Bijv. bakfiets, hulp bij instappen…"
            multiline
          />
        </Card>

        <Card>
          <Text style={styles.section}>Prijsindicatie</Text>
          {quoteBusy ? (
            <Text style={styles.meta}>Bezig met berekenen…</Text>
          ) : offer ? (
            <>
              <Text style={styles.price}>
                {offer.price_label ||
                  (offer.price != null
                    ? `€ ${Number(offer.price).toFixed(2).replace('.', ',')}`
                    : '—')}
              </Text>
              <Text style={styles.meta}>
                {(quote?.marketplace?.candidate_count ?? 0) === 1
                  ? '1 taxi in straal'
                  : `${quote?.marketplace?.candidate_count ?? 0} taxi’s in straal`}
              </Text>
            </>
          ) : (
            <Text style={styles.meta}>Vul ophalen + bestemming in voor een prijs.</Text>
          )}

          {bookingEnabled ? (
            <Pressable
              onPress={() => setPayOnline((v) => !v)}
              style={styles.payToggle}
            >
              <View style={[styles.checkbox, payOnline && styles.checkboxOn]} />
              <Text style={styles.payToggleText}>Nu online betalen</Text>
            </Pressable>
          ) : null}
        </Card>

        <PrimaryButton
          title={payOnline ? 'Boeken en betalen' : 'Taxi aanvragen'}
          onPress={onBook}
          loading={busy}
          disabled={!pickup || !dropoff || !offer}
        />
        <GhostButton title="Terug" onPress={onBack} />
      </ScrollView>
    </Screen>
  );
}

function PhasePill({ phase }: { phase?: string }) {
  const label =
    phase === 'searching'
      ? 'Zoeken'
      : phase === 'accepted'
        ? 'Geaccepteerd'
        : phase === 'awaiting_payment'
          ? 'Betaling'
          : phase === 'cancelled'
            ? 'Geannuleerd'
            : phase === 'completed'
              ? 'Afgerond'
              : phase || '…';
  const color =
    phase === 'accepted'
      ? COLORS.success
      : phase === 'cancelled'
        ? COLORS.danger
        : phase === 'awaiting_payment'
          ? COLORS.amber
          : COLORS.primary;
  return (
    <View style={[styles.pill, { backgroundColor: color + '33', borderColor: color }]}>
      <Text style={[styles.pillText, { color }]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  scroll: {
    paddingHorizontal: 20,
    paddingTop: 8,
    paddingBottom: 40,
  },
  section: {
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.4,
    marginBottom: 8,
  },
  rowLabel: {
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '600',
    marginBottom: 4,
  },
  rowValue: {
    color: COLORS.text,
    fontSize: 15,
    lineHeight: 21,
  },
  meta: {
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 20,
    marginTop: 6,
  },
  price: {
    color: COLORS.text,
    fontSize: 28,
    fontWeight: '700',
  },
  nameRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  liveTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '700',
    marginTop: 10,
  },
  pill: {
    alignSelf: 'flex-start',
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  pillText: {
    fontSize: 12,
    fontWeight: '700',
  },
  payToggle: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginTop: 14,
  },
  checkbox: {
    width: 22,
    height: 22,
    borderRadius: 6,
    borderWidth: 1.5,
    borderColor: COLORS.border,
    backgroundColor: 'transparent',
  },
  checkboxOn: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  payToggleText: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '600',
  },
});
