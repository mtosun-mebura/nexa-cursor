import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { useEffect, useMemo, useState } from 'react';
import { activateKeepAwakeAsync, deactivateKeepAwake } from 'expo-keep-awake';
import {
  ActivityIndicator,
  AppState,
  Linking,
  Platform,
  StatusBar,
  Text,
  View,
  type AppStateStatus,
} from 'react-native';
import { NavigationContainer, DarkTheme, DefaultTheme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { SafeAreaProvider, SafeAreaView } from 'react-native-safe-area-context';
import { AuthProvider, resolveAppScreen, useAuth } from './src/auth/AuthContext';
import { hydrateApiBase } from './src/config';
import { DRIVER_BG } from './src/debug/driverBgKillSwitch';
import { DemoProvider, useDemo } from './src/demo/DemoContext';
import { prepareDriverNotificationChannels } from './src/notifications/driverOffers';
import { parseAppDeepLink } from './src/linking';
import { LocaleProvider } from './src/i18n';
import { ThemeProvider, useTheme } from './src/theme/ThemeContext';
import { ModalBlurHost } from './src/ui/ModalBlurHost';
import { WelcomeScreen } from './src/screens/WelcomeScreen';
import { LoginScreen } from './src/screens/LoginScreen';
import { MarketplaceRegisterScreen } from './src/screens/MarketplaceRegisterScreen';

/** Zware schermen (GPS/maps) pas laden als ze nodig zijn — anders blijft splash #0B1220 hangen. */
const DriverHomeScreen = React.lazy(() =>
  import('./src/screens/DriverHomeScreen').then((m) => ({ default: m.DriverHomeScreen }))
);
const ContractHomeScreen = React.lazy(() =>
  import('./src/screens/ContractHomeScreen').then((m) => ({ default: m.ContractHomeScreen }))
);
const CustomerHomeScreen = React.lazy(() =>
  import('./src/screens/CustomerHomeScreen').then((m) => ({ default: m.CustomerHomeScreen }))
);

export type RootStackParamList = {
  Welcome: undefined;
  Login: undefined;
  MarketplaceRegister: undefined;
  Customer: undefined;
  Driver: undefined;
  Contract: undefined;
};

const GUEST_ROUTE_KEY = 'nexa_taxi_guest_route';
type GuestRoute = 'welcome' | 'login' | 'customer' | 'marketplace';

/** iOS idleTimerDisabled + Android FLAG_KEEP_SCREEN_ON via expo-keep-awake. */
const FOREGROUND_KEEP_AWAKE_TAG = 'nexa-app-foreground';

/**
 * Houd het scherm aan zolang de app actief op de voorgrond staat.
 * Bij inactive/background (slotknop, Home, app-wisselaar) de lock loslaten
 * zodat het toestel normaal kan slapen. De gebruiker kan altijd zelf weg.
 */
function KeepScreenAwake() {
  useEffect(() => {
    if (!DRIVER_BG.keepAwake) return;
    const apply = (state: AppStateStatus) => {
      if (state === 'active') {
        void activateKeepAwakeAsync(FOREGROUND_KEEP_AWAKE_TAG).catch(() => undefined);
        return;
      }
      if (state === 'inactive' || state === 'background') {
        void deactivateKeepAwake(FOREGROUND_KEEP_AWAKE_TAG).catch(() => undefined);
      }
    };
    apply(AppState.currentState);
    const sub = AppState.addEventListener('change', apply);
    return () => {
      sub.remove();
      void deactivateKeepAwake(FOREGROUND_KEEP_AWAKE_TAG).catch(() => undefined);
    };
  }, []);
  return null;
}

const Stack = createNativeStackNavigator<RootStackParamList>();

function RootNavigator() {
  const { ready, session, activeScreen, capabilities } = useAuth();
  const { colors, colorScheme } = useTheme();
  const demo = useDemo();
  const [guestRoute, setGuestRoute] = useState<GuestRoute | null>(null);
  const hadSessionRef = React.useRef(false);

  useEffect(() => {
    void prepareDriverNotificationChannels();
  }, []);

  const persistGuestRoute = (route: GuestRoute) => {
    setGuestRoute(route);
    AsyncStorage.setItem(GUEST_ROUTE_KEY, route).catch(() => undefined);
  };

  useEffect(() => {
    let cancelled = false;
    const failsafe = setTimeout(() => {
      if (!cancelled) setGuestRoute((current) => current ?? 'welcome');
    }, 2500);
    (async () => {
      try {
        const initialUrl = await Linking.getInitialURL();
        if (!cancelled && parseAppDeepLink(initialUrl)?.host === 'customer') {
          setGuestRoute('customer');
          AsyncStorage.setItem(GUEST_ROUTE_KEY, 'customer').catch(() => undefined);
          return;
        }
        const saved = await AsyncStorage.getItem(GUEST_ROUTE_KEY);
        if (!cancelled) {
          setGuestRoute(
            saved === 'customer' || saved === 'login' || saved === 'marketplace' ? saved : 'welcome'
          );
        }
      } catch {
        if (!cancelled) setGuestRoute('welcome');
      } finally {
        clearTimeout(failsafe);
      }
    })();

    const sub = Linking.addEventListener('url', ({ url }) => {
      if (parseAppDeepLink(url)?.host === 'customer') {
        persistGuestRoute('customer');
      }
    });

    return () => {
      cancelled = true;
      clearTimeout(failsafe);
      sub.remove();
    };
  }, []);

  // Na sessie-einde: verlopen/forceReLogin → login; bewust uitloggen → startpagina.
  useEffect(() => {
    if (!ready) return;
    if (session) {
      hadSessionRef.current = true;
      return;
    }
    let cancelled = false;
    (async () => {
      const saved = await AsyncStorage.getItem(GUEST_ROUTE_KEY);
      if (cancelled) return;
      if (saved === 'login') {
        hadSessionRef.current = false;
        persistGuestRoute('login');
        return;
      }
      if (hadSessionRef.current) {
        hadSessionRef.current = false;
        persistGuestRoute('welcome');
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [ready, session]);

  const navTheme = useMemo(() => {
    const base = colorScheme === 'light' ? DefaultTheme : DarkTheme;
    return {
      ...base,
      colors: {
        ...base.colors,
        background: colors.bg,
        card: colors.card,
        text: colors.text,
        border: colors.border,
        primary: colors.primary,
      },
    };
  }, [colorScheme, colors]);

  if (!ready || guestRoute === null) {
    return (
      <View style={{ flex: 1, backgroundColor: colors.bg || '#0B1220', alignItems: 'center', justifyContent: 'center' }}>
        <ActivityIndicator color={colors.text || '#FFFFFF'} size="large" />
      </View>
    );
  }

  const screenKey = resolveAppScreen(session, activeScreen || capabilities?.default_screen);
  const showDriver = screenKey === 'driver';
  const showContract = screenKey === 'contract';

  // Superadmin-demo: Boeken openen vanuit het chauffeurs-/contractprofiel.
  const demoBooking = demo.bookingOpen && (showDriver || showContract);

  const initialRouteName: keyof RootStackParamList = demoBooking
    ? 'Customer'
    : showDriver
    ? 'Driver'
    : showContract
      ? 'Contract'
      : guestRoute === 'customer'
          ? 'Customer'
          : guestRoute === 'login'
            ? 'Login'
            : guestRoute === 'marketplace'
              ? 'MarketplaceRegister'
              : 'Welcome';

  const bootFallback = (
    <View style={{ flex: 1, backgroundColor: colors.bg || '#0B1220', alignItems: 'center', justifyContent: 'center' }}>
      <ActivityIndicator color={colors.text || '#FFFFFF'} size="large" />
    </View>
  );

  return (
    <NavigationContainer theme={navTheme}>
      <React.Suspense fallback={bootFallback}>
      <Stack.Navigator
        key={initialRouteName}
        initialRouteName={initialRouteName}
        screenOptions={{
          headerShown: false,
          animation: 'slide_from_right',
          freezeOnBlur: true,
          contentStyle: { flex: 1, backgroundColor: colors.bg },
        }}
      >
        {demoBooking ? (
          <Stack.Screen name="Customer">
            {() => <CustomerHomeScreen onBack={() => demo.setBookingOpen(false)} />}
          </Stack.Screen>
        ) : showDriver ? (
          <Stack.Screen name="Driver" component={DriverHomeScreen} />
        ) : showContract ? (
          <Stack.Screen name="Contract" component={ContractHomeScreen} />
        ) : guestRoute === 'customer' ? (
          <Stack.Screen name="Customer">
            {() => <CustomerHomeScreen onBack={() => persistGuestRoute('welcome')} />}
          </Stack.Screen>
        ) : (
          <>
            <Stack.Screen name="Welcome">
              {({ navigation }) => (
                <WelcomeScreen
                  onLogin={() => {
                    persistGuestRoute('login');
                    navigation.navigate('Login');
                  }}
                  onCustomer={() => {
                    AsyncStorage.setItem('nexa_taxi_customer_tab', 'book').catch(() => undefined);
                    persistGuestRoute('customer');
                  }}
                  onMarketplaceRegister={() => {
                    persistGuestRoute('marketplace');
                    navigation.navigate('MarketplaceRegister');
                  }}
                />
              )}
            </Stack.Screen>
            <Stack.Screen name="Login">
              {({ navigation }) => (
                <LoginScreen
                  onBack={() => {
                    persistGuestRoute('welcome');
                    if (navigation.canGoBack()) navigation.goBack();
                    else navigation.navigate('Welcome');
                  }}
                />
              )}
            </Stack.Screen>
            <Stack.Screen name="MarketplaceRegister">
              {({ navigation }) => (
                <MarketplaceRegisterScreen
                  onBack={() => {
                    persistGuestRoute('welcome');
                    if (navigation.canGoBack()) navigation.goBack();
                    else navigation.navigate('Welcome');
                  }}
                />
              )}
            </Stack.Screen>
          </>
        )}
      </Stack.Navigator>
      </React.Suspense>
    </NavigationContainer>
  );
}

function AppShell() {
  const { colors, colorScheme } = useTheme();
  return (
    <View style={{ flex: 1, backgroundColor: colors.bg }}>
      <StatusBar
        barStyle={colorScheme === 'light' ? 'dark-content' : 'light-content'}
        translucent={Platform.OS === 'android'}
        backgroundColor={Platform.OS === 'android' ? 'transparent' : undefined}
      />
      <AuthProvider>
        <DemoProvider>
          <ModalBlurHost>
            <SafeAreaView style={{ flex: 1, backgroundColor: colors.bg }}>
              <RootNavigator />
            </SafeAreaView>
          </ModalBlurHost>
        </DemoProvider>
      </AuthProvider>
    </View>
  );
}

/** Vangt render-fouten op zodat de app niet hard crasht tijdens idle online. */
class AppErrorBoundary extends React.Component<
  { children: React.ReactNode },
  { failed: boolean }
> {
  state = { failed: false };

  static getDerivedStateFromError() {
    return { failed: true };
  }

  componentDidCatch() {
    /* gelogd door native; UI blijft bruikbaar */
  }

  render() {
    if (this.state.failed) {
      return (
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#0B1220', padding: 24 }}>
          <Text style={{ color: '#FFFFFF', fontSize: 16, textAlign: 'center', marginBottom: 16 }}>
            Er ging iets mis. Heropen het scherm om door te gaan.
          </Text>
          <Text
            onPress={() => this.setState({ failed: false })}
            style={{ color: '#38BDF8', fontSize: 16, fontWeight: '600' }}
          >
            Opnieuw proberen
          </Text>
        </View>
      );
    }
    return this.props.children;
  }
}

export default function App() {
  useEffect(() => {
    // De API-host staat al vast. Niet op AsyncStorage wachten: dat liet het
    // scherm op het lege #0B1220-vlak staan als de eerste storage-call hing.
    void hydrateApiBase();
  }, []);

  useEffect(() => {
    // Android: herstel default MainActivity als Light/Dark-alias de launcher brak.
    if (Platform.OS !== 'android') return;
    try {
      // eslint-disable-next-line @typescript-eslint/no-require-imports
      const { syncAlternateAppIcon } = require('./src/theme/alternateAppIcon') as typeof import('./src/theme/alternateAppIcon');
      syncAlternateAppIcon('system', null, { force: true });
    } catch {
      /* ignore */
    }
  }, []);

  useEffect(() => {
    // Voorkom dat niet-fatale JS-fouten de hele app killen.
    const g = globalThis as {
      ErrorUtils?: {
        getGlobalHandler?: () => ((error: Error, isFatal?: boolean) => void) | undefined;
        setGlobalHandler?: (handler: (error: Error, isFatal?: boolean) => void) => void;
      };
    };
    const utils = g.ErrorUtils;
    if (!utils?.getGlobalHandler || !utils.setGlobalHandler) return;
    const previous = utils.getGlobalHandler();
    utils.setGlobalHandler((error, isFatal) => {
      if (!isFatal) return;
      previous?.(error, isFatal);
    });
    return () => {
      if (previous) utils.setGlobalHandler?.(previous);
    };
  }, []);

  return (
    <SafeAreaProvider style={{ flex: 1, backgroundColor: '#0B1220' }}>
      <AppErrorBoundary>
        <KeepScreenAwake />
        <ThemeProvider>
          <LocaleProvider>
            <AppShell />
          </LocaleProvider>
        </ThemeProvider>
      </AppErrorBoundary>
    </SafeAreaProvider>
  );
}
