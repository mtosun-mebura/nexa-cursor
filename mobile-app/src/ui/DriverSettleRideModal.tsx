import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Image,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import {
  completeRide,
  createRideQrPayment,
  DriverActiveRide,
  fetchRideInvoice,
  fetchRidePayment,
  isContractRide,
  isRidePaid,
  markRideCashPaid,
  RideInvoiceSummary,
  RideOpenPayment,
  rideRequiresPaymentBeforeComplete,
  sendRideInvoice,
} from '../api/driver';
import { ApiError } from '../api/client';
import { ColorPalette } from '../config';
import { useThemeColors } from '../theme/ThemeContext';
import { hexAlpha, useDriverAccent } from '../theme/driverAccent';
import { AppModal } from './AppModal';

function formatEuro(amount: number) {
  return new Intl.NumberFormat('nl-NL', {
    style: 'currency',
    currency: 'EUR',
  }).format(amount);
}

function parseAmount(raw: string): number | null {
  const n = parseFloat(raw.replace(',', '.').replace(/[^\d.]/g, ''));
  if (!Number.isFinite(n) || n < 0.01) return null;
  return Math.round(n * 100) / 100;
}

function defaultAmount(ride: DriverActiveRide): string {
  const due = ride.payment?.amount_due ?? ride.quoted_price;
  if (due == null || !Number.isFinite(Number(due))) return '';
  return Number(due).toFixed(2);
}

function qrImageUrl(open: RideOpenPayment | null): string | null {
  if (!open) return null;
  if (open.qr_url) return open.qr_url;
  if (open.checkout_url) {
    return `https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=${encodeURIComponent(open.checkout_url)}`;
  }
  return null;
}

