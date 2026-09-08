<script setup lang="ts">
import { inject, onUnmounted, provide, ref, watch, type Ref } from 'vue'
import BuilderConfigFields from './BuilderConfigFields.vue'
import BuilderFooterLogoField from './BuilderFooterLogoField.vue'
import BuilderFooterMapField from './BuilderFooterMapField.vue'
import BuilderFooterSocialIcon from './BuilderFooterSocialIcon.vue'
import BuilderHeroiconPicker from './BuilderHeroiconPicker.vue'
import BuilderPricingPackagesPreview from './BuilderPricingPackagesPreview.vue'
import BuilderWysiwygField from './BuilderWysiwygField.vue'
import type { ConfigField, FieldVisibleContext, FieldVisibleWhen, SelectOption } from './section-config-schemas'
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
  blockKey?: string
  sideComponentOptions?: SelectOption[]
  emailTemplateOptions?: SelectOption[]
  footerLogoUploadUrl?: string
  footerLogoFallbackUrl?: string
  googleMapsApiKey?: string
  googleMapsMapId?: string
  postcodeLookupUrl?: string
  moduleName?: string | null
  generateImageUrl?: string
  pageTitle?: string
  companyName?: string
  companyId?: number | null
  visibility?: Record<string, unknown>
}>()

const emit = defineEmits<{
  patch: [Record<string, unknown>]
  'patch-visibility': [string, boolean]
}>()

const injectedExpanded = inject<Ref<Set<string>> | null>(EXPAND_KEY, null)
const ownExpanded = ref(new Set<string>())
const expanded = injectedExpanded ?? ownExpanded
if (injectedExpanded === null) {
  provide(EXPAND_KEY, ownExpanded)
}

const uploadingKey = ref<string | null>(null)
const generatingImageKey = ref<string | null>(null)

const injectedLightbox = inject<ImageLightboxApi | null>(IMAGE_LIGHTBOX_KEY, null)
const ownLightboxSrc = ref<string | null>(null)
const builderCapabilities = inject<Ref<{ gpsTracking: boolean; superAdmin: boolean }>>(
  'builderCapabilities',
  ref({ gpsTracking: false, superAdmin: false }),
)

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

function onCollapsibleHeaderClick(field: ConfigField, index: number) {
  if (field.type === 'group' && field.alwaysOpen) {
    return
  }
  toggleCollapsed(index, field.label)
}

function childCollapsePrefix(index: number, label: string): string {
  return sectionKey(index, label)
}

function str(key: string, fallback = ''): string {
  const v = getByPath(props.data, key)
  if (v === null || v === undefined) return fallback
  return String(v)
}

function fieldVisible(field: ConfigField): boolean {
  if (!matchesVisibleContext(field)) {
    return false
  }
  const when = (field as { visibleWhen?: FieldVisibleWhen }).visibleWhen
  if (!when) {
    return true
  }
  const value = String(getByPath(props.data, when.key) ?? '').trim()
  if (when.notEmpty) {
    return value !== ''
  }
  return true
}

function matchesVisibleContext(field: ConfigField): boolean {
  const raw = (field as { visibleWhenContext?: FieldVisibleContext | FieldVisibleContext[] }).visibleWhenContext
  if (!raw) {
    return true
  }
  const keys = Array.isArray(raw) ? raw : [raw]
  const caps = builderCapabilities.value
  return keys.some((key) => {
    if (key === 'gpsTracking') {
      return !!caps.gpsTracking
    }
    if (key === 'superAdmin') {
      return !!caps.superAdmin
    }
    return false
  })
}

function dynamicSelectOptions(field: Extract<ConfigField, { type: 'dynamic-select' }>): SelectOption[] {
  if (field.source === 'sideComponents') {
    return props.sideComponentOptions ?? [{ value: '', label: '— Geen —' }]
  }
  return props.emailTemplateOptions ?? [{ value: '', label: '— Kies een e-mailtemplate —' }]
}

function updateDynamicSelect(field: Extract<ConfigField, { type: 'dynamic-select' }>, value: string) {
  if (field.key === 'side_component_key' && value === '') {
    emit('patch', { side_component_key: '', side_template_id: '' })
    return
  }
  if (field.key === 'side_template_id') {
    updateField(field.key, value === '' ? '' : Number(value))
    return
  }
  updateField(field.key, value)
}

function socialNetworkFromFieldKey(key: string): string | null {
  if (!key.startsWith('social_')) return null
  return key.slice('social_'.length)
}

function isFooterSocialField(key: string): boolean {
  return socialNetworkFromFieldKey(key) !== null
}

function isFooterLinkList(field: ConfigField): boolean {
  if (props.blockKey !== 'footer' || field.type !== 'item-list') {
    return false
  }
  return field.key === 'quick_links' || field.key === 'support_links'
}

function isCompactItemList(field: ConfigField): boolean {
  return field.type === 'item-list' && !!field.compact
}

function compactItemPlaceholder(field: ConfigField): string {
  if (field.type !== 'item-list') {
    return ''
  }
  const first = field.fields.find((sub) => sub.type === 'text')
  return first && first.type === 'text' ? (first.placeholder ?? '') : ''
}

function compactItemTextKey(field: ConfigField): string {
  if (field.type !== 'item-list') {
    return 'text'
  }
  const first = field.fields.find((sub) => sub.type === 'text')
  return first && 'key' in first ? first.key : 'text'
}

function wysiwygEditorKey(fieldKey: string, itemIndex?: number): string {
  const block = props.blockKey ?? 'block'
  if (itemIndex !== undefined) {
    return `${block}-${fieldKey}-${itemIndex}`
  }
  return `${block}-${fieldKey}`
}

function resolveSubVisibilityKey(key: string): string {
  if (key.startsWith('_') && props.blockKey) {
    return `${props.blockKey}${key}`
  }
  return key
}

function subVisibilityVisible(key: string): boolean {
  const v = props.visibility?.[resolveSubVisibilityKey(key)]
  return v !== false && v !== '0' && v !== 0
}

