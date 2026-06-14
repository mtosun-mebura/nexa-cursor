<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import BuilderPalette from './website-page-builder-v2/BuilderPalette.vue'
import BuilderCanvas from './website-page-builder-v2/BuilderCanvas.vue'
import BuilderConfigPanel from './website-page-builder-v2/BuilderConfigPanel.vue'
import { useBuilderState } from './website-page-builder-v2/builder-state'
import type { BuilderBootstrap, PaletteDragPayload } from './website-page-builder-v2/types'
import { baseTypeFromKey } from './website-page-builder-v2/palette-meta'

const props = defineProps<{
  bootstrap: BuilderBootstrap
}>()

const {
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
  sectionData,
  setSectionData,
  addBlock,
  removeBlock,
  moveBlock,
  reorderBlock,
  selectBlock,
  sectionVisible,
  setSectionVisible,
  save: saveToServer,
} = useBuilderState(props.bootstrap)

const dragOverIndex = ref<number | null>(null)
const draggingKey = ref<string | null>(null)
const paletteDragging = ref(false)
const previewRevision = ref(0)
const canvasColumnPercent = ref(45)

const previewUrl = computed(() => {
  const base = bootstrap.routes.preview
  const sep = base.includes('?') ? '&' : '?'
  return `${base}${sep}_preview_rev=${previewRevision.value}`
})

const selectedBlock = computed(() => canvasBlocks.value.find((b) => b.key === selectedKey.value) ?? null)

const selectedComponentInfo = computed(() => {
  if (!selectedBlock.value?.isComponent) return null
  return bootstrap.catalog.components.find((c) => c.sectionKey === selectedBlock.value?.key) ?? null
})

function previewText(key: string, label: string, isComponent: boolean) {
  const data = sectionData(key)
  if (isComponent) {
    return label
  }
  const base = baseTypeFromKey(key)
  if (base === 'hero') return String(data.title ?? 'Hero banner')
  if (base === 'text_block') {
    return String(data.content ?? '').replace(/<[^>]+>/g, ' ').trim() || 'Tekstblok'
  }
  if (base === 'cta') return String(data.title ?? 'Call to action')
  if (base === 'why_nexa') return String(data.title ?? 'Introductie')
  if (base === 'features') return String(data.section_title ?? 'Kenmerken')
  if (base === 'stats') return 'Statistieken'
  return label
}

function handlePaletteAdd(payload: PaletteDragPayload) {
  addBlock(payload)
}

function handleCanvasAdd(payload: PaletteDragPayload, index: number) {
  addBlock(payload, index)
}

function handleCanvasReorder(key: string, index: number) {
  reorderBlock(key, index)
}

function patchSelected(patch: Record<string, unknown>) {
  if (!selectedKey.value) return
  setSectionData(selectedKey.value, patch)
}

async function save() {
  await saveToServer()
  if (!saveError.value) {
    previewRevision.value += 1
  }
}

async function saveAndPreview() {
  await save()
  if (!saveError.value) {
    previewMode.value = 'live'
  }
}

let saveMessageTimer: ReturnType<typeof setTimeout> | null = null

watch(saveMessage, (message) => {
  if (saveMessageTimer) {
    clearTimeout(saveMessageTimer)
    saveMessageTimer = null
  }
  if (!message) {
    return
  }
  saveMessageTimer = setTimeout(() => {
    saveMessage.value = null
    saveMessageTimer = null
  }, 5000)
})

onUnmounted(() => {
  if (saveMessageTimer) {
    clearTimeout(saveMessageTimer)
  }
})
</script>

