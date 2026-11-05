import { registerRootComponent } from 'expo';

// Achtergrond-push → ritgeluid: TaskManager.defineTask moet vóór App laden.
import {
  registerDriverOfferBackgroundTask,
} from './src/notifications/driverOfferBackgroundTask';

import App from './App';

registerDriverOfferBackgroundTask();

// registerRootComponent calls AppRegistry.registerComponent('main', () => App);
// It also ensures that whether you load the app in Expo Go or in a native build,
// the environment is set up appropriately
registerRootComponent(App);
