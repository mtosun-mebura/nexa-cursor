import { baseTypeFromKey } from './palette-meta'

const CAROUSEL_ITEM_KEYS = [
  'uuid',
  'alt',
  'text_color',
  'text_bg_color',
  'text_bg_opacity',
  'text_bg_width_percent',
  'text_position',
  'text_size_px',
  'text_animation',
  'text_animation_duration_ms',
  'text_animation_stagger_ms',
] as const

function isRecord(value: unknown): value is Record<string, unknown> {
  return !!value && typeof value === 'object' && !Array.isArray(value)
}

function normalizeCarouselItem(item: unknown): Record<string, unknown> {
  if (!isRecord(item)) {
    return {}
  }

  const out: Record<string, unknown> = {}

  let uuid = String(item.uuid ?? item.UUID ?? '').trim()
  if (!uuid) {
    const imageUrl = String(item.image_url ?? '').trim()
    const match = imageUrl.match(/\/website-media\/([^/?#]+)/)
    if (match) {
      uuid = decodeURIComponent(match[1])
    }
  }
  if (uuid) {
    out.uuid = uuid
  }

  const alt = String(item.alt ?? item.caption ?? '').trim()
  if (alt) {
    out.alt = alt
  }

  for (const key of CAROUSEL_ITEM_KEYS) {
    if (key === 'uuid' || key === 'alt') {
      continue
    }
    if (item[key] !== undefined && item[key] !== null && item[key] !== '') {
      out[key] = item[key]
    }
  }

  return out
}

function denormalizeCarouselItem(item: unknown): Record<string, unknown> {
  if (!isRecord(item)) {
    return {}
  }

  const out: Record<string, unknown> = {}
  const uuid = String(item.uuid ?? '').trim()
  if (uuid) {
    out.uuid = uuid
  }

  const alt = String(item.alt ?? item.caption ?? '').trim()
  if (alt) {
    out.alt = alt
  }

  for (const key of CAROUSEL_ITEM_KEYS) {
    if (key === 'uuid' || key === 'alt') {
      continue
    }
    if (item[key] !== undefined && item[key] !== null && item[key] !== '') {
      out[key] = item[key]
    }
  }

  return out
}

function isComparisonTableKey(sectionKey: string): boolean {
  return sectionKey.includes('comparison_table')
}

function textItemsFromUnknown(value: unknown): Array<{ text: string }> {
  if (!Array.isArray(value)) {
    return []
  }
  return value.map((item) => {
    if (typeof item === 'string') {
      return { text: item.trim() }
    }
    if (isRecord(item)) {
      return { text: String(item.text ?? '').trim() }
    }
    return { text: '' }
  })
}

function textItemsHaveContent(items: Array<{ text: string }>): boolean {
  return items.some((item) => item.text !== '')
}

function comparisonItemsFromRows(rows: unknown[], side: 'left' | 'right'): Array<{ text: string }> {
  const out: Array<{ text: string }> = []
  for (const row of rows) {
    if (!isRecord(row)) {
      continue
    }
    const text = String(row[side] ?? '').trim()
    if (text !== '') {
      out.push({ text })
    }
  }
  return out
}

function zipComparisonRows(cons: Array<{ text: string }>, pros: Array<{ text: string }>): Array<{ left: string; right: string }> {
  const count = Math.max(cons.length, pros.length)
  const rows: Array<{ left: string; right: string }> = []
  for (let i = 0; i < count; i += 1) {
    const left = (cons[i]?.text ?? '').trim()
    const right = (pros[i]?.text ?? '').trim()
    if (left === '' && right === '') {
      continue
    }
    rows.push({ left, right })
  }
  return rows.length > 0 ? rows : [{ left: '', right: '' }]
}

function hexOrFallback(value: unknown, fallback: string): string {
  const raw = String(value ?? '').trim()
  if (/^#[0-9a-fA-F]{6}$/.test(raw)) {
    return raw
  }
  if (/^#[0-9a-fA-F]{3}$/.test(raw)) {
    return `#${raw[1]}${raw[1]}${raw[2]}${raw[2]}${raw[3]}${raw[3]}`
  }
  return fallback
}

function widthPercentOrFallback(value: unknown, fallback: number): string {
  const n = Number.parseInt(String(value ?? ''), 10)
  if (!Number.isFinite(n) || n < 30 || n > 100) {
    return String(fallback)
  }
  return String(n)
}

function normalizeComparisonTableData(data: Record<string, unknown>): Record<string, unknown> {
  const rows = Array.isArray(data.rows) ? data.rows : []
  const consFromList = textItemsFromUnknown(data.cons)
  const prosFromList = textItemsFromUnknown(data.pros)
  const cons = textItemsHaveContent(consFromList) ? consFromList : comparisonItemsFromRows(rows, 'left')
  const pros = textItemsHaveContent(prosFromList) ? prosFromList : comparisonItemsFromRows(rows, 'right')

  return {
    ...data,
    left_heading: String(data.left_heading ?? '').trim() || 'Nadelen',
    right_heading: String(data.right_heading ?? '').trim() || 'Voordelen',
    left_color: hexOrFallback(data.left_color, '#dc2626'),
    right_color: hexOrFallback(data.right_color, '#16a34a'),
    layout: String(data.layout ?? '') === 'stack' ? 'stack' : 'columns',
    left_width_percent: widthPercentOrFallback(data.left_width_percent, 50),
    right_width_percent: widthPercentOrFallback(data.right_width_percent, 50),
    cons: cons.length > 0 ? cons : [{ text: '' }],
    pros: pros.length > 0 ? pros : [{ text: '' }],
    rows: zipComparisonRows(
      cons.length > 0 ? cons : [{ text: '' }],
      pros.length > 0 ? pros : [{ text: '' }],
    ),
  }
}

function normalizeSectionData(sectionKey: string, data: unknown): Record<string, unknown> {
  if (!isRecord(data)) {
    return {}
  }

  if (isComparisonTableKey(sectionKey)) {
    return normalizeComparisonTableData(data)
  }

  if (baseTypeFromKey(sectionKey) !== 'carousel') {
    return { ...data }
  }

  const items = Array.isArray(data.items)
    ? data.items.map((item) => normalizeCarouselItem(item))
    : []

  return {
    ...data,
    items,
    interval_seconds: data.interval_seconds ?? 5,
    max_height_percent: data.max_height_percent ?? 0,
  }
}

function denormalizeSectionData(sectionKey: string, data: unknown): Record<string, unknown> {
  if (!isRecord(data)) {
    return {}
  }

  if (isComparisonTableKey(sectionKey)) {
    return normalizeComparisonTableData(data)
  }

  if (baseTypeFromKey(sectionKey) !== 'carousel') {
    return { ...data }
  }

  const items = Array.isArray(data.items)
    ? data.items.map((item) => denormalizeCarouselItem(item)).filter((item) => Object.keys(item).length > 0)
    : []

  const out: Record<string, unknown> = {
    items,
    interval_seconds: data.interval_seconds ?? 5,
    max_height_percent: data.max_height_percent ?? 0,
  }

  return out
}

export function normalizeHomeSectionsForBuilder(homeSections: Record<string, unknown>): Record<string, unknown> {
  const out: Record<string, unknown> = { ...homeSections }

  for (const [key, value] of Object.entries(homeSections)) {
    if (['section_order', 'visibility', 'footer', 'copyright', 'admin_collapsed', 'removed_section_keys'].includes(key)) {
      continue
    }
    out[key] = normalizeSectionData(key, value)
  }

  return out
}

export function denormalizeHomeSectionsForSave(homeSections: Record<string, unknown>): Record<string, unknown> {
  const out: Record<string, unknown> = { ...homeSections }

  for (const [key, value] of Object.entries(homeSections)) {
    if (['section_order', 'visibility', 'footer', 'copyright', 'admin_collapsed', 'removed_section_keys'].includes(key)) {
      continue
    }
    out[key] = denormalizeSectionData(key, value)
  }

  return out
}

export function websiteMediaPreviewUrl(serveBase: string, uuid: string): string {
  const id = uuid.trim()
  if (!id) {
    return ''
  }
  const base = serveBase.replace(/\/$/, '')
  return `${base}/${encodeURIComponent(id)}`
}

/** Zelfde logica als WebsiteBuilderService::storageUrlToDisplayUrl (voor admin-previews). */
export function websiteImageDisplayUrl(url: string): string {
  const u = url.trim()
  if (!u) {
    return ''
  }
  if (u.startsWith('data:')) {
    return u
  }

  let path: string | null = null
  if (u.startsWith('/storage/')) {
    path = u.replace(/^\/storage\//, '')
  } else {
    const storageMatch = u.match(/^https?:\/\/[^/]+\/storage\/([^?#]+)/)
    if (storageMatch) {
      path = storageMatch[1]
    }
  }

  if (path) {
    const encoded = path.replace(/\.\./g, '').replace(/\//g, '--').replace(/^\/+/, '')
    const origin = typeof window !== 'undefined' ? window.location.origin : ''
    return `${origin}/file/${encoded}`
  }

  if (/^https?:\/\//.test(u)) {
    const fileMatch = u.match(/^https?:\/\/[^/]+(\/file\/[^?#]+)/)
    if (fileMatch && typeof window !== 'undefined') {
      return `${window.location.origin}${fileMatch[1]}`
    }
    return u
  }

  if (typeof window !== 'undefined') {
    return u.startsWith('/') ? `${window.location.origin}${u}` : `${window.location.origin}/${u}`
  }

  return u
}

/** Admin-preview src: normaliseer paden; geen cache-buster op data:/blob:-URL's. */
export function websiteImagePreviewSrc(url: string, cacheToken?: number): string {
  const trimmed = url.trim()
  if (!trimmed) {
    return ''
  }
  if (trimmed.startsWith('data:') || trimmed.startsWith('blob:')) {
    return trimmed
  }
  const normalized = websiteImageDisplayUrl(trimmed)
  if (!normalized) {
    return ''
  }
  if (cacheToken === undefined) {
    return normalized
  }
  const sep = normalized.includes('?') ? '&' : '?'
  return `${normalized}${sep}t=${cacheToken}`
}
