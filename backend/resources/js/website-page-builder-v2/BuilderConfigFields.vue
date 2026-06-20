<script setup lang="ts">
import { inject, onUnmounted, provide, ref, watch, type Ref } from 'vue'
import BuilderConfigFields from './BuilderConfigFields.vue'
import type { ConfigField } from './section-config-schemas'
import { buildPatchForPath, getByPath } from './nested-data'
import { websiteMediaPreviewUrl } from './section-data-normalize'

const EXPAND_KEY = Symbol('builderConfigExpand')
const IMAGE_LIGHTBOX_KEY = Symbol('builderImageLightbox')

type ImageLightboxApi = {
  open: (src: string) => void
  close: () => void
}

const props = defineProps<{
  fields: ConfigField[]
  data: Record<string, unknown>
  uploadUrl: string
  websiteMediaUploadUrl?: string
  websiteMediaServeBase?: string
  collapsePrefix?: string
}>()

const emit = defineEmits<{
  patch: [Record<string, unknown>]
}>()

const injectedExpanded = inject<Ref<Set<string>> | null>(EXPAND_KEY, null)
const ownExpanded = ref(new Set<string>())
const expanded = injectedExpanded ?? ownExpanded
if (injectedExpanded === null) {
  provide(EXPAND_KEY, ownExpanded)
}

const uploadingKey = ref<string | null>(null)

const injectedLightbox = inject<ImageLightboxApi | null>(IMAGE_LIGHTBOX_KEY, null)
const ownLightboxSrc = ref<string | null>(null)

function openImagePreview(src: string) {
  const url = src.trim()
  if (!url) {
    return
  }
  if (injectedLightbox) {
    injectedLightbox.open(url)
    return
  }
  ownLightboxSrc.value = url
}

function closeImagePreview() {
  if (injectedLightbox) {
    injectedLightbox.close()
    return
  }
  ownLightboxSrc.value = null
}

if (injectedLightbox === null) {
  provide(IMAGE_LIGHTBOX_KEY, {
    open(src: string) {
      ownLightboxSrc.value = src
    },
    close() {
      ownLightboxSrc.value = null
    },
  })
}

function onLightboxKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && ownLightboxSrc.value) {
    closeImagePreview()
  }
}

if (injectedLightbox === null) {
  let bodyOverflowBeforeLightbox = ''

  watch(ownLightboxSrc, (src) => {
    if (src) {
      bodyOverflowBeforeLightbox = document.body.style.overflow
      document.body.style.overflow = 'hidden'
      window.addEventListener('keydown', onLightboxKeydown)
    } else {
      document.body.style.overflow = bodyOverflowBeforeLightbox
      window.removeEventListener('keydown', onLightboxKeydown)
    }
  })
}

onUnmounted(() => {
  if (injectedLightbox === null && ownLightboxSrc.value) {
    window.removeEventListener('keydown', onLightboxKeydown)
  }
})

function sectionKey(index: number, label: string): string {
  const prefix = props.collapsePrefix ?? 'root'
  return `${prefix}/${index}:${label}`
}

function isCollapsed(index: number, label: string): boolean {
  return !expanded.value.has(sectionKey(index, label))
}

function toggleCollapsed(index: number, label: string) {
  const key = sectionKey(index, label)
  const next = new Set(expanded.value)
  if (next.has(key)) {
    next.delete(key)
  } else {
    next.add(key)
  }
  expanded.value = next
}

function childCollapsePrefix(index: number, label: string): string {
  return sectionKey(index, label)
}

function str(key: string, fallback = ''): string {
  const v = getByPath(props.data, key)
  if (v === null || v === undefined) return fallback
  return String(v)
}

function num(key: string, fallback = 0): number {
  const v = Number(getByPath(props.data, key))
  return Number.isFinite(v) ? v : fallback
}

function rangeValue(field: { key: string; min?: number; max?: number; defaultValue?: number }, fallback?: number): number {
  const raw = getByPath(props.data, field.key)
  const min = field.min ?? 0
  const max = field.max ?? 100
  const fb = fallback ?? field.defaultValue ?? 0
  if (raw === null || raw === undefined || raw === '') {
    return fb
  }
  const v = Number(raw)
  if (!Number.isFinite(v)) {
    return fb
  }
  return Math.max(min, Math.min(max, Math.round(v)))
}

