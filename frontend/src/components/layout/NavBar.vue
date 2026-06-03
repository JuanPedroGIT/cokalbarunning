<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

const isOpen = ref(false)
const auth = useAuthStore()
const router = useRouter()

function toggle() { isOpen.value = !isOpen.value }
function close() { isOpen.value = false }
function logout() { auth.logout(); close(); router.push('/') }
</script>

<template>
  <nav class="fixed top-0 left-0 w-full z-50 bg-[#0A0A0A]/90 backdrop-blur-md border-b border-white/5">
    <div class="px-6 py-4 flex items-center justify-between">
      <!-- Logo -->
      <RouterLink to="/" class="font-black text-xl tracking-widest text-white uppercase no-underline shrink-0"
        style="font-family: 'Barlow Condensed', sans-serif;"
      >
        COKALBA <span class="text-[#FF5C00]">RUNNING</span>
      </RouterLink>

      <!-- Desktop menu -->
      <ul class="hidden md:flex gap-8 list-none">
        <li><RouterLink to="/" class="font-semibold text-sm tracking-widest uppercase text-gray-400 hover:text-[#FF5C00] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">Inicio</RouterLink></li>
        <li><RouterLink to="/carrera" class="font-semibold text-sm tracking-widest uppercase text-gray-400 hover:text-[#FF5C00] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">La Carrera</RouterLink></li>
        <li><RouterLink to="/ediciones" class="font-semibold text-sm tracking-widest uppercase text-gray-400 hover:text-[#FF5C00] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">Ediciones</RouterLink></li>
        <li><RouterLink to="/galeria" class="font-semibold text-sm tracking-widest uppercase text-gray-400 hover:text-[#FF5C00] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">Galeria</RouterLink></li>
        <li><RouterLink to="/blog" class="font-semibold text-sm tracking-widest uppercase text-gray-400 hover:text-[#FF5C00] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">Noticias</RouterLink></li>
      </ul>

      <!-- Desktop CTA -->
      <div class="hidden md:flex items-center gap-3">
        <template v-if="auth.isAuthenticated">
          <RouterLink to="/perfil" class="font-semibold text-xs tracking-widest uppercase text-gray-400 hover:text-[#FF5C00] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">Mi Perfil</RouterLink>
          <RouterLink to="/admin" class="font-semibold text-xs tracking-widest uppercase text-gray-400 hover:text-[#FF5C00] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">Admin</RouterLink>
          <button @click="logout" class="font-bold text-xs tracking-widest uppercase text-red-400 hover:text-red-300 transition-colors cursor-pointer border border-red-400/30 px-3 py-1.5" style="font-family: 'Barlow Condensed', sans-serif;">Salir</button>
        </template>
        <a v-else href="https://www.deporticket.com/web-evento/13254-ix-carrera-solidaria-un-nuevo-impulso" target="_blank"
          class="font-bold text-sm tracking-widest uppercase bg-[#FF5C00] text-white px-4 py-2 hover:bg-[#FFD600] hover:text-[#0A0A0A] transition-colors"
          style="font-family: 'Barlow Condensed', sans-serif;"
        >Inscribete</a>
      </div>

      <!-- Hamburger button -->
      <button @click="toggle" class="md:hidden text-white p-1" aria-label="Menu">
        <svg v-if="!isOpen" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
        <svg v-else class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Mobile menu -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div v-if="isOpen" class="md:hidden bg-[#0A0A0A] border-t border-white/5">
        <ul class="flex flex-col px-6 py-4 gap-4 list-none">
          <li><RouterLink @click="close" to="/" class="block font-semibold text-sm tracking-widest uppercase text-gray-400 hover:text-[#FF5C00] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">Inicio</RouterLink></li>
          <li><RouterLink @click="close" to="/carrera" class="block font-semibold text-sm tracking-widest uppercase text-gray-400 hover:text-[#FF5C00] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">La Carrera</RouterLink></li>
          <li><RouterLink @click="close" to="/ediciones" class="block font-semibold text-sm tracking-widest uppercase text-gray-400 hover:text-[#FF5C00] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">Ediciones</RouterLink></li>
          <li><RouterLink @click="close" to="/galeria" class="block font-semibold text-sm tracking-widest uppercase text-gray-400 hover:text-[#FF5C00] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">Galeria</RouterLink></li>
          <li><RouterLink @click="close" to="/blog" class="block font-semibold text-sm tracking-widest uppercase text-gray-400 hover:text-[#FF5C00] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">Noticias</RouterLink></li>
        </ul>
        <div class="px-6 pb-5 flex flex-col gap-3">
          <template v-if="auth.isAuthenticated">
            <RouterLink @click="close" to="/perfil" class="block w-full text-center font-bold text-sm tracking-widest uppercase text-[#FF5C00] border border-[#FF5C00]/30 px-4 py-3 hover:bg-[#FF5C00] hover:text-[#0A0A0A] transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">Mi Perfil</RouterLink>
            <RouterLink @click="close" to="/admin" class="block w-full text-center font-bold text-sm tracking-widest uppercase text-gray-400 border border-white/10 px-4 py-3 hover:bg-white/5 transition-colors" style="font-family: 'Barlow Condensed', sans-serif;">Admin</RouterLink>
            <button @click="logout" class="block w-full text-center font-bold text-sm tracking-widest uppercase text-red-400 border border-red-400/30 px-4 py-3 hover:bg-red-400/10 transition-colors cursor-pointer" style="font-family: 'Barlow Condensed', sans-serif;">Cerrar sesion</button>
          </template>
          <a v-else href="https://www.deporticket.com/web-evento/13254-ix-carrera-solidaria-un-nuevo-impulso" target="_blank"
            class="block w-full text-center font-bold text-sm tracking-widest uppercase bg-[#FF5C00] text-white px-4 py-3 hover:bg-[#FFD600] hover:text-[#0A0A0A] transition-colors"
            style="font-family: 'Barlow Condensed', sans-serif;"
          >Inscribete</a>
        </div>
      </div>
    </transition>
  </nav>
</template>