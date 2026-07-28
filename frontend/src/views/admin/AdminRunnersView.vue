<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'

interface Edition {
  id: string; name: string; year: number; isActive: boolean
}

interface RunnerItem {
  id: string; firstName: string; lastName: string; fullName: string
  bibNumber: string | null; club: string | null; email: string | null
  gender: string | null; category: string | null; birthDate: string | null
}

interface ImportResult {
  created: number; updated: number; skipped: number; total: number
}

const router = useRouter()
const editions = ref<Edition[]>([])
const selectedEditionId = ref<string>('')
const csvFile = ref<File | null>(null)
const importing = ref(false)
const result = ref<ImportResult | null>(null)
const message = ref<{ type: 'success' | 'error'; text: string } | null>(null)

// Runner list
const runners = ref<RunnerItem[]>([])
const listEditionFilter = ref<string>('')
const loadingRunners = ref(false)
const deleting = ref<string | null>(null)

async function fetchEditions() {
  try {
    const res = await api.get('/editions')
    editions.value = res.data.data
    if (editions.value.length > 0 && !selectedEditionId.value) {
      const active = editions.value.find((e) => e.isActive)
      selectedEditionId.value = active?.id ?? editions.value[0]!.id
    }
  } catch { /* */ }
}

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  csvFile.value = target.files?.[0] ?? null
  result.value = null
  message.value = null
}

async function importRunners() {
  if (!csvFile.value || !selectedEditionId.value) return
  importing.value = true; message.value = null; result.value = null
  const formData = new FormData(); formData.append('file', csvFile.value)
  try {
    const res = await api.post(`/admin/editions/${selectedEditionId.value}/runners/import`, formData)
    result.value = res.data.data
    message.value = { type: 'success', text: `Importación completada: ${result.value!.created} creados, ${result.value!.skipped} duplicados (${result.value!.total} filas procesadas)` }
    csvFile.value = null
    const fi = document.getElementById('csvFileInput') as HTMLInputElement
    if (fi) fi.value = ''
    await fetchRunners()
  } catch (err: unknown) {
    message.value = { type: 'error', text: err instanceof Error ? err.message : 'Error al importar' }
  } finally { importing.value = false }
}

async function fetchRunners() {
  if (!listEditionFilter.value) { runners.value = []; return }
  loadingRunners.value = true
  try {
    const res = await api.get(`/admin/editions/${listEditionFilter.value}/runners`)
    runners.value = res.data.data
  } catch { /* */ }
  finally { loadingRunners.value = false }
}

async function deleteRunner(id: string) {
  if (!confirm('Eliminar este runner?')) return
  deleting.value = id
  try {
    await api.delete(`/admin/runners/${id}`)
    runners.value = runners.value.filter((r) => r.id !== id)
    message.value = { type: 'success', text: 'Runner eliminado.' }
  } catch (err: unknown) {
    message.value = { type: 'error', text: err instanceof Error ? err.message : 'Error al eliminar' }
  } finally { deleting.value = null }
}

async function deleteAllRunners() {
  if (!listEditionFilter.value) return
  if (!confirm(`Eliminar TODOS los runners de esta edicion? Esta accion no se puede deshacer.`)) return
  deleting.value = '__all__'
  try {
    const res = await api.delete(`/admin/editions/${listEditionFilter.value}/runners`)
    message.value = { type: 'success', text: `${res.data.data.deleted} runner(s) eliminados.` }
    runners.value = []
  } catch (err: unknown) {
    message.value = { type: 'error', text: err instanceof Error ? err.message : 'Error al eliminar' }
  } finally { deleting.value = null }
}

