import type {PromoVideoProps} from './types';

export const DEFAULT_PROMO_PROPS: PromoVideoProps = {
  companyName: 'NEXA Suite',
  productName: 'NexaTaxi',
  tagline: 'Online boeken, dispatch en chauffeur-app in één platform',
  subtitle: 'White-label taxi SaaS voor ZZP tot vloot',
  primaryColor: '#2563eb',
  accentColor: '#f97316',
  backgroundColor: '#0b1220',
  ctaText: 'Start met NexaTaxi',
  websiteUrl: 'nexasuite.nl',
  logoUrl: 'images/nexa-taxi-logo.png',
  showAppScreens: true,
};

export const PRODUCT_PROMO = {
  id: 'NexaProductPromo',
  fps: 30,
  width: 1920,
  height: 1080,
  durationInFrames: 1350, // 45s
} as const;

export const APP_TEASER = {
  id: 'NexaAppTeaser',
  fps: 30,
  width: 1080,
  height: 1920,
  durationInFrames: 450, // 15s vertical
} as const;

export const FEATURES = [
  {
    title: 'Website-boeking',
    body: 'Klanten boeken zelf — minder telefoonchaos',
    image: 'images/feature-taxi-booking.png',
  },
  {
    title: 'Dispatch & ritten',
    body: 'Live overzicht van ritten, chauffeurs en status',
    image: 'images/feature-contract-planning.png',
  },
  {
    title: 'Chauffeur-app',
    body: 'Navigatie, inbox en actieve rit op de telefoon',
    image: 'images/feature-chauffeur-app.png',
  },
  {
    title: 'Contractvervoer',
    body: 'School, zorg en zakelijk: planning + ouderportaal',
    image: 'images/feature-contract-portal.png',
  },
] as const;
