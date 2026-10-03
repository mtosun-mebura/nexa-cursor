import AsyncStorage from '@react-native-async-storage/async-storage';
import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';
import { Appearance, ColorSchemeName, StatusBar } from 'react-native';
import { ColorPalette, DARK_COLORS, LIGHT_COLORS } from '../config';

export type ThemePreference = 'system' | 'light' | 'dark';

const THEME_KEY = 'nexa_taxi_theme_preference';

type ThemeContextValue = {
  preference: ThemePreference;
  colorScheme: 'light' | 'dark';
  colors: ColorPalette;
  setPreference: (next: ThemePreference) => void;
  ready: boolean;
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

function resolveScheme(
  preference: ThemePreference,
  system: ColorSchemeName
): 'light' | 'dark' {
  if (preference === 'light') return 'light';
  if (preference === 'dark') return 'dark';
  return system === 'light' ? 'light' : 'dark';
}

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const [preference, setPreferenceState] = useState<ThemePreference>('system');
  const [systemScheme, setSystemScheme] = useState<ColorSchemeName>(
    () => Appearance.getColorScheme() ?? 'dark'
  );
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const saved = await AsyncStorage.getItem(THEME_KEY);
        if (!cancelled && (saved === 'system' || saved === 'light' || saved === 'dark')) {
          setPreferenceState(saved);
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
    const sub = Appearance.addChangeListener(({ colorScheme }) => {
      setSystemScheme(colorScheme);
    });
    return () => sub.remove();
  }, []);

  const setPreference = useCallback((next: ThemePreference) => {
    setPreferenceState(next);
    AsyncStorage.setItem(THEME_KEY, next).catch(() => {
      /* ignore */
    });
  }, []);

  const colorScheme = resolveScheme(preference, systemScheme);
  const colors = colorScheme === 'light' ? LIGHT_COLORS : DARK_COLORS;

  useEffect(() => {
    StatusBar.setBarStyle(colorScheme === 'light' ? 'dark-content' : 'light-content');
  }, [colorScheme]);

  const value = useMemo(
    () => ({ preference, colorScheme, colors, setPreference, ready }),
    [preference, colorScheme, colors, setPreference, ready]
  );

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
