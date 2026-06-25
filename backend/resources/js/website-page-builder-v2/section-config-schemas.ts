export type SelectOption = { value: string; label: string }

export type FieldVisibleWhen = {
  key: string
  notEmpty?: boolean
}

export type ConfigField =
  | { type: 'text'; key: string; label: string; placeholder?: string; hint?: string; visibleWhen?: FieldVisibleWhen }
  | { type: 'textarea'; key: string; label: string; rows?: number; placeholder?: string; mono?: boolean; hint?: string; visibleWhen?: FieldVisibleWhen }
  | { type: 'wysiwyg'; key: string; label: string; placeholder?: string; hint?: string; visibleWhen?: FieldVisibleWhen }
  | { type: 'select'; key: string; label: string; options: SelectOption[]; hint?: string; visibleWhen?: FieldVisibleWhen }
  | { type: 'dynamic-select'; key: string; label: string; source: 'sideComponents' | 'emailTemplates'; hint?: string; visibleWhen?: FieldVisibleWhen }
  | { type: 'number'; key: string; label: string; min?: number; max?: number; step?: number; hint?: string; visibleWhen?: FieldVisibleWhen }
  | { type: 'range'; key: string; label: string; min?: number; max?: number; step?: number; hint?: string; unit?: string; previewColorKey?: string; defaultValue?: number }
  | { type: 'color'; key: string; label: string; hint?: string }
  | { type: 'image'; key: string; label: string; hint?: string }
  | { type: 'website-media-image'; key: string; label: string; hint?: string }
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
    { type: 'group', label: 'Achtergrond tekstblok', fields: [
      { type: 'color', key: 'text_bg_color', label: 'Achtergrond', hint: 'Kleurvlak achter titel en ondertitel' },
      { type: 'range', key: 'text_bg_opacity', label: 'Transparantie', min: 0, max: 100, step: 1, unit: '%', previewColorKey: 'text_bg_color', hint: '0 = doorzichtig, 100 = ondoorzichtig' },
    ]},
    { type: 'image', key: 'background_image_url', label: 'Achtergrond banner', hint: 'Atom-v2 / modern thema' },
    { type: 'image', key: 'author_image_url', label: 'Ronde foto in banner', hint: 'Atom-v2 thema' },
    { type: 'group', label: 'Gradient overloop', fields: [
      { type: 'color', key: 'overlay_color_from', label: 'Kleur van' },
      { type: 'color', key: 'overlay_color_to', label: 'Kleur naar' },
      { type: 'range', key: 'overlay_opacity', label: 'Helderheid overloop', min: 0, max: 100, step: 1, unit: '%', previewColorKey: 'overlay_color_from', defaultValue: 85, hint: '0 = lichter, 100 = donkerder' },
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
    { type: 'wysiwyg', key: 'content', label: 'Tekst (rich text)', placeholder: 'Voeg hier uw tekst toe...' },
    {
      type: 'select',
      key: 'alignment',
      label: 'Tekstuitlijning op de website',
      options: [
        { value: 'left', label: 'Links' },
        { value: 'center', label: 'Midden' },
        { value: 'right', label: 'Rechts' },
        { value: 'full', label: 'Volledige breedte' },
      ],
      hint: 'Bepaalt hoe de tekst wordt uitgelijnd en of er ruimte is voor een component ernaast.',
    },
    {
      type: 'dynamic-select',
      key: 'side_component_key',
      label: 'Component naast de tekst',
      source: 'sideComponents',
      hint: 'Toon een informatieaanvraag-formulier naast de tekst. Alleen bij uitlijning Links of Rechts.',
    },
    {
      type: 'dynamic-select',
      key: 'side_template_id',
      label: 'E-mailtemplate voor het formulier',
      source: 'emailTemplates',
      visibleWhen: { key: 'side_component_key', notEmpty: true },
      hint: 'Verplicht wanneer u een formulier naast de tekst toont.',
    },
    {
      type: 'select',
      key: 'width_percent',
      label: 'Sectiebreedte op de website',
      options: [
        { value: '100', label: '100%' },
        { value: '90', label: '90%' },
        { value: '80', label: '80%' },
        { value: '70', label: '70%' },
        { value: '60', label: '60%' },
        { value: '50', label: '50%' },
        { value: '40', label: '40%' },
        { value: '30', label: '30%' },
      ],
      hint: 'Breedte van de sectie ten opzichte van de pagina (in procenten).',
    },
    {
      type: 'image',
      key: 'image_url',
      label: 'Afbeelding naast de tekst',
      hint: 'Optioneel: toon een afbeelding links of rechts van de tekst (zelfde zijde als het component). Alleen bij uitlijning Links of Rechts.',
    },
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
        { type: 'website-media-image', key: 'uuid', label: 'Afbeelding', hint: 'Website media (zelfde als klassieke editor)' },
        { type: 'text', key: 'alt', label: 'Omschrijving (alt-tekst)', placeholder: 'Bijv. Comfortabel, betrouwbaar en altijd op tijd' },
        { type: 'color', key: 'text_color', label: 'Tekstkleur', hint: 'Leeg = wit' },
        { type: 'color', key: 'text_bg_color', label: 'Achtergrondkleur tekstblok' },
        { type: 'range', key: 'text_bg_opacity', label: 'Transparantie achtergrond', min: 0, max: 100, step: 1, unit: '%', previewColorKey: 'text_bg_color', hint: '0 = doorzichtig, 100 = ondoorzichtig' },
        { type: 'select', key: 'text_position', label: 'Tekstpositie', options: [
          { value: 'top', label: 'Boven' },
          { value: 'center', label: 'Midden' },
          { value: 'bottom', label: 'Onder' },
        ]},
        { type: 'number', key: 'text_size_px', label: 'Tekstgrootte (px)', min: 12, max: 50, step: 2 },
        { type: 'select', key: 'text_animation', label: 'Animatie', options: [
          { value: 'rise', label: 'Woorden omhoog' },
          { value: 'fade', label: 'Infaden' },
          { value: 'slide_left', label: 'Van links' },
          { value: 'zoom', label: 'Inzoomen' },
          { value: 'blur', label: 'Scherp worden' },
        ]},
        { type: 'select', key: 'text_animation_duration_ms', label: 'Animatieduur (per woord)', options: [
          { value: '300', label: '0,3 s' },
          { value: '450', label: '0,45 s' },
          { value: '550', label: '0,55 s (standaard)' },
          { value: '800', label: '0,8 s' },
          { value: '1000', label: '1 s' },
          { value: '1500', label: '1,5 s' },
          { value: '2000', label: '2 s' },
          { value: '3000', label: '3 s' },
          { value: '4000', label: '4 s' },
        ]},
        { type: 'select', key: 'text_animation_stagger_ms', label: 'Pauze tussen woorden', options: [
          { value: '0', label: 'Geen' },
          { value: '50', label: '0,05 s' },
          { value: '90', label: '0,09 s (standaard)' },
          { value: '120', label: '0,12 s' },
          { value: '150', label: '0,15 s' },
          { value: '200', label: '0,2 s' },
          { value: '300', label: '0,3 s' },
          { value: '500', label: '0,5 s' },
        ]},
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