function toggleSubVisibility(key: string) {
  emit('patch-visibility', resolveSubVisibilityKey(key), !subVisibilityVisible(key))
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

function groupHeading(field: ConfigField): string {
  if (field.type !== 'group') {
    return ''
  }
  if (field.headingKey) {
    const heading = str(field.headingKey).trim()
    if (heading !== '') {
      return heading
    }
  }
  return field.label
}

function groupIsOpen(field: ConfigField, index: number): boolean {
  if (field.type === 'group' && field.alwaysOpen) {
    return true
  }
  return !isCollapsed(index, field.type === 'group' ? field.label : String(index))
}

function groupAccentStyle(field: ConfigField): Record<string, string> | undefined {
  if (field.type !== 'group' || !field.accentColorKey) {
    return undefined
  }
  return {
    '--builder-group-accent': hexForPicker(str(field.accentColorKey), field.accentColorFallback ?? '#2563eb'),
  }
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

async function generateAiImage(fieldKey: string) {
  if (!props.generateImageUrl || generatingImageKey.value) {
    return
  }
  generatingImageKey.value = fieldKey
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const res = await fetch(props.generateImageUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        content: String(props.data.content ?? ''),
        page_title: props.pageTitle ?? '',
        company_name: props.companyName ?? '',
        company_id: props.companyId ?? null,
      }),
    })
    const json = (await res.json()) as { ok?: boolean; url?: string; message?: string }
    if (!res.ok || !json.ok || !json.url) {
      throw new Error(json.message ?? 'Afbeelding genereren mislukt')
    }
    const patch: Record<string, unknown> = { [fieldKey]: json.url }
    const alignment = String(props.data.alignment ?? '')
    if (fieldKey === 'image_url' && !['left', 'right'].includes(alignment)) {
      patch.alignment = 'left'
    }
    emit('patch', patch)
  } catch (e) {
    alert(e instanceof Error ? e.message : 'Afbeelding genereren mislukt')
  } finally {
    generatingImageKey.value = null
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

function isVideoFile(file: File): boolean {
  return file.type.startsWith('video/') || /\.(mp4|webm|ogg)$/i.test(file.name)
}

function fileFromDataTransfer(dataTransfer: DataTransfer | null, kind: 'image' | 'video'): File | null {
  if (!dataTransfer) {
    return null
  }
  const match = kind === 'video' ? isVideoFile : isImageFile
  const fromList = dataTransfer.files?.[0]
  if (fromList && match(fromList)) {
    return fromList
  }
  for (const item of Array.from(dataTransfer.items)) {
    if (item.kind !== 'file') {
      continue
    }
    const file = item.getAsFile()
    if (file && match(file)) {
      return file
    }
  }
  return null
}

function isUploadedVideoUrl(url: string): boolean {
  return /\.(mp4|webm|ogg)(\?|#|$)/i.test(url)
}

function isExternalVideoUrl(url: string): boolean {
  return /youtube\.com|youtu\.be|vimeo\.com/i.test(url)
}

function onMediaDragOver(uploadKey: string, event: DragEvent, kind: 'image' | 'video' = 'image') {
  event.preventDefault()
  const file = fileFromDataTransfer(event.dataTransfer ?? null, kind)
  if (file) {
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

function onMediaDrop(uploadKey: string, event: DragEvent, onFile: (file: File) => void, kind: 'image' | 'video' = 'image') {
  event.preventDefault()
  mediaDragOverKey.value = null
  const file = fileFromDataTransfer(event.dataTransfer ?? null, kind)
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
  <div
    class="builder-config-fields"
    :class="{ 'builder-config-fields--compact': blockKey === 'footer' || blockKey === 'copyright' }"
  >
    <template v-for="(field, fi) in fields" :key="`${field.type}-${fi}`">
      <template v-if="fieldVisible(field)">
      <div
        v-if="field.type === 'group'"
        class="builder-config-group"
        :class="{
          'builder-config-group--collapsed': !groupIsOpen(field, fi),
          'builder-config-group--hidden': field.subVisibilityKey && !subVisibilityVisible(field.subVisibilityKey),
          'builder-config-group--accent': !!field.accentColorKey,
          'builder-config-group--row': field.layout === 'row',
          'builder-config-group--row-wide': field.layout === 'row' && field.wideStart,
          'is-open': groupIsOpen(field, fi),
        }"
        :style="groupAccentStyle(field)"
      >
        <div
          class="builder-config-group__header"
          :class="{ 'builder-config-header--clickable': !field.alwaysOpen }"
          :role="field.alwaysOpen ? undefined : 'button'"
          :tabindex="field.alwaysOpen ? undefined : 0"
          :aria-expanded="field.alwaysOpen ? undefined : groupIsOpen(field, fi)"
          @click="onCollapsibleHeaderClick(field, fi)"
          @keydown.enter.prevent="onCollapsibleHeaderClick(field, fi)"
          @keydown.space.prevent="onCollapsibleHeaderClick(field, fi)"
        >
          <span class="builder-config-group__legend">{{ groupHeading(field) }}</span>
          <div class="builder-config-section__actions" @click.stop>
            <button
              v-if="field.subVisibilityKey"
              type="button"
              class="builder-icon-btn"
              :title="subVisibilityVisible(field.subVisibilityKey) ? 'Verbergen op website' : 'Tonen op website'"
              :aria-label="subVisibilityVisible(field.subVisibilityKey) ? 'Verbergen op website' : 'Tonen op website'"
              @click.stop="toggleSubVisibility(field.subVisibilityKey)"
            >
              <i
                class="ki-filled"
                :class="subVisibilityVisible(field.subVisibilityKey) ? 'ki-eye' : 'ki-eye-slash'"
              />
            </button>
            <button
              v-if="!field.alwaysOpen"
              type="button"
              class="builder-icon-btn"
              :title="isCollapsed(fi, field.label) ? 'Uitklappen' : 'Inklappen'"
              :aria-expanded="groupIsOpen(field, fi)"
              tabindex="-1"
              @click.stop="toggleCollapsed(fi, field.label)"
            >
              <i class="ki-filled ki-down builder-config-chevron" />
            </button>
          </div>
        </div>
        <div
          class="builder-config-accordion"
          :class="{ 'builder-config-accordion--static': field.alwaysOpen }"
          :inert="field.alwaysOpen ? undefined : !groupIsOpen(field, fi)"
          :aria-hidden="field.alwaysOpen ? undefined : (!groupIsOpen(field, fi) ? 'true' : 'false')"
        >
          <div class="builder-config-accordion__clip">
            <div class="builder-config-accordion__inner">
              <p v-if="field.hint" class="builder-field-hint builder-config-group__hint">{{ field.hint }}</p>
              <div
                v-if="field.label === 'Social media'"
                class="builder-social-preview-row"
                aria-hidden="true"
              >
                <BuilderFooterSocialIcon
                  v-for="network in ['facebook', 'instagram', 'x', 'linkedin', 'youtube', 'tiktok']"
                  :key="network"
                  :network="network"
                  :class="{
                    'builder-social-icon--inactive': !str(`social_${network}`).trim(),
                    'builder-social-icon--instagram': network === 'instagram',
                  }"
                />
              </div>
              <div class="builder-config-group__body">
                <BuilderConfigFields
                  :fields="field.fields"
                  :data="data"
                  :upload-url="uploadUrl"
                  :website-media-upload-url="websiteMediaUploadUrl"
                  :website-media-serve-base="websiteMediaServeBase"
                  :footer-logo-upload-url="footerLogoUploadUrl"
                  :footer-logo-fallback-url="footerLogoFallbackUrl"
                  :google-maps-api-key="googleMapsApiKey"
                  :google-maps-map-id="googleMapsMapId"
                  :postcode-lookup-url="postcodeLookupUrl"
                  :module-name="moduleName"
                  :visibility="visibility"
                  :collapse-prefix="childCollapsePrefix(fi, field.label)"
                  :block-key="blockKey"
                  :side-component-options="sideComponentOptions"
                  :email-template-options="emailTemplateOptions"
                  @patch="emit('patch', $event)"
                  @patch-visibility="(key, visible) => emit('patch-visibility', key, visible)"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div
        v-else-if="field.type === 'step-order'"
        class="builder-config-step-order"
        :class="{
          'builder-config-section--collapsed': isCollapsed(fi, field.label),
          'is-open': !isCollapsed(fi, field.label),
        }"
      >
        <div
          class="builder-config-section__header builder-config-header--clickable"
          role="button"
          tabindex="0"
          :aria-expanded="!isCollapsed(fi, field.label)"
          @click="toggleCollapsed(fi, field.label)"
          @keydown.enter.prevent="toggleCollapsed(fi, field.label)"
          @keydown.space.prevent="toggleCollapsed(fi, field.label)"
        >
          <span class="builder-config-item-list__title">{{ field.label }}</span>
          <div class="builder-config-section__actions" @click.stop>
            <button
              type="button"
              class="builder-icon-btn"
              :title="isCollapsed(fi, field.label) ? 'Uitklappen' : 'Inklappen'"
              :aria-expanded="!isCollapsed(fi, field.label)"
              tabindex="-1"
              @click.stop="toggleCollapsed(fi, field.label)"
            >
              <i class="ki-filled ki-down builder-config-chevron" />
            </button>
          </div>
        </div>
        <div
          class="builder-config-accordion"
          :inert="isCollapsed(fi, field.label)"
          :aria-hidden="isCollapsed(fi, field.label) ? 'true' : 'false'"
        >
          <div class="builder-config-accordion__clip">
            <div class="builder-config-accordion__inner">
              <div class="builder-config-step-order__grid">
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
          </div>
        </div>
      </div>

      <div v-else-if="field.type === 'pricing-packages-preview'" class="builder-config-field builder-config-field--pricing-preview min-w-0 max-w-full">
        <BuilderPricingPackagesPreview />
      </div>

      <div
        v-else-if="field.type === 'item-list'"
        class="builder-config-item-list"
        :class="{
          'builder-config-section--collapsed': !isFooterLinkList(field) && isCollapsed(fi, field.label),
          'builder-config-item-list--footer-links': isFooterLinkList(field),
          'builder-config-item-list--compact': isCompactItemList(field),
          'is-open': isFooterLinkList(field) || !isCollapsed(fi, field.label),
        }"
      >
        <div
          class="builder-config-section__header"
          :class="{ 'builder-config-header--clickable': !isFooterLinkList(field) }"
          :role="isFooterLinkList(field) ? undefined : 'button'"
          :tabindex="isFooterLinkList(field) ? undefined : 0"
          :aria-expanded="isFooterLinkList(field) ? undefined : !isCollapsed(fi, field.label)"
          @click="!isFooterLinkList(field) && toggleCollapsed(fi, field.label)"
          @keydown.enter.prevent="!isFooterLinkList(field) && toggleCollapsed(fi, field.label)"
          @keydown.space.prevent="!isFooterLinkList(field) && toggleCollapsed(fi, field.label)"
        >
          <span class="builder-config-item-list__title">{{ field.label }}</span>
          <div class="builder-config-section__actions" @click.stop>
            <button
              v-if="(field.maxItems ?? 99) > items(field.key).length"
              type="button"
              class="builder-icon-btn"
              title="Toevoegen"
              aria-label="Toevoegen"
              @click.stop="addItem(field.key, field.maxItems ?? 99, {})"
            >
              <i class="ki-filled ki-plus" aria-hidden="true" />
            </button>
            <button
              v-if="!isFooterLinkList(field)"
              type="button"
              class="builder-icon-btn"
              :title="isCollapsed(fi, field.label) ? 'Uitklappen' : 'Inklappen'"
              :aria-expanded="!isCollapsed(fi, field.label)"
              tabindex="-1"
              @click.stop="toggleCollapsed(fi, field.label)"
            >
              <i class="ki-filled ki-down builder-config-chevron" />
            </button>
          </div>
        </div>
        <div
          class="builder-config-accordion"
          :class="{ 'builder-config-accordion--static': isFooterLinkList(field) }"
          :inert="isFooterLinkList(field) ? undefined : isCollapsed(fi, field.label)"
          :aria-hidden="isFooterLinkList(field) ? undefined : (isCollapsed(fi, field.label) ? 'true' : 'false')"
        >
          <div class="builder-config-accordion__clip">
            <div class="builder-config-accordion__inner">
        <div
          v-for="(item, index) in ensureItemCount(field.key, field.minItems ?? 0, field.maxItems ?? 99, {})"
          :key="`${field.key}-${index}`"
          class="builder-config-item"
          :class="{
            'builder-config-item--footer-link': isFooterLinkList(field),
            'builder-config-item--compact': isCompactItemList(field),
          }"
        >
          <template v-if="isFooterLinkList(field)">
            <label
              v-for="(sub, si) in field.fields"
              :key="`${field.key}-${index}-${si}`"
              class="builder-config-footer-link-field"
            >
              <span class="builder-config-footer-link-field__label">{{ sub.label }}</span>
              <input
                v-if="sub.type === 'text'"
                class="kt-input kt-input-sm"
                :value="itemFieldDisplay(item, sub.key)"
                :placeholder="sub.placeholder"
                @input="patchItemField(field.key, index, sub.key, ($event.target as HTMLInputElement).value)"
              />
            </label>
            <button
              v-if="items(field.key).length > (field.minItems ?? 0)"
              type="button"
              class="builder-config-item__remove"
              title="Verwijderen"
              aria-label="Verwijderen"
              @click="removeItem(field.key, index, field.minItems ?? 0)"
            >
              <i class="ki-filled ki-trash" />
            </button>
          </template>
          <template v-else-if="isCompactItemList(field)">
            <input
              class="kt-input"
              :value="itemFieldDisplay(item, compactItemTextKey(field))"
              :placeholder="compactItemPlaceholder(field)"
              :aria-label="`Punt ${index + 1}`"
              @input="patchItemField(field.key, index, compactItemTextKey(field), ($event.target as HTMLInputElement).value)"
            />
            <button
              v-if="items(field.key).length > (field.minItems ?? 0)"
              type="button"
              class="builder-config-item__remove"
              title="Verwijderen"
              aria-label="Verwijderen"
              @click="removeItem(field.key, index, field.minItems ?? 0)"
            >
              <i class="ki-filled ki-trash" />
            </button>
          </template>
          <template v-else>
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
              <span v-if="sub.label">{{ sub.label }}</span>
              <input
                class="kt-input"
                :value="itemFieldDisplay(item, sub.key)"
                :placeholder="sub.placeholder"
                @input="patchItemField(field.key, index, sub.key, ($event.target as HTMLInputElement).value)"
              />
            </label>
            <div v-else-if="sub.type === 'heroicon'" class="builder-field">
              <BuilderHeroiconPicker
                :label="sub.label"
                :model-value="itemFieldDisplay(item, sub.key)"
                @update:model-value="patchItemField(field.key, index, sub.key, $event)"
              />
            </div>
            <label v-else-if="sub.type === 'textarea' && sub.key === 'features_text'" class="builder-field">
              <span>{{ sub.label }}</span>
              <textarea
                class="kt-input"
                :class="{ 'font-mono text-sm': sub.mono }"
                :rows="sub.rows ?? 3"
                :value="itemFieldDisplay(item, sub.key)"
                @input="patchItemField(field.key, index, sub.key, ($event.target as HTMLTextAreaElement).value)"
              />
            </label>
            <BuilderWysiwygField
              v-else-if="sub.type === 'textarea' || sub.type === 'wysiwyg'"
              :editor-key="wysiwygEditorKey(`${field.key}-${sub.key}`, index)"
              :label="sub.label"
              :model-value="itemFieldDisplay(item, sub.key)"
              :placeholder="sub.placeholder"
              @update:model-value="patchItemField(field.key, index, sub.key, $event)"
            />
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
            <div v-else-if="sub.type === 'image'" class="builder-field builder-field--media">
              <span>{{ sub.label }}</span>
              <div class="builder-media-image-row">
                <div v-if="itemFieldDisplay(item, sub.key)" class="builder-hero-image-preview-wrap shrink-0 flex flex-col items-center">
                  <img
                    :src="itemFieldDisplay(item, sub.key)"
                    alt=""
                    class="builder-image-preview builder-image-preview--clickable builder-media-image-row__preview"
                    role="button"
                    tabindex="0"
                    title="Klik om te vergroten"
                    @click="openImagePreview(itemFieldDisplay(item, sub.key))"
                    @keydown.enter.prevent="openImagePreview(itemFieldDisplay(item, sub.key))"
                  />
                  <button
                    type="button"
                    class="builder-hero-image-remove kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1"
                    title="Afbeelding verwijderen"
                    aria-label="Afbeelding verwijderen"
                    @click="patchItemField(field.key, index, sub.key, '')"
                  >
                    <i class="ki-filled ki-trash" aria-hidden="true" />
                  </button>
                </div>
                <label
                  class="builder-media-upload-area"
                  :class="{
                    'builder-media-upload-area--dragover': mediaDragOverKey === `${field.key}.${index}.${sub.key}`,
                    'builder-media-upload-area--busy': uploadingKey === `${field.key}.${index}.${sub.key}`,
                  }"
                  @dragover="onMediaDragOver(`${field.key}.${index}.${sub.key}`, $event)"
                  @dragleave="onMediaDragLeave(`${field.key}.${index}.${sub.key}`, $event)"
                  @drop="onMediaDrop(`${field.key}.${index}.${sub.key}`, $event, (file) => uploadImage(`${field.key}.${index}.${sub.key}`, file, (url) => patchItemField(field.key, index, sub.key, url)))"
                >
                  <span class="builder-media-upload-area__title">
                    {{ uploadingKey === `${field.key}.${index}.${sub.key}` ? 'Uploaden…' : 'Klik of sleep afbeelding' }}
                  </span>
                  <span class="builder-media-upload-area__hint">JPG, PNG, WebP (max. 5MB)</span>
                  <input
                    type="file"
                    class="hero-image-file-input hidden"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    @change="onItemImagePick(field.key, index, sub.key, $event)"
                  />
                </label>
              </div>
            </div>
            <div v-else-if="sub.type === 'video'" class="builder-field builder-field--media">
              <span>{{ sub.label }}</span>
              <p v-if="sub.hint" class="builder-field-hint">{{ sub.hint }}</p>
              <div class="builder-media-image-row">
                <video
                  v-if="isUploadedVideoUrl(itemFieldDisplay(item, sub.key))"
                  class="builder-image-preview builder-media-image-row__preview"
                  :src="itemFieldDisplay(item, sub.key)"
                  controls
                  muted
                />
                <div v-else-if="itemFieldDisplay(item, sub.key)" class="builder-media-image-row__placeholder">
                  <i class="ki-filled ki-youtube" />
                  <span>{{ isExternalVideoUrl(itemFieldDisplay(item, sub.key)) ? 'Externe video' : 'Video-link' }}</span>
                </div>
                <label
                  class="builder-media-upload-area"
                  :class="{
                    'builder-media-upload-area--dragover': mediaDragOverKey === `${field.key}.${index}.${sub.key}`,
                    'builder-media-upload-area--busy': uploadingKey === `${field.key}.${index}.${sub.key}`,
                  }"
                  @dragover="onMediaDragOver(`${field.key}.${index}.${sub.key}`, $event, 'video')"
                  @dragleave="onMediaDragLeave(`${field.key}.${index}.${sub.key}`, $event)"
                  @drop="onMediaDrop(`${field.key}.${index}.${sub.key}`, $event, (file) => uploadImage(`${field.key}.${index}.${sub.key}`, file, (url) => patchItemField(field.key, index, sub.key, url)), 'video')"
                >
                  <span class="builder-media-upload-area__title">
                    {{ uploadingKey === `${field.key}.${index}.${sub.key}` ? 'Uploaden…' : 'Klik of sleep video' }}
                  </span>
                  <span class="builder-media-upload-area__hint">MP4, WebM (max. 15MB)</span>
                  <input
                    type="file"
                    class="hero-image-file-input hidden"
                    accept="video/mp4,video/webm,video/ogg"
                    @change="onItemImagePick(field.key, index, sub.key, $event)"
                  />
                </label>
              </div>
              <input
                class="kt-input mt-2"
                :value="itemFieldDisplay(item, sub.key)"
                placeholder="Of plak een YouTube- of Vimeo-link"
                @input="patchItemField(field.key, index, sub.key, ($event.target as HTMLInputElement).value)"
              />
            </div>
            <div v-else-if="sub.type === 'website-media-image'" class="builder-field builder-field--media">
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
                <label
                  class="builder-media-upload-area"
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
          </template>
        </div>
            </div>
          </div>
        </div>
      </div>

      <div
        v-else-if="field.type === 'footer-map'"
        class="builder-config-group"
        :class="{
          'builder-config-section--collapsed': isCollapsed(fi, field.label),
          'is-open': !isCollapsed(fi, field.label),
        }"
      >
        <div
          class="builder-config-group__header builder-config-header--clickable"
          role="button"
          tabindex="0"
          :aria-expanded="!isCollapsed(fi, field.label)"
          @click="toggleCollapsed(fi, field.label)"
          @keydown.enter.prevent="toggleCollapsed(fi, field.label)"
          @keydown.space.prevent="toggleCollapsed(fi, field.label)"
        >
          <span class="builder-config-group__legend">{{ field.label }}</span>
          <div class="builder-config-section__actions" @click.stop>
            <button
              v-if="field.subVisibilityKey"
              type="button"
              class="builder-icon-btn"
              :title="subVisibilityVisible(field.subVisibilityKey) ? 'Verbergen op website' : 'Tonen op website'"
              @click.stop="toggleSubVisibility(field.subVisibilityKey)"
            >
              <i
                class="ki-filled"
                :class="subVisibilityVisible(field.subVisibilityKey) ? 'ki-eye' : 'ki-eye-slash'"
              />
            </button>
            <button
              type="button"
              class="builder-icon-btn"
              :title="isCollapsed(fi, field.label) ? 'Uitklappen' : 'Inklappen'"
              tabindex="-1"
              @click.stop="toggleCollapsed(fi, field.label)"
            >
              <i class="ki-filled ki-down builder-config-chevron" />
            </button>
          </div>
        </div>
        <div
          class="builder-config-accordion"
          :inert="isCollapsed(fi, field.label)"
          :aria-hidden="isCollapsed(fi, field.label) ? 'true' : 'false'"
        >
          <div class="builder-config-accordion__clip">
            <div class="builder-config-accordion__inner">
              <div class="builder-config-group__body">
                <BuilderFooterMapField
                  v-if="postcodeLookupUrl && googleMapsApiKey !== undefined"
                  :data="data"
                  :label="field.label"
                  :google-maps-api-key="googleMapsApiKey ?? ''"
                  :google-maps-map-id="googleMapsMapId ?? ''"
                  :postcode-lookup-url="postcodeLookupUrl"
                  @patch="emit('patch', $event)"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <BuilderFooterLogoField
        v-else-if="field.type === 'footer-logo' && footerLogoUploadUrl"
        :label="field.label"
        :model-value="str(field.key)"
        :upload-url="footerLogoUploadUrl"
        :module-name="moduleName"
        :fallback-logo-url="footerLogoFallbackUrl"
        @update:model-value="updateField(field.key, $event)"
      />

      <BuilderWysiwygField
        v-else-if="field.type === 'wysiwyg'"
        :editor-key="wysiwygEditorKey(field.key)"
        :label="field.label"
        :model-value="str(field.key)"
        :placeholder="field.placeholder"
        @update:model-value="updateField(field.key, $event)"
      />

      <label v-else-if="field.type === 'dynamic-select'" class="builder-field">
        <span>{{ field.label }}</span>
        <select
          class="kt-input"
          :value="field.key === 'side_template_id' ? str(field.key) : str(field.key, '')"
          @change="updateDynamicSelect(field, ($event.target as HTMLSelectElement).value)"
        >
          <option v-for="opt in dynamicSelectOptions(field)" :key="`${field.key}-${opt.value}`" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
      </label>

      <label
        v-else-if="field.type === 'text'"
        class="builder-field"
        :class="{
          'builder-field--social': isFooterSocialField(field.key),
          'builder-field--span-2': field.colSpan === 2,
          'builder-field--span-3': field.colSpan === 3,
        }"
      >
        <span class="builder-field__label-row">
          <BuilderFooterSocialIcon
            v-if="isFooterSocialField(field.key)"
            :network="socialNetworkFromFieldKey(field.key)!"
            :class="{
              'builder-social-icon--inactive': !str(field.key).trim(),
              'builder-social-icon--instagram': socialNetworkFromFieldKey(field.key) === 'instagram',
            }"
          />
          <span>{{ field.label }}</span>
        </span>
        <input
          class="kt-input"
          :value="str(field.key)"
          :placeholder="field.placeholder"
          @input="updateField(field.key, ($event.target as HTMLInputElement).value)"
        />
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
      </label>

      <div v-else-if="field.type === 'heroicon'" class="builder-field">
        <BuilderHeroiconPicker
          :label="field.label"
          :model-value="str(field.key)"
          @update:model-value="updateField(field.key, $event)"
        />
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
      </div>

      <BuilderWysiwygField
        v-else-if="field.type === 'textarea' && field.key !== 'features_text'"
        :editor-key="wysiwygEditorKey(field.key)"
        :label="field.label"
        :model-value="str(field.key)"
        :placeholder="field.placeholder"
        @update:model-value="updateField(field.key, $event)"
      />

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
          :value="str(field.key, field.defaultValue ?? field.options[0]?.value ?? '')"
          @change="updateField(field.key, ($event.target as HTMLSelectElement).value)"
        >
          <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
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

      <label
        v-else-if="field.type === 'number'"
        class="builder-field"
        :class="{ 'builder-field--digits': field.inputWidth === 'digits' }"
      >
        <span>{{ field.label }}</span>
        <input
          type="number"
          class="kt-input"
          :min="field.min"
          :max="field.max"
          :step="field.step ?? 1"
          :value="num(field.key, field.min ?? 0)"
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

      <div
        v-else-if="field.type === 'color'"
        class="builder-field"
        :class="{
          'builder-field--span-2': field.colSpan === 2,
          'builder-field--span-3': field.colSpan === 3,
        }"
      >
        <span>{{ field.label }}</span>
        <div class="builder-color-row">
          <input
            type="color"
            class="builder-color-picker"
            :value="hexForPicker(str(field.key), field.defaultValue ?? '#2563eb')"
            @input="updateField(field.key, ($event.target as HTMLInputElement).value)"
          />
          <input
            class="kt-input font-mono text-sm"
            :value="str(field.key, field.defaultValue ?? '')"
            :placeholder="field.defaultValue ?? '#hex (leeg = standaard)'"
            @input="updateField(field.key, ($event.target as HTMLInputElement).value)"
          />
        </div>
        <div v-if="field.presets?.length" class="builder-color-swatches">
          <button
            v-for="preset in field.presets"
            :key="preset.hex"
            type="button"
            class="builder-color-swatch"
            :class="{
              'is-active': hexForPicker(str(field.key), field.defaultValue ?? '#2563eb').toLowerCase() === preset.hex.toLowerCase(),
            }"
            :style="{ background: preset.hex }"
            :title="preset.label"
            :aria-label="preset.label"
            @click="updateField(field.key, preset.hex)"
          />
        </div>
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
      </div>

      <div v-else-if="field.type === 'image'" class="builder-field builder-field--media">
        <span>{{ field.label }}</span>
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
        <div class="builder-media-image-row">
          <div v-if="str(field.key)" class="builder-hero-image-preview-wrap shrink-0 flex flex-col items-center">
            <img
              :src="str(field.key)"
              alt=""
              class="builder-image-preview builder-image-preview--clickable builder-media-image-row__preview"
              role="button"
              tabindex="0"
              title="Klik om te vergroten"
              @click="openImagePreview(str(field.key))"
              @keydown.enter.prevent="openImagePreview(str(field.key))"
            />
            <button
              type="button"
              class="builder-hero-image-remove kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1"
              title="Afbeelding verwijderen"
              aria-label="Afbeelding verwijderen"
              @click="updateField(field.key, '')"
            >
              <i class="ki-filled ki-trash" aria-hidden="true" />
            </button>
          </div>
          <label
            class="builder-media-upload-area"
            :class="{
              'builder-media-upload-area--dragover': mediaDragOverKey === field.key,
              'builder-media-upload-area--busy': uploadingKey === field.key,
            }"
            @dragover="onMediaDragOver(field.key, $event)"
            @dragleave="onMediaDragLeave(field.key, $event)"
            @drop="onMediaDrop(field.key, $event, (file) => uploadImage(field.key, file))"
          >
            <span class="builder-media-upload-area__title">
              {{ uploadingKey === field.key ? 'Uploaden…' : 'Klik of sleep afbeelding' }}
            </span>
            <span class="builder-media-upload-area__hint">JPG, PNG, WebP (max. 5MB)</span>
            <input
              type="file"
              class="hero-image-file-input hidden"
              accept="image/jpeg,image/png,image/webp,image/gif"
              @change="onImagePick(field.key, $event)"
            />
          </label>
          <button
            v-if="field.generateImage && generateImageUrl"
            type="button"
            class="kt-btn kt-btn-outline kt-btn-sm shrink-0"
            :disabled="generatingImageKey === field.key"
            @click="generateAiImage(field.key)"
          >
            <i class="ki-filled ki-magic me-1" aria-hidden="true" />
            {{ generatingImageKey === field.key ? 'Genereren…' : 'Genereer afbeelding' }}
          </button>
        </div>
      </div>

      <div v-else-if="field.type === 'video'" class="builder-field builder-field--media">
        <span>{{ field.label }}</span>
        <p v-if="field.hint" class="builder-field-hint">{{ field.hint }}</p>
        <div class="builder-media-image-row">
          <video
            v-if="isUploadedVideoUrl(str(field.key))"
            class="builder-image-preview builder-media-image-row__preview"
            :src="str(field.key)"
            controls
            muted
          />
          <div v-else-if="str(field.key)" class="builder-media-image-row__placeholder">
            <i class="ki-filled ki-youtube" />
            <span>{{ isExternalVideoUrl(str(field.key)) ? 'Externe video' : 'Video-link' }}</span>
          </div>
          <label
            class="builder-media-upload-area"
            :class="{
              'builder-media-upload-area--dragover': mediaDragOverKey === field.key,
              'builder-media-upload-area--busy': uploadingKey === field.key,
            }"
            @dragover="onMediaDragOver(field.key, $event, 'video')"
            @dragleave="onMediaDragLeave(field.key, $event)"
            @drop="onMediaDrop(field.key, $event, (file) => uploadImage(field.key, file), 'video')"
          >
            <span class="builder-media-upload-area__title">
              {{ uploadingKey === field.key ? 'Uploaden…' : 'Klik of sleep video' }}
            </span>
            <span class="builder-media-upload-area__hint">MP4, WebM (max. 15MB)</span>
            <input
              type="file"
              class="hero-image-file-input hidden"
              accept="video/mp4,video/webm,video/ogg"
              @change="onImagePick(field.key, $event)"
            />
          </label>
        </div>
        <div v-if="str(field.key)" class="mt-2">
          <button
            type="button"
            class="kt-btn kt-btn-xs kt-btn-ghost text-destructive"
            @click="updateField(field.key, '')"
          >
            Video verwijderen
          </button>
        </div>
        <input
          class="kt-input mt-2"
          :value="str(field.key)"
          placeholder="Of plak een YouTube- of Vimeo-link"
          @input="updateField(field.key, ($event.target as HTMLInputElement).value)"
        />
      </div>

      <div v-else-if="field.type === 'website-media-image'" class="builder-field builder-field--media">
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
          <label
            class="builder-media-upload-area"
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

      <label v-else-if="field.type === 'checkbox'" class="builder-checkbox" :class="{ 'builder-checkbox--switch': field.control === 'switch' }">
        <input
          type="checkbox"
          :class="field.control === 'switch' ? 'kt-switch kt-switch-sm shrink-0' : 'kt-checkbox'"
          :role="field.control === 'switch' ? 'switch' : undefined"
          :checked="bool(field.key)"
          @change="updateField(field.key, ($event.target as HTMLInputElement).checked)"
        />
        <span>{{ field.label }}</span>
      </label>
      <p v-if="field.type === 'checkbox' && field.hint" class="builder-field-hint">{{ field.hint }}</p>
      </template>
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
  gap: 0;
  min-width: 0;
  max-width: 100%;
  box-sizing: border-box;
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

