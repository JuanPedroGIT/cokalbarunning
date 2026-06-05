<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'
import ImageDropZone from '@/components/ui/ImageDropZone.vue'

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
  trophyUrl: string | null
  resultsUrl: string | null
  resultsDocumentId: string | null
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
  trophyUrl: null,
  inscriptionInfo: '',
  solidarityCause: '',
  solidarityUrl: '',
})

const router = useRouter()
const editingId = ref<string | null>(null)
const uploadingPoster = ref(false)
const uploadingShirt = ref(false)
const uploadingTrophy = ref(false)
const uploadingResult = ref(false)

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

async function uploadPoster(file: File) {
  if (!editingId.value) return
  uploadingPoster.value = true
  const fd = new FormData()
  fd.append('file', file)
  try {
    await api.post(`/admin/editions/${editingId.value}/poster`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const res = await api.get(`/editions`)
    const updated = res.data.data.find((e: Edition) => e.id === editingId.value)
    if (updated) form.value.posterUrl = updated.posterUrl
  } finally {
    uploadingPoster.value = false
  }
}

async function deletePoster() {
  if (!editingId.value) return
  if (!confirm('¿Eliminar cartel?')) return
  await api.delete(`/admin/editions/${editingId.value}/poster`)
  form.value.posterUrl = null
  await fetchEditions()
}

async function uploadShirt(file: File) {
  if (!editingId.value) return
  uploadingShirt.value = true
  const fd = new FormData()
  fd.append('file', file)
  try {
    await api.post(`/admin/editions/${editingId.value}/shirt`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const res = await api.get(`/editions`)
    const updated = res.data.data.find((e: Edition) => e.id === editingId.value)
    if (updated) form.value.shirtUrl = updated.shirtUrl
  } finally {
    uploadingShirt.value = false
  }
}

async function deleteShirt() {
  if (!editingId.value) return
  if (!confirm('¿Eliminar camiseta?')) return
  await api.delete(`/admin/editions/${editingId.value}/shirt`)
  form.value.shirtUrl = null
  await fetchEditions()
}

async function uploadTrophy(file: File) {
  if (!editingId.value) return
  uploadingTrophy.value = true
  const fd = new FormData()
  fd.append('file', file)
  try {
    await api.post(`/admin/editions/${editingId.value}/trophy`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const res = await api.get(`/editions`)
    const updated = res.data.data.find((e: Edition) => e.id === editingId.value)
    if (updated) form.value.trophyUrl = updated.trophyUrl
  } finally {
    uploadingTrophy.value = false
  }
}

async function deleteTrophy() {
  if (!editingId.value) return
  if (!confirm('¿Eliminar trofeo?')) return
  await api.delete(`/admin/editions/${editingId.value}/trophy`)
  form.value.trophyUrl = null
  await fetchEditions()
}

async function uploadResult(file: File) {
  if (!editingId.value) return
  uploadingResult.value = true
  const year = form.value.year ?? new Date().getFullYear()
  const fd = new FormData()
  fd.append('file', file)
  fd.append('name', `Clasificación ${year}`)
  fd.append('type', 'results')
  fd.append('editionId', editingId.value)
  try {
    await api.post('/admin/documents', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const res = await api.get(`/editions`)
    const updated = res.data.data.find((e: Edition) => e.id === editingId.value)
    if (updated) {
      form.value.resultsUrl = updated.resultsUrl
      form.value.resultsDocumentId = updated.resultsDocumentId
    }
  } finally {
    uploadingResult.value = false
  }
}

async function deleteResults() {
  if (!editingId.value || !form.value.resultsDocumentId) return
  if (!confirm('¿Eliminar resultados?')) return
  await api.delete(`/admin/documents/${form.value.resultsDocumentId}`)
  form.value.resultsUrl = null
  form.value.resultsDocumentId = null
  await fetchEditions()
}

function edit(e: Edition) {
  editingId.value = e.id
  form.value = { ...e }
}

async function remove(id: string) {
  if (!confirm('Eliminar edicion?')) return
  await api.delete(`/admin/editions/${id}`)
  if (editingId.value === id) resetForm()
  await fetchEditions()
}

function resetForm() {
  editingId.value = null
  form.value = {
    year: new Date().getFullYear(),
    name: '',
    description: '',
    date: '',
    location: '',
    isActive: true,
    posterUrl: null,
    registrationUrl: null,
    shirtUrl: null,
    trophyUrl: null,
    inscriptionInfo: '',
    solidarityCause: '',
    solidarityUrl: '',
  }
}

onMounted(() => {
  fetchEditions()
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
      <h1 class="text-xl font-bold uppercase tracking-wider">Ediciones</h1>
    </header>

    <main class="max-w-5xl mx-auto p-4 md:p-6 space-y-6">
      <!-- Formulario -->
      <div class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-4">
        <h2 class="text-lg font-semibold border-b border-white/5 pb-3">
          {{ editingId ? 'Editar' : 'Nueva' }} Edicion
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Año</label>
            <input
              v-model.number="form.year"
              type="number"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Nombre</label>
            <input
              v-model="form.name"
              placeholder="IX Carrera Solidaria..."
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Fecha</label>
            <input
              v-model="form.date"
              type="date"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Lugar</label>
            <input
              v-model="form.location"
              placeholder="Coca de Alba, Salamanca"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">URL inscripcion</label>
            <input
              v-model="form.registrationUrl"
              placeholder="https://..."
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            />
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
          <textarea
            v-model="form.description"
            placeholder="Descripcion de la carrera..."
            rows="3"
            class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
          ></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Info de inscripcion</label>
            <input
              v-model="form.inscriptionInfo"
              placeholder="Inscripciones hasta el 2 de julio..."
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Causa solidaria</label>
            <input
              v-model="form.solidarityCause"
              placeholder="A favor de Asociación Síndrome X-Frágil"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs text-gray-400 mb-1">URL causa solidaria</label>
          <input
            v-model="form.solidarityUrl"
            placeholder="https://www.xfragil.net/"
            class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
          />
        </div>

        <!-- Archivos (solo edicion) -->
        <template v-if="editingId">
          <div class="border-t border-white/5 pt-4 space-y-4">
            <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider">
              Archivos de la edicion
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <!-- Cartel -->
              <div class="space-y-2">
                <label class="block text-xs text-gray-400">Cartel</label>
                <div v-if="form.posterUrl" class="relative rounded-lg overflow-hidden border border-white/5 bg-[#0A0A0A]">
                  <img :src="form.posterUrl" alt="Cartel" class="w-full aspect-square object-cover" />
                  <button
                    @click="deletePoster"
                    class="absolute top-1.5 right-1.5 bg-red-500/90 text-white text-[10px] px-1.5 py-0.5 rounded cursor-pointer"
                  >
                    Eliminar
                  </button>
                </div>
                <div v-else>
                  <ImageDropZone
                    :label="uploadingPoster ? 'Subiendo...' : 'Arrastra el cartel o haz clic'"
                    :compact="true"
                    @select="uploadPoster"
                  />
                </div>
              </div>

              <!-- Camiseta -->
              <div class="space-y-2">
                <label class="block text-xs text-gray-400">Camiseta</label>
                <div v-if="form.shirtUrl" class="relative rounded-lg overflow-hidden border border-white/5 bg-[#0A0A0A]">
                  <img :src="form.shirtUrl" alt="Camiseta" class="w-full aspect-square object-cover" />
                  <button
                    @click="deleteShirt"
                    class="absolute top-1.5 right-1.5 bg-red-500/90 text-white text-[10px] px-1.5 py-0.5 rounded cursor-pointer"
                  >
                    Eliminar
                  </button>
                </div>
                <div v-else>
                  <ImageDropZone
                    :label="uploadingShirt ? 'Subiendo...' : 'Arrastra la camiseta o haz clic'"
                    :compact="true"
                    @select="uploadShirt"
                  />
                </div>
              </div>

              <!-- Trofeo -->
              <div class="space-y-2">
                <label class="block text-xs text-gray-400">Trofeo</label>
                <div v-if="form.trophyUrl" class="relative rounded-lg overflow-hidden border border-white/5 bg-[#0A0A0A]">
                  <img :src="form.trophyUrl" alt="Trofeo" class="w-full aspect-square object-cover" />
                  <button
                    @click="deleteTrophy"
                    class="absolute top-1.5 right-1.5 bg-red-500/90 text-white text-[10px] px-1.5 py-0.5 rounded cursor-pointer"
                  >
                    Eliminar
                  </button>
                </div>
                <div v-else>
                  <ImageDropZone
                    :label="uploadingTrophy ? 'Subiendo...' : 'Arrastra el trofeo o haz clic'"
                    :compact="true"
                    @select="uploadTrophy"
                  />
                </div>
              </div>
            </div>

            <!-- Resultados PDF -->
            <div class="space-y-2 pt-2">
              <label class="block text-xs text-gray-400">Clasificaciones (PDF)</label>
              <div v-if="form.resultsUrl" class="flex items-center gap-3 bg-[#0A0A0A] border border-white/10 rounded-lg px-3 py-2">
                <a
                  :href="form.resultsUrl"
                  target="_blank"
                  class="text-sm text-amber-400 hover:text-amber-300 underline truncate flex-1"
                >
                  📄 Ver clasificaciones
                </a>
                <button
                  @click="deleteResults"
                  class="text-red-400 hover:text-red-300 text-xs font-medium cursor-pointer"
                >
                  Eliminar
                </button>
              </div>
              <div v-else>
                <ImageDropZone
                  :label="uploadingResult ? 'Subiendo...' : 'Arrastra el PDF de clasificaciones o haz clic'"
                  :compact="true"
                  accept=".pdf"
                  @select="uploadResult"
                />
              </div>
            </div>
          </div>


        </template>

        <div class="flex flex-wrap gap-2 pt-2">
          <button
            @click="save"
            class="bg-[#FF5C00] text-white px-4 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition cursor-pointer"
          >
            Guardar
          </button>
          <button
            v-if="editingId"
            @click="resetForm"
            class="bg-[#222] px-4 py-2 rounded hover:bg-[#333] transition cursor-pointer"
          >
            Cancelar
          </button>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-[#141414] rounded-lg border border-white/5 overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[640px]">
          <thead class="bg-[#1a1a1a] text-gray-400">
            <tr>
              <th class="p-2 md:p-3 font-medium">Año</th>
              <th class="p-2 md:p-3 font-medium">Nombre</th>
              <th class="p-2 md:p-3 font-medium">Lugar</th>
              <th class="p-2 md:p-3 font-medium">Activa</th>
              <th class="p-2 md:p-3 font-medium text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="e in editions"
              :key="e.id"
              :class="[
                'border-t border-white/5 transition',
                editingId === e.id
                  ? 'bg-naranja/10 border-l-2 border-l-naranja'
                  : 'hover:bg-[#1a1a1a]',
              ]"
            >
              <td class="p-2 md:p-3">{{ e.year }}</td>
              <td class="p-2 md:p-3">{{ e.name }}</td>
              <td class="p-2 md:p-3 text-gray-400">{{ e.location }}</td>
              <td class="p-2 md:p-3">
                <span
                  v-if="e.isActive"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-500/10 text-green-400"
                >
                  Activa
                </span>
                <span
                  v-else
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-500/10 text-gray-400"
                >
                  Inactiva
                </span>
              </td>
              <td class="p-2 md:p-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="edit(e)"
                    class="text-[#FF5C00] hover:text-[#FFD600] text-xs font-medium bg-[#222] px-3 py-1.5 rounded border border-white/5 cursor-pointer transition"
                  >
                    Editar
                  </button>
                  <button
                    @click="remove(e.id)"
                    class="bg-red-500/20 text-red-400 hover:bg-red-500/30 text-xs px-3 py-1.5 rounded border border-red-500/20 cursor-pointer transition"
                  >
                    Eliminar
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!editions.length">
              <td colspan="5" class="p-6 text-center text-gray-500">
                No hay ediciones registradas
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</template>
