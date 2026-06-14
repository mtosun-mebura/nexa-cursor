<script setup lang="ts">
import { inject, provide, ref, type Ref } from 'vue'
import BuilderConfigFields from './BuilderConfigFields.vue'
import type { ConfigField } from './section-config-schemas'
import { buildPatchForPath, getByPath } from './nested-data'

const EXPAND_KEY = Symbol('builderConfigExpand')

const props = defineProps<{
  fields: ConfigField[]
  data: Record<string, unknown>
  uploadUrl: string
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
  const v = item[fieldKey]
  if (v === null || v === undefined) return ''
  return String(v)
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
              class="kt-btn kt-btn-xs kt-btn-outline"
              @click="addItem(field.key, field.maxItems ?? 99, {})"
            >
              + Toevoegen
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
          class="builder-image-preview"
        />
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
  border: 0;
  background: transparent;
  color: var(--muted-foreground);
  padding: 0.15rem;
  border-radius: 0.35rem;
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

.builder-image-preview {
  max-width: 100%;
  max-height: 5rem;
  object-fit: cover;
  border-radius: 0.4rem;
  border: 1px solid var(--border);
}

.builder-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  cursor: pointer;
}
</style>
