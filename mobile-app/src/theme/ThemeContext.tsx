import AsyncStorage from '@react-native-async-storage/async-storage';
import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { NavigationBar } from 'expo-navigation-bar';
import {
  Appearance,
  AppState,
  ColorSchemeName,
  Platform,
  StatusBar,
  useColorScheme,
  View,
} from 'react-native';
import { ColorPalette, DARK_COLORS, LIGHT_COLORS } from '../config';
import { syncAlternateAppIcon } from './alternateAppIcon';

export type ThemePreference = 'system' | 'light' | 'dark';

const THEME_KEY = 'nexa_taxi_theme_preference';
const SKIP_ICON_KEY = 'nexa_taxi_skip_icon_sync';

type ThemeContextValue = {
  preference: ThemePreference;
  colorScheme: 'light' | 'dark';
  colors: ColorPalette;
  /** syncIcon false keeps the iOS home-screen icon unchanged. */
  setPreference: (next: ThemePreference, options?: { syncIcon?: boolean }) => void;
  ready: boolean;
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

function concreteScheme(value: ColorSchemeName | null | undefined): 'light' | 'dark' | null {
  return value === 'light' || value === 'dark' ? value : null;
}

/** Explicit Licht/Donker wins. System (and a missing preference) follows the phone on iOS and Android. */
function resolveScheme(
  preference: ThemePreference,
  system: 'light' | 'dark' | null
): 'light' | 'dark' | null {
  if (preference === 'light' || preference === 'dark') return preference;
  return system;
}

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const osScheme = concreteScheme(useColorScheme());
  const [preference, setPreferenceState] = useState<ThemePreference>('system');
  const [systemScheme, setSystemScheme] = useState<'light' | 'dark' | null>(() =>
    concreteScheme(Appearance.getColorScheme())
  );
  const [ready, setReady] = useState(false);
  const skipIconSync = useRef(false);
  const phoneScheme = osScheme ?? systemScheme;

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const [saved, skip] = await Promise.all([
          AsyncStorage.getItem(THEME_KEY),
          AsyncStorage.getItem(SKIP_ICON_KEY),
        ]);
        if (!cancelled) {
          skipIconSync.current = skip === '1';
          if (saved === 'system' || saved === 'light' || saved === 'dark') {
            setPreferenceState(saved);
          }
        }
      } catch {
        /* keep system default */
      } finally {
        if (!cancelled) setReady(true);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    let busted = false;
    const apply = (value: ColorSchemeName | null | undefined) => {
      const next = concreteScheme(value);
      if (next) setSystemScheme(next);
      return next;
    };
    const read = () => {
      if (apply(Appearance.getColorScheme()) || busted) return;
      busted = true;
      // The first native read is cached and can be null on iOS and Android
      // before the window or activity is ready. 'unspecified' follows the phone.
      Appearance.setColorScheme('unspecified');
      apply(Appearance.getColorScheme());
    };
    read();
    const appearanceSub = Appearance.addChangeListener(({ colorScheme }) => {
      if (!apply(colorScheme)) read();
    });
    const appStateSub = AppState.addEventListener('change', (state) => {
      if (state === 'active') read();
    });
    return () => {
      appearanceSub.remove();
      appStateSub.remove();
    };
  }, []);

  const setPreference = useCallback((next: ThemePreference, options?: { syncIcon?: boolean }) => {
    const syncIcon = options?.syncIcon !== false;
    skipIconSync.current = !syncIcon;
    setPreferenceState(next);
    AsyncStorage.setItem(THEME_KEY, next).catch(() => {
      /* ignore */
    });
    AsyncStorage.setItem(SKIP_ICON_KEY, syncIcon ? '0' : '1').catch(() => {
      /* ignore */
    });
    if (syncIcon) {
      syncAlternateAppIcon(next, Appearance.getColorScheme() ?? null, { force: true });
    }
  }, []);

  const colorScheme = resolveScheme(preference, phoneScheme);

  useEffect(() => {
    if (colorScheme == null) return;
    const barStyle = colorScheme === 'light' ? 'dark-content' : 'light-content';
    StatusBar.setBarStyle(barStyle);
    if (Platform.OS !== 'android') return;
    try {
      // light = lichte knoppen op een donkere balk; dark = donkere knoppen op een lichte balk.
      NavigationBar.setStyle(colorScheme === 'light' ? 'dark' : 'light');
    } catch {
      /* native module ontbreekt tot een Android-rebuild */
    }
  }, [colorScheme]);

  useEffect(() => {
    if (!ready || skipIconSync.current) return;
    if (preference === 'system' && phoneScheme == null) return;
    const apply = () => syncAlternateAppIcon(preference, phoneScheme);
    if (AppState.currentState === 'active') apply();
    const sub = AppState.addEventListener('change', (state) => {
      if (state === 'active') apply();
    });
    return () => sub.remove();
  }, [ready, preference, phoneScheme]);

  const value = useMemo(() => {
    const scheme: 'light' | 'dark' = colorScheme === 'dark' ? 'dark' : 'light';
    return {
      preference,
      colorScheme: scheme,
      colors: scheme === 'light' ? LIGHT_COLORS : DARK_COLORS,
      setPreference,
      ready,
    };
  }, [preference, colorScheme, setPreference, ready]);

  if (colorScheme == null) {
    return (
      <ThemeContext.Provider value={value}>
        <View style={{ flex: 1 }} />
      </ThemeContext.Provider>
    );
  }

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme() {
  const ctx = useContext(ThemeContext);
  if (!ctx) {
    throw new Error('useTheme must be used within ThemeProvider');
  }
  return ctx;
}

export function useThemeColors(): ColorPalette {
  const ctx = useContext(ThemeContext);
  return ctx?.colors ?? DARK_COLORS;
}