function rgbaFromHex(hex: string, opacityPercent: number): string {
  const normalized = hexForPicker(hex, '#000000')
  const r = parseInt(normalized.slice(1, 3), 16)
  const g = parseInt(normalized.slice(3, 5), 16)
  const b = parseInt(normalized.slice(5, 7), 16)
  const alpha = Math.max(0, Math.min(100, opacityPercent)) / 100
  return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

const rangePreviewChecker =
  'linear-gradient(45deg, #d1d5db 25%, transparent 25%), linear-gradient(-45deg, #d1d5db 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #d1d5db 75%), linear-gradient(-45deg, transparent 75%, #d1d5db 75%)'

function rangePreviewStyle(colorKey: string | undefined, opacity: number): Record<string, string> {
  const hex = colorKey ? str(colorKey) : ''
  const rgba = rgbaFromHex(hex, opacity)
  return {
    backgroundImage: `linear-gradient(${rgba}, ${rgba}), ${rangePreviewChecker}`,
    backgroundSize: 'auto, 8px 8px, 8px 8px, 8px 8px, 8px 8px',
    backgroundColor: 'transparent',
  }
}

function itemRangeValue(item: Record<string, unknown>, field: { key: string; min?: number; max?: number; defaultValue?: number }, fallback?: number): number {
  const raw = item[field.key]
  const min = field.min ?? 0
  const max = field.max ?? 100
  const fb = fallback ?? field.defaultValue ?? 0
  if (raw === null || raw === undefined || raw === '') {
    return fb
  }
  const v = Number(raw)
  if (!Number.isFinite(v)) {
    return fb
  }
  return Math.max(min, Math.min(max, Math.round(v)))
}

function itemRangePreviewStyle(item: Record<string, unknown>, colorKey: string | undefined, opacity: number): Record<string, string> {
  const hex = colorKey ? String(item[colorKey] ?? '') : ''
  const rgba = rgbaFromHex(hex, opacity)
  return {
    backgroundImage: `linear-gradient(${rgba}, ${rgba}), ${rangePreviewChecker}`,
    backgroundSize: 'auto, 8px 8px, 8px 8px, 8px 8px, 8px 8px',
    backgroundColor: 'transparent',
  }
}

function starRating(key: string, min = 1, max = 5, fallback = 1): number {
  const v = Math.round(num(key, fallback))
  return Math.max(min, Math.min(max, v))
}

function setStarRating(key: string, value: number, min = 1, max = 5) {
  updateField(key, Math.max(min, Math.min(max, value)))
}

function bool(key: string): boolean {
  const v = getByPath(props.data, key)
  return v === true || v === '1' || v === 1
}

function updateField(key: string, value: unknown) {
  emit('patch', buildPatchForPath(props.data, key, value))
}

function stepOrderValues(): string[] {
  const raw = props.data.step_order
  if (!Array.isArray(raw)) {
    return ['trip', 'baggage', 'offers', 'contact', 'confirm']
  }
  return raw.map((v) => String(v))
}

function patchStepOrderIndex(index: number, value: string) {
  const order = [...stepOrderValues()]
  while (order.length < 5) {
    order.push('trip')
  }
  order[index] = value
  emit('patch', { step_order: order.slice(0, 5) })
}

function items(key: string): Array<Record<string, unknown>> {
  const raw = props.data[key]
  if (!Array.isArray(raw)) return []
  return raw.map((item) => (item && typeof item === 'object' ? { ...(item as Record<string, unknown>) } : {}))
}

function ensureItemCount(key: string, min: number, max: number, template: Record<string, unknown>) {
  const list = items(key)
  while (list.length < min) {
    list.push({ ...template })
  }
  return list.slice(0, max)
}

function patchItems(key: string, list: Array<Record<string, unknown>>) {
  emit('patch', { [key]: list })
}

function patchItemField(key: string, index: number, field: string, value: unknown) {
  const list = ensureItemCount(key, index + 1, 999, {})
  let storedKey = field
  let storedValue: unknown = value
  if (field === 'features_text') {
    storedKey = 'features'
    storedValue = String(value)
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean)
  }
  list[index] = { ...list[index], [storedKey]: storedValue }
  patchItems(key, list)
}

function itemFieldDisplay(item: Record<string, unknown>, fieldKey: string): string {
  if (fieldKey === 'features_text') {
    const features = item.features
    if (Array.isArray(features)) {
      return features.map(String).join('\n')
    }
    return ''
  }
  if (fieldKey === 'alt') {
    const alt = item.alt ?? item.caption
    if (alt === null || alt === undefined) return ''
    return String(alt)
  }
  const v = item[fieldKey]
  if (v === null || v === undefined) return ''
  return String(v)
}

function mediaPreviewSrc(uuid: string): string {
  const base = props.websiteMediaServeBase ?? '/website-media'
  return websiteMediaPreviewUrl(base, uuid)
}

function addItem(key: string, max: number, template: Record<string, unknown>) {
  const list = items(key)
  if (list.length >= max) return
  patchItems(key, [...list, { ...template }])
}

function removeItem(key: string, index: number, min: number) {
  const list = items(key)
  if (list.length <= min) return
  patchItems(key, list.filter((_, i) => i !== index))
}

function hexForPicker(value: string, fallback: string): string {
  const v = value.trim()
  if (/^#[0-9a-fA-F]{6}$/.test(v)) return v
  if (/^#[0-9a-fA-F]{3}$/.test(v)) {
    return `#${v[1]}${v[1]}${v[2]}${v[2]}${v[3]}${v[3]}`
  }
  return fallback
}

async function uploadImage(fieldKey: string, file: File, onSuccess?: (url: string) => void) {
  uploadingKey.value = fieldKey
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const form = new FormData()
    form.append('image', file)
    form.append('previous_url', str(fieldKey))
    const res = await fetch(props.uploadUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
      },
      body: form,
    })
    const json = await res.json()
    if (!res.ok) {
      throw new Error(json.message ?? 'Upload mislukt')
    }
    const url = json.url ?? json.path ?? ''
    if (url) {
      if (onSuccess) {
        onSuccess(url)
      } else {
        updateField(fieldKey, url)
      }
    }
  } catch (e) {
    alert(e instanceof Error ? e.message : 'Upload mislukt')
  } finally {
    uploadingKey.value = null
  }
}

