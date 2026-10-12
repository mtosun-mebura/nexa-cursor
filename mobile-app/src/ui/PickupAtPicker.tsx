import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { ColorPalette } from '../config';
import { defaultPickupDate, formatPickupAtLabel } from '../geo/route';
import { useTheme } from '../theme/ThemeContext';
import { AppModal } from './AppModal';

const TIME_ITEM_H = 44;
const TIME_VIEWPORT_H = 180;
/** Padding zodat eerste/laatste items (o.a. 50/55) in het midden van de kolom kunnen staan. */
const TIME_PAD = Math.max(0, (TIME_VIEWPORT_H - TIME_ITEM_H) / 2);

const QUICK_PRESETS: { minutes: number; label: string }[] = [
  { minutes: 10, label: 'Over 10 min' },
  { minutes: 30, label: 'Over een half uur' },
  { minutes: 60, label: 'Over 1 uur' },
];

function startOfDay(d: Date) {
  return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}

function clampToFuture(d: Date): Date {
  const min = new Date(Date.now() + 5 * 60_000);
  min.setSeconds(0, 0);
  const rem = min.getMinutes() % 5;
  if (rem !== 0) min.setMinutes(min.getMinutes() + (5 - rem));
  return d.getTime() < min.getTime() ? min : d;
}

function snapMinutes(m: number) {
  return Math.min(55, Math.round(m / 5) * 5);
}

function sameMinute(a: Date, b: Date): boolean {
  return (
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate() &&
    a.getHours() === b.getHours() &&
    snapMinutes(a.getMinutes()) === snapMinutes(b.getMinutes())
  );
}

