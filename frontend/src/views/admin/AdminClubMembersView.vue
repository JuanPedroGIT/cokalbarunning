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
  userId: string | null
}

interface AdminUser {
  id: string
  email: string
  firstName: string
  lastName: string
}

const members = ref<ClubMember[]>([])
const users = ref<AdminUser[]>([])
const form = ref<Partial<ClubMember>>({ name: '', description: '', bio: '', isActive: true, sortOrder: 0, userId: null })
const router = useRouter()
const editingId = ref<string | null>(null)
const photoFiles = ref<Record<string, File | null>>({})
const draggingId = ref<string | null>(null)

async function fetch() {
  const [membersRes, usersRes] = await Promise.all([
    api.get('/admin/club-members'),
    api.get('/admin/users'),
  ])
  members.value = membersRes.data.data
  users.value = usersRes.data.data
}

async function save() {
  const payload: Record<string, any> = { ...form.value }
  if (editingId.value) {
    await api.put(`/admin/club-members/${editingId.value}`, payload)
  } else {
    const res = await api.post('/admin/club-members', payload)
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

const photoInputs = ref<Record<string, HTMLInputElement>>({})

function triggerPhotoInput(memberId: string) {
  photoInputs.value[memberId]?.click()
}

function onPhotoDrop(memberId: string, e: DragEvent) {
  draggingId.value = null
  const file = e.dataTransfer?.files[0]
  if (file && file.type.startsWith('image/')) {
    uploadPhotoDirect(memberId, file)
  }
}

function onPhotoFileChange(memberId: string, e: Event) {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) uploadPhotoDirect(memberId, file)
}

async function uploadPhotoDirect(memberId: string, file: File) {
  const fd = new FormData()
  fd.append('file', file)
  await api.post(`/admin/club-members/${memberId}/photo`, fd, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  await fetch()
}

function onPhotoSelect(memberId: string, file: File) {
  if (memberId !== 'new') {
    uploadPhotoDirect(memberId, file)
  } else {
    photoFiles.value['new'] = file
  }
}

function resetForm() {
  editingId.value = null
  photoFiles.value['new'] = null
  form.value = { name: '', description: '', bio: '', isActive: true, sortOrder: 0, userId: null }
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
            <label class="block text-xs text-gray-400 mb-1">Usuario asignado</label>
            <select v-model="form.userId" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition">
              <option :value="null">Sin usuario</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.email }} ({{ u.firstName }} {{ u.lastName }})</option>
            </select>
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
              <th class="p-3 font-medium">Usuario</th>
              <th class="p-3 font-medium">Activo</th>
              <th class="p-3 text-right font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in members" :key="m.id" :class="['border-t border-white/5 transition', editingId === m.id ? 'bg-naranja/10 border-l-2 border-l-naranja' : 'hover:bg-[#1a1a1a]']">
              <td class="p-3 font-medium">{{ m.name }}</td>
              <td class="p-3">
                <div
                  class="relative h-10 w-10 rounded-full overflow-hidden cursor-pointer group shrink-0"
                  @click="triggerPhotoInput(m.id)"
                  @drop.prevent="onPhotoDrop(m.id, $event)"
                  @dragover.prevent="draggingId = m.id"
                  @dragleave="draggingId = null"
                >
                  <img
                    v-if="m.photoUrl"
                    :src="m.photoUrl"
                    class="h-full w-full object-cover"
                    :class="draggingId === m.id ? 'opacity-40' : ''"
                    :alt="m.name"
                  />
                  <div
                    v-else
                    class="h-full w-full bg-white/5 flex items-center justify-center text-white/15 text-xs"
                    :class="draggingId === m.id ? 'opacity-40' : ''"
                  >
                    👤
                  </div>
                  <div
                    class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity"
                    :class="draggingId === m.id ? 'opacity-100' : ''"
                  >
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                    </svg>
                  </div>
                  <input
                    :ref="(el) => { if (el) photoInputs[m.id] = el as HTMLInputElement }"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="onPhotoFileChange(m.id, $event)"
                  />
                </div>
              </td>
              <td class="p-3 text-gray-400 text-xs">{{ m.description || '—' }}</td>
              <td class="p-3 text-gray-400 text-xs max-w-[200px] truncate">{{ m.bio || '—' }}</td>
              <td class="p-3 text-gray-400 text-xs">
                <span v-if="m.userId">
                  {{ users.find(u => u.id === m.userId)?.email || m.userId }}
                </span>
                <span v-else>—</span>
              </td>
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
              <td colspan="7" class="p-6 text-center text-gray-500">No hay miembros registrados</td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</template>
