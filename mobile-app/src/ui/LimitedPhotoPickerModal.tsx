import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  Pressable,
  StyleSheet,
  Text,
  useWindowDimensions,
  View,
} from 'react-native';
import { t } from '../i18n';
import { ColorPalette } from '../config';
import { useThemeColors } from '../theme/ThemeContext';
import { AppModal } from './AppModal';

type MediaLibraryModule = typeof import('expo-media-library/legacy');
type LibraryAsset = import('expo-media-library/legacy').Asset;

export type PickedLibraryImage = { uri: string; mimeType: string | null };

const PAGE_SIZE = 60;
const GAP = 6;
const COLUMNS = 3;

let cachedModule: MediaLibraryModule | null | undefined;

/**
 * expo-media-library zit in de native build; lazy laden zodat een oudere build
 * zonder de module de rest van de app niet laat crashen (dan blijft ImagePicker).
 */
export function loadMediaLibrary(): MediaLibraryModule | null {
  if (cachedModule !== undefined) return cachedModule;
  try {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    cachedModule = require('expo-media-library/legacy') as MediaLibraryModule;
  } catch {
    cachedModule = null;
  }
  return cachedModule;
}

function mimeFromFilename(filename: string | undefined): string | null {
  const ext = (filename || '').split('.').pop()?.toLowerCase();
  if (ext === 'png') return 'image/png';
  if (ext === 'jpg' || ext === 'jpeg') return 'image/jpeg';
  if (ext === 'webp') return 'image/webp';
  if (ext === 'heic' || ext === 'heif') return 'image/heic';
  return null;
}

type Props = {
  visible: boolean;
  onClose: () => void;
  onPick: (image: PickedLibraryImage) => void;
  /** Zonder eigen Modal, voor gebruik binnen een andere AppModal (iOS kan geen twee Modals stapelen). */
  embedded?: boolean;
};

/**
 * iOS "beperkte toegang": toont alleen de foto's waarvoor de gebruiker toestemming gaf,
 * met een knop om er meer aan toe te voegen (verschijnen direct via de library-listener).
 */
export function LimitedPhotoPickerModal({ visible, onClose, onPick, embedded = false }: Props) {
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const { width } = useWindowDimensions();
  const inner = Math.min(width - 48, 400) - 34;
  const tile = Math.floor((inner - GAP * (COLUMNS - 1)) / COLUMNS);

  const [assets, setAssets] = useState<LibraryAsset[]>([]);
  const [cursor, setCursor] = useState<string | undefined>();
  const [hasMore, setHasMore] = useState(false);
  const [loading, setLoading] = useState(false);
  const [busyId, setBusyId] = useState<string | null>(null);
  const requestId = useRef(0);

  const load = useCallback(async (after?: string) => {
    const ml = loadMediaLibrary();
    if (!ml) return;
    const id = ++requestId.current;
    setLoading(true);
    try {
      const page = await ml.getAssetsAsync({
        first: PAGE_SIZE,
        after,
        mediaType: 'photo',
        sortBy: [['creationTime', false]],
      });
      if (id !== requestId.current) return;
      setAssets((prev) => (after ? [...prev, ...page.assets] : page.assets));
      setCursor(page.endCursor);
      setHasMore(page.hasNextPage);
    } catch {
      if (id === requestId.current && !after) setAssets([]);
    } finally {
      if (id === requestId.current) setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!visible) return;
    const ml = loadMediaLibrary();
    if (!ml) return;
    setAssets([]);
    setHasMore(false);
    void load();
    const sub = ml.addListener(() => {
      void load();
    });
    return () => sub.remove();
  }, [visible, load]);

  const addMore = async () => {
    const ml = loadMediaLibrary();
    if (!ml) return;
    try {
      await ml.presentPermissionsPickerAsync(['photo']);
    } catch {
      /* listener of herladen hieronder toont wat er wél is */
    }
    void load();
  };

  const choose = async (asset: LibraryAsset) => {
    const ml = loadMediaLibrary();
    if (!ml || busyId) return;
    setBusyId(asset.id);
    try {
      const info = await ml.getAssetInfoAsync(asset);
      onPick({
        uri: info.localUri || asset.uri,
        mimeType: mimeFromFilename(info.filename || asset.filename),
      });
    } catch {
      /* foto niet beschikbaar (bijv. iCloud offline): gebruiker kan een andere kiezen */
    } finally {
      setBusyId(null);
    }
  };

  const content = (
    <>
      <Text style={styles.title}>{t("Foto kiezen")}</Text>
      <Text style={styles.hint}>
        {t("Je geeft Nexa Taxi toegang tot een selectie foto’s. Voeg er meer aan toe als je een andere foto nodig hebt.")}
      </Text>
      <View style={styles.grid}>
        {assets.map((asset) => (
          <Pressable
            key={asset.id}
            onPress={() => void choose(asset)}
            style={[styles.tile, { width: tile, height: tile }]}
            accessibilityRole="imagebutton"
            accessibilityLabel={asset.filename}
          >
            <Image source={{ uri: asset.uri }} style={styles.tileImage} resizeMode="cover" />
            {busyId === asset.id ? (
              <View style={styles.tileBusy}>
                <ActivityIndicator color="#fff" />
              </View>
            ) : null}
          </Pressable>
        ))}
      </View>
      {loading && assets.length === 0 ? (
        <ActivityIndicator color={colors.primary} style={styles.spinner} />
      ) : null}
      {!loading && assets.length === 0 ? (
        <Text style={styles.hint}>{t("Geen toegestane foto’s gevonden.")}</Text>
      ) : null}
      {hasMore ? (
        <Pressable style={styles.ghostBtn} onPress={() => void load(cursor)} disabled={loading}>
          <Text style={styles.ghostText}>{loading ? t("Laden…") : t("Meer laden")}</Text>
        </Pressable>
      ) : null}
      <Pressable style={styles.primaryBtn} onPress={() => void addMore()}>
        <Text style={styles.primaryText}>{t("Meer foto’s toevoegen")}</Text>
      </Pressable>
      <Pressable style={styles.ghostBtn} onPress={onClose}>
        <Text style={styles.ghostText}>{t("Annuleren")}</Text>
      </Pressable>
    </>
  );

  if (embedded) return visible ? content : null;
  return (
    <AppModal visible={visible} onRequestClose={onClose}>
      {content}
    </AppModal>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    title: { color: colors.text, fontSize: 18, fontWeight: '700', marginBottom: 8 },
    hint: { color: colors.muted, fontSize: 13, lineHeight: 18, marginBottom: 12 },
    grid: { flexDirection: 'row', flexWrap: 'wrap', gap: GAP, marginBottom: 12 },
    tile: {
      borderRadius: 8,
      overflow: 'hidden',
      backgroundColor: colors.inputBg,
    },
    tileImage: { width: '100%', height: '100%' },
    tileBusy: {
      ...StyleSheet.absoluteFill,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: 'rgba(15, 23, 42, 0.45)',
    },
    spinner: { marginVertical: 16 },
    primaryBtn: {
      minHeight: 46,
      borderRadius: 12,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: colors.primary,
      marginBottom: 8,
    },
    primaryText: { color: '#fff', fontSize: 15, fontWeight: '700' },
    ghostBtn: {
      minHeight: 46,
      borderRadius: 12,
      alignItems: 'center',
      justifyContent: 'center',
      borderWidth: 1,
      borderColor: colors.border,
      marginBottom: 8,
    },
    ghostText: { color: colors.text, fontSize: 15, fontWeight: '600' },
  });
}
