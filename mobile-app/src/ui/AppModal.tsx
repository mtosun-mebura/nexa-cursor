import React, { ReactNode } from 'react';
import {
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  StyleSheet,
  View,
  ViewStyle,
} from 'react-native';
import { BlurView } from 'expo-blur';
import { ColorPalette } from '../config';
import { useThemeColors } from '../theme/ThemeContext';

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
};

/**
 * Standaard app-modal: geblurde slate-overlay + paneel.
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
}: AppModalProps) {
  const colors = useThemeColors();
  const styles = makeStyles(colors);
  const isSheet = placement === 'sheet';
  const wrapKeyboard = avoidKeyboard ?? !isSheet;

  const body = (
    <View style={[styles.root, isSheet ? styles.rootSheet : styles.rootCenter]}>
      <View style={StyleSheet.absoluteFill} pointerEvents="box-none">
        <BlurView
          intensity={Platform.OS === 'ios' ? 36 : 48}
          tint="dark"
          style={StyleSheet.absoluteFill}
        />
        <Pressable
          style={styles.scrim}
          disabled={dismissDisabled}
          onPress={() => {
            if (!dismissDisabled) onRequestClose();
          }}
          accessibilityLabel="Sluiten"
        />
      </View>
      <View style={[isSheet ? styles.sheet : styles.panel, panelStyle]}>{children}</View>
    </View>
  );

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={() => {
        if (!dismissDisabled) onRequestClose();
      }}
    >
      {wrapKeyboard ? (
        <KeyboardAvoidingView
          style={styles.flex}
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        >
          {body}
        </KeyboardAvoidingView>
      ) : (
        body
      )}
    </Modal>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    flex: { flex: 1 },
    root: {
      flex: 1,
      paddingHorizontal: 24,
    },
    rootCenter: {
      justifyContent: 'center',
      alignItems: 'center',
    },
    rootSheet: {
      justifyContent: 'flex-end',
      paddingHorizontal: 0,
    },
    scrim: {
      ...StyleSheet.absoluteFill,
      backgroundColor: 'rgba(15, 23, 42, 0.45)',
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
