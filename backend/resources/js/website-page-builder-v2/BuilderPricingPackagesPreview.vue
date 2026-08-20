<script setup lang="ts">
import { computed, inject, ref, type ComputedRef } from 'vue'

type PricingPackage = {
  name?: string
  audience?: string
  price?: string
  offer?: string
  free_months?: string | number
  period?: string
  badge?: string
  highlighted?: boolean | string | number
  cta_text?: string
  cta_url?: string
  features?: unknown
  features_text?: unknown
}

type NexaPricing = {
  packages?: PricingPackage[]
}

const injectedPricing = inject<ComputedRef<NexaPricing> | NexaPricing | undefined>('nexaPricing', undefined)
const injectedEditUrl = inject<ComputedRef<string> | string | undefined>('nexaPricingEditUrl', undefined)

const pricing = computed<NexaPricing>(() => {
  const raw = injectedPricing && typeof injectedPricing === 'object' && 'value' in injectedPricing
    ? injectedPricing.value
    : injectedPricing
  return raw && typeof raw === 'object' ? raw : {}
})

const editUrl = computed(() => {
  const raw = injectedEditUrl && typeof injectedEditUrl === 'object' && 'value' in injectedEditUrl
    ? injectedEditUrl.value
    : injectedEditUrl
  return typeof raw === 'string' ? raw : ''
})

const packages = computed(() => {
  const list = pricing.value.packages
  return Array.isArray(list) ? list.filter((item) => item && typeof item === 'object') : []
})

const openFeatures = ref<Record<number, boolean>>({})

function packageFeatures(pkg: PricingPackage): string[] {
  const fromArray = Array.isArray(pkg.features)
    ? pkg.features.map((line) => String(line).trim()).filter(Boolean)
    : []
  if (fromArray.length > 0) {
    return fromArray
  }
  if (typeof pkg.features_text === 'string') {
    return pkg.features_text.split(/\r\n|\r|\n/).map((line) => line.trim()).filter(Boolean)
  }
  return []
}

function isHighlighted(pkg: PricingPackage): boolean {
  const value = pkg.highlighted
  return value === true || value === 1 || value === '1'
}

function text(value: unknown): string {
  const raw = value == null ? '' : String(value).trim()
  return raw === '' ? '—' : raw
}

function freeMonthsLabel(value: unknown): string {
  const months = Number.parseInt(String(value ?? '0'), 10)
  if (!Number.isFinite(months) || months <= 0) {
    return '—'
  }
  return months === 1 ? '1 maand gratis' : `${months} maanden gratis`
}

function featuresOpen(index: number): boolean {
  return openFeatures.value[index] === true
}

function toggleFeatures(index: number): void {
  openFeatures.value = {
    ...openFeatures.value,
    [index]: !featuresOpen(index),
  }
}
</script>

<template>
  <div class="nexa-pricing-preview">
    <div class="nexa-pricing-preview__notice">
      <p>Pakketten komen uit Admin → Prijzen en zijn hier alleen ter inzage.</p>
      <a
        v-if="editUrl"
        :href="editUrl"
        class="kt-btn kt-btn-outline kt-btn-sm mt-2 inline-flex w-full justify-center sm:w-auto"
      >
        Prijzen bewerken
      </a>
    </div>

    <div v-if="packages.length === 0" class="text-sm text-muted-foreground">
      Er zijn nog geen maandpakketten ingesteld.
    </div>

    <article
      v-for="(pkg, index) in packages"
      :key="`package-${index}`"
      class="nexa-pricing-package"
      :class="{ 'nexa-pricing-package--highlighted': isHighlighted(pkg) }"
      :data-package-index="index"
    >
      <header class="nexa-pricing-package__header">
        <div class="nexa-pricing-package__title-row">
          <h3 class="nexa-pricing-package__name">{{ text(pkg.name) }}</h3>
          <span v-if="pkg.badge" class="nexa-pricing-package__badge">{{ text(pkg.badge) }}</span>
        </div>
        <p class="nexa-pricing-package__price">
          <span class="nexa-pricing-package__amount">€ {{ text(pkg.price) }}</span>
          <span class="nexa-pricing-package__period">/ {{ text(pkg.period) }}</span>
        </p>
        <p class="nexa-pricing-package__audience">{{ text(pkg.audience) }}</p>
      </header>

      <dl class="nexa-pricing-package__meta">
        <div class="nexa-pricing-package__row">
          <dt>Aanbieding</dt>
          <dd>{{ text(pkg.offer) }}</dd>
        </div>
        <div class="nexa-pricing-package__row">
          <dt>Gratis</dt>
          <dd>{{ freeMonthsLabel(pkg.free_months) }}</dd>
        </div>
        <div class="nexa-pricing-package__row">
          <dt>Knop</dt>
          <dd>{{ text(pkg.cta_text) }}</dd>
        </div>
        <div class="nexa-pricing-package__row">
          <dt>URL</dt>
          <dd class="nexa-pricing-package__url">{{ text(pkg.cta_url) }}</dd>
        </div>
        <div class="nexa-pricing-package__row">
          <dt>Aanbevolen</dt>
          <dd>{{ isHighlighted(pkg) ? 'Ja' : 'Nee' }}</dd>
        </div>
      </dl>

      <div class="nexa-pricing-package__features">
        <button
          type="button"
          class="nexa-pricing-package__features-toggle"
          :aria-expanded="featuresOpen(index) ? 'true' : 'false'"
          @click="toggleFeatures(index)"
        >
          <span>Kenmerken ({{ packageFeatures(pkg).length }})</span>
          <i
            class="ki-filled text-xs"
            :class="featuresOpen(index) ? 'ki-up' : 'ki-down'"
            aria-hidden="true"
          />
        </button>
        <ul v-if="featuresOpen(index)" class="nexa-pricing-package__feature-list">
          <li v-if="packageFeatures(pkg).length === 0" class="text-muted-foreground">Geen kenmerken.</li>
          <li v-for="(feature, featureIndex) in packageFeatures(pkg)" :key="`${index}-${featureIndex}`">
            <i class="ki-filled ki-check text-green-500 shrink-0" aria-hidden="true" />
            <span>{{ feature }}</span>
          </li>
        </ul>
      </div>
    </article>
  </div>
