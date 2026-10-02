import * as SecureStore from 'expo-secure-store';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import type { BootstrapSession, Capabilities } from '../api/auth';

const SESSION_KEY = 'nexa_taxi_native_session';

type AuthState = {
  ready: boolean;
  session: BootstrapSession | null;
  activeScreen: string | null;
  setSession: (session: BootstrapSession | null) => Promise<void>;
  setActiveScreen: (key: string | null) => void;
  logout: () => Promise<void>;
  driverToken: string | null;
  contractToken: string | null;
  capabilities: Capabilities | null;
};

const AuthContext = createContext<AuthState | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [ready, setReady] = useState(false);
  const [session, setSessionState] = useState<BootstrapSession | null>(null);
  const [activeScreen, setActiveScreen] = useState<string | null>(null);

  useEffect(() => {
    (async () => {
      try {
        const raw = await SecureStore.getItemAsync(SESSION_KEY);
        if (raw) {
          const parsed = JSON.parse(raw) as BootstrapSession & { activeScreen?: string };
          setSessionState(parsed);
          setActiveScreen(parsed.activeScreen || parsed.capabilities?.default_screen || null);
        }
      } catch {
        /* ignore */
      } finally {
        setReady(true);
      }
    })();
  }, []);

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

  const logout = useCallback(async () => {
    await setSession(null);
  }, [setSession]);

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
      driverToken: session?.tokens?.driver?.token || null,
      contractToken: session?.tokens?.contract?.token || null,
      capabilities: session?.capabilities || null,
    }),
    [ready, session, activeScreen, setSession, logout]
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