function onImagePick(fieldKey: string, event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (file) {
    void uploadImage(fieldKey, file)
  }
}

function onItemImagePick(listKey: string, index: number, fieldKey: string, event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (file) {
    void uploadImage(`${listKey}.${index}.${fieldKey}`, file, (url) => {
      patchItemField(listKey, index, fieldKey, url)
    })
  }
}

async function uploadWebsiteMedia(fieldKey: string, file: File, onSuccess?: (uuid: string) => void) {
  const uploadUrl = props.websiteMediaUploadUrl
  if (!uploadUrl) {
    alert('Website-media upload is niet geconfigureerd')
    return
  }
  uploadingKey.value = fieldKey
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const form = new FormData()
    form.append('file', file)
    const res = await fetch(uploadUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
      },
      body: form,
    })
    const json = await res.json()
    if (!res.ok) {
      throw new Error(json.message ?? 'Upload mislukt')
    }
    const uuid = String(json.uuid ?? '').trim()
    if (uuid) {
      if (onSuccess) {
        onSuccess(uuid)
      } else {
        updateField(fieldKey, uuid)
      }
    }
  } catch (e) {
    alert(e instanceof Error ? e.message : 'Upload mislukt')
  } finally {
    uploadingKey.value = null
  }
}

function onWebsiteMediaPick(fieldKey: string, event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (file) {
    void uploadWebsiteMedia(fieldKey, file)
  }
}

function onItemWebsiteMediaPick(listKey: string, index: number, fieldKey: string, event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (file) {
    void uploadWebsiteMedia(`${listKey}.${index}.${fieldKey}`, file, (uuid) => {
      patchItemField(listKey, index, fieldKey, uuid)
    })
  }
}

const mediaDragOverKey = ref<string | null>(null)

function isImageFile(file: File): boolean {
  return file.type.startsWith('image/')
}

function imageFileFromDataTransfer(dataTransfer: DataTransfer | null): File | null {
  if (!dataTransfer) {
    return null
  }
  const fromList = dataTransfer.files?.[0]
  if (fromList && isImageFile(fromList)) {
    return fromList
  }
  for (const item of Array.from(dataTransfer.items)) {
    if (item.kind !== 'file') {
      continue
    }
    const file = item.getAsFile()
    if (file && isImageFile(file)) {
      return file
    }
  }
  return null
}

function onMediaDragOver(uploadKey: string, event: DragEvent) {
  event.preventDefault()
  if (imageFileFromDataTransfer(event.dataTransfer ?? null)) {
    mediaDragOverKey.value = uploadKey
    if (event.dataTransfer) {
      event.dataTransfer.dropEffect = 'copy'
    }
  }
}

function onMediaDragLeave(uploadKey: string, event: DragEvent) {
  const current = event.currentTarget as HTMLElement | null
  const related = event.relatedTarget as Node | null
  if (current && related && current.contains(related)) {
    return
  }
  if (mediaDragOverKey.value === uploadKey) {
    mediaDragOverKey.value = null
  }
}

function onMediaDrop(uploadKey: string, event: DragEvent, onFile: (file: File) => void) {
  event.preventDefault()
  mediaDragOverKey.value = null
  const file = imageFileFromDataTransfer(event.dataTransfer ?? null)
  if (file) {
    onFile(file)
  }
}

function uploadItemWebsiteMedia(listKey: string, index: number, fieldKey: string, file: File) {
  void uploadWebsiteMedia(`${listKey}.${index}.${fieldKey}`, file, (uuid) => {
    patchItemField(listKey, index, fieldKey, uuid)
  })
}

function uploadRootWebsiteMedia(fieldKey: string, file: File) {
  void uploadWebsiteMedia(fieldKey, file)
}
</script>

