import React, { useEffect, useRef } from 'react';
import { Animated, Easing, Image, StyleSheet, View } from 'react-native';

const ROAD = 'rgba(148,163,184,0.45)';
const DASH = 'rgba(226,232,240,0.85)';
const CAR_ASPECT = 974 / 656;

const carSource = require('../../assets/active-ride-car.png');

function useForwardLoop(delayMs: number) {
  const v = useRef(new Animated.Value(0)).current;
  useEffect(() => {
    v.setValue(0);
    const loop = Animated.loop(
      Animated.timing(v, {
        toValue: 1,
        duration: 2600,
        easing: Easing.linear,
        useNativeDriver: true,
      })
    );
    const t = setTimeout(() => loop.start(), delayMs);
    return () => {
      clearTimeout(t);
      loop.stop();
    };
  }, [delayMs, v]);
  return v;
}

function CenterDash({
  progress,
  roadH,
}: {
  progress: Animated.Value;
  roadH: number;
}) {
  const translateY = progress.interpolate({
    inputRange: [0, 1],
    outputRange: [roadH - 2, 2],
  });
  const scaleX = progress.interpolate({
    inputRange: [0, 1],
    outputRange: [1.15, 0.35],
  });
  const opacity = progress.interpolate({
    inputRange: [0, 0.12, 0.82, 1],
    outputRange: [0, 1, 1, 0],
  });

  return (
    <Animated.View
      style={[
        styles.dash,
        {
          opacity,
          transform: [{ translateY }, { scaleX }],
        },
      ]}
    />
  );
}

/**
 * Taxi van voren. Strepen schuiven naar achteren (auto rijdt vooruit).
 */
export function ActiveRideIcon({ size = 28 }: { size?: number }) {
  const height = size;
  const width = size + 8;
  const roadH = Math.max(8, size * 0.28);
  const carH = size * 0.86;
  const carW = Math.min(width, carH * CAR_ASPECT);
  const p0 = useForwardLoop(0);
  const p1 = useForwardLoop(870);
  const p2 = useForwardLoop(1740);

  return (
    <View style={[styles.wrap, { width, height }]} accessibilityElementsHidden>
      <View style={[styles.road, { height: roadH }]}>
        <View style={[styles.roadEdge, styles.roadEdgeLeft]} />
        <View style={[styles.roadEdge, styles.roadEdgeRight]} />
        <View style={styles.dashCol}>
          <CenterDash progress={p0} roadH={roadH} />
          <CenterDash progress={p1} roadH={roadH} />
          <CenterDash progress={p2} roadH={roadH} />
        </View>
      </View>

      <Image
        source={carSource}
        style={{ width: carW, height: carH, marginBottom: roadH * 0.28 }}
        resizeMode="contain"
      />
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    overflow: 'visible',
    alignItems: 'center',
    justifyContent: 'flex-end',
  },
  road: {
    position: 'absolute',
    left: 2,
    right: 2,
    bottom: 0,
    overflow: 'hidden',
    alignItems: 'center',
  },
  roadEdge: {
    position: 'absolute',
    bottom: 0,
    width: 1.2,
    height: '100%',
    backgroundColor: ROAD,
    borderRadius: 1,
  },
  roadEdgeLeft: {
    left: '18%',
    transform: [{ rotate: '-16deg' }],
  },
  roadEdgeRight: {
    right: '18%',
    transform: [{ rotate: '16deg' }],
  },
  dashCol: {
    position: 'absolute',
    top: 0,
    bottom: 0,
    width: 8,
    alignItems: 'center',
  },
  dash: {
    position: 'absolute',
    top: 0,
    width: 5,
    height: 2,
    borderRadius: 1,
    backgroundColor: DASH,
  },
});
