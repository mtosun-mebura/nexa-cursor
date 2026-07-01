<script setup lang="ts">
import { ref } from 'vue'
import { componentMeta, sectionMeta } from './palette-meta'
import type { PaletteDragPayload } from './types'
import { writeDragPayload } from './builder-state'

defineProps<{
  sections: Array<{ type: string; label: string }>
  components: Array<{ id: string; name: string; description: string; moduleName: string; sectionKey: string }>
  query: string
}>()

const emit = defineEmits<{
  add: [PaletteDragPayload]
  'update:query': [string]
  'drag-start': []
  'drag-end': []
}>()

const suppressClick = ref(false)

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
            <span class="min-w-0">
              <span class="block text-sm font-medium">{{ component.name }}</span>
              <span class="block text-xs text-muted-foreground">{{ component.moduleName }}</span>
            </span>
          </div>
        </div>
      </section>
    </div>
  </aside>
</template>
