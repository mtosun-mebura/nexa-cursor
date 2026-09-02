<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

type Option = { value: string; label: string; hint?: string; icon?: string; tone?: string }
type Screenshot = { path?: string; url: string; name: string; size?: number }
type LocalShot = { id: string; file: File; url: string; name: string }
type Person = { id: number; name: string; email?: string }
type IncidentComment = {
  id: number
  body: string
  user: Person | null
  created_at_formatted?: string | null
}
type Incident = {
  id: number
  reference: string
  kind: string
  kind_label: string
  title: string
  page_url?: string | null
  description?: string
  priority: string
  priority_label: string
  status: string
  status_label: string
  is_handled: boolean
  company: { id: number; name: string } | null
  reporter: Person | null
  screenshot_count: number
  screenshots?: Screenshot[]
  resolution_note?: string | null
  resolved_at_formatted?: string | null
  resolved_by?: Person | null
  comments?: IncidentComment[]
  created_at_human: string
  created_at_formatted: string
  is_archived?: boolean
}
type CompanyOption = { id: number; name: string }
type ListMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}
type Bootstrap = {
  is_super_admin: boolean
  can_create: boolean
  user_name: string
  open_id: number | null
  csrf: string
  routes: { list: string; store: string; show: string; update: string; comments: string; archive: string }
  kinds: Option[]
  statuses: Option[]
  priorities: Option[]
  companies?: CompanyOption[]
}

const props = defineProps<{ bootstrap: Bootstrap }>()
const b = props.bootstrap

const incidents = ref<Incident[]>([])
const stats = ref({ open: 0, in_progress: 0, resolved: 0, total: 0, archived: 0 })
const listMeta = ref<ListMeta>({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 })
const loading = ref(true)
const saving = ref(false)
const archiveSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const search = ref('')
const statusFilter = ref('')
const priorityFilter = ref('')
const showArchived = ref(false)
const page = ref(1)
const perPage = ref(10)
const selectedIds = ref<number[]>([])

const composerOpen = ref(false)
const composerStep = ref(1)
const composerVisible = ref(false)
const kind = ref(b.kinds[0]?.value ?? 'storing')
const title = ref('')
const pageUrl = ref('')
const description = ref('')
const priority = ref('normal')
const companyId = ref('')
const shots = ref<LocalShot[]>([])
const dragging = ref(false)
const capturing = ref(false)
const submitted = ref(false)
const submittedIncident = ref<Incident | null>(null)

const detailOpen = ref(false)
const detailVisible = ref(false)
const detail = ref<Incident | null>(null)
const detailLoading = ref(false)
const handleStatus = ref('open')
const handleNote = ref('')
const noteEditing = ref(true)
const commentDraft = ref('')
const noteSaving = ref(false)
const noteDeleting = ref(false)
const commentSaving = ref(false)
const deletingCommentId = ref<number | null>(null)
const pendingDelete = ref<{ kind: 'note' } | { kind: 'comment'; comment: IncidentComment } | null>(null)
const noteSavedMessage = ref('')
const commentSavedMessage = ref('')
const statusSaving = ref(false)
const noteTextarea = ref<HTMLTextAreaElement | null>(null)
let noteSavedTimer: number | undefined
let commentSavedTimer: number | undefined
const lightboxIndex = ref<number | null>(null)

const lightboxShots = computed(() => {
  if (composerOpen.value) {
    return shots.value
      .filter((shot) => shot.url)
      .map((shot) => ({ url: shot.url, name: shot.name }))
  }
  return (detail.value?.screenshots ?? []).filter((shot) => shot.url)
})
const lightboxShot = computed(() => {
  if (lightboxIndex.value === null) return null
  return lightboxShots.value[lightboxIndex.value] ?? null
})
const lightboxCanNav = computed(() => lightboxShots.value.length > 1)

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || b.csrf

function headers(json = true): HeadersInit {
  const h: Record<string, string> = {
    Accept: 'application/json',
    'X-CSRF-TOKEN': csrf(),
    'X-Requested-With': 'XMLHttpRequest',
  }
  if (json) h['Content-Type'] = 'application/json'
  return h
}

async function loadList() {
  loading.value = true
  errorMessage.value = ''
  const params = new URLSearchParams()
  if (search.value.trim()) params.set('search', search.value.trim())
  if (statusFilter.value) params.set('status', statusFilter.value)
  if (priorityFilter.value) params.set('priority', priorityFilter.value)
  if (showArchived.value) params.set('archived', '1')
  params.set('page', String(page.value))
  params.set('per_page', String(perPage.value))
  const url = `${b.routes.list}?${params}`
  try {
    const res = await fetch(url, { headers: headers(false) })
    if (!res.ok) throw new Error('Kon incidenten niet laden.')
    const data = await res.json()
    incidents.value = data.incidents || []
    stats.value = { archived: 0, ...stats.value, ...(data.stats || {}) }
    if (data.meta) listMeta.value = data.meta
    if (data.meta?.last_page && page.value > data.meta.last_page && data.meta.last_page > 0) {
      page.value = data.meta.last_page
      return
    }
    selectedIds.value = selectedIds.value.filter((id) => incidents.value.some((item) => item.id === id))
  } catch (e: any) {
    errorMessage.value = e?.message || 'Er ging iets mis.'
  } finally {
    loading.value = false
  }
}

const pageIds = computed(() => incidents.value.map((item) => item.id))
const allSelected = computed(() => pageIds.value.length > 0 && pageIds.value.every((id) => selectedIds.value.includes(id)))
const someSelected = computed(() => selectedIds.value.length > 0 && !allSelected.value)
const PAGE_SIZE_OPTIONS = [5, 10, 25, 50, 100]
const PAGE_MORE_LIMIT = 5
type PaginationItem = { type: 'page'; page: number } | { type: 'ellipsis'; jump: number }
const paginationInfo = computed(() => `Toon ${listMeta.value.from} tot ${listMeta.value.to} van ${listMeta.value.total} incidenten`)
const paginationItems = computed<PaginationItem[]>(() => {
  const total = Math.max(1, listMeta.value.last_page)
  const current = page.value
  const items: PaginationItem[] = []
  if (total <= PAGE_MORE_LIMIT + 2) {
    for (let n = 1; n <= total; n += 1) items.push({ type: 'page', page: n })
    return items
  }
  let start = Math.max(1, current - Math.floor(PAGE_MORE_LIMIT / 2))
  let end = start + PAGE_MORE_LIMIT - 1
  if (end > total) {
    end = total
    start = Math.max(1, end - PAGE_MORE_LIMIT + 1)
  }
  if (start > 1) {
    items.push({ type: 'page', page: 1 })
    if (start > 2) items.push({ type: 'ellipsis', jump: start - 1 })
  }
  for (let n = start; n <= end; n += 1) items.push({ type: 'page', page: n })
  if (end < total) {
    if (end < total - 1) items.push({ type: 'ellipsis', jump: end + 1 })
    items.push({ type: 'page', page: total })
  }
  return items
})

function goToPage(next: number) {
  const last = Math.max(1, listMeta.value.last_page)
  const clamped = Math.min(last, Math.max(1, next))
  if (clamped === page.value) return
  page.value = clamped
}

function toggleSelect(id: number, checked: boolean) {
  if (checked) {
    if (!selectedIds.value.includes(id)) selectedIds.value = [...selectedIds.value, id]
    return
  }
  selectedIds.value = selectedIds.value.filter((item) => item !== id)
}

function toggleSelectAll(checked: boolean) {
  selectedIds.value = checked ? [...pageIds.value] : []
}

function onToggleSelect(id: number) {
  toggleSelect(id, !selectedIds.value.includes(id))
}

function onToggleSelectAll() {
  toggleSelectAll(!allSelected.value)
}

async function archiveSelected(archived: boolean) {
  if (!selectedIds.value.length || archiveSaving.value) return
  archiveSaving.value = true
  errorMessage.value = ''
  try {
    const res = await fetch(b.routes.archive, {
      method: 'POST',
      headers: headers(true),
      body: JSON.stringify({ ids: selectedIds.value, archived }),
    })
    const data = await res.json().catch(() => ({}))
    if (!res.ok) throw new Error(data?.message || 'Archiveren is niet gelukt.')
    selectedIds.value = []
    successMessage.value = archived
      ? `${data.count || ''} incident${data.count === 1 ? '' : 'en'} naar archief.`
      : `${data.count || ''} incident${data.count === 1 ? '' : 'en'} teruggezet.`
    window.setTimeout(() => { successMessage.value = '' }, 3500)
    await loadList()
  } catch (e: any) {
    errorMessage.value = e?.message || 'Archiveren is niet gelukt.'
  } finally {
    archiveSaving.value = false
  }
}