.builder-config-fields--compact {
  gap: 0.55rem;
}

.builder-config-fields--compact .builder-config-group,
.builder-config-fields--compact .builder-config-item-list,
.builder-config-fields--compact .builder-config-step-order {
  padding: 0.5rem 0.6rem;
  gap: 0;
}

.builder-config-fields--compact .builder-config-group__body {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  column-gap: 0.5rem;
  row-gap: 0.45rem;
  align-items: start;
}

.builder-config-fields--compact .builder-config-group__body > .builder-config-fields {
  grid-column: 1 / -1;
  min-width: 0;
}

.builder-config-fields--compact .builder-config-group__body > .builder-field:has(.builder-footer-logo),
.builder-config-fields--compact .builder-field--footer-logo,
.builder-config-fields--compact .builder-config-group__body > .builder-field--media,
.builder-config-fields--compact .builder-config-item .builder-field--media,
.builder-config-fields--compact .builder-config-group__body > .builder-field--wysiwyg,
.builder-config-fields--compact .builder-config-group__body > .builder-config-item-list,
.builder-config-fields--compact .builder-config-group__body > .builder-config-group,
.builder-config-fields--compact .builder-config-group__body > .builder-footer-map,
.builder-config-fields--compact .builder-config-group__body > .builder-field:last-child,
.builder-config-fields--compact .builder-config-group__body > .builder-social-preview-row,
.builder-config-fields--compact .builder-field--wysiwyg,
.builder-config-fields--compact .builder-config-item-list,
.builder-config-fields--compact > .builder-config-group,
.builder-config-fields--compact > .builder-field--wysiwyg,
.builder-config-fields--compact > .builder-field--media,
.builder-config-fields--compact > .builder-config-item-list {
  grid-column: 1 / -1;
}

