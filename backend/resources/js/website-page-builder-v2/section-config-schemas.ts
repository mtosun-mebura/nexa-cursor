export type SelectOption = { value: string; label: string }

export type ConfigField =
  | { type: 'text'; key: string; label: string; placeholder?: string }
  | { type: 'textarea'; key: string; label: string; rows?: number; placeholder?: string; mono?: boolean }
  | { type: 'select'; key: string; label: string; options: SelectOption[] }
  | { type: 'number'; key: string; label: string; min?: number; max?: number; step?: number; hint?: string }
  | { type: 'color'; key: string; label: string; hint?: string }
  | { type: 'image'; key: string; label: string; hint?: string }
  | { type: 'checkbox'; key: string; label: string }
  | { type: 'star-rating'; key: string; label: string; min?: number; max?: number; hint?: string }
  | { type: 'step-order'; key: string; label: string; options: SelectOption[] }
  | { type: 'group'; label: string; fields: ConfigField[] }
  | {
      type: 'item-list'
      key: string
      label: string
      minItems?: number
      maxItems?: number
      itemLabel?: string
      fields: ConfigField[]
    }

const alignmentOptions: SelectOption[] = [
  { value: 'left', label: 'Links' },
  { value: 'center', label: 'Gecentreerd' },
  { value: 'right', label: 'Rechts' },
]

const ctaButtonFields = (prefix: 'cta_primary' | 'cta_secondary', label: string): ConfigField[] => [
  { type: 'group', label, fields: [
    { type: 'text', key: `${prefix}_text`, label: 'Tekst' },
    { type: 'text', key: `${prefix}_url`, label: 'URL' },
    { type: 'color', key: `${prefix}_bg`, label: 'Achtergrondkleur' },
    { type: 'color', key: `${prefix}_text_color`, label: 'Tekstkleur' },
    { type: 'color', key: `${prefix}_border`, label: 'Borderkleur' },
  ]},
]

