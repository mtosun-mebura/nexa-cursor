import type { ConfigField, SelectOption } from './section-config-schemas'

const alignOptions: SelectOption[] = [
  { value: 'left', label: 'Links' },
  { value: 'center', label: 'Midden' },
  { value: 'right', label: 'Rechts' },
]

const offerDisplayOptions: SelectOption[] = [
  { value: 'vehicle', label: 'Per auto' },
  { value: 'person_range', label: 'Per aantal personen' },
]

const personRangeOptions: SelectOption[] = [
  { value: '', label: 'Alle personen' },
  { value: '1-4', label: 't/m 4 personen' },
  { value: '5-8', label: '5 t/m 8 personen' },
]

const stepOptions: SelectOption[] = [
  { value: 'trip', label: 'Reisgegevens' },
  { value: 'baggage', label: 'Bagage' },
  { value: 'offers', label: 'Aanbiedingen' },
  { value: 'contact', label: 'Contactgegevens' },
  { value: 'confirm', label: 'Bevestiging' },
]

function pxSelectOptions(min: number, max: number, step: number): SelectOption[] {
  const options: SelectOption[] = []
  for (let px = min; px <= max; px += step) {
    options.push({ value: String(px), label: `${px} px` })
  }
  return options
}

const containerMaxWidthOptions: SelectOption[] = [100, 90, 80, 70, 60, 50, 40, 30].map((percent) => ({
  value: `${percent}%`,
  label: `${percent}%`,
}))

const TAXI_BOOKING_KEYS = new Set(['component:taxi.boekingsmodule', 'component:taxiroyaal.boekingsmodule', 'component:taxi.boekingsmodule_v2'])

