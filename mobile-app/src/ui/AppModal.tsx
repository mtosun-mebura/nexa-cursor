import React, { ReactNode } from 'react';
import {
  KeyboardAvoidingView,
  Modal,
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
  scrollEnabled = true,
}: AppModalProps) {
  const colors = useThemeColors();
  const styles = makeStyles(colors);
  const { height } = useWindowDimensions();
  const isSheet = placement === 'sheet';
  const wrapKeyboard = avoidKeyboard ?? !isSheet;
  const scrollMaxHeight = Math.round(height * (isSheet ? 0.72 : 0.78));

  const body = (
    <View style={[styles.root, isSheet ? styles.rootSheet : styles.rootCenter]} pointerEvents="box-none">
      <BlurView
        pointerEvents="none"
        intensity={Platform.OS === 'ios' ? 36 : 48}
        tint="dark"
        style={styles.backdrop}
      />
      <Pressable
        style={styles.scrim}
        disabled={dismissDisabled}
        onPress={() => {
          if (!dismissDisabled) onRequestClose();
        }}
        accessibilityLabel="Sluiten"
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
            showsVerticalScrollIndicator
          >
            {children}
          </ScrollView>
        </View>
      </View>
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
      ...StyleSheet.absoluteFill,
      zIndex: 0,
    },
    scrim: {
      ...StyleSheet.absoluteFill,
      zIndex: 1,
      backgroundColor: 'rgba(15, 23, 42, 0.45)',
    },
    centerLayer: {
      ...StyleSheet.absoluteFill,
      zIndex: 2,
      justifyContent: 'center',
      alignItems: 'center',
      paddingHorizontal: 24,
    },
    sheetLayer: {
      ...StyleSheet.absoluteFill,
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
