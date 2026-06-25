import type { SelectOption } from './section-config-schemas'
import { baseTypeFromKey } from './palette-meta'
import type { CanvasBlock } from './types'

export const widthPercentOptions: SelectOption[] = [
  { value: '100', label: '100%' },
  { value: '90', label: '90%' },
  { value: '80', label: '80%' },
  { value: '70', label: '70%' },
  { value: '60', label: '60%' },
  { value: '50', label: '50%' },
  { value: '40', label: '40%' },
  { value: '30', label: '30%' },
]

function isEmailTemplateBlock(block: CanvasBlock): boolean {
  if (block.isComponent) {
    return block.key.toLowerCase().includes('email_template')
  }
  return block.baseType === 'email_template' || baseTypeFromKey(block.key) === 'email_template'
}

export function sideComponentSelectOptions(
  blockKey: string,
  canvasBlocks: CanvasBlock[],
): SelectOption[] {
  const options: SelectOption[] = [{ value: '', label: '— Geen —' }]
  const seen = new Set<string>()

  for (const block of canvasBlocks) {
    if (block.key === blockKey || !isEmailTemplateBlock(block)) {
      continue
    }
    if (seen.has(block.key)) {
      continue
    }
    seen.add(block.key)
    const suffix = block.key.includes('_') && block.key !== 'email_template' ? ` – ${block.key}` : ''
    options.push({
      value: block.key,
      label: `${block.label}${suffix}`,
    })
  }

  if (!seen.has('email_template')) {
    options.push({
      value: 'email_template',
      label: 'E-mailtemplate (informatieaanvraag)',
    })
  }

  return options
}

export function emailTemplateSelectOptions(
  templates: Array<{ id: number; name: string; type: string }>,
): SelectOption[] {
  return [
    { value: '', label: '— Kies een e-mailtemplate —' },
    ...templates.map((template) => ({
      value: String(template.id),
      label: `${template.name} (${template.type})`,
    })),
  ]
}
