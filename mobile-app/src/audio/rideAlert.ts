import {
  createAudioPlayer,
  setAudioModeAsync,
  setIsAudioActiveAsync,
  type AudioPlayer,
} from 'expo-audio';

export const RIDE_ALERT_TONES = ['classic', 'chime', 'alert', 'soft', 'siren'] as const;
export type RideAlertTone = (typeof RIDE_ALERT_TONES)[number];

export const RIDE_ALERT_OPTIONS: { key: RideAlertTone; label: string }[] = [
  { key: 'classic', label: 'Klassiek' },
  { key: 'chime', label: 'Bel' },
  { key: 'alert', label: 'Alert' },
  { key: 'soft', label: 'Zacht' },
  { key: 'siren', label: 'Sirene' },
];

const SOURCES: Record<RideAlertTone, number> = {
  classic: require('../../assets/sounds/classic.m4a'),
  chime: require('../../assets/sounds/chime.m4a'),
  alert: require('../../assets/sounds/alert.m4a'),
  soft: require('../../assets/sounds/soft.m4a'),
  siren: require('../../assets/sounds/siren.m4a'),
};

/** Bestandsnaam voor expo-notifications (wav in app.json sounds). */
export function rideAlertNotificationSound(tone?: string | null): string {
  return `${normalizeRideAlertTone(tone)}.wav`;
}

let modeReady = false;
let current: AudioPlayer | null = null;

export function normalizeRideAlertTone(value?: string | null): RideAlertTone {
  const key = String(value || '').toLowerCase();
  return (RIDE_ALERT_TONES as readonly string[]).includes(key)
    ? (key as RideAlertTone)
    : 'classic';
}

async function ensureAudioMode(): Promise<void> {
  if (modeReady) return;
  await setAudioModeAsync({
    allowsRecording: false,
    playsInSilentMode: true,
    shouldPlayInBackground: true,
    shouldRouteThroughEarpiece: false,
    interruptionMode: 'doNotMix',
  });
  await setIsAudioActiveAsync(true);
  modeReady = true;
}

export async function playRideAlertTone(tone?: string | null): Promise<void> {
  const key = normalizeRideAlertTone(tone);

  try {
    await ensureAudioMode();

    if (current) {
      try {
        current.pause();
        current.remove();
      } catch {
        /* ignore */
      }
      current = null;
    }

    const player = createAudioPlayer(SOURCES[key], {
      updateInterval: 200,
      keepAudioSessionActive: true,
    });
    player.volume = 1;
    player.loop = false;
    player.addListener('playbackStatusUpdate', (status) => {
      if (!status.didJustFinish) return;
      try {
        player.remove();
      } catch {
        /* ignore */
      }
      if (current === player) current = null;
    });
    current = player;
    player.play();
  } catch {
    /* geluid niet beschikbaar */
  }
}
