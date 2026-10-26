import AsyncStorage from '@react-native-async-storage/async-storage';
import { useKeepAwake } from 'expo-keep-awake';
import React, { useEffect, useMemo, useState } from 'react';
import { NavigationBar } from 'expo-navigation-bar';
import { ActivityIndicator, Linking, Platform, StatusBar, View } from 'react-native';
import { NavigationContainer, DarkTheme, DefaultTheme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { SafeAreaProvider, SafeAreaView } from 'react-native-safe-area-context';
import { AuthProvider, resolveAppScreen, useAuth } from './src/auth/AuthContext';
import { prepareDriverNotificationChannels } from './src/notifications/driverOffers';
import { parseAppDeepLink } from './src/linking';
import { LocaleProvider } from './src/i18n';
import { ThemeProvider, useTheme } from './src/theme/ThemeContext';
import { WelcomeScreen } from './src/screens/WelcomeScreen';
import { LoginScreen } from './src/screens/LoginScreen';
import { MarketplaceRegisterScreen } from './src/screens/MarketplaceRegisterScreen';
import { DriverHomeScreen } from './src/screens/DriverHomeScreen';
import { ContractHomeScreen } from './src/screens/ContractHomeScreen';
import { CustomerHomeScreen } from './src/screens/CustomerHomeScreen';

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

const Stack = createNativeStackNavigator<RootStackParamList>();

function RootNavigator() {
  const { ready, session, activeScreen, capabilities } = useAuth();
  const { colors, colorScheme } = useTheme();
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
      }
    })();

    const sub = Linking.addEventListener('url', ({ url }) => {
      if (parseAppDeepLink(url)?.host === 'customer') {
        persistGuestRoute('customer');
      }
    });

    return () => {
      cancelled = true;
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
      <View style={{ flex: 1, backgroundColor: colors.bg, alignItems: 'center', justifyContent: 'center' }}>
        <ActivityIndicator color={colors.text} size="large" />
      </View>
    );
  }

  const screenKey = resolveAppScreen(session, activeScreen || capabilities?.default_screen);
  const showDriver = screenKey === 'driver';
  const showContract = screenKey === 'contract';

  const initialRouteName: keyof RootStackParamList = showDriver
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

  return (
    <NavigationContainer theme={navTheme}>
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
        {showDriver ? (
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
    </NavigationContainer>
  );
}

function AppShell() {
  useKeepAwake();
  const { colors, colorScheme } = useTheme();
  return (
    <>
      <StatusBar barStyle={colorScheme === 'light' ? 'dark-content' : 'light-content'} />
      {Platform.OS === 'android' ? (
        <NavigationBar style={colorScheme === 'light' ? 'dark' : 'light'} />
      ) : null}
      <AuthProvider>
        <SafeAreaView style={{ flex: 1, backgroundColor: colors.bg }}>
          <RootNavigator />
        </SafeAreaView>
      </AuthProvider>
    </>
  );
}

export default function App() {
  return (
    <SafeAreaProvider>
      <ThemeProvider>
        <LocaleProvider>
          <AppShell />
        </LocaleProvider>
      </ThemeProvider>
    </SafeAreaProvider>
  );
}
