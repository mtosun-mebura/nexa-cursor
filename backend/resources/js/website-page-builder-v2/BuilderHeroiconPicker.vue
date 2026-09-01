<script setup lang="ts">
import { computed, inject, nextTick, onUnmounted, ref, watch, type ComputedRef } from 'vue'
import type { HeroiconOption } from './types'

const props = defineProps<{
  modelValue: string
  label?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [string]
}>()

const injected = inject<ComputedRef<HeroiconOption[]>>('builderHeroicons')
const catalog = computed(() => injected?.value ?? [])

const open = ref(false)
const query = ref('')
const searchInput = ref<HTMLInputElement | null>(null)
let bodyOverflowBefore = ''

const selected = computed(() => catalog.value.find((icon) => icon.id === props.modelValue) ?? null)

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (q === '') {
    return catalog.value
  }
  return catalog.value.filter(
    (icon) => icon.label.toLowerCase().includes(q) || icon.id.toLowerCase().includes(q),
  )
})

const buttonLabel = computed(() => selected.value?.label ?? (props.modelValue.trim() !== '' ? props.modelValue : 'Icoon kiezen'))

function openPicker() {
  query.value = ''
  open.value = true
}

function closePicker() {
  open.value = false
}

function choose(id: string) {
  emit('update:modelValue', id)
  closePicker()
}

function onKeydown(event: KeyboardEvent) {
  if (event.key !== 'Escape' || !open.value) {
    return
  }
  event.preventDefault()
  event.stopPropagation()
  event.stopImmediatePropagation()
  closePicker()
}

watch(open, async (isOpen) => {
  if (isOpen) {
    bodyOverflowBefore = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', onKeydown, true)
    await nextTick()
    searchInput.value?.focus()
    return
  }
  document.body.style.overflow = bodyOverflowBefore
  window.removeEventListener('keydown', onKeydown, true)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown, true)
  if (open.value) {
    document.body.style.overflow = bodyOverflowBefore
  }
})
</script>

<template>
  <div class="builder-heroicon-picker">
    <span v-if="label" class="builder-heroicon-picker__field-label">{{ label }}</span>
    <div class="builder-heroicon-picker__row">
      <span class="builder-heroicon-picker__preview" aria-hidden="true">
        <svg
          v-if="selected"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          v-html="selected.svg"
        />
        <i v-else class="ki-filled ki-abstract-26" />
      </span>
      <button
        type="button"
        class="builder-heroicon-picker__trigger"
        :aria-label="selected ? `Icoon wijzigen: ${selected.label}` : 'Icoon kiezen'"
        :aria-expanded="open"
        aria-haspopup="dialog"
        @click="openPicker"
      >
        <span class="builder-heroicon-picker__meta">
          <span class="builder-heroicon-picker__name">{{ buttonLabel }}</span>
          <span v-if="selected" class="builder-heroicon-picker__id">{{ selected.id }}</span>
        </span>
        <span class="builder-heroicon-picker__action">Kiezen</span>
      </button>
    </div>
  </div>

  <Teleport to="body">
    <div
      v-if="open"
      class="builder-heroicon-picker-modal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="builder-heroicon-picker-title"
    >
      <button
        type="button"
        class="builder-heroicon-picker-modal__backdrop"
        aria-label="Icoonkiezer sluiten"
        @click="closePicker"
      />
      <div class="builder-heroicon-picker-modal__dialog">
        <header class="builder-heroicon-picker-modal__header">
          <div>
            <h2 id="builder-heroicon-picker-title" class="builder-heroicon-picker-modal__title">Icoon kiezen</h2>
            <p class="builder-heroicon-picker-modal__hint">Kies één Heroicon. Het icoon verschijnt meteen in het veld.</p>
          </div>
          <button
            type="button"
            class="builder-heroicon-picker-modal__close"
            title="Sluiten"
            aria-label="Sluiten"
            @click="closePicker"
          >
            <i class="ki-filled ki-cross" aria-hidden="true" />
          </button>
        </header>
        <div class="builder-heroicon-picker-modal__toolbar">
          <input
            ref="searchInput"
            v-model="query"
            type="search"
            class="kt-input"
            placeholder="Zoek op naam of id…"
            aria-label="Heroicons zoeken"
          />
          <p class="builder-heroicon-picker-modal__count">{{ filtered.length }} iconen</p>
        </div>
        <div class="builder-heroicon-picker-modal__grid-wrap">
          <p v-if="filtered.length === 0" class="builder-heroicon-picker-modal__empty">Geen iconen gevonden.</p>
          <div v-else class="builder-heroicon-picker-modal__grid">
            <button
              v-for="icon in filtered"
              :key="icon.id"
              type="button"
              class="builder-heroicon-picker-modal__option"
              :class="{ 'builder-heroicon-picker-modal__option--selected': icon.id === modelValue }"
              :aria-pressed="icon.id === modelValue"
              :title="icon.label"
              @click="choose(icon.id)"
            >
              <span class="builder-heroicon-picker-modal__option-icon" aria-hidden="true">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  v-html="icon.svg"
                />
              </span>
              <span class="builder-heroicon-picker-modal__option-label">{{ icon.label }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.builder-heroicon-picker {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 0;
  width: 100%;
}

.builder-heroicon-picker__field-label {
  color: var(--muted-foreground);
  font-weight: 500;
  font-size: 0.875rem;
}

.builder-heroicon-picker__row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.builder-heroicon-picker__trigger {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  width: 13.5rem;
  flex: none;
  min-height: 3.25rem;
  padding: 0.45rem 0.75rem;
  border: 1px solid var(--border);
  border-radius: 0.5rem;
  background: var(--background);
  color: var(--foreground);
  text-align: left;
  cursor: pointer;
  box-sizing: border-box;
  transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}

.builder-heroicon-picker__trigger:hover {
  border-color: color-mix(in srgb, var(--primary) 40%, var(--border));
  background: color-mix(in srgb, var(--muted) 35%, transparent);
}

.builder-heroicon-picker__trigger:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 22%, transparent);
}

