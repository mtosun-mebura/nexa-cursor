import React from 'react';
import { StyleSheet, Text, TextInput, View } from 'react-native';
import { formatDutchPlate } from '../dutchPlate';

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
});
