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
  onComplete,
  onCancel,
  onOpenMaps,
  onArchive,
  archived,
  onProposePickup,
  onRelease,
}: {
  ride: DriverActiveRide;
  variant: 'active' | 'scheduled' | 'overdue' | 'completed';
  busy?: boolean;
  highlighted?: boolean;
  onHighlightEnd?: () => void;
  onStart?: () => void;
  onComplete?: () => void;
  onCancel?: () => void;
  onOpenMaps?: () => void;
  onArchive?: () => void;
  archived?: boolean;
  onProposePickup?: () => void;
  onRelease?: () => void;
}) {
  return (
    <DriverOfferCard
      ride={ride}
      variant={variant}
      busy={busy}
      highlighted={highlighted}
      onHighlightEnd={onHighlightEnd}
      onStart={onStart}
      onComplete={onComplete}
      onCancel={onCancel}
      onOpenMaps={onOpenMaps}
      onArchive={onArchive}
      archived={archived}
      onProposePickup={onProposePickup}
      onRelease={onRelease}
    />
  );
}
