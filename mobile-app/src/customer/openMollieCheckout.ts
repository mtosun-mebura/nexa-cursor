import { Keyboard, Linking, Platform } from 'react-native';
import { CUSTOMER_PAYMENT_RETURN_URL } from '../linking';

export type MollieCheckoutOpenResult = {
  /** Deep-link return URL when AuthSession closed on redirect (mainly iOS). */
  returnUrl: string | null;
};

type WebBrowserModule = typeof import('expo-web-browser');

function loadWebBrowser(): WebBrowserModule | null {
  try {
    // Native module ontbreekt soms in een stale Dev Client — dan Linking-fallback.
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    return require('expo-web-browser') as WebBrowserModule;
  } catch {
    return null;
  }
}

/** Close an in-app browser / Custom Tab after a nexataxi:// payment return. */
export function dismissMollieCheckout(): void {
  const WebBrowser = loadWebBrowser();
  if (!WebBrowser) return;
  void WebBrowser.dismissBrowser().catch(() => undefined);
}

/**
 * Open Mollie checkout so the payment UI stays visible.
 *
 * Android: Chrome Custom Tabs (`openBrowserAsync` + createTask/proxy). Plain
 * `Linking.openURL` after booking setState/MapView commits often never surfaces
 * (or flashes and returns). Custom Tabs stay in front of the app task.
 *
 * iOS: `openAuthSessionAsync` with the nexataxi return scheme.
 */
export async function openMollieCheckout(url: string): Promise<MollieCheckoutOpenResult> {
  const checkout = String(url || '').trim();
  if (!checkout) {
    throw new Error('missing_checkout_url');
  }

  Keyboard.dismiss();

  if (Platform.OS === 'android') {
    await new Promise<void>((resolve) => {
      requestAnimationFrame(() => resolve());
    });
  }

  const WebBrowser = loadWebBrowser();
  if (!WebBrowser) {
    const can = await Linking.canOpenURL(checkout).catch(() => true);
    if (!can) {
      throw new Error('cannot_open_checkout_url');
    }
    await Linking.openURL(checkout);
    return { returnUrl: null };
  }

  try {
    if (Platform.OS === 'ios') {
      const result = await WebBrowser.openAuthSessionAsync(
        checkout,
        CUSTOMER_PAYMENT_RETURN_URL,
        {
          preferEphemeralSession: false,
          showTitle: true,
        }
      );
      if (result.type === 'success' && typeof result.url === 'string' && result.url) {
        return { returnUrl: result.url };
      }
      return { returnUrl: null };
    }

    await WebBrowser.warmUpAsync().catch(() => undefined);
    // Resolves with { type: 'opened' } as soon as the Custom Tab is up — do not
    // block booking persistence on payment completion.
    await WebBrowser.openBrowserAsync(checkout, {
      createTask: true,
      showInRecents: true,
      useProxyActivity: true,
      enableDefaultShareMenuItem: false,
      showTitle: true,
    });
    return { returnUrl: null };
  } catch {
    const can = await Linking.canOpenURL(checkout).catch(() => true);
    if (!can) {
      throw new Error('cannot_open_checkout_url');
    }
    await Linking.openURL(checkout);
    return { returnUrl: null };
  }
}
