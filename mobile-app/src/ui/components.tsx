import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  ViewStyle,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { ColorPalette } from '../config';
import { useThemeColors } from '../theme/ThemeContext';
import { useOptionalDriverAccent } from '../theme/driverAccent';

export function Screen({ children, style }: { children: React.ReactNode; style?: ViewStyle }) {
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  return <View style={[styles.screen, style]}>{children}</View>;
}

export function Title({ children }: { children: React.ReactNode }) {
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  return <Text style={styles.title}>{children}</Text>;
}

export function Subtitle({ children }: { children: React.ReactNode }) {
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  return <Text style={styles.subtitle}>{children}</Text>;
}

export function Card({ children }: { children: React.ReactNode }) {
  const colors = useThemeColors();
  const accent = useOptionalDriverAccent();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  return (
    <View style={[styles.card, accent ? { borderColor: accent.border } : null]}>{children}</View>
  );
}

export function Field({
  label,
  value,
  onChangeText,
  secureTextEntry,
  keyboardType,
  autoCapitalize,
  placeholder,
  multiline,
  rightAccessory,
  confirmed,
  inputRef,
  returnKeyType,
  onSubmitEditing,
  error,
}: {
  label: string;
  value: string;
  onChangeText: (v: string) => void;
  secureTextEntry?: boolean;
  keyboardType?: 'default' | 'email-address' | 'number-pad' | 'phone-pad';
  autoCapitalize?: 'none' | 'sentences' | 'words';
  placeholder?: string;
  multiline?: boolean;
  rightAccessory?: React.ReactNode;
  confirmed?: boolean;
  inputRef?: React.RefObject<TextInput | null>;
  returnKeyType?: 'done' | 'next' | 'search' | 'go';
  onSubmitEditing?: () => void;
  error?: string | null;
}) {
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const [hideError, setHideError] = useState(false);
  const [passwordVisible, setPasswordVisible] = useState(false);

  useEffect(() => {
    setHideError(false);
  }, [error]);

  const showError = !!error && !hideError;
  const showPasswordToggle = !!secureTextEntry;
  const accessory = rightAccessory
    ? rightAccessory
    : showPasswordToggle ? (
        <Pressable
          onPress={() => setPasswordVisible((v) => !v)}
          hitSlop={8}
          style={styles.passwordToggle}
          accessibilityRole="button"
          accessibilityLabel={passwordVisible ? 'Wachtwoord verbergen' : 'Wachtwoord tonen'}
        >
          <Ionicons
            name={passwordVisible ? 'eye-off-outline' : 'eye-outline'}
            size={22}
            color={colors.muted}
          />
        </Pressable>
      ) : null;

  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <View
        style={[
          styles.inputWrap,
          confirmed && !showError && styles.inputWrapConfirmed,
          showError && styles.inputWrapError,
        ]}
      >
        <TextInput
          ref={inputRef}
          style={[
            styles.input,
            styles.inputInWrap,
            multiline && styles.inputMultiline,
            !!accessory && styles.inputWithAccessory,
          ]}
          value={value}
          onChangeText={(v) => {
            if (showError) setHideError(true);
            onChangeText(v);
          }}
          secureTextEntry={!!secureTextEntry && !passwordVisible}
          keyboardType={keyboardType}
          autoCapitalize={autoCapitalize}
          placeholder={placeholder}
          placeholderTextColor={colors.muted}
          autoCorrect={false}
          autoComplete="off"
          textContentType="none"
          blurOnSubmit={false}
          returnKeyType={returnKeyType}
          onSubmitEditing={onSubmitEditing}
          multiline={multiline}
          textAlignVertical={multiline ? 'top' : 'center'}
          aria-invalid={showError}
        />
        {confirmed && !showError ? <Text style={styles.checkMark} pointerEvents="none">✓</Text> : null}
        {accessory ? (
          <View style={styles.accessory} pointerEvents="box-none">
            {accessory}
          </View>
        ) : null}
      </View>
      {showError ? <Text style={styles.fieldError}>{error}</Text> : null}
    </View>
  );
}

