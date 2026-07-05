<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { getByPath } from './nested-data'

const SIZE_HEIGHTS: Record<string, number> = { small: 200, normal: 300, large: 400 }

const props = defineProps<{
  data: Record<string, unknown>
  googleMapsApiKey: string
  googleMapsMapId: string
  postcodeLookupUrl: string
  label: string
}>()

const emit = defineEmits<{
  patch: [Record<string, unknown>]
}>()

const mapContainerRef = ref<HTMLElement | null>(null)
const lookupLoading = ref(false)
const mapAuthFailed = ref(false)

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const gmaps = () => (window as any).google?.maps

let mapInstance: any = null
let mapMarker: any = null
let infoWindow: any = null
let geocoder: any = null
let geocodeSeq = 0
let geocodeTimer: ReturnType<typeof setTimeout> | null = null
let scriptEl: HTMLScriptElement | null = null
let mutationObserver: MutationObserver | null = null

const cityOnly = computed(() => !!getByPath(props.data, 'map_city_only'))
const mapSize = computed(() => String(getByPath(props.data, 'map_size') ?? 'normal'))
const mapZoom = computed(() => {
  const z = Number(getByPath(props.data, 'map_zoom') ?? 17)
  return z >= 1 && z <= 20 ? z : 17
})

const previewHeight = computed(() => SIZE_HEIGHTS[mapSize.value] ?? SIZE_HEIGHTS.normal)

function str(key: string): string {
  const v = getByPath(props.data, key)
  return v === null || v === undefined ? '' : String(v)
}

function bool(key: string): boolean {
  const v = getByPath(props.data, key)
  return v === true || v === 1 || v === '1'
}

function patch(fields: Record<string, unknown>) {
  emit('patch', fields)
}

function patchField(key: string, value: unknown) {
  patch({ [key]: value })
}

function onCityOnlyChange(event: Event) {
  const checked = (event.target as HTMLInputElement).checked
  if (checked) {
    patch({
      map_city_only: true,
      map_postcode: '',
      map_huisnummer: '',
      map_street: '',
      map_lat: '',
      map_lng: '',
    })
    scheduleGeocode(50)
    return
  }
  patch({ map_city_only: false })
}

function onPostcodeInput(event: Event) {
  const value = (event.target as HTMLInputElement).value.toUpperCase().replace(/\s+/g, '')
  patchField('map_postcode', value)
}

function addressBalloonText(): string {
  const street = str('map_street')
  const huisnummer = str('map_huisnummer')
  const postcode = str('map_postcode')
  const city = str('map_city')
  const line1 = [street, huisnummer].filter(Boolean).join(' ')
  const line2 = [postcode, city].filter(Boolean).join(' ')
  if (line1 && line2) return `${line1}, ${line2}`
  return line1 || line2
}

function searchAddress(): string {
  if (cityOnly.value) {
    return str('map_city').trim()
  }
  const parts: string[] = []
  const street = str('map_street').trim()
  const huisnummer = str('map_huisnummer').trim()
  const postcode = str('map_postcode').trim()
  const city = str('map_city').trim()
  if (street) parts.push(street)
  if (huisnummer) parts.push(huisnummer)
  if (postcode || city) parts.push([postcode, city].filter(Boolean).join(' '))
  return parts.join(', ').trim()
}

function removeMarker(marker: typeof mapMarker) {
  if (!marker) return
  if ('setMap' in marker && typeof marker.setMap === 'function') {
    marker.setMap(null)
  } else if ('map' in marker) {
    marker.map = null
  }
}

function createMarker(position: { lat: number; lng: number }, map: unknown) {
  const maps = gmaps()
  if (!maps) return null
  if (maps.marker?.AdvancedMarkerElement) {
    return new maps.marker.AdvancedMarkerElement({ map, position })
  }
  return new maps.Marker({ map, position })
}

function updateBalloon() {
  const maps = gmaps()
  if (!mapInstance || !mapMarker || !maps) return
  infoWindow?.close()
  infoWindow = null
  if (!bool('map_show_address_balloon')) return
  const addr = addressBalloonText().trim()
  if (!addr || !maps.InfoWindow) return
  infoWindow = new maps.InfoWindow({
    content: `<div style="padding:4px 8px 6px;font-size:14px;color:#000;line-height:1.25;margin:0;">${addr.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>`,
  })
  infoWindow.open(mapInstance, mapMarker)
}

