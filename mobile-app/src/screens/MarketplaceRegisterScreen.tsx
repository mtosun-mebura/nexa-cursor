import React, { useMemo, useState } from 'react';
import {
  Image,
  KeyboardAvoidingView,
  Platform,
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
} from '../ui/components';

const logoLight = require('../../assets/nexa-taxi-logo.png');
const logoDark = require('../../assets/nexa-taxi-logo-dark.png');

function firstValidationMessage(body: unknown): string | null {
  if (!body || typeof body !== 'object') return null;
  const errors = (body as { errors?: Record<string, string[]> }).errors;
  if (!errors) return null;
  for (const msgs of Object.values(errors)) {
    if (Array.isArray(msgs) && msgs[0]) return msgs[0];
  }
  return null;
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
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [hint, setHint] = useState<string | null>(null);

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
      setChannel(res.channel || 'driver');
      setHint(res.message);
      setStep('verify');
    } catch (e) {
      if (e instanceof ApiError) {
        const body = e.body as {
          next?: string;
          message?: string;
          channel?: string;
          errors?: Record<string, string[]>;
        };
        if (e.status === 422 && body?.next === 'verify_code') {
          setChannel(body.channel || 'driver');
          setHint(body.message || 'Bedrijf aangemaakt. Vraag de code opnieuw aan.');
          setStep('verify');
          return;
        }
        setError(firstValidationMessage(e.body) || e.message || 'Registreren mislukt.');
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
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Code versturen mislukt.');
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
      await setSession(session);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Code bevestigen mislukt.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen>
      <View style={styles.logoBar}>
        <Image
          source={logoSource}
          style={styles.logo}
          resizeMode="contain"
          accessibilityLabel="NEXA | taxi"
        />
      </View>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView
          keyboardShouldPersistTaps="handled"
          keyboardDismissMode="on-drag"
          contentContainerStyle={{ flexGrow: 1, paddingBottom: 24 }}
        >
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
              <Subtitle>{email}</Subtitle>
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

          {hint ? <Subtitle>{hint}</Subtitle> : null}
          <ErrorText>{error}</ErrorText>

          {step === 'form' ? (
            <>
              <PrimaryButton title="Aanmelden" onPress={onRegister} loading={loading} />
              <GhostButton title="Terug" onPress={onBack} />
            </>
          ) : (
            <>
              <PrimaryButton title="Account activeren" onPress={onVerify} loading={loading} />
              <GhostButton title="Code opnieuw sturen" onPress={onResendCode} />
              <GhostButton
                title="Terug"
                onPress={() => {
                  setStep('form');
                  setCode('');
                  setError(null);
                  setHint(null);
                }}
              />
            </>
          )}
        </ScrollView>
      </KeyboardAvoidingView>
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
      fontSize: 28,
      fontWeight: '700',
      marginBottom: 8,
      textAlign: 'center',
    },
  });
}
