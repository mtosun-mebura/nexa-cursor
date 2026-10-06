import * as Location from 'expo-location';
import * as TaskManager from 'expo-task-manager';
import * as SecureStore from 'expo-secure-store';
import { API_BASE_URL } from '../config';

export const LOCATION_TASK = 'nexa-taxi-driver-location';
const TOKEN_KEY = 'nexa_taxi_location_token';
const VEHICLE_KEY = 'nexa_taxi_location_vehicle';

TaskManager.defineTask(LOCATION_TASK, async ({ data, error }) => {
  if (error) {
    return;
  }
  const locations = (data as { locations?: Location.LocationObject[] })?.locations;
  if (!locations?.length) {
    return;
  }
  const latest = locations[locations.length - 1];
  const token = await SecureStore.getItemAsync(TOKEN_KEY);
  if (!token) {
    return;
  }
  const vehicleRaw = await SecureStore.getItemAsync(VEHICLE_KEY);
  const vehicleId = vehicleRaw ? Number(vehicleRaw) : null;

  try {
    await fetch(`${API_BASE_URL}/api/taxi/v1/driver/availability/location`, {
      method: 'PUT',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        lat: latest.coords.latitude,
        lng: latest.coords.longitude,
        accuracy: latest.coords.accuracy ?? undefined,
        heading: latest.coords.heading ?? undefined,
        speed: latest.coords.speed ?? undefined,
        ...(vehicleId ? { vehicle_id: vehicleId } : {}),
      }),
    });
  } catch {
    /* ignore network errors in background */
  }
});

export async function prepareBackgroundLocation(token: string, vehicleId?: number | null) {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
  if (vehicleId) {
    await SecureStore.setItemAsync(VEHICLE_KEY, String(vehicleId));
  } else {
    await SecureStore.deleteItemAsync(VEHICLE_KEY);
  }
}

export async function startBackgroundLocation(token: string, vehicleId?: number | null) {
  await prepareBackgroundLocation(token, vehicleId);

  const foreground = await Location.requestForegroundPermissionsAsync();
  if (foreground.status !== 'granted') {
    throw new Error('Locatietoestemming is verplicht om online te gaan.');
  }

  const background = await Location.requestBackgroundPermissionsAsync();
  if (background.status !== 'granted') {
    throw new Error(
      'Kies Locatie → Altijd. Marktplaats heeft achtergrondlocatie nodig om klanten te koppelen.'
    );
  }

  const started = await Location.hasStartedLocationUpdatesAsync(LOCATION_TASK);
  if (started) {
    return;
  }

  await Location.startLocationUpdatesAsync(LOCATION_TASK, {
    accuracy: Location.Accuracy.High,
    timeInterval: 8000,
    distanceInterval: 25,
    deferredUpdatesInterval: 8000,
    showsBackgroundLocationIndicator: true,
    pausesUpdatesAutomatically: false,
    activityType: Location.ActivityType.AutomotiveNavigation,
    foregroundService: {
      notificationTitle: 'Nexa Taxi',
      notificationBody: 'Locatie actief zodat klanten je taxi kunnen vinden.',
      notificationColor: '#2563EB',
    },
  });
}

export async function stopBackgroundLocation() {
  const started = await Location.hasStartedLocationUpdatesAsync(LOCATION_TASK);
  if (started) {
    await Location.stopLocationUpdatesAsync(LOCATION_TASK);
  }
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}