function updateMapPreview(lat: number, lng: number) {
  const maps = gmaps()
  if (mapAuthFailed.value || !maps?.Map || !mapContainerRef.value) {
    return
  }
  const center = { lat, lng }
  const mapId = props.googleMapsMapId.trim() || 'DEMO_MAP_ID'
  const options: Record<string, unknown> = {
    center,
    zoom: mapZoom.value,
    scrollwheel: false,
    mapTypeControl: true,
    streetViewControl: false,
    fullscreenControl: false,
    zoomControl: true,
    mapId,
  }
  if (!mapInstance) {
    mapInstance = new maps.Map(mapContainerRef.value, options)
    mapMarker = createMarker(center, mapInstance)
  } else {
    mapInstance.setCenter(center)
    mapInstance.setZoom(mapZoom.value)
    removeMarker(mapMarker)
    mapMarker = createMarker(center, mapInstance)
  }
  updateBalloon()
  setTimeout(() => {
    if (mapInstance && maps.event) {
      maps.event.trigger(mapInstance, 'resize')
    }
  }, 100)
}

function geocodeAddress(notifyOnFail = false) {
  const maps = gmaps()
  if (!maps?.Geocoder) return
  const address = searchAddress()
  if (!address) return
  if (!geocoder) geocoder = new maps.Geocoder()
  const seq = ++geocodeSeq
  geocoder.geocode({ address }, (results: any[], status: string) => {
    if (seq !== geocodeSeq) return
    if (status === 'OK' && results?.[0]?.geometry?.location) {
      const loc = results[0].geometry.location
      const lat = typeof loc.lat === 'function' ? loc.lat() : loc.lat
      const lng = typeof loc.lng === 'function' ? loc.lng() : loc.lng
      if (typeof lat === 'number' && typeof lng === 'number') {
        patch({ map_lat: String(lat), map_lng: String(lng) })
        updateMapPreview(lat, lng)
      }
    } else if (notifyOnFail) {
      alert('Adres niet gevonden op de kaart.')
    }
  })
}

function scheduleGeocode(delay = 250) {
  if (geocodeTimer) clearTimeout(geocodeTimer)
  geocodeTimer = setTimeout(() => geocodeAddress(false), delay)
}

async function lookupPostcode() {
  if (cityOnly.value) return
  const postcode = str('map_postcode').trim().toUpperCase().replace(/\s+/g, '')
  const huisnummer = str('map_huisnummer').trim()
  if (!/^[1-9][0-9]{3}[A-Z]{2}$/.test(postcode)) {
    alert('Ongeldig postcode formaat. Gebruik 1234AB')
    return
  }
  if (!huisnummer) {
    alert('Vul een huisnummer in.')
    return
  }
  lookupLoading.value = true
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const res = await fetch(props.postcodeLookupUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
      },
      body: JSON.stringify({ postcode, huisnummer }),
    })
    const data = await res.json()
    if (!data.success) {
      throw new Error(data.message ?? 'Adres niet gevonden.')
    }
    const patchData: Record<string, unknown> = {
      map_street: data.street ?? '',
      map_city: data.city ?? '',
    }
    if (data.latitude != null && data.longitude != null) {
      patchData.map_lat = String(data.latitude)
      patchData.map_lng = String(data.longitude)
    }
    patch(patchData)
    if (data.latitude != null && data.longitude != null) {
      updateMapPreview(Number(data.latitude), Number(data.longitude))
    }
  } catch (error) {
    alert(error instanceof Error ? error.message : 'Zoeken mislukt.')
  } finally {
    lookupLoading.value = false
  }
}

function showMapFallback() {
  mapAuthFailed.value = true
  if (!mapContainerRef.value) return
  mapContainerRef.value.innerHTML =
    '<div class="builder-footer-map__fallback">Kaart niet beschikbaar. Controleer de Google Maps API-sleutel en facturering.</div>'
}