<template>
  <div class="builder-config-fields">
    <template v-for="(field, fi) in fields" :key="`${field.type}-${fi}`">
      <div
        v-if="field.type === 'group'"
        class="builder-config-group"
        :class="{ 'builder-config-group--collapsed': isCollapsed(fi, field.label) }"
      >
        <div class="builder-config-group__header">
          <span class="builder-config-group__legend">{{ field.label }}</span>
          <button
            type="button"
            class="builder-icon-btn"
            :title="isCollapsed(fi, field.label) ? 'Uitklappen' : 'Inklappen'"
            :aria-expanded="!isCollapsed(fi, field.label)"
            @click="toggleCollapsed(fi, field.label)"
          >
            <i class="ki-filled" :class="isCollapsed(fi, field.label) ? 'ki-down' : 'ki-up'" />
          </button>
        </div>
        <div v-show="!isCollapsed(fi, field.label)" class="builder-config-group__body">
          <BuilderConfigFields
            :fields="field.fields"
            :data="data"
            :upload-url="uploadUrl"
            :website-media-upload-url="websiteMediaUploadUrl"
            :website-media-serve-base="websiteMediaServeBase"
            :collapse-prefix="childCollapsePrefix(fi, field.label)"
            @patch="emit('patch', $event)"
          />
        </div>
      </div>

      <div
        v-else-if="field.type === 'step-order'"
        class="builder-config-step-order"
        :class="{ 'builder-config-section--collapsed': isCollapsed(fi, field.label) }"
      >
        <div class="builder-config-section__header">
          <span class="builder-config-item-list__title">{{ field.label }}</span>
          <button
            type="button"
            class="builder-icon-btn"
            :title="isCollapsed(fi, field.label) ? 'Uitklappen' : 'Inklappen'"
            :aria-expanded="!isCollapsed(fi, field.label)"
            @click="toggleCollapsed(fi, field.label)"
          >
            <i class="ki-filled" :class="isCollapsed(fi, field.label) ? 'ki-down' : 'ki-up'" />
          </button>
        </div>
        <div v-show="!isCollapsed(fi, field.label)" class="builder-config-step-order__grid">
          <label v-for="(_, index) in 5" :key="`${field.key}-${index}`" class="builder-field">
            <span>Positie {{ index + 1 }}</span>
            <select
              class="kt-input"
              :value="stepOrderValues()[index] ?? field.options[0]?.value ?? ''"
              @change="patchStepOrderIndex(index, ($event.target as HTMLSelectElement).value)"
            >
              <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </label>
        </div>
      </div>

      <div
        v-else-if="field.type === 'item-list'"
        class="builder-config-item-list"
        :class="{ 'builder-config-section--collapsed': isCollapsed(fi, field.label) }"
      >
        <div class="builder-config-section__header">
          <span class="builder-config-item-list__title">{{ field.label }}</span>
          <div class="builder-config-section__actions">
            <button
              v-if="(field.maxItems ?? 99) > items(field.key).length"
              type="button"
              class="builder-icon-btn"
              title="Toevoegen"
              aria-label="Toevoegen"
              @click="addItem(field.key, field.maxItems ?? 99, {})"
            >
              <i class="ki-filled ki-plus" aria-hidden="true" />
            </button>
            <button
              type="button"
              class="builder-icon-btn"
              :title="isCollapsed(fi, field.label) ? 'Uitklappen' : 'Inklappen'"
              :aria-expanded="!isCollapsed(fi, field.label)"
              @click="toggleCollapsed(fi, field.label)"
            >
              <i class="ki-filled" :class="isCollapsed(fi, field.label) ? 'ki-down' : 'ki-up'" />
            </button>
          </div>
        </div>
        <template v-if="!isCollapsed(fi, field.label)">
        <div
          v-for="(item, index) in ensureItemCount(field.key, field.minItems ?? 0, field.maxItems ?? 99, {})"
          :key="`${field.key}-${index}`"
          class="builder-config-item"
        >
          <div class="builder-config-item__header">
            <span>{{ field.itemLabel ?? 'Item' }} {{ index + 1 }}</span>
            <button
              v-if="items(field.key).length > (field.minItems ?? 0)"
              type="button"
              class="builder-config-item__remove"
              title="Verwijderen"
              @click="removeItem(field.key, index, field.minItems ?? 0)"
            >
              <i class="ki-filled ki-trash" />
            </button>
          </div>
          <template v-for="(sub, si) in field.fields" :key="`${field.key}-${index}-${si}`">
            <label v-if="sub.type === 'text'" class="builder-field">
              <span>{{ sub.label }}</span>
              <input
                class="kt-input"
                :value="itemFieldDisplay(item, sub.key)"
                :placeholder="sub.placeholder"
                @input="patchItemField(field.key, index, sub.key, ($event.target as HTMLInputElement).value)"
              />
            </label>
            <label v-else-if="sub.type === 'textarea'" class="builder-field">
              <span>{{ sub.label }}</span>
              <textarea
                class="kt-input"
                :class="{ 'font-mono text-sm': sub.mono }"
                :rows="sub.rows ?? 3"
                :value="itemFieldDisplay(item, sub.key)"
                @input="patchItemField(field.key, index, sub.key, ($event.target as HTMLTextAreaElement).value)"
              />
            </label>
            <label v-else-if="sub.type === 'select'" class="builder-field">
              <span>{{ sub.label }}</span>
              <select
                class="kt-input"
                :value="itemFieldDisplay(item, sub.key) || sub.options[0]?.value || ''"
                @change="patchItemField(field.key, index, sub.key, ($event.target as HTMLSelectElement).value)"
              >
                <option v-for="opt in sub.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </label>
            <label v-else-if="sub.type === 'number'" class="builder-field">
              <span>{{ sub.label }}</span>
              <input
                type="number"
                class="kt-input"
                :min="sub.min"
                :max="sub.max"
                :step="sub.step ?? 1"
                :value="Number(itemFieldDisplay(item, sub.key) || 0)"
                @input="patchItemField(field.key, index, sub.key, Number(($event.target as HTMLInputElement).value))"
              />
            </label>
            <div v-else-if="sub.type === 'range'" class="builder-field">
              <div class="builder-range-row">
                <span>{{ sub.label }}</span>
                <span class="builder-range-value" aria-live="polite">{{ itemRangeValue(item, sub) }}{{ sub.unit ?? '' }}</span>
              </div>
              <div class="builder-range-control">
                <div class="builder-range-slider">
                  <input
                    type="range"
                    class="builder-range-input"
                    :min="sub.min ?? 0"
                    :max="sub.max ?? 100"
                    :step="sub.step ?? 1"
                    :value="itemRangeValue(item, sub)"
                    :aria-valuenow="itemRangeValue(item, sub)"
                    :aria-valuemin="sub.min ?? 0"
                    :aria-valuemax="sub.max ?? 100"
                    @input="patchItemField(field.key, index, sub.key, Number(($event.target as HTMLInputElement).value))"
                  />
                </div>
                <span
                  v-if="sub.previewColorKey"
                  class="builder-range-preview"
                  :style="itemRangePreviewStyle(item, sub.previewColorKey, itemRangeValue(item, sub))"
                  title="Voorbeeld achtergrondvlak"
                  role="img"
                  aria-label="Voorbeeld achtergrond"
                />
              </div>
              <p v-if="sub.hint" class="builder-field-hint">{{ sub.hint }}</p>
            </div>
            <div v-else-if="sub.type === 'color'" class="builder-field">
              <span>{{ sub.label }}</span>
              <div class="builder-color-row">
                <input
                  type="color"
                  class="builder-color-picker"
                  :value="hexForPicker(itemFieldDisplay(item, sub.key), '#2563eb')"
                  @input="patchItemField(field.key, index, sub.key, ($event.target as HTMLInputElement).value)"
                />
                <input
                  class="kt-input font-mono text-sm"
                  :value="itemFieldDisplay(item, sub.key)"
                  placeholder="#hex"
                  @input="patchItemField(field.key, index, sub.key, ($event.target as HTMLInputElement).value)"
                />
              </div>
            </div>
            <div v-else-if="sub.type === 'image'" class="builder-field">
              <span>{{ sub.label }}</span>
              <div class="builder-image-field">
                <input
                  class="kt-input text-sm"
                  :value="itemFieldDisplay(item, sub.key)"
                  placeholder="URL of upload"
                  @input="patchItemField(field.key, index, sub.key, ($event.target as HTMLInputElement).value)"
                />
                <label class="kt-btn kt-btn-xs kt-btn-outline shrink-0 cursor-pointer">
                  {{ uploadingKey === `${field.key}.${index}.${sub.key}` ? 'Uploaden…' : 'Upload' }}
                  <input
                    type="file"
                    class="hidden"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    @change="onItemImagePick(field.key, index, sub.key, $event)"
                  />
                </label>
              </div>
            </div>
            <div v-else-if="sub.type === 'website-media-image'" class="builder-field">
              <span>{{ sub.label }}</span>
              <p v-if="sub.hint" class="builder-field-hint">{{ sub.hint }}</p>
              <div class="builder-media-image-row">
                <img
                  v-if="itemFieldDisplay(item, sub.key)"
                  :src="mediaPreviewSrc(itemFieldDisplay(item, sub.key))"
                  alt=""
                  class="builder-image-preview builder-image-preview--clickable builder-media-image-row__preview"
                  role="button"
                  tabindex="0"
                  title="Klik om te vergroten"
                  @click="openImagePreview(mediaPreviewSrc(itemFieldDisplay(item, sub.key)))"
                  @keydown.enter.prevent="openImagePreview(mediaPreviewSrc(itemFieldDisplay(item, sub.key)))"
                />
                <div v-else class="builder-media-image-row__placeholder" aria-hidden="true">
                  <i class="ki-filled ki-picture" />
                  <span>Geen afbeelding</span>
                </div>
                <label
                  class="hero-image-upload-area builder-media-upload-area"
                  :class="{
                    'builder-media-upload-area--dragover': mediaDragOverKey === `${field.key}.${index}.${sub.key}`,
                    'builder-media-upload-area--busy': uploadingKey === `${field.key}.${index}.${sub.key}`,
                  }"
                  @dragover="onMediaDragOver(`${field.key}.${index}.${sub.key}`, $event)"
                  @dragleave="onMediaDragLeave(`${field.key}.${index}.${sub.key}`, $event)"
                  @drop="onMediaDrop(`${field.key}.${index}.${sub.key}`, $event, (file) => uploadItemWebsiteMedia(field.key, index, sub.key, file))"
                >
                  <span class="builder-media-upload-area__title">
                    {{ uploadingKey === `${field.key}.${index}.${sub.key}` ? 'Uploaden…' : 'Klik of sleep afbeelding' }}
                  </span>
                  <span class="builder-media-upload-area__hint">JPG, PNG, WebP (max. 5MB)</span>
                  <input
                    type="file"
                    class="hero-image-file-input hidden"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    @change="onItemWebsiteMediaPick(field.key, index, sub.key, $event)"
                  />
                </label>
              </div>
            </div>
          </template>
        </div>
        </template>
      </div>

      <label v-else-if="field.type === 'text'" class="builder-field">
        <span>{{ field.label }}</span>
        <input
          class="kt-input"
          :value="str(field.key)"
          :placeholder="field.placeholder"
          @input="updateField(field.key, ($event.target as HTMLInputElement).value)"
        />
      </label>

      <label v-else-if="field.type === 'textarea'" class="builder-field">
        <span>{{ field.label }}</span>
        <textarea
          class="kt-input"
          :class="{ 'font-mono text-sm min-h-40': field.mono }"
          :rows="field.rows ?? 3"
          :value="str(field.key)"
          :placeholder="field.placeholder"
          @input="updateField(field.key, ($event.target as HTMLTextAreaElement).value)"
        />
      </label>

      <label v-else-if="field.type === 'select'" class="builder-field">
        <span>{{ field.label }}</span>
        <select
          class="kt-input"
          :value="str(field.key, field.options[0]?.value ?? '')"
          @change="updateField(field.key, ($event.target as HTMLSelectElement).value)"
        >
          <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
      </label>

      <div v-else-if="field.type === 'range'" class="builder-field">
        <div class="builder-range-row">
          <span>{{ field.label }}</span>
          <span class="builder-range-value" aria-live="polite">{{ rangeValue(field) }}{{ field.unit ?? '' }}</span>
        </div>
        <div class="builder-range-control">
          <div class="builder-range-slider">
            <input
              type="range"
              class="builder-range-input"
              :min="field.min ?? 0"
              :max="field.max ?? 100"
              :step="field.step ?? 1"
              :value="rangeValue(field)"
              :aria-valuenow="rangeValue(field)"
              :aria-valuemin="field.min ?? 0"
              :aria-valuemax="field.max ?? 100"
              @input="updateField(field.key, Number(($event.target as HTMLInputElement).value))"
            />
          </div>
          <span
            v-if="field.previewColorKey"
            class="builder-range-preview"
            :style="rangePreviewStyle(field.previewColorKey, rangeValue(field))"
            title="Voorbeeld achtergrondvlak"
            role="img"
            aria-label="Voorbeeld achtergrond"
          />
        </div>
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
      </div>

      <label v-else-if="field.type === 'number'" class="builder-field">
        <span>{{ field.label }}</span>
        <input
          type="number"
          class="kt-input"
          :min="field.min"
          :max="field.max"
          :step="field.step ?? 1"
          :value="num(field.key)"
          @input="updateField(field.key, Number(($event.target as HTMLInputElement).value))"
        />
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
      </label>

      <div v-else-if="field.type === 'star-rating'" class="builder-field">
        <span>{{ field.label }}</span>
        <div class="builder-star-picker" role="group" :aria-label="field.label">
          <button
            v-for="star in (field.max ?? 5)"
            :key="`${field.key}-${star}`"
            type="button"
            class="builder-star-picker__btn"
            :class="{ 'builder-star-picker__btn--active': star <= starRating(field.key, field.min ?? 1, field.max ?? 5, field.min ?? 1) }"
            :aria-label="`Minimaal ${star} ${star === 1 ? 'ster' : 'sterren'}`"
            @click="setStarRating(field.key, star, field.min ?? 1, field.max ?? 5)"
          >
            <span aria-hidden="true">★</span>
          </button>
        </div>
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
      </div>

      <div v-else-if="field.type === 'color'" class="builder-field">
        <span>{{ field.label }}</span>
        <div class="builder-color-row">
          <input
            type="color"
            class="builder-color-picker"
            :value="hexForPicker(str(field.key), '#2563eb')"
            @input="updateField(field.key, ($event.target as HTMLInputElement).value)"
          />
          <input
            class="kt-input font-mono text-sm"
            :value="str(field.key)"
            placeholder="#hex (leeg = standaard)"
            @input="updateField(field.key, ($event.target as HTMLInputElement).value)"
          />
        </div>
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
      </div>

      <div v-else-if="field.type === 'image'" class="builder-field">
        <span>{{ field.label }}</span>
        <div class="builder-image-field">
          <input
            class="kt-input text-sm"
            :value="str(field.key)"
            placeholder="URL of upload afbeelding"
            @input="updateField(field.key, ($event.target as HTMLInputElement).value)"
          />
          <label class="kt-btn kt-btn-xs kt-btn-outline shrink-0 cursor-pointer">
            {{ uploadingKey === field.key ? 'Uploaden…' : 'Upload' }}
            <input
              type="file"
              class="hidden"
              accept="image/jpeg,image/png,image/webp,image/gif"
              @change="onImagePick(field.key, $event)"
            />
          </label>
        </div>
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
        <img
          v-if="str(field.key)"
          :src="str(field.key)"
          alt=""
          class="builder-image-preview builder-image-preview--clickable"
          role="button"
          tabindex="0"
          title="Klik om te vergroten"
          @click="openImagePreview(str(field.key))"
          @keydown.enter.prevent="openImagePreview(str(field.key))"
        />
      </div>

      <div v-else-if="field.type === 'website-media-image'" class="builder-field">
        <span>{{ field.label }}</span>
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
        <div class="builder-media-image-row">
          <img
            v-if="str(field.key)"
            :src="mediaPreviewSrc(str(field.key))"
            alt=""
            class="builder-image-preview builder-image-preview--clickable builder-media-image-row__preview"
            role="button"
            tabindex="0"
            title="Klik om te vergroten"
            @click="openImagePreview(mediaPreviewSrc(str(field.key)))"
            @keydown.enter.prevent="openImagePreview(mediaPreviewSrc(str(field.key)))"
          />
          <div v-else class="builder-media-image-row__placeholder" aria-hidden="true">
            <i class="ki-filled ki-picture" />
            <span>Geen afbeelding</span>
          </div>
          <label
            class="hero-image-upload-area builder-media-upload-area"
            :class="{
              'builder-media-upload-area--dragover': mediaDragOverKey === field.key,
              'builder-media-upload-area--busy': uploadingKey === field.key,
            }"
            @dragover="onMediaDragOver(field.key, $event)"
            @dragleave="onMediaDragLeave(field.key, $event)"
            @drop="onMediaDrop(field.key, $event, (file) => uploadRootWebsiteMedia(field.key, file))"
          >
            <span class="builder-media-upload-area__title">
              {{ uploadingKey === field.key ? 'Uploaden…' : 'Klik of sleep afbeelding' }}
            </span>
            <span class="builder-media-upload-area__hint">JPG, PNG, WebP (max. 5MB)</span>
            <input
              type="file"
              class="hero-image-file-input hidden"
              accept="image/jpeg,image/png,image/webp,image/gif"
              @change="onWebsiteMediaPick(field.key, $event)"
            />
          </label>
        </div>
      </div>

      <label v-else-if="field.type === 'checkbox'" class="builder-checkbox">
        <input
          type="checkbox"
          class="kt-checkbox"
          :checked="bool(field.key)"
          @change="updateField(field.key, ($event.target as HTMLInputElement).checked)"
        />
        <span>{{ field.label }}</span>
      </label>
    </template>
  </div>

  <Teleport v-if="injectedLightbox === null" to="body">
    <div
      v-if="ownLightboxSrc"
      class="builder-image-lightbox"
      role="dialog"
      aria-modal="true"
      aria-label="Afbeelding vergroot"
    >
      <button
        type="button"
        class="builder-image-lightbox__backdrop"
        aria-label="Sluiten"
        @click="closeImagePreview"
      />
      <div class="builder-image-lightbox__content">
        <button
          type="button"
          class="builder-image-lightbox__close"
          aria-label="Sluiten"
          @click="closeImagePreview"
        >
          <i class="ki-filled ki-cross" aria-hidden="true" />
        </button>
        <img :src="ownLightboxSrc" alt="Vergrote afbeelding" class="builder-image-lightbox__img" />
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.builder-config-step-order,
.builder-config-item-list {
  border: 1px solid var(--border);
  border-radius: 0.65rem;
  padding: 0.65rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.builder-config-step-order__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.65rem;
}

