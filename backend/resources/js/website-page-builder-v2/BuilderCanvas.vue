<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import { baseTypeFromKey, sectionMeta, componentMeta } from './palette-meta'
import type { CanvasBlock } from './types'
import { readBlockKeyDrag, readDragPayload, writeBlockKeyDrag } from './builder-state'

const props = defineProps<{
  blocks: CanvasBlock[]
  selectedKey: string | null
  previewUrl: string
  mode: 'build' | 'live'
  paletteDragging: boolean
  copyrightPreview?: string
  visibleForBlock: (key: string) => boolean
}>()

const emit = defineEmits<{
  select: [string | null]
  add: [import('./types').PaletteDragPayload, number]
  reorder: [string, number]
  remove: [string]
  move: [string, -1 | 1]
  'toggle-visibility': [string]
}>()

const dragOverIndex = defineModel<number | null>('dragOverIndex', { default: null })
const draggingKey = defineModel<string | null>('draggingKey', { default: null })
const pageRef = ref<HTMLElement | null>(null)
const popoutOpen = ref(false)
let bodyOverflowBeforePopout = ''

const isDragging = computed(
  () => props.paletteDragging || draggingKey.value !== null || dragOverIndex.value !== null
)

function accentClass(block: CanvasBlock) {
  if (block.isComponent) {
    return componentMeta(block.key).accent
  }
  return sectionMeta(baseTypeFromKey(block.key)).accent
}

function iconClass(block: CanvasBlock) {
  if (block.isComponent) {
    return componentMeta(block.key).icon
  }
  return sectionMeta(baseTypeFromKey(block.key)).icon
}

function resolveDropIndex(event: DragEvent): number {
  if (props.blocks.length === 0) {
    return 0
  }

  const target = event.target as HTMLElement | null
  const blockEl = target?.closest('[data-block-key]') as HTMLElement | null
  if (blockEl) {
    const key = blockEl.getAttribute('data-block-key') ?? ''
    const index = props.blocks.findIndex((block) => block.key === key)
    if (index >= 0) {
      const rect = blockEl.getBoundingClientRect()
      const mid = rect.top + rect.height / 2
      return event.clientY < mid ? index : index + 1
    }
  }

  const footerEl = target?.closest('.builder-block--fixed')
  if (footerEl) {
    return props.blocks.length
  }

  const pageEl = pageRef.value
  if (pageEl) {
    const blockNodes = pageEl.querySelectorAll<HTMLElement>('[data-block-key]')
    for (let i = 0; i < blockNodes.length; i++) {
      const rect = blockNodes[i].getBoundingClientRect()
      if (event.clientY < rect.top + rect.height / 2) {
        return i
      }
    }
  }

  return props.blocks.length
}

function onCanvasDragOver(event: DragEvent, index?: number) {
  event.preventDefault()
  event.stopPropagation()
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = props.paletteDragging || readDragPayload(event) ? 'copy' : 'move'
  }
  dragOverIndex.value = index ?? resolveDropIndex(event)
}

function onCanvasDrop(event: DragEvent, index?: number) {
  event.preventDefault()
  event.stopPropagation()

  const dropIndex = index ?? resolveDropIndex(event)
  dragOverIndex.value = null

  const payload = readDragPayload(event)
  if (payload) {
    emit('add', payload, dropIndex)
    draggingKey.value = null
    return
  }

  const blockKey = readBlockKeyDrag(event) ?? draggingKey.value
  if (blockKey) {
    emit('reorder', blockKey, dropIndex)
  }
  draggingKey.value = null
}

function onBlockDragStart(event: DragEvent, key: string) {
  draggingKey.value = key
  writeBlockKeyDrag(event, key)
}

function onBlockDragEnd() {
  draggingKey.value = null
  dragOverIndex.value = null
}

function onPageDragLeave(event: DragEvent) {
  const next = event.relatedTarget as Node | null
  if (next && pageRef.value?.contains(next)) {
    return
  }
  dragOverIndex.value = null
}

function fixedMeta(key: 'footer' | 'copyright') {
  return sectionMeta(key)
}

function visibilityTitle(key: string): string {
  return props.visibleForBlock(key) ? 'Verbergen op website' : 'Tonen op website'
}

function openPopout() {
  popoutOpen.value = true
}

function closePopout() {
  popoutOpen.value = false
}

function onPopoutKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && popoutOpen.value) {
    closePopout()
  }
}

