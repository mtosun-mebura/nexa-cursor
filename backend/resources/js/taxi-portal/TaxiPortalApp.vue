<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import TaxiPortalPerPageSelect from './TaxiPortalPerPageSelect.vue'

type TabKey = 'dashboard' | 'rides' | 'profile' | 'invoices'

type PortalRide = {
  id: number
  from: string
  to: string
  route: string
  at: string
  pickup_at_iso?: string | null
  status?: string
  status_label: string
  status_badge: string
  amount: string
  amount_raw?: number | null
  invoice_id: number | null
  has_invoice: boolean
  can_view_invoice_pdf?: boolean
}

type RidesSortKey = 'route' | 'date' | 'status' | 'amount'
type SortDir = 'asc' | 'desc'

type PortalRideDetail = PortalRide & {
  summary_text?: string
  summary_lines?: string[]
  customer_name?: string
  customer_phone?: string
  customer_email?: string
  passengers?: number
  payment_method?: string
  payment_status?: string
  distance_km?: number | null
  duration_minutes?: number | null
  customer_note?: string
  invoice_number?: string | null
}

type ChartPeriod = 'day' | 'month' | 'year'

type PortalDashboardStats = {
  total_rides: number
  completed_rides: number
  upcoming_rides: number
  total_spent: string
  total_spent_raw: number
  invoice_count: number
}

type PortalDashboard = {
  stats: PortalDashboardStats
  chart: {
    labels: string[]
    amounts: number[]
    has_data: boolean
    total: number
    period: ChartPeriod
    period_label: string
  }
}

type PortalInvoice = {
  id: number
  invoice_number: string
  ride_id: number | null
  date: string
  date_iso?: string | null
  status_label: string
  status_badge: string
  amount: string
  amount_raw?: number
  route: string | null
  from: string | null
  to: string | null
}

type InvoicesSortKey = 'invoice_number' | 'route' | 'date' | 'status' | 'amount'

type PortalProfile = {
  first_name: string
  last_name: string
  email: string
  phone: string
}

const VALID_TABS: TabKey[] = ['dashboard', 'rides', 'profile', 'invoices']

declare global {
  interface Window {
    ApexCharts?: new (
      el: HTMLElement,
      options: Record<string, unknown>
    ) => { render: () => void; destroy: () => void }
  }
}

function readPortalStateFromUrl(): { tab: TabKey; showNewRide: boolean } {
  const params = new URLSearchParams(window.location.search)
  const tabParam = params.get('tab')
  const hashTab = window.location.hash.replace(/^#/, '').trim()

  let resolvedTab: TabKey = 'dashboard'
  if (tabParam && (VALID_TABS as string[]).includes(tabParam)) {
    resolvedTab = tabParam as TabKey
  } else if (hashTab && (VALID_TABS as string[]).includes(hashTab)) {
    resolvedTab = hashTab as TabKey
  }

  const showNewRide =
    params.get('booking') === '1' || params.get('view') === 'new-ride'

  return { tab: showNewRide ? 'rides' : resolvedTab, showNewRide }
}

function syncPortalUrl() {
  const params = new URLSearchParams()
  params.set('tab', tab.value)
  if (showNewRide.value) {
    params.set('booking', '1')
  }
  const query = params.toString()
  const nextUrl = query ? `${window.location.pathname}?${query}` : window.location.pathname
  if (`${window.location.pathname}${window.location.search}` !== nextUrl) {
    history.replaceState(history.state, '', nextUrl)
  }
}

const initialPortalState = readPortalStateFromUrl()

const tab = ref<TabKey>(initialPortalState.tab)
const showNewRide = ref(initialPortalState.showNewRide)
const bookingSlot = ref<HTMLElement | null>(null)
const bookingMounted = ref(false)

const mountEl = document.getElementById('taxi-portal-app')
const logoAlt = mountEl?.dataset.logoAlt || 'Nexa'
const logoHref = mountEl?.dataset.logoHref || '#'
const logoLight = mountEl?.dataset.logoLight || '/metronic-v9.4.13/demo1/assets/media/app/default-logo.svg'
const logoDark = mountEl?.dataset.logoDark || '/metronic-v9.4.13/demo1/assets/media/app/default-logo-dark.svg'

const apiDashboardUrl = mountEl?.dataset.apiDashboard || '/mijn-taxi/api/dashboard'
const apiRidesUrl = mountEl?.dataset.apiRides || '/mijn-taxi/api/rides'
const apiRidesBase = (mountEl?.dataset.apiRidesBase || '/mijn-taxi/api/rides').replace(/\/$/, '')
const apiInvoicesUrl = mountEl?.dataset.apiInvoices || '/mijn-taxi/api/invoices'
const apiProfileUrl = mountEl?.dataset.apiProfile || '/mijn-taxi/api/profile'
const apiProfileUpdateUrl = mountEl?.dataset.apiProfileUpdate || '/mijn-taxi/api/profile'
const apiProfilePasswordUrl = mountEl?.dataset.apiProfilePassword || '/mijn-taxi/api/profile/password'
const apiInvoicePdfBase = (mountEl?.dataset.apiInvoicePdf || '/mijn-taxi/api/invoices').replace(/\/$/, '')

function csrfToken(): string {
  return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || ''
}

async function portalFetch<T>(url: string, options: RequestInit = {}): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...(options.headers as Record<string, string> | undefined),
  }
  if (options.method && options.method !== 'GET') {
    headers['X-CSRF-TOKEN'] = csrfToken()
    if (!(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json'
    }
  }

  const response = await fetch(url, {
    credentials: 'same-origin',
    ...options,
    headers,
  })

  const payload = await response.json().catch(() => ({}))
  if (!response.ok || payload.success === false) {
    const message =
      payload.message ||
      (payload.errors ? Object.values(payload.errors).flat().join(' ') : null) ||
      'Er ging iets mis.'
    throw new Error(message)
  }

  return payload as T
}

const title = computed(() => {
  if (showNewRide.value) {
    return 'Nieuwe rit'
  }
  switch (tab.value) {
    case 'dashboard':
      return 'Dashboard'
    case 'rides':
      return 'Mijn ritten'
    case 'profile':
      return 'Mijn gegevens'
    case 'invoices':
      return 'Facturen'
  }
})

const chartSectionTitle = computed(() => {
  switch (chartPeriod.value) {
    case 'month':
      return 'Kosten per maand'
    case 'year':
      return 'Kosten per jaar'
    default:
      return 'Kosten per dag'
  }
})

function dashboardApiUrl(period: ChartPeriod = chartPeriod.value): string {
  return `${apiDashboardUrl}?chart_period=${encodeURIComponent(period)}`
}

const dashboard = ref<PortalDashboard | null>(null)
const dashboardLoading = ref(false)
const dashboardError = ref<string | null>(null)
const dashboardLoaded = ref(false)
const chartPeriod = ref<ChartPeriod>('month')
const chartLoading = ref(false)
const costChartEl = ref<HTMLElement | null>(null)
let costChartInstance: { destroy: () => void; render: () => void } | null = null
let portalThemeObserver: MutationObserver | null = null
const APEX_CHARTS_SRC = '/assets/vendors/apexcharts/apexcharts.min.js'

const rides = ref<PortalRide[]>([])
const ridesSortKey = ref<RidesSortKey>('date')
const ridesSortDir = ref<SortDir>('desc')
const ridesLoading = ref(false)
const ridesError = ref<string | null>(null)
const ridesLoaded = ref(false)
const ridesSearchQuery = ref('')
const ridesStatusFilter = ref('')
const ridesAmountMin = ref('')
const ridesAmountMax = ref('')
const ridesPage = ref(1)
const ridesPerPage = ref(10)
const RIDES_PER_PAGE_OPTIONS = [10, 25, 50] as const

const invoices = ref<PortalInvoice[]>([])
const invoicesSortKey = ref<InvoicesSortKey>('invoice_number')
const invoicesSortDir = ref<SortDir>('desc')
const invoicesLoading = ref(false)
const invoicesError = ref<string | null>(null)
const invoicesLoaded = ref(false)
const invoicesPage = ref(1)
const invoicesPerPage = ref(10)

const profile = ref<PortalProfile>({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
})
const profileLoading = ref(false)
const profileSaving = ref(false)
const profileError = ref<string | null>(null)
const profileSuccess = ref<string | null>(null)
const profileLoaded = ref(false)

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const passwordSaving = ref(false)
const passwordError = ref<string | null>(null)
const passwordSuccess = ref<string | null>(null)

