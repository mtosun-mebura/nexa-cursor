import React, { useEffect, useRef } from 'react';
import {
  AccessibilityInfo,
  Animated,
  Easing,
  StyleProp,
  StyleSheet,
  View,
  ViewStyle,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';

export type ArchiveMotionMode = 'idle' | 'archive-out' | 'restore-out' | 'delete-out';

type Props = {
  children: React.ReactNode;
  mode?: ArchiveMotionMode;
  accentColor?: string;
  style?: StyleProp<ViewStyle>;
  onFinished?: () => void;
};

/**
 * Ritkaart-animatie: archiveren (pakketje weg), terugzetten (terugschuiven),
 * of verwijderen (omlaag faden).
 */
export function ArchiveMotion({
  children,
  mode = 'idle',
  accentColor = '#2563EB',
  style,
  onFinished,
}: Props) {
  const opacity = useRef(new Animated.Value(1)).current;
  const translateY = useRef(new Animated.Value(0)).current;
  const translateX = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(1)).current;
  const rotate = useRef(new Animated.Value(0)).current;
  const badgeOpacity = useRef(new Animated.Value(0)).current;
  const reduceMotion = useRef(false);
  const finishedRef = useRef(false);

  useEffect(() => {
    AccessibilityInfo.isReduceMotionEnabled()
      .then((v) => {
        reduceMotion.current = !!v;
      })
      .catch(() => undefined);
  }, []);

  useEffect(() => {
    finishedRef.current = false;
    if (mode === 'idle') {
      opacity.setValue(1);
      translateY.setValue(0);
      translateX.setValue(0);
      scale.setValue(1);
      rotate.setValue(0);
      badgeOpacity.setValue(0);
      return;
    }

    const done = () => {
      if (finishedRef.current) return;
      finishedRef.current = true;
      onFinished?.();
    };

    if (reduceMotion.current) {
      done();
      return;
    }

    if (mode === 'archive-out') {
      badgeOpacity.setValue(1);
      Animated.parallel([
        Animated.timing(opacity, {
          toValue: 0,
          duration: 480,
          easing: Easing.in(Easing.cubic),
          useNativeDriver: true,
        }),
        Animated.timing(translateY, {
          toValue: -64,
          duration: 480,
          easing: Easing.in(Easing.cubic),
          useNativeDriver: true,
        }),
        Animated.timing(translateX, {
          toValue: 56,
          duration: 480,
          easing: Easing.in(Easing.cubic),
          useNativeDriver: true,
        }),
        Animated.timing(scale, {
          toValue: 0.78,
          duration: 480,
          easing: Easing.in(Easing.cubic),
          useNativeDriver: true,
        }),
        Animated.timing(rotate, {
          toValue: 1,
          duration: 480,
          easing: Easing.in(Easing.cubic),
          useNativeDriver: true,
        }),
        Animated.sequence([
          Animated.timing(badgeOpacity, {
            toValue: 1,
            duration: 90,
            useNativeDriver: true,
          }),
          Animated.timing(badgeOpacity, {
            toValue: 0,
            duration: 280,
            delay: 80,
            useNativeDriver: true,
          }),
        ]),
      ]).start(({ finished }) => {
        if (finished) done();
      });
      return;
    }

    if (mode === 'restore-out') {
      Animated.parallel([
        Animated.timing(opacity, {
          toValue: 0,
          duration: 360,
          easing: Easing.in(Easing.cubic),
          useNativeDriver: true,
        }),
        Animated.timing(translateY, {
          toValue: -28,
          duration: 360,
          easing: Easing.out(Easing.cubic),
          useNativeDriver: true,
        }),
        Animated.timing(scale, {
          toValue: 0.94,
          duration: 360,
          useNativeDriver: true,
        }),
      ]).start(({ finished }) => {
        if (finished) done();
      });
      return;
    }

    // delete-out
    Animated.parallel([
      Animated.timing(opacity, {
        toValue: 0,
        duration: 320,
        easing: Easing.in(Easing.quad),
        useNativeDriver: true,
      }),
      Animated.timing(translateY, {
        toValue: 28,
        duration: 320,
        easing: Easing.in(Easing.quad),
        useNativeDriver: true,
      }),
      Animated.timing(scale, {
        toValue: 0.92,
        duration: 320,
        useNativeDriver: true,
      }),
    ]).start(({ finished }) => {
      if (finished) done();
    });
  }, [mode, onFinished, opacity, translateY, translateX, scale, rotate, badgeOpacity]);

  const spin = rotate.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '-10deg'],
  });

  return (
    <Animated.View
      style={[
        style,
        {
          opacity,
          transform: [{ translateX }, { translateY }, { scale }, { rotate: spin }],
        },
      ]}
    >
      {mode === 'archive-out' ? (
        <Animated.View pointerEvents="none" style={[styles.badge, { opacity: badgeOpacity }]}>
          <View style={[styles.badgePill, { borderColor: accentColor }]}>
            <Ionicons name="archive-outline" size={18} color={accentColor} />
          </View>
        </Animated.View>
      ) : null}
      {children}
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  badge: {
    position: 'absolute',
    top: 10,
    right: 14,
    zIndex: 4,
  },
  badgePill: {
    width: 34,
    height: 34,
    borderRadius: 17,
    borderWidth: 1.5,
    backgroundColor: 'rgba(15,23,42,0.72)',
    alignItems: 'center',
    justifyContent: 'center',
  },
});
