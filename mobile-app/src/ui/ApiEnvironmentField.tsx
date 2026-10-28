import React, { useEffect, useState, useSyncExternalStore } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import {
  API_ENVIRONMENTS,
  environmentIdForUrl,
  fetchApiEnvironments,
  getApiBaseUrl,
  normalizeApiBaseUrl,
  setApiBaseUrl,
  subscribeApiBase,
  type ApiEnvironment,
} from '../config';
import { useThemeColors } from '../theme/ThemeContext';
import { Field } from './components';

/**
 * Serverkeuze vóór het inloggen. Staat los van de publieke host in de admin:
 * die is voor betaal-URL's; dit is welke server dit toestel aanroept.
 */
export function ApiEnvironmentField() {
  const colors = useThemeColors();
  const saved = useSyncExternalStore(subscribeApiBase, getApiBaseUrl);
  const [draft, setDraft] = useState(saved);
  const [touched, setTouched] = useState(false);
  const [remote, setRemote] = useState<ApiEnvironment[] | null>(null);

  useEffect(() => {
    if (!touched) setDraft(saved);
  }, [saved, touched]);

  useEffect(() => {
    let cancelled = false;
    setRemote(null);
    void fetchApiEnvironments(saved).then((list) => {
      if (!cancelled) setRemote(list);
    });
    return () => {
      cancelled = true;
    };
  }, [saved]);

  const environments = remote && remote.length > 0 ? remote : API_ENVIRONMENTS;
  const environment = environmentIdForUrl(draft, environments);
  const normalized = normalizeApiBaseUrl(draft);
  const error =
    touched && !normalized
      ? 'Vul een https-adres in. http alleen voor localhost.'
      : null;

  async function choose(url: string) {
    setTouched(true);
    setDraft(url);
    await setApiBaseUrl(url);
  }

  return (
    <View style={styles.wrap}>
      <Text style={[styles.label, { color: colors.muted }]}>Omgeving</Text>
      <View style={styles.row}>
        {environments.map((item) => {
          const active = environment === item.id;
          return (
            <Pressable
              key={item.id}
              onPress={() => {
                void choose(item.url);
              }}
              accessibilityRole="button"
              accessibilityState={{ selected: active }}
              style={[
                styles.chip,
                {
                  borderColor: active ? colors.primary : colors.border,
                  backgroundColor: active ? colors.primary : colors.inputBg,
                },
              ]}
            >
              <Text style={{ color: active ? '#fff' : colors.text, fontWeight: '700', fontSize: 13 }}>
                {item.label}
              </Text>
            </Pressable>
          );
        })}
        {environment === 'aangepast' ? (
          <View
            style={[
              styles.chip,
              { borderColor: colors.primary, backgroundColor: colors.primary },
            ]}
          >
            <Text style={{ color: '#fff', fontWeight: '700', fontSize: 13 }}>Aangepast</Text>
          </View>
        ) : null}
      </View>
      <Field
        label="Host"
        value={draft}
        onChangeText={(next) => {
          setTouched(true);
          setDraft(next);
          if (normalizeApiBaseUrl(next)) void setApiBaseUrl(next);
        }}
        autoCapitalize="none"
        autoComplete="off"
        keyboardType="url"
        placeholder="https://nexasuite.nl"
        error={error}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: 4 },
  label: {
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 6,
    textTransform: 'uppercase',
    letterSpacing: 0.4,
  },
  row: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 12,
  },
  chip: {
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
});
