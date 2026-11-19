import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { t } from '../../i18n';
import { useTheme } from '../../theme/ThemeContext';

/** Knop “Ophaalcode bekijken” — CURSOR_OPHAALCODE_BUTTON.md */
export function NexaPickupCodeButton({
  onPress,
}: {
  onPress: () => void;
}) {
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';

  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [
        styles.btn,
        light ? styles.btnLight : styles.btnDark,
        pressed && styles.pressed,
      ]}
      accessibilityRole="button"
      accessibilityLabel={t('Ophaalcode bekijken')}
    >
      <Ionicons
        name="shield-checkmark"
        size={22}
        color={light ? '#C2410C' : '#F7A028'}
      />
      <Text style={[styles.label, light ? styles.labelLight : styles.labelDark]}>
        {t('Ophaalcode bekijken')}
      </Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  btn: {
    /** Zelfde hoogte als Live volgen (`liveBtn` minHeight 52). */
    minHeight: 52,
    height: 52,
    borderRadius: 16,
    borderWidth: 2,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    paddingHorizontal: 16,
  },
  btnDark: {
    backgroundColor: '#152A48',
    borderColor: '#DA8B2C',
  },
  btnLight: {
    backgroundColor: '#FFF8EE',
    borderColor: '#E9B66C',
  },
  pressed: {
    opacity: 0.88,
  },
  label: {
    fontSize: 16,
    fontWeight: '800',
  },
  labelDark: {
    color: '#FFFFFF',
  },
  labelLight: {
    color: '#0A1A34',
  },
});
