<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'
import ImageDropZone from '@/components/ui/ImageDropZone.vue'

interface ClubMember {
  id: string
  name: string
  description: string | null
  bio: string | null
  photoUrl: string | null
  isActive: boolean
  sortOrder: number
}

const members = ref<ClubMember[]>([])
const form = ref<Partial<ClubMember>>({ name: '', description: '', bio: '', isActive: true, sortOrder: 0 })
const router = useRouter()
const editingId = ref<string | null>(null)
const photoFiles = ref<Record<string, File | null>>({})

async function fetch() {
  const res = await api.get('/admin/club-members')
  members.value = res.data.data
}

async function save() {
  if (editingId.value) {
    await api.put(`/admin/club-members/${editingId.value}`, form.value)
  } else {
    const res = await api.post('/admin/club-members', form.value)
    if (photoFiles.value['new'] && res.data.data.id) {
      await uploadPhotoDirect(res.data.data.id, photoFiles.value['new'])
      photoFiles.value['new'] = null
    }
  }
  resetForm()
  await fetch()
}

function edit(m: ClubMember) {
  editingId.value = m.id
  form.value = { ...m }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function remove(id: string) {
  if (!confirm('Eliminar miembro?')) return
  await api.delete(`/admin/club-members/${id}`)
  await fetch()
}

function onPhotoSelect(memberId: string, file: File) {
  if (memberId !== 'new') {
    photoFiles.value[memberId] = file
    uploadPhoto(memberId)
  } else {
    photoFiles.value['new'] = file
  }
}

async function uploadPhoto(memberId: string) {
  const file = photoFiles.value[memberId]
  if (!file) return
  await uploadPhotoDirect(memberId, file)
  photoFiles.value[memberId] = null
  await fetch()
}

async function uploadPhotoDirect(memberId: string, file: File) {
  const fd = new FormData(); fd.append('file', file)
  await api.post(`/admin/club-members/${memberId}/photo`, fd, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

function resetForm() {
  editingId.value = null
  photoFiles.value['new'] = null
  form.value = { name: '', description: '', bio: '', isActive: true, sortOrder: 0 }
}

onMounted(fetch)
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A] text-white">
    <header class="bg-[#141414] p-4 flex items-center gap-4 border-b border-white/5">
      <button @click="router.back()" class="text-sm bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">← Volver</button>
      <h1 class="text-xl font-bold uppercase tracking-wider">Miembros del Club</h1>
    </header>

    <main class="max-w-5xl mx-auto p-6 space-y-6">
      <div class="bg-[#141414] rounded-lg border border-naranja/30 p-6 space-y-4">
        <h2 class="text-lg font-semibold border-b border-white/5 pb-3 text-naranja">
          {{ editingId ? 'Editar Miembro' : '+ Nuevo Miembro' }}
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Nombre *</label>
            <input v-model="form.name" placeholder="Nombre del miembro" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Descripción / Rol</label>
            <input v-model="form.description" placeholder="Presidente, Corredor..." class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs text-gray-400 mb-1">Biografía</label>
            <textarea v-model="form.bio" rows="3" placeholder="Breve biografía del miembro..." class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition resize-none"></textarea>
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs text-gray-400 mb-1">Foto</label>
            <ImageDropZone @select="onPhotoSelect('new', $event)" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Orden</label>
            <input v-model.number="form.sortOrder" type="number" placeholder="0" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div class="flex items-end">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.isActive" type="checkbox" class="w-4 h-4 accent-[#FF5C00]" />
              <span class="text-sm">Activo</span>
            </label>
          </div>
        </div>
        <div class="flex gap-2 pt-2">
          <button @click="save" :disabled="!form.name" class="bg-[#FF5C00] text-white px-6 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
            {{ editingId ? 'Guardar cambios' : 'Crear miembro' }}
          </button>
          <button v-if="editingId" @click="resetForm" class="bg-[#222] px-4 py-2 rounded hover:bg-[#333] transition cursor-pointer">Cancelar</button>
        </div>
      </div>

      <div class="bg-[#141414] rounded-lg border border-white/5 overflow-hidden">
        <table class="w-full text-left text-sm">
          <thead class="bg-[#1a1a1a] text-gray-400">
            <tr>
              <th class="p-3 font-medium">Nombre</th>
              <th class="p-3 font-medium">Foto</th>
              <th class="p-3 font-medium">Rol</th>
              <th class="p-3 font-medium">Bio</th>
              <th class="p-3 font-medium">Activo</th>
              <th class="p-3 text-right font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in members" :key="m.id" :class="['border-t border-white/5 transition', editingId === m.id ? 'bg-naranja/10 border-l-2 border-l-naranja' : 'hover:bg-[#1a1a1a]']">
              <td class="p-3 font-medium">{{ m.name }}</td>
              <td class="p-3">
                <div class="flex items-center gap-2">
                  <img v-if="m.photoUrl" :src="m.photoUrl" class="h-8 w-8 object-cover rounded-full bg-white/5 shrink-0" :alt="m.name" />
                  <ImageDropZone
                    compact
                    :label="m.photoUrl ? 'Cambiar' : 'Subir foto'"
                    class="min-w-[140px]"
                    @select="onPhotoSelect(m.id, $event)"
                  />
                </div>
              </td>
              <td class="p-3 text-gray-400 text-xs">{{ m.description || '—' }}</td>
              <td class="p-3 text-gray-400 text-xs max-w-[200px] truncate">{{ m.bio || '—' }}</td>
              <td class="p-3">
                <span v-if="m.isActive" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-500/10 text-green-400">Sí</span>
                <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-500/10 text-gray-400">No</span>
              </td>
              <td class="p-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="edit(m)" class="text-[#FF5C00] hover:text-[#FFD600] text-sm transition cursor-pointer">Editar</button>
                  <span class="text-white/10">|</span>
                  <button @click="remove(m.id)" class="text-red-400 hover:text-red-300 text-sm transition cursor-pointer">Eliminar</button>
                </div>
              </td>
            </tr>
            <tr v-if="!members.length">
              <td colspan="6" class="p-6 text-center text-gray-500">No hay miembros registrados</td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</template>
