import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  AccessibilityInfo,
  Animated,
  Easing,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import type { DriverInboxNotification } from '../api/driver';
import { ColorPalette } from '../config';
import { t } from '../i18n';
import { useThemeColors } from '../theme/ThemeContext';
import { AppModal } from './AppModal';
import { GhostButton } from './components';

type Props = {
  visible: boolean;
  busy?: boolean;
  notifications: DriverInboxNotification[];
  accentColor: string;
  onRequestClose: () => void;
  onOpen: (item: DriverInboxNotification) => void | Promise<void>;
  onArchive: (item: DriverInboxNotification) => void | Promise<void>;
  dateLabel: (iso?: string | null) => string;
};

type RowProps = {
  item: DriverInboxNotification;
  focused: boolean;
  hidden: boolean;
  accentColor: string;
  colors: ColorPalette;
  dateLabel: (iso?: string | null) => string;
  onPress: () => void;
  onCloseFocus: () => void;
  onArchive: () => void;
  archiving: boolean;
  onArchiveAnimDone: () => void;
};

function NotificationRow({
  item,
  focused,
  hidden,
  accentColor,
  colors,
  dateLabel,
  onPress,
  onCloseFocus,
  onArchive,
  archiving,
  onArchiveAnimDone,
}: RowProps) {
  const styles = useMemo(() => makeRowStyles(colors), [colors]);
  const opacity = useRef(new Animated.Value(1)).current;
  const translateY = useRef(new Animated.Value(0)).current;
  const translateX = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(1)).current;
  const rotate = useRef(new Animated.Value(0)).current;
  const packageOpacity = useRef(new Animated.Value(0)).current;
  const reduceMotion = useRef(false);

  useEffect(() => {
    AccessibilityInfo.isReduceMotionEnabled()
      .then((v) => {
        reduceMotion.current = !!v;
      })
      .catch(() => undefined);
  }, []);

  useEffect(() => {
    const dur = reduceMotion.current ? 0 : focused ? 220 : 180;
    Animated.parallel([
      Animated.timing(opacity, {
        toValue: hidden ? 0 : 1,
        duration: dur,
        easing: Easing.out(Easing.cubic),
        useNativeDriver: true,
      }),
      Animated.timing(translateY, {
        toValue: hidden ? -8 : 0,
        duration: dur,
        easing: Easing.out(Easing.cubic),
        useNativeDriver: true,
      }),
    ]).start();
  }, [hidden, focused, opacity, translateY]);

  useEffect(() => {
    if (!archiving) return;
    if (reduceMotion.current) {
      onArchiveAnimDone();
      return;
    }
    packageOpacity.setValue(1);
    Animated.parallel([
      Animated.timing(opacity, {
        toValue: 0,
        duration: 420,
        easing: Easing.in(Easing.cubic),
        useNativeDriver: true,
      }),
      Animated.timing(translateY, {
        toValue: -56,
        duration: 420,
        easing: Easing.in(Easing.cubic),
        useNativeDriver: true,
      }),
      Animated.timing(translateX, {
        toValue: 48,
        duration: 420,
        easing: Easing.in(Easing.cubic),
        useNativeDriver: true,
      }),
      Animated.timing(scale, {
        toValue: 0.72,
        duration: 420,
        easing: Easing.in(Easing.cubic),
        useNativeDriver: true,
      }),
      Animated.timing(rotate, {
        toValue: 1,
        duration: 420,
        easing: Easing.in(Easing.cubic),
        useNativeDriver: true,
      }),
      Animated.sequence([
        Animated.timing(packageOpacity, {
          toValue: 1,
          duration: 80,
          useNativeDriver: true,
        }),
        Animated.timing(packageOpacity, {
          toValue: 0,
          duration: 280,
          delay: 60,
          useNativeDriver: true,
        }),
      ]),
    ]).start(({ finished }) => {
      if (finished) onArchiveAnimDone();
    });
  }, [
    archiving,
    onArchiveAnimDone,
    opacity,
    packageOpacity,
    rotate,
    scale,
    translateX,
    translateY,
  ]);

  const spin = rotate.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '-12deg'],
  });

  return (
    <Animated.View
      style={[
        styles.wrap,
        {
          opacity,
          transform: [{ translateX }, { translateY }, { scale }, { rotate: spin }],
          borderColor: !item.is_read || focused ? accentColor : colors.border,
        },
        hidden ? styles.hiddenSlot : null,
      ]}
      pointerEvents={hidden ? 'none' : 'auto'}
    >
      <Animated.View
        pointerEvents="none"
        style={[styles.packageBadge, { opacity: packageOpacity }]}
      >
        <Ionicons name="cube-outline" size={28} color={accentColor} />
      </Animated.View>
      <Pressable
        onPress={focused ? undefined : onPress}
        disabled={focused || archiving}
        style={styles.press}
      >
        <View style={styles.head}>
          <Text style={styles.title}>{item.title}</Text>
          <Text style={styles.date}>{dateLabel(item.created_at)}</Text>
        </View>
        <Text style={styles.body} numberOfLines={focused ? undefined : 3}>
          {item.body}
        </Text>
        {!item.is_read && !focused ? (
          <Text style={styles.unread}>{t('Ongelezen')}</Text>
        ) : null}
      </Pressable>
      {focused && !archiving ? (
        <View style={styles.actions}>
          <View style={styles.actionSlot}>
            <Pressable onPress={onCloseFocus} style={styles.secondaryBtn}>
              <Text style={styles.secondaryText}>{t('Sluiten')}</Text>
            </Pressable>
          </View>
          <View style={styles.actionSlot}>
            <Pressable
              onPress={onArchive}
              style={[styles.primaryBtn, { backgroundColor: accentColor }]}
            >
              <Text style={styles.primaryText}>{t('Archiveren')}</Text>
            </Pressable>
          </View>
        </View>
      ) : null}
    </Animated.View>
  );
}

