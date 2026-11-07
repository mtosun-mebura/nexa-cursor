/**
 * iOS data-only push (`_contentAvailable`) wekt deze taak zonder banner.
 * Nieuwe rit én annulering: één lokale Bel-melding (geen systeemtriangel).
 *
 * Moet vroeg geladen worden (index.ts) vóór de root component.
 */
import * as TaskManager from 'expo-task-manager';

export const DRIVER_OFFER_BG_TASK = 'NEXA-DRIVER-OFFER-NOTIFICATION';

function asRecord(value: unknown): Record<string, unknown> | null {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return null;
  return value as Record<string, unknown>;
}

function offerPayloadFromTaskData(data: unknown): Record<string, unknown> | null {
  const root = asRecord(data);
  if (!root) return null;

  // NotificationResponse (user tap) — geen extra melding.
  if ('actionIdentifier' in root) return null;

  const nested = asRecord(root.data);
  if (nested) {
    if (typeof nested.type === 'string') return nested;
    if (typeof nested.dataString === 'string') {
      try {
        const parsed = asRecord(JSON.parse(nested.dataString));
        if (parsed) return parsed;
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
    if (contentData) return contentData;
  }

  if (typeof root.type === 'string') return root;
  return null;
}

function positiveId(value: unknown): number {
  const n = Number(value);
  return Number.isFinite(n) && n > 0 ? Math.trunc(n) : 0;
}

TaskManager.defineTask(
  DRIVER_OFFER_BG_TASK,
  (({ data, error }: { data?: unknown; error?: unknown }) => {
    try {
      if (error) return;
      const payload = offerPayloadFromTaskData(data);
      if (!payload) return;
      const type = String(payload.type || '');
      if (type && type !== 'driver_offer' && type !== 'driver_offer_test' && type !== 'driver_offer_closed') {
        return;
      }

      const offerId = positiveId(payload.offer_id ?? payload.offerId);
      const rideRequestId = positiveId(
        payload.ride_request_id ?? payload.rideRequestId ?? payload.ride_id
      );

      // Lazy: voorkomt zware import-cyclus bij native task-load.
      // eslint-disable-next-line @typescript-eslint/no-require-imports
      const driverOffers = require('./driverOffers') as typeof import('./driverOffers');
      // eslint-disable-next-line @typescript-eslint/no-require-imports
      const { activeRideAlertTone } = require('../audio/rideAlert') as typeof import('../audio/rideAlert');

      if (type === 'driver_offer_closed') {
        void driverOffers.notifyDriverOfferClosed({
          offerId: offerId || null,
          rideRequestId: rideRequestId || null,
          pickupAddress: String(payload.pickup_address || payload.pickupAddress || '').trim() || null,
          pickupAt: String(payload.pickup_at || payload.pickupAt || '').trim() || null,
          quotedPrice:
            payload.quoted_price != null && Number.isFinite(Number(payload.quoted_price))
              ? Number(payload.quoted_price)
              : payload.quotedPrice != null && Number.isFinite(Number(payload.quotedPrice))
                ? Number(payload.quotedPrice)
                : null,
          tone: activeRideAlertTone(),
        });
        return;
      }

      void driverOffers.notifyNewDriverOffer({
        count: 1,
        pickup: String(payload.pickup_address || payload.pickupAddress || '').trim() || null,
        dropoff: String(payload.dropoff_address || payload.dropoffAddress || '').trim() || null,
        pickupPlaceName:
          String(payload.pickup_place_name || payload.pickupPlaceName || '').trim() || null,
        dropoffPlaceName:
          String(payload.dropoff_place_name || payload.dropoffPlaceName || '').trim() || null,
        pickupAt: String(payload.pickup_at || payload.pickupAt || '').trim() || null,
        quotedPrice:
          payload.quoted_price != null && Number.isFinite(Number(payload.quoted_price))
            ? Number(payload.quoted_price)
            : payload.quotedPrice != null && Number.isFinite(Number(payload.quotedPrice))
              ? Number(payload.quotedPrice)
              : null,
        expiresAt: String(payload.expires_at || payload.expiresAt || '').trim() || null,
        playSound: true,
        tone: activeRideAlertTone(),
        offerId: offerId || null,
        rideRequestId: rideRequestId || null,
      });
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
