import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Animated, Linking, Pressable, StyleSheet, Text, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import type { DispatchOffer, DispatchOfferRide, DriverActiveRide } from '../api/driver';
import { isMarketplaceOffer, isMarketplaceRide } from '../api/driver';
import { ColorPalette } from '../config';
import { formatEuroNl } from '../geo/route';
import { hexAlpha, useDriverAccent } from '../theme/driverAccent';
import { useThemeColors } from '../theme/ThemeContext';

const OVERDUE_RED = '#EF4444';
const GREEN = '#22C55E';

/** Flikkerende stip vóór “Ophaalmoment verlopen” (alleen eigen klant). */
function OverdueFlickerDot() {
  const opacity = useRef(new Animated.Value(1)).current;
  useEffect(() => {
    const anim = Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, { toValue: 0.15, duration: 450, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 1, duration: 450, useNativeDriver: true }),
      ])
    );
    anim.start();
    return () => anim.stop();
  }, [opacity]);

  return (
    <Animated.View
      style={{
        width: 7,
        height: 7,
        borderRadius: 999,
        backgroundColor: '#FCA5A5',
        marginRight: 6,
        opacity,
      }}
      accessibilityElementsHidden
    />
  );
}

function stripPostalAndCountry(part: string): string {
  return part
    .replace(/\b\d{4}\s?[A-Za-z]{2}\b/g, '')
    .replace(/\b(nederland|netherlands|nl)\b/gi, '')
    .replace(/\s{2,}/g, ' ')
    .replace(/^[\s,]+|[\s,]+$/g, '')
    .trim();
}

function compactAddress(address?: string | null): string {
  const text = String(address || '').trim();
  if (!text) return '';
  const parts = text
    .split(',')
    .map((p) => stripPostalAndCountry(p.trim()))
    .filter(Boolean);
  if (!parts.length) return text;
  if (parts.length === 1) return parts[0];
  return `${parts[0]}, ${parts[parts.length - 1]}`;
}

function splitAddress(address?: string | null): { main: string; sub: string } {
  const text = String(address || '').trim();
  if (!text) return { main: '—', sub: '' };
  const comma = text.indexOf(',');
  if (comma > 0 && comma < text.length - 1) {
    return { main: text.slice(0, comma).trim(), sub: text.slice(comma + 1).trim() };
  }
  return { main: text, sub: '' };
}

