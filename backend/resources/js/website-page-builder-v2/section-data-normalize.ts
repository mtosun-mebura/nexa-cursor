import { baseTypeFromKey } from './palette-meta'

const CAROUSEL_ITEM_KEYS = [
  'uuid',
  'alt',
  'text_color',
  'text_bg_color',
  'text_bg_opacity',
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

function normalizeSectionData(sectionKey: string, data: unknown): Record<string, unknown> {
  if (!isRecord(data)) {
    return {}
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
