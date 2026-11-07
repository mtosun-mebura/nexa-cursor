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
/** Houdt AVAudioSession actief terwijl chauffeur online is (nodig voor Bel op slot). */
let keepAlivePlayer: AudioPlayer | null = null;
let modePromise: Promise<void> | null = null;
let modeReady = false;
let currentTone: RideAlertTone = DEFAULT_RIDE_ALERT_TONE;
let backgroundArmed = false;
let disarmTimer: ReturnType<typeof setTimeout> | null = null;
let iosSoundsInstall: Promise<void> | null = null;

export function normalizeRideAlertTone(value?: string | null): RideAlertTone {
  const key = String(value || '')
    .trim()
    .toLowerCase();
  return (RIDE_ALERT_TONES as readonly string[]).includes(key)
    ? (key as RideAlertTone)
    : DEFAULT_RIDE_ALERT_TONE;
}

/**
 * Bestandsnaam voor expo-notifications / Expo-push.
 * Altijd `.wav` — zit in de main bundle én (na install) in Library/Sounds.
 */
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

/**
 * Kopieer ritgeluiden naar Library/Sounds zodat UNNotificationSound ze vindt
 * (main bundle + Library/Sounds). Zonder dit speelt iOS vaak de systeemtriangel.
 */
export function ensureIosNotificationSoundsInstalled(): Promise<void> {
  if (Platform.OS !== 'ios') return Promise.resolve();
  if (!iosSoundsInstall) {
    iosSoundsInstall = (async () => {
      try {
        // eslint-disable-next-line @typescript-eslint/no-require-imports
        const FileSystem = require('expo-file-system/legacy') as {
          documentDirectory: string | null;
          makeDirectoryAsync: (path: string, opts?: { intermediates?: boolean }) => Promise<void>;
          getInfoAsync: (path: string) => Promise<{ exists: boolean }>;
          copyAsync: (opts: { from: string; to: string }) => Promise<void>;
        };
        // eslint-disable-next-line @typescript-eslint/no-require-imports
        const { Asset } = require('expo-asset') as {
          Asset: {
            fromModule: (m: number) => {
              downloadAsync: () => Promise<unknown>;
              localUri?: string | null;
              uri?: string;
            };
          };
        };
        const docs = FileSystem.documentDirectory;
        if (!docs) return;
        const soundsDir = docs.replace(/Documents\/?$/, 'Library/Sounds/');
        await FileSystem.makeDirectoryAsync(soundsDir, { intermediates: true }).catch(() => undefined);
        for (const tone of RIDE_ALERT_TONES) {
          const file = TONE_FILES[tone];
          const dest = `${soundsDir}${file}`;
          const info = await FileSystem.getInfoAsync(dest).catch(() => ({ exists: false }));
          if (info.exists) continue;
          const mod = requireToneModule(tone);
          if (typeof mod !== 'number') continue;
          const asset = Asset.fromModule(mod);
          await asset.downloadAsync();
          const from = asset.localUri || asset.uri;
          if (!from) continue;
          await FileSystem.copyAsync({ from, to: dest }).catch(() => undefined);
        }
      } catch {
        iosSoundsInstall = null;
      }
    })();
  }
  return iosSoundsInstall;
}

function stopKeepAlive(): void {
  try {
    keepAlivePlayer?.pause();
    keepAlivePlayer?.remove();
  } catch {
    /* ignore */
  }
  keepAlivePlayer = null;
}

function startKeepAlive(): void {
  if (keepAlivePlayer) {
    try {
      keepAlivePlayer.volume = 0;
      keepAlivePlayer.loop = true;
      keepAlivePlayer.play();
      return;
    } catch {
      stopKeepAlive();
    }
  }
  const mod = requireToneModule('soft');
  try {
    if (mod != null) {
      keepAlivePlayer = createAudioPlayer(mod, {
        updateInterval: 60000,
        keepAudioSessionActive: true,
      });
    } else {
      const uri = bundledToneUri('soft');
      if (!uri) return;
      keepAlivePlayer = createAudioPlayer(
        { uri },
        { updateInterval: 60000, keepAudioSessionActive: true }
      );
    }
    keepAlivePlayer.volume = 0;
    keepAlivePlayer.loop = true;
    keepAlivePlayer.play();
  } catch {
    stopKeepAlive();
  }
}

/** Chauffeur online: audio-sessie + keep-alive zodat Bel op slot/achtergrond speelt. */
export function armRideAlertBackgroundAudio(): void {
  backgroundArmed = true;
  if (disarmTimer) {
    clearTimeout(disarmTimer);
    disarmTimer = null;
  }
  void ensureIosNotificationSoundsInstalled();
  void applyAlertAudioMode(true)
    .then(() => {
      modeReady = true;
      startKeepAlive();
      try {
        ensurePlayer(currentTone);
      } catch {
        /* volgende play */
      }
    })
    .catch(() => undefined);
}

/** Chauffeur offline. */
export function disarmRideAlertBackgroundAudio(): void {
  backgroundArmed = false;
  if (disarmTimer) {
    clearTimeout(disarmTimer);
    disarmTimer = null;
  }
  stopKeepAlive();
  void applyAlertAudioMode(false).catch(() => undefined);
}

export function prepareRideAlertAudio(): Promise<void> {
  if (modeReady) return Promise.resolve();
  if (!modePromise) {
    modePromise = applyAlertAudioMode(backgroundArmed)
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
  void ensureIosNotificationSoundsInstalled();
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
  void playRideAlertToneAsync(tone);
}

/** Zelfde als playRideAlertTone, met succes van start (niet garantie dat je het hoort op slot). */
export async function playRideAlertToneAsync(tone?: string | null): Promise<boolean> {
  const next = normalizeRideAlertTone(tone ?? currentTone);
  currentTone = next;
  if (disarmTimer) {
    clearTimeout(disarmTimer);
    disarmTimer = null;
  }
  try {
    await applyAlertAudioMode(true);
    modeReady = true;
    if (backgroundArmed) startKeepAlive();
    if (!startPlayback(next)) return false;
  } catch {
    try {
      await prepareRideAlertAudio();
      if (!startPlayback(next)) return false;
    } catch {
      return false;
    }
  }
  if (!backgroundArmed) {
    disarmTimer = setTimeout(() => {
      disarmTimer = null;
      void applyAlertAudioMode(false).catch(() => undefined);
    }, 2500);
  }
  return true;
}

export function stopRideAlertTone(): void {
  try {
    player?.pause();
  } catch {
    /* ignore */
  }
}
