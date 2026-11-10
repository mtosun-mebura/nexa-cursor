const fs = require('fs');
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
 * Remote push for lock screen / standby. This Mac signs device installs with an
 * Apple Development certificate, so the matching value is development.
 * App Store archives: EXPO_PUBLIC_IOS_PUSH_ENV=production.
 * No other entitlements — a free Personal Team cannot sign Push.
 * @type {import('expo/config-plugins').ConfigPlugin}
 */
function withIosPushEntitlement(config) {
  return withEntitlementsPlist(config, (config) => {
    config.modResults['aps-environment'] =
      process.env.EXPO_PUBLIC_IOS_PUSH_ENV === 'production' ? 'production' : 'development';
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
    apiBaseUrl: 'https://nexasuite.online',
    // getExpoPushTokenAsync needs extra.eas.projectId (or EXPO_PUBLIC_EAS_PROJECT_ID).
    // Android also needs google-services.json plus an FCM v1 service account on the Expo project.
    // TODO (after eas init): set EXPO_PUBLIC_EAS_PROJECT_ID, or let `eas init` write projectId —
    // do not invent a fake UUID.
  };

  const easProjectId = (process.env.EXPO_PUBLIC_EAS_PROJECT_ID || '').trim();
  if (easProjectId) {
    config.extra.eas = {
      ...(config.extra.eas || {}),
      projectId: easProjectId,
    };
  }

  // Absent file must not break prebuild. google-services.json is the Firebase client
  // file (typically committed); the FCM service-account key stays out of git.
  if (fs.existsSync(path.join(__dirname, 'google-services.json'))) {
    config.android = {
      ...(config.android || {}),
      googleServicesFile: './google-services.json',
    };
  }

  config.plugins = [...(config.plugins || []), withAlternateIconDeepLinks, withIosPushEntitlement];

  return config;
};
