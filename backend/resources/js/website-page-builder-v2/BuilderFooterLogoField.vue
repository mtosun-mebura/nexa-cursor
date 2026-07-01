<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { websiteImagePreviewSrc } from './section-data-normalize'

const props = defineProps<{
  modelValue: string
  uploadUrl: string
  moduleName?: string | null
  label?: string
  fallbackLogoUrl?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [string]
}>()

const uploading = ref(false)
const dragOver = ref(false)
const previewToken = ref(Date.now())

const hasCustomLogo = computed(() => props.modelValue.trim() !== '')

const effectiveLogoUrl = computed(() => {
  const custom = props.modelValue.trim()
  if (custom) {
    return custom
  }
  return (props.fallbackLogoUrl ?? '').trim()
})

const isBrandingFallback = computed(() => !hasCustomLogo.value && effectiveLogoUrl.value !== '')

const displayPreviewSrc = computed(() => {
  return websiteImagePreviewSrc(effectiveLogoUrl.value, previewToken.value)
})

const previewLoadFailed = ref(false)

watch(
  () => [props.modelValue, props.fallbackLogoUrl],
  () => {
    previewToken.value = Date.now()
    previewLoadFailed.value = false
  },
)

function onPreviewError() {
  previewLoadFailed.value = true
}

function patchUrl(url: string) {
  emit('update:modelValue', url)
}

async function uploadFile(file: File) {
  const allowed = ['image/svg+xml', 'image/png', 'image/jpeg', 'image/jpg', 'image/gif']
  if (!allowed.includes(file.type)) {
    alert('Alleen SVG, PNG, JPG en GIF zijn toegestaan.')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    alert('Max. 2MB.')
    return
  }

  uploading.value = true
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const form = new FormData()
    form.append('logo', file)
    if (props.moduleName) {
      form.append('module', props.moduleName)
    }
    const res = await fetch(props.uploadUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
      },
      body: form,
    })
    const json = await res.json()
    if (!res.ok || !json.success || !json.logo_url) {
      throw new Error(json.message ?? 'Upload mislukt')
    }
    patchUrl(String(json.logo_url))
    previewToken.value = Date.now()
  } catch (error) {
    alert(error instanceof Error ? error.message : 'Upload mislukt')
  } finally {
    uploading.value = false
  }
}

function onFilePick(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (file) {
    void uploadFile(file)
  }
}

function onDrop(event: DragEvent) {
  event.preventDefault()
  dragOver.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) {
    void uploadFile(file)
  }
}

function removeLogo() {
  patchUrl('')
}
</script>

<template>
  <div class="builder-field builder-field--footer-logo">
    <span v-if="label">{{ label }}</span>
    <p v-if="isBrandingFallback" class="builder-field-hint">
      Standaardlogo uit Algemene instellingen. Upload een eigen logo om dit te overschrijven.
    </p>
    <div class="builder-footer-logo">
      <div v-if="displayPreviewSrc && !previewLoadFailed" class="builder-footer-logo__preview-wrap">
        <img
          :src="displayPreviewSrc"
          alt="Footer logo voorbeeld"
          class="builder-footer-logo__preview"
          @error="onPreviewError"
        />
        <button
          v-if="hasCustomLogo"
          type="button"
          class="builder-footer-logo__remove kt-btn kt-btn-xs kt-btn-ghost text-destructive"
          title="Logo verwijderen"
          aria-label="Logo verwijderen"
          @click="removeLogo"
        >
          <i class="ki-filled ki-trash" aria-hidden="true" />
        </button>
      </div>
      <div v-else-if="!displayPreviewSrc || previewLoadFailed" class="builder-footer-logo__placeholder" aria-hidden="true">
        <i class="ki-filled ki-picture" />
        <span>{{ previewLoadFailed ? 'Logo laden mislukt' : 'Geen logo' }}</span>
      </div>
      <label
        class="builder-footer-logo__upload"
        :class="{ 'builder-footer-logo__upload--drag': dragOver }"
        @dragover.prevent="dragOver = true"
        @dragleave.prevent="dragOver = false"
        @drop="onDrop"
      >
        <span class="builder-footer-logo__upload-title">
          {{ uploading ? 'Uploaden…' : 'Klik of sleep afbeelding' }}
        </span>
        <span class="builder-footer-logo__upload-hint">SVG, PNG, JPG, GIF (max. 2MB)</span>
        <input
          type="file"
          class="hidden"
          accept="image/svg+xml,image/png,image/jpeg,image/jpg,image/gif"
          :disabled="uploading"
          @change="onFilePick"
        />
      </label>
    </div>
  </div>
