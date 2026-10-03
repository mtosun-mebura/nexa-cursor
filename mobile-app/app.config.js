const appJson = require('./app.json');

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
  };

  return config;
};
