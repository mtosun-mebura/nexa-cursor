import { registerRootComponent } from 'expo';
import { LogBox } from 'react-native';

// Achtergrond-push → ritgeluid: TaskManager.defineTask moet vóór App laden.
import {
  registerDriverOfferBackgroundTask,
} from './src/notifications/driverOfferBackgroundTask';

import App from './App';

// Dev-client HMR/WebSocket: native emit vóór JS-listener → gele LogBox-spam.
// Keychain op slot: legacy WHEN_UNLOCKED (nu afgevangen in secureStoreSafe).
LogBox.ignoreLogs([
  'Sending `websocketMessage` with no listeners registered.',
  "Deep imports from the 'react-native' package are deprecated",
  'setValueWithKeyAsync',
  'KeyChainException',
  'User interaction is not allowed',
]);

registerDriverOfferBackgroundTask();

// registerRootComponent calls AppRegistry.registerComponent('main', () => App);
// It also ensures that whether you load the app in Expo Go or in a native build,
// the environment is set up appropriately
registerRootComponent(App);
