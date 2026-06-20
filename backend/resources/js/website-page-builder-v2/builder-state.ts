import { computed, ref, watch } from 'vue'
import type { BuilderBootstrap, CanvasBlock, PaletteDragPayload } from './types'
import { baseTypeFromKey } from './palette-meta'
import { denormalizeHomeSectionsForSave, normalizeHomeSectionsForBuilder } from './section-data-normalize'

const FIXED_KEYS = new Set(['footer', 'copyright'])

function clone<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T
}

function normalizeOrder(raw: unknown): string[] {
  if (Array.isArray(raw)) {
    return raw.filter((k): k is string => typeof k === 'string' && k !== '')
  }
  if (typeof raw === 'string' && raw.trim() !== '') {
    return raw.split(',').map((s) => s.trim()).filter(Boolean)
  }
  return []
}

function generateSectionKey(baseType: string, existingKeys: string[]): string {
  const matches = existingKeys.filter((k) => k === baseType || k.startsWith(`${baseType}_`))
  if (matches.length === 0) {
    return baseType
  }
  if (matches.length === 1) {
    return `${baseType}_2`
  }
  return `${baseType}_${matches.length + 1}`
}

export function useBuilderState(bootstrap: BuilderBootstrap) {
  const homeSections = ref<Record<string, unknown>>(normalizeHomeSectionsForBuilder(clone(bootstrap.homeSections)))
  const selectedKey = ref<string | null>(null)
  const removedSectionKeys = ref<string[]>([])
  const saving = ref(false)
  const saveMessage = ref<string | null>(null)
  const saveError = ref<string | null>(null)
  const dirty = ref(false)
  const previewMode = ref<'build' | 'live'>('build')
  const paletteQuery = ref('')

  const labelByType = computed(() => {
    const map = new Map<string, string>()
    bootstrap.catalog.sections.forEach((s) => map.set(s.type, s.label))
    bootstrap.catalog.components.forEach((c) => map.set(c.sectionKey, c.name))
    return map
  })

  const canvasKeys = computed(() =>
    normalizeOrder(homeSections.value.section_order).filter((k) => !FIXED_KEYS.has(k))
  )

  const canvasBlocks = computed<CanvasBlock[]>(() =>
    canvasKeys.value.map((key) => {
      const baseType = baseTypeFromKey(key)
      const isComponent = key.startsWith('component:')
      return {
        key,
        baseType: isComponent ? 'component' : baseType,
        label: labelByType.value.get(isComponent ? key : baseType) ?? key,
        isComponent,
      }
    })
  )

  function sectionData(key: string): Record<string, unknown> {
    const data = homeSections.value[key]
    return data && typeof data === 'object' && !Array.isArray(data)
      ? (data as Record<string, unknown>)
      : {}
  }

  function setSectionData(key: string, patch: Record<string, unknown>) {
    homeSections.value[key] = { ...sectionData(key), ...patch }
    dirty.value = true
  }

  function syncSectionOrder(keys: string[]) {
    const footerKeys = normalizeOrder(homeSections.value.section_order).filter((k) => FIXED_KEYS.has(k))
    homeSections.value.section_order = [...keys, ...footerKeys.filter((k) => !keys.includes(k))]
    dirty.value = true
  }

  function addBlock(payload: PaletteDragPayload, insertIndex?: number) {
    let key: string
    if (payload.kind === 'component') {
      key = payload.sectionKey
      if (canvasKeys.value.includes(key)) {
        saveError.value = 'Dit component staat al op de pagina.'
        return
      }
      homeSections.value[key] = {}
    } else {
      key = generateSectionKey(payload.type, canvasKeys.value)
      const defaults = bootstrap.defaults[payload.type]
      homeSections.value[key] = clone(
        defaults && typeof defaults === 'object' ? defaults : {}
      )
    }

    const keys = [...canvasKeys.value]
    const index = insertIndex === undefined ? keys.length : Math.max(0, Math.min(insertIndex, keys.length))
    keys.splice(index, 0, key)
    syncSectionOrder(keys)
    selectedKey.value = key
    saveError.value = null
  }

  function syncRemovedKeys() {
    homeSections.value.removed_section_keys = removedSectionKeys.value.join(',')
  }

  function removeBlock(key: string) {
    if (!removedSectionKeys.value.includes(key)) {
      removedSectionKeys.value.push(key)
      syncRemovedKeys()
    }
    const keys = canvasKeys.value.filter((k) => k !== key)
    syncSectionOrder(keys)
    delete homeSections.value[key]
    if (selectedKey.value === key) {
      selectedKey.value = keys[0] ?? null
    }
  }

  function moveBlock(key: string, direction: -1 | 1) {
    const keys = [...canvasKeys.value]
    const index = keys.indexOf(key)
    if (index < 0) {
      return
    }
    const target = index + direction
    if (target < 0 || target >= keys.length) {
      return
    }
    ;[keys[index], keys[target]] = [keys[target], keys[index]]
    syncSectionOrder(keys)
  }

  function reorderBlock(fromKey: string, toIndex: number) {
    const keys = [...canvasKeys.value]
    const fromIndex = keys.indexOf(fromKey)
    if (fromIndex < 0) {
      return
    }

    keys.splice(fromIndex, 1)

    let insertIndex = toIndex
    if (fromIndex < toIndex) {
      insertIndex = toIndex - 1
    }
    insertIndex = Math.max(0, Math.min(insertIndex, keys.length))
    keys.splice(insertIndex, 0, fromKey)
    syncSectionOrder(keys)
  }

  function selectBlock(key: string | null) {
    selectedKey.value = key
  }

  function sectionVisible(key: string): boolean {
    const vis = homeSections.value.visibility
    if (!vis || typeof vis !== 'object' || Array.isArray(vis)) {
      return true
    }
    const value = (vis as Record<string, unknown>)[key]
    return value !== false && value !== '0' && value !== 0
  }

  function setSectionVisible(key: string, visible: boolean) {
    const current = homeSections.value.visibility
    const vis =
      current && typeof current === 'object' && !Array.isArray(current)
        ? { ...(current as Record<string, unknown>) }
        : {}
    vis[key] = visible
    homeSections.value.visibility = vis
    dirty.value = true
  }

  async function save() {
    saving.value = true
    saveMessage.value = null
    saveError.value = null
    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
      const response = await fetch(bootstrap.routes.save, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ home_sections: denormalizeHomeSectionsForSave(homeSections.value) }),
      })
      const data = await response.json()
      if (!response.ok || !data.ok) {
        throw new Error(data.message ?? 'Opslaan mislukt')
      }
      if (data.homeSections) {
        homeSections.value = normalizeHomeSectionsForBuilder(clone(data.homeSections))
      }
      dirty.value = false
      saveMessage.value = data.message ?? 'Opgeslagen'
    } catch (error) {
      saveError.value = error instanceof Error ? error.message : 'Opslaan mislukt'
    } finally {
      saving.value = false
    }
  }

  watch(homeSections, () => {
    // noop: mutations set dirty explicitly
  }, { deep: true })

  return {
    bootstrap,
    homeSections,
    selectedKey,
    saving,
    saveMessage,
    saveError,
    dirty,
    previewMode,
    paletteQuery,
    canvasBlocks,
    canvasKeys,
    sectionData,
    setSectionData,
    addBlock,
    removeBlock,
    moveBlock,
    reorderBlock,
    selectBlock,
    sectionVisible,
    setSectionVisible,
    save,
  }
}

