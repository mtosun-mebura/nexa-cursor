const SKIP_KEY = /(url|color|image|uuid|font|opacity|animation|padding|margin|background|logo|map_|_lat|_lng|zoom|align|class|token|percent|duration|stagger|radius|shadow|border|icon|visibility|inherit|order|style|_src|href|file|_path|width|height|_px|api_key|html_id|removed_)/i

const TEXT_KEY = /^(title|subtitle|content|description|text|heading|label|caption|alt|value|cta_primary_text|cta_secondary_text|section_title|intro|body|quote|name|question|answer|left_heading|right_heading|features_text|badge|button_text|copyright)$|_(title|subtitle|content|description|text|heading|label|caption)$/i

const META_KEYS = new Set([
  'section_order',
  'visibility',
  'removed_section_keys',
  'footer',
  'copyright',
])

const SECTION_LABELS: Record<string, string> = {
  hero: 'Hero',
  stats: 'Statistieken',
  why_nexa: 'Introductie',
  features: 'Kenmerken',
  cta: 'Call-to-action',
  carousel: 'Carousel',
  cards_ronde_hoeken: 'Kaarten',
  featured_services: 'Diensten',
  email_template: 'Formulier',
  text_block: 'Tekstblok',
}

const MAX_CHARS = 8000

function isRecord(value: unknown): value is Record<string, unknown> {
  return !!value && typeof value === 'object' && !Array.isArray(value)
}

function stripHtml(value: string): string {
  return value
    .replace(/<script[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style[\s\S]*?<\/style>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/&quot;/gi, '"')
    .replace(/\s+/g, ' ')
    .trim()
}

function looksLikeUrlOrCode(value: string): boolean {
  if (/^https?:\/\//i.test(value) || /^\/[a-z0-9_\-./]+$/i.test(value)) {
    return true
  }
  if (/^#[0-9a-f]{3,8}$/i.test(value)) {
    return true
  }
  if (/^[0-9.]+(px|rem|em|%|ms)$/i.test(value)) {
    return true
  }
  return false
}

function shouldKeepKey(key: string): boolean {
  if (SKIP_KEY.test(key)) {
    return false
  }
  return TEXT_KEY.test(key)
}

function collectStrings(value: unknown, into: string[], depth = 0): void {
  if (depth > 8 || into.length > 80) {
    return
  }
  if (typeof value === 'string') {
    const text = stripHtml(value)
    if (text.length >= 2 && !looksLikeUrlOrCode(text)) {
      into.push(text)
    }
    return
  }
  if (Array.isArray(value)) {
    value.forEach((item) => collectKeyed(item, into, depth + 1))
    return
  }
  if (isRecord(value)) {
    collectKeyed(value, into, depth + 1)
  }
}

function collectKeyed(value: unknown, into: string[], depth: number): void {
  if (!isRecord(value)) {
    collectStrings(value, into, depth)
    return
  }
  for (const [key, nested] of Object.entries(value)) {
    if (shouldKeepKey(key)) {
      collectStrings(nested, into, depth + 1)
      continue
    }
    if (SKIP_KEY.test(key)) {
      continue
    }
    if (Array.isArray(nested) || isRecord(nested)) {
      collectKeyed(nested, into, depth + 1)
    }
  }
}

function sectionLabel(key: string): string {
  if (key.startsWith('component:')) {
    const name = key.slice('component:'.length).replace(/[._]/g, ' ')
    return name.charAt(0).toUpperCase() + name.slice(1)
  }
  const base = key.replace(/_\d+$/, '')
  return SECTION_LABELS[base] ?? base
}

function uniqueLines(lines: string[]): string[] {
  const seen = new Set<string>()
  const out: string[] = []
  for (const line of lines) {
    const normalized = line.toLowerCase()
    if (seen.has(normalized)) {
      continue
    }
    seen.add(normalized)
    out.push(line)
  }
  return out
}

export function extractPageContentText(homeSections: Record<string, unknown>): string {
  const order = Array.isArray(homeSections.section_order)
    ? homeSections.section_order.filter((k): k is string => typeof k === 'string' && k !== '')
    : Object.keys(homeSections)

  const blocks: string[] = []

  for (const key of order) {
    if (META_KEYS.has(key) || key === 'footer' || key === 'copyright') {
      continue
    }
    const data = homeSections[key]
    if (!isRecord(data) && typeof data !== 'string') {
      continue
    }
    const lines: string[] = []
    collectKeyed(data, lines, 0)
    const unique = uniqueLines(lines)
    if (unique.length === 0) {
      continue
    }
    blocks.push(`[${sectionLabel(key)}]\n${unique.join('\n')}`)
  }

  const text = blocks.join('\n\n').trim()
  if (text.length <= MAX_CHARS) {
    return text
  }
  return text.slice(0, MAX_CHARS - 1).trimEnd() + '…'
}
