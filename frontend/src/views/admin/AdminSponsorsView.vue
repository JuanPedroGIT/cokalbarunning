<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'

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
const form = ref<Partial<Sponsor>>({ name: '', tier: 'bronze', isActive: true, sortOrder: 0, website: '', message: '' })
const router = useRouter()
const editingId = ref<string | null>(null)
const formLogoFile = ref<File | null>(null)
const logoFiles = ref<Record<string, File | null>>({})

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
  if (formLogoFile.value && editingId.value) {
    await uploadLogoDirect(editingId.value, formLogoFile.value)
    formLogoFile.value = null
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
  await fetchSponsors()
}

function onLogoFileChange(sponsorId: string, e: Event) {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0] || null
  if (file) {
    logoFiles.value[sponsorId] = file
    uploadLogo(sponsorId)
  }
}

async function uploadLogo(sponsorId: string) {
  const file = logoFiles.value[sponsorId]
  if (!file) return
  await uploadLogoDirect(sponsorId, file)
  logoFiles.value[sponsorId] = null
  await fetchSponsors()
}

async function uploadLogoDirect(sponsorId: string, file: File) {
  const formData = new FormData()
  formData.append('file', file)
  await api.post(`/admin/sponsors/${sponsorId}/logo`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

function resetForm() {
  editingId.value = null
  formLogoFile.value = null
  form.value = { name: '', tier: 'bronze', isActive: true, sortOrder: 0, website: '', message: '' }
}

onMounted(() => { fetchSponsors() })
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A] text-white">
    <header class="bg-[#141414] p-4 flex items-center gap-4 border-b border-white/5">
      <button @click="router.back()" class="text-sm bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">← Volver</button>
      <h1 class="text-xl font-bold uppercase tracking-wider">Patrocinadores</h1>
    </header>

    <main class="max-w-5xl mx-auto p-6 space-y-6">
      <!-- Formulario -->
      <div class="bg-[#141414] rounded-lg border border-naranja/30 p-6 space-y-4">
        <h2 class="text-lg font-semibold border-b border-white/5 pb-3 text-naranja">
          {{ editingId ? 'Editar Patrocinador' : '+ Nuevo Patrocinador' }}
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Nombre *</label>
            <input v-model="form.name" placeholder="Nombre empresa" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Web</label>
            <input v-model="form.website" placeholder="https://..." class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Nivel</label>
            <select v-model="form.tier" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition">
              <option value="principal">⭐ Principal</option>
              <option value="gold">Oro</option>
              <option value="silver">Plata</option>
              <option value="bronze">Bronce</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Orden</label>
            <input v-model.number="form.sortOrder" type="number" placeholder="0" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Logo</label>
            <input type="file" accept="image/*" @change="ev => formLogoFile = (ev.target as HTMLInputElement).files?.[0] || null" class="text-sm text-gray-400" />
          </div>
          <div class="flex items-end">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.isActive" type="checkbox" class="w-4 h-4 accent-[#FF5C00]" />
              <span class="text-sm">Activo</span>
            </label>
          </div>
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1">Mensaje <span class="text-white/30">(solo visible en sponsor principal, acepta HTML)</span></label>
          <textarea v-model="form.message" placeholder="&lt;strong&gt;Erbe&lt;/strong&gt; es el patrocinador principal..." rows="4" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white text-sm focus:border-[#FF5C00] focus:outline-none transition font-mono"></textarea>
        </div>
        <div class="flex gap-2 pt-2">
          <button @click="save" :disabled="!form.name" class="bg-[#FF5C00] text-white px-6 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
            {{ editingId ? 'Guardar cambios' : 'Crear patrocinador' }}
          </button>
          <button v-if="editingId" @click="resetForm" class="bg-[#222] px-4 py-2 rounded hover:bg-[#333] transition cursor-pointer">Cancelar</button>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-[#141414] rounded-lg border border-white/5 overflow-hidden">
        <table class="w-full text-left text-sm">
          <thead class="bg-[#1a1a1a] text-gray-400">
            <tr>
              <th class="p-3 font-medium">Nombre</th>
              <th class="p-3 font-medium">Logo</th>
              <th class="p-3 font-medium">Nivel</th>
              <th class="p-3 font-medium">Mensaje</th>
              <th class="p-3 font-medium">Activo</th>
              <th class="p-3 text-right font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in sponsors" :key="s.id" :class="['border-t border-white/5 transition', editingId === s.id ? 'bg-naranja/10 border-l-2 border-l-naranja' : 'hover:bg-[#1a1a1a]']">
              <td class="p-3 font-medium">{{ s.name }}</td>
              <td class="p-3">
                <div class="flex items-center gap-2">
                  <img v-if="s.logoUrl" :src="s.logoUrl" class="h-8 w-auto object-contain bg-white/5 rounded" :alt="s.name" />
                  <input type="file" accept="image/*" :id="`logo-${s.id}`" class="hidden" @change="onLogoFileChange(s.id, $event)" />
                  <label :for="`logo-${s.id}`" class="cursor-pointer text-xs bg-[#222] text-gray-300 px-2 py-1 rounded hover:bg-[#333] transition border border-white/10">
                    {{ s.logoUrl ? 'Cambiar' : 'Subir logo' }}
                  </label>
                </div>
              </td>
              <td class="p-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize"
                  :class="{
                    'bg-amber-500/20 text-amber-400': s.tier === 'principal',
                    'bg-yellow-500/20 text-yellow-400': s.tier === 'gold',
                    'bg-blue-500/20 text-blue-400': s.tier === 'silver',
                    'bg-gray-500/20 text-gray-400': s.tier === 'bronze',
                  }"
                >{{ s.tier }}</span>
              </td>
              <td class="p-3 text-gray-500 text-xs max-w-[200px] truncate">{{ s.message ? '✔️' : '—' }}</td>
              <td class="p-3">
                <span v-if="s.isActive" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-500/10 text-green-400">Si</span>
                <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-500/10 text-gray-400">No</span>
              </td>
              <td class="p-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="edit(s)" class="text-[#FF5C00] hover:text-[#FFD600] text-sm font-medium transition cursor-pointer">Editar</button>
                  <span class="text-white/10">|</span>
                  <button @click="remove(s.id)" class="text-red-400 hover:text-red-300 text-sm font-medium transition cursor-pointer">Eliminar</button>
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
