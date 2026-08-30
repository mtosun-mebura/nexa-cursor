<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps<{
  open: boolean
  title: string
  previewUrl: string
}>()

const emit = defineEmits<{
  close: []
}>()

const iframeRef = ref<HTMLIFrameElement | null>(null)
const iframeKey = ref(0)
const loading = ref(true)
const isDark = ref(false)

const cacheBustedUrl = computed(() => {
  if (!props.previewUrl) {
    return ''
  }
  const joiner = props.previewUrl.includes('?') ? '&' : '?'
  return `${props.previewUrl}${joiner}_r=${iframeKey.value}`
})

function syncThemeFromAdmin() {
  isDark.value =
    document.documentElement.classList.contains('dark') ||
    document.body.classList.contains('dark')
}

function close() {
  emit('close')
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    close()
  }
}

function callIframeRestart(): boolean {
  const frame = iframeRef.value
  if (!frame) {
    return false
  }
  try {
    const win = frame.contentWindow as (Window & { nexaBlockPreviewRestart?: () => void }) | null
    if (win && typeof win.nexaBlockPreviewRestart === 'function') {
      win.nexaBlockPreviewRestart()
      return true
    }
  } catch {
    // ignore
  }
  return false
}

function restartAnimation() {
  // Always reload the iframe so JS, media and CSS animations start from scratch.
  refreshPreview()
}

function refreshPreview() {
  loading.value = true
  iframeKey.value += 1
}

function onIframeLoad() {
  loading.value = false
  nextTick(() => {
    // Play once after load (iframe also self-inits; this is a safety net).
    window.setTimeout(() => {
      callIframeRestart()
    }, 60)
  })
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      syncThemeFromAdmin()
      loading.value = true
      iframeKey.value += 1
    }
  }
)

onMounted(() => {
  syncThemeFromAdmin()
  window.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="builder-block-preview-modal"
      :class="{ 'builder-block-preview-modal--dark': isDark }"
      role="dialog"
      aria-modal="true"
      :aria-label="`Voorbeeld: ${title}`"
    >
      <button
        type="button"
        class="builder-block-preview-modal__backdrop"
        aria-label="Voorbeeld sluiten"
        @click="close"
      />
      <div class="builder-block-preview-modal__dialog">
        <header class="builder-block-preview-modal__header">
          <div class="min-w-0">
            <p class="builder-block-preview-modal__eyebrow">Voorbeeld</p>
            <h2 class="builder-block-preview-modal__title">{{ title }}</h2>
          </div>
          <div class="builder-block-preview-modal__actions">
            <button
              type="button"
              class="builder-block-preview-modal__icon-btn"
              title="Voorbeeld opnieuw laden"
              aria-label="Voorbeeld opnieuw laden"
              @click="restartAnimation"
            >
              <i class="ki-filled ki-arrows-circle" aria-hidden="true" />
            </button>
            <button
              type="button"
              class="builder-block-preview-modal__icon-btn"
              title="Sluiten"
              aria-label="Sluiten"
              @click="close"
            >
              <i class="ki-filled ki-cross" aria-hidden="true" />
            </button>
          </div>
        </header>
        <div class="builder-block-preview-modal__body">
          <div v-if="loading" class="builder-block-preview-modal__loading" aria-live="polite">
            Voorbeeld laden…
          </div>
          <iframe
            :key="iframeKey"
            ref="iframeRef"
            class="builder-block-preview-modal__frame"
            :class="{ 'is-loading': loading }"
            :src="cacheBustedUrl"
            title="Blokvoorbeeld"
            @load="onIframeLoad"
          />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.builder-block-preview-modal {
  position: fixed;
  inset: 0;
  z-index: 12000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  --bpm-surface: #ffffff;
  --bpm-surface-muted: #f1f5f9;
  --bpm-body: #f8fafc;
  --bpm-border: color-mix(in srgb, #0f172a 12%, transparent);
  --bpm-text: #0f172a;
  --bpm-muted: #64748b;
  --bpm-backdrop: color-mix(in srgb, #0f172a 42%, transparent);
  --bpm-shadow: 0 24px 64px color-mix(in srgb, #0f172a 28%, transparent);
}

.builder-block-preview-modal--dark {
  --bpm-surface: #0f172a;
  --bpm-surface-muted: #111827;
  --bpm-body: #020617;
  --bpm-border: color-mix(in srgb, #f8fafc 12%, transparent);
  --bpm-text: #f8fafc;
  --bpm-muted: #94a3b8;
  --bpm-backdrop: color-mix(in srgb, #020617 62%, transparent);
  --bpm-shadow: 0 24px 64px color-mix(in srgb, #000 55%, transparent);
}

.builder-block-preview-modal__backdrop {
  position: absolute;
  inset: 0;
  border: 0;
  margin: 0;
  padding: 0;
  background: var(--bpm-backdrop);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  cursor: pointer;
}

.builder-block-preview-modal__dialog {
  position: relative;
  z-index: 1;
  width: min(960px, 100%);
  height: min(820px, calc(100vh - 2.5rem));
  display: flex;
  flex-direction: column;
  border-radius: 1rem;
  border: 1px solid var(--bpm-border);
  background: var(--bpm-surface);
  box-shadow: var(--bpm-shadow);
  overflow: hidden;
  color: var(--bpm-text);
}

.builder-block-preview-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--bpm-border);
  background: var(--bpm-surface-muted);
}

.builder-block-preview-modal__eyebrow {
  margin: 0;
  font-size: 0.6875rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--bpm-muted);
}

.builder-block-preview-modal__title {
  margin: 0.15rem 0 0;
  font-size: 1rem;
  font-weight: 650;
  color: var(--bpm-text);
  line-height: 1.3;
}

.builder-block-preview-modal__actions {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  flex-shrink: 0;
}

.builder-block-preview-modal__icon-btn {
  width: 2.15rem;
  height: 2.15rem;
  border-radius: 0.55rem;
  border: 1px solid var(--bpm-border);
  background: var(--bpm-surface);
  color: var(--bpm-text);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.builder-block-preview-modal__icon-btn:hover {
  border-color: color-mix(in srgb, var(--primary, #2563eb) 45%, var(--bpm-border));
  color: var(--primary, #2563eb);
}

.builder-block-preview-modal__body {
  position: relative;
  flex: 1;
  min-height: 0;
  background: var(--bpm-body);
}

.builder-block-preview-modal__loading {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  color: var(--bpm-muted);
  background: color-mix(in srgb, var(--bpm-body) 88%, transparent);
  pointer-events: none;
}

.builder-block-preview-modal__frame {
  width: 100%;
  height: 100%;
  border: 0;
  background: var(--bpm-body);
}

.builder-block-preview-modal__frame.is-loading {
  opacity: 0.35;
}
</style>
