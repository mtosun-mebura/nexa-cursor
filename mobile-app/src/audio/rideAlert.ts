import { Asset } from 'expo-asset';
import { requireOptionalNativeModule } from 'expo-modules-core';

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

type NativeAudio = {
  setAudioModeAsync?: (mode: Record<string, unknown>) => Promise<void>;
  AudioPlayer?: new (
    source: { uri: string } | null,
    updateInterval: number,
    keepAudioSessionActive: boolean,
    preferredForwardBufferDuration: number
  ) => {
    volume: number;
    loop: boolean;
    play: () => void;
    pause: () => void;
    remove: () => void;
    addListener: (event: string, cb: (status: { didJustFinish?: boolean }) => void) => unknown;
  };
};

let nativeAudio: NativeAudio | null | undefined;
let ready = false;
let current: InstanceType<NonNullable<NativeAudio['AudioPlayer']>> | null = null;

export function normalizeRideAlertTone(value?: string | null): RideAlertTone {
  const key = String(value || '').toLowerCase();
  return (RIDE_ALERT_TONES as readonly string[]).includes(key)
    ? (key as RideAlertTone)
    : 'classic';
}

function getNativeAudio(): NativeAudio | null {
  if (nativeAudio !== undefined) return nativeAudio;
  nativeAudio = requireOptionalNativeModule<NativeAudio>('ExpoAudio');
  return nativeAudio;
}

async function ensureAudioMode(native: NativeAudio) {
  if (ready || typeof native.setAudioModeAsync !== 'function') return;
  await native.setAudioModeAsync({
    allowsRecording: false,
    playsInSilentMode: true,
    shouldPlayInBackground: false,
    shouldRouteThroughEarpiece: false,
    interruptionMode: 'doNotMix',
  });
  ready = true;
}

async function resolveUri(moduleId: number): Promise<string | null> {
  const asset = Asset.fromModule(moduleId);
  if (!asset.localUri) {
    await asset.downloadAsync();
  }
  return asset.localUri || asset.uri || null;
}

export async function playRideAlertTone(tone?: string | null): Promise<void> {
  const key = normalizeRideAlertTone(tone);
  const native = getNativeAudio();
  const Player = native?.AudioPlayer;
  if (!native || !Player) return;

  try {
    await ensureAudioMode(native);
    const uri = await resolveUri(SOURCES[key]);
    if (!uri) return;

    if (current) {
      try {
        current.pause();
        current.remove();
      } catch {
        /* ignore */
      }
      current = null;
    }

    const player = new Player({ uri }, 100, false, 0);
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
