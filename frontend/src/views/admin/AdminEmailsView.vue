<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'
import ImageDropZone from '@/components/ui/ImageDropZone.vue'

interface Edition {
  id: string
  name: string
  year: number
  isActive?: boolean
}

interface PreviewItem {
  firstName: string
  lastName: string
  fullName: string
  email: string
  reference: string | null
  club: string | null
  gender: string | null
  category: string | null
  shirtSize: string | null
  birthDate: string | null
  emailValid: boolean
  status: string
  errorMessage: string | null
  sentAt: string | null
  selected?: boolean
}

interface LogItem {
  id: string
  type: string
  raceEditionId: string | null
  recipientEmail: string
  recipientName: string
  reference: string | null
  status: string
  errorMessage: string | null
  sentAt: string | null
  sentBy: string | null
  createdAt: string
  metadata: Record<string, unknown> | null
}

interface LogGroup {
  raceEditionId: string | null
  recipientEmail: string
  recipientName: string
  reference: string | null
  count: number
  lastSentAt: string | null
  lastStatus: string
  lastErrorMessage: string | null
  lastSentBy: string | null
  createdAt: string
}

interface AdminUser {
  id: string
  email: string
  firstName: string
  lastName: string
  roles: string[]
}

interface EmailConfigData {
  id?: string
  subject: string
  title: string
  description: string
  prize?: string
  drawDate?: string
  prizeImageUrl?: string
}

type EmailType = 'last_instructions' | 'raffle' | 'thanks' | 'generic'

const router = useRouter()
const fileInput = ref<HTMLInputElement | null>(null)
const edition = ref<Edition | null>(null)
const editions = ref<Edition[]>([])
const selectedEditionId = ref<string>('')
const items = ref<PreviewItem[]>([])
const logs = ref<LogItem[]>([])
const loading = ref(false)
const sending = ref(false)
const forceResend = ref(false)
const message = ref<{ type: 'success' | 'error'; text: string } | null>(null)
const users = ref<AdminUser[]>([])
const selectedGroupKeys = ref<Set<string>>(new Set())
const sentCounts = ref<Map<string, number>>(new Map())
const activeTab = ref<EmailType>('raffle')
const bccEmail = ref('')

const emailConfig = ref<EmailConfigData>({
  subject: '',
  title: '',
  description: '',
  prize: '',
  drawDate: '',
  prizeImageUrl: '',
})
const emailConfigId = ref<string | null>(null)
const emailConfigSaving = ref(false)

const userMap = computed(() => {
  const map = new Map<string, AdminUser>()
  users.value.forEach((u) => map.set(u.id, u))
  return map
})

const stats = computed(() => {
  const total = logs.value.length
  const sent = logs.value.filter((l) => l.status === 'sent').length
  const pending = logs.value.filter((l) => l.status === 'pending').length
  const error = logs.value.filter((l) => l.status === 'error').length
  return { total, sent, pending, error }
})

const emailSentCounts = computed(() => sentCounts.value)

function groupKey(group: LogGroup): string {
  return `${group.raceEditionId ?? ''}|${group.recipientEmail}|${group.recipientName}|${group.reference ?? ''}`
}

const groupedLogs = computed(() => {
  const groups = new Map<string, LogGroup>()

  logs.value.forEach((log) => {
    const base: LogGroup = {
      raceEditionId: log.raceEditionId ?? null,
      recipientEmail: log.recipientEmail,
      recipientName: log.recipientName,
      reference: log.reference ?? null,
      count: 0,
      lastSentAt: null,
      lastStatus: log.status,
      lastErrorMessage: log.errorMessage,
      lastSentBy: log.sentBy,
      createdAt: log.createdAt,
    }
    const key = groupKey(base)
    const existing = groups.get(key)

    if (existing) {
      if (log.status === 'sent') {
        existing.count += 1
      }
      if (log.sentAt && (existing.lastSentAt === null || log.sentAt > existing.lastSentAt)) {
        existing.lastSentAt = log.sentAt
      }
      if (log.createdAt > (existing as any).createdAt) {
        existing.lastStatus = log.status
        existing.lastErrorMessage = log.errorMessage
        existing.lastSentBy = log.sentBy
        ;(existing as any).createdAt = log.createdAt
      }
    } else {
      groups.set(key, {
        ...base,
        count: log.status === 'sent' ? 1 : 0,
        lastSentAt: log.sentAt,
        lastStatus: log.status,
        lastErrorMessage: log.errorMessage,
        lastSentBy: log.sentBy,
        createdAt: log.createdAt,
      } as LogGroup)
    }
  })

  return Array.from(groups.values()).sort((a, b) =>
    (b.lastSentAt ?? b.createdAt ?? '').localeCompare(a.lastSentAt ?? a.createdAt ?? '')
  )
})