export const SECTION_CONFIG_SCHEMAS: Record<string, ConfigField[]> = {
  hero: [
    { type: 'text', key: 'title', label: 'Titel' },
    { type: 'text', key: 'title_highlight', label: 'Highlight woord' },
    { type: 'color', key: 'title_highlight_color', label: 'Highlight kleur' },
    { type: 'textarea', key: 'subtitle', label: 'Ondertitel', rows: 4 },
    { type: 'color', key: 'subtitle_color', label: 'Ondertitel kleur' },
    { type: 'image', key: 'background_image_url', label: 'Achtergrond banner', hint: 'Atom-v2 / modern thema' },
    { type: 'image', key: 'author_image_url', label: 'Ronde foto in banner', hint: 'Atom-v2 thema' },
    { type: 'group', label: 'Gradient overloop', fields: [
      { type: 'color', key: 'overlay_color_from', label: 'Kleur van' },
      { type: 'color', key: 'overlay_color_to', label: 'Kleur naar' },
      { type: 'number', key: 'overlay_opacity', label: 'Helderheid overloop (%)', min: 0, max: 100, step: 1 },
    ]},
    ...ctaButtonFields('cta_primary', 'Knop 1'),
    ...ctaButtonFields('cta_secondary', 'Knop 2'),
  ],
  cta: [
    { type: 'text', key: 'title', label: 'Titel' },
    { type: 'textarea', key: 'subtitle', label: 'Ondertitel', rows: 3 },
    { type: 'color', key: 'subtitle_color', label: 'Ondertitel kleur' },
    ...ctaButtonFields('cta_primary', 'Knop 1'),
    ...ctaButtonFields('cta_secondary', 'Knop 2'),
  ],
  why_nexa: [
    { type: 'text', key: 'title', label: 'Titel' },
    { type: 'textarea', key: 'subtitle', label: 'Tekst', rows: 4 },
    { type: 'color', key: 'subtitle_color', label: 'Tekstkleur' },
  ],
  text_block: [
    { type: 'textarea', key: 'content', label: 'Tekst (HTML toegestaan)', rows: 8, mono: true },
    { type: 'select', key: 'alignment', label: 'Uitlijning', options: alignmentOptions },
    { type: 'image', key: 'image_url', label: 'Afbeelding' },
    { type: 'number', key: 'width_percent', label: 'Breedte (%)', min: 25, max: 100, step: 5 },
    { type: 'text', key: 'side_component_key', label: 'Sidebar component (optioneel)' },
  ],
  features: [
    { type: 'text', key: 'section_title', label: 'Sectietitel' },
    {
      type: 'item-list',
      key: 'items',
      label: 'Kenmerken',
      minItems: 2,
      maxItems: 8,
      itemLabel: 'Kenmerk',
      fields: [
        { type: 'text', key: 'title', label: 'Titel' },
        { type: 'textarea', key: 'description', label: 'Tekst', rows: 3 },
        { type: 'text', key: 'icon', label: 'Icoon (heroicon-id)' },
        { type: 'select', key: 'icon_size', label: 'Icoongrootte', options: [
          { value: 'small', label: 'Klein' },
          { value: 'medium', label: 'Normaal' },
          { value: 'large', label: 'Groot' },
        ]},
        { type: 'select', key: 'icon_align', label: 'Icoon uitlijning', options: [
          { value: 'left', label: 'Links' },
          { value: 'center', label: 'Gecentreerd' },
          { value: 'right', label: 'Rechts' },
        ]},
      ],
    },
  ],
  stats: [
    {
      type: 'item-list',
      key: 'items',
      label: 'Statistieken',
      minItems: 4,
      maxItems: 4,
      itemLabel: 'Stat',
      fields: [
        { type: 'text', key: 'value', label: 'Waarde' },
        { type: 'text', key: 'label', label: 'Label' },
        { type: 'color', key: 'value_color', label: 'Kleur waarde' },
        { type: 'number', key: 'value_size', label: 'Grootte waarde (pt)', min: 10, max: 30, step: 2 },
        { type: 'number', key: 'label_size', label: 'Grootte label (pt)', min: 10, max: 30, step: 2 },
      ],
    },
    { type: 'image', key: 'background_image', label: 'Achtergrondplaatje' },
    { type: 'color', key: 'background', label: 'Achtergrondkleur sectie' },
  ],
  carousel: [
    { type: 'number', key: 'interval_seconds', label: 'Wisselinterval (seconden)', min: 0, max: 120, step: 1, hint: '0 = geen automatisch wisselen' },
    { type: 'number', key: 'max_height_percent', label: 'Maximale hoogte (% scherm)', min: 0, max: 100, step: 1, hint: '0 = geen limiet' },
    {
      type: 'item-list',
      key: 'items',
      label: 'Slides',
      minItems: 0,
      maxItems: 12,
      itemLabel: 'Slide',
      fields: [
        { type: 'image', key: 'image_url', label: 'Afbeelding' },
        { type: 'text', key: 'caption', label: 'Omschrijving (alt-tekst)' },
        { type: 'select', key: 'text_position', label: 'Tekstpositie', options: [
          { value: 'top', label: 'Boven' },
          { value: 'center', label: 'Midden' },
          { value: 'bottom', label: 'Onder' },
        ]},
        { type: 'number', key: 'text_size_px', label: 'Tekstgrootte (px)', min: 12, max: 50, step: 2 },
      ],
    },
  ],
  featured_services: [
    { type: 'text', key: 'title', label: 'Titel' },
    { type: 'textarea', key: 'subtitle', label: 'Ondertitel', rows: 2 },
    { type: 'number', key: 'title_font_size_px', label: 'Titelgrootte (px)', min: 14, max: 48, step: 1 },
    { type: 'number', key: 'subtitle_font_size_px', label: 'Ondertitelgrootte (px)', min: 12, max: 32, step: 1 },
    { type: 'number', key: 'blocks_per_row', label: 'Blokken per rij', min: 1, max: 4, step: 1 },
    { type: 'select', key: 'animation_speed', label: 'Animatiesnelheid', options: [
      { value: 'slow', label: 'Langzaam' },
      { value: 'normal', label: 'Normaal' },
      { value: 'fast', label: 'Snel' },
    ]},
    {
      type: 'item-list',
      key: 'items',
      label: 'Diensten',
      minItems: 1,
      maxItems: 6,
      itemLabel: 'Dienst',
      fields: [
        { type: 'text', key: 'icon', label: 'Icoon' },
        { type: 'text', key: 'title', label: 'Titel' },
        { type: 'textarea', key: 'description', label: 'Beschrijving', rows: 3 },
      ],
    },
  ],
  email_template: [
    { type: 'text', key: 'title', label: 'Titel formulier' },
    { type: 'number', key: 'template_id', label: 'E-mailtemplate ID', min: 0, step: 1, hint: 'Laat leeg of 0 voor standaard' },
  ],
  cards_ronde_hoeken: [
    { type: 'number', key: 'cards_per_row', label: 'Kaarten per rij', min: 1, max: 6, step: 1 },
    {
      type: 'item-list',
      key: 'items',
      label: 'Kaarten',
      minItems: 1,
      maxItems: 12,
      itemLabel: 'Kaart',
      fields: [
        { type: 'image', key: 'image_url', label: 'Afbeelding' },
        { type: 'textarea', key: 'text', label: 'Tekst', rows: 2 },
        { type: 'number', key: 'font_size', label: 'Lettergrootte (px)', min: 10, max: 32, step: 1 },
        { type: 'select', key: 'text_align', label: 'Tekstuitlijning', options: alignmentOptions },
      ],
    },
  ],
}

export function schemaForBaseType(baseType: string): ConfigField[] {
  return SECTION_CONFIG_SCHEMAS[baseType] ?? []
}
