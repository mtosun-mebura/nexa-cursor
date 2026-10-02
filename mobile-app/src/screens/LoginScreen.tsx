import React, { useState } from 'react';
import { loginWithPassword, requestLoginCode, verifyLoginCode } from '../api/auth';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import {
  ErrorText,
  Field,
  GhostButton,
  PrimaryButton,
  Screen,
  Subtitle,
  Title,
} from '../ui/components';

export function LoginScreen({ onBack }: { onBack: () => void }) {
  const { setSession } = useAuth();
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
      <Title>Inloggen</Title>
      <Subtitle>
        Geen browser. Je logt hier in; daarna openen we het juiste scherm op basis van je rollen.
      </Subtitle>

      <Field
        label="E-mailadres"
        value={email}
        onChangeText={setEmail}
        keyboardType="email-address"
        autoCapitalize="none"
        placeholder="jij@bedrijf.nl"
      />
      <Field
        label="Wachtwoord (als je er een hebt)"
        value={password}
        onChangeText={setPassword}
        secureTextEntry
        placeholder="••••••••"
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
    </Screen>
  );
}
