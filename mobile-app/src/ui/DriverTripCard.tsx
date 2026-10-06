import React from 'react';
import type { DriverActiveRide } from '../api/driver';
import { DriverOfferCard } from './DriverOfferCard';

/** Zelfde detailkaart als Aanvragen, met trip-acties (Navigeer / Rit starten). */
export function DriverTripCard({
  ride,
  variant,
  busy,
  highlighted,
  onHighlightEnd,
  onStart,
  onCancel,
  onOpenMaps,
}: {
  ride: DriverActiveRide;
  variant: 'active' | 'scheduled' | 'overdue';
  busy?: boolean;
  highlighted?: boolean;
  onHighlightEnd?: () => void;
  onStart?: () => void;
  onCancel?: () => void;
  onOpenMaps?: () => void;
}) {
  return (
    <DriverOfferCard
      ride={ride}
      variant={variant}
      busy={busy}
      highlighted={highlighted}
      onHighlightEnd={onHighlightEnd}
      onStart={onStart}
      onCancel={onCancel}
      onOpenMaps={onOpenMaps}
    />
  );
}
