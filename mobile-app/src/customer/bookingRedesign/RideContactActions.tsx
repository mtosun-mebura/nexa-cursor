import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { t } from '../../i18n';
import { nexaBookingTheme as theme } from './theme';

type Props = {
  canCommunicate: boolean;
  onCall: () => void;
  onChat: () => void;
  onShare?: () => void;
  busy?: boolean;
};

/**
 * Bel / Chat / Deel — toont nooit een telefoonnummer.
 * Bel gebruikt de centrale-relay (company phone) via parent-callback.
 */
export function RideContactActions({
  canCommunicate,
  onCall,
  onChat,
  onShare,
  busy,
}: Props) {
  const disabled = !canCommunicate || !!busy;
  return (
    <View style={styles.row}>
      <Pressable
        style={[styles.btn, styles.btnCall, disabled && styles.btnDisabled]}
        disabled={disabled}
        onPress={onCall}
        accessibilityRole="button"
        accessibilityLabel={t('Bel chauffeur')}
        accessibilityHint={
          disabled
            ? t('Beschikbaar zodra een chauffeur is gekoppeld')
            : t('Belt via de centrale, zonder privénummer')
        }
      >
        <Ionicons name="call" size={18} color="#fff" />
        <Text style={styles.btnText}>{t('Bel')}</Text>
      </Pressable>
      <Pressable
        style={[styles.btn, styles.btnChat, disabled && styles.btnDisabled]}
        disabled={disabled}
        onPress={onChat}
        accessibilityRole="button"
        accessibilityLabel={t('Chat')}
      >
        <Ionicons name="chatbubble-ellipses" size={18} color="#fff" />
        <Text style={styles.btnText}>{t('Chat')}</Text>
      </Pressable>
      {onShare ? (
        <Pressable
          style={[styles.btn, styles.btnShare, disabled && styles.btnDisabled]}
          disabled={disabled}
          onPress={onShare}
          accessibilityRole="button"
          accessibilityLabel={t('Deel rit')}
        >
          <Ionicons name="share-outline" size={18} color={theme.color.navy900} />
          <Text style={[styles.btnText, styles.btnShareText]}>{t('Deel')}</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 12,
  },
  btn: {
    flex: 1,
    minHeight: theme.touchTargetMin,
    borderRadius: theme.radius.md,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingHorizontal: 10,
  },
  btnCall: {
    backgroundColor: theme.color.orangeHot,
  },
  btnChat: {
    backgroundColor: theme.color.blueBright,
  },
  btnShare: {
    backgroundColor: '#E8EEF7',
  },
  btnDisabled: {
    opacity: 0.45,
  },
  btnText: {
    color: '#fff',
    fontWeight: '800',
    fontSize: 14,
  },
  btnShareText: {
    color: theme.color.navy900,
  },
});
