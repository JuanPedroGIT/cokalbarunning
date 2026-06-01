<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'

interface Edition {
  id: string
  year: number
  name: string
  description: string
  date: string
  location: string
  isActive: boolean
  posterUrl: string | null
  registrationUrl: string | null
  shirtUrl: string | null
  resultsUrl: string | null
  inscriptionInfo: string | null
  solidarityCause: string | null
  solidarityUrl: string | null
}

const editions = ref<Edition[]>([])
const form = ref<Partial<Edition>>({
  year: new Date().getFullYear(),
  name: '',
  description: '',
  date: '',
  location: '',
  isActive: true,
  posterUrl: null,
  registrationUrl: null,
  shirtUrl: null,
  inscriptionInfo: '', solidarityCause: '', solidarityUrl: '',
})
const router = useRouter()
const editingId = ref<string | null>(null)
const posterFile = ref<File | null>(null)
const shirtFile = ref<File | null>(null)
const resultFile = ref<File | null>(null)
const uploadingPosterId = ref<string | null>(null)
const uploadingShirtId = ref<string | null>(null)
const uploadingResultId = ref<string | null>(null)

async function fetchEditions() {
  const res = await api.get('/editions')
  editions.value = res.data.data
}

async function save() {
  const payload = { ...form.value }
  if (editingId.value) {
    await api.put(`/admin/editions/${editingId.value}`, payload)
  } else {
    await api.post('/admin/editions', payload)
  }
  resetForm()
  await fetchEditions()
}

