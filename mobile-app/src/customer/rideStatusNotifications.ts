import AsyncStorage from '@react-native-async-storage/async-storage';
import { AppState, Platform } from 'react-native';
import type { LiveRide, RideStatusUpdate } from '../api/customer';

const SEEN_KEY = 'nexa_taxi_seen_status_events';

let handlerReady = false;
let permissionReady: Promise<boolean> | null = null;

async function notifications() {
  return import('expo-notifications');
}

async function ensureHandler(): Promise<void> {
  if (handlerReady) return;
  try {
    const Notifications = await notifications();
    Notifications.setNotificationHandler({
      handleNotification: async () => ({
        shouldShowBanner: true,
        shouldShowList: true,
        shouldPlaySound: true,
        shouldSetBadge: false,
      }),
    });
    handlerReady = true;
  } catch {
    /* Expo Go / ontbrekende native module */
  }
}

async function ensurePermission(): Promise<boolean> {
  if (!permissionReady) {
    permissionReady = (async () => {
      try {
        await ensureHandler();
        const Notifications = await notifications();
        const current = await Notifications.getPermissionsAsync();
        if (current.granted) return true;
        const asked = await Notifications.requestPermissionsAsync();
        return !!asked.granted;
      } catch {
        return false;
      }
    })();
  }
  return permissionReady;
}

async function loadSeen(): Promise<Record<string, string[]>> {
  try {
    const raw = await AsyncStorage.getItem(SEEN_KEY);
    const parsed = raw ? JSON.parse(raw) : {};
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch {
    return {};
  }
}

async function saveSeen(map: Record<string, string[]>): Promise<void> {
  await AsyncStorage.setItem(SEEN_KEY, JSON.stringify(map));
}

function eventKey(update: RideStatusUpdate): string {
  return `${update.event}:${update.at || ''}`;
}

/** Minimaal altijd: accepted + en_route + started; verder alleen als aangevinkt. */
function shouldNotify(event: string, enabled: string[] | undefined): boolean {
  if (event === 'accepted' || event === 'en_route' || event === 'started') return true;
  if (!enabled || enabled.length === 0) return false;
  return enabled.includes(event);
}

/**
 * Toont een lokale melding per nieuwe statusupdate (vooral nuttig op de achtergrond).
 * Retourneert of er nieuwe updates waren.
 */
export async function notifyNewRideStatusUpdates(ride: LiveRide): Promise<void> {
  const updates = Array.isArray(ride.status_updates) ? ride.status_updates : [];
  if (updates.length === 0) return;

  const rideId = String(ride.id || '');
  if (!rideId) return;

  const seenMap = await loadSeen();
  const seen = new Set(seenMap[rideId] || []);
  const enabled = ride.status_notify_events;
  const fresh = updates.filter(
    (u) => u?.event && shouldNotify(u.event, enabled) && !seen.has(eventKey(u))
  );
  if (fresh.length === 0) return;

  const granted = await ensurePermission();
  const inBackground = AppState.currentState !== 'active';

  for (const update of fresh) {
    seen.add(eventKey(update));
    if (!granted) continue;
    // Op voorgrond tonen we de timeline in de rit; op achtergrond altijd melding.
    // Ook op voorgrond een melding zodat elke nieuwe status zichtbaar is.
    try {
      await ensureHandler();
      const Notifications = await notifications();
      await Notifications.scheduleNotificationAsync({
        content: {
          title: 'Ritstatus',
          body: update.label || ride.status_label || 'Status bijgewerkt',
          data: { rideId: ride.id, event: update.event },
          sound: Platform.OS === 'ios' ? 'default' : undefined,
        },
        trigger: null,
      });
    } catch {
      /* ignore notification failures */
    }
  }

  seenMap[rideId] = Array.from(seen).slice(-40);
  await saveSeen(seenMap);

  // Silence unused for lint if tree-shaken; keep for clarity of intent.
  void inBackground;
}
