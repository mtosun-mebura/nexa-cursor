import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  LayoutChangeEvent,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { manipulateAsync, SaveFormat } from 'expo-image-manipulator';
import { ColorPalette } from '../config';
import { useThemeColors } from '../theme/ThemeContext';

type CropResult = {
  uri: string;
  mimeType: 'image/png' | 'image/jpeg';
};

type Props = {
  visible: boolean;
  uri: string | null;
  /** Bron-MIME (png/webp behouden transparantie). */
  sourceMimeType?: string | null;
  /** Breedte / hoogte — 1 = vierkant (profielfoto), 4/3 = logo/auto. */
  aspectRatio?: number;
  /** Forceer PNG (bv. bedrijfslogo). */
  preferPng?: boolean;
  title?: string;
  /** In een al open popup, zonder eigen scherm. */
  embedded?: boolean;
  maxFrameHeight?: number;
  onCancel: () => void;
  onConfirm: (result: CropResult) => void;
};

type Size = { width: number; height: number };

/**
 * Bijsnijden met in- én uitzoomen (+/−) en slepen.
 * Zoom 100% = kader gevuld; lager = hele foto zichtbaar; hoger = dichter op bv. gezicht.
 */
function shouldPreserveAlpha(sourceMimeType?: string | null, preferPng?: boolean): boolean {
  if (preferPng) return true;
  const mime = (sourceMimeType || '').toLowerCase();
  return mime === 'image/png' || mime === 'image/webp';
}

