<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

type FlowbiteWrapper = HTMLElement & {
  _flowbiteEditor?: {
    getHTML: () => string
    on: (event: string, callback: () => void) => void
    off: (event: string, callback: () => void) => void
    commands?: { setContent: (html: string) => void }
    chain?: () => { focus: () => { setContent: (html: string) => void; run: () => void } }
  }
}

const props = defineProps<{
  editorKey: string
  label: string
  modelValue: string
  placeholder?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [string]
}>()

const hostRef = ref<HTMLElement | null>(null)
let wrapperEl: FlowbiteWrapper | null = null
let onEditorUpdate: (() => void) | null = null

function getFlowbiteHtml(editorId: string, textareaId: string): string {
  const builder = (window as Window & {
    getFlowbiteWysiwygHtml?: (
      editorId: string,
      name: string,
      textareaId: string,
      placeholder: string,
      heightOpts?: { minH?: number; maxH?: number },
    ) => string
  }).getFlowbiteWysiwygHtml

  if (!builder) {
    return ''
  }

  return builder(editorId, '', textareaId, props.placeholder ?? 'Voeg hier uw tekst toe...', {
    minH: 200,
    maxH: 360,
  })
}

function syncFromEditor() {
  if (!wrapperEl?._flowbiteEditor) {
    return
  }
  const html = wrapperEl._flowbiteEditor.getHTML()
  if (html !== props.modelValue) {
    emit('update:modelValue', html)
  }
}

function setEditorContent(html: string) {
  const editor = wrapperEl?._flowbiteEditor
  if (!editor) {
    return
  }
  if (editor.chain) {
    editor.chain().focus().setContent(html || '').run()
    return
  }
  const textarea = wrapperEl?.querySelector('[data-editor-input]') as HTMLTextAreaElement | null
  if (textarea) {
    textarea.value = html
  }
}

async function mountEditor() {
  if (!hostRef.value) {
    return
  }

  const editorId = `builder-wysiwyg-${props.editorKey}`
  const textareaId = `${editorId}-input`
  const html = getFlowbiteHtml(editorId, textareaId)
  if (!html) {
    return
  }

  hostRef.value.innerHTML = html
  const textarea = hostRef.value.querySelector('[data-editor-input]') as HTMLTextAreaElement | null
  if (textarea) {
    textarea.value = props.modelValue ?? ''
  }

  await nextTick()

  const init = (window as Window & { initFlowbiteWysiwyg?: (container: HTMLElement) => void }).initFlowbiteWysiwyg
  init?.(hostRef.value)

  wrapperEl = hostRef.value.querySelector('[data-flowbite-wysiwyg]') as FlowbiteWrapper | null
  if (!wrapperEl?._flowbiteEditor) {
    return
  }

  onEditorUpdate = () => syncFromEditor()
  wrapperEl._flowbiteEditor.on('update', onEditorUpdate)
}

onMounted(() => {
  void mountEditor()
})

onUnmounted(() => {
  if (wrapperEl?._flowbiteEditor && onEditorUpdate) {
    wrapperEl._flowbiteEditor.off('update', onEditorUpdate)
  }
  if (hostRef.value) {
    const destroy = (window as Window & { destroyFlowbiteWysiwygIn?: (container: HTMLElement) => void }).destroyFlowbiteWysiwygIn
    destroy?.(hostRef.value)
  }
  wrapperEl = null
  onEditorUpdate = null
})

watch(
  () => props.modelValue,
  (value) => {
    if (!wrapperEl?._flowbiteEditor) {
      return
    }
    const current = wrapperEl._flowbiteEditor.getHTML()
    if ((value ?? '') !== current) {
      setEditorContent(value ?? '')
    }
  },
)
</script>

<template>
  <div class="builder-field builder-field--wysiwyg">
    <span>{{ label }}</span>
    <div ref="hostRef" class="builder-wysiwyg-host" />
  </div>
</template>

<style scoped>
.builder-wysiwyg-host {
  width: 100%;
  min-width: 0;
  overflow: hidden;
}

.builder-wysiwyg-host :deep(.flowbite-wysiwyg-wrapper) {
  width: 100%;
  max-width: none !important;
  overflow: hidden;
}

.builder-wysiwyg-host :deep(.flowbite-wysiwyg-toolbar) {
  min-width: 0;
  overflow: hidden;
}

.builder-wysiwyg-host :deep(.flowbite-wysiwyg-toolbar > div) {
  flex-wrap: wrap;
  row-gap: 0.25rem;
  min-width: 0;
  overflow: hidden;
}

.builder-wysiwyg-host :deep(.flowbite-wysiwyg-icon-picker) {
  flex: 1 1 100%;
  min-width: 0;
  max-width: 100%;
  flex-wrap: wrap;
}

.builder-wysiwyg-host :deep(.flowbite-wysiwyg-icon-pick-btn .sr-only) {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.builder-wysiwyg-host :deep(.flowbite-wysiwyg-content) {
  max-width: 100%;
}
</style>
