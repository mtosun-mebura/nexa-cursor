<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps<{
  text: string
  label: string
}>()

const open = ref(false)
const triggerRef = ref<HTMLButtonElement | null>(null)
const panelStyle = ref<Record<string, string>>({})
let hideTimer: number | null = null

function clearHideTimer() {
  if (hideTimer === null) {
    return
  }
  window.clearTimeout(hideTimer)
  hideTimer = null
}

function positionPanel() {
  const trigger = triggerRef.value
  if (!trigger) {
    return
  }
  const rect = trigger.getBoundingClientRect()
  const width = Math.min(22 * 16, window.innerWidth - 24)
  let left = rect.left
  if (left + width > window.innerWidth - 12) {
    left = Math.max(12, window.innerWidth - width - 12)
  }
  const spaceBelow = window.innerHeight - rect.bottom
  if (spaceBelow > 160) {
    panelStyle.value = {
      top: `${Math.round(rect.bottom + 6)}px`,
      left: `${Math.round(left)}px`,
      width: `${Math.round(width)}px`,
      bottom: 'auto',
    }
    return
  }
  panelStyle.value = {
    top: 'auto',
    bottom: `${Math.round(window.innerHeight - rect.top + 6)}px`,
    left: `${Math.round(left)}px`,
    width: `${Math.round(width)}px`,
  }
}

function show() {
  clearHideTimer()
  open.value = true
  void nextTick(positionPanel)
}

function hideSoon() {
  clearHideTimer()
  hideTimer = window.setTimeout(() => {
    open.value = false
  }, 180)
}

function toggle() {
  if (open.value) {
    open.value = false
    return
  }
  show()
}

function onViewportChange() {
  if (open.value) {
    positionPanel()
  }
}

onMounted(() => {
  window.addEventListener('scroll', onViewportChange, true)
  window.addEventListener('resize', onViewportChange)
})

onBeforeUnmount(() => {
  clearHideTimer()
  window.removeEventListener('scroll', onViewportChange, true)
  window.removeEventListener('resize', onViewportChange)
})
</script>

<template>
  <span
    class="builder-field-info"
    @mouseenter="show"
    @mouseleave="hideSoon"
  >
    <button
      ref="triggerRef"
      type="button"
      class="builder-field-info__btn"
      :aria-label="`Uitleg: ${label}`"
      :aria-expanded="open ? 'true' : 'false'"
      @click.stop.prevent="toggle"
      @focus="show"
      @blur="hideSoon"
    >
      <svg
        class="builder-field-info__icon"
        viewBox="0 0 16 16"
        width="16"
        height="16"
        aria-hidden="true"
        focusable="false"
      >
        <circle cx="8" cy="8" r="8" fill="currentColor" />
        <circle cx="8" cy="4.55" r="1.05" fill="#fff" />
        <rect x="7.15" y="6.7" width="1.7" height="5.35" rx="0.75" fill="#fff" />
      </svg>
    </button>
    <Teleport to="body">
      <span
        v-if="open"
        class="builder-field-info__panel"
        role="tooltip"
        :style="panelStyle"
        @mouseenter="show"
        @mouseleave="hideSoon"
      >{{ props.text }}</span>
    </Teleport>
  </span>
</template>

<style scoped>
.builder-field-info {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
}

.builder-field-info__btn {
  width: 1rem;
  height: 1rem;
  padding: 0;
  border: 0;
  background: transparent;
  color: #2563eb;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border-radius: 999px;
}

.builder-field-info__btn:hover,
.builder-field-info__btn:focus-visible {
  color: #1d4ed8;
}

.builder-field-info__btn:focus-visible {
  outline: 2px solid color-mix(in srgb, #2563eb 55%, transparent);
  outline-offset: 2px;
}

.builder-field-info__icon {
  display: block;
  width: 1rem;
  height: 1rem;
}
</style>

<style>
.builder-field-info__panel {
  position: fixed;
  z-index: 12050;
  max-height: min(18rem, 55vh);
  overflow-y: auto;
  padding: 0.625rem 0.75rem;
  border-radius: 0.375rem;
  border: 1px solid var(--border);
  background: var(--background);
  box-shadow: 0 4px 14px rgb(0 0 0 / 0.14);
  font-size: 0.75rem;
  line-height: 1.45;
  color: var(--muted-foreground);
  scrollbar-width: thin;
  scrollbar-color: #94a3b8 transparent;
}

html.dark .builder-field-info__panel {
  scrollbar-color: #94a3b8 transparent;
}
</style>
