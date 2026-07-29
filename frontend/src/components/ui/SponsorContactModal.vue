<script setup lang="ts">
import { ref, reactive } from 'vue'
import api from '@/services/api.service'

const emit = defineEmits<{ close: [] }>()

const form = reactive({ name: '', company: '', email: '', phone: '', message: '' })
const sending = ref(false)
const sent = ref(false)
const error = ref<string | null>(null)

const valid = () => form.name.trim() && form.company.trim() && form.email.trim() && form.phone.trim()

async function submit() {
  if (!valid()) return
  sending.value = true
  error.value = null
  try {
    await api.post('/sponsorship/contact', { ...form })
    sent.value = true
  } catch (err: any) {
    error.value = err.response?.data?.error ?? 'Error al enviar. Inténtalo de nuevo.'
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click.self="emit('close')">
    <div class="bg-[#0A0A0A] border border-white/10 rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6 space-y-5">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-white" style="font-family: 'Barlow Condensed', sans-serif;">
            {{ sent ? '¡Gracias!' : 'Conviértete en patrocinador' }}
          </h2>
          <button @click="emit('close')" class="text-gray-400 hover:text-white text-2xl leading-none cursor-pointer">&times;</button>
        </div>

        <template v-if="sent">
          <p class="text-gray-400 text-sm">Hemos recibido tu solicitud. Nos pondremos en contacto contigo pronto para hablar sobre las oportunidades de patrocinio.</p>
          <button @click="emit('close')" class="w-full bg-[#FF5C00] text-white py-3 rounded font-bold text-sm tracking-widest uppercase hover:bg-[#FFD600] hover:text-[#0A0A0A] transition cursor-pointer" style="font-family: 'Barlow Condensed', sans-serif;">
            Cerrar
          </button>
        </template>

        <template v-else>
          <div v-if="error" class="bg-red-900/20 border border-red-500/30 text-red-400 text-sm rounded p-3">{{ error }}</div>

          <div class="space-y-4">
            <div>
              <label class="block text-xs text-gray-400 mb-1">Nombre *</label>
              <input v-model="form.name" type="text" class="w-full bg-white/5 border border-white/10 rounded px-3 py-2.5 text-white focus:border-[#FF5C00] focus:outline-none transition" placeholder="Tu nombre" />
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Empresa *</label>
              <input v-model="form.company" type="text" class="w-full bg-white/5 border border-white/10 rounded px-3 py-2.5 text-white focus:border-[#FF5C00] focus:outline-none transition" placeholder="Nombre de tu empresa" />
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Email *</label>
              <input v-model="form.email" type="email" class="w-full bg-white/5 border border-white/10 rounded px-3 py-2.5 text-white focus:border-[#FF5C00] focus:outline-none transition" placeholder="tu@email.com" />
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Teléfono *</label>
              <input v-model="form.phone" type="tel" class="w-full bg-white/5 border border-white/10 rounded px-3 py-2.5 text-white focus:border-[#FF5C00] focus:outline-none transition" placeholder="Tu teléfono" />
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Mensaje</label>
              <textarea v-model="form.message" rows="3" class="w-full bg-white/5 border border-white/10 rounded px-3 py-2.5 text-white focus:border-[#FF5C00] focus:outline-none transition" placeholder="Cuéntanos tu interés en patrocinar la carrera..."></textarea>
            </div>
          </div>

          <button @click="submit" :disabled="!valid() || sending" class="w-full bg-[#FF5C00] text-white py-3 rounded font-bold text-sm tracking-widest uppercase hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer" style="font-family: 'Barlow Condensed', sans-serif;">
            {{ sending ? 'Enviando...' : 'Enviar solicitud' }}
          </button>
        </template>
      </div>
    </div>
  </div>
</template>
