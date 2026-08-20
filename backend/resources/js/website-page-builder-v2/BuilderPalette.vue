<script setup lang="ts">
import { computed, ref } from 'vue'
import { componentMeta, sectionMeta } from './palette-meta'
import type { PaletteDragPayload } from './types'
import { writeDragPayload } from './builder-state'
import BuilderBlockPreviewModal from './BuilderBlockPreviewModal.vue'

const props = defineProps<{
  sections: Array<{ type: string; label: string }>
  components: Array<{ id: string; name: string; description: string; moduleName: string; sectionKey: string }>
  query: string
  blockPreviewUrl: string
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

      <section v-if="components.length" class="builder-palette-group">
        <h3 class="builder-palette-group__title">Componenten</h3>
        <div class="builder-palette-list">
          <div
            v-for="component in components.filter((c) => matches(c.name + ' ' + c.description, query))"
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
              <span class="block text-xs text-muted-foreground">{{ component.moduleName }}</span>
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
    </div>

    <BuilderBlockPreviewModal
      :open="previewOpen"
      :title="previewTitle"
      :preview-url="previewUrl"
      @close="closePreview"
    />
  </aside>
</template>
