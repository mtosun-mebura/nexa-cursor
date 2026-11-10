import React, { useMemo } from 'react';
import { Pressable, StyleSheet, Switch, Text, View } from 'react-native';
import { useAuth } from '../auth/AuthContext';
import { ColorPalette } from '../config';
import { useDemo } from '../demo/DemoContext';
import { t, useI18n } from '../i18n';
import { useThemeColors } from '../theme/ThemeContext';

function readIsSuperAdmin(value: unknown): boolean {
  return value === true || value === 1 || value === '1' || value === 'true';
}

/** Profielrij voor de superadmin-demo. Verbergt zichzelf voor andere gebruikers. */
export function DemoModeRow({ showOpenBooking = true }: { showOpenBooking?: boolean }) {
  useI18n();
  const colors = useThemeColors();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const { session } = useAuth();
  const { isSuperAdmin: demoSaysAdmin, environmentAllowed, active, setEnabled, setBookingOpen } =
    useDemo();

  // Zichtbaarheid via auth-sessie (bron van waarheid), niet alleen DemoContext —
  // bij fallback/context-mismatch bleef de hele rij anders onzichtbaar.
  const isSuperAdmin =
    readIsSuperAdmin(session?.user?.is_super_admin) || demoSaysAdmin;

  if (!isSuperAdmin) return null;

  return (
    <View style={styles.wrap}>
      <View style={styles.row}>
        <View style={styles.text}>
          <Text style={styles.label}>{t('Demo-modus')}</Text>
          <Text style={styles.hint}>
            {environmentAllowed
              ? t(
                  'Zet virtuele taxi’s online voor alle klanten op deze omgeving. Boeken gaat via echte dispatch (ook naar ingelogde chauffeurs).'
                )
              : t('Demo-modus is alleen beschikbaar op lokaal en test, niet op productie.')}
          </Text>
        </View>
        <Switch
          value={active}
          onValueChange={setEnabled}
          disabled={!environmentAllowed}
          trackColor={{ false: colors.border, true: colors.success }}
          accessibilityLabel={t('Demo-modus')}
          accessibilityState={{ checked: active, disabled: !environmentAllowed }}
        />
      </View>
      {active && showOpenBooking ? (
        <Pressable
          onPress={() => setBookingOpen(true)}
          style={styles.openBtn}
          accessibilityRole="button"
        >
          <Text style={styles.openBtnText}>{t('Open Boeken (demo)')}</Text>
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
