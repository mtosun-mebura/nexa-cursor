<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { componentMeta, sectionMeta } from './palette-meta'
import type { ComponentCatalogItem, PaletteDragPayload } from './types'
import { writeDragPayload } from './builder-state'
import BuilderBlockPreviewModal from './BuilderBlockPreviewModal.vue'

const THEME_GROUP_ORDER = ['Landwind', 'Play Tailwind', 'Vue Material Kit']

const props = defineProps<{
  sections: Array<{ type: string; label: string }>
  components: ComponentCatalogItem[]
  disabledComponents?: ComponentCatalogItem[]
  query: string
  blockPreviewUrl: string
  toggleDisabledUrl?: string
  themeSlug: string
}>()

const emit = defineEmits<{
  add: [PaletteDragPayload]
  'update:query': [string]
  'drag-start': []
  'drag-end': []
}>()

const suppressClick = ref(false)
const previewOpen = ref(false)
const previewTitle = ref('')
const previewQuery = ref('')
const togglingId = ref('')
const toggleError = ref('')
const activeComponents = ref<ComponentCatalogItem[]>([...props.components])
const disabledList = ref<ComponentCatalogItem[]>([...(props.disabledComponents ?? [])])

watch(
  () => props.components,
  (value) => {
    activeComponents.value = [...value]
  }
)
watch(
  () => props.disabledComponents,
  (value) => {
    disabledList.value = [...(value ?? [])]
  }
)

const previewUrl = computed(() => {
  if (!props.blockPreviewUrl || !previewQuery.value) {
    return ''
  }
  const joiner = props.blockPreviewUrl.includes('?') ? '&' : '?'
  return `${props.blockPreviewUrl}${joiner}${previewQuery.value}`
})

function onDragStart(event: DragEvent, payload: PaletteDragPayload) {
  suppressClick.value = false
  writeDragPayload(event, payload)
  emit('drag-start')
}

function onDragEnd() {
  suppressClick.value = true
  emit('drag-end')
  window.setTimeout(() => {
    suppressClick.value = false
  }, 0)
}

function clickAdd(payload: PaletteDragPayload) {
  if (suppressClick.value) {
    return
  }
  emit('add', payload)
}

function matches(text: string, query: string) {
  if (!query.trim()) return true
  return text.toLowerCase().includes(query.trim().toLowerCase())
}

const componentGroups = computed(() => {
  const filtered = activeComponents.value.filter((c) =>
    matches(`${c.name} ${c.description} ${c.themeName || ''} ${c.moduleName}`, props.query)
  )
  const general = filtered.filter((c) => !c.themeName)
  const byTheme = new Map<string, ComponentCatalogItem[]>()
  for (const component of filtered) {
    const themeName = (component.themeName || '').trim()
    if (themeName === '') {
      continue
    }
    const list = byTheme.get(themeName) ?? []
    list.push(component)
    byTheme.set(themeName, list)
  }
  const groups: { title: string; items: ComponentCatalogItem[] }[] = []
  if (general.length) {
    groups.push({ title: 'Componenten', items: general })
  }
  const seen = new Set<string>()
  for (const name of THEME_GROUP_ORDER) {
    const items = byTheme.get(name)
    if (items?.length) {
      groups.push({ title: `Thema: ${name}`, items })
      seen.add(name)
    }
  }
  for (const [name, items] of byTheme) {
    if (!seen.has(name) && items.length) {
      groups.push({ title: `Thema: ${name}`, items })
    }
  }
  return groups
})

const filteredDisabled = computed(() =>
  disabledList.value.filter((c) =>
    matches(`${c.name} ${c.description} ${c.themeName || ''} ${c.moduleName}`, props.query)
  )
)

function currentAdminIsDark(): boolean {
  return (
    document.documentElement.classList.contains('dark') ||
    document.body.classList.contains('dark')
  )
}

function openSectionPreview(event: Event, section: { type: string; label: string }) {
  event.preventDefault()
  event.stopPropagation()
  const params = new URLSearchParams({
    kind: 'section',
    type: section.type,
    theme: props.themeSlug || 'modern',
  })
  if (currentAdminIsDark()) {
    params.set('dark', '1')
  }
  previewTitle.value = section.label
  previewQuery.value = params.toString()
  previewOpen.value = true
}

function openComponentPreview(
  event: Event,
  component: { id: string; name: string; sectionKey: string }
) {
  event.preventDefault()
  event.stopPropagation()
  const params = new URLSearchParams({
    kind: 'component',
    component: component.id || component.sectionKey.replace(/^component:/, ''),
    theme: props.themeSlug || 'modern',
  })
  if (currentAdminIsDark()) {
    params.set('dark', '1')
  }
  previewTitle.value = component.name
  previewQuery.value = params.toString()
  previewOpen.value = true
}

function closePreview() {
  previewOpen.value = false
}

async function activateComponent(event: Event, component: ComponentCatalogItem) {
  event.preventDefault()
  event.stopPropagation()
  if (!props.toggleDisabledUrl || togglingId.value) {
    return
  }
  togglingId.value = component.id
  toggleError.value = ''
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  try {
    const response = await fetch(props.toggleDisabledUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        component_id: component.id,
        disabled: false,
      }),
    })
    const payload = await response.json().catch(() => null)
    if (!response.ok || !payload?.success) {
      toggleError.value = payload?.message || 'Activeren is niet gelukt.'
      return
    }
    disabledList.value = disabledList.value.filter((item) => item.id !== component.id)
    if (!activeComponents.value.some((item) => item.id === component.id)) {
      activeComponents.value = [...activeComponents.value, { ...component, disabled: false }]
    }
  } catch {
    toggleError.value = 'Activeren is niet gelukt.'
  } finally {
    togglingId.value = ''
  }
}
</script>