.builder-config-fields {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.builder-config-group {
  border: 1px solid var(--border);
  border-radius: 0.65rem;
  padding: 0.65rem 0.75rem;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.builder-config-group--collapsed {
  padding-bottom: 0.65rem;
}

.builder-config-group__header,
.builder-config-section__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  min-height: 1.75rem;
}

.builder-config-group__legend {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--foreground);
  min-width: 0;
}

.builder-config-group__body {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.builder-config-section__actions {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  flex-shrink: 0;
}

.builder-icon-btn {
  width: 1.75rem;
  height: 1.75rem;
  border: 0;
  border-radius: 0.45rem;
  background: transparent;
  color: var(--muted-foreground);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  cursor: pointer;
}

.builder-icon-btn:hover {
  background: color-mix(in srgb, var(--muted) 50%, transparent);
  color: var(--foreground);
}

.builder-config-item-list__title {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--foreground);
  min-width: 0;
}

.builder-config-item {
  border: 1px solid var(--border);
  border-radius: 0.65rem;
  padding: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  background: color-mix(in srgb, var(--muted) 12%, transparent);
}

.builder-config-item__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--foreground);
}

.builder-config-item__remove {
  width: 2rem;
  height: 2rem;
  border: 0;
  background: transparent;
  color: var(--muted-foreground);
  padding: 0;
  border-radius: 0.45rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  cursor: pointer;
  font-size: 1.125rem;
  line-height: 1;
}

