import React, { useEffect, useRef } from 'react';
import {
  NativeScrollEvent,
  NativeSyntheticEvent,
  ScrollView,
  ScrollViewProps,
  StyleSheet,
} from 'react-native';

/** Scrollpositie per scherm — blijft behouden bij refresh en remount. */
const scrollOffsets = new Map<string, number>();

type Props = ScrollViewProps & {
  /** Unieke key per tab/panel (bijv. `contract-trips`). */
  persistKey?: string;
};

/**
 * Verticale scroll met begrensde hoogte (flex:1 + minHeight:0).
 * Met `persistKey` blijft de scrollpositie staan na pull-to-refresh / data-update.
 */
export function AppScrollView({
  style,
  contentContainerStyle,
  children,
  persistKey,
  onScroll,
  onContentSizeChange,
  refreshControl,
  ...rest
}: Props) {
  const ref = useRef<ScrollView>(null);
  const yRef = useRef(persistKey ? scrollOffsets.get(persistKey) || 0 : 0);
  const restoringRef = useRef(false);
  const wasRefreshingRef = useRef(false);

  const refreshing =
    React.isValidElement(refreshControl) &&
    Boolean((refreshControl.props as { refreshing?: boolean }).refreshing);

  const saveY = (y: number) => {
    if (restoringRef.current) return;
    if (y < 0) return;
    yRef.current = y;
    if (persistKey) scrollOffsets.set(persistKey, y);
  };

  const restoreY = () => {
    const y = persistKey ? scrollOffsets.get(persistKey) ?? yRef.current : yRef.current;
    if (y <= 0) return;
    restoringRef.current = true;
    ref.current?.scrollTo({ y, animated: false });
    requestAnimationFrame(() => {
      restoringRef.current = false;
    });
  };

  // Na einde pull-to-refresh: iOS/Android zetten scroll vaak op 0 — herstel.
  useEffect(() => {
    if (wasRefreshingRef.current && !refreshing) {
      const t = setTimeout(restoreY, 32);
      wasRefreshingRef.current = false;
      return () => clearTimeout(t);
    }
    wasRefreshingRef.current = refreshing;
    return undefined;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [refreshing, persistKey]);

  // Remount (tabwissel): zet vorige positie terug.
  useEffect(() => {
    if (!persistKey) return;
    const y = scrollOffsets.get(persistKey) || 0;
    if (y <= 0) return;
    const t = setTimeout(restoreY, 48);
    return () => clearTimeout(t);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [persistKey]);

  const handleScroll = (e: NativeSyntheticEvent<NativeScrollEvent>) => {
    if (!refreshing) {
      saveY(e.nativeEvent.contentOffset.y);
    }
    onScroll?.(e);
  };

  return (
    <ScrollView
      ref={ref}
      style={[styles.scroll, style]}
      contentContainerStyle={[styles.content, contentContainerStyle]}
      keyboardShouldPersistTaps="handled"
      keyboardDismissMode="on-drag"
      nestedScrollEnabled
      showsVerticalScrollIndicator
      bounces
      alwaysBounceVertical
      scrollEventThrottle={16}
      refreshControl={refreshControl}
      onScroll={handleScroll}
      onContentSizeChange={(w, h) => {
        // Data-update zonder RefreshControl (opslaan, start rit, …).
        if (!refreshing) {
          const saved = persistKey ? scrollOffsets.get(persistKey) ?? yRef.current : yRef.current;
          if (saved > 0) restoreY();
        }
        onContentSizeChange?.(w, h);
      }}
      {...rest}
    >
      {children}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  scroll: {
    flex: 1,
    minHeight: 0,
  },
  content: {
    flexGrow: 0,
    paddingBottom: 28,
  },
});
