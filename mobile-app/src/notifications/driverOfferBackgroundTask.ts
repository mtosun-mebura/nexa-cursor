/**
 * Speelt ritgeluid wanneer een push binnenkomt terwijl de app op de achtergrond
 * staat (andere app actief of scherm vergrendeld). Vereist `_contentAvailable` op
 * de Expo-push én UIBackgroundModes audio + remote-notification.
 *
 * Moet vroeg geladen worden (index.ts) vóór de root component.
 */
import * as TaskManager from 'expo-task-manager';
import { playRideAlertTone } from '../audio/rideAlert';

export const DRIVER_OFFER_BG_TASK = 'NEXA-DRIVER-OFFER-NOTIFICATION';

function dataTypeFromPayload(data: unknown): string {
  if (!data || typeof data !== 'object') return '';
  const root = data as Record<string, unknown>;

  // NotificationResponse (user tap) — geen extra toon.
  if ('actionIdentifier' in root) return '__response__';

  const nested = root.data;
  if (nested && typeof nested === 'object') {
    const bag = nested as Record<string, unknown>;
    if (typeof bag.type === 'string') return bag.type;
    if (typeof bag.dataString === 'string') {
      try {
        const parsed = JSON.parse(bag.dataString) as Record<string, unknown>;
        if (typeof parsed.type === 'string') return parsed.type;
      } catch {
        /* ignore */
      }
    }
  }

  const notification = root.notification;
  if (notification && typeof notification === 'object') {
    const content = (notification as { request?: { content?: { data?: Record<string, unknown> } } })
      .request?.content?.data;
    if (content && typeof content.type === 'string') return content.type;
  }

  if (typeof root.type === 'string') return root.type;
  return '';
}

TaskManager.defineTask(
  DRIVER_OFFER_BG_TASK,
  (({ data, error }: { data?: unknown; error?: unknown }) => {
    try {
      if (error) return;
      const type = dataTypeFromPayload(data);
      if (type === '__response__' || type === 'driver_offer_closed') return;
      // driver_offer, driver_offer_test, of onbekend (oude payloads): speel toon.
      if (type && type !== 'driver_offer' && type !== 'driver_offer_test') return;
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