.builder-config-fields--compact .builder-field {
  gap: 0.25rem;
}

.builder-config-fields--compact .builder-field > span,
.builder-config-fields--compact .builder-config-group__legend,
.builder-config-fields--compact .builder-config-item-list__title {
  font-size: 0.75rem;
}

.builder-config-fields--compact .builder-config-item {
  padding: 0.5rem 0.55rem;
  gap: 0.45rem;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  column-gap: 0.45rem;
  row-gap: 0.35rem;
}

.builder-config-fields--compact .builder-config-item__header {
  grid-column: 1 / -1;
}

.builder-config-fields--compact .builder-config-item--compact {
  display: flex;
  grid-template-columns: none;
  flex-direction: row;
  align-items: center;
}

.builder-config-fields--compact .builder-field-hint {
  font-size: 0.6875rem;
  margin-top: 0.1rem;
}

.builder-config-fields--compact .builder-checkbox {
  font-size: 0.8125rem;
}

.builder-config-group {
  border: 1px solid var(--border);
  border-radius: 0.65rem;
  padding: 0.65rem 0.75rem;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.builder-config-group--collapsed {
  padding-bottom: 0.65rem;
}

.builder-config-group--collapsed .builder-config-group__header.builder-config-header--clickable {
  margin: -0.65rem -0.75rem;
  padding: 0.65rem 0.75rem;
}

.builder-config-group--accent.builder-config-group--collapsed .builder-config-group__header.builder-config-header--clickable {
  margin: -0.65rem -0.75rem;
  padding: 0.7rem 0.85rem;
}

.builder-config-section--collapsed .builder-config-section__header.builder-config-header--clickable,
.builder-config-section--collapsed .builder-config-group__header.builder-config-header--clickable {
  margin: -0.65rem -0.75rem;
  padding: 0.65rem 0.75rem;
}

.builder-config-fields--compact .builder-config-group--collapsed .builder-config-group__header.builder-config-header--clickable,
.builder-config-fields--compact .builder-config-section--collapsed .builder-config-section__header.builder-config-header--clickable,
.builder-config-fields--compact .builder-config-section--collapsed .builder-config-group__header.builder-config-header--clickable {
  margin: -0.5rem -0.6rem;
  padding: 0.5rem 0.6rem;
}

.builder-config-group--hidden {
  opacity: 0.72;
}

.builder-config-group--accent {
  border-color: color-mix(in srgb, var(--builder-group-accent, var(--border)) 42%, var(--border));
  overflow: hidden;
}

.builder-config-group--accent .builder-config-group__header {
  margin: -0.65rem -0.75rem 0;
  padding: 0.7rem 0.85rem;
  background: color-mix(in srgb, var(--builder-group-accent, transparent) 12%, transparent);
}

.builder-config-group--accent .builder-config-group__legend {
  color: var(--builder-group-accent, var(--foreground));
  font-size: 0.95rem;
  font-weight: 700;
}

.builder-config-group__header,
.builder-config-section__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  min-height: 1.75rem;
}

