import React, { useMemo, useState } from 'react';
import {
  Image,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { loginWithPassword, requestLoginCode, verifyLoginCode } from '../api/auth';
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

export function LoginScreen({ onBack }: { onBack: () => void }) {
  const { setSession } = useAuth();
  const { colors, colorScheme } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const logoSource = colorScheme === 'light' ? logoLight : logoDark;
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [code, setCode] = useState('');
  const [channel, setChannel] = useState<string | undefined>();
  const [codeSent, setCodeSent] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [hint, setHint] = useState<string | null>(null);

  async function onRequestCode() {
    setError(null);
    setLoading(true);
    try {
      const res = await requestLoginCode(email.trim());
      setChannel(res.channel);
      setCodeSent(true);
      setHint(res.message);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Code versturen mislukt.');
    } finally {
      setLoading(false);
    }
  }

  async function onSubmit() {
    setError(null);
    setLoading(true);
    try {
      const trimmed = email.trim();
      let session;
      if (codeSent || code.trim()) {
        session = await verifyLoginCode({
          email: trimmed,
          code: code.trim(),
          skip_password: !password,
          password: password || undefined,
          channel,
        });
      } else {
        session = await loginWithPassword(trimmed, password);
      }
      await setSession(session);
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : 'Inloggen mislukt.';
      setError(msg);
      const body = e instanceof ApiError ? (e.body as { error?: string }) : null;
      if (body?.error === 'first_login_required') {
        setHint('Vraag een eenmalige code aan om verder te gaan.');
        setCodeSent(true);
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen>
      <ScrollView
        style={{ flex: 1 }}
        keyboardShouldPersistTaps="always"
        keyboardDismissMode="on-drag"
        automaticallyAdjustKeyboardInsets
        contentContainerStyle={{ flexGrow: 1, paddingBottom: 24 }}
      >
        <View style={styles.logoBar}>
          <Image
            source={logoSource}
            style={styles.logo}
            resizeMode="contain"
            accessibilityLabel="NEXA | taxi"
          />
        </View>
        <Text style={styles.pageTitle}>Inloggen</Text>

        <Field
          label="E-mailadres"
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
          autoComplete="email"
          textContentType="emailAddress"
          placeholder="jij@bedrijf.nl"
          returnKeyType="next"
        />
        <Field
          label="Wachtwoord (als je er een hebt)"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          autoComplete="password"
          textContentType="password"
          placeholder="••••••••"
          returnKeyType="done"
          onSubmitEditing={() => {
            if (!loading) void onSubmit();
          }}
        />
        {(codeSent || !!code) && (
          <Field
            label="Code uit e-mail"
            value={code}
            onChangeText={setCode}
            keyboardType="number-pad"
            placeholder="000000"
          />
        )}

        {hint ? <Subtitle>{hint}</Subtitle> : null}
        <ErrorText>{error}</ErrorText>

        <PrimaryButton title="Inloggen" onPress={onSubmit} loading={loading} />
        <GhostButton title="Code sturen" onPress={onRequestCode} />
        <GhostButton title="Terug" onPress={onBack} />
      </ScrollView>
    </Screen>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    logoBar: {
      alignItems: 'center',
      paddingTop: 4,
      paddingBottom: 12,
    },
    logo: {
      width: 160,
      height: 40,
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