export function ImageCropModal({
  visible,
  uri,
  sourceMimeType,
  aspectRatio = 1,
  preferPng = false,
  title = 'Bijsnijden',
  embedded = false,
  maxFrameHeight,
  onCancel,
  onConfirm,
}: Props) {
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const scrollRef = useRef<ScrollView>(null);

  const [natural, setNatural] = useState<Size | null>(null);
  const [frame, setFrame] = useState<Size>({ width: 280, height: 280 });
  const [zoom, setZoom] = useState(1);
  const [offset, setOffset] = useState({ x: 0, y: 0 });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!visible || !uri) {
      setNatural(null);
      setZoom(1);
      setOffset({ x: 0, y: 0 });
      setError(null);
      setBusy(false);
      return;
    }
    Image.getSize(
      uri,
      (width, height) => setNatural({ width, height }),
      () => setError('Afbeelding kon niet worden geladen.')
    );
  }, [visible, uri]);

  const onRootLayout = (e: LayoutChangeEvent) => {
    const maxW = Math.min(e.nativeEvent.layout.width - (embedded ? 0 : 32), 340);
    const width = Math.max(embedded ? 160 : 200, maxW);
    const fitted = width / Math.max(0.5, aspectRatio);
    const height = Math.max(embedded ? 120 : 160, maxFrameHeight ? Math.min(maxFrameHeight, fitted) : fitted);
    setFrame((prev) =>
      Math.abs(prev.width - width) < 1 && Math.abs(prev.height - height) < 1
        ? prev
        : { width, height }
    );
  };

  const scales = useMemo(() => {
    if (!natural) return null;
    const cover = Math.max(frame.width / natural.width, frame.height / natural.height);
    const fit = Math.min(frame.width / natural.width, frame.height / natural.height);
    // Relatief t.o.v. cover: 1 = kader vol, minZoom = hele foto in beeld.
    const minZoom = Math.min(1, fit / cover);
    const maxZoom = 4;
    return { cover, fit, minZoom, maxZoom };
  }, [natural, frame]);

  const display = useMemo(() => {
    if (!natural || !scales) return null;
    const scale = scales.cover * zoom;
    return {
      width: natural.width * scale,
      height: natural.height * scale,
      scale,
    };
  }, [natural, scales, zoom]);

  /** Content minstens zo groot als het kader, foto gecentreerd als uitgezoomd. */
  const content = useMemo(() => {
    if (!display) return null;
    const width = Math.max(display.width, frame.width);
    const height = Math.max(display.height, frame.height);
    return {
      width,
      height,
      padX: (width - display.width) / 2,
      padY: (height - display.height) / 2,
    };
  }, [display, frame]);

  const clampZoom = useCallback(
    (z: number) => {
      if (!scales) return z;
      return Math.min(scales.maxZoom, Math.max(scales.minZoom, Math.round(z * 100) / 100));
    },
    [scales]
  );

  const changeZoom = (delta: number) => {
    if (!scales || !content || !display) return;
    setZoom((z) => {
      const next = clampZoom(z + delta);
      const nextScale = scales.cover * next;
      const nextDispW = (natural?.width || 0) * nextScale;
      const nextDispH = (natural?.height || 0) * nextScale;
      const nextContentW = Math.max(nextDispW, frame.width);
      const nextContentH = Math.max(nextDispH, frame.height);
      const nextPadX = (nextContentW - nextDispW) / 2;
      const nextPadY = (nextContentH - nextDispH) / 2;
      // Houd het midden van het kader ongeveer stabiel.
      const centerX = offset.x + frame.width / 2;
      const centerY = offset.y + frame.height / 2;
      const relX = (centerX - content.padX) / Math.max(1, display.width);
      const relY = (centerY - content.padY) / Math.max(1, display.height);
      const newCenterX = nextPadX + relX * nextDispW;
      const newCenterY = nextPadY + relY * nextDispH;
      const maxX = Math.max(0, nextContentW - frame.width);
      const maxY = Math.max(0, nextContentH - frame.height);
      const nextX = Math.min(maxX, Math.max(0, newCenterX - frame.width / 2));
      const nextY = Math.min(maxY, Math.max(0, newCenterY - frame.height / 2));
      requestAnimationFrame(() => {
        scrollRef.current?.scrollTo({ x: nextX, y: nextY, animated: false });
      });
      setOffset({ x: nextX, y: nextY });
      return next;
    });
  };

  const exportCrop = async () => {
    if (!uri || !natural || !scales || !display || !content) return;
    setBusy(true);
    setError(null);
    try {
      const scale = display.scale;
      const imgOffsetX = offset.x - content.padX;
      const imgOffsetY = offset.y - content.padY;

      let originX = imgOffsetX / scale;
      let originY = imgOffsetY / scale;
      let width = frame.width / scale;
      let height = frame.height / scale;

      // Als het kader deels buiten de foto valt (uitgezoomd), snijd op de foto.
      if (originX < 0) {
        width += originX;
        originX = 0;
      }
      if (originY < 0) {
        height += originY;
        originY = 0;
      }
      width = Math.min(width, natural.width - originX);
      height = Math.min(height, natural.height - originY);

      if (width < 8 || height < 8) {
        setError('Zoom iets in of verschuif de foto.');
        return;
      }

      const crop = {
        originX: Math.round(originX),
        originY: Math.round(originY),
        width: Math.round(width),
        height: Math.round(height),
      };
      const outW = Math.min(1280, Math.max(crop.width, Math.round(frame.width * 2)));
      const asPng = shouldPreserveAlpha(sourceMimeType, preferPng);
      const result = await manipulateAsync(
        uri,
        [{ crop }, { resize: { width: outW } }],
        {
          compress: asPng ? 1 : 0.88,
          format: asPng ? SaveFormat.PNG : SaveFormat.JPEG,
        }
      );
      onConfirm({
        uri: result.uri,
        mimeType: asPng ? 'image/png' : 'image/jpeg',
      });
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Bijsnijden mislukt.');
    } finally {
      setBusy(false);
    }
  };

  const minZoom = scales?.minZoom ?? 1;
  const maxZoom = scales?.maxZoom ?? 4;
  const zoomLabel = Math.round(zoom * 100);

  const body = (
      <View style={embedded ? styles.rootEmbedded : styles.root} onLayout={onRootLayout}>
        <Text style={styles.title}>{title}</Text>
        <Text style={styles.hint}>
          Zoom uit voor meer overzicht, zoom in en verschuif voor bv. alleen een gezicht.
        </Text>

        <View style={[styles.frame, { width: frame.width, height: frame.height }]}>
          {display && content && uri ? (
            <ScrollView
              ref={scrollRef}
              style={StyleSheet.absoluteFill}
              contentContainerStyle={{ width: content.width, height: content.height }}
              bounces={false}
              showsHorizontalScrollIndicator={false}
              showsVerticalScrollIndicator={false}
              scrollEventThrottle={16}
              onScroll={(e) => {
                const { contentOffset } = e.nativeEvent;
                setOffset({ x: contentOffset.x, y: contentOffset.y });
              }}
            >
              <View
                style={{
                  width: content.width,
                  height: content.height,
                  alignItems: 'flex-start',
                  justifyContent: 'flex-start',
                }}
              >
                <Image
                  source={{ uri }}
                  style={{
                    width: display.width,
                    height: display.height,
                    marginLeft: content.padX,
                    marginTop: content.padY,
                  }}
                  resizeMode="stretch"
                />
              </View>
            </ScrollView>
          ) : (
            <ActivityIndicator color={colors.text} />
          )}
          <View style={styles.frameBorder} pointerEvents="none" />
        </View>

        <View style={styles.zoomRow}>
          <Pressable
            style={[styles.zoomBtn, zoom <= minZoom + 0.001 && styles.zoomBtnDisabled]}
            onPress={() => changeZoom(-0.25)}
            disabled={zoom <= minZoom + 0.001 || busy}
            accessibilityLabel="Uitzoomen"
          >
            <Text style={styles.zoomBtnText}>−</Text>
          </Pressable>
          <Text style={styles.zoomLabel}>{zoomLabel}%</Text>
          <Pressable
            style={[styles.zoomBtn, zoom >= maxZoom - 0.001 && styles.zoomBtnDisabled]}
            onPress={() => changeZoom(0.25)}
            disabled={zoom >= maxZoom - 0.001 || busy}
            accessibilityLabel="Inzoomen"
          >
            <Text style={styles.zoomBtnText}>+</Text>
          </Pressable>
        </View>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <View style={styles.actions}>
          <Pressable style={styles.secondaryBtn} onPress={onCancel} disabled={busy}>
            <Text style={styles.secondaryBtnText}>Annuleren</Text>
          </Pressable>
          <Pressable
            style={[styles.primaryBtn, busy && { opacity: 0.6 }]}
            onPress={() => void exportCrop()}
            disabled={busy || !display}
          >
            {busy ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.primaryBtnText}>Gebruik</Text>
            )}
          </Pressable>
        </View>
      </View>
  );

  if (embedded) {
    if (!visible) return null;
    return body;
  }

  return (
    <Modal
      visible={visible}
      animationType="slide"
      presentationStyle="fullScreen"
      onRequestClose={onCancel}
    >
      {body}
    </Modal>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    root: {
      flex: 1,
      backgroundColor: colors.bg,
      paddingTop: Platform.OS === 'ios' ? 56 : 28,
      paddingHorizontal: 16,
      paddingBottom: 28,
      alignItems: 'center',
    },
    rootEmbedded: {
      width: '100%',
      alignItems: 'center',
    },
    title: {
      color: colors.text,
      fontSize: 20,
      fontWeight: '700',
      marginBottom: 6,
    },
    hint: {
      color: colors.muted,
      fontSize: 14,
      textAlign: 'center',
      marginBottom: 18,
      paddingHorizontal: 12,
    },
    frame: {
      backgroundColor: '#000',
      borderRadius: 12,
      overflow: 'hidden',
      alignItems: 'center',
      justifyContent: 'center',
    },
    frameBorder: {
      ...StyleSheet.absoluteFill,
      borderWidth: 2,
      borderColor: 'rgba(255,255,255,0.85)',
      borderRadius: 12,
    },
    zoomRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 16,
      marginTop: 18,
    },
    zoomBtn: {
      width: 44,
      height: 44,
      borderRadius: 22,
      borderWidth: 1,
      borderColor: colors.border,
      backgroundColor: colors.card,
      alignItems: 'center',
      justifyContent: 'center',
    },
    zoomBtnDisabled: {
      opacity: 0.4,
    },
    zoomBtnText: {
      color: colors.text,
      fontSize: 28,
      fontWeight: '600',
      lineHeight: 30,
    },
    zoomLabel: {
      color: colors.muted,
      fontSize: 14,
      fontWeight: '600',
      minWidth: 48,
      textAlign: 'center',
    },
    error: {
      color: colors.danger,
      marginTop: 12,
      textAlign: 'center',
    },
    actions: {
      marginTop: 'auto',
      flexDirection: 'row',
      gap: 12,
      width: '100%',
      maxWidth: 400,
    },
    secondaryBtn: {
      flex: 1,
      paddingVertical: 14,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: colors.border,
      alignItems: 'center',
    },
    secondaryBtnText: {
      color: colors.text,
      fontWeight: '600',
      fontSize: 16,
    },
    primaryBtn: {
      flex: 1,
      paddingVertical: 14,
      borderRadius: 12,
      backgroundColor: colors.primary,
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: 50,
    },
    primaryBtnText: {
      color: '#fff',
      fontWeight: '700',
      fontSize: 16,
    },
  });
}
