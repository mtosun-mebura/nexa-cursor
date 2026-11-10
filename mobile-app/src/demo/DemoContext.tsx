import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { fetchDemoModeStatus, setDemoModeEnabled } from '../api/demoMode';
import { useAuth } from '../auth/AuthContext';
import { isDemoModeEnvironmentAllowed } from '../config';

type DemoState = {
  /** Ingelogd als superadmin (alleen dan is de demo-schakelaar zichtbaar). */
  isSuperAdmin: boolean;
  /** Deze API-host mag demo (lokaal/test); productie niet. */
  environmentAllowed: boolean;
  /**
   * Server-demo staat aan op deze omgeving — geldt voor élke klant/chauffeur,
   * niet alleen het superadmin-apparaat.
   */
  active: boolean;
  setEnabled: (on: boolean) => void;
  /** Boeken-scherm geopend vanuit het chauffeurs-/contractprofiel. */
  bookingOpen: boolean;
  setBookingOpen: (open: boolean) => void;
};

const DemoContext = createContext<DemoState | null>(null);

function readIsSuperAdmin(value: unknown): boolean {
  return value === true || value === 1 || value === '1' || value === 'true';
}

/**
 * Omgeving-brede demo: superadmin zet aan via API; alle clients pollen de status.
 * Virtueel vloot + echte dispatch draaien server-side.
 */
export function DemoProvider({ children }: { children: React.ReactNode }) {
  const { ready, session, driverToken, contractToken } = useAuth();
  const isSuperAdmin = readIsSuperAdmin(session?.user?.is_super_admin);
  const token = driverToken || contractToken || null;
  const [serverEnabled, setServerEnabled] = useState(false);
  const [bookingOpen, setBookingOpen] = useState(false);

  const environmentAllowed = isDemoModeEnvironmentAllowed();

  const refreshStatus = useCallback(async () => {
    if (!isDemoModeEnvironmentAllowed()) {
      setServerEnabled(false);
      return;
    }
    try {
      const status = await fetchDemoModeStatus();
      setServerEnabled(!!status.enabled && status.environment_allowed !== false);
    } catch {
      /* houd vorige status */
    }
  }, []);

  useEffect(() => {
    if (!ready) return;
    void refreshStatus();
    const timer = setInterval(() => {
      void refreshStatus();
    }, 4000);
    return () => clearInterval(timer);
  }, [ready, refreshStatus]);

  const setEnabled = useCallback(
    (on: boolean) => {
      if (on && !isDemoModeEnvironmentAllowed()) {
        setServerEnabled(false);
        setBookingOpen(false);
        return;
      }
      if (!isSuperAdmin) return;
      if (!token) {
        // Geen Bearer-token → schakelaar deed stil niets.
        void refreshStatus();
        return;
      }
      // Optimistisch voor snelle UI; server is bron van waarheid.
      setServerEnabled(on);
      if (!on) setBookingOpen(false);
      void setDemoModeEnabled(token, on)
        .then((status) => {
          setServerEnabled(!!status.enabled);
        })
        .catch(() => {
          void refreshStatus();
        });
    },
    [token, isSuperAdmin, refreshStatus]
  );

  useEffect(() => {
    if (!environmentAllowed) {
      setServerEnabled(false);
      setBookingOpen(false);
    }
  }, [environmentAllowed]);

  const active = environmentAllowed && serverEnabled;
  const value = useMemo<DemoState>(
    () => ({
      isSuperAdmin,
      environmentAllowed,
      active,
      setEnabled,
      bookingOpen: active && bookingOpen,
      setBookingOpen,
    }),
    [isSuperAdmin, environmentAllowed, active, setEnabled, bookingOpen]
  );

  return <DemoContext.Provider value={value}>{children}</DemoContext.Provider>;
}

const DEMO_FALLBACK: DemoState = {
  isSuperAdmin: false,
  environmentAllowed: false,
  active: false,
  setEnabled: () => undefined,
  bookingOpen: false,
  setBookingOpen: () => undefined,
};

export function useDemo() {
  return useContext(DemoContext) ?? DEMO_FALLBACK;
}