watch(popoutOpen, (open) => {
  if (open) {
    bodyOverflowBeforePopout = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', onPopoutKeydown)
  } else {
    document.body.style.overflow = bodyOverflowBeforePopout
    window.removeEventListener('keydown', onPopoutKeydown)
  }
})

watch(
  () => props.mode,
  (mode) => {
    if (mode !== 'live' && popoutOpen.value) {
      closePopout()
    }
  },
)

onUnmounted(() => {
  if (popoutOpen.value) {
    document.body.style.overflow = bodyOverflowBeforePopout
    window.removeEventListener('keydown', onPopoutKeydown)
  }
})
</script>

<template>
  <main class="builder-canvas-wrap">
    <template v-if="mode === 'live'">
      <div class="builder-live-preview">
        <div class="builder-live-preview__chrome">
          <button
            type="button"
            class="builder-live-preview__popout-btn kt-btn kt-btn-sm kt-btn-outline"
            title="Volledig scherm"
            @click="openPopout"
          >
            <i class="ki-filled ki-maximize" aria-hidden="true" />
            Volledig scherm
          </button>
        </div>
        <iframe :key="previewUrl" :src="previewUrl" title="Pagina voorbeeld" class="builder-live-preview__frame" />
      </div>
    </template>

    <div v-else class="builder-canvas">
      <div
        ref="pageRef"
        class="builder-canvas__page"
        :class="{ 'builder-canvas__page--dragging': isDragging }"
        @dragover.prevent="onCanvasDragOver($event)"
        @drop="onCanvasDrop($event)"
        @dragleave="onPageDragLeave"
      >
        <div class="builder-canvas__page-bar">
          <span class="builder-canvas__dot bg-red-400" />
          <span class="builder-canvas__dot bg-amber-400" />
          <span class="builder-canvas__dot bg-emerald-400" />
          <span class="builder-canvas__url">{{ previewUrl.replace(/^https?:\/\//, '') }}</span>
        </div>

        <div
          v-if="blocks.length === 0"
          class="builder-dropzone builder-dropzone--empty"
          :class="{ 'builder-dropzone--active': dragOverIndex === 0 }"
        >
          <i class="ki-filled ki-mouse-square text-3xl text-muted-foreground/70" />
          <p class="text-base font-medium mt-3">Lege pagina</p>
          <p class="text-sm text-muted-foreground mt-1 max-w-xs text-center">
            Sleep een blok van links naar hier, of klik op een blok om direct toe te voegen.
          </p>
        </div>

        <template v-else>
          <template v-for="(block, index) in blocks" :key="block.key">
            <div
              class="builder-dropzone builder-dropzone--inline"
              :class="{ 'builder-dropzone--active': dragOverIndex === index }"
            >
              <span v-if="dragOverIndex === index" class="builder-dropzone__label">Plaats hier</span>
            </div>

            <article
              class="builder-block"
              :data-block-key="block.key"
              :class="{
                'builder-block--selected': selectedKey === block.key,
                'builder-block--dragging': draggingKey === block.key,
                'builder-block--drop-before': dragOverIndex === index,
                'builder-block--drop-after': dragOverIndex === index + 1,
                'builder-block--hidden': !visibleForBlock(block.key),
              }"
              @click="emit('select', block.key)"
            >
              <div class="builder-block__toolbar">
                <div class="builder-block__toolbar-start">
                  <button
                    type="button"
                    class="builder-block__drag-handle"
                    draggable="true"
                    title="Sleep om te verplaatsen"
                    aria-label="Sleep om te verplaatsen"
                    @click.stop
                    @dragstart="onBlockDragStart($event, block.key)"
                    @dragend="onBlockDragEnd"
                  >
                    <i class="ki-filled ki-menu" />
                  </button>
                  <span class="builder-block__type">{{ block.label }}</span>
                </div>
                <div class="builder-block__actions" @click.stop>
                  <button
                    type="button"
                    class="builder-icon-btn"
                    :class="{ 'builder-icon-btn--inactive': !visibleForBlock(block.key) }"
                    :title="visibilityTitle(block.key)"
                    :aria-label="visibilityTitle(block.key)"
                    @click="emit('toggle-visibility', block.key)"
                  >
                    <i class="ki-filled" :class="visibleForBlock(block.key) ? 'ki-eye' : 'ki-eye-slash'" />
                  </button>
                  <button
                    v-if="index > 0"
                    type="button"
                    class="builder-icon-btn"
                    title="Omhoog"
                    @click="emit('move', block.key, -1)"
                  >
                    <i class="ki-filled ki-up" />
                  </button>
                  <button
                    v-if="index < blocks.length - 1"
                    type="button"
                    class="builder-icon-btn"
                    title="Omlaag"
                    @click="emit('move', block.key, 1)"
                  >
                    <i class="ki-filled ki-down" />
                  </button>
                  <button type="button" class="builder-icon-btn builder-icon-btn--danger" title="Verwijderen" @click="emit('remove', block.key)">
                    <i class="ki-filled ki-trash" />
                  </button>
                </div>
              </div>

              <div class="builder-block__preview bg-gradient-to-br text-white" :class="accentClass(block)">
                <i class="ki-filled text-2xl opacity-90" :class="iconClass(block)" />
                <p class="builder-block__preview-title">
                  <slot name="preview" :block="block">{{ block.label }}</slot>
                </p>
              </div>
            </article>
          </template>

          <div
            class="builder-dropzone builder-dropzone--inline"
            :class="{ 'builder-dropzone--active': dragOverIndex === blocks.length }"
          >
            <span v-if="dragOverIndex === blocks.length" class="builder-dropzone__label">Plaats hier</span>
          </div>
        </template>

        <article
          class="builder-block builder-block--fixed"
          data-fixed-key="footer"
          :class="{
            'builder-block--selected': selectedKey === 'footer',
            'builder-block--hidden': !visibleForBlock('footer'),
          }"
          @click="emit('select', 'footer')"
        >
          <div class="builder-block__toolbar builder-block__toolbar--static">
            <div class="builder-block__toolbar-start">
              <span class="builder-block__type">Footer</span>
            </div>
            <div class="builder-block__actions" @click.stop>
              <button
                type="button"
                class="builder-icon-btn"
                :class="{ 'builder-icon-btn--inactive': !visibleForBlock('footer') }"
                :title="visibilityTitle('footer')"
                :aria-label="visibilityTitle('footer')"
                @click="emit('toggle-visibility', 'footer')"
              >
                <i class="ki-filled" :class="visibleForBlock('footer') ? 'ki-eye' : 'ki-eye-slash'" />
              </button>
            </div>
          </div>
          <div
            class="builder-block__preview builder-block__preview--muted bg-gradient-to-br text-white"
            :class="fixedMeta('footer').accent"
          >
            <i class="ki-filled text-2xl opacity-90" :class="fixedMeta('footer').icon" />
            <p class="builder-block__preview-title">Footer</p>
            <p class="builder-block__preview-hint">Klik om footer-instellingen te bewerken.</p>
          </div>
        </article>

        <article
          class="builder-block builder-block--fixed"
          data-fixed-key="copyright"
          :class="{ 'builder-block--selected': selectedKey === 'copyright' }"
          @click="emit('select', 'copyright')"
        >
          <div class="builder-block__toolbar builder-block__toolbar--static">
            <div class="builder-block__toolbar-start">
              <span class="builder-block__type">Copyright</span>
            </div>
          </div>
          <div
            class="builder-block__preview builder-block__preview--muted bg-gradient-to-br text-white"
            :class="fixedMeta('copyright').accent"
          >
            <i class="ki-filled text-2xl opacity-90" :class="fixedMeta('copyright').icon" />
            <p class="builder-block__preview-title">
              {{ copyrightPreview || 'Copyrighttekst' }}
            </p>
            <p class="builder-block__preview-hint">Klik om de copyrighttekst te bewerken.</p>
          </div>
        </article>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="popoutOpen && mode === 'live'"
        class="builder-preview-popout"
        role="dialog"
        aria-modal="true"
        aria-label="Pagina voorbeeld volledig scherm"
      >
        <header class="builder-preview-popout__header">
          <span class="builder-preview-popout__title">Pagina voorbeeld</span>
          <button
            type="button"
            class="builder-preview-popout__close kt-btn kt-btn-sm kt-btn-outline"
            title="Sluiten (Esc)"
            aria-label="Volledig scherm sluiten"
            @click="closePopout"
          >
            <i class="ki-filled ki-cross" aria-hidden="true" />
            Sluiten
          </button>
        </header>
        <iframe :key="`popout-${previewUrl}`" :src="previewUrl" title="Pagina voorbeeld" class="builder-preview-popout__frame" />
      </div>
    </Teleport>
  </main>
</template>
