export type PaletteMeta = {
  icon: string
  accent: string
  preview: string
}

const SECTION_META: Record<string, PaletteMeta> = {
  hero: {
    icon: 'ki-picture',
    accent: 'from-violet-500/90 to-indigo-600/90',
    preview: 'Grote banner met titel en knoppen',
  },
  stats: {
    icon: 'ki-chart-line-up',
    accent: 'from-sky-500/90 to-cyan-600/90',
    preview: 'Vier statistieken naast elkaar',
  },
  why_nexa: {
    icon: 'ki-information-2',
    accent: 'from-emerald-500/90 to-teal-600/90',
    preview: 'Introductie met titel en tekst',
  },
  features: {
    icon: 'ki-element-11',
    accent: 'from-amber-500/90 to-orange-600/90',
    preview: 'Grid met kenmerken of diensten',
  },
  cta: {
    icon: 'ki-mouse-square',
    accent: 'from-rose-500/90 to-pink-600/90',
    preview: 'Call-to-action met knoppen',
  },
  carousel: {
    icon: 'ki-slider-horizontal',
    accent: 'from-fuchsia-500/90 to-purple-600/90',
    preview: 'Wisselende slides met afbeeldingen',
  },
  cards_ronde_hoeken: {
    icon: 'ki-grid',
    accent: 'from-lime-500/90 to-green-600/90',
    preview: 'Kaarten met afbeelding en tekst',
  },
  featured_services: {
    icon: 'ki-briefcase',
    accent: 'from-blue-500/90 to-indigo-600/90',
    preview: 'Dienstenblok met animatie',
  },
  email_template: {
    icon: 'ki-sms',
    accent: 'from-slate-500/90 to-zinc-600/90',
    preview: 'Formulier gekoppeld aan e-mailtemplate',
  },
  text_block: {
    icon: 'ki-text',
    accent: 'from-neutral-500/90 to-stone-600/90',
    preview: 'Vrije tekst met optionele sidebar',
  },
}

const COMPONENT_META_BY_KEY: Record<string, PaletteMeta> = {
  'component:taxi.boekingsmodule': {
    icon: 'ki-calendar-tick',
    accent: 'from-amber-500/90 to-orange-600/90',
    preview: 'Taxi boekingsmodule',
  },
  'component:taxiroyaal.boekingsmodule': {
    icon: 'ki-calendar-tick',
    accent: 'from-yellow-500/90 to-amber-600/90',
    preview: 'Taxi Royaal boekingsmodule',
  },
  'component:taxi.tarieven': {
    icon: 'ki-price-tag',
    accent: 'from-teal-500/90 to-cyan-600/90',
    preview: 'Taxi tarieven',
  },
  'component:taxiroyaal.tarieven': {
    icon: 'ki-price-tag',
    accent: 'from-emerald-500/90 to-teal-600/90',
    preview: 'Taxi Royaal tarieven',
  },
  'component:website.google_reviews': {
    icon: 'ki-star',
    accent: 'from-yellow-400/90 to-amber-500/90',
    preview: 'Google reviews',
  },
  'component:nexa.google_reviews': {
    icon: 'ki-star',
    accent: 'from-yellow-400/90 to-amber-500/90',
    preview: 'Google reviews',
  },
  'component:website.nexa_modules_overview': {
    icon: 'ki-element-11',
    accent: 'from-violet-500/90 to-purple-600/90',
    preview: 'Nexa modules overzicht',
  },
  'component:nexa.recente_vacatures': {
    icon: 'ki-briefcase',
    accent: 'from-blue-500/90 to-indigo-600/90',
    preview: 'Recente vacatures',
  },
}

const COMPONENT_META_DEFAULT: PaletteMeta = {
  icon: 'ki-cube-2',
  accent: 'from-slate-500/90 to-zinc-600/90',
  preview: 'Module-component',
}

export function sectionMeta(type: string): PaletteMeta {
  return SECTION_META[type] ?? {
    icon: 'ki-element-plus',
    accent: 'from-muted-foreground/70 to-muted-foreground',
    preview: 'Sectie',
  }
}

export function componentMeta(sectionKey: string): PaletteMeta {
  return COMPONENT_META_BY_KEY[sectionKey] ?? COMPONENT_META_DEFAULT
}

export function baseTypeFromKey(key: string): string {
  if (key.startsWith('component:')) {
    return 'component'
  }
  const match = key.match(/^(.+?)_\d+$/)
  return match ? match[1] : key
}
