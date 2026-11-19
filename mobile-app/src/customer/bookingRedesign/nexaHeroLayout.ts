import { ImageStyle, ViewStyle } from 'react-native';
import {
  APP_CHROME_HEIGHT,
  APP_CHROME_PAD_H,
  APP_CHROME_SIDE_SLOT,
  appChromeRow,
  appChromeSideRight,
  appChromeSideSlot,
} from '../../ui/appChrome';
import { nexaBookingTheme as theme } from './theme';

/** @deprecated use APP_CHROME_HEIGHT — kept for existing imports */
export const NEXA_HERO_CHROME_HEIGHT = APP_CHROME_HEIGHT;

/** Light + dark hero assets ≈ 1024×769 (waterfront+taxi). */
const HERO_ASPECT_LIGHT = 769 / 1024;
const HERO_ASPECT_DARK = 769 / 1024;

export type NexaHeroVariant = 'home' | 'plan';

export function nexaHeroNaturalHeight(screenWidth: number, light = true): number {
  const aspect = light ? HERO_ASPECT_LIGHT : HERO_ASPECT_DARK;
  return Math.round(screenWidth * aspect);
}

/**
 * Zelfde hero-hoogte Home + Boeken (positie/overloop gelijk).
 */
export function nexaHeroContentHeight(
  screenWidth: number,
  topInset = 0,
  _variant: NexaHeroVariant = 'home'
): number {
  void _variant;
  // Gelijk aan Boeken-copyFloor — Home volgt dezelfde crop/overloop.
  const copyFloor = 128;
  const fadeRoom = 66;
  const base = Math.round(topInset + APP_CHROME_HEIGHT + copyFloor + fadeRoom);

  if (screenWidth >= 900) {
    return Math.round(base * 1.08);
  }
  if (screenWidth >= 700) {
    return Math.round(base * 1.04);
  }
  return base;
}

/**
 * Flush bovenaan. Cover vult de hero; lichte top-crop → taxi lager onder iconen.
 * Onderkant van de foto verdwijnt in de fade (eindigt bij oranje streep-zone).
 */
export function nexaHeroImageStyle(
  screenWidth: number,
  heroHeight: number,
  light = true
): ImageStyle {
  const natural = nexaHeroNaturalHeight(screenWidth, light);
  // Toon genoeg van de scene zonder full-height 769px banner.
  const fillH = Math.max(heroHeight, Math.round(Math.min(natural * 0.55, heroHeight * 1.35)));
  // ~30px omlaag t.o.v. vorige crop (minder top-clip → taxi lager).
  const downBias = Math.max(0, Math.round(fillH * 0.1) - 30);
  return {
    resizeMode: 'cover',
    width: screenWidth,
    height: fillH + downBias + 30,
    marginTop: -downBias,
  };
}

/**
 * Light: witte waas links + stevige onder-fade (plaatje-rand weg bij oranje streep).
 * Dark: navy waas + onder-fade.
 */
export function nexaHeroReadableScrim(light: boolean) {
  const fadeTo = light ? '#FFFFFF' : theme.color.navy900;
  return {
    horizontal: light
      ? ([
          'rgba(255,255,255,0.97)',
          'rgba(255,255,255,0.88)',
          'rgba(255,255,255,0.55)',
          'rgba(255,255,255,0.18)',
          'rgba(255,255,255,0)',
        ] as const)
      : ([
          'rgba(6,19,41,1)',
          'rgba(6,19,41,0.95)',
          'rgba(6,19,41,0.62)',
          'rgba(6,19,41,0.22)',
          'rgba(6,19,41,0)',
        ] as const),
    horizontalLoc: [0, 0.22, 0.42, 0.62, 0.88] as const,
    bottom: light
      ? ([
          'rgba(255,255,255,0)',
          'rgba(255,255,255,0)',
          'rgba(255,255,255,0.35)',
          'rgba(255,255,255,0.85)',
          '#FFFFFF',
        ] as const)
      : ([
          'rgba(6,19,41,0)',
          'rgba(6,19,41,0)',
          'rgba(6,19,41,0.35)',
          'rgba(6,19,41,0.85)',
          fadeTo,
        ] as const),
    // Overloop begint lager op het plaatje (meer foto zichtbaar).
    bottomLoc: [0, 0.58, 0.72, 0.88, 1] as const,
  };
}

export const nexaHeroChromeRow: ViewStyle = {
  ...appChromeRow,
  paddingHorizontal: APP_CHROME_PAD_H,
};

export const nexaHeroSideSlot: ViewStyle = {
  ...appChromeSideSlot,
  minWidth: APP_CHROME_SIDE_SLOT,
};

export const nexaHeroSideRight: ViewStyle = {
  ...appChromeSideRight,
};