export function DriverNotificationsModal({
  visible,
  busy = false,
  notifications,
  accentColor,
  onRequestClose,
  onOpen,
  onArchive,
  dateLabel,
}: Props) {
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const [focusedId, setFocusedId] = useState<number | null>(null);
  const [archivingId, setArchivingId] = useState<number | null>(null);
  const [focusSettled, setFocusSettled] = useState(false);
  const focusTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    if (!visible) {
      setFocusedId(null);
      setArchivingId(null);
      setFocusSettled(false);
      if (focusTimer.current) clearTimeout(focusTimer.current);
    }
  }, [visible]);

  useEffect(() => {
    return () => {
      if (focusTimer.current) clearTimeout(focusTimer.current);
    };
  }, []);

  const shownNotifications =
    focusSettled && focusedId != null
      ? notifications.filter((row) => row.id === focusedId || row.id === archivingId)
      : notifications;

  function clearFocus() {
    if (focusTimer.current) clearTimeout(focusTimer.current);
    setFocusSettled(false);
    setFocusedId(null);
  }

  return (
    <AppModal
      visible={visible}
      onRequestClose={() => {
        if (archivingId) return;
        if (focusedId) {
          clearFocus();
          return;
        }
        onRequestClose();
      }}
      panelStyle={styles.panel}
      showsVerticalScrollIndicator={false}
    >
      <Text style={styles.pickerTitle}>
        {focusedId ? t('Melding') : t('Meldingen')}
      </Text>
      {busy ? <Text style={styles.hint}>{t('Laden…')}</Text> : null}
      {!busy && notifications.length === 0 ? (
        <Text style={styles.hint}>{t('Geen openstaande meldingen.')}</Text>
      ) : null}
      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.scrollContent}
        nestedScrollEnabled
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        {shownNotifications.map((item) => (
          <NotificationRow
            key={item.id}
            item={item}
            focused={focusedId === item.id}
            hidden={focusedId != null && focusedId !== item.id && !focusSettled}
            archiving={archivingId === item.id}
            accentColor={accentColor}
            colors={colors}
            dateLabel={dateLabel}
            onPress={() => {
              void onOpen(item);
              setFocusSettled(false);
              setFocusedId(item.id);
              if (focusTimer.current) clearTimeout(focusTimer.current);
              focusTimer.current = setTimeout(() => setFocusSettled(true), 200);
            }}
            onCloseFocus={clearFocus}
            onArchive={() => {
              if (archivingId) return;
              setArchivingId(item.id);
            }}
            onArchiveAnimDone={() => {
              void (async () => {
                try {
                  await onArchive(item);
                } finally {
                  setArchivingId(null);
                  clearFocus();
                }
              })();
            }}
          />
        ))}
      </ScrollView>
      {!focusedId ? <GhostButton title={t('Sluiten')} onPress={onRequestClose} /> : null}
    </AppModal>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    panel: {
      maxWidth: 420,
      width: '100%',
    },
    pickerTitle: {
      color: colors.text,
      fontSize: 18,
      fontWeight: '700',
      marginBottom: 10,
    },
    hint: {
      color: colors.muted,
      fontSize: 14,
      marginBottom: 8,
    },
    scroll: {
      maxHeight: 420,
      marginBottom: 8,
    },
    scrollContent: {
      gap: 10,
      paddingBottom: 4,
    },
  });
}

function makeRowStyles(colors: ColorPalette) {
  return StyleSheet.create({
    wrap: {
      borderWidth: 1,
      borderRadius: 14,
      backgroundColor: colors.card,
      overflow: 'hidden',
      position: 'relative',
    },
    press: {
      paddingHorizontal: 14,
      paddingVertical: 12,
    },
    head: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: 10,
      marginBottom: 6,
    },
    title: {
      flex: 1,
      color: colors.text,
      fontSize: 15,
      fontWeight: '700',
    },
    date: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '600',
    },
    body: {
      color: colors.muted,
      fontSize: 14,
      lineHeight: 20,
    },
    unread: {
      color: colors.muted,
      fontSize: 12,
      marginTop: 6,
    },
    actions: {
      flexDirection: 'row',
      gap: 10,
      paddingHorizontal: 14,
      paddingBottom: 12,
    },
    actionSlot: {
      flex: 1,
      minWidth: 0,
    },
    secondaryBtn: {
      borderRadius: 12,
      borderWidth: 1,
      borderColor: colors.border,
      paddingVertical: 12,
      alignItems: 'center',
      justifyContent: 'center',
    },
    secondaryText: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '700',
    },
    primaryBtn: {
      borderRadius: 12,
      paddingVertical: 12,
      alignItems: 'center',
      justifyContent: 'center',
    },
    primaryText: {
      color: '#fff',
      fontSize: 15,
      fontWeight: '700',
    },
    packageBadge: {
      position: 'absolute',
      top: 10,
      right: 14,
      zIndex: 2,
    },
    hiddenSlot: {
      position: 'absolute',
      left: 0,
      right: 0,
      zIndex: 0,
    },
  });
}
