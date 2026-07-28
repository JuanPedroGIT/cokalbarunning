<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'
import ImageDropZone from '@/components/ui/ImageDropZone.vue'

interface Edition {
  id: string; name: string; year: number; isActive?: boolean
}

interface EmailConfigItem {
  id: string; raceEditionId: string; type: string
  subject: string | null; title: string | null; description: string | null
  prize: string | null; drawDate: string | null; prizeImageUrl: string | null
  createdAt: string; updatedAt: string
}

interface PreviewItem {
  id: string; firstName: string; lastName: string; fullName: string
  email: string | null; reference: string | null; club: string | null
  gender: string | null; category: string | null; birthDate: string | null
  emailValid: boolean; status: string; sentCount: number; errorMessage: string | null; sentAt: string | null
  selected?: boolean
}

interface LogItem {
  id: string; type: string; raceEditionId: string | null
  recipientEmail: string; recipientName: string; reference: string | null
  status: string; errorMessage: string | null; sentAt: string | null
  sentBy: string | null; createdAt: string; metadata: Record<string, unknown> | null
}

interface LogGroup {
  raceEditionId: string | null; recipientEmail: string; recipientName: string
  reference: string | null; count: number; lastSentAt: string | null
  lastStatus: string; lastErrorMessage: string | null; lastSentBy: string | null; createdAt: string
}

interface AdminUser {
  id: string; email: string; firstName: string; lastName: string; roles: string[]
}

interface EmailForm {
  raceEditionId: string; type: string; subject: string; title: string; description: string
  prize: string; drawDate: string; prizeImageUrl: string
}

type TabName = 'manage' | 'send'

const router = useRouter()
const activeTab = ref<TabName>('manage')

// Shared
const editions = ref<Edition[]>([])
const message = ref<{ type: 'success' | 'error'; text: string } | null>(null)

// Tab: Gestión
const configs = ref<EmailConfigItem[]>([])
const configEditionFilter = ref<string>('')
const editingConfigId = ref<string | null>(null)
const configSaving = ref(false)
const configForm = ref<EmailForm>({
  type: 'raffle', subject: '', title: '', description: '',
  prize: '', drawDate: '', prizeImageUrl: '',
})
const previewModalOpen = ref(false)
const previewData = ref<{ subject: string; html: string; configType: string } | null>(null)

// Tab: Envío
const selectedConfigId = ref<string>('')
const bibFrom = ref('')
const bibTo = ref('')
const items = ref<PreviewItem[]>([])
const loading = ref(false)
const sending = ref(false)
const forceResend = ref(false)
const logs = ref<LogItem[]>([])
const users = ref<AdminUser[]>([])
const selectedGroupKeys = ref<Set<string>>(new Set())
const sentCounts = ref<Map<string, number>>(new Map())
const bccEmail = ref('')
const currentSendType = ref<string>('')

const emailTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    raffle: 'Sorteo', last_instructions: 'Ultimas Indicaciones',
    thanks: 'Agradecimiento', bib: 'Dorsales', generic: 'Genérico',
  }
  return labels[type] ?? type
}

const referenceLabel = computed(() => 'Dorsal')

const filteredConfigs = computed(() => {
  if (!configEditionFilter.value) return configs.value
  return configs.value.filter((c) => c.raceEditionId === configEditionFilter.value)
})

// Auto-reset on edition change
watch(configEditionFilter, () => {
  items.value = []
  logs.value = []
  if (selectedConfigId.value && configEditionFilter.value) {
    const match = configs.value.find((c) => c.id === selectedConfigId.value)
    if (match && match.raceEditionId !== configEditionFilter.value) {
      selectedConfigId.value = ''
    }
  }
})

// -- Tab: Gestión ------------------------------------------------------------

async function fetchConfigs() {
  try {
    const params = configEditionFilter.value ? { editionId: configEditionFilter.value } : {}
    const res = await api.get('/admin/emails/config', { params })
    configs.value = res.data.data
    autoLoadExistingConfig()
  } catch { /* silencioso */ }
}

function editionName(id: string): string {
  return editions.value.find((e) => e.id === id)?.name ?? id
}

