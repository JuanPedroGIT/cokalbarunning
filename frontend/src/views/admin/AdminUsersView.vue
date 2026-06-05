<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'

interface AdminUser {
  id: string
  email: string
  firstName: string
  lastName: string
  roles: string[]
}

const users = ref<AdminUser[]>([])
const form = ref({ email: '', password: '', firstName: '', lastName: '', roles: ['ROLE_EDITOR'] })
const router = useRouter()
const editingId = ref<string | null>(null)

async function fetch() {
  const res = await api.get('/admin/users')
  users.value = res.data.data
}

async function save() {
  if (editingId.value) {
    await api.put(`/admin/users/${editingId.value}`, form.value)
  } else {
    await api.post('/admin/users', form.value)
  }
  resetForm()
  await fetch()
}

function edit(u: AdminUser) {
  editingId.value = u.id
  form.value = { email: u.email, password: '', firstName: u.firstName, lastName: u.lastName, roles: [...u.roles] }
}

async function remove(id: string) {
  if (!confirm('Eliminar usuario?')) return
  await api.delete(`/admin/users/${id}`)
  await fetch()
}

function toggleRole(role: string) {
  const idx = form.value.roles.indexOf(role)
  if (idx >= 0) form.value.roles.splice(idx, 1)
  else form.value.roles.push(role)
}

function resetForm() {
  editingId.value = null
  form.value = { email: '', password: '', firstName: '', lastName: '', roles: ['ROLE_EDITOR'] }
}

onMounted(fetch)
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A] text-white">
    <header class="bg-[#141414] p-4 flex items-center gap-4 border-b border-white/5">
      <button @click="router.back()" class="text-sm bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">← Volver</button>
      <h1 class="text-xl font-bold uppercase tracking-wider">Usuarios</h1>
    </header>

    <main class="max-w-5xl mx-auto p-6 space-y-6">
      <div class="bg-[#141414] rounded-lg border border-naranja/30 p-6 space-y-4">
        <h2 class="text-lg font-semibold border-b border-white/5 pb-3 text-naranja">
          {{ editingId ? 'Editar' : '+ Nuevo' }} Usuario
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Email *</label>
            <input v-model="form.email" placeholder="usuario@email.com" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">{{ editingId ? 'Nueva contraseña (dejar vacío = no cambiar)' : 'Contraseña *' }}</label>
            <input v-model="form.password" type="password" placeholder="••••••••" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Nombre</label>
            <input v-model="form.firstName" placeholder="Nombre" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Apellidos</label>
            <input v-model="form.lastName" placeholder="Apellidos" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1">Roles</label>
          <div class="flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer text-sm">
              <input type="checkbox" :checked="form.roles.includes('ROLE_ADMIN')" @change="toggleRole('ROLE_ADMIN')" class="w-4 h-4 accent-[#FF5C00]" />
              Admin
            </label>
            <label class="flex items-center gap-2 cursor-pointer text-sm">
              <input type="checkbox" :checked="form.roles.includes('ROLE_EDITOR')" @change="toggleRole('ROLE_EDITOR')" class="w-4 h-4 accent-[#FF5C00]" />
              Editor
            </label>
          </div>
        </div>
        <div class="flex gap-2 pt-2">
          <button @click="save" :disabled="!form.email || (!editingId && !form.password)" class="bg-[#FF5C00] text-white px-6 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
            {{ editingId ? 'Guardar cambios' : 'Crear usuario' }}
          </button>
          <button v-if="editingId" @click="resetForm" class="bg-[#222] px-4 py-2 rounded hover:bg-[#333] transition cursor-pointer">Cancelar</button>
        </div>
      </div>

      <div class="bg-[#141414] rounded-lg border border-white/5 overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[480px]">
          <thead class="bg-[#1a1a1a] text-gray-400">
            <tr>
              <th class="p-2 md:p-3 font-medium">Email</th>
              <th class="p-2 md:p-3 font-medium">Nombre</th>
              <th class="p-2 md:p-3 font-medium">Roles</th>
              <th class="p-2 md:p-3 text-right font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in users" :key="u.id" :class="['border-t border-white/5 transition', editingId === u.id ? 'bg-naranja/10 border-l-2 border-l-naranja' : 'hover:bg-[#1a1a1a]']">
              <td class="p-2 md:p-3 font-medium">{{ u.email }}</td>
              <td class="p-2 md:p-3 text-gray-400">{{ u.firstName }} {{ u.lastName }}</td>
              <td class="p-2 md:p-3">
                <span v-for="r in u.roles" :key="r" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mr-1" :class="r === 'ROLE_ADMIN' ? 'bg-naranja/20 text-naranja' : r === 'ROLE_EDITOR' ? 'bg-blue-500/20 text-blue-400' : 'bg-gray-500/20 text-gray-400'">{{ r.replace('ROLE_', '') }}</span>
              </td>
              <td class="p-2 md:p-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="edit(u)" class="text-[#FF5C00] hover:text-[#FFD600] text-sm transition cursor-pointer">Editar</button>
                  <span class="text-white/10">|</span>
                  <button @click="remove(u.id)" class="text-red-400 hover:text-red-300 text-sm transition cursor-pointer">Eliminar</button>
                </div>
              </td>
            </tr>
            <tr v-if="!users.length">
              <td colspan="4" class="p-6 text-center text-gray-500">No hay usuarios</td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</template>