export function PrimaryButton({
  title,
  onPress,
  loading,
  disabled,
}: {
  title: string;
  onPress: () => void;
  loading?: boolean;
  disabled?: boolean;
}) {
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled || loading}
      style={({ pressed }) => [
        styles.primaryBtn,
        (disabled || loading) && styles.btnDisabled,
        pressed && !disabled && !loading && styles.primaryPressed,
      ]}
    >
      {loading ? (
        <ActivityIndicator color="#fff" />
      ) : (
        <Text style={styles.primaryBtnText}>{title}</Text>
      )}
    </Pressable>
  );
}

export function GhostButton({
  title,
  onPress,
  danger,
}: {
  title: string;
  onPress: () => void;
  danger?: boolean;
}) {
  const colors = useThemeColors();
  const accent = useOptionalDriverAccent();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  return (
    <Pressable
      onPress={onPress}
      style={[styles.ghostBtn, accent && { borderColor: accent.border }, danger && styles.dangerBtn]}
    >
      <Text style={[styles.ghostBtnText, danger && styles.dangerBtnText]}>{title}</Text>
    </Pressable>
  );
}

export function ErrorText({ children }: { children?: string | null }) {
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  if (!children) return null;
  return <Text style={styles.error}>{children}</Text>;
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    screen: {
      flex: 1,
      backgroundColor: colors.bg,
      paddingHorizontal: 20,
      paddingTop: 24,
      paddingBottom: 28,
    },
    title: {
      color: colors.text,
      fontSize: 28,
      fontWeight: '700',
      marginBottom: 8,
    },
    subtitle: {
      color: colors.muted,
      fontSize: 15,
      lineHeight: 22,
      marginBottom: 22,
    },
    card: {
      backgroundColor: colors.card,
      borderColor: colors.border,
      borderWidth: 1,
      borderRadius: 18,
      padding: 16,
      marginBottom: 12,
    },
    field: { marginBottom: 14 },
    label: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '600',
      marginBottom: 6,
    },
    input: {
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 12,
      paddingHorizontal: 14,
      paddingVertical: 12,
      color: colors.text,
      fontSize: 16,
      backgroundColor: colors.inputBg,
    },
    inputWrap: {
      flexDirection: 'row',
      alignItems: 'center',
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 12,
      backgroundColor: colors.inputBg,
      paddingRight: 6,
    },
    inputWrapConfirmed: {
      borderColor: colors.border,
    },
    inputWrapError: {
      borderColor: colors.danger,
    },
    fieldError: {
      color: colors.danger,
      fontSize: 13,
      marginTop: 6,
      marginBottom: 0,
    },
    inputInWrap: {
      flex: 1,
      minWidth: 0,
      borderWidth: 0,
      backgroundColor: 'transparent',
      marginBottom: 0,
    },
    inputWithAccessory: {
      paddingRight: 4,
    },
    accessory: {
      marginLeft: 2,
    },
    passwordToggle: {
      paddingHorizontal: 8,
      paddingVertical: 8,
      justifyContent: 'center',
      alignItems: 'center',
    },
    checkMark: {
      color: colors.success,
      fontSize: 18,
      fontWeight: '700',
      paddingHorizontal: 6,
    },
    inputMultiline: {
      minHeight: 88,
    },
    primaryBtn: {
      backgroundColor: colors.primary,
      borderRadius: 14,
      paddingVertical: 14,
      alignItems: 'center',
      marginTop: 8,
    },
    primaryPressed: { backgroundColor: colors.primaryPressed },
    primaryBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
    btnDisabled: { opacity: 0.55 },
    ghostBtn: {
      borderRadius: 14,
      paddingVertical: 14,
      alignItems: 'center',
      marginTop: 8,
      borderWidth: 1,
      borderColor: colors.border,
    },
    ghostBtnText: { color: colors.muted, fontSize: 15, fontWeight: '600' },
    dangerBtn: {
      backgroundColor: 'transparent',
      borderColor: colors.danger,
    },
    dangerBtnText: {
      color: colors.danger,
      fontWeight: '700',
    },
    error: { color: colors.danger, marginTop: 8, marginBottom: 4, fontSize: 14 },
  });
}
