import { useEffect, useRef } from 'react';
import { BackHandler, Platform } from 'react-native';

/**
 * Android hardware / gesture back.
 * Return true from the handler to consume the press (same as the on-screen back).
 * Return false to let the navigator pop, or leave the app when this screen is the root.
 */
export function useAndroidBackHandler(handler: () => boolean) {
  const handlerRef = useRef(handler);
  handlerRef.current = handler;

  useEffect(() => {
    if (Platform.OS !== 'android') return;
    const sub = BackHandler.addEventListener('hardwareBackPress', () => handlerRef.current());
    return () => sub.remove();
  }, []);
}