<template>
  <aside class="builder-panel builder-panel--left">
    <div class="builder-panel__header builder-panel__header--palette">
      <h2 class="text-sm font-semibold">Blokken</h2>
      <input
        class="kt-input builder-panel__search mt-2"
        type="search"
        placeholder="Zoek blok..."
        :value="query"
        @input="emit('update:query', ($event.target as HTMLInputElement).value)"
      />
      <p class="text-xs text-muted-foreground mt-2 mb-0">Sleep naar de pagina of klik om toe te voegen</p>
    </div>

    <div class="builder-panel__scroll">
      <section class="builder-palette-group">
        <h3 class="builder-palette-group__title">Secties</h3>
        <div class="builder-palette-grid">
          <div
            v-for="section in sections.filter((s) => matches(s.label + ' ' + s.type, query))"
            :key="section.type"
            role="button"
            tabindex="0"
            class="builder-palette-tile"
            draggable="true"
            @dragstart="onDragStart($event, { kind: 'section', type: section.type, label: section.label })"
            @dragend="onDragEnd"
            @click="clickAdd({ kind: 'section', type: section.type, label: section.label })"
            @keydown.enter.prevent="clickAdd({ kind: 'section', type: section.type, label: section.label })"
            @keydown.space.prevent="clickAdd({ kind: 'section', type: section.type, label: section.label })"
          >
            <span class="builder-palette-tile__icon bg-gradient-to-br" :class="sectionMeta(section.type).accent">
              <i class="ki-filled text-white text-lg" :class="sectionMeta(section.type).icon" />
            </span>
            <span class="builder-palette-tile__label">{{ section.label }}</span>
            <button
              type="button"
              class="builder-palette-preview-btn"
              title="Voorbeeld tonen"
              aria-label="Voorbeeld tonen"
              draggable="false"
              @click="openSectionPreview($event, section)"
              @mousedown.stop
              @dragstart.stop.prevent
            >
              <i class="ki-filled ki-eye" aria-hidden="true" />
            </button>
          </div>
        </div>
      </section>

      <section v-for="group in componentGroups" :key="group.title" class="builder-palette-group">
        <h3 class="builder-palette-group__title">{{ group.title }}</h3>
        <div class="builder-palette-list">
          <div
            v-for="component in group.items"
            :key="component.sectionKey"
            role="button"
            tabindex="0"
            class="builder-palette-row"
            draggable="true"
            @dragstart="onDragStart($event, { kind: 'component', sectionKey: component.sectionKey, label: component.name })"
            @dragend="onDragEnd"
            @click="clickAdd({ kind: 'component', sectionKey: component.sectionKey, label: component.name })"
            @keydown.enter.prevent="clickAdd({ kind: 'component', sectionKey: component.sectionKey, label: component.name })"
            @keydown.space.prevent="clickAdd({ kind: 'component', sectionKey: component.sectionKey, label: component.name })"
          >
            <span class="builder-palette-row__icon bg-gradient-to-br" :class="componentMeta(component.sectionKey).accent">
              <i class="ki-filled text-white" :class="componentMeta(component.sectionKey).icon" />
            </span>
            <span class="min-w-0 grow">
              <span class="block text-sm font-medium">{{ component.name }}</span>
              <span class="block text-xs text-muted-foreground">{{ component.themeName ? `Thema: ${component.themeName}` : component.moduleName }}</span>
            </span>
            <button
              type="button"
              class="builder-palette-preview-btn"
              title="Voorbeeld tonen"
              aria-label="Voorbeeld tonen"
              draggable="false"
              @click="openComponentPreview($event, component)"
              @mousedown.stop
              @dragstart.stop.prevent
            >
              <i class="ki-filled ki-eye" aria-hidden="true" />
            </button>
          </div>
        </div>
      </section>

      <section v-if="filteredDisabled.length" class="builder-palette-group">
        <h3 class="builder-palette-group__title">Uitgeschakeld</h3>
        <p class="text-xs text-muted-foreground mb-2">Niet toe te voegen tot je ze weer activeert. Bestaande pagina’s blijven werken.</p>
        <p v-if="toggleError" class="text-xs text-destructive mb-2">{{ toggleError }}</p>
        <div class="builder-palette-list">
          <div
            v-for="component in filteredDisabled"
            :key="'disabled-' + component.sectionKey"
            class="builder-palette-row builder-palette-row--disabled"
          >
            <span class="builder-palette-row__icon bg-gradient-to-br from-zinc-400/80 to-zinc-600/80">
              <i class="ki-filled text-white" :class="componentMeta(component.sectionKey).icon" />
            </span>
            <span class="min-w-0 grow">
              <span class="block text-sm font-medium text-muted-foreground">{{ component.name }}</span>
              <span class="block text-xs text-muted-foreground">{{ component.themeName ? `Thema: ${component.themeName}` : component.moduleName }}</span>
            </span>
            <button
              type="button"
              class="builder-palette-activate-btn"
              :disabled="togglingId === component.id || !toggleDisabledUrl"
              @click="activateComponent($event, component)"
            >
              {{ togglingId === component.id ? 'Bezig…' : 'Activeren' }}
            </button>
          </div>
        </div>
      </section>
    </div>

    <BuilderBlockPreviewModal
      :open="previewOpen"
      :title="previewTitle"
      :preview-url="previewUrl"
      @close="closePreview"
    />
  </aside>
</template>
