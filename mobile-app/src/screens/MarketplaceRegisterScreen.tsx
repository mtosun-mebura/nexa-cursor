import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { registerMarketplaceCompany, requestLoginCode, verifyLoginCode } from '../api/auth';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { ColorPalette } from '../config';
import { ibanFieldError } from '../iban';
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

const VERIFY_PENDING_KEY = 'nexa_taxi_marketplace_verify_pending';
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

type PendingVerify = {
  email: string;
  channel?: string;
  company_name?: string;
  phone?: string;
  city?: string;
  hint?: string;
};

type RegisterField = 'company_name' | 'email' | 'phone' | 'city' | 'iban';
type RegisterErrors = Partial<Record<RegisterField, string>>;

const REGISTER_FIELDS: RegisterField[] = ['company_name', 'email', 'phone', 'city', 'iban'];

function isValidEmail(value: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
}

function registerFieldErrors(values: {
  company_name: string;
  email: string;
  phone: string;
  city: string;
  iban: string;
}): RegisterErrors {
  const next: RegisterErrors = {};
  if (!values.company_name.trim()) next.company_name = t("Vul de bedrijfsnaam in.");
  const email = values.email.trim();
  if (!email) next.email = t("Vul een e-mailadres in.");
  else if (!isValidEmail(email)) next.email = t("Vul een geldig e-mailadres in.");
  if (!values.phone.trim()) next.phone = t("Vul een telefoonnummer in.");
  const city = values.city.trim();
  if (!city) next.city = t("Vul de plaats in waar het bedrijf zich bevindt.");
  else if (city.length < 2) next.city = t("Plaats moet minimaal 2 tekens bevatten.");
  const ibanError = ibanFieldError(values.iban);
  if (ibanError) next.iban = ibanError;
  return next;
}

