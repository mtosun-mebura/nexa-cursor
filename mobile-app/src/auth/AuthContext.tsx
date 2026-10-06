import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import type { BootstrapSession, Capabilities } from '../api/auth';
import { setUnauthorizedHandler } from '../api/client';
import { stopBackgroundLocation } from '../location/background';

const SESSION_KEY = 'nexa_taxi_native_session';
const GUEST_ROUTE_KEY = 'nexa_taxi_guest_route';

type AuthState = {
  ready: boolean;
  session: BootstrapSession | null;
  activeScreen: string | null;
  setSession: (session: BootstrapSession | null) => Promise<void>;
  setActiveScreen: (key: string | null) => void;
  logout: () => Promise<void>;
  /** Sessie wissen en volgende scherm = login (verlopen token). */
  forceReLogin: () => Promise<void>;
  driverToken: string | null;
  contractToken: string | null;
  capabilities: Capabilities | null;
};

const AuthContext = createContext<AuthState | null>(null);

function tokenIsExpired(expiresAt?: string | null): boolean {
  if (!expiresAt) return false;
  const ms = Date.parse(expiresAt);
  return Number.isFinite(ms) && ms <= Date.now();
}

function sanitizeSession(session: BootstrapSession): BootstrapSession | null {
  const tokens = { ...(session.tokens || {}) };
  if (tokens.driver && tokenIsExpired(tokens.driver.expires_at)) {
    delete tokens.driver;
  }
  if (tokens.contract && tokenIsExpired(tokens.contract.expires_at)) {
    delete tokens.contract;
  }
  if (!tokens.driver && !tokens.contract) {
    return null;
  }
  return { ...session, tokens };
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [ready, setReady] = useState(false);
  const [session, setSessionState] = useState<BootstrapSession | null>(null);
  const [activeScreen, setActiveScreen] = useState<string | null>(null);

  const setSession = useCallback(async (next: BootstrapSession | null) => {
    setSessionState(next);
    if (!next) {
      setActiveScreen(null);
      await SecureStore.deleteItemAsync(SESSION_KEY);
      return;
    }
    const screens = next.capabilities?.screens || [];
    const nextScreen = screens.length === 1 ? screens[0].key : null;
    setActiveScreen(nextScreen);
    await SecureStore.setItemAsync(
      SESSION_KEY,
      JSON.stringify({ ...next, activeScreen: nextScreen })
    );
  }, []);

  const forceReLogin = useCallback(async () => {
    try {
      await stopBackgroundLocation();
    } catch {
      /* ignore */
    }
    await AsyncStorage.setItem(GUEST_ROUTE_KEY, 'login').catch(() => undefined);
    await setSession(null);
  }, [setSession]);

  const logout = useCallback(async () => {
    try {
      await stopBackgroundLocation();
    } catch {
      /* ignore */
    }
    await setSession(null);
  }, [setSession]);

  useEffect(() => {
    (async () => {
      try {
        const raw = await SecureStore.getItemAsync(SESSION_KEY);
        if (raw) {
          const parsed = JSON.parse(raw) as BootstrapSession & { activeScreen?: string };
          const sanitized = sanitizeSession(parsed);
          if (!sanitized) {
            await SecureStore.deleteItemAsync(SESSION_KEY);
            await AsyncStorage.setItem(GUEST_ROUTE_KEY, 'login').catch(() => undefined);
          } else {
            setSessionState(sanitized);
            const screens = sanitized.capabilities?.screens || [];
            let screen = parsed.activeScreen || sanitized.capabilities?.default_screen || null;
            if (screen === 'driver' && !sanitized.tokens?.driver) {
              screen = sanitized.tokens?.contract ? 'contract' : null;
            }
            if (screen === 'contract' && !sanitized.tokens?.contract) {
              screen = sanitized.tokens?.driver ? 'driver' : null;
            }
            if (!screen && screens.length === 1) {
              screen = screens[0].key;
            }
            if (screen === 'driver' && !sanitized.tokens?.driver) screen = null;
            if (screen === 'contract' && !sanitized.tokens?.contract) screen = null;
            setActiveScreen(screen);
            if (sanitized !== parsed || screen !== parsed.activeScreen) {
              await SecureStore.setItemAsync(
                SESSION_KEY,
                JSON.stringify({ ...sanitized, activeScreen: screen })
              );
            }
          }
        }
      } catch {
        /* ignore */
      } finally {
        setReady(true);
      }
    })();
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(() => {
      void forceReLogin();
    });
    return () => setUnauthorizedHandler(null);
  }, [forceReLogin]);

  const value = useMemo<AuthState>(
    () => ({
      ready,
      session,
      activeScreen,
      setSession,
      setActiveScreen: (key) => {
        setActiveScreen(key);
        if (session) {
          SecureStore.setItemAsync(
            SESSION_KEY,
            JSON.stringify({ ...session, activeScreen: key })
          ).catch(() => undefined);
        }
      },
      logout,
      forceReLogin,
      driverToken: session?.tokens?.driver?.token || null,
      contractToken: session?.tokens?.contract?.token || null,
      capabilities: session?.capabilities || null,
    }),
    [ready, session, activeScreen, setSession, logout, forceReLogin]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return ctx;
}
