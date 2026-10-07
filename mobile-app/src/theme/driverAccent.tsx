import React, { createContext, useContext, useMemo } from 'react';

export const DRIVER_ACCENT_OPTIONS = [
  { key: 'orange', hex: '#f97316', label: 'Oranje' },
  { key: 'yellow', hex: '#eab308', label: 'Geel' },
  { key: 'blue', hex: '#2563eb', label: 'Blauw' },
  { key: 'red', hex: '#ef4444', label: 'Rood' },
  { key: 'green', hex: '#16a34a', label: 'Groen' },
  { key: 'pink', hex: '#ec4899', label: 'Roze' },
] as const;

export type DriverAccentKey = (typeof DRIVER_ACCENT_OPTIONS)[number]['key'];

export const DEFAULT_DRIVER_ACCENT: DriverAccentKey = 'orange';

export function hexAlpha(hex: string, alpha: number): string {
  const raw = hex.replace('#', '').trim();
  const full = raw.length === 3 ? raw.split('').map((c) => c + c).join('') : raw;
  const n = parseInt(full, 16);
  if (!Number.isFinite(n)) return `rgba(249,115,22,${alpha})`;
  return `rgba(${(n >> 16) & 255},${(n >> 8) & 255},${n & 255},${alpha})`;
}

export function normalizeDriverAccent(value?: string | null): DriverAccentKey {
  const key = String(value || '').toLowerCase();
  return DRIVER_ACCENT_OPTIONS.some((item) => item.key === key)
    ? (key as DriverAccentKey)
    : DEFAULT_DRIVER_ACCENT;
}

export function driverAccentHex(value?: string | null): string {
  const key = normalizeDriverAccent(value);
  return DRIVER_ACCENT_OPTIONS.find((item) => item.key === key)?.hex || DRIVER_ACCENT_OPTIONS[0].hex;
}

export type DriverAccentValue = {
  key: DriverAccentKey;
  hex: string;
  border: string;
  borderMuted: string;
  fillSoft: string;
};

function accentValue(key?: string | null): DriverAccentValue {
  const normalized = normalizeDriverAccent(key);
  const hex = driverAccentHex(normalized);
  return {
    key: normalized,
    hex,
    border: hexAlpha(hex, 0.5),
    borderMuted: hexAlpha(hex, 0.28),
    fillSoft: hexAlpha(hex, 0.16),
  };
}

const DriverAccentContext = createContext<DriverAccentValue | null>(null);

export function DriverAccentProvider({
  accent,
  children,
}: {
  accent: string;
  children: React.ReactNode;
}) {
  const value = useMemo(() => accentValue(accent), [accent]);
  return <DriverAccentContext.Provider value={value}>{children}</DriverAccentContext.Provider>;
}

export function useDriverAccent(): DriverAccentValue {
  return useContext(DriverAccentContext) ?? accentValue(DEFAULT_DRIVER_ACCENT);
}

export function useOptionalDriverAccent(): DriverAccentValue | null {
  return useContext(DriverAccentContext);
}