.builder-config-item__remove:hover {
  color: #dc2626;
  background: color-mix(in srgb, #dc2626 10%, transparent);
}

.builder-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.875rem;
}

.builder-field > span {
  color: var(--muted-foreground);
  font-weight: 500;
}

.builder-field-hint {
  font-size: 0.75rem;
  color: var(--muted-foreground);
  margin: 0;
}

.builder-star-picker {
  display: flex;
  align-items: center;
  gap: 0.15rem;
}

.builder-star-picker__btn {
  width: 2rem;
  height: 2rem;
  border: 0;
  border-radius: 0.35rem;
  background: transparent;
  color: var(--muted-foreground);
  font-size: 1.25rem;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: color 0.15s;
}

.builder-star-picker__btn:hover,
.builder-star-picker__btn--active {
  color: #eab308;
}

.builder-color-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.builder-color-picker {
  width: 2.5rem;
  height: 2.25rem;
  padding: 0.15rem;
  border: 1px solid var(--border);
  border-radius: 0.4rem;
  cursor: pointer;
  flex-shrink: 0;
}

.builder-image-field {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.builder-media-image-row {
  display: flex;
  align-items: stretch;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.builder-media-image-row__preview {
  flex: 0 1 auto;
  align-self: center;
}

.builder-media-image-row__placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  min-width: 7rem;
  width: min(100%, 14rem);
  min-height: 6rem;
  max-height: 14rem;
  border: 1px dashed var(--border);
  border-radius: 0.4rem;
  background: color-mix(in srgb, var(--muted) 22%, transparent);
  color: var(--muted-foreground);
  font-size: 0.75rem;
  flex-shrink: 0;
}

.builder-media-image-row__placeholder i {
  font-size: 1.35rem;
  opacity: 0.7;
}

.builder-media-upload-area {
  flex: 1 1 10rem;
  min-height: 6rem;
  max-height: 14rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 0.85rem 1rem;
  border: 1px dashed var(--border);
  border-radius: 0.65rem;
  background: color-mix(in srgb, var(--muted) 28%, transparent);
  cursor: pointer;
  text-align: center;
  transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}

.builder-media-upload-area:hover,
.builder-media-upload-area--dragover {
  border-color: color-mix(in srgb, var(--primary) 45%, var(--border));
  background: color-mix(in srgb, var(--primary) 8%, var(--muted) 22%);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 12%, transparent);
}

