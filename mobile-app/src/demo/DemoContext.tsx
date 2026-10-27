import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { useAuth } from '../auth/AuthContext';

const DEMO_KEY = 'nexa_taxi_demo_mode';

type DemoState = {
  /** Ingelogd als superadmin (alleen dan is de demo-schakelaar zichtbaar). */
  isSuperAdmin: boolean;
  /** Demo staat aan én gebruiker is superadmin. */
  active: boolean;
  setEnabled: (on: boolean) => void;
  /** Boeken-scherm geopend vanuit het chauffeurs-/contractprofiel. */
  bookingOpen: boolean;
  setBookingOpen: (open: boolean) => void;
};

const DemoContext = createContext<DemoState | null>(null);

/**
 * Demo-modus voor presentaties: puur client-side, geen backend-effect.
 * Staat standaard uit, wordt lokaal bewaard en gaat uit zodra de gebruiker
 * uitlogt of geen superadmin is.
 */
export function DemoProvider({ children }: { children: React.ReactNode }) {
  const { ready, session } = useAuth();
  const isSuperAdmin = session?.user?.is_super_admin === true;
  const [enabled, setEnabledState] = useState(false);
  const [loaded, setLoaded] = useState(false);
  const [bookingOpen, setBookingOpen] = useState(false);

  useEffect(() => {
    AsyncStorage.getItem(DEMO_KEY)
      .then((saved) => setEnabledState(saved === '1'))
      .catch(() => undefined)
      .finally(() => setLoaded(true));
  }, []);

  const setEnabled = useCallback((on: boolean) => {
    setEnabledState(on);
    if (!on) setBookingOpen(false);
    AsyncStorage.setItem(DEMO_KEY, on ? '1' : '0').catch(() => undefined);
  }, []);

  useEffect(() => {
    if (!ready || !loaded || isSuperAdmin) return;
    setEnabledState(false);
    setBookingOpen(false);
    AsyncStorage.removeItem(DEMO_KEY).catch(() => undefined);
  }, [ready, loaded, isSuperAdmin]);

  const active = isSuperAdmin && enabled;
  const value = useMemo<DemoState>(
    () => ({ isSuperAdmin, active, setEnabled, bookingOpen: active && bookingOpen, setBookingOpen }),
    [isSuperAdmin, active, setEnabled, bookingOpen]
  );

  return <DemoContext.Provider value={value}>{children}</DemoContext.Provider>;
}

export function useDemo() {
  const ctx = useContext(DemoContext);
  if (!ctx) throw new Error('useDemo must be used within DemoProvider');
  return ctx;
}
