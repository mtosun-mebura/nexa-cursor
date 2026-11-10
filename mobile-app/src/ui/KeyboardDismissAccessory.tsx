import React, { useEffect, useRef, useState } from 'react';
import {
  Dimensions,
  InputAccessoryView,
  Keyboard,
  Platform,
  Pressable,
  StyleSheet,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useThemeColors } from '../theme/ThemeContext';

/**
 * iOS: InputAccessoryView boven het toetsenbord (één per nativeID, Fabric koppelt er maar één).
 * Android: balk direct boven het toetsenbord. Sluit de modal niet.
 */
export function KeyboardDismissAccessory({ nativeIDs }: { nativeIDs: readonly string[] }) {
  const colors = useThemeColors();
  const keyboardTopRef = useRef<number | null>(null);
  const [androidBottom, setAndroidBottom] = useState<number | null>(null);
  const [iosReady, setIosReady] = useState(Platform.OS !== 'ios');

  useEffect(() => {
    setIosReady(true);
  }, []);

  useEffect(() => {
    if (Platform.OS === 'ios') return;

    const sync = () => {
      const top = keyboardTopRef.current;
      if (top == null) {
        setAndroidBottom(null);
        return;
      }
      const windowHeight = Dimensions.get('window').height;
      setAndroidBottom(Math.max(0, Math.round(windowHeight - top)));
    };

    const show = Keyboard.addListener('keyboardDidShow', (event) => {
      keyboardTopRef.current = event.endCoordinates.screenY;
      sync();
    });
    const hide = Keyboard.addListener('keyboardDidHide', () => {
      keyboardTopRef.current = null;
      setAndroidBottom(null);
    });
    const dimensions = Dimensions.addEventListener('change', sync);

    return () => {
      show.remove();
      hide.remove();
      dimensions.remove();
    };
  }, []);

  const renderBar = () => (
    <View style={[styles.bar, { backgroundColor: colors.card, borderTopColor: colors.border }]}>
      <Pressable
        onPress={() => Keyboard.dismiss()}
        accessibilityRole="button"
        accessibilityLabel="Toetsenbord verbergen"
        hitSlop={8}
        style={styles.button}
      >
        <Ionicons name="chevron-down" size={22} color={colors.text} />
      </Pressable>
    </View>
  );

  if (Platform.OS === 'ios') {
    if (!iosReady) return null;
    return (
      <>
        {nativeIDs.map((nativeID) => (
          <InputAccessoryView
            key={nativeID}
            nativeID={nativeID}
            backgroundColor={colors.card}
            style={styles.accessory}
          >
            {renderBar()}
          </InputAccessoryView>
        ))}
      </>
    );
  }

  if (androidBottom == null) return null;

  return (
    <View pointerEvents="box-none" style={[styles.androidWrap, { bottom: androidBottom }]}>
      {renderBar()}
    </View>
  );
}

const styles = StyleSheet.create({
  accessory: {
    height: 44,
  },
  bar: {
    height: 44,
    width: '100%',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-end',
    paddingRight: 6,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  button: {
    width: 44,
    height: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
  androidWrap: {
    position: 'absolute',
    left: 0,
    right: 0,
    zIndex: 30,
    elevation: 24,
  },
});
