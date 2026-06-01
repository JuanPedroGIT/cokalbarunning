<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const authStore = useAuthStore()
const router = useRouter()

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    router.push('/admin')
  } catch (e) {
    error.value = 'Credenciales incorrectas'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="relative z-10 min-h-screen flex items-center justify-center px-6 bg-[#0A0A0A]">
    <div class="w-full max-w-md">
      <div class="text-center mb-10">
        <div class="font-black text-3xl tracking-widest text-white uppercase mb-2">
          COKALBA <span class="text-[#FF5C00]">RUNNING</span>
        </div>
        <div class="text-gray-500 text-sm">Panel de Administracion</div>
      </div>

      <form @submit.prevent="handleLogin" class="bg-[#141414] p-8 rounded-lg border border-white/5">
        <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-400 text-sm p-3 mb-6 rounded">{{ error }}</div>

        <div class="mb-5">
          <label class="block font-semibold text-sm tracking-wider uppercase text-gray-400 mb-2">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full bg-[#0A0A0A] border border-white/10 rounded px-4 py-3 text-white focus:border-[#FF5C00] focus:outline-none transition"
          />
        </div>

        <div class="mb-6">
          <label class="block font-semibold text-sm tracking-wider uppercase text-gray-400 mb-2">Contrasena</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full bg-[#0A0A0A] border border-white/10 rounded px-4 py-3 text-white focus:border-[#FF5C00] focus:outline-none transition"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full font-bold text-base tracking-widest uppercase bg-[#FF5C00] text-white py-3 rounded hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50"
        >
          {{ loading ? 'Entrando...' : 'Entrar' }}
        </button>
      </form>
    </div>
  </section>
</template>