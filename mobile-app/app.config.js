const path = require('path');
const { AndroidConfig, withEntitlementsPlist, withFinalizedMod } = require('@expo/config-plugins');
const appJson = require('./app.json');

/**
 * Alternate launcher icons disable MainActivity. Copy its non-launcher
 * intent filters (the nexataxi:// scheme) onto those aliases after prebuild.
 * @type {import('expo/config-plugins').ConfigPlugin}
 */
function withAlternateIconDeepLinks(config) {
  return withFinalizedMod(config, [
    'android',
    async (mod) => {
      const manifestPath = path.join(
        mod.modRequest.platformProjectRoot,
        'app/src/main/AndroidManifest.xml'
      );
      const manifest = await AndroidConfig.Manifest.readAndroidManifestAsync(manifestPath);
      const application = manifest.manifest.application?.[0];
      const mainActivity = application?.activity?.find(
        (activity) => activity.$['android:name'] === '.MainActivity'
      );
      const aliases = application?.['activity-alias'];
      if (!mainActivity || !aliases?.length) return mod;

      const sharedFilters = (mainActivity['intent-filter'] ?? []).filter((filter) =>
        (filter.action ?? []).every(
          (action) => action.$['android:name'] !== 'android.intent.action.MAIN'
        )
      );
      if (!sharedFilters.length) return mod;

      let changed = false;
      for (const alias of aliases) {
        const name = alias.$['android:name'] ?? '';
        if (!name.startsWith('.MainActivity')) continue;
        const filters = alias['intent-filter'] ?? [];
        const hasView = filters.some((filter) =>
          (filter.action ?? []).some(
            (action) => action.$['android:name'] === 'android.intent.action.VIEW'
          )
        );
        if (!hasView) {
          alias['intent-filter'] = filters.concat(sharedFilters);
          changed = true;
        }
      }
      if (changed) {
        await AndroidConfig.Manifest.writeAndroidManifestAsync(manifestPath, manifest);
      }
      return mod;
    },
  ]);
}

/**
 * Personal Apple Development teams cannot sign Push Notifications.
 * expo-notifications always writes aps-environment; drop it after that plugin.
 *
 * One-line toggle once a paid Apple Developer Program membership and an APNs
 * key exist: EXPO_PUBLIC_IOS_PUSH=1 (optional EXPO_PUBLIC_IOS_PUSH_ENV=development).
 * Leave it unset on this Mac. Re-adding aps-environment without that membership
 * breaks signing.
 * @type {import('expo/config-plugins').ConfigPlugin}
 */
function withoutPushEntitlement(config) {
  return withEntitlementsPlist(config, (config) => {
    if (process.env.EXPO_PUBLIC_IOS_PUSH === '1') {
      config.modResults['aps-environment'] =
        process.env.EXPO_PUBLIC_IOS_PUSH_ENV === 'development' ? 'development' : 'production';
      return config;
    }
    delete config.modResults['aps-environment'];
    return config;
  });
}

/** @type {import('expo/config').ExpoConfig} */
module.exports = () => {
  const mapsKey = process.env.EXPO_PUBLIC_GOOGLE_MAPS_API_KEY || '';
  const config = { ...appJson.expo };

  config.plugins = (config.plugins || []).map((plugin) => {
    if (plugin === 'react-native-maps' && mapsKey) {
      return [
        'react-native-maps',
        {
          iosGoogleMapsApiKey: mapsKey,
          androidGoogleMapsApiKey: mapsKey,
        },
      ];
    }
    return plugin;
  });

  config.extra = {
    ...(config.extra || {}),
    apiBaseUrl:
      process.env.EXPO_PUBLIC_API_BASE_URL || config.extra?.apiBaseUrl || 'https://nexasuite.online',
    // getExpoPushTokenAsync needs extra.eas.projectId (or EXPO_PUBLIC_EAS_PROJECT_ID).
    // Android also needs google-services.json plus an FCM v1 service account on the Expo project.
    // Do not set a project id here until `eas init` has created one.
  };

  config.plugins = [withoutPushEntitlement, ...(config.plugins || []), withAlternateIconDeepLinks];

  return config;
};
