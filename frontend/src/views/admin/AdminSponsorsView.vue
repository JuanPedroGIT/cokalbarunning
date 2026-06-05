<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'
import ImageDropZone from '@/components/ui/ImageDropZone.vue'

interface Sponsor {
  id: string
  name: string
  logoUrl: string | null
  website: string | null
  tier: string
  isActive: boolean
  sortOrder: number
  message: string | null
}

const sponsors = ref<Sponsor[]>([])
const loading = ref(false)
const form = ref<Partial<Sponsor>>({
  name: '',
  tier: 'bronze',
  isActive: true,
  sortOrder: 0,
  website: '',
  message: '',
})
const router = useRouter()
const editingId = ref<string | null>(null)
const uploadingLogo = ref(false)

async function fetchSponsors() {
  loading.value = true
  const res = await api.get('/admin/sponsors')
  sponsors.value = res.data.data
  loading.value = false
}

async function save() {
  if (editingId.value) {
    await api.put(`/admin/sponsors/${editingId.value}`, form.value)
  } else {
    const res = await api.post('/admin/sponsors', form.value)
    editingId.value = res.data.id
  }
  resetForm()
  await fetchSponsors()
}

function edit(s: Sponsor) {
  editingId.value = s.id
  form.value = { ...s }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function remove(id: string) {
  if (!confirm('Eliminar patrocinador?')) return
  await api.delete(`/admin/sponsors/${id}`)
  if (editingId.value === id) resetForm()
  await fetchSponsors()
}

async function uploadLogo(file: File) {
  if (!editingId.value) return
  uploadingLogo.value = true
  const formData = new FormData()
  formData.append('file', file)
  try {
    await api.post(`/admin/sponsors/${editingId.value}/logo`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const res = await api.get('/admin/sponsors')
    const updated = res.data.data.find((s: Sponsor) => s.id === editingId.value)
    if (updated) form.value.logoUrl = updated.logoUrl
  } finally {
    uploadingLogo.value = false
  }
}

async function deleteLogo() {
  if (!editingId.value) return
  if (!confirm('¿Eliminar logo?')) return
  await api.put(`/admin/sponsors/${editingId.value}`, { logoUrl: '' })
  form.value.logoUrl = null
  await fetchSponsors()
}

function resetForm() {
  editingId.value = null
  form.value = {
    name: '',
    tier: 'bronze',
    isActive: true,
    sortOrder: 0,
    website: '',
    message: '',
  }
}

onMounted(() => {
  fetchSponsors()
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
      <h1 class="text-xl font-bold uppercase tracking-wider">Patrocinadores</h1>
    </header>

    <main class="max-w-5xl mx-auto p-4 md:p-6 space-y-6">
      <!-- Formulario -->
      <div class="bg-[#141414] rounded-lg border border-naranja/30 p-4 md:p-6 space-y-4">
        <h2 class="text-lg font-semibold border-b border-white/5 pb-3 text-naranja">
          {{ editingId ? 'Editar Patrocinador' : '+ Nuevo Patrocinador' }}
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Nombre *</label>
            <input
              v-model="form.name"
              placeholder="Nombre empresa"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Web</label>
            <input
              v-model="form.website"
              placeholder="https://..."
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Nivel</label>
            <select
              v-model="form.tier"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            >
              <option value="principal">⭐ Principal</option>
              <option value="gold">Oro</option>
              <option value="silver">Plata</option>
              <option value="bronze">Bronce</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Orden</label>
            <input
              v-model.number="form.sortOrder"
              type="number"
              placeholder="0"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Logo (solo edicion) -->
          <div v-if="editingId" class="space-y-2">
            <label class="block text-xs text-gray-400">Logo</label>
            <div
              v-if="form.logoUrl"
              class="relative rounded-lg overflow-hidden border border-white/5 bg-white w-full"
            >
              <img :src="form.logoUrl" alt="Logo" class="w-full aspect-[3/1] object-contain p-2" />
              <button
                @click="deleteLogo"
                class="absolute top-1.5 right-1.5 bg-red-500/90 text-white text-[10px] px-1.5 py-0.5 rounded cursor-pointer"
              >
                Eliminar
              </button>
            </div>
            <div v-else>
              <ImageDropZone
                :label="uploadingLogo ? 'Subiendo...' : 'Arrastra el logo o haz clic'"
                :compact="true"
                @select="uploadLogo"
              />
            </div>
          </div>

          <!-- Mensaje -->
          <div :class="editingId ? '' : 'sm:col-span-2'">
            <label class="block text-xs text-gray-400 mb-1">
              Mensaje <span class="text-white/30">(solo visible en sponsor principal, acepta HTML)</span>
            </label>
            <textarea
              v-model="form.message"
              placeholder="&lt;strong&gt;Erbe&lt;/strong&gt; es el patrocinador principal..."
              rows="4"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white text-sm focus:border-[#FF5C00] focus:outline-none transition font-mono"
            ></textarea>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.isActive" type="checkbox" class="w-4 h-4 accent-[#FF5C00]" />
            <span class="text-sm">Activo</span>
          </label>
        </div>

        <div class="flex flex-wrap gap-2 pt-2">
          <button
            @click="save"
            :disabled="!form.name"
            class="bg-[#FF5C00] text-white px-6 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            {{ editingId ? 'Guardar cambios' : 'Crear patrocinador' }}
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
              <th class="p-2 md:p-3 font-medium">Nombre</th>
              <th class="p-2 md:p-3 font-medium">Logo</th>
              <th class="p-2 md:p-3 font-medium">Nivel</th>
              <th class="p-2 md:p-3 font-medium hidden md:table-cell">Mensaje</th>
              <th class="p-2 md:p-3 font-medium">Activo</th>
              <th class="p-2 md:p-3 text-right font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="s in sponsors"
              :key="s.id"
              :class="[
                'border-t border-white/5 transition',
                editingId === s.id
                  ? 'bg-naranja/10 border-l-2 border-l-naranja'
                  : 'hover:bg-[#1a1a1a]',
              ]"
            >
              <td class="p-2 md:p-3 font-medium">{{ s.name }}</td>
              <td class="p-2 md:p-3">
                <div class="w-10 h-10 bg-white/5 rounded flex items-center justify-center overflow-hidden">
                  <img
                    v-if="s.logoUrl"
                    :src="s.logoUrl"
                    class="w-full h-full object-contain p-1"
                    :alt="s.name"
                  />
                  <span v-else class="text-xs text-gray-600">—</span>
                </div>
              </td>
              <td class="p-2 md:p-3">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize"
                  :class="{
                    'bg-amber-500/20 text-amber-400': s.tier === 'principal',
                    'bg-yellow-500/20 text-yellow-400': s.tier === 'gold',
                    'bg-blue-500/20 text-blue-400': s.tier === 'silver',
                    'bg-gray-500/20 text-gray-400': s.tier === 'bronze',
                  }"
                >
                  {{ s.tier }}
                </span>
              </td>
              <td class="p-2 md:p-3 text-gray-500 text-xs max-w-[200px] truncate hidden md:table-cell">
                {{ s.message ? '✔️' : '—' }}
              </td>
              <td class="p-2 md:p-3">
                <span
                  v-if="s.isActive"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-500/10 text-green-400"
                  >Si</span
                >
                <span
                  v-else
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-500/10 text-gray-400"
                  >No</span
                >
              </td>
              <td class="p-2 md:p-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="edit(s)"
                    class="text-[#FF5C00] hover:text-[#FFD600] text-xs font-medium bg-[#222] px-3 py-1.5 rounded border border-white/5 cursor-pointer transition"
                  >
                    Editar
                  </button>
                  <button
                    @click="remove(s.id)"
                    class="bg-red-500/20 text-red-400 hover:bg-red-500/30 text-xs px-3 py-1.5 rounded border border-red-500/20 cursor-pointer transition"
                  >
                    Eliminar
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!sponsors.length">
              <td colspan="6" class="p-6 text-center text-gray-500">No hay patrocinadores</td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</template>