function formatPickupLabel(iso?: string | null): string {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleString('nl-NL', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function formatDistanceKm(km?: number | null): string | null {
  if (km == null || !Number.isFinite(Number(km))) return null;
  return `${String(km).replace('.', ',')} km`;
}

function formatDurationMins(ride?: DispatchOfferRide | null): string | null {
  if (!ride) return null;
  let mins: number | null = null;
  if (ride.duration_seconds != null && Number.isFinite(Number(ride.duration_seconds))) {
    mins = Math.max(0, Math.round(Number(ride.duration_seconds) / 60));
  } else if (ride.duration_minutes != null && Number.isFinite(Number(ride.duration_minutes))) {
    mins = Math.max(0, Math.round(Number(ride.duration_minutes)));
  }
  if (mins == null) return null;
  const hours = Math.floor(mins / 60);
  const rest = mins % 60;
  if (hours <= 0) return `${rest} min`;
  if (rest <= 0) return `${hours} u`;
  return `${hours} u ${rest} min`;
}

function baggageLines(ride?: DispatchOfferRide | null): string[] {
  const items = ride?.baggage?.items || [];
  if (items.length) {
    return items.map((i) => `${i.label} × ${i.qty}`);
  }
  const summary = String(ride?.baggage?.summary || '').trim();
  if (!summary || /^geen$/i.test(summary)) return ['Geen'];
  return summary
    .split(',')
    .map((part) => part.trim())
    .filter(Boolean);
}

type TripVariant = 'active' | 'scheduled' | 'overdue' | 'completed';

type OfferModeProps = {
  offer: DispatchOffer;
  ride?: never;
  variant?: never;
  busy?: boolean;
  highlighted?: boolean;
  onHighlightEnd?: () => void;
  fallbackVehicleLabel?: string | null;
  fallbackVehicleName?: string | null;
  onAccept: () => void;
  onDecline: () => void;
  onStart?: never;
  onComplete?: never;
  onOpenMaps?: never;
  onCancel?: never;
  onArchive?: never;
};

type TripModeProps = {
  offer?: never;
  ride: DriverActiveRide;
  variant: TripVariant;
  busy?: boolean;
  highlighted?: boolean;
  onHighlightEnd?: () => void;
  fallbackVehicleLabel?: string | null;
  fallbackVehicleName?: string | null;
  onAccept?: never;
  onDecline?: never;
  onStart?: () => void;
  onComplete?: () => void;
  onOpenMaps?: () => void;
  onCancel?: () => void;
  onArchive?: () => void;
  archived?: boolean;
};

export type DriverOfferCardProps = OfferModeProps | TripModeProps;

/** Blijft behouden bij tab-wissels; nieuwe kaarten starten ingeklapt. */
const cardExpandedByKey = new Map<string, boolean>();

function cardExpandKey(props: DriverOfferCardProps): string {
  if (props.offer) return `offer-${props.offer.id}`;
  return `ride-${props.ride.id}`;
}

export function DriverOfferCard(props: DriverOfferCardProps) {
  const colors = useThemeColors();
  const accent = useDriverAccent();
  const styles = useMemo(() => makeStyles(colors, accent.hex), [colors, accent.hex]);
  const expandKey = cardExpandKey(props);
  const [expanded, setExpanded] = useState(
    () => cardExpandedByKey.get(expandKey) ?? false
  );

  // Na remount (andere tab → terug) de opgeslagen stand herstellen.
  useEffect(() => {
    setExpanded(cardExpandedByKey.get(expandKey) ?? false);
  }, [expandKey]);

  function toggleExpanded() {
    setExpanded((prev) => {
      const next = !prev;
      cardExpandedByKey.set(expandKey, next);
      return next;
    });
  }

  const isTrip = !props.offer;
  const offer = props.offer;
  const ride: DispatchOfferRide | undefined = isTrip ? props.ride : offer?.ride;
  const tripVariant = isTrip ? props.variant : undefined;
  const busy = props.busy;
  const highlighted = !!props.highlighted;
  const onHighlightEndRef = useRef(props.onHighlightEnd);
  onHighlightEndRef.current = props.onHighlightEnd;
  const highlightOpacity = useRef(new Animated.Value(0)).current;
  const [highlightActive, setHighlightActive] = useState(false);

  useEffect(() => {
    if (!highlighted) {
      highlightOpacity.setValue(0);
      setHighlightActive(false);
      return;
    }
    setHighlightActive(true);
    highlightOpacity.setValue(0);
    const flash = (to: number) =>
      Animated.timing(highlightOpacity, {
        toValue: to,
        duration: 180,
        useNativeDriver: true,
      });
    const anim = Animated.sequence([
      flash(1),
      flash(0),
      flash(1),
      flash(0),
      flash(1),
      flash(0),
    ]);
    anim.start(({ finished }) => {
      setHighlightActive(false);
      if (finished) onHighlightEndRef.current?.();
    });
    return () => {
      anim.stop();
    };
  }, [highlighted, highlightOpacity]);

  const marketplace = isTrip
    ? isMarketplaceRide(props.ride)
    : isMarketplaceOffer(offer!) ||
      !!ride?.fee_breakdown?.is_marketplace ||
      !!ride?.is_nexa_suite;
  const network = !!ride?.is_network_ride || !!ride?.fee_breakdown?.is_network;
  const ownCustomer = !marketplace && !network;

  const pickupOverdue = isTrip
    ? tripVariant === 'overdue'
    : !!(
        offer?.is_pickup_overdue ||
        ride?.is_pickup_overdue ||
        offer?.is_waiting ||
        ride?.is_scheduled_overdue
      );
  // Marketplace: nooit overdue-chrome. Eigen klant wel.
  const showOverdueUi = pickupOverdue && ownCustomer;

  const pickup = splitAddress(ride?.pickup_address);
  const dropoff = splitAddress(ride?.dropoff_address);
  const pickupCompact = compactAddress(ride?.pickup_address);
  const dropoffCompact = compactAddress(ride?.dropoff_address);
  const price =
    ride?.quoted_price != null && Number.isFinite(Number(ride.quoted_price))
      ? formatEuroNl(Number(ride.quoted_price))
      : null;
  const distance = formatDistanceKm(ride?.distance_km);
  const duration = formatDurationMins(ride);
  const passengers = Number(ride?.passengers || 0);
  const passengersLabel = passengers > 0 ? String(passengers) : null;
  const phone = String(ride?.customer_phone || '').trim();
  const kindLabel = network ? 'Network' : marketplace ? 'NEXA Suite' : 'Taxi';

  const isAccepted =
    isTrip &&
    !showOverdueUi &&
    tripVariant !== 'active' &&
    tripVariant !== 'completed' &&
    (ride?.status === 'accepted' || tripVariant === 'scheduled');
  const isCompletedTrip = isTrip && (tripVariant === 'completed' || ride?.status === 'completed');
  const statusBadgeLabel = isTrip
    ? isCompletedTrip
      ? 'Afgerond'
      : tripVariant === 'active'
      ? 'Onderweg'
      : showOverdueUi
        ? 'Ophaalmoment verlopen'
        : isAccepted
          ? 'Geaccepteerd'
          : 'Gepland'
    : null;

  const primaryLabel = showOverdueUi && !isTrip ? 'Nieuw tijdstip voorstellen' : 'Accepteren';
  const secondaryLabel = showOverdueUi && !isTrip ? 'Vrijgeven' : 'Weigeren';
  const baggage = baggageLines(ride);
  const showBaggage = baggage.length > 0 && !(baggage.length === 1 && baggage[0] === 'Geen');
  const showMetrics = !!(distance || duration || passengersLabel);
  const showStats = showMetrics || showBaggage;
  const showOfferSecondary = !isTrip && !marketplace;
  const canStart = isTrip && ride?.status === 'accepted' && !!props.onStart;
  const canComplete = isTrip && tripVariant === 'active' && !canStart && !!props.onComplete;
  const canCancel = isTrip && !!ride?.can_cancel_with_reason && !!props.onCancel && !isCompletedTrip;
  const canArchive = isCompletedTrip && !!props.onArchive;
  const fee = ride?.fee_breakdown;
  const showFee =
    !!fee &&
    (fee.customer_pays != null ||
      !!fee.owner_name ||
      !!fee.executor_name ||
      fee.nexa_fee != null);
  const driverShare =
    fee?.driver_share != null
      ? Number(fee.driver_share)
      : fee?.customer_pays != null && fee?.nexa_fee != null
        ? Math.max(0, Number(fee.customer_pays) - Number(fee.nexa_fee))
        : null;
  const cardBorder = accent.border;

  return (
    <View
      style={[
        styles.card,
        { borderColor: showOverdueUi ? OVERDUE_RED : isCompletedTrip ? colors.muted : cardBorder },
        showOverdueUi && styles.cardOverdue,
        isCompletedTrip && styles.cardCompleted,
      ]}
    >
      {highlightActive ? (
        <Animated.View
          pointerEvents="none"
          style={[styles.highlightRing, { opacity: highlightOpacity }]}
        />
      ) : null}
      <View
        style={[
          styles.accentBar,
          {
            backgroundColor: showOverdueUi
              ? OVERDUE_RED
              : network
                ? '#3B82F6'
                : accent.hex,
          },
        ]}
      />

      <Pressable
        onPress={toggleExpanded}
        style={styles.header}
        accessibilityRole="button"
        accessibilityState={{ expanded }}
      >
        <View style={styles.headerText}>
          <View style={styles.badgeRow}>
            {showOverdueUi && !isTrip ? (
              <View style={[styles.badge, styles.badgeDanger]}>
                <OverdueFlickerDot />
                <Text style={styles.badgeDangerText}>Ophaalmoment verlopen</Text>
              </View>
            ) : null}
            <View style={[styles.badge, network ? styles.badgeNetwork : styles.badgeTaxi]}>
              <Text style={network ? styles.badgeNetworkText : styles.badgeTaxiText}>
                {kindLabel}
              </Text>
            </View>
            {statusBadgeLabel ? (
              <View
                style={[
                  styles.badge,
                  showOverdueUi
                    ? styles.badgeDanger
                    : isCompletedTrip
                      ? styles.badgeNeutral
                      : isAccepted
                      ? styles.badgeSuccess
                      : tripVariant === 'active'
                        ? styles.badgeActive
                        : styles.badgeNeutral,
                ]}
              >
                {showOverdueUi ? <OverdueFlickerDot /> : null}
                <Text
                  style={
                    showOverdueUi
                      ? styles.badgeDangerText
                      : isCompletedTrip
                        ? styles.badgeNeutralText
                        : isAccepted
                        ? styles.badgeSuccessText
                        : tripVariant === 'active'
                          ? styles.badgeActiveText
                          : styles.badgeNeutralText
                  }
                >
                  {statusBadgeLabel}
                </Text>
              </View>
            ) : null}
          </View>
          {!marketplace ? (
            <Text style={styles.title}>Rit #{ride?.id ?? offer?.id}</Text>
          ) : null}
          {ride?.pickup_at ? (
            <Text style={[styles.meta, marketplace && styles.metaProminent]}>
              {formatPickupLabel(ride.pickup_at)}
            </Text>
          ) : null}
          {!expanded && (pickupCompact || dropoffCompact) ? (
            <View style={styles.collapsedRoute}>
              {pickupCompact ? (
                <Text style={styles.collapsedRouteLine} numberOfLines={1}>
                  {pickupCompact}
                </Text>
              ) : null}
              {dropoffCompact ? (
                <Text style={styles.collapsedRouteLine} numberOfLines={1}>
                  →  {dropoffCompact}
                </Text>
              ) : null}
            </View>
          ) : null}
        </View>
        <View style={styles.headerRight}>
          {!expanded && price ? <Text style={styles.collapsedPrice}>{price}</Text> : null}
          <View style={styles.chevron}>
            <Ionicons
              name={expanded ? 'chevron-up' : 'chevron-down'}
              size={15}
              color={colors.muted}
            />
          </View>
        </View>
      </Pressable>

      {expanded ? (
        <View style={styles.body}>
          <View style={styles.route}>
            <View style={styles.routeRail} />
            <View style={[styles.routeStop, styles.routeStopFirst]}>
              <View style={styles.routeHead}>
                <View style={[styles.dotOuter, styles.dotOuterPickup]}>
                  <View style={[styles.dot, styles.dotPickup]} />
                </View>
                <Text style={styles.routeLabel}>Ophalen</Text>
              </View>
              <View style={styles.routeAddress}>
                <Text style={styles.routeMain}>{pickup.main}</Text>
                {pickup.sub ? <Text style={styles.routeSub}>{pickup.sub}</Text> : null}
              </View>
            </View>
            <View style={[styles.routeStop, styles.routeStopLast]}>
              <View style={styles.routeHead}>
                <View style={[styles.dotOuter, styles.dotOuterDropoff]}>
                  <View style={[styles.dot, styles.dotDropoff]} />
                </View>
                <Text style={styles.routeLabel}>Afzetten</Text>
              </View>
              <View style={styles.routeAddress}>
                <Text style={styles.routeMain}>{dropoff.main}</Text>
                {dropoff.sub ? <Text style={styles.routeSub}>{dropoff.sub}</Text> : null}
              </View>
            </View>
          </View>

          <View style={styles.contactCard}>
            <View style={styles.contactLeft}>
              <Text style={styles.contactName} numberOfLines={2}>
                {ride?.customer_name || '—'}
              </Text>
              {phone ? (
                <Pressable
                  onPress={() => Linking.openURL(`tel:${phone.replace(/[^\d+]/g, '')}`)}
                  hitSlop={6}
                  style={styles.phoneRow}
                >
                  <Ionicons name="call-outline" size={13} color={colors.primary} />
                  <Text style={styles.contactPhone}>{phone}</Text>
                </Pressable>
              ) : null}
            </View>
            {price && !showFee ? (
              <View style={styles.priceBlock}>
                <Text style={styles.priceLabel}>Prijs</Text>
                <Text style={styles.price}>{price}</Text>
              </View>
            ) : null}
          </View>

          {showFee && fee ? (
            <View style={styles.feeBox} accessibilityLabel="Fee-splitsing">
              <View style={styles.feeRow}>
                <Text style={styles.feeLabel}>Klant betaalt</Text>
                <Text style={styles.feeValue}>
                  {fee.customer_pays != null ? formatEuroNl(Number(fee.customer_pays)) : '—'}
                </Text>
              </View>
              <View style={styles.feeRow}>
                <Text style={styles.feeLabel}>Eigenaar</Text>
                <Text style={styles.feeValue}>{fee.owner_name || '—'}</Text>
              </View>
              <View style={styles.feeRow}>
                <Text style={styles.feeLabel}>Uitvoerder</Text>
                <Text style={styles.feeValue}>{fee.executor_name || '—'}</Text>
              </View>
              <View style={styles.feeRow}>
                <Text style={styles.feeLabel}>NEXA fee</Text>
                <Text style={styles.feeValue}>
                  {fee.nexa_fee != null ? formatEuroNl(Number(fee.nexa_fee)) : '—'}
                  {fee.nexa_fee_percent != null ? (
                    <Text style={styles.feePct}>{` (${fee.nexa_fee_percent}%)`}</Text>
                  ) : null}
                </Text>
              </View>
              <View style={styles.feeRow}>
                <Text style={styles.feeLabel}>Chauffeur</Text>
                <Text style={styles.feeValue}>
                  {driverShare != null ? formatEuroNl(driverShare) : '—'}
                </Text>
              </View>
            </View>
          ) : null}

          {showStats ? (
            <View style={styles.stats}>
              {showMetrics ? (
                <View style={styles.metricsRow}>
                  {distance ? (
                    <View style={styles.metric}>
                      <Ionicons name="navigate-outline" size={14} color={colors.muted} />
                      <Text style={styles.metricValue}>{distance}</Text>
                      <Text style={styles.metricLabel}>afstand</Text>
                    </View>
                  ) : null}
                  {distance && (duration || passengersLabel) ? (
                    <View style={styles.metricDivider} />
                  ) : null}
                  {duration ? (
                    <View style={styles.metric}>
                      <Ionicons name="time-outline" size={14} color={colors.muted} />
                      <Text style={styles.metricValue}>{duration}</Text>
                      <Text style={styles.metricLabel}>rijtijd</Text>
                    </View>
                  ) : null}
                  {duration && passengersLabel ? <View style={styles.metricDivider} /> : null}
                  {passengersLabel ? (
                    <View style={styles.metric}>
                      <Ionicons name="people-outline" size={14} color={colors.muted} />
                      <Text style={styles.metricValue}>{passengersLabel}</Text>
                      <Text style={styles.metricLabel}>
                        {Number(passengersLabel) === 1 ? 'persoon' : 'personen'}
                      </Text>
                    </View>
                  ) : null}
                </View>
              ) : null}
              {showBaggage ? (
                <View style={[styles.baggageBlock, showMetrics && styles.baggageBlockSpaced]}>
                  <Text style={styles.baggageHeading}>Bagage</Text>
                  <View style={styles.baggageChips}>
                    {baggage.map((line, index) => (
                      <View key={`${index}-${line}`} style={styles.baggageChip}>
                        <Text style={styles.baggageChipText}>{line}</Text>
                      </View>
                    ))}
                  </View>
                </View>
              ) : null}
            </View>
          ) : null}
        </View>
      ) : null}

      <View style={[styles.actions, !expanded && styles.actionsCollapsed]}>
        {isTrip ? (
          <>
            {isCompletedTrip ? (
              <View style={styles.doneBanner} accessibilityRole="text">
                <View style={styles.doneBannerDot} />
                <View style={styles.activeBannerCopy}>
                  <Text style={styles.doneBannerTitle}>Rit is afgerond</Text>
                  <Text style={styles.activeBannerText}>Deze rit is voltooid.</Text>
                </View>
              </View>
            ) : null}
            {canComplete ? (
              <View style={styles.activeBanner} accessibilityRole="text">
                <View style={styles.activeBannerDot} />
                <View style={styles.activeBannerCopy}>
                  <Text style={styles.activeBannerTitle}>Rit is actief</Text>
                  <Text style={styles.activeBannerText}>
                    Klant is onderweg naar de bestemming.
                  </Text>
                </View>
              </View>
            ) : null}
            {canCancel ? (
              <Pressable
                style={[styles.btn, styles.btnGhost, busy && styles.btnDisabled]}
                disabled={busy}
                onPress={props.onCancel}
              >
                <Text style={styles.btnGhostText}>Annuleren</Text>
              </Pressable>
            ) : null}
            {props.onOpenMaps && !isCompletedTrip ? (
              <Pressable
                style={[styles.btn, styles.btnGhost, busy && styles.btnDisabled]}
                disabled={busy}
                onPress={props.onOpenMaps}
              >
                <View style={styles.btnInner}>
                  <Ionicons
                    name={tripVariant === 'active' ? 'flag-outline' : 'navigate-outline'}
                    size={16}
                    color={colors.text}
                  />
                  <Text style={styles.btnGhostText}>
                    {tripVariant === 'active' ? 'Afzetten' : 'Ophalen'}
                  </Text>
                </View>
              </Pressable>
            ) : null}
            {canStart ? (
              <Pressable
                style={[styles.btn, styles.btnPrimary, busy && styles.btnDisabled]}
                disabled={busy}
                onPress={props.onStart}
              >
                <Text style={styles.btnPrimaryText}>Klant opgehaald rit starten</Text>
              </Pressable>
            ) : null}
            {canComplete ? (
              <Pressable
                style={[styles.btn, styles.btnPrimary, busy && styles.btnDisabled]}
                disabled={busy}
                onPress={props.onComplete}
              >
                <Text style={styles.btnPrimaryText}>Rit afronden</Text>
              </Pressable>
            ) : null}
            {canArchive ? (
              <Pressable
                style={[styles.btn, styles.btnGhost, styles.btnFull, busy && styles.btnDisabled]}
                disabled={busy}
                onPress={props.onArchive}
              >
                <Text style={styles.btnGhostText}>
                  {props.archived ? 'Terugzetten' : 'Naar archief'}
                </Text>
              </Pressable>
            ) : null}
          </>
        ) : (
          <>
            {showOfferSecondary ? (
              <Pressable
                style={[styles.btn, styles.btnGhost, busy && styles.btnDisabled]}
                disabled={busy}
                onPress={props.onDecline}
              >
                <Text style={styles.btnGhostText}>{secondaryLabel}</Text>
              </Pressable>
            ) : null}
            <Pressable
              style={[styles.btn, styles.btnPrimary, busy && styles.btnDisabled]}
              disabled={busy}
              onPress={props.onAccept}
            >
              <Text style={styles.btnPrimaryText}>{primaryLabel}</Text>
            </Pressable>
          </>
        )}
      </View>
    </View>
  );
}

function makeStyles(colors: ColorPalette, accentHex: string) {
  return StyleSheet.create({
    card: {
      borderWidth: 1,
      borderRadius: 16,
      backgroundColor: colors.card,
      marginBottom: 14,
      paddingTop: 14,
      paddingHorizontal: 14,
      paddingBottom: 14,
      overflow: 'hidden',
      position: 'relative',
    },
    cardOverdue: {
      borderWidth: 1.5,
    },
    cardCompleted: {
      opacity: 0.78,
    },
    highlightRing: {
      position: 'absolute',
      left: 0,
      top: 0,
      right: 0,
      bottom: 0,
      borderRadius: 16,
      borderWidth: 2,
      borderColor: GREEN,
      zIndex: 5,
    },
    accentBar: {
      position: 'absolute',
      left: 0,
      top: 0,
      bottom: 0,
      width: 3,
    },
    header: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: 10,
      paddingLeft: 4,
    },
    headerText: {
      flex: 1,
      minWidth: 0,
      gap: 4,
    },
    badgeRow: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 6,
      marginBottom: 2,
    },
    headerRight: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: 8,
      marginTop: 2,
      marginLeft: 4,
    },
    collapsedPrice: {
      color: colors.text,
      fontSize: 18,
      fontWeight: '800',
      letterSpacing: -0.3,
      textAlign: 'right',
      marginTop: 4,
    },
    badge: {
      alignSelf: 'flex-start',
      flexDirection: 'row',
      alignItems: 'center',
      borderRadius: 999,
      paddingHorizontal: 10,
      paddingVertical: 4,
    },
    badgeDanger: {
      backgroundColor: 'rgba(239,68,68,0.18)',
    },
    badgeDangerText: {
      color: '#FCA5A5',
      fontSize: 10,
      fontWeight: '800',
      textTransform: 'uppercase',
      letterSpacing: 0.35,
    },
    badgeSuccess: {
      backgroundColor: 'rgba(34,197,94,0.18)',
      borderWidth: 1,
      borderColor: 'rgba(34,197,94,0.45)',
    },
    badgeSuccessText: {
      color: '#86EFAC',
      fontSize: 10,
      fontWeight: '800',
      textTransform: 'uppercase',
      letterSpacing: 0.35,
    },
    badgeActive: {
      backgroundColor: 'rgba(59,130,246,0.16)',
      borderWidth: 1,
      borderColor: 'rgba(59,130,246,0.4)',
    },
    badgeActiveText: {
      color: '#93C5FD',
      fontSize: 10,
      fontWeight: '800',
      textTransform: 'uppercase',
      letterSpacing: 0.35,
    },
    badgeNeutral: {
      backgroundColor: 'rgba(148,163,184,0.16)',
    },
    badgeNeutralText: {
      color: colors.muted,
      fontSize: 10,
      fontWeight: '800',
      textTransform: 'uppercase',
      letterSpacing: 0.35,
    },
    badgeTaxi: {
      backgroundColor: hexAlpha(accentHex, 0.14),
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.4),
    },
    badgeTaxiText: {
      color: accentHex,
      fontSize: 10,
      fontWeight: '800',
      textTransform: 'uppercase',
      letterSpacing: 0.4,
    },
    badgeNetwork: {
      backgroundColor: 'rgba(59,130,246,0.16)',
      borderWidth: 1,
      borderColor: 'rgba(59,130,246,0.45)',
    },
    badgeNetworkText: {
      color: '#93C5FD',
      fontSize: 10,
      fontWeight: '800',
      letterSpacing: 0.25,
    },
    title: {
      color: colors.text,
      fontSize: 17,
      fontWeight: '800',
      letterSpacing: -0.2,
    },
    meta: {
      color: colors.muted,
      fontSize: 14,
      fontWeight: '600',
    },
    metaProminent: {
      color: colors.text,
      fontSize: 18,
      fontWeight: '800',
      letterSpacing: -0.3,
      marginTop: 2,
    },
    collapsedRoute: {
      marginTop: 4,
      gap: 2,
    },
    collapsedRouteLine: {
      color: colors.muted,
      fontSize: 12,
      lineHeight: 16,
    },
    chevron: {
      width: 30,
      height: 30,
      borderRadius: 999,
      backgroundColor: 'rgba(148,163,184,0.1)',
      alignItems: 'center',
      justifyContent: 'center',
      marginTop: 2,
    },
    body: {
      marginTop: 14,
      paddingTop: 14,
      paddingLeft: 4,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: hexAlpha(accentHex, 0.35),
      gap: 16,
    },
    route: {
      position: 'relative',
    },
    routeRail: {
      position: 'absolute',
      left: 9,
      top: 20,
      bottom: 0,
      width: 0,
      borderLeftWidth: 2,
      borderStyle: 'dashed',
      borderColor: hexAlpha(accentHex, 0.45),
    },
    routeStop: {
      paddingBottom: 16,
    },
    routeStopFirst: {},
    routeStopLast: {
      paddingBottom: 0,
    },
    routeHead: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
      marginBottom: 4,
    },
    dotOuter: {
      width: 20,
      height: 20,
      borderRadius: 999,
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 1,
    },
    dotOuterPickup: {
      backgroundColor: 'rgba(34,197,94,0.18)',
    },
    dotOuterDropoff: {
      backgroundColor: hexAlpha(accentHex, 0.18),
    },
    dot: {
      width: 10,
      height: 10,
      borderRadius: 999,
    },
    dotPickup: {
      backgroundColor: GREEN,
    },
    dotDropoff: {
      backgroundColor: accentHex,
    },
    routeLabel: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '600',
      letterSpacing: 0.2,
    },
    routeAddress: {
      paddingLeft: 30,
    },
    routeMain: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '800',
      lineHeight: 21,
      letterSpacing: -0.2,
    },
    routeSub: {
      color: colors.muted,
      fontSize: 12,
      marginTop: 2,
      lineHeight: 16,
    },
    contactCard: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: 12,
      paddingVertical: 12,
      paddingHorizontal: 12,
      borderRadius: 14,
      backgroundColor: 'rgba(148,163,184,0.06)',
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: hexAlpha(accentHex, 0.35),
    },
    contactLeft: {
      flex: 1,
      minWidth: 0,
      gap: 5,
    },
    contactName: {
      color: colors.text,
      fontSize: 16,
      fontWeight: '800',
      lineHeight: 21,
      letterSpacing: -0.2,
    },
    phoneRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 5,
      alignSelf: 'flex-start',
    },
    contactPhone: {
      color: colors.primary,
      fontSize: 14,
      fontWeight: '600',
    },
    priceBlock: {
      alignItems: 'flex-end',
      gap: 1,
    },
    priceLabel: {
      color: colors.muted,
      fontSize: 10,
      fontWeight: '600',
      textTransform: 'uppercase',
      letterSpacing: 0.5,
    },
    price: {
      color: colors.text,
      fontSize: 22,
      fontWeight: '800',
      letterSpacing: -0.4,
    },
    feeBox: {
      borderRadius: 14,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: hexAlpha(accentHex, 0.35),
      paddingHorizontal: 12,
      paddingVertical: 10,
      gap: 8,
    },
    feeRow: {
      flexDirection: 'row',
      alignItems: 'baseline',
      justifyContent: 'space-between',
      gap: 12,
    },
    feeLabel: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '500',
      flex: 1,
    },
    feeValue: {
      color: colors.text,
      fontSize: 13,
      fontWeight: '700',
      textAlign: 'right',
    },
    feePct: {
      color: colors.muted,
      fontWeight: '500',
    },
    stats: {
      borderRadius: 14,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: hexAlpha(accentHex, 0.35),
      backgroundColor: 'rgba(148,163,184,0.05)',
      paddingHorizontal: 10,
      paddingVertical: 12,
    },
    metricsRow: {
      flexDirection: 'row',
      alignItems: 'stretch',
    },
    metric: {
      flex: 1,
      alignItems: 'center',
      gap: 3,
      paddingVertical: 2,
    },
    metricValue: {
      color: colors.text,
      fontSize: 15,
      fontWeight: '800',
      letterSpacing: -0.2,
    },
    metricLabel: {
      color: colors.muted,
      fontSize: 11,
      fontWeight: '500',
    },
    metricDivider: {
      width: StyleSheet.hairlineWidth,
      backgroundColor: hexAlpha(accentHex, 0.35),
      marginVertical: 4,
    },
    baggageBlock: {
      gap: 8,
    },
    baggageBlockSpaced: {
      marginTop: 12,
      paddingTop: 12,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: hexAlpha(accentHex, 0.35),
    },
    baggageHeading: {
      color: colors.muted,
      fontSize: 10,
      fontWeight: '700',
      textTransform: 'uppercase',
      letterSpacing: 0.6,
    },
    baggageChips: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 6,
    },
    baggageChip: {
      borderRadius: 999,
      paddingHorizontal: 10,
      paddingVertical: 5,
      backgroundColor: 'rgba(148,163,184,0.12)',
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: hexAlpha(accentHex, 0.35),
    },
    baggageChipText: {
      color: colors.text,
      fontSize: 12,
      fontWeight: '700',
    },
    actions: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 8,
      marginTop: 14,
      paddingLeft: 4,
    },
    actionsCollapsed: {
      marginTop: 14,
      paddingTop: 14,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: hexAlpha(accentHex, 0.35),
    },
    activeBanner: {
      width: '100%',
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: 'rgba(34,197,94,0.4)',
      backgroundColor: 'rgba(34,197,94,0.12)',
      paddingHorizontal: 12,
      paddingVertical: 10,
    },
    activeBannerDot: {
      width: 8,
      height: 8,
      borderRadius: 4,
      backgroundColor: GREEN,
    },
    activeBannerCopy: {
      flex: 1,
      minWidth: 0,
      gap: 1,
    },
    activeBannerTitle: {
      color: GREEN,
      fontSize: 13,
      fontWeight: '800',
    },
    activeBannerText: {
      color: colors.text,
      fontSize: 12,
      fontWeight: '500',
      opacity: 0.85,
    },
    doneBanner: {
      width: '100%',
      flexDirection: 'row',
      alignItems: 'center',
      gap: 10,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: 'rgba(148,163,184,0.4)',
      backgroundColor: 'rgba(148,163,184,0.12)',
      paddingHorizontal: 12,
      paddingVertical: 10,
    },
    doneBannerDot: {
      width: 8,
      height: 8,
      borderRadius: 4,
      backgroundColor: colors.muted,
    },
    doneBannerTitle: {
      color: colors.muted,
      fontSize: 13,
      fontWeight: '800',
    },
    btn: {
      flex: 1,
      minWidth: '40%',
      minHeight: 48,
      borderRadius: 14,
      alignItems: 'center',
      justifyContent: 'center',
      paddingHorizontal: 10,
      paddingVertical: 12,
    },
    btnFull: {
      minWidth: '100%',
      flexBasis: '100%',
    },
    btnInner: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 6,
    },
    btnDisabled: {
      opacity: 0.55,
    },
    btnGhost: {
      borderWidth: 1.5,
      borderColor: accentHex,
      backgroundColor: 'transparent',
    },
    btnGhostText: {
      color: colors.text,
      fontWeight: '800',
      fontSize: 12,
      textTransform: 'uppercase',
      letterSpacing: 0.3,
      textAlign: 'center',
    },
    btnPrimary: {
      backgroundColor: accentHex,
    },
    btnPrimaryText: {
      color: '#fff',
      fontWeight: '800',
      fontSize: 12,
      lineHeight: 16,
      textTransform: 'uppercase',
      letterSpacing: 0.2,
      textAlign: 'center',
    },
  });
}