<template>
  <div class="website-builder-v2">
    <header class="builder-toolbar">
      <div class="builder-toolbar__left">
        <a :href="bootstrap.routes.index" class="builder-toolbar__back" title="Terug naar overzicht">
          <i class="ki-filled ki-left" />
        </a>
        <div class="min-w-0">
          <p class="text-xs uppercase tracking-wide text-muted-foreground">Page Builder v2</p>
          <h1 class="text-base font-semibold truncate">{{ bootstrap.page.title }}</h1>
          <p class="text-xs text-muted-foreground truncate">
            /{{ bootstrap.page.slug }} · {{ bootstrap.themeName }}
          </p>
        </div>
      </div>

      <div class="builder-toolbar__center">
        <div class="builder-toolbar__center-stack">
          <div class="builder-mode-toggle">
            <button
              type="button"
              class="builder-mode-toggle__btn"
              :class="{ 'builder-mode-toggle__btn--active': previewMode === 'build' }"
              @click="previewMode = 'build'"
            >
              Bouwen
            </button>
            <button
              type="button"
              class="builder-mode-toggle__btn"
              :class="{ 'builder-mode-toggle__btn--active': previewMode === 'live' }"
              @click="previewMode = 'live'"
            >
              Voorbeeld
            </button>
          </div>
          <label v-if="previewMode === 'build'" class="builder-canvas-width-control">
            <span class="builder-canvas-width-control__label">Pagina-breedte</span>
            <input
              v-model.number="canvasColumnPercent"
              type="range"
              min="25"
              max="65"
              step="1"
              class="builder-canvas-width-control__range"
            />
            <span class="builder-canvas-width-control__value">{{ canvasColumnPercent }}%</span>
          </label>
        </div>
      </div>

      <div class="builder-toolbar__right">
        <div v-if="dirty || saveError" class="builder-toolbar__status">
          <span v-if="dirty" class="builder-status builder-status--dirty">Niet opgeslagen</span>
          <span v-if="saveError" class="builder-status builder-status--error">{{ saveError }}</span>
        </div>

        <div class="builder-toolbar__actions">
          <a :href="bootstrap.routes.classicEdit" class="kt-btn kt-btn-outline kt-btn-sm">Klassieke editor</a>
          <a :href="previewUrl" target="_blank" rel="noopener" class="kt-btn kt-btn-outline kt-btn-sm">
            <i class="ki-filled ki-eye me-1" /> Nieuw tabblad
          </a>
          <button type="button" class="kt-btn kt-btn-outline kt-btn-sm" :disabled="saving" @click="saveAndPreview">
            Opslaan &amp; bekijk
          </button>
          <button type="button" class="kt-btn kt-btn-primary kt-btn-sm" :disabled="saving" @click="save">
            {{ saving ? 'Opslaan…' : 'Opslaan' }}
          </button>
        </div>
      </div>
    </header>

    <div v-if="saveMessage" class="builder-save-banner" role="status" aria-live="polite">
      <i class="ki-filled ki-check-circle" aria-hidden="true" />
      <span>{{ saveMessage }}</span>
    </div>

    <div
      class="builder-workspace"
      :class="{ 'builder-workspace--preview': previewMode === 'live' }"
      :style="previewMode === 'build' ? { '--builder-canvas-column-percent': canvasColumnPercent } : undefined"
    >
      <BuilderPalette
        :sections="bootstrap.catalog.sections"
        :components="bootstrap.catalog.components"
        :query="paletteQuery"
        @update:query="paletteQuery = $event"
        @add="handlePaletteAdd"
        @drag-start="paletteDragging = true"
        @drag-end="paletteDragging = false"
      />

      <BuilderCanvas
        v-model:drag-over-index="dragOverIndex"
        v-model:dragging-key="draggingKey"
        :blocks="canvasBlocks"
        :selected-key="selectedKey"
        :preview-url="previewUrl"
        :mode="previewMode"
        :palette-dragging="paletteDragging"
        @select="selectBlock"
        @add="handleCanvasAdd"
        @reorder="handleCanvasReorder"
        @remove="removeBlock"
        @move="moveBlock"
      >
        <template #preview="{ block }">
          {{ previewText(block.key, block.label, block.isComponent) }}
        </template>
      </BuilderCanvas>

      <aside v-show="previewMode === 'build'" class="builder-panel builder-panel--right">
        <div class="builder-panel__header">
          <h2 class="text-sm font-semibold">Instellingen</h2>
          <p class="text-xs text-muted-foreground mt-1">Klik op een blok op de pagina om te configureren</p>
        </div>
        <div class="builder-panel__scroll">
          <BuilderConfigPanel
            v-if="selectedBlock"
            :block-key="selectedBlock.key"
            :label="selectedBlock.label"
            :is-component="selectedBlock.isComponent"
            :data="sectionData(selectedBlock.key)"
            :visible="sectionVisible(selectedBlock.key)"
            :component-info="selectedComponentInfo"
            :component-defaults="bootstrap.componentDefaults"
            :upload-url="bootstrap.routes.uploadHeroImage"
            @patch="patchSelected"
            @update:visible="setSectionVisible(selectedBlock.key, $event)"
          />
          <div v-else class="builder-empty-config">
            <i class="ki-filled ki-setting-2 text-3xl text-muted-foreground/60" />
            <p class="text-sm text-muted-foreground mt-3">Selecteer een blok om titels, teksten en knoppen aan te passen.</p>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.website-builder-v2 {
  display: flex;
  flex-direction: column;
  min-height: calc(100vh - 7rem);
  margin: -1rem -1.25rem 0;
  background: var(--background);
}

