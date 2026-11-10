import React, { useMemo, useRef, useState } from 'react';
import {
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { loginWithPassword, requestLoginCode, verifyLoginCode } from '../api/auth';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { ColorPalette } from '../config';
import { useI18n, t } from '../i18n';
import { useThemeColors } from '../theme/ThemeContext';
import { AppearanceControls } from '../ui/AppearanceControls';
import { AppPageHeader, APP_PAGE_TOP_INSET } from '../ui/AppPageHeader';
import { useAndroidKeyboardOverlap } from '../ui/androidText';
import { useAndroidBackHandler } from '../ui/useAndroidBackHandler';
import {
  ErrorText,
  Field,
  GhostButton,
  PrimaryButton,
  Screen,
  Subtitle,
  SuccessText,
} from '../ui/components';

const CODE_LENGTH = 6;

function CodeDigits({
  value,
  onChange,
}: {
  value: string;
  onChange: (next: string) => void;
}) {
  const colors = useThemeColors();
  const inputRef = useRef<TextInput>(null);
  const digits = value.replace(/\D/g, '').slice(0, CODE_LENGTH);
  const activeIndex = Math.min(digits.length, CODE_LENGTH - 1);

  return (
    <View style={codeStyles.field}>
      <Text style={[codeStyles.label, { color: colors.muted }]}>{t("Code uit e-mail")}</Text>
      <View style={codeStyles.row}>
        {Array.from({ length: CODE_LENGTH }, (_, index) => {
          const digit = digits[index] ?? '';
          const active = digits.length < CODE_LENGTH ? index === digits.length : index === activeIndex;
          return (
            <View
              key={index}
              style={[
                codeStyles.cell,
                {
                  borderColor: active ? colors.primary : colors.border,
                  backgroundColor: colors.inputBg,
                },
              ]}
            >
              <Text style={[codeStyles.digit, { color: colors.text }]}>{digit}</Text>
            </View>
          );
        })}
        <TextInput
          ref={inputRef}
          value={digits}
          onChangeText={(next) => onChange(next.replace(/\D/g, '').slice(0, CODE_LENGTH))}
          keyboardType="number-pad"
          inputMode="numeric"
          textContentType="oneTimeCode"
          autoComplete="sms-otp"
          maxLength={CODE_LENGTH}
          caretHidden
          autoCorrect={false}
          spellCheck={false}
          style={codeStyles.capture}
          accessibilityLabel={t("Code uit e-mail")}
        />
      </View>
    </View>
  );
}

const codeStyles = StyleSheet.create({
  field: { marginBottom: 14 },
  label: {
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 6,
    textTransform: 'uppercase',
    letterSpacing: 0.4,
  },
  row: {
    flexDirection: 'row',
    gap: 8,
  },
  cell: {
    flex: 1,
    height: 56,
    borderWidth: 1,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  digit: {
    fontSize: 22,
    fontWeight: '700',
  },
  capture: {
    ...StyleSheet.absoluteFill,
    color: 'transparent',
  },
});

export function LoginScreen({ onBack }: { onBack: () => void }) {
  const keyboardOverlap = useAndroidKeyboardOverlap();
  useAndroidBackHandler(() => {
    onBack();
    return true;
  });
  const { setSession } = useAuth();
  const colors = useThemeColors();
  useI18n();
  const styles = useMemo(() => makeStyles(colors), [colors]);
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
      setError(e instanceof ApiError ? e.message : t("Code versturen mislukt."));
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
      const msg = e instanceof ApiError ? e.message : t("Inloggen mislukt.");
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
    <Screen style={{ paddingTop: APP_PAGE_TOP_INSET, paddingHorizontal: 0 }}>
      <AppPageHeader
        title={t("Inloggen")}
        left={
          <Pressable
            onPress={onBack}
            hitSlop={10}
            accessibilityRole="button"
            accessibilityLabel={t("Naar startpagina")}
          >
            <Ionicons name="home-outline" size={24} color={colors.text} />
          </Pressable>
        }
        right={<AppearanceControls />}
      />
      <ScrollView
        style={styles.body}
        keyboardShouldPersistTaps="always"
        keyboardDismissMode="on-drag"
        automaticallyAdjustKeyboardInsets
        contentContainerStyle={{ flexGrow: 1, paddingBottom: 24 + keyboardOverlap }}
      >
        <Field
          label={t("E-mailadres")}
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
          label={t("Wachtwoord (als je er een hebt)")}
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
          <CodeDigits value={code} onChange={setCode} />
        )}

        {hint ? (
          /code gestuurd/i.test(hint) ? (
            <SuccessText>{hint}</SuccessText>
          ) : (
            <Subtitle>{hint}</Subtitle>
          )
        ) : null}
        <ErrorText>{error}</ErrorText>

        <PrimaryButton title={t("Inloggen")} onPress={onSubmit} loading={loading} />
        <GhostButton title={t("Code sturen")} onPress={onRequestCode} />
      </ScrollView>
    </Screen>
  );
}

function makeStyles(_colors: ColorPalette) {
  return StyleSheet.create({
    body: {
      flex: 1,
      paddingHorizontal: 20,
    },
  });
}
