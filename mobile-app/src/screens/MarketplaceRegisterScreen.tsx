import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { useEffect, useMemo, useState } from 'react';
import {
  Image,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { registerMarketplaceCompany, requestLoginCode, verifyLoginCode } from '../api/auth';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { ColorPalette } from '../config';
import { useTheme } from '../theme/ThemeContext';
import {
  ErrorText,
  Field,
  GhostButton,
  PrimaryButton,
  Screen,
  Subtitle,
  SuccessText,
} from '../ui/components';

const logoLight = require('../../assets/nexa-taxi-logo.png');
const logoDark = require('../../assets/nexa-taxi-logo-dark.png');

const VERIFY_PENDING_KEY = 'nexa_taxi_marketplace_verify_pending';

type PendingVerify = {
  email: string;
  channel?: string;
  company_name?: string;
  phone?: string;
  city?: string;
  hint?: string;
};

function firstValidationMessage(body: unknown): string | null {
  if (!body || typeof body !== 'object') return null;
  const errors = (body as { errors?: Record<string, string[]> }).errors;
  if (!errors) return null;
  for (const msgs of Object.values(errors)) {
    if (Array.isArray(msgs) && msgs[0]) return msgs[0];
  }
  return null;
}

async function savePendingVerify(pending: PendingVerify) {
  await AsyncStorage.setItem(VERIFY_PENDING_KEY, JSON.stringify(pending));
}

async function clearPendingVerify() {
  await AsyncStorage.removeItem(VERIFY_PENDING_KEY);
}

export function MarketplaceRegisterScreen({ onBack }: { onBack: () => void }) {
  const { setSession } = useAuth();
  const { colors, colorScheme } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const logoSource = colorScheme === 'light' ? logoLight : logoDark;

  const [companyName, setCompanyName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [city, setCity] = useState('');
  const [code, setCode] = useState('');
  const [password, setPassword] = useState('');
  const [channel, setChannel] = useState<string | undefined>('driver');
  const [step, setStep] = useState<'form' | 'verify'>('form');
  const [ready, setReady] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [hint, setHint] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const raw = await AsyncStorage.getItem(VERIFY_PENDING_KEY);
        if (!raw || cancelled) return;
        const pending = JSON.parse(raw) as PendingVerify;
        if (!pending?.email) return;
        setEmail(String(pending.email));
        if (pending.company_name) setCompanyName(String(pending.company_name));
        if (pending.phone) setPhone(String(pending.phone));
        if (pending.city) setCity(String(pending.city));
        if (pending.channel) setChannel(String(pending.channel));
        if (pending.hint) setHint(String(pending.hint));
        setStep('verify');
      } catch {
        /* ignore corrupt storage */
      } finally {
        if (!cancelled) setReady(true);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  async function goToVerify(next: {
    email: string;
    channel?: string;
    hint?: string | null;
  }) {
    const trimmed = next.email.trim().toLowerCase();
    setEmail(trimmed);
    setChannel(next.channel || 'driver');
    if (next.hint) setHint(next.hint);
    setStep('verify');
    setError(null);
    await savePendingVerify({
      email: trimmed,
      channel: next.channel || 'driver',
      company_name: companyName.trim() || undefined,
      phone: phone.trim() || undefined,
      city: city.trim() || undefined,
      hint: next.hint || undefined,
    });
  }

  function openCodeStep() {
    setError(null);
    const trimmed = email.trim();
    if (!trimmed || !trimmed.includes('@')) {
      setError('Vul eerst je e-mailadres in om de code te bevestigen.');
      return;
    }
    void goToVerify({
      email: trimmed,
      channel: channel || 'driver',
      hint: 'Vul de code uit je e-mail in. Geen code ontvangen? Stuur hem opnieuw.',
    });
  }

  async function onRegister() {
    setError(null);
    setLoading(true);
    try {
      const res = await registerMarketplaceCompany({
        company_name: companyName.trim(),
        email: email.trim(),
        phone: phone.trim(),
        city: city.trim(),
      });
      await goToVerify({
        email: res.email || email,
        channel: res.channel || 'driver',
        hint: res.message,
      });
    } catch (e) {
      if (e instanceof ApiError) {
        const body = e.body as {
          next?: string;
          message?: string;
          channel?: string;
          email?: string;
          errors?: Record<string, string[]>;
        };
        // Al geregistreerd / code opnieuw → naar verify
        if (e.status === 422 && body?.next === 'verify_code') {
          await goToVerify({
            email: body.email || email,
            channel: body.channel || 'driver',
            hint: body.message || 'Bedrijf aangemaakt. Vraag de code opnieuw aan.',
          });
          return;
        }
        const msg = firstValidationMessage(e.body) || e.message || 'Registreren mislukt.';
        // Bestaand e-mailadres: bied code-stap aan
        if (/al in gebruik|bestaat al|log in/i.test(msg)) {
          setError(`${msg} Of open “Ik heb al een code” hieronder.`);
        } else {
          setError(msg);
        }
      } else {
        setError('Registreren mislukt.');
      }
    } finally {
      setLoading(false);
    }
  }

  async function onResendCode() {
    setError(null);
    setLoading(true);
    try {
      const res = await requestLoginCode(email.trim());
      setChannel(res.channel || 'driver');
      setHint(res.message);
      await savePendingVerify({
        email: email.trim().toLowerCase(),
        channel: res.channel || 'driver',
        company_name: companyName.trim() || undefined,
        phone: phone.trim() || undefined,
        city: city.trim() || undefined,
        hint: res.message,
      });
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message || 'Code versturen mislukt.');
      } else {
        setError(
          'Geen verbinding met de server. Controleer je netwerk of probeer het zo opnieuw.'
        );
      }
    } finally {
      setLoading(false);
    }
  }

  async function onVerify() {
    setError(null);
    setLoading(true);
    try {
      const session = await verifyLoginCode({
        email: email.trim(),
        code: code.trim(),
        skip_password: !password,
        password: password || undefined,
        channel,
      });
      await clearPendingVerify();
      await setSession(session);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Code bevestigen mislukt.');
    } finally {
      setLoading(false);
    }
  }

  if (!ready) {
    return <Screen>{null}</Screen>;
  }

  return (
    <Screen>
      <ScrollView
        style={{ flex: 1 }}
        keyboardShouldPersistTaps="always"
        keyboardDismissMode="on-drag"
        automaticallyAdjustKeyboardInsets
        contentContainerStyle={{ flexGrow: 1, paddingBottom: 40 }}
      >
        <View style={styles.logoBar}>
          <Image
            source={logoSource}
            style={styles.logo}
            resizeMode="contain"
            accessibilityLabel="NEXA | taxi"
          />
        </View>
        <Text style={styles.pageTitle}>
          {step === 'form' ? 'Taxibedrijf aanmelden' : 'Bevestig e-mail'}
        </Text>
        <Subtitle>
          {step === 'form'
            ? 'Maak je marktplaats-account aan. Daarna ontvang je een code per e-mail.'
            : 'Vul de code uit je e-mail in om je account te activeren.'}
        </Subtitle>

        {step === 'form' ? (
          <>
            <Field
              label="Bedrijfsnaam"
              value={companyName}
              onChangeText={setCompanyName}
              autoCapitalize="words"
              placeholder="Taxi Amsterdam"
              returnKeyType="next"
            />
            <Field
              label="E-mailadres"
              value={email}
              onChangeText={setEmail}
              keyboardType="email-address"
              autoCapitalize="none"
              autoComplete="email"
              textContentType="emailAddress"
              placeholder="beheer@taxibedrijf.nl"
              returnKeyType="next"
            />
            <Field
              label="Telefoon"
              value={phone}
              onChangeText={setPhone}
              keyboardType="phone-pad"
              autoComplete="tel"
              textContentType="telephoneNumber"
              placeholder="06…"
              returnKeyType="next"
            />
            <Field
              label="Plaats"
              value={city}
              onChangeText={setCity}
              autoCapitalize="words"
              placeholder="Amsterdam"
              returnKeyType="done"
              onSubmitEditing={() => {
                if (!loading) void onRegister();
              }}
            />
          </>
        ) : (
          <>
            <Field
              label="E-mailadres"
              value={email}
              onChangeText={setEmail}
              keyboardType="email-address"
              autoCapitalize="none"
              autoComplete="email"
              textContentType="emailAddress"
              placeholder="beheer@taxibedrijf.nl"
              returnKeyType="next"
            />
            <Field
              label="Code uit e-mail"
              value={code}
              onChangeText={setCode}
              keyboardType="number-pad"
              placeholder="000000"
              returnKeyType="next"
            />
            <Field
              label="Wachtwoord (optioneel)"
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              autoComplete="password"
              textContentType="password"
              placeholder="Min. 8 tekens"
              returnKeyType="done"
              onSubmitEditing={() => {
                if (!loading) void onVerify();
              }}
            />
          </>
        )}

        {hint ? (
          /code gestuurd/i.test(hint) ? (
            <SuccessText>{hint}</SuccessText>
          ) : (
            <Subtitle>{hint}</Subtitle>
          )
        ) : null}
        <ErrorText>{error}</ErrorText>

        {step === 'form' ? (
          <>
            <PrimaryButton title="Aanmelden" onPress={onRegister} loading={loading} />
            <GhostButton title="Ik heb al een code" onPress={openCodeStep} />
            <GhostButton title="Terug" onPress={onBack} />
          </>
        ) : (
          <>
            <PrimaryButton title="Account activeren" onPress={onVerify} loading={loading} />
            <GhostButton title="Code opnieuw sturen" onPress={onResendCode} />
            <GhostButton
              title="Terug naar aanmelden"
              onPress={() => {
                setStep('form');
                setCode('');
                setPassword('');
                setError(null);
              }}
            />
          </>
        )}
      </ScrollView>
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
    pageTitle: {
      color: colors.text,
      fontSize: 22,
      fontWeight: '700',
      marginBottom: 8,
      textAlign: 'center',
    },
  });
}