.builder-config-header--clickable {
  cursor: pointer;
  user-select: none;
}

.builder-config-header--clickable:focus-visible {
  outline: 2px solid color-mix(in srgb, var(--theme-primary, #2563eb) 55%, transparent);
  outline-offset: 2px;
  border-radius: 0.45rem;
}

.builder-config-group__legend {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--foreground);
  min-width: 0;
  flex: 1;
}

.builder-config-group__hint {
  margin: 0;
}

.builder-config-group__body {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.builder-config-accordion {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 0.45s cubic-bezier(0.22, 1, 0.36, 1);
}

.is-open > .builder-config-accordion {
  grid-template-rows: 1fr;
}

.builder-config-accordion--static {
  grid-template-rows: 1fr;
  transition: none;
}

.builder-config-accordion__clip {
  overflow: hidden;
  min-height: 0;
  pointer-events: none;
}

.is-open > .builder-config-accordion > .builder-config-accordion__clip,
.builder-config-accordion--static > .builder-config-accordion__clip {
  pointer-events: auto;
}

.builder-config-accordion__inner {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  padding-top: 0.65rem;
  opacity: 0;
  transform: translateY(-8px);
  transition: opacity 0.32s ease, transform 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}

.is-open > .builder-config-accordion > .builder-config-accordion__clip > .builder-config-accordion__inner {
  opacity: 1;
  transform: none;
  transition-delay: 0.06s;
}

.builder-config-accordion--static > .builder-config-accordion__clip > .builder-config-accordion__inner {
  opacity: 1;
  transform: none;
  transition: none;
}

.builder-config-fields--compact .builder-config-accordion__inner {
  gap: 0.45rem;
  padding-top: 0.45rem;
}

.builder-config-item-list--compact > .builder-config-accordion > .builder-config-accordion__clip > .builder-config-accordion__inner {
  gap: 0.45rem;
  padding-top: 0.45rem;
}

.builder-config-chevron {
  display: inline-flex;
  transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.is-open > .builder-config-group__header .builder-config-chevron,
.is-open > .builder-config-section__header .builder-config-chevron {
  transform: rotate(180deg);
}

@media (prefers-reduced-motion: reduce) {
  .builder-config-accordion,
  .builder-config-accordion__inner,
  .builder-config-chevron {
    transition: none;
  }
}

.builder-config-group--row {
  padding: 0.5rem 0.65rem 0.6rem;
  gap: 0;
  background: color-mix(in srgb, var(--muted) 28%, transparent);
  border-color: color-mix(in srgb, var(--border) 85%, transparent);
}

.builder-config-group--row > .builder-config-group__header {
  min-height: auto;
}

.builder-config-group--row > .builder-config-group__header .builder-config-group__legend {
  font-size: 0.6875rem;
  font-weight: 650;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--muted-foreground);
}

.builder-config-group--row > .builder-config-accordion > .builder-config-accordion__clip > .builder-config-accordion__inner {
  gap: 0.35rem;
  padding-top: 0.35rem;
}

.builder-config-group--row .builder-config-group__body {
  gap: 0;
}

.builder-config-group--row .builder-config-group__body :deep(.builder-config-fields) {
  display: grid !important;
  grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
  gap: 0.55rem 0.85rem;
  align-items: start;
}

.builder-config-group--row .builder-config-group__body :deep(.builder-config-fields > .builder-field) {
  min-width: 0;
  max-width: none;
}

.builder-config-group--row .builder-config-group__body :deep(.builder-config-fields > .builder-checkbox) {
  grid-column: 1 / -1;
  margin-top: 0.1rem;
}

.builder-config-group--row-wide .builder-config-group__body :deep(.builder-config-fields) {
  grid-template-columns: minmax(16rem, 2.4fr) minmax(10.5rem, 1fr) minmax(10.5rem, 1fr) !important;
}

.builder-config-group--row .builder-config-group__body :deep(.builder-field--span-2) {
  grid-column: span 2;
}

.builder-config-group--row .builder-config-group__body :deep(.builder-field--span-3) {
  grid-column: 1 / -1;
}

.builder-config-group--row :deep(.builder-color-row .kt-input) {
  max-width: 7.25rem;
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
  flex: 1;
}

.builder-config-item {
  border: 1px solid var(--border);
  border-radius: 0.65rem;
  padding: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  background: color-mix(in srgb, var(--muted) 12%, transparent);
  min-width: 0;
  max-width: 100%;
  box-sizing: border-box;
}

.builder-config-item--compact {
  flex-direction: row;
  align-items: center;
  gap: 0.45rem;
  padding: 0.35rem 0.45rem;
}

.builder-config-item--compact .kt-input {
  flex: 1;
  min-width: 0;
}

.builder-config-item-list--compact {
  gap: 0;
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

.builder-config-item-list--footer-links {
  gap: 0.35rem;
}

.builder-config-item--footer-link {
  display: grid;
  grid-template-columns: minmax(5.5rem, 0.85fr) minmax(7rem, 1.35fr) auto;
  gap: 0.4rem;
  align-items: end;
  padding: 0.35rem 0.4rem;
}

.builder-config-footer-link-field {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}

.builder-config-footer-link-field__label {
  font-size: 0.6875rem;
  font-weight: 500;
  color: var(--muted-foreground);
}

.builder-config-item--footer-link .builder-config-item__remove {
  align-self: end;
  margin-bottom: 0.05rem;
}

.builder-config-fields--compact .builder-config-item--footer-link {
  display: grid;
  grid-template-columns: minmax(5.5rem, 0.85fr) minmax(7rem, 1.35fr) auto;
  padding: 0.3rem 0.35rem;
}

.builder-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.875rem;
  min-width: 0;
  max-width: 100%;
}

.builder-field--media {
  width: 100%;
  grid-column: 1 / -1;
}

.builder-field > span {
  color: var(--muted-foreground);
  font-weight: 500;
}

.builder-field__label-row {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-width: 0;
}

.builder-field--social .builder-social-icon--inactive {
  opacity: 0.38;
  filter: grayscale(0.35);
}

.builder-field--social .builder-social-icon--inactive.builder-social-icon--instagram {
  filter: none;
}

.builder-social-preview-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.45rem;
  padding: 0.15rem 0 0.35rem;
}

.builder-social-preview-row .builder-social-icon {
  width: 1.25rem;
  height: 1.25rem;
}

.builder-social-preview-row .builder-social-icon--inactive {
  opacity: 0.32;
  filter: grayscale(0.4);
}

.builder-social-preview-row .builder-social-icon--inactive.builder-social-icon--instagram {
  filter: none;
}

.builder-field select.kt-input {
  align-self: flex-start;
  width: auto;
  max-width: 100%;
  min-width: 4.5rem;
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

.builder-field--digits .kt-input {
  width: 4.75rem;
  max-width: 4.75rem;
}

.builder-color-swatches {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.45rem;
  margin-top: 0.55rem;
}

.builder-color-swatch {
  width: 2rem;
  height: 2rem;
  border-radius: 999px;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.2);
  cursor: pointer;
  padding: 0;
}

.builder-color-swatch.is-active {
  box-shadow: 0 0 0 2px #2563eb;
}

.builder-hero-image-remove {
  flex-shrink: 0;
}

.builder-hero-image-preview-wrap {
  align-self: center;
}

.builder-hero-image-remove:hover {
  background: color-mix(in srgb, #dc2626 10%, transparent);
}

.builder-image-field {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.builder-media-image-row {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 0.5rem;
  width: 100%;
  min-width: 0;
  max-width: 100%;
  box-sizing: border-box;
}

.builder-media-image-row__preview {
  flex: 0 1 auto;
  align-self: flex-start;
  max-width: 100%;
}

.builder-media-image-row__placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  min-height: 4.5rem;
  max-height: 10rem;
  border: 1px dashed var(--border);
  border-radius: 0.4rem;
  background: color-mix(in srgb, var(--muted) 22%, transparent);
  color: var(--muted-foreground);
  font-size: 0.6875rem;
  box-sizing: border-box;
}

.builder-media-image-row__placeholder i {
  font-size: 1.1rem;
  opacity: 0.7;
}

.builder-media-upload-area {
  flex: 1 1 100%;
  align-self: stretch;
  display: flex;
  width: 100%;
  max-width: none;
  min-width: 0;
  min-height: 4.5rem;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.15rem;
  padding: 0.5rem 0.65rem;
  border: 1px dashed var(--border);
  border-radius: 0.5rem;
  background: color-mix(in srgb, var(--muted) 28%, transparent);
  cursor: pointer;
  text-align: center;
  box-sizing: border-box;
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
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--foreground);
  line-height: 1.25;
}

.builder-media-upload-area__hint {
  font-size: 0.6875rem;
  color: var(--muted-foreground);
  line-height: 1.25;
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
  z-index: 11050;
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

.builder-checkbox--switch {
  gap: 0.75rem;
  min-height: 2rem;
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

.builder-config-field--pricing-preview {
  width: 100%;
  min-width: 0;
  max-width: 100%;
  overflow-x: clip;
}
</style>
