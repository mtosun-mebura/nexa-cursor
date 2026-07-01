<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { BuilderBootstrap, PageMetaForm } from './types'

const props = defineProps<{
  open: boolean
  bootstrap: BuilderBootstrap
  modelValue: PageMetaForm
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  'update:modelValue': [value: PageMetaForm]
  saved: [payload: { pageMeta: PageMetaForm; themeName: string; themeSlug: string; page: BuilderBootstrap['page'] }]
}>()

const form = ref<PageMetaForm>(cloneMeta(props.modelValue))
const saving = ref(false)
const error = ref<string | null>(null)
const seoLoading = ref(false)
const seoApplySections = ref(true)
const slugManuallyEdited = ref(false)

const options = computed(() => props.bootstrap.pageMetaOptions)
const themes = computed(() => options.value.themes)
const singleTheme = computed(() => themes.value.length === 1 ? themes.value[0] : null)
const metaDescriptionLength = computed(() => form.value.metaDescription.length)

const moduleChoice = computed({
  get: () => form.value.moduleName ?? '',
  set: (value: string) => {
    form.value.moduleName = value === '' ? null : value
  },
})

function cloneMeta(value: PageMetaForm): PageMetaForm {
  return JSON.parse(JSON.stringify(value)) as PageMetaForm
}

function close() {
  emit('update:open', false)
}

function slugify(value: string): string {
  return value
    .toLowerCase()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9\-]/g, '')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
}

function onTitleInput() {
  if (!options.value.slugReadonly && !slugManuallyEdited.value) {
    form.value.slug = slugify(form.value.title)
  }
}

function onSlugInput() {
  slugManuallyEdited.value = true
}

function onModuleChange() {
  const selected = options.value.modules.find((m) => m.name === moduleChoice.value)
  if (selected?.themeId) {
    form.value.frontendThemeId = selected.themeId
  }
}

function csrfToken(): string {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

async function generateSeo() {
  seoLoading.value = true
  error.value = null
  try {
    const res = await fetch(props.bootstrap.routes.generateSeo, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        title: form.value.title,
        page_type: form.value.pageType,
        module_name: form.value.moduleName ?? '',
        slug: form.value.slug,
        company_id: form.value.companyId,
        include_sections: seoApplySections.value,
      }),
    })
    const json = (await res.json()) as {
      ok?: boolean
      message?: string
      data?: { title?: string; meta_description?: string }
    }
    if (!res.ok || !json.ok) {
      throw new Error(json.message ?? 'SEO genereren mislukt.')
    }
    if (json.data?.title) {
      form.value.title = json.data.title
      if (!slugManuallyEdited.value && !options.value.slugReadonly) {
        form.value.slug = slugify(json.data.title)
      }
    }
    if (json.data?.meta_description) {
      form.value.metaDescription = json.data.meta_description
    }
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'SEO genereren mislukt.'
  } finally {
    seoLoading.value = false
  }
}

async function save() {
  saving.value = true
  error.value = null
  try {
    const res = await fetch(props.bootstrap.routes.updateMeta, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        title: form.value.title,
        slug: form.value.slug,
        page_type: form.value.pageType,
        module_name: form.value.moduleName ?? '',
        frontend_theme_id: form.value.frontendThemeId,
        is_active: form.value.isActive,
        show_in_menu: form.value.showInMenu,
        sort_order: form.value.sortOrder,
        meta_description: form.value.metaDescription,
        company_id: form.value.companyId,
      }),
    })
    const json = (await res.json()) as {
      ok?: boolean
      message?: string
      pageMeta?: PageMetaForm
      page?: BuilderBootstrap['page']
      themeName?: string
      themeSlug?: string
      errors?: Record<string, string[]>
    }
    if (!res.ok || !json.ok || !json.pageMeta) {
      const firstError = json.errors ? Object.values(json.errors).flat()[0] : null
      throw new Error(firstError ?? json.message ?? 'Opslaan mislukt.')
    }
    emit('update:modelValue', json.pageMeta)
    emit('saved', {
      pageMeta: json.pageMeta,
      themeName: json.themeName ?? props.bootstrap.themeName,
      themeSlug: json.themeSlug ?? props.bootstrap.themeSlug,
      page: json.page ?? props.bootstrap.page,
    })
    close()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Opslaan mislukt.'
  } finally {
    saving.value = false
  }
}

