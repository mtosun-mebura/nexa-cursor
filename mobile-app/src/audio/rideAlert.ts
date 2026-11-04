import { Platform, TurboModuleRegistry } from 'react-native';
import Constants from 'expo-constants';
import {
  createAudioPlayer,
  setAudioModeAsync,
  setIsAudioActiveAsync,
  type AudioPlayer,
} from 'expo-audio';

/** Eén vaste toon — geen chip-preview / replace (dat liet de app vastlopen). */
export const RIDE_ALERT_TONES = ['alert'] as const;
export type RideAlertTone = (typeof RIDE_ALERT_TONES)[number];

export const DEFAULT_RIDE_ALERT_TONE: RideAlertTone = 'alert';

export const RIDE_ALERT_OPTIONS: { key: RideAlertTone; label: string }[] = [
  { key: 'alert', label: 'Alert' },
];

const ALERT_FILE = 'alert.wav';

let player: AudioPlayer | null = null;
let modePromise: Promise<void> | null = null;
let modeReady = false;

export function normalizeRideAlertTone(_value?: string | null): RideAlertTone {
  return DEFAULT_RIDE_ALERT_TONE;
}

/** Bestandsnaam voor expo-notifications én Expo-push (in app-bundle). */
export function rideAlertNotificationSound(_tone?: string | null): string {
  return ALERT_FILE;
}

function bundledAlertUri(): string | null {
  if (Platform.OS === 'ios') {
    try {
      const source = TurboModuleRegistry.get<{
        getConstants: () => { scriptURL?: string };
      }>('SourceCode');
      const scriptURL = String(source?.getConstants()?.scriptURL || '');
      if (scriptURL.startsWith('file')) {
        const slash = scriptURL.lastIndexOf('/');
        if (slash > 0) return `${scriptURL.slice(0, slash + 1)}${ALERT_FILE}`;
      }
    } catch {
      /* fallback hieronder */
    }
  } else {
    const pkg = Constants.expoConfig?.android?.package || 'nl.nexasuite.taxi';
    return `android.resource://${pkg}/raw/alert`;
  }
  // Dev/Metro: scriptURL is http — bundel via require.
  try {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    const mod = require('../../assets/sounds/alert.wav') as number | { uri?: string };
    if (typeof mod === 'number') {
      // eslint-disable-next-line @typescript-eslint/no-require-imports
      const Asset = require('expo-asset').Asset as {
        fromModule: (m: number) => { uri?: string; localUri?: string; downloadAsync: () => Promise<unknown> };
      };
      const asset = Asset.fromModule(mod);
      return asset.localUri || asset.uri || null;
    }
    if (mod && typeof mod === 'object' && typeof mod.uri === 'string') return mod.uri;
  } catch {
    /* ignore */
  }
  return null;
}

export function prepareRideAlertAudio(): Promise<void> {
  if (modeReady) return Promise.resolve();
  if (!modePromise) {
    modePromise = setAudioModeAsync({
      allowsRecording: false,
      // Stille schakelaar mag het rit-alarm niet dempen (zoals een melding).
      playsInSilentMode: true,
      // Geen background-audio-sessie — die liet de app crashen tijdens idle online.
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

function ensurePlayer(): boolean {
  if (player) return true;
  try {
    // Prefer require (werkt in Metro én release).
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    const mod = require('../../assets/sounds/alert.wav');
    player = createAudioPlayer(mod, { updateInterval: 30000, keepAudioSessionActive: true });
  } catch {
    const uri = bundledAlertUri();
    if (!uri) return false;
    player = createAudioPlayer(
      { uri },
      { updateInterval: 30000, keepAudioSessionActive: true }
    );
  }
  player.volume = 1;
  player.loop = false;
  return true;
}

export function primeRideAlertPlayer(): void {
  void prepareRideAlertAudio().then(
    async () => {
      try {
        // Dev: asset downloaden zodat localUri klaar is.
        try {
          // eslint-disable-next-line @typescript-eslint/no-require-imports
          const Asset = require('expo-asset').Asset as {
            fromModule: (m: number) => { downloadAsync: () => Promise<unknown> };
          };
          // eslint-disable-next-line @typescript-eslint/no-require-imports
          const mod = require('../../assets/sounds/alert.wav');
          if (typeof mod === 'number') await Asset.fromModule(mod).downloadAsync();
        } catch {
          /* release heeft file:// */
        }
        ensurePlayer();
      } catch {
        /* volgende play probeert het opnieuw */
      }
    },
    () => undefined
  );
}

function startPlayback(): boolean {
  if (!player && !ensurePlayer()) return false;
  void player?.seekTo(0).catch(() => undefined);
  player?.play();
  return true;
}

/** Alleen als speler al klaar is (geen native setup op de aanbod-beurt). */
export function playPreparedRideAlert(): void {
  if (!modeReady || !player) {
    playRideAlertTone('alert');
    return;
  }
  try {
    startPlayback();
  } catch {
    /* één poging */
  }
}

/** Bereidt audio indien nodig, speelt daarna alert.wav. */
export function playRideAlertTone(_tone?: string | null): void {
  if (modeReady && player) {
    try {
      startPlayback();
    } catch {
      /* één poging */
    }
    return;
  }
  void prepareRideAlertAudio().then(
    () => {
      try {
        startPlayback();
      } catch {
        /* één poging */
      }
    },
    () => undefined
  );
}

export function stopRideAlertTone(): void {
  try {
    player?.pause();
  } catch {
    /* ignore */
  }
}
