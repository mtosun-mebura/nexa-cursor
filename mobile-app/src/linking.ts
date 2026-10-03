/** Native deep-link scheme (must match app.json `scheme` + backend safeReturnUrl). */
export const APP_SCHEME = 'nexataxi';

export const CUSTOMER_PAYMENT_RETURN_URL = `${APP_SCHEME}://customer`;

export type AppDeepLink = {
  host: string;
  params: Record<string, string>;
};

export function parseAppDeepLink(url: string | null | undefined): AppDeepLink | null {
  if (!url || typeof url !== 'string') return null;
  const trimmed = url.trim();
  if (!trimmed.startsWith(`${APP_SCHEME}://`)) return null;
  try {
    const parsed = new URL(trimmed);
    const params: Record<string, string> = {};
    parsed.searchParams.forEach((value, key) => {
      params[key] = value;
    });
    return {
      host: (parsed.hostname || parsed.host || '').toLowerCase(),
      params,
    };
  } catch {
    return null;
  }
}

export function isCustomerPaymentReturn(link: AppDeepLink | null): boolean {
  if (!link || link.host !== 'customer') return false;
  const boeking = String(link.params.boeking || '');
  return (
    boeking === 'betaald' ||
    boeking === 'betaling-bezig' ||
    boeking === 'betaling-mislukt' ||
    !!link.params.token
  );
}