function newConfig() {
  editingConfigId.value = null
  configForm.value = {
    raceEditionId: configEditionFilter.value || (editions.value[0]?.id ?? ''),
    type: 'raffle', subject: '', title: '', description: '',
    prize: '', drawDate: '', prizeImageUrl: '',
  }
}

function editConfig(c: EmailConfigItem) {
  editingConfigId.value = c.id
  configForm.value = {
    raceEditionId: c.raceEditionId,
    type: c.type,
    subject: c.subject ?? '',
    title: c.title ?? '',
    description: c.description ?? '',
    prize: c.prize ?? '',
    drawDate: c.drawDate ?? '',
    prizeImageUrl: c.prizeImageUrl ?? '',
  }
}

function duplicateConfig(c: EmailConfigItem) {
  editingConfigId.value = null
  configForm.value = {
    raceEditionId: c.raceEditionId,
    type: c.type,
    subject: c.subject ?? '',
    title: c.title ? `${c.title} (copia)` : '',
    description: c.description ?? '',
    prize: c.prize ?? '',
    drawDate: c.drawDate ?? '',
    prizeImageUrl: '', // Don't copy the image reference
  }
  message.value = { type: 'success', text: 'Configuracion duplicada. Selecciona una edicion y tipo diferentes si quieres crear una nueva.' }
}

function autoLoadExistingConfig() {
  if (!configForm.value.raceEditionId) return
  const existing = configs.value.find(
    (c) => c.raceEditionId === configForm.value.raceEditionId && c.type === configForm.value.type
  )
  if (existing && existing.id !== editingConfigId.value) {
    editConfig(existing)
  } else if (!existing && editingConfigId.value) {
    newConfig()
  }
}

async function saveConfig() {
  if (!configForm.value.raceEditionId) {
    message.value = { type: 'error', text: 'Selecciona una edicion en el formulario' }
    return
  }
  configSaving.value = true
  message.value = null
  try {
    const payload: any = {
      editionId: configForm.value.raceEditionId,
      subject: configForm.value.subject,
      title: configForm.value.title,
      description: configForm.value.description,
      prizeImageUrl: configForm.value.prizeImageUrl,
    }
    if (configForm.value.type === 'raffle') {
      payload.prize = configForm.value.prize
      payload.drawDate = configForm.value.drawDate
    }
    if (editingConfigId.value) {
      await api.put(`/admin/emails/${configForm.value.type}/config/${editingConfigId.value}`, payload)
      message.value = { type: 'success', text: 'Configuracion actualizada.' }
    } else {
      const res = await api.post(`/admin/emails/${configForm.value.type}/config`, payload)
      editingConfigId.value = res.data.data.id
      message.value = { type: 'success', text: 'Configuracion creada.' }
    }
    await fetchConfigs()
  } catch (err: any) {
    message.value = { type: 'error', text: err.response?.data?.error ?? 'Error al guardar' }
  } finally {
    configSaving.value = false
  }
}

async function deleteConfig(id: string) {
  if (!confirm('Eliminar esta configuracion?')) return
  try {
    await api.delete(`/admin/emails/config/${id}`)
    message.value = { type: 'success', text: 'Configuracion eliminada.' }
    if (editingConfigId.value === id) newConfig()
    await fetchConfigs()
  } catch (err: any) {
    message.value = { type: 'error', text: err.response?.data?.error ?? 'Error al eliminar' }
  }
}

async function previewConfig(id: string) {
  try {
    const res = await api.get(`/admin/emails/config/${id}/preview`)
    previewData.value = res.data.data
    previewModalOpen.value = true
  } catch (err: any) {
    message.value = { type: 'error', text: err.response?.data?.error ?? 'Error al cargar preview' }
  }
}

async function uploadConfigImage(file: File) {
  if (!configForm.value.raceEditionId) {
    message.value = { type: 'error', text: 'Selecciona una edicion en el formulario' }
    return
  }
  configSaving.value = true
  try {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('editionId', configForm.value.raceEditionId)
    const res = await api.post(`/admin/emails/${configForm.value.type}/prize-image`, formData)
    configForm.value.prizeImageUrl = res.data.data.prizeImageUrl
    message.value = { type: 'success', text: 'Imagen subida.' }
  } catch (err: any) {
    message.value = { type: 'error', text: err.response?.data?.error ?? 'Error al subir imagen' }
  } finally {
    configSaving.value = false
  }
}