const selectedGroups = computed(() =>
  groupedLogs.value.filter((g) => selectedGroupKeys.value.has(groupKey(g)))
)

const allLogsSelected = computed({
  get: () => groupedLogs.value.length > 0 && groupedLogs.value.every((g) => selectedGroupKeys.value.has(groupKey(g))),
  set: (value: boolean) => {
    selectedGroupKeys.value = new Set(value ? groupedLogs.value.map((g) => groupKey(g)) : [])
  },
})

const selectedEdition = computed(() =>
  editions.value.find((e) => e.id === selectedEditionId.value) ?? edition.value
)

function isZeroDorsal(ref: string | null): boolean {
  return ref !== null && ref !== '' && /^0+$/.test(ref)
}

const tabItems = computed(() => {
  if (activeTab.value === 'thanks') {
    return items.value.filter((i) => isZeroDorsal(i.reference))
  }
  if (activeTab.value === 'generic') {
    return items.value
  }
  return items.value.filter((i) => !isZeroDorsal(i.reference))
})

const validItems = computed(() => tabItems.value.filter((i) => i.emailValid))
const invalidItems = computed(() => tabItems.value.filter((i) => !i.emailValid))
const selectedItems = computed(() => tabItems.value.filter((i) => i.selected && i.emailValid))

const duplicateEmails = computed(() => {
  const counts = new Map<string, number>()
  tabItems.value.forEach((i) => counts.set(i.email, (counts.get(i.email) || 0) + 1))
  return Array.from(counts.entries()).filter(([, count]) => count > 1).map(([email]) => email)
})

const allSelected = computed({
  get: () => validItems.value.length > 0 && validItems.value.every((i) => i.selected),
  set: (value: boolean) => {
    validItems.value.forEach((i) => (i.selected = value))
  },
})

const referenceLabel = computed(() => {
  const labels: Record<EmailType, string> = {
    last_instructions: 'Dorsal',
    raffle: 'Dorsal',
    thanks: 'Dorsal',
    generic: 'Dorsal',
  }
  return labels[activeTab.value]
})

const csvFormatHint = 'Dorsal;Nombre;Apellidos;Sexo;email;...'

function statusLabel(status: string): string {
  const labels: Record<string, string> = {
    pending: 'Pendiente',
    sent: 'Enviado',
    error: 'Error',
    not_sent: 'No enviado',
  }
  return labels[status] ?? status
}

function statusClass(status: string): string {
  const map: Record<string, string> = {
    pending: 'bg-amber-500/10 text-amber-400',
    sent: 'bg-green-500/10 text-green-400',
    error: 'bg-red-500/10 text-red-400',
    not_sent: 'bg-gray-500/10 text-gray-400',
  }
  return map[status] ?? 'bg-gray-500/10 text-gray-400'
}

function setTab(tab: EmailType) {
  activeTab.value = tab
  selectedGroupKeys.value = new Set()
  fetchLogs()
  fetchSentCounts()
  loadEmailConfig()
}