const rideDetailOpen = ref(false)
const rideDetailLoading = ref(false)
const rideDetailError = ref<string | null>(null)
const rideDetail = ref<PortalRideDetail | null>(null)

const RIDE_BADGE_CLASS: Record<string, string> = {
  success: 'kt-badge-success',
  destructive: 'kt-badge-destructive',
  danger: 'kt-badge-destructive',
  warning: 'kt-badge-warning',
  secondary: 'kt-badge-secondary',
  mono: 'kt-badge-mono',
  primary: 'kt-badge-primary',
  info: 'kt-badge-info',
  offered: 'taxi-status-badge taxi-status-badge--offered',
  accepted: 'taxi-status-badge taxi-status-badge--accepted',
  assigned: 'taxi-status-badge taxi-status-badge--assigned',
  pending_dispatch: 'taxi-status-badge taxi-status-badge--pending-dispatch',
  pending_payment: 'taxi-status-badge taxi-status-badge--pending-payment',
  invoice_sent: 'taxi-status-badge taxi-status-badge--invoice-sent',
  invoice_progress: 'taxi-status-badge taxi-status-badge--invoice-progress',
}

function rideBadgeClass(badge: string): string {
  return RIDE_BADGE_CLASS[badge] ?? 'kt-badge-secondary'
}

function rideRouteSortValue(ride: PortalRide): string {
  if (ride.from && ride.to) {
    return `${ride.from} ${ride.to}`.toLowerCase()
  }

  return (ride.route || '').toLowerCase()
}

function ridesSortAria(key: RidesSortKey): 'asc' | 'desc' | 'none' {
  return ridesSortKey.value === key ? ridesSortDir.value : 'none'
}

function toggleRidesSort(key: RidesSortKey): void {
  if (ridesSortKey.value === key) {
    ridesSortDir.value = ridesSortDir.value === 'asc' ? 'desc' : 'asc'
    return
  }
  ridesSortKey.value = key
  ridesSortDir.value = key === 'amount' || key === 'date' ? 'desc' : 'asc'
}

function parseAmountFilter(value: string): number | null {
  const trimmed = value.trim().replace(',', '.')
  if (!trimmed) return null
  const parsed = Number.parseFloat(trimmed)
  return Number.isFinite(parsed) ? parsed : null
}

function rideMatchesSearch(ride: PortalRide, query: string): boolean {
  const q = query.trim().toLowerCase()
  if (!q) return true

  const haystack = [
    ride.from,
    ride.to,
    ride.route,
    ride.at,
    ride.status_label,
    ride.amount,
    String(ride.id),
  ]
    .join(' ')
    .toLowerCase()

  return haystack.includes(q)
}

function rideMatchesAmountFilter(ride: PortalRide, min: number | null, max: number | null): boolean {
  if (min === null && max === null) return true
  if (ride.amount_raw === null || ride.amount_raw === undefined) return false

  if (min !== null && ride.amount_raw < min) return false
  if (max !== null && ride.amount_raw > max) return false

  return true
}

const rideStatusOptions = computed(() => {
  const seen = new Map<string, string>()
  for (const ride of rides.value) {
    const status = ride.status || ''
    if (!status || seen.has(status)) continue
    seen.set(status, ride.status_label || status)
  }

  return [...seen.entries()]
    .map(([value, label]) => ({ value, label }))
    .sort((a, b) => a.label.localeCompare(b.label, 'nl', { sensitivity: 'base' }))
})

const ridesHasActiveFilters = computed(
  () =>
    ridesSearchQuery.value.trim() !== '' ||
    ridesStatusFilter.value !== '' ||
    ridesAmountMin.value.trim() !== '' ||
    ridesAmountMax.value.trim() !== ''
)

const filteredRides = computed(() => {
  const min = parseAmountFilter(ridesAmountMin.value)
  const max = parseAmountFilter(ridesAmountMax.value)

  return rides.value.filter((ride) => {
    if (!rideMatchesSearch(ride, ridesSearchQuery.value)) return false
    if (ridesStatusFilter.value !== '' && ride.status !== ridesStatusFilter.value) return false
    if (!rideMatchesAmountFilter(ride, min, max)) return false
    return true
  })
})

function resetRidesFilters(): void {
  ridesSearchQuery.value = ''
  ridesStatusFilter.value = ''
  ridesAmountMin.value = ''
  ridesAmountMax.value = ''
  ridesPage.value = 1
}

function goToRidesPage(page: number): void {
  const total = ridesTotalPages.value
  if (total < 1) {
    ridesPage.value = 1
    return
  }
  ridesPage.value = Math.min(Math.max(1, page), total)
}

const sortedRides = computed(() => {
  const dir = ridesSortDir.value === 'asc' ? 1 : -1
  const list = [...filteredRides.value]

  list.sort((a, b) => {
    let cmp = 0

    switch (ridesSortKey.value) {
      case 'route':
        cmp = rideRouteSortValue(a).localeCompare(rideRouteSortValue(b), 'nl', {
          sensitivity: 'base',
        })
        break
      case 'date': {
        const aTime = a.pickup_at_iso ? Date.parse(a.pickup_at_iso) : Number.NaN
        const bTime = b.pickup_at_iso ? Date.parse(b.pickup_at_iso) : Number.NaN
        const aMissing = Number.isNaN(aTime)
        const bMissing = Number.isNaN(bTime)
        if (aMissing && bMissing) cmp = 0
        else if (aMissing) cmp = 1
        else if (bMissing) cmp = -1
        else cmp = aTime - bTime
        break
      }
      case 'status':
        cmp = (a.status_label || '').localeCompare(b.status_label || '', 'nl', {
          sensitivity: 'base',
        })
        break
      case 'amount': {
        const aAmount = a.amount_raw ?? Number.NEGATIVE_INFINITY
        const bAmount = b.amount_raw ?? Number.NEGATIVE_INFINITY
        cmp = aAmount - bAmount
        break
      }
    }

    if (cmp === 0) {
      cmp = b.id - a.id
    }

    return cmp * dir
  })

  return list
})

const ridesTotalFiltered = computed(() => sortedRides.value.length)

const ridesTotalPages = computed(() =>
  ridesTotalFiltered.value === 0 ? 0 : Math.ceil(ridesTotalFiltered.value / ridesPerPage.value)
)

const paginatedRides = computed(() => {
  if (ridesTotalFiltered.value === 0) return []
  const start = (ridesPage.value - 1) * ridesPerPage.value
  return sortedRides.value.slice(start, start + ridesPerPage.value)
})

const ridesPageRangeStart = computed(() => {
  if (ridesTotalFiltered.value === 0) return 0
  return (ridesPage.value - 1) * ridesPerPage.value + 1
})

const ridesPageRangeEnd = computed(() =>
  Math.min(ridesPage.value * ridesPerPage.value, ridesTotalFiltered.value)
)

const ridesVisiblePages = computed(() => {
  const total = ridesTotalPages.value
  const current = ridesPage.value
  if (total <= 1) return total === 1 ? [1] : []

  const pages = new Set<number>([1, total, current])
  if (current > 1) pages.add(current - 1)
  if (current < total) pages.add(current + 1)

  return [...pages].sort((a, b) => a - b)
})

watch([ridesSearchQuery, ridesStatusFilter, ridesAmountMin, ridesAmountMax, ridesPerPage], () => {
  ridesPage.value = 1
})

watch(ridesTotalPages, (total) => {
  if (total < 1) {
    ridesPage.value = 1
    return
  }
  if (ridesPage.value > total) {
    ridesPage.value = total
  }
})

function invoiceRouteSortValue(invoice: PortalInvoice): string {
  if (invoice.from && invoice.to) {
    return `${invoice.from} ${invoice.to}`.toLowerCase()
  }

  return (invoice.route || (invoice.ride_id ? `rit #${invoice.ride_id}` : '')).toLowerCase()
}

function invoicesSortAria(key: InvoicesSortKey): 'asc' | 'desc' | 'none' {
  return invoicesSortKey.value === key ? invoicesSortDir.value : 'none'
}

function toggleInvoicesSort(key: InvoicesSortKey): void {
  if (invoicesSortKey.value === key) {
    invoicesSortDir.value = invoicesSortDir.value === 'asc' ? 'desc' : 'asc'
    return
  }
  invoicesSortKey.value = key
  invoicesSortDir.value = key === 'date' || key === 'amount' || key === 'invoice_number' ? 'desc' : 'asc'
}

