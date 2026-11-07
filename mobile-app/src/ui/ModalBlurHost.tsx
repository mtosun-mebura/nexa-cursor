import React, {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useRef,
  useState,
  type ReactNode,
  type RefObject,
} from 'react';
import { StyleSheet, View } from 'react-native';
import { BlurTargetView } from 'expo-blur';

type ModalBlurHostApi = {
  blurTargetRef: RefObject<View | null>;
  showOverlay: (key: string, node: ReactNode) => void;
  hideOverlay: (key: string) => void;
};

const ModalBlurHostContext = createContext<ModalBlurHostApi | null>(null);

/**
 * Wraps app content in BlurTargetView and hosts modal overlays as siblings,
 * so Android dimezisBlurView can blur the real screen (RN Modal cannot).
 * iOS also needs same-window overlay — UIBlurEffect does not blur across Modal windows.
 */
export function ModalBlurHost({ children }: { children: ReactNode }) {
  const blurTargetRef = useRef<View | null>(null);
  const [overlays, setOverlays] = useState<Record<string, ReactNode>>({});

  const showOverlay = useCallback((key: string, node: ReactNode) => {
    setOverlays((prev) => ({ ...prev, [key]: node }));
  }, []);

  const hideOverlay = useCallback((key: string) => {
    setOverlays((prev) => {
      if (!(key in prev)) return prev;
      const next = { ...prev };
      delete next[key];
      return next;
    });
  }, []);

  const api = useMemo(
    () => ({ blurTargetRef, showOverlay, hideOverlay }),
    [showOverlay, hideOverlay]
  );

  const entries = Object.entries(overlays);

  return (
    <ModalBlurHostContext.Provider value={api}>
      <View style={styles.fill}>
        <BlurTargetView ref={blurTargetRef} style={styles.fill} collapsable={false}>
          {children}
        </BlurTargetView>
        {entries.map(([key, node]) => (
          <View key={key} style={styles.overlaySlot} pointerEvents="box-none">
            {node}
          </View>
        ))}
      </View>
    </ModalBlurHostContext.Provider>
  );
}

export function useModalBlurHost(): ModalBlurHostApi | null {
  return useContext(ModalBlurHostContext);
}

const styles = StyleSheet.create({
  fill: {
    flex: 1,
  },
  overlaySlot: {
    ...StyleSheet.absoluteFill,
    zIndex: 1000,
    elevation: 1000,
  },
});