watch(
  () => props.open,
  (open) => {
    if (open) {
      form.value = cloneMeta(props.modelValue)
      slugManuallyEdited.value = false
      error.value = null
    }
  }
)
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="builder-config-modal"
      role="dialog"
      aria-modal="true"
      aria-labelledby="builder-page-info-modal-title"
    >
      <button
        type="button"
        class="builder-config-modal__backdrop"
        aria-label="Sluiten"
        @click="close"
      />
      <div class="builder-config-modal__dialog builder-page-info-modal__dialog">
        <header class="builder-config-modal__header">
          <div class="builder-config-modal__header-text">
            <p class="text-xs uppercase tracking-wide text-muted-foreground">Pagina-informatie</p>
            <h2 id="builder-page-info-modal-title" class="text-lg font-semibold text-foreground mt-0.5">
              Pagina bewerken
            </h2>
            <p class="text-xs text-muted-foreground mt-0.5">
              Titel, slug, module, thema, SEO en zichtbaarheid
            </p>
          </div>
          <div class="builder-page-info-modal__header-actions">
            <button
              type="button"
              class="kt-btn kt-btn-sm kt-btn-outline shrink-0 builder-page-info-modal__seo-btn"
              :disabled="seoLoading || saving"
              @click="generateSeo"
            >
              <i class="ki-filled ki-magic me-1" aria-hidden="true" />
              SEO &amp; AI genereren
            </button>
            <button
              type="button"
              class="builder-config-modal__shrink-btn"
              title="Sluiten"
              aria-label="Sluiten"
              @click="close"
            >
              <i class="ki-filled ki-cross" aria-hidden="true" />
            </button>
          </div>
        </header>

        <div class="builder-config-modal__body">
          <div class="builder-page-info-modal__toolbar">
            <label class="builder-page-info-modal__switch">
              <span class="text-sm font-medium text-secondary-foreground">Menuitem</span>
              <select v-model="form.showInMenu" class="kt-input kt-input-sm w-[120px]">
                <option :value="true">Ja</option>
                <option :value="false">Nee</option>
              </select>
            </label>
            <label class="builder-page-info-modal__switch">
              <input v-model="form.isActive" type="checkbox" class="kt-switch kt-switch-sm shrink-0" />
              <span class="text-sm font-medium text-secondary-foreground">Actief (zichtbaar op de website)</span>
            </label>
          </div>

          <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full builder-page-info-modal__table">
            <tbody>
              <tr v-if="!options.isCentralWelcome">
                <td class="min-w-56 text-secondary-foreground font-normal">Bij welke module hoort deze pagina? *</td>
                <td class="min-w-48 w-full">
                  <select v-model="moduleChoice" class="kt-input" @change="onModuleChange">
                    <option value="">Geen (kernpagina's voor home, over ons, contact)</option>
                    <option v-for="module in options.modules" :key="module.name" :value="module.name">
                      {{ module.displayName }}
                    </option>
                  </select>
                  <p class="text-xs text-muted-foreground mt-1 mb-0">
                    Home, Over ons, Contact en Custom kunnen aan een module gekoppeld worden.
                  </p>
                </td>
              </tr>

              <tr v-if="options.tenant.visible">
                <td class="min-w-56 text-secondary-foreground font-normal align-top">Bedrijf (tenant)</td>
                <td class="min-w-48 w-full">
                  <template v-if="options.tenant.storedCompanyName">
                    <p class="text-sm text-foreground mb-1 font-medium">{{ options.tenant.storedCompanyName }}</p>
                    <p class="text-xs text-muted-foreground mb-0">Deze pagina is aan dit bedrijf gekoppeld.</p>
                  </template>
                  <template v-else-if="options.tenant.showCompanyDropdown">
                    <select v-model="form.companyId" class="kt-input" required>
                      <option :value="null" disabled>— Selecteer een bedrijf —</option>
                      <option v-for="company in options.tenant.companies" :key="company.id" :value="company.id">
                        {{ company.name }}
                      </option>
                    </select>
                  </template>
                  <template v-else-if="options.tenant.effectiveCompanyName">
                    <p class="text-sm text-foreground mb-1 font-medium">{{ options.tenant.effectiveCompanyName }}</p>
                    <p class="text-xs text-muted-foreground mb-0">De actieve tenant in de sidebar wordt gebruikt.</p>
                  </template>
                </td>
              </tr>

              <tr>
                <td class="text-secondary-foreground font-normal">Thema</td>
                <td>
                  <template v-if="singleTheme">
                    <span class="inline-flex items-center rounded-md bg-orange-100 px-3 py-1.5 text-sm font-medium text-orange-900 border border-orange-200 dark:bg-orange-500/20 dark:text-orange-100 dark:border-orange-400/40">
                      {{ singleTheme.name }}
                    </span>
                  </template>
                  <template v-else-if="themes.length > 1">
                    <select v-model="form.frontendThemeId" class="kt-input max-w-md" required>
                      <option v-for="theme in themes" :key="theme.id" :value="theme.id">{{ theme.name }}</option>
                    </select>
                  </template>
                  <span v-else class="text-sm text-muted-foreground">Geen gepubliceerd thema beschikbaar.</span>
                </td>
              </tr>

              <tr>
                <td class="text-secondary-foreground font-normal">Paginatype *</td>
                <td>
                  <select v-model="form.pageType" class="kt-input" required>
                    <option value="custom">Custom (tekstpagina)</option>
                    <option value="home">Home</option>
                    <option value="about">Over ons</option>
                    <option value="contact">Contact</option>
                    <option value="module">Module-pagina</option>
                  </select>
                </td>
              </tr>

              <tr>
                <td class="text-secondary-foreground font-normal">Titel *</td>
                <td>
                  <input v-model="form.title" type="text" class="kt-input" required autocomplete="off" @input="onTitleInput" />
                </td>
              </tr>

              <tr>
                <td class="min-w-56 text-secondary-foreground font-normal">Slug *</td>
                <td class="min-w-48 w-full">
                  <input
                    v-model="form.slug"
                    type="text"
                    class="kt-input"
                    required
                    pattern="[a-z0-9\-]+"
                    autocomplete="off"
                    :readonly="options.slugReadonly"
                    @input="onSlugInput"
                  />
                  <p class="text-xs text-muted-foreground mt-1 mb-0">
                    <template v-if="options.slugReadonly">Deze slug is gereserveerd en kan niet worden gewijzigd.</template>
                    <template v-else>Wordt automatisch ingevuld op basis van de titel. Alleen kleine letters, cijfers en streepjes.</template>
                  </p>
                </td>
              </tr>

              <tr>
                <td class="text-secondary-foreground font-normal align-top">SEO &amp; vindbaarheid</td>
                <td class="min-w-0 w-full">
                  <label class="block text-sm font-medium text-secondary-foreground mb-1" for="builder-page-meta-description">
                    Meta-omschrijving
                  </label>
                  <textarea
                    id="builder-page-meta-description"
                    v-model="form.metaDescription"
                    rows="3"
                    maxlength="500"
                    class="kt-input w-full min-h-[4.5rem]"
                    placeholder="Korte omschrijving voor Google en AI-zoekmachines (ideaal 150–160 tekens)"
                  />
                  <div class="flex flex-wrap items-center justify-between gap-2 mt-1.5">
                    <p class="text-xs text-muted-foreground mb-0 flex-1 min-w-[12rem]">
                      Gebruik <strong>SEO &amp; AI genereren</strong> voor titel en meta-teksten.
                    </p>
                    <span
                      class="text-xs text-muted-foreground tabular-nums shrink-0"
                      :class="{
                        'text-destructive': metaDescriptionLength > 160,
                        'text-success': metaDescriptionLength >= 120 && metaDescriptionLength <= 160,
                      }"
                    >
                      {{ metaDescriptionLength }} / 160
                    </span>
                  </div>
                  <label class="kt-label flex items-center gap-2 mt-3 mb-0">
                    <input v-model="seoApplySections" type="checkbox" class="kt-checkbox" />
                    <span class="text-sm text-muted-foreground">Ook hero-teksten invullen (indien aanwezig)</span>
                  </label>
                </td>
              </tr>

              <tr>
                <td class="text-secondary-foreground font-normal">Volgorde</td>
                <td>
                  <input v-model.number="form.sortOrder" type="number" class="kt-input w-24" min="0" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <footer class="builder-config-modal__footer">
          <div class="builder-config-modal__footer-status">
            <span v-if="error" class="builder-status builder-status--error">{{ error }}</span>
          </div>
          <div class="builder-config-modal__footer-actions">
            <button type="button" class="kt-btn kt-btn-outline" :disabled="saving" @click="close">Annuleren</button>
            <button type="button" class="kt-btn kt-btn-primary" :disabled="saving" @click="save">
              {{ saving ? 'Opslaan…' : 'Opslaan' }}
            </button>
          </div>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.builder-page-info-modal__dialog {
  max-width: min(90vw, 52rem);
}