const sortedInvoices = computed(() => {
  const dir = invoicesSortDir.value === 'asc' ? 1 : -1
  const list = [...invoices.value]

  list.sort((a, b) => {
    let cmp = 0

    switch (invoicesSortKey.value) {
      case 'invoice_number':
        cmp = (a.invoice_number || '').localeCompare(b.invoice_number || '', 'nl', {
          numeric: true,
          sensitivity: 'base',
        })
        break
      case 'route':
        cmp = invoiceRouteSortValue(a).localeCompare(invoiceRouteSortValue(b), 'nl', {
          sensitivity: 'base',
        })
        break
      case 'date': {
        const aTime = a.date_iso ? Date.parse(a.date_iso) : Number.NaN
        const bTime = b.date_iso ? Date.parse(b.date_iso) : Number.NaN
        const aMissing = Number.isNaN(aTime)
        const bMissing = Number.isNaN(bTime)
        if (aMissing && bMissing) cmp = 0
        else if (aMissing) cmp = 1
        else if (bMissing) cmp = -1
        else cmp = aTime - bTime
        break
      }
      case 'status':
        cmp = (a.status_label || '').localeCompare(b.status_label || '', 'nl', {
          sensitivity: 'base',
        })
        break
      case 'amount': {
        const aAmount = a.amount_raw ?? Number.NEGATIVE_INFINITY
        const bAmount = b.amount_raw ?? Number.NEGATIVE_INFINITY
        cmp = aAmount - bAmount
        break
      }
    }

    if (cmp === 0) {
      cmp = b.id - a.id
    }

    return cmp * dir
  })

  return list
})

const invoicesTotal = computed(() => sortedInvoices.value.length)

const invoicesTotalPages = computed(() =>
  invoicesTotal.value === 0 ? 0 : Math.ceil(invoicesTotal.value / invoicesPerPage.value)
)

const paginatedInvoices = computed(() => {
  if (invoicesTotal.value === 0) return []
  const start = (invoicesPage.value - 1) * invoicesPerPage.value
  return sortedInvoices.value.slice(start, start + invoicesPerPage.value)
})

const invoicesPageRangeStart = computed(() => {
  if (invoicesTotal.value === 0) return 0
  return (invoicesPage.value - 1) * invoicesPerPage.value + 1
})

const invoicesPageRangeEnd = computed(() =>
  Math.min(invoicesPage.value * invoicesPerPage.value, invoicesTotal.value)
)

const invoicesVisiblePages = computed(() => {
  const total = invoicesTotalPages.value
  const current = invoicesPage.value
  if (total <= 1) return total === 1 ? [1] : []

  const pages = new Set<number>([1, total, current])
  if (current > 1) pages.add(current - 1)
  if (current < total) pages.add(current + 1)

  return [...pages].sort((a, b) => a - b)
})

function goToInvoicesPage(page: number): void {
  const total = invoicesTotalPages.value
  if (total < 1) {
    invoicesPage.value = 1
    return
  }
  invoicesPage.value = Math.min(Math.max(1, page), total)
}

watch(invoicesPerPage, () => {
  invoicesPage.value = 1
})

watch(invoicesTotalPages, (total) => {
  if (total < 1) {
    invoicesPage.value = 1
    return
  }
  if (invoicesPage.value > total) {
    invoicesPage.value = total
  }
})

function invoicePdfUrl(invoiceId: number): string {
  return `${apiInvoicePdfBase}/${invoiceId}/pdf`
}

function isPortalDarkMode(): boolean {
  return (
    document.documentElement.classList.contains('dark') ||
    document.body.classList.contains('dark')
  )
}

function destroyCostChart() {
  if (costChartInstance) {
    costChartInstance.destroy()
    costChartInstance = null
  }
  if (costChartEl.value) {
    costChartEl.value.innerHTML = ''
  }
}

function loadApexChartsScript(): Promise<void> {
  if (typeof window.ApexCharts !== 'undefined') {
    return Promise.resolve()
  }

  const existing = document.querySelector<HTMLScriptElement>(
    'script[data-taxi-portal-apexcharts="1"]'
  )
  if (existing) {
    return new Promise((resolve, reject) => {
      existing.addEventListener('load', () => resolve(), { once: true })
      existing.addEventListener('error', () => reject(new Error('Grafiek kon niet laden.')), {
        once: true,
      })
    })
  }

  return new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = APEX_CHARTS_SRC
    script.async = true
    script.dataset.taxiPortalApexcharts = '1'
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('Grafiek kon niet laden.'))
    document.head.appendChild(script)
  })
}

function renderCostChart() {
  destroyCostChart()
  const el = costChartEl.value
  const chart = dashboard.value?.chart
  if (
    !el ||
    !chart ||
    typeof window.ApexCharts === 'undefined' ||
    chart.labels.length === 0
  ) {
    return
  }

  const isDark = isPortalDarkMode()
  const chartTextColor = isDark ? '#9ca3af' : '#6b7280'
  const chartGridColor = isDark ? '#374151' : '#e5e7eb'
  const chartBackground = isDark ? '#111827' : '#ffffff'

  costChartInstance = new window.ApexCharts(el, {
    series: [{ name: 'Kosten', data: chart.amounts }],
    chart: {
      type: 'area',
      height: 280,
      toolbar: { show: false },
      fontFamily: 'inherit',
      background: chartBackground,
      foreColor: chartTextColor,
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: {
      categories: chart.labels,
      labels: {
        rotate: -45,
        rotateAlways: chart.labels.length > 14,
        style: {
          fontSize: '11px',
          colors: chartTextColor,
        },
      },
      axisBorder: {
        show: true,
        color: chartGridColor,
      },
      axisTicks: {
        color: chartGridColor,
      },
    },
    yaxis: {
      min: 0,
      labels: {
        style: {
          colors: chartTextColor,
        },
        formatter: (val: number) => `€${val.toFixed(0)}`,
      },
    },
    tooltip: {
      theme: isDark ? 'dark' : 'light',
      y: {
        formatter: (val: number) => `€${val.toFixed(2)}`,
      },
    },
    colors: ['#2563eb'],
    fill: {
      type: 'gradient',
      gradient: {
        shade: isDark ? 'dark' : 'light',
        shadeIntensity: 0.35,
        opacityFrom: isDark ? 0.5 : 0.35,
        opacityTo: isDark ? 0.08 : 0.04,
      },
    },
    grid: {
      borderColor: chartGridColor,
      strokeDashArray: 4,
    },
    theme: { mode: isDark ? 'dark' : 'light' },
    noData: {
      text: 'Nog geen kosten in de laatste 30 dagen',
      align: 'center',
      verticalAlign: 'middle',
      style: { fontSize: '14px' },
    },
  })
  costChartInstance.render()
}

async function scheduleCostChartRender() {
  if (tab.value !== 'dashboard' || showNewRide.value || !dashboard.value?.chart) {
    return
  }
  await nextTick()
  try {
    await loadApexChartsScript()
    renderCostChart()
  } catch {
    /* grafiek optioneel; stats blijven zichtbaar */
  }
}

async function loadDashboard(force = false) {
  if (dashboardLoaded.value && !force) {
    await scheduleCostChartRender()
    return
  }
  dashboardLoading.value = true
  dashboardError.value = null
  try {
    const res = await portalFetch<{ data: PortalDashboard }>(dashboardApiUrl())
    dashboard.value = res.data
    dashboardLoaded.value = true
  } catch (e) {
    dashboardError.value = e instanceof Error ? e.message : 'Dashboard laden mislukt.'
  } finally {
    dashboardLoading.value = false
  }
  await nextTick()
  await scheduleCostChartRender()
}

async function loadDashboardChart() {
  if (!dashboard.value) {
    await loadDashboard(true)
    return
  }

  chartLoading.value = true
  dashboardError.value = null
  try {
    const res = await portalFetch<{ data: PortalDashboard }>(dashboardApiUrl())
    dashboard.value = {
      ...dashboard.value,
      chart: res.data.chart,
    }
    await scheduleCostChartRender()
  } catch (e) {
    dashboardError.value = e instanceof Error ? e.message : 'Grafiek laden mislukt.'
  } finally {
    chartLoading.value = false
  }
}

async function loadRides(force = false) {
  if (ridesLoaded.value && !force) return
  ridesLoading.value = true
  ridesError.value = null
  try {
    const res = await portalFetch<{ data: PortalRide[] }>(apiRidesUrl)
    rides.value = res.data || []
    ridesLoaded.value = true
  } catch (e) {
    ridesError.value = e instanceof Error ? e.message : 'Ritten laden mislukt.'
  } finally {
    ridesLoading.value = false
  }
}

async function loadInvoices(force = false) {
  if (invoicesLoaded.value && !force) return
  invoicesLoading.value = true
  invoicesError.value = null
  try {
    const res = await portalFetch<{ data: PortalInvoice[] }>(apiInvoicesUrl)
    invoices.value = res.data || []
    invoicesLoaded.value = true
  } catch (e) {
    invoicesError.value = e instanceof Error ? e.message : 'Facturen laden mislukt.'
  } finally {
    invoicesLoading.value = false
  }
}

async function loadProfile(force = false) {
  if (profileLoaded.value && !force) return
  profileLoading.value = true
  profileError.value = null
  try {
    const res = await portalFetch<{ data: PortalProfile }>(apiProfileUrl)
    profile.value = res.data
    profileLoaded.value = true
  } catch (e) {
    profileError.value = e instanceof Error ? e.message : 'Gegevens laden mislukt.'
  } finally {
    profileLoading.value = false
  }
}

async function saveProfile() {
  profileSaving.value = true
  profileError.value = null
  profileSuccess.value = null
  try {
    const res = await portalFetch<{ data: PortalProfile; message?: string }>(apiProfileUpdateUrl, {
      method: 'PUT',
      body: JSON.stringify({
        first_name: profile.value.first_name,
        last_name: profile.value.last_name,
        phone: profile.value.phone,
      }),
    })
    profile.value = res.data
    profileSuccess.value = res.message || 'Gegevens opgeslagen.'
  } catch (e) {
    profileError.value = e instanceof Error ? e.message : 'Opslaan mislukt.'
  } finally {
    profileSaving.value = false
  }
}

async function savePassword() {
  passwordSaving.value = true
  passwordError.value = null
  passwordSuccess.value = null

  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    passwordError.value = 'Wachtwoord bevestiging komt niet overeen.'
    passwordSaving.value = false
    return
  }

  try {
    const res = await portalFetch<{ message?: string }>(apiProfilePasswordUrl, {
      method: 'PUT',
      body: JSON.stringify({
        current_password: passwordForm.value.current_password,
        password: passwordForm.value.password,
        password_confirmation: passwordForm.value.password_confirmation,
      }),
    })
    passwordForm.value = {
      current_password: '',
      password: '',
      password_confirmation: '',
    }
    passwordSuccess.value = res.message || 'Wachtwoord succesvol gewijzigd.'
  } catch (e) {
    passwordError.value = e instanceof Error ? e.message : 'Wachtwoord wijzigen mislukt.'
  } finally {
    passwordSaving.value = false
  }
}