.builder-media-upload-area--busy {
  pointer-events: none;
  opacity: 0.75;
}

.builder-media-upload-area__title {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--foreground);
}

.builder-media-upload-area__hint {
  font-size: 0.75rem;
  color: var(--muted-foreground);
  margin-top: 0.25rem;
}

.builder-image-preview {
  display: block;
  max-width: 100%;
  width: auto;
  height: auto;
  max-height: 9rem;
  object-fit: contain;
  object-position: left center;
  border-radius: 0.4rem;
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--muted) 25%, transparent);
}

.builder-image-preview--clickable {
  cursor: zoom-in;
  transition: opacity 0.15s, box-shadow 0.15s;
}

.builder-image-preview--clickable:hover {
  opacity: 0.92;
  box-shadow: 0 4px 14px color-mix(in srgb, var(--foreground) 12%, transparent);
}

.builder-image-preview--clickable:focus-visible {
  outline: 2px solid color-mix(in srgb, var(--primary) 55%, transparent);
  outline-offset: 2px;
}

.builder-image-lightbox {
  position: fixed;
  inset: 0;
  z-index: 10060;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.builder-image-lightbox__backdrop {
  position: absolute;
  inset: 0;
  border: 0;
  background: rgba(15, 23, 42, 0.72);
  backdrop-filter: blur(6px);
  cursor: zoom-out;
}

.builder-image-lightbox__content {
  position: relative;
  z-index: 1;
  max-width: min(92vw, 72rem);
  max-height: 90vh;
}

.builder-image-lightbox__close {
  position: absolute;
  top: -0.65rem;
  right: -0.65rem;
  z-index: 2;
  width: 2.5rem;
  height: 2.5rem;
  border: 1px solid var(--border);
  border-radius: 9999px;
  background: var(--background);
  color: var(--foreground);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.25);
}