export const TAXI_BOOKING_MODULE_SCHEMA: ConfigField[] = [
  { type: 'group', label: 'Uiterlijk & titel', fields: [
    { type: 'text', key: 'title', label: 'Bloktitel' },
    { type: 'select', key: 'style.title_font_size_px', label: 'Tekstgrootte titel', options: pxSelectOptions(16, 72, 2) },
    { type: 'text', key: 'subtitle', label: 'Subtitel' },
    { type: 'color', key: 'style.primary_color', label: 'Primair kleur' },
    { type: 'color', key: 'style.active_tab_color', label: 'Actieve tab kleur' },
    { type: 'select', key: 'style.tab_font_size_px', label: 'Tekstgrootte tabbladen', options: pxSelectOptions(10, 24, 2) },
    { type: 'select', key: 'style.step_heading_font_size_px', label: 'Tekstgrootte stapkop', options: pxSelectOptions(16, 48, 2) },
    { type: 'number', key: 'style.route_map_zoom', label: 'Zoom routekaarten', min: 1, max: 21, step: 1 },
    { type: 'select', key: 'style.container_max_width', label: 'Max breedte', options: containerMaxWidthOptions, defaultValue: '100%' },
    { type: 'text', key: 'style.container_min_height', label: 'Min. hoogte blok', placeholder: 'auto of 600px' },
    { type: 'number', key: 'style.border_radius', label: 'Border radius (px)', min: 0, max: 40, step: 1 },
    { type: 'select', key: 'style.align', label: 'Uitlijning blok', options: alignOptions },
    { type: 'select', key: 'logic.offer_display_mode', label: 'Aanbiedingen tonen als', options: offerDisplayOptions },
    { type: 'checkbox', key: 'logic.use_evening_night_tariff', label: 'Avond/nacht tarief (22:00–06:00 ×1,2)' },
  ]},
  { type: 'group', label: 'Staplabels (tabs)', fields: [
    { type: 'text', key: 'step_labels.step1', label: 'STEP1 label' },
    { type: 'text', key: 'step_labels.step2', label: 'STEP2 label' },
    { type: 'text', key: 'step_labels.step3', label: 'STEP3 label' },
    { type: 'text', key: 'step_labels.step4', label: 'STEP4 label' },
    { type: 'text', key: 'step_labels.step5', label: 'STEP5 label' },
  ]},
  { type: 'step-order', key: 'step_order', label: 'Stapvolgorde', options: stepOptions },
  { type: 'group', label: 'Logica & passagiers', fields: [
    { type: 'number', key: 'logic.min_passengers', label: 'Min passagiers', min: 1, max: 8, step: 1 },
    { type: 'number', key: 'logic.max_passengers', label: 'Max passagiers', min: 1, max: 20, step: 1 },
    { type: 'number', key: 'logic.default_passengers', label: 'Default passagiers', min: 1, max: 20, step: 1 },
    { type: 'number', key: 'logic.max_stopovers', label: 'Max tussenstops', min: 0, max: 6, step: 1 },
    { type: 'number', key: 'logic.return_price_multiplier', label: 'Retour multiplier', min: 1, max: 3, step: 0.05 },
    { type: 'number', key: 'logic.person_range_base_price_multiplier', label: 'Standaard × prijs', min: 0.1, max: 5, step: 0.05 },
    { type: 'number', key: 'logic.person_range_base_old_price_multiplier', label: 'Standaard × oud', min: 1, max: 5, step: 0.05 },
    { type: 'checkbox', key: 'logic.return_enabled_by_default', label: 'Retour standaard aan' },
    { type: 'checkbox', key: 'logic.skip_baggage_step', label: 'Bagage overslaan' },
  ]},
  { type: 'group', label: 'Bagage → bus/van', fields: [
    { type: 'checkbox', key: 'logic.baggage_van_upgrade_enabled', label: 'Automatisch bus/van bij te veel bagage' },
    { type: 'number', key: 'logic.baggage_car_max_units', label: 'Max eenheden auto', min: 0, max: 50, step: 1 },
    { type: 'select', key: 'logic.baggage_upgrade_person_range', label: 'Personenrange bus/van', options: personRangeOptions.filter((o) => o.value !== '') },
    { type: 'text', key: 'texts.baggage_van_upgrade_message', label: 'Melding op aanbiedingen-stap' },
  ]},
  { type: 'group', label: 'Teksten & placeholders', fields: [
    { type: 'text', key: 'texts.pickup_placeholder', label: 'Placeholder ophaaladres' },
    { type: 'text', key: 'texts.dropoff_placeholder', label: 'Placeholder afzetadres' },
    { type: 'text', key: 'texts.person_range_feature_text', label: 'Tekst personenkaart' },
    { type: 'text', key: 'texts.submit_button_text', label: 'Submit knoptekst' },
    { type: 'text', key: 'texts.success_message', label: 'Succesmelding' },
    { type: 'text', key: 'texts.offer_button_text', label: 'Aanbieding knoptekst' },
    { type: 'text', key: 'texts.remarks_placeholder', label: 'Placeholder opmerkingen' },
  ]},
  {
    type: 'item-list',
    key: 'baggage_items',
    label: 'Bagage-items',
    minItems: 1,
    maxItems: 12,
    itemLabel: 'Bagage',
    fields: [
      { type: 'text', key: 'key', label: 'Key' },
      { type: 'text', key: 'title', label: 'Titel' },
      { type: 'text', key: 'subtitle', label: 'Subtitel' },
      { type: 'number', key: 'price', label: 'Prijs (€)', min: 0, step: 0.01 },
      { type: 'number', key: 'max_qty', label: 'Max', min: 0, max: 20, step: 1 },
      { type: 'number', key: 'baggage_units', label: 'Eenheden', min: 0, max: 10, step: 0.5 },
    ],
  },
  {
    type: 'item-list',
    key: 'special_items',
    label: 'Speciale bagage',
    minItems: 0,
    maxItems: 12,
    itemLabel: 'Item',
    fields: [
      { type: 'text', key: 'key', label: 'Key' },
      { type: 'text', key: 'title', label: 'Titel' },
      { type: 'number', key: 'price', label: 'Prijs (€)', min: 0, step: 0.01 },
      { type: 'number', key: 'max_qty', label: 'Max', min: 0, max: 20, step: 1 },
      { type: 'number', key: 'baggage_units', label: 'Eenheden', min: 0, max: 10, step: 0.5 },
    ],
  },
  {
    type: 'item-list',
    key: 'offers',
    label: 'Aanbiedingen',
    minItems: 0,
    maxItems: 12,
    itemLabel: 'Aanbieding',
    fields: [
      { type: 'text', key: 'id', label: 'ID' },
      { type: 'text', key: 'title', label: 'Titel' },
      { type: 'text', key: 'badge', label: 'Badge' },
      { type: 'select', key: 'person_range', label: 'Personen', options: personRangeOptions },
      { type: 'number', key: 'vehicle_id', label: 'Voertuig ID', min: 0, step: 1, hint: 'Leeg/0 = automatisch' },
      { type: 'number', key: 'price_multiplier', label: '× prijs', min: 0.1, step: 0.05 },
      { type: 'number', key: 'old_price_multiplier', label: '× oud', min: 1, step: 0.05 },
      { type: 'textarea', key: 'features_text', label: 'Features (1 per regel)', rows: 3 },
    ],
  },
]

export const TAXI_BOOKING_MODULE_V2_SCHEMA: ConfigField[] = [
  ...TAXI_BOOKING_MODULE_SCHEMA,
  { type: 'group', label: 'Live kaart (v2)', fields: [
    { type: 'select', key: 'style.live_map_position', label: 'Kaartpositie', options: [
      { value: 'beside_card', label: 'Rechts naast het blok' },
      { value: 'inside_content', label: 'In het blok, rechts van de inhoud' },
    ]},
  ]},
]