// -- Tab: Envío ---------------------------------------------------------------

async function fetchEditions() {
  try {
    const res = await api.get('/editions')
    editions.value = res.data.data
  } catch { /* */ }
}

async function fetchUsers() {
  try {
    const res = await api.get('/admin/users')
    users.value = res.data.data
  } catch { /* */ }
}

async function loadSendingPreview() {
  if (!selectedConfigId.value) {
    message.value = { type: 'error', text: 'Selecciona un email para enviar' }
    return
  }
  loading.value = true; message.value = null
  try {
    const payload: Record<string, unknown> = { emailConfigId: selectedConfigId.value }
    if (bibFrom.value !== '') payload.bibFrom = bibFrom.value
    if (bibTo.value !== '') payload.bibTo = bibTo.value
    const res = await api.post('/admin/emails/preview', payload)
    items.value = res.data.data.items.map((item: PreviewItem) => ({
      ...item,
      selected: false,
    }))
    page.value = 1
    if (res.data.data.config?.type) {
      currentSendType.value = res.data.data.config.type
    }
    message.value = { type: 'success', text: `${items.value.length} runner(s) encontrados.` }
    await fetchSendLogs()
    await fetchSendCounts()
  } catch (err: any) {
    message.value = { type: 'error', text: err.response?.data?.error ?? 'Error al cargar preview' }
  } finally {
    loading.value = false
  }
}

async function fetchSendLogs() {
  if (!currentSendType.value) return
  try {
    const params = configEditionFilter.value ? { editionId: configEditionFilter.value } : {}
    const res = await api.get(`/admin/emails/${currentSendType.value}`, { params })
    logs.value = res.data.data
  } catch { /* */ }
}

async function fetchSendCounts() {
  if (!currentSendType.value) return
  try {
    const params = configEditionFilter.value ? { editionId: configEditionFilter.value } : {}
    const res = await api.get(`/admin/emails/${currentSendType.value}/sent-counts`, { params })
    sentCounts.value = new Map((res.data.data as Array<{ email: string; count: number }>).map((e) => [e.email, e.count]))
  } catch { /* */ }
}

const userMap = computed(() => {
  const map = new Map<string, AdminUser>()
  users.value.forEach((u) => map.set(u.id, u))
  return map
})

const stats = computed(() => ({
  total: logs.value.length,
  sent: logs.value.filter((l) => l.status === 'sent').length,
  pending: logs.value.filter((l) => l.status === 'pending').length,
  error: logs.value.filter((l) => l.status === 'error').length,
}))

const validItems = computed(() => items.value.filter((i) => i.emailValid))
const invalidItems = computed(() => items.value.filter((i) => !i.emailValid))
const selectedItems = computed(() => items.value.filter((i) => i.selected && i.emailValid))
const allSelected = computed({
  get: () => validItems.value.length > 0 && validItems.value.every((i) => i.selected),
  set: (v: boolean) => validItems.value.forEach((i) => (i.selected = v)),
})

// Pagination
const page = ref(1)
const pageSize = ref(25)
const totalPages = computed(() => Math.max(1, Math.ceil(items.value.length / pageSize.value)))
const pagedItems = computed(() => {
  const start = (page.value - 1) * pageSize.value
  return items.value.slice(start, start + pageSize.value)
})
function goToPage(p: number) { page.value = Math.max(1, Math.min(p, totalPages.value)) }
const duplicateEmails = computed(() => {
  const counts = new Map<string, number>()
  items.value.forEach((i) => { if (i.email) counts.set(i.email, (counts.get(i.email) || 0) + 1) })
  return Array.from(counts.entries()).filter(([, c]) => c > 1).map(([e]) => e)
})

function groupKey(g: LogGroup): string {
  return `${g.raceEditionId ?? ''}|${g.recipientEmail}|${g.recipientName}|${g.reference ?? ''}`
}