</template>

<style scoped>
.nexa-pricing-preview {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  width: 100%;
  min-width: 0;
  max-width: 100%;
  box-sizing: border-box;
}

.nexa-pricing-preview__notice {
  border: 1px solid var(--border);
  border-radius: 0.5rem;
  background: color-mix(in oklab, var(--muted) 35%, transparent);
  padding: 0.65rem 0.75rem;
  font-size: 0.75rem;
  line-height: 1.45;
  color: var(--muted-foreground);
  min-width: 0;
}

.nexa-pricing-package {
  width: 100%;
  min-width: 0;
  max-width: 100%;
  border: 1px solid var(--border);
  border-radius: 0.65rem;
  background: var(--card, var(--background));
  overflow: hidden;
  box-sizing: border-box;
}

.nexa-pricing-package--highlighted {
  border-color: color-mix(in oklab, var(--primary) 45%, var(--border));
  box-shadow: inset 0 0 0 1px color-mix(in oklab, var(--primary) 20%, transparent);
}

.nexa-pricing-package__header {
  padding: 0.7rem 0.75rem 0.55rem;
  border-bottom: 1px solid var(--border);
  min-width: 0;
}

.nexa-pricing-package__title-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.35rem 0.5rem;
  min-width: 0;
}

.nexa-pricing-package__name {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 650;
  line-height: 1.25;
  color: var(--foreground);
  min-width: 0;
  overflow-wrap: anywhere;
}

.nexa-pricing-package__badge {
  display: inline-flex;
  align-items: center;
  max-width: 100%;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  font-size: 0.65rem;
  font-weight: 600;
  line-height: 1.3;
  color: var(--primary);
  background: color-mix(in oklab, var(--primary) 12%, transparent);
  overflow-wrap: anywhere;
}

.nexa-pricing-package__price {
  margin: 0.35rem 0 0;
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 0.25rem;
  min-width: 0;
}

.nexa-pricing-package__amount {
  font-size: 1.05rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  color: var(--foreground);
}

.nexa-pricing-package__period {
  font-size: 0.75rem;
  color: var(--muted-foreground);
}

.nexa-pricing-package__audience {
  margin: 0.2rem 0 0;
  font-size: 0.75rem;
  line-height: 1.4;
  color: var(--muted-foreground);
  overflow-wrap: anywhere;
}

.nexa-pricing-package__meta {
  margin: 0;
  padding: 0.35rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}

.nexa-pricing-package__row {
  display: grid;
  grid-template-columns: minmax(4.5rem, 5.5rem) minmax(0, 1fr);
  gap: 0.35rem 0.6rem;
  align-items: start;
  padding: 0.28rem 0;
  border-bottom: 1px dashed color-mix(in oklab, var(--border) 80%, transparent);
  min-width: 0;
}

.nexa-pricing-package__row:last-child {
  border-bottom: 0;
}

.nexa-pricing-package__row dt {
  margin: 0;
  font-size: 0.7rem;
  font-weight: 560;
  color: var(--muted-foreground);
  line-height: 1.35;
}

.nexa-pricing-package__row dd {
  margin: 0;
  font-size: 0.78rem;
  color: var(--foreground);
  line-height: 1.35;
  min-width: 0;
  overflow-wrap: anywhere;
}

.nexa-pricing-package__url {
  word-break: break-all;
}

.nexa-pricing-package__features {
  border-top: 1px solid var(--border);
  min-width: 0;
}

.nexa-pricing-package__features-toggle {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.55rem 0.75rem;
  border: 0;
  background: transparent;
  color: var(--foreground);
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  text-align: left;
}

.nexa-pricing-package__features-toggle:hover {
  background: color-mix(in oklab, var(--muted) 40%, transparent);
}

.nexa-pricing-package__feature-list {
  list-style: none;
  margin: 0;
  padding: 0 0.75rem 0.7rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 0;
}

.nexa-pricing-package__feature-list li {
  display: flex;
  align-items: flex-start;
  gap: 0.4rem;
  font-size: 0.75rem;
  line-height: 1.4;
  color: var(--foreground);
  min-width: 0;
}

.nexa-pricing-package__feature-list li span {
  min-width: 0;
  overflow-wrap: anywhere;
}

@media (max-width: 360px) {
  .nexa-pricing-package__row {
    grid-template-columns: 1fr;
    gap: 0.1rem;
  }
}
</style>