let searchTimer: number | undefined
watch([search, statusFilter, priorityFilter, showArchived], () => {
  selectedIds.value = []
  if (page.value !== 1) {
    page.value = 1
    return
  }
  window.clearTimeout(searchTimer)
  searchTimer = window.setTimeout(() => loadList(), search.value ? 280 : 0)
})
watch(perPage, () => {
  selectedIds.value = []
  if (page.value !== 1) {
    page.value = 1
  }
})
watch([page, perPage], () => {
  window.clearTimeout(searchTimer)
  searchTimer = window.setTimeout(() => loadList(), 0)
})

function toneClass(tone?: string) {
  if (tone === 'danger') return 'incident-chip incident-chip-danger'
  if (tone === 'warning') return 'incident-chip incident-chip-warning'
  if (tone === 'success') return 'incident-chip incident-chip-success'
  if (tone === 'secondary') return 'incident-chip incident-chip-muted'
  return 'incident-chip incident-chip-info'
}

function statusTone(status: string) {
  return b.statuses.find((s) => s.value === status)?.tone || 'info'
}

function priorityTone(value: string) {
  return b.priorities.find((s) => s.value === value)?.tone || 'info'
}

function openComposer() {
  submitted.value = false
  submittedIncident.value = null
  composerStep.value = 1
  kind.value = b.kinds[0]?.value ?? 'storing'
  title.value = ''
  pageUrl.value = ''
  description.value = ''
  priority.value = 'normal'
  companyId.value = ''
  clearShots()
  errorMessage.value = ''
  composerOpen.value = true
  nextTick(() => {
    composerVisible.value = true
  })
}

function closeComposer() {
  closeLightbox()
  composerVisible.value = false
  window.setTimeout(() => {
    composerOpen.value = false
  }, 220)
}

const composerStepMeta = [
  { n: 1, label: 'Soort' },
  { n: 2, label: 'Uitleg' },
  { n: 3, label: 'Versturen' },
]

function goToComposerStep(step: number) {
  if (step < 1 || step > 3 || step > composerStep.value) return
  composerStep.value = step
}

function clearShots() {
  shots.value.forEach((s) => URL.revokeObjectURL(s.url))
  shots.value = []
}

function addFiles(files: FileList | File[]) {
  const incoming = Array.from(files).filter((f) => f.type.startsWith('image/'))
  for (const file of incoming) {
    if (shots.value.length >= 5) break
    if (file.size > 5 * 1024 * 1024) {
      errorMessage.value = 'Een afbeelding is groter dan 5MB. Kies een kleinere screenshot.'
      continue
    }
    shots.value.push({
      id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
      file,
      url: URL.createObjectURL(file),
      name: file.name || 'screenshot.png',
    })
  }
}

function removeShot(id: string) {
  const index = shots.value.findIndex((s) => s.id === id)
  const found = index >= 0 ? shots.value[index] : null
  if (found) URL.revokeObjectURL(found.url)
  shots.value = shots.value.filter((s) => s.id !== id)
  if (lightboxIndex.value === null) return
  if (!shots.value.length) {
    closeLightbox()
    return
  }
  if (lightboxIndex.value >= shots.value.length) {
    lightboxIndex.value = shots.value.length - 1
  } else if (index >= 0 && lightboxIndex.value > index) {
    lightboxIndex.value -= 1
  }
}

function onDrop(event: DragEvent) {
  dragging.value = false
  event.preventDefault()
  if (event.dataTransfer?.files) addFiles(event.dataTransfer.files)
}

function onPaste(event: ClipboardEvent) {
  if (!composerOpen.value) return
  const items = event.clipboardData?.items
  if (!items) return
  const files: File[] = []
  for (const item of items) {
    if (item.type.startsWith('image/')) {
      const file = item.getAsFile()
      if (file) files.push(new File([file], `plak-${Date.now()}.png`, { type: file.type }))
    }
  }
  if (files.length) {
    event.preventDefault()
    addFiles(files)
  }
}

async function captureScreen() {
  if (!navigator.mediaDevices?.getDisplayMedia) {
    errorMessage.value = 'Schermafbeelding maken wordt niet ondersteund in deze browser. Plak of sleep een screenshot.'
    return
  }
  capturing.value = true
  errorMessage.value = ''
  let stream: MediaStream | null = null
  try {
    stream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false })
    const track = stream.getVideoTracks()[0]
    const video = document.createElement('video')
    video.srcObject = stream
    video.muted = true
    await video.play()
    await new Promise((r) => requestAnimationFrame(() => r(null)))
    const canvas = document.createElement('canvas')
    canvas.width = video.videoWidth || 1280
    canvas.height = video.videoHeight || 720
    canvas.getContext('2d')?.drawImage(video, 0, 0)
    track?.stop()
    stream.getTracks().forEach((t) => t.stop())
    const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/png'))
    if (!blob) throw new Error('Kon geen schermafbeelding maken.')
    addFiles([new File([blob], `schermafbeelding-${Date.now()}.png`, { type: 'image/png' })])
  } catch (e: any) {
    if (e?.name !== 'NotAllowedError') {
      errorMessage.value = 'Schermafbeelding is geannuleerd of mislukt. Je kunt ook plakken of slepen.'
    }
  } finally {
    stream?.getTracks().forEach((t) => t.stop())
    capturing.value = false
  }
}

const canSubmit = computed(() => title.value.trim().length >= 4 && description.value.trim().length >= 10 && !saving.value)

async function submitIncident() {
  if (!canSubmit.value) {
    if (title.value.trim().length < 4) errorMessage.value = 'Beschrijf kort wat er aan de hand is (minimaal 4 tekens).'
    else errorMessage.value = 'Vertel iets meer, zodat we je snel kunnen helpen (minimaal 10 tekens).'
    return
  }
  saving.value = true
  errorMessage.value = ''
  const form = new FormData()
  form.append('kind', kind.value)
  form.append('title', title.value.trim())
  if (pageUrl.value.trim()) form.append('page_url', pageUrl.value.trim())
  form.append('description', description.value.trim())
  form.append('priority', priority.value)
  if (b.is_super_admin && companyId.value) form.append('company_id', companyId.value)
  shots.value.forEach((s) => form.append('screenshots[]', s.file, s.name))
  try {
    const res = await fetch(b.routes.store, {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
      body: form,
    })
    const data = await res.json().catch(() => ({}))
    if (!res.ok) {
      const first = data?.errors ? Object.values(data.errors).flat()[0] : data?.message
      throw new Error((first as string) || 'Versturen is niet gelukt.')
    }
    submittedIncident.value = data.incident
    submitted.value = true
    await loadList()
  } catch (e: any) {
    errorMessage.value = e?.message || 'Versturen is niet gelukt.'
  } finally {
    saving.value = false
  }
}

async function openDetail(id: number) {
  detailLoading.value = true
  detailOpen.value = true
  nextTick(() => {
    detailVisible.value = true
  })
  try {
    const res = await fetch(`${b.routes.show}/${id}`, { headers: headers(false) })
    if (!res.ok) throw new Error('Incident niet gevonden.')
    const data = await res.json()
    detail.value = data.incident
    handleStatus.value = data.incident?.status || 'open'
    handleNote.value = data.incident?.resolution_note || ''
    noteEditing.value = !data.incident?.resolution_note
    commentDraft.value = ''
    noteSavedMessage.value = ''
    commentSavedMessage.value = ''
    nextTick(() => growNoteTextarea())
  } catch (e: any) {
    errorMessage.value = e?.message || 'Kon incident niet openen.'
    closeDetail()
  } finally {
    detailLoading.value = false
  }
}

function closeDetail() {
  closeLightbox()
  pendingDelete.value = null
  detailVisible.value = false
  window.setTimeout(() => {
    detailOpen.value = false
    detail.value = null
  }, 220)
}

function openLightbox(index: number) {
  if (!lightboxShots.value[index]) return
  lightboxIndex.value = index
}

function closeLightbox() {
  lightboxIndex.value = null
}

function lightboxPrev() {
  const total = lightboxShots.value.length
  if (total < 2 || lightboxIndex.value === null) return
  lightboxIndex.value = (lightboxIndex.value + total - 1) % total
}

function lightboxNext() {
  const total = lightboxShots.value.length
  if (total < 2 || lightboxIndex.value === null) return
  lightboxIndex.value = (lightboxIndex.value + 1) % total
}

function growNoteTextarea() {
  const el = noteTextarea.value
  if (!el) return
  el.style.height = 'auto'
  el.style.height = `${el.scrollHeight}px`
}

watch(noteEditing, (editing) => {
  if (!editing) return
  nextTick(() => growNoteTextarea())
})

