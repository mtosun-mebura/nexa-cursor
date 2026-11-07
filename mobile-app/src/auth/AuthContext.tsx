import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import type { BootstrapSession, Capabilities } from '../api/auth';
import { setUnauthorizedHandler } from '../api/client';
import { unregisterDriverPushToken } from '../notifications/driverPush';
import { secureDelete, secureGet, secureSet } from '../storage/secureStoreSafe';

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

/** Geen tussenscherm: bij meerdere rollen direct het standaardscherm, anders de laatste keuze. */
export function resolveAppScreen(
  session: BootstrapSession | null,
  preferred?: string | null
): 'driver' | 'contract' | null {
  if (!session) return null;
  const allowed = new Set((session.capabilities?.screens || []).map((screen) => screen.key));
  const open = (key: string | null | undefined): key is 'driver' | 'contract' => {
    if (key !== 'driver' && key !== 'contract') return false;
    if (allowed.size > 0 && !allowed.has(key)) return false;
    return key === 'driver' ? !!session.tokens?.driver : !!session.tokens?.contract;
  };
  const candidates = [preferred, session.capabilities?.default_screen, 'driver', 'contract'];
  return candidates.find(open) ?? null;
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

function cleanupDriverSideEffects(driverTok: string | null | undefined) {
  void (async () => {
    try {
      await unregisterDriverPushToken(driverTok);
    } catch {
      /* ignore */
    }
    try {
      // Lazy: sync import van tracking→background blokkeerde het eerste frame (zwart scherm).
      // eslint-disable-next-line @typescript-eslint/no-require-imports
      const { stopDriverLocationTracking } = require('../location/tracking') as {
        stopDriverLocationTracking: () => Promise<void>;
      };
      await stopDriverLocationTracking();
    } catch {
      /* ignore */
    }
  })();
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [ready, setReady] = useState(false);
  const [session, setSessionState] = useState<BootstrapSession | null>(null);
  const [activeScreen, setActiveScreen] = useState<string | null>(null);
  const sessionRef = useRef(session);
  sessionRef.current = session;

  const setSession = useCallback(async (next: BootstrapSession | null) => {
    sessionRef.current = next;
    setSessionState(next);
    if (!next) {
      setActiveScreen(null);
      await secureDelete(SESSION_KEY);
      return;
    }
    const nextScreen = resolveAppScreen(next, next.capabilities?.default_screen);
    setActiveScreen(nextScreen);
    await secureSet(SESSION_KEY, JSON.stringify({ ...next, activeScreen: nextScreen }));
  }, []);

  const forceReLogin = useCallback(async () => {
    const current = sessionRef.current;
    if (!current) return;
    const driverTok = current.tokens?.driver?.token;
    await AsyncStorage.setItem(GUEST_ROUTE_KEY, 'login').catch(() => undefined);
    await setSession(null);
    cleanupDriverSideEffects(driverTok);
  }, [setSession]);

  const logout = useCallback(async () => {
    const driverTok = sessionRef.current?.tokens?.driver?.token;
    // Eerst de sessie wissen: GPS-stop en push-unregister mogen de UI niet blokkeren.
    await AsyncStorage.setItem(GUEST_ROUTE_KEY, 'welcome').catch(() => undefined);
    await setSession(null);
    cleanupDriverSideEffects(driverTok);
  }, [setSession]);

  useEffect(() => {
    let cancelled = false;
    const failsafe = setTimeout(() => {
      if (!cancelled) setReady(true);
    }, 2500);
    (async () => {
      try {
        const raw = await secureGet(SESSION_KEY);
        if (cancelled) return;
        if (raw) {
          const parsed = JSON.parse(raw) as BootstrapSession & { activeScreen?: string };
          const sanitized = sanitizeSession(parsed);
          if (!sanitized) {
            await secureDelete(SESSION_KEY);
            await AsyncStorage.setItem(GUEST_ROUTE_KEY, 'login').catch(() => undefined);
          } else {
            setSessionState(sanitized);
            const screen = resolveAppScreen(
              sanitized,
              parsed.activeScreen || sanitized.capabilities?.default_screen
            );
            setActiveScreen(screen);
            if (sanitized !== parsed || screen !== parsed.activeScreen) {
              await secureSet(
                SESSION_KEY,
                JSON.stringify({ ...sanitized, activeScreen: screen })
              );
            }
          }
        }
      } catch {
        /* ignore */
      } finally {
        if (!cancelled) setReady(true);
        clearTimeout(failsafe);
      }
    })();
    return () => {
      cancelled = true;
      clearTimeout(failsafe);
    };
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
          void secureSet(SESSION_KEY, JSON.stringify({ ...session, activeScreen: key }));
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