.builder-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
  padding: 0.45rem 1rem 0.65rem;
  border-bottom: 1px solid var(--border);
  background: color-mix(in srgb, var(--background) 92%, var(--muted) 8%);
  position: sticky;
  top: 0;
  z-index: 20;
}

.builder-toolbar__left,
.builder-toolbar__right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.builder-toolbar__right {
  margin-left: auto;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.4rem;
  min-width: 0;
}

.builder-toolbar__status {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.35rem;
}

.builder-toolbar__actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.45rem;
  width: min(100%, 22rem);
}

.builder-toolbar__actions .kt-btn {
  justify-content: center;
  white-space: nowrap;
}

.builder-save-banner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.65rem 1rem;
  background: color-mix(in srgb, #10b981 16%, var(--background));
  color: #047857;
  font-size: 0.875rem;
  font-weight: 500;
  border-bottom: 1px solid color-mix(in srgb, #10b981 28%, var(--border));
}

.builder-toolbar__center {
  flex: 1;
  display: flex;
  justify-content: center;
  min-width: 0;
}

.builder-toolbar__center-stack {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
}

.builder-canvas-width-control {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: var(--muted-foreground);
  min-width: 0;
  width: 100%;
  justify-content: center;
}

.builder-canvas-width-control__label {
  white-space: nowrap;
}

.builder-canvas-width-control__range {
  width: min(160px, 28vw);
  accent-color: var(--primary);
}

.builder-canvas-width-control__value {
  font-variant-numeric: tabular-nums;
  min-width: 3.25rem;
  text-align: right;
  color: var(--foreground);
  font-weight: 500;
}

.builder-toolbar__back {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 0.5rem;
  border: 1px solid var(--border);
  color: var(--muted-foreground);
}

.builder-mode-toggle {
  display: inline-flex;
  padding: 0.2rem;
  border-radius: 9999px;
  border: 1px solid var(--border);
  background: var(--background);
}

.builder-mode-toggle__btn {
  border: 0;
  background: transparent;
  padding: 0.35rem 0.9rem;
  border-radius: 9999px;
  font-size: 0.8125rem;
  color: var(--muted-foreground);
}

.builder-mode-toggle__btn--active {
  background: var(--primary);
  color: var(--primary-foreground);
}

.builder-status {
  font-size: 0.75rem;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
}
.builder-status--dirty {
  background: color-mix(in srgb, var(--warning, #f59e0b) 15%, transparent);
  color: #b45309;
}
.builder-status--ok {
  background: color-mix(in srgb, #10b981 15%, transparent);
  color: #047857;
}
.builder-status--error {
  background: color-mix(in srgb, #ef4444 12%, transparent);
  color: #b91c1c;
}

.builder-workspace {
  display: grid;
  grid-template-columns:
    minmax(16rem, max-content)
    minmax(16rem, calc(var(--builder-canvas-column-percent, 45) * 1%))
    minmax(14rem, 1fr);
  min-height: 0;
  flex: 1;
}

.builder-workspace--preview {
  grid-template-columns: minmax(16rem, max-content) minmax(0, 1fr);
}

:deep(.builder-panel) {
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--border);
  min-height: 0;
  background: var(--background);
}

:deep(.builder-panel--right) {
  border-right: 0;
  border-left: 1px solid var(--border);
}

:deep(.builder-panel--left) {
  width: max-content;
  max-width: min(26rem, 34vw);
}

:deep(.builder-panel__header) {
  padding: 1rem;
  border-bottom: 1px solid var(--border);
}

:deep(.builder-panel__scroll) {
  overflow: auto;
  padding: 1rem;
  flex: 1;
}

:deep(.builder-palette-group + .builder-palette-group) {
  margin-top: 1.25rem;
}

:deep(.builder-palette-group__title) {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--muted-foreground);
  margin-bottom: 0.65rem;
}

:deep(.builder-palette-grid) {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: max-content;
  min-width: 100%;
}

:deep(.builder-palette-tile) {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: flex-start;
  gap: 0.65rem;
  width: 100%;
  min-width: max-content;
  padding: 0.55rem 0.65rem;
  border: 1px solid var(--border);
  border-radius: 0.75rem;
  background: var(--background);
  cursor: grab;
  transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
}

:deep(.builder-palette-tile:hover) {
  border-color: color-mix(in srgb, var(--primary) 40%, var(--border));
  box-shadow: 0 4px 14px color-mix(in srgb, var(--primary) 10%, transparent);
  transform: translateY(-1px);
}

:deep(.builder-palette-tile__icon) {
  width: 2rem;
  height: 2rem;
  border-radius: 0.55rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

:deep(.builder-palette-tile__label) {
  font-size: 0.8125rem;
  line-height: 1.25;
  text-align: left;
  color: var(--foreground);
  white-space: nowrap;
}

:deep(.builder-palette-list) {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  width: max-content;
  min-width: 100%;
}

:deep(.builder-palette-row) {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  width: 100%;
  min-width: max-content;
  padding: 0.55rem 0.65rem;
  border: 1px solid var(--border);
  border-radius: 0.75rem;
  background: var(--background);
  text-align: left;
  cursor: grab;
}

:deep(.builder-palette-row__icon) {
  width: 2rem;
  height: 2rem;
  border-radius: 0.55rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

:deep(.builder-canvas-wrap) {
  min-height: 0;
  min-width: 0;
  overflow: auto;
  background:
    radial-gradient(circle at 1px 1px, color-mix(in srgb, var(--border) 70%, transparent) 1px, transparent 0) 0 0 / 18px 18px,
    color-mix(in srgb, var(--muted) 35%, var(--background));
}

:deep(.builder-canvas) {
  min-height: 100%;
  display: flex;
  justify-content: stretch;
  padding: 1.5rem 1rem 2rem;
}

:deep(.builder-canvas__page) {
  width: 100%;
  max-width: 100%;
  border-radius: 1rem;
  border: 1px solid var(--border);
  background: var(--background);
  box-shadow: 0 18px 50px color-mix(in srgb, #000 8%, transparent);
  overflow: hidden;
}

.builder-workspace--preview :deep(.builder-canvas__page),
.builder-workspace--preview :deep(.builder-live-preview__frame) {
  width: min(100%, 1120px);
}

:deep(.builder-canvas__page-bar) {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.65rem 0.85rem;
  border-bottom: 1px solid var(--border);
  background: color-mix(in srgb, var(--muted) 30%, var(--background));
}

:deep(.builder-canvas__dot) {
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 9999px;
}

:deep(.builder-canvas__url) {
  margin-left: 0.35rem;
  font-size: 0.75rem;
  color: var(--muted-foreground);
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

:deep(.builder-dropzone) {
  transition: background 0.15s, min-height 0.15s;
}

:deep(.builder-dropzone--empty) {
  min-height: 320px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 1rem;
  border: 2px dashed color-mix(in srgb, var(--primary) 35%, var(--border));
  border-radius: 1rem;
  background: color-mix(in srgb, var(--primary) 4%, transparent);
}

:deep(.builder-dropzone--inline) {
  min-height: 0.35rem;
  margin: 0 1rem;
  border-radius: 0.35rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

:deep(.builder-canvas__page--dragging .builder-dropzone--inline) {
  min-height: 1rem;
}

:deep(.builder-canvas__page--dragging .builder-dropzone--empty) {
  border-color: color-mix(in srgb, var(--primary) 55%, var(--border));
  background: color-mix(in srgb, var(--primary) 8%, transparent);
}

:deep(.builder-dropzone--active) {
  min-height: 2.75rem;
  background: color-mix(in srgb, var(--primary) 12%, transparent);
  border: 1px dashed color-mix(in srgb, var(--primary) 45%, var(--border));
}

:deep(.builder-dropzone__label) {
  font-size: 0.72rem;
  font-weight: 600;
  color: color-mix(in srgb, var(--primary) 80%, var(--foreground));
  pointer-events: none;
}

:deep(.builder-block) {
  margin: 0.75rem 1rem;
  border: 1px solid var(--input);
  border-radius: 0.9rem;
  overflow: hidden;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
}

:deep(.builder-block--selected) {
  border-color: color-mix(in srgb, var(--primary) 55%, var(--input));
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 12%, transparent);
}

:deep(.builder-block--dragging) {
  opacity: 0.45;
}

:deep(.builder-block--drop-before) {
  box-shadow: inset 0 3px 0 color-mix(in srgb, var(--primary) 70%, transparent);
}

:deep(.builder-block--drop-after) {
  box-shadow: inset 0 -3px 0 color-mix(in srgb, var(--primary) 70%, transparent);
}

:deep(.builder-canvas__page--dragging) {
  outline: 2px dashed color-mix(in srgb, var(--primary) 25%, transparent);
  outline-offset: -2px;
}

:deep(.builder-block__toolbar) {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.55rem 0.75rem;
  background-color: transparent;
  border-bottom: 1px solid var(--input);
}

:deep(.builder-block__toolbar-start) {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  min-width: 0;
}

:deep(.builder-block__drag-handle) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.65rem;
  height: 1.65rem;
  border: 0;
  border-radius: 0.4rem;
  background: transparent;
  color: var(--muted-foreground);
  cursor: grab;
  flex-shrink: 0;
}

:deep(.builder-block__drag-handle:active) {
  cursor: grabbing;
}

:deep(.builder-block__drag-handle:hover) {
  background: color-mix(in srgb, var(--muted) 50%, transparent);
  color: var(--foreground);
}

:deep(.builder-block__type) {
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--foreground);
}

:deep(.builder-block__actions) {
  display: flex;
  gap: 0.15rem;
}

:deep(.builder-icon-btn) {
  width: 1.75rem;
  height: 1.75rem;
  border: 0;
  border-radius: 0.45rem;
  background: transparent;
  color: var(--muted-foreground);
}

:deep(.builder-icon-btn:hover) {
  background: color-mix(in srgb, var(--muted) 50%, transparent);
  color: var(--foreground);
}

:deep(.builder-icon-btn--danger:hover) {
  color: #dc2626;
}

:deep(.builder-block__preview) {
  min-height: 120px;
  padding: 1.25rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: flex-end;
  gap: 0.5rem;
}

:deep(.builder-block__preview-title) {
  font-size: 1.1rem;
  font-weight: 600;
  line-height: 1.3;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: +2;
  -webkit-box-orient: vertical;
}

:deep(.builder-block--fixed) {
  margin: 0.75rem 1rem 1rem;
  cursor: default;
}

:deep(.builder-block--fixed .builder-block__toolbar--static) {
  padding-left: 0.65rem;
}

:deep(.builder-block__preview--muted) {
  min-height: 120px;
  padding: 1.25rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: flex-end;
  gap: 0.5rem;
  background-color: #d4d4d8;
  color: #18181b;
}

:global(.dark) :deep(.builder-block__preview--muted) {
  background-color: #52525b;
  color: #fafafa;
}

:deep(.builder-block__preview--muted .builder-block__preview-title) {
  color: inherit;
}

:deep(.builder-block__preview--muted .builder-block__preview-hint) {
  color: #3f3f46;
}

:global(.dark) :deep(.builder-block__preview--muted .builder-block__preview-hint) {
  color: #e4e4e7;
}

:deep(.builder-block__preview-hint) {
  margin: 0;
  font-size: 0.72rem;
  line-height: 1.35;
  color: var(--muted-foreground);
}

:deep(.builder-live-preview) {
  height: 100%;
  min-height: calc(100vh - 10rem);
  padding: 1rem;
  display: flex;
  justify-content: center;
}

:deep(.builder-live-preview__frame) {
  width: 100%;
  max-width: 1120px;
  height: 100%;
  min-height: calc(100vh - 12rem);
  border: 1px solid var(--border);
  border-radius: 0.75rem;
  background: white;
}

.builder-empty-config {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  min-height: 220px;
  padding: 1rem;
}

@media (max-width: 1200px) {
  .builder-workspace:not(.builder-workspace--preview) {
    grid-template-columns:
      minmax(14rem, max-content)
      minmax(14rem, calc(var(--builder-canvas-column-percent, 45) * 1%))
      minmax(12rem, 1fr);
  }
}

@media (max-width: 992px) {
  .builder-workspace {
    grid-template-columns: 1fr;
  }
  :deep(.builder-panel--right),
  :deep(.builder-panel--left) {
    display: none;
  }
}
</style>
