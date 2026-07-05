<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { initPlaygroundStepComponents } from './metronic-playground-ktui'
import { PLAYGROUND_STEPS } from './metronic-playground-steps'

const steps = PLAYGROUND_STEPS
const stepIndex = ref(0)
const count = ref(0)

const activeStep = computed(() => steps[stepIndex.value] ?? steps[0])

function scheduleKtuiInit() {
  nextTick(() => {
    requestAnimationFrame(() => initPlaygroundStepComponents())
  })
}

function goToStep(index: number) {
  if (index < 0 || index >= steps.length) {
    return
  }
  stepIndex.value = index
}

function prevStep() {
  goToStep(stepIndex.value - 1)
}

function nextStep() {
  goToStep(stepIndex.value + 1)
}

watch(stepIndex, () => {
  if (activeStep.value.ktui) {
    scheduleKtuiInit()
  }
})

onMounted(() => {
  if (activeStep.value.ktui) {
    scheduleKtuiInit()
  }
})
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="kt-card">
      <div class="kt-card-content flex flex-col gap-4 py-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
              Stap {{ stepIndex + 1 }} / {{ steps.length }}
            </p>
            <h2 class="text-lg font-semibold text-foreground mt-1">{{ activeStep.title }}</h2>
            <p class="text-sm text-muted-foreground mt-1 max-w-2xl">{{ activeStep.description }}</p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <button
              type="button"
              class="kt-btn kt-btn-outline kt-btn-primary"
              :disabled="stepIndex === 0"
              @click="prevStep"
            >
              Vorige
            </button>
            <button
              type="button"
              class="kt-btn kt-btn-primary"
              :disabled="stepIndex === steps.length - 1"
              @click="nextStep"
            >
              Volgende
            </button>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <button
            v-for="(step, index) in steps"
            :key="step.id"
            type="button"
            class="kt-btn kt-btn-sm"
            :class="index === stepIndex ? 'kt-btn-primary' : 'kt-btn-outline'"
            @click="goToStep(index)"
          >
            {{ step.shortLabel }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="activeStep.id === 'intro'" class="kt-alert kt-alert-info" role="alert">
      <div class="kt-alert-title">Showroom voor Mijn Taxi</div>
      <div class="kt-alert-description">
        Gebruik <strong>Volgende</strong> of de knoppen hierboven om componenten stuk voor stuk te testen.
        Interactieve onderdelen (tabs, accordion, modal) worden na elke stap opnieuw aan KTUI gekoppeld.
      </div>
    </div>

    <div v-else-if="activeStep.id === 'buttons'" class="kt-card">
      <div class="kt-card-header">
        <h3 class="kt-card-title">Buttons & badges</h3>
      </div>
      <div class="kt-card-content flex flex-col gap-4">
        <div class="flex flex-wrap items-center gap-2">
          <button type="button" class="kt-btn kt-btn-primary" @click="count++">Primary (+1)</button>
          <button type="button" class="kt-btn kt-btn-outline kt-btn-primary">Outline</button>
          <button type="button" class="kt-btn kt-btn-ghost">Ghost</button>
          <button type="button" class="kt-btn kt-btn-danger">Danger</button>
          <button type="button" class="kt-btn kt-btn-success">Success</button>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <span class="kt-badge kt-badge-info">Info</span>
          <span class="kt-badge kt-badge-success">Success</span>
          <span class="kt-badge kt-badge-warning">Warning</span>
          <span class="kt-badge kt-badge-danger">Danger</span>
          <span class="text-sm text-muted-foreground">
            Count: <span class="font-semibold text-foreground">{{ count }}</span>
          </span>
        </div>
      </div>
    </div>

    <div v-else-if="activeStep.id === 'forms'" class="kt-card">
      <div class="kt-card-header">
        <h3 class="kt-card-title">Form controls</h3>
      </div>
      <div class="kt-card-content">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="flex flex-col gap-1">
            <span class="kt-label">Naam</span>
            <input class="kt-input" placeholder="Jan Jansen" />
          </label>
          <label class="flex flex-col gap-1">
            <span class="kt-label">E-mail</span>
            <input class="kt-input" placeholder="jan@voorbeeld.nl" />
          </label>
          <label class="flex flex-col gap-1 md:col-span-2">
            <span class="kt-label">Opmerking</span>
            <textarea class="kt-input" rows="3" placeholder="Bijv. bel bij aankomst"></textarea>
          </label>
          <label class="flex items-center gap-2 md:col-span-2">
            <input type="checkbox" class="kt-checkbox" />
            <span class="text-sm text-foreground">Voorbeeld checkbox</span>
          </label>
        </div>
      </div>
    </div>

    <div v-else-if="activeStep.id === 'alerts'" class="flex flex-col gap-4">
      <div class="kt-alert kt-alert-info" role="alert">
        <div class="kt-alert-title">Info</div>
        <div class="kt-alert-description">Algemene informatie voor de gebruiker.</div>
      </div>
      <div class="kt-alert kt-alert-success" role="alert">
        <div class="kt-alert-title">Success</div>
        <div class="kt-alert-description">Actie is gelukt.</div>
      </div>
      <div class="kt-alert kt-alert-warning" role="alert">
        <div class="kt-alert-title">Warning</div>
        <div class="kt-alert-description">Let op: controleer de gegevens.</div>
      </div>
      <div class="kt-alert kt-alert-danger" role="alert">
        <div class="kt-alert-title">Danger</div>
        <div class="kt-alert-description">Er is iets misgegaan.</div>
      </div>
    </div>

    <div v-else-if="activeStep.id === 'table'" class="kt-card">
      <div class="kt-card-header">
        <h3 class="kt-card-title">Table</h3>
      </div>
      <div class="kt-card-content">
        <div class="kt-scrollable-x-auto">
          <table class="kt-table table-auto kt-table-border">
            <thead>
              <tr>
                <th class="min-w-[180px]">Rit</th>
                <th class="min-w-[160px]">Datum</th>
                <th class="min-w-[140px]">Status</th>
                <th class="min-w-[120px] text-end">Bedrag</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Schiphol → Amsterdam</td>
                <td>2026-06-02 10:30</td>
                <td><span class="kt-badge kt-badge-info">Gepland</span></td>
                <td class="text-end">€ 52,00</td>
              </tr>
              <tr>
                <td>Amsterdam → Utrecht</td>
                <td>2026-05-30 18:10</td>
                <td><span class="kt-badge kt-badge-success">Voltooid</span></td>
                <td class="text-end">€ 84,50</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-else-if="activeStep.id === 'tabs'" class="kt-card">
      <div class="kt-card-header">
        <h3 class="kt-card-title">Tabs</h3>
      </div>
      <div class="kt-card-content">
        <div class="kt-tabs kt-tabs-line mb-4" data-kt-tabs="true">
          <div class="flex items-center gap-5 border-b border-border">
            <button class="kt-tab-toggle py-3 active" data-kt-tab-toggle="#playground_tab_overview" type="button">
              Overzicht
            </button>
            <button class="kt-tab-toggle py-3" data-kt-tab-toggle="#playground_tab_details" type="button">
              Details
            </button>
            <button class="kt-tab-toggle py-3" data-kt-tab-toggle="#playground_tab_notes" type="button">
              Notities
            </button>
          </div>
        </div>
        <div id="playground_tab_overview" class="kt-tab-pane active">
          <p class="text-sm text-muted-foreground">Tab 1 — korte samenvatting van een rit of klant.</p>
        </div>
        <div id="playground_tab_details" class="kt-tab-pane hidden">
          <p class="text-sm text-muted-foreground">Tab 2 — adres, chauffeur, voertuig.</p>
        </div>
        <div id="playground_tab_notes" class="kt-tab-pane hidden">
          <p class="text-sm text-muted-foreground">Tab 3 — opmerkingen en interne notities.</p>
        </div>
      </div>
    </div>

    <div v-else-if="activeStep.id === 'accordion'" class="kt-card">
      <div class="kt-card-header">
        <h3 class="kt-card-title">Accordion</h3>
      </div>
      <div class="kt-card-content">
        <div data-kt-accordion="true">
          <div class="kt-accordion-item not-last:border-b border-b-border" data-kt-accordion-item="true">
            <button
              type="button"
              class="kt-accordion-toggle py-4 w-full text-start"
              data-kt-accordion-toggle="#playground_acc_1"
            >
              Wat is Mijn Taxi?
            </button>
            <div id="playground_acc_1" class="kt-accordion-content hidden pb-4">
              <p class="text-sm text-muted-foreground">Klantportaal voor ritten, profiel en facturen.</p>
            </div>
          </div>
          <div class="kt-accordion-item not-last:border-b border-b-border" data-kt-accordion-item="true">
            <button
              type="button"
              class="kt-accordion-toggle py-4 w-full text-start"
              data-kt-accordion-toggle="#playground_acc_2"
            >
              Hoe annuleer ik een rit?
            </button>
            <div id="playground_acc_2" class="kt-accordion-content hidden pb-4">
              <p class="text-sm text-muted-foreground">Via rittenoverzicht → detail → annuleren (voorbeeld).</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="activeStep.id === 'modal'" class="kt-card">
      <div class="kt-card-header flex items-center justify-between gap-3">
        <h3 class="kt-card-title">Modal</h3>
        <button type="button" class="kt-btn kt-btn-outline kt-btn-primary" data-kt-modal-toggle="#playground_modal">
          Open modal
        </button>
      </div>
      <div class="kt-card-content">
        <p class="text-sm text-muted-foreground">
          Klik op <strong>Open modal</strong>. Sluiten kan via het kruisje, Sluiten of Oké.
        </p>
      </div>

      <div class="kt-modal" data-kt-modal="true" id="playground_modal">
        <div class="kt-modal-content max-w-[600px] top-[15%]">
          <div class="kt-modal-header py-4 px-5">
            <h3 class="kt-modal-title">Voorbeeld modal</h3>
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-modal-dismiss="true" type="button">
              <i class="ki-filled ki-cross"></i>
            </button>
          </div>
          <div class="kt-modal-body p-5">
            <p class="text-sm text-muted-foreground">
              Dit is een Metronic modal. Zelfde patroon voor factuur bekijken, rit annuleren, enz.
            </p>
          </div>
          <div class="kt-modal-footer px-5 py-4 flex justify-end gap-2">
            <button class="kt-btn kt-btn-ghost" data-kt-modal-dismiss="true" type="button">Sluiten</button>
            <button class="kt-btn kt-btn-primary" data-kt-modal-dismiss="true" type="button">Oké</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
