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

let current: AudioPlayer | null = null;
let playChain: Promise<boolean> = Promise.resolve(false);

export function normalizeRideAlertTone(value?: string | null): RideAlertTone {
  const key = String(value || '').toLowerCase();
  return (RIDE_ALERT_TONES as readonly string[]).includes(key)
    ? (key as RideAlertTone)
    : 'classic';
}

/**
 * AVAudioSession .playback + playsInSilentMode: speelt ook bij iPhone stille stand
 * (mute-schakelaar). Media-volume 0 blijft stil. Background: UIBackgroundModes audio.
 */
export async function prepareRideAlertAudio(): Promise<void> {
  await setAudioModeAsync({
    allowsRecording: false,
    playsInSilentMode: true,
    shouldPlayInBackground: true,
    shouldRouteThroughEarpiece: false,
    interruptionMode: 'doNotMix',
  });
  await setIsAudioActiveAsync(true);
}

function releasePlayer(player: AudioPlayer | null): void {
  if (!player) return;
  try {
    player.pause();
  } catch {
    /* ignore */
  }
  try {
    player.remove();
  } catch {
    /* ignore */
  }
}

function waitUntilLoaded(player: AudioPlayer, timeoutMs = 2500): Promise<boolean> {
  if (player.isLoaded) return Promise.resolve(true);
  return new Promise((resolve) => {
    const started = Date.now();
    let settled = false;
    const finish = (ok: boolean) => {
      if (settled) return;
      settled = true;
      clearInterval(poll);
      try {
        sub.remove();
      } catch {
        /* ignore */
      }
      resolve(ok);
    };
    const sub = player.addListener('playbackStatusUpdate', () => {
      if (player.isLoaded) finish(true);
    });
    const poll = setInterval(() => {
      if (player.isLoaded) {
        finish(true);
      } else if (Date.now() - started >= timeoutMs) {
        finish(false);
      }
    }, 40);
  });
}

async function playOnce(key: RideAlertTone): Promise<boolean> {
  try {
    // Elke keer opnieuw: iOS deactiveert de sessie vaak na achtergrond/GPS-wake.
    await prepareRideAlertAudio();

    if (current) {
      releasePlayer(current);
      current = null;
    }

    const player = createAudioPlayer(SOURCES[key], {
      updateInterval: 100,
      keepAudioSessionActive: true,
    });
    player.volume = 1;
    player.loop = false;
    current = player;

    await waitUntilLoaded(player);
    await setIsAudioActiveAsync(true);

    player.addListener('playbackStatusUpdate', (status) => {
      if (!status.didJustFinish) return;
      releasePlayer(player);
      if (current === player) current = null;
    });

    player.play();
    return true;
  } catch {
    return false;
  }
}

/** Speelt de gekozen toon; true als starten lukte. Werkt in stille stand en achtergrond. */
export function playRideAlertTone(tone?: string | null): Promise<boolean> {
  const key = normalizeRideAlertTone(tone);
  const run = playChain.then(() => playOnce(key));
  playChain = run.then(
    () => false,
    () => false
  );
  return run;
}
