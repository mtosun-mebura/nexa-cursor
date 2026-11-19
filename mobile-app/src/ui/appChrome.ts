import type { ViewStyle } from 'react-native';

/**
 * Canonieke header-chrome (logo + appearance) — Home, Boeken, Ritten,
 * Meldingen, Profiel, chauffeur, contract.
 */
export const APP_CHROME_HEIGHT = 48;
export const APP_CHROME_SIDE_SLOT = 88;
export const APP_CHROME_PAD_H = 12;

export const appChromeRow: ViewStyle = {
  flexDirection: 'row',
  alignItems: 'center',
  justifyContent: 'space-between',
  minHeight: APP_CHROME_HEIGHT,
  height: APP_CHROME_HEIGHT,
  paddingHorizontal: APP_CHROME_PAD_H,
  zIndex: 2,
};

export const appChromeSideSlot: ViewStyle = {
  minWidth: APP_CHROME_SIDE_SLOT,
  height: APP_CHROME_HEIGHT,
  justifyContent: 'center',
};

export const appChromeSideRight: ViewStyle = {
  alignItems: 'flex-end',
};
