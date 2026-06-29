<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'
import ImageDropZone from '@/components/ui/ImageDropZone.vue'

interface Photo {
  id: string
  originalUrl: string
  thumbUrl: string | null
  altText: string | null
  isFeatured: boolean
  sortOrder: number
  raceEditionId: string | null
}

interface Edition {
  id: string
  year: number
  name: string
}

const photos = ref<Photo[]>([])
const editions = ref<Edition[]>([])
const file = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const altText = ref('')
const isFeatured = ref(false)
const sortOrder = ref(0)
const selectedEditionId = ref<string>('')
const router = useRouter()

async function fetchPhotos() {
  if (!selectedEditionId.value) {
    photos.value = []
    return
  }
  const res = await api.get('/admin/photos', { params: { editionId: selectedEditionId.value } })
  photos.value = res.data.data
}

async function fetchEditions() {
  const res = await api.get('/editions')
  editions.value = res.data.data
}

async function upload() {
  if (!file.value) return
  if (!selectedEditionId.value) {
    alert('Selecciona una edicion primero')
    return
  }
  const formData = new FormData()
  formData.append('file', file.value)
  formData.append('altText', altText.value)
  formData.append('isFeatured', String(isFeatured.value))
  formData.append('sortOrder', String(sortOrder.value))
  formData.append('raceEditionId', selectedEditionId.value)

  await api.post('/admin/photos', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })

  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }
  file.value = null
  previewUrl.value = null
  altText.value = ''
  isFeatured.value = false
  sortOrder.value = 0
  await fetchPhotos()
}

async function remove(id: string) {
  if (!confirm('Eliminar foto?')) return
  await api.delete(`/admin/photos/${id}`)
  await fetchPhotos()
}

function onPhotoSelect(selectedFile: File) {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }
  file.value = selectedFile
  previewUrl.value = URL.createObjectURL(selectedFile)
}

watch(selectedEditionId, () => {
  fetchPhotos()
})

onMounted(() => { fetchEditions() })
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A] text-white">
    <header class="bg-[#141414] p-4 flex items-center gap-4 border-b border-white/5">
      <button @click="router.back()" class="text-sm bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">← Volver</button>
      <h1 class="text-xl font-bold uppercase tracking-wider">Galeria</h1>
    </header>

    <main class="max-w-6xl mx-auto p-6 space-y-6">
      <!-- Selector de edicion (unico) -->
      <div class="bg-[#141414] rounded-lg border border-white/5 p-4 flex items-center gap-4 flex-wrap">
        <label class="text-sm text-gray-400 whitespace-nowrap">Seleccionar edicion:</label>
        <select v-model="selectedEditionId" class="bg-[#0A0A0A] border border-white/10 rounded px-4 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition min-w-[250px]">
          <option value="">-- Selecciona una edicion --</option>
          <option v-for="e in editions" :key="e.id" :value="e.id">{{ e.year }} - {{ e.name }}</option>
        </select>
        <span v-if="selectedEditionId" class="text-xs text-gray-500 ml-auto">{{ photos.length }} fotos</span>
        <span v-else class="text-xs text-gray-500 ml-auto">Selecciona una edicion para ver sus fotos</span>
      </div>

      <!-- Upload form (solo visible si hay edicion seleccionada) -->
      <div v-if="selectedEditionId" class="bg-[#141414] rounded-lg border border-white/5 p-4 sm:p-6">
        <h2 class="text-lg font-semibold border-b border-white/5 pb-3 mb-4">Subir Foto</h2>
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
          <!-- Drop zone — cuadrado a la izquierda -->
          <div class="shrink-0 w-full sm:w-[240px]">
            <ImageDropZone
              :label="'Arrastra o haz clic'"
              :selected-label="file ? file.name : undefined"
              :image-url="previewUrl || undefined"
              :square="true"
              @select="onPhotoSelect"
            />
          </div>
          <!-- Campos a la derecha -->
          <div class="flex-1 flex flex-col gap-4 min-w-0">
            <div>
              <label class="block text-xs text-gray-400 mb-1">Texto alternativo (alt)</label>
              <input v-model="altText" placeholder="Descripcion de la imagen..." class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-400 mb-1">Orden</label>
                <input v-model.number="sortOrder" type="number" placeholder="0" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
              </div>
              <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input v-model="isFeatured" type="checkbox" class="w-4 h-4 accent-[#FF5C00]" />
                  <span class="text-sm">Destacada</span>
                </label>
              </div>
            </div>
            <button @click="upload" :disabled="!file" class="bg-[#FF5C00] text-white px-5 py-2.5 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer self-start">Subir foto</button>
          </div>
        </div>
      </div>

      <div v-else class="bg-[#141414] rounded-lg border border-white/5 p-10 text-center text-gray-500">
        Selecciona una edicion para ver sus fotos y subir nuevas
      </div>

      <!-- Grid -->
      <div v-if="photos.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <div v-for="p in photos" :key="p.id" class="bg-[#141414] rounded-lg border border-white/5 overflow-hidden group">
          <div class="aspect-video bg-[#0A0A0A]">
            <img :src="p.thumbUrl || p.originalUrl" class="w-full h-full object-cover" :alt="p.altText || 'Foto'" />
          </div>
          <div class="p-3">
            <p class="text-sm truncate">{{ p.altText || 'Sin alt text' }}</p>
            <div class="flex items-center justify-between mt-2">
              <p class="text-xs text-gray-500">{{ p.isFeatured ? 'Destacada' : '' }}</p>
              <button @click="remove(p.id)" class="text-red-400 hover:text-red-300 text-xs font-medium transition flex items-center gap-1 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                Borrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