async function openRideDetails(ride: PortalRide) {
  rideDetailOpen.value = true
  rideDetailLoading.value = true
  rideDetailError.value = null
  rideDetail.value = null
  try {
    const res = await portalFetch<{ data: PortalRideDetail }>(`${apiRidesBase}/${ride.id}`)
    rideDetail.value = res.data
  } catch (e) {
    rideDetailError.value = e instanceof Error ? e.message : 'Details laden mislukt.'
  } finally {
    rideDetailLoading.value = false
  }
}

function closeRideDetails() {
  rideDetailOpen.value = false
  rideDetail.value = null
  rideDetailError.value = null
}

function openRidePdf(ride: PortalRide) {
  if (ride.can_view_invoice_pdf && ride.invoice_id) {
    window.open(invoicePdfUrl(ride.invoice_id), '_blank', 'noopener')
  }
}

function refreshTabData() {
  if (tab.value === 'dashboard') {
    void loadDashboard(true)
  } else if (tab.value === 'rides') {
    void loadRides(true)
  } else if (tab.value === 'invoices') {
    void loadInvoices(true)
  } else if (tab.value === 'profile') {
    void loadProfile(true)
  }
}

const portalPrimaryBtnClass =
  'kt-btn kt-btn-primary shrink-0 border border-blue-600 bg-blue-600 text-white hover:border-blue-700 hover:bg-blue-700 dark:border-blue-500 dark:bg-blue-500 dark:hover:border-blue-400 dark:hover:bg-blue-400'

function scheduleBookingRouteIcons() {
  const schedule = (
    window as Window & { __nexataxiScheduleRouteIcons?: () => void }
  ).__nexataxiScheduleRouteIcons
  if (typeof schedule === 'function') {
    schedule()
    return
  }
  const sync = (window as Window & { __nexataxiSyncRouteIcons?: () => void }).__nexataxiSyncRouteIcons
  if (typeof sync === 'function') {
    requestAnimationFrame(() => requestAnimationFrame(sync))
  }
}

function mountBookingModule() {
  if (bookingMounted.value) {
    return
  }
  const source = document.getElementById('taxi-portal-booking-source')
  if (!source || !bookingSlot.value) {
    return
  }
  bookingSlot.value.appendChild(source)
  source.classList.remove('hidden')
  bookingMounted.value = true
}

function openNewRide() {
  showNewRide.value = true
  tab.value = 'rides'
  void nextTick(() => {
    mountBookingModule()
    window.scrollTo({ top: 0, behavior: 'smooth' })
    document.dispatchEvent(new CustomEvent('taxi-portal-booking-visible'))
    scheduleBookingRouteIcons()
  })
}

function closeNewRide() {
  showNewRide.value = false
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function goToTab(next: TabKey) {
  tab.value = next
  showNewRide.value = false
}

function applyPortalStateFromUrl() {
  const state = readPortalStateFromUrl()
  tab.value = state.tab
  showNewRide.value = state.showNewRide
  if (state.showNewRide) {
    void nextTick(() => {
      mountBookingModule()
      scheduleBookingRouteIcons()
    })
  } else {
    refreshTabData()
  }
}

function onPortalBookingReset() {
  syncPortalUrl()
}

function onPortalRefreshRides() {
  dashboardLoaded.value = false
  ridesLoaded.value = false
  invoicesLoaded.value = false
  destroyCostChart()
  refreshTabData()
}

watch([tab, showNewRide], syncPortalUrl)

watch(tab, (next) => {
  if (showNewRide.value) return
  if (next === 'dashboard') {
    void loadDashboard()
  } else {
    destroyCostChart()
    if (next === 'rides') void loadRides()
    else if (next === 'invoices') void loadInvoices()
    else if (next === 'profile') void loadProfile()
  }
})

watch(
  () => dashboard.value?.chart,
  () => {
    void scheduleCostChartRender()
  }
)

watch(chartPeriod, (next, prev) => {
  if (next === prev || tab.value !== 'dashboard' || showNewRide.value) {
    return
  }
  void loadDashboardChart()
})

onMounted(() => {
  ;(window as Window & { closeTaxiPortalBooking?: () => void }).closeTaxiPortalBooking = closeNewRide

  if (!showNewRide.value) {
    if (tab.value === 'dashboard') void loadDashboard()
    else if (tab.value === 'rides') void loadRides()
    else if (tab.value === 'invoices') void loadInvoices()
    else if (tab.value === 'profile') void loadProfile()
  }

  document.addEventListener('taxi-portal-refresh-rides', onPortalRefreshRides)
  document.addEventListener('taxi-portal-booking-reset', onPortalBookingReset)

  portalThemeObserver = new MutationObserver(() => {
    if (tab.value === 'dashboard' && dashboard.value?.chart) {
      void scheduleCostChartRender()
    }
  })
  portalThemeObserver.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class'],
  })

  if (showNewRide.value) {
    void nextTick(() => {
      mountBookingModule()
      scheduleBookingRouteIcons()
    })
  }

  syncPortalUrl()
  window.addEventListener('popstate', applyPortalStateFromUrl)
})

onUnmounted(() => {
  portalThemeObserver?.disconnect()
  portalThemeObserver = null
  destroyCostChart()
  window.removeEventListener('popstate', applyPortalStateFromUrl)
  document.removeEventListener('taxi-portal-refresh-rides', onPortalRefreshRides)
  document.removeEventListener('taxi-portal-booking-reset', onPortalBookingReset)
})
</script>

