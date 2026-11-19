/** Tokens uit Exact Pack `tokens/design_tokens.json`. */
export const nexaBookingTheme = {
  color: {
    navy900: '#061329',
    navy800: '#102542',
    navy700: '#142D4E',
    blue: '#1E4C97',
    blueBright: '#0F84FF',
    lightBlue: '#35B6FF',
    orange: '#FF8A00',
    orangeHot: '#FF8A00',
    /** Altijd wit — alleen op donkere vlakken / hero overlays. */
    text: '#FFFFFF',
    textMuted: '#B9CBE4',
    /** Leesbare inkt op light surfaces (kaarten, Datum/Prijs, enz.). */
    ink: '#0A1A34',
    inkMuted: '#36506F',
    border: '#355A86',
    /** Cyan/blauwe gloed-rand (pixel-spec kaarten). */
    glow: '#35B6FF',
    glowBorder: 'rgba(53, 182, 255, 0.55)',
    success: '#22C55E',
    danger: '#EF5B65',
    surface: '#102542',
    surfaceElevated: '#17365C',
    /** Gradient-eindpunten voor glow-kaarten. */
    surfaceGradientEnd: '#142D4E',
    lightInput: '#FFFFFF',
    lightSurface: '#FFFFFF',
    lightBg: '#FFFFFF',
    plateBg: '#F5C518',
    plateText: '#111827',
  },
  radius: { sm: 10, md: 16, card: 26, lg: 24, xl: 32, pill: 999 },
  spacing: { xs: 4, sm: 8, md: 12, lg: 16, xl: 20, xxl: 24 },
  touchTargetMin: 44,
  font: {
    title: 27,
    section: 22,
    body: 15,
    caption: 12,
    button: 18,
  },
} as const;

export type NexaBookingTheme = typeof nexaBookingTheme;

/** Tekstkleur op redesign-oppervlakken: wit in dark, navy in light. */
export function nexaSurfaceText(light: boolean): string {
  return light ? nexaBookingTheme.color.ink : nexaBookingTheme.color.text;
}

export function nexaSurfaceMuted(light: boolean): string {
  return light ? nexaBookingTheme.color.inkMuted : nexaBookingTheme.color.textMuted;
}
