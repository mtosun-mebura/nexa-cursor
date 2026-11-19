/**
 * Exact Execution Pack assets (app-design-map/exact-pack + assets/nexa-booking/exact).
 * Pure achtergronden — geen screenshot-slices met gebakken tekst/iconen.
 */

/** Zelfde waterfront+taxi headers voor Home én Boeken (light/dark). */
export const exactHeroHome = {
  dark: require('../../../assets/nexa-booking/exact/hero/dark/home_hero_with_car_dark.jpg'),
  light: require('../../../assets/nexa-booking/exact/hero/light/home_hero_with_car_light.jpg'),
} as const;

export const exactHeroPlan = {
  dark: require('../../../assets/nexa-booking/exact/hero/dark/taxi_scene_with_car_dark.jpg'),
  light: require('../../../assets/nexa-booking/exact/hero/light/taxi_scene_with_car_light.jpg'),
} as const;

export const exactWaterfront = {
  dark: require('../../../assets/nexa-booking/exact/hero/dark/waterfront_no_car_dark.png'),
  light: require('../../../assets/nexa-booking/exact/hero/light/waterfront_no_car_light.png'),
} as const;

export const exactAppBackground = {
  dark: require('../../../assets/nexa-booking/exact/bg/dark/app_background_dark.png'),
  light: require('../../../assets/nexa-booking/exact/bg/light/app_background_light.png'),
} as const;

/** Light glow-card fill (witte wash) — NexaGlowCard. */
export const exactCardBaseLight = require('../../../assets/nexa-booking/exact/bg/light/card_base_light.png');

export const exactTopOverlay = {
  dark: require('../../../assets/nexa-booking/exact/bg/dark/top_overlay_dark.png'),
  light: require('../../../assets/nexa-booking/exact/bg/light/top_overlay_light.png'),
} as const;

/** Chauffeurkaart: pure cityscape (user ChatGPT + pack). */
export const exactDriverCardBg = {
  dark: require('../../../assets/nexa-booking/exact/cards/dark/driver_card_bg_dark.jpg'),
  light: require('../../../assets/nexa-booking/exact/cards/light/driver_card_bg_light.jpg'),
} as const;

export const exactMapCarMarker = {
  dark: require('../../../assets/nexa-booking/exact/markers/map_car_marker_dark.png'),
  light: require('../../../assets/nexa-booking/exact/markers/map_car_marker_light.png'),
  /** Gele NEXA-marker (auto alleen, geen cirkel). */
  neon: require('../../../assets/nexa-booking/exact/markers/nexa-map-car-marker-neon.png'),
} as const;

/** Decoratieve route/map-strip achter logo-header (overloop naar hero). */
export const exactRoutePreview = {
  dark: require('../../../assets/nexa-booking/exact/maps/dark/route_preview_dark.png'),
  light: require('../../../assets/nexa-booking/exact/maps/light/route_preview_light.png'),
} as const;

export const exactOphaalcodePopupBg = {
  dark: require('../../../assets/nexa-booking/exact/ophaalcode/popup_bg_dark.png'),
  light: require('../../../assets/nexa-booking/exact/ophaalcode/popup_bg_light.png'),
} as const;
export const exactOphaalcodeShield = require('../../../assets/nexa-booking/exact/ophaalcode/shield-check.png');



export function exactModeAsset<T extends { dark: unknown; light: unknown }>(
  set: T,
  light: boolean
): T['dark'] | T['light'] {
  return light ? set.light : set.dark;
}
