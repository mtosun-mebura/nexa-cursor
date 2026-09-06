import { sectionWidthPercentOptions, type ConfigField, type SelectOption } from './section-config-schemas'

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

const TAXI_BOOKING_KEYS = new Set([
  'component:taxi.boekingsmodule',
  'component:taxiroyaal.boekingsmodule',
  'component:taxi.boekingsmodule_v2',
  'component:taxi.algemene_boekingsmodule',
])

export const TAXI_BOOKING_MODULE_SCHEMA: ConfigField[] = [
  { type: 'group', label: 'Uiterlijk & titel', fields: [
    { type: 'group', label: 'Titel', alwaysOpen: true, layout: 'row', wideStart: true, fields: [
      { type: 'text', key: 'title', label: 'Bloktitel' },
      { type: 'color', key: 'style.title_color', label: 'Kleur titel', defaultValue: '#f97316' },
      { type: 'select', key: 'style.title_font_size_px', label: 'Tekstgrootte titel', options: pxSelectOptions(16, 72, 2), defaultValue: '24' },
      { type: 'text', key: 'subtitle', label: 'Subtitel', colSpan: 2 },
    ]},
    { type: 'group', label: 'Tabbladen', alwaysOpen: true, layout: 'row', fields: [
      { type: 'color', key: 'style.primary_color', label: 'Primair kleur' },
      { type: 'color', key: 'style.active_tab_color', label: 'Actieve tab kleur' },
      { type: 'select', key: 'style.tab_font_size_px', label: 'Tekstgrootte tabbladen', options: pxSelectOptions(10, 24, 2), defaultValue: '14' },
    ]},
    { type: 'group', label: 'Koppen', alwaysOpen: true, layout: 'row', fields: [
      { type: 'select', key: 'style.step_heading_font_size_px', label: 'Tekstgrootte stapkop', options: pxSelectOptions(16, 48, 2), defaultValue: '20' },
      { type: 'select', key: 'style.field_heading_font_size_px', label: 'Tekstgrootte veldkoppen', options: pxSelectOptions(12, 28, 2), defaultValue: '16' },
    ]},
    { type: 'group', label: 'Blok', alwaysOpen: true, layout: 'row', fields: [
      { type: 'number', key: 'style.route_map_zoom', label: 'Zoom routekaarten', min: 1, max: 21, step: 1 },
      { type: 'select', key: 'style.container_max_width', label: 'Max breedte', options: containerMaxWidthOptions, defaultValue: '100%' },
      { type: 'text', key: 'style.container_min_height', label: 'Min. hoogte blok', placeholder: 'auto of 600px' },
      { type: 'number', key: 'style.border_radius', label: 'Border radius (px)', min: 0, max: 40, step: 1 },
      { type: 'select', key: 'style.align', label: 'Uitlijning blok', options: alignOptions },
      { type: 'select', key: 'logic.offer_display_mode', label: 'Aanbiedingen tonen als', options: offerDisplayOptions },
      { type: 'checkbox', key: 'logic.use_evening_night_tariff', label: 'Avond/nacht tarief (22:00–06:00 ×1,2)' },
    ]},
  ]},
  { type: 'group', label: 'Staplabels (tabs)', layout: 'row', fields: [
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
  'component:taxi.algemene_boekingsmodule': TAXI_BOOKING_MODULE_V2_SCHEMA,
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
        { type: 'image', key: 'image_url', label: 'Afbeelding' },
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
      type: 'select',
      key: 'width_percent',
      label: 'Blokbreedte',
      options: sectionWidthPercentOptions,
      defaultValue: '100',
      hint: 'Breedte van dit blok ten opzichte van de pagina. 100% is volle breedte.',
    },
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
        { type: 'heroicon', key: 'icon', label: 'Icoon' },
        { type: 'text', key: 'url', label: 'Link (optioneel)', placeholder: '/taxi' },
        { type: 'textarea', key: 'features_text', label: 'Features (1 per regel)', rows: 3 },
      ],
    },
  ],
  'component:website.screenshot_gallery': [
    { type: 'text', key: 'title', label: 'Titel' },
    { type: 'text', key: 'subtitle', label: 'Subtitel' },
    { type: 'select', key: 'layout', label: 'Layout', options: [
      { value: 'stack', label: 'Gestapeld (één kolom)' },
      { value: 'grid', label: 'Grid' },
    ]},
    {
      type: 'item-list',
      key: 'items',
      label: 'Screenshots',
      minItems: 1,
      maxItems: 8,
      itemLabel: 'Screenshot',
      fields: [
        { type: 'image', key: 'image_url', label: 'Afbeelding' },
        { type: 'text', key: 'caption', label: 'Bijschrift', placeholder: 'Korte tekst bij deze afbeelding' },
        { type: 'text', key: 'alt', label: 'Alt-tekst', placeholder: 'Beschrijving voor toegankelijkheid' },
        { type: 'text', key: 'url', label: 'Link (optioneel)', placeholder: '/taxi' },
        { type: 'select', key: 'crop', label: 'Crop', options: [
          { value: 'none', label: 'Geen crop' },
          { value: 'phone', label: 'Telefoon (inzoomen)' },
          { value: 'portal', label: 'Portaal (inzoomen)' },
        ]},
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

COMPONENT_SCHEMAS['component:website.pricing_packages'] = [
  {
    type: 'select',
    key: 'width_percent',
    label: 'Schaal prijzenoverzicht',
    options: sectionWidthPercentOptions,
    defaultValue: '100',
    hint: 'Breedte van het overzicht ten opzichte van de pagina. 100% is volle breedte.',
  },
  {
    type: 'select',
    key: 'packages_font_size_px',
    label: 'Tekstgrootte pakketten',
    options: pxSelectOptions(10, 24, 1),
    defaultValue: '14',
    hint: 'Vergelijkingstabel met maandpakketten (Start / Pro / Business).',
  },
  {
    type: 'select',
    key: 'website_font_size_px',
    label: 'Tekstgrootte website eenmalig',
    options: pxSelectOptions(10, 24, 1),
    defaultValue: '14',
    hint: 'Blok “Website live zetten” onder de pakketten.',
  },
  {
    type: 'select',
    key: 'website_width_percent',
    label: 'Breedte website eenmalig',
    options: sectionWidthPercentOptions,
    defaultValue: '50',
    hint: 'Breedte van de tekstkolom in het eenmalige websiteblok.',
  },
  {
    type: 'select',
    key: 'addons_font_size_px',
    label: 'Tekstgrootte extra’s',
    options: pxSelectOptions(10, 24, 1),
    defaultValue: '14',
    hint: 'Kaarten zoals extra vestiging, AI-assistent en vloot.',
  },
  { type: 'pricing-packages-preview' },
]

COMPONENT_SCHEMAS['component:website.comparison_table'] = [
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  {
    type: 'select',
    key: 'layout',
    label: 'Plaatsing',
    options: [
      { value: 'columns', label: 'Naast elkaar' },
      { value: 'stack', label: 'Onder elkaar' },
    ],
    defaultValue: 'columns',
  },
  {
    type: 'group',
    label: 'Nadelen',
    headingKey: 'left_heading',
    accentColorKey: 'left_color',
    accentColorFallback: '#dc2626',
    alwaysOpen: true,
    fields: [
      { type: 'text', key: 'left_heading', label: 'Koptekst', placeholder: 'Nadelen' },
      { type: 'color', key: 'left_color', label: 'Kleur', defaultValue: '#dc2626' },
      {
        type: 'select',
        key: 'left_width_percent',
        label: 'Breedte',
        options: sectionWidthPercentOptions,
        defaultValue: '50',
        hint: 'Telt op met de andere kolom. De rest is lege ruimte links en rechts.',
      },
      {
        type: 'item-list',
        key: 'cons',
        label: 'Punten',
        minItems: 1,
        maxItems: 16,
        compact: true,
        fields: [
          { type: 'text', key: 'text', label: '', placeholder: 'Bijv. Klanten haken af zonder online boeking' },
        ],
      },
    ],
  },
  {
    type: 'group',
    label: 'Voordelen',
    headingKey: 'right_heading',
    accentColorKey: 'right_color',
    accentColorFallback: '#16a34a',
    alwaysOpen: true,
    fields: [
      { type: 'text', key: 'right_heading', label: 'Koptekst', placeholder: 'Voordelen' },
      { type: 'color', key: 'right_color', label: 'Kleur', defaultValue: '#16a34a' },
      {
        type: 'select',
        key: 'right_width_percent',
        label: 'Breedte',
        options: sectionWidthPercentOptions,
        defaultValue: '50',
        hint: 'Telt op met de andere kolom. De rest is lege ruimte links en rechts.',
      },
      {
        type: 'item-list',
        key: 'pros',
        label: 'Punten',
        minItems: 1,
        maxItems: 16,
        compact: true,
        fields: [
          { type: 'text', key: 'text', label: '', placeholder: 'Bijv. 24/7 een rit vastleggen op jouw site' },
        ],
      },
    ],
  },
]

COMPONENT_SCHEMAS['component:taxiroyaal.tarieven'] = COMPONENT_SCHEMAS['component:taxi.tarieven']

COMPONENT_SCHEMAS['component:landwind.faq'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  {
    type: 'item-list',
    key: 'items',
    label: 'Vragen',
    minItems: 1,
    maxItems: 16,
    itemLabel: 'Vraag',
    fields: [
      { type: 'text', key: 'question', label: 'Vraag' },
      { type: 'textarea', key: 'answer', label: 'Antwoord', rows: 3 },
    ],
  },
]

COMPONENT_SCHEMAS['component:landwind.trusted_by'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  {
    type: 'item-list',
    key: 'items',
    label: 'Merken',
    minItems: 1,
    maxItems: 16,
    itemLabel: 'Merk',
    fields: [
      { type: 'image', key: 'image_url', label: 'Logo' },
      { type: 'text', key: 'name', label: 'Naam' },
    ],
  },
]

COMPONENT_SCHEMAS['component:play.team'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  {
    type: 'item-list',
    key: 'items',
    label: 'Teamleden',
    minItems: 1,
    maxItems: 12,
    itemLabel: 'Lid',
    fields: [
      { type: 'image', key: 'image_url', label: 'Foto' },
      { type: 'text', key: 'name', label: 'Naam' },
      { type: 'text', key: 'role', label: 'Rol' },
      { type: 'text', key: 'initials', label: 'Initialen (als er geen foto is)' },
    ],
  },
]

COMPONENT_SCHEMAS['component:play.video_spotlight'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  { type: 'image', key: 'image_url', label: 'Poster / still' },
  { type: 'video', key: 'video_url', label: 'Video', hint: 'Sleep een MP4/WebM, of plak een YouTube- of Vimeo-link.' },
  { type: 'text', key: 'cta_label', label: 'Overlay-tekst' },
]

COMPONENT_SCHEMAS['component:vue_material.elevated_cards'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  {
    type: 'item-list',
    key: 'items',
    label: 'Kaarten',
    minItems: 1,
    maxItems: 8,
    itemLabel: 'Kaart',
    fields: [
      { type: 'image', key: 'image_url', label: 'Afbeelding (optioneel)' },
      { type: 'text', key: 'title', label: 'Titel' },
      { type: 'textarea', key: 'text', label: 'Tekst', rows: 3 },
      { type: 'color', key: 'accent', label: 'Accentkleur', defaultValue: '#e91e63' },
    ],
  },
]

COMPONENT_SCHEMAS['component:vue_material.quote_cards'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  {
    type: 'item-list',
    key: 'items',
    label: 'Quotes',
    minItems: 1,
    maxItems: 8,
    itemLabel: 'Quote',
    fields: [
      { type: 'image', key: 'image_url', label: 'Foto auteur' },
      { type: 'textarea', key: 'quote', label: 'Quote', rows: 3 },
      { type: 'text', key: 'author', label: 'Naam' },
      { type: 'text', key: 'role', label: 'Functie' },
    ],
  },
]

COMPONENT_SCHEMAS['component:landwind.feature_checklist'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  { type: 'image', key: 'image_url', label: 'Screenshot' },
  {
    type: 'item-list',
    key: 'items',
    label: 'Vinkjes',
    minItems: 1,
    maxItems: 12,
    itemLabel: 'Punt',
    compact: true,
    fields: [{ type: 'text', key: 'text', label: 'Tekst' }],
  },
]

COMPONENT_SCHEMAS['component:landwind.stats_strip'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  {
    type: 'item-list',
    key: 'items',
    label: 'Cijfers',
    minItems: 1,
    maxItems: 8,
    itemLabel: 'Cijfer',
    fields: [
      { type: 'text', key: 'value', label: 'Getal' },
      { type: 'text', key: 'suffix', label: 'Suffix' },
      { type: 'text', key: 'label', label: 'Label' },
    ],
  },
]

COMPONENT_SCHEMAS['component:play.about_overlap'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  { type: 'textarea', key: 'body', label: 'Tekst', rows: 4 },
  { type: 'image', key: 'image_url', label: 'Foto 1' },
  { type: 'image', key: 'image_url_2', label: 'Foto 2' },
  { type: 'text', key: 'cta_label', label: 'Knoptekst' },
  { type: 'text', key: 'cta_url', label: 'Knop-URL' },
]

COMPONENT_SCHEMAS['component:play.blog_preview'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  {
    type: 'item-list',
    key: 'items',
    label: 'Berichten',
    minItems: 1,
    maxItems: 9,
    itemLabel: 'Bericht',
    fields: [
      { type: 'image', key: 'image_url', label: 'Foto' },
      { type: 'text', key: 'title', label: 'Titel' },
      { type: 'textarea', key: 'excerpt', label: 'Excerpt', rows: 2 },
      { type: 'text', key: 'date', label: 'Datum' },
      { type: 'text', key: 'url', label: 'Link' },
    ],
  },
]

COMPONENT_SCHEMAS['component:play.contact_split'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  { type: 'image', key: 'image_url', label: 'Foto (optioneel)' },
  { type: 'text', key: 'address', label: 'Adres' },
  { type: 'text', key: 'phone', label: 'Telefoon' },
  { type: 'text', key: 'email', label: 'E-mail' },
  { type: 'text', key: 'hours', label: 'Openingstijden' },
  { type: 'text', key: 'cta_label', label: 'Knoptekst' },
]

COMPONENT_SCHEMAS['component:vue_material.stats_counters'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  {
    type: 'item-list',
    key: 'items',
    label: 'Tellers',
    minItems: 1,
    maxItems: 8,
    itemLabel: 'Teller',
    fields: [
      { type: 'text', key: 'value', label: 'Getal' },
      { type: 'text', key: 'suffix', label: 'Suffix' },
      { type: 'text', key: 'decimals', label: 'Decimalen' },
      { type: 'text', key: 'label', label: 'Label' },
    ],
  },
]

COMPONENT_SCHEMAS['component:vue_material.info_pills'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Titel' },
  { type: 'text', key: 'subtitle', label: 'Subtitel' },
  {
    type: 'item-list',
    key: 'items',
    label: 'Pills',
    minItems: 1,
    maxItems: 8,
    itemLabel: 'Pill',
    fields: [
      { type: 'image', key: 'image_url', label: 'Afbeelding (optioneel)' },
      { type: 'text', key: 'label', label: 'Pill' },
      { type: 'text', key: 'title', label: 'Paneeltitel' },
      { type: 'textarea', key: 'text', label: 'Tekst', rows: 3 },
    ],
  },
]

COMPONENT_SCHEMAS['component:vue_material.author_header'] = [
  { type: 'text', key: 'eyebrow', label: 'Boventitel' },
  { type: 'text', key: 'title', label: 'Naam' },
  { type: 'text', key: 'subtitle', label: 'Functie' },
  { type: 'image', key: 'image_url', label: 'Foto' },
  { type: 'textarea', key: 'bio', label: 'Bio', rows: 4 },
  {
    type: 'item-list',
    key: 'items',
    label: 'Socials',
    minItems: 0,
    maxItems: 8,
    itemLabel: 'Link',
    fields: [
      { type: 'text', key: 'label', label: 'Label' },
      { type: 'text', key: 'url', label: 'URL' },
    ],
  },
]

const FIELD_LABELS: Record<string, string> = {
  eyebrow: 'Boventitel',
  title: 'Titel',
  subtitle: 'Subtitel',
  body: 'Tekst',
  bio: 'Bio',
  image_url: 'Afbeelding',
  image_url_2: 'Tweede afbeelding',
  video_url: 'Video',
  poster_url: 'Poster',
  cta_label: 'Knoptekst',
  cta_url: 'Knop-URL',
  address: 'Adres',
  phone: 'Telefoon',
  email: 'E-mail',
  hours: 'Openingstijden',
  icon: 'Icoon',
}

function humanizeFieldKey(key: string): string {
  return FIELD_LABELS[key] ?? key.replace(/_/g, ' ')
}

function looksLikeImageKey(key: string): boolean {
  return /(^|_)(image|photo|poster|logo|avatar|screenshot|thumb)(s|_url|_url_2|$)|image_url/i.test(key)
}

function looksLikeVideoKey(key: string): boolean {
  return /video/i.test(key)
}

function inferScalarField(key: string, value: unknown): ConfigField {
  if (looksLikeImageKey(key)) {
    return { type: 'image', key, label: humanizeFieldKey(key) }
  }
  if (looksLikeVideoKey(key)) {
    return { type: 'video', key, label: humanizeFieldKey(key), hint: 'Sleep een MP4/WebM, of plak een YouTube- of Vimeo-link.' }
  }
  if (key === 'icon' || key.endsWith('_icon')) {
    return { type: 'heroicon', key, label: humanizeFieldKey(key) === key.replace(/_/g, ' ') ? 'Icoon' : humanizeFieldKey(key) }
  }
  if (typeof value === 'boolean') {
    return { type: 'checkbox', key, label: humanizeFieldKey(key) }
  }
  if (typeof value === 'number') {
    return { type: 'number', key, label: humanizeFieldKey(key) }
  }
  if (typeof value === 'string' && (value.length > 80 || /body|bio|text|answer|excerpt/i.test(key))) {
    return { type: 'textarea', key, label: humanizeFieldKey(key), rows: 3 }
  }
  return { type: 'text', key, label: humanizeFieldKey(key) }
}

/** Fallback als een component nog geen vast schema heeft: media als drag-and-drop, lijsten met +/verwijderen. */
export function inferConfigFieldsFromData(data: Record<string, unknown>): ConfigField[] {
  const fields: ConfigField[] = []
  for (const [key, value] of Object.entries(data)) {
    if (key.startsWith('_') || key === 'visibility') {
      continue
    }
    if (Array.isArray(value)) {
      const sample = value.find((item) => item && typeof item === 'object' && !Array.isArray(item)) as Record<string, unknown> | undefined
      const itemFields = sample
        ? Object.entries(sample)
            .filter(([, itemValue]) => itemValue === null || ['string', 'number', 'boolean'].includes(typeof itemValue))
            .map(([itemKey, itemValue]) => inferScalarField(itemKey, itemValue))
        : [{ type: 'text' as const, key: 'text', label: 'Tekst' }]
      fields.push({
        type: 'item-list',
        key,
        label: humanizeFieldKey(key) === key.replace(/_/g, ' ') ? 'Onderdelen' : humanizeFieldKey(key),
        minItems: 0,
        maxItems: 16,
        itemLabel: 'Onderdeel',
        fields: itemFields.length > 0 ? itemFields : [{ type: 'text', key: 'text', label: 'Tekst' }],
      })
      continue
    }
    if (value !== null && typeof value === 'object') {
      continue
    }
    fields.push(inferScalarField(key, value))
  }
  return fields
}

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