const groupedLogs = computed(() => {
  const groups = new Map<string, LogGroup>()
  logs.value.forEach((log) => {
    const base: LogGroup = { raceEditionId: log.raceEditionId ?? null, recipientEmail: log.recipientEmail, recipientName: log.recipientName, reference: log.reference ?? null, count: 0, lastSentAt: null, lastStatus: log.status, lastErrorMessage: log.errorMessage, lastSentBy: log.sentBy, createdAt: log.createdAt }
    const key = groupKey(base)
    const existing = groups.get(key)
    if (existing) {
      if (log.status === 'sent') existing.count++
      if (log.sentAt && (existing.lastSentAt === null || log.sentAt > existing.lastSentAt)) existing.lastSentAt = log.sentAt
      if (log.createdAt > (existing as any).createdAt) {
        existing.lastStatus = log.status; existing.lastErrorMessage = log.errorMessage; existing.lastSentBy = log.sentBy
        ;(existing as any).createdAt = log.createdAt
      }
    } else {
      groups.set(key, { ...base, count: log.status === 'sent' ? 1 : 0, lastSentAt: log.sentAt, lastStatus: log.status, lastErrorMessage: log.errorMessage, lastSentBy: log.sentBy, createdAt: log.createdAt } as LogGroup)
    }
  })
  return Array.from(groups.values()).sort((a, b) => (b.lastSentAt ?? b.createdAt ?? '').localeCompare(a.lastSentAt ?? a.createdAt ?? ''))
})

const selectedGroups = computed(() => groupedLogs.value.filter((g) => selectedGroupKeys.value.has(groupKey(g))))
const allLogsSelected = computed({
  get: () => groupedLogs.value.length > 0 && groupedLogs.value.every((g) => selectedGroupKeys.value.has(groupKey(g))),
  set: (v: boolean) => { selectedGroupKeys.value = new Set(v ? groupedLogs.value.map((g) => groupKey(g)) : []) },
})

function statusLabel(s: string): string {
  return { pending: 'Pendiente', sent: 'Enviado', error: 'Error', not_sent: 'No enviado' }[s] ?? s
}
function statusClass(s: string): string {
  return { pending: 'bg-amber-500/10 text-amber-400', sent: 'bg-green-500/10 text-green-400', error: 'bg-red-500/10 text-red-400', not_sent: 'bg-gray-500/10 text-gray-400' }[s] ?? 'bg-gray-500/10 text-gray-400'
}

async function sendEmails() {
  if (selectedItems.value.length === 0) return
  sending.value = true; message.value = null
  try {
    const hasSent = selectedItems.value.some((i) => i.status === 'sent')
    const cfg = configs.value.find((c) => c.id === selectedConfigId.value)
    const payload: any = {
      emailConfigId: selectedConfigId.value,
      runnerIds: selectedItems.value.map((i) => i.id),
      editionId: cfg?.raceEditionId ?? configEditionFilter.value,
      force: forceResend.value || hasSent,
    }
    if (bccEmail.value) payload.metadata = { bccEmail: bccEmail.value }
    const res = await api.post('/admin/emails/send', payload)
    const { queued, skipped, queuedInstructions } = res.data.data
    let text = `${queued} correo(s) marcado(s) como pendiente(s). ${skipped} omitido(s).`
    if (queuedInstructions > 0) text += ` Tambien ${queuedInstructions} de ultimas indicaciones.`
    message.value = { type: 'success', text }
    await fetchSendLogs(); await fetchSendCounts()
  } catch (err: any) {
    message.value = { type: 'error', text: err.response?.data?.error ?? 'Error al enviar' }
  } finally { sending.value = false }
}

async function resendLogs(groups: LogGroup[]) {
  if (groups.length === 0) return
  if (!selectedConfigId.value || !configEditionFilter.value) {
    message.value = { type: 'error', text: 'Selecciona un email y una edicion para reenviar.' }
    return
  }
  sending.value = true; message.value = null
  try {
    const payload: any = {
      emailConfigId: selectedConfigId.value,
      runnerIds: [],
      runnerEmails: groups.map((g) => g.recipientEmail),
      editionId: configEditionFilter.value,
      force: true,
    }
    if (bccEmail.value) payload.metadata = { bccEmail: bccEmail.value }
    await api.post('/admin/emails/send', payload)
    message.value = { type: 'success', text: 'Reenvio encolado. Pulsa "Ejecutar envios pendientes".' }
    selectedGroupKeys.value = new Set()
    await fetchSendLogs(); await fetchSendCounts()
  } catch (err: any) {
    message.value = { type: 'error', text: err.response?.data?.error ?? 'Error al reenviar' }
  } finally { sending.value = false }
}