function initMapFromStoredCoords() {
  const lat = Number(str('map_lat'))
  const lng = Number(str('map_lng'))
  if (Number.isFinite(lat) && Number.isFinite(lng)) {
    updateMapPreview(lat, lng)
    return
  }
  geocodeAddress(false)
}

function loadGoogleMaps() {
  const apiKey = props.googleMapsApiKey.trim()
  if (!apiKey || !mapContainerRef.value) return

  const callbackName = `builderFooterMapInit_${Math.random().toString(36).slice(2)}`
  ;(window as unknown as Record<string, () => void>)[callbackName] = () => {
    initMapFromStoredCoords()
  }
  ;(window as unknown as { gm_authFailure?: () => void }).gm_authFailure = () => {
    showMapFallback()
  }

  scriptEl = document.createElement('script')
  scriptEl.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=marker&callback=${callbackName}&loading=async`
  scriptEl.async = true
  document.head.appendChild(scriptEl)

  mutationObserver = new MutationObserver(() => {
    if (mapAuthFailed.value || !mapContainerRef.value) return
    const err = mapContainerRef.value.querySelector('[role="alertdialog"], .xxGHyP-dialog-view')
    if (err) showMapFallback()
  })
  mutationObserver.observe(mapContainerRef.value, { childList: true, subtree: true })
}

watch(mapZoom, (zoom) => {
  if (mapInstance) mapInstance.setZoom(zoom)
})

watch(mapSize, () => {
  setTimeout(() => {
    const maps = gmaps()
    if (mapInstance && maps?.event) {
      maps.event.trigger(mapInstance, 'resize')
    }
  }, 100)
})

watch(
  () => [str('map_lat'), str('map_lng')],
  ([lat, lng]) => {
    const la = Number(lat)
    const ln = Number(lng)
    if (Number.isFinite(la) && Number.isFinite(ln) && mapInstance) {
      updateMapPreview(la, ln)
    }
  },
)

onMounted(() => {
  if (props.googleMapsApiKey.trim()) {
    loadGoogleMaps()
  }
})

onUnmounted(() => {
  if (geocodeTimer) clearTimeout(geocodeTimer)
  mutationObserver?.disconnect()
  if (scriptEl?.parentNode) {
    scriptEl.parentNode.removeChild(scriptEl)
  }
})
</script>

<template>
  <div class="builder-footer-map">
    <p class="builder-footer-map__hint">
      Postcode + huisnummer zoeken, of alleen plaats invoeren.
    </p>

    <label class="builder-footer-map__toggle">
      <input
        type="checkbox"
        class="kt-checkbox"
        :checked="cityOnly"
        @change="onCityOnlyChange"
      />
      <span>Alleen plaats invoeren</span>
    </label>

    <div class="builder-footer-map__search-row">
      <label class="builder-footer-map__field builder-footer-map__field--postcode">
        <span>Postcode</span>
        <input
          class="kt-input kt-input-sm uppercase"
          :value="str('map_postcode')"
          placeholder="1234AB"
          maxlength="7"
          :disabled="cityOnly"
          @input="onPostcodeInput"
          @blur="scheduleGeocode(100)"
        />
      </label>
      <label class="builder-footer-map__field builder-footer-map__field--huisnr">
        <span>Huisnr.</span>
        <input
          class="kt-input kt-input-sm"
          :value="str('map_huisnummer')"
          placeholder="1"
          :disabled="cityOnly"
          @input="patchField('map_huisnummer', ($event.target as HTMLInputElement).value)"
          @blur="scheduleGeocode(100)"
        />
      </label>
      <button
        type="button"
        class="kt-btn kt-btn-xs kt-btn-outline builder-footer-map__search-btn"
        :disabled="cityOnly || lookupLoading"
        @click="lookupPostcode"
      >
        {{ lookupLoading ? '…' : 'Zoeken' }}
      </button>
    </div>

    <div class="builder-footer-map__address-col">
      <label class="builder-footer-map__field">
        <span>Straat</span>
        <input class="kt-input kt-input-sm" :value="str('map_street')" readonly />
      </label>
      <label class="builder-footer-map__field">
        <span>Plaats</span>
        <input
          class="kt-input kt-input-sm"
          :value="str('map_city')"
          :readonly="!cityOnly"
          @input="patchField('map_city', ($event.target as HTMLInputElement).value)"
          @blur="cityOnly ? geocodeAddress(false) : undefined"
        />
      </label>
    </div>

    <div class="builder-footer-map__options-row">
      <label class="builder-footer-map__field builder-footer-map__field--size">
        <span>Grootte</span>
        <select
          class="kt-input kt-input-sm"
          :value="mapSize"
          @change="patchField('map_size', ($event.target as HTMLSelectElement).value)"
        >
          <option value="small">Klein</option>
          <option value="normal">Normaal</option>
          <option value="large">Groot</option>
        </select>
      </label>
      <label class="builder-footer-map__field builder-footer-map__field--zoom">
        <span>Zoom</span>
        <select
          class="kt-input kt-input-sm"
          :value="String(mapZoom)"
          @change="patchField('map_zoom', Number(($event.target as HTMLSelectElement).value))"
        >
          <option v-for="z in 20" :key="z" :value="String(z)">{{ z }}</option>
        </select>
      </label>
      <label class="builder-footer-map__toggle builder-footer-map__toggle--inline">
        <input
          type="checkbox"
          class="kt-checkbox"
          :checked="bool('map_show_address_balloon')"
          @change="patchField('map_show_address_balloon', ($event.target as HTMLInputElement).checked); updateBalloon()"
        />
        <span>Adres in ballon</span>
      </label>
    </div>

    <div v-if="googleMapsApiKey.trim()" class="builder-footer-map__preview-wrap">
      <div
        ref="mapContainerRef"
        class="builder-footer-map__preview"
        :style="{ height: `${previewHeight}px` }"
      />
    </div>
    <p v-else class="builder-footer-map__hint">Geen Google Maps API-sleutel geconfigureerd.</p>
  </div>
</template>

<style scoped>
.builder-footer-map {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.builder-footer-map__hint {
  margin: 0;
  font-size: 0.6875rem;
  color: var(--muted-foreground);
  line-height: 1.35;
}

.builder-footer-map__toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8125rem;
  cursor: pointer;
  align-self: flex-start;
}

.builder-footer-map__toggle--inline {
  align-self: end;
  min-height: 2rem;
}

.builder-footer-map__field {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.builder-footer-map__field > span {
  font-size: 0.6875rem;
  font-weight: 500;
  color: var(--muted-foreground);
}

.builder-footer-map__field :deep(.kt-input),
.builder-footer-map__field .kt-input {
  min-height: 2rem;
  padding-top: 0.3rem;
  padding-bottom: 0.3rem;
  font-size: 0.8125rem;
}

.builder-footer-map__search-row {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 0.45rem;
}

.builder-footer-map__field--postcode {
  max-width: 6.75rem;
}

.builder-footer-map__field--huisnr {
  max-width: 4.25rem;
}

.builder-footer-map__search-btn {
  width: auto;
  flex: 0 0 auto;
  min-height: 2rem;
  padding-inline: 0.65rem;
  white-space: nowrap;
}

.builder-footer-map__address-col {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  max-width: 50%;
}

.builder-footer-map__options-row {
  display: grid;
  grid-template-columns: minmax(0, 6.5rem) minmax(0, 4.25rem) minmax(0, 1fr);
  gap: 0.45rem;
  align-items: end;
}

.builder-footer-map__preview-wrap {
  margin-top: 0.15rem;
}

.builder-footer-map__preview {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: 0.45rem;
  background: color-mix(in srgb, var(--muted) 35%, transparent);
  overflow: hidden;
}

.builder-footer-map__fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 6rem;
  padding: 0.65rem;
  text-align: center;
  font-size: 0.75rem;
  color: var(--muted-foreground);
  background: color-mix(in srgb, var(--muted) 45%, transparent);
}

@media (max-width: 520px) {
  .builder-footer-map__address-col {
    max-width: 100%;
  }

  .builder-footer-map__field--postcode,
  .builder-footer-map__field--huisnr {
    max-width: none;
  }

  .builder-footer-map__options-row {
    grid-template-columns: 1fr 1fr;
  }

  .builder-footer-map__toggle--inline {
    grid-column: 1 / -1;
    align-self: flex-start;
  }
}
</style>
