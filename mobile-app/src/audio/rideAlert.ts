import { AppState } from 'react-native';
import {
  createAudioPlayer,
  setAudioModeAsync,
  setIsAudioActiveAsync,
  type AudioPlayer,
} from 'expo-audio';

export const RIDE_ALERT_TONES = ['classic', 'chime', 'alert', 'soft', 'siren'] as const;
export type RideAlertTone = (typeof RIDE_ALERT_TONES)[number];

/** Nieuwe installatie / geen opgeslagen keuze. Een gekozen chip blijft staan. */
export const DEFAULT_RIDE_ALERT_TONE: RideAlertTone = 'alert';

export const RIDE_ALERT_OPTIONS: { key: RideAlertTone; label: string }[] = [
  { key: 'classic', label: 'Klassiek' },
  { key: 'chime', label: 'Bel' },
  { key: 'alert', label: 'Alert' },
  { key: 'soft', label: 'Zacht' },
  { key: 'siren', label: 'Sirene' },
];

/** Zelfde wav als de lokale melding (classic.wav … siren.wav in de app-bundle). Eén bestand per naam. */
const SOURCES: Record<RideAlertTone, number> = {
  classic: require('../../assets/sounds/classic.wav'),
  chime: require('../../assets/sounds/chime.wav'),
  alert: require('../../assets/sounds/alert.wav'),
  soft: require('../../assets/sounds/soft.wav'),
  siren: require('../../assets/sounds/siren.wav'),
};

/** Bestandsnaam voor expo-notifications (wav in app.json sounds). */
export function rideAlertNotificationSound(tone?: string | null): string {
  return `${normalizeRideAlertTone(tone)}.wav`;
}

let current: AudioPlayer | null = null;
let playChain: Promise<boolean> = Promise.resolve(false);
let audioModeTask: Promise<void> | null = null;

export function normalizeRideAlertTone(value?: string | null): RideAlertTone {
  const key = String(value || '').toLowerCase();
  return (RIDE_ALERT_TONES as readonly string[]).includes(key)
    ? (key as RideAlertTone)
    : DEFAULT_RIDE_ALERT_TONE;
}

/**
 * iOS soloAmbient: beltoonvolume, stil bij de stille schakelaar.
 * Playback (playsInSilentMode) volgt alleen het mediavolume.
 * Eén keer configureren — niet bij elke tik. Na achtergrond opnieuw, omdat iOS de sessie dan loslaat.
 */
export function prepareRideAlertAudio(): Promise<void> {
  if (audioModeTask) return audioModeTask;
  const task = setAudioModeAsync({
    allowsRecording: false,
    playsInSilentMode: false,
    shouldPlayInBackground: false,
    shouldRouteThroughEarpiece: false,
    interruptionMode: 'doNotMix',
  })
    .then(() => setIsAudioActiveAsync(true))
    .catch((error) => {
      if (audioModeTask === task) audioModeTask = null;
      throw error;
    });
  audioModeTask = task;
  return task;
}

AppState.addEventListener('change', (state) => {
  if (state === 'background') audioModeTask = null;
});

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

function waitUntilLoaded(player: AudioPlayer, timeoutMs = 1200): Promise<boolean> {
  if (player.isLoaded && !player.currentStatus.error) return Promise.resolve(true);
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
    const sub = player.addListener('playbackStatusUpdate', (status) => {
      if (status.error) finish(false);
      else if (player.isLoaded) finish(true);
    });
    const poll = setInterval(() => {
      const error = player.currentStatus?.error;
      if (error) {
        finish(false);
      } else if (player.isLoaded) {
        finish(true);
      } else if (Date.now() - started >= timeoutMs) {
        finish(false);
      }
    }, 50);
  });
}

async function playOnce(key: RideAlertTone): Promise<boolean> {
  let player: AudioPlayer | null = null;
  try {
    await prepareRideAlertAudio();

    if (current) {
      releasePlayer(current);
      current = null;
    }

    player = createAudioPlayer(SOURCES[key], {
      updateInterval: 250,
      keepAudioSessionActive: true,
    });
    player.volume = 1;
    player.loop = false;
    current = player;

    const loaded = await waitUntilLoaded(player);
    if (!loaded || current !== player) {
      releasePlayer(player);
      if (current === player) current = null;
      return false;
    }

    const releaseTimer = setTimeout(() => {
      releasePlayer(player);
      if (current === player) current = null;
    }, 8000);
    player.addListener('playbackStatusUpdate', (status) => {
      if (!status.didJustFinish && !status.error) return;
      clearTimeout(releaseTimer);
      releasePlayer(player);
      if (current === player) current = null;
    });

    player.play();
    return true;
  } catch {
    releasePlayer(player);
    if (current === player) current = null;
    return false;
  }
}

/** Speelt de gekozen toon; true als starten lukte. Stil als de telefoon op de stille stand staat. */
export function playRideAlertTone(tone?: string | null): Promise<boolean> {
  const key = normalizeRideAlertTone(tone);
  const run = playChain.then(() => playOnce(key));
  playChain = run.then(
    () => false,
    () => false
  );
  return run;
}

/** Stop lopende preview/alert (bijv. bij schermwissels). */
export function stopRideAlertTone(): void {
  releasePlayer(current);
  current = null;
}