function applyIncidentPatch(id: number, patch: Record<string, unknown>) {
  if (detail.value?.id === id) {
    detail.value = { ...detail.value, ...patch } as Incident
  }
  const row = incidents.value.find((item) => item.id === id)
  if (row) Object.assign(row, patch)
}

function flashSaved(target: 'note' | 'comment') {
  const messageRef = target === 'note' ? noteSavedMessage : commentSavedMessage
  const timer = target === 'note' ? noteSavedTimer : commentSavedTimer
  messageRef.value = 'Opgeslagen'
  window.clearTimeout(timer)
  const next = window.setTimeout(() => {
    messageRef.value = ''
  }, 3500)
  if (target === 'note') noteSavedTimer = next
  else commentSavedTimer = next
}

function cancelNote() {
  handleNote.value = detail.value?.resolution_note || ''
  noteEditing.value = !handleNote.value.trim()
}

async function saveNote() {
  if (!detail.value || !noteEditing.value || noteSaving.value) return
  noteSaving.value = true
  errorMessage.value = ''
  try {
    await patchIncident({ resolution_note: handleNote.value })
    if (handleNote.value.trim()) noteEditing.value = false
    flashSaved('note')
  } catch (e: any) {
    errorMessage.value = e?.message || 'Toelichting opslaan is niet gelukt.'
  } finally {
    noteSaving.value = false
  }
}

async function deleteNote() {
  if (!detail.value || noteDeleting.value) return
  noteDeleting.value = true
  errorMessage.value = ''
  try {
    await patchIncident({ resolution_note: '' })
    handleNote.value = ''
    noteEditing.value = true
  } catch (e: any) {
    errorMessage.value = e?.message || 'Toelichting verwijderen is niet gelukt.'
  } finally {
    noteDeleting.value = false
  }
}

async function saveComment() {
  if (!detail.value) return
  const commentBody = commentDraft.value.trim()
  if (!commentBody || commentSaving.value) return
  commentSaving.value = true
  errorMessage.value = ''
  try {
    const res = await fetch(`${b.routes.comments}/${detail.value.id}/comments`, {
      method: 'POST',
      headers: headers(true),
      body: JSON.stringify({ body: commentBody }),
    })
    const data = await res.json().catch(() => ({}))
    if (!res.ok) {
      const first = data?.errors ? Object.values(data.errors).flat()[0] : data?.message
      throw new Error((first as string) || 'Commentaar opslaan is niet gelukt.')
    }
    if (data.comment && detail.value) {
      detail.value.comments = [...(detail.value.comments || []), data.comment]
    }
    commentDraft.value = ''
    flashSaved('comment')
  } catch (e: any) {
    errorMessage.value = e?.message || 'Commentaar opslaan is niet gelukt.'
  } finally {
    commentSaving.value = false
  }
}

async function deleteComment(comment: IncidentComment) {
  if (!detail.value || deletingCommentId.value) return
  deletingCommentId.value = comment.id
  errorMessage.value = ''
  try {
    const res = await fetch(`${b.routes.comments}/${detail.value.id}/comments/${comment.id}`, {
      method: 'DELETE',
      headers: headers(false),
    })
    const data = await res.json().catch(() => ({}))
    if (!res.ok) {
      const first = data?.errors ? Object.values(data.errors).flat()[0] : data?.message
      throw new Error((first as string) || 'Commentaar verwijderen is niet gelukt.')
    }
    if (detail.value) {
      detail.value.comments = (detail.value.comments || []).filter((item) => item.id !== comment.id)
    }
  } catch (e: any) {
    errorMessage.value = e?.message || 'Commentaar verwijderen is niet gelukt.'
  } finally {
    deletingCommentId.value = null
  }
}

async function patchIncident(payload: Record<string, unknown>) {
  if (!detail.value) return null
  const res = await fetch(`${b.routes.update}/${detail.value.id}`, {
    method: 'PATCH',
    headers: headers(true),
    body: JSON.stringify(payload),
  })
  const data = await res.json().catch(() => ({}))
  if (!res.ok) {
    const first = data?.errors ? Object.values(data.errors).flat()[0] : data?.message
    throw new Error((first as string) || 'Opslaan is niet gelukt.')
  }
  if (data.patch) applyIncidentPatch(detail.value.id, data.patch)
  return data
}

async function saveStatus() {
  if (!detail.value || statusSaving.value) return
  if (handleStatus.value === detail.value.status) return
  statusSaving.value = true
  errorMessage.value = ''
  try {
    await patchIncident({ status: handleStatus.value })
  } catch (e: any) {
    handleStatus.value = detail.value.status
    errorMessage.value = e?.message || 'Status opslaan is niet gelukt.'
  } finally {
    statusSaving.value = false
  }
}

function requestDeleteNote() {
  pendingDelete.value = { kind: 'note' }
}

function requestDeleteComment(comment: IncidentComment) {
  pendingDelete.value = { kind: 'comment', comment }
}

function cancelPendingDelete() {
  pendingDelete.value = null
}

const pendingDeleteTitle = computed(() => (
  pendingDelete.value?.kind === 'comment' ? 'Commentaar verwijderen?' : 'Toelichting verwijderen?'
))

const pendingDeleteText = computed(() => (
  pendingDelete.value?.kind === 'comment'
    ? 'Dit commentaar wordt definitief verwijderd.'
    : 'De toelichting voor de klant wordt definitief verwijderd.'
))

async function confirmPendingDelete() {
  const pending = pendingDelete.value
  if (!pending) return
  pendingDelete.value = null
  if (pending.kind === 'note') await deleteNote()
  else await deleteComment(pending.comment)
}

function onKeydown(event: KeyboardEvent) {
  if (pendingDelete.value) {
    if (event.key === 'Escape') {
      event.preventDefault()
      cancelPendingDelete()
    }
    return
  }
  if (lightboxIndex.value !== null) {
    if (event.key === 'Escape') closeLightbox()
    else if (event.key === 'ArrowLeft') {
      event.preventDefault()
      lightboxPrev()
    } else if (event.key === 'ArrowRight') {
      event.preventDefault()
      lightboxNext()
    }
    return
  }
  if (event.key === 'Escape') {
    if (composerOpen.value) closeComposer()
    else if (detailOpen.value) closeDetail()
  }
}

onMounted(async () => {
  window.addEventListener('paste', onPaste)
  window.addEventListener('keydown', onKeydown)
  await loadList()
  const openId = b.open_id
  const url = new URL(window.location.href)
  if (url.searchParams.has('open')) {
    url.searchParams.delete('open')
    const query = url.searchParams.toString()
    window.history.replaceState({}, '', url.pathname + (query ? `?${query}` : '') + url.hash)
  }
  if (openId) openDetail(openId)
})

onUnmounted(() => {
  window.removeEventListener('paste', onPaste)
  window.removeEventListener('keydown', onKeydown)
  clearShots()
})
</script>

