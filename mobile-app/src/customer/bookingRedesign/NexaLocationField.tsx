import { Ionicons } from '@expo/vector-icons';
import React, { useMemo } from 'react';
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  type TextInputProps,
} from 'react-native';
import { useTheme } from '../../theme/ThemeContext';
import { NexaGlowCard } from './NexaGlowCard';
import { nexaBookingTheme as theme, nexaSurfaceMuted, nexaSurfaceText } from './theme';

type Variant = 'pickup' | 'dropoff';

const PIN_ICON = 22;

type Props = {
  variant: Variant;
  label: string;
  value: string;
  onChangeText: (v: string) => void;
  placeholder?: string;
  confirmed?: boolean;
  error?: string | null;
  inputRef?: React.RefObject<TextInput | null>;
  rightAccessory?: React.ReactNode;
  onClear?: () => void;
  clearAccessibilityLabel?: string;
  suggestions?: { key: string; label: string; onPress: () => void }[];
} & Pick<
  TextInputProps,
  'autoCapitalize' | 'autoComplete' | 'returnKeyType' | 'onSubmitEditing'
>;

/** Ophaal-/bestemmingsveld — Exact Pack / mockup (glass + wit). */
export function NexaLocationField({
  variant,
  label,
  value,
  onChangeText,
  placeholder,
  confirmed: _confirmed,
  error,
  inputRef,
  rightAccessory,
  onClear,
  clearAccessibilityLabel,
  suggestions,
  autoCapitalize,
  autoComplete,
  returnKeyType,
  onSubmitEditing,
}: Props) {
  void _confirmed;
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  const styles = useMemo(() => makeStyles(light, variant), [light, variant]);
  const pinColor =
    variant === 'pickup' ? theme.color.blueBright : theme.color.orangeHot;
  const showClear = !!onClear && value.trim().length > 0;
  const suggestInk = nexaSurfaceText(light);
  const clearColor = variant === 'dropoff' ? theme.color.inkMuted : nexaSurfaceMuted(light);

  return (
    <View style={styles.wrap}>
      <NexaGlowCard
        glowOnly
        style={[styles.card, variant === 'dropoff' && !value.trim() && styles.cardDropoffFocus]}
        contentStyle={styles.cardInner}
      >
        <View style={styles.row}>
          <View style={[styles.pin, { backgroundColor: `${pinColor}22` }]}>
            <Ionicons name="location" size={PIN_ICON} color={pinColor} />
          </View>
          <View style={styles.mid}>
            <Text style={styles.label}>{label}</Text>
            <TextInput
              ref={inputRef}
              value={value}
              onChangeText={onChangeText}
              placeholder={placeholder}
              placeholderTextColor={
                variant === 'dropoff' ? theme.color.inkMuted : nexaSurfaceMuted(light)
              }
              style={styles.input}
              autoCapitalize={autoCapitalize}
              autoComplete={autoComplete}
              returnKeyType={returnKeyType}
              onSubmitEditing={onSubmitEditing}
              autoCorrect={false}
              selectionColor={theme.color.orangeHot}
            />
          </View>
          <View style={styles.actions}>
            {/* Vaste slot: kruis links van locate — locate blijft rechts, ook zonder adres. */}
            {showClear ? (
              <Pressable
                onPress={onClear}
                hitSlop={6}
                style={styles.iconBtn}
                accessibilityRole="button"
                accessibilityLabel={clearAccessibilityLabel || label}
              >
                <Ionicons name="close-circle" size={PIN_ICON} color={clearColor} />
              </Pressable>
            ) : (
              <View style={styles.iconBtn} pointerEvents="none" />
            )}
            {rightAccessory ?? <View style={styles.iconBtn} pointerEvents="none" />}
          </View>
        </View>
      </NexaGlowCard>
      {error ? <Text style={styles.error}>{error}</Text> : null}
      {suggestions && suggestions.length > 0 ? (
        <View style={styles.suggestBox}>
          {suggestions.map((s, i) => (
            <Pressable
              key={s.key}
              onPress={s.onPress}
              style={[styles.suggestRow, i === 0 && styles.suggestFirst]}
            >
              <Ionicons name="locate-outline" size={18} color={pinColor} />
              <Text style={[styles.suggestText, { color: suggestInk }]} numberOfLines={2}>
                {s.label}
              </Text>
            </Pressable>
          ))}
        </View>
      ) : null}
    </View>
  );
}

