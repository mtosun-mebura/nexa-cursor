<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'

defineProps<{
  modelValue: number
  options: readonly number[]
  ariaLabel: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number]
}>()

const open = ref(false)
const root = ref<HTMLElement | null>(null)

function toggle(): void {
  open.value = !open.value
}

function choose(value: number): void {
  emit('update:modelValue', value)
  open.value = false
}

function onDocumentClick(event: MouseEvent): void {
  if (!open.value || !root.value) return
  if (!root.value.contains(event.target as Node)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('click', onDocumentClick, true))
onUnmounted(() => document.removeEventListener('click', onDocumentClick, true))
</script>

<template>
  <div ref="root" class="taxi-portal-perpage-select relative inline-flex">
    <button
      type="button"
      class="taxi-portal-datatable-select taxi-portal-datatable-perpage-select taxi-portal-perpage-select__trigger inline-flex items-center justify-between gap-1"
      :aria-expanded="open"
      :aria-haspopup="'listbox'"
      :aria-label="ariaLabel"
      @click.stop="toggle"
    >
      <span class="tabular-nums">{{ modelValue }}</span>
      <i class="ki-filled ki-down text-xs opacity-70 shrink-0" aria-hidden="true"></i>
    </button>
    <ul
      v-show="open"
      class="taxi-portal-perpage-select__menu absolute left-0 top-full z-[100] mt-1 min-w-full list-none rounded-md border border-gray-200 bg-white p-1 shadow-lg dark:border-gray-600 dark:bg-[#111827]"
      role="listbox"
      :aria-label="ariaLabel"
    >
      <li v-for="option in options" :key="option" role="none">
        <button
          type="button"
          class="taxi-portal-perpage-select__option flex w-full items-center justify-between gap-2 rounded px-2.5 py-1.5 text-left text-sm text-foreground hover:bg-gray-100 dark:hover:bg-gray-800"
          :class="
            option === modelValue
              ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
              : ''
          "
          role="option"
          :aria-selected="option === modelValue"
          @click.stop="choose(option)"
        >
          <span class="tabular-nums">{{ option }}</span>
          <i
            v-if="option === modelValue"
            class="ki-filled ki-check text-sm shrink-0"
            aria-hidden="true"
          ></i>
        </button>
      </li>
    </ul>
  </div>
</template>
