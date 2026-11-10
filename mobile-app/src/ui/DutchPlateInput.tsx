import React from 'react';
import { StyleSheet, Text, TextInput, View } from 'react-native';
import { formatDutchPlate, formatDutchPlateDisplay } from '../dutchPlate';

export function DutchPlateInput({
  value,
  onChangeText,
  inputAccessoryViewID,
}: {
  value: string;
  onChangeText: (plate: string) => void;
  inputAccessoryViewID?: string;
}) {
  return (
    <View style={styles.plate}>
      <View style={styles.eu}>
        <Text style={styles.stars}>★★★</Text>
        <Text style={styles.nl}>NL</Text>
      </View>
      <TextInput
        value={value}
        onChangeText={(next) => onChangeText(formatDutchPlate(next))}
        autoCapitalize="characters"
        autoCorrect={false}
        spellCheck={false}
        placeholder="AB-12-CD"
        placeholderTextColor="rgba(20,20,20,0.35)"
        style={styles.input}
        accessibilityLabel="Kenteken"
        inputAccessoryViewID={inputAccessoryViewID}
      />
    </View>
  );
}

const BADGE_HEIGHT = 34;
const EU_STARS = 12;

function EuStarRing() {
  const size = 12;
  const star = 3.5;
  const radius = size / 2 - star / 2;
  const center = size / 2;
  return (
    <View style={{ width: size, height: size }}>
      {Array.from({ length: EU_STARS }, (_, index) => {
        const angle = (Math.PI * 2 * index) / EU_STARS - Math.PI / 2;
        return (
          <Text
            key={index}
            style={{
              position: 'absolute',
              left: center + radius * Math.cos(angle) - star / 2,
              top: center + radius * Math.sin(angle) - star / 2,
              width: star,
              color: '#FFCC00',
              fontSize: star,
              lineHeight: star,
              textAlign: 'center',
            }}
          >
            ★
          </Text>
        );
      })}
    </View>
  );
}

/** Geel NL-kenteken, alleen weergave. */
export function DutchPlateBadge({
  plate,
  accessibilityLabel = 'Kenteken',
}: {
  plate: string;
  accessibilityLabel?: string;
}) {
  const formatted = formatDutchPlateDisplay(plate);
  const label = formatted || plate.trim() || '—';
  return (
    <View
      style={styles.badge}
      accessibilityRole="text"
      accessibilityLabel={`${accessibilityLabel} ${label}`}
    >
      <View style={styles.badgeEu}>
        <EuStarRing />
        <Text style={styles.badgeNl}>NL</Text>
      </View>
      <Text style={styles.badgeText} numberOfLines={1}>
        {label}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  plate: {
    flexDirection: 'row',
    alignItems: 'stretch',
    height: 56,
    borderRadius: 8,
    overflow: 'hidden',
    backgroundColor: '#F5C400',
    borderWidth: 1,
    borderColor: '#1A1A1A',
  },
  eu: {
    width: 36,
    backgroundColor: '#003399',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 1,
  },
  stars: {
    color: '#F5C400',
    fontSize: 8,
    letterSpacing: 0.4,
  },
  nl: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '800',
    letterSpacing: 0.4,
  },
  input: {
    flex: 1,
    color: '#1A1A1A',
    fontSize: 22,
    fontWeight: '800',
    letterSpacing: 1.5,
    textAlign: 'center',
    paddingHorizontal: 8,
  },
  badge: {
    flexDirection: 'row',
    alignItems: 'stretch',
    alignSelf: 'center',
    flexShrink: 1,
    maxWidth: '100%',
    height: BADGE_HEIGHT,
    borderRadius: 3,
    overflow: 'hidden',
    backgroundColor: '#F5C400',
    borderWidth: 1,
    borderColor: '#1A1A1A',
  },
  badgeEu: {
    width: BADGE_HEIGHT / 2,
    backgroundColor: '#003399',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 1,
  },
  badgeNl: {
    color: '#FFFFFF',
    fontSize: 7,
    fontWeight: '800',
    letterSpacing: 0.2,
    lineHeight: 8,
  },
  badgeText: {
    flexShrink: 1,
    color: '#141414',
    fontSize: 22,
    fontWeight: '800',
    letterSpacing: 0.6,
    textAlign: 'center',
    paddingHorizontal: 8,
    lineHeight: BADGE_HEIGHT - 2,
  },
});