<template>
  <div
    class="flex grow min-h-[calc(100vh-(4rem+1px)-var(--portal-footer-h))] md:min-h-[calc(100vh-(5rem+1px)-var(--portal-footer-h))] w-full bg-white dark:!bg-[#111827]"
    style="--portal-footer-h: 45px; --portal-sidebar-w: 188px;"
  >
    <!-- Sidebar -->
    <aside
      id="taxi_portal_sidebar"
      class="kt-sidebar taxi-portal-sidebar w-[var(--portal-sidebar-w)] bg-white dark:!bg-[#111827] border-e border-gray-200 dark:!border-gray-600 fixed top-[calc(4rem+1px)] md:top-[calc(5rem+1px)] bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
      data-kt-drawer="true"
      data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0"
    >
      <div
        class="kt-sidebar-content flex grow flex-col min-h-full min-w-0 w-full py-5 px-2 lg:ps-5 lg:pe-3 bg-white dark:!bg-[#111827]"
        id="sidebar_content"
      >
          <nav class="kt-menu flex flex-col grow gap-1 min-w-0 w-full" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_menu">
            <div class="kt-menu-item" :class="tab === 'dashboard' && !showNewRide ? 'active' : ''">
              <button
                type="button"
                class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                @click="goToTab('dashboard')"
              >
                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]"><i class="ki-filled ki-element-11 text-lg"></i></span>
                <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                  Dashboard
                </span>
              </button>
            </div>

            <div class="kt-menu-item" :class="tab === 'rides' && !showNewRide ? 'active' : ''">
              <button
                type="button"
                class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                @click="goToTab('rides')"
              >
                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]"><i class="ki-filled ki-calendar text-lg"></i></span>
                <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                  Ritten
                </span>
              </button>
            </div>

            <div class="kt-menu-item" :class="tab === 'invoices' && !showNewRide ? 'active' : ''">
              <button
                type="button"
                class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                @click="goToTab('invoices')"
              >
                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]"><i class="ki-filled ki-document text-lg"></i></span>
                <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                  Facturen
                </span>
              </button>
            </div>

            <div class="kt-menu-item" :class="tab === 'profile' && !showNewRide ? 'active' : ''">
              <button
                type="button"
                class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                @click="goToTab('profile')"
              >
                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]"><i class="ki-filled ki-profile-circle text-lg"></i></span>
                <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                  Mijn gegevens
                </span>
              </button>
            </div>
          </nav>
      </div>
    </aside>
    <!-- /Sidebar -->

    <div
      class="kt-wrapper flex grow flex-col bg-white dark:!bg-[#111827] lg:ms-[var(--portal-sidebar-w)]"
    >
      <main class="grow">
        <div class="container-custom pt-2.5 pb-10">
          <header class="mb-4 md:mb-6">
            <div class="mt-3 md:mt-4 min-w-0 flex flex-col items-center text-center">
              <h1 class="text-xl md:text-2xl font-semibold text-foreground leading-tight">
                {{ title }}
              </h1>
            </div>
          </header>

          <div v-show="showNewRide" ref="bookingSlot" class="taxi-portal-booking-slot w-full min-w-0" />

          <template v-if="!showNewRide">
            <!-- Dashboard -->
            <div v-if="tab === 'dashboard'" class="flex flex-col gap-5">
              <p v-if="dashboardLoading" class="text-sm text-muted-foreground">Dashboard laden…</p>
              <div v-else-if="dashboardError" class="kt-alert kt-alert-danger" role="alert">
                <div class="kt-alert-description">{{ dashboardError }}</div>
              </div>
              <template v-else-if="dashboard">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                  <div class="kt-card border !border-gray-200 dark:!border-gray-600 bg-white dark:!bg-[#111827]">
                    <div class="kt-card-content py-5">
                      <p class="text-sm text-muted-foreground mb-1">Totaal ritten</p>
                      <p class="text-2xl font-semibold text-foreground tabular-nums">{{ dashboard.stats.total_rides }}</p>
                    </div>
                  </div>
                  <div class="kt-card border !border-gray-200 dark:!border-gray-600 bg-white dark:!bg-[#111827]">
                    <div class="kt-card-content py-5">
                      <p class="text-sm text-muted-foreground mb-1">Afgerond</p>
                      <p class="text-2xl font-semibold text-foreground tabular-nums">{{ dashboard.stats.completed_rides }}</p>
                    </div>
                  </div>
                  <div class="kt-card border !border-gray-200 dark:!border-gray-600 bg-white dark:!bg-[#111827]">
                    <div class="kt-card-content py-5">
                      <p class="text-sm text-muted-foreground mb-1">Gepland / actief</p>
                      <p class="text-2xl font-semibold text-foreground tabular-nums">{{ dashboard.stats.upcoming_rides }}</p>
                    </div>
                  </div>
                  <div class="kt-card border !border-gray-200 dark:!border-gray-600 bg-white dark:!bg-[#111827]">
                    <div class="kt-card-content py-5">
                      <p class="text-sm text-muted-foreground mb-1">Totaal kosten</p>
                      <p class="text-2xl font-semibold text-foreground tabular-nums">{{ dashboard.stats.total_spent }}</p>
                    </div>
                  </div>
                </div>

                <section class="kt-card bg-white dark:!bg-[#111827] border !border-gray-200 dark:!border-gray-600">
                  <div class="kt-card-header flex flex-wrap items-center justify-between gap-3">
                    <h3 class="kt-card-title">{{ chartSectionTitle }}</h3>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                      <label class="sr-only" for="taxi-portal-chart-period">Periode</label>
                      <select
                        id="taxi-portal-chart-period"
                        v-model="chartPeriod"
                        class="taxi-portal-chart-period-select"
                        :disabled="chartLoading"
                      >
                        <option value="month">Per maand</option>
                        <option value="day">Per dag</option>
                        <option value="year">Per jaar</option>
                      </select>
                      <span class="text-sm text-muted-foreground tabular-nums">
                        {{ dashboard.chart.period_label }} · € {{ dashboard.chart.total.toFixed(2).replace('.', ',') }}
                      </span>
                    </div>
                  </div>
                  <div class="kt-card-content min-w-0 relative">
                    <p
                      v-if="chartLoading"
                      class="absolute inset-0 z-10 flex items-center justify-center text-sm text-muted-foreground bg-white/70 dark:bg-[#111827]/70"
                    >
                      Grafiek laden…
                    </p>
                    <div
                      ref="costChartEl"
                      id="taxi-portal-cost-chart"
                      class="taxi-portal-cost-chart min-h-[280px] w-full rounded-md bg-white dark:!bg-[#111827]"
                      :class="chartLoading ? 'opacity-40' : ''"
                    />
                  </div>
                </section>
              </template>
            </div>

            <!-- Rides -->
            <section v-else-if="tab === 'rides'" class="kt-card taxi-portal-list-card bg-white dark:!bg-[#111827] border !border-gray-200 dark:!border-gray-600 min-w-0 max-w-full">
              <div class="kt-card-header flex flex-wrap items-center justify-between gap-3">
                <h3 class="kt-card-title">Ritten</h3>
                <button type="button" :class="portalPrimaryBtnClass" @click="openNewRide">
                  <i class="ki-filled ki-plus"></i> Nieuwe rit
                </button>
              </div>
              <div class="kt-card-content min-w-0">
                <p v-if="ridesLoading" class="text-sm text-muted-foreground">Ritten laden…</p>
                <div v-else-if="ridesError" class="kt-alert kt-alert-danger" role="alert">
                  <div class="kt-alert-description">{{ ridesError }}</div>
                </div>
                <p v-else-if="rides.length === 0" class="text-sm text-muted-foreground">Je hebt nog geen ritten.</p>
                <template v-else>
                  <div class="taxi-portal-datatable-toolbar">
                    <div class="taxi-portal-datatable-filters">
                      <label class="taxi-portal-datatable-search">
                        <span class="sr-only">Zoeken in ritten</span>
                        <span class="taxi-portal-datatable-search-inner">
                          <i class="ki-filled ki-magnifier" aria-hidden="true"></i>
                          <input
                            v-model="ridesSearchQuery"
                            class="taxi-portal-datatable-input"
                            type="search"
                            placeholder="Zoeken…"
                            autocomplete="off"
                          />
                        </span>
                      </label>
                      <select
                        v-model="ridesStatusFilter"
                        class="taxi-portal-datatable-select taxi-portal-datatable-field-status"
                        aria-label="Filter op status"
                      >
                        <option value="">Alle statussen</option>
                        <option v-for="opt in rideStatusOptions" :key="opt.value" :value="opt.value">
                          {{ opt.label }}
                        </option>
                      </select>
                      <input
                        v-model="ridesAmountMin"
                        class="taxi-portal-datatable-input taxi-portal-datatable-field-amount"
                        type="text"
                        inputmode="decimal"
                        placeholder="Min €"
                        aria-label="Minimum bedrag"
                      />
                      <input
                        v-model="ridesAmountMax"
                        class="taxi-portal-datatable-input taxi-portal-datatable-field-amount"
                        type="text"
                        inputmode="decimal"
                        placeholder="Max €"
                        aria-label="Maximum bedrag"
                      />
                      <button
                        v-if="ridesHasActiveFilters"
                        type="button"
                        class="kt-btn kt-btn-icon kt-btn-ghost taxi-portal-datatable-reset"
                        title="Filters resetten"
                        aria-label="Filters resetten"
                        @click="resetRidesFilters"
                      >
                        <i class="ki-filled ki-arrows-circle"></i>
                      </button>
                    </div>
                    <p class="taxi-portal-datatable-count">
                      {{ ridesTotalFiltered }} {{ ridesTotalFiltered === 1 ? 'rit' : 'ritten' }}
                      <span v-if="ridesHasActiveFilters">gevonden</span>
                    </p>
                  </div>

                  <p v-if="ridesTotalFiltered === 0" class="text-sm text-muted-foreground">
                    Geen ritten gevonden voor deze filters.
                  </p>

                  <div v-else class="taxi-portal-table-wrap kt-scrollable-x-auto">
                  <table class="kt-table table-auto kt-table-border taxi-portal-responsive-table">
                    <thead>
                      <tr>
                        <th
                          class="taxi-portal-th-sort min-w-[220px]"
                          :aria-sort="ridesSortAria('route')"
                          tabindex="0"
                          role="columnheader"
                          @click="toggleRidesSort('route')"
                          @keydown.enter.prevent="toggleRidesSort('route')"
                          @keydown.space.prevent="toggleRidesSort('route')"
                        >
                          <span class="taxi-portal-th-sort-inner">
                            <span class="taxi-portal-th-sort-label">Van → Naar</span>
                            <span class="kt-table-col-sort" aria-hidden="true"></span>
                          </span>
                        </th>
                        <th
                          class="taxi-portal-th-sort min-w-[180px]"
                          :aria-sort="ridesSortAria('date')"
                          tabindex="0"
                          role="columnheader"
                          @click="toggleRidesSort('date')"
                          @keydown.enter.prevent="toggleRidesSort('date')"
                          @keydown.space.prevent="toggleRidesSort('date')"
                        >
                          <span class="taxi-portal-th-sort-inner">
                            <span class="taxi-portal-th-sort-label">Datum</span>
                            <span class="kt-table-col-sort" aria-hidden="true"></span>
                          </span>
                        </th>
                        <th
                          class="taxi-portal-th-sort min-w-[140px]"
                          :aria-sort="ridesSortAria('status')"
                          tabindex="0"
                          role="columnheader"
                          @click="toggleRidesSort('status')"
                          @keydown.enter.prevent="toggleRidesSort('status')"
                          @keydown.space.prevent="toggleRidesSort('status')"
                        >
                          <span class="taxi-portal-th-sort-inner">
                            <span class="taxi-portal-th-sort-label">Status</span>
                            <span class="kt-table-col-sort" aria-hidden="true"></span>
                          </span>
                        </th>
                        <th
                          class="taxi-portal-th-sort min-w-[120px]"
                          :aria-sort="ridesSortAria('amount')"
                          tabindex="0"
                          role="columnheader"
                          @click="toggleRidesSort('amount')"
                          @keydown.enter.prevent="toggleRidesSort('amount')"
                          @keydown.space.prevent="toggleRidesSort('amount')"
                        >
                          <span class="taxi-portal-th-sort-inner">
                            <span class="taxi-portal-th-sort-label">Bedrag</span>
                            <span class="kt-table-col-sort" aria-hidden="true"></span>
                          </span>
                        </th>
                        <th class="min-w-[80px] text-center">Acties</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="r in paginatedRides" :key="r.id">
                        <td
                          class="align-top whitespace-normal leading-snug taxi-portal-table-route"
                          data-label="Van → Naar"
                        >
                          <div
                            v-if="r.from && r.to"
                            class="taxi-portal-route-address inline-flex flex-col items-start text-left gap-0.5 font-medium"
                          >
                            <span>{{ r.from }}</span>
                            <span class="leading-none self-center py-1" aria-hidden="true">
                              <i class="ki-filled ki-arrow-down text-sm"></i>
                            </span>
                            <span>{{ r.to }}</span>
                          </div>
                          <span v-else>{{ r.route || '—' }}</span>
                        </td>
                        <td data-label="Datum">{{ r.at }}</td>
                        <td data-label="Status">
                          <span class="kt-badge" :class="rideBadgeClass(r.status_badge)">
                            {{ r.status_label }}
                          </span>
                        </td>
                        <td class="tabular-nums" data-label="Bedrag">{{ r.amount }}</td>
                        <td class="text-center align-middle taxi-portal-table-actions" data-label="">
                          <div class="inline-flex items-center justify-center">
                            <button
                              type="button"
                              class="kt-btn kt-btn-icon kt-btn-ghost taxi-portal-ride-action-btn"
                              title="Details bekijken"
                              aria-label="Details bekijken"
                              @click="openRideDetails(r)"
                            >
                              <i class="ki-filled ki-eye"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                  <div
                    v-if="ridesTotalPages > 0"
                    class="taxi-portal-datatable-footer kt-card-footer admin-datatable-footer text-secondary-foreground text-sm font-medium !px-0 !pb-0 !pt-4 !border-0"
                  >
                    <div class="admin-datatable-footer__perpage flex items-center gap-2">
                      <span>Toon</span>
                      <TaxiPortalPerPageSelect
                        v-model="ridesPerPage"
                        :options="RIDES_PER_PAGE_OPTIONS"
                        aria-label="Ritten per pagina"
                      />
                      <span class="whitespace-nowrap">per pagina</span>
                    </div>
                    <div class="admin-datatable-footer__pagination">
                      <div class="kt-datatable-pagination">
                        <button
                          type="button"
                          class="kt-btn kt-btn-icon kt-btn-ghost kt-datatable-pagination-button"
                          :disabled="ridesPage <= 1"
                          title="Vorige pagina"
                          aria-label="Vorige pagina"
                          @click="goToRidesPage(ridesPage - 1)"
                        >
                          <i class="ki-filled ki-left"></i>
                        </button>
                        <button
                          v-for="page in ridesVisiblePages"
                          :key="`rides-page-${page}`"
                          type="button"
                          class="kt-btn kt-btn-icon kt-datatable-pagination-button"
                          :class="page === ridesPage ? 'kt-btn-primary' : 'kt-btn-ghost'"
                          :aria-label="`Pagina ${page}`"
                          :aria-current="page === ridesPage ? 'page' : undefined"
                          @click="goToRidesPage(page)"
                        >
                          {{ page }}
                        </button>
                        <button
                          type="button"
                          class="kt-btn kt-btn-icon kt-btn-ghost kt-datatable-pagination-button"
                          :disabled="ridesPage >= ridesTotalPages"
                          title="Volgende pagina"
                          aria-label="Volgende pagina"
                          @click="goToRidesPage(ridesPage + 1)"
                        >
                          <i class="ki-filled ki-right"></i>
                        </button>
                      </div>
                    </div>
                    <span class="admin-datatable-footer__info">
                      {{ ridesPageRangeStart }}-{{ ridesPageRangeEnd }} van {{ ridesTotalFiltered }}
                    </span>
                  </div>
                </template>
              </div>
            </section>

            <!-- Invoices -->
            <section v-else-if="tab === 'invoices'" class="kt-card taxi-portal-list-card bg-white dark:!bg-[#111827] border !border-gray-200 dark:!border-gray-600 min-w-0 max-w-full">
              <div class="kt-card-header">
                <h3 class="kt-card-title">Facturen</h3>
              </div>
              <div class="kt-card-content min-w-0">
                <p v-if="invoicesLoading" class="text-sm text-muted-foreground">Facturen laden…</p>
                <div v-else-if="invoicesError" class="kt-alert kt-alert-danger" role="alert">
                  <div class="kt-alert-description">{{ invoicesError }}</div>
                </div>
                <p v-else-if="invoices.length === 0" class="text-sm text-muted-foreground">
                  Er zijn nog geen facturen voor jouw ritten.
                </p>
                <template v-else>
                <div class="taxi-portal-table-wrap kt-scrollable-x-auto">
                  <table class="kt-table table-auto kt-table-border taxi-portal-responsive-table">
                    <thead>
                      <tr>
                        <th
                          class="taxi-portal-th-sort min-w-[160px]"
                          :aria-sort="invoicesSortAria('invoice_number')"
                          tabindex="0"
                          role="columnheader"
                          @click="toggleInvoicesSort('invoice_number')"
                          @keydown.enter.prevent="toggleInvoicesSort('invoice_number')"
                          @keydown.space.prevent="toggleInvoicesSort('invoice_number')"
                        >
                          <span class="taxi-portal-th-sort-inner">
                            <span class="taxi-portal-th-sort-label">Factuurnr.</span>
                            <span class="kt-table-col-sort" aria-hidden="true"></span>
                          </span>
                        </th>
                        <th
                          class="taxi-portal-th-sort min-w-[220px]"
                          :aria-sort="invoicesSortAria('route')"
                          tabindex="0"
                          role="columnheader"
                          @click="toggleInvoicesSort('route')"
                          @keydown.enter.prevent="toggleInvoicesSort('route')"
                          @keydown.space.prevent="toggleInvoicesSort('route')"
                        >
                          <span class="taxi-portal-th-sort-inner">
                            <span class="taxi-portal-th-sort-label">Rit</span>
                            <span class="kt-table-col-sort" aria-hidden="true"></span>
                          </span>
                        </th>
                        <th
                          class="taxi-portal-th-sort min-w-[120px]"
                          :aria-sort="invoicesSortAria('date')"
                          tabindex="0"
                          role="columnheader"
                          @click="toggleInvoicesSort('date')"
                          @keydown.enter.prevent="toggleInvoicesSort('date')"
                          @keydown.space.prevent="toggleInvoicesSort('date')"
                        >
                          <span class="taxi-portal-th-sort-inner">
                            <span class="taxi-portal-th-sort-label">Datum</span>
                            <span class="kt-table-col-sort" aria-hidden="true"></span>
                          </span>
                        </th>
                        <th
                          class="taxi-portal-th-sort min-w-[120px]"
                          :aria-sort="invoicesSortAria('status')"
                          tabindex="0"
                          role="columnheader"
                          @click="toggleInvoicesSort('status')"
                          @keydown.enter.prevent="toggleInvoicesSort('status')"
                          @keydown.space.prevent="toggleInvoicesSort('status')"
                        >
                          <span class="taxi-portal-th-sort-inner">
                            <span class="taxi-portal-th-sort-label">Status</span>
                            <span class="kt-table-col-sort" aria-hidden="true"></span>
                          </span>
                        </th>
                        <th
                          class="taxi-portal-th-sort min-w-[120px]"
                          :aria-sort="invoicesSortAria('amount')"
                          tabindex="0"
                          role="columnheader"
                          @click="toggleInvoicesSort('amount')"
                          @keydown.enter.prevent="toggleInvoicesSort('amount')"
                          @keydown.space.prevent="toggleInvoicesSort('amount')"
                        >
                          <span class="taxi-portal-th-sort-inner">
                            <span class="taxi-portal-th-sort-label">Bedrag</span>
                            <span class="kt-table-col-sort" aria-hidden="true"></span>
                          </span>
                        </th>
                        <th class="min-w-[80px] text-center">Acties</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="inv in paginatedInvoices" :key="inv.id">
                        <td class="font-medium whitespace-nowrap min-w-[160px]" data-label="Factuurnr.">{{ inv.invoice_number }}</td>
                        <td
                          class="align-top whitespace-normal leading-snug taxi-portal-table-route"
                          data-label="Rit"
                        >
                          <div
                            v-if="inv.from && inv.to"
                            class="taxi-portal-route-address inline-flex flex-col items-start text-left gap-0.5 font-medium"
                          >
                            <span>{{ inv.from }}</span>
                            <span class="leading-none self-center py-1" aria-hidden="true">
                              <i class="ki-filled ki-arrow-down text-sm"></i>
                            </span>
                            <span>{{ inv.to }}</span>
                          </div>
                          <template v-else>
                            {{ inv.route || (inv.ride_id ? 'Rit #' + inv.ride_id : '—') }}
                          </template>
                        </td>
                        <td data-label="Datum">{{ inv.date }}</td>
                        <td data-label="Status">
                          <span class="kt-badge" :class="rideBadgeClass(inv.status_badge)">
                            {{ inv.status_label }}
                          </span>
                        </td>
                        <td class="tabular-nums" data-label="Bedrag">{{ inv.amount }}</td>
                        <td class="text-center align-middle taxi-portal-table-actions" data-label="">
                          <div class="inline-flex items-center justify-center">
                            <a
                              :href="invoicePdfUrl(inv.id)"
                              class="kt-btn kt-btn-icon kt-btn-ghost taxi-portal-ride-action-btn"
                              target="_blank"
                              rel="noopener"
                              title="Factuur PDF openen"
                              aria-label="Factuur PDF openen"
                            >
                              <i class="ki-filled ki-file-down"></i>
                            </a>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div
                  v-if="invoicesTotalPages > 0"
                  class="taxi-portal-datatable-footer kt-card-footer admin-datatable-footer text-secondary-foreground text-sm font-medium !px-0 !pb-0 !pt-4 !border-0"
                >
                  <div class="admin-datatable-footer__perpage flex items-center gap-2">
                    <span>Toon</span>
                    <TaxiPortalPerPageSelect
                      v-model="invoicesPerPage"
                      :options="RIDES_PER_PAGE_OPTIONS"
                      aria-label="Facturen per pagina"
                    />
                    <span class="whitespace-nowrap">per pagina</span>
                  </div>
                  <div class="admin-datatable-footer__pagination">
                    <div class="kt-datatable-pagination">
                      <button
                        type="button"
                        class="kt-btn kt-btn-icon kt-btn-ghost kt-datatable-pagination-button"
                        :disabled="invoicesPage <= 1"
                        title="Vorige pagina"
                        aria-label="Vorige pagina"
                        @click="goToInvoicesPage(invoicesPage - 1)"
                      >
                        <i class="ki-filled ki-left"></i>
                      </button>
                      <button
                        v-for="page in invoicesVisiblePages"
                        :key="`invoices-page-${page}`"
                        type="button"
                        class="kt-btn kt-btn-icon kt-btn-ghost kt-datatable-pagination-button"
                        :class="page === invoicesPage ? 'kt-btn-primary' : 'kt-btn-ghost'"
                        :aria-label="`Pagina ${page}`"
                        :aria-current="page === invoicesPage ? 'page' : undefined"
                        @click="goToInvoicesPage(page)"
                      >
                        {{ page }}
                      </button>
                      <button
                        type="button"
                        class="kt-btn kt-btn-icon kt-btn-ghost kt-datatable-pagination-button"
                        :disabled="invoicesPage >= invoicesTotalPages"
                        title="Volgende pagina"
                        aria-label="Volgende pagina"
                        @click="goToInvoicesPage(invoicesPage + 1)"
                      >
                        <i class="ki-filled ki-right"></i>
                      </button>
                    </div>
                  </div>
                  <span class="admin-datatable-footer__info">
                    {{ invoicesPageRangeStart }}-{{ invoicesPageRangeEnd }} van {{ invoicesTotal }}
                  </span>
                </div>
                </template>
              </div>
            </section>

            <!-- Profile -->
            <section v-else-if="tab === 'profile'" class="kt-card bg-white dark:!bg-[#111827] border !border-gray-200 dark:!border-gray-600">
              <div class="kt-card-header">
                <h3 class="kt-card-title">Mijn gegevens</h3>
              </div>
              <div class="kt-card-content min-w-0">
                <p v-if="profileLoading && !profileLoaded" class="text-sm text-muted-foreground mb-4">Gegevens laden…</p>
                <div v-if="profileError" class="kt-alert kt-alert-danger mb-4" role="alert">
                  <div class="kt-alert-description">{{ profileError }}</div>
                </div>
                <div v-if="profileSuccess" class="kt-alert kt-alert-success mb-4" role="alert">
                  <div class="kt-alert-description">{{ profileSuccess }}</div>
                </div>
                <div class="taxi-portal-profile-form grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-x-6 md:gap-y-5">
                  <div class="taxi-portal-profile-field min-w-0">
                    <label class="taxi-portal-profile-label" for="taxi-portal-first_name">Voornaam</label>
                    <input
                      id="taxi-portal-first_name"
                      v-model="profile.first_name"
                      class="taxi-portal-profile-input"
                      type="text"
                      autocomplete="given-name"
                    />
                  </div>
                  <div class="taxi-portal-profile-field min-w-0">
                    <label class="taxi-portal-profile-label" for="taxi-portal-last_name">Achternaam</label>
                    <input
                      id="taxi-portal-last_name"
                      v-model="profile.last_name"
                      class="taxi-portal-profile-input"
                      type="text"
                      autocomplete="family-name"
                    />
                  </div>
                  <div class="taxi-portal-profile-field min-w-0">
                    <label class="taxi-portal-profile-label" for="taxi-portal-email">E-mail</label>
                    <input
                      id="taxi-portal-email"
                      v-model="profile.email"
                      class="taxi-portal-profile-input opacity-80"
                      type="email"
                      autocomplete="email"
                      inputmode="email"
                      readonly
                    />
                  </div>
                  <div class="taxi-portal-profile-field min-w-0">
                    <label class="taxi-portal-profile-label" for="taxi-portal-phone">Telefoon</label>
                    <input
                      id="taxi-portal-phone"
                      v-model="profile.phone"
                      class="taxi-portal-profile-input"
                      type="tel"
                      autocomplete="tel"
                      inputmode="tel"
                    />
                  </div>
                </div>
              </div>
              <div class="kt-card-footer flex justify-center">
                <button
                  class="kt-btn kt-btn-primary px-8 justify-center shrink-0"
                  type="button"
                  :disabled="profileSaving || profileLoading"
                  @click="saveProfile"
                >
                  {{ profileSaving ? 'Opslaan…' : 'Opslaan' }}
                </button>
              </div>
            </section>

            <section
              v-if="tab === 'profile'"
              class="kt-card bg-white dark:!bg-[#111827] border !border-gray-200 dark:!border-gray-600 mt-5"
            >
              <div class="kt-card-header">
                <h3 class="kt-card-title">Wachtwoord wijzigen</h3>
              </div>
              <div class="kt-card-content min-w-0">
                <div v-if="passwordError" class="kt-alert kt-alert-danger mb-4" role="alert">
                  <div class="kt-alert-description">{{ passwordError }}</div>
                </div>
                <div v-if="passwordSuccess" class="kt-alert kt-alert-success mb-4" role="alert">
                  <div class="kt-alert-description">{{ passwordSuccess }}</div>
                </div>
                <div class="taxi-portal-profile-form grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-x-6 md:gap-y-5">
                  <div class="taxi-portal-profile-field min-w-0 md:col-span-2">
                    <label class="taxi-portal-profile-label" for="taxi-portal-current_password">Huidig wachtwoord</label>
                    <input
                      id="taxi-portal-current_password"
                      v-model="passwordForm.current_password"
                      class="taxi-portal-profile-input"
                      type="password"
                      autocomplete="current-password"
                    />
                  </div>
                  <div class="taxi-portal-profile-field min-w-0">
                    <label class="taxi-portal-profile-label" for="taxi-portal-password">Nieuw wachtwoord</label>
                    <input
                      id="taxi-portal-password"
                      v-model="passwordForm.password"
                      class="taxi-portal-profile-input"
                      type="password"
                      autocomplete="new-password"
                    />
                  </div>
                  <div class="taxi-portal-profile-field min-w-0">
                    <label class="taxi-portal-profile-label" for="taxi-portal-password_confirmation">Herhaal nieuw wachtwoord</label>
                    <input
                      id="taxi-portal-password_confirmation"
                      v-model="passwordForm.password_confirmation"
                      class="taxi-portal-profile-input"
                      type="password"
                      autocomplete="new-password"
                    />
                  </div>
                </div>
                <p class="text-xs text-muted-foreground mt-4">
                  Minimaal 8 tekens, met hoofdletters, kleine letters, een cijfer en een speciaal teken.
                </p>
              </div>
              <div class="kt-card-footer flex justify-center">
                <button
                  class="kt-btn kt-btn-primary px-8 justify-center shrink-0"
                  type="button"
                  :disabled="passwordSaving"
                  @click="savePassword"
                >
                  {{ passwordSaving ? 'Opslaan…' : 'Wachtwoord wijzigen' }}
                </button>
              </div>
            </section>
          </template>
        </div>
      </main>
    </div>

    <!-- Ritdetails modal -->
    <div
      v-if="rideDetailOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="taxi-portal-ride-detail-title"
    >
      <div
        class="taxi-portal-ride-detail-backdrop absolute inset-0"
        aria-hidden="true"
        @click="closeRideDetails"
      />
      <div
        class="kt-card relative z-10 w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col bg-white dark:!bg-[#111827] border !border-gray-200 dark:!border-gray-600 shadow-xl"
      >
        <div class="kt-card-header flex items-center justify-between gap-3 shrink-0">
          <h3 id="taxi-portal-ride-detail-title" class="kt-card-title">
            Ritdetails<span v-if="rideDetail"> #{{ rideDetail.id }}</span>
          </h3>
          <button type="button" class="kt-btn kt-btn-icon kt-btn-ghost" aria-label="Sluiten" @click="closeRideDetails">
            <i class="ki-filled ki-cross"></i>
          </button>
        </div>
        <div class="kt-card-content min-w-0 overflow-y-auto grow">
          <p v-if="rideDetailLoading" class="text-sm text-muted-foreground">Details laden…</p>
          <div v-else-if="rideDetailError" class="kt-alert kt-alert-danger" role="alert">
            <div class="kt-alert-description">{{ rideDetailError }}</div>
          </div>
          <template v-else-if="rideDetail">
            <dl class="grid grid-cols-1 gap-3 text-sm mb-5">
              <div>
                <dt class="text-muted-foreground">Route</dt>
                <dd class="text-foreground leading-snug">
                  <div class="taxi-portal-route-address inline-flex flex-col items-start text-left gap-0.5 font-medium">
                    <span>{{ rideDetail.from }}</span>
                    <span class="leading-none self-center py-1" aria-hidden="true">
                      <i class="ki-filled ki-arrow-down text-sm"></i>
                    </span>
                    <span>{{ rideDetail.to }}</span>
                  </div>
                </dd>
              </div>
              <div>
                <dt class="text-muted-foreground">Datum</dt>
                <dd>{{ rideDetail.at }}</dd>
              </div>
              <div>
                <dt class="text-muted-foreground">Status</dt>
                <dd>
                  <span class="kt-badge" :class="rideBadgeClass(rideDetail.status_badge)">
                    {{ rideDetail.status_label }}
                  </span>
                </dd>
              </div>
              <div>
                <dt class="text-muted-foreground">Bedrag</dt>
                <dd class="tabular-nums font-medium">{{ rideDetail.amount }}</dd>
              </div>
              <div v-if="rideDetail.invoice_number">
                <dt class="text-muted-foreground">Factuur</dt>
                <dd>{{ rideDetail.invoice_number }}</dd>
              </div>
            </dl>
            <div v-if="rideDetail.summary_lines?.length" class="border-t border-gray-200 dark:border-gray-600 pt-4">
              <p class="text-sm font-medium text-foreground mb-2">Samenvatting</p>
              <ul class="text-sm text-muted-foreground space-y-1 list-disc ps-5">
                <li v-for="(line, i) in rideDetail.summary_lines" :key="i">{{ line }}</li>
              </ul>
            </div>
            <pre
              v-else-if="rideDetail.summary_text"
              class="text-xs whitespace-pre-wrap text-muted-foreground border-t border-gray-200 dark:border-gray-600 pt-4"
            >{{ rideDetail.summary_text }}</pre>
          </template>
        </div>
        <div v-if="rideDetail?.can_view_invoice_pdf && rideDetail.invoice_id" class="kt-card-footer shrink-0 flex justify-end gap-2">
          <button
            type="button"
            class="kt-btn kt-btn-outline kt-btn-primary"
            @click="openRidePdf(rideDetail)"
          >
            <i class="ki-filled ki-file-down"></i>
            PDF openen
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.taxi-status-badge {
  color: #fff;
  border: none;
}
.taxi-status-badge--offered {
  background-color: #7c3aed;
}
.taxi-status-badge--accepted {
  background-color: #0891b2;
}
.taxi-status-badge--assigned {
  background-color: #2563eb;
}
.taxi-status-badge--pending-dispatch {
  background-color: #d97706;
}
.taxi-status-badge--pending-payment {
  background-color: #ea580c;
}
.taxi-status-badge--invoice-sent {
  background-color: #4f46e5;
}
.taxi-status-badge--invoice-progress {
  background-color: #0d9488;
}
</style>
