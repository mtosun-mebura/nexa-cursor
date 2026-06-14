export type PlaygroundStep = {
  id: string
  shortLabel: string
  title: string
  description: string
  ktui: boolean
}

export const PLAYGROUND_STEPS: PlaygroundStep[] = [
  {
    id: 'intro',
    shortLabel: 'Intro',
    title: 'Component catalog',
    description: 'Loop stap voor stap door Metronic demo1-componenten. Na elke stap worden KTUI-plugins opnieuw geïnitialiseerd.',
    ktui: false,
  },
  {
    id: 'buttons',
    shortLabel: 'Buttons',
    title: 'Buttons & badges',
    description: 'Primary, outline, ghost, danger, success en status-badges.',
    ktui: false,
  },
  {
    id: 'forms',
    shortLabel: 'Forms',
    title: 'Form controls',
    description: 'Inputs, textarea en checkbox in kt-styling.',
    ktui: false,
  },
  {
    id: 'alerts',
    shortLabel: 'Alerts',
    title: 'Alerts',
    description: 'Info, success, warning en danger meldingen.',
    ktui: false,
  },
  {
    id: 'table',
    shortLabel: 'Table',
    title: 'Table',
    description: 'Rittenoverzicht met kt-table en badges.',
    ktui: false,
  },
  {
    id: 'tabs',
    shortLabel: 'Tabs',
    title: 'Tabs',
    description: 'Wissel tussen tabbladen (data-kt-tabs).',
    ktui: true,
  },
  {
    id: 'accordion',
    shortLabel: 'Accordion',
    title: 'Accordion',
    description: 'Inklapbare secties (data-kt-accordion).',
    ktui: true,
  },
  {
    id: 'modal',
    shortLabel: 'Modal',
    title: 'Modal',
    description: 'Open de Metronic modal via data-kt-modal-toggle.',
    ktui: true,
  },
]