.builder-image-lightbox__close:hover {
  background: color-mix(in srgb, var(--muted) 35%, var(--background));
}

.builder-image-lightbox__img {
  display: block;
  max-width: min(92vw, 72rem);
  max-height: 85vh;
  width: auto;
  height: auto;
  object-fit: contain;
  border-radius: 0.75rem;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.35);
}

.builder-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  cursor: pointer;
}

.builder-range-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.builder-range-row > span:first-child {
  color: var(--muted-foreground);
  font-weight: 500;
  font-size: 0.875rem;
}

.builder-range-value {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2.75rem;
  border-radius: 0.375rem;
  background: color-mix(in srgb, var(--muted) 80%, transparent);
  padding: 0.125rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  color: var(--foreground);
  flex-shrink: 0;
}

.builder-range-control {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.builder-range-slider {
  display: flex;
  align-items: center;
  flex: 1;
  min-width: 0;
  min-height: 1.75rem;
  padding: 0.25rem 0;
}

.builder-range-preview {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.375rem;
  border: 1px solid var(--border);
  flex-shrink: 0;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}

.builder-range-input {
  width: 100%;
  height: 1.25rem;
  margin: 0;
  padding: 0;
  cursor: pointer;
  background: transparent;
  -webkit-appearance: none;
  appearance: none;
}

.builder-range-input:focus {
  outline: none;
}

.builder-range-input:focus-visible::-webkit-slider-thumb {
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.35);
}

.builder-range-input:focus-visible::-moz-range-thumb {
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.35);
}

.builder-range-input::-webkit-slider-runnable-track {
  height: 6px;
  border-radius: 9999px;
  background: #64748b;
  border: 1px solid #475569;
}

:global(html.dark) .builder-range-input::-webkit-slider-runnable-track,
:global(.dark) .builder-range-input::-webkit-slider-runnable-track {
  background: #9ca3af;
  border-color: #e5e7eb;
}

.builder-range-input::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 20px;
  height: 20px;
  margin-top: -8px;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #2563eb;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.28);
}

:global(html.dark) .builder-range-input::-webkit-slider-thumb,
:global(.dark) .builder-range-input::-webkit-slider-thumb {
  background: #f8fafc;
  border-color: #60a5fa;
  box-shadow: 0 1px 5px rgba(0, 0, 0, 0.45);
}

.builder-range-input::-moz-range-track {
  height: 6px;
  border-radius: 9999px;
  background: #64748b;
  border: 1px solid #475569;
}

:global(html.dark) .builder-range-input::-moz-range-track,
:global(.dark) .builder-range-input::-moz-range-track {
  background: #9ca3af;
  border-color: #e5e7eb;
}

.builder-range-input::-moz-range-thumb {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #2563eb;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.28);
  box-sizing: border-box;
}

:global(html.dark) .builder-range-input::-moz-range-thumb,
:global(.dark) .builder-range-input::-moz-range-thumb {
  background: #f8fafc;
  border-color: #60a5fa;
  box-shadow: 0 1px 5px rgba(0, 0, 0, 0.45);
}
</style>