async function uploadPoster(id: string) {
  if (!posterFile.value) return
  uploadingPosterId.value = id
  const fd = new FormData(); fd.append('file', posterFile.value)
  try {
    await api.post(`/admin/editions/${id}/poster`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    posterFile.value = null
    await fetchEditions()
  } finally { uploadingPosterId.value = null }
}

async function uploadShirt(id: string) {
  if (!shirtFile.value) return
  uploadingShirtId.value = id
  const fd = new FormData(); fd.append('file', shirtFile.value)
  try {
    await api.post(`/admin/editions/${id}/shirt`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    shirtFile.value = null
    await fetchEditions()
  } finally { uploadingShirtId.value = null }
}

async function uploadResult(id: string, year: number) {
  if (!resultFile.value) return
  uploadingResultId.value = id
  const fd = new FormData()
  fd.append('file', resultFile.value)
  fd.append('name', `Clasificación ${year}`)
  fd.append('type', 'results')
  fd.append('editionId', id)

  // Delete existing
  try {
    await api.post('/admin/documents', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    resultFile.value = null
    await fetchEditions()
  } finally { uploadingResultId.value = null }
}

function edit(e: Edition) {
  editingId.value = e.id
  form.value = { ...e }
}

async function remove(id: string) {
  if (!confirm('Eliminar edicion?')) return
  await api.delete(`/admin/editions/${id}`)
  await fetchEditions()
}

function resetForm() {
  editingId.value = null
  form.value = {
    year: new Date().getFullYear(), name: '', description: '', date: '',
    location: '', isActive: true, posterUrl: null, registrationUrl: null, shirtUrl: null, inscriptionInfo: '', solidarityCause: '', solidarityUrl: '',
  }
}

onMounted(() => { fetchEditions() })
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A] text-white">
    <header class="bg-[#141414] p-4 flex items-center gap-4 border-b border-white/5">
      <button @click="router.back()" class="text-sm bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">← Volver</button>
      <h1 class="text-xl font-bold uppercase tracking-wider">Ediciones</h1>
    </header>

    <main class="max-w-5xl mx-auto p-6 space-y-6">
      <!-- Formulario -->
      <div class="bg-[#141414] rounded-lg border border-white/5 p-6 space-y-4">
        <h2 class="text-lg font-semibold border-b border-white/5 pb-3">{{ editingId ? 'Editar' : 'Nueva' }} Edicion</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Ano</label>
            <input v-model.number="form.year" type="number" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Nombre</label>
            <input v-model="form.name" placeholder="IX Carrera Solidaria..." class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Fecha</label>
            <input v-model="form.date" type="date" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Lugar</label>
            <input v-model="form.location" placeholder="Coca de Alba, Salamanca" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">URL inscripcion</label>
            <input v-model="form.registrationUrl" placeholder="https://..." class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div class="flex items-end">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.isActive" type="checkbox" class="w-4 h-4 accent-[#FF5C00]" />
              <span class="text-sm">Edicion activa</span>
            </label>
          </div>
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1">Descripcion</label>
          <textarea v-model="form.description" placeholder="Descripcion de la carrera..." rows="3" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"></textarea>
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1">Info de inscripcion</label>
          <input v-model="form.inscriptionInfo" placeholder="Inscripciones hasta el 2 de julio..." class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1">Causa solidaria</label>
          <input v-model="form.solidarityCause" placeholder="A favor de Asociación Síndrome X-Frágil" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1">URL causa solidaria</label>
          <input v-model="form.solidarityUrl" placeholder="https://www.xfragil.net/" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
        </div>
        <div class="flex gap-2 pt-2">
          <button @click="save" class="bg-[#FF5C00] text-white px-4 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition cursor-pointer">Guardar</button>
          <button v-if="editingId" @click="resetForm" class="bg-[#222] px-4 py-2 rounded hover:bg-[#333] transition cursor-pointer">Cancelar</button>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-[#141414] rounded-lg border border-white/5 overflow-hidden">
        <table class="w-full text-left text-sm">
          <thead class="bg-[#1a1a1a] text-gray-400">
            <tr>
              <th class="p-3 font-medium">Ano</th>
              <th class="p-3 font-medium">Nombre</th>
              <th class="p-3 font-medium">Lugar</th>
              <th class="p-3 font-medium">Activa</th>
              <th class="p-3 font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="e in editions" :key="e.id" :class="['border-t border-white/5 transition', editingId === e.id ? 'bg-naranja/10 border-l-2 border-l-naranja' : 'hover:bg-[#1a1a1a]']">
              <td class="p-3">{{ e.year }}</td>
              <td class="p-3">{{ e.name }}</td>
              <td class="p-3 text-gray-400">{{ e.location }}</td>
              <td class="p-3">
                <span v-if="e.isActive" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-500/10 text-green-400">Activa</span>
                <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-500/10 text-gray-400">Inactiva</span>
              </td>
              <td class="p-3">
                <div class="flex items-center gap-1 flex-wrap">
                  <button @click="edit(e)" class="text-[#FF5C00] hover:text-[#FFD600] text-xs font-medium bg-[#222] px-2 py-1 rounded border border-white/5 cursor-pointer">Editar</button>

                  <input type="file" accept="image/*" class="hidden" :id="`poster-${e.id}`" @change="ev => { posterFile = (ev.target as HTMLInputElement).files?.[0] || null; uploadPoster(e.id) }" />
                  <label :for="`poster-${e.id}`" class="bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 text-xs px-2 py-1 rounded border border-blue-500/20 cursor-pointer transition">
                    {{ uploadingPosterId === e.id ? '⏳' : '🖼️' }} Cartel
                  </label>

                  <input type="file" accept="image/*" class="hidden" :id="`shirt-${e.id}`" @change="ev => { shirtFile = (ev.target as HTMLInputElement).files?.[0] || null; uploadShirt(e.id) }" />
                  <label :for="`shirt-${e.id}`" class="bg-purple-500/20 text-purple-400 hover:bg-purple-500/30 text-xs px-2 py-1 rounded border border-purple-500/20 cursor-pointer transition">
                    {{ uploadingShirtId === e.id ? '⏳' : '👕' }} Camiseta
                  </label>

                  <input type="file" accept=".pdf" class="hidden" :id="`result-${e.id}`" @change="ev => { resultFile = (ev.target as HTMLInputElement).files?.[0] || null; uploadResult(e.id, e.year) }" />
                  <label :for="`result-${e.id}`" class="bg-amber-500/20 text-amber-400 hover:bg-amber-500/30 text-xs px-2 py-1 rounded border border-amber-500/20 cursor-pointer transition">
                    {{ uploadingResultId === e.id ? '⏳' : '🏆' }} Resultados
                  </label>

                  <button @click="remove(e.id)" class="bg-red-500/20 text-red-400 hover:bg-red-500/30 text-xs px-2 py-1 rounded border border-red-500/20 cursor-pointer transition">🗑️</button>
                </div>
                <div class="flex gap-2 mt-1 text-xs">
                  <a v-if="e.posterUrl" :href="e.posterUrl" target="_blank" class="text-blue-400/70 hover:text-blue-300 underline">🖼️ Ver cartel</a>
                  <a v-if="e.shirtUrl" :href="e.shirtUrl" target="_blank" class="text-purple-400/70 hover:text-purple-300 underline">👕 Ver camiseta</a>
                  <a v-if="e.resultsUrl" :href="e.resultsUrl" target="_blank" class="text-amber-400/70 hover:text-amber-300 underline">🏆 Ver resultados</a>
                </div>
              </td>
            </tr>
            <tr v-if="!editions.length">
              <td colspan="5" class="p-6 text-center text-gray-500">No hay ediciones registradas</td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</template>