.builder-page-info-modal__header-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.builder-page-info-modal__seo-btn {
  height: 2.25rem;
  min-height: 2.25rem;
  padding-top: 0;
  padding-bottom: 0;
  display: inline-flex;
  align-items: center;
}

.builder-page-info-modal__toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem 1.5rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border);
}

.builder-page-info-modal__switch {
  display: inline-flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
}

.builder-page-info-modal__table td {
  vertical-align: top;
  padding-top: 0.65rem;
  padding-bottom: 0.65rem;
}
</style>

<style>
.builder-config-modal {
  position: fixed;
  inset: 0;
  z-index: 10050;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
}

.builder-config-modal__backdrop {
  position: absolute;
  inset: 0;
  border: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  cursor: pointer;
}

.builder-config-modal__dialog {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  width: 90vw;
  max-width: 90vw;
  max-height: min(90vh, 56rem);
  border: 1px solid var(--border);
  border-radius: 1rem;
  background: var(--background);
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.28);
  overflow: hidden;
}

.builder-config-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--border);
  background: color-mix(in srgb, var(--background) 90%, var(--muted) 10%);
}

.builder-config-modal__header-text {
  min-width: 0;
}

.builder-config-modal__shrink-btn {
  width: 2.25rem;
  height: 2.25rem;
  border: 1px solid var(--border);
  border-radius: 0.55rem;
  background: var(--background);
  color: var(--muted-foreground);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  cursor: pointer;
}

.builder-config-modal__shrink-btn:hover {
  color: var(--foreground);
  border-color: color-mix(in srgb, var(--primary) 35%, var(--border));
  background: color-mix(in srgb, var(--muted) 25%, transparent);
}

.builder-config-modal__body {
  flex: 1;
  min-height: 0;
  overflow: auto;
  padding: 1.25rem 1.5rem;
}

.builder-config-modal__footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.85rem 1.25rem;
  border-top: 1px solid var(--border);
  background: color-mix(in srgb, var(--background) 92%, var(--muted) 8%);
}

.builder-config-modal__footer-status {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  min-width: 0;
}

.builder-config-modal__footer-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-left: auto;
}

.builder-status--error {
  color: #dc2626;
  font-size: 0.8125rem;
}
</style>
