<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import BuilderPalette from './website-page-builder-v2/BuilderPalette.vue'
import BuilderCanvas from './website-page-builder-v2/BuilderCanvas.vue'
import BuilderConfigPanel from './website-page-builder-v2/BuilderConfigPanel.vue'
import BuilderPageInfoModal from './website-page-builder-v2/BuilderPageInfoModal.vue'
import { useBuilderState, isFixedSectionKey } from './website-page-builder-v2/builder-state'
import { mergeFooterData } from './website-page-builder-v2/footer-data'
import { deepMerge } from './website-page-builder-v2/nested-data'
import type { BuilderBootstrap, PageMetaForm, PaletteDragPayload } from './website-page-builder-v2/types'
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
  getCopyrightText,
  setCopyrightText,
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
const configModalOpen = ref(false)
const pageInfoModalOpen = ref(false)
const pageMeta = ref<PageMetaForm>(JSON.parse(JSON.stringify(props.bootstrap.pageMeta)) as PageMetaForm)
const pageHeader = ref({
  title: props.bootstrap.page.title,
  slug: props.bootstrap.page.slug,
  themeName: props.bootstrap.themeName,
})

const previewUrl = computed(() => {
  const base = bootstrap.routes.preview
  const sep = base.includes('?') ? '&' : '?'
  return `${base}${sep}_preview_rev=${previewRevision.value}&embed=1`
})

const selectedBlock = computed(() => canvasBlocks.value.find((b) => b.key === selectedKey.value) ?? null)

const FIXED_SECTION_LABELS: Record<string, string> = {
  footer: 'Footer',
  copyright: 'Copyright',
}

const hasConfigSelection = computed(() => {
  const key = selectedKey.value
  return !!key && (!!selectedBlock.value || isFixedSectionKey(key))
})

const selectedConfigLabel = computed(() => {
  const key = selectedKey.value
  if (!key) return ''
  if (isFixedSectionKey(key)) {
    return FIXED_SECTION_LABELS[key] ?? key
  }
  return selectedBlock.value?.label ?? key
})

const selectedConfigData = computed(() => {
  const key = selectedKey.value
  if (!key) return {}
  if (key === 'copyright') {
    return { text: getCopyrightText() }
  }
  if (key === 'footer') {
    return mergeFooterData(bootstrap.defaults.footer, sectionData('footer'))
  }
  const base = baseTypeFromKey(key)
  const defaults = bootstrap.defaults[base]
  const stored = sectionData(key)
  if (defaults && typeof defaults === 'object' && !Array.isArray(defaults)) {
    return deepMerge(defaults as Record<string, unknown>, stored)
  }
  return stored
})

const isNonHomePage = computed(() => bootstrap.page.pageType !== 'home')

const copyrightInheritedFromHome = computed(() => {
  if (!isNonHomePage.value) return false
  return !!sectionData('footer').inherit_from_home
})

const footerInheritedFromHome = computed(() => {
  if (!isNonHomePage.value) return false
  return !!sectionData('footer').inherit_from_home
})

const sectionVisibility = computed(() => {
  const vis = homeSections.value.visibility
  return vis && typeof vis === 'object' && !Array.isArray(vis)
    ? (vis as Record<string, unknown>)
    : {}
})

const configReadonly = computed(() => {
  if (selectedKey.value === 'copyright' && copyrightInheritedFromHome.value) return true
  if (selectedKey.value === 'footer' && footerInheritedFromHome.value) return true
  return false
})

const configReadonlyMessage = computed(() => {
  if (selectedKey.value === 'footer' && footerInheritedFromHome.value) {
    return 'De footer van de Home-pagina wordt op deze pagina getoond. Zet Overnemen van Home uit om eigen footer-instellingen te bewerken.'
  }
  if (!configReadonly.value) return null
  return 'Als Overnemen van Home bij Footer aan staat, wordt de copyrighttekst overgenomen van de Home-pagina. Zet die optie uit om hier een eigen copyrighttekst in te stellen.'
})