async function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  loading.value = true
  message.value = null

  const formData = new FormData()
  formData.append('file', file)
  if (selectedEditionId.value) {
    formData.append('editionId', selectedEditionId.value)
  }

  try {
    const res = await api.post(`/admin/emails/${activeTab.value}/preview`, formData)
    edition.value = res.data.data.edition
    if (edition.value && !selectedEditionId.value) {
      selectedEditionId.value = edition.value.id
    }
    items.value = res.data.data.items.map((item: PreviewItem) => ({
      ...item,
      selected: item.emailValid && item.status !== 'sent',
    }))
    const runnersCreated = res.data.data.runnersCreated ?? 0
    message.value = {
      type: 'success',
      text: `${items.value.length} fila(s) procesadas. ${runnersCreated} runner(s) creado(s)/actualizado(s).`,
    }
    await fetchLogs()
    await fetchSentCounts()
  } catch (err: any) {
    message.value = {
      type: 'error',
      text: err.response?.data?.error ?? 'Error al procesar el CSV',
    }
  } finally {
    loading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

async function fetchEditions() {
  try {
    const res = await api.get('/editions')
    editions.value = res.data.data
    if (editions.value.length > 0 && !selectedEditionId.value) {
      const active = editions.value.find((e) => e.isActive)
      selectedEditionId.value = active?.id ?? editions.value[0]!.id
    }
  } catch {
    // Silencioso: el selector es opcional
  }
}

async function fetchUsers() {
  try {
    const res = await api.get('/admin/users')
    users.value = res.data.data
  } catch {
    // Silencioso: los usuarios son informativos
  }
}

async function fetchLogs() {
  try {
    const params = selectedEditionId.value ? { editionId: selectedEditionId.value } : {}
    const res = await api.get(`/admin/emails/${activeTab.value}`, { params })
    logs.value = res.data.data
  } catch {
    // Silencioso: los logs son informativos
  }
}

async function fetchSentCounts() {
  try {
    const params = selectedEditionId.value ? { editionId: selectedEditionId.value } : {}
    const res = await api.get(`/admin/emails/${activeTab.value}/sent-counts`, { params })
    const entries = res.data.data as Array<{ email: string; count: number }>
    sentCounts.value = new Map(entries.map((e) => [e.email, e.count]))
  } catch {
    // Silencioso: el contador es informativo
  }
}

function onEditionChange() {
  fetchLogs()
  fetchSentCounts()
  loadEmailConfig()
}

async function loadEmailConfig() {
  if (!selectedEditionId.value) return
  try {
    const res = await api.get(`/admin/emails/${activeTab.value}/config`, {
      params: { editionId: selectedEditionId.value },
    })
    const data = res.data.data
    if (data) {
      emailConfigId.value = data.id
      emailConfig.value = {
        subject: data.subject ?? '',
        title: data.title ?? '',
        description: data.description ?? '',
        prize: data.prize ?? '',
        drawDate: data.drawDate ?? '',
        prizeImageUrl: data.prizeImageUrl ?? '',
      }
    } else {
      emailConfigId.value = null
      emailConfig.value = { subject: '', title: '', description: '', prize: '', drawDate: '', prizeImageUrl: '' }
    }
  } catch {
    // Silencioso: la configuracion es opcional
  }
}

async function saveEmailConfig() {
  if (!selectedEditionId.value) {
    message.value = { type: 'error', text: 'Selecciona una edicion antes de guardar la configuracion' }
    return
  }

  emailConfigSaving.value = true
  message.value = null

  const typeLabel = activeTab.value === 'raffle' ? 'del sorteo' : activeTab.value === 'last_instructions' ? 'de ultimas indicaciones' : activeTab.value === 'thanks' ? 'de agradecimiento' : 'generica'

  try {
    const payload: any = {
      editionId: selectedEditionId.value,
      subject: emailConfig.value.subject,
      title: emailConfig.value.title,
      description: emailConfig.value.description,
    }
    payload.prizeImageUrl = emailConfig.value.prizeImageUrl
    if (activeTab.value === 'raffle') {
      payload.prize = emailConfig.value.prize
      payload.drawDate = emailConfig.value.drawDate
    }
    if (emailConfigId.value) {
      await api.put(`/admin/emails/${activeTab.value}/config/${emailConfigId.value}`, payload)
      message.value = { type: 'success', text: `Configuracion ${typeLabel} actualizada.` }
    } else {
      const res = await api.post(`/admin/emails/${activeTab.value}/config`, payload)
      emailConfigId.value = res.data.data.id
      message.value = { type: 'success', text: `Configuracion ${typeLabel} guardada.` }
    }
  } catch (err: any) {
    message.value = {
      type: 'error',
      text: err.response?.data?.error ?? `Error al guardar la configuracion ${typeLabel}`,
    }
  } finally {
    emailConfigSaving.value = false
  }
}

async function uploadPrizeImage(file: File) {
  if (!selectedEditionId.value) {
    message.value = { type: 'error', text: 'Selecciona una edicion antes de subir la imagen' }
    return
  }

  emailConfigSaving.value = true
  message.value = null

  const formData = new FormData()
  formData.append('file', file)
  formData.append('editionId', selectedEditionId.value)

  try {
    const res = await api.post(`/admin/emails/${activeTab.value}/prize-image`, formData)
    emailConfig.value.prizeImageUrl = res.data.data.prizeImageUrl
    emailConfigId.value = res.data.data.id
    message.value = { type: 'success', text: 'Imagen del premio subida correctamente.' }
  } catch (err: any) {
    message.value = {
      type: 'error',
      text: err.response?.data?.error ?? 'Error al subir la imagen del premio',
    }
  } finally {
    emailConfigSaving.value = false
  }
}

async function loadGenericRecipients() {
  loading.value = true
  message.value = null

  try {
    const res = await api.get('/admin/emails/generic/recipients')
    const data = res.data.data
    items.value = data.items.map((item: PreviewItem) => ({
      ...item,
      selected: item.emailValid,
    }))
    message.value = {
      type: 'success',
      text: `${data.total} participante(s) cargado(s) de todas las ediciones (agrupados por email, edicion mas antigua).`,
    }
    await fetchLogs()
    await fetchSentCounts()
  } catch (err: any) {
    message.value = {
      type: 'error',
      text: err.response?.data?.error ?? 'Error al cargar los participantes',
    }
  } finally {
    loading.value = false
  }
}

async function sendEmails() {
  if (selectedItems.value.length === 0) return

  sending.value = true
  message.value = null

  try {
    const payload: any = {
      force: forceResend.value,
      items: selectedItems.value.map(({ firstName, lastName, fullName, email, reference, club, gender, category, birthDate }) => ({
        firstName,
        lastName,
        fullName,
        email,
        reference,
        club,
        gender,
        category,
        birthDate,
      })),
    }
    if (selectedEditionId.value) {
      payload.editionId = selectedEditionId.value
    }
    const meta: Record<string, unknown> = {}
    for (const [k, v] of Object.entries(emailConfig.value)) {
      if (v !== '' && v !== null && v !== undefined) meta[k] = v
    }
    if (bccEmail.value) meta.bccEmail = bccEmail.value
    payload.metadata = Object.keys(meta).length > 0 ? meta : undefined

    const typeLabel2 = activeTab.value === 'raffle' ? 'sorteo' : activeTab.value === 'last_instructions' ? 'indicaciones' : activeTab.value === 'thanks' ? 'agradecimiento' : 'generico'
    const res = await api.post(`/admin/emails/${activeTab.value}/send`, payload)
    const { queued, skipped, queuedInstructions } = res.data.data
    const baseText = `${queued} correo(s) de ${typeLabel2} marcado(s) como pendiente(s). ${skipped} omitido(s) por ya enviado(s).`
    const extraText = activeTab.value === 'raffle' && queuedInstructions > 0
      ? ` Tambien se han encolado ${queuedInstructions} correo(s) de ultimas indicaciones.`
      : ''
    message.value = {
      type: 'success',
      text: baseText + extraText,
    }
    await fetchLogs()
    await fetchSentCounts()
  } catch (err: any) {
    message.value = {
      type: 'error',
      text: err.response?.data?.error ?? 'Error al marcar los correos como pendientes',
    }
  } finally {
    sending.value = false
  }
}

async function resendLogs(groupsToResend: LogGroup[]) {
  if (groupsToResend.length === 0) return

  sending.value = true
  message.value = null

  try {
    const payload: any = {
      force: true,
      items: groupsToResend.map((group) => ({
        firstName: '',
        lastName: '',
        fullName: group.recipientName,
        email: group.recipientEmail,
        reference: group.reference,
      })),
    }
    if (selectedEditionId.value) {
      payload.editionId = selectedEditionId.value
    }
    const meta: Record<string, unknown> = {}
    for (const [k, v] of Object.entries(emailConfig.value)) {
      if (v !== '' && v !== null && v !== undefined) meta[k] = v
    }
    if (bccEmail.value) meta.bccEmail = bccEmail.value
    payload.metadata = Object.keys(meta).length > 0 ? meta : undefined

    const res = await api.post(`/admin/emails/${activeTab.value}/send`, payload)
    const { queued, skipped } = res.data.data
    message.value = {
      type: 'success',
      text: `${queued} correo(s) marcado(s) para reenvio. Pulsa "Ejecutar envios pendientes" para enviarlos.`,
    }
    selectedGroupKeys.value = new Set()
    await fetchLogs()
    await fetchSentCounts()
  } catch (err: any) {
    message.value = {
      type: 'error',
      text: err.response?.data?.error ?? 'Error al reenviar los correos',
    }
  } finally {
    sending.value = false
  }
}

async function runPendingEmails() {
  sending.value = true
  message.value = null

  try {
    const payload: any = {}
    if (selectedEditionId.value) {
      payload.editionId = selectedEditionId.value
    }
    if (bccEmail.value) {
      payload.bccEmail = bccEmail.value
    }
    const res = await api.post(`/admin/emails/${activeTab.value}/run`, payload)
    message.value = {
      type: 'success',
      text: res.data.data.message,
    }
    // Refrescar logs y conteos tras unos segundos para reflejar el envio en segundo plano.
    setTimeout(async () => {
      await fetchLogs()
      await fetchSentCounts()
    }, 3000)
  } catch (err: any) {
    message.value = {
      type: 'error',
      text: err.response?.data?.error ?? 'Error al iniciar el envio',
    }
  } finally {
    sending.value = false
  }
}

fetchEditions().then(() => {
  fetchLogs()
  fetchSentCounts()
  fetchUsers()
  loadEmailConfig()
})
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A] text-white">
    <header class="bg-[#141414] p-4 flex items-center gap-4 border-b border-white/5">
      <button
        @click="router.back()"
        class="text-sm bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10"
      >
        ← Volver
      </button>
      <h1 class="text-xl font-bold uppercase tracking-wider">Envio de Correos</h1>
    </header>

    <main class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">
      <!-- Upload -->
      <div class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-naranja">
              {{ activeTab === 'generic' ? 'Cargar participantes de todas las ediciones' : 'Seleccionar edicion y subir CSV de inscritos' }}
            </h2>
            <p v-if="activeTab !== 'generic'" class="text-sm text-gray-400">
              Formato: <code class="bg-[#0A0A0A] px-1.5 py-0.5 rounded">{{ csvFormatHint }}</code>
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Edicion</label>
            <select
              v-model="selectedEditionId"
              @change="onEditionChange"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            >
              <option
                v-for="e in editions"
                :key="e.id"
                :value="e.id"
              >
                {{ e.name }} ({{ e.year }}){{ e.isActive ? ' — Activa' : '' }}
              </option>
            </select>
          </div>
          <div class="flex items-end gap-4">
            <template v-if="activeTab === 'generic'">
              <button
                @click="loadGenericRecipients"
                :disabled="loading"
                class="bg-[#FF5C00] text-white px-5 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 cursor-pointer"
              >
                {{ loading ? 'Cargando...' : 'Cargar participantes' }}
              </button>
              <span v-if="items.length > 0" class="text-sm text-gray-400">
                {{ items.length }} participante(s) cargados
              </span>
            </template>
            <template v-else>
              <input
                ref="fileInput"
                type="file"
                accept=".csv,text/csv"
                @change="handleFileChange"
                class="hidden"
              />
              <button
                @click="fileInput?.click()"
                :disabled="loading"
                class="bg-[#FF5C00] text-white px-5 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 cursor-pointer"
              >
                {{ loading ? 'Procesando...' : 'Seleccionar CSV' }}
              </button>
              <span v-if="items.length > 0" class="text-sm text-gray-400">
                {{ items.length }} fila(s) procesadas
              </span>
            </template>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex gap-2 border-b border-white/5">
        <button
          @click="setTab('raffle')"
          :class="[
            'px-4 py-2 text-sm font-medium transition cursor-pointer border-b-2',
            activeTab === 'raffle'
              ? 'border-[#FF5C00] text-[#FF5C00]'
              : 'border-transparent text-gray-400 hover:text-white',
          ]"
        >
          1. Sorteo
        </button>
        <button
          @click="setTab('last_instructions')"
          :class="[
            'px-4 py-2 text-sm font-medium transition cursor-pointer border-b-2',
            activeTab === 'last_instructions'
              ? 'border-[#FF5C00] text-[#FF5C00]'
              : 'border-transparent text-gray-400 hover:text-white',
          ]"
        >
          2. Últimas Indicaciones
        </button>
        <button
          @click="setTab('thanks')"
          :class="[
            'px-4 py-2 text-sm font-medium transition cursor-pointer border-b-2',
            activeTab === 'thanks'
              ? 'border-[#FF5C00] text-[#FF5C00]'
              : 'border-transparent text-gray-400 hover:text-white',
          ]"
        >
          3. Agradecimiento
        </button>
        <button
          @click="setTab('generic')"
          :class="[
            'px-4 py-2 text-sm font-medium transition cursor-pointer border-b-2',
            activeTab === 'generic'
              ? 'border-[#FF5C00] text-[#FF5C00]'
              : 'border-transparent text-gray-400 hover:text-white',
          ]"
        >
          4. Genérico
        </button>
      </div>

      <div
        v-if="message"
        :class="[
          'rounded-lg border px-4 py-3 text-sm',
          message.type === 'success'
            ? 'bg-green-500/10 border-green-500/20 text-green-400'
            : 'bg-red-500/10 border-red-500/20 text-red-400',
        ]"
      >
        {{ message.text }}
      </div>

      <!-- Email config -->
      <div
        class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-4"
      >
        <h2 class="text-lg font-semibold text-naranja">
          {{ activeTab === 'raffle' ? 'Configuración del sorteo' : activeTab === 'last_instructions' ? 'Configuración de últimas indicaciones' : activeTab === 'thanks' ? 'Configuración de agradecimiento' : 'Configuración genérica' }}
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="block text-xs text-gray-400 mb-1">Asunto del correo</label>
            <input
              v-model="emailConfig.subject"
              type="text"
              :placeholder="activeTab === 'raffle' ? 'Ej: Sorteo {title} - {prize}' : 'Ej: Ultimas indicaciones - {editionName}'"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            />
            <p class="text-xs text-gray-500 mt-1">Variables disponibles: {title}, {drawDate}, {prize}, {description}, {editionName}, {reference}</p>
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Título</label>
            <input
              v-model="emailConfig.title"
              type="text"
              :placeholder="activeTab === 'raffle' ? 'Ej: Sorteo de la camiseta' : 'Ej: Instrucciones para la carrera'"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            />
          </div>
          <template v-if="activeTab === 'raffle'">
            <div>
              <label class="block text-xs text-gray-400 mb-1">Fecha del sorteo</label>
              <input
                v-model="emailConfig.drawDate"
                type="text"
                placeholder="Ej: 5 de julio de 2026"
                class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
              />
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Premio</label>
              <input
                v-model="emailConfig.prize"
                type="text"
                placeholder="Ej: Camiseta oficial de la carrera"
                class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
              />
            </div>
          </template>
          <div class="sm:col-span-2">
            <label class="block text-xs text-gray-400 mb-1">
              {{ activeTab === 'raffle' ? 'Descripción / bases' : 'Descripción / contenido' }}
            </label>
            <textarea
              v-model="emailConfig.description"
              rows="3"
              :placeholder="activeTab === 'raffle' ? 'Describe las bases del sorteo...' : 'Describe el contenido de las ultimas indicaciones...'"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            ></textarea>
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs text-gray-400 mb-1">
              {{ activeTab === 'raffle' ? 'Foto del premio' : 'Foto' }}
            </label>
            <div class="max-w-[240px]">
              <ImageDropZone
                :label="emailConfigSaving ? 'Subiendo...' : (activeTab === 'raffle' ? 'Arrastra la foto del premio aqui o haz clic' : 'Arrastra la foto aqui o haz clic')"
                :selected-label="'Arrastra otra imagen o haz clic para cambiar'"
                :image-url="emailConfig.prizeImageUrl || undefined"
                accept="image/*"
                square
                @select="uploadPrizeImage"
                @clear="emailConfig.prizeImageUrl = ''"
              />
            </div>
          </div>
        </div>
        <div class="flex items-center gap-3 pt-2">
          <button
            @click="saveEmailConfig"
            :disabled="emailConfigSaving"
            class="bg-[#FF5C00] text-white px-5 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 cursor-pointer"
          >
            {{ emailConfigSaving ? 'Guardando...' : (emailConfigId ? 'Actualizar configuracion' : 'Guardar configuracion') }}
          </button>
          <span v-if="emailConfigId" class="text-xs text-green-400">
            Configuracion guardada
          </span>
        </div>
      </div>

      <!-- Preview -->
      <div v-if="items.length > 0" class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <h2 class="text-lg font-semibold text-naranja">2. Vista previa</h2>
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input v-model="allSelected" type="checkbox" class="w-4 h-4 accent-[#FF5C00]" />
            <span>Seleccionar todos los validos</span>
          </label>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm min-w-[640px]">
            <thead class="bg-[#1a1a1a] text-gray-400">
              <tr>
                <th class="p-2 md:p-3 font-medium w-10"></th>
                <th class="p-2 md:p-3 font-medium">Nombre</th>
                <th class="p-2 md:p-3 font-medium">Email</th>
                <th class="p-2 md:p-3 font-medium">{{ referenceLabel }}</th>
                <th class="p-2 md:p-3 font-medium">Envios previos</th>
                <th class="p-2 md:p-3 font-medium">Estado anterior</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in tabItems"
                :key="`${item.email}-${item.reference ?? ''}`"
                :class="[
                  'border-t border-white/5 transition',
                  !item.emailValid ? 'opacity-60 bg-red-500/5' : 'hover:bg-[#1a1a1a]',
                ]"
              >
                <td class="p-2 md:p-3">
                  <input
                    v-if="item.emailValid"
                    v-model="item.selected"
                    type="checkbox"
                    class="w-4 h-4 accent-[#FF5C00]"
                  />
                  <span v-else class="text-red-400 text-xs">Invalido</span>
                </td>
                <td class="p-2 md:p-3">{{ item.fullName }}</td>
                <td class="p-2 md:p-3">{{ item.email }}</td>
                <td class="p-2 md:p-3 font-mono">{{ item.reference ?? '—' }}</td>
                <td class="p-2 md:p-3 text-gray-400">
                  {{ emailSentCounts.get(item.email) ?? 0 }}
                </td>
                <td class="p-2 md:p-3">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                    :class="statusClass(item.status)"
                  >
                    {{ statusLabel(item.status) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="invalidItems.length > 0" class="text-sm text-red-400">
          Hay {{ invalidItems.length }} fila(s) con email invalido y no se enviaran.
        </div>
        <div v-if="duplicateEmails.length > 0" class="text-sm text-amber-400">
          Emails repetidos: {{ duplicateEmails.join(', ') }}.
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 pt-2 border-t border-white/5">
          <button
            @click="sendEmails"
            :disabled="selectedItems.length === 0 || sending"
            class="bg-[#FF5C00] text-white px-6 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            {{ sending ? 'Enviando...' : `Enviar ${selectedItems.length} correo(s)` }}
          </button>
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input v-model="forceResend" type="checkbox" class="w-4 h-4 accent-[#FF5C00]" />
            <span>Forzar reenvio a los ya enviados</span>
          </label>
        </div>
      </div>

      <!-- Logs -->
      <div v-if="logs.length > 0" class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <h2 class="text-lg font-semibold text-naranja">Historial de envios</h2>
          <div class="flex flex-wrap gap-3 text-xs">
            <span class="bg-gray-500/10 text-gray-400 px-2 py-1 rounded">Total: {{ stats.total }}</span>
            <span class="bg-green-500/10 text-green-400 px-2 py-1 rounded">Enviados: {{ stats.sent }}</span>
            <span class="bg-amber-500/10 text-amber-400 px-2 py-1 rounded">Pendientes: {{ stats.pending }}</span>
            <span class="bg-red-500/10 text-red-400 px-2 py-1 rounded">Errores: {{ stats.error }}</span>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="runPendingEmails"
            :disabled="sending"
            class="bg-green-600 text-white text-sm px-4 py-2 rounded font-medium hover:bg-green-500 transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            {{ sending ? 'Iniciando...' : 'Ejecutar envios pendientes' }}
          </button>
          <input
            v-model.trim="bccEmail"
            type="email"
            placeholder="BCC (opcional)"
            class="bg-[#0A0A0A] border border-white/20 rounded px-3 py-2 text-sm text-white placeholder-white/30 focus:border-[#FF5C00] focus:outline-none transition w-44"
          />
          <button
            @click="resendLogs(selectedGroups)"
            :disabled="selectedGroups.length === 0 || sending"
            class="bg-[#FF5C00] text-white text-sm px-4 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            {{ sending ? 'Reenviando...' : `Reenviar ${selectedGroups.length} seleccionado(s)` }}
          </button>
          <label class="flex items-center gap-2 text-sm cursor-pointer text-gray-400">
            <input v-model="allLogsSelected" type="checkbox" class="w-4 h-4 accent-[#FF5C00]" />
            <span>Seleccionar todos</span>
          </label>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm min-w-[900px]">
            <thead class="bg-[#1a1a1a] text-gray-400">
              <tr>
                <th class="p-2 md:p-3 font-medium w-10"></th>
                <th class="p-2 md:p-3 font-medium">Nombre</th>
                <th class="p-2 md:p-3 font-medium">Email</th>
                <th class="p-2 md:p-3 font-medium">{{ referenceLabel }}</th>
                <th class="p-2 md:p-3 font-medium">Estado</th>
                <th class="p-2 md:p-3 font-medium">Enviado</th>
                <th class="p-2 md:p-3 font-medium">Envios</th>
                <th class="p-2 md:p-3 font-medium">Enviado por</th>
                <th class="p-2 md:p-3 font-medium text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="group in groupedLogs" :key="groupKey(group)" class="border-t border-white/5 hover:bg-[#1a1a1a]">
                <td class="p-2 md:p-3">
                  <input
                    v-model="selectedGroupKeys"
                    :value="groupKey(group)"
                    type="checkbox"
                    class="w-4 h-4 accent-[#FF5C00]"
                  />
                </td>
                <td class="p-2 md:p-3">{{ group.recipientName }}</td>
                <td class="p-2 md:p-3">{{ group.recipientEmail }}</td>
                <td class="p-2 md:p-3 font-mono">{{ group.reference ?? '—' }}</td>
                <td class="p-2 md:p-3">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                    :class="statusClass(group.lastStatus)"
                  >
                    {{ statusLabel(group.lastStatus) }}
                  </span>
                  <div v-if="group.lastErrorMessage" class="text-xs text-red-400 mt-1">
                    {{ group.lastErrorMessage }}
                  </div>
                </td>
                <td class="p-2 md:p-3 text-gray-400">
                  {{ group.lastSentAt ?? '—' }}
                </td>
                <td class="p-2 md:p-3 text-gray-400">
                  {{ group.count }}
                </td>
                <td class="p-2 md:p-3 text-gray-400">
                  <span v-if="group.lastSentBy && userMap.get(group.lastSentBy)">
                    {{ userMap.get(group.lastSentBy)?.email }}
                  </span>
                  <span v-else>—</span>
                </td>
                <td class="p-2 md:p-3 text-right">
                  <button
                    @click="resendLogs([group])"
                    :disabled="sending"
                    class="text-xs bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition disabled:opacity-50 cursor-pointer border border-white/10"
                  >
                    Reenviar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</template>
