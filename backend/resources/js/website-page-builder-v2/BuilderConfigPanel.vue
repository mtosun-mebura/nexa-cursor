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
  componentInfo: ComponentCatalogItem | null
  componentDefaults: Record<string, Record<string, unknown>>
  uploadUrl: string
  websiteMediaUploadUrl?: string
  websiteMediaServeBase?: string
  layout?: 'compact' | 'expanded'
  canvasBlocks?: CanvasBlock[]
  emailTemplates?: EmailTemplateOption[]
  isNonHomePage?: boolean
  configReadonly?: boolean
  configReadonlyMessage?: string | null
  footerInheritedFromHome?: boolean
  footerLogoUploadUrl?: string
  footerLogoFallbackUrl?: string
  googleMapsApiKey?: string
  googleMapsMapId?: string
  postcodeLookupUrl?: string
  moduleName?: string | null
  visibility?: Record<string, unknown>
}>()

const emit = defineEmits<{
  patch: [Record<string, unknown>]
  'patch-visibility': [string, boolean]
}>()

const baseType = computed(() => baseTypeFromKey(props.blockKey))

const schemaFields = computed(() => {
  let fields: ReturnType<typeof schemaForBaseType>
  if (props.isComponent) {
    fields = schemaForComponent(props.blockKey)
  } else {
    fields = schemaForBaseType(baseType.value)
  }
  if (props.blockKey === 'footer' && !props.isNonHomePage) {
    fields = fields.filter((field) => !('key' in field) || field.key !== 'inherit_from_home')
  }
  if (props.blockKey === 'footer' && props.footerInheritedFromHome) {
    fields = fields.filter((field) => 'key' in field && field.key === 'inherit_from_home')
  }
  return fields
})

const displayData = computed(() => {
  if (props.isComponent) {
    const defaults = props.componentDefaults[props.blockKey]
      ?? props.componentDefaults[props.blockKey.toLowerCase()]
      ?? {}
    return deepMerge(defaults, props.data)
  }
  return props.data
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

    <p
      v-if="configReadonly && configReadonlyMessage"
      class="rounded-lg border border-border bg-muted/30 p-3 text-sm text-muted-foreground leading-relaxed"
    >
      {{ configReadonlyMessage }}
    </p>

    <p v-if="isComponent && componentInfo?.description" class="text-xs text-muted-foreground leading-relaxed">
      {{ componentInfo.description }}
    </p>

    <BuilderConfigFields
      v-if="schemaFields.length > 0 && (!configReadonly || blockKey === 'footer')"
      :key="blockKey"
      :fields="schemaFields"
      :data="displayData"
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
      :block-key="blockKey"
      :side-component-options="sideComponentOptions"
      :email-template-options="emailTemplateOptions"
      @patch="emit('patch', $event)"
      @patch-visibility="(key, visible) => emit('patch-visibility', key, visible)"
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
      v-if="!configReadonly && schemaFields.length === 0 && extraScalarFields.length === 0 && blockKey !== 'footer'"
      class="rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground"
    >
      Geen bekende instellingen voor dit blok. Voeg het component opnieuw toe of gebruik de klassieke editor.
    </div>
  </div>
</template>

<style scoped>
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

.builder-config-panel--expanded :deep(.builder-config-group__body > .builder-config-fields) {
  grid-column: 1 / -1;
  min-width: 0;
}

.builder-config-panel--expanded :deep(.builder-config-group__body .builder-field--wysiwyg),
.builder-config-panel--expanded :deep(.builder-config-group__body .builder-config-item-list) {
  grid-column: 1 / -1;
}

.builder-config-panel--expanded :deep(.builder-config-item:not(.builder-config-item--footer-link)) {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem 1.25rem;
  align-items: start;
}

.builder-config-panel--expanded :deep(.builder-config-item--footer-link) {
  grid-template-columns: minmax(6rem, 0.9fr) minmax(8rem, 1.5fr) auto;
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
  min-height: 4.5rem;
  max-width: 16rem;
}

@media (max-width: 768px) {
  .builder-config-panel--expanded :deep(.builder-config-fields),
  .builder-config-panel--expanded :deep(.builder-config-item) {
    grid-template-columns: 1fr;
  }
}
</style>
