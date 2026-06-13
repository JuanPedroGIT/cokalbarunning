<script setup lang="ts">
import { useAuthStore } from '@/stores/auth.store'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

function logout() {
  auth.logout()
  router.push('/admin/login')
}

const menu = [
  { label: 'Ediciones', route: '/admin/editions', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', adminOnly: true },
  // { label: 'Importar Resultados', route: '/admin/results', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', adminOnly: true },
  { label: 'Galeria', route: '/admin/photos', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
  { label: 'Blog', route: '/admin/posts', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
  { label: 'Patrocinadores', route: '/admin/sponsors', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', adminOnly: true },
  { label: 'Miembros del Club', route: '/admin/club-members', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', adminOnly: true },
  { label: 'Usuarios', route: '/admin/users', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', adminOnly: true },
]
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A]">
    <header class="bg-[#141414] text-white p-4 flex justify-between items-center border-b border-white/5">
      <h1 class="text-xl font-bold uppercase tracking-wider">Panel de Administracion</h1>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-400">{{ auth.user?.email }}</span>
        <button @click="logout" class="text-sm bg-[#FF5C00] px-3 py-1.5 rounded hover:bg-[#FFD600] hover:text-[#0A0A0A] transition font-medium">
          Cerrar sesion
        </button>
      </div>
    </header>

    <main class="max-w-6xl mx-auto p-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <router-link
          v-for="item in menu.filter(m => !m.adminOnly || auth.hasRole('ROLE_ADMIN'))"
          :key="item.route"
          :to="item.route"
          class="bg-[#141414] rounded-lg border border-white/5 p-6 hover:border-[#FF5C00]/50 hover:bg-[#1a1a1a] transition flex items-center gap-4 group"
        >
          <div class="w-12 h-12 rounded-lg bg-[#FF5C00]/10 flex items-center justify-center group-hover:bg-[#FF5C00]/20 transition">
            <svg class="w-6 h-6 text-[#FF5C00]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
            </svg>
          </div>
          <span class="text-lg font-semibold text-gray-200 group-hover:text-white">{{ item.label }}</span>
        </router-link>
      </div>
    </main>
  </div>
</template>