const copyrightPreview = computed(() => {
  const text = getCopyrightText()
  if (!text.trim()) return ''
  return text.replace(/\{year\}/gi, String(new Date().getFullYear()))
})

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
  if (selectedKey.value === 'copyright') {
    if ('text' in patch) {
      setCopyrightText(String(patch.text ?? ''))
    }
    return
  }
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

function onSaveShortcut(event: KeyboardEvent) {
  if (event.key === 'Escape' && pageInfoModalOpen.value) {
    pageInfoModalOpen.value = false
    return
  }
  if (event.key === 'Escape' && configModalOpen.value) {
    configModalOpen.value = false
    return
  }
  if (!(event.metaKey || event.ctrlKey) || event.key.toLowerCase() !== 's') {
    return
  }
  event.preventDefault()
  if (saving.value) {
    return
  }
  void save()
}

function closeConfigModal() {
  configModalOpen.value = false
}

function openPageInfoModal() {
  pageInfoModalOpen.value = true
}

function onPageInfoSaved(payload: {
  pageMeta: PageMetaForm
  themeName: string
  themeSlug: string
  page: BuilderBootstrap['page']
}) {
  pageMeta.value = payload.pageMeta
  pageHeader.value = {
    title: payload.page.title,
    slug: payload.page.slug,
    themeName: payload.themeName,
  }
  saveMessage.value = 'Pagina-informatie opgeslagen.'
  previewRevision.value += 1
}

watch(pageInfoModalOpen, (open) => {
  if (open) {
    document.body.style.overflow = 'hidden'
    return
  }
  if (!configModalOpen.value) {
    document.body.style.overflow = ''
  }
})

watch(hasConfigSelection, (has) => {
  if (!has) {
    configModalOpen.value = false
  }
})

watch(configModalOpen, (open) => {
  document.body.style.overflow = open || pageInfoModalOpen.value ? 'hidden' : ''
})

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