.builder-heroicon-picker__preview {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 3.25rem;
  height: 3.25rem;
  flex-shrink: 0;
  border-radius: 0.55rem;
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--muted) 40%, transparent);
  color: var(--foreground);
}

.builder-heroicon-picker__preview svg,
.builder-heroicon-picker__preview i {
  width: 1.85rem;
  height: 1.85rem;
}

.builder-heroicon-picker__meta {
  display: flex;
  flex-direction: column;
  min-width: 0;
  flex: 1;
  gap: 0.05rem;
}

.builder-heroicon-picker__name {
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1.25;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.builder-heroicon-picker__id {
  font-size: 0.6875rem;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  color: var(--muted-foreground);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.builder-heroicon-picker__action {
  flex-shrink: 0;
  font-size: 0.75rem;
  color: var(--muted-foreground);
}

.builder-heroicon-picker-modal {
  position: fixed;
  inset: 0;
  z-index: 11050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
}

.builder-heroicon-picker-modal__backdrop {
  position: absolute;
  inset: 0;
  border: 0;
  margin: 0;
  padding: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  cursor: pointer;
}

.builder-heroicon-picker-modal__dialog {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  width: min(52rem, 100%);
  max-height: min(86vh, 44rem);
  border: 1px solid var(--border);
  border-radius: 1rem;
  background: var(--background);
  box-shadow: 0 24px 64px color-mix(in srgb, #000 45%, transparent);
}

.builder-heroicon-picker-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.15rem 0.75rem;
  border-bottom: 1px solid var(--border);
}

.builder-heroicon-picker-modal__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--foreground);
}

.builder-heroicon-picker-modal__hint {
  margin: 0.25rem 0 0;
  font-size: 0.8125rem;
  color: var(--muted-foreground);
}

.builder-heroicon-picker-modal__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border: 0;
  border-radius: 0.45rem;
  background: transparent;
  color: var(--muted-foreground);
  cursor: pointer;
}

.builder-heroicon-picker-modal__close:hover {
  background: color-mix(in srgb, var(--muted) 55%, transparent);
  color: var(--foreground);
}

.builder-heroicon-picker-modal__toolbar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1.15rem 0.5rem;
}

.builder-heroicon-picker-modal__toolbar .kt-input {
  flex: 1;
  min-width: 0;
}

.builder-heroicon-picker-modal__count {
  margin: 0;
  flex-shrink: 0;
  font-size: 0.75rem;
  color: var(--muted-foreground);
  white-space: nowrap;
}

.builder-heroicon-picker-modal__grid-wrap {
  min-height: 0;
  flex: 1;
  overflow: auto;
  padding: 0.5rem 1.15rem 1.15rem;
}

.builder-heroicon-picker-modal__empty {
  margin: 1.5rem 0;
  text-align: center;
  color: var(--muted-foreground);
  font-size: 0.875rem;
}

.builder-heroicon-picker-modal__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(6.75rem, 1fr));
  gap: 0.5rem;
}

.builder-heroicon-picker-modal__option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  padding: 0.7rem 0.45rem 0.55rem;
  border: 0;
  border-radius: 0.65rem;
  background: color-mix(in srgb, var(--muted) 18%, transparent);
  color: var(--foreground);
  cursor: pointer;
  text-align: center;
  transition: background 0.15s, box-shadow 0.15s;
}

.builder-heroicon-picker-modal__option:hover {
  background: color-mix(in srgb, var(--primary) 8%, var(--muted) 18%);
}

.builder-heroicon-picker-modal__option--selected {
  background: color-mix(in srgb, var(--primary) 14%, transparent);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary) 22%, transparent);
}

.builder-heroicon-picker-modal__option-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  color: var(--foreground);
}

.builder-heroicon-picker-modal__option-icon svg {
  width: 1.5rem;
  height: 1.5rem;
}

.builder-heroicon-picker-modal__option-label {
  font-size: 0.6875rem;
  line-height: 1.25;
  color: var(--muted-foreground);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}

.builder-heroicon-picker-modal__option--selected .builder-heroicon-picker-modal__option-label {
  color: var(--foreground);
  font-weight: 500;
}
</style>
