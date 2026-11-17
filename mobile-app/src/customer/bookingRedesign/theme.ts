/** Tokens uit assets/tokens/design-tokens.json (pixel-spec). */
export const nexaBookingTheme = {
  color: {
    navy900: '#061329',
    navy800: '#102542',
    navy700: '#152E50',
    blue: '#1E4C97',
    blueBright: '#087DFF',
    lightBlue: '#8EBBFF',
    orange: '#F57C00',
    orangeHot: '#FF8900',
    text: '#FFFFFF',
    textMuted: '#B7C7DF',
    border: '#355A86',
    /** Cyan/blauwe gloed-rand (pixel-spec kaarten). */
    glow: '#4DB2FF',
    glowBorder: 'rgba(110, 190, 255, 0.55)',
    success: '#19BA83',
    danger: '#EF5B65',
    surface: '#102542',
    surfaceElevated: '#152E50',
    /** Gradient-eindpunten voor glow-kaarten. */
    surfaceGradientEnd: '#143A6B',
    lightInput: '#EAF2FF',
    plateBg: '#F5C518',
    plateText: '#111827',
  },
  radius: { sm: 10, md: 16, card: 22, lg: 24, xl: 32, pill: 999 },
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