onMounted(fetchEditions)
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A] text-white">
    <header class="bg-[#141414] p-4 flex items-center gap-4 border-b border-white/5">
      <button @click="router.back()" class="text-sm bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">← Volver</button>
      <h1 class="text-xl font-bold uppercase tracking-wider">Carga de Runners</h1>
    </header>

    <main class="max-w-4xl mx-auto p-4 md:p-6 space-y-6">
      <!-- Import section -->
      <div class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-4">
        <h2 class="text-lg font-semibold text-naranja">Importar runners desde CSV</h2>
        <div class="flex flex-col sm:flex-row gap-4 items-end">
          <div class="flex-1">
            <label class="block text-xs text-gray-400 mb-1">Edicion</label>
            <select v-model="selectedEditionId" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition">
              <option value="" disabled>-- Selecciona una edición --</option>
              <option v-for="e in editions" :key="e.id" :value="e.id">{{ e.name }} ({{ e.year }}) {{ e.isActive ? '— Activa' : '' }}</option>
            </select>
          </div>
          <div class="flex-1">
            <input id="csvFileInput" type="file" accept=".csv" @change="handleFileChange" class="w-full text-sm text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-medium file:bg-[#FF5C00] file:text-white hover:file:bg-[#FFD600] hover:file:text-[#0A0A0A] file:transition file:cursor-pointer" />
          </div>
          <button @click="importRunners" :disabled="!csvFile || !selectedEditionId || importing" class="bg-[#FF5C00] text-white px-5 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 cursor-pointer whitespace-nowrap">
            {{ importing ? 'Importando...' : 'Importar' }}
          </button>
        </div>
      </div>

      <!-- Message -->
      <div v-if="message" :class="['rounded-lg border p-4 text-sm font-medium', message.type === 'success' ? 'bg-green-900/20 border-green-500/30 text-green-400' : 'bg-red-900/20 border-red-500/30 text-red-400']">{{ message.text }}</div>

      <!-- Result cards -->
      <div v-if="result" class="grid grid-cols-3 gap-4 max-w-md">
        <div class="bg-[#141414] rounded-lg border border-white/5 p-4 text-center"><span class="text-2xl font-bold text-green-400">{{ result.created }}</span><p class="text-xs text-gray-400 mt-1">Creados</p></div>
        <div class="bg-[#141414] rounded-lg border border-white/5 p-4 text-center"><span class="text-2xl font-bold text-yellow-400">{{ result.skipped }}</span><p class="text-xs text-gray-400 mt-1">Duplicados</p></div>
        <div class="bg-[#141414] rounded-lg border border-white/5 p-4 text-center"><span class="text-2xl font-bold text-gray-300">{{ result.total }}</span><p class="text-xs text-gray-400 mt-1">Filas CSV</p></div>
      </div>

      <!-- Runner list -->
      <div class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <h2 class="text-lg font-semibold text-naranja">Runners cargados</h2>
          <div class="flex gap-2">
            <select v-model="listEditionFilter" @change="fetchRunners" class="bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-sm text-white focus:border-[#FF5C00] focus:outline-none transition min-w-[200px]">
              <option value="">-- Selecciona una edición --</option>
              <option v-for="e in editions" :key="e.id" :value="e.id">{{ e.name }} ({{ e.year }}){{ e.isActive ? ' — Activa' : '' }}</option>
            </select>
            <button @click="fetchRunners" :disabled="!listEditionFilter || loadingRunners" class="text-xs bg-[#222] text-white px-3 py-2 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">Recargar</button>
            <button v-if="runners.length > 0" @click="deleteAllRunners" :disabled="deleting === '__all__'" class="text-xs bg-red-900/20 text-red-400 px-3 py-2 rounded hover:bg-red-900/40 transition cursor-pointer border border-red-500/20">Eliminar todos</button>
          </div>
        </div>

        <div v-if="loadingRunners" class="text-center text-gray-400 py-4">Cargando...</div>
        <div v-else-if="!listEditionFilter" class="text-center text-gray-500 py-4">Selecciona una edición para ver los runners.</div>
        <div v-else-if="runners.length === 0" class="text-center text-gray-500 py-4">No hay runners cargados en esta edición.</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-[#1a1a1a] text-gray-400">
              <tr>
                <th class="p-2 md:p-3 font-medium">Dorsal</th>
                <th class="p-2 md:p-3 font-medium">Nombre</th>
                <th class="p-2 md:p-3 font-medium">Club</th>
                <th class="p-2 md:p-3 font-medium w-16"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in runners" :key="r.id" class="border-t border-white/5 hover:bg-[#1a1a1a]">
                <td class="p-2 md:p-3 font-mono">{{ r.bibNumber ?? '—' }}</td>
                <td class="p-2 md:p-3">{{ r.fullName }}</td>
                <td class="p-2 md:p-3 text-gray-400">{{ r.club ?? '—' }}</td>
                <td class="p-2 md:p-3 text-right">
                  <button @click="deleteRunner(r.id)" :disabled="deleting === r.id" class="text-xs bg-red-900/20 text-red-400 px-2 py-1 rounded hover:bg-red-900/40 transition cursor-pointer border border-red-500/20">{{ deleting === r.id ? '...' : 'Eliminar' }}</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</template>