export function PickupAtField({
  value,
  onChange,
}: {
  value: Date;
  onChange: (next: Date) => void;
}) {
  const { colors } = useTheme();
  const styles = useMemo(() => makeStyles(colors), [colors]);
  const [open, setOpen] = useState(false);
  const [draft, setDraft] = useState(value);
  const hourScrollRef = useRef<ScrollView>(null);
  const minuteScrollRef = useRef<ScrollView>(null);
  const dayScrollRef = useRef<ScrollView>(null);

  const quickOptions = useMemo(
    () =>
      QUICK_PRESETS.map((p) => ({
        ...p,
        at: defaultPickupDate(p.minutes),
      })),
    [open]
  );
  const selectedQuick = quickOptions.find((p) => sameMinute(draft, p.at)) ?? null;

  function scrollToDraft(d: Date, animated = false) {
    const hour = d.getHours();
    const minuteIndex = snapMinutes(d.getMinutes()) / 5;
    const dayIndex = Math.max(
      0,
      Math.round((startOfDay(d).getTime() - startOfDay(new Date()).getTime()) / 86_400_000)
    );
    const go = () => {
      hourScrollRef.current?.scrollTo({ y: hour * TIME_ITEM_H, animated });
      minuteScrollRef.current?.scrollTo({ y: minuteIndex * TIME_ITEM_H, animated });
      dayScrollRef.current?.scrollTo({ x: Math.max(0, dayIndex * 84 - 24), animated });
    };
    requestAnimationFrame(go);
    setTimeout(go, 40);
    setTimeout(go, 120);
  }

  useEffect(() => {
    if (!open) return;
    const next = clampToFuture(value);
    setDraft(next);
    scrollToDraft(next, false);
  }, [open, value]);

  const days = useMemo(() => {
    const today = startOfDay(new Date());
    return Array.from({ length: 14 }, (_, i) => {
      const day = new Date(today);
      day.setDate(today.getDate() + i);
      const label =
        i === 0 ? 'Vandaag' : i === 1 ? 'Morgen' : day.toLocaleDateString('nl-NL', { weekday: 'short' });
      const month = day.toLocaleDateString('nl-NL', { month: 'short' });
      return {
        day,
        label,
        month,
        key: `${day.getFullYear()}-${day.getMonth()}-${day.getDate()}`,
      };
    });
  }, [open]);

  const selectedDayKey = `${draft.getFullYear()}-${draft.getMonth()}-${draft.getDate()}`;
  const hours = Array.from({ length: 24 }, (_, h) => h);
  const minutes = Array.from({ length: 12 }, (_, i) => i * 5);

  function pickDay(day: Date) {
    const next = clampToFuture(
      new Date(day.getFullYear(), day.getMonth(), day.getDate(), draft.getHours(), draft.getMinutes(), 0, 0)
    );
    setDraft(next);
    scrollToDraft(next, true);
  }

  function pickQuick(minutes: number) {
    const next = defaultPickupDate(minutes);
    setDraft(next);
    scrollToDraft(next, true);
  }

  function confirm() {
    onChange(clampToFuture(draft));
    setOpen(false);
  }

  const valueQuickLabel =
    QUICK_PRESETS.find((p) => sameMinute(value, defaultPickupDate(p.minutes)))?.label ?? null;

  return (
    <>
      <Text style={styles.fieldLabel}>Ophalen</Text>
      <Pressable
        onPress={() => setOpen(true)}
        style={styles.trigger}
        accessibilityRole="button"
        accessibilityLabel="Kies ophaaldatum en -tijd"
      >
        <Text style={styles.triggerText}>
          {valueQuickLabel
            ? `${valueQuickLabel} · ${formatPickupAtLabel(value)}`
            : formatPickupAtLabel(value)}
        </Text>
        <Text style={styles.chevron}>▼</Text>
      </Pressable>

      <AppModal
        visible={open}
        onRequestClose={() => setOpen(false)}
        panelStyle={styles.sheet}
      >
        <Text style={styles.sheetTitle}>Ophaalmoment</Text>

        <View style={styles.quickRow}>
          {quickOptions.map((p) => {
            const active = selectedQuick?.minutes === p.minutes;
            return (
              <Pressable
                key={p.minutes}
                onPress={() => pickQuick(p.minutes)}
                style={[styles.quickChip, active && styles.quickChipActive]}
                accessibilityRole="button"
                accessibilityState={{ selected: active }}
              >
                <Text style={[styles.quickChipText, active && styles.quickChipTextActive]}>
                  {p.label}
                </Text>
              </Pressable>
            );
          })}
        </View>

        <ScrollView
          ref={dayScrollRef}
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.daysRow}
        >
          {days.map((d) => {
            const active = d.key === selectedDayKey;
            return (
              <Pressable
                key={d.key}
                onPress={() => pickDay(d.day)}
                style={[styles.dayBtn, active && styles.dayBtnActive]}
              >
                <Text style={[styles.dayWd, active && styles.dayTextActive]} numberOfLines={1}>
                  {d.label}
                </Text>
                <Text style={[styles.dayNr, active && styles.dayTextActive]}>{d.day.getDate()}</Text>
                <Text style={[styles.dayMonth, active && styles.dayTextActive]}>{d.month}</Text>
              </Pressable>
            );
          })}
        </ScrollView>

        <View style={styles.timeRow}>
          <ScrollView
            ref={hourScrollRef}
            style={styles.timeCol}
            contentContainerStyle={styles.timeColContent}
            showsVerticalScrollIndicator={false}
            snapToInterval={TIME_ITEM_H}
            decelerationRate="fast"
            nestedScrollEnabled
          >
            {hours.map((h) => {
              const active = draft.getHours() === h;
              return (
                <Pressable
                  key={`h-${h}`}
                  onPress={() => {
                    const next = clampToFuture(
                      new Date(
                        draft.getFullYear(),
                        draft.getMonth(),
                        draft.getDate(),
                        h,
                        draft.getMinutes(),
                        0,
                        0
                      )
                    );
                    setDraft(next);
                    scrollToDraft(next, true);
                  }}
                  style={[styles.timeItem, active && styles.timeItemActive]}
                >
                  <Text style={[styles.timeText, active && styles.timeTextActive]}>
                    {String(h).padStart(2, '0')}
                  </Text>
                </Pressable>
              );
            })}
          </ScrollView>
          <Text style={styles.timeSep}>:</Text>
          <ScrollView
            ref={minuteScrollRef}
            style={styles.timeCol}
            contentContainerStyle={styles.timeColContent}
            showsVerticalScrollIndicator={false}
            snapToInterval={TIME_ITEM_H}
            decelerationRate="fast"
            nestedScrollEnabled
          >
            {minutes.map((m) => {
              const active = snapMinutes(draft.getMinutes()) === m;
              return (
                <Pressable
                  key={`m-${m}`}
                  onPress={() => {
                    const next = clampToFuture(
                      new Date(
                        draft.getFullYear(),
                        draft.getMonth(),
                        draft.getDate(),
                        draft.getHours(),
                        m,
                        0,
                        0
                      )
                    );
                    setDraft(next);
                    scrollToDraft(next, true);
                  }}
                  style={[styles.timeItem, active && styles.timeItemActive]}
                >
                  <Text style={[styles.timeText, active && styles.timeTextActive]}>
                    {String(m).padStart(2, '0')}
                  </Text>
                </Pressable>
              );
            })}
          </ScrollView>
        </View>

        <Text style={styles.preview}>
          {selectedQuick
            ? `${selectedQuick.label} · ${formatPickupAtLabel(draft)}`
            : formatPickupAtLabel(draft)}
        </Text>
        <Pressable onPress={confirm} style={styles.confirmBtn}>
          <Text style={styles.confirmText}>Bevestigen</Text>
        </Pressable>
      </AppModal>
    </>
  );
}