export function NexaLocateButton({
  loading,
  onPress,
  accessibilityLabel,
}: {
  loading?: boolean;
  onPress: () => void;
  accessibilityLabel: string;
}) {
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  return (
    <Pressable
      onPress={onPress}
      disabled={loading}
      hitSlop={8}
      style={stylesLocate.btn}
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel}
    >
      {loading ? (
        <ActivityIndicator size="small" color={theme.color.blueBright} />
      ) : (
        <Ionicons
          name="locate-outline"
          size={PIN_ICON}
          color={light ? theme.color.blueBright : '#FFFFFF'}
        />
      )}
    </Pressable>
  );
}

const stylesLocate = StyleSheet.create({
  btn: {
    width: 28,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
  },
});

function makeStyles(light: boolean, variant: Variant) {
  const dropoff = variant === 'dropoff';
  const ink = dropoff ? theme.color.ink : nexaSurfaceText(light);
  const muted = dropoff ? theme.color.inkMuted : nexaSurfaceMuted(light);
  return StyleSheet.create({
    wrap: {
      marginBottom: 14,
    },
    card: {
      marginBottom: 0,
      borderRadius: 24,
      borderWidth: 1.5,
      ...(dropoff
        ? {
            backgroundColor: light ? '#FFFFFF' : '#FFFFFF',
            borderWidth: 1.5,
            borderColor: light ? 'rgba(8, 125, 255, 0.28)' : 'transparent',
            shadowColor: light ? '#087DFF' : '#000',
            shadowOpacity: light ? 0.08 : 0.35,
            shadowRadius: 10,
            shadowOffset: { width: 0, height: 4 },
            elevation: 4,
          }
        : {
            backgroundColor: light ? '#FFFFFF' : 'rgba(16, 37, 66, 0.92)',
            borderColor: light ? 'rgba(15, 132, 255, 0.35)' : 'rgba(53, 182, 255, 0.55)',
            shadowColor: light ? '#0F84FF' : '#35B6FF',
            shadowOpacity: light ? 0.12 : 0.35,
            shadowRadius: 12,
            shadowOffset: { width: 0, height: 0 },
            elevation: 5,
          }),
    },
    cardDropoffFocus: {
      borderWidth: 1.5,
      borderColor: light ? 'rgba(255, 138, 0, 0.55)' : 'rgba(255, 162, 26, 0.85)',
    },
    cardInner: {
      paddingHorizontal: 14,
      paddingVertical: 14,
      backgroundColor: 'transparent',
      borderRadius: 24,
    },
    row: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
    },
    pin: {
      width: 40,
      height: 40,
      borderRadius: 20,
      alignItems: 'center',
      justifyContent: 'center',
    },
    mid: {
      flex: 1,
      minWidth: 0,
    },
    label: {
      color: muted,
      fontSize: 12,
      fontWeight: '700',
      marginBottom: 2,
    },
    input: {
      color: ink,
      fontSize: 16,
      fontWeight: '700',
      padding: 0,
      margin: 0,
      minHeight: 22,
    },
    actions: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 0,
      marginRight: -4,
    },
    iconBtn: {
      width: 28,
      height: 36,
      alignItems: 'center',
      justifyContent: 'center',
    },
    error: {
      color: theme.color.danger,
      fontSize: 13,
      fontWeight: '600',
      marginTop: 6,
      marginLeft: 4,
    },
    suggestBox: {
      marginTop: 8,
      borderRadius: theme.radius.md,
      borderWidth: 1,
      borderColor: light ? 'rgba(15,132,255,0.28)' : 'rgba(53,182,255,0.45)',
      backgroundColor: light ? '#FFFFFF' : '#102542',
      overflow: 'hidden',
    },
    suggestRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
      paddingHorizontal: 12,
      paddingVertical: 13,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: light ? 'rgba(15,23,42,0.08)' : 'rgba(185,203,228,0.18)',
    },
    suggestFirst: {
      borderTopWidth: 0,
    },
    suggestText: {
      flex: 1,
      fontSize: 14,
      fontWeight: '700',
      lineHeight: 20,
    },
  });
}
