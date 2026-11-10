import React, { ReactNode, useEffect, useId, useMemo, useState, type RefObject } from 'react';
import {
  BackHandler,
  Keyboard,
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
import { useAndroidKeyboardOverlap } from './androidText';
import { useModalBlurHost } from './ModalBlurHost';

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

type OverlayBodyProps = AppModalProps & {
  backdropArmed: boolean;
  blurTargetRef?: RefObject<View | null> | null;
  colors: ColorPalette;
};

function ModalOverlayBody({
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
  backdropArmed,
  blurTargetRef,
  colors,
}: OverlayBodyProps) {
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const androidKeyboardOverlap = useAndroidKeyboardOverlap();
  const { height } = useWindowDimensions();
  const isSheet = placement === 'sheet';
  const wrapKeyboard = avoidKeyboard ?? !isSheet;
  const scrollMaxHeight = Math.round(height * (isSheet ? 0.72 : 0.78));
  const closeFromBackdrop = dismissOnBackdrop && !dismissDisabled;
  const useAndroidBlur = Platform.OS === 'android' && !!blurTargetRef;
  /** Remount BlurView once so findNodeHandle resolves after BlurTargetView is attached. */
  const [blurEpoch, setBlurEpoch] = useState(0);

  useEffect(() => {
    if (!useAndroidBlur) return;
    let cancelled = false;
    const bump = () => {
      if (!cancelled && blurTargetRef?.current) setBlurEpoch((n) => n + 1);
    };
    bump();
    const t1 = setTimeout(bump, 32);
    const t2 = setTimeout(bump, 120);
    return () => {
      cancelled = true;
      clearTimeout(t1);
      clearTimeout(t2);
    };
  }, [useAndroidBlur, blurTargetRef]);

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
        key={`blur-${blurEpoch}`}
        pointerEvents="none"
        // tint=dark op hoge intensity + aparte scrim = pechzwart; houd blur zichtbaar.
        intensity={Platform.OS === 'ios' ? 55 : 70}
        tint="dark"
        // Android: echte blur alleen met blurTarget (ModalBlurHost) + dimezisBlurView.
        // iOS: UIBlurEffect in dezelfde window (portal); RN Modal doet dat niet.
        {...(useAndroidBlur
          ? {
              blurMethod: 'dimezisBlurView' as const,
              blurTarget: blurTargetRef!,
              blurReductionFactor: 3,
            }
          : {})}
        style={styles.backdrop}
      />
      <Pressable
        style={styles.scrim}
        disabled={dismissDisabled && dismissOnBackdrop}
        onPress={() => {
          if (!backdropArmed) {
            Keyboard.dismiss();
            return;
          }
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
    <View style={styles.fill} pointerEvents="box-none">
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

/**
 * Standaard app-modal: geblurde overlay + paneel.
 * Rendert via ModalBlurHost (sibling van BlurTargetView) zodat Android én iOS wél blurben;
 * zonder host: native Modal-fallback (iOS/Android dan alleen scrim, weinig/geen blur).
 * Zie `.cursor/rules/mobile-app-modal-blur.mdc`.
 */
export function AppModal(props: AppModalProps) {
  const { visible, onRequestClose, dismissDisabled = false } = props;
  const colors = useThemeColors();
  const host = useModalBlurHost();
  const overlayKey = useId();
  /** Voorkom dat de openingstik meteen de backdrop raakt. */
  const [backdropArmed, setBackdropArmed] = useState(false);

  useEffect(() => {
    if (!visible) {
      setBackdropArmed(false);
      return;
    }
    const timer = setTimeout(() => setBackdropArmed(true), 280);
    return () => clearTimeout(timer);
  }, [visible]);

  // Android terug-knop bij portal-overlay (geen native Modal).
  useEffect(() => {
    if (!host || !visible || dismissDisabled) return;
    const sub = BackHandler.addEventListener('hardwareBackPress', () => {
      onRequestClose();
      return true;
    });
    return () => sub.remove();
  }, [host, visible, dismissDisabled, onRequestClose]);

  useEffect(() => {
    if (!host) return;
    if (!visible) {
      host.hideOverlay(overlayKey);
      return;
    }
    host.showOverlay(
      overlayKey,
      <ModalOverlayBody
        {...props}
        visible
        backdropArmed={backdropArmed}
        blurTargetRef={host.blurTargetRef}
        colors={colors}
      />
    );
    return () => host.hideOverlay(overlayKey);
  }, [
    host,
    overlayKey,
    visible,
    backdropArmed,
    colors,
    props.children,
    props.placement,
    props.dismissDisabled,
    props.panelStyle,
    props.avoidKeyboard,
    props.scrollEnabled,
    props.dismissOnBackdrop,
    props.showsVerticalScrollIndicator,
    props.keyboardAccessory,
    props.onRequestClose,
  ]);

  // Host-pad: overlay wordt elders gerenderd.
  if (host) return null;

  // Fallback (geen ModalBlurHost): native Modal.
  if (!visible) return null;

  return (
    <Modal
      visible
      transparent
      animationType="fade"
      presentationStyle="overFullScreen"
      statusBarTranslucent
      onRequestClose={() => {
        if (!dismissDisabled) onRequestClose();
      }}
    >
      <ModalOverlayBody
        {...props}
        visible
        backdropArmed={backdropArmed}
        blurTargetRef={null}
        colors={colors}
      />
    </Modal>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    flex: { flex: 1 },
    fill: { flex: 1 },
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
      // Lichte sluier bovenop BlurView-tint (niet stapelen tot pechzwart).
      backgroundColor: 'rgba(15, 23, 42, 0.28)',
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
