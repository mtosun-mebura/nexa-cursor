import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Image,
  ImageBackground,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { AppModal } from '../../ui/AppModal';
import { t, useI18n } from '../../i18n';
import { useTheme } from '../../theme/ThemeContext';
import { nexaBookingTheme as theme } from './theme';

const cardBase = require('../../../assets/nexa-booking/crops/nexa-card-base-dark.png');

export type RideChatMessage = {
  id: string;
  from: 'customer' | 'driver' | 'system';
  body: string;
  at: string;
};

type Props = {
  visible: boolean;
  onClose: () => void;
  /** Peer display name (chauffeur of klant). */
  driverName?: string | null;
  /** Optionele peer-foto (chauffeurfoto bij klant-chat). */
  peerPhotoUrl?: string | null;
  /** Wie “jij” bent in deze chat (bepaalt bubble-uitlijning). */
  selfFrom?: 'customer' | 'driver';
  messages: RideChatMessage[];
  sending?: boolean;
  onSend: (body: string) => void | Promise<void>;
  readOnly?: boolean;
};

export function RideChatModal({
  visible,
  onClose,
  driverName,
  peerPhotoUrl,
  selfFrom = 'customer',
  messages,
  sending,
  onSend,
  readOnly,
}: Props) {
  useI18n();
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  const styles = useMemo(() => makeStyles(light), [light]);
  const [draft, setDraft] = useState('');
  const [photoFailed, setPhotoFailed] = useState(false);
  const listRef = useRef<FlatList<RideChatMessage>>(null);
  const inputRef = useRef<TextInput>(null);
  const photoUri = String(peerPhotoUrl || '').trim();
  const showPeerPhoto = photoUri !== '' && !photoFailed;

  const scrollToLatest = (animated = false) => {
    requestAnimationFrame(() => {
      listRef.current?.scrollToEnd({ animated });
    });
  };

  useEffect(() => {
    setPhotoFailed(false);
  }, [photoUri, visible]);

  useEffect(() => {
    if (!visible) {
      setDraft('');
      return;
    }
    // Na modal-animatie: focus + laatste berichten zichtbaar.
    const focusTmr = setTimeout(() => {
      if (!readOnly) inputRef.current?.focus();
      scrollToLatest(false);
    }, 350);
    const scrollTmr = setTimeout(() => scrollToLatest(false), 120);
    const scrollAgain = setTimeout(() => scrollToLatest(true), 500);
    return () => {
      clearTimeout(focusTmr);
      clearTimeout(scrollTmr);
      clearTimeout(scrollAgain);
    };
  }, [visible, readOnly]);

  useEffect(() => {
    if (!visible || messages.length === 0) return;
    scrollToLatest(messages.length > 1);
  }, [visible, messages.length]);

  async function submit() {
    const body = draft.trim();
    if (!body || sending || readOnly) return;
    setDraft('');
    try {
      await onSend(body);
    } finally {
      // Send-knop / re-render mag focus niet stelen — gelijk verder typen.
      requestAnimationFrame(() => inputRef.current?.focus());
      setTimeout(() => inputRef.current?.focus(), 50);
    }
  }

  return (
    <AppModal
      visible={visible}
      onRequestClose={onClose}
      placement="center"
      scrollEnabled={false}
      avoidKeyboard
      panelStyle={{
        width: '100%',
        maxWidth: 440,
        borderRadius: theme.radius.card,
        borderWidth: 0,
        backgroundColor: 'transparent',
        overflow: 'visible',
        paddingHorizontal: 0,
        paddingTop: 0,
        paddingBottom: 0,
      }}
    >
      <View style={styles.wrap}>
        <View style={styles.card}>
          <LinearGradient
            colors={
              light
                ? ['#F8FBFF', '#E8F1FF', '#D6E8FF', '#E8F1FF', '#F8FBFF']
                : ['#030914', '#0A1C38', '#1A4578', '#0A1C38', '#030914']
            }
            locations={[0, 0.22, 0.5, 0.78, 1]}
            start={{ x: 0.5, y: 0 }}
            end={{ x: 0.5, y: 1 }}
            style={styles.fillBleed}
            pointerEvents="none"
          />
          <View style={styles.fillBleed} pointerEvents="none">
            <ImageBackground
              source={cardBase}
              style={StyleSheet.absoluteFill}
              imageStyle={[styles.fillImg, light ? { opacity: 0.28 } : null]}
            />
          </View>
          {light ? (
            <LinearGradient
              colors={[
                'rgba(248,251,255,0.72)',
                'rgba(232,241,255,0.55)',
                'rgba(248,251,255,0.78)',
              ]}
              locations={[0, 0.5, 1]}
              style={styles.fillBleed}
              pointerEvents="none"
            />
          ) : null}

          <View style={styles.header}>
            {showPeerPhoto ? (
              <View style={styles.headerPhotoWrap}>
                <Image
                  source={{ uri: photoUri }}
                  style={styles.headerPhoto}
                  onError={() => setPhotoFailed(true)}
                  accessibilityIgnoresInvertColors
                />
              </View>
            ) : (
              <View style={styles.headerIcon}>
                <Ionicons name="chatbubble-ellipses" size={18} color="#fff" />
              </View>
            )}
            <View style={{ flex: 1, minWidth: 0 }}>
              <Text style={styles.title}>{t('Chat')}</Text>
              <Text style={styles.sub} numberOfLines={1}>
                {driverName
                  ? t('Met {name}', { name: driverName })
                  : selfFrom === 'driver'
                    ? t('Met je klant')
                    : t('Met je chauffeur')}
              </Text>
            </View>
            <Pressable
              onPress={onClose}
              hitSlop={10}
              style={styles.closeBtn}
              accessibilityRole="button"
              accessibilityLabel={t('Sluiten')}
            >
              <Ionicons name="close" size={22} color={light ? '#0B1220' : '#FFFFFF'} />
            </Pressable>
          </View>

          <FlatList
            ref={listRef}
            data={messages}
            keyExtractor={(item) => item.id}
            style={styles.listFlex}
            contentContainerStyle={[
              styles.list,
              messages.length === 0 ? styles.listGrow : null,
            ]}
            keyboardShouldPersistTaps="handled"
            onContentSizeChange={() => {
              if (visible && messages.length > 0) scrollToLatest(false);
            }}
            onLayout={() => {
              if (visible && messages.length > 0) scrollToLatest(false);
            }}
            ListEmptyComponent={
              <View style={styles.emptyWrap}>
                <View style={styles.emptyIcon}>
                  <Ionicons
                    name="lock-closed"
                    size={18}
                    color={light ? theme.color.blueBright : theme.color.lightBlue}
                  />
                </View>
                <Text style={styles.empty}>
                  {selfFrom === 'driver'
                    ? t('Stuur een bericht naar je klant. Privénummers blijven verborgen.')
                    : t('Stuur een bericht naar je chauffeur. Privénummers blijven verborgen.')}
                </Text>
              </View>
            }
            renderItem={({ item }) => {
              const mine = item.from === selfFrom;
              const system = item.from === 'system';
              return (
                <View
                  style={[
                    styles.bubble,
                    system && styles.bubbleSystem,
                    mine && !system && styles.bubbleMine,
                    !mine && !system && styles.bubbleTheirs,
                  ]}
                >
                  <Text
                    style={[
                      styles.bubbleText,
                      system && styles.bubbleTextSystem,
                      mine && !system && styles.bubbleTextMine,
                    ]}
                  >
                    {item.body}
                  </Text>
                </View>
              );
            }}
          />

          {readOnly ? (
            <Text style={styles.readOnly}>{t('Chat is gesloten voor deze rit.')}</Text>
          ) : (
            <View style={styles.composer}>
              <TextInput
                ref={inputRef}
                style={styles.input}
                value={draft}
                onChangeText={setDraft}
                placeholder={t('Typ een bericht…')}
                placeholderTextColor={light ? '#64748B' : theme.color.textMuted}
                maxLength={2000}
                multiline
                editable={!readOnly}
                autoFocus={visible && !readOnly}
                blurOnSubmit={false}
              />
              <Pressable
                style={[styles.sendBtn, (!draft.trim() || sending) && styles.sendDisabled]}
                disabled={!draft.trim() || !!sending}
                onPress={() => {
                  void submit();
                }}
                accessibilityRole="button"
                accessibilityLabel={t('Versturen')}
              >
                {sending ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <Ionicons name="send" size={18} color="#fff" />
                )}
              </Pressable>
            </View>
          )}
        </View>
      </View>
    </AppModal>
  );
}

