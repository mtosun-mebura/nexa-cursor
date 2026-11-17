import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import React, { useEffect, useState } from 'react';
import {
  Image,
  ImageBackground,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { LiveRide } from '../../api/customer';
import { t, useI18n } from '../../i18n';
import { DutchPlateBadge } from '../../ui/DutchPlateInput';
import { getApiBaseUrl } from '../../config';
import { useTheme } from '../../theme/ThemeContext';
import { NexaGlowCard } from './NexaGlowCard';
import { nexaBookingTheme as theme } from './theme';

/** Zelfde map-route visual als welkomst-/boekenscherm — als schermzone, niet als card. */
export const nexaMapHeaderBg = require('../../../assets/map-header-background.png');
const driverCardScene = require('../../../assets/nexa-booking/crops/nexa-driver-card-scene.jpg');
const sedanFallback = require('../../../assets/nexa-booking/vehicles/nexa-taxi-sedan.png');

function resolveMediaUrl(raw?: string | null): string | null {
  if (!raw) return null;
  const value = String(raw).trim();
  if (!value) return null;
  if (/^https?:\/\//i.test(value)) return value;
  const base = getApiBaseUrl().replace(/\/$/, '');
  if (value.startsWith('/')) return `${base}${value}`;
  return `${base}/${value}`;
}

type Props = {
  live: LiveRide;
  driverComing?: boolean;
  tripStarted?: boolean;
  onPreview: (uri: string) => void;
  onCall: () => void;
  onChat: () => void;
  onLiveFollow: () => void;
  /** Als true: statusregel zonder eigen achtergrond (scherm heeft al map-header). */
  embedInScreenBackdrop?: boolean;
};

export function NexaAssignedRidePanel({
  live,
  driverComing,
  tripStarted,
  onPreview,
  onCall,
  onChat,
  onLiveFollow,
  embedInScreenBackdrop = false,
}: Props) {
  useI18n();
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  const ink = light ? ({ color: '#0B1220' } as const) : null;
  const mutedInk = light ? ({ color: '#64748B' } as const) : null;
  const photo = resolveMediaUrl(live.driver?.photo_url);
  const carPhoto = resolveMediaUrl(live.vehicle?.photo_url);
  const plate = String(live.vehicle?.license_plate || '').trim();
  const vehicleLabel = live.vehicle?.name || live.vehicle?.label || t('Taxi');
  const driverName = live.driver?.name || t('Chauffeur');
  const [carFailed, setCarFailed] = useState(false);
  const showCar = Boolean(carPhoto) && !carFailed;
  const canCommunicate = !!(live.driver?.id || live.accepted);
  const etaRaw = String(live.eta_label || '').trim();
  const etaMinutes =
    live.eta_minutes != null && Number.isFinite(Number(live.eta_minutes))
      ? Number(live.eta_minutes)
      : null;
  const eta = (() => {
    if (etaMinutes != null && etaMinutes > 0) {
      return etaMinutes === 1
        ? t('ongeveer 1 minuut')
        : t('ongeveer {n} minuten', { n: Math.max(1, Math.round(etaMinutes)) });
    }
    const m = etaRaw.match(/ongeveer\s+(\d+)\s+minu/i);
    if (m) {
      const n = Number(m[1]);
      return n === 1
        ? t('ongeveer 1 minuut')
        : t('ongeveer {n} minuten', { n });
    }
    return etaRaw;
  })();

  useEffect(() => {
    setCarFailed(false);
  }, [carPhoto]);

  const headline = tripStarted
    ? t('De rit is gestart.')
    : driverComing
      ? t('Chauffeur onderweg')
      : t('Je rit is bevestigd');
  const sub = tripStarted
    ? t('Je bent onderweg naar je bestemming.')
    : driverComing
      ? t('{name} is naar je ophaallocatie onderweg.', { name: driverName })
      : t('Er is een chauffeur aan je rit gekoppeld.');

  const step = tripStarted ? 3 : driverComing ? 1 : 0;

  const statusBlock = (
    <View style={styles.statusRow}>
      <View style={styles.checkBadge} accessibilityRole="image" accessibilityLabel={t('Bevestigd')}>
        <View style={styles.checkBadgeInner}>
          <Ionicons name="checkmark" size={22} color="#FFFFFF" />
        </View>
      </View>
      <View style={{ flex: 1 }}>
        <Text style={[styles.headline, ink]}>{headline}</Text>
        <Text style={[styles.sub, mutedInk]}>{sub}</Text>
        <View style={styles.underline} />
      </View>
    </View>
  );

  return (
    <View style={styles.wrap}>
      {embedInScreenBackdrop ? (
        <View style={styles.statusOnBackdrop}>{statusBlock}</View>
      ) : (
        <View style={styles.heroZone}>
          <ImageBackground
            source={nexaMapHeaderBg}
            style={StyleSheet.absoluteFill}
            imageStyle={styles.heroImg}
          />
          <LinearGradient
            colors={['rgba(6,19,41,0.25)', 'rgba(6,19,41,0.88)']}
            style={StyleSheet.absoluteFill}
          />
          {statusBlock}
        </View>
      )}

      <NexaGlowCard glowOnly={light ? false : true}>
        {!light ? (
          <>
            <ImageBackground
              source={driverCardScene}
              style={StyleSheet.absoluteFill}
              imageStyle={styles.driverCardSceneImg}
            />
            <LinearGradient
              colors={[
                'rgba(6,19,41,0.97)',
                'rgba(6,19,41,0.88)',
                'rgba(6,19,41,0.45)',
                'rgba(6,19,41,0.2)',
              ]}
              locations={[0, 0.42, 0.72, 1]}
              style={StyleSheet.absoluteFill}
            />
          </>
        ) : null}

        <View style={styles.driverTop}>
          <View style={styles.mediaCol}>
            <Pressable
              disabled={!photo}
              onPress={() => photo && onPreview(photo)}
              style={styles.avatarWrap}
              accessibilityRole={photo ? 'button' : 'image'}
            >
              {photo ? (
                <Image source={{ uri: photo }} style={styles.avatar} />
              ) : (
                <View style={[styles.avatar, styles.avatarFallback]}>
                  <Ionicons name="person" size={28} color={light ? '#64748B' : theme.color.textMuted} />
                </View>
              )}
            </Pressable>
            <Pressable
              disabled={!showCar}
              onPress={() => showCar && carPhoto && onPreview(carPhoto)}
              style={styles.carFrame}
              accessibilityRole={showCar ? 'button' : 'image'}
              accessibilityLabel={showCar ? t('Autofoto vergroten') : t('Voertuig')}
            >
              <Image
                source={showCar ? { uri: carPhoto! } : sedanFallback}
                style={styles.carThumb}
                resizeMode="cover"
                onError={() => setCarFailed(true)}
              />
            </Pressable>
          </View>

          <View style={styles.driverMeta}>
            <Text style={[styles.driverName, ink]} numberOfLines={1}>
              {driverName}
            </Text>
            {live.driver?.rating_avg != null ? (
              <Text style={[styles.rating, mutedInk]}>
                <Text style={styles.star}>★</Text>
                {' '}
                {Number(live.driver.rating_avg).toFixed(1)}
                {live.driver.rating_count
                  ? ` (${Number(live.driver.rating_count).toLocaleString('nl-NL')} ${t('ritten')})`
                  : ''}
              </Text>
            ) : null}
            {plate ? (
              <View style={styles.plateWrap}>
                <DutchPlateBadge plate={plate} accessibilityLabel={t('Kenteken')} />
              </View>
            ) : null}
            <View style={styles.vehicleRow}>
              <Ionicons name="car-sport" size={15} color={light ? '#0B1220' : '#FFFFFF'} />
              <Text style={[styles.vehicleLabel, ink]} numberOfLines={1}>
                {vehicleLabel}
              </Text>
            </View>
          </View>

          <View style={[styles.vDivider, light && { backgroundColor: 'rgba(15,23,42,0.15)' }]} />

          <View style={styles.etaCol}>
            <View style={styles.etaIcon}>
              <Ionicons name="car-sport" size={16} color="#fff" />
            </View>
            {eta ? (
              <>
                <Text style={[styles.etaLabel, mutedInk]}>{t('Aankomst over')}</Text>
                <Text style={[styles.etaValue, ink]} numberOfLines={2}>
                  {eta}
                </Text>
              </>
            ) : (
              <>
                <Text style={[styles.etaLabel, mutedInk]}>{t('Status')}</Text>
                <Text style={[styles.etaValue, ink]} numberOfLines={2}>
                  {driverComing ? t('Onderweg') : t('Gekoppeld')}
                </Text>
              </>
            )}
          </View>
        </View>
      </NexaGlowCard>

      <NexaGlowCard contentStyle={styles.routeCard}>
        <View style={styles.routeRail}>
          <View style={styles.routeDotBlue} />
          <View style={styles.routeLine} />
          <View style={styles.routePinWrap}>
            <Ionicons name="location" size={16} color={theme.color.orangeHot} />
          </View>
        </View>
        <View style={styles.routeTexts}>
          <View style={styles.routeBlock}>
            <Text style={[styles.routeLabel, mutedInk]}>{t('Ophalen')}</Text>
            <Text style={[styles.routeValue, ink]} numberOfLines={2}>
              {live.pickup_address || '—'}
            </Text>
          </View>
          <View style={styles.routeBlock}>
            <Text style={[styles.routeLabel, mutedInk]}>{t('Bestemming')}</Text>
            <Text style={[styles.routeValue, ink]} numberOfLines={2}>
              {live.dropoff_place_name || live.dropoff_address || '—'}
            </Text>
          </View>
        </View>
      </NexaGlowCard>

      <View style={styles.contactRow}>
        <Pressable
          style={[styles.contactBtn, styles.callBtn, !canCommunicate && styles.disabled]}
          disabled={!canCommunicate}
          onPress={onCall}
          accessibilityRole="button"
          accessibilityLabel={t('Bel chauffeur')}
        >
          <Ionicons name="call" size={18} color="#fff" />
          <Text style={styles.contactText}>{t('Bel chauffeur')}</Text>
        </Pressable>
        <Pressable
          style={[styles.contactBtn, styles.chatBtn, !canCommunicate && styles.disabled]}
          disabled={!canCommunicate}
          onPress={onChat}
          accessibilityRole="button"
          accessibilityLabel={t('Chat')}
        >
          <Ionicons name="chatbubble-ellipses" size={18} color="#fff" />
          <Text style={styles.contactText}>{t('Chat')}</Text>
        </Pressable>
      </View>

      <NexaGlowCard contentStyle={styles.timeline}>
        <TimelineStep
          done={step >= 0}
          active={step === 0}
          title={t('Boeking bevestigd')}
          detail={formatPickupWhenSafe(live)}
        />
        <TimelineStep
          done={step >= 1}
          active={step === 1}
          title={t('Chauffeur onderweg')}
          detail={
            step >= 1
              ? t('{name} is naar je ophaallocatie onderweg.', { name: driverName })
              : undefined
          }
        />
        <TimelineStep
          done={step >= 2}
          active={step === 2}
          title={t('Aankomst')}
          detail={
            eta
              ? t('Verwachte aankomst over {eta}.', { eta })
              : t('Verwachte aankomst…')
          }
        />
        <TimelineStep
          done={step >= 3}
          active={step === 3}
          title={t('Bestemming')}
          detail={t('Je wordt naar je bestemming gebracht.')}
          last
        />
      </NexaGlowCard>

      {driverComing ? (
        <Pressable
          style={styles.liveBtn}
          onPress={onLiveFollow}
          accessibilityRole="button"
          accessibilityLabel={t('Live volgen')}
        >
          <Ionicons name="navigate" size={18} color="#fff" />
          <Text style={styles.liveBtnText}>{t('Live volgen')}</Text>
          <Ionicons name="chevron-forward" size={18} color="#fff" />
        </Pressable>
      ) : null}
    </View>
  );
}

function formatPickupWhenSafe(live: LiveRide): string | undefined {
  const raw = live.pickup_at_label || live.pickup_at;
  if (!raw) return undefined;
  return String(raw);
}

function TimelineStatusIcon({
  completed,
  active,
}: {
  completed: boolean;
  active: boolean;
}) {
  if (completed) {
    return (
      <LinearGradient
        colors={['#34D399', '#16A34A', '#15803D']}
        locations={[0, 0.55, 1]}
        start={{ x: 0.3, y: 0 }}
        end={{ x: 0.7, y: 1 }}
        style={styles.stepDotDone}
      >
        <Ionicons name="checkmark" size={15} color="#FFFFFF" />
      </LinearGradient>
    );
  }
  if (active) {
    return (
      <View style={styles.stepDotActiveOuter}>
        <View style={styles.stepDotActiveInner}>
          <View style={styles.stepDotActiveCore} />
        </View>
      </View>
    );
  }
  return <View style={styles.stepDotPending} />;
}

function TimelineStep({
  title,
  detail,
  done,
  active,
  last,
}: {
  title: string;
  detail?: string;
  done: boolean;
  active: boolean;
  last?: boolean;
}) {
  const { colorScheme } = useTheme();
  const light = colorScheme === 'light';
  const completed = done && !active;
  return (
    <View style={styles.stepRow}>
      <View style={styles.stepRail}>
        <TimelineStatusIcon completed={completed} active={active} />
        {!last ? (
          completed ? (
            <View style={[styles.stepLine, styles.stepLineDone]} />
          ) : active ? (
            <View style={styles.stepLineDashedWrap}>
              {Array.from({ length: 4 }, (_, i) => (
                <View key={i} style={styles.stepLineDash} />
              ))}
            </View>
          ) : (
            <View style={styles.stepLine} />
          )
        ) : null}
      </View>
      <View style={styles.stepBody}>
        <Text
          style={[
            styles.stepTitle,
            light && { color: '#64748B' },
            (active || completed) && styles.stepTitleActive,
            (active || completed) && light && { color: '#0B1220' },
          ]}
        >
          {title}
        </Text>
        {detail ? (
          <Text style={[styles.stepDetail, light && { color: '#64748B' }]}>{detail}</Text>
        ) : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    gap: 12,
  },
  heroZone: {
    overflow: 'hidden',
    minHeight: 118,
    justifyContent: 'flex-end',
    paddingHorizontal: 4,
    paddingBottom: 8,
    paddingTop: 28,
  },
  heroImg: {
    resizeMode: 'cover',
  },
  statusOnBackdrop: {
    paddingHorizontal: 4,
    paddingBottom: 4,
    paddingTop: 8,
  },
  statusRow: {
    flexDirection: 'row',
    gap: 12,
    alignItems: 'flex-start',
  },
  checkBadge: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(25,186,131,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkBadgeInner: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: theme.color.success,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: theme.color.success,
    shadowOpacity: 0.55,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 0 },
  },
  headline: {
    color: theme.color.text,
    fontSize: 22,
    fontWeight: '800',
  },
  sub: {
    color: theme.color.textMuted,
    fontSize: 14,
    marginTop: 2,
  },
  underline: {
    marginTop: 8,
    width: 64,
    height: 3,
    borderRadius: 2,
    backgroundColor: theme.color.orangeHot,
  },
  driverCardSceneImg: {
    resizeMode: 'cover',
  },
  driverTop: {
    flexDirection: 'row',
    gap: 10,
    alignItems: 'flex-start',
    paddingTop: 14,
    paddingHorizontal: 12,
    paddingBottom: 14,
  },
  mediaCol: {
    gap: 8,
  },
  avatarWrap: {
    marginTop: 0,
  },
  avatar: {
    width: 68,
    height: 68,
    borderRadius: 14,
    backgroundColor: theme.color.navy700,
    borderWidth: 2,
    borderColor: 'rgba(8,125,255,0.85)',
  },
  avatarFallback: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  carFrame: {
    width: 68,
    height: 68,
    borderRadius: 14,
    overflow: 'hidden',
    backgroundColor: theme.color.navy700,
    borderWidth: 2,
    borderColor: 'rgba(8,125,255,0.85)',
  },
  carThumb: {
    width: '100%',
    height: '100%',
  },
  driverMeta: {
    flex: 1,
    gap: 3,
    minWidth: 0,
    paddingTop: 0,
    minHeight: 144,
    justifyContent: 'space-between',
  },
  driverName: {
    color: theme.color.text,
    fontSize: 20,
    fontWeight: '800',
    lineHeight: 24,
  },
  rating: {
    color: theme.color.textMuted,
    fontSize: 13,
  },
  star: {
    color: '#F5B301',
  },
  vehicleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 6,
  },
  vehicleLabel: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '600',
    flexShrink: 1,
  },
  plateWrap: {
    alignSelf: 'flex-start',
    marginTop: 2,
  },
  vDivider: {
    width: StyleSheet.hairlineWidth,
    alignSelf: 'stretch',
    backgroundColor: 'rgba(183,199,223,0.35)',
    marginVertical: 4,
  },
  etaCol: {
    alignItems: 'center',
    justifyContent: 'flex-start',
    width: 86,
    gap: 4,
    paddingTop: 2,
  },
  etaIcon: {
    width: 38,
    height: 38,
    borderRadius: 19,
    borderWidth: 2,
    borderColor: 'rgba(8,125,255,0.95)',
    backgroundColor: 'rgba(8,125,255,0.22)',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#087DFF',
    shadowOpacity: 0.55,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 0 },
  },
  etaLabel: {
    color: theme.color.textMuted,
    fontSize: 10,
    fontWeight: '600',
    textAlign: 'center',
  },
  etaValue: {
    color: theme.color.text,
    fontSize: 15,
    fontWeight: '800',
    textAlign: 'center',
  },
  routeCard: {
    flexDirection: 'row',
    gap: 12,
    padding: 14,
  },
  routeRail: {
    width: 18,
    alignItems: 'center',
    paddingTop: 4,
  },
  routeDotBlue: {
    width: 14,
    height: 14,
    borderRadius: 7,
    borderWidth: 2.5,
    borderColor: '#5EB6FF',
    backgroundColor: 'transparent',
    shadowColor: '#5EB6FF',
    shadowOpacity: 0.9,
    shadowRadius: 6,
    shadowOffset: { width: 0, height: 0 },
  },
  routeLine: {
    flex: 1,
    width: 2,
    backgroundColor: 'rgba(142,187,255,0.55)',
    marginVertical: 4,
  },
  routePinWrap: {
    shadowColor: theme.color.orangeHot,
    shadowOpacity: 0.7,
    shadowRadius: 6,
    shadowOffset: { width: 0, height: 0 },
  },
  routeTexts: {
    flex: 1,
    gap: 12,
  },
  routeBlock: {
    gap: 2,
  },
  routeLabel: {
    color: theme.color.textMuted,
    fontSize: 12,
    fontWeight: '600',
  },
  routeValue: {
    color: theme.color.text,
    fontSize: 15,
    fontWeight: '700',
  },
  contactRow: {
    flexDirection: 'row',
    gap: 10,
  },
  contactBtn: {
    flex: 1,
    minHeight: 48,
    borderRadius: theme.radius.md,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    shadowColor: '#4DB2FF',
    shadowOpacity: 0.35,
    shadowRadius: 10,
    shadowOffset: { width: 0, height: 0 },
    elevation: 4,
  },
  callBtn: {
    backgroundColor: theme.color.orangeHot,
    shadowColor: theme.color.orangeHot,
  },
  chatBtn: {
    backgroundColor: theme.color.blueBright,
  },
  contactText: {
    color: '#fff',
    fontWeight: '800',
    fontSize: 14,
  },
  disabled: {
    opacity: 0.45,
  },
  timeline: {
    paddingHorizontal: 14,
    paddingTop: 12,
    paddingBottom: 4,
  },
  stepRow: {
    flexDirection: 'row',
    gap: 10,
    minHeight: 44,
  },
  stepRail: {
    width: 26,
    alignItems: 'center',
  },
  /** HD voltooid: groene bol met vinkje (pixel-spec). */
  stepDotDone: {
    width: 24,
    height: 24,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#22C55E',
    shadowOpacity: 0.65,
    shadowRadius: 5,
    shadowOffset: { width: 0, height: 1 },
  },
  /**
   * Actief: dikke cyan ring, donkerblauwe vul, witte stip (~45% diameter).
   */
  stepDotActiveOuter: {
    width: 24,
    height: 24,
    borderRadius: 12,
    borderWidth: 3,
    borderColor: '#3DB4FF',
    backgroundColor: '#0A1628',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#3DB4FF',
    shadowOpacity: 0.75,
    shadowRadius: 5,
    shadowOffset: { width: 0, height: 0 },
  },
  stepDotActiveInner: {
    width: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: '#0A1628',
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepDotActiveCore: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: '#FFFFFF',
  },
  stepDotPending: {
    width: 22,
    height: 22,
    borderRadius: 11,
    borderWidth: 2,
    borderColor: 'rgba(183,199,223,0.4)',
    backgroundColor: 'transparent',
  },
  stepLine: {
    width: 3,
    flex: 1,
    backgroundColor: 'rgba(183,199,223,0.28)',
    marginVertical: 2,
    borderRadius: 1.5,
  },
  stepLineDone: {
    backgroundColor: '#3DB4FF',
  },
  stepLineDashedWrap: {
    flex: 1,
    width: 3,
    marginVertical: 2,
    alignItems: 'center',
    justifyContent: 'space-evenly',
    gap: 2,
  },
  stepLineDash: {
    width: 3,
    height: 3,
    borderRadius: 1.5,
    backgroundColor: 'rgba(183,199,223,0.4)',
  },
  stepBody: {
    flex: 1,
    paddingBottom: 8,
    justifyContent: 'center',
  },
  stepTitle: {
    color: theme.color.textMuted,
    fontSize: 14,
    fontWeight: '700',
  },
  stepTitleActive: {
    color: theme.color.text,
  },
  stepDetail: {
    color: theme.color.textMuted,
    fontSize: 12,
    marginTop: 1,
    lineHeight: 15,
  },
  liveBtn: {
    minHeight: 52,
    borderRadius: theme.radius.md,
    backgroundColor: theme.color.orangeHot,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    paddingHorizontal: 16,
    shadowColor: theme.color.orangeHot,
    shadowOpacity: 0.45,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 0 },
    elevation: 5,
  },
  liveBtnText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '800',
    flex: 1,
    textAlign: 'center',
  },
});
