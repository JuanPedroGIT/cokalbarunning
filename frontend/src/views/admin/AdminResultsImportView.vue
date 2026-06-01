<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'

interface Edition {
  id: string
  year: number
  name: string
}

const editions = ref<Edition[]>([])
const selectedEdition = ref('')
const file = ref<File | null>(null)
const loading = ref(false)
const result = ref<{ created: number; errors: string[] } | null>(null)
const router = useRouter()
const fileInputKey = ref(0)

async function fetchEditions() {
  const res = await api.get('/editions')
  editions.value = res.data.data
}

async function importCsv() {
  if (!file.value || !selectedEdition.value) return
  loading.value = true
  result.value = null
  const formData = new FormData()
  formData.append('file', file.value)

  try {
    const res = await api.post(`/admin/editions/${selectedEdition.value}/results/import`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    result.value = res.data.data
    file.value = null
    fileInputKey.value++
  } catch (e: any) {
    alert(e.response?.data?.error || 'Error al importar')
  } finally {
    loading.value = false
  }
}

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  file.value = target.files?.[0] || null
}

onMounted(fetchEditions)
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A] text-white">
    <header class="bg-[#141414] p-4 flex items-center gap-4 border-b border-white/5">
      <button @click="router.back()" class="text-sm bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">← Volver</button>
      <h1 class="text-xl font-bold uppercase tracking-wider">Importar Resultados</h1>
    </header>

    <main class="max-w-2xl mx-auto p-6 space-y-6">
      <div class="bg-[#141414] rounded-lg border border-white/5 p-6 space-y-4">
        <h2 class="text-lg font-semibold border-b border-white/5 pb-3">Importar CSV</h2>

        <div>
          <label class="block text-xs text-gray-400 mb-1">Edicion</label>
          <select v-model="selectedEdition" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition">
            <option value="">Selecciona una edicion</option>
            <option v-for="e in editions" :key="e.id" :value="e.id">{{ e.year }} - {{ e.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs text-gray-400 mb-1">Archivo CSV (separador ;)</label>
          <p class="text-xs text-gray-500 mb-2">Columnas esperadas: firstName, lastName, bib, time, category, gender, club, email</p>

          <input
            :key="fileInputKey"
            type="file"
            accept=".csv"
            id="csv-upload"
            class="hidden"
            @change="onFileChange"
          />
          <label
            for="csv-upload"
            class="cursor-pointer inline-flex items-center gap-2 bg-[#0A0A0A] border-2 border-dashed border-white/20 rounded-lg px-6 py-3 hover:border-[#FF5C00]/50 hover:bg-[#1a1a1a] transition"
          >
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3l4.5 4.5m-13.5 9V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0021 6.75v6.75" />
            </svg>
            <span v-if="!file" class="text-gray-400">Seleccionar archivo CSV</span>
            <span v-else class="text-white font-medium">{{ file.name }}</span>
          </label>
        </div>

        <button
          @click="importCsv"
          :disabled="!file || !selectedEdition || loading"
          class="bg-[#FF5C00] text-white px-4 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? 'Importando...' : 'Importar' }}
        </button>
      </div>

      <div v-if="result" class="bg-[#141414] rounded-lg border border-white/5 p-6 space-y-3">
        <h2 class="font-semibold">Resultado</h2>
        <p>Registros creados/actualizados: <strong class="text-[#FF5C00]">{{ result.created }}</strong></p>
        <div v-if="result.errors.length" class="space-y-2">
          <p class="text-red-400 font-medium">Errores ({{ result.errors.length }}):</p>
          <ul class="text-sm text-red-400 list-disc pl-5 max-h-40 overflow-auto space-y-1 bg-red-500/5 rounded p-3">
            <li v-for="(err, i) in result.errors" :key="i">{{ err }}</li>
          </ul>
        </div>
        <div v-else class="text-green-400 text-sm">Sin errores</div>
      </div>
    </main>
  </div>
</template>
