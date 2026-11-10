import type { CapacitorConfig } from '@capacitor/cli';

// Eén native shell (iOS + Android) voor klant, chauffeur én contract.
// Laravel rendert de juiste PWA; de launcher onthoudt de rol.
//
// Zet de omgeving die je test in NEXA_APP_URL, bijvoorbeeld:
//   NEXA_APP_URL=http://192.168.178.116:8085/taxi/app npx cap sync
const serverUrl = process.env.NEXA_APP_URL || 'https://nexasuite.online/taxi/app';

const config: CapacitorConfig = {
    appId: 'nl.nexasuite.taxi',
    appName: 'Nexa Taxi',
    webDir: 'www',
    server: {
        url: serverUrl,
        cleartext: serverUrl.startsWith('http://'),
    },
    plugins: {
        SplashScreen: {
            launchAutoHide: true,
        },
    },
};

export default config;
