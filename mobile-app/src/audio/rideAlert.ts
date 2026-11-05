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

/** Standaard = Bel (chime), gelijk aan taxi/app op prod. */
export const DEFAULT_RIDE_ALERT_TONE: RideAlertTone = 'chime';

export const RIDE_ALERT_OPTIONS: { key: RideAlertTone; label: string }[] = [
  { key: 'classic', label: 'Klassiek' },
  { key: 'chime', label: 'Bel' },
  { key: 'alert', label: 'Alert' },
  { key: 'soft', label: 'Zacht' },
  { key: 'siren', label: 'Sirene' },
];

const TONE_FILES: Record<RideAlertTone, string> = {
  classic: 'classic.wav',
  chime: 'chime.wav',
  alert: 'alert.wav',
  soft: 'soft.wav',
  siren: 'siren.wav',
};

let player: AudioPlayer | null = null;
let playerTone: RideAlertTone | null = null;
let modePromise: Promise<void> | null = null;
let modeReady = false;
let currentTone: RideAlertTone = DEFAULT_RIDE_ALERT_TONE;

export function normalizeRideAlertTone(value?: string | null): RideAlertTone {
  const key = String(value || '')
    .trim()
    .toLowerCase();
  return (RIDE_ALERT_TONES as readonly string[]).includes(key)
    ? (key as RideAlertTone)
    : DEFAULT_RIDE_ALERT_TONE;
}

/** Bestandsnaam voor expo-notifications én Expo-push (in app-bundle). */
export function rideAlertNotificationSound(tone?: string | null): string {
  return TONE_FILES[normalizeRideAlertTone(tone ?? currentTone)];
}

export function setActiveRideAlertTone(tone?: string | null): RideAlertTone {
  currentTone = normalizeRideAlertTone(tone);
  return currentTone;
}

export function activeRideAlertTone(): RideAlertTone {
  return currentTone;
}

function bundledToneUri(tone: RideAlertTone): string | null {
  const file = TONE_FILES[tone];
  if (Platform.OS === 'ios') {
    try {
      const source = TurboModuleRegistry.get<{
        getConstants: () => { scriptURL?: string };
      }>('SourceCode');
      const scriptURL = String(source?.getConstants()?.scriptURL || '');
      if (scriptURL.startsWith('file')) {
        const slash = scriptURL.lastIndexOf('/');
        if (slash > 0) return `${scriptURL.slice(0, slash + 1)}${file}`;
      }
    } catch {
      /* fallback */
    }
  } else {
    const pkg = Constants.expoConfig?.android?.package || 'nl.nexasuite.taxi';
    const base = file.replace(/\.wav$/i, '');
    return `android.resource://${pkg}/raw/${base}`;
  }
  try {
    const mod = requireToneModule(tone);
    if (typeof mod === 'number') {
      // eslint-disable-next-line @typescript-eslint/no-require-imports
      const Asset = require('expo-asset').Asset as {
        fromModule: (m: number) => { uri?: string; localUri?: string };
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

function requireToneModule(tone: RideAlertTone): number | { uri?: string } | null {
  try {
    switch (tone) {
      case 'classic':
        // eslint-disable-next-line @typescript-eslint/no-require-imports
        return require('../../assets/sounds/classic.wav');
      case 'alert':
        // eslint-disable-next-line @typescript-eslint/no-require-imports
        return require('../../assets/sounds/alert.wav');
      case 'soft':
        // eslint-disable-next-line @typescript-eslint/no-require-imports
        return require('../../assets/sounds/soft.wav');
      case 'siren':
        // eslint-disable-next-line @typescript-eslint/no-require-imports
        return require('../../assets/sounds/siren.wav');
      case 'chime':
      default:
        // eslint-disable-next-line @typescript-eslint/no-require-imports
        return require('../../assets/sounds/chime.wav');
    }
  } catch {
    return null;
  }
}

async function applyAlertAudioMode(background: boolean): Promise<void> {
  await setAudioModeAsync({
    allowsRecording: false,
    playsInSilentMode: true,
    shouldPlayInBackground: background,
    shouldRouteThroughEarpiece: false,
    interruptionMode: 'doNotMix',
  });
  await setIsAudioActiveAsync(true);
}

export function prepareRideAlertAudio(): Promise<void> {
  if (modeReady) return Promise.resolve();
  if (!modePromise) {
    modePromise = applyAlertAudioMode(false)
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

function ensurePlayer(tone: RideAlertTone): boolean {
  if (player && playerTone === tone) return true;
  try {
    player?.remove();
  } catch {
    /* ignore */
  }
  player = null;
  playerTone = null;
  const mod = requireToneModule(tone);
  try {
    if (mod != null) {
      player = createAudioPlayer(mod, { updateInterval: 30000, keepAudioSessionActive: true });
    } else {
      const uri = bundledToneUri(tone);
      if (!uri) return false;
      player = createAudioPlayer({ uri }, { updateInterval: 30000, keepAudioSessionActive: true });
    }
  } catch {
    return false;
  }
  player.volume = 1;
  player.loop = false;
  playerTone = tone;
  return true;
}

export function primeRideAlertPlayer(): void {
  void prepareRideAlertAudio().then(
    () => {
      try {
        ensurePlayer(currentTone);
      } catch {
        /* volgende play */
      }
    },
    () => undefined
  );
}

function startPlayback(tone: RideAlertTone): boolean {
  if (!ensurePlayer(tone)) return false;
  try {
    player!.volume = 1;
    void player!.seekTo(0).catch(() => undefined);
    player!.play();
    return true;
  } catch {
    return false;
  }
}

export function playPreparedRideAlert(): void {
  playRideAlertTone(currentTone);
}

/** Speelt gekozen ritgeluid (standaard Bel/chime). Werkt ook bij stille schakelaar. */
export function playRideAlertTone(tone?: string | null): void {
  const next = normalizeRideAlertTone(tone ?? currentTone);
  currentTone = next;
  void (async () => {
    try {
      await applyAlertAudioMode(true);
      modeReady = true;
      startPlayback(next);
    } catch {
      try {
        await prepareRideAlertAudio();
        startPlayback(next);
      } catch {
        /* één poging */
      }
    } finally {
      setTimeout(() => {
        void applyAlertAudioMode(false).catch(() => undefined);
      }, 2500);
    }
  })();
}

export function stopRideAlertTone(): void {
  try {
    player?.pause();
  } catch {
    /* ignore */
  }
}