const COMPONENT_SCHEMAS: Record<string, ConfigField[]> = {
  'component:taxi.boekingsmodule': TAXI_BOOKING_MODULE_SCHEMA,
  'component:taxi.boekingsmodule_v2': TAXI_BOOKING_MODULE_V2_SCHEMA,
  'component:taxiroyaal.boekingsmodule': TAXI_BOOKING_MODULE_SCHEMA,
  'component:taxi.tarieven': [
    { type: 'text', key: 'title', label: 'Bloktitel' },
    { type: 'text', key: 'title_font_size', label: 'Bloktitel grootte (bijv. 24px)' },
    { type: 'select', key: 'title_font_style', label: 'Bloktitel stijl', options: [
      { value: 'normal', label: 'Normaal' },
      { value: 'bold', label: 'Vet' },
      { value: 'italic', label: 'Cursief' },
    ]},
    { type: 'select', key: 'title_align', label: 'Bloktitel uitlijning', options: alignOptions },
    { type: 'checkbox', key: 'price_animation', label: 'Telleranimatie prijzen' },
    { type: 'number', key: 'image_fade_duration', label: 'Plaatjes fade-in (ms)', min: 300, max: 5000, step: 100 },
    {
      type: 'item-list',
      key: 'items',
      label: 'Tarievenkaarten',
      minItems: 1,
      maxItems: 8,
      itemLabel: 'Kaart',
      fields: [
        { type: 'select', key: 'rate_type', label: 'Tarief', options: [
          { value: '1-4', label: 't/m 4 personen' },
          { value: '5-8', label: '5 t/m 8 personen' },
          { value: 'overige_kosten', label: 'Overige kosten' },
        ]},
        { type: 'text', key: 'title', label: 'Titel' },
        { type: 'text', key: 'image_url', label: 'Afbeelding URL' },
        { type: 'number', key: 'vehicle_id', label: 'Voertuig ID', min: 0, step: 1 },
      ],
    },
  ],
  'component:taxiroyaal.tarieven': [],
  'component:website.nexa_modules_overview': [
    { type: 'text', key: 'eyebrow', label: 'Boventitel' },
    { type: 'text', key: 'title', label: 'Titel' },
    { type: 'text', key: 'subtitle', label: 'Subtitel' },
    {
      type: 'item-list',
      key: 'items',
      label: 'Modules',
      minItems: 1,
      maxItems: 8,
      itemLabel: 'Module',
      fields: [
        { type: 'text', key: 'name', label: 'Naam' },
        { type: 'wysiwyg', key: 'description', label: 'Beschrijving', placeholder: 'Beschrijving…' },
        { type: 'text', key: 'badge', label: 'Badge' },
        { type: 'text', key: 'icon', label: 'Icoon (heroicon-id)' },
        { type: 'textarea', key: 'features_text', label: 'Features (1 per regel)', rows: 3 },
      ],
    },
  ],
}

const GOOGLE_REVIEWS_SCHEMA: ConfigField[] = [
  { type: 'text', key: 'place_id', label: 'Place ID', placeholder: 'ChIJ...' },
  { type: 'text', key: 'business_name', label: 'Bedrijfsnaam (fallback)', placeholder: 'bijv. Nexa Taxi Amsterdam' },
  { type: 'text', key: 'section_title', label: 'Titel boven de review-carousel', placeholder: 'Standaard: Wat anderen zeggen' },
  { type: 'color', key: 'section_background', label: 'Achtergrondkleur van de sectie', hint: 'Leeg = standaard thema-achtergrond.' },
  { type: 'number', key: 'count', label: 'Aantal reviews (1–5)', min: 1, max: 5, step: 1 },
  { type: 'number', key: 'cache_hours', label: 'Cacheduur (uren)', min: 1, max: 168, step: 1 },
  {
    type: 'star-rating',
    key: 'min_stars',
    label: 'Min. sterren',
    min: 1,
    max: 5,
    hint: 'Alleen reviews met dit aantal sterren of meer tonen.',
  },
]

COMPONENT_SCHEMAS['component:website.google_reviews'] = GOOGLE_REVIEWS_SCHEMA
COMPONENT_SCHEMAS['component:nexa.google_reviews'] = GOOGLE_REVIEWS_SCHEMA

COMPONENT_SCHEMAS['component:taxiroyaal.tarieven'] = COMPONENT_SCHEMAS['component:taxi.tarieven']

export function schemaForComponent(blockKey: string): ConfigField[] {
  if (COMPONENT_SCHEMAS[blockKey]) {
    return COMPONENT_SCHEMAS[blockKey]
  }
  const lower = blockKey.toLowerCase()
  for (const [key, schema] of Object.entries(COMPONENT_SCHEMAS)) {
    if (key.toLowerCase() === lower) {
      return schema
    }
  }
  return []
}

export function isTaxiBookingComponent(blockKey: string): boolean {
  return TAXI_BOOKING_KEYS.has(blockKey) || TAXI_BOOKING_KEYS.has(blockKey.toLowerCase())
}
