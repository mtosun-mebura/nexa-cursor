import { Platform, TurboModuleRegistry } from 'react-native';
import Constants from 'expo-constants';
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

/** Zelfde wav als de lokale melding (classic.wav … siren.wav in de app-bundle). */
const FILE_NAME: Record<RideAlertTone, string> = {
  classic: 'classic.wav',
  chime: 'chime.wav',
  alert: 'alert.wav',
  soft: 'soft.wav',
  siren: 'siren.wav',
};

/** Eén speler voor alle chips. Geen nieuwe sessie of listener per tik. */
let player: AudioPlayer | null = null;
let modePromise: Promise<void> | null = null;
let modeReady = false;
let wantedKey: RideAlertTone | null = null;

export function normalizeRideAlertTone(value?: string | null): RideAlertTone {
  const key = String(value || '').toLowerCase();
  return (RIDE_ALERT_TONES as readonly string[]).includes(key)
    ? (key as RideAlertTone)
    : DEFAULT_RIDE_ALERT_TONE;
}

/** Bestandsnaam voor expo-notifications (wav in app.json sounds). */
export function rideAlertNotificationSound(tone?: string | null): string {
  return FILE_NAME[normalizeRideAlertTone(tone)];
}

/**
 * iOS: de notificatie-plugin kopieert de wav naar de app-root
 * (NexaTaxi.app/alert.wav). Dat is een echt bestand. Asset.downloadAsync
 * en de expo-asset-URL (https://expo.dev/assets/…) niet gebruiken:
 * AVPlayer speelt die niet, dus de chip blijft stil.
 */
function bundledToneUri(key: RideAlertTone): string | null {
  const fileName = FILE_NAME[key];
  if (Platform.OS === 'ios') {
    try {
      // Release-bundle: file://…/NexaTaxi.app/main.jsbundle
      const source = TurboModuleRegistry.get<{
        getConstants: () => { scriptURL?: string };
      }>('SourceCode');
      const scriptURL = String(source?.getConstants()?.scriptURL || '');
      if (scriptURL.startsWith('file')) {
        const slash = scriptURL.lastIndexOf('/');
        if (slash > 0) return `${scriptURL.slice(0, slash + 1)}${fileName}`;
      }
    } catch {
      return null;
    }
    return null;
  }
  const pkg = Constants.expoConfig?.android?.package || 'nl.nexasuite.taxi';
  return `android.resource://${pkg}/raw/${key}`;
}

/**
 * iOS soloAmbient: beltoonvolume. Stille schakelaar uit = geluid.
 * Playback (playsInSilentMode) volgt alleen het mediavolume.
 * Eén keer configureren — niet bij elke tik.
 */
export function prepareRideAlertAudio(): Promise<void> {
  if (modeReady) return Promise.resolve();
  if (!modePromise) {
    modePromise = setAudioModeAsync({
      allowsRecording: false,
      playsInSilentMode: false,
      shouldPlayInBackground: false,
      shouldRouteThroughEarpiece: false,
      interruptionMode: 'doNotMix',
    })
      .then(() => setIsAudioActiveAsync(true))
      .then(() => {
        modeReady = true;
      })
      .catch((error) => {
        modePromise = null;
        modeReady = false;
        throw error;
      });
  }
  return modePromise;
}

function ensurePlayer(key: RideAlertTone): boolean {
  if (player) return true;
  const uri = bundledToneUri(key);
  if (!uri) return false;
  // Eén speler, lange interval: de native time-observer anders elke 500ms naar JS.
  // Geen playbackStatusUpdate-listener.
  player = createAudioPlayer(
    { uri },
    { updateInterval: 30000, keepAudioSessionActive: true }
  );
  player.volume = 1;
  player.loop = false;
  return true;
}

/** Maak de speler alvast bij online gaan, niet op het moment dat een aanbod binnenkomt. */
export function primeRideAlertPlayer(): void {
  void prepareRideAlertAudio().then(
    () => {
      try {
        ensurePlayer('alert');
      } catch {
        /* volgende play probeert het opnieuw */
      }
    },
    () => undefined
  );
}

function startPlayback(key: RideAlertTone): boolean {
  const uri = bundledToneUri(key);
  if (!uri) return false;
  if (!player) {
    if (!ensurePlayer(key)) return false;
  } else if (key !== 'alert') {
    player.replace({ uri });
  }
  if (key === 'alert' && player) {
    void player.seekTo(0).catch(() => undefined);
  }
  player?.play();
  return true;
}

/**
 * Eén keer afspelen. Wacht niet op setAudioMode of een nieuwe speler:
 * die native calls blokkeren de JS-thread als ze in de aanbod-beurt lopen.
 */
/** Alleen de speler die bij online gaan klaarstaat. Geen sessie of nieuwe speler. */
export function playPreparedRideAlert(): void {
  if (!modeReady || !player) return;
  try {
    startPlayback('alert');
  } catch {
    /* één poging */
  }
}

export function playRideAlertTone(tone?: string | null): void {
  const key = normalizeRideAlertTone(tone);
  wantedKey = key;
  if (modeReady && player) {
    try {
      startPlayback(key);
    } catch {
      /* één poging */
    }
    return;
  }
  void prepareRideAlertAudio().then(
    () => {
      if (wantedKey !== key) return;
      try {
        startPlayback(key);
      } catch {
        /* één poging */
      }
    },
    () => undefined
  );
}

/** Pauzeer de gedeelde speler (bijv. bij schermwissels). Niet weggooien. */
export function stopRideAlertTone(): void {
  try {
    player?.pause();
  } catch {
    /* ignore */
  }
}