export function DriverSettleRideModal({
  visible,
  ride,
  token,
  onClose,
  onCompleted,
  onRideUpdated,
}: {
  visible: boolean;
  ride: DriverActiveRide | null;
  token: string;
  onClose: () => void;
  onCompleted: () => void;
  onRideUpdated?: (ride: DriverActiveRide) => void;
}) {
  const colors = useThemeColors();
  const accent = useDriverAccent();
  const styles = useMemo(() => makeStyles(colors, accent.hex), [colors, accent.hex]);
  const [localRide, setLocalRide] = useState<DriverActiveRide | null>(ride);
  const [amountText, setAmountText] = useState('');
  const [busy, setBusy] = useState(false);
  const [sendingInvoice, setSendingInvoice] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [openPayment, setOpenPayment] = useState<RideOpenPayment | null>(null);
  const [invoice, setInvoice] = useState<RideInvoiceSummary | null>(null);
  const [email, setEmail] = useState('');
  const [invoiceNumber, setInvoiceNumber] = useState('');
  const [invoiceMessage, setInvoiceMessage] = useState<string | null>(null);
  const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

  function stopPoll() {
    if (pollRef.current) {
      clearInterval(pollRef.current);
      pollRef.current = null;
    }
  }

  function applyRide(next: DriverActiveRide) {
    setLocalRide(next);
    queueMicrotask(() => onRideUpdated?.(next));
  }

  useEffect(() => {
    if (!visible || !ride) {
      stopPoll();
      return;
    }
    setLocalRide(ride);
    setAmountText(defaultAmount(ride));
    setEmail(String(ride.invoice?.customer_email || ride.customer_email || '').trim());
    setInvoiceNumber(String(ride.invoice?.invoice_number || '').trim());
    setInvoice(ride.invoice || null);
    setOpenPayment(null);
    setError(null);
    setInvoiceMessage(null);
    setSendingInvoice(false);
    setBusy(false);
  }, [visible, ride?.id]);

  useEffect(() => {
    if (!visible || !ride || !token) return;
    let cancelled = false;
    (async () => {
      if (isContractRide(ride)) return;
      try {
        const pay = await fetchRidePayment(token, ride.id);
        if (cancelled) return;
        const nextRide = pay.data?.ride;
        if (nextRide) applyRide(nextRide);
        setOpenPayment(pay.data?.open_payment || null);
        if (nextRide && isRidePaid(nextRide)) {
          const inv = await fetchRideInvoice(token, ride.id);
          if (!cancelled) {
            setInvoice(inv.data || null);
            setEmail(String(inv.data?.customer_email || nextRide.customer_email || '').trim());
            setInvoiceNumber(String(inv.data?.invoice_number || '').trim());
          }
        }
      } catch {
        /* start with inbox payload */
      }
    })();
    return () => {
      cancelled = true;
      stopPoll();
    };
  }, [visible, ride?.id, token]);

  const current = localRide || ride;
  if (!current) return null;
  const rideId = current.id;

  const contract = isContractRide(current);
  const paid = contract || isRidePaid(current);
  const needsPay = !contract && rideRequiresPaymentBeforeComplete(current);
  const qrVisible = !!(openPayment?.checkout_url && openPayment.status === 'open' && !paid);
  const qrUrl = qrVisible ? qrImageUrl(openPayment) : null;
  const qrEnabled = current.payment?.driver_payment_enabled !== false;
  const cashEnabled = current.payment?.cash_payment_enabled !== false;
  const title = contract ? 'Rit afronden' : needsPay ? 'Betalen' : 'Factuur en afronden';

  function startPoll(rideId: number) {
    stopPoll();
    pollRef.current = setInterval(async () => {
      try {
        const data = await fetchRidePayment(token, rideId);
        const nextRide = data.data?.ride;
        const open = data.data?.open_payment || null;
        setOpenPayment(open);
        if (nextRide) applyRide(nextRide);
        const failed = ['failed', 'canceled', 'expired'].includes(String(open?.status || ''));
        if (failed) {
          stopPoll();
          setOpenPayment(null);
          setError(data.data?.payment?.payment_error || 'Betaling is niet gelukt. Probeer opnieuw.');
          return;
        }
        if (nextRide && isRidePaid(nextRide)) {
          stopPoll();
          setOpenPayment(null);
          try {
            const inv = await fetchRideInvoice(token, rideId);
            setInvoice(inv.data || null);
            setEmail(String(inv.data?.customer_email || nextRide.customer_email || '').trim());
            setInvoiceNumber(String(inv.data?.invoice_number || '').trim());
          } catch {
            /* invoice later */
          }
        }
      } catch {
        /* poll ignore */
      }
    }, 2500);
  }

  async function onShowQr() {
    const amount = parseAmount(amountText);
    if (amount == null) {
      setError('Vul een geldig bedrag in.');
      return;
    }
    setBusy(true);
    setError(null);
    try {
        const existing = await fetchRidePayment(token, rideId);
      const open = existing.data?.open_payment;
      if (
        open?.checkout_url &&
        open.status === 'open' &&
        open.amount != null &&
        Math.abs(Number(open.amount) - amount) < 0.005
      ) {
        setOpenPayment(open);
        startPoll(rideId);
        return;
      }
      const created = await createRideQrPayment(token, rideId, amount);
      if (created.data?.ride) applyRide(created.data.ride);
      setOpenPayment(created.data?.open_payment || null);
      if (created.data?.open_payment) startPoll(rideId);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'QR-betaling starten mislukt.');
    } finally {
      setBusy(false);
    }
  }

  function onCash() {
    const amount = parseAmount(amountText);
    if (amount == null) {
      setError('Vul een geldig bedrag in.');
      return;
    }
    Alert.alert(
      'Contant betalen?',
      `Bevestig: klant heeft ${formatEuro(amount)} contant betaald? Dit bedrag wordt vastgelegd.`,
      [
        { text: 'Terug', style: 'cancel' },
        { text: 'Bevestigen', onPress: () => void submitCash(amount) },
      ]
    );
  }

  async function submitCash(amount: number) {
    setBusy(true);
    setError(null);
    stopPoll();
    try {
      const res = await markRideCashPaid(token, rideId, amount);
      const next = res.data?.ride;
      if (next) applyRide(next);
      setOpenPayment(null);
      try {
        const inv = await fetchRideInvoice(token, rideId);
        setInvoice(inv.data || null);
        setEmail(String(inv.data?.customer_email || next?.customer_email || '').trim());
        setInvoiceNumber(String(inv.data?.invoice_number || '').trim());
      } catch {
        /* optioneel */
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Contante betaling mislukt.');
    } finally {
      setBusy(false);
    }
  }

  async function onSendInvoice() {
    const mail = email.trim();
    if (!mail) {
      setError('Vul een e-mailadres in.');
      return;
    }
    setBusy(true);
    setSendingInvoice(true);
    setError(null);
    setInvoiceMessage(null);
    try {
      const res = await sendRideInvoice(token, rideId, mail, invoiceNumber.trim() || undefined);
      setInvoiceMessage(res.message || 'Factuur verstuurd.');
      if (res.data?.invoice) setInvoice(res.data.invoice);
      if (res.data?.ride) applyRide(res.data.ride);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Factuur versturen mislukt.');
    } finally {
      setSendingInvoice(false);
      setBusy(false);
    }
  }

  async function onComplete() {
    if (!contract && rideRequiresPaymentBeforeComplete(current)) {
      setError('Rond eerst de betaling af voordat je de rit afrondt.');
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await completeRide(token, rideId);
      stopPoll();
      onCompleted();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Rit afronden mislukt.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <AppModal
      visible={visible}
      onRequestClose={() => {
        if (!busy) {
          stopPoll();
          onClose();
        }
      }}
      dismissDisabled={busy}
      panelStyle={styles.sheet}
    >
      <ScrollView keyboardShouldPersistTaps="handled" contentContainerStyle={styles.scroll}>
        <Text style={styles.title}>{title}</Text>
        {error ? <Text style={styles.error}>{error}</Text> : null}

        {contract ? (
          <Text style={styles.hint}>
            Contractrit: geen betaling in de app. Je kunt deze rit afronden.
          </Text>
        ) : needsPay ? (
              <>
                <Text style={styles.hint}>
                  Afronden kan pas na betaling. Laat de klant via QR betalen of registreer contant.
                </Text>
                {current.payment?.payment_leg_label ? (
                  <Text style={styles.meta}>{current.payment.payment_leg_label}</Text>
                ) : null}
                <Text style={styles.label}>Te betalen bedrag</Text>
                <View style={styles.amountWrap}>
                  <Text style={styles.amountPrefix}>€</Text>
                  <TextInput
                    style={styles.amountInput}
                    value={amountText}
                    onChangeText={setAmountText}
                    keyboardType="decimal-pad"
                    editable={!busy && !qrVisible}
                    placeholder="0,00"
                    placeholderTextColor={colors.muted}
                  />
                </View>
                {qrVisible && qrUrl ? (
                  <View style={styles.qrBox}>
                    <Text style={styles.hint}>Laat de klant deze QR scannen</Text>
                    <View style={styles.qrFrame}>
                      <Image source={{ uri: qrUrl }} style={styles.qrImage} />
                    </View>
                    <Text style={styles.meta}>Wachten op betaling…</Text>
                    <Pressable
                      style={[styles.btn, styles.btnGhost]}
                      disabled={busy}
                      onPress={() => {
                        stopPoll();
                        setOpenPayment(null);
                      }}
                    >
                      <Text style={styles.btnGhostText}>Terug</Text>
                    </Pressable>
                  </View>
                ) : (
                  <View style={styles.actions}>
                    {qrEnabled ? (
                      <Pressable
                        style={[styles.btn, styles.btnPrimary, busy && styles.btnDisabled]}
                        disabled={busy}
                        onPress={onShowQr}
                      >
                        {busy ? (
                          <ActivityIndicator color="#fff" />
                        ) : (
                          <Text style={styles.btnPrimaryText}>QR-code tonen</Text>
                        )}
                      </Pressable>
                    ) : null}
                    {cashEnabled ? (
                      <Pressable
                        style={[styles.btn, styles.btnGhost, busy && styles.btnDisabled]}
                        disabled={busy}
                        onPress={onCash}
                      >
                        <Text style={styles.btnGhostText}>Contant betalen</Text>
                      </Pressable>
                    ) : null}
                  </View>
                )}
              </>
            ) : (
              <>
                <View style={styles.paidRow}>
                  <Ionicons name="checkmark-circle" size={18} color={colors.success} />
                  <Text style={styles.paidText}>Betaald</Text>
                </View>
                <Text style={styles.hint}>
                  Verstuur de factuur als PDF naar de klant, en rond daarna de rit af.
                </Text>
                <Text style={styles.label}>E-mailadres klant</Text>
                <TextInput
                  style={styles.input}
                  value={email}
                  onChangeText={setEmail}
                  keyboardType="email-address"
                  autoCapitalize="none"
                  autoCorrect={false}
                  editable={!busy}
                  placeholder="klant@email.nl"
                  placeholderTextColor={colors.muted}
                />
                <Text style={styles.label}>Factuurnummer</Text>
                <TextInput
                  style={styles.input}
                  value={invoiceNumber}
                  onChangeText={setInvoiceNumber}
                  editable={!busy}
                  autoCapitalize="none"
                  placeholder="Optioneel"
                  placeholderTextColor={colors.muted}
                />
                {invoiceMessage ? <Text style={styles.success}>{invoiceMessage}</Text> : null}
                <Pressable
                  style={[
                    styles.btn,
                    styles.btnSuccess,
                    (busy || invoice?.can_send === false) && styles.btnDisabled,
                  ]}
                  disabled={busy || invoice?.can_send === false}
                  onPress={onSendInvoice}
                  accessibilityState={{ busy: sendingInvoice }}
                >
                  {sendingInvoice ? (
                    <View style={styles.btnLoading}>
                      <ActivityIndicator color="#fff" />
                      <Text style={styles.btnSuccessText}>Bezig…</Text>
                    </View>
                  ) : (
                    <Text style={styles.btnSuccessText}>
                      {invoice?.invoice_leg_label
                        ? `Factuur ${invoice.invoice_leg_label} versturen`
                        : 'Factuur versturen'}
                    </Text>
                  )}
                </Pressable>
              </>
            )}

        <View style={styles.footer}>
          <Pressable
            style={[styles.btn, styles.btnGhost, busy && styles.btnDisabled]}
            disabled={busy}
            onPress={() => {
              stopPoll();
              onClose();
            }}
          >
            <Text style={styles.btnGhostText}>Sluiten</Text>
          </Pressable>
          <Pressable
            style={[
              styles.btn,
              styles.btnPrimary,
              (busy || needsPay) && styles.btnDisabled,
            ]}
            disabled={busy || needsPay}
            onPress={onComplete}
          >
            <Text style={styles.btnPrimaryText}>
              {busy && paid ? 'Bezig…' : 'Rit afronden'}
            </Text>
          </Pressable>
        </View>
      </ScrollView>
    </AppModal>
  );
}

function makeStyles(colors: ColorPalette, accentHex: string) {
  return StyleSheet.create({
    sheet: {
      maxWidth: 440,
      maxHeight: '88%',
      borderColor: hexAlpha(accentHex, 0.45),
      paddingHorizontal: 0,
      paddingTop: 0,
      paddingBottom: 0,
    },
    scroll: {
      paddingHorizontal: 16,
      paddingTop: 16,
      paddingBottom: 28,
      gap: 10,
    },
    title: { color: colors.text, fontSize: 17, fontWeight: '700' },
    hint: { color: colors.muted, fontSize: 14, lineHeight: 20 },
    meta: { color: colors.muted, fontSize: 13, textAlign: 'center' },
    label: {
      color: colors.muted,
      fontSize: 12,
      fontWeight: '700',
      letterSpacing: 0.3,
      textTransform: 'uppercase',
      marginTop: 4,
    },
    error: { color: colors.danger, fontSize: 14, fontWeight: '600' },
    success: { color: colors.success, fontSize: 14, fontWeight: '600' },
    amountWrap: {
      flexDirection: 'row',
      alignItems: 'center',
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      borderRadius: 12,
      paddingHorizontal: 12,
      minHeight: 48,
    },
    amountPrefix: { color: colors.muted, fontSize: 18, fontWeight: '700', marginRight: 6 },
    amountInput: { flex: 1, color: colors.text, fontSize: 18, fontWeight: '700', paddingVertical: 10 },
    input: {
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
      borderRadius: 12,
      minHeight: 48,
      paddingHorizontal: 12,
      color: colors.text,
      fontSize: 16,
    },
    actions: { gap: 8, marginTop: 4 },
    qrBox: { alignItems: 'center', gap: 10, marginTop: 8 },
    qrFrame: {
      backgroundColor: '#fff',
      padding: 10,
      borderRadius: 12,
    },
    qrImage: { width: 220, height: 220 },
    paidRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
    paidText: { color: colors.success, fontWeight: '800', fontSize: 15 },
    footer: { flexDirection: 'row', gap: 8, marginTop: 8 },
    btn: {
      minHeight: 46,
      borderRadius: 12,
      alignItems: 'center',
      justifyContent: 'center',
      paddingHorizontal: 12,
    },
    btnPrimary: { backgroundColor: accentHex, flex: 1 },
    btnPrimaryText: { color: '#fff', fontWeight: '800' },
    btnSuccess: { backgroundColor: colors.success },
    btnSuccessText: { color: '#fff', fontWeight: '800' },
    btnGhost: {
      flex: 1,
      borderWidth: 1,
      borderColor: hexAlpha(accentHex, 0.45),
    },
    btnGhostText: { color: colors.text, fontWeight: '700' },
    btnLoading: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      gap: 10,
    },
    btnDisabled: { opacity: 0.45 },
  });
}
