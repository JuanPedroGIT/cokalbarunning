<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'

interface Contact {
  id: string; name: string; company: string; email: string; phone: string
  message: string | null; createdAt: string
}

const router = useRouter()
const contacts = ref<Contact[]>([])
const loading = ref(false)

async function fetchContacts() {
  loading.value = true
  try {
    const res = await api.get('/admin/sponsorship/contacts')
    contacts.value = res.data.data
  } catch { /* */ }
  finally { loading.value = false }
}

onMounted(fetchContacts)
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A] text-white">
    <header class="bg-[#141414] p-4 flex items-center gap-4 border-b border-white/5">
      <button @click="router.back()" class="text-sm bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">← Volver</button>
      <h1 class="text-xl font-bold uppercase tracking-wider">Solicitudes de Patrocinio</h1>
    </header>

    <main class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">
      <div class="bg-[#141414] rounded-lg border border-white/5 p-4 md:p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-naranja">Contactos recibidos</h2>
          <button @click="fetchContacts" :disabled="loading" class="text-xs bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">Recargar</button>
        </div>

        <div v-if="loading" class="text-center text-gray-400 py-4">Cargando...</div>
        <div v-else-if="contacts.length === 0" class="text-center text-gray-500 py-4">No hay solicitudes de patrocinio todavía.</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-[#1a1a1a] text-gray-400">
              <tr>
                <th class="p-2 md:p-3">Nombre</th>
                <th class="p-2 md:p-3">Empresa</th>
                <th class="p-2 md:p-3">Email</th>
                <th class="p-2 md:p-3">Teléfono</th>
                <th class="p-2 md:p-3">Mensaje</th>
                <th class="p-2 md:p-3">Fecha</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in contacts" :key="c.id" class="border-t border-white/5 hover:bg-[#1a1a1a]">
                <td class="p-2 md:p-3">{{ c.name }}</td>
                <td class="p-2 md:p-3">{{ c.company }}</td>
                <td class="p-2 md:p-3 text-gray-400">{{ c.email }}</td>
                <td class="p-2 md:p-3 text-gray-400">{{ c.phone }}</td>
                <td class="p-2 md:p-3 text-gray-400 max-w-[200px] truncate">{{ c.message ?? '—' }}</td>
                <td class="p-2 md:p-3 text-gray-400 text-xs">{{ c.createdAt?.substring(0, 16).replace('T', ' ') ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</template>
