import { Ionicons } from '@expo/vector-icons';
import React, { useEffect, useRef } from 'react';
import { Animated, Pressable, StyleSheet, Text, View } from 'react-native';
import { t } from '../../i18n';
import { nexaBookingTheme as theme } from './theme';

type Props = {
  canCommunicate: boolean;
  onCall: () => void;
  onChat: () => void;
  onShare?: () => void;
  busy?: boolean;
  /** Knipper bij ongelezen chatbericht van de chauffeur. */
  chatAttention?: boolean;
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
  chatAttention,
}: Props) {
  const disabled = !canCommunicate || !!busy;
  const chatPulse = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    if (!chatAttention) {
      chatPulse.stopAnimation();
      chatPulse.setValue(1);
      return;
    }
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(chatPulse, {
          toValue: 0.35,
          duration: 450,
          useNativeDriver: true,
        }),
        Animated.timing(chatPulse, {
          toValue: 1,
          duration: 450,
          useNativeDriver: true,
        }),
      ])
    );
    loop.start();
    return () => {
      loop.stop();
      chatPulse.setValue(1);
    };
  }, [chatAttention, chatPulse]);

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
        style={[
          styles.btn,
          chatAttention ? styles.btnChatAttention : styles.btnChat,
          disabled && styles.btnDisabled,
        ]}
        disabled={disabled}
        onPress={onChat}
        accessibilityRole="button"
        accessibilityLabel={
          chatAttention ? t('Chat — nieuw bericht') : t('Chat')
        }
      >
        <Animated.View
          style={[styles.btnInner, { opacity: chatAttention ? chatPulse : 1 }]}
        >
          <Ionicons name="chatbubble-ellipses" size={18} color="#fff" />
          <Text style={styles.btnText}>{t('Chat')}</Text>
          {chatAttention ? <View style={styles.chatDot} /> : null}
        </Animated.View>
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
  btnInner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  btnCall: {
    backgroundColor: theme.color.orangeHot,
  },
  btnChat: {
    backgroundColor: theme.color.blueBright,
  },
  btnChatAttention: {
    backgroundColor: '#0F84FF',
    borderWidth: 1.5,
    borderColor: '#FF8A00',
  },
  chatDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#FF8A00',
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
