import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useI18n } from '../i18n';
import { useTheme } from '../theme/ThemeContext';

type Props = {
  /** On a dark hero (start screen) the icons stay light. */
  tone?: 'theme' | 'onDark';
};

/**
 * One theme icon and one flag: the choice you can switch to.
 * Light shows the moon, dark shows the sun. Dutch shows the English flag.
 * Does not change the iOS home-screen icon.
 */
export function AppearanceControls({ tone = 'theme' }: Props) {
  const { colorScheme, setPreference, colors } = useTheme();
  const { locale, setLocale, t } = useI18n();
  const onDark = tone === 'onDark';
  const icon = onDark ? '#ffffff' : colors.text;
  const toDark = colorScheme === 'light';

  return (
    <View style={styles.row}>
      <Pressable
        onPress={() => setPreference(toDark ? 'dark' : 'light', { syncIcon: false })}
        hitSlop={6}
        accessibilityRole="button"
        accessibilityLabel={toDark ? t('Donker') : t('Licht')}
        style={styles.hit}
      >
        <Ionicons name={toDark ? 'moon' : 'sunny'} size={20} color={icon} />
      </Pressable>
      <Pressable
        onPress={() => setLocale(locale === 'nl' ? 'en' : 'nl')}
        hitSlop={6}
        accessibilityRole="button"
        accessibilityLabel={locale === 'nl' ? 'English' : 'Nederlands'}
        style={styles.hit}
      >
        <Text style={styles.flag}>{locale === 'nl' ? '🇬🇧' : '🇳🇱'}</Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  hit: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
  },
  flag: {
    fontSize: 18,
    lineHeight: 22,
  },
});
