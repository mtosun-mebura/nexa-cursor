import React, { ReactNode, useEffect } from 'react';
import {
  BackHandler,
  Keyboard,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  useWindowDimensions,
  View,
  ViewStyle,
} from 'react-native';
import { BlurView } from 'expo-blur';
import { ColorPalette } from '../config';
import { useThemeColors } from '../theme/ThemeContext';
import { useAndroidKeyboardOverlap } from './androidText';

type AppModalProps = {
  visible: boolean;
  onRequestClose: () => void;
  children: ReactNode;
  /** center = midden scherm (standaard); sheet = onderaan (pickers / lange flows) */
  placement?: 'center' | 'sheet';
  /** Blokkeer sluiten via backdrop (bijv. tijdens busy) */
  dismissDisabled?: boolean;
  /** Extra style op het paneel */
  panelStyle?: ViewStyle;
  /** KeyboardAvoidingView om het paneel (default true bij center) */
  avoidKeyboard?: boolean;
  /** Uitzetten als de inhoud zelf scrollt, bijvoorbeeld bij bijsnijden. */
  scrollEnabled?: boolean;
  /**
   * Backdrop-tik roept onRequestClose aan. Standaard true.
   * Uit: tik op de achtergrond verbergt alleen het toetsenbord.
   */
  dismissOnBackdrop?: boolean;
  /** Verticale scrollbalk in het paneel. Standaard true. */
  showsVerticalScrollIndicator?: boolean;
  /** Buiten de scroll, in de modal (bijv. iOS InputAccessoryView). */
  keyboardAccessory?: ReactNode;
};

/**
 * Standaard app-modal: geblurde slate-overlay + paneel in hetzelfde window
 * (geen RN Modal — die blurde de achtergrond niet betrouwbaar).
 * Zie `.cursor/rules/mobile-app-modal-blur.mdc`.
 */
export function AppModal({
  visible,
  onRequestClose,
  children,
  placement = 'center',
  dismissDisabled = false,
  panelStyle,
  avoidKeyboard,
  scrollEnabled = true,
  dismissOnBackdrop = true,
  showsVerticalScrollIndicator = true,
  keyboardAccessory,
}: AppModalProps) {
  const colors = useThemeColors();
  const styles = makeStyles(colors);
  const androidKeyboardOverlap = useAndroidKeyboardOverlap();
  const { height } = useWindowDimensions();
  const isSheet = placement === 'sheet';
  const wrapKeyboard = avoidKeyboard ?? !isSheet;
  const scrollMaxHeight = Math.round(height * (isSheet ? 0.72 : 0.78));
  const closeFromBackdrop = dismissOnBackdrop && !dismissDisabled;

  useEffect(() => {
    if (!visible || dismissDisabled) return;
    const sub = BackHandler.addEventListener('hardwareBackPress', () => {
      onRequestClose();
      return true;
    });
    return () => sub.remove();
  }, [visible, dismissDisabled, onRequestClose]);

  // Niet-zichtbare overlays blijven touches stelen. Unmount volledig.
  if (!visible) return null;

  const body = (
    <View
      style={[
        styles.root,
        isSheet ? styles.rootSheet : styles.rootCenter,
        androidKeyboardOverlap > 0 ? { paddingBottom: androidKeyboardOverlap } : null,
      ]}
      pointerEvents="box-none"
    >
      <BlurView
        pointerEvents="none"
        intensity={Platform.OS === 'ios' ? 64 : 80}
        tint="dark"
        style={styles.backdrop}
      />
      <Pressable
        style={styles.scrim}
        disabled={dismissDisabled && dismissOnBackdrop}
        onPress={() => {
          if (closeFromBackdrop) {
            onRequestClose();
            return;
          }
          Keyboard.dismiss();
        }}
        {...(closeFromBackdrop
          ? { accessibilityLabel: 'Sluiten' }
          : { accessible: false, importantForAccessibility: 'no' as const })}
      />
      <View pointerEvents="box-none" style={isSheet ? styles.sheetLayer : styles.centerLayer}>
        <View
          pointerEvents="auto"
          collapsable={false}
          style={[isSheet ? styles.sheet : styles.panel, panelStyle]}
        >
          <ScrollView
            style={{ maxHeight: scrollMaxHeight }}
            scrollEnabled={scrollEnabled}
            keyboardShouldPersistTaps="handled"
            nestedScrollEnabled
            bounces={false}
            showsVerticalScrollIndicator={showsVerticalScrollIndicator}
          >
            {children}
          </ScrollView>
        </View>
      </View>
    </View>
  );

  const modalBody = wrapKeyboard ? (
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      {body}
    </KeyboardAvoidingView>
  ) : (
    body
  );

  return (
    <View style={styles.portal} pointerEvents="box-none">
      {keyboardAccessory ? (
        <View style={styles.flex} pointerEvents="box-none">
          {modalBody}
          {keyboardAccessory}
        </View>
      ) : (
        modalBody
      )}
    </View>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    flex: { flex: 1 },
    portal: {
      ...StyleSheet.absoluteFillObject,
      zIndex: 1000,
      elevation: 1000,
    },
    root: {
      flex: 1,
    },
    rootCenter: {
      justifyContent: 'center',
      alignItems: 'center',
    },
    rootSheet: {
      justifyContent: 'flex-end',
      paddingHorizontal: 0,
    },
    backdrop: {
      ...StyleSheet.absoluteFillObject,
      zIndex: 0,
    },
    scrim: {
      ...StyleSheet.absoluteFillObject,
      zIndex: 1,
      // Lichte sluier boven de blur (admin: slate-900/45) — niet pechzwart.
      backgroundColor: 'rgba(15, 23, 42, 0.4)',
    },
    centerLayer: {
      ...StyleSheet.absoluteFillObject,
      zIndex: 2,
      justifyContent: 'center',
      alignItems: 'center',
      paddingHorizontal: 24,
    },
    sheetLayer: {
      ...StyleSheet.absoluteFillObject,
      zIndex: 2,
      justifyContent: 'flex-end',
    },
    panel: {
      width: '100%',
      maxWidth: 400,
      backgroundColor: colors.card,
      borderRadius: 16,
      borderWidth: 1,
      borderColor: colors.border,
      paddingHorizontal: 16,
      paddingTop: 18,
      paddingBottom: 16,
      zIndex: 2,
      elevation: 16,
      shadowColor: '#000',
      shadowOpacity: 0.35,
      shadowRadius: 20,
      shadowOffset: { width: 0, height: 10 },
    },
    sheet: {
      width: '100%',
      backgroundColor: colors.card,
      borderTopLeftRadius: 18,
      borderTopRightRadius: 18,
      borderWidth: 1,
      borderColor: colors.border,
      zIndex: 2,
      elevation: 16,
      maxHeight: '88%',
    },
  });
}