<template>
  <div class="incident-shell">
    <div class="flex flex-col gap-5 pb-7.5">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-xl font-medium leading-none text-mono mb-0">
            {{ b.is_super_admin ? 'Incidenten' : 'Hulp nodig?' }}
          </h1>
          <p class="text-sm text-muted-foreground mt-2 mb-0">
            {{ b.is_super_admin
              ? 'Bekijk meldingen, dien zelf een ticket in en werk de status bij.'
              : 'Meld een storing, vraag of wens. Voeg een screenshot toe, dan kunnen we je sneller helpen.' }}
          </p>
        </div>
        <button
          v-if="b.can_create"
          type="button"
          class="kt-btn kt-btn-primary shrink-0 incident-cta"
          @click="openComposer"
        >
          <i class="ki-filled ki-plus me-2"></i>
          Nieuw incident
        </button>
      </div>

      <div class="kt-card">
        <div class="kt-card-content p-5">
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="incident-stat">
              <span class="incident-stat-value">{{ stats.open }}</span>
              <span class="incident-stat-label">Nieuw</span>
            </div>
            <div class="incident-stat">
              <span class="incident-stat-value">{{ stats.in_progress }}</span>
              <span class="incident-stat-label">In behandeling</span>
            </div>
            <div class="incident-stat">
              <span class="incident-stat-value">{{ stats.resolved }}</span>
              <span class="incident-stat-label">Afgehandeld</span>
            </div>
            <div class="incident-stat">
              <span class="incident-stat-value">{{ stats.total }}</span>
              <span class="incident-stat-label">Totaal</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="successMessage" class="kt-alert kt-alert-success mb-5" role="alert">
      <i class="ki-filled ki-check-circle me-2"></i>
      {{ successMessage }}
    </div>
    <div v-if="errorMessage && !composerOpen && !detailOpen" class="kt-alert kt-alert-danger mb-5" role="alert">
      <i class="ki-filled ki-information me-2"></i>
      {{ errorMessage }}
    </div>

    <div class="kt-card kt-card-grid w-full min-w-0">
      <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5 min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="kt-card-title mb-0">{{ showArchived ? 'Archief' : (b.is_super_admin ? 'Alle meldingen' : 'Jouw meldingen') }}</h3>
          <div class="incident-view-toggle" role="tablist" aria-label="Lijstweergave">
            <button
              type="button"
              role="tab"
              class="incident-view-btn"
              :class="{ 'is-active': !showArchived }"
              :aria-selected="!showArchived"
              @click="showArchived = false"
            >
              Meldingen
            </button>
            <button
              type="button"
              role="tab"
              class="incident-view-btn"
              :class="{ 'is-active': showArchived }"
              :aria-selected="showArchived"
              @click="showArchived = true"
            >
              Archief{{ stats.archived ? ` (${stats.archived})` : '' }}
            </button>
          </div>
        </div>
        <div class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2.5 w-full sm:w-auto min-w-0">
          <label class="kt-input w-full sm:w-64 min-w-0">
            <i class="ki-filled ki-magnifier"></i>
            <input v-model="search" type="text" placeholder="Zoek op titel of nummer" class="grow">
          </label>
          <select v-model="statusFilter" class="kt-select w-full sm:w-44">
            <option value="">Alle statussen</option>
            <option v-for="item in b.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
          </select>
          <select v-model="priorityFilter" class="kt-select w-full sm:w-44">
            <option value="">Alle prioriteiten</option>
            <option v-for="item in b.priorities" :key="item.value" :value="item.value">{{ item.label }}</option>
          </select>
        </div>
      </div>
      <div class="kt-card-content p-5 min-w-0">
        <div v-if="!loading && incidents.length" class="incident-toolbar">
          <button type="button" class="incident-check-all" @click="onToggleSelectAll">
            <span
              class="kt-checkbox"
              role="checkbox"
              :aria-checked="allSelected ? 'true' : (someSelected ? 'mixed' : 'false')"
            ></span>
            Alles
          </button>
          <button
            v-if="!showArchived"
            type="button"
            class="kt-btn incident-btn-muted"
            :disabled="!selectedIds.length || archiveSaving"
            @click="archiveSelected(true)"
          >
            {{ archiveSaving ? 'Archiveren…' : 'Naar archief' }}
          </button>
          <button
            v-else
            type="button"
            class="kt-btn incident-btn-muted"
            :disabled="!selectedIds.length || archiveSaving"
            @click="archiveSelected(false)"
          >
            {{ archiveSaving ? 'Terugzetten…' : 'Terugzetten' }}
          </button>
          <span v-if="selectedIds.length" class="incident-toolbar-count">{{ selectedIds.length }} geselecteerd</span>
        </div>
        <div v-if="loading" class="incident-empty">Incidenten laden…</div>
        <div v-else-if="errorMessage" class="incident-empty">
          <i class="ki-filled ki-information text-3xl text-destructive mb-3"></i>
          <p class="text-sm text-muted-foreground mb-0">{{ errorMessage }}</p>
        </div>
        <div v-else-if="incidents.length === 0" class="incident-empty">
          <i class="ki-filled ki-flag text-3xl text-muted-foreground mb-3"></i>
          <p class="text-sm text-muted-foreground mb-0">
            {{ showArchived
              ? 'Het archief is leeg.'
              : (b.can_create ? 'Nog geen incidenten. Klik op Nieuw incident om iets te melden.' : 'Er zijn nog geen incidenten.') }}
          </p>
        </div>
        <TransitionGroup v-else name="incident-list" tag="div" class="incident-list">
          <div
            v-for="item in incidents"
            :key="item.id"
            class="incident-row"
            :class="{ 'is-selected': selectedIds.includes(item.id) }"
          >
            <button
              type="button"
              class="incident-row-check"
              :aria-label="'Selecteer ' + item.reference"
              @click.stop="onToggleSelect(item.id)"
            >
              <span
                class="kt-checkbox"
                role="checkbox"
                :aria-checked="selectedIds.includes(item.id) ? 'true' : 'false'"
              ></span>
            </button>
            <button type="button" class="incident-row-open" @click="openDetail(item.id)">
              <div class="incident-row-main">
                <div class="incident-row-top">
                  <span class="incident-ref">{{ item.reference }}</span>
                  <span :class="toneClass(statusTone(item.status))">{{ item.status_label }}</span>
                  <span :class="toneClass(priorityTone(item.priority))">{{ item.priority_label }}</span>
                </div>
                <strong class="incident-row-title">{{ item.title }}</strong>
                <p class="incident-row-meta">
                  {{ item.kind_label }}
                  <template v-if="b.is_super_admin && item.company"> · {{ item.company.name }}</template>
                  <template v-if="item.reporter"> · {{ item.reporter.name }}</template>
                  · {{ item.created_at_human }}
                  <template v-if="item.screenshot_count"> · {{ item.screenshot_count }} screenshot{{ item.screenshot_count === 1 ? '' : 's' }}</template>
                </p>
              </div>
              <i class="ki-filled ki-right text-muted-foreground"></i>
            </button>
          </div>
        </TransitionGroup>
      </div>
      <div
        v-if="!loading && listMeta.total > 0"
        class="kt-card-footer admin-datatable-footer text-secondary-foreground text-sm font-medium"
      >
        <div class="admin-datatable-footer__perpage flex items-center gap-2">
          Toon
          <select v-model.number="perPage" class="kt-select incident-perpage-select" aria-label="Toon aantal per pagina">
            <option v-for="size in PAGE_SIZE_OPTIONS" :key="size" :value="size">{{ size }}</option>
          </select>
          per pagina
        </div>
        <div class="admin-datatable-footer__pagination">
          <div class="kt-datatable-pagination" role="navigation" aria-label="Paginering">
            <button
              type="button"
              class="kt-datatable-pagination-button kt-datatable-pagination-prev"
              :class="{ disabled: page <= 1 }"
              :disabled="page <= 1"
              @click="goToPage(page - 1)"
            >
              Vorige
            </button>
            <button
              v-for="(item, index) in paginationItems"
              :key="item.type === 'page' ? 'p-' + item.page : 'e-' + index"
              type="button"
              class="kt-datatable-pagination-button"
              :class="{ active: item.type === 'page' && item.page === page, disabled: item.type === 'page' && item.page === page }"
              :disabled="item.type === 'page' && item.page === page"
              @click="goToPage(item.type === 'page' ? item.page : item.jump)"
            >
              {{ item.type === 'page' ? item.page : '…' }}
            </button>
            <button
              type="button"
              class="kt-datatable-pagination-button kt-datatable-pagination-next"
              :class="{ disabled: page >= listMeta.last_page }"
              :disabled="page >= listMeta.last_page"
              @click="goToPage(page + 1)"
            >
              Volgende
            </button>
          </div>
        </div>
        <span class="admin-datatable-footer__info">{{ paginationInfo }}</span>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="composerOpen" class="incident-overlay" :class="{ 'is-open': composerVisible }" @click.self="closeComposer">
        <div class="incident-panel" :class="{ 'is-open': composerVisible }" role="dialog" aria-modal="true">
          <button type="button" class="incident-panel-close" aria-label="Sluiten" @click="closeComposer">
            <i class="ki-filled ki-cross"></i>
          </button>
          <div v-if="!submitted" class="incident-panel-head">
            <div>
              <h2 class="text-lg font-medium mb-0">Incident melden</h2>
              <p class="text-sm text-muted-foreground mt-1 mb-0">In een paar stappen klaar. Screenshot plakken mag gewoon met Ctrl+V.</p>
            </div>
          </div>

          <ol v-if="!submitted" class="incident-stepper" aria-label="Stappen">
            <li
              v-for="(step, index) in composerStepMeta"
              :key="step.n"
              class="incident-stepper-item"
              :class="{
                'is-current': composerStep === step.n,
                'is-done': composerStep > step.n,
              }"
            >
              <div class="incident-stepper-track">
                <button
                  v-if="composerStep > step.n"
                  type="button"
                  class="incident-stepper-marker incident-stepper-marker--done"
                  :title="'Ga naar ' + step.label"
                  @click="goToComposerStep(step.n)"
                >
                  <i class="ki-filled ki-check text-sm"></i>
                  <span class="sr-only">{{ step.label }} (voltooid)</span>
                </button>
                <span
                  v-else
                  class="incident-stepper-marker"
                  :class="{ 'incident-stepper-marker--current': composerStep === step.n }"
                  :aria-current="composerStep === step.n ? 'step' : undefined"
                >
                  {{ step.n }}
                </span>
                <span
                  v-if="index < 2"
                  class="incident-stepper-line"
                  :class="{ 'is-done': composerStep > step.n }"
                  aria-hidden="true"
                ></span>
              </div>
              <button
                v-if="composerStep > step.n"
                type="button"
                class="incident-stepper-label incident-stepper-label--clickable"
                @click="goToComposerStep(step.n)"
              >
                {{ step.label }}
              </button>
              <span
                v-else
                class="incident-stepper-label"
                :class="{ 'is-current': composerStep === step.n }"
              >
                {{ step.label }}
              </span>
            </li>
          </ol>

          <div v-if="errorMessage && composerOpen && !submitted" class="incident-alert-wrap">
            <div class="kt-alert kt-alert-danger" role="alert">{{ errorMessage }}</div>
          </div>

          <Transition name="incident-fade" mode="out-in">
            <div v-if="submitted && submittedIncident" key="done" class="incident-success">
              <div class="incident-success-mark">
                <i class="ki-filled ki-check"></i>
              </div>
              <h3 class="text-lg font-medium mb-1">Verstuurd</h3>
              <p class="text-sm text-muted-foreground mb-4">
                {{ submittedIncident.reference }} staat bij NEXA Support. Je krijgt een melding zodra het is afgehandeld.
              </p>
              <div class="flex flex-wrap gap-2">
                <button type="button" class="kt-btn kt-btn-primary" @click="closeComposer(); openDetail(submittedIncident.id)">
                  Bekijk melding
                </button>
                <button type="button" class="kt-btn kt-btn-outline" @click="closeComposer">Sluiten</button>
              </div>
            </div>

            <div v-else-if="composerStep === 1" key="step1" class="incident-panel-body">
              <p class="text-sm font-medium mb-3">Wat is er aan de hand?</p>
              <div class="incident-kind-grid">
                <button
                  v-for="item in b.kinds"
                  :key="item.value"
                  type="button"
                  class="incident-kind"
                  :class="{ selected: kind === item.value }"
                  @click="kind = item.value"
                >
                  <i :class="['ki-filled', item.icon || 'ki-flag']"></i>
                  <strong>{{ item.label }}</strong>
                  <span>{{ item.hint }}</span>
                </button>
              </div>
              <div class="incident-panel-actions">
                <button type="button" class="kt-btn kt-btn-primary" @click="composerStep = 2">Volgende</button>
              </div>
            </div>

            <div v-else-if="composerStep === 2" key="step2" class="incident-panel-body">
              <label class="block mb-4">
                <span class="text-sm font-medium">Korte titel</span>
                <input v-model="title" type="text" maxlength="160" class="kt-input mt-1.5 w-full" placeholder="Bijv. Planning opent niet op mobiel">
              </label>
              <label class="block mb-4">
                <span class="text-sm font-medium">URL <span class="text-muted-foreground font-normal">(optioneel)</span></span>
                <input v-model="pageUrl" type="url" maxlength="2048" class="kt-input mt-1.5 w-full" placeholder="https://…" autocomplete="url">
              </label>
              <label class="block mb-4">
                <span class="text-sm font-medium">Omschrijving</span>
                <textarea v-model="description" rows="5" maxlength="5000" class="kt-input mt-1.5 w-full min-h-32" placeholder="Wat gebeurde er, wat verwachtte je, en waar zag je het?"></textarea>
              </label>
              <div>
                <span class="text-sm font-medium">Screenshots</span>
                <p class="text-xs text-muted-foreground mt-1 mb-2">Sleep, plak (Ctrl+V / Cmd+V) of maak een schermafbeelding. Max. 5, JPG/PNG/WebP tot 5MB.</p>
                <div
                  class="incident-drop"
                  :class="{ dragging }"
                  @dragover.prevent="dragging = true"
                  @dragleave="dragging = false"
                  @drop="onDrop"
                >
                  <input type="file" accept="image/*" multiple class="sr-only" id="incident-file" @change="addFiles(($event.target as HTMLInputElement).files || [])">
                  <label for="incident-file" class="incident-drop-label">
                    <i class="ki-filled ki-picture"></i>
                    <strong>Klik of sleep afbeelding</strong>
                    <span>JPG, PNG, WebP (max. 5MB)</span>
                  </label>
                  <button type="button" class="kt-btn kt-btn-outline kt-btn-sm" :disabled="capturing" @click="captureScreen">
                    <i class="ki-filled ki-screen me-1"></i>
                    {{ capturing ? 'Bezig…' : 'Schermafbeelding maken' }}
                  </button>
                </div>
                <div v-if="shots.length" class="incident-thumbs">
                  <div v-for="(shot, index) in shots" :key="shot.id" class="incident-thumb">
                    <button type="button" class="incident-thumb-open" :aria-label="'Vergroot ' + shot.name" @click="openLightbox(index)">
                      <img :src="shot.url" :alt="shot.name">
                    </button>
                    <button type="button" class="incident-thumb-remove" aria-label="Verwijderen" @click.stop="removeShot(shot.id)">
                      <i class="ki-filled ki-cross"></i>
                    </button>
                  </div>
                </div>
              </div>
              <div class="incident-panel-actions">
                <button type="button" class="kt-btn kt-btn-outline" @click="composerStep = 1">Terug</button>
                <button type="button" class="kt-btn kt-btn-primary" @click="composerStep = 3">Volgende</button>
              </div>
            </div>

            <div v-else key="step3" class="incident-panel-body">
              <p class="text-sm font-medium mb-3">Hoe dringend is het?</p>
              <div class="incident-priority-grid">
                <button
                  v-for="item in b.priorities"
                  :key="item.value"
                  type="button"
                  class="incident-priority"
                  :class="{ selected: priority === item.value }"
                  @click="priority = item.value"
                >
                  <strong>{{ item.label }}</strong>
                  <span>{{ item.hint }}</span>
                </button>
              </div>
              <div class="incident-summary">
                <p class="mb-1"><strong>{{ title || 'Geen titel' }}</strong></p>
                <p class="text-sm text-muted-foreground mb-0">{{ description.slice(0, 180) }}{{ description.length > 180 ? '…' : '' }}</p>
                <p v-if="pageUrl.trim()" class="text-xs text-muted-foreground mt-2 mb-0 break-all">{{ pageUrl.trim() }}</p>
                <p v-if="shots.length" class="text-xs text-muted-foreground mt-2 mb-0">{{ shots.length }} screenshot{{ shots.length === 1 ? '' : 's' }} bijgevoegd</p>
              </div>
              <label v-if="b.is_super_admin && (b.companies || []).length" class="block mt-4 mb-0">
                <span class="text-sm font-medium">Bedrijf (optioneel)</span>
                <select v-model="companyId" class="kt-select mt-1.5 w-full">
                  <option value="">Intern / geen specifiek bedrijf</option>
                  <option v-for="item in (b.companies || [])" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                </select>
              </label>
              <div class="incident-panel-actions">
                <button type="button" class="kt-btn kt-btn-outline" @click="composerStep = 2">Terug</button>
                <button type="button" class="kt-btn kt-btn-primary" :disabled="saving" @click="submitIncident">
                  {{ saving ? 'Versturen…' : 'Incident versturen' }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="detailOpen" class="incident-overlay" :class="{ 'is-open': detailVisible }" @click.self="closeDetail">
        <div class="incident-panel incident-panel-wide" :class="{ 'is-open': detailVisible }" role="dialog" aria-modal="true">
          <button type="button" class="incident-panel-close" aria-label="Sluiten" @click="closeDetail">
            <i class="ki-filled ki-cross"></i>
          </button>
          <div class="incident-panel-head">
            <div class="incident-panel-head-top">
              <div class="incident-panel-head-main">
                <p class="incident-panel-ref">{{ detail?.reference }}</p>
                <h2 class="incident-panel-title">{{ detail?.title || 'Incident' }}</h2>
              </div>
            </div>
            <div v-if="detail && !detailLoading" class="incident-badges">
              <span v-if="!b.is_super_admin" :class="toneClass(statusTone(detail.status))">{{ detail.status_label }}</span>
              <span :class="toneClass(priorityTone(detail.priority))">{{ detail.priority_label }}</span>
              <span class="incident-chip incident-chip-muted">{{ detail.kind_label }}</span>
              <select
                v-if="b.is_super_admin"
                v-model="handleStatus"
                class="incident-status-select"
                aria-label="Status"
                :disabled="statusSaving"
                @change="saveStatus"
              >
                <option v-for="item in b.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
              </select>
            </div>
          </div>
          <div class="incident-panel-body">
            <div v-if="detailLoading" class="incident-empty">Laden…</div>
            <template v-else-if="detail">
              <table class="incident-meta">
                <tbody>
                  <tr v-if="detail.company">
                    <th scope="row">Bedrijf</th>
                    <td>{{ detail.company.name }}</td>
                  </tr>
                  <tr v-if="detail.reporter">
                    <th scope="row">Gemeld door</th>
                    <td>{{ detail.reporter.name }}</td>
                  </tr>
                  <tr>
                    <th scope="row">Gemeld op</th>
                    <td>{{ detail.created_at_formatted }}</td>
                  </tr>
                  <tr v-if="detail.page_url">
                    <th scope="row">URL</th>
                    <td>
                      <a :href="detail.page_url" class="incident-page-url" target="_blank" rel="noopener noreferrer">{{ detail.page_url }}</a>
                    </td>
                  </tr>
                </tbody>
              </table>
              <div class="incident-frame">
                <p class="incident-description">{{ detail.description }}</p>
              </div>
              <div v-if="detail.screenshots?.length" class="incident-frame incident-thumbs-frame">
                <div class="incident-thumbs">
                  <button
                    v-for="(shot, index) in detail.screenshots"
                    :key="shot.url"
                    type="button"
                    class="incident-thumb"
                    @click="openLightbox(index)"
                  >
                    <img :src="shot.url" :alt="shot.name">
                  </button>
                </div>
              </div>
              <div v-if="detail.is_handled && !b.is_super_admin" class="incident-resolution">
                <strong>Afgehandeld</strong>
                <p class="text-sm mb-0 mt-1">{{ detail.resolution_note || 'Geen toelichting.' }}</p>
                <p class="text-xs text-muted-foreground mt-2 mb-0">
                  {{ detail.resolved_by?.name }} · {{ detail.resolved_at_formatted }}
                </p>
              </div>
              <div v-else-if="!b.is_super_admin" class="text-sm text-muted-foreground">
                We zijn ermee bezig. Je krijgt een melding zodra dit incident is afgehandeld.
              </div>
              <div v-if="b.is_super_admin" class="incident-handle">
                <p class="text-sm font-medium mb-3">Afhandeling</p>
                <div class="mb-4">
                  <span class="text-sm">Toelichting voor de klant</span>
                  <textarea
                    v-if="noteEditing"
                    ref="noteTextarea"
                    v-model="handleNote"
                    rows="4"
                    class="kt-input mt-1.5 w-full incident-autosize"
                    placeholder="Wat is er gedaan of wat moet de klant weten?"
                    @input="growNoteTextarea"
                  ></textarea>
                  <div v-else class="incident-note-view">
                    <p class="incident-note-text">{{ detail.resolution_note }}</p>
                    <div class="incident-note-actions">
                      <button type="button" class="incident-note-edit" aria-label="Toelichting bewerken" @click="noteEditing = true">
                        <i class="ki-filled ki-pencil"></i>
                      </button>
                      <button type="button" class="incident-note-edit" aria-label="Toelichting verwijderen" :disabled="noteDeleting" @click="requestDeleteNote">
                        <i class="ki-filled ki-trash"></i>
                      </button>
                    </div>
                  </div>
                  <div v-if="noteEditing" class="incident-handle-actions">
                    <span v-if="noteSavedMessage" class="incident-saved-hint" role="status">{{ noteSavedMessage }}</span>
                    <button type="button" class="kt-btn incident-btn-muted" :disabled="noteSaving" @click="cancelNote">Annuleren</button>
                    <button type="button" class="kt-btn kt-btn-primary" :disabled="noteSaving" @click="saveNote">
                      {{ noteSaving ? 'Opslaan…' : 'Opslaan' }}
                    </button>
                  </div>
                </div>
                <div class="mb-0">
                  <span class="text-sm">Commentaar</span>
                  <div v-if="detail.comments?.length" class="incident-comment-list">
                    <div v-for="comment in detail.comments" :key="comment.id" class="incident-comment">
                      <p class="incident-comment-body">{{ comment.body }}</p>
                      <div class="incident-comment-footer">
                        <p class="incident-comment-meta">{{ comment.user?.name }} · {{ comment.created_at_formatted }}</p>
                        <button
                          type="button"
                          class="incident-note-edit"
                          aria-label="Commentaar verwijderen"
                          :disabled="deletingCommentId === comment.id"
                          @click="requestDeleteComment(comment)"
                        >
                          <i class="ki-filled ki-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  <textarea
                    v-model="commentDraft"
                    rows="3"
                    class="kt-input mt-1.5 w-full"
                    placeholder="Interne notitie voor jezelf of andere ontwikkelaars"
                  ></textarea>
                  <div class="incident-handle-actions">
                    <span v-if="commentSavedMessage" class="incident-saved-hint" role="status">{{ commentSavedMessage }}</span>
                    <button type="button" class="kt-btn kt-btn-primary" :disabled="commentSaving || !commentDraft.trim()" @click="saveComment">
                      {{ commentSaving ? 'Opslaan…' : 'Opslaan' }}
                    </button>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="pendingDelete"
        class="incident-confirm-overlay"
        role="dialog"
        aria-modal="true"
        aria-labelledby="incident-confirm-title"
        @click.self="cancelPendingDelete"
      >
        <div class="incident-confirm-panel">
          <h3 id="incident-confirm-title" class="incident-confirm-title">{{ pendingDeleteTitle }}</h3>
          <p class="incident-confirm-text">{{ pendingDeleteText }}</p>
          <div class="incident-confirm-actions">
            <button type="button" class="kt-btn kt-btn-outline" @click="cancelPendingDelete">Annuleren</button>
            <button type="button" class="incident-confirm-delete" @click="confirmPendingDelete">Verwijderen</button>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="lightboxShot" class="incident-lightbox" @click="closeLightbox">
        <button type="button" class="incident-lightbox-close" aria-label="Sluiten" @click.stop="closeLightbox">
          <i class="ki-filled ki-cross"></i>
        </button>
        <button
          v-if="lightboxCanNav"
          type="button"
          class="incident-lightbox-nav is-prev"
          aria-label="Vorige afbeelding"
          @click.stop="lightboxPrev"
        >
          <i class="ki-filled ki-left"></i>
        </button>
        <figure class="incident-lightbox-figure" @click.stop>
          <img :src="lightboxShot.url" :alt="lightboxShot.name">
          <figcaption v-if="lightboxCanNav">{{ (lightboxIndex ?? 0) + 1 }} / {{ lightboxShots.length }}</figcaption>
        </figure>
        <button
          v-if="lightboxCanNav"
          type="button"
          class="incident-lightbox-nav is-next"
          aria-label="Volgende afbeelding"
          @click.stop="lightboxNext"
        >
          <i class="ki-filled ki-right"></i>
        </button>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.incident-stat {
  text-align: center;
  padding: 0.5rem;
}
.incident-stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 600;
  line-height: 1.1;
}
.incident-stat-label {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: var(--tw-muted-foreground, #6b7280);
}
.incident-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 2.5rem 1rem;
  color: var(--foreground);
}
.incident-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.incident-view-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  padding: 0.15rem;
  border: 1px solid var(--border, #e4e4e7);
  border-radius: 0.65rem;
}
.incident-view-btn {
  border: 0;
  background: transparent;
  color: var(--muted-foreground, #71717a);
  border-radius: 0.5rem;
  padding: 0.28rem 0.7rem;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}
.incident-view-btn.is-active {
  background: color-mix(in srgb, var(--foreground, #18181b) 8%, transparent);
  color: var(--foreground, #18181b);
}
html.dark .incident-view-btn.is-active,
.dark .incident-view-btn.is-active {
  background: rgb(255 255 255 / 0.1);
  color: #fafafa;
}
.incident-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.9rem;
}
.incident-check-all {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.875rem;
  cursor: pointer;
  border: 0;
  background: transparent;
  color: inherit;
  padding: 0;
}
.incident-check-all .kt-checkbox[aria-checked='mixed'],
.incident-row-check .kt-checkbox[aria-checked='mixed'] {
  border-color: var(--primary, #2563eb);
  background-color: var(--primary, #2563eb);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath fill='none' stroke='white' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 10h8'/%3E%3C/svg%3E");
}
.incident-toolbar-count {
  font-size: 0.8rem;
  color: var(--muted-foreground, #71717a);
}
.incident-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  text-align: left;
  padding: 0.65rem 0.75rem 0.65rem 0.85rem;
  border: 1px solid var(--border, #e4e4e7);
  border-radius: 0.9rem;
  background: var(--card, transparent);
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}
.incident-row:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 24px rgb(15 23 42 / 0.08);
  border-color: color-mix(in srgb, var(--primary, #2563eb) 35%, var(--border, #e4e4e7));
}
.incident-row.is-selected {
  border-color: color-mix(in srgb, var(--primary, #2563eb) 45%, var(--border, #e4e4e7));
}
.incident-row-check {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.35rem;
  cursor: pointer;
  border: 0;
  background: transparent;
}
.incident-row-open {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
  min-width: 0;
  padding: 0.35rem 0.35rem 0.35rem 0;
  border: 0;
  background: transparent;
  color: inherit;
  text-align: left;
  cursor: pointer;
}
.incident-row-main { flex: 1; min-width: 0; }
.incident-row-top { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.35rem; }
.incident-ref { font-size: 0.75rem; font-weight: 600; letter-spacing: 0.04em; color: var(--muted-foreground, #6b7280); }
.incident-row-title { display: block; font-size: 0.95rem; }
.incident-row-meta { margin: 0.25rem 0 0; font-size: 0.8rem; color: var(--muted-foreground, #6b7280); }
.incident-perpage-select {
  width: 4.25rem;
  min-width: 4.25rem;
  max-width: 4.25rem;
  padding-inline: 0.5rem 1.4rem;
}
.incident-chip {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.15rem 0.55rem;
  font-size: 0.72rem;
  font-weight: 600;
}
.incident-chip-info { background: rgb(59 130 246 / 0.12); color: #2563eb; }
.incident-chip-warning { background: rgb(245 158 11 / 0.14); color: #d97706; }
.incident-chip-success { background: rgb(34 197 94 / 0.14); color: #16a34a; }
.incident-chip-danger { background: rgb(239 68 68 / 0.14); color: #dc2626; }
.incident-chip-muted { background: rgb(113 113 122 / 0.12); color: var(--muted-foreground, #52525b); }
.incident-overlay {
  position: fixed;
  inset: 0;
  z-index: 100000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  opacity: 0;
  transition: opacity 0.22s ease;
}
html.dark .incident-overlay,
.dark .incident-overlay {
  background: rgba(9, 9, 11, 0.62);
}
.incident-overlay.is-open { opacity: 1; }
.incident-confirm-overlay {
  position: fixed;
  inset: 0;
  z-index: 100020;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}
.incident-confirm-panel {
  width: min(24rem, 100%);
  padding: 1.25rem 1.35rem 1.15rem;
  background: var(--card, var(--background, #fff));
  color: var(--foreground, #18181b);
  border: 1px solid var(--border, #e4e4e7);
  border-radius: 1rem;
  box-shadow: 0 25px 50px rgb(15 23 42 / 0.28);
}
.incident-confirm-title {
  margin: 0 0 0.4rem;
  font-size: 1.05rem;
  font-weight: 600;
  line-height: 1.3;
}
.incident-confirm-text {
  margin: 0 0 1.15rem;
  font-size: 0.9rem;
  line-height: 1.45;
  color: var(--muted-foreground, #52525b);
}
.incident-confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}
.incident-confirm-delete {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 2.25rem;
  padding: 0.4rem 0.9rem;
  border: 0;
  border-radius: 0.5rem;
  background: #dc2626;
  color: #fff;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
}
.incident-confirm-delete:hover {
  background: #b91c1c;
}
.incident-panel {
  position: relative;
  width: min(40rem, 100%);
  max-height: calc(100vh - 2.5rem);
  overflow: auto;
  overflow-x: hidden;
  text-align: left;
  background: var(--card, var(--background, #fff));
  color: var(--foreground, #18181b);
  border: 1px solid var(--border, #e4e4e7);
  border-radius: 1rem;
  box-shadow: 0 25px 50px rgb(15 23 42 / 0.28);
  transform: translateY(12px) scale(0.98);
  opacity: 0;
  transition: transform 0.22s ease, opacity 0.22s ease;
}
html.dark .incident-panel,
html.dark .incident-confirm-panel,
.dark .incident-panel,
.dark .incident-confirm-panel {
  background: #27272a;
  color: #fafafa;
  border-color: rgb(255 255 255 / 0.1);
}
html.dark .incident-panel .kt-input,
html.dark .incident-panel .kt-select,
.dark .incident-panel .kt-input,
.dark .incident-panel .kt-select {
  background-color: #1f1f23;
  border-color: rgb(255 255 255 / 0.1);
  color: #fafafa;
}
.incident-panel-wide { width: min(48rem, 100%); }
.incident-panel.is-open { transform: translateY(0) scale(1); opacity: 1; }
.incident-panel-close {
  position: absolute;
  top: 0.85rem;
  right: 0.85rem;
  z-index: 5;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.15rem;
  height: 2.15rem;
  padding: 0;
  border: 0;
  border-radius: 0.55rem;
  background: transparent;
  color: var(--muted-foreground, #71717a);
  cursor: pointer;
}
.incident-panel-close:hover {
  background: color-mix(in srgb, var(--foreground, #18181b) 8%, transparent);
  color: var(--foreground, #18181b);
}
.incident-panel-head {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  padding: 1.25rem 3.25rem 0.85rem 1.25rem;
}
.incident-panel-head-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}
.incident-panel-head-main {
  min-width: 0;
  flex: 1;
}
.incident-panel-ref {
  margin: 0 0 0.35rem;
  font-size: 0.75rem;
  color: var(--muted-foreground, #6b7280);
}
.incident-panel-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 500;
  line-height: 1.35;
  word-break: break-word;
}
.incident-panel-body {
  padding: 0 1.25rem 1.5rem;
  text-align: left;
}
.incident-alert-wrap {
  padding: 0.25rem 1.25rem 0;
  min-width: 0;
  max-width: 100%;
  box-sizing: border-box;
}
.incident-alert-wrap .kt-alert {
  margin: 0;
  max-width: 100%;
  box-sizing: border-box;
  overflow-wrap: anywhere;
  word-break: break-word;
  white-space: normal;
}
.incident-panel-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1.25rem;
}
.incident-stepper {
  display: flex;
  width: 100%;
  margin: 0;
  padding: 0.35rem 1.25rem 1.5rem;
  list-style: none;
}
.incident-stepper-item {
  display: flex;
  flex: 1;
  min-width: 0;
  flex-direction: column;
  align-items: stretch;
  text-align: center;
}
.incident-stepper-track {
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  width: 100%;
  height: 1.75rem;
}
.incident-stepper-marker {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 1;
  width: 1.75rem;
  height: 1.75rem;
  padding: 0;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  line-height: 1;
  flex-shrink: 0;
  border: 2px solid var(--border, #e4e4e7);
  background: var(--background, #fff);
  color: var(--muted-foreground, #71717a);
}
.incident-stepper-item.is-done .incident-stepper-marker,
.incident-stepper-marker--done {
  background: #166534;
  border-color: #166534;
  color: #fff;
  cursor: pointer;
}
.incident-stepper-marker--current,
.incident-stepper-item.is-current .incident-stepper-marker {
  background: #166534;
  border-color: #166534;
  color: #fff;
  box-shadow: 0 0 0 4px color-mix(in srgb, #166534 28%, transparent);
}
.incident-stepper-line {
  position: absolute;
  top: 50%;
  left: calc(50% + 1.05rem);
  right: calc(-50% + 1.05rem);
  height: 2px;
  margin: 0;
  transform: translateY(-50%);
  background: var(--border, #e4e4e7);
  pointer-events: none;
}
.incident-stepper-line.is-done {
  background: #166534;
}
.incident-stepper-label {
  display: block;
  margin: 0.5rem 0 0;
  padding: 0 0.2rem;
  border: 0;
  background: transparent;
  text-align: center;
  font-size: 0.7rem;
  line-height: 1.25;
  color: var(--muted-foreground, #71717a);
  font-weight: 500;
}
.incident-stepper-label.is-current {
  color: #166534;
  font-weight: 600;
}
.incident-stepper-label--clickable {
  color: var(--secondary-foreground, #3f3f46);
  cursor: pointer;
}
.incident-stepper-label--clickable:hover {
  color: #166534;
}
.incident-kind-grid, .incident-priority-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
.incident-kind, .incident-priority {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  text-align: left;
  padding: 0.9rem;
  border-radius: 0.9rem;
  border: 1px solid var(--border, #e4e4e7);
  background: transparent;
  transition: border-color 0.16s ease, transform 0.16s ease, background 0.16s ease;
}
.incident-kind i { font-size: 1.15rem; color: var(--primary, #2563eb); }
.incident-kind span, .incident-priority span { font-size: 0.75rem; color: var(--muted-foreground, #6b7280); }
.incident-kind.selected, .incident-priority.selected {
  border-color: var(--primary, #2563eb);
  background: color-mix(in srgb, var(--primary, #2563eb) 8%, transparent);
  transform: translateY(-1px);
}
.incident-drop {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1.25rem;
  border: 1.5px dashed var(--border, #d4d4d8);
  border-radius: 0.9rem;
  transition: border-color 0.16s ease, background 0.16s ease;
}
.incident-drop.dragging {
  border-color: var(--primary, #2563eb);
  background: color-mix(in srgb, var(--primary, #2563eb) 8%, transparent);
}
.incident-drop-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
  cursor: pointer;
  text-align: center;
}
.incident-drop-label i { font-size: 1.5rem; color: var(--muted-foreground, #6b7280); }
.incident-drop-label span { font-size: 0.75rem; color: var(--muted-foreground, #6b7280); }
.incident-thumbs {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(5.5rem, 1fr));
  gap: 0.6rem;
  margin-top: 0.85rem;
}
.incident-thumb {
  position: relative;
  aspect-ratio: 1;
  overflow: hidden;
  border-radius: 0.7rem;
  border: 1px solid var(--border, #e4e4e7);
  padding: 0;
  cursor: pointer;
}
.incident-thumb img { width: 100%; height: 100%; object-fit: cover; }
.incident-thumb-open {
  display: block;
  width: 100%;
  height: 100%;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: zoom-in;
}
.incident-thumb-open img {
  pointer-events: none;
}
.incident-thumb-remove {
  position: absolute;
  top: 0.25rem;
  right: 0.25rem;
  width: 1.4rem;
  height: 1.4rem;
  border: 0;
  border-radius: 999px;
  background: rgb(15 23 42 / 0.75);
  color: #fff;
}
.incident-summary {
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 0.9rem;
  background: rgb(113 113 122 / 0.08);
}
.incident-success {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 3rem 1.5rem;
}
.incident-success-mark {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 4.25rem;
  height: 4.25rem;
  margin-bottom: 1rem;
  border-radius: 999px;
  background: rgb(34 197 94 / 0.16);
  color: #16a34a;
  font-size: 1.75rem;
  animation: incident-pop 0.45s ease;
}
.incident-badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  margin-top: 0.7rem;
}
.incident-badges .incident-chip {
  padding: 0.32rem 0.8rem;
  font-size: 0.8125rem;
}
.incident-status-select {
  display: inline-flex;
  align-items: center;
  width: 8.7rem;
  min-width: 8.7rem;
  height: 2.2rem;
  margin-left: auto;
  flex-shrink: 0;
  padding: 0 1.95rem 0 0.95rem;
  border: 0;
  border-radius: 0.55rem;
  background-color: #2563eb;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23fff' d='M1 1.5h10L6 7z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.7rem center;
  background-size: 0.7rem 0.45rem;
  color: #fff;
  font-size: 0.8125rem;
  font-weight: 600;
  line-height: 1;
  cursor: pointer;
  appearance: none;
  -webkit-appearance: none;
  white-space: nowrap;
}
.incident-status-select:disabled {
  opacity: 0.7;
  cursor: wait;
}
.incident-status-select:focus {
  outline: 2px solid color-mix(in srgb, #2563eb 55%, #fff);
  outline-offset: 2px;
}
.incident-status-select option {
  color: #18181b;
  background: #fff;
  font-weight: 500;
}
.incident-meta {
  width: 100%;
  margin: 0 0 1.15rem;
  border-collapse: collapse;
  font-size: 0.875rem;
}
.incident-meta th,
.incident-meta td {
  padding: 0.45rem 0;
  vertical-align: top;
  text-align: left;
  border: 0;
  font-weight: 400;
}
.incident-meta th {
  width: 8.75rem;
  padding-right: 1rem;
  color: var(--muted-foreground, #6b7280);
  white-space: nowrap;
}
.incident-meta td {
  color: var(--foreground, #18181b);
}
.incident-frame {
  margin: 0 0 1.15rem;
  padding: 0.95rem 1.05rem;
  border: 1px solid color-mix(in srgb, var(--foreground, #18181b) 28%, transparent);
  border-radius: 0.9rem;
}
.incident-thumbs-frame .incident-thumbs {
  margin-top: 0;
}
.incident-description {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.55;
  white-space: pre-wrap;
}
.incident-page-url {
  color: var(--primary, #2563eb);
  word-break: break-all;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.incident-resolution, .incident-handle {
  padding: 1rem;
  border-radius: 0.9rem;
  background: rgb(113 113 122 / 0.08);
}
.incident-handle { margin-top: 1rem; }
.incident-handle-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.85rem;
  margin-top: 0.75rem;
}
.incident-btn-muted {
  background: transparent;
  border: 1px solid color-mix(in srgb, var(--foreground, #18181b) 22%, transparent);
  color: var(--foreground, #18181b);
}
.incident-btn-muted:hover:not(:disabled) {
  background: color-mix(in srgb, var(--foreground, #18181b) 6%, transparent);
  border-color: color-mix(in srgb, var(--foreground, #18181b) 34%, transparent);
}
html.dark .incident-btn-muted,
.dark .incident-btn-muted {
  background: transparent;
  border-color: rgb(255 255 255 / 0.22);
  color: #fafafa;
}
html.dark .incident-btn-muted:hover:not(:disabled),
.dark .incident-btn-muted:hover:not(:disabled) {
  background: rgb(255 255 255 / 0.06);
  border-color: rgb(255 255 255 / 0.34);
}
.incident-autosize {
  overflow: hidden;
  resize: none;
  min-height: 5.5rem;
  field-sizing: content;
}
.incident-note-view {
  position: relative;
  margin-top: 0.4rem;
  padding: 0.75rem 0.85rem 2rem;
  border: 1px solid color-mix(in srgb, var(--foreground, #18181b) 28%, transparent);
  border-radius: 0.7rem;
}
.incident-note-text {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.55;
  white-space: pre-wrap;
}
.incident-note-actions {
  position: absolute;
  right: 0.4rem;
  bottom: 0.3rem;
  display: flex;
  align-items: center;
  gap: 0.1rem;
}
.incident-note-edit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.65rem;
  height: 1.65rem;
  padding: 0;
  border: 0;
  border-radius: 0.4rem;
  background: transparent;
  color: var(--muted-foreground, #71717a);
  cursor: pointer;
}
.incident-note-edit:hover:not(:disabled) {
  color: var(--foreground, #18181b);
  background: color-mix(in srgb, var(--foreground, #18181b) 8%, transparent);
}
.incident-note-edit:disabled {
  opacity: 0.5;
  cursor: wait;
}
.incident-comment-list {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  margin: 0.65rem 0 0;
}
.incident-comment {
  padding: 0.7rem 0.8rem;
  border: 1px solid color-mix(in srgb, var(--foreground, #18181b) 16%, transparent);
  border-radius: 0.7rem;
}
.incident-comment-body {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.5;
  white-space: pre-wrap;
}
.incident-comment-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-top: 0.35rem;
}
.incident-comment-meta {
  margin: 0;
  font-size: 0.75rem;
  color: var(--muted-foreground, #71717a);
}
.incident-saved-hint {
  font-size: 0.875rem;
  font-weight: 600;
  color: #16a34a;
}
.incident-lightbox {
  position: fixed;
  inset: 0;
  z-index: 100010;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  background: rgba(15, 23, 42, 0.78);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  padding: 1.5rem 4.5rem;
}
.incident-lightbox-figure {
  margin: 0;
  max-width: min(72rem, 100%);
  max-height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}
.incident-lightbox-figure img {
  max-width: 100%;
  max-height: calc(100vh - 6.5rem);
  border-radius: 0.75rem;
  object-fit: contain;
}
.incident-lightbox-figure figcaption {
  margin: 0;
  font-size: 0.8rem;
  color: #e4e4e7;
}
.incident-lightbox-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 2.5rem;
  height: 2.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 999px;
  background: rgb(15 23 42 / 0.72);
  color: #fff;
  cursor: pointer;
}
.incident-lightbox-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 2.75rem;
  height: 2.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 999px;
  background: rgb(15 23 42 / 0.72);
  color: #fff;
  cursor: pointer;
}
.incident-lightbox-nav.is-prev { left: 1rem; }
.incident-lightbox-nav.is-next { right: 1rem; }
.incident-lightbox-nav:hover,
.incident-lightbox-close:hover {
  background: rgb(15 23 42 / 0.92);
}
.incident-list-enter-active, .incident-list-leave-active { transition: all 0.22s ease; }
.incident-list-enter-from, .incident-list-leave-to { opacity: 0; transform: translateY(8px); }
.incident-fade-enter-active, .incident-fade-leave-active { transition: all 0.18s ease; }
.incident-fade-enter-from { opacity: 0; transform: translateX(12px); }
.incident-fade-leave-to { opacity: 0; transform: translateX(-12px); }
@keyframes incident-pop {
  0% { transform: scale(0.6); opacity: 0; }
  70% { transform: scale(1.08); opacity: 1; }
  100% { transform: scale(1); }
}
.sr-only {
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
@media (max-width: 640px) {
  .incident-kind-grid, .incident-priority-grid { grid-template-columns: 1fr; }
  .incident-panel, .incident-panel-wide { width: 100%; }
}
</style>
