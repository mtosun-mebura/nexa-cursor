export type PromoVideoProps = {
  companyName: string;
  productName: string;
  tagline: string;
  subtitle: string;
  primaryColor: string;
  accentColor: string;
  backgroundColor: string;
  ctaText: string;
  websiteUrl: string;
  logoUrl: string;
  showAppScreens: boolean;
};

export const COMPOSITION_IDS = {
  productPromo: 'NexaProductPromo',
  appTeaser: 'NexaAppTeaser',
} as const;

export type CompositionId =
  (typeof COMPOSITION_IDS)[keyof typeof COMPOSITION_IDS];