async function runPendingEmails() {
  if (!currentSendType.value) return
  sending.value = true; message.value = null
  try {
    const payload: any = {}
    if (configEditionFilter.value) payload.editionId = configEditionFilter.value
    if (bccEmail.value) payload.bccEmail = bccEmail.value
    await api.post(`/admin/emails/${currentSendType.value}/run`, payload)
    message.value = { type: 'success', text: 'Envio iniciado en segundo plano.' }
    setTimeout(async () => { await fetchSendLogs(); await fetchSendCounts() }, 3000)
  } catch (err: any) {
    message.value = { type: 'error', text: err.response?.data?.error ?? 'Error al iniciar envio' }
  } finally { sending.value = false }
}

function switchTab(tab: TabName) {
  activeTab.value = tab
  message.value = null
  if (tab === 'manage') fetchConfigs()
}

// Init
fetchEditions()
fetchUsers()
fetchConfigs()

// Recargar tabla al cambiar cualquier filtro en Tab Envío
watch([selectedConfigId, configEditionFilter, bibFrom, bibTo], () => {
  if (activeTab.value === 'send' && selectedConfigId.value) {
    loadSendingPreview()
  }
})
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A] text-white">
    <header class="bg-[#141414] p-4 flex items-center gap-4 border-b border-white/5">
      <button @click="router.back()" class="text-sm bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">
        ← Volver
      </button>
      <h1 class="text-xl font-bold uppercase tracking-wider">Emails</h1>
    </header>

    <main class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">
      <!-- Tabs -->
      <div class="flex gap-2 border-b border-white/5">
        <button @click="switchTab('manage')" :class="['px-4 py-2 text-sm font-medium transition cursor-pointer border-b-2', activeTab === 'manage' ? 'border-[#FF5C00] text-[#FF5C00]' : 'border-transparent text-gray-400 hover:text-white']">
          Gestion de Emails
        </button>
        <button @click="switchTab('send')" :class="['px-4 py-2 text-sm font-medium transition cursor-pointer border-b-2', activeTab === 'send' ? 'border-[#FF5C00] text-[#FF5C00]' : 'border-transparent text-gray-400 hover:text-white']">
          Envio de Emails
        </button>
      </div>

      <!-- Message -->
      <div v-if="message" :class="['rounded-lg border px-4 py-3 text-sm', message.type === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-400' : 'bg-red-500/10 border-red-500/20 text-red-400']">
        {{ message.text }}
      </div>

      <!-- ==================== TAB: GESTIÓN ==================== -->
      <template v-if="activeTab === 'manage'">
        <!-- Form -->
        <div class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-4">
          <h2 class="text-lg font-semibold text-naranja">
            {{ editingConfigId ? 'Editar email' : 'Nuevo email' }}
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs text-gray-400 mb-1">Edicion</label>
              <select v-model="configForm.raceEditionId" @change="autoLoadExistingConfig" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition">
                <option value="">-- Selecciona una edicion --</option>
                <option v-for="e in editions" :key="e.id" :value="e.id">{{ e.name }} ({{ e.year }}){{ e.isActive ? ' — Activa' : '' }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Tipo de email</label>
              <select v-model="configForm.type" @change="autoLoadExistingConfig" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition">
                <option value="raffle">Sorteo</option>
                <option value="last_instructions">Ultimas Indicaciones</option>
                <option value="thanks">Agradecimiento</option>
                <option value="bib">Dorsales</option>
                <option value="generic">Genérico</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs text-gray-400 mb-1">Asunto</label>
              <input v-model="configForm.subject" type="text" placeholder="Ej: Sorteo {title} - {prize}" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
              <p class="text-xs text-gray-500 mt-1">Placeholders: {title}, {drawDate}, {prize}, {description}, {editionName}, {reference}</p>
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Titulo</label>
              <input v-model="configForm.title" type="text" placeholder="Ej: Sorteo de la camiseta" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
            </div>
            <template v-if="configForm.type === 'raffle'">
              <div>
                <label class="block text-xs text-gray-400 mb-1">Fecha del sorteo</label>
                <input v-model="configForm.drawDate" type="text" placeholder="Ej: 5 de julio de 2026" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
              </div>
              <div>
                <label class="block text-xs text-gray-400 mb-1">Premio</label>
                <input v-model="configForm.prize" type="text" placeholder="Ej: Camiseta oficial" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
              </div>
            </template>
            <div class="sm:col-span-2">
              <label class="block text-xs text-gray-400 mb-1">Descripcion / Contenido</label>
              <textarea v-model="configForm.description" rows="3" placeholder="Describe el contenido del email..." class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"></textarea>
            </div>
            <div class="sm:col-span-2">
              <label class="block text-xs text-gray-400 mb-1">Imagen</label>
              <div class="max-w-[240px]">
                <ImageDropZone
                  :label="configSaving ? 'Subiendo...' : 'Arrastra una imagen o haz clic'"
                  :selected-label="'Arrastra otra imagen o haz clic para cambiar'"
                  :image-url="configForm.prizeImageUrl || undefined"
                  accept="image/*" square
                  @select="uploadConfigImage"
                  @clear="configForm.prizeImageUrl = ''"
                />
              </div>
            </div>
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button @click="saveConfig" :disabled="configSaving || !configForm.raceEditionId" class="bg-[#FF5C00] text-white px-5 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 cursor-pointer">
              {{ configSaving ? 'Guardando...' : (editingConfigId ? 'Actualizar' : 'Crear email') }}
            </button>
            <button v-if="editingConfigId" @click="newConfig" class="bg-[#222] text-white px-5 py-2 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">Nuevo</button>
          </div>
        </div>

        <!-- Config list -->
        <div class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="text-lg font-semibold text-naranja">Emails configurados</h2>
            <div class="w-full sm:w-64">
              <select v-model="configEditionFilter" @change="fetchConfigs" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-sm text-white focus:border-[#FF5C00] focus:outline-none transition">
                <option value="">-- Todas las ediciones --</option>
                <option v-for="e in editions" :key="e.id" :value="e.id">{{ e.name }} ({{ e.year }}){{ e.isActive ? ' — Activa' : '' }}</option>
              </select>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm min-w-[700px]">
              <thead class="bg-[#1a1a1a] text-gray-400">
                <tr>
                  <th class="p-2 md:p-3 font-medium">Tipo</th>
                  <th class="p-2 md:p-3 font-medium">Edicion</th>
                  <th class="p-2 md:p-3 font-medium">Asunto</th>
                  <th class="p-2 md:p-3 font-medium">Titulo</th>
                  <th class="p-2 md:p-3 font-medium">Creado</th>
                  <th class="p-2 md:p-3 font-medium text-right">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in configs" :key="c.id" class="border-t border-white/5 hover:bg-[#1a1a1a]">
                  <td class="p-2 md:p-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-[#FF5C00]/10 text-[#FF5C00]">{{ emailTypeLabel(c.type) }}</span>
                  </td>
                  <td class="p-2 md:p-3 text-gray-400">{{ editionName(c.raceEditionId) }}</td>
                  <td class="p-2 md:p-3">{{ c.subject ?? '—' }}</td>
                  <td class="p-2 md:p-3">{{ c.title ?? '—' }}</td>
                  <td class="p-2 md:p-3 text-gray-400 text-xs">{{ c.createdAt?.substring(0, 10) ?? '—' }}</td>
                  <td class="p-2 md:p-3 text-right">
                    <div class="flex gap-1 justify-end">
                      <button @click="previewConfig(c.id)" class="text-xs bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">Preview</button>
                      <button @click="editConfig(c)" class="text-xs bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">Editar</button>
                      <button @click="duplicateConfig(c)" class="text-xs bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">Duplicar</button>
                      <button @click="deleteConfig(c.id)" class="text-xs bg-red-900/20 text-red-400 px-3 py-1.5 rounded hover:bg-red-900/40 transition cursor-pointer border border-red-500/20">Eliminar</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="configs.length === 0">
                  <td colspan="6" class="p-4 text-center text-gray-500">No hay emails configurados{{ configEditionFilter ? ' para esta edicion' : '' }}.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Preview modal -->
        <div v-if="previewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click.self="previewModalOpen = false">
          <div class="bg-[#141414] rounded-lg border border-white/10 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-4 border-b border-white/5 flex justify-between items-center">
              <h3 class="font-semibold text-naranja">Vista previa — {{ emailTypeLabel(previewData?.configType ?? '') }}</h3>
              <button @click="previewModalOpen = false" class="text-gray-400 hover:text-white text-xl cursor-pointer">&times;</button>
            </div>
            <div class="p-4 space-y-4">
              <div>
                <span class="text-xs text-gray-400">Asunto:</span>
                <p class="text-sm text-white mt-0.5">{{ previewData?.subject }}</p>
              </div>
              <div>
                <span class="text-xs text-gray-400">Contenido:</span>
                <div class="mt-1 border border-white/10 rounded bg-white text-black p-4 text-sm" v-html="previewData?.html"></div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ==================== TAB: ENVÍO ==================== -->
      <template v-if="activeTab === 'send'">
        <!-- 1. Filtros -->
        <div class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-4">
          <h2 class="text-lg font-semibold text-naranja">Filtros</h2>
          <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div>
              <label class="block text-xs text-gray-400 mb-1">Edicion</label>
              <select v-model="configEditionFilter" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition">
                <option value="">-- Todas --</option>
                <option v-for="e in editions" :key="e.id" :value="e.id">{{ e.name }} ({{ e.year }})</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Email a enviar</label>
              <select v-model="selectedConfigId" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition">
                <option value="">-- Selecciona --</option>
                <option v-for="c in filteredConfigs" :key="c.id" :value="c.id">{{ emailTypeLabel(c.type) }} — {{ c.title ?? c.subject ?? 'sin titulo' }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Dorsal desde</label>
              <input v-model="bibFrom" type="number" placeholder="Ej: 1" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Dorsal hasta</label>
              <input v-model="bibTo" type="number" placeholder="Ej: 500" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
            </div>
          </div>
        </div>

        <!-- 2. Acciones -->
        <div class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-3">
          <h2 class="text-lg font-semibold text-naranja">Acciones</h2>
          <div class="flex flex-wrap items-center gap-2">
            <button @click="loadSendingPreview" :disabled="loading || !selectedConfigId" class="bg-[#FF5C00] text-white px-5 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 cursor-pointer">
              {{ loading ? 'Cargando...' : 'Cargar preview' }}
            </button>
            <button @click="sendEmails" :disabled="selectedItems.length === 0 || sending" class="bg-green-600 text-white px-5 py-2 rounded font-medium hover:bg-green-500 transition disabled:opacity-50 cursor-pointer">
              {{ sending ? 'Enviando...' : `Enviar ${selectedItems.length} correo(s)` }}
            </button>
            <button @click="runPendingEmails" :disabled="sending || !currentSendType" class="bg-amber-600 text-white px-5 py-2 rounded font-medium hover:bg-amber-500 transition disabled:opacity-50 cursor-pointer">
              Ejecutar pendientes
            </button>
            <label class="flex items-center gap-2 text-sm cursor-pointer text-gray-400 ml-2">
              <input v-model="forceResend" type="checkbox" class="w-4 h-4 accent-[#FF5C00]" /> Forzar reenvio
            </label>
            <input v-model.trim="bccEmail" type="email" placeholder="BCC (opcional)" class="bg-[#0A0A0A] border border-white/20 rounded px-3 py-2 text-sm text-white placeholder-white/30 focus:border-[#FF5C00] focus:outline-none transition w-40 ml-2" />
            <span v-if="items.length > 0" class="text-sm text-gray-400 ml-2">{{ items.length }} runners |</span>
            <span v-if="items.length > 0" class="text-xs text-green-400">{{ stats.sent }} enviados</span>
            <span v-if="items.length > 0" class="text-xs text-amber-400">{{ stats.pending }} pendientes</span>
            <span v-if="items.length > 0" class="text-xs text-red-400">{{ stats.error }} errores</span>
          </div>
        </div>

        <!-- 3. Tabla -->
        <div v-if="items.length > 0" class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-4">
          <div class="flex items-center justify-between gap-4">
            <h2 class="text-lg font-semibold text-naranja">Runners</h2>
            <div class="flex items-center gap-4">
              <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input v-model="allSelected" type="checkbox" class="w-4 h-4 accent-[#FF5C00]" /><span>Seleccionar todos</span>
              </label>
              <button @click="loadSendingPreview" :disabled="loading || !selectedConfigId" class="text-xs bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">Recargar</button>
            </div>
          </div>

          <div v-if="invalidItems.length" class="text-sm text-red-400">{{ invalidItems.length }} runner(s) con email invalido.</div>
          <div v-if="duplicateEmails.length" class="text-sm text-amber-400">Emails repetidos: {{ duplicateEmails.join(', ') }}</div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm min-w-[900px]">
              <thead class="bg-[#1a1a1a] text-gray-400">
                <tr>
                  <th class="p-2 md:p-3 w-10"></th>
                  <th class="p-2 md:p-3">Nombre</th>
                  <th class="p-2 md:p-3">Email</th>
                  <th class="p-2 md:p-3">Dorsal</th>
                  <th class="p-2 md:p-3">Estado</th>
                  <th class="p-2 md:p-3">Enviado</th>
                  <th class="p-2 md:p-3">Envios</th>
                  <th class="p-2 md:p-3">Enviado por</th>
                  <th class="p-2 md:p-3">Error</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in pagedItems" :key="item.id" :class="['border-t border-white/5', !item.emailValid ? 'opacity-60 bg-red-500/5' : item.status !== 'not_sent' ? 'bg-green-500/5' : 'hover:bg-[#1a1a1a]']">
                  <td class="p-2 md:p-3">
                    <input v-if="item.emailValid" v-model="item.selected" type="checkbox" class="w-4 h-4 accent-[#FF5C00]" />
                  </td>
                  <td class="p-2 md:p-3">{{ item.fullName }}</td>
                  <td class="p-2 md:p-3">{{ item.email }}</td>
                  <td class="p-2 md:p-3 font-mono">{{ item.reference ?? '—' }}</td>
                  <td class="p-2 md:p-3"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span></td>
                  <td class="p-2 md:p-3 text-gray-400 text-xs">{{ item.sentAt ?? '—' }}</td>
                  <td class="p-2 md:p-3 text-gray-400">{{ item.sentCount }}</td>
                  <td class="p-2 md:p-3 text-gray-400 text-xs">{{ '—' }}</td>
                  <td class="p-2 md:p-3 text-xs text-red-400 max-w-[120px] truncate">{{ item.errorMessage ?? '' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="flex items-center justify-between pt-2 border-t border-white/5">
            <div class="flex items-center gap-2 text-xs text-gray-400">
              <span>Pág. {{ page }} de {{ totalPages }} ({{ items.length }} runners)</span>
              <select v-model="pageSize" @change="goToPage(1)" class="bg-[#0A0A0A] border border-white/10 rounded px-2 py-1 text-white text-xs focus:border-[#FF5C00] focus:outline-none">
                <option :value="10">10</option><option :value="25">25</option><option :value="50">50</option><option :value="100">100</option>
              </select>
            </div>
            <div class="flex gap-1">
              <button @click="goToPage(1)" :disabled="page === 1" class="text-xs bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition disabled:opacity-30 cursor-pointer border border-white/10">««</button>
              <button @click="goToPage(page - 1)" :disabled="page === 1" class="text-xs bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition disabled:opacity-30 cursor-pointer border border-white/10">«</button>
              <button @click="goToPage(page + 1)" :disabled="page === totalPages" class="text-xs bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition disabled:opacity-30 cursor-pointer border border-white/10">»</button>
              <button @click="goToPage(totalPages)" :disabled="page === totalPages" class="text-xs bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition disabled:opacity-30 cursor-pointer border border-white/10">»»</button>
            </div>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>
