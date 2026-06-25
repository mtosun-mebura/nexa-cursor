<script setup lang="ts">
import { computed } from 'vue'
import BuilderConfigFields from './BuilderConfigFields.vue'
import { schemaForComponent } from './component-config-schemas'
import { baseTypeFromKey } from './palette-meta'
import { deepMerge } from './nested-data'
import { schemaForBaseType } from './section-config-schemas'
import { emailTemplateSelectOptions, sideComponentSelectOptions } from './text-block-field-options'
import type { CanvasBlock, ComponentCatalogItem, EmailTemplateOption } from './types'

const props = defineProps<{
  blockKey: string
  label: string
  isComponent: boolean
  data: Record<string, unknown>
  visible: boolean
  componentInfo: ComponentCatalogItem | null
  componentDefaults: Record<string, Record<string, unknown>>
  uploadUrl: string
  websiteMediaUploadUrl?: string
  websiteMediaServeBase?: string
  layout?: 'compact' | 'expanded'
  canvasBlocks?: CanvasBlock[]
  emailTemplates?: EmailTemplateOption[]
}>()

const emit = defineEmits<{
  patch: [Record<string, unknown>]
  'update:visible': [boolean]
}>()

const baseType = computed(() => baseTypeFromKey(props.blockKey))

const schemaFields = computed(() => {
  if (props.isComponent) {
    return schemaForComponent(props.blockKey)
  }
  return schemaForBaseType(baseType.value)
})

const displayData = computed(() => {
  if (!props.isComponent) {
    return props.data
  }
  const defaults = props.componentDefaults[props.blockKey]
    ?? props.componentDefaults[props.blockKey.toLowerCase()]
    ?? {}
  return deepMerge(defaults, props.data)
})

const extraScalarFields = computed(() => {
  if (schemaFields.value.length > 0) {
    return []
  }
  return Object.entries(displayData.value)
    .filter(([key, value]) => {
      if (key === 'items') return false
      const t = typeof value
      return t === 'string' || t === 'number' || t === 'boolean'
    })
    .map(([key, value]) => ({ key, value }))
})

const sideComponentOptions = computed(() =>
  sideComponentSelectOptions(props.blockKey, props.canvasBlocks ?? []),
)

const emailTemplateOptions = computed(() =>
  emailTemplateSelectOptions(props.emailTemplates ?? []),
)
</script>

<template>
  <div class="flex flex-col gap-4" :class="{ 'builder-config-panel--expanded': layout === 'expanded' }">
    <div v-if="layout !== 'expanded'">
      <p class="text-xs uppercase tracking-wide text-muted-foreground">Geselecteerd</p>
      <h3 class="text-base font-semibold text-foreground mt-1">{{ label }}</h3>
      <p class="text-xs text-muted-foreground mt-1">{{ blockKey }}</p>
    </div>

    <label class="builder-visibility-toggle">
      <input
        type="checkbox"
        class="kt-checkbox"
        :checked="visible"
        @change="emit('update:visible', ($event.target as HTMLInputElement).checked)"
      />
      <span>Zichtbaar op website</span>
    </label>

    <p v-if="isComponent && componentInfo?.description" class="text-xs text-muted-foreground leading-relaxed">
      {{ componentInfo.description }}
    </p>

    <BuilderConfigFields
      v-if="schemaFields.length > 0"
      :key="blockKey"
      :fields="schemaFields"
      :data="displayData"
      :upload-url="uploadUrl"
      :website-media-upload-url="websiteMediaUploadUrl"
      :website-media-serve-base="websiteMediaServeBase"
      :block-key="blockKey"
      :side-component-options="sideComponentOptions"
      :email-template-options="emailTemplateOptions"
      @patch="emit('patch', $event)"
    />

    <div v-if="extraScalarFields.length > 0" class="space-y-3">
      <p class="text-xs font-medium text-muted-foreground">Overige velden</p>
      <label v-for="entry in extraScalarFields" :key="entry.key" class="builder-field">
        <span>{{ entry.key }}</span>
        <input
          class="kt-input"
          :value="String(entry.value ?? '')"
          @input="emit('patch', { [entry.key]: ($event.target as HTMLInputElement).value })"
        />
      </label>
    </div>

    <div
      v-if="schemaFields.length === 0 && extraScalarFields.length === 0"
      class="rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground"
    >
      Geen bekende instellingen voor dit blok. Voeg het component opnieuw toe of gebruik de klassieke editor.
    </div>
  </div>
</template>

<style scoped>
.builder-visibility-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  cursor: pointer;
  padding: 0.5rem 0.65rem;
  border-radius: 0.5rem;
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--muted) 15%, transparent);
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

.builder-config-panel--expanded :deep(.builder-config-fields) {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem 1.25rem;
  align-items: start;
}

.builder-config-panel--expanded :deep(.builder-config-fields > .builder-field--wysiwyg),
.builder-config-panel--expanded :deep(.builder-config-fields > .builder-field:has(.builder-media-image-row)),
.builder-config-panel--expanded :deep(.builder-config-fields > .builder-config-group),
.builder-config-panel--expanded :deep(.builder-config-fields > .builder-config-item-list),
.builder-config-panel--expanded :deep(.builder-config-fields > .builder-config-step-order) {
  grid-column: 1 / -1;
}

.builder-config-panel--expanded :deep(.builder-config-item) {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem 1.25rem;
  align-items: start;
}

.builder-config-panel--expanded :deep(.builder-config-item__header) {
  grid-column: 1 / -1;
}

.builder-config-panel--expanded :deep(.builder-field:has(textarea)),
.builder-config-panel--expanded :deep(.builder-field--wysiwyg) {
  grid-column: 1 / -1;
}

.builder-config-panel--expanded :deep(.builder-config-group) {
  grid-column: 1 / -1;
}

.builder-config-panel--expanded :deep(.builder-field:has(.builder-image-preview)),
.builder-config-panel--expanded :deep(.builder-field:has(.builder-media-image-row)) {
  grid-column: 1 / -1;
}

.builder-config-panel--expanded :deep(.builder-image-preview),
.builder-config-panel--expanded :deep(.builder-media-image-row__preview) {
  max-height: 14rem;
  max-width: min(100%, 28rem);
}

.builder-config-panel--expanded :deep(.builder-media-upload-area) {
  min-height: 8rem;
}

@media (max-width: 768px) {
  .builder-config-panel--expanded :deep(.builder-config-fields),
  .builder-config-panel--expanded :deep(.builder-config-item) {
    grid-template-columns: 1fr;
  }
}
</style>