onMounted(() => {
  window.addEventListener('keydown', onSaveShortcut)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onSaveShortcut)
  document.body.style.overflow = ''
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
        <div class="min-w-0 builder-toolbar__title-wrap">
          <p class="text-xs uppercase tracking-wide text-muted-foreground">Page Builder v2</p>
          <div class="builder-toolbar__title-row">
            <h1 class="text-base font-semibold truncate">{{ pageHeader.title }}</h1>
            <button
              type="button"
              class="builder-toolbar__edit-page-btn"
              title="Pagina-informatie bewerken"
              aria-label="Pagina-informatie bewerken"
              @click="openPageInfoModal"
            >
              <i class="ki-filled ki-pencil" aria-hidden="true" />
            </button>
          </div>
          <p class="text-xs text-muted-foreground truncate">
            /{{ pageHeader.slug }} · {{ pageHeader.themeName }}
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
          <button type="button" class="kt-btn kt-btn-primary kt-btn-sm builder-save-btn" :disabled="saving" @click="save">
            <span v-if="saving" class="builder-save-btn__loading">
              <i class="ki-filled ki-loader-2 builder-save-btn__spinner" aria-hidden="true" />
              Opslaan…
            </span>
            <span v-else>Opslaan</span>
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
        :copyright-preview="copyrightPreview"
        :visible-for-block="sectionVisible"
        @select="selectBlock"
        @add="handleCanvasAdd"
        @reorder="handleCanvasReorder"
        @remove="removeBlock"
        @move="moveBlock"
        @toggle-visibility="(key) => setSectionVisible(key, !sectionVisible(key))"
      >
        <template #preview="{ block }">
          {{ previewText(block.key, block.label, block.isComponent) }}
        </template>
      </BuilderCanvas>

      <aside v-show="previewMode === 'build'" class="builder-panel builder-panel--right">
        <div class="builder-panel__header">
          <div class="builder-panel__header-text">
            <h2 class="text-sm font-semibold">Instellingen</h2>
            <p class="text-xs text-muted-foreground mt-1">Klik op een blok op de pagina om te configureren</p>
          </div>
          <button
            v-if="hasConfigSelection"
            type="button"
            class="builder-panel__expand-btn"
            title="Instellingen vergroten"
            aria-label="Instellingen vergroten"
            @click="configModalOpen = true"
          >
            <i class="ki-filled ki-arrow-two-diagonals" aria-hidden="true" />
          </button>
        </div>
        <div class="builder-panel__scroll">
          <BuilderConfigPanel
            v-if="hasConfigSelection && selectedKey"
            :block-key="selectedKey"
            :label="selectedConfigLabel"
            :is-component="!!selectedBlock?.isComponent"
            :data="selectedConfigData"
            :is-non-home-page="isNonHomePage"
            :config-readonly="configReadonly"
            :config-readonly-message="configReadonlyMessage"
            :footer-inherited-from-home="footerInheritedFromHome"
            :footer-logo-upload-url="bootstrap.routes.uploadFooterLogo"
            :footer-logo-fallback-url="bootstrap.siteBrandingLogoUrl"
            :google-maps-api-key="bootstrap.googleMapsApiKey"
            :google-maps-map-id="bootstrap.googleMapsMapId"
            :postcode-lookup-url="bootstrap.routes.postcodeLookup"
            :module-name="bootstrap.page.moduleName"
            :visibility="sectionVisibility"
            :component-info="selectedComponentInfo"
            :component-defaults="bootstrap.componentDefaults"
            :upload-url="bootstrap.routes.uploadHeroImage"
            :website-media-upload-url="bootstrap.routes.uploadWebsiteMedia"
            :website-media-serve-base="bootstrap.routes.websiteMediaServeBase"
            :canvas-blocks="canvasBlocks"
            :email-templates="bootstrap.emailTemplates"
            @patch="patchSelected"
            @patch-visibility="setSectionVisible"
          />
          <div v-else class="builder-empty-config">
            <i class="ki-filled ki-setting-2 text-3xl text-muted-foreground/60" />
            <p class="text-sm text-muted-foreground mt-3">Selecteer een blok om titels, teksten en knoppen aan te passen.</p>
          </div>
        </div>
      </aside>
    </div>

    <Teleport to="body">
      <div
        v-if="configModalOpen && hasConfigSelection && selectedKey"
        class="builder-config-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="builder-config-modal-title"
      >
        <button
          type="button"
          class="builder-config-modal__backdrop"
          aria-label="Instellingen verkleinen"
          @click="closeConfigModal"
        />
        <div class="builder-config-modal__dialog">
          <header class="builder-config-modal__header">
            <div class="builder-config-modal__header-text">
              <p class="text-xs uppercase tracking-wide text-muted-foreground">Instellingen</p>
              <h2 id="builder-config-modal-title" class="text-lg font-semibold text-foreground mt-0.5">
                {{ selectedConfigLabel }}
              </h2>
              <p class="text-xs text-muted-foreground mt-0.5">{{ selectedKey }}</p>
            </div>
            <button
              type="button"
              class="builder-config-modal__shrink-btn"
              title="Verkleinen"
              aria-label="Instellingen verkleinen"
              @click="closeConfigModal"
            >
              <i class="ki-filled ki-maximize" aria-hidden="true" />
            </button>
          </header>

          <div class="builder-config-modal__body">
            <BuilderConfigPanel
              :block-key="selectedKey"
              :label="selectedConfigLabel"
              :is-component="!!selectedBlock?.isComponent"
              :data="selectedConfigData"
              :is-non-home-page="isNonHomePage"
              :config-readonly="configReadonly"
              :config-readonly-message="configReadonlyMessage"
              :footer-inherited-from-home="footerInheritedFromHome"
              :footer-logo-upload-url="bootstrap.routes.uploadFooterLogo"
            :footer-logo-fallback-url="bootstrap.siteBrandingLogoUrl"
              :google-maps-api-key="bootstrap.googleMapsApiKey"
              :google-maps-map-id="bootstrap.googleMapsMapId"
              :postcode-lookup-url="bootstrap.routes.postcodeLookup"
              :module-name="bootstrap.page.moduleName"
              :visibility="sectionVisibility"
              :component-info="selectedComponentInfo"
              :component-defaults="bootstrap.componentDefaults"
              :upload-url="bootstrap.routes.uploadHeroImage"
              :website-media-upload-url="bootstrap.routes.uploadWebsiteMedia"
              :website-media-serve-base="bootstrap.routes.websiteMediaServeBase"
              :canvas-blocks="canvasBlocks"
              :email-templates="bootstrap.emailTemplates"
              layout="expanded"
              @patch="patchSelected"
              @patch-visibility="setSectionVisible"
            />
          </div>

          <footer class="builder-config-modal__footer">
            <div class="builder-config-modal__footer-status">
              <span v-if="dirty" class="builder-status builder-status--dirty">Niet opgeslagen</span>
              <span v-if="saveError" class="builder-status builder-status--error">{{ saveError }}</span>
              <span v-if="saveMessage && !dirty" class="builder-status builder-status--saved">{{ saveMessage }}</span>
            </div>
            <div class="builder-config-modal__footer-actions">
              <button type="button" class="kt-btn kt-btn-outline kt-btn-sm" @click="closeConfigModal">
                Verkleinen
              </button>
              <button type="button" class="kt-btn kt-btn-primary kt-btn-sm builder-save-btn" :disabled="saving" @click="save">
                <span v-if="saving" class="builder-save-btn__loading">
                  <i class="ki-filled ki-loader-2 builder-save-btn__spinner" aria-hidden="true" />
                  Opslaan…
                </span>
                <span v-else>Opslaan</span>
              </button>
            </div>
          </footer>
        </div>
      </div>
    </Teleport>

    <BuilderPageInfoModal
      v-model:open="pageInfoModalOpen"
      v-model="pageMeta"
      :bootstrap="bootstrap"
      @saved="onPageInfoSaved"
    />
  </div>
</template>

<style scoped>
.website-builder-v2 {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  height: 100%;
  overflow: hidden;
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
  flex-shrink: 0;
  position: sticky;
  top: 0;
  z-index: 30;
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

.builder-save-btn__loading {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
}

.builder-save-btn__spinner {
  animation: builder-save-spin 0.75s linear infinite;
}

@keyframes builder-save-spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.builder-save-banner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  flex-shrink: 0;
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

.builder-toolbar__title-wrap {
  min-width: 0;
}

.builder-toolbar__title-row {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  min-width: 0;
}

.builder-toolbar__edit-page-btn {
  flex-shrink: 0;
  width: auto;
  height: auto;
  min-width: 0;
  min-height: 0;
  padding: 0.15rem;
  border: none;
  outline: none;
  box-shadow: none;
  appearance: none;
  background: transparent;
  color: var(--muted-foreground);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: color 0.15s;
  font-size: 0.9375rem;
  line-height: 1;
}

.builder-toolbar__edit-page-btn:hover,
.builder-toolbar__edit-page-btn:focus,
.builder-toolbar__edit-page-btn:focus-visible {
  color: var(--foreground);
  border: none;
  outline: none;
  box-shadow: none;
  background: transparent;
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
    minmax(13rem, max-content)
    minmax(16rem, calc(var(--builder-canvas-column-percent, 45) * 1%))
    minmax(14rem, 1fr);
  min-height: 0;
  flex: 1;
  overflow: hidden;
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
  max-width: min(19rem, 26vw);
  min-width: 13rem;
}

:deep(.builder-panel__header) {
  padding: 1rem;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
}

:deep(.builder-panel__header--palette) {
  flex-direction: column;
  align-items: stretch;
  justify-content: flex-start;
}

:deep(.builder-panel__header--palette .builder-panel__search) {
  width: 100%;
  max-width: 100%;
}

:deep(.builder-panel__header-text) {
  min-width: 0;
  flex: 1;
}

.builder-panel__expand-btn {
  width: 2rem;
  height: 2rem;
  border: 1px solid var(--border);
  border-radius: 0.5rem;
  background: var(--background);
  color: var(--muted-foreground);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  cursor: pointer;
  transition: color 0.15s, border-color 0.15s, background 0.15s;
}

.builder-panel__expand-btn:hover {
  color: var(--foreground);
  border-color: color-mix(in srgb, var(--primary) 35%, var(--border));
  background: color-mix(in srgb, var(--muted) 25%, transparent);
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
  width: 100%;
  min-width: 0;
  max-width: 100%;
}

:deep(.builder-palette-tile) {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: flex-start;
  gap: 0.65rem;
  width: 100%;
  min-width: 0;
  max-width: 100%;
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
  min-width: 0;
}

:deep(.builder-palette-list) {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  width: 100%;
  min-width: 0;
  max-width: 100%;
}

:deep(.builder-palette-row) {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  width: 100%;
  min-width: 0;
  max-width: 100%;
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

:deep(.builder-block--hidden .builder-block__preview) {
  opacity: 0.42;
}

:deep(.builder-block--hidden .builder-block__type) {
  opacity: 0.65;
}

:deep(.builder-icon-btn--inactive) {
  opacity: 0.55;
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
  margin: 0.75rem 1rem 0;
  cursor: pointer;
}

:deep(.builder-block--fixed:last-child) {
  margin-bottom: 1rem;
}

:deep(.builder-block--fixed .builder-block__toolbar--static) {
  padding-left: 0.65rem;
  justify-content: space-between;
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

:deep(.builder-block__preview--muted.bg-gradient-to-br .builder-block__preview-hint) {
  color: rgba(255, 255, 255, 0.82);
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
  min-height: 0;
  padding: 0.75rem 1rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 0.5rem;
}

:deep(.builder-live-preview__chrome) {
  display: flex;
  justify-content: flex-end;
  flex-shrink: 0;
}

:deep(.builder-live-preview__popout-btn) {
  gap: 0.35rem;
}

:deep(.builder-live-preview__frame) {
  width: 100%;
  max-width: none;
  flex: 1;
  min-height: 0;
  height: auto;
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

.builder-config-modal {
  position: fixed;
  inset: 0;
  z-index: 10050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
}

.builder-config-modal__backdrop {
  position: absolute;
  inset: 0;
  border: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  cursor: pointer;
}

.builder-config-modal__dialog {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  width: 90vw;
  max-width: 90vw;
  max-height: min(90vh, 56rem);
  border: 1px solid var(--border);
  border-radius: 1rem;
  background: var(--background);
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.28);
  overflow: hidden;
}

.builder-config-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--border);
  background: color-mix(in srgb, var(--background) 90%, var(--muted) 10%);
}

.builder-config-modal__header-text {
  min-width: 0;
}

.builder-config-modal__shrink-btn {
  width: 2.25rem;
  height: 2.25rem;
  border: 1px solid var(--border);
  border-radius: 0.55rem;
  background: var(--background);
  color: var(--muted-foreground);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  cursor: pointer;
  transition: color 0.15s, border-color 0.15s, background 0.15s;
}

.builder-config-modal__shrink-btn:hover {
  color: var(--foreground);
  border-color: color-mix(in srgb, var(--primary) 35%, var(--border));
  background: color-mix(in srgb, var(--muted) 25%, transparent);
}

.builder-config-modal__body {
  flex: 1;
  min-height: 0;
  overflow: auto;
  padding: 1.25rem 1.5rem;
  scrollbar-width: thin;
  scrollbar-color: color-mix(in srgb, var(--muted-foreground) 42%, var(--border)) color-mix(in srgb, var(--muted) 55%, var(--background));
}

.builder-config-modal__body::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}

.builder-config-modal__body::-webkit-scrollbar-track {
  background: color-mix(in srgb, var(--muted) 50%, var(--background));
  border-radius: 9999px;
}

.builder-config-modal__body::-webkit-scrollbar-thumb {
  background: color-mix(in srgb, var(--muted-foreground) 38%, var(--border));
  border-radius: 9999px;
  border: 2px solid color-mix(in srgb, var(--muted) 50%, var(--background));
  background-clip: padding-box;
}

.builder-config-modal__body::-webkit-scrollbar-thumb:hover {
  background: color-mix(in srgb, var(--muted-foreground) 58%, var(--border));
  background-clip: padding-box;
}

:global(html.dark) .builder-config-modal__body,
:global(.dark) .builder-config-modal__body {
  scrollbar-color: color-mix(in srgb, var(--muted-foreground) 65%, #64748b) color-mix(in srgb, var(--muted) 70%, #0f172a);
}

:global(html.dark) .builder-config-modal__body::-webkit-scrollbar-track,
:global(.dark) .builder-config-modal__body::-webkit-scrollbar-track {
  background: color-mix(in srgb, var(--muted) 75%, #0f172a);
}

:global(html.dark) .builder-config-modal__body::-webkit-scrollbar-thumb,
:global(.dark) .builder-config-modal__body::-webkit-scrollbar-thumb {
  background: color-mix(in srgb, var(--muted-foreground) 55%, #94a3b8);
  border-color: color-mix(in srgb, var(--muted) 75%, #0f172a);
}

:global(html.dark) .builder-config-modal__body::-webkit-scrollbar-thumb:hover,
:global(.dark) .builder-config-modal__body::-webkit-scrollbar-thumb:hover {
  background: color-mix(in srgb, var(--muted-foreground) 72%, #cbd5e1);
}

.builder-config-modal__body :deep(.builder-config-item) {
  padding: 1rem 1.1rem;
}

.builder-config-modal__body :deep(.builder-config-item-list) {
  padding: 0.85rem 1rem;
}

.builder-config-modal__body :deep(.builder-config-fields) {
  gap: 1rem;
}

.builder-config-modal__body :deep(.builder-media-upload-area) {
  flex: none !important;
  width: 16rem !important;
  max-width: 16rem !important;
  min-height: 4.5rem;
}

.builder-config-modal__footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.85rem 1.25rem;
  border-top: 1px solid var(--border);
  background: color-mix(in srgb, var(--background) 92%, var(--muted) 8%);
}

.builder-config-modal__footer-status {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  min-width: 0;
}

.builder-config-modal__footer-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-left: auto;
}

.builder-status--saved {
  color: #16a34a;
}

@media (max-width: 1200px) {
  .builder-workspace:not(.builder-workspace--preview) {
    grid-template-columns:
      minmax(13rem, max-content)
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

<style>
.builder-preview-popout {
  position: fixed;
  inset: 0;
  z-index: 10100;
  display: flex;
  flex-direction: column;
  background: var(--background, #fff);
}

.builder-preview-popout__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-shrink: 0;
  padding: 0.65rem 1rem;
  border-bottom: 1px solid var(--border, #e5e7eb);
  background: color-mix(in srgb, var(--background, #fff) 92%, var(--muted, #f4f4f5) 8%);
}

.builder-preview-popout__title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--foreground, #18181b);
}

.builder-preview-popout__close {
  gap: 0.35rem;
}

.builder-preview-popout__frame {
  flex: 1;
  width: 100%;
  min-height: 0;
  border: 0;
  background: #fff;
}
</style>
