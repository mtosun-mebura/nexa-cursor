import AsyncStorage from '@react-native-async-storage/async-storage';
import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  useSyncExternalStore,
} from 'react';
import { EN } from './en';

export type AppLocale = 'nl' | 'en';

const LOCALE_KEY = 'nexa_taxi_locale';

let current: AppLocale = 'nl';
const listeners = new Set<() => void>();

function emit() {
  listeners.forEach((listener) => listener());
}

export function getLocale(): AppLocale {
  return current;
}

export function dateLocale(): string {
  return current === 'en' ? 'en-GB' : 'nl-NL';
}

export function t(message: string, vars?: Record<string, string | number>): string {
  const translated = current === 'en' ? (EN[message] ?? message) : message;
  if (!vars) return translated;
  return translated.replace(/\{(\w+)\}/g, (_, key) => String(vars[key] ?? ''));
}

function subscribe(listener: () => void) {
  listeners.add(listener);
  return () => listeners.delete(listener);
}

type LocaleContextValue = {
  locale: AppLocale;
  setLocale: (next: AppLocale) => void;
};

const LocaleContext = createContext<LocaleContextValue | null>(null);

export function LocaleProvider({ children }: { children: React.ReactNode }) {
  const locale = useSyncExternalStore(subscribe, getLocale, getLocale);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const saved = await AsyncStorage.getItem(LOCALE_KEY);
        if (!cancelled && (saved === 'nl' || saved === 'en') && saved !== current) {
          current = saved;
          emit();
        }
      } catch {
        /* keep Dutch */
      } finally {
        if (!cancelled) setReady(true);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const setLocale = useCallback((next: AppLocale) => {
    if (current === next) return;
    current = next;
    emit();
    AsyncStorage.setItem(LOCALE_KEY, next).catch(() => {
      /* ignore */
    });
  }, []);

  const value = useMemo(() => ({ locale, setLocale }), [locale, setLocale]);

  if (!ready && current === 'nl') {
    /* render immediately; Dutch is the default while storage loads */
  }

  return <LocaleContext.Provider value={value}>{children}</LocaleContext.Provider>;
}

/** Subscribe this component to language changes. `t` reads the current language. */
export function useI18n() {
  const ctx = useContext(LocaleContext);
  const locale = useSyncExternalStore(subscribe, getLocale, getLocale);
  const setLocale = ctx?.setLocale ?? ((next: AppLocale) => {
    current = next;
    emit();
    AsyncStorage.setItem(LOCALE_KEY, next).catch(() => undefined);
  });
  return { locale, setLocale, t };
}