export const DRAG_MIME = 'application/x-nexa-builder-block'
export const DRAG_BLOCK_KEY_MIME = 'application/x-nexa-builder-block-key'

function parseDragPayload(raw: string | undefined): PaletteDragPayload | null {
  if (!raw || raw.trim() === '') {
    return null
  }
  try {
    const parsed = JSON.parse(raw) as PaletteDragPayload
    if (parsed.kind === 'section' && typeof parsed.type === 'string') {
      return parsed
    }
    if (parsed.kind === 'component' && typeof parsed.sectionKey === 'string') {
      return parsed
    }
    return null
  } catch {
    return null
  }
}

export function readDragPayload(event: DragEvent): PaletteDragPayload | null {
  const transfer = event.dataTransfer
  if (!transfer) {
    return null
  }
  return (
    parseDragPayload(transfer.getData(DRAG_MIME))
    ?? parseDragPayload(transfer.getData('text/plain'))
  )
}

export function writeDragPayload(event: DragEvent, payload: PaletteDragPayload) {
  const json = JSON.stringify(payload)
  const transfer = event.dataTransfer
  if (!transfer) {
    return
  }
  transfer.setData(DRAG_MIME, json)
  transfer.setData('text/plain', json)
  transfer.effectAllowed = 'copyMove'
}

export function writeBlockKeyDrag(event: DragEvent, key: string) {
  event.dataTransfer?.setData(DRAG_BLOCK_KEY_MIME, key)
  event.dataTransfer!.effectAllowed = 'move'
}

export function readBlockKeyDrag(event: DragEvent): string | null {
  const key = event.dataTransfer?.getData(DRAG_BLOCK_KEY_MIME)
  return key && key.trim() !== '' ? key : null
}
