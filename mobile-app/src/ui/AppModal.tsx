import React, { ReactNode, useEffect, useId, useMemo, useRef, useState, type RefObject } from 'react';
import {
  AccessibilityInfo,
  Animated,
  BackHandler,
  Easing,
  Keyboard,
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

const MODAL_FADE_MS = 220;
const MODAL_ZOOM_MS = 320;

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
  /** Paneel boven toetsenbord houden (default true bij center) */
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
  /**
   * fade = standaard; zoom = paneel vliegt naar voren (foto-preview e.d.).
   */
  enterAnimation?: 'fade' | 'zoom';
};

type OverlayBodyProps = AppModalProps & {
  backdropArmed: boolean;
  blurTargetRef?: RefObject<View | null> | null;
  colors: ColorPalette;
  fadeOpacity: Animated.Value;
  panelScale: Animated.Value;
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
  enterAnimation = 'fade',
  backdropArmed,
  blurTargetRef,
  colors,
  fadeOpacity,
  panelScale,
}: OverlayBodyProps) {
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const androidKeyboardOverlap = useAndroidKeyboardOverlap();
  const { height } = useWindowDimensions();
  const isSheet = placement === 'sheet';
  const wrapKeyboard = avoidKeyboard ?? !isSheet;
  const closeFromBackdrop = dismissOnBackdrop && !dismissDisabled;
  const useAndroidBlur = Platform.OS === 'android' && !!blurTargetRef;
  const zoom = enterAnimation === 'zoom' && !isSheet;
  /** Remount BlurView once so findNodeHandle resolves after BlurTargetView is attached. */
  const [blurEpoch, setBlurEpoch] = useState(0);
  /**
   * Alleen handmatige keyboard-padding (geen KeyboardAvoidingView erbij).
   * KAV + paddingBottom samen schoven het paneel naar de top en knipten knoppen af
   * (ophaalcode / chat in ModalBlurHost-portal).
   */
  const [keyboardHeight, setKeyboardHeight] = useState(0);

  useEffect(() => {
    if (!wrapKeyboard) {
      setKeyboardHeight(0);
      return;
    }
    const showEvent = Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow';
    const hideEvent = Platform.OS === 'ios' ? 'keyboardWillHide' : 'keyboardDidHide';
    const onShow = (e: { endCoordinates?: { height?: number } }) => {
      setKeyboardHeight(Math.max(0, Number(e?.endCoordinates?.height) || 0));
    };
    const onHide = () => setKeyboardHeight(0);
    const subShow = Keyboard.addListener(showEvent, onShow);
    const subHide = Keyboard.addListener(hideEvent, onHide);
    return () => {
      subShow.remove();
      subHide.remove();
    };
  }, [wrapKeyboard]);

  const keyboardPad = wrapKeyboard
    ? Math.max(androidKeyboardOverlap, keyboardHeight)
    : 0;
  /** Ruimte boven het toetsenbord — paneel blijft hierin gecentreerd. */
  const availableHeight = Math.max(220, height - keyboardPad);
  const scrollMaxHeight = Math.round(
    availableHeight * (isSheet ? 0.88 : keyboardPad > 0 ? 0.92 : 0.78)
  );

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
    <Animated.View
      style={[
        styles.root,
        isSheet ? styles.rootSheet : styles.rootCenter,
        // Alleen paddingBottom: krimpt de layout tot boven het toetsenbord.
        keyboardPad > 0 ? { paddingBottom: keyboardPad } : null,
        { opacity: fadeOpacity },
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
      {/*
        Paneel gecentreerd in de ruimte bóven het toetsenbord (geen flex-end,
        geen tweede KeyboardAvoidingView).
      */}
      <View
        pointerEvents="box-none"
        style={isSheet ? styles.sheetLayerFlex : styles.centerLayerFlex}
      >
        <Animated.View
          pointerEvents="auto"
          collapsable={false}
          style={[
            isSheet ? styles.sheet : styles.panel,
            panelStyle,
            zoom
              ? {
                  transform: [
                    { scale: panelScale },
                    {
                      translateY: panelScale.interpolate({
                        inputRange: [0.72, 1],
                        outputRange: [36, 0],
                      }),
                    },
                  ],
                }
              : null,
          ]}
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
        </Animated.View>
      </View>
    </Animated.View>
  );

  return (
    <View style={styles.fill} pointerEvents="box-none">
      {keyboardAccessory ? (
        <View style={styles.flex} pointerEvents="box-none">
          {body}
          {keyboardAccessory}
        </View>
      ) : (
        body
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
  const [rendered, setRendered] = useState(visible);
  const fadeOpacity = useRef(new Animated.Value(visible ? 1 : 0)).current;
  const panelScale = useRef(new Animated.Value(visible ? 1 : 0.72)).current;
  const reduceMotionRef = useRef(false);
  const enterAnimation = props.enterAnimation ?? 'fade';
  const useZoom = enterAnimation === 'zoom';

  useEffect(() => {
    AccessibilityInfo.isReduceMotionEnabled()
      .then((v) => {
        reduceMotionRef.current = !!v;
      })
      .catch(() => undefined);
  }, []);

  useEffect(() => {
    let cancelled = false;
    const reduce = reduceMotionRef.current;
    if (visible) {
      setRendered(true);
      fadeOpacity.setValue(0);
      panelScale.setValue(useZoom ? 0.72 : 1);
      if (reduce) {
        fadeOpacity.setValue(1);
        panelScale.setValue(1);
        return () => {
          cancelled = true;
        };
      }
      const openMs = useZoom ? MODAL_ZOOM_MS : MODAL_FADE_MS;
      Animated.parallel([
        Animated.timing(fadeOpacity, {
          toValue: 1,
          duration: openMs,
          easing: Easing.out(Easing.cubic),
          useNativeDriver: true,
        }),
        useZoom
          ? Animated.spring(panelScale, {
              toValue: 1,
              friction: 7,
              tension: 68,
              useNativeDriver: true,
            })
          : Animated.timing(panelScale, {
              toValue: 1,
              duration: openMs,
              useNativeDriver: true,
            }),
      ]).start();
      return () => {
        cancelled = true;
      };
    }
    const closeMs = reduce ? 0 : useZoom ? MODAL_ZOOM_MS : MODAL_FADE_MS;
    Animated.parallel([
      Animated.timing(fadeOpacity, {
        toValue: 0,
        duration: closeMs,
        easing: Easing.in(Easing.cubic),
        useNativeDriver: true,
      }),
      Animated.timing(panelScale, {
        toValue: useZoom ? 0.72 : 1,
        duration: closeMs,
        easing: Easing.in(Easing.cubic),
        useNativeDriver: true,
      }),
    ]).start(({ finished }) => {
      if (finished && !cancelled) setRendered(false);
    });
    return () => {
      cancelled = true;
    };
  }, [visible, fadeOpacity, panelScale, useZoom]);

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
    if (!host || !rendered || dismissDisabled) return;
    const sub = BackHandler.addEventListener('hardwareBackPress', () => {
      onRequestClose();
      return true;
    });
    return () => sub.remove();
  }, [host, rendered, dismissDisabled, onRequestClose]);

  useEffect(() => {
    if (!host) return;
    if (!rendered) {
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
        fadeOpacity={fadeOpacity}
        panelScale={panelScale}
      />
    );
    return () => host.hideOverlay(overlayKey);
  }, [
    host,
    overlayKey,
    rendered,
    backdropArmed,
    colors,
    fadeOpacity,
    panelScale,
    props.children,
    props.placement,
    props.dismissDisabled,
    props.panelStyle,
    props.avoidKeyboard,
    props.scrollEnabled,
    props.dismissOnBackdrop,
    props.showsVerticalScrollIndicator,
    props.keyboardAccessory,
    props.enterAnimation,
    props.onRequestClose,
  ]);

  // Host-pad: overlay wordt elders gerenderd.
  if (host) return null;

  // Fallback (geen ModalBlurHost): native Modal.
  if (!rendered) return null;

  return (
    <Modal
      visible
      transparent
      animationType="none"
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
        fadeOpacity={fadeOpacity}
        panelScale={panelScale}
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
    centerLayerFlex: {
      flex: 1,
      zIndex: 2,
      justifyContent: 'center',
      alignItems: 'center',
      paddingHorizontal: 24,
      width: '100%',
    },
    sheetLayerFlex: {
      flex: 1,
      zIndex: 2,
      justifyContent: 'flex-end',
      width: '100%',
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
