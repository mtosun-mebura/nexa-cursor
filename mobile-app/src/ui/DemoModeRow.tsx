import React, { useMemo } from 'react';
import { Pressable, StyleSheet, Switch, Text, View } from 'react-native';
import { ColorPalette } from '../config';
import { useDemo } from '../demo/DemoContext';
import { t, useI18n } from '../i18n';
import { useThemeColors } from '../theme/ThemeContext';

/** Profielrij voor de superadmin-demo. Verbergt zichzelf voor andere gebruikers. */
export function DemoModeRow({ showOpenBooking = true }: { showOpenBooking?: boolean }) {
  useI18n();
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const { isSuperAdmin, active, setEnabled, setBookingOpen } = useDemo();

  if (!isSuperAdmin) return null;

  return (
    <View style={styles.wrap}>
      <View style={styles.row}>
        <View style={styles.text}>
          <Text style={styles.label}>{t("Demo-modus")}</Text>
          <Text style={styles.hint}>{t("Toont 20 gesimuleerde taxi's op de kaart bij Boeken")}</Text>
        </View>
        <Switch
          value={active}
          onValueChange={setEnabled}
          trackColor={{ false: colors.border, true: colors.success }}
          accessibilityLabel={t("Demo-modus")}
        />
      </View>
      {active && showOpenBooking ? (
        <Pressable
          onPress={() => setBookingOpen(true)}
          style={styles.openBtn}
          accessibilityRole="button"
        >
          <Text style={styles.openBtnText}>{t("Open Boeken (demo)")}</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    wrap: { marginTop: 16, marginBottom: 4 },
    row: { flexDirection: 'row', alignItems: 'center', gap: 12 },
    text: { flex: 1, minWidth: 0 },
    label: { color: colors.text, fontSize: 15, fontWeight: '600' },
    hint: { color: colors.muted, fontSize: 13, marginTop: 2 },
    openBtn: {
      marginTop: 12,
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 12,
      paddingVertical: 12,
      alignItems: 'center',
    },
    openBtnText: { color: colors.text, fontSize: 14, fontWeight: '600' },
  });
}
