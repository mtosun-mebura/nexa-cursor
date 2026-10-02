import React, { useState } from 'react';
import { ActivityIndicator, StatusBar, View } from 'react-native';
import { NavigationContainer, DarkTheme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { SafeAreaProvider, SafeAreaView } from 'react-native-safe-area-context';
import { AuthProvider, useAuth } from './src/auth/AuthContext';
import { COLORS } from './src/config';
import { WelcomeScreen } from './src/screens/WelcomeScreen';
import { LoginScreen } from './src/screens/LoginScreen';
import { RoleSelectScreen } from './src/screens/RoleSelectScreen';
import { DriverHomeScreen } from './src/screens/DriverHomeScreen';
import { ContractHomeScreen } from './src/screens/ContractHomeScreen';
import { CustomerHomeScreen } from './src/screens/CustomerHomeScreen';

export type RootStackParamList = {
  Welcome: undefined;
  Login: undefined;
  Customer: undefined;
  RoleSelect: undefined;
  Driver: undefined;
  Contract: undefined;
};

const Stack = createNativeStackNavigator<RootStackParamList>();

const navTheme = {
  ...DarkTheme,
  colors: {
    ...DarkTheme.colors,
    background: COLORS.bg,
    card: COLORS.card,
    text: COLORS.text,
    border: COLORS.border,
    primary: COLORS.primary,
  },
};

function RootNavigator() {
  const { ready, session, activeScreen, capabilities } = useAuth();
  const [guestCustomer, setGuestCustomer] = useState(false);

  if (!ready) {
    return (
      <View style={{ flex: 1, backgroundColor: COLORS.bg, alignItems: 'center', justifyContent: 'center' }}>
        <ActivityIndicator color={COLORS.text} size="large" />
      </View>
    );
  }

  const screens = capabilities?.screens || [];
  const needsRolePick = !!session && !activeScreen && screens.length > 1;
  const showDriver = !!session && activeScreen === 'driver' && !!session.tokens?.driver;
  const showContract = !!session && activeScreen === 'contract' && !!session.tokens?.contract;

  return (
    <NavigationContainer theme={navTheme}>
      <Stack.Navigator screenOptions={{ headerShown: false, animation: 'fade' }}>
        {showDriver ? (
          <Stack.Screen name="Driver" component={DriverHomeScreen} />
        ) : showContract ? (
          <Stack.Screen name="Contract" component={ContractHomeScreen} />
        ) : needsRolePick ? (
          <Stack.Screen name="RoleSelect" component={RoleSelectScreen} />
        ) : guestCustomer ? (
          <Stack.Screen name="Customer">
            {() => <CustomerHomeScreen onBack={() => setGuestCustomer(false)} />}
          </Stack.Screen>
        ) : (
          <>
            <Stack.Screen name="Welcome">
              {({ navigation }) => (
                <WelcomeScreen
                  onLogin={() => navigation.navigate('Login')}
                  onCustomer={() => setGuestCustomer(true)}
                />
              )}
            </Stack.Screen>
            <Stack.Screen name="Login">
              {({ navigation }) => <LoginScreen onBack={() => navigation.goBack()} />}
            </Stack.Screen>
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}

export default function App() {
  return (
    <SafeAreaProvider>
      <StatusBar barStyle="light-content" />
      <AuthProvider>
        <SafeAreaView style={{ flex: 1, backgroundColor: COLORS.bg }}>
          <RootNavigator />
        </SafeAreaView>
      </AuthProvider>
    </SafeAreaProvider>
  );
}
