import type { CapacitorConfig } from '@capacitor/cli';

// De chauffeur-app wordt door Laravel gerenderd, dus het omhulsel laadt die pagina
// in plaats van een meegeleverde bundel. Zo blijft er één codebase en hoeft er voor
// een wijziging in de app geen nieuwe build naar de App Store.
//
// Zet de omgeving die je test in NEXA_APP_URL, bijvoorbeeld:
//   NEXA_APP_URL=http://192.168.178.116:8085/taxi/chauffeur npx cap sync ios
const serverUrl = process.env.NEXA_APP_URL || 'https://nexasuite.online/taxi/chauffeur';

const config: CapacitorConfig = {
    appId: 'nl.nexasuite.chauffeur',
    appName: 'Nexa Chauffeur',
    // Alleen de terugvalpagina voor als de server onbereikbaar is.
    webDir: 'www',
    server: {
        url: serverUrl,
        // Zonder TLS weigert iOS de verbinding; alleen voor een lokale testserver.
        cleartext: serverUrl.startsWith('http://'),
    },
};

export default config;
