import { useEffect, useState } from 'react';
import { Dimensions, Keyboard, Platform, TextProps, TextInputProps } from 'react-native';

/**
 * Caps Android font scale on single-line chrome (tabs, titles, fields).
 * iOS keeps Dynamic Type; `adjustsFontSizeToFit` is iOS-only.
 */
export const compactFontProps: Pick<TextProps, 'maxFontSizeMultiplier'> =
  Platform.OS === 'android' ? { maxFontSizeMultiplier: 1.15 } : {};

export const compactInputProps: Pick<TextInputProps, 'maxFontSizeMultiplier'> =
  Platform.OS === 'android' ? { maxFontSizeMultiplier: 1.15 } : {};

/** Single-line labels that shrink on iOS via adjustsFontSizeToFit. Keep the design size on Android. */
export const fitLineFontProps: Pick<TextProps, 'maxFontSizeMultiplier'> =
  Platform.OS === 'android' ? { maxFontSizeMultiplier: 1 } : {};

/**
 * Space the keyboard still covers after the window resizes.
 * Android stand-in for iOS `automaticallyAdjustKeyboardInsets` (0 when resize already cleared the field).
 */
export function useAndroidKeyboardOverlap(): number {
  const [overlap, setOverlap] = useState(0);

  useEffect(() => {
    if (Platform.OS !== 'android') return;
    const show = Keyboard.addListener('keyboardDidShow', (event) => {
      const windowHeight = Dimensions.get('window').height;
      setOverlap(Math.max(0, Math.round(windowHeight - event.endCoordinates.screenY)));
    });
    const hide = Keyboard.addListener('keyboardDidHide', () => setOverlap(0));
    return () => {
      show.remove();
      hide.remove();
    };
  }, []);

  return overlap;
}