function makeStyles(colors: ColorPalette) {
  return StyleSheet.create({
    fieldLabel: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '700',
      textTransform: 'uppercase',
      letterSpacing: 0.4,
      marginBottom: 8,
    },
    trigger: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      borderWidth: 1,
      borderColor: colors.border,
      backgroundColor: colors.inputBg,
      borderRadius: 12,
      paddingHorizontal: 14,
      paddingVertical: 12,
    },
    triggerText: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '700',
      flex: 1,
      paddingRight: 8,
    },
    chevron: {
      color: colors.muted,
      fontSize: 12,
    },
    sheet: {
      maxHeight: '78%',
      paddingHorizontal: 16,
      paddingTop: 16,
      paddingBottom: 16,
    },
    sheetTitle: {
      color: colors.text,
      fontSize: 18,
      fontWeight: '700',
      marginBottom: 12,
    },
    quickRow: {
      flexDirection: 'row',
      flexWrap: 'nowrap',
      alignItems: 'stretch',
      gap: 6,
      marginBottom: 12,
    },
    quickChip: {
      flex: 1,
      borderRadius: 999,
      borderWidth: 1,
      borderColor: colors.border,
      backgroundColor: colors.inputBg,
      paddingVertical: 10,
      paddingHorizontal: 6,
      alignItems: 'center',
      justifyContent: 'center',
    },
    quickChipActive: {
      backgroundColor: colors.primary,
      borderColor: colors.primary,
    },
    quickChipText: {
      color: colors.text,
      fontSize: 11,
      fontWeight: '700',
      textAlign: 'center',
    },
    quickChipTextActive: {
      color: '#FFFFFF',
    },
    daysRow: {
      gap: 8,
      paddingBottom: 12,
    },
    dayBtn: {
      minWidth: 76,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: colors.border,
      paddingVertical: 10,
      paddingHorizontal: 12,
      alignItems: 'center',
      backgroundColor: colors.inputBg,
    },
    dayBtnActive: {
      backgroundColor: colors.primary,
      borderColor: colors.primary,
    },
    dayWd: {
      color: colors.muted,
      fontSize: 11,
      fontWeight: '600',
      marginBottom: 2,
    },
    dayNr: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '700',
    },
    dayMonth: {
      color: colors.muted,
      fontSize: 11,
      fontWeight: '600',
      marginTop: 2,
      textTransform: 'lowercase',
    },
    dayTextActive: {
      color: '#FFFFFF',
    },
    timeRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
      height: TIME_VIEWPORT_H,
      marginBottom: 12,
    },
    timeCol: {
      flex: 1,
      height: TIME_VIEWPORT_H,
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 12,
      backgroundColor: colors.inputBg,
    },
    timeColContent: {
      paddingVertical: TIME_PAD,
    },
    timeSep: {
      color: colors.text,
      fontSize: 22,
      fontWeight: '700',
    },
    timeItem: {
      height: TIME_ITEM_H,
      alignItems: 'center',
      justifyContent: 'center',
    },
    timeItemActive: {
      backgroundColor: colors.primary + '33',
    },
    timeText: {
      color: colors.muted,
      fontSize: 18,
      fontWeight: '600',
    },
    timeTextActive: {
      color: colors.primary,
      fontWeight: '800',
    },
    preview: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '700',
      textAlign: 'center',
      marginBottom: 10,
    },
    confirmBtn: {
      backgroundColor: colors.primary,
      borderRadius: 14,
      paddingVertical: 14,
      alignItems: 'center',
    },
    confirmText: {
      color: '#FFFFFF',
      fontSize: 16,
      fontWeight: '700',
    },
  });
}