function makeStyles(light: boolean) {
  const text = light ? '#0B1220' : theme.color.text;
  const muted = light ? '#64748B' : theme.color.textMuted;
  const border = light ? 'rgba(8, 125, 255, 0.35)' : 'rgba(110, 190, 255, 0.65)';
  return StyleSheet.create({
    wrap: {
      width: '100%',
      height: 440,
      maxHeight: 440,
    },
    card: {
      flex: 1,
      minHeight: 0,
      borderRadius: theme.radius.card,
      borderWidth: 1.5,
      borderColor: border,
      overflow: 'hidden',
      backgroundColor: light ? '#F4F8FF' : '#0A1C38',
      paddingHorizontal: 16,
      paddingTop: 16,
      paddingBottom: 14,
    },
    fillBleed: {
      position: 'absolute',
      top: -2,
      right: -2,
      bottom: -2,
      left: -2,
    },
    fillImg: {
      resizeMode: 'cover',
    },
    header: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 12,
      marginBottom: 14,
    },
    headerIcon: {
      width: 40,
      height: 40,
      borderRadius: 12,
      backgroundColor: theme.color.blueBright,
      alignItems: 'center',
      justifyContent: 'center',
    },
    headerPhotoWrap: {
      width: 40,
      height: 40,
      borderRadius: 12,
      overflow: 'hidden',
      backgroundColor: light ? 'rgba(8, 125, 255, 0.12)' : 'rgba(255,255,255,0.12)',
      borderWidth: 1,
      borderColor: light ? 'rgba(8, 125, 255, 0.35)' : 'rgba(110, 190, 255, 0.55)',
    },
    headerPhoto: {
      width: 40,
      height: 40,
    },
    /** Integer size + solid fill — geen dunne border/shadow (voorkomt aliasing). */
    closeBtn: {
      width: 40,
      height: 40,
      borderRadius: 20,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: light ? 'rgba(15, 23, 42, 0.08)' : 'rgba(255, 255, 255, 0.14)',
    },
    title: {
      color: text,
      fontSize: 18,
      fontWeight: '800',
    },
    sub: {
      color: muted,
      fontSize: 13,
      marginTop: 2,
    },
    listFlex: {
      flexGrow: 1,
      flexShrink: 1,
    },
    list: {
      paddingVertical: 4,
      gap: 8,
      paddingBottom: 8,
    },
    listGrow: {
      flexGrow: 1,
    },
    emptyWrap: {
      alignItems: 'center',
      justifyContent: 'center',
      paddingVertical: 36,
      paddingHorizontal: 8,
      gap: 12,
    },
    emptyIcon: {
      width: 48,
      height: 48,
      borderRadius: 24,
      backgroundColor: light ? 'rgba(8, 125, 255, 0.12)' : 'rgba(8, 125, 255, 0.18)',
      alignItems: 'center',
      justifyContent: 'center',
    },
    empty: {
      color: muted,
      textAlign: 'center',
      lineHeight: 20,
      fontSize: 14,
    },
    bubble: {
      maxWidth: '84%',
      borderRadius: 16,
      paddingHorizontal: 12,
      paddingVertical: 10,
      marginBottom: 8,
    },
    bubbleMine: {
      alignSelf: 'flex-end',
      backgroundColor: theme.color.blueBright,
    },
    bubbleTheirs: {
      alignSelf: 'flex-start',
      backgroundColor: light ? '#FFFFFF' : 'rgba(21, 46, 80, 0.92)',
      borderWidth: 1,
      borderColor: light ? 'rgba(8, 125, 255, 0.2)' : 'rgba(110, 190, 255, 0.35)',
    },
    bubbleSystem: {
      alignSelf: 'center',
      backgroundColor: 'transparent',
    },
    bubbleText: {
      color: text,
      fontSize: 15,
      lineHeight: 20,
    },
    bubbleTextMine: {
      color: '#fff',
    },
    bubbleTextSystem: {
      color: muted,
      fontSize: 12,
      textAlign: 'center',
    },
    composer: {
      flexDirection: 'row',
      alignItems: 'flex-end',
      gap: 10,
      marginTop: 10,
      paddingTop: 12,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: light ? 'rgba(15, 23, 42, 0.12)' : 'rgba(110, 190, 255, 0.28)',
    },
    input: {
      flex: 1,
      minHeight: 44,
      maxHeight: 120,
      borderRadius: 14,
      borderWidth: 1.5,
      borderColor: light ? 'rgba(8, 125, 255, 0.35)' : 'rgba(110, 190, 255, 0.55)',
      backgroundColor: light ? '#FFFFFF' : 'rgba(6, 19, 41, 0.72)',
      color: text,
      paddingHorizontal: 14,
      paddingVertical: 10,
      fontSize: 15,
    },
    sendBtn: {
      width: 44,
      height: 44,
      borderRadius: 22,
      backgroundColor: theme.color.blueBright,
      alignItems: 'center',
      justifyContent: 'center',
      overflow: 'hidden',
    },
    sendDisabled: {
      opacity: 0.45,
    },
    readOnly: {
      color: muted,
      textAlign: 'center',
      marginTop: 12,
      paddingVertical: 8,
    },
  });
}
