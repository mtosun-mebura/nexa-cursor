/**
 * Push op achtergrond/slot: APNs toont banner + chime (of systeemtoon).
 * Deze taak speelt Bel via expo-audio bij (werkt ook met stille schakelaar)
 * en plant géén tweede lokale melding.
 *
 * Moet vroeg geladen worden (index.ts) vóór de root component.
 */
import * as TaskManager from 'expo-task-manager';

export const DRIVER_OFFER_BG_TASK = 'NEXA-DRIVER-OFFER-NOTIFICATION';

function asRecord(value: unknown): Record<string, unknown> | null {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return null;
  return value as Record<string, unknown>;
}

function dataTypeFromPayload(data: unknown): string {
  const root = asRecord(data);
  if (!root) return '';

  if ('actionIdentifier' in root) return '__response__';

  const nested = asRecord(root.data);
  if (nested) {
    if (typeof nested.type === 'string') return nested.type;
    if (typeof nested.dataString === 'string') {
      try {
        const parsed = asRecord(JSON.parse(nested.dataString));
        if (parsed && typeof parsed.type === 'string') return parsed.type;
      } catch {
        /* ignore */
      }
    }
  }

  const notification = asRecord(root.notification);
  if (notification) {
    const request = asRecord(notification.request);
    const content = request ? asRecord(request.content) : null;
    const contentData = content ? asRecord(content.data) : null;
    if (contentData && typeof contentData.type === 'string') return contentData.type;
  }

  if (typeof root.type === 'string') return root.type;
  return '';
}

function positiveId(value: unknown): number {
  const n = Number(value);
  return Number.isFinite(n) && n > 0 ? Math.trunc(n) : 0;
}

function offerIdFromPayload(data: unknown): number {
  const root = asRecord(data);
  if (!root) return 0;
  const nested = asRecord(root.data) || root;
  const notification = asRecord(root.notification);
  const request = notification ? asRecord(notification.request) : null;
  const content = request ? asRecord(request.content) : null;
  const contentData = content ? asRecord(content.data) : null;
  const bag = contentData || nested;
  return positiveId(bag.offer_id ?? bag.offerId);
}

TaskManager.defineTask(
  DRIVER_OFFER_BG_TASK,
  (({ data, error }: { data?: unknown; error?: unknown }) => {
    try {
      if (error) return;
      const type = dataTypeFromPayload(data);
      if (type === '__response__') return;
      // Annulering: APNs speelt al geluid — geen tweede Bel.
      if (type === 'driver_offer_closed') return;
      if (type && type !== 'driver_offer' && type !== 'driver_offer_test') return;

      const offerId = offerIdFromPayload(data);
      // eslint-disable-next-line @typescript-eslint/no-require-imports
      const { playRideAlertTone } = require('../audio/rideAlert') as typeof import('../audio/rideAlert');

      if (offerId > 0) {
        try {
          // eslint-disable-next-line @typescript-eslint/no-require-imports
          const watch = require('./offerAlertWatch') as typeof import('./offerAlertWatch');
          // APNs-banner is al zichtbaar → geen lokale tweede melding meer.
          watch.markDriverOfferBannerPresented([offerId]);
          if (watch.driverOfferAlreadySounded(offerId)) return;
          watch.playDriverOfferToneForIds([offerId]);
          return;
        } catch {
          /* fallback */
        }
      }
      playRideAlertTone();
    } catch {
      /* nooit de achtergrondtaak laten crashen */
    }
  }) as unknown as Parameters<typeof TaskManager.defineTask>[1]
);

export function registerDriverOfferBackgroundTask(): void {
  try {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    const Notifications = require('expo-notifications') as typeof import('expo-notifications');
    void Notifications.registerTaskAsync(DRIVER_OFFER_BG_TASK).catch(() => undefined);
  } catch {
    /* native module ontbreekt */
  }
}