</template>

<style scoped>
.builder-field-hint {
  margin: 0;
  font-size: 0.75rem;
  color: var(--muted-foreground);
}

.builder-footer-logo {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  flex-wrap: wrap;
  width: 100%;
}

.builder-footer-logo__preview-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  flex-shrink: 0;
  width: min(100%, 13rem);
  min-height: 5.5rem;
  padding: 0.65rem 0.85rem;
  border: 1px solid var(--border);
  border-radius: 0.5rem;
  background-color: #f8fafc;
  background-image:
    linear-gradient(45deg, #e2e8f0 25%, transparent 25%),
    linear-gradient(-45deg, #e2e8f0 25%, transparent 25%),
    linear-gradient(45deg, transparent 75%, #e2e8f0 75%),
    linear-gradient(-45deg, transparent 75%, #e2e8f0 75%);
  background-size: 14px 14px;
  background-position: 0 0, 0 7px, 7px -7px, -7px 0;
}

:global(.dark) .builder-footer-logo__preview-wrap {
  background-color: #27272a;
  background-image:
    linear-gradient(45deg, #3f3f46 25%, transparent 25%),
    linear-gradient(-45deg, #3f3f46 25%, transparent 25%),
    linear-gradient(45deg, transparent 75%, #3f3f46 75%),
    linear-gradient(-45deg, transparent 75%, #3f3f46 75%);
}

.builder-footer-logo__preview {
  display: block;
  width: auto;
  max-width: 100%;
  height: 5rem;
  max-height: 5rem;
  object-fit: contain;
  object-position: center;
  border: 0;
  border-radius: 0;
  background: transparent;
}

.builder-footer-logo__remove {
  flex-shrink: 0;
}

.builder-footer-logo__remove:hover {
  background: color-mix(in srgb, #dc2626 10%, transparent);
}

.builder-footer-logo__placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  width: min(100%, 8rem);
  min-width: 5.5rem;
  min-height: 4.5rem;
  padding: 0.5rem;
  border: 1px solid var(--border);
  border-radius: 0.5rem;
  background: color-mix(in srgb, var(--muted) 25%, transparent);
  color: var(--muted-foreground);
  font-size: 0.75rem;
  flex-shrink: 0;
}

.builder-footer-logo__placeholder .ki-filled {
  font-size: 1.25rem;
  opacity: 0.7;
}

.builder-footer-logo__upload {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.2rem;
  flex: 1 1 12rem;
  min-width: min(100%, 12rem);
  max-width: 20rem;
  min-height: 4.5rem;
  padding: 0.65rem;
  border: 1px dashed var(--border);
  border-radius: 0.75rem;
  background: color-mix(in srgb, var(--muted) 30%, transparent);
  cursor: pointer;
  text-align: center;
  transition: border-color 0.15s, background 0.15s;
}

.builder-footer-logo__upload--drag {
  border-color: color-mix(in srgb, var(--primary) 55%, var(--border));
  background: color-mix(in srgb, var(--primary) 8%, transparent);
}

.builder-footer-logo__upload-title {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--foreground);
}

.builder-footer-logo__upload-hint {
  font-size: 0.75rem;
  color: var(--muted-foreground);
}
</style>