function serverFieldErrors(body: unknown): RegisterErrors {
  if (!body || typeof body !== 'object') return {};
  const errors = (body as { errors?: Record<string, string[] | string> }).errors;
  if (!errors) return {};
  const next: RegisterErrors = {};
  for (const field of REGISTER_FIELDS) {
    const raw = errors[field];
    const message = Array.isArray(raw) ? raw[0] : raw;
    if (typeof message === 'string' && message.trim()) next[field] = message;
  }
  return next;
}

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
  const colors = useThemeColors();
  useI18n();
  const styles = useMemo(() => makeStyles(colors), [colors]);

  const [companyName, setCompanyName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [city, setCity] = useState('');
  const [iban, setIban] = useState('');
  const [code, setCode] = useState('');
  const [password, setPassword] = useState('');
  const [channel, setChannel] = useState<string | undefined>('driver');
  const [step, setStep] = useState<'form' | 'verify'>('form');
  const [ready, setReady] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<RegisterErrors>({});
  const [formChecked, setFormChecked] = useState(false);
  const [hint, setHint] = useState<string | null>(null);
  const keyboardOverlap = useAndroidKeyboardOverlap();

  useAndroidBackHandler(() => {
    if (step === 'verify') {
      setStep('form');
      setCode('');
      setPassword('');
      setError(null);
      return true;
    }
    onBack();
    return true;
  });

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

  function valuesNow(overrides?: Partial<Record<RegisterField, string>>) {
    return {
      company_name: companyName,
      email,
      phone,
      city,
      iban,
      ...overrides,
    };
  }

  function changeField(field: RegisterField, value: string, setter: (next: string) => void) {
    setter(value);
    if (field === 'iban') {
      const live = ibanFieldError(value, true);
      setFieldErrors((current) => ({ ...current, iban: live ?? undefined }));
      if (!formChecked) return;
    }
    if (!formChecked) return;
    const next = registerFieldErrors(valuesNow({ [field]: value }));
    setFieldErrors((current) => ({ ...current, [field]: next[field] }));
  }

  function openCodeStep() {
    setError(null);
    const trimmed = email.trim();
    if (!trimmed || !isValidEmail(trimmed)) {
      setFieldErrors((current) => ({
        ...current,
        email: t("Vul eerst je e-mailadres in om de code te bevestigen."),
      }));
      setFormChecked(true);
      return;
    }
    void goToVerify({
      email: trimmed,
      channel: channel || 'driver',
      hint: t("Vul de code uit je e-mail in. Geen code ontvangen? Stuur hem opnieuw."),
    });
  }

  async function onRegister() {
    setError(null);
    const nextErrors = registerFieldErrors(valuesNow());
    setFieldErrors(nextErrors);
    setFormChecked(true);
    if (Object.keys(nextErrors).length > 0) return;
    setLoading(true);
    try {
      const res = await registerMarketplaceCompany({
        company_name: companyName.trim(),
        email: email.trim(),
        phone: phone.trim(),
        city: city.trim(),
        iban: iban.trim(),
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
            hint: body.message || t("Bedrijf aangemaakt. Vraag de code opnieuw aan."),
          });
          return;
        }
        const mapped = serverFieldErrors(e.body);
        if (Object.keys(mapped).length > 0) {
          setFieldErrors(mapped);
          setFormChecked(true);
          return;
        }
        const msg = firstValidationMessage(e.body) || e.message || t("Registreren mislukt.");
        if (/al in gebruik|bestaat al|log in/i.test(msg)) {
          setFieldErrors({ email: msg });
          setFormChecked(true);
        } else {
          setError(msg);
        }
      } else {
        setError(t("Registreren mislukt."));
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
        setError(e.message || t("Code versturen mislukt."));
      } else {
        setError(
          t("Geen verbinding met de server. Controleer je netwerk of probeer het zo opnieuw.")
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
      setError(e instanceof ApiError ? e.message : t("Code bevestigen mislukt."));
    } finally {
      setLoading(false);
    }
  }

  if (!ready) {
    return <Screen>{null}</Screen>;
  }

  return (
    <Screen style={{ paddingTop: APP_PAGE_TOP_INSET, paddingHorizontal: 0 }}>
      <AppPageHeader
        title={step === 'form' ? t("NEXA Taxi marktplaats") : t("Bevestig e-mail")}
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
        contentContainerStyle={{ flexGrow: 1, paddingBottom: 40 + keyboardOverlap }}
      >
        <Subtitle>
          {step === 'form'
            ? t("Registreer je taxibedrijf op de NEXA Taxi marktplaats.\nNa registratie ontvang je een code per e-mail.")
            : t("Vul de code uit je e-mail in om je account te activeren.")}
        </Subtitle>

        {step === 'form' ? (
          <>
            <Field
              label={t("Bedrijfsnaam")}
              value={companyName}
              onChangeText={(value) => changeField('company_name', value, setCompanyName)}
              autoCapitalize="words"
              placeholder={t("Taxi Amsterdam")}
              returnKeyType="next"
              error={fieldErrors.company_name}
            />
            <Field
              label={t("E-mailadres")}
              value={email}
              onChangeText={(value) => changeField('email', value, setEmail)}
              keyboardType="email-address"
              autoCapitalize="none"
              autoComplete="email"
              textContentType="emailAddress"
              placeholder="beheer@taxibedrijf.nl"
              returnKeyType="next"
              error={fieldErrors.email}
            />
            <Field
              label={t("Telefoon")}
              value={phone}
              onChangeText={(value) => changeField('phone', value, setPhone)}
              keyboardType="phone-pad"
              autoComplete="tel"
              textContentType="telephoneNumber"
              placeholder="06…"
              returnKeyType="next"
              error={fieldErrors.phone}
            />
            <Field
              label={t("Plaats")}
              value={city}
              onChangeText={(value) => changeField('city', value, setCity)}
              autoCapitalize="words"
              placeholder="Amsterdam"
              returnKeyType="next"
              error={fieldErrors.city}
            />
            <Field
              label="IBAN"
              value={iban}
              onChangeText={(value) => changeField('iban', value.toUpperCase(), setIban)}
              autoCapitalize="characters"
              autoComplete="off"
              placeholder="NL91 ABNA 0417 1643 00"
              returnKeyType="done"
              error={fieldErrors.iban}
              onSubmitEditing={() => {
                if (!loading) void onRegister();
              }}
            />
            <Text style={styles.ibanHint}>{t("Dit is nodig voor de uitbetaling van de ritten.")}</Text>
          </>
        ) : (
          <>
            <Field
              label={t("E-mailadres")}
              value={email}
              onChangeText={setEmail}
              keyboardType="email-address"
              autoCapitalize="none"
              autoComplete="email"
              textContentType="emailAddress"
              placeholder="beheer@taxibedrijf.nl"
              returnKeyType="next"
            />
            <CodeDigits value={code} onChange={setCode} />
            <Field
              label={t("Wachtwoord (optioneel)")}
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              autoComplete="password"
              textContentType="password"
              placeholder={t("Min. 8 tekens")}
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
            <PrimaryButton title={t("Registreren")} onPress={onRegister} loading={loading} />
            <GhostButton title={t("Activeer account met code")} onPress={openCodeStep} />
          </>
        ) : (
          <>
            <PrimaryButton title={t("Activeer account")} onPress={onVerify} loading={loading} />
            <GhostButton title={t("Code opnieuw sturen")} onPress={onResendCode} />
            <GhostButton
              title={t("Terug naar registreren")}
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
    body: {
      flex: 1,
      paddingHorizontal: 20,
    },
    ibanHint: {
      color: colors.muted,
      fontSize: 13,
      lineHeight: 18,
      marginTop: -8,
      marginBottom: 12,
    },
  });
}
